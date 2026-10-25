<?php
declare(strict_types=1);

/**
 * Repdoc — authenticated download for UDC archive templates.
 *
 * The original files live in Entrepreneur/data/, which is blocked from direct
 * web access by its .htaccess. This endpoint re-checks the same archive grant
 * before streaming a whitelisted file, so links cannot be shared to bypass
 * the landing page.
 */

require_once dirname(__DIR__, 2) . '/src/bootstrap.php';

require_login();

if (!is_udc() && !is_admin()) {
    redirect(repdoc_url('Entrepreneur/apply/'));
}

if (!has_archive_access()) {
    flash_set('error', 'ডকুমেন্ট আর্কাইভ ব্যবহারের জন্য অ্যাডমিনের অনুমোদন প্রয়োজন।');
    redirect(repdoc_url('Entrepreneur/panel/'));
}

$allowed = [
    'oldblank.PNG',
    '8mgirls.jpg',
    'oldbc.jpg',
    'PSC.jpg',
    'Oxford_Prottoyon.JPG',
    'Gobarpara_Primary.jpg',
];

$file = (string) ($_GET['file'] ?? '');
if ($file === '' || !in_array($file, $allowed, true)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'File not found.';
    exit;
}

$path = REPDOC_ROOT . '/Entrepreneur/data/' . $file;
if (!is_file($path) || !is_readable($path)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'File not found.';
    exit;
}

$ext = strtolower((string) pathinfo($file, PATHINFO_EXTENSION));
$mime = [
    'png'  => 'image/png',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
][$ext] ?? 'application/octet-stream';

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) filesize($path));
header('Content-Disposition: attachment; filename="' . basename($file) . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, max-age=0');
readfile($path);
exit;
