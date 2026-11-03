<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/ad-catalog.php';

$db = repdoc_db();
$catalog = repdoc_ad_catalog();
$editId = isset($_GET['edit']) ? max(0, (int) $_GET['edit']) : 0;
$editPosition = null;
$editBuiltIn = false;

if ($db && $editId > 0) {
    try {
        $st = $db->prepare('SELECT * FROM ad_positions WHERE id = ? LIMIT 1');
        $st->execute([$editId]);
        $editPosition = $st->fetch();
        if ($editPosition) {
            $editBuiltIn = isset($catalog[(string) $editPosition['slug']]);
        }
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
        $builtIn = false;

        if ($id > 0) {
            $current = $db->prepare('SELECT slug FROM ad_positions WHERE id = ? LIMIT 1');
            $current->execute([$id]);
            $currentSlug = (string) ($current->fetchColumn() ?: '');
            $builtIn = isset($catalog[$currentSlug]);
            if ($builtIn) {
                $slug = $currentSlug;
            }
        }

        if ($slug === '' || $name === '') {
            flash_set('error', 'A placement needs both a name and a slug.');
            redirect('ad-positions.php' . ($id > 0 ? '?edit=' . $id : ''));
        }

        try {
            if ($id > 0) {
                $st = $db->prepare('UPDATE ad_positions SET slug = ?, name = ?, description = ? WHERE id = ?');
                $st->execute([$slug, $name, $description, $id]);
                flash_set('ok', $builtIn ? 'Placement renamed. Its built-in slug is unchanged.' : 'Placement updated.');
            } else {
                $st = $db->prepare('INSERT INTO ad_positions (slug, name, description, is_active, created_at) VALUES (?, ?, ?, 1, ?)');
                $st->execute([$slug, $name, $description, date('Y-m-d H:i:s')]);
                flash_set('ok', 'Custom placement created.');
            }
        } catch (Throwable $e) {
            flash_set('error', 'That slug is already used. Choose a different one.');
        }
        redirect('ad-positions.php');
    }

    if ($action === 'delete-position') {
        $id = max(0, (int) ($_POST['id'] ?? 0));
        if ($id > 0) {
            $st = $db->prepare('SELECT slug FROM ad_positions WHERE id = ? LIMIT 1');
            $st->execute([$id]);
            $slug = (string) ($st->fetchColumn() ?: '');
            if (isset($catalog[$slug])) {
                flash_set('error', 'Built-in placements cannot be deleted.');
            } else {
                $del = $db->prepare('DELETE FROM ad_positions WHERE id = ?');
                $del->execute([$id]);
                flash_set('ok', 'Custom placement removed.');
            }
        }
        redirect('ad-positions.php');
    }
}

$positions = [];
if ($db) {
    try {
        $positions = $db->query('SELECT p.*,
                                        (SELECT COUNT(*) FROM ad_assignments aa WHERE aa.position_id = p.id) AS ad_count,
                                        (SELECT COUNT(*) FROM ad_assignments aa
                                         INNER JOIN ads a ON a.id = aa.ad_id
                                         WHERE aa.position_id = p.id AND aa.is_enabled = 1
                                           AND a.is_active = 1 AND a.status = \'active\') AS live_count
                                 FROM ad_positions p
                                 ORDER BY p.name ASC')->fetchAll();
    } catch (Throwable $e) {
        $db = null;
    }
}

admin_page_start('Ad Placements', 'ad-positions.php');
admin_notice();
?>

<div class="admin-page-head">
    <div>
        <p class="kicker">Monetization</p>
        <h1 class="admin-title">Ad Placements</h1>
        <p class="admin-sub">Every card is an example of a spot on the site. Match the size, then add your ad.</p>
    </div>
    <div class="ad-head-actions">
        <a class="btn btn-primary btn-sm" href="ads.php">Add an ad</a>
    </div>
</div>

<?php if (!$db): ?>
    <div class="alert alert-error" role="alert">Database connection unavailable.</div>
<?php else: ?>

<?php if ($editPosition): ?>
    <div class="panel ad-edit-panel">
        <div class="panel-head">
            <span><?= $editBuiltIn ? 'Rename built-in placement' : 'Edit custom placement' ?></span>
            <span class="count"><?= e((string) $editPosition['slug']) ?></span>
        </div>
        <div class="panel-body">
            <form method="post" action="ad-positions.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save-position">
                <input type="hidden" name="id" value="<?= (int) $editPosition['id'] ?>">

                <div class="field-row">
                    <div class="field">
                        <label for="position-name">Display name</label>
                        <input id="position-name" type="text" name="name" value="<?= e((string) $editPosition['name']) ?>" placeholder="Example: Homepage top banner" required>
                    </div>
                    <div class="field">
                        <label for="position-slug">Slug</label>
                        <input id="position-slug" type="text" name="slug" value="<?= e((string) $editPosition['slug']) ?>" <?= $editBuiltIn ? 'readonly' : '' ?> required>
                        <p class="ad-hint"><?= $editBuiltIn ? 'Built-in placement: the slug stays locked so the site keeps working.' : 'Use lowercase letters, numbers, and underscores.' ?></p>
                    </div>
                </div>

                <div class="field">
                    <label for="position-desc">Description</label>
                    <textarea id="position-desc" name="description" rows="3" placeholder="Example: The wide banner above the homepage tools."><?= e((string) ($editPosition['description'] ?? '')) ?></textarea>
                </div>

                <div class="ad-form-actions">
                    <button class="btn btn-primary" type="submit">Save placement</button>
                    <a class="btn btn-secondary" href="ad-positions.php">Cancel</a>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<div class="panel ad-placement-panel">
    <div class="panel-head">
        <span>Placement examples</span>
        <span class="count"><?= count($positions) ?> spots</span>
    </div>
    <div class="panel-body">
        <?php if (empty($positions)): ?>
            <div class="ad-empty">
                <strong>No placements yet.</strong>
                <span>Restore the page or add a custom placement below.</span>
            </div>
        <?php else: ?>
            <div class="ad-placement-grid">
                <?php foreach ($positions as $pos): ?>
                    <?php
                    $slug = (string) $pos['slug'];
                    $info = repdoc_ad_example($slug, (string) $pos['name'], (string) ($pos['description'] ?? ''));
                    [$demoWidth, $demoHeight] = repdoc_ad_demo_dimensions((int) $info['width'], (int) $info['height']);
                    $builtIn = isset($catalog[$slug]);
                    ?>
                    <article class="ad-placement-card">
                        <div class="ad-placement-demo" aria-hidden="true">
                            <span class="ad-demo-shape is-<?= e((string) $info['kind']) ?>" style="width:<?= (int) $demoWidth ?>px;height:<?= (int) $demoHeight ?>px;"></span>
                        </div>
                        <div class="ad-placement-body">
                            <div class="ad-placement-title">
                                <h3><?= e((string) $info['label']) ?></h3>
                                <span class="pill <?= ((int) $pos['is_active']) === 1 ? 'pill-approved' : 'pill-pending' ?>"><?= ((int) $pos['is_active']) === 1 ? 'Active' : 'Inactive' ?></span>
                            </div>
                            <p class="ad-placement-size"><?= e((string) $info['size']) ?></p>
                            <p class="ad-placement-where"><?= e((string) $info['where']) ?></p>
                            <div class="ad-placement-meta">
                                <span><?= (int) $pos['ad_count'] ?> ads</span>
                                <span><?= (int) $pos['live_count'] ?> live</span>
                                <span class="ad-tag"><?= $builtIn ? 'Built-in' : 'Custom' ?></span>
                            </div>
                            <div class="ad-placement-actions">
                                <a class="btn btn-primary btn-sm" href="ads.php?position=<?= e(urlencode($slug)) ?>">Use this spot</a>
                                <a class="btn btn-secondary btn-sm" href="ad-positions.php?edit=<?= (int) $pos['id'] ?>">Edit</a>
                                <?php if (!$builtIn): ?>
                                    <form method="post" action="ad-positions.php" onsubmit="return confirm('Delete this custom placement?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete-position">
                                        <input type="hidden" name="id" value="<?= (int) $pos['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<details class="panel ad-custom-panel">
    <summary class="panel-head">Add a custom placement</summary>
    <div class="panel-body">
        <p class="ad-hint">Only use this if you already added the matching slot in the site code. Built-in examples are enough for normal Adsterra sizes.</p>
        <form method="post" action="ad-positions.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save-position">
            <div class="field-row">
                <div class="field">
                    <label for="custom-name">Display name</label>
                    <input id="custom-name" type="text" name="name" placeholder="Example: Article sidebar" required>
                </div>
                <div class="field">
                    <label for="custom-slug">Slug</label>
                    <input id="custom-slug" type="text" name="slug" placeholder="example_article_sidebar" required>
                </div>
            </div>
            <div class="field">
                <label for="custom-desc">Description</label>
                <textarea id="custom-desc" name="description" rows="3" placeholder="Where this spot appears."></textarea>
            </div>
            <div class="ad-form-actions">
                <button class="btn btn-primary" type="submit">Create placement</button>
            </div>
        </form>
    </div>
</details>

<?php endif; ?>
<?php admin_page_end(); ?>
