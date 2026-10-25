<?php
// JSON-only endpoint: never let a PHP notice/warning corrupt the response.
ini_set('display_errors', '0');

// Central auth + wallet (replaces the old per-tool auth/config.php).
require_once dirname(__DIR__, 3) . '/src/bootstrap.php';
if (!is_logged_in()) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Authentication required. Please login first.']);
    exit;
}
require_once __DIR__ . '/../everify-clone/proxy/init.php';

header('Content-Type: application/json; charset=utf-8');

function parseBirthSearchResult($html) {
    $doc = new DOMDocument();
    @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    $xpath = new DOMXPath($doc);

    $tables = $xpath->query('//table[contains(@class, "table-hover")]');
    if ($tables->length === 0) return null;

    $r = [];

    // ─── First table: Registration info (3-column alternating label/value rows) ───
    $rows = $xpath->query('.//tr', $tables->item(0));
    for ($i = 0; $i < $rows->length; $i++) {
        $cells = $xpath->query('.//td', $rows->item($i));
        $vals = [];
        foreach ($cells as $cell) {
            $vals[] = trim($cell->textContent);
        }
        $text = implode(' | ', $vals);

        if (stripos($text, 'Registration Date') !== false && $i + 1 < $rows->length) {
            $nextCells = $xpath->query('.//td', $rows->item($i + 1));
            $nextVals = [];
            foreach ($nextCells as $cell) {
                $nextVals[] = trim($cell->textContent);
            }
            $r['reg_date'] = $nextVals[0] ?? '';
            $r['reg_office'] = $nextVals[1] ?? '';
            $r['issuance_date'] = $nextVals[2] ?? '';
        }

        if (stripos($text, 'Date of Birth') !== false && $i + 1 < $rows->length) {
            $nextCells = $xpath->query('.//td', $rows->item($i + 1));
            $nextVals = [];
            foreach ($nextCells as $cell) {
                $nextVals[] = trim($cell->textContent);
            }
            $r['dob'] = $nextVals[0] ?? '';
            $r['brn'] = $nextVals[1] ?? '';
            $r['sex'] = $nextVals[2] ?? '';
        }
    }

    // ─── Second table: Person details (2-column-pair layout: label_bn, value_bn, label_en, value_en) ───
    if ($tables->length > 1) {
        $rows = $xpath->query('.//tr', $tables->item(1));
        foreach ($rows as $row) {
            $cells = $xpath->query('.//td', $row);
            $cellData = [];
            foreach ($cells as $cell) {
                $img = $xpath->query('.//img', $cell);
                if ($img->length > 0) {
                    $src = $img->item(0)->getAttribute('src');
                    if (strpos($src, 'base64') !== false && empty($r['photo'])) {
                        $r['photo'] = $src;
                    }
                    continue;
                }
                $cellData[] = trim($cell->textContent);
            }

            if (count($cellData) < 4) continue;

            $labelBn = $cellData[0] ?? '';
            $valueBn = $cellData[1] ?? '';
            $labelEn = $cellData[2] ?? '';
            $valueEn = $cellData[3] ?? '';
            $combinedText = $labelBn . $labelEn;

            if (strpos($combinedText, 'নিবন্ধিত ব্যক্তির নাম') !== false || strpos($combinedText, 'Registered Person Name') !== false) {
                $r['name_bn'] = $valueBn;
                $r['name_en'] = $valueEn;
            } elseif (strpos($combinedText, 'জন্মস্থান') !== false || strpos($combinedText, 'Place of Birth') !== false) {
                $r['place_of_birth_bn'] = $valueBn;
                $r['place_of_birth_en'] = $valueEn;
            } elseif (strpos($combinedText, 'মাতার নাম') !== false || stripos($combinedText, "Mother's Name") !== false) {
                $r['mother_name_bn'] = $valueBn;
                $r['mother_name_en'] = $valueEn;
            } elseif (strpos($combinedText, 'পিতার নাম') !== false || stripos($combinedText, "Father's Name") !== false) {
                $r['father_name_bn'] = $valueBn;
                $r['father_name_en'] = $valueEn;
            }
        }
    }

    return empty($r) ? null : $r;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'init') {
    $result = initSession('birth');
    if (!$result['ok']) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => $result['error'] ?? 'Failed to initialize session']);
        exit;
    }

    $session = $_SESSION['everify'] ?? null;
    if (!$session) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Session not created']);
        exit;
    }

    $cookies = $session['cookies'];
    $captchaToken = $session['captcha_token'];
    $captchaUrl = BASE_URL . '/DefaultCaptcha/Generate?t=' . urlencode($captchaToken);

    $ch = curl_init($captchaUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_REFERER => BASE_URL . '/',
        CURLOPT_TIMEOUT => 10,
    ]);
    if ($cookies) {
        curl_setopt($ch, CURLOPT_COOKIE, $cookies);
    }
    $imageData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $error = curl_error($ch);
    unset($ch);

    if ($error || $httpCode !== 200 || !$imageData) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Failed to fetch captcha: ' . ($error ?: 'HTTP ' . $httpCode)]);
        exit;
    }

    echo json_encode([
        'ok' => true,
        'captcha_base64' => base64_encode($imageData),
        'captcha_mime' => $contentType ?: 'image/png',
        'captcha_token' => $captchaToken,
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ubrn = trim($_POST['ubrn'] ?? '');
    $birthDate = trim($_POST['birth_date'] ?? '');
    $captchaAnswer = trim($_POST['captcha_answer'] ?? '');
    $errors = [];

    if (!preg_match('/^\d{17}$/', $ubrn)) {
        $errors[] = 'Birth Registration Number must be exactly 17 digits.';
    }
    if (!preg_match('/^\d{4}-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])$/', $birthDate)) {
        $errors[] = 'Date of Birth must be in YYYY-MM-DD format.';
    }
    if (empty($captchaAnswer)) {
        $errors[] = 'Please enter the captcha answer.';
    }

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'errors' => $errors]);
        exit;
    }

    $session = getSession();
    if (!$session) {
        $result = initSession('birth');
        if (!$result['ok']) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'Could not connect to the verification service.']);
            exit;
        }
        $session = $_SESSION['everify'];
    }

    // Use captcha_token from POST if provided (ensures consistency with the displayed captcha)
    $captchaToken = trim($_POST['captcha_token'] ?? '');
    if (!$captchaToken) {
        $captchaToken = $session['captcha_token'];
    }

    $postData = [
        'UBRN' => $ubrn,
        'BirthDate' => $birthDate,
        'CaptchaDeText' => $captchaToken,
        'CaptchaInputText' => $captchaAnswer,
        '__RequestVerificationToken' => $session['request_token'],
    ];

    $cookies = $session['cookies'];
    $raw = httpPost(BASE_URL . '/UBRNVerification/Search', $postData, $cookies);

    if (!$raw['ok']) {
        refreshSession('birth');
        http_response_code(502);
        echo json_encode(['ok' => false, 'error' => 'Could not connect to the verification service.']);
        exit;
    }

    $redirectPattern = '/<script[^>]*>\\s*window\.location\s*=\s*[\'"]([^\'"]+)[\'"]/i';
    if (preg_match($redirectPattern, $raw['body'], $m)) {
        $redirectUrl = $m[1];
        if (strpos($redirectUrl, 'http') !== 0) {
            $redirectUrl = BASE_URL . $redirectUrl;
        }
        $raw = httpGet($redirectUrl, $cookies);
        if (!$raw['ok']) {
            refreshSession('birth');
            http_response_code(502);
            echo json_encode(['ok' => false, 'error' => 'Session expired. Please try again.']);
            exit;
        }
    }

    if (stripos($raw['body'], 'validation-summary-errors') !== false || stripos($raw['body'], 'text-danger') !== false) {
        $doc = new DOMDocument();
        @$doc->loadHTML(mb_convert_encoding($raw['body'], 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($doc);

        $errorNodes = $xpath->query('//*[contains(@class, "validation-summary-errors")]//li');
        $apiErrors = [];
        foreach ($errorNodes as $node) {
            $apiErrors[] = trim($node->textContent);
        }

        if (empty($apiErrors)) {
            $errorNodes = $xpath->query('//span[contains(@class, "field-validation-error")]');
            foreach ($errorNodes as $node) {
                $apiErrors[] = trim($node->textContent);
            }
        }

        if (empty($apiErrors)) {
            $apiErrors[] = 'Invalid captcha or input. Please try again.';
        }

        refreshSession('birth');
        echo json_encode(['ok' => false, 'errors' => $apiErrors, 'captcha_expired' => true]);
        exit;
    }

    $record = parseBirthSearchResult($raw['body']);

    if (!$record) {
        refreshSession('birth');
        echo json_encode(['ok' => false, 'errors' => ['Could not parse record data. The site structure may have changed.'], 'captcha_expired' => true]);
        exit;
    }

    refreshSession('birth');

    echo json_encode(['ok' => true, 'record' => $record]);
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
