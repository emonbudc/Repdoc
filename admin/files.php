<?php
declare(strict_types=1);

/**
 * Repdoc — admin: file packs (PSD / AI / PLP + Word / Excel / PowerPoint).
 *
 * Admin-only (NOT UDC panel): upload new packs and manage the published
 * ones. Files live in:
 *   offlinefiles/{psd,ai,plp}/   → PSD, AI, PLP hub
 *   officefiles/{word,excel}/     → Word, Excel hub
 * The public pages auto-list whatever is on disk; downloads stay gated
 * behind approved login via each hub's download.php.
 */

require_once __DIR__ . '/includes/layout.php';
require_once dirname(__DIR__) . '/src/filemeta.php';

const FILES_MAX_BYTES = 10 * 1024 * 1024; // 10 MB — InfinityFree per-file limit.
const FILES_DESC_MAX = 2000;

/** Format key => hub dir, label, allowed extensions. */
$FORMATS = [
    'psd'   => ['hub' => 'offlinefiles', 'label' => 'PSD',   'exts' => ['psd', 'psb', 'zip']],
    'ai'    => ['hub' => 'offlinefiles', 'label' => 'AI',    'exts' => ['ai', 'eps', 'zip']],
    'plp'   => ['hub' => 'offlinefiles', 'label' => 'PLP',   'exts' => ['plp', 'zip']],
    'word'  => ['hub' => 'officefiles',  'label' => 'Word',       'exts' => ['doc', 'docx', 'rtf', 'odt', 'zip']],
    'excel' => ['hub' => 'officefiles',  'label' => 'Excel',      'exts' => ['xls', 'xlsx', 'csv', 'zip']],
    'ppt'   => ['hub' => 'officefiles',  'label' => 'PowerPoint', 'exts' => ['ppt', 'pptx', 'zip']],
];

/** Format key => logo filename in src/assets/image/file-logos/. */
$FORMAT_LOGOS = [
    'psd'   => 'photoshop.svg',
    'ai'    => 'illustrator.svg',
    'plp'   => 'pixellab.png',
    'word'  => 'word.svg',
    'excel' => 'excel.svg',
    'ppt'   => 'powerpoint.svg',
];

function admin_format_logo_url(string $type): string
{
    global $FORMAT_LOGOS;
    $file = $FORMAT_LOGOS[$type] ?? 'microsoft-office.svg';
    return repdoc_url('src/assets/image/file-logos/' . $file);
}

$ROOT = dirname(__DIR__);

function files_human_size(int $bytes): string
{
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1024 * 1024) return number_format($bytes / 1024, 1) . ' KB';
    if ($bytes < 1024 * 1024 * 1024) return number_format($bytes / (1024 * 1024), 1) . ' MB';
    return number_format($bytes / (1024 * 1024 * 1024), 2) . ' GB';
}

/** Sanitize an uploaded filename; returns '' when unusable. */
function files_safe_name(string $raw): string
{
    $base = basename(trim($raw));
    if ($base === '' || $base === '.' || $base === '..') return '';
    // Spaces → dash, strip anything that is not a letter/mark/number/dot/dash/underscore.
    // \p{M} keeps Bangla vowel signs (ি ো ্) intact.
    $base = str_replace(' ', '-', $base);
    $base = (string) preg_replace('/[^\p{L}\p{M}\p{N}._-]+/u', '_', $base);
    $base = trim($base, '._-');
    if ($base === '') return '';
    // Keep the name reasonably short (ext preserved).
    if (strlen($base) > 120) {
        $ext = strtolower((string) pathinfo($base, PATHINFO_EXTENSION));
        $stem = substr((string) pathinfo($base, PATHINFO_FILENAME), 0, 110 - strlen($ext));
        $base = rtrim($stem, '._-') . ($ext !== '' ? '.' . $ext : '');
    }
    if (str_starts_with($base, '.')) return '';
    return $base;
}

/** Absolute dir for a format key (created on demand for upload). */
function files_dir(string $root, array $formats, string $type): string
{
    return $root . DIRECTORY_SEPARATOR . $formats[$type]['hub'] . DIRECTORY_SEPARATOR . $type;
}

// ── POST: upload / delete (PRG + flash) ─────────────────────────────
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Session expired — please try again.');
        redirect('files.php');
    }

    if ($action === 'upload') {
        $type = strtolower((string) ($_POST['type'] ?? ''));
        if (!isset($FORMATS[$type])) {
            flash_set('error', 'Unknown format.');
            redirect('files.php');
        }
        $file = $_FILES['pack'] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $msg = 'No file received.';
            if (($file['error'] ?? 0) === UPLOAD_ERR_INI_SIZE || ($file['error'] ?? 0) === UPLOAD_ERR_FORM_SIZE) {
                $msg = 'File too large for the server (max ' . ini_get('upload_max_filesize') . ').';
            }
            flash_set('error', $msg);
            redirect('files.php?type=' . rawurlencode($type));
        }
        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > FILES_MAX_BYTES) {
            flash_set('error', 'File must be 1 B – 10 MB.');
            redirect('files.php?type=' . rawurlencode($type));
        }
        $safe = files_safe_name((string) ($file['name'] ?? ''));
        if ($safe === '') {
            flash_set('error', 'Unusable filename.');
            redirect('files.php?type=' . rawurlencode($type));
        }
        $ext = strtolower((string) pathinfo($safe, PATHINFO_EXTENSION));
        if (!in_array($ext, $FORMATS[$type]['exts'], true)) {
            flash_set('error', $FORMATS[$type]['label'] . ' accepts: ' . implode(', ', $FORMATS[$type]['exts']) . '.');
            redirect('files.php?type=' . rawurlencode($type));
        }
        $dir = files_dir($ROOT, $FORMATS, $type);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            flash_set('error', 'Storage folder is not writable.');
            redirect('files.php?type=' . rawurlencode($type));
        }
        // Never overwrite silently: file-2, file-3, …
        $target = $dir . DIRECTORY_SEPARATOR . $safe;
        if (is_file($target)) {
            $stem = (string) pathinfo($safe, PATHINFO_FILENAME);
            $i = 2;
            do {
                $candidate = $stem . '-' . $i . '.' . $ext;
                $target = $dir . DIRECTORY_SEPARATOR . $candidate;
                $i++;
            } while (is_file($target) && $i < 1000);
            $safe = basename($target);
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp) || !@move_uploaded_file($tmp, $target)) {
            flash_set('error', 'Upload failed — please retry.');
            redirect('files.php?type=' . rawurlencode($type));
        }
        @chmod($target, 0644);

        $description = trim((string) ($_POST['description'] ?? ''));
        $description = str_replace(["\r\n", "\r"], "\n", $description);
        if (mb_strlen($description, 'UTF-8') > FILES_DESC_MAX) {
            $description = mb_substr($description, 0, FILES_DESC_MAX, 'UTF-8');
        }
        $descSaved = false;
        if ($description !== '') {
            $descSaved = repdoc_file_description_save($type, $safe, $description);
        }

        $thumbNote = '';
        $thumbFile = $_FILES['thumb'] ?? null;
        if (is_array($thumbFile) && ($thumbFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            [$thumbOk, $thumbErr] = repdoc_file_thumb_save($type, $safe, $thumbFile);
            $thumbNote = $thumbOk ? ' · thumbnail added' : ' · thumbnail: ' . $thumbErr;
        }

        $me = current_user();
        if ($me !== null) {
            log_usage((int) $me['id'], 'admin-files', 'ok',
                'upload:' . $type . ':' . $safe . ($description !== '' ? ':described' : ''));
        }
        $note = $description !== ''
            ? ($descSaved ? ' · description saved' : ' · description not saved (database unavailable)')
            : ' · no description yet';
        $flashType = ($description !== '' && !$descSaved) || str_contains($thumbNote, 'thumbnail:') ? 'error' : 'ok';
        flash_set($flashType, $FORMATS[$type]['label'] . ' uploaded: ' . $safe . ' (' . files_human_size($size) . ')' . $note . $thumbNote . '.');
        redirect('files.php?type=' . rawurlencode($type));
    }

    if ($action === 'describe') {
        $type = strtolower((string) ($_POST['type'] ?? ''));
        $name = basename((string) ($_POST['file'] ?? ''));
        if (!isset($FORMATS[$type]) || $name === '' || str_starts_with($name, '.')) {
            flash_set('error', 'Unknown file.');
            redirect('files.php');
        }
        $ext = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
        $dir = files_dir($ROOT, $FORMATS, $type);
        if (!in_array($ext, $FORMATS[$type]['exts'], true) || !is_file($dir . DIRECTORY_SEPARATOR . $name)) {
            flash_set('error', 'File not found.');
            redirect('files.php?type=' . rawurlencode($type));
        }
        $description = trim((string) ($_POST['description'] ?? ''));
        $description = str_replace(["\r\n", "\r"], "\n", $description);
        if (mb_strlen($description, 'UTF-8') > FILES_DESC_MAX) {
            $description = mb_substr($description, 0, FILES_DESC_MAX, 'UTF-8');
        }
        $saved = repdoc_file_description_save($type, $name, $description);

        $thumbAction = '';
        $thumbError = '';
        if (!empty($_POST['remove_thumb'])) {
            repdoc_file_thumb_delete($type, $name);
            $thumbAction = 'thumbnail removed';
        }
        $thumb = $_FILES['thumb'] ?? null;
        if (is_array($thumb) && ($thumb['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            [$thumbOk, $thumbErr] = repdoc_file_thumb_save($type, $name, $thumb);
            if ($thumbOk) {
                $thumbAction = 'thumbnail updated';
            } else {
                $thumbError = $thumbErr;
            }
        }

        $me = current_user();
        if ($me !== null) {
            log_usage((int) $me['id'], 'admin-files', 'ok',
                'describe:' . $type . ':' . $name . ':' . ($description === '' ? 'cleared' : 'saved')
                . ($thumbAction !== '' ? ':' . $thumbAction : ''));
        }

        $parts = [];
        if ($saved) {
            $parts[] = $description === '' ? 'Description cleared' : 'Description saved';
        } else {
            $parts[] = 'Description could not be saved (database unavailable)';
        }
        if ($thumbAction !== '') $parts[] = ucfirst($thumbAction);
        if ($thumbError !== '') $parts[] = 'Thumbnail: ' . $thumbError;
        flash_set(($thumbError !== '' || !$saved) ? 'error' : 'ok', implode(' · ', $parts) . ': ' . $name . '.');
        redirect('files.php?type=' . rawurlencode($type));
    }

    if ($action === 'delete') {
        $type = strtolower((string) ($_POST['type'] ?? ''));
        $name = basename((string) ($_POST['file'] ?? ''));
        if (!isset($FORMATS[$type]) || $name === '' || str_starts_with($name, '.')) {
            flash_set('error', 'Unknown file.');
            redirect('files.php');
        }
        $ext = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, $FORMATS[$type]['exts'], true)) {
            flash_set('error', 'Unknown file.');
            redirect('files.php?type=' . rawurlencode($type));
        }
        $dir = files_dir($ROOT, $FORMATS, $type);
        $real = realpath($dir . DIRECTORY_SEPARATOR . $name);
        $rootReal = realpath($dir);
        if ($real === false || $rootReal === false || !str_starts_with($real, $rootReal) || !is_file($real)) {
            flash_set('error', 'File not found.');
            redirect('files.php?type=' . rawurlencode($type));
        }
        if (!@unlink($real)) {
            flash_set('error', 'Delete failed — check file permissions.');
            redirect('files.php?type=' . rawurlencode($type));
        }
        repdoc_file_description_delete($type, $name);
        repdoc_file_thumb_delete($type, $name);
        $me = current_user();
        if ($me !== null) log_usage((int) $me['id'], 'admin-files', 'ok', 'delete:' . $type . ':' . $name);
        flash_set('ok', 'Deleted: ' . $name . '.');
        redirect('files.php?type=' . rawurlencode($type));
    }

    flash_set('error', 'Unknown action.');
    redirect('files.php');
}

// ── GET: filter + listing ───────────────────────────────────────────
$filter = strtolower((string) ($_GET['type'] ?? 'all'));
if ($filter !== 'all' && !isset($FORMATS[$filter])) $filter = 'all';

$rows = [];
$totals = [];
foreach ($FORMATS as $type => $meta) {
    $dir = files_dir($ROOT, $FORMATS, $type);
    $list = [];
    foreach (@scandir($dir) ?: [] as $name) {
        if ($name === '.' || $name === '..' || str_starts_with($name, '.')) continue;
        $ext = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, $meta['exts'], true)) continue;
        $path = $dir . DIRECTORY_SEPARATOR . $name;
        if (!is_file($path)) continue;
        $list[] = [
            'name'  => $name,
            'type'  => $type,
            'hub'   => $meta['hub'],
            'ext'   => $ext,
            'size'  => (int) @filesize($path),
            'mtime' => (int) @filemtime($path),
        ];
    }
    usort($list, static fn(array $a, array $b): int => $b['mtime'] <=> $a['mtime']);
    $totals[$type] = ['n' => count($list), 'bytes' => array_sum(array_column($list, 'size'))];
    foreach ($list as $r) $rows[] = $r;
}
usort($rows, static fn(array $a, array $b): int => $b['mtime'] <=> $a['mtime']);
if ($filter !== 'all') {
    $rows = array_values(array_filter($rows, static fn(array $r): bool => $r['type'] === $filter));
}
$totalFiles = array_sum(array_column($totals, 'n'));
$totalBytes = array_sum(array_column($totals, 'bytes'));
$descriptions = repdoc_file_descriptions_all();
$described = 0;
foreach ($rows as $r) {
    if (($descriptions[repdoc_file_description_key($r['type'], $r['name'])] ?? '') !== '') $described++;
}

admin_page_start('File Packs', 'files.php');
admin_notice();
?>

<style>
    .desc-cell { max-width: 300px; }
    .desc-preview {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        overflow-wrap: anywhere;
        margin-bottom: 4px;
    }
    .desc-editor summary {
        cursor: pointer;
        color: var(--primary);
        font-size: .78rem;
        font-weight: 700;
        list-style: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 9px;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        background: var(--bg-surface);
    }
    .desc-editor summary:hover { border-color: var(--primary); background: var(--primary-light); }
    .desc-editor summary::-webkit-details-marker { display: none; }
    .desc-editor summary::before { content: "+"; font-weight: 800; line-height: 1; }
    .desc-editor[open] summary::before { content: "−"; }
    .upload-form { display: grid; grid-template-columns: minmax(170px, .8fr) minmax(220px, 1.2fr) auto; gap: 12px; align-items: end; }
    .upload-form label { display: grid; gap: 6px; min-width: 0; }
    .upload-form .upload-wide { grid-column: 1 / -1; }
    .upload-form select,
    .upload-form input[type="file"],
    .upload-form textarea {
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
    .upload-form textarea { min-height: 78px; line-height: 1.55; resize: vertical; }
    .upload-form .upload-note { font-size: .74rem; color: var(--text-muted); }
    .upload-form .upload-submit { align-self: end; }
    @media (max-width: 760px) {
        .upload-form { grid-template-columns: 1fr; }
        .upload-form .upload-submit { width: 100%; }
    }
    .desc-form { display: grid; gap: 8px; margin-top: 8px; min-width: 260px; }
    .desc-form textarea {
        width: 100%;
        min-height: 84px;
        padding: 9px 11px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font: inherit;
        font-size: .86rem;
        line-height: 1.5;
        resize: vertical;
        background: #fff;
    }
    .desc-form-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .desc-count { font-size: .74rem; color: var(--text-muted); }
    .file-cell { display: flex; align-items: center; gap: 10px; }
    .file-cell-text { min-width: 0; }
    .file-thumb-mini {
        flex: none;
        width: 48px; height: 34px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid var(--border-color);
        background: var(--bg-muted);
        display: block;
    }
    .file-thumb-empty {
        display: grid; place-items: center;
        font-family: var(--font-mono); font-size: .7rem; font-weight: 700;
        color: var(--text-muted);
    }
    .desc-thumb-row {
        display: flex; flex-wrap: wrap; align-items: flex-end; gap: 10px;
        padding: 8px;
        border: 1px dashed var(--border-color);
        border-radius: 8px;
        background: var(--bg-base);
    }
    .desc-thumb-preview {
        width: 84px; height: 56px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid var(--border-color);
        background: #fff;
    }
    .thumb-input { display: grid; gap: 4px; flex: 1 1 180px; min-width: 0; }
    .thumb-input input { font-size: .78rem; max-width: 100%; }
    .thumb-remove { display: inline-flex; align-items: center; gap: 5px; font-size: .76rem; color: var(--danger); }
</style>

<div class="admin-page-head">
    <div>
        <p class="kicker">Uploads</p>
        <h1 class="admin-title">File Packs</h1>
        <p class="admin-sub">PSD / AI / PLP + Word / Excel / PowerPoint — <?= number_format($totalFiles) ?> files · <?= e(files_human_size($totalBytes)) ?> total · <?= number_format($described) ?> with a description. Public pages update instantly.</p>
    </div>
</div>

<div class="panel">
    <div class="panel-head">Upload a pack <span class="count">max 10 MB</span></div>
    <div class="panel-body">
        <form method="post" enctype="multipart/form-data" action="files.php" class="upload-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="upload">
            <input type="hidden" name="MAX_FILE_SIZE" value="<?= FILES_MAX_BYTES ?>">
            <label>
                <span class="cell-sub">Format</span>
                <select name="type" required>
                    <?php foreach ($FORMATS as $type => $meta): ?>
                        <option value="<?= e($type) ?>"<?= $filter === $type ? ' selected' : '' ?>>
                            <?= e($meta['label']) ?> — <?= e($meta['hub']) ?>/<?= e($type) ?>/ (<?= e(implode(', ', $meta['exts'])) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span class="cell-sub">File (same name goes live)</span>
                <input type="file" name="pack" required>
            </label>
            <button type="submit" class="btn btn-primary upload-submit">Upload</button>
            <label class="upload-wide">
                <span class="cell-sub">Description <span class="upload-note">— optional, shown on the public showcase</span></span>
                <textarea name="description" rows="2" maxlength="<?= FILES_DESC_MAX ?>" placeholder="এই ফাইলটি কী কাজে লাগবে, কীভাবে ব্যবহার করবেন — সংক্ষেপে লিখুন…"></textarea>
            </label>
            <label class="upload-wide">
                <span class="cell-sub">Thumbnail <span class="upload-note">— optional JPG, PNG, WebP or GIF · max 2 MB</span></span>
                <input type="file" name="thumb" accept="image/jpeg,image/png,image/webp,image/gif">
            </label>
        </form>
        <p class="admin-sub" style="margin:10px 0 0">The description is saved against the final filename, so duplicates stay independent. Existing names are never overwritten — a duplicate becomes <span class="cell-sub">name-2.ext</span>. Server upload limit: <?= e((string) ini_get('upload_max_filesize')) ?>.</p>
    </div>
</div>

<div class="panel">
    <div class="panel-head">
        Published files
        <span class="count"><?= number_format(count($rows)) ?><?= $filter !== 'all' ? ' · ' . e(strtoupper($filter)) : '' ?></span>
        <span style="margin-left:auto;display:flex;gap:8px;flex-wrap:wrap">
            <a class="btn btn-secondary btn-sm" href="files.php"<?= $filter === 'all' ? ' aria-current="page"' : '' ?>>All (<?= (int) $totalFiles ?>)</a>
            <?php foreach ($FORMATS as $type => $meta): ?>
                <a class="btn btn-secondary btn-sm" href="files.php?type=<?= e($type) ?>"<?= $filter === $type ? ' aria-current="page"' : '' ?>><img src="<?= e(admin_format_logo_url($type)) ?>" alt="" width="14" height="14" loading="lazy" decoding="async" style="vertical-align:middle;margin-right:4px"> <?= e($meta['label']) ?> (<?= (int) $totals[$type]['n'] ?>)</a>
            <?php endforeach; ?>
        </span>
    </div>
    <?php if ($rows === []): ?>
        <div class="empty-state-sm">No files here yet — upload the first pack above.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>File</th><th>Format</th><th>Size</th><th>Updated</th><th>Description</th><th>Public page</th><th></th><th></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r):
                    $dl = '../' . $r['hub'] . '/download.php?type=' . rawurlencode($r['type']) . '&file=' . rawurlencode($r['name']);
                    $page = '../' . $r['hub'] . '/';
                    $desc = $descriptions[repdoc_file_description_key($r['type'], $r['name'])] ?? '';
                    $thumbUrl = repdoc_file_thumb_url($r['type'], $r['name']);
                ?>
                    <tr id="file-<?= e(substr(md5($r['type'] . ':' . $r['name']), 0, 10)) ?>">
                        <td>
                            <div class="file-cell">
                                <?php if ($thumbUrl !== ''): ?>
                                    <img class="file-thumb-mini" src="<?= e($thumbUrl) ?>" alt="" width="48" height="34" loading="lazy">
                                <?php else: ?>
                                    <span class="file-thumb-mini file-thumb-empty" aria-hidden="true"><img src="<?= e(admin_format_logo_url($r['type'])) ?>" alt="" width="28" height="28" loading="lazy" decoding="async"></span>
                                <?php endif; ?>
                                <span class="file-cell-text"><span class="cell-name"><?= e($r['name']) ?></span><span class="cell-sub"><?= e($r['hub']) ?>/<?= e($r['type']) ?>/</span></span>
                            </div>
                        </td>
                        <td><span class="pill pill-approved"><img src="<?= e(admin_format_logo_url($r['type'])) ?>" alt="" width="12" height="12" loading="lazy" decoding="async" style="vertical-align:middle;margin-right:4px"><?= e(strtoupper($r['type']) . ' · ' . strtoupper($r['ext'])) ?></span></td>
                        <td class="balance"><?= e(files_human_size($r['size'])) ?></td>
                        <td class="cell-sub"><?= $r['mtime'] > 0 ? e(date('d-m-Y H:i', $r['mtime'])) : '—' ?></td>
                        <td class="desc-cell">
                            <?php if ($desc !== ''): ?>
                                <span class="cell-sub desc-preview" title="<?= e($desc) ?>"><?= e($desc) ?></span>
                            <?php else: ?>
                                <span class="cell-sub">No description</span>
                            <?php endif; ?>
                            <details class="desc-editor">
                                <summary><?= ($desc === '' && $thumbUrl === '') ? 'Add description' : 'Edit description' ?></summary>
                                <form method="post" action="files.php" class="desc-form" enctype="multipart/form-data">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="describe">
                                    <input type="hidden" name="type" value="<?= e($r['type']) ?>">
                                    <input type="hidden" name="file" value="<?= e($r['name']) ?>">
                                    <textarea name="description" rows="3" maxlength="<?= FILES_DESC_MAX ?>"
                                              placeholder="Showcase text shown under this file on the public page…"
                                              oninput="var c=this.closest('form').querySelector('.desc-count'); if(c){c.textContent=this.value.length+'/<?= FILES_DESC_MAX ?>';}"><?= e($desc) ?></textarea>
                                    <div class="desc-thumb-row">
                                        <?php if ($thumbUrl !== ''): ?>
                                            <img class="desc-thumb-preview" src="<?= e($thumbUrl) ?>" alt="Current thumbnail" width="84" height="56" loading="lazy">
                                        <?php endif; ?>
                                        <label class="thumb-input">
                                            <span class="cell-sub">Thumbnail — JPG, PNG, WebP or GIF · max 2 MB</span>
                                            <input type="file" name="thumb" accept="image/jpeg,image/png,image/webp,image/gif">
                                        </label>
                                        <?php if ($thumbUrl !== ''): ?>
                                            <label class="thumb-remove"><input type="checkbox" name="remove_thumb" value="1"> Remove thumbnail</label>
                                        <?php endif; ?>
                                    </div>
                                    <div class="desc-form-actions">
                                        <button type="submit" class="btn btn-primary btn-sm">Save description &amp; thumbnail</button>
                                        <span class="desc-count"><?= mb_strlen($desc, 'UTF-8') ?>/<?= FILES_DESC_MAX ?></span>
                                        <span class="cell-sub">Leave the description empty to remove it</span>
                                    </div>
                                </form>
                            </details>
                        </td>
                        <td><a class="btn btn-secondary btn-sm" href="<?= e($page) ?>" target="_blank" rel="noopener">View</a></td>
                        <td><a class="btn btn-secondary btn-sm" href="<?= e($dl) ?>">Download</a></td>
                        <td>
                            <form method="post" action="files.php" onsubmit="return confirm('Delete <?= e($r['name']) ?>?');" style="margin:0">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="type" value="<?= e($r['type']) ?>">
                                <input type="hidden" name="file" value="<?= e($r['name']) ?>">
                                <button type="submit" class="btn btn-secondary btn-sm" aria-label="Delete <?= e($r['name']) ?>">
                                    <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-trash"/></svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    <div class="panel-body" style="padding-top:0">
        <p class="admin-sub" style="margin:0">
            Storage: offlinefiles/psd+ai+plp → <a href="../offlinefiles/" target="_blank" rel="noopener">/offlinefiles/</a> ·
            officefiles/word+excel+ppt → <a href="../officefiles/" target="_blank" rel="noopener">/officefiles/</a>.
            Only the listed extensions are served; everything else in those folders is ignored.
            Descriptions appear under each file on <a href="../officefiles/" target="_blank" rel="noopener">the public showcase</a> — leave one empty to fall back to the format note.
        </p>
    </div>
</div>

<?php admin_page_end(); ?>
