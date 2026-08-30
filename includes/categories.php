<?php
/**
 * includes/categories.php - Funkcije za rad sa kategorijama
 */

/**
 * Dohvata glavne kategorije (bez roditelja)
 */
function getMainCategories() {
    try {
        $db = getDatabaseConnection();
        $stmt = $db->prepare("
            SELECT 
                c.id, 
                c.name, 
                c.slug, 
                c.icon, 
                c.ad_count,
                (SELECT COUNT(*) FROM categories WHERE parent_id = c.id) as has_subcategories
            FROM categories c
            WHERE c.parent_id IS NULL OR c.parent_id = 0
            ORDER BY c.sort_order, c.name
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Get main categories error: " . $e->getMessage());
        return [];
    }
}

/**
 * Dohvata sve kategorije (hijerarhijski)
 */
function getAllCategories($parentId = null, $depth = 0) {
    $db = getDatabaseConnection();
    
    $sql = "SELECT id, name, slug, parent_id FROM categories 
            WHERE parent_id " . 
            ($parentId ? "= ?" : "IS NULL OR parent_id = 0") . 
            " ORDER BY sort_order ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($parentId ? [$parentId] : []);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result = [];
    foreach ($categories as $category) {
        $category['depth'] = $depth;
        $result[] = $category;
        
        // Rekurzivno dohvati podkategorije
        $children = getAllCategories($category['id'], $depth + 1);
        $result = array_merge($result, $children);
    }
    
    return $result;
}

/**
 * Dohvata kategoriju po ID-u
 */
function getCategoryById($categoryId) {
    try {
        $db = getDatabaseConnection();
        $stmt = $db->prepare("SELECT id, name, slug, parent_id FROM categories WHERE id = ?");
        $stmt->execute([$categoryId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    } catch (Exception $e) {
        error_log("Get category by ID error: " . $e->getMessage());
        return null;
    }
}

/**
 * Dohvata kategoriju po slug-u (ZA SEO URL)
 */
function getCategoryBySlug($slug) {
    try {
        $db = getDatabaseConnection();
        
        $stmt = $db->prepare("
            SELECT * FROM categories 
            WHERE slug = ? 
            LIMIT 1
        ");
        
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("getCategoryBySlug error: " . $e->getMessage());
        return null;
    }
}

/**
 * Dohvata podkategoriju po slug-u (ZA SEO URL)
 */
function getSubcategoryBySlug($slug, $parentSlug = null) {
    try {
        $db = getDatabaseConnection();
        
        if ($parentSlug) {
            // Prvo nadji parent kategoriju po slug-u
            $stmt = $db->prepare("SELECT id FROM categories WHERE slug = ? AND (parent_id IS NULL OR parent_id = 0) LIMIT 1");
            $stmt->execute([$parentSlug]);
            $parent = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($parent) {
                // Onda traži podkategoriju sa tim parent_id
                $stmt = $db->prepare("SELECT * FROM categories WHERE slug = ? AND parent_id = ? LIMIT 1");
                $stmt->execute([$slug, $parent['id']]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result) {
                    return $result;
                }
            }
        }
        
        // Ako nema parent sluga, traži bilo koju kategoriju sa tim slug-om
        $stmt = $db->prepare("SELECT * FROM categories WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("getSubcategoryBySlug error: " . $e->getMessage());
        return null;
    }
}

/**
 * Dohvata slug kategorije po ID-u
 */
function getCategorySlug($categoryId) {
    $cat = getCategoryById($categoryId);
    if ($cat && !empty($cat['slug'])) {
        return $cat['slug'];
    }
    return (string)$categoryId;
}

/**
 * Formatira kategorije za select dropdown
 */
function getCategoriesForSelect() {
    $categories = getAllCategories();
    $options = [];
    
    foreach ($categories as $cat) {
        $prefix = str_repeat('— ', $cat['depth']);
        $options[$cat['id']] = $prefix . $cat['name'];
    }
    
    return $options;
}

/**
 * Povećava broj oglasa u kategoriji
 */
function incrementCategoryAdCount($categoryId) {
    $db = getDatabaseConnection();
    
    $stmt = $db->prepare("
        UPDATE categories 
        SET ad_count = ad_count + 1 
        WHERE id = ?
    ");
    
    return $stmt->execute([$categoryId]);
}

/**
 * Dohvata podkategorije za roditeljsku kategoriju
 */
function getChildCategories($parentId) {
    $db = getDatabaseConnection();
    
    $stmt = $db->prepare("
        SELECT id, name, slug, icon
        FROM categories 
        WHERE parent_id = ?
        ORDER BY name ASC
    ");
    
    $stmt->execute([$parentId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Smanjuje broj oglasa u kategoriji
 */
function decrementCategoryAdCount($categoryId) {
    $db = getDatabaseConnection();
    
    $stmt = $db->prepare("
        UPDATE categories 
        SET ad_count = GREATEST(0, ad_count - 1) 
        WHERE id = ?
    ");
    
    return $stmt->execute([$categoryId]);
}