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

    if ($is(['death registration number', 'মৃত্যু নিবন্ধন নম্বর', 'মৃত্যু নিবন্ধন নং'])) {
        $r['drn'] = $vals[0] ?? ($r['drn'] ?? '');
    } elseif ($is(['registration date', 'নিবন্ধনের তারিখ'])) {
        $r['reg_date'] = $vals[0] ?? '';
        $r['reg_office'] = $vals[1] ?? ($r['reg_office'] ?? '');
        $r['issue_date'] = $vals[2] ?? ($r['issue_date'] ?? '');
    } elseif ($is(['date of issuance', 'ইস্যু'])) {
        $r['issue_date'] = $vals[0] ?? '';
    } elseif ($is(['date of death', 'death date', 'মৃত্যুর তারিখ'])) {
        $r['dod'] = $vals[0] ?? '';
        if (!empty($vals[1]) && preg_match('/^\d{17}$/', preg_replace('/\D/', '', $vals[1]))) {
            $r['drn'] = $vals[1];
        }
        if (!empty($vals[2])) $r['sex'] = $vals[2];
    } elseif ($is(['date of birth', 'জন্ম তারিখ'])) {
        $r['dob'] = $vals[0] ?? '';
        if (!empty($vals[1]) && preg_match('/^\d{17}$/', preg_replace('/\D/', '', $vals[1]))) {
            $r['drn'] = $vals[1];
        }
        if (!empty($vals[2])) $r['sex'] = $vals[2];
    } elseif ($is(['sex', 'gender', 'লিঙ্গ'])) {
        $r['sex'] = $vals[0] ?? '';
    } elseif ($is(['place of death', 'মৃত্যুর স্থান', 'মৃত্যুস্থান'])) {
        $r['pod_bn'] = $vals[0] ?? '';
        $r['pod_en'] = $vals[1] ?? ($r['pod_en'] ?? '');
    } elseif ($is(['place of birth', 'জন্মস্থান'])) {
        if (empty($r['pod_bn'])) $r['pod_bn'] = $vals[0] ?? '';
        if (empty($r['pod_en'])) $r['pod_en'] = $vals[1] ?? '';
    } elseif ($is(['cause of death', 'মৃত্যুর কারণ'])) {
        $r['cause_bn'] = $vals[0] ?? '';
        $r['cause_en'] = $vals[1] ?? ($r['cause_en'] ?? '');
    } elseif ($is(['mother', 'মাতার'])) {
        if ($is(['nationality', 'জাতীয়তা'])) {
            $r['mother_nat_bn'] = $vals[0] ?? '';
            $r['mother_nat_en'] = $vals[1] ?? ($r['mother_nat_en'] ?? '');
        } else {
            $r['mother_name_bn'] = $vals[0] ?? '';
            $r['mother_name_en'] = $vals[1] ?? ($r['mother_name_en'] ?? '');
        }
    } elseif ($is(['father', 'পিতার'])) {
        if ($is(['nationality', 'জাতীয়তা'])) {
            $r['father_nat_bn'] = $vals[0] ?? '';
            $r['father_nat_en'] = $vals[1] ?? ($r['father_nat_en'] ?? '');
        } else {
            $r['father_name_bn'] = $vals[0] ?? '';
            $r['father_name_en'] = $vals[1] ?? ($r['father_name_en'] ?? '');
        }
    } elseif ($is(['deceased', 'registered person name', 'মৃত ব্যক্তির নাম', 'নিবন্ধিত ব্যক্তির নাম'])) {
        $r['name_bn'] = $vals[0] ?? '';
        $r['name_en'] = $vals[1] ?? ($r['name_en'] ?? '');
    }
}

function parseDeathSearchResult(string $html): ?array
{
    $doc = new DOMDocument();
    @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    $xpath = new DOMXPath($doc);

    $tables = $xpath->query('//table[contains(@class, "table-hover")]');
    if ($tables->length === 0) return null;

    $r = [];

    // ─── Registration block: 3-column label row / value row pairs ───
    $labelPattern = '/registration date|registration office|issuance date|date of birth|birth registration number|date of death|death registration number|\bsex\b/i';
    $tableRows = [];
    foreach ($xpath->query('.//tr', $tables->item(0)) as $row) {
        $tableRows[] = deathCellTexts($xpath, $row);
    }

    for ($i = 0; $i < count($tableRows); $i++) {
        $vals = $tableRows[$i];
        if (!$vals || !preg_match($labelPattern, implode(' | ', $vals))) continue;

        $next = $tableRows[$i + 1] ?? [];
        foreach ($vals as $j => $label) {
            $l = mb_strtolower($label, 'UTF-8');
            $value = $next[$j] ?? '';
            if ($value === '' && isset($vals[$j + 1]) && $vals[$j + 1] !== '') {
                $value = $vals[$j + 1];
            }

            if (str_contains($l, 'birth registration number')) {
                $r['brn'] = $value;
            } elseif (str_contains($l, 'death registration number') || str_contains($l, 'মৃত্যু নিবন্ধন নম্বর')) {
                $r['drn'] = $value;
            } elseif (str_contains($l, 'registration date') || str_contains($l, 'নিবন্ধনের তারিখ')) {
                $r['reg_date'] = $value;
            } elseif (str_contains($l, 'registration office')) {
                $r['reg_office'] = $value;
            } elseif (str_contains($l, 'issuance date') || str_contains($l, 'ইস্যু')) {
                $r['issue_date'] = $value;
            } elseif (str_contains($l, 'date of birth') || str_contains($l, 'জন্ম তারিখ')) {
                $r['dob'] = $value;
            } elseif (str_contains($l, 'date of death') || str_contains($l, 'মৃত্যুর তারিখ')) {
                $r['dod'] = $value;
            } elseif (preg_match('/\bsex\b|লিঙ্গ/', $l)) {
                $r['sex'] = $value;
            }
        }
    }

    // ─── Person details (label_bn, value_bn, label_en, value_en) ───
    for ($t = 1; $t < $tables->length; $t++) {
        $rows = $xpath->query('.//tr', $tables->item($t));
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
                $cellData[] = trim(preg_replace('/\s+/u', ' ', $cell->textContent));
            }
            if (count($cellData) < 4) continue;

            $labelBn = $cellData[0] ?? '';
            $valueBn = $cellData[1] ?? '';
            $labelEn = $cellData[2] ?? '';
            $valueEn = $cellData[3] ?? '';
            $combined = $labelBn . ' | ' . $labelEn;

            // Fall back to Bengali/English values separately where labels only carry one language.
            $pair = [$valueBn, $valueEn];
            deathAssignLabel($r, $combined, $pair);

            $bh = mb_strpos($combined, 'মাতার', 0, 'UTF-8');
            $fh = mb_strpos($combined, 'পিতার', 0, 'UTF-8');
            if ($bh !== false && mb_strpos($combined, 'জাতীয়তা', 0, 'UTF-8') !== false) {
                $r['mother_nat_bn'] = $valueBn;
                $r['mother_nat_en'] = $valueEn;
            } elseif ($bh !== false) {
                $r['mother_name_bn'] = $valueBn;
                $r['mother_name_en'] = $valueEn;
            }
            if ($fh !== false && mb_strpos($combined, 'জাতীয়তা', 0, 'UTF-8') !== false) {
                $r['father_nat_bn'] = $valueBn;
                $r['father_nat_en'] = $valueEn;
            } elseif ($fh !== false) {
                $r['father_name_bn'] = $valueBn;
                $r['father_name_en'] = $valueEn;
            }
            if (stripos($combined, 'Cause of Death') !== false || mb_strpos($combined, 'মৃত্যুর কারণ', 0, 'UTF-8') !== false) {
                $r['cause_bn'] = $valueBn;
                $r['cause_en'] = $valueEn;
            }
            if (stripos($combined, 'Place of Death') !== false || mb_strpos($combined, 'মৃত্যুর স্থান', 0, 'UTF-8') !== false) {
                $r['pod_bn'] = $valueBn;
                $r['pod_en'] = $valueEn;
            }
            if (stripos($combined, 'Date of Death') !== false || mb_strpos($combined, 'মৃত্যুর তারিখ', 0, 'UTF-8') !== false) {
                $r['dod'] = $valueEn ?: $valueBn;
            }
            if (stripos($combined, 'Date of Birth') !== false || mb_strpos($combined, 'জন্ম তারিখ', 0, 'UTF-8') !== false) {
                $r['dob'] = $valueEn ?: $valueBn;
            }
        }
    }

    return empty($r) ? null : $r;
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
    if (!$session) {
        $result = initSession('death');
        if (!$result['ok']) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'Could not connect to the verification service.']);
            exit;
        }
        $session = $_SESSION['everify'];
    }

    $captchaToken = trim($_POST['captcha_token'] ?? '');
    if (!$captchaToken) {
        $captchaToken = $session['captcha_token'];
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

        refreshSession('death');
        echo json_encode(['ok' => false, 'errors' => $apiErrors, 'captcha_expired' => true]);
        exit;
    }

    $record = parseDeathSearchResult($raw['body']);

    if (!$record) {
        refreshSession('death');
        echo json_encode(['ok' => false, 'errors' => ['Could not parse record data. The site structure may have changed.'], 'captcha_expired' => true]);
        exit;
    }

    refreshSession('death');

    echo json_encode(['ok' => true, 'record' => $record]);
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
