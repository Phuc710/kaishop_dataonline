<?php
/**
 * Search Suggestions API
 * Provides autocomplete suggestions for product search
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/config.php';

try {
    // Get search query
    $query = trim($_GET['q'] ?? '');
    
    // Minimum query length
    if (strlen($query) < 2) {
        echo json_encode([
            'success' => true,
            'suggestions' => []
        ]);
        exit;
    }
    
    // Sanitize query for SQL LIKE
    $searchTerm = '%' . $query . '%';
    
    // Get product suggestions
    $stmt = $pdo->prepare("
        SELECT DISTINCT
            id,
            name,
            image,
            final_price_vnd,
            final_price_usd,
            category_id,
            (CASE 
                WHEN name LIKE ? THEN 1
                WHEN name LIKE ? THEN 2
                ELSE 3
            END) as relevance_score
        FROM products 
        WHERE is_active = 1 
        AND (
            name LIKE ? 
            OR description LIKE ?
            OR tags LIKE ?
        )
        ORDER BY relevance_score ASC, sold_count DESC, name ASC
        LIMIT 10
    ");
    
    $exactMatch = $query . '%';
    $stmt->execute([
        $exactMatch,     // Exact start match (highest priority)
        $searchTerm,     // Contains match
        $searchTerm,     // Name contains
        $searchTerm,     // Description contains  
        $searchTerm      // Tags contains
    ]);
    
    $products = $stmt->fetchAll();
    
    // Get category suggestions
    $stmt = $pdo->prepare("
        SELECT DISTINCT
            id,
            name,
            'category' as type
        FROM categories 
        WHERE is_active = 1 
        AND name LIKE ?
        ORDER BY name ASC
        LIMIT 5
    ");
    $stmt->execute([$searchTerm]);
    $categories = $stmt->fetchAll();
    
    // Format suggestions
    $suggestions = [];
    
    // Add product suggestions
    foreach ($products as $product) {
        $suggestions[] = [
            'type' => 'product',
            'id' => $product['id'],
            'title' => $product['name'],
            'subtitle' => 'Sản phẩm',
            'image' => $product['image'] ? asset('images/uploads/' . $product['image']) : null,
            'price_vnd' => $product['final_price_vnd'],
            'price_usd' => $product['final_price_usd'],
            'url' => url('sanpham/view?id=' . $product['id']),
            'category_id' => $product['category_id']
        ];
    }
    
    // Add category suggestions
    foreach ($categories as $category) {
        $suggestions[] = [
            'type' => 'category',
            'id' => $category['id'],
            'title' => $category['name'],
            'subtitle' => 'Danh mục',
            'image' => null,
            'url' => url('sanpham?category=' . $category['id'])
        ];
    }
    
    // Add popular searches if no results
    if (empty($suggestions)) {
        $stmt = $pdo->prepare("
            SELECT name, COUNT(*) as search_count
            FROM search_logs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            AND query LIKE ?
            GROUP BY name
            ORDER BY search_count DESC
            LIMIT 5
        ");
        $stmt->execute([$searchTerm]);
        $popularSearches = $stmt->fetchAll();
        
        foreach ($popularSearches as $search) {
            $suggestions[] = [
                'type' => 'popular',
                'title' => $search['name'],
                'subtitle' => 'Tìm kiếm phổ biến',
                'image' => null,
                'url' => url('sanpham?search=' . urlencode($search['name']))
            ];
        }
    }
    
    // Log search query for analytics
    if (!empty($query)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO search_logs (query, results_count, user_id, ip_address) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $query,
                count($suggestions),
                $_SESSION['user_id'] ?? null,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        } catch (Exception $e) {
            // Ignore logging errors
        }
    }
    
    echo json_encode([
        'success' => true,
        'query' => $query,
        'suggestions' => $suggestions,
        'total' => count($suggestions)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi tìm kiếm: ' . $e->getMessage(),
        'suggestions' => []
    ]);
}
?>