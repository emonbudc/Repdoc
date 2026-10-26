<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_login();

$me = current_user();
$path = isset($_GET['f']) && is_string($_GET['f']) ? trim($_GET['f']) : '';
$path = ltrim(str_replace('\\', '/', $path), '/');

if ($path === '' || !str_starts_with($path, 'love/uploads/prottoyon/')) {
    http_response_code(404);
    exit('Not found');
}

$segments = explode('/', $path);
$ownerId = (int) ($segments[3] ?? 0);
if ($ownerId <= 0 || ((int) $me['id'] !== $ownerId && !is_admin())) {
    http_response_code(403);
    exit('Forbidden');
}

$root = realpath(REPDOC_ROOT . '/love/uploads/prottoyon');
$full = realpath(REPDOC_ROOT . '/' . $path);
if ($root === false || $full === false || !str_starts_with($full, $root . DIRECTORY_SEPARATOR) || !is_file($full)) {
    http_response_code(404);
    exit('Not found');
}

$mime = prottoyon_upload_mime($full);
$allowed = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
if (!in_array($mime, $allowed, true)) {
    http_response_code(403);
    exit('Forbidden');
}

$name = basename($full);
header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) filesize($full));
header('Content-Disposition: inline; filename="' . str_replace('"', '', $name) . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=3600');
readfile($full);
exit;
