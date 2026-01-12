<?php
/**
 * Admin Stock Manager API
 * CRUD operations for product_stock_pool
 */

session_start();
require_once __DIR__ . '/../config/database.php';

// Check admin auth
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    if ($action === 'delete_stock') {
        $id = $_POST['id'] ?? '';
        if (empty($id)) {
            throw new Exception('Missing ID');
        }
        
        $stmt = $pdo->prepare("DELETE FROM product_stock_pool WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true]);
        
    } elseif ($action === 'save_stock') {
        $product_id = $_POST['product_id'] ?? '';
        $variant_id = $_POST['variant_id'] ?? null;
        $items = json_decode($_POST['items'] ?? '[]', true);
        
        if (empty($product_id)) {
            throw new Exception('Missing product_id');
        }
        
        $pdo->beginTransaction();
        
        // Delete old items for this product/variant
        if ($variant_id) {
            $pdo->prepare("DELETE FROM product_stock_pool WHERE product_id = ? AND variant_id = ? AND is_used = 0")
                ->execute([$product_id, $variant_id]);
        } else {
            $pdo->prepare("DELETE FROM product_stock_pool WHERE product_id = ? AND variant_id IS NULL AND is_used = 0")
                ->execute([$product_id]);
        }
        
        // Insert new items
        $stmt = $pdo->prepare("INSERT INTO product_stock_pool (product_id, variant_id, content, is_used) VALUES (?, ?, ?, 0)");
        
        foreach ($items as $item) {
            if (!isset($item['username']) || !isset($item['password'])) continue;
            
            $content = trim($item['username']) . '|' . trim($item['password']);
            
            $stmt->execute([$product_id, $variant_id ?: null, $content]);
        }
        
        // Update stock count
        $count = count($items);
        
        if ($variant_id) {
            $pdo->prepare("UPDATE product_variants SET stock = ? WHERE id = ?")
                ->execute([$count, $variant_id]);
        } else {
            $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?")
                ->execute([$count, $product_id]);
        }
        
        $pdo->commit();
        
        echo json_encode(['success' => true, 'count' => $count]);
        
    } elseif ($action === 'load_stock') {
        $product_id = $_POST['product_id'] ?? '';
        $variant_id = $_POST['variant_id'] ?? null;
        $include_sold = $_POST['include_sold'] ?? '1'; // Default: include sold items
        
        if ($include_sold === '1') {
            // Load all items (available first, then sold)
            if ($variant_id) {
                $stmt = $pdo->prepare("
                    SELECT sp.*, o.order_number, o.created_at as sold_at
                    FROM product_stock_pool sp
                    LEFT JOIN orders o ON sp.used_by_order_id = o.id
                    WHERE sp.product_id = ? AND sp.variant_id = ?
                    ORDER BY sp.is_used ASC, sp.id ASC
                ");
                $stmt->execute([$product_id, $variant_id]);
            } else {
                $stmt = $pdo->prepare("
                    SELECT sp.*, o.order_number, o.created_at as sold_at
                    FROM product_stock_pool sp
                    LEFT JOIN orders o ON sp.used_by_order_id = o.id
                    WHERE sp.product_id = ? AND sp.variant_id IS NULL
                    ORDER BY sp.is_used ASC, sp.id ASC
                ");
                $stmt->execute([$product_id]);
            }
        } else {
            // Load only available items
            if ($variant_id) {
                $stmt = $pdo->prepare("SELECT * FROM product_stock_pool WHERE product_id = ? AND variant_id = ? AND is_used = 0 ORDER BY id ASC");
                $stmt->execute([$product_id, $variant_id]);
            } else {
                $stmt = $pdo->prepare("SELECT * FROM product_stock_pool WHERE product_id = ? AND variant_id IS NULL AND is_used = 0 ORDER BY id ASC");
                $stmt->execute([$product_id]);
            }
        }
        
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Count available and sold
        $available = 0;
        $sold = 0;
        foreach ($items as $item) {
            if ($item['is_used']) $sold++;
            else $available++;
        }
        
        echo json_encode(['success' => true, 'items' => $items, 'available' => $available, 'sold' => $sold]);
    
    } elseif ($action === 'load_sold_history') {
        // Load all sold accounts for a product with order details
        $product_id = $_POST['product_id'] ?? '';
        $variant_id = $_POST['variant_id'] ?? null;
        
        if ($variant_id) {
            $stmt = $pdo->prepare("
                SELECT sp.*, o.order_number, o.created_at as sold_at, u.username as buyer
                FROM product_stock_pool sp
                LEFT JOIN orders o ON sp.used_by_order_id = o.id
                LEFT JOIN users u ON o.user_id = u.id
                WHERE sp.product_id = ? AND sp.variant_id = ? AND sp.is_used = 1
                ORDER BY sp.used_at DESC
            ");
            $stmt->execute([$product_id, $variant_id]);
        } else {
            $stmt = $pdo->prepare("
                SELECT sp.*, o.order_number, o.created_at as sold_at, u.username as buyer
                FROM product_stock_pool sp
                LEFT JOIN orders o ON sp.used_by_order_id = o.id
                LEFT JOIN users u ON o.user_id = u.id
                WHERE sp.product_id = ? AND sp.variant_id IS NULL AND sp.is_used = 1
                ORDER BY sp.used_at DESC
            ");
            $stmt->execute([$product_id]);
        }
        
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'items' => $items]);
        
    } elseif ($action === 'delete_variant') {
        $variant_id = $_POST['variant_id'] ?? '';
        
        if (empty($variant_id)) {
            throw new Exception('Missing variant_id');
        }
        
        $pdo->beginTransaction();
        
        // Delete stock pool
        $pdo->prepare("DELETE FROM product_stock_pool WHERE variant_id = ?")->execute([$variant_id]);
        
        // Delete variant
        $pdo->prepare("DELETE FROM product_variants WHERE id = ?")->execute([$variant_id]);
        
        $pdo->commit();
        
        echo json_encode(['success' => true]);
        
    } elseif ($action === 'mark_as_sold') {
        // Mark a stock item as sold (manual move to sold)
        $id = $_POST['id'] ?? '';
        $product_id = $_POST['product_id'] ?? '';
        $variant_id = $_POST['variant_id'] ?? null;
        
        if (empty($id)) {
            throw new Exception('Missing stock item ID');
        }
        
        $pdo->beginTransaction();
        
        // Update the stock item to mark as sold
        $stmt = $pdo->prepare("UPDATE product_stock_pool SET is_used = 1, used_at = NOW() WHERE id = ? AND is_used = 0");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Không tìm thấy tài khoản hoặc đã được bán');
        }
        
        // Update stock count
        if ($variant_id) {
            $pdo->prepare("UPDATE product_variants SET stock = stock - 1 WHERE id = ? AND stock > 0")
                ->execute([$variant_id]);
        } else {
            $pdo->prepare("UPDATE products SET stock = stock - 1 WHERE id = ? AND stock > 0")
                ->execute([$product_id]);
        }
        
        $pdo->commit();
        
        echo json_encode(['success' => true, 'message' => 'Đã chuyển sang Đã Bán']);
        
    } elseif ($action === 'clear_all_stock') {
        $product_id = $_POST['product_id'] ?? '';
        $variant_id = $_POST['variant_id'] ?? null;
        
        if (empty($product_id)) {
            throw new Exception('Missing product_id');
        }
        
        $pdo->beginTransaction();
        
        // Delete all stock (both used and unused)
        if ($variant_id) {
            $stmt = $pdo->prepare("DELETE FROM product_stock_pool WHERE product_id = ? AND variant_id = ?");
            $stmt->execute([$product_id, $variant_id]);
            
            // Update variant stock to 0
            $pdo->prepare("UPDATE product_variants SET stock = 0 WHERE id = ?")
                ->execute([$variant_id]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM product_stock_pool WHERE product_id = ? AND variant_id IS NULL");
            $stmt->execute([$product_id]);
            
            // Update product stock to 0
            $pdo->prepare("UPDATE products SET stock = 0 WHERE id = ?")
                ->execute([$product_id]);
        }
        
        $pdo->commit();
        
        echo json_encode(['success' => true, 'message' => 'Đã xóa tất cả tài khoản trong kho']);
        
    } else {
        throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
