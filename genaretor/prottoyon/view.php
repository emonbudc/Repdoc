<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
require_login();
repdoc_security_headers();

$me = current_user();
$id = (int) ($_GET['id'] ?? 0);
$row = null;
if ($id > 0 && repdoc_db()) {
    try {
        $st = repdoc_db()->prepare('SELECT * FROM document_logs WHERE id = ?');
        $st->execute([$id]);
        $candidate = $st->fetch();
        if ($candidate && str_starts_with((string) $candidate['tool_key'], 'prottoyon_')) {
            if ((int) $candidate['user_id'] === (int) $me['id'] || is_admin()) $row = $candidate;
        }
    } catch (Throwable $e) {}
}

if (!$row) {
    http_response_code(404);
    prottoyon_page_start('সনদ পাওয়া যায়নি');
    echo '<div class="pp-alert pp-alert-error" role="alert">সনদটি পাওয়া যায়নি অথবা এটি আপনার নয়।</div>';
    echo '<p><a class="pp-btn" href="' . e(repdoc_url('genaretor/prottoyon/applications.php')) . '">← আমার আবেদনে ফিরুন</a></p>';
    prottoyon_page_end();
    exit;
}

$flash = flash_get();
$notice = '';
if ($flash) {
    $type = ($flash['type'] ?? '') === 'ok' ? 'pp-alert-success' : 'pp-alert-error';
    $notice = '<div class="' . $type . '" style="padding:10px 14px;border:1.5px solid #a9cdb8;border-radius:8px;background:#e3f1e9;color:#0b4a33;font-weight:600">' . e((string) ($flash['message'] ?? '')) . '</div>';
}

$backUrl = repdoc_url('genaretor/prottoyon/applications.php');
if (is_admin()) $backUrl = repdoc_url('admin/prottoyon.php');
elseif (is_udc()) $backUrl = repdoc_url('Entrepreneur/panel/prottoyons.php');

$decoded = prottoyon_decode_row($row);
$reapply = '';
if ((int) ($decoded['v'] ?? 1) >= 2 && !is_admin()) {
    $rt = (string) ($decoded['type'] ?? '');
    if (prottoyon_type($rt) !== null) {
        $reapply = '<a href="' . e(repdoc_url('genaretor/prottoyon/form.php?type=' . rawurlencode($rt) . '&reapply=' . $id)) . '">আবার আবেদন</a>';
    }
}

$options = [
    'back_url' => $backUrl,
    'self_url' => repdoc_url('genaretor/prottoyon/view.php?id=' . $id),
    'lang' => (isset($_GET['lang']) && $_GET['lang'] === 'en') ? 'en' : 'bn',
    'notice' => $notice,
    'actions' => $reapply,
];
echo prottoyon_render_saved($row, $options);
