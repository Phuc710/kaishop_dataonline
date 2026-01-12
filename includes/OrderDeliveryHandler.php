<?php
/**
 * Order Delivery Handler
 * Handle product delivery based on product type
 */

class OrderDeliveryHandler {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Process order delivery when order status = completed
     */
    public function deliverOrder($order_id) {
        // Get order items
        $stmt = $this->pdo->prepare("
            SELECT 
                oi.*,
                p.product_type,
                p.delivery_content,
                p.name as product_name
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $deliveries = [];
        
        foreach ($items as $item) {
            $product_type = $item['product_type'] ?? 'account';
            
            switch ($product_type) {
                case 'account':
                    $deliveries[] = $this->deliverAccount($item);
                    break;
                case 'source':
                    $deliveries[] = $this->deliverSource($item);
                    break;
                case 'book':
                    $deliveries[] = $this->deliverBook($item);
                    break;
                default:
                    $deliveries[] = $this->deliverAccount($item);
            }
        }
        
        return $deliveries;
    }
    
    /**
     * Deliver Account product - Get accounts from pool
     */
    private function deliverAccount($item) {
        $order_item_id = $item['id'];
        $product_id = $item['product_id'];
        $variant_id = $item['variant_id'] ?? null;
        $quantity = $item['quantity'];
        
        // Check if already delivered
        $check = $this->pdo->prepare("SELECT delivery_content FROM order_items WHERE id = ?");
        $check->execute([$order_item_id]);
        $existing = $check->fetch();
        
        if (!empty($existing['delivery_content'])) {
            return [
                'type' => 'account',
                'product_name' => $item['product_name'],
                'content' => $existing['delivery_content'],
                'already_delivered' => true
            ];
        }
        
        // Get accounts from stock pool
        $accounts = [];
        
        // Check if table has variant_id column
        try {
            $checkCol = $this->pdo->query("SHOW COLUMNS FROM product_stock_pool LIKE 'variant_id'");
            $hasVariantCol = $checkCol->rowCount() > 0;
            
            // Cast quantity to int for LIMIT (PDO doesn't support binding LIMIT params by default)
            $qty = (int) $quantity;
            
            if ($hasVariantCol && $variant_id) {
                // Get by variant
                $stmt = $this->pdo->prepare("
                    SELECT * FROM product_stock_pool
                    WHERE product_id = ? AND variant_id = ? AND is_used = 0
                    LIMIT {$qty}
                ");
                $stmt->execute([$product_id, $variant_id]);
            } else {
                // Get without variant filter
                $stmt = $this->pdo->prepare("
                    SELECT * FROM product_stock_pool
                    WHERE product_id = ? AND is_used = 0
                    LIMIT {$qty}
                ");
                $stmt->execute([$product_id]);
            }
            
            $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Table doesn't exist or error
            return [
                'type' => 'account',
                'product_name' => $item['product_name'],
                'error' => 'Stock pool not available',
                'fallback' => $item['delivery_content']
            ];
        }
        
        if (count($accounts) < $quantity) {
            return [
                'type' => 'account',
                'product_name' => $item['product_name'],
                'error' => 'Not enough stock',
                'available' => count($accounts),
                'required' => $quantity
            ];
        }
        
        // Mark accounts as used
        $delivered_accounts = [];
        foreach ($accounts as $acc) {
            $this->pdo->prepare("
                UPDATE product_stock_pool
                SET is_used = 1, used_by_order_id = ?, used_at = NOW()
                WHERE id = ?
            ")->execute([$item['order_id'], $acc['id']]);
            
            $delivered_accounts[] = $acc['content'];
        }
        
        // Save to order_items
        $delivery_text = implode("\n", $delivered_accounts);
        $this->pdo->prepare("
            UPDATE order_items SET delivery_content = ? WHERE id = ?
        ")->execute([$delivery_text, $order_item_id]);
        
        return [
            'type' => 'account',
            'product_name' => $item['product_name'],
            'content' => $delivery_text,
            'accounts_delivered' => count($delivered_accounts)
        ];
    }
    
    /**
     * Deliver Source Code product - Return download link from product
     */
    private function deliverSource($item) {
        $order_item_id = $item['id'];
        // Get link from product's delivery_content (aliased as product_delivery_content in query)
        $link = $item['product_delivery_content'] ?? '';
        
        if (empty($link)) {
            // Fallback: get from products table directly
            $stmt = $this->pdo->prepare("SELECT delivery_content FROM products WHERE id = ?");
            $stmt->execute([$item['product_id']]);
            $prod = $stmt->fetch();
            $link = $prod['delivery_content'] ?? '';
        }
        
        if (empty($link)) {
            return [
                'type' => 'source',
                'product_name' => $item['product_name'],
                'error' => 'No download link configured'
            ];
        }
        
        // Save to order_items
        $this->pdo->prepare("
            UPDATE order_items SET delivery_content = ? WHERE id = ?
        ")->execute([$link, $order_item_id]);
        
        return [
            'type' => 'source',
            'product_name' => $item['product_name'],
            'content' => $link
        ];
    }
    
    /**
     * Deliver Book product - Return ebook link from product
     */
    private function deliverBook($item) {
        $order_item_id = $item['id'];
        // Get link from product's delivery_content (aliased as product_delivery_content in query)
        $link = $item['product_delivery_content'] ?? '';
        
        if (empty($link)) {
            // Fallback: get from products table directly
            $stmt = $this->pdo->prepare("SELECT delivery_content FROM products WHERE id = ?");
            $stmt->execute([$item['product_id']]);
            $prod = $stmt->fetch();
            $link = $prod['delivery_content'] ?? '';
        }
        
        if (empty($link)) {
            return [
                'type' => 'book',
                'product_name' => $item['product_name'],
                'error' => 'No ebook link configured'
            ];
        }
        
        // Save to order_items
        $this->pdo->prepare("
            UPDATE order_items SET delivery_content = ? WHERE id = ?
        ")->execute([$link, $order_item_id]);
        
        return [
            'type' => 'book',
            'product_name' => $item['product_name'],
            'content' => $link
        ];
    }
    
    /**
     * Get user's delivered products
     */
    public function getUserDeliveries($order_id) {
        $stmt = $this->pdo->prepare("
            SELECT 
                oi.*,
                p.name as product_name,
                p.product_type,
                p.image
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order_id]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Auto-deliver items that DON'T require customer info
     * Items with requires_customer_info=1 will wait for admin
     */
    public function deliverAutoItems($order_id) {
        // Get items that don't require customer info and haven't been delivered
        $stmt = $this->pdo->prepare("
            SELECT 
                oi.*,
                p.product_type,
                p.delivery_content as product_delivery_content,
                p.name as product_name,
                p.requires_customer_info
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ? 
            AND (oi.delivery_content IS NULL OR oi.delivery_content = '')
            AND (p.requires_customer_info = 0 OR p.requires_customer_info IS NULL)
        ");
        $stmt->execute([$order_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $deliveries = [];
        
        foreach ($items as $item) {
            $product_type = $item['product_type'] ?? 'account';
            
            switch ($product_type) {
                case 'account':
                    $deliveries[] = $this->deliverAccount($item);
                    break;
                case 'source':
                    $deliveries[] = $this->deliverSource($item);
                    break;
                case 'book':
                    $deliveries[] = $this->deliverBook($item);
                    break;
                default:
                    $deliveries[] = $this->deliverAccount($item);
            }
        }
        
        return $deliveries;
    }
    
    /**
     * Deliver a single order item by admin (with admin response content)
     */
    public function deliverSingleItem($order_item_id, $admin_response = null) {
        // Get item info
        $stmt = $this->pdo->prepare("
            SELECT 
                oi.*,
                p.product_type,
                p.delivery_content as product_delivery_content,
                p.name as product_name,
                p.requires_customer_info
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.id = ?
        ");
        $stmt->execute([$order_item_id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$item) {
            return ['success' => false, 'error' => 'Item not found'];
        }
        
        // If admin provides response, use it directly
        if (!empty($admin_response)) {
            $this->pdo->prepare("
                UPDATE order_items SET delivery_content = ? WHERE id = ?
            ")->execute([$admin_response, $order_item_id]);
            
            // Update order status
            $this->updateOrderStatus($item['order_id']);
            
            return [
                'success' => true,
                'type' => 'admin_response',
                'product_name' => $item['product_name'],
                'content' => $admin_response
            ];
        }
        
        // Otherwise, try auto-deliver based on product type
        $product_type = $item['product_type'] ?? 'account';
        
        switch ($product_type) {
            case 'account':
                $result = $this->deliverAccount($item);
                break;
            case 'source':
                $result = $this->deliverSource($item);
                break;
            case 'book':
                $result = $this->deliverBook($item);
                break;
            default:
                $result = $this->deliverAccount($item);
        }
        
        // Update order status
        $this->updateOrderStatus($item['order_id']);
        
        return $result;
    }
    
    /**
     * Update order status based on all items delivery status
     * Order is 'completed' only when ALL items are delivered
     */
    public function updateOrderStatus($order_id) {
        // Count total items and delivered items
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN delivery_content IS NOT NULL AND delivery_content != '' THEN 1 ELSE 0 END) as delivered
            FROM order_items
            WHERE order_id = ?
        ");
        $stmt->execute([$order_id]);
        $counts = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $total = intval($counts['total']);
        $delivered = intval($counts['delivered']);
        
        // Update order status
        if ($total > 0 && $delivered >= $total) {
            // All items delivered
            $this->pdo->prepare("UPDATE orders SET status = 'completed' WHERE id = ?")->execute([$order_id]);
            return 'completed';
        } else {
            // Some items pending
            $this->pdo->prepare("UPDATE orders SET status = 'pending' WHERE id = ?")->execute([$order_id]);
            return 'pending';
        }
    }
}
