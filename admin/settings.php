<?php
/**
 * admin/settings.php - Podešavanja sajta
 */

require_once __DIR__ . '/includes/auth.php';
requireAdmin();

$pageTitle = 'Podešavanja';
$db = getDatabaseConnection();
$message = '';
$messageType = '';

// Sačuvaj podešavanja
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'setting_') === 0) {
            $settingKey = substr($key, 8);
            updateSetting($settingKey, $value);
        }
    }
    $message = 'Podešavanja su uspešno sačuvana.';
    $messageType = 'success';
}

// Dohvati sva podešavanja po grupama
$settings = [];
$stmt = $db->query("SELECT * FROM settings ORDER BY `group`, sort_order");
while ($row = $stmt->fetch()) {
    $settings[$row['group']][] = $row;
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="card">
    <div class="card-header">
        <h5 class="card-title">Podešavanja sajta</h5>
    </div>
    <div class="card-body">
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <!-- Opšta podešavanja -->
            <div class="settings-section">
                <h5 class="settings-section-title">
                    <i class="fas fa-globe me-2"></i> Opšta podešavanja
                </h5>
                <div class="row g-3">
                    <?php foreach ($settings['general'] ?? [] as $setting): ?>
                    <div class="col-md-6">
                        <label class="form-label"><?php echo htmlspecialchars($setting['label']); ?></label>
                        <?php if ($setting['setting_type'] === 'textarea'): ?>
                        <textarea class="form-control" name="setting_<?php echo $setting['setting_key']; ?>" rows="3"><?php echo htmlspecialchars($setting['setting_value']); ?></textarea>
                        <?php else: ?>
                        <input type="<?php echo $setting['setting_type']; ?>" class="form-control" name="setting_<?php echo $setting['setting_key']; ?>" value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                        <?php endif; ?>
                        <?php if ($setting['description']): ?>
                        <div class="form-text"><?php echo htmlspecialchars($setting['description']); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Društvene mreže -->
            <div class="settings-section">
                <h5 class="settings-section-title">
                    <i class="fas fa-share-alt me-2"></i> Društvene mreže
                </h5>
                <div class="row g-3">
                    <?php foreach ($settings['social'] ?? [] as $setting): ?>
                    <div class="col-md-6">
                        <label class="form-label"><?php echo htmlspecialchars($setting['label']); ?></label>
                        <input type="text" class="form-control" name="setting_<?php echo $setting['setting_key']; ?>" value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Oglasi podešavanja -->
            <div class="settings-section">
                <h5 class="settings-section-title">
                    <i class="fas fa-tags me-2"></i> Oglasi
                </h5>
                <div class="row g-3">
                    <?php foreach ($settings['ads'] ?? [] as $setting): ?>
                    <div class="col-md-6">
                        <label class="form-label"><?php echo htmlspecialchars($setting['label']); ?></label>
                        <input type="number" class="form-control" name="setting_<?php echo $setting['setting_key']; ?>" value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Sistem podešavanja -->
            <div class="settings-section">
                <h5 class="settings-section-title">
                    <i class="fas fa-cog me-2"></i> Sistem
                </h5>
                <div class="row g-3">
                    <?php foreach ($settings['system'] ?? [] as $setting): ?>
                    <div class="col-md-6">
                        <label class="form-label"><?php echo htmlspecialchars($setting['label']); ?></label>
                        <select class="form-select" name="setting_<?php echo $setting['setting_key']; ?>">
                            <option value="0" <?php echo $setting['setting_value'] == '0' ? 'selected' : ''; ?>>Ne</option>
                            <option value="1" <?php echo $setting['setting_value'] == '1' ? 'selected' : ''; ?>>Da</option>
                        </select>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Sigurnosna podešavanja -->
            <div class="settings-section">
                <h5 class="settings-section-title">
                    <i class="fas fa-shield-alt me-2"></i> Sigurnost
                </h5>
                <div class="row g-3">
                    <?php foreach ($settings['security'] ?? [] as $setting): ?>
                    <div class="col-md-6">
                        <label class="form-label"><?php echo htmlspecialchars($setting['label']); ?></label>
                        <input type="text" class="form-control" name="setting_<?php echo $setting['setting_key']; ?>" value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Email podešavanja -->
            <div class="settings-section">
                <h5 class="settings-section-title">
                    <i class="fas fa-envelope me-2"></i> Email
                </h5>
                <div class="row g-3">
                    <?php foreach ($settings['email'] ?? [] as $setting): ?>
                    <div class="col-md-6">
                        <label class="form-label"><?php echo htmlspecialchars($setting['label']); ?></label>
                        <input type="text" class="form-control" name="setting_<?php echo $setting['setting_key']; ?>" value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i> Sačuvaj podešavanja
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>