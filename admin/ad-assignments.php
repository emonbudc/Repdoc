<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$db = repdoc_db();
$positions = [];
$ads = [];
$assignments = [];

if ($db) {
    try {
        $positions = $db->query('SELECT * FROM ad_positions ORDER BY name ASC')->fetchAll();
        $ads = $db->query('SELECT * FROM ads ORDER BY name ASC')->fetchAll();
        $assignments = $db->query('SELECT aa.*, p.slug AS position_slug, p.name AS position_name, a.name AS ad_name
                                   FROM ad_assignments aa
                                   LEFT JOIN ad_positions p ON p.id = aa.position_id
                                   LEFT JOIN ads a ON a.id = aa.ad_id
                                   ORDER BY aa.id DESC')->fetchAll();
    } catch (Throwable $e) {
        $db = null;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Security token mismatch.');
        redirect('ad-assignments.php');
    }

    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'save-assignment') {
        $id = max(0, (int) ($_POST['id'] ?? 0));
        $adId = max(0, (int) ($_POST['ad_id'] ?? 0));
        $positionId = max(0, (int) ($_POST['position_id'] ?? 0));
        $pageSlug = trim((string) ($_POST['page_slug'] ?? 'all')) !== '' ? trim((string) $_POST['page_slug']) : 'all';
        $deviceType = in_array((string) ($_POST['device_type'] ?? 'all'), ['all', 'desktop', 'mobile'], true) ? (string) $_POST['device_type'] : 'all';
        $enabled = isset($_POST['is_enabled']) ? 1 : 0;
        $priority = max(1, min(100, (int) ($_POST['priority'] ?? 10)));
        $weight = max(1, min(100, (int) ($_POST['rotation_weight'] ?? 10)));
        $start = trim((string) ($_POST['start_date'] ?? ''));
        $end = trim((string) ($_POST['end_date'] ?? ''));

        if ($adId < 1 || $positionId < 1) {
            flash_set('error', 'Select both an ad and a position.');
            redirect('ad-assignments.php');
        }

        if ($id > 0) {
            $st = $db->prepare('UPDATE ad_assignments SET ad_id = ?, position_id = ?, page_slug = ?, device_type = ?, is_enabled = ?, priority = ?, rotation_weight = ?, start_date = ?, end_date = ? WHERE id = ?');
            $st->execute([$adId, $positionId, $pageSlug, $deviceType, $enabled, $priority, $weight, $start !== '' ? $start : null, $end !== '' ? $end : null, $id]);
            flash_set('ok', 'Assignment updated.');
        } else {
            $st = $db->prepare('INSERT INTO ad_assignments (ad_id, position_id, page_slug, device_type, is_enabled, priority, rotation_weight, start_date, end_date, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $st->execute([$adId, $positionId, $pageSlug, $deviceType, $enabled, $priority, $weight, $start !== '' ? $start : null, $end !== '' ? $end : null, date('Y-m-d H:i:s')]);
            flash_set('ok', 'Assignment created.');
        }
        redirect('ad-assignments.php');
    }

    if ($action === 'delete-assignment') {
        $id = max(0, (int) ($_POST['id'] ?? 0));
        if ($id > 0) {
            $st = $db->prepare('DELETE FROM ad_assignments WHERE id = ?');
            $st->execute([$id]);
            flash_set('ok', 'Assignment deleted.');
        }
        redirect('ad-assignments.php');
    }
}

admin_page_start('Ad Assignments', 'ad-assignments.php');
admin_notice();
?>

<div class="admin-page-head">
    <div>
        <p class="kicker">Monetization</p>
        <h1 class="admin-title">Ad Assignments</h1>
        <p class="admin-sub">Link ads to slots and control where they show.</p>
    </div>
</div>

<?php if (!$db): ?>
    <div class="alert alert-error" role="alert">Database connection unavailable.</div>
<?php else: ?>

<form class="panel" method="post" action="ad-assignments.php">
    <div class="panel-head">Create assignment</div>
    <div class="panel-body">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save-assignment">

        <div class="field-row">
            <div class="field">
                <label for="ad-id">Ad</label>
                <select id="ad-id" name="ad_id" required>
                    <option value="">Choose an ad</option>
                    <?php foreach ($ads as $ad): ?>
                        <option value="<?= (int) $ad['id'] ?>"><?= e((string) $ad['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="position-id">Position</label>
                <select id="position-id" name="position_id" required>
                    <option value="">Choose a position</option>
                    <?php foreach ($positions as $pos): ?>
                        <option value="<?= (int) $pos['id'] ?>"><?= e((string) $pos['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="field-row">
            <div class="field">
                <label for="page-slug">Page slug</label>
                <input id="page-slug" type="text" name="page_slug" value="all" placeholder="all or home">
            </div>
            <div class="field">
                <label for="device-type">Device</label>
                <select id="device-type" name="device_type">
                    <option value="all">All</option>
                    <option value="desktop">Desktop</option>
                    <option value="mobile">Mobile</option>
                </select>
            </div>
        </div>

        <div class="field-row">
            <div class="field">
                <label for="priority">Priority</label>
                <input id="priority" type="number" name="priority" min="1" max="100" value="10">
            </div>
            <div class="field">
                <label for="rotation-weight">Rotation weight</label>
                <input id="rotation-weight" type="number" name="rotation_weight" min="1" max="100" value="10">
            </div>
            <div class="field" style="display:flex;align-items:end;">
                <label style="display:flex;align-items:center;gap:8px;min-height:44px;">
                    <input type="checkbox" name="is_enabled" value="1" checked>
                    Enabled
                </label>
            </div>
        </div>

        <div class="field-row">
            <div class="field">
                <label for="start-date">Start date</label>
                <input id="start-date" type="datetime-local" name="start_date">
            </div>
            <div class="field">
                <label for="end-date">End date</label>
                <input id="end-date" type="datetime-local" name="end_date">
            </div>
        </div>

        <div class="field" style="margin-top:16px;">
            <button type="submit" class="btn btn-primary">Save assignment</button>
        </div>
    </div>
</form>

<div class="panel">
    <div class="panel-head">All assignments</div>
    <div class="panel-body">
        <?php if (empty($assignments)): ?>
            <div class="empty-state-sm">No assignment has been created yet.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>Ad</th>
                            <th>Position</th>
                            <th>Page</th>
                            <th>Device</th>
                            <th>Enabled</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assignments as $row): ?>
                            <tr>
                                <td><?= e((string) ($row['ad_name'] ?? '—')) ?></td>
                                <td><?= e((string) ($row['position_name'] ?? $row['position_slug'] ?? '—')) ?></td>
                                <td><?= e((string) ($row['page_slug'] ?? 'all')) ?></td>
                                <td><?= e((string) ($row['device_type'] ?? 'all')) ?></td>
                                <td><?= ((int) $row['is_enabled']) === 1 ? 'Yes' : 'No' ?></td>
                                <td>
                                    <form method="post" action="ad-assignments.php" onsubmit="return confirm('Delete this assignment?');" style="display:inline;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete-assignment">
                                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
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
