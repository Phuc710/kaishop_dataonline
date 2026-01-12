<?php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}


$data = json_decode(file_get_contents('php://input'), true);
$items = $data['items'] ?? [];
$currency = $data['currency'] ?? 'VND';
$voucher_code = $data['voucher_code'] ?? '';
$discount_amount = floatval($data['discount_amount'] ?? 0);
$direct_checkout = $data['direct_checkout'] ?? false;

// Get exchange rate from database
$exchange_rate = 25000;
try {
    $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key='exchange_rate'");
    $exchange_rate = floatval($stmt->fetchColumn() ?? 25000);
} catch (Exception $e) {
}

if (empty($items)) {
    echo json_encode(['success' => false, 'message' => 'Giỏ hàng trống']);
    exit;
}


try {
    $pdo->beginTransaction();
    $user = getCurrentUser();

    // Get product info for each item (check requires_customer_info per item)
    $items_info = [];
    foreach ($items as $idx => $item) {
        $stmt = $pdo->prepare("SELECT id, requires_customer_info, product_type FROM products WHERE id = ?");
        $stmt->execute([$item['product_id']]);
        $prod = $stmt->fetch();
        $items[$idx]['requires_customer_info'] = $prod ? ($prod['requires_customer_info'] == 1) : false;
        $items[$idx]['product_type'] = $prod ? $prod['product_type'] : 'account';
    }

    // Tính tổng tiền
    $total_vnd = $total_usd = 0;
    foreach ($items as $item) {
        $price_vnd = $item['final_price_vnd'] ?? $item['price_vnd'];
        $price_usd = $item['final_price_usd'] ?? $item['price_usd'];
        $total_vnd += $price_vnd * $item['quantity'];
        $total_usd += $price_usd * $item['quantity'];
    }

    // Apply voucher (nếu có)
    $voucher_id = null;
    if (!empty($voucher_code) && $discount_amount > 0) {
        $stmt = $pdo->prepare("SELECT id, applicable_products FROM vouchers WHERE code = ? AND is_active = 1");
        $stmt->execute([$voucher_code]);
        $v = $stmt->fetch();
        if ($v) {
            // Check applicable_products
            if (!empty($v['applicable_products'])) {
                $applicable = json_decode($v['applicable_products'], true);
                if (is_array($applicable) && count($applicable) > 0) {
                    $found = false;
                    foreach ($items as $item) {
                        $pid = $item['product_id'];
                        if (in_array($pid, $applicable) || in_array((string) $pid, $applicable)) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        throw new Exception('Mã voucher này không áp dụng cho sản phẩm đang thanh toán');
                    }
                }
            }

            $voucher_id = $v['id'];
            $pdo->prepare("UPDATE vouchers SET used_count = used_count + 1 WHERE id = ?")->execute([$voucher_id]);
        }
    }

    // Tính số tiền cuối
    $final_vnd = $total_vnd - $discount_amount;
    $final_usd = $total_usd - ($discount_amount / $exchange_rate); // Rough estimate

    // Số tiền thực tế trừ (luôn trừ VND)
    $amount_vnd = $final_vnd;

    // Check balance
    if ($user['balance_vnd'] < $amount_vnd) {
        throw new Exception('Số dư không đủ');
    }

    // Tạo order
    $order_id = generateShortId();
    $order_number = $order_id;

    // Check if ANY item requires customer info (for initial order status)
    $has_pending_items = false;
    foreach ($items as $item) {
        if (!empty($item['requires_customer_info'])) {
            $has_pending_items = true;
            break;
        }
    }

    // Initial order status: pending if any item needs admin, otherwise will be set later
    $order_status = 'pending';

    $stmt = $pdo->prepare("
        INSERT INTO orders (id, user_id, order_number, total_amount_vnd, total_amount_usd, currency, status, payment_status, ip_address, voucher_code, voucher_id, discount_amount)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'paid', ?, ?, ?, ?)
    ");
    $stmt->execute([
        $order_id,
        $_SESSION['user_id'],
        $order_number,
        $final_vnd,
        $final_usd,
        $currency,
        $order_status,
        $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        $voucher_code ?: null,
        $voucher_id,
        $discount_amount
    ]);

    // ===== STOCK VALIDATION: Check stock before processing =====
    foreach ($items as $item) {
        $product_id = $item['product_id'];
        $variant_id = $item['variant_id'] ?? null;
        $requested_qty = $item['quantity'];

        // Get current stock
        if ($variant_id) {
            $stmt = $pdo->prepare("SELECT stock FROM product_variants WHERE id = ? AND is_active = 1");
            $stmt->execute([$variant_id]);
            $stockData = $stmt->fetch();
            $current_stock = $stockData ? $stockData['stock'] : 0;
            $item_name = $item['name'] . ' - ' . ($item['variant_name'] ?? 'Variant');
        } else {
            $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ? AND is_active = 1");
            $stmt->execute([$product_id]);
            $stockData = $stmt->fetch();
            $current_stock = $stockData ? $stockData['stock'] : 0;
            $item_name = $item['name'];
        }

        // Validate stock
        if ($current_stock <= 0) {
            throw new Exception("Sản phẩm '{$item_name}' đã hết hàng. Vui lòng xóa khỏi giỏ hàng.");
        }

        if ($current_stock < $requested_qty) {
            throw new Exception("Sản phẩm '{$item_name}' chỉ còn {$current_stock} sản phẩm. Bạn đã chọn {$requested_qty}.");
        }
    }

    // ===== Create order items & update stock =====
    foreach ($items as $item) {
        $product_id = $item['product_id'];
        $variant_id = $item['variant_id'] ?? null;

        // Try query with ID from item
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND is_active = 1");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        // Nếu không tìm thấy, thử tìm theo tên (fallback)
        if (!$product) {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE name = ? AND is_active = 1 LIMIT 1");
            $stmt->execute([$item['name']]);
            $product = $stmt->fetch();
        }

        if (!$product) {
            throw new Exception("Sản phẩm '{$item['name']}' (ID: {$product_id}) không tồn tại. Vui lòng xóa khỏi giỏ hàng và thêm lại.");
        }

        // Dùng ID từ database
        $verified_product_id = $product['id'];
        $item_id = generateShortId();
        $price_vnd = $item['final_price_vnd'] ?? $item['price_vnd'];
        $price_usd = $item['final_price_usd'] ?? $item['price_usd'];

        // Get customer_info if exists
        $customer_info = $item['customer_info'] ?? null;

        // Insert order_items (with variant_id support)
        $stmt = $pdo->prepare("
            INSERT INTO order_items (id, order_id, product_id, variant_id, product_name, product_image, quantity, price, price_vnd, price_usd, subtotal_vnd, subtotal_usd, customer_info)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $item_id,
            $order_id,
            $verified_product_id,
            $variant_id,
            $product['name'],
            $product['image'],
            $item['quantity'],
            $price_vnd,
            $price_vnd,
            $price_usd,
            $price_vnd * $item['quantity'],
            $price_usd * $item['quantity'],
            $customer_info
        ]);

        // Update stock - check if variant or product
        if ($variant_id) {
            // Update variant stock
            $pdo->prepare("UPDATE product_variants SET stock = stock - ? WHERE id = ?")
                ->execute([$item['quantity'], $variant_id]);
            // Also update product sold_count
            $pdo->prepare("UPDATE products SET sold_count = sold_count + ? WHERE id = ?")
                ->execute([$item['quantity'], $verified_product_id]);
        } else {
            // Update product stock & sold_count
            $pdo->prepare("UPDATE products SET stock = stock - ?, sold_count = sold_count + ? WHERE id = ?")
                ->execute([$item['quantity'], $item['quantity'], $verified_product_id]);
        }
    }

    // Trừ balance
    $pdo->prepare("UPDATE users SET balance_vnd = balance_vnd - ? WHERE id = ?")
        ->execute([$amount_vnd, $_SESSION['user_id']]);

    // Tạo transaction (giống buy-now.php dòng 196-213)
    // Log transaction to balance_transactions
    $description = 'Thanh toán đơn hàng ' . $order_number;
    if ($discount_amount > 0) {
        $description .= ' (Giảm ' . number_format($discount_amount) . 'đ)';
    }

    $new_balance = $user['balance_vnd'] - $amount_vnd;
    logBalanceTransaction(
        $_SESSION['user_id'],
        'purchase',
        'VND',
        $final_vnd,
        $user['balance_vnd'],
        $new_balance,
        $description
    );

    // Log order activity
    $product_names = array_map(function ($item) {
        return $item['name'] ?? 'N/A';
    }, $items);
    logActivity(
        'order',
        'Mua hàng thành công',
        'Đơn hàng ' . $order_number . ' - ' . count($items) . ' sản phẩm: ' . implode(', ', array_slice($product_names, 0, 3)) . (count($product_names) > 3 ? '...' : '') . ' - Tổng: ' . number_format($final_vnd) . 'đ',
        '',
        json_encode(['order_id' => $order_id, 'order_number' => $order_number, 'amount' => $final_vnd, 'items' => count($items)]),
        $_SESSION['user_id']
    );

    // Xóa giỏ hàng hoặc xóa checkout session
    // Clean up cart if not direct checkout - Only remove purchased items
    if (!$direct_checkout) {
        $cart_ids = [];
        foreach ($items as $item) {
            if (!empty($item['cart_id'])) {
                $cart_ids[] = $item['cart_id'];
            }
        }

        if (!empty($cart_ids)) {
            $placeholders = str_repeat('?,', count($cart_ids) - 1) . '?';
            $params = array_merge([$_SESSION['user_id']], $cart_ids);
            $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND id IN ($placeholders)")->execute($params);
        }
    }

    $pdo->commit();

    // Auto-deliver items that don't require customer info
    require_once __DIR__ . '/../includes/OrderDeliveryHandler.php';
    $deliveryHandler = new OrderDeliveryHandler($pdo);
    $deliveryHandler->deliverAutoItems($order_id);

    // Check and update final order status
    $deliveryHandler->updateOrderStatus($order_id);

    // Get final order status
    $stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $finalOrder = $stmt->fetch();
    $order_status = $finalOrder['status'] ?? 'pending';

    echo json_encode([
        'success' => true,
        'order_number' => $order_number,
        'order_id' => $order_id,
        'amount' => $final_vnd,
        'new_balance' => $new_balance,
        'currency' => 'VND',
        'service_name' => 'Thanh toán đơn hàng',
        'order_status' => $order_status
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>