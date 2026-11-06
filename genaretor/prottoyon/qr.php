<?php
declare(strict_types=1);

$text = isset($_GET['text']) && is_string($_GET['text']) ? trim($_GET['text']) : '';
if ($text === '') {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Missing text');
}
if (strlen($text) > 1200) {
    $text = substr($text, 0, 1200);
}

require __DIR__ . '/assets/phpqrcode/qrlib.php';

$old = error_reporting(0);
$tmp = tempnam(sys_get_temp_dir(), 'repdoc-qr-');
if ($tmp === false) {
    http_response_code(500);
    exit;
}
QRcode::svg($text, $tmp, QR_ECLEVEL_L, 4, 2);
$svg = is_file($tmp) ? (string) file_get_contents($tmp) : '';
@unlink($tmp);
error_reporting($old);

if ($svg === '') {
    http_response_code(500);
    exit;
}

header('Content-Type: image/svg+xml; charset=utf-8');
header('Cache-Control: public, max-age=86400');
header('X-Content-Type-Options: nosniff');
echo $svg;
