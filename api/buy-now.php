<?php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

/**
 * Get exchange rate from database
 */
function getExchangeRate()
{
    global $pdo;
    static $rate = null;

    if ($rate === null) {
        try {
            $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key='exchange_rate'");
            $rate = floatval($stmt->fetchColumn() ?? 25000);
        } catch (Exception $e) {
            $rate = 25000;
        }
    }

    return $rate;
}

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $product_id = $data['product_id'] ?? 0;
    $quantity = $data['quantity'] ?? 1;
    $variant_id = $data['variant_id'] ?? null;
    $voucher_code = $data['voucher_code'] ?? null;

    // Get product
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND is_active = 1");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        throw new Exception('Sản phẩm không tồn tại');
    }

    // Get variant if specified
    $variant = null;
    if ($variant_id) {
        $stmt = $pdo->prepare("SELECT * FROM product_variants WHERE id = ? AND product_id = ? AND is_active = 1");
        $stmt->execute([$variant_id, $product_id]);
        $variant = $stmt->fetch();

        if (!$variant) {
            throw new Exception('Gói sản phẩm không tồn tại');
        }
    }

    // Calculate price
    $currency = $_COOKIE['currency'] ?? 'VND';

    if ($variant) {
        $price_vnd = $variant['final_price_vnd'] ?? $variant['price_vnd'];
        $price_usd = $variant['final_price_usd'] ?? $variant['price_usd'];
        $stock = $variant['stock'];
    } else {
        $price_vnd = $product['final_price_vnd'];
        $price_usd = $product['final_price_usd'];
        $stock = $product['stock'];
    }

    // Check stock
    if ($stock < $quantity) {
        throw new Exception('Không đủ hàng trong kho');
    }

    $customer_info = $data['customer_info'] ?? '';

    // Check if product requires customer info
    $requires_info = $product['requires_customer_info'] ?? 0;
    $status = 'completed';

    if ($requires_info) {
        if (empty($customer_info)) {
            throw new Exception('Vui lòng nhập thông tin yêu cầu (VD: Email/Facebook...)');
        }
        $status = 'pending';
    }

    // Calculate total
    $total_vnd = $price_vnd * $quantity;
    $total_usd = $price_usd * $quantity;

    // Apply voucher if provided
    $discount_amount = 0;
    $voucher_info = null;

    if ($voucher_code) {
        $stmt = $pdo->prepare("
            SELECT * FROM vouchers 
            WHERE code = ? AND is_active = 1 
            AND (usage_limit = 0 OR used_count < usage_limit)
            AND (valid_from IS NULL OR valid_from <= NOW())
            AND (valid_until IS NULL OR valid_until >= NOW())
        ");
        $stmt->execute([strtoupper($voucher_code)]);
        $voucher = $stmt->fetch();

        if ($voucher && $total_vnd >= $voucher['min_amount']) {
            // Check applicable_products
            $can_use = true;
            if (!empty($voucher['applicable_products'])) {
                $applicable = json_decode($voucher['applicable_products'], true);
                if (is_array($applicable) && count($applicable) > 0) {
                    if (!in_array($product_id, $applicable) && !in_array((string) $product_id, $applicable)) {
                        $can_use = false;
                    }
                }
            }

            if ($can_use) {
                if ($voucher['discount_type'] === 'percentage') {
                    $discount_amount = ($total_vnd * $voucher['discount_value']) / 100;
                    if ($voucher['max_discount'] > 0) {
                        $discount_amount = min($discount_amount, $voucher['max_discount']);
                    }
                } else {
                    $discount_amount = $voucher['discount_value'];
                }

                $total_vnd -= $discount_amount;
                $voucher_info = [
                    'id' => $voucher['id'],
                    'code' => $voucher['code'],
                    'discount_amount' => $discount_amount,
                    'type' => $voucher['discount_type']
                ];
            }
        }
    }

    // Get user balance
    $user = getCurrentUser();
    $amount = $currency === 'VND' ? $total_vnd : $total_usd;

    // Tiền thực tế trừ từ balance_vnd (luôn là VND)
    $amount_vnd = $currency === 'VND' ? $total_vnd : ($total_usd * getExchangeRate());

    // Check balance
    if ($user['balance_vnd'] < $amount_vnd) {
        echo json_encode([
            'success' => false,
            'message' => 'Số dư không đủ. Vui lòng nạp thêm tiền!',
            'insufficient_balance' => true,
            'balance' => $user['balance_vnd'],
            'required' => $amount_vnd,
            'currency' => 'VND'
        ]);
        exit;
    }

    // Process payment
    $pdo->beginTransaction();

    // Create order
    $order_id = generateShortId();
    $order_number = $order_id;

    // Format note if customer info exists
    $order_note = '';
    if ($requires_info && $customer_info) {
        $label = $product['customer_info_label'] ?: 'Thông tin khách hàng';
        $order_note = "[$label]: $customer_info";
    }

    $stmt = $pdo->prepare("
        INSERT INTO orders (id, user_id, order_number, total_amount_vnd, total_amount_usd, currency, status, payment_status, payment_method, ip_address, note)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'paid', 'balance', ?, ?)
    ");
    $stmt->execute([
        $order_id,
        $_SESSION['user_id'],
        $order_number,
        $total_vnd,
        $total_usd,
        $currency,
        $status,
        $_SERVER['REMOTE_ADDR'],
        $order_note
    ]);

    // Create order item with account data
    $account_data = null;
    if ($variant) {
        // Get account from variant
        $stmt = $pdo->prepare("SELECT account_data FROM product_variants WHERE id = ? FOR UPDATE");
        $stmt->execute([$variant_id]);
        $account_data = $stmt->fetchColumn();

        // Update variant stock
        $stmt = $pdo->prepare("UPDATE product_variants SET stock = stock - ? WHERE id = ?");
        $stmt->execute([$quantity, $variant_id]);
    } else {
        // Update product stock
        $stmt = $pdo->prepare("UPDATE products SET stock = stock - ?, sold_count = sold_count + ? WHERE id = ?");
        $stmt->execute([$quantity, $quantity, $product_id]);
    }

    $item_id = generateShortId();
    $stmt = $pdo->prepare("
        INSERT INTO order_items (id, order_id, product_id, variant_id, product_name, product_image, quantity, price, price_vnd, price_usd, subtotal_vnd, subtotal_usd, account_data, customer_info)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $item_id,
        $order_id,
        $product_id,
        $variant_id, // Add variant_id
        $product['name'],
        $product['image'],
        $quantity,
        $amount,
        $price_vnd,
        $price_usd,
        $total_vnd,
        $total_usd,
        $account_data,
        $customer_info // Add customer_info
    ]);

    // Deduct balance (luôn trừ balance_vnd)
    $stmt = $pdo->prepare("UPDATE users SET balance_vnd = balance_vnd - ? WHERE id = ?");
    $stmt->execute([$amount_vnd, $_SESSION['user_id']]);

    // Calculate new balance
    $new_balance_vnd = $user['balance_vnd'] - $amount_vnd;

    // Create balance transaction (Required for user history)
    $balance_trans_id = generateShortId();
    $stmt = $pdo->prepare("
        INSERT INTO balance_transactions (id, user_id, type, currency, amount, balance_before, balance_after, note, ip_address)
        VALUES (?, ?, 'purchase', 'VND', ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $balance_trans_id,
        $_SESSION['user_id'],
        $amount_vnd,
        $user['balance_vnd'],
        $new_balance_vnd,
        'Thanh toán đơn hàng ' . $order_number,
        $_SERVER['REMOTE_ADDR']
    ]);

    // Create transaction (Legacy/Detailed log - Optional: duplicates balance_transactions)
    // Note: balance_usd fields are deprecated, always set to 0
    $trans_id = generateShortId();
    $stmt = $pdo->prepare("
        INSERT INTO transactions (id, user_id, order_id, type, amount_vnd, amount_usd, currency, balance_before_vnd, balance_after_vnd, balance_before_usd, balance_after_usd, description, status)
        VALUES (?, ?, ?, 'purchase', ?, ?, ?, ?, ?, 0, 0, ?, 'completed')
    ");
    $stmt->execute([
        $trans_id,
        $_SESSION['user_id'],
        $order_id,
        $total_vnd,
        $total_usd,
        $currency,
        $user['balance_vnd'],
        $new_balance_vnd,
        'Thanh toán đơn hàng ' . $order_number
    ]);

    // Increment voucher usage
    if ($voucher_info && isset($voucher_info['id'])) {
        $stmt = $pdo->prepare("UPDATE vouchers SET used_count = used_count + 1 WHERE id = ?");
        $stmt->execute([$voucher_info['id']]);
    }

    $pdo->commit();

    // Log order activity
    $product_display = $product['name'] . ($variant ? ' (' . $variant['name'] . ')' : '');
    logActivity(
        'order',
        'Mua hàng thành công',
        'Đơn hàng ' . $order_number . ' - ' . $product_display . ' x' . $quantity . ' - Tổng: ' . number_format($amount) . ($currency === 'USD' ? '$' : 'đ'),
        '',
        json_encode(['order_id' => $order_id, 'order_number' => $order_number, 'product_id' => $product_id, 'amount' => $amount, 'currency' => $currency]),
        $_SESSION['user_id']
    );

    // Auto-deliver products that don't require customer info
    require_once __DIR__ . '/../includes/OrderDeliveryHandler.php';
    $deliveryHandler = new OrderDeliveryHandler($pdo);
    $deliveryHandler->deliverAutoItems($order_id);

    // Update order status based on all items delivery status
    $deliveryHandler->updateOrderStatus($order_id);

    // Get final status
    $stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $finalOrder = $stmt->fetch();
    $status = $finalOrder['status'] ?? 'pending';

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Thanh toán thành công!',
        'order_number' => $order_number,
        'order_id' => $order_id,
        'amount' => $amount,
        'currency' => $currency,
        'new_balance' => $new_balance_vnd,
        'voucher' => $voucher_info
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
