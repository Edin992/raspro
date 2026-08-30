<?php
/**
 * api/categories/children.php - Vraća podkategorije za roditeljsku kategoriju
 */

header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/functions.php';

$parentId = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : 0;

if ($parentId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Nevažeći ID kategorije']);
    exit;
}

try {
    $db = getDatabaseConnection();
    
    $stmt = $db->prepare("
        SELECT 
            c.id,
            c.name,
            c.slug,
            c.icon,
            COUNT(DISTINCT a.id) as ad_count
        FROM categories c
        LEFT JOIN ads a ON c.id = a.category_id AND a.status = 'active'
        WHERE c.parent_id = ?
        GROUP BY c.id
        ORDER BY c.sort_order ASC
    ");
    
    $stmt->execute([$parentId]);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($categories)) {
        echo json_encode([
            'success' => true,
            'categories' => [],
            'message' => 'Nema podkategorija za ovu kategoriju'
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'categories' => $categories,
        'total' => count($categories)
    ]);
    
} catch (Exception $e) {
    error_log("Subcategories API error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Greška pri učitavanju podkategorija'
    ]);
}
?>