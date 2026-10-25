<?php
declare(strict_types=1);

/**
 * Repdoc — authenticated passport photo stream.
 *
 * Files live in love/passport/<user>/ and are never directly web-accessible.
 * The owner can always open their own file; an administrator may add `u=<id>`
 * for support review. Everything else is a 404.
 */

define('REPDOC_PASSPORT', true);
require_once __DIR__ . '/lib.php';

require_login();
if (!passport_access_allowed()) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Forbidden.';
    exit;
}

repdoc_security_headers();

$me      = current_user();
$ownerId = (int) $me['id'];

if (is_admin() && isset($_GET['u'])) {
    $requested = (int) $_GET['u'];
    if ($requested > 0) $ownerId = $requested;
}

$name = (string) ($_GET['f'] ?? '');
$path = passport_file_path($ownerId, $name);

if ($path === '') {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'File not found.';
    exit;
}

$ext     = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
$mime    = match ($ext) {
    'png'  => 'image/png',
    'webp' => 'image/webp',
    default => 'image/jpeg',
};
$download = isset($_GET['download']);
$filename = 'passport-' . date('Ymd-His') . '.' . (in_array($ext, ['png', 'webp'], true) ? $ext : 'jpg');

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) filesize($path));
header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="' . $filename . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, max-age=0');
readfile($path);
exit;
