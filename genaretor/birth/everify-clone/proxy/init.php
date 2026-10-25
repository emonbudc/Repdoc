<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('BASE_URL', 'https://everify.bdris.gov.bd');

function httpGet($url, &$cookieStore = '') {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        CURLOPT_HEADER => true,
        CURLOPT_TIMEOUT => 15,
    ]);
    if ($cookieStore) {
        curl_setopt($ch, CURLOPT_COOKIE, $cookieStore);
    }
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $error = curl_error($ch);
    unset($ch);

    if ($error) {
        return ['ok' => false, 'error' => $error];
    }

    $headerStr = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);

    preg_match_all('/^Set-Cookie:\s*([^;]+)/mi', $headerStr, $m);
    if (!empty($m[1])) {
        $cookieStore = implode('; ', $m[1]);
    }

    return ['ok' => true, 'body' => $body, 'code' => $httpCode, 'cookies' => $cookieStore];
}

function httpPost($url, $data, &$cookieStore = '') {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded',
        ],
    ]);
    if ($cookieStore) {
        curl_setopt($ch, CURLOPT_COOKIE, $cookieStore);
    }
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    unset($ch);

    if ($error) {
        return ['ok' => false, 'error' => $error];
    }

    return ['ok' => true, 'body' => $response, 'code' => $httpCode];
}

function extractHiddenValue($html, $name) {
    $patterns = [
        '/<input[^>]*name=["\']' . preg_quote($name, '/') . '["\'][^>]*value=["\']([^"\']*)["\']/i',
        '/<input[^>]*value=["\']([^"\']*)["\'][^>]*name=["\']' . preg_quote($name, '/') . '["\']/i',
    ];
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $html, $m)) {
            return $m[1];
        }
    }
    return null;
}

function initSession($type = 'birth') {
    $url = $type === 'death' ? BASE_URL . '/UDRNVerification' : BASE_URL . '/';
    $result = httpGet($url);
    if (!$result['ok']) {
        return $result;
    }

    $token = extractHiddenValue($result['body'], '__RequestVerificationToken');
    $captchaToken = extractHiddenValue($result['body'], 'CaptchaDeText');

    $_SESSION['everify'] = [
        'request_token' => $token,
        'captcha_token' => $captchaToken,
        'cookies' => $result['cookies'],
        'last_fetch' => time(),
        'page_html' => $result['body'],
    ];

    return ['ok' => true];
}

function refreshSession($type = 'birth') {
    unset($_SESSION['everify']);
    return initSession($type);
}

function getSession() {
    if (empty($_SESSION['everify'])) {
        return null;
    }
    if (time() - $_SESSION['everify']['last_fetch'] > 120) {
        return null;
    }
    return $_SESSION['everify'];
}

function parseRecordTable($html) {
    $doc = new DOMDocument();
    @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    $xpath = new DOMXPath($doc);

    $tables = $xpath->query('//table[contains(@class, "table-hover")]');
    $data = [];

    foreach ($tables as $ti => $table) {
        $rows = $xpath->query('.//tr', $table);
        $data[$ti] = [];
        foreach ($rows as $row) {
            $cells = $xpath->query('.//td', $row);
            $rowData = [];
            foreach ($cells as $cell) {
                $img = $xpath->query('.//img', $cell);
                if ($img->length > 0) {
                    $src = $img->item(0)->getAttribute('src');
                    if (strpos($src, 'base64') !== false) {
                        $rowData[] = ['type' => 'photo', 'src' => $src];
                        continue;
                    }
                }
                $rowData[] = ['type' => 'text', 'value' => trim($cell->textContent)];
            }
            $data[$ti][] = $rowData;
        }
    }

    return $data;
}

function extractPersonData($parsed) {
    $result = ['error' => null, 'record' => null];

    if (empty($parsed) || empty($parsed[0]) || count($parsed[0]) < 2) {
        $result['error'] = 'No record found. Please verify the information and try again.';
        return $result;
    }

    $r = [];

    $firstTable = $parsed[0] ?? [];
    foreach ($firstTable as $row) {
        $vals = array_map(function($c) { return $c['value'] ?? ''; }, $row);
        $text = implode(' | ', $vals);

        if (stripos($text, 'Registration Date') !== false && count($vals) >= 3) {
            $r['reg_date'] = $vals[0];
            $r['reg_office'] = $vals[1] ?? '';
            $r['issuance_date'] = $vals[2] ?? '';
        }
        if (stripos($text, 'Date of Birth') !== false && count($vals) >= 3) {
            $r['dob'] = $vals[0];
            $r['brn'] = $vals[1] ?? '';
            $r['sex'] = $vals[2] ?? '';
        }
    }

    if (isset($parsed[1])) {
        $secondTable = $parsed[1];
        foreach ($secondTable as $row) {
            $cells = $row;
            if (count($cells) < 4) continue;

            $labelBn = isset($cells[0]) ? strip_tags($cells[0]['value'] ?? '') : '';
            $valueBn = isset($cells[1]) ? strip_tags($cells[1]['value'] ?? '') : '';
            $labelEn = isset($cells[2]) ? strip_tags($cells[2]['value'] ?? '') : '';
            $valueEn = isset($cells[3]) ? strip_tags($cells[3]['value'] ?? '') : '';

            $text = $labelBn . $labelEn;

            if (strpos($text, 'নিবন্ধিত ব্যক্তির নাম') !== false || strpos($text, 'Registered Person Name') !== false) {
                $r['name_bn'] = $valueBn;
                $r['name_en'] = $valueEn;
            } elseif (strpos($text, 'জন্মস্থান') !== false || strpos($text, 'Place of Birth') !== false) {
                $r['place_of_birth_bn'] = $valueBn;
                $r['place_of_birth_en'] = $valueEn;
            } elseif (strpos($text, 'মাতার নাম') !== false || strpos($text, "Mother's Name") !== false) {
                $r['mother_name_bn'] = $valueBn;
                $r['mother_name_en'] = $valueEn;
            } elseif (strpos($text, 'মাতার জাতীয়তা') !== false || strpos($text, "Mother's Nationality") !== false) {
                $r['mother_nationality_bn'] = $valueBn;
                $r['mother_nationality_en'] = $valueEn;
            } elseif (strpos($text, 'পিতার নাম') !== false || strpos($text, "Father's Name") !== false) {
                $r['father_name_bn'] = $valueBn;
                $r['father_name_en'] = $valueEn;
            } elseif (strpos($text, 'পিতার জাতীয়তা') !== false || strpos($text, "Father's Nationality") !== false) {
                $r['father_nationality_bn'] = $valueBn;
                $r['father_nationality_en'] = $valueEn;
            }

            if (count($cells) >= 5 && !empty($cells[4]) && $cells[4]['type'] === 'photo') {
                $r['photo'] = $cells[4]['src'];
            }
        }

        foreach ($secondTable as $row) {
            foreach ($row as $cell) {
                if ($cell['type'] === 'photo' && empty($r['photo'])) {
                    $r['photo'] = $cell['src'];
                }
            }
        }
    }

    if (empty($r)) {
        $result['error'] = 'Could not parse record data. The site structure may have changed.';
        return $result;
    }

    $result['record'] = $r;
    return $result;
}
