<?php
/**
 * Add to Cart API - Simple & Clean
 */
require_once __DIR__ . '/../config/config.php';

// Headers
header('Content-Type: application/json; charset=utf-8');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check login
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

// Get input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

// Get product_id - Convert to string to handle Snowflake IDs properly
$product_id = $data['product_id'] ?? null;
$variant_id = $data['variant_id'] ?? null; // Support for product variants
$quantity = isset($data['quantity']) ? max(1, (int)$data['quantity']) : 1;
$customer_info = isset($data['customer_info']) ? trim($data['customer_info']) : null;

if (!$product_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Product ID required']);
    exit;
}

// Convert product_id to string for consistent comparison
$product_id = strval($product_id);

try {
    // Get product - Query as string
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND is_active = 1");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        http_response_code(404);
        echo json_encode([
            'success' => false, 
            'message' => 'Sản phẩm không tồn tại hoặc đã bị vô hiệu hóa'
        ]);
        exit;
    }

    // Get variant if specified
    $variant = null;
    $stock = $product['stock'];
    $min_purchase = $product['min_purchase'];
    $max_purchase = $product['max_purchase'];
    
    if ($variant_id) {
        $stmt = $pdo->prepare("SELECT * FROM product_variants WHERE id = ? AND product_id = ? AND is_active = 1");
        $stmt->execute([$variant_id, $product_id]);
        $variant = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$variant) {
            http_response_code(404);
            echo json_encode([
                'success' => false, 
                'message' => 'Option sản phẩm không tồn tại hoặc đã bị vô hiệu hóa'
            ]);
            exit;
        }
        
        // Use variant stock and limits
        $stock = $variant['stock'];
        $min_purchase = $variant['min_purchase'];
        $max_purchase = $variant['max_purchase'];
    }

    // Check stock
    if ($stock < $quantity) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Không đủ hàng trong kho. Còn: ' . $stock
        ]);
        exit;
    }

    // Check min/max
    if ($quantity < $min_purchase) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Số lượng tối thiểu: ' . $min_purchase
        ]);
        exit;
    }

    if ($quantity > $max_purchase) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Số lượng tối đa: ' . $max_purchase
        ]);
        exit;
    }

    // Check if already in cart (including variant)
    if ($variant_id) {
        $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ? AND variant_id = ?");
        $stmt->execute([$_SESSION['user_id'], $product_id, $variant_id]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ? AND variant_id IS NULL");
        $stmt->execute([$_SESSION['user_id'], $product_id]);
    }
    $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cart_item) {
        // Update quantity
        $new_qty = $cart_item['quantity'] + $quantity;
        
        if ($new_qty > $max_purchase) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'Vượt quá số lượng tối đa: ' . $max_purchase
            ]);
            exit;
        }

        if ($new_qty > $stock) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Không đủ hàng trong kho']);
            exit;
        }

        // Update quantity and customer_info if provided
        if ($customer_info !== null) {
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ?, customer_info = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$new_qty, $customer_info, $cart_item['id']]);
        } else {
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$new_qty, $cart_item['id']]);
        }
        
        $message = 'Đã cập nhật số lượng';
    } else {
        // Insert new
        $id = Snowflake::generateId();
        $stmt = $pdo->prepare("INSERT INTO cart (id, user_id, product_id, variant_id, quantity, customer_info, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$id, $_SESSION['user_id'], $product_id, $variant_id, $quantity, $customer_info]);
        
        $message = 'Đã thêm vào giỏ hàng';
    }

    // Get cart count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $count = $stmt->fetchColumn();

    // Success
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $message,
        'cart_count' => (int)$count,
        'product_name' => $product['name']
    ]);

} catch (Exception $e) {
    error_log("Add to cart error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi server: ' . $e->getMessage()
    ]);
}
