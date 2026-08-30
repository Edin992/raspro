<?php
/**
 * api/user/followers.php - Dohvati listu pratilaca
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

// Ukupan broj pratilaca (koristi funkciju iz functions.php)
$totalFollowers = getFollowersCount($userId);

// Lista pratilaca
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
            WHERE f2.follower_id = ? AND f2.following_id = u.id
        ) as is_followed_by_me
    FROM followers f
    INNER JOIN users u ON f.follower_id = u.id
    WHERE f.following_id = ?
    ORDER BY f.created_at DESC
    LIMIT ? OFFSET ?
");

$currentUserId = $_SESSION['user_id'] ?? 0;
$stmt->execute([$currentUserId, $userId, $limit, $offset]);
$followers = $stmt->fetchAll();

$response = [
    'success' => true,
    'data' => [
        'user' => [
            'id' => $user['id'],
            'username' => $user['username']
        ],
        'followers' => [],
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $totalFollowers,
            'pages' => ceil($totalFollowers / $limit),
            'has_more' => ($page * $limit) < $totalFollowers
        ]
    ]
];

foreach ($followers as $follower) {
    $response['data']['followers'][] = [
        'id' => $follower['id'],
        'username' => $follower['username'],
        'full_name' => trim($follower['first_name'] . ' ' . $follower['last_name']),
        'avatar' => !empty($follower['avatar']) ? $follower['avatar'] : SITE_URL . '/assets/images/defaults/avatar.png',
        'ads_count' => $follower['ads_count'],
        'is_verified' => (bool)$follower['is_verified'],
        'followed_date' => $follower['followed_date'],
        'is_followed_by_me' => (bool)$follower['is_followed_by_me'],
        'profile_url' => SITE_URL . '/?page=profile&id=' . $follower['id']
    ];
}

echo json_encode($response);
?>