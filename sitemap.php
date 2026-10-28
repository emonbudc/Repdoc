<?php
declare(strict_types=1);

require_once __DIR__ . '/src/sitemap.php';

header('Content-Type: application/xml; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=3600');

echo repdoc_sitemap_xml();
