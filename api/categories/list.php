<?php
/**
 * api/categories/list.php - Vraća listu kategorija za formu
 */

header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/categories.php';
try {
    
    $categories = getMainCategories();
    
    // Formatiraj za select
    $formattedCategories = [];
    foreach ($categories as $cat) {
        $formattedCategories[] = [
            'id' => (int)$cat['id'],
            'name' => $cat['name'],
            'slug' => $cat['slug'],
            'icon' => $cat['icon'] ?? 'fas fa-folder',
            'ad_count' => (int)$cat['total_ads']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'categories' => $formattedCategories,
        'total' => count($formattedCategories)
    ]);
    
} catch (Exception $e) {
    error_log("Categories API error: " . $e->getMessage());
    
    // Fallback kategorije ako baza ne radi
    $fallbackCategories = [
        ['id' => 1, 'name' => 'Automobili', 'slug' => 'automobili', 'icon' => 'fas fa-car', 'color' => '#dc3545', 'ad_count' => 0],
        ['id' => 2, 'name' => 'Nekretnine', 'slug' => 'nekretnine', 'icon' => 'fas fa-home', 'color' => '#0d6efd', 'ad_count' => 0],
        ['id' => 3, 'name' => 'Telefoni', 'slug' => 'telefoni', 'icon' => 'fas fa-mobile-alt', 'color' => '#198754', 'ad_count' => 0],
        ['id' => 4, 'name' => 'Računari', 'slug' => 'racunari', 'icon' => 'fas fa-laptop', 'color' => '#6f42c1', 'ad_count' => 0],
        ['id' => 5, 'name' => 'Odeća i obuća', 'slug' => 'odeca-obuca', 'icon' => 'fas fa-tshirt', 'color' => '#fd7e14', 'ad_count' => 0]
    ];
    
    echo json_encode([
        'success' => true,
        'categories' => $fallbackCategories,
        'total' => count($fallbackCategories),
        'note' => 'Using fallback categories'
    ]);
}
?>