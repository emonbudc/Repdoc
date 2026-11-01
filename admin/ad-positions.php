<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$db = repdoc_db();
$editId = isset($_GET['edit']) ? max(0, (int) $_GET['edit']) : 0;
$editPosition = null;

if ($db && $editId > 0) {
    try {
        $st = $db->prepare('SELECT * FROM ad_positions WHERE id = ? LIMIT 1');
        $st->execute([$editId]);
        $editPosition = $st->fetch();
    } catch (Throwable $e) {
        $db = null;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Security token mismatch.');
        redirect('ad-positions.php');
    }

    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'save-position') {
        $id = max(0, (int) ($_POST['id'] ?? 0));
        $slug = trim((string) ($_POST['slug'] ?? ''));
        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));

        if ($slug === '' || $name === '') {
            flash_set('error', 'Position slug and name are required.');
            redirect('ad-positions.php');
        }

        if ($id > 0) {
            $st = $db->prepare('UPDATE ad_positions SET slug = ?, name = ?, description = ? WHERE id = ?');
            $st->execute([$slug, $name, $description, $id]);
            flash_set('ok', 'Position updated.');
        } else {
            $st = $db->prepare('INSERT INTO ad_positions (slug, name, description, is_active, created_at) VALUES (?, ?, ?, 1, ?)');
            $st->execute([$slug, $name, $description, date('Y-m-d H:i:s')]);
            flash_set('ok', 'Position created.');
        }
        redirect('ad-positions.php');
    }

    if ($action === 'delete-position') {
        $id = max(0, (int) ($_POST['id'] ?? 0));
        if ($id > 0) {
            $st = $db->prepare('DELETE FROM ad_positions WHERE id = ?');
            $st->execute([$id]);
            flash_set('ok', 'Position deleted.');
        }
        redirect('ad-positions.php');
    }
}

$positions = [];
if ($db) {
    try {
        $positions = $db->query('SELECT * FROM ad_positions ORDER BY name ASC')->fetchAll();
    } catch (Throwable $e) {
        $db = null;
    }
}

admin_page_start('Ad Positions', 'ad-positions.php');
admin_notice();
?>

<div class="admin-page-head">
    <div>
        <p class="kicker">Monetization</p>
        <h1 class="admin-title">Ad Positions</h1>
        <p class="admin-sub">Define where ad slots appear on the site.</p>
    </div>
</div>

<?php if (!$db): ?>
    <div class="alert alert-error" role="alert">Database connection unavailable.</div>
<?php else: ?>

<form class="panel" method="post" action="ad-positions.php">
    <div class="panel-head"><?= $editPosition ? 'Edit position' : 'Create position' ?></div>
    <div class="panel-body">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save-position">
        <?php if ($editPosition): ?>
            <input type="hidden" name="id" value="<?= (int) $editPosition['id'] ?>">
        <?php endif; ?>

        <div class="field-row">
            <div class="field">
                <label for="position-slug">Slug</label>
                <input id="position-slug" type="text" name="slug" value="<?= e($editPosition['slug'] ?? '') ?>" placeholder="home_top_banner" required>
            </div>
            <div class="field">
                <label for="position-name">Name</label>
                <input id="position-name" type="text" name="name" value="<?= e($editPosition['name'] ?? '') ?>" placeholder="Homepage Top Banner" required>
            </div>
        </div>

        <div class="field">
            <label for="position-desc">Description</label>
            <textarea id="position-desc" name="description" rows="4" placeholder="Where this block appears on the site."><?= e($editPosition['description'] ?? '') ?></textarea>
        </div>

        <div class="field" style="margin-top:16px;">
            <button class="btn btn-primary" type="submit"><?= $editPosition ? 'Update position' : 'Create position' ?></button>
            <?php if ($editPosition): ?>
                <a class="btn btn-secondary" href="ad-positions.php">Cancel</a>
            <?php endif; ?>
        </div>
    </div>
</form>

<div class="panel">
    <div class="panel-head">Position list</div>
    <div class="panel-body">
        <?php if (empty($positions)): ?>
            <div class="empty-state-sm">No positions have been created yet.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>Slug</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($positions as $pos): ?>
                            <tr>
                                <td><?= e((string) $pos['slug']) ?></td>
                                <td><?= e((string) $pos['name']) ?></td>
                                <td><?= ((int) $pos['is_active']) === 1 ? 'Active' : 'Inactive' ?></td>
                                <td><?= e((string) ($pos['description'] ?? '')) ?></td>
                                <td>
                                    <div class="inline-actions">
                                        <a class="btn btn-secondary btn-sm" href="ad-positions.php?edit=<?= (int) $pos['id'] ?>">Edit</a>
                                        <form method="post" action="ad-positions.php" onsubmit="return confirm('Delete this position?');" style="display:inline;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete-position">
                                            <input type="hidden" name="id" value="<?= (int) $pos['id'] ?>">
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
