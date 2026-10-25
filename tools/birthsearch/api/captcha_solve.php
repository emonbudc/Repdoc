<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/CaptchaSolverClient.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'অননুমোদিত মেথড'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST;
    }

    $base64Image = $input['captcha_base64'] ?? '';
    $mimeType = $input['captcha_mime'] ?? 'image/png';

    if (empty($base64Image)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'ক্যাপচা ইমেজ ডেটা পাওয়া যায়নি'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $solverUrl = defined('CAPTCHA_SOLVER_URL') ? CAPTCHA_SOLVER_URL : 'http://localhost:8001';
    $client = new CaptchaSolverClient($solverUrl);

    if (!$client->healthCheck()) {
        http_response_code(503);
        echo json_encode([
            'ok' => false, 
            'error' => 'ক্যাপচা সলভার সার্ভিস অনুপলব্ধ। ম্যানুয়ালি ক্যাপচা দিন।'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $result = $client->solve($base64Image, $mimeType);

    if (!$result['ok']) {
        http_response_code(422);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'ok' => true,
        'answer' => $result['answer'],
        'message' => 'ক্যাপচা সফলভাবে সমাধান করা হয়েছে'
    ], JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}