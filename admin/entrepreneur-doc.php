<?php
declare(strict_types=1);

/**
 * Repdoc — admin-only viewer for entrepreneur identity documents.
 *
 * The office ID card / trade licence lives under love/uploads/entrepreneur/,
 * which the web server blocks from direct access. This endpoint authenticates
 * an admin, checks the stored path stays inside that tree, then streams the
 * image inline.
 */

require_once __DIR__ . '/../src/bootstrap.php';
require_admin();

$appId = (int) ($_GET['id'] ?? 0);
$path  = '';

if ($appId > 0 && ($db = repdoc_db())) {
    try {
        $st = $db->prepare('SELECT id_doc_path FROM entrepreneur_apps WHERE id = ?');
        $st->execute([$appId]);
        $path = (string) ($st->fetchColumn() ?: '');
    } catch (Throwable $e) {
        $path = '';
    }
}

$full = entrepreneur_identity_doc_full_path($path);
if ($full === '') {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Document not found.';
    exit;
}

$mime = '';
try {
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($full);
    }
} catch (Throwable $e) {}
if (!in_array($mime, ['image/jpeg', 'image/png'], true)) {
    $mime = 'application/octet-stream';
}

$ext = strtolower((string) pathinfo($full, PATHINFO_EXTENSION));
$filename = 'identity-' . $appId . ($ext !== '' ? '.' . $ext : '');

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) filesize($full));
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Cache-Control: private, no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
readfile($full);
exit;
