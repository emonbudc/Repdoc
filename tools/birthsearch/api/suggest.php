<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/SearchEngine.php';

$db = getDB();
$engine = new SearchEngine($db);

$q = trim($_GET['q'] ?? '');
$limit = max(1, min(12, (int) ($_GET['limit'] ?? 8)));

if ($q === '') {
    echo json_encode(['suggestions' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'suggestions' => $engine->suggest($q, $limit),
], JSON_UNESCAPED_UNICODE);