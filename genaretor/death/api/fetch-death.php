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

function deathCellTexts(DOMXPath $xpath, DOMNode $row): array
{
    $vals = [];
    foreach ($xpath->query('.//td', $row) as $cell) {
        $vals[] = trim(preg_replace('/\s+/u', ' ', $cell->textContent));
    }
    return $vals;
}

function deathAssignLabel(array &$r, string $label, array $vals): void
{
    $label = trim(preg_replace('/\s+/u', ' ', $label));
    $l = mb_strtolower($label, 'UTF-8');

    $is = static function (array $needles) use ($l): bool {
        foreach ($needles as $n) {
            if ($n !== '' && mb_strpos($l, mb_strtolower($n, 'UTF-8')) !== false) return true;
        }
        return false;
    };
    $val = static fn(int $i): string => trim((string) ($vals[$i] ?? ''));

    if ($is(['death registration number', 'মৃত্যু নিবন্ধন নম্বর', 'মৃত্যু নিবন্ধন নং'])) {
        $r['drn'] = $val(0);
    } elseif ($is(['registration date', 'নিবন্ধনের তারিখ'])) {
        $r['reg_date'] = $val(0);
        $r['reg_office'] = $val(1);
        $r['issue_date'] = $val(2);
    } elseif ($is(['date of issuance', 'ইস্যু'])) {
        $r['issue_date'] = $val(0);
    } elseif ($is(['date of death', 'death date', 'মৃত্যুর তারিখ'])) {
        $r['dod'] = $val(0);
        if (preg_match('/^\d{17}$/', preg_replace('/\D/', '', $val(1)))) {
            $r['drn'] = $val(1);
        }
        if ($val(2) !== '') $r['sex'] = $val(2);
    } elseif ($is(['date of birth', 'জন্ম তারিখ'])) {
        $r['dob'] = $val(0);
        if (preg_match('/^\d{17}$/', preg_replace('/\D/', '', $val(1)))) {
            $r['drn'] = $val(1);
        }
        if ($val(2) !== '') $r['sex'] = $val(2);
    } elseif ($is(['sex', 'gender', 'লিঙ্গ'])) {
        $r['sex'] = $val(0);
    } elseif ($is(['place of death', 'মৃত্যুর স্থান', 'মৃত্যুস্থান'])) {
        $r['pod_bn'] = $val(0);
        $r['pod_en'] = $val(1);
    } elseif ($is(['place of birth', 'জন্মস্থান'])) {
        if (empty($r['pod_bn'])) $r['pod_bn'] = $val(0);
        if (empty($r['pod_en'])) $r['pod_en'] = $val(1);
    } elseif ($is(['cause of death', 'মৃত্যুর কারণ'])) {
        $r['cause_bn'] = $val(0);
        $r['cause_en'] = $val(1);
    } elseif ($is(['mother', 'মাতার'])) {
        if ($is(['nationality', 'জাতীয়তা'])) {
            $r['mother_nat_bn'] = $val(0);
            $r['mother_nat_en'] = $val(1);
        } else {
            $r['mother_name_bn'] = $val(0);
            $r['mother_name_en'] = $val(1);
        }
    } elseif ($is(['father', 'পিতার'])) {
        if ($is(['nationality', 'জাতীয়তা'])) {
            $r['father_nat_bn'] = $val(0);
            $r['father_nat_en'] = $val(1);
        } else {
            $r['father_name_bn'] = $val(0);
            $r['father_name_en'] = $val(1);
        }
    } elseif ($is([
        'name of dead person', 'name of deceased person', 'name of deceased',
        'deceased person', 'registered person name', 'মৃত ব্যক্তির নাম', 'নিবন্ধিত ব্যক্তির নাম',
    ])) {
        $r['name_bn'] = $val(0);
        $r['name_en'] = $val(1);
    }
}

function parseDeathSearchResult(string $html): ?array
{
    $doc = new DOMDocument();
    @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    $xpath = new DOMXPath($doc);

    $r = [];

    // The live result page states the number, death date and name in a sentence
    // above the table instead of repeating them as labelled table cells.
    $pageText = trim(preg_replace('/\s+/u', ' ', $doc->textContent));
    if (preg_match(
        '/Given Death registration number\s+(\d{17})\s+and Date of Death is\s+(.+?)\s+are belonged to\s+(.+?)\s+with the following particulars/iu',
        $pageText,
        $m
    )) {
        $r['drn'] = trim($m[1]);
        $r['dod'] = trim($m[2]);
        $r['name_en'] = trim($m[3]);
    }

    $tables = $xpath->query('//table');

    // Some BDRIS pages use a 3-column label row followed by a value row.
    foreach ($tables as $table) {
        $rows = [];
        foreach ($xpath->query('.//tr', $table) as $row) {
            $rows[] = deathCellTexts($xpath, $row);
        }
        for ($i = 0, $n = count($rows); $i < $n; $i++) {
            $labels = $rows[$i];
            $values = $rows[$i + 1] ?? [];
            if (count($labels) !== 3 || count($values) !== 3) continue;

            $flat = mb_strtolower(implode(' | ', $labels), 'UTF-8');
            if (!preg_match('/registration date|registration office|issuance date|date of birth|birth registration number|date of death|death registration number|\bsex\b|নিবন্ধনের তারিখ|জন্ম তারিখ|মৃত্যুর তারিখ|মৃত্যু নিবন্ধন/', $flat)) {
                continue;
            }

            if (str_contains($flat, 'registration date')) {
                $r['reg_date'] = $values[0];
                $r['reg_office'] = $values[1];
                $r['issue_date'] = $values[2];
            } elseif (str_contains($flat, 'date of birth')) {
                $r['dob'] = $values[0];
                $r['brn'] = $values[1];
                $r['sex'] = $values[2];
            } elseif (str_contains($flat, 'date of death')) {
                $r['dod'] = $values[0];
                if (preg_match('/^\d{17}$/', preg_replace('/\D/', '', $values[1]))) {
                    $r['drn'] = $values[1];
                }
                if ($values[2] !== '') $r['sex'] = $values[2];
            }
        }
    }

    // Live/person tables: Bengali label, Bengali value, English label, English value.
    foreach ($tables as $table) {
        foreach ($xpath->query('.//tr', $table) as $row) {
            $cellData = [];
            foreach ($xpath->query('.//td', $row) as $cell) {
                $img = $xpath->query('.//img', $cell);
                if ($img->length > 0) {
                    $src = $img->item(0)->getAttribute('src');
                    if (strpos($src, 'base64') !== false && empty($r['photo'])) {
                        $r['photo'] = $src;
                    }
                    continue;
                }
                $cellData[] = trim(preg_replace('/\s+/u', ' ', $cell->textContent));
            }
            if (count($cellData) !== 4) continue;

            deathAssignLabel($r, $cellData[0] . ' | ' . $cellData[2], [$cellData[1], $cellData[3]]);
        }
    }

    $r = array_filter($r, static fn($v): bool => is_array($v) ? $v !== [] : $v !== '');

    return (isset($r['drn']) || isset($r['name_bn']) || isset($r['name_en'])) ? $r : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'init') {
    $result = initSession('death');
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
        CURLOPT_REFERER => BASE_URL . '/UDRNVerification',
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
    $udrn = trim($_POST['udrn'] ?? '');
    $deathDate = trim($_POST['death_date'] ?? '');
    $captchaAnswer = trim($_POST['captcha_answer'] ?? '');
    $errors = [];

    if (!preg_match('/^\d{17}$/', $udrn)) {
        $errors[] = 'Death Registration Number must be exactly 17 digits.';
    }
    if (!preg_match('/^\d{4}-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])$/', $deathDate)) {
        $errors[] = 'Date of Death must be in YYYY-MM-DD format.';
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
    $captchaToken = trim($_POST['captcha_token'] ?? '');

    // Never re-initialise a session and then submit the old captcha token:
    // that guarantees an "invalid captcha" response. Ask the client to refresh.
    if (!$session || $captchaToken === '' || !hash_equals((string) $session['captcha_token'], $captchaToken)) {
        unset($_SESSION['everify']);
        echo json_encode([
            'ok' => false,
            'errors' => ['Captcha expired. Please solve the new captcha and try again.'],
            'captcha_expired' => true,
        ]);
        exit;
    }

    // BDRIS' death form posts the death date under the historical "BirthDate" name.
    $postData = [
        'UBRN' => $udrn,
        'BirthDate' => $deathDate,
        'CaptchaDeText' => $captchaToken,
        'CaptchaInputText' => $captchaAnswer,
        '__RequestVerificationToken' => $session['request_token'],
    ];

    $cookies = $session['cookies'];
    $raw = httpPost(BASE_URL . '/UDRNVerification/Search', $postData, $cookies);

    if (!$raw['ok']) {
        refreshSession('death');
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
            refreshSession('death');
            http_response_code(502);
            echo json_encode(['ok' => false, 'error' => 'Session expired. Please try again.']);
            exit;
        }
    }

    // Parse first: a successful BDRIS page also contains the word
    // "text-danger" in its footer, so it must not be treated as an error.
    $record = parseDeathSearchResult($raw['body']);

    if ($record) {
        refreshSession('death');
        echo json_encode(['ok' => true, 'record' => $record]);
        exit;
    }

    $doc = new DOMDocument();
    @$doc->loadHTML(mb_convert_encoding($raw['body'], 'HTML-ENTITIES', 'UTF-8'));
    $xpath = new DOMXPath($doc);

    $apiErrors = [];
    $captchaInvalid = false;

    $errorNodes = $xpath->query(
        '//*[contains(@class, "validation-summary-errors")]//li'
        . ' | //span[contains(@class, "field-validation-error")]'
        . ' | //p[contains(@class, "text-danger")]'
    );
    foreach ($errorNodes as $node) {
        $text = trim(preg_replace('/\s+/u', ' ', $node->textContent));
        if ($text === '') continue;
        if (preg_match('/captcha/i', $text)) $captchaInvalid = true;
        $apiErrors[] = $text;
    }

    $captchaInput = $xpath->query('//input[@id="CaptchaInputText" or @name="CaptchaInputText"]')->item(0);
    if ($captchaInput instanceof DOMElement && str_contains($captchaInput->getAttribute('class'), 'input-validation-error')) {
        $captchaInvalid = true;
    }

    if ($captchaInvalid) {
        $apiErrors = ['Invalid captcha. Please try again with the new captcha.'];
    } elseif (empty($apiErrors)) {
        $apiErrors = ['No record found, or the verification response could not be read. Please check the UDRN and date.'];
    }

    refreshSession('death');
    echo json_encode([
        'ok' => false,
        'errors' => array_values(array_unique($apiErrors)),
        'captcha_expired' => true,
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
