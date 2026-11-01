<?php
declare(strict_types=1);

/**
 * Repdoc — admin: Adsterra ad management.
 * Stores ad code, schedules and placements in the central database so the
 * public pages render them without hardcoded snippets in templates.
 */

require_once __DIR__ . '/includes/layout.php';

$db = repdoc_db();
$editId = isset($_GET['edit']) ? max(0, (int) $_GET['edit']) : 0;
$editAd = null;
$positions = [];
$settings = [];
$ads = [];

if ($db) {
    try {
        $positions = $db->query('SELECT * FROM ad_positions WHERE is_active = 1 ORDER BY name ASC')->fetchAll();
        $ads = $db->query('SELECT a.*, p.slug AS position_slug, p.name AS position_name, aa.page_slug, aa.device_type
                            FROM ads a
                            LEFT JOIN ad_assignments aa ON aa.ad_id = a.id
                            LEFT JOIN ad_positions p ON p.id = aa.position_id
                            ORDER BY a.priority DESC, a.id DESC')->fetchAll();
        $st = $db->query('SELECT setting_key, setting_value FROM ad_settings');
        foreach ($st->fetchAll() as $row) {
            $settings[(string) $row['setting_key']] = (string) $row['setting_value'];
        }
        if ($editId > 0) {
            $st = $db->prepare('SELECT * FROM ads WHERE id = ? LIMIT 1');
            $st->execute([$editId]);
            $editAd = $st->fetch();
            if ($editAd) {
                $assignment = $db->prepare('SELECT aa.*, p.slug AS position_slug FROM ad_assignments aa LEFT JOIN ad_positions p ON p.id = aa.position_id WHERE aa.ad_id = ? ORDER BY aa.id DESC LIMIT 1');
                $assignment->execute([$editId]);
                $editAssignment = $assignment->fetch();
                if ($editAssignment) {
                    $editAd['position_slug'] = (string) ($editAssignment['position_slug'] ?? '');
                    $editAd['page_slug'] = (string) ($editAssignment['page_slug'] ?? 'all');
                    $editAd['device_type'] = (string) ($editAssignment['device_type'] ?? 'all');
                }
            }
        }
    } catch (Throwable $e) {
        $db = null;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Security token mismatch.');
        redirect('ads.php');
    }

    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'save-settings') {
        $enabled = isset($_POST['ads_enabled']) ? '1' : '0';
        $rotation = (string) ($_POST['default_rotation_mode'] ?? 'weighted');
        $allowPopunder = isset($_POST['allow_popunder']) ? '1' : '0';
        $debug = isset($_POST['ads_debug_mode']) ? '1' : '0';

        $saveKey = static function (PDO $dbx, string $key, string $value): void {
            $check = $dbx->prepare('SELECT 1 FROM ad_settings WHERE setting_key = ? LIMIT 1');
            $check->execute([$key]);
            if ($check->fetch()) {
                $up = $dbx->prepare('UPDATE ad_settings SET setting_value = ? WHERE setting_key = ?');
                $up->execute([$value, $key]);
            } else {
                $in = $dbx->prepare('INSERT INTO ad_settings (setting_key, setting_value) VALUES (?, ?)');
                $in->execute([$key, $value]);
            }
        };

        $saveKey($db, 'ads_enabled', $enabled);
        $saveKey($db, 'default_rotation_mode', $rotation);
        $saveKey($db, 'allow_popunder', $allowPopunder);
        $saveKey($db, 'ads_debug_mode', $debug);

        flash_set('ok', 'Ad settings saved.');
        redirect('ads.php');
    }

    if ($action === 'save-ad') {
        $adId = isset($_POST['ad_id']) ? max(0, (int) $_POST['ad_id']) : 0;
        $name = trim((string) ($_POST['name'] ?? ''));
        $type = trim((string) ($_POST['type'] ?? 'banner'));
        $code = (string) ($_POST['code'] ?? '');
        $status = in_array((string) ($_POST['status'] ?? 'active'), ['active', 'draft', 'paused'], true)
            ? (string) $_POST['status']
            : 'active';
        $startDate = trim((string) ($_POST['start_date'] ?? ''));
        $endDate = trim((string) ($_POST['end_date'] ?? ''));
        $priority = max(1, min(100, (int) ($_POST['priority'] ?? 10)));
        $rotationWeight = max(1, min(100, (int) ($_POST['rotation_weight'] ?? 10)));
        $deviceTarget = in_array((string) ($_POST['device_target'] ?? 'all'), ['all', 'desktop', 'mobile'], true)
            ? (string) $_POST['device_target']
            : 'all';
        $positionSlug = trim((string) ($_POST['position_slug'] ?? ''));
        $pageSlug = trim((string) ($_POST['page_slug'] ?? 'all')) !== '' ? trim((string) $_POST['page_slug']) : 'all';
        $deviceType = in_array((string) ($_POST['assignment_device'] ?? 'all'), ['all', 'desktop', 'mobile'], true)
            ? (string) $_POST['assignment_device']
            : 'all';

        if ($name === '' || $code === '' || $positionSlug === '') {
            flash_set('error', 'Ad name, code and a valid position are required.');
            redirect('ads.php');
        }

        if ($adId > 0) {
            $st = $db->prepare('UPDATE ads SET name = ?, type = ?, code = ?, status = ?, start_date = ?, end_date = ?, priority = ?, rotation_weight = ?, device_target = ?, is_active = 1, updated_at = ? WHERE id = ?');
            $st->execute([$name, $type, $code, $status, $startDate !== '' ? $startDate : null, $endDate !== '' ? $endDate : null, $priority, $rotationWeight, $deviceTarget, date('Y-m-d H:i:s'), $adId]);
            $targetId = $adId;
        } else {
            $st = $db->prepare('INSERT INTO ads (name, type, code, status, start_date, end_date, priority, rotation_weight, device_target, is_active, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 0, ?, ?)');
            $st->execute([$name, $type, $code, $status, $startDate !== '' ? $startDate : null, $endDate !== '' ? $endDate : null, $priority, $rotationWeight, $deviceTarget, date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]);
            $targetId = (int) $db->lastInsertId();
        }

        $posCheck = $db->prepare('SELECT id FROM ad_positions WHERE slug = ? LIMIT 1');
        $posCheck->execute([$positionSlug]);
        $positionId = $posCheck->fetchColumn();
        if ($positionId === false || $positionId === null || $positionId === '') {
            $ins = $db->prepare('INSERT INTO ad_positions (slug, name, description, is_active, created_at) VALUES (?, ?, ?, 1, ?)');
            $ins->execute([$positionSlug, ucfirst(str_replace(['-', '_'], ' ', $positionSlug)), 'Created from ad manager', date('Y-m-d H:i:s')]);
            $positionId = (int) $db->lastInsertId();
        }

        $assignCheck = $db->prepare('SELECT id FROM ad_assignments WHERE ad_id = ? AND position_id = ? LIMIT 1');
        $assignCheck->execute([$targetId, (int) $positionId]);
        $assignmentId = $assignCheck->fetchColumn();
        if ($assignmentId === false || $assignmentId === null || $assignmentId === '') {
            $insAssign = $db->prepare('INSERT INTO ad_assignments (ad_id, position_id, page_slug, device_type, is_enabled, priority, rotation_weight, start_date, end_date, created_at) VALUES (?, ?, ?, ?, 1, ?, ?, ?, ?, ?)');
            $insAssign->execute([$targetId, (int) $positionId, $pageSlug, $deviceType, $priority, $rotationWeight, $startDate !== '' ? $startDate : null, $endDate !== '' ? $endDate : null, date('Y-m-d H:i:s')]);
        } else {
            $updAssign = $db->prepare('UPDATE ad_assignments SET page_slug = ?, device_type = ?, priority = ?, rotation_weight = ?, start_date = ?, end_date = ?, is_enabled = 1 WHERE id = ?');
            $updAssign->execute([$pageSlug, $deviceType, $priority, $rotationWeight, $startDate !== '' ? $startDate : null, $endDate !== '' ? $endDate : null, (int) $assignmentId]);
        }

        flash_set('ok', 'Ad saved successfully.');
        redirect('ads.php');
    }

    if ($action === 'delete-ad') {
        $adId = max(0, (int) ($_POST['ad_id'] ?? 0));
        if ($adId > 0) {
            $st = $db->prepare('DELETE FROM ads WHERE id = ?');
            $st->execute([$adId]);
            flash_set('ok', 'Ad removed.');
        }
        redirect('ads.php');
    }

    if ($action === 'toggle-ad') {
        $adId = max(0, (int) ($_POST['ad_id'] ?? 0));
        if ($adId > 0) {
            $st = $db->prepare('UPDATE ads SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END, updated_at = ? WHERE id = ?');
            $st->execute([date('Y-m-d H:i:s'), $adId]);
            flash_set('ok', 'Ad status changed.');
        }
        redirect('ads.php');
    }
}

admin_page_start('Adsterra Ads', 'ads.php');
admin_notice();
?>

<div class="admin-page-head">
    <div>
        <p class="kicker">Monetization</p>
        <h1 class="admin-title">Adsterra Ads</h1>
        <p class="admin-sub">Manage ad blocks, placements and page targeting from one place.</p>
    </div>
</div>

<?php if (!$db): ?>
    <div class="alert alert-error" role="alert">Could not connect to the database. Make sure the central DB is available.</div>
<?php else: ?>

<form class="panel" method="post" action="ads.php">
    <div class="panel-head">Global ad settings</div>
    <div class="panel-body">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save-settings">

        <div class="field-row">
            <label class="field checkbox-inline" style="min-width:180px;">
                <input type="checkbox" name="ads_enabled" value="1" <?= ((string) ($settings['ads_enabled'] ?? '1')) === '1' ? 'checked' : '' ?>>
                Ads enabled globally
            </label>
            <label class="field checkbox-inline" style="min-width:180px;">
                <input type="checkbox" name="allow_popunder" value="1" <?= ((string) ($settings['allow_popunder'] ?? '0')) === '1' ? 'checked' : '' ?>>
                Allow popunder
            </label>
            <label class="field checkbox-inline" style="min-width:180px;">
                <input type="checkbox" name="ads_debug_mode" value="1" <?= ((string) ($settings['ads_debug_mode'] ?? '0')) === '1' ? 'checked' : '' ?>>
                Debug mode
            </label>
        </div>

        <div class="field-row">
            <div class="field">
                <label for="default_rotation_mode">Rotation mode</label>
                <select id="default_rotation_mode" name="default_rotation_mode">
                    <option value="weighted" <?= ((string) ($settings['default_rotation_mode'] ?? 'weighted')) === 'weighted' ? 'selected' : '' ?>>Weighted rotation</option>
                    <option value="priority" <?= ((string) ($settings['default_rotation_mode'] ?? 'weighted')) === 'priority' ? 'selected' : '' ?>>Priority order</option>
                    <option value="random" <?= ((string) ($settings['default_rotation_mode'] ?? 'weighted')) === 'random' ? 'selected' : '' ?>>Random</option>
                </select>
            </div>
            <div class="field" style="display:flex;align-items:end;">
                <button class="btn btn-primary" type="submit">Save settings</button>
            </div>
        </div>
    </div>
</form>

<form class="panel" method="post" action="ads.php">
    <div class="panel-head"><?= $editAd ? 'Edit Ad' : 'Create Ad' ?></div>
    <div class="panel-body">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save-ad">
        <?php if ($editAd): ?>
            <input type="hidden" name="ad_id" value="<?= (int) $editAd['id'] ?>">
        <?php endif; ?>

        <div class="field-row">
            <div class="field">
                <label for="ad-name">Ad name</label>
                <input id="ad-name" name="name" type="text" value="<?= e($editAd['name'] ?? '') ?>" placeholder="Homepage banner ad" required>
            </div>
            <div class="field">
                <label for="ad-type">Ad type</label>
                <select id="ad-type" name="type">
                    <option value="banner" <?= (($editAd['type'] ?? 'banner') === 'banner') ? 'selected' : '' ?>>Banner</option>
                    <option value="native" <?= (($editAd['type'] ?? 'banner') === 'native') ? 'selected' : '' ?>>Native</option>
                    <option value="popup" <?= (($editAd['type'] ?? 'banner') === 'popup') ? 'selected' : '' ?>>Popup</option>
                    <option value="sidebar" <?= (($editAd['type'] ?? 'banner') === 'sidebar') ? 'selected' : '' ?>>Sidebar</option>
                </select>
            </div>
        </div>

        <div class="field-row">
            <div class="field">
                <label for="position-slug">Position slug</label>
                <input id="position-slug" name="position_slug" type="text" value="<?= e($editAd['position_slug'] ?? 'home_top_banner') ?>" placeholder="home_top_banner" required>
            </div>
            <div class="field">
                <label for="page-slug">Page slug</label>
                <input id="page-slug" name="page_slug" type="text" value="<?= e($editAd['page_slug'] ?? 'all') ?>" placeholder="all or home">
            </div>
        </div>

        <div class="field-row">
            <div class="field">
                <label for="device-target">Target device</label>
                <select id="device-target" name="device_target">
                    <option value="all" <?= (($editAd['device_target'] ?? 'all') === 'all') ? 'selected' : '' ?>>All devices</option>
                    <option value="desktop" <?= (($editAd['device_target'] ?? 'all') === 'desktop') ? 'selected' : '' ?>>Desktop only</option>
                    <option value="mobile" <?= (($editAd['device_target'] ?? 'all') === 'mobile') ? 'selected' : '' ?>>Mobile only</option>
                </select>
            </div>
            <div class="field">
                <label for="assignment-device">Assignment device</label>
                <select id="assignment-device" name="assignment_device">
                    <option value="all" <?= (($editAd['device_type'] ?? 'all') === 'all') ? 'selected' : '' ?>>All</option>
                    <option value="desktop" <?= (($editAd['device_type'] ?? 'all') === 'desktop') ? 'selected' : '' ?>>Desktop</option>
                    <option value="mobile" <?= (($editAd['device_type'] ?? 'all') === 'mobile') ? 'selected' : '' ?>>Mobile</option>
                </select>
            </div>
        </div>

        <div class="field-row">
            <div class="field">
                <label for="priority">Priority</label>
                <input id="priority" name="priority" type="number" min="1" max="100" value="<?= (int) ($editAd['priority'] ?? 10) ?>">
            </div>
            <div class="field">
                <label for="rotation-weight">Rotation weight</label>
                <input id="rotation-weight" name="rotation_weight" type="number" min="1" max="100" value="<?= (int) ($editAd['rotation_weight'] ?? 10) ?>">
            </div>
            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="active" <?= (($editAd['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Active</option>
                    <option value="draft" <?= (($editAd['status'] ?? 'active') === 'draft') ? 'selected' : '' ?>>Draft</option>
                    <option value="paused" <?= (($editAd['status'] ?? 'active') === 'paused') ? 'selected' : '' ?>>Paused</option>
                </select>
            </div>
        </div>

        <div class="field-row">
            <div class="field">
                <label for="start-date">Start date</label>
                <input id="start-date" name="start_date" type="datetime-local" value="<?= e(($editAd['start_date'] ?? '') !== '' ? date('Y-m-d\TH:i', strtotime((string) ($editAd['start_date'] ?? ''))) : '') ?>">
            </div>
            <div class="field">
                <label for="end-date">End date</label>
                <input id="end-date" name="end_date" type="datetime-local" value="<?= e(($editAd['end_date'] ?? '') !== '' ? date('Y-m-d\TH:i', strtotime((string) ($editAd['end_date'] ?? ''))) : '') ?>">
            </div>
        </div>

        <div class="field">
            <label for="ad-code">Ad script / HTML</label>
            <textarea id="ad-code" name="code" rows="10" placeholder="Paste your Adsterra ad HTML or JS snippet here." required><?= e($editAd['code'] ?? '') ?></textarea>
        </div>

        <div class="field" style="margin-top:16px;">
            <button type="submit" class="btn btn-primary"><?= $editAd ? 'Update ad' : 'Create ad' ?></button>
            <?php if ($editAd): ?>
                <a class="btn btn-secondary" href="ads.php">Cancel</a>
            <?php endif; ?>
        </div>
    </div>
</form>

<div class="panel">
    <div class="panel-head">
        Existing ads
    </div>
    <div class="panel-body">
        <?php if (empty($ads)): ?>
            <div class="empty-state-sm">No ads created yet. Add a banner or sidebar slot to start monetizing.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Position</th>
                            <th>Page</th>
                            <th>Priority</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ads as $row): ?>
                            <tr>
                                <td><?= e((string) $row['name']) ?></td>
                                <td><?= e((string) $row['type']) ?></td>
                                <td>
                                    <span class="pill <?= ((int) $row['is_active']) === 1 ? 'pill-approved' : 'pill-pending' ?>">
                                        <?= ((int) $row['is_active']) === 1 ? 'Enabled' : 'Disabled' ?>
                                    </span>
                                </td>
                                <td><?= e((string) ($row['position_name'] ?? $row['position_slug'] ?? '—')) ?></td>
                                <td><?= e((string) ($row['page_slug'] ?? 'all')) ?></td>
                                <td><?= (int) $row['priority'] ?></td>
                                <td>
                                    <div class="inline-actions">
                                        <a class="btn btn-secondary btn-sm" href="ads.php?edit=<?= (int) $row['id'] ?>">Edit</a>
                                        <form method="post" action="ads.php" style="display:inline;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="toggle-ad">
                                            <input type="hidden" name="ad_id" value="<?= (int) $row['id'] ?>">
                                            <button type="submit" class="btn btn-sm <?= ((int) $row['is_active']) === 1 ? 'btn-danger' : 'btn-primary' ?>"><?= ((int) $row['is_active']) === 1 ? 'Disable' : 'Enable' ?></button>
                                        </form>
                                        <form method="post" action="ads.php" style="display:inline;" onsubmit="return confirm('Delete this ad?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete-ad">
                                            <input type="hidden" name="ad_id" value="<?= (int) $row['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php admin_page_end(); ?>
