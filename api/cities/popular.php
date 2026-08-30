<?php
/**
 * api/cities/popular.php - Vraća popularne gradove iz postojećih oglasa
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../../config/database.php';

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

try {
    $db = getDatabaseConnection();
    
    // Dobij popularne gradove iz oglasa gde je city popunjeno
    $stmt = $db->prepare("
        SELECT 
            a.city as name,
            COUNT(*) as count,
            COUNT(DISTINCT a.user_id) as user_count
        FROM ads a
        WHERE a.city IS NOT NULL 
        AND a.city != ''
        AND a.status = 'active'
        GROUP BY a.city
        ORDER BY count DESC
        LIMIT ?
    ");
    
    $stmt->execute([$limit]);
    $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Dodaj "Svi gradovi" opciju na početak
    array_unshift($cities, [
        'name' => 'Svi gradovi',
        'count' => 0,
        'user_count' => 0
    ]);
    
    echo json_encode([
        'success' => true,
        'cities' => $cities,
        'count' => count($cities),
        'limit' => $limit
    ]);
    
} catch (Exception $e) {
    error_log("Cities API error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Greška pri učitavanju gradova',
        'cities' => [
            ['name' => 'Svi gradovi', 'count' => 0, 'user_count' => 0]
        ],
        'count' => 1
    ]);
}
?>