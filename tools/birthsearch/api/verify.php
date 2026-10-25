<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/SearchEngine.php';
require_once __DIR__ . '/../includes/BDRISVerifier.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'অননুমোদিত মেথড']);
    exit;
}

try {
    $db = getDB();
    $engine = new SearchEngine($db);

    $recordId = (int) ($_POST['record_id'] ?? 0);
    $ubrn = trim($_POST['ubrn'] ?? '');
    $birthDate = trim($_POST['birth_date'] ?? '');
    $captchaAnswer = trim($_POST['captcha_answer'] ?? '');
    $captchaToken = trim($_POST['captcha_token'] ?? '');

    if ($recordId <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'অবৈধ রেকর্ড আইডি']);
        exit;
    }

    $existing = $engine->getFull($recordId);
    if (!$existing) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'রেকর্ডটি ডাটাবেজে পাওয়া যায়নি']);
        exit;
    }

    if (empty($ubrn)) {
        $ubrn = $existing['registration_number'] ?? '';
    }
    if (empty($birthDate)) {
        $birthDate = $existing['date_of_birth'] ?? '';
    }

    $verifyResult = BDRISVerifier::verifyRecord($ubrn, $birthDate, $captchaAnswer, $captchaToken, $existing);

    if (!$verifyResult['ok']) {
        http_response_code(422);
        echo json_encode($verifyResult, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Update the database record with verified data (including English names)
    $verifiedData = $verifyResult['record'];
    $updated = $engine->updateVerifiedRecord($recordId, $verifiedData);

    if (!$updated) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'ডাটাবেজে তথ্য আপডেট করতে ত্রুটি হয়েছে।']);
        exit;
    }

    $fullUpdated = $engine->getFull($recordId);

    echo json_encode(fixUtf8([
        'ok' => true,
        'message' => 'বিডিআরআইএস জাতীয় ডাটাবেজ থেকে তথ্য সফলভাবে যাচাই ও আপডেট করা হয়েছে!',
        'record' => $fullUpdated,
        'verified_fields' => [
            'name_bn' => $fullUpdated['name'] ?? '',
            'name_en' => $fullUpdated['name_en'] ?? '',
            'father_name_bn' => $fullUpdated['father_name'] ?? '',
            'father_name_en' => $fullUpdated['father_name_en'] ?? '',
            'mother_name_bn' => $fullUpdated['mother_name'] ?? '',
            'mother_name_en' => $fullUpdated['mother_name_en'] ?? '',
            'place_of_birth_en' => $fullUpdated['place_of_birth_en'] ?? '',
            'status' => 'verified',
            'verified_at' => $fullUpdated['verified_at'] ?? date('Y-m-d H:i:s'),
        ]
    ]), JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
