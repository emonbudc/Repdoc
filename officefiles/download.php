<?php
declare(strict_types=1);

/**
 * Repdoc — officefiles download endpoint.
 *
 * Login-gated file serve for Word / Excel / PowerPoint packs. Listing page
 * (officefiles/index.php) is public; the bytes require an approved
 * operator login — same contract as every other tool (one account,
 * every tool). Direct folder URLs still work on hosts without
 * .htaccess protection, so this proxy is the enforced path linked
 * from the UI.
 *
 * Usage: download.php?type=word&file=example.docx
 */

require_once __DIR__ . '/../src/bootstrap.php';

require_login();

$ALLOWED = [
    'word'  => ['doc', 'docx', 'rtf', 'odt', 'zip'],
    'excel' => ['xls', 'xlsx', 'csv', 'zip'],
    'ppt'   => ['ppt', 'pptx', 'zip'],
];

$type = strtolower((string) ($_GET['type'] ?? ''));
$file = (string) ($_GET['file'] ?? '');

if (!isset($ALLOWED[$type])) {
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>ফাইল পাওয়া যায়নি</title>'
        . '<p style="font:16px/1.7 system-ui,sans-serif;max-width:520px;margin:80px auto;text-align:center">'
        . 'ভুল ফরম্যাট। <a href="' . e(repdoc_url('officefiles/')) . '">ফিরে যান</a></p>';
    exit;
}

// Block traversal: only a plain basename is accepted.
$base = basename($file);
if ($base === '' || $base !== $file || str_contains($base, "\0")) {
    http_response_code(400);
    exit;
}

$ext = strtolower((string) pathinfo($base, PATHINFO_EXTENSION));
if (!in_array($ext, $ALLOWED[$type], true)) {
    http_response_code(404);
    exit;
}

$path = __DIR__ . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . $base;
$real = realpath($path);
$root = realpath(__DIR__ . DIRECTORY_SEPARATOR . $type);
if ($real === false || $root === false || !str_starts_with($real, $root) || !is_file($real)) {
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>ফাইল পাওয়া যায়নি</title>'
        . '<p style="font:16px/1.7 system-ui,sans-serif;max-width:520px;margin:80px auto;text-align:center">'
        . 'ফাইল পাওয়া যায়নি। <a href="' . e(repdoc_url('officefiles/')) . '">ফিরে যান</a></p>';
    exit;
}

$user = current_user();
if ($user !== null) {
    log_usage((int) $user['id'], 'officefiles', 'ok', $type . ':' . $base);
}

$mime = [
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'rtf'  => 'application/rtf',
    'odt'  => 'application/vnd.oasis.opendocument.text',
    'xls'  => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'csv'  => 'text/csv',
    'ppt'  => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'zip'  => 'application/zip',
][$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($real));
header('Content-Disposition: attachment; filename="' . addcslashes($base, '"\\') . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0, must-revalidate');

readfile($real);
exit;
