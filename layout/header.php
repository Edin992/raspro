<?php
/**
 * header.php - HTML HEAD deo sa meta tagovima, CSS, itd.
 */

// Postavi default title ako nije definisan
if (!isset($pageTitle)) {
    $pageTitle = 'Rasprodaja.rs - Brza kupovina i prodaja';
}

// Postavi default opis
$pageDescription = isset($pageDescription) ? $pageDescription : 
                   'Najveći oglasnik u Srbiji. Kupujte i prodajte brzo, lako i bezbedno.';
?>
<!DOCTYPE html>
<html lang="sr" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- META TAGOVI -->
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta name="keywords" content="rasprodaja, oglasi, prodaja, kupovina, polovni,polovni automobili, telefoni, novo, srbija">
    <meta name="author" content="Rasprodaja.rs">
    <meta name="robots" content="index, follow">
    
    <!-- Open Graph meta tagovi -->
    <?php if (isset($GLOBALS['og_tags'])): ?>
        <?php foreach ($GLOBALS['og_tags'] as $property => $content): ?>
            <?php if (strpos($property, 'twitter:') === 0): ?>
                <meta name="<?php echo $property; ?>" content="<?php echo $content; ?>">
            <?php else: ?>
                <meta property="<?php echo $property; ?>" content="<?php echo $content; ?>">
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- OPEN GRAPH / FACEBOOK -->
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo SITE_URL; ?>">
    <meta property="og:image" content="<?php echo SITE_URL; ?>/assets/images/logo/logo.png">
    
    <!-- TWITTER CARD -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    
    <!-- FAVICON -->
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo SITE_URL; ?>/assets/images/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo SITE_URL; ?>/assets/images/favicon/favicon-32x32.png">
    <link rel="manifest" href="<?php echo SITE_URL; ?>/assets/images/favicon/site.webmanifest">
    <link rel="icon" type="image/x-icon" href="<?php echo SITE_URL; ?>/assets/images/favicon.ico">
    
    <!-- BOOTSTRAP 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/bootstrap.min.css"> <!-- Fallback -->
    
    <!-- FONT AWESOME -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- GOOGLE FONTS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- CUSTOM CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/custom.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/responsive.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/navbar.css">
    <!-- PAGE SPECIFIC CSS -->
    <?php if (isset($pageSpecificCSS) && !empty($pageSpecificCSS)): ?>
        <?php foreach ($pageSpecificCSS as $cssFile): ?>
            <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/<?php echo $cssFile; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- INLINE STYLES (ako postoje) -->
    <?php if (isset($inlineStyles) && !empty($inlineStyles)): ?>
        <style><?php echo $inlineStyles; ?></style>
    <?php endif; ?>
    
    <!-- COOKIE CONSENT: bootstrap modal iz layout/cookie-consent.php (nije potreban CDN) -->
    
    <!-- ANALYTICS - FIX: gtag se vise NE ucitava bez pristanka.
         ID definisite u config/constants.php (GOOGLE_ANALYTICS_ID),
         a skripta je dinamicki ubacuje assets/js/cookies.js
         tek kad korisnik prihvati ANALITICKE kolacice (GDPR/ZZPL). -->
    <script>
        window.GOOGLE_ANALYTICS_ID = '<?php echo defined('GOOGLE_ANALYTICS_ID') ? GOOGLE_ANALYTICS_ID : ''; ?>';
    </script>
    <script>
        window.SITE_CONFIG = {
            url: '<?php echo SITE_URL; ?>',
            userId: '<?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; ?>',
            userPackage: '<?php echo isset($userPackage) ? $userPackage : 'free'; ?>',
            csrfToken: '<?php echo generateCSRFToken(); ?>',
            currentPage: '<?php echo $page; ?>',
            isLoggedIn: <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>
        };
    </script>
    <script>
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- ACCESSIBILITY SKIP LINK -->
    <a href="#main-content" class="visually-hidden-focusable">
        Preskoči na glavni sadržaj
    </a>
    
    <!-- LOADING OVERLAY (globalni) -->
    <div id="global-loading" class="position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50 d-none" style="z-index: 9999;">
        <div class="d-flex justify-content-center align-items-center h-100">
            <div class="text-center text-white">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Učitavanje...</span>
                </div>
                <p class="mt-3">Učitavanje...</p>
            </div>
        </div>
    </div>