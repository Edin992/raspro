<?php
/**
 * admin/categories.php - Upravljanje kategorijama
 */

// Debug mode (ukloni kada proradi)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);


require_once __DIR__ . '/includes/auth.php';
requireAdmin();

$pageTitle = 'Upravljanje kategorijama';
$db = getDatabaseConnection();
$message = '';
$messageType = '';
/*
function createSlug($string) {
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}
*/
// Dodavanje kategorije
if (isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    $slug = createSlug($name);
    $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $icon = trim($_POST['icon']);
    $sortOrder = (int)$_POST['sort_order'];
    $description = trim($_POST['description']);
    
    $stmt = $db->prepare("INSERT INTO categories (name, slug, parent_id, icon, description, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$name, $slug, $parentId, $icon, $description, $sortOrder])) {
        $message = 'Kategorija je uspešno dodata.';
        $messageType = 'success';
    } else {
        $message = 'Greška pri dodavanju kategorije.';
        $messageType = 'danger';
    }
}

// Izmena kategorije
if (isset($_POST['edit_category'])) {
    $id = (int)$_POST['id'];
    $name = trim($_POST['name']);
    $slug = createSlug($name);
    $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $icon = trim($_POST['icon']);
    $sortOrder = (int)$_POST['sort_order'];
    $description = trim($_POST['description']);
    
    $stmt = $db->prepare("UPDATE categories SET name = ?, slug = ?, parent_id = ?, icon = ?, description = ?, sort_order = ? WHERE id = ?");
    if ($stmt->execute([$name, $slug, $parentId, $icon, $description, $sortOrder, $id])) {
        $message = 'Kategorija je uspešno izmenjena.';
        $messageType = 'success';
    } else {
        $message = 'Greška pri izmeni kategorije.';
        $messageType = 'danger';
    }
}

// Brisanje kategorije
if (isset($_POST['delete_category'])) {
    $id = (int)$_POST['id'];
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM ads WHERE category_id = ?");
    $stmt->execute([$id]);
    $adsCount = $stmt->fetchColumn();
    
    if ($adsCount > 0) {
        $message = 'Ne možete obrisati kategoriju koja ima oglase.';
        $messageType = 'danger';
    } else {
        $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Kategorija je uspešno obrisana.';
        $messageType = 'success';
    }
}

// Dohvati kategorije
$stmt = $db->query("
    SELECT c.*, 
           (SELECT COUNT(*) FROM ads WHERE category_id = c.id) as ads_count,
           (SELECT COUNT(*) FROM categories WHERE parent_id = c.id) as subcategories_count
    FROM categories c
    ORDER BY c.sort_order ASC
");
$categories = $stmt->fetchAll();

$stmt = $db->query("SELECT id, name FROM categories ORDER BY name");
$allCategories = $stmt->fetchAll();


include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>


<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Kategorije</h5>
                <div class="card-tools">
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="fas fa-plus me-1"></i> Dodaj kategoriju
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> m-3">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>
                
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width:60px">ID</th>
                                <th>Kategorija</th>
                                <th>Slug</th>
                                <th>Ikona</th>
                                <th>Podkategorije</th>
                                <th>Oglasi</th>
                                <th>Redosled</th>
                                <th style="width:100px">Akcije</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    Nema kategorija. Kliknite "Dodaj kategoriju".
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td class="fw-bold">#<?php echo $cat['id']; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="fas <?php echo htmlspecialchars($cat['icon'] ?: 'fa-folder'); ?> me-2 text-primary"></i>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                        <?php if ($cat['description']): ?>
                                        <small class="text-muted ms-2">(<?php echo htmlspecialchars(substr($cat['description'], 0, 40)); ?>)</small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><code><?php echo htmlspecialchars($cat['slug']); ?></code></td>
                                <td><?php echo $cat['icon'] ? '<span class="badge bg-light">' . htmlspecialchars($cat['icon']) . '</span>' : '-'; ?></td>
                                <td><?php echo $cat['subcategories_count'] ? '<span class="badge bg-info">' . $cat['subcategories_count'] . '</span>' : '-'; ?></td>
                                <td><span class="badge bg-primary"><?php echo $cat['ads_count']; ?></span></td>
                                <td><?php echo $cat['sort_order']; ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editCategoryModal"
                                                data-id="<?php echo $cat['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($cat['name']); ?>"
                                                data-parent-id="<?php echo $cat['parent_id']; ?>"
                                                data-icon="<?php echo htmlspecialchars($cat['icon']); ?>"
                                                data-description="<?php echo htmlspecialchars($cat['description']); ?>"
                                                data-sort-order="<?php echo $cat['sort_order']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($cat['ads_count'] == 0): ?>
                                        <form method="POST" style="display:inline" onsubmit="return confirm('Obrisati kategoriju?')">
                                            <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                                            <button type="submit" name="delete_category" class="btn btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Dodaj kategoriju</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Naziv *</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Roditeljska kategorija</label>
                        <select class="form-select" name="parent_id">
                            <option value="">-- Bez roditelja --</option>
                            <?php foreach ($allCategories as $allCat): ?>
                            <option value="<?php echo $allCat['id']; ?>"><?php echo htmlspecialchars($allCat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ikona</label>
                        <input type="text" class="form-control" name="icon" placeholder="fa-car">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Opis</label>
                        <textarea class="form-control" name="description" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Redosled</label>
                        <input type="number" class="form-control" name="sort_order" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Otkaži</button>
                    <button type="submit" name="add_category" class="btn btn-primary">Dodaj</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Izmeni kategoriju</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label">Naziv *</label>
                        <input type="text" class="form-control" name="name" id="edit_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Roditeljska kategorija</label>
                        <select class="form-select" name="parent_id" id="edit_parent_id">
                            <option value="">-- Bez roditelja --</option>
                            <?php foreach ($allCategories as $allCat): ?>
                            <option value="<?php echo $allCat['id']; ?>"><?php echo htmlspecialchars($allCat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ikona</label>
                        <input type="text" class="form-control" name="icon" id="edit_icon">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Opis</label>
                        <textarea class="form-control" name="description" id="edit_description" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Redosled</label>
                        <input type="number" class="form-control" name="sort_order" id="edit_sort_order">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Otkaži</button>
                    <button type="submit" name="edit_category" class="btn btn-primary">Sačuvaj</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('[data-bs-target="#editCategoryModal"]').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('edit_id').value = this.dataset.id;
        document.getElementById('edit_name').value = this.dataset.name;
        document.getElementById('edit_parent_id').value = this.dataset.parentId || '';
        document.getElementById('edit_icon').value = this.dataset.icon || '';
        document.getElementById('edit_description').value = this.dataset.description || '';
        document.getElementById('edit_sort_order').value = this.dataset.sortOrder || 0;
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

