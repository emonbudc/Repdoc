<?php
declare(strict_types=1);

/**
 * Repdoc — admin: software downloads.
 *
 * Add, edit, reorder, activate/deactivate and remove the entries shown on
 * the public /software/ page. Metadata lives in love/software.json; the
 * thumbnail uses the shared file-thumb store keyed by the entry id.
 */

require_once __DIR__ . '/includes/layout.php';
require_once dirname(__DIR__) . '/src/filemeta.php';
require_once dirname(__DIR__) . '/src/software.php';

const SOFTWARE_DESC_MAX = 800;

function software_admin_input(): array
{
    return [
        'name'        => (string) ($_POST['name'] ?? ''),
        'platform'    => (string) ($_POST['platform'] ?? 'other'),
        'version'     => (string) ($_POST['version'] ?? ''),
        'size'        => (string) ($_POST['size'] ?? ''),
        'description' => (string) ($_POST['description'] ?? ''),
        'url'         => (string) ($_POST['url'] ?? ''),
        'active'      => isset($_POST['active']),
    ];
}

// ── POST: create / update / delete / move / toggle ──────────────────
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Session expired — please try again.');
        redirect('software.php');
    }

    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'create' || $action === 'update') {
        $id = $action === 'update'
            ? strtolower(trim((string) ($_POST['id'] ?? '')))
            : null;
        $input = software_admin_input();
        $result = repdoc_software_save_entry($input, $id);

        if (!$result['ok']) {
            flash_set('error', $result['error']);
            redirect($id !== null && $id !== '' ? 'software.php?edit=' . rawurlencode($id) : 'software.php');
        }

        $savedId = $result['id'];
        $thumbNotes = [];
        $thumbUploaded = false;
        $thumb = $_FILES['thumb'] ?? null;

        if (is_array($thumb) && ($thumb['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            [$thumbOk, $thumbErr] = repdoc_file_thumb_save('software', $savedId, $thumb);
            if ($thumbOk) {
                $thumbUploaded = true;
                $thumbNotes[] = 'thumbnail saved';
            } else {
                $thumbNotes[] = 'thumbnail: ' . $thumbErr;
            }
        }

        if (!$thumbUploaded && !empty($_POST['remove_thumb'])) {
            repdoc_file_thumb_delete('software', $savedId);
            $thumbNotes[] = 'thumbnail removed';
        }

        $me = current_user();
        if ($me !== null) {
            log_usage((int) $me['id'], 'admin-software', 'ok', $action . ':' . $result['entry']['name']);
        }

        $thumbFailed = false;
        foreach ($thumbNotes as $note) {
            if (str_starts_with($note, 'thumbnail: ')) $thumbFailed = true;
        }
        $message = ($action === 'create' ? 'Software added' : 'Software updated')
            . ': ' . $result['entry']['name'];
        if ($thumbNotes !== []) $message .= ' · ' . implode(' · ', $thumbNotes);
        flash_set($thumbFailed ? 'warn' : 'ok', $message . '.');
        redirect('software.php');
    }

    $id = strtolower(trim((string) ($_POST['id'] ?? '')));

    if ($action === 'delete') {
        $entry = repdoc_software_find($id);
        if ($entry === null) {
            flash_set('error', 'Software entry not found.');
            redirect('software.php');
        }
        if (!repdoc_software_delete_entry($id)) {
            flash_set('error', 'Could not delete the entry. Check love/ permissions.');
            redirect('software.php');
        }
        repdoc_file_thumb_delete('software', $id);
        $me = current_user();
        if ($me !== null) log_usage((int) $me['id'], 'admin-software', 'ok', 'delete:' . $entry['name']);
        flash_set('ok', 'Deleted: ' . $entry['name'] . '.');
        redirect('software.php');
    }

    if ($action === 'move') {
        $direction = (string) ($_POST['direction'] ?? '');
        if (!repdoc_software_move_entry($id, $direction)) {
            flash_set('warn', 'Could not move that entry.');
        }
        redirect('software.php');
    }

    if ($action === 'toggle') {
        $entry = repdoc_software_find($id);
        if ($entry === null || !repdoc_software_toggle_entry($id)) {
            flash_set('error', 'Could not update the entry status.');
            redirect('software.php');
        }
        $me = current_user();
        if ($me !== null) log_usage((int) $me['id'], 'admin-software', 'ok', 'toggle:' . $entry['name']);
        flash_set('ok', ($entry['active'] ? 'Paused: ' : 'Activated: ') . $entry['name'] . '.');
        redirect('software.php');
    }

    flash_set('error', 'Unknown action.');
    redirect('software.php');
}

// ── GET: editor + list ──────────────────────────────────────────────
$editId = strtolower(trim((string) ($_GET['edit'] ?? '')));
$editEntry = $editId !== '' ? repdoc_software_find($editId) : null;
if ($editId !== '' && $editEntry === null) {
    flash_set('error', 'Software entry not found.');
    redirect('software.php');
}

$entries = repdoc_software_load_all();
$total = count($entries);
$activeCount = count(array_filter($entries, static fn(array $row): bool => !empty($row['active']) && !empty($row['valid'])));
$invalidCount = count(array_filter($entries, static fn(array $row): bool => empty($row['valid'])));
$platformsUsed = [];
foreach ($entries as $row) {
    $platformsUsed[$row['platform']] = true;
}

$isEdit = $editEntry !== null;
$form = $isEdit ? $editEntry : [
    'id'          => '',
    'name'        => '',
    'platform'    => 'windows',
    'version'     => '',
    'size'        => '',
    'description' => '',
    'url'         => '',
    'active'      => true,
];
$formThumb = $isEdit ? repdoc_file_thumb_url('software', (string) $form['id']) : '';

$platforms = repdoc_software_platforms();

admin_page_start('Software Downloads', 'software.php');
admin_notice();
?>

<style>
    .sw-admin-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
        margin-bottom: var(--space-5);
    }
    .sw-admin-stat {
        padding: 14px 16px;
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        background: var(--bg-surface);
        box-shadow: var(--shadow-sm);
    }
    .sw-admin-stat .k { display: block; color: var(--text-muted); font-size: .74rem; font-weight: 600; }
    .sw-admin-stat .v {
        display: block;
        margin-top: 3px;
        color: var(--text-primary);
        font-family: var(--font-mono);
        font-size: 1.28rem;
        font-weight: 700;
    }
    .sw-admin-stat.is-warn { border-color: var(--warning); background: var(--warning-bg); }
    .sw-form {
        display: grid;
        grid-template-columns: minmax(0, 1.2fr) minmax(0, .7fr) minmax(0, .65fr) minmax(0, .55fr);
        gap: 12px;
        align-items: end;
    }
    .sw-form label { display: grid; gap: 6px; min-width: 0; }
    .sw-form .sw-wide { grid-column: 1 / -1; }
    .sw-form input[type="text"],
    .sw-form input[type="url"],
    .sw-form input[type="file"],
    .sw-form select,
    .sw-form textarea {
        width: 100%;
        min-height: 44px;
        padding: 10px 12px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        background: #fff;
        color: var(--text-primary);
        font: inherit;
        font-size: .88rem;
    }
    .sw-form textarea { min-height: 84px; line-height: 1.55; resize: vertical; }
    .sw-form .sw-note { font-size: .74rem; color: var(--text-muted); }
    .sw-form .sw-check {
        display: flex;
        align-items: center;
        gap: 8px;
        min-height: 44px;
        padding: 10px 12px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        background: #fff;
        font-size: .84rem;
        font-weight: 600;
        cursor: pointer;
    }
    .sw-form .sw-check input { width: 16px; height: 16px; accent-color: var(--primary); }
    .sw-form-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; grid-column: 1 / -1; }
    .sw-thumb-row {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: 12px;
        padding: 10px;
        border: 1px dashed var(--border-color);
        border-radius: 8px;
        background: var(--bg-base);
    }
    .sw-thumb-preview {
        width: 96px;
        height: 72px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid var(--border-color);
        background: #fff;
    }
    .sw-thumb-input { display: grid; gap: 4px; flex: 1 1 220px; min-width: 0; }
    .sw-remove { display: inline-flex; align-items: center; gap: 6px; color: var(--danger); font-size: .76rem; font-weight: 600; }
    .sw-remove input { accent-color: var(--danger); }
    .sw-thumb-mini {
        flex: none;
        width: 52px;
        height: 39px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid var(--border-color);
        background: var(--bg-muted);
        display: block;
    }
    .sw-thumb-empty { display: grid; place-items: center; background: var(--bg-muted); }
    .sw-thumb-empty img { width: 26px; height: 26px; }
    .sw-file-cell { display: flex; align-items: center; gap: 10px; min-width: 220px; }
    .sw-file-text { min-width: 0; }
    .sw-url-cell { max-width: 280px; }
    .sw-url-host { display: block; color: var(--text-primary); font-weight: 600; }
    .sw-url-raw { display: block; color: var(--text-muted); font-size: .72rem; overflow-wrap: anywhere; }
    .sw-url-error { display: block; margin-top: 4px; color: var(--danger); font-size: .72rem; font-weight: 600; }
    .sw-actions { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .sw-move { display: flex; gap: 4px; }
    .sw-move button {
        display: inline-grid;
        place-items: center;
        width: 28px;
        height: 28px;
        padding: 0;
        border: 1px solid var(--border-color);
        border-radius: 5px;
        background: #fff;
        color: var(--text-muted);
        font: inherit;
        font-size: .9rem;
        line-height: 1;
        cursor: pointer;
    }
    .sw-move button:hover { border-color: var(--primary); color: var(--primary); background: var(--primary-light); }
    .sw-help-list { display: flex; flex-direction: column; gap: 7px; padding: 0; margin: 0; list-style: none; }
    .sw-help-list li { position: relative; padding-inline-start: 17px; color: var(--text-muted); font-size: .82rem; line-height: 1.55; }
    .sw-help-list li::before { content: ""; position: absolute; top: .52em; inset-inline-start: 2px; width: 6px; height: 6px; border-radius: 50%; background: var(--primary); }
    @media (max-width: 980px) {
        .sw-form { grid-template-columns: 1fr 1fr; }
        .sw-admin-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 640px) {
        .sw-form { grid-template-columns: 1fr; }
        .sw-admin-stats { grid-template-columns: 1fr; }
        .sw-form-actions .btn { flex: 1 1 auto; justify-content: center; }
        .sw-actions { width: 100%; }
    }
</style>

<div class="admin-page-head">
    <div>
        <p class="kicker">Publishing</p>
        <h1 class="admin-title">Software Downloads</h1>
        <p class="admin-sub">Manage the public /software/ list, thumbnails and external download links. No installer is hosted on Repdoc.</p>
    </div>
    <div class="sw-actions">
        <a class="btn btn-secondary btn-sm" href="../software/" target="_blank" rel="noopener">
            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-external"/></svg>
            View public page
        </a>
    </div>
</div>

<div class="sw-admin-stats">
    <div class="sw-admin-stat">
        <span class="k">Total entries</span>
        <span class="v"><?= e(number_format($total)) ?></span>
    </div>
    <div class="sw-admin-stat">
        <span class="k">Live on site</span>
        <span class="v"><?= e(number_format($activeCount)) ?></span>
    </div>
    <div class="sw-admin-stat">
        <span class="k">Platforms in use</span>
        <span class="v"><?= e(number_format(count($platformsUsed))) ?></span>
    </div>
    <div class="sw-admin-stat<?= $invalidCount > 0 ? ' is-warn' : '' ?>">
        <span class="k">Needs attention</span>
        <span class="v"><?= e(number_format($invalidCount)) ?></span>
    </div>
</div>

<div class="panel">
    <div class="panel-head">
        <?= $isEdit ? 'Edit software' : 'Add software' ?>
        <span class="count"><?= $isEdit ? e($form['name']) : 'external download link' ?></span>
        <?php if ($isEdit): ?>
            <a class="panel-link" href="software.php">Back to add form</a>
        <?php endif; ?>
    </div>
    <div class="panel-body">
        <form method="post" action="software.php" enctype="multipart/form-data" class="sw-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
            <?php if ($isEdit): ?>
                <input type="hidden" name="id" value="<?= e((string) $form['id']) ?>">
            <?php endif; ?>
            <input type="hidden" name="MAX_FILE_SIZE" value="2097152">

            <label>
                <span class="cell-sub">Software name</span>
                <input type="text" name="name" required maxlength="120" value="<?= e((string) $form['name']) ?>" placeholder="e.g. VLC Media Player">
            </label>

            <label>
                <span class="cell-sub">Platform</span>
                <select name="platform">
                    <?php foreach ($platforms as $key => $meta): ?>
                        <option value="<?= e($key) ?>"<?= $form['platform'] === $key ? ' selected' : '' ?>>
                            <?= e($meta['admin']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                <span class="cell-sub">Version <span class="sw-note">optional</span></span>
                <input type="text" name="version" maxlength="40" value="<?= e((string) $form['version']) ?>" placeholder="e.g. 3.0.21">
            </label>

            <label>
                <span class="cell-sub">Size <span class="sw-note">optional</span></span>
                <input type="text" name="size" maxlength="40" value="<?= e((string) $form['size']) ?>" placeholder="e.g. 40 MB">
            </label>

            <label class="sw-wide">
                <span class="cell-sub">Download URL <span class="sw-note">— must be a full http(s) link</span></span>
                <input type="url" name="url" required maxlength="1000" value="<?= e((string) $form['url']) ?>" placeholder="https://example.com/download/vlc.exe">
            </label>

            <label class="sw-wide">
                <span class="cell-sub">Description <span class="sw-note">— shown on the public card</span></span>
                <textarea name="description" rows="3" maxlength="<?= SOFTWARE_DESC_MAX ?>" placeholder="এই সফটওয়্যারটি কী কাজে লাগবে, কোন প্ল্যাটফর্মে চলবে — সংক্ষেপে লিখুন…"><?= e((string) $form['description']) ?></textarea>
            </label>

            <div class="sw-wide sw-thumb-row">
                <?php if ($formThumb !== ''): ?>
                    <img class="sw-thumb-preview" src="<?= e($formThumb) ?>" alt="Current thumbnail" width="96" height="72" loading="lazy">
                <?php endif; ?>
                <label class="sw-thumb-input">
                    <span class="cell-sub">Thumbnail <span class="sw-note">— JPG, PNG, WebP or GIF · max 2 MB · 4:3 recommended</span></span>
                    <input type="file" name="thumb" accept="image/jpeg,image/png,image/webp,image/gif">
                </label>
                <?php if ($formThumb !== ''): ?>
                    <label class="sw-remove"><input type="checkbox" name="remove_thumb" value="1"> Remove thumbnail</label>
                <?php endif; ?>
            </div>

            <label class="sw-check sw-wide">
                <input type="checkbox" name="active" value="1"<?= !empty($form['active']) ? ' checked' : '' ?>>
                Published on the public /software/ page
            </label>

            <div class="sw-form-actions">
                <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save changes' : 'Add software' ?></button>
                <?php if ($isEdit): ?>
                    <a class="btn btn-secondary" href="software.php">Cancel</a>
                <?php endif; ?>
                <span class="sw-note">Links open in a new tab. Repdoc never mirrors the installer.</span>
            </div>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-head">
        All software
        <span class="count"><?= e(number_format($total)) ?> entries</span>
    </div>
    <?php if ($entries === []): ?>
        <div class="empty-state-sm">No software yet — add the first entry above.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr><th>Software</th><th>Platform</th><th>Download</th><th>Size</th><th>Updated</th><th>Status</th><th>Order</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($entries as $row):
                    $platform = repdoc_software_platform_meta((string) $row['platform']);
                    $thumbUrl = repdoc_file_thumb_url('software', (string) $row['id']);
                    $host = repdoc_software_host((string) $row['url']);
                    $valid = !empty($row['valid']);
                    $active = !empty($row['active']);
                ?>
                    <tr>
                        <td>
                            <div class="sw-file-cell">
                                <?php if ($thumbUrl !== ''): ?>
                                    <img class="sw-thumb-mini" src="<?= e($thumbUrl) ?>" alt="" width="52" height="39" loading="lazy">
                                <?php else: ?>
                                    <span class="sw-thumb-mini sw-thumb-empty" aria-hidden="true"><img src="<?= e(repdoc_url('src/assets/image/file-logos/software.svg')) ?>" alt="" width="26" height="26" loading="lazy"></span>
                                <?php endif; ?>
                                <span class="sw-file-text">
                                    <span class="cell-name"><?= e((string) $row['name']) ?></span>
                                    <span class="cell-sub"><?= e($platform['admin']) ?><?= $row['version'] !== '' ? ' · v' . e((string) $row['version']) : '' ?></span>
                                </span>
                            </div>
                        </td>
                        <td><span class="pill pill-approved"><?= e($platform['label']) ?></span></td>
                        <td class="sw-url-cell">
                            <?php if ($valid): ?>
                                <a class="sw-url-host" href="<?= e((string) $row['url']) ?>" target="_blank" rel="noopener noreferrer nofollow"><?= e($host) ?> ↗</a>
                            <?php else: ?>
                                <span class="sw-url-host"><?= e($host) ?></span>
                            <?php endif; ?>
                            <span class="sw-url-raw"><?= e((string) $row['url']) ?></span>
                            <?php if (!$valid): ?>
                                <span class="sw-url-error"><?= e((string) ($row['error'] ?? 'Invalid entry.')) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="balance"><?= e($row['size'] !== '' ? (string) $row['size'] : '—') ?></td>
                        <td class="cell-sub"><?= (int) $row['updated_at'] > 0 ? e(date('d-m-Y H:i', (int) $row['updated_at'])) : '—' ?></td>
                        <td>
                            <?php if (!$valid): ?>
                                <span class="pill pill-pending">Invalid</span>
                            <?php else: ?>
                                <form method="post" action="software.php" style="margin:0">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= e((string) $row['id']) ?>">
                                    <button type="submit" class="btn btn-secondary btn-sm">
                                        <?= $active ? 'Pause' : 'Activate' ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="post" action="software.php" class="sw-move">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="move">
                                <input type="hidden" name="id" value="<?= e((string) $row['id']) ?>">
                                <button type="submit" name="direction" value="up" aria-label="Move up" title="Move up">↑</button>
                                <button type="submit" name="direction" value="down" aria-label="Move down" title="Move down">↓</button>
                            </form>
                        </td>
                        <td>
                            <div class="sw-actions">
                                <a class="btn btn-secondary btn-sm" href="software.php?edit=<?= e(rawurlencode((string) $row['id'])) ?>">Edit</a>
                                <form method="post" action="software.php" onsubmit="return confirm('Delete this software entry? The thumbnail will be removed too.');" style="margin:0">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= e((string) $row['id']) ?>">
                                    <button type="submit" class="btn btn-secondary btn-sm" aria-label="Delete <?= e((string) $row['name']) ?>">
                                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-trash"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    <div class="panel-body" style="padding-top:0">
        <ul class="sw-help-list">
            <li>Entries are stored in <code>love/software.json</code>; the public page updates immediately.</li>
            <li>Download links must be full <code>http://</code> or <code>https://</code> URLs. Unsafe schemes are rejected.</li>
            <li>Thumbnails are optional JPG, PNG, WebP or GIF files up to 2 MB.</li>
            <li>Use ↑ and ↓ to set the public order. Paused entries stay here but disappear from the public page.</li>
            <li>Repdoc does not host the installer — visitors open the publisher’s download page in a new tab.</li>
        </ul>
    </div>
</div>

<?php admin_page_end(); ?>
