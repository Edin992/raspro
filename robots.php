<?php
/**
 * robots.php - Dinamički robots.txt
 */

header('Content-Type: text/plain; charset=utf-8');

$isProduction = ($_SERVER['HTTP_HOST'] !== 'localhost' && $_SERVER['HTTP_HOST'] !== '127.0.0.1');

// Ako je development, zabrani sve
if (!$isProduction) {
    echo "User-agent: *\n";
    echo "Disallow: /\n";
    echo "\n";
    echo "# Development environment - no indexing\n";
    exit;
}

// Production robots.txt
echo "# robots.txt za Rasprodaja.rs\n";
echo "# Generated: " . date('Y-m-d H:i:s') . "\n\n";

echo "User-agent: *\n";
echo "Allow: /\n\n";

// Dozvoli važne stranice
echo "# DOZVOLI - VAŽNE STRANICE ZA SEO\n";
echo "Allow: /$\n";
echo "Allow: /ads\n";
echo "Allow: /ad/\n";
echo "Allow: /categories\n";
echo "Allow: /how-it-works\n";
echo "Allow: /contact\n";
echo "Allow: /about\n";
echo "Allow: /faq\n";
echo "Allow: /safety\n";
echo "Allow: /terms\n";
echo "Allow: /privacy\n";
echo "Allow: /cookies\n";
echo "Allow: /packages\n";
echo "Allow: /premium-ads\n";
echo "Allow: /profile/\n";
echo "Allow: /sitemap.xml\n\n";

// Zabrani admin i sistemske fajlove
echo "# ZABRANI - ADMIN PANEL (SIGURNOST!)\n";
echo "Disallow: /admin/\n";
echo "Disallow: /admin/*\n\n";

echo "# ZABRANI - API I SISTEMSKI FAJLOVI\n";
echo "Disallow: /api/\n";
echo "Disallow: /includes/\n";
echo "Disallow: /layout/\n";
echo "Disallow: /config/\n";
echo "Disallow: /libs/\n\n";

echo "# ZABRANI - DUPLI SADRŽAJ (SEO)\n";
echo "Disallow: /*?page=\n";
echo "Disallow: /?page=\n";
echo "Disallow: /*&page=\n";
echo "Disallow: /*?*\n";
echo "Disallow: /index.php\n\n";

echo "# ZABRANI - PRIVATNE STRANICE\n";
echo "Disallow: /login\n";
echo "Disallow: /register\n";
echo "Disallow: /logout\n";
echo "Disallow: /dashboard\n";
echo "Disallow: /messages\n";
echo "Disallow: /create-ad\n";
echo "Disallow: /edit-ad/\n";
echo "Disallow: /verify-email\n";
echo "Disallow: /forgot-password\n";
echo "Disallow: /reset-password/\n\n";

echo "# ZABRANI - STATIČKE RESURSE\n";
echo "Disallow: /assets/css/\n";
echo "Disallow: /assets/js/\n";
echo "Disallow: /assets/images/\n";
echo "Disallow: /assets/uploads/\n\n";

echo "# SITEMAP LOKACIJA\n";
echo "Sitemap: https://" . $_SERVER['HTTP_HOST'] . "/sitemap.xml\n\n";


?>