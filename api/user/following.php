<?php
/**
 * api/user/following.php - Dohvati listu korisnika koje prati
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
header('Content-Type: application/json');

// Dohvatanje parametara
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;

if ($userId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID korisnika je obavezan']);
    exit;
}

$offset = ($page - 1) * $limit;
$db = getDatabaseConnection();

// Proveri da li korisnik postoji
$stmt = $db->prepare("SELECT id, username FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Korisnik nije pronađen']);
    exit;
}

// Ukupan broj korisnika koje prati (koristi funkciju iz functions.php)
$totalFollowing = getFollowingCount($userId);

// Lista korisnika koje prati
$stmt = $db->prepare("
    SELECT 
        u.id,
        u.username,
        u.first_name,
        u.last_name,
        u.avatar,
        u.ads_count,
        u.is_verified,
        DATE_FORMAT(f.created_at, '%d.%m.%Y.') as followed_date,
        EXISTS(
            SELECT 1 FROM followers f2 
            WHERE f2.follower_id = u.id AND f2.following_id = ?
        ) as follows_me_back
    FROM followers f
    INNER JOIN users u ON f.following_id = u.id
    WHERE f.follower_id = ?
    ORDER BY f.created_at DESC
    LIMIT ? OFFSET ?
");

$currentUserId = $_SESSION['user_id'] ?? 0;
$stmt->execute([$currentUserId, $userId, $limit, $offset]);
$following = $stmt->fetchAll();

$response = [
    'success' => true,
    'data' => [
        'user' => [
            'id' => $user['id'],
            'username' => $user['username']
        ],
        'following' => [],
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $totalFollowing,
            'pages' => ceil($totalFollowing / $limit),
            'has_more' => ($page * $limit) < $totalFollowing
        ]
    ]
];

foreach ($following as $followedUser) {
    $response['data']['following'][] = [
        'id' => $followedUser['id'],
        'username' => $followedUser['username'],
        'full_name' => trim($followedUser['first_name'] . ' ' . $followedUser['last_name']),
        'avatar' => !empty($followedUser['avatar']) ? $followedUser['avatar'] : SITE_URL . '/assets/images/defaults/avatar.png',
        'ads_count' => $followedUser['ads_count'],
        'is_verified' => (bool)$followedUser['is_verified'],
        'followed_date' => $followedUser['followed_date'],
        'follows_me_back' => (bool)$followedUser['follows_me_back'],
        'profile_url' => SITE_URL . '/?page=profile&id=' . $followedUser['id']
    ];
}

echo json_encode($response);
?>