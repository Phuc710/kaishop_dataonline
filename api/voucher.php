<?php
/**
 * API Voucher Backend
 * Xử lý validate và apply mã giảm giá
 */
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

// Chỉ chấp nhận POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

try {
    switch ($action) {
        case 'validate':
            // Validate voucher code
            $code = strtoupper(trim($data['code'] ?? ''));
            $total_amount = floatval($data['total_amount'] ?? 0);
            $product_ids = $data['product_ids'] ?? []; // Array of product IDs to check
            $product_id = $data['product_id'] ?? null; // Single product ID (for buy now)
            
            // Convert single product_id to array
            if ($product_id && empty($product_ids)) {
                $product_ids = [$product_id];
            }
            
            if (empty($code)) {
                throw new Exception('Vui lòng nhập mã voucher');
            }
            
            if ($total_amount <= 0) {
                throw new Exception('Số tiền không hợp lệ');
            }
            
            // Lấy thông tin voucher
            $stmt = $pdo->prepare("
                SELECT * FROM vouchers 
                WHERE code = ? AND is_active = 1
            ");
            $stmt->execute([$code]);
            $voucher = $stmt->fetch();
            
            if (!$voucher) {
                throw new Exception('Mã voucher không tồn tại hoặc đã hết hiệu lực');
            }
            
            // Kiểm tra sản phẩm áp dụng
            if (!empty($voucher['applicable_products']) && !empty($product_ids)) {
                $applicable = json_decode($voucher['applicable_products'], true);
                if (is_array($applicable) && count($applicable) > 0) {
                    // Check if ANY of the products is in applicable list
                    $found = false;
                    foreach ($product_ids as $pid) {
                        if (in_array($pid, $applicable) || in_array((string)$pid, $applicable)) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        throw new Exception('Mã voucher này không áp dụng cho sản phẩm đang chọn');
                    }
                }
            }
            
            // Kiểm tra thời gian hiệu lực
            $now = date('Y-m-d H:i:s');
            if ($voucher['valid_from'] && $voucher['valid_from'] > $now) {
                throw new Exception('Mã voucher chưa đến thời gian sử dụng');
            }
            if ($voucher['valid_until'] && $voucher['valid_until'] < $now) {
                throw new Exception('Mã voucher đã hết hạn');
            }
            
            // Kiểm tra số lần sử dụng
            if ($voucher['usage_limit'] > 0 && $voucher['used_count'] >= $voucher['usage_limit']) {
                throw new Exception('Mã voucher đã hết lượt sử dụng');
            }
            
            // Kiểm tra đơn hàng tối thiểu
            if ($voucher['min_amount'] > 0 && $total_amount < $voucher['min_amount']) {
                throw new Exception('Đơn hàng tối thiểu ' . formatVND($voucher['min_amount']) . ' để sử dụng mã này');
            }
            
            // Kiểm tra user đã dùng voucher này chưa (nếu muốn giới hạn 1 lần/user)
            $check = $pdo->prepare("
                SELECT COUNT(*) FROM orders 
                WHERE user_id = ? AND voucher_code = ?
            ");
            $check->execute([$_SESSION['user_id'], $code]);
            if ($check->fetchColumn() > 0) {
                throw new Exception('Bạn đã sử dụng mã voucher này rồi');
            }
            
            
            // Tính số tiền giảm giá
            $discount_amount = 0;
            if ($voucher['discount_type'] === 'percentage') {
                $discount_amount = ($total_amount * $voucher['discount_value']) / 100;
                
                // Áp dụng giảm tối đa nếu có
                if ($voucher['max_discount'] > 0 && $discount_amount > $voucher['max_discount']) {
                    $discount_amount = $voucher['max_discount'];
                }
            } else {
                // Fixed amount
                $discount_amount = $voucher['discount_value'];
            }
            
            // Không giảm quá tổng tiền
            if ($discount_amount > $total_amount) {
                $discount_amount = $total_amount;
            }
            
            $final_amount = $total_amount - $discount_amount;
            
            echo json_encode([
                'success' => true,
                'message' => 'Áp dụng mã giảm giá thành công',
                'voucher' => [
                    'id' => $voucher['id'],
                    'code' => $voucher['code'],
                    'discount_type' => $voucher['discount_type'],
                    'discount_value' => $voucher['discount_value'],
                    'max_discount' => $voucher['max_discount'],
                    'min_amount' => $voucher['min_amount']
                ],
                'calculation' => [
                    'original_amount' => $total_amount,
                    'discount_amount' => $discount_amount,
                    'final_amount' => $final_amount
                ],
                'formatted' => [
                    'original_amount' => formatVND($total_amount),
                    'discount_amount' => formatVND($discount_amount),
                    'final_amount' => formatVND($final_amount)
                ]
            ]);
            break;
            
        case 'apply':
            // Apply voucher trong quá trình thanh toán
            // Function này sẽ được gọi từ process.php
            $voucher_code = strtoupper(trim($data['voucher_code'] ?? ''));
            $total_amount = floatval($data['total_amount'] ?? 0);
            $product_ids = $data['product_ids'] ?? []; // Array of product IDs to check
            
            if (empty($voucher_code)) {
                echo json_encode([
                    'success' => true,
                    'discount_amount' => 0,
                    'voucher_applied' => false
                ]);
                exit;
            }
            
            // Re-validate voucher
            $stmt = $pdo->prepare("
                SELECT * FROM vouchers 
                WHERE code = ? AND is_active = 1
            ");
            $stmt->execute([$voucher_code]);
            $voucher = $stmt->fetch();
            
            if (!$voucher) {
                throw new Exception('Mã voucher không hợp lệ');
            }
            
            // Kiểm tra sản phẩm áp dụng
            if (!empty($voucher['applicable_products']) && !empty($product_ids)) {
                $applicable = json_decode($voucher['applicable_products'], true);
                if (is_array($applicable) && count($applicable) > 0) {
                    $found = false;
                    foreach ($product_ids as $pid) {
                        if (in_array($pid, $applicable) || in_array((string)$pid, $applicable)) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        throw new Exception('Mã voucher này không áp dụng cho sản phẩm đang chọn');
                    }
                }
            }
            
            // Validate các điều kiện
            $now = date('Y-m-d H:i:s');
            if ($voucher['valid_from'] && $voucher['valid_from'] > $now) {
                throw new Exception('Mã voucher chưa đến thời gian sử dụng');
            }
            if ($voucher['valid_until'] && $voucher['valid_until'] < $now) {
                throw new Exception('Mã voucher đã hết hạn');
            }
            if ($voucher['usage_limit'] > 0 && $voucher['used_count'] >= $voucher['usage_limit']) {
                throw new Exception('Mã voucher đã hết lượt sử dụng');
            }
            if ($voucher['min_amount'] > 0 && $total_amount < $voucher['min_amount']) {
                throw new Exception('Đơn hàng chưa đủ điều kiện sử dụng voucher');
            }
            
            // Tính discount
            $discount_amount = 0;
            if ($voucher['discount_type'] === 'percentage') {
                $discount_amount = ($total_amount * $voucher['discount_value']) / 100;
                if ($voucher['max_discount'] > 0 && $discount_amount > $voucher['max_discount']) {
                    $discount_amount = $voucher['max_discount'];
                }
            } else {
                $discount_amount = $voucher['discount_value'];
            }
            
            if ($discount_amount > $total_amount) {
                $discount_amount = $total_amount;
            }
            
            // Tăng used_count
            $pdo->prepare("UPDATE vouchers SET used_count = used_count + 1 WHERE id = ?")->execute([$voucher['id']]);
            
            echo json_encode([
                'success' => true,
                'discount_amount' => $discount_amount,
                'voucher_id' => $voucher['id'],
                'voucher_code' => $voucher['code'],
                'voucher_applied' => true
            ]);
            break;
            
        case 'list':
            // Lấy danh sách voucher đang active cho user
            $total_amount = floatval($data['total_amount'] ?? 0);
            $now = date('Y-m-d H:i:s');
            
            $stmt = $pdo->prepare("
                SELECT * FROM vouchers 
                WHERE is_active = 1 
                AND (valid_from IS NULL OR valid_from <= ?)
                AND (valid_until IS NULL OR valid_until >= ?)
                AND (usage_limit = 0 OR used_count < usage_limit)
                ORDER BY discount_value DESC
            ");
            $stmt->execute([$now, $now]);
            $vouchers = $stmt->fetchAll();
            
            // Filter theo min_amount và format
            $available = [];
            $unavailable = [];
            
            foreach ($vouchers as $v) {
                $can_use = ($v['min_amount'] == 0 || $total_amount >= $v['min_amount']);
                
                $item = [
                    'id' => $v['id'],
                    'code' => $v['code'],
                    'discount_type' => $v['discount_type'],
                    'discount_value' => $v['discount_value'],
                    'max_discount' => $v['max_discount'],
                    'min_amount' => $v['min_amount'],
                    'used_count' => $v['used_count'],
                    'usage_limit' => $v['usage_limit'],
                    'valid_until' => $v['valid_until'],
                    'can_use' => $can_use,
                    'formatted' => [
                        'min_amount' => formatVND($v['min_amount']),
                        'max_discount' => $v['max_discount'] > 0 ? formatVND($v['max_discount']) : null
                    ]
                ];
                
                if ($can_use) {
                    $available[] = $item;
                } else {
                    $unavailable[] = $item;
                }
            }
            
            echo json_encode([
                'success' => true,
                'vouchers' => [
                    'available' => $available,
                    'unavailable' => $unavailable
                ]
            ]);
            break;
            
        default:
            throw new Exception('Action không hợp lệ');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
