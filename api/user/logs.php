<?php
/**
 * api/user/logs.php - Dohvatanje logova korisnika
 */

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// Proveri da li je korisnik ulogovan
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false, 
        'message' => 'Morate biti prijavljeni'
    ]);
    exit();
}

$userId = $_SESSION['user_id'];

// Parametri
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
$limit = min($limit, 100); // Maksimum 100
$action = $_GET['action'] ?? null;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

try {
    $db = getDatabaseConnection();
    
    // Dohvati logove
    $sql = "SELECT * FROM user_logs WHERE user_id = ?";
    $params = [$userId];
    $countParams = [$userId];
    
    if ($action) {
        $sql .= " AND action = ?";
        $params[] = $action;
        $countParams[] = $action;
    }
    
    // Brojanje ukupnog broja
    $countSql = "SELECT COUNT(*) as total FROM user_logs WHERE user_id = ?";
    if ($action) {
        $countSql .= " AND action = ?";
    }
    
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($countParams);
    $countResult = $countStmt->fetch();
    $totalLogs = $countResult['total'] ?? 0;
    
    // Dohvati sa paginacijom
    $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
    
    // Formatiraj podatke
    $formattedLogs = [];
    foreach ($logs as $log) {
        $details = !empty($log['details']) 
            ? json_decode($log['details'], true) 
            : [];
        
        $formattedLogs[] = [
            'id' => $log['id'],
            'action' => $log['action'],
            'details' => $details,
            'ip_address' => $log['ip_address'],
            'user_agent' => $log['user_agent'],
            'created_at' => $log['created_at'],
            'human_time' => timeAgo($log['created_at'])
        ];
    }
    
    echo json_encode([
        'success' => true,
        'logs' => $formattedLogs,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $totalLogs,
            'pages' => ceil($totalLogs / $limit)
        ],
        'stats' => [
            'total' => $totalLogs,
            'last_7_days' => getLogsCountLastDays($userId, 7),
            'last_30_days' => getLogsCountLastDays($userId, 30)
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Greška pri dohvatanju logova: ' . $e->getMessage()
    ]);
    error_log("Get user logs error: " . $e->getMessage());
}

/**
 * Pomocna funkcija za brojanje logova po danima
 */
function getLogsCountLastDays($userId, $days) {
    try {
        $db = getDatabaseConnection();
        
        $date = date('Y-m-d H:i:s', strtotime("-$days days"));
        
        $stmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM user_logs 
            WHERE user_id = ? 
            AND created_at >= ?
        ");
        
        $stmt->execute([$userId, $date]);
        $result = $stmt->fetch();
        
        return $result['count'] ?? 0;
        
    } catch (Exception $e) {
        error_log("Get logs count error: " . $e->getMessage());
        return 0;
    }
}
?>