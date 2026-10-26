<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
require_once dirname(__DIR__) . '/src/bootstrap.php';
repdoc_security_headers();

$id = (int) ($_GET['id'] ?? 0);
$row = null;
if ($id > 0 && repdoc_db()) {
    try {
        $st = repdoc_db()->prepare('SELECT * FROM document_logs WHERE id = ?');
        $st->execute([$id]);
        $candidate = $st->fetch();
        if ($candidate && str_starts_with((string) $candidate['tool_key'], 'prottoyon_')) $row = $candidate;
    } catch (Throwable $e) {}
}

if (!$row) {
    admin_page_start('Prottoyon', 'prottoyon.php');
    echo '<div class="alert alert-error" role="alert">সনদ পাওয়া যায়নি।</div>';
    echo '<p><a class="btn btn-secondary btn-sm" href="prottoyon.php">← ফিরে যান</a></p>';
    admin_page_end();
    exit;
}

$flash = flash_get();
$notice = '';
if ($flash) {
    $notice = '<div style="padding:10px 14px;border:1.5px solid #a9cdb8;border-radius:8px;background:#e3f1e9;color:#0b4a33;font-weight:600">' . e((string) ($flash['message'] ?? '')) . '</div>';
}

$revoked = (($row['status'] ?? 'valid') === 'revoked');
$action = '<form method="post" action="' . e(repdoc_url('admin/prottoyon.php')) . '" style="display:inline">'
    . csrf_field()
    . '<input type="hidden" name="id" value="' . (int) $row['id'] . '">'
    . '<input type="hidden" name="act" value="' . ($revoked ? 'restore' : 'revoke') . '">'
    . '<button type="submit" style="background:#fff;border-color:#c1272d;color:#c1272d" onclick="return confirm(\'নিশ্চিত?\')">' . ($revoked ? 'পুনর্বহাল' : 'বাতিল') . '</button>'
    . '</form>';

$options = [
    'back_url' => repdoc_url('admin/prottoyon.php'),
    'self_url' => repdoc_url('admin/prottoyon-view.php?id=' . (int) $row['id']),
    'lang' => (isset($_GET['lang']) && $_GET['lang'] === 'en') ? 'en' : 'bn',
    'notice' => $notice,
    'actions' => $action,
];
echo prottoyon_render_saved($row, $options);
