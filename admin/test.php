<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "1. Početak fajla<br>";

require_once __DIR__ . '/includes/auth.php';
echo "2. Auth uključen<br>";

requireAdmin();
echo "3. Admin provera prošla<br>";

$pageTitle = 'Test stranica';
echo "4. Title postavljen<br>";

include __DIR__ . '/includes/header.php';
echo "5. Header uključen<br>";

include __DIR__ . '/includes/sidebar.php';
echo "6. Sidebar uključen<br>";

echo "<div class='card'><div class='card-body'>";
echo "<h1>TEST STRANICA RADI!</h1>";
echo "<p>Ako ovo vidite, problem je u categories.php</p>";
echo "</div></div>";

include __DIR__ . '/includes/footer.php';
echo "7. Footer uključen<br>";
?>