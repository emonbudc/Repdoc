<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
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
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>পাওয়া যায়নি</title>'
       . '<p style="font:16px/1.7 system-ui,sans-serif;max-width:520px;margin:80px auto;text-align:center">সনদ পাওয়া যায়নি।</p>';
    exit;
}

$flash = flash_get();
$notice = '';
if ($flash) {
    $notice = '<div style="padding:10px 14px;border:1.5px solid #a9cdb8;border-radius:8px;background:#e3f1e9;color:#0b4a33;font-weight:600">' . e((string) ($flash['message'] ?? '')) . '</div>';
}

echo prottoyon_render_saved($row, [
    'back_url' => repdoc_url('Entrepreneur/panel/prottoyons.php'),
    'self_url' => repdoc_url('Entrepreneur/panel/view.php?id=' . $id),
    'lang' => (isset($_GET['lang']) && $_GET['lang'] === 'en') ? 'en' : 'bn',
    'notice' => $notice,
]);
