<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/ad-catalog.php';

$db = repdoc_db();
$editId = isset($_GET['edit']) ? max(0, (int) $_GET['edit']) : 0;
$editAd = null;
$positions = [];
$ads = [];
$selectedPosition = trim((string) ($_GET['position'] ?? ''));
$globalEnabled = repdoc_ads_enabled();

if ($db) {
    try {
        $positions = $db->query('SELECT * FROM ad_positions WHERE is_active = 1 ORDER BY name ASC')->fetchAll();
        $ads = $db->query('SELECT a.*, p.slug AS position_slug, p.name AS position_name,
                                  aa.page_slug, aa.device_type, aa.id AS assignment_id
                           FROM ads a
                           LEFT JOIN ad_assignments aa ON aa.id = (
                               SELECT x.id FROM ad_assignments x WHERE x.ad_id = a.id ORDER BY x.id DESC LIMIT 1
                           )
                           LEFT JOIN ad_positions p ON p.id = aa.position_id
                           ORDER BY a.id DESC')->fetchAll();

        if ($editId > 0) {
            $st = $db->prepare('SELECT * FROM ads WHERE id = ? LIMIT 1');
            $st->execute([$editId]);
            $editAd = $st->fetch();
            if ($editAd) {
                $assignment = $db->prepare('SELECT aa.*, p.slug AS position_slug
                                            FROM ad_assignments aa
                                            LEFT JOIN ad_positions p ON p.id = aa.position_id
                                            WHERE aa.ad_id = ?
                                            ORDER BY aa.id DESC
                                            LIMIT 1');
                $assignment->execute([$editId]);
                $editAssignment = $assignment->fetch();
                if ($editAssignment) {
                    $editAd['position_slug'] = (string) ($editAssignment['position_slug'] ?? '');
                    $editAd['page_slug'] = (string) ($editAssignment['page_slug'] ?? 'all');
                    $editAd['device_type'] = (string) ($editAssignment['device_type'] ?? 'all');
                    $editAd['assignment_id'] = (int) $editAssignment['id'];
                }
                $selectedPosition = (string) ($editAd['position_slug'] ?? '');
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
        $check = $db->prepare('SELECT 1 FROM ad_settings WHERE setting_key = ? LIMIT 1');
        $check->execute(['ads_enabled']);
        if ($check->fetch()) {
            $save = $db->prepare('UPDATE ad_settings SET setting_value = ? WHERE setting_key = ?');
            $save->execute([$enabled, 'ads_enabled']);
        } else {
            $save = $db->prepare('INSERT INTO ad_settings (setting_key, setting_value) VALUES (?, ?)');
            $save->execute(['ads_enabled', $enabled]);
        }
        flash_set('ok', $enabled === '1' ? 'Site ads turned on.' : 'Site ads turned off.');
        redirect('ads.php');
    }

    if ($action === 'save-ad') {
        $adId = isset($_POST['ad_id']) ? max(0, (int) $_POST['ad_id']) : 0;
        $name = trim((string) ($_POST['name'] ?? ''));
        $code = (string) ($_POST['code'] ?? '');
        $status = isset($_POST['is_live']) ? 'active' : 'paused';
        $startDate = trim((string) ($_POST['start_date'] ?? ''));
        $endDate = trim((string) ($_POST['end_date'] ?? ''));
        $priority = max(1, min(100, (int) ($_POST['priority'] ?? 10)));
        $rotationWeight = max(1, min(100, (int) ($_POST['rotation_weight'] ?? 10)));
        $deviceTarget = in_array((string) ($_POST['device_target'] ?? 'all'), ['all', 'desktop', 'mobile'], true)
            ? (string) $_POST['device_target']
            : 'all';
        $positionSlug = trim((string) ($_POST['position_slug'] ?? ''));
        $pageSlug = trim((string) ($_POST['page_slug'] ?? 'all')) !== '' ? trim((string) $_POST['page_slug']) : 'all';
        $type = repdoc_ad_type_for_slug($positionSlug);

        if ($name === '' || $code === '' || $positionSlug === '') {
            flash_set('error', 'Add a name, the ad code, and choose a placement.');
            redirect('ads.php' . ($adId > 0 ? '?edit=' . $adId : ''));
        }

        $posCheck = $db->prepare('SELECT id FROM ad_positions WHERE slug = ? LIMIT 1');
        $posCheck->execute([$positionSlug]);
        $positionId = $posCheck->fetchColumn();
        if ($positionId === false || $positionId === null || $positionId === '') {
            flash_set('error', 'That placement no longer exists. Pick another one.');
            redirect('ads.php' . ($adId > 0 ? '?edit=' . $adId : ''));
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

        $assignmentId = 0;
        if ($adId > 0) {
            $latest = $db->prepare('SELECT id FROM ad_assignments WHERE ad_id = ? ORDER BY id DESC LIMIT 1');
            $latest->execute([$targetId]);
            $assignmentId = (int) ($latest->fetchColumn() ?: 0);
        }

        if ($assignmentId > 0) {
            $updAssign = $db->prepare('UPDATE ad_assignments SET position_id = ?, page_slug = ?, device_type = ?, priority = ?, rotation_weight = ?, start_date = ?, end_date = ?, is_enabled = 1 WHERE id = ?');
            $updAssign->execute([(int) $positionId, $pageSlug, $deviceTarget, $priority, $rotationWeight, $startDate !== '' ? $startDate : null, $endDate !== '' ? $endDate : null, $assignmentId]);
        } else {
            $insAssign = $db->prepare('INSERT INTO ad_assignments (ad_id, position_id, page_slug, device_type, is_enabled, priority, rotation_weight, start_date, end_date, created_at) VALUES (?, ?, ?, ?, 1, ?, ?, ?, ?, ?)');
            $insAssign->execute([$targetId, (int) $positionId, $pageSlug, $deviceTarget, $priority, $rotationWeight, $startDate !== '' ? $startDate : null, $endDate !== '' ? $endDate : null, date('Y-m-d H:i:s')]);
        }

        flash_set('ok', $adId > 0 ? 'Ad updated.' : 'Ad created and placed.');
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
            $st = $db->prepare('SELECT status, is_active FROM ads WHERE id = ? LIMIT 1');
            $st->execute([$adId]);
            $row = $st->fetch();
            if ($row) {
                $isLive = (int) $row['is_active'] === 1 && (string) $row['status'] === 'active';
                $upd = $db->prepare('UPDATE ads SET status = ?, is_active = 1, updated_at = ? WHERE id = ?');
                $upd->execute([$isLive ? 'paused' : 'active', date('Y-m-d H:i:s'), $adId]);
                flash_set('ok', $isLive ? 'Ad paused.' : 'Ad is live.');
            }
        }
        redirect('ads.php');
    }
}

$liveCount = 0;
foreach ($ads as $adRow) {
    if ((int) $adRow['is_active'] === 1 && (string) $adRow['status'] === 'active') {
        $liveCount++;
    }
}

$pageLabels = [
    'all' => 'All pages',
    'home' => 'Homepage',
    'birth' => 'Birth form',
    'death' => 'Death form',
    'nid' => 'NID form',
    'smartnid' => 'Smart NID form',
];
$deviceLabels = ['all' => 'All devices', 'desktop' => 'Desktop', 'mobile' => 'Mobile'];
$currentPage = (string) ($editAd['page_slug'] ?? 'all');
if (!isset($pageLabels[$currentPage])) {
    $pageLabels[$currentPage] = $currentPage;
}

admin_page_start('Ad Manager', 'ads.php');
admin_notice();
?>

<div class="admin-page-head">
    <div>
        <p class="kicker">Monetization</p>
        <h1 class="admin-title">Ad Manager</h1>
        <p class="admin-sub">Pick a spot from the examples, paste your Adsterra code, and save. No slugs or setup needed.</p>
    </div>
    <div class="ad-head-actions">
        <a class="btn btn-secondary btn-sm" href="ad-positions.php">Placement examples</a>
        <a class="btn btn-secondary btn-sm" href="ad-logs.php">Logs</a>
    </div>
</div>

<?php if (!$db): ?>
    <div class="alert alert-error" role="alert">Could not connect to the database.</div>
<?php else: ?>

<form class="ad-global-bar" method="post" action="ads.php">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save-settings">
    <label class="ad-switch" for="adsEnabled">
        <input id="adsEnabled" type="checkbox" name="ads_enabled" value="1" <?= $globalEnabled ? 'checked' : '' ?>>
        <span class="ad-switch-track" aria-hidden="true"></span>
        <span class="ad-switch-copy">
            <strong id="adsSwitchTitle"><?= $globalEnabled ? 'Site ads are on' : 'Site ads are off' ?></strong>
            <small id="adsSwitchSub"><?= $globalEnabled ? 'Every live placement can show.' : 'Every placement is hidden until you turn this back on.' ?></small>
        </span>
    </label>
    <div class="ad-global-actions">
        <button class="btn btn-primary btn-sm" type="submit">Save switch</button>
        <a href="ad-settings.php">More settings</a>
    </div>
</form>

<form class="panel ad-builder" method="post" action="ads.php">
    <div class="panel-head">
        <span><?= $editAd ? 'Edit ad' : 'Add an ad' ?></span>
        <span class="count">2 quick steps</span>
    </div>
    <div class="panel-body">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save-ad">
        <?php if ($editAd): ?>
            <input type="hidden" name="ad_id" value="<?= (int) $editAd['id'] ?>">
        <?php endif; ?>

        <section class="ad-step">
            <div class="ad-step-head">
                <span class="ad-step-num">1</span>
                <div>
                    <h2>Choose a spot</h2>
                    <p>Each card is a real example. Pick the one that matches the ad size you created in Adsterra.</p>
                </div>
            </div>

            <?php if (empty($positions)): ?>
                <div class="alert alert-warning" role="status">No placements found. <a href="ad-positions.php">Create or restore a placement</a> first.</div>
            <?php else: ?>
                <div class="ad-spot-grid">
                    <?php foreach ($positions as $pos): ?>
                        <?php
                        $slug = (string) $pos['slug'];
                        $info = repdoc_ad_example($slug, (string) $pos['name'], (string) ($pos['description'] ?? ''));
                        [$demoWidth, $demoHeight] = repdoc_ad_demo_dimensions((int) $info['width'], (int) $info['height']);
                        $checked = $slug === $selectedPosition;
                        ?>
                        <label class="ad-spot">
                            <input type="radio" name="position_slug" value="<?= e($slug) ?>" data-ad-name="<?= e((string) $info['label']) ?>" <?= $checked ? 'checked' : '' ?> required>
                            <span class="ad-spot-demo" aria-hidden="true">
                                <span class="ad-demo-shape is-<?= e((string) $info['kind']) ?>" style="width:<?= (int) $demoWidth ?>px;height:<?= (int) $demoHeight ?>px;"></span>
                            </span>
                            <span class="ad-spot-copy">
                                <strong><?= e((string) $info['label']) ?></strong>
                                <span><?= e((string) $info['size']) ?></span>
                                <small><?= e((string) $info['where']) ?></small>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="ad-step">
            <div class="ad-step-head">
                <span class="ad-step-num">2</span>
                <div>
                    <h2>Paste the ad code</h2>
                    <p>In Adsterra open the ad unit, choose “Get code”, then paste the full script here.</p>
                </div>
            </div>

            <div class="field-row">
                <div class="field">
                    <label for="ad-name">Ad name</label>
                    <input id="ad-name" name="name" type="text" value="<?= e((string) ($editAd['name'] ?? '')) ?>" placeholder="Example: Homepage top banner" required>
                </div>
                <div class="field">
                    <label for="device-target">Show on</label>
                    <select id="device-target" name="device_target">
                        <option value="all" <?= (($editAd['device_target'] ?? 'all') === 'all') ? 'selected' : '' ?>>All devices</option>
                        <option value="desktop" <?= (($editAd['device_target'] ?? 'all') === 'desktop') ? 'selected' : '' ?>>Desktop only</option>
                        <option value="mobile" <?= (($editAd['device_target'] ?? 'all') === 'mobile') ? 'selected' : '' ?>>Mobile only</option>
                    </select>
                </div>
            </div>

            <div class="field">
                <label for="ad-code">Ad code</label>
                <textarea id="ad-code" name="code" rows="8" placeholder="Paste the full Adsterra code here, including the script tags." required><?= e((string) ($editAd['code'] ?? '')) ?></textarea>
                <p class="ad-hint">Tip: copy everything Adsterra gives you. Do not paste only the URL.</p>
                <details class="ad-help">
                    <summary>Show an example of what to paste</summary>
                    <pre>&lt;script type="text/javascript"&gt;
  atOptions = {
    'key' : 'YOUR_ADSTERRA_KEY',
    'format' : 'iframe',
    'height' : 90,
    'width' : 728,
    'params' : {}
  };
&lt;/script&gt;
&lt;script src="//www.highperformanceformat.com/YOUR_ADSTERRA_KEY/invoke.js"&gt;&lt;/script&gt;</pre>
                </details>
            </div>

            <label class="ad-live-toggle">
                <input type="checkbox" name="is_live" value="1" <?= (!$editAd || (string) ($editAd['status'] ?? 'active') === 'active') ? 'checked' : '' ?>>
                <span>
                    <strong>Start showing this ad</strong>
                    <small>Uncheck to save it as paused.</small>
                </span>
            </label>

            <details class="ad-advanced">
                <summary>Advanced options</summary>
                <div class="field-row">
                    <div class="field">
                        <label for="page-slug">Show on page</label>
                        <select id="page-slug" name="page_slug">
                            <?php foreach ($pageLabels as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= (($editAd['page_slug'] ?? 'all') === $value) ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="priority">Priority</label>
                        <input id="priority" name="priority" type="number" min="1" max="100" value="<?= (int) ($editAd['priority'] ?? 10) ?>">
                    </div>
                    <div class="field">
                        <label for="rotation-weight">Rotation weight</label>
                        <input id="rotation-weight" name="rotation_weight" type="number" min="1" max="100" value="<?= (int) ($editAd['rotation_weight'] ?? 10) ?>">
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
            </details>

            <div class="ad-form-actions">
                <button class="btn btn-primary" type="submit"><?= $editAd ? 'Update ad' : 'Save ad' ?></button>
                <?php if ($editAd): ?>
                    <a class="btn btn-secondary" href="ads.php">Cancel edit</a>
                <?php endif; ?>
            </div>
        </section>
    </div>
</form>

<div class="panel ad-list-panel">
    <div class="panel-head">
        <span>Your ads</span>
        <span class="count"><?= count($ads) ?> total · <?= (int) $liveCount ?> live</span>
    </div>
    <div class="panel-body">
        <?php if (empty($ads)): ?>
            <div class="ad-empty">
                <strong>No ads yet.</strong>
                <span>Use the form above to add your first Adsterra banner.</span>
            </div>
        <?php else: ?>
            <div class="ad-live-grid">
                <?php foreach ($ads as $row): ?>
                    <?php
                    $slug = (string) ($row['position_slug'] ?? '');
                    $info = $slug !== '' ? repdoc_ad_example($slug, (string) ($row['position_name'] ?? '')) : ['label' => 'No placement', 'size' => '—'];
                    $isLive = (int) $row['is_active'] === 1 && (string) $row['status'] === 'active';
                    $page = (string) ($row['page_slug'] ?? 'all');
                    $device = (string) ($row['device_type'] ?? 'all');
                    ?>
                    <article class="ad-live-card<?= $isLive ? '' : ' is-paused' ?>">
                        <div class="ad-live-top">
                            <span class="pill <?= $isLive ? 'pill-approved' : 'pill-pending' ?>"><?= $isLive ? 'Live' : 'Paused' ?></span>
                            <span class="ad-live-size"><?= e((string) $info['size']) ?></span>
                        </div>
                        <h3><?= e((string) $row['name']) ?></h3>
                        <p><?= e((string) $info['label']) ?> · <?= e($pageLabels[$page] ?? $page) ?> · <?= e($deviceLabels[$device] ?? $device) ?></p>
                        <div class="ad-live-actions">
                            <a class="btn btn-secondary btn-sm" href="ads.php?edit=<?= (int) $row['id'] ?>">Edit</a>
                            <form method="post" action="ads.php">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="toggle-ad">
                                <input type="hidden" name="ad_id" value="<?= (int) $row['id'] ?>">
                                <button type="submit" class="btn btn-sm <?= $isLive ? 'btn-secondary' : 'btn-primary' ?>"><?= $isLive ? 'Pause' : 'Go live' ?></button>
                            </form>
                            <form method="post" action="ads.php" onsubmit="return confirm('Delete this ad?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete-ad">
                                <input type="hidden" name="ad_id" value="<?= (int) $row['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    var enabled = document.getElementById('adsEnabled');
    var title = document.getElementById('adsSwitchTitle');
    var sub = document.getElementById('adsSwitchSub');
    if (enabled && title && sub) {
        enabled.addEventListener('change', function () {
            title.textContent = enabled.checked ? 'Site ads are on' : 'Site ads are off';
            sub.textContent = enabled.checked ? 'Every live placement can show.' : 'Every placement is hidden until you turn this back on.';
        });
    }

    var nameInput = document.getElementById('ad-name');
    var spots = document.querySelectorAll('.ad-spot input[type="radio"]');
    function syncSpotSelection() {
        var labels = document.querySelectorAll('.ad-spot');
        for (var j = 0; j < labels.length; j++) {
            var field = labels[j].querySelector('input[type="radio"]');
            labels[j].classList.toggle('is-selected', !!(field && field.checked));
        }
    }
    for (var i = 0; i < spots.length; i++) {
        spots[i].addEventListener('change', function () {
            if (nameInput && nameInput.value.trim() === '') {
                nameInput.value = this.getAttribute('data-ad-name') || '';
            }
            syncSpotSelection();
        });
    }
    syncSpotSelection();
})();
</script>

<?php endif; ?>

<?php admin_page_end(); ?>
