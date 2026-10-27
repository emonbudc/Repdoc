<?php
declare(strict_types=1);

/**
 * Repdoc — mouza map download (admin only).
 *
 * Validates the Drive file id against the manifest, logs the download, then
 * forwards to Google Drive. The files stay in Drive; this endpoint only decides
 * who may reach them and keeps a usage trail.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';
require_once dirname(__DIR__) . '/src/maps.php';

require_admin();

$id   = (string) ($_GET['id'] ?? '');
$file = repdoc_maps_file($id);

if ($file === null) {
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>ম্যাপ পাওয়া যায়নি</title>'
        . '<p style="font:16px/1.7 system-ui,sans-serif;max-width:520px;margin:80px auto;text-align:center">'
        . 'এই ম্যাপটি তালিকায় নেই। <a href="' . e(repdoc_url('admin/maps.php')) . '">ম্যাপ ব্রাউজারে ফিরে যান</a>।</p>';
    exit;
}

$user = current_user();
if ($user !== null) {
    log_usage(
        (int) $user['id'],
        'map_download',
        'ok',
        $file['district_bn'] . '/' . $file['upazila_bn'] . '/JL ' . $file['jl'] . '/' . $file['mouza'] . '/sheet ' . $file['sheet']
    );
}

header('Cache-Control: private, no-store');
header('X-Robots-Tag: noindex, nofollow');
header('Location: ' . repdoc_maps_drive_url($id), true, 302);
exit;
