<?php
declare(strict_types=1);

/**
 * Repdoc — admin: file packs (PSD / AI / PLP + Word / Excel).
 *
 * Admin-only (NOT UDC panel): upload new packs and manage the published
 * ones. Files live in:
 *   offlinefiles/{psd,ai,plp}/   → PSD, AI, PLP hub
 *   officefiles/{word,excel}/     → Word, Excel hub
 * The public pages auto-list whatever is on disk; downloads stay gated
 * behind approved login via each hub's download.php.
 */

require_once __DIR__ . '/includes/layout.php';

const FILES_MAX_BYTES = 10 * 1024 * 1024; // 10 MB — InfinityFree per-file limit.

/** Format key => hub dir, label, allowed extensions. */
$FORMATS = [
    'psd'   => ['hub' => 'offlinefiles', 'label' => 'PSD',   'exts' => ['psd', 'psb', 'zip']],
    'ai'    => ['hub' => 'offlinefiles', 'label' => 'AI',    'exts' => ['ai', 'eps', 'zip']],
    'plp'   => ['hub' => 'offlinefiles', 'label' => 'PLP',   'exts' => ['plp', 'zip']],
    'word'  => ['hub' => 'officefiles',  'label' => 'Word',  'exts' => ['doc', 'docx', 'rtf', 'odt', 'zip']],
    'excel' => ['hub' => 'officefiles',  'label' => 'Excel', 'exts' => ['xls', 'xlsx', 'csv', 'zip']],
];

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
        $me = current_user();
        if ($me !== null) log_usage((int) $me['id'], 'admin-files', 'ok', 'upload:' . $type . ':' . $safe);
        flash_set('ok', $FORMATS[$type]['label'] . ' uploaded: ' . $safe . ' (' . files_human_size($size) . ').');
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

admin_page_start('File Packs', 'files.php');
admin_notice();
?>

<div class="admin-page-head">
    <div>
        <p class="kicker">Uploads</p>
        <h1 class="admin-title">File Packs</h1>
        <p class="admin-sub">PSD / AI / PLP + Word / Excel — <?= number_format($totalFiles) ?> files · <?= e(files_human_size($totalBytes)) ?> total. Public pages update instantly.</p>
    </div>
</div>

<div class="panel">
    <div class="panel-head">Upload a pack <span class="count">max 10 MB</span></div>
    <div class="panel-body">
        <form method="post" enctype="multipart/form-data" action="files.php" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="upload">
            <input type="hidden" name="MAX_FILE_SIZE" value="<?= FILES_MAX_BYTES ?>">
            <label style="display:grid;gap:6px;min-width:200px">
                <span class="cell-sub">Format</span>
                <select name="type" required style="min-height:44px;padding:10px 12px;border:1px solid var(--border);border-radius:8px;font:inherit">
                    <?php foreach ($FORMATS as $type => $meta): ?>
                        <option value="<?= e($type) ?>"<?= $filter === $type ? ' selected' : '' ?>>
                            <?= e($meta['label']) ?> — <?= e($meta['hub']) ?>/<?= e($type) ?>/ (<?= e(implode(', ', $meta['exts'])) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label style="display:grid;gap:6px;flex:1 1 240px">
                <span class="cell-sub">File (same name goes live)</span>
                <input type="file" name="pack" required style="min-height:44px;padding:10px 12px;border:1px solid var(--border);border-radius:8px;font:inherit;background:#fff">
            </label>
            <button type="submit" class="btn btn-primary">Upload</button>
        </form>
        <p class="admin-sub" style="margin:10px 0 0">Existing names are never overwritten — a duplicate becomes <span class="cell-sub">name-2.ext</span>. Server upload limit: <?= e((string) ini_get('upload_max_filesize')) ?>.</p>
    </div>
</div>

<div class="panel">
    <div class="panel-head">
        Published files
        <span class="count"><?= number_format(count($rows)) ?><?= $filter !== 'all' ? ' · ' . e(strtoupper($filter)) : '' ?></span>
        <span style="margin-left:auto;display:flex;gap:8px;flex-wrap:wrap">
            <a class="btn btn-secondary btn-sm" href="files.php"<?= $filter === 'all' ? ' aria-current="page"' : '' ?>>All (<?= (int) $totalFiles ?>)</a>
            <?php foreach ($FORMATS as $type => $meta): ?>
                <a class="btn btn-secondary btn-sm" href="files.php?type=<?= e($type) ?>"<?= $filter === $type ? ' aria-current="page"' : '' ?>><?= e($meta['label']) ?> (<?= (int) $totals[$type]['n'] ?>)</a>
            <?php endforeach; ?>
        </span>
    </div>
    <?php if ($rows === []): ?>
        <div class="empty-state-sm">No files here yet — upload the first pack above.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>File</th><th>Format</th><th>Size</th><th>Updated</th><th>Public page</th><th></th><th></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r):
                    $dl = '../' . $r['hub'] . '/download.php?type=' . rawurlencode($r['type']) . '&file=' . rawurlencode($r['name']);
                    $page = '../' . $r['hub'] . '/';
                ?>
                    <tr>
                        <td><span class="cell-name"><?= e($r['name']) ?></span><span class="cell-sub"><?= e($r['hub']) ?>/<?= e($r['type']) ?>/</span></td>
                        <td><span class="pill pill-approved"><?= e(strtoupper($r['type']) . ' · ' . strtoupper($r['ext'])) ?></span></td>
                        <td class="balance"><?= e(files_human_size($r['size'])) ?></td>
                        <td class="cell-sub"><?= $r['mtime'] > 0 ? e(date('d-m-Y H:i', $r['mtime'])) : '—' ?></td>
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
            officefiles/word+excel → <a href="../officefiles/" target="_blank" rel="noopener">/officefiles/</a>.
            Only the listed extensions are served; everything else in those folders is ignored.
        </p>
    </div>
</div>

<?php admin_page_end(); ?>
