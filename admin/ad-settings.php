<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$db = repdoc_db();
$settings = [];

if ($db) {
    try {
        $st = $db->query('SELECT setting_key, setting_value FROM ad_settings ORDER BY setting_key ASC');
        foreach ($st->fetchAll() as $row) {
            $settings[(string) $row['setting_key']] = (string) $row['setting_value'];
        }
    } catch (Throwable $e) {
        $db = null;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Security token mismatch.');
        redirect('ad-settings.php');
    }

    $keys = ['ads_enabled', 'default_rotation_mode', 'allow_popunder', 'ads_debug_mode'];
    foreach ($keys as $key) {
        $val = isset($_POST[$key]) ? '1' : '0';
        if ($key === 'default_rotation_mode') {
            $val = in_array((string) ($_POST[$key] ?? 'weighted'), ['weighted', 'priority', 'random'], true) ? (string) $_POST[$key] : 'weighted';
        }
        $check = $db->prepare('SELECT 1 FROM ad_settings WHERE setting_key = ? LIMIT 1');
        $check->execute([$key]);
        if ($check->fetch()) {
            $save = $db->prepare('UPDATE ad_settings SET setting_value = ? WHERE setting_key = ?');
            $save->execute([$val, $key]);
        } else {
            $save = $db->prepare('INSERT INTO ad_settings (setting_key, setting_value) VALUES (?, ?)');
            $save->execute([$key, $val]);
        }
    }

    flash_set('ok', 'Ad settings updated.');
    redirect('ad-settings.php');
}

admin_page_start('Ad Settings', 'ad-settings.php');
admin_notice();
?>

<div class="admin-page-head">
    <div>
        <p class="kicker">Monetization</p>
        <h1 class="admin-title">Ad Settings</h1>
        <p class="admin-sub">Control global rules for your Adsterra placements.</p>
    </div>
</div>

<?php if (!$db): ?>
    <div class="alert alert-error" role="alert">Database connection unavailable.</div>
<?php else: ?>

<form class="panel" method="post" action="ad-settings.php">
    <div class="panel-head">Global ad configuration</div>
    <div class="panel-body">
        <?= csrf_field() ?>
        <div class="field-row">
            <label class="field checkbox-inline" style="min-width:220px;">
                <input type="checkbox" name="ads_enabled" value="1" <?= ((string) ($settings['ads_enabled'] ?? '1')) === '1' ? 'checked' : '' ?>>
                Ads enabled globally
            </label>
            <label class="field checkbox-inline" style="min-width:220px;">
                <input type="checkbox" name="allow_popunder" value="1" <?= ((string) ($settings['allow_popunder'] ?? '0')) === '1' ? 'checked' : '' ?>>
                Allow popunder ads
            </label>
            <label class="field checkbox-inline" style="min-width:220px;">
                <input type="checkbox" name="ads_debug_mode" value="1" <?= ((string) ($settings['ads_debug_mode'] ?? '0')) === '1' ? 'checked' : '' ?>>
                Debug mode active
            </label>
        </div>

        <div class="field">
            <label for="default_rotation_mode">Default rotation mode</label>
            <select id="default_rotation_mode" name="default_rotation_mode">
                <option value="weighted" <?= ((string) ($settings['default_rotation_mode'] ?? 'weighted')) === 'weighted' ? 'selected' : '' ?>>Weighted rotation</option>
                <option value="priority" <?= ((string) ($settings['default_rotation_mode'] ?? 'weighted')) === 'priority' ? 'selected' : '' ?>>Priority order</option>
                <option value="random" <?= ((string) ($settings['default_rotation_mode'] ?? 'weighted')) === 'random' ? 'selected' : '' ?>>Random</option>
            </select>
        </div>

        <div class="field" style="margin-top:16px;">
            <button type="submit" class="btn btn-primary">Save settings</button>
        </div>
    </div>
</form>
<?php endif; ?>
<?php admin_page_end(); ?>
