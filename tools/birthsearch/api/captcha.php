<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/BDRISVerifier.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $captchaData = BDRISVerifier::initCaptchaSession();
    if (!$captchaData['ok']) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => $captchaData['error'] ?? 'ক্যাপচা লোড করতে ত্রুটি হয়েছে।']);
        exit;
    }

    echo json_encode($captchaData, JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
