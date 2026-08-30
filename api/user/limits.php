<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/packages.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Niste prijavljeni']);
    exit();
}

$userId = $_SESSION['user_id'];
$limits = getUserLimits($userId);

// Za prikaz u JS-u
$adLimitText = $limits['is_unlimited'] ? 'neograničeno' : $limits['ad_limit'];
$remainingText = $limits['is_unlimited'] ? 'neograničeno' : $limits['remaining_ads'];

echo json_encode([
    'success' => true,
    'package' => $limits['package'],
    'image_limit' => $limits['image_limit'],
    'ad_limit' => $limits['ad_limit'],
    'ad_limit_display' => $adLimitText,
    'current_ads' => $limits['current_ads'],
    'remaining_ads' => $limits['remaining_ads'],
    'remaining_display' => $remainingText,
    'is_unlimited' => $limits['is_unlimited'],
    'can_create_ad' => $limits['can_create_ad']
]);