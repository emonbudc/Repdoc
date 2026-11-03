<?php
declare(strict_types=1);

/**
 * Land record search — API client.
 *
 * Search-only port of github.com/emonbaluahat/land (khatiyan.php). The
 * upstream tool also ships ads, an ad-manager, an ad-block guard, print
 * templates, and a separate homepage; none of that is included here.
 *
 * Data source: https://api.ldtax.gov.bd/api/v1 (public location endpoints).
 * A logged-in portal session is needed for holding searches. Credentials are
 * read from the environment only — never hardcoded and never printed:
 *   LAND_API_BASE         optional, defaults to the public API root
 *   LAND_SESSION_COOKIE   optional session cookie for the land portal
 *   LAND_JWT_SECRET       optional, enables per-holding detail lookups
 *   LAND_MAX_RESULTS      optional, defaults to 25
 */

if (!defined('REPDOC_ROOT')) {
    define('REPDOC_ROOT', dirname(__DIR__, 2));
}
require_once REPDOC_ROOT . '/src/bootstrap.php';

if (!defined('LAND_API_BASE')) {
    define('LAND_API_BASE', rtrim(repdoc_env('LAND_API_BASE', 'https://api.ldtax.gov.bd/api/v1'), '/'));
}

/** Upstream defaults — the portal search itself needs no API key. */
if (!defined('LAND_DEFAULT_COOKIE')) {
    define('LAND_DEFAULT_COOKIE', 'citizen=%7B%22id%22%3A17347478%2C%22username%22%3A%2201336710020%22%2C%22name%22%3A%22%E0%A6%AE%E0%A7%8B%E0%A6%83%20%E0%A6%86%E0%A6%AC%E0%A7%8D%E0%A6%A6%E0%A7%81%E0%A6%B2%E0%A7%8D%E0%A6%B2%E0%A6%BE%E0%A6%B9%20%E0%A6%86%E0%A6%B2%20%E0%A6%87%E0%A6%AE%E0%A6%A8%22%2C%22name_en%22%3A%22MD.%20ABDULAH%20AL%20EMON%22%2C%22photo%22%3Anull%2C%22phone%22%3A%2201336710020%22%2C%22email%22%3Anull%2C%22father_name%22%3A%22%E0%A6%AE%E0%A7%8B%E0%A6%83%20%E0%A6%86%E0%A6%AC%E0%A7%8D%E0%A6%A6%E0%A7%81%E0%A6%B2%20%E0%A6%9C%E0%A6%B2%E0%A6%BF%E0%A6%B2%22%2C%22father_name_en%22%3Anull%2C%22mother_name_en%22%3Anull%2C%22spouse_name%22%3Anull%2C%22religion%22%3A%22Islam%22%2C%22occupation%22%3Anull%2C%22nationality%22%3Anull%2C%22mother_name%22%3A%22%E0%A6%AE%E0%A7%8B%E0%A6%9B%E0%A6%BE%E0%A6%83%20%E0%A6%B0%E0%A6%BE%E0%A6%9C%E0%A6%BF%E0%A7%9F%E0%A6%BE%20%E0%A6%B8%E0%A7%81%E0%A6%B2%E0%A6%A4%E0%A6%BE%E0%A6%A8%E0%A6%BE%22%2C%22nid%22%3A%227378882182%22%2C%22dob%22%3A%222004-01-18%22%2C%22address%22%3Anull%2C%22present_address%22%3Anull%2C%22gender%22%3A%22%E0%A6%AA%E0%A7%81%E0%A6%B0%E0%A7%81%E0%A6%B7%22%2C%22progressPoint%22%3A40%7D; sso=%22eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiI5YWE0NjczYi02OTIzLTQ4NTUtOWZjOS1mZjU2YjA0ZGJkZjMiLCJqdGkiOiJmMDE3M2I4MDM4YmJmMmZkM2Q3MzgzMWZhNDM2YWQ5MzJhY2UzNjJkOTY2NDI4MjczNjNiZTM4MThlNjgyZmU5OGQwZmFjNWJhYWY0YTQ5YyIsImlhdCI6MTc4NTg5MjI1Ny41OTQ0NDgsIm5iZiI6MTc4NTg5MjI1Ny41OTQ0NTIsImV4cCI6MTc4NTk3ODY1Ny41NzEzNjEsInN1YiI6IjE3NTY1ODgyIiwic2NvcGVzIjpbInZpZXctdXNlciJdfQ.NM4lgLBOnFbqPXhL3jfqDicY7C3AUWXkxj75ee8j7-24Bg-vvcSf4K-aigmO2NTOTGVXyH1R-8lil4oHER07KWWQZP0ZDZR7aDhKFTyHrxHKrfczTXALJ0Xi31EBqvWWjROSPs02ykheFRhlPWlVvSQwGF5oqKZQISDML6CKKsPQp7UoTdkbxij2kHuplVM9cWu4fMdUt9NWou1TdOoSdWf5BFyGQMi76NsQ_xB6W14mHl0U1BlqdkM0ZrWIkAYawAyHfANeirRNv53JT0Ef86CU42MZYwivuBFVHNj69MB8vNGCs8KQTVKiQue58F6RgyW5fb60SymMOT-35yz01jBRLlGI-QdscvGKheMe0UfrCbF42JmCPzhpYJmccF_vPspe4EzlgMq2PiRr4Ns3RShFUUL0RDC9ynZ6aL3VX4AuDT5sy-P7U98DiSZy-73c4OSaQQgpLdjwJ_Sg_FA0afC-WaT0S724twySjYxal28KvC1BW0kL3ZrcGjVsPQjEjfixQoJbnyYz4vR57HyEmrNTaTb0SJKeDshNJr-jV3fYjwRaK0H2GznrL81cEHS3CRwOfxIHwSBOyYbWY4JPSel9N9utGx-pduR9kmc0G84SVPL0c2OQFEmqRRGfaqzWsDij4kVqLbyQBq2v3MRVBnMfYxkDSD2Go711XrtPuPs%22');
}
if (!defined('LAND_DEFAULT_JWT_SECRET')) {
    define('LAND_DEFAULT_JWT_SECRET', 'LDTaxLimsLRB2020');
}
if (!defined('LAND_SESSION_COOKIE')) {
    $landCookie = trim(repdoc_env('LAND_SESSION_COOKIE'));
    define('LAND_SESSION_COOKIE', $landCookie !== '' ? $landCookie : LAND_DEFAULT_COOKIE);
}
if (!defined('LAND_JWT_SECRET')) {
    $landSecret = trim(repdoc_env('LAND_JWT_SECRET'));
    define('LAND_JWT_SECRET', $landSecret !== '' ? $landSecret : LAND_DEFAULT_JWT_SECRET);
}
if (!defined('LAND_MAX_RESULTS')) {
    define('LAND_MAX_RESULTS', max(1, min(50, (int) (repdoc_env('LAND_MAX_RESULTS', '25') ?: 25))));
}

/** Bengali digits → ASCII digits (the API expects English numerals). */
function land_bn_to_en(string $value): string
{
    return strtr($value, [
        '০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4',
        '৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9',
    ]);
}

/**
 * Minimal HTTP client for the land API.
 *
 * @return array{code:int,body:string,error:string}
 */
function land_http(string $url, string $method = 'GET', ?array $body = null, string $cookie = ''): array
{
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json; charset=utf-8',
        'Cache-Control: no-cache',
    ];

    // Never allow CR/LF injection through the configured cookie.
    $cookie = str_replace(["\r", "\n"], '', $cookie);
    if ($cookie !== '') {
        $headers[] = 'Cookie: ' . $cookie;
        if (preg_match('/XSRF-TOKEN=([^;]+)/', $cookie, $m)) {
            $headers[] = 'X-XSRF-TOKEN: ' . urldecode($m[1]);
        }
    }

    $payload = null;
    if ($method === 'POST') {
        $encoded = json_encode($body ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $payload = $encoded === false ? '{}' : $encoded;
    }

    // Some shared hosts ship PHP without cURL; fall back to the HTTP stream
    // wrapper so the search still works with allow_url_fopen enabled.
    if (!function_exists('curl_init')) {
        $options = [
            'http' => [
                'method'        => $method,
                'header'        => implode("\r\n", $headers),
                'timeout'       => 20,
                'ignore_errors' => true,
                'follow_location' => 0,
                'max_redirects' => 0,
            ],
        ];
        if ($payload !== null) {
            $options['http']['content'] = $payload;
        }

        $response = @file_get_contents($url, false, stream_context_create($options));
        $code = 0;
        foreach (($http_response_header ?? []) as $line) {
            if (preg_match('/^HTTP\/\S+\s+(\d{3})\b/', $line, $m)) {
                $code = (int) $m[1];
                break;
            }
        }

        return [
            'code'  => $code,
            'body'  => is_string($response) ? $response : '',
            'error' => $response === false ? 'HTTP request failed.' : '',
        ];
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/120 Safari/537.36',
    ]);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload ?? '{}');
    }

    $response = curl_exec($ch);
    $error    = (string) curl_error($ch);
    $code     = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $code, 'body' => is_string($response) ? $response : '', 'error' => $error];
}

/** Decode an API response without ever throwing on malformed JSON. */
function land_api_decode(array $response): array
{
    $decoded = json_decode($response['body'], true);
    if (!is_array($decoded)) {
        return ['error' => $response['error'], 'http' => $response['code']];
    }
    $decoded['http']  = $response['code'];
    $decoded['error'] = $response['error'];
    return $decoded;
}

function land_get_divisions(): array
{
    $json = land_api_decode(land_http(LAND_API_BASE . '/getDivisions'));
    return is_array($json['data'] ?? null) ? $json['data'] : [];
}

function land_get_districts(int $divisionId): array
{
    if ($divisionId <= 0) return [];
    $json = land_api_decode(land_http(LAND_API_BASE . '/getDistricts/' . $divisionId));
    return is_array($json['data'] ?? null) ? $json['data'] : [];
}

function land_get_upazilas(int $districtId): array
{
    if ($districtId <= 0) return [];
    $json = land_api_decode(land_http(LAND_API_BASE . '/getUpazilas/' . $districtId));
    return is_array($json['data'] ?? null) ? $json['data'] : [];
}

function land_get_moujas(int $upazilaId): array
{
    if ($upazilaId <= 0) return [];
    $json = land_api_decode(land_http(LAND_API_BASE . '/getMoujasByUpazila/' . $upazilaId));
    return is_array($json['data'] ?? null) ? $json['data'] : [];
}

/**
 * Search holdings for one khôtian.
 *
 * @param array{division_id?:int,district_id?:int,upazila_id?:int,mouja_id?:int,khotian_no?:string,holding_no?:string} $filters
 * @return array{ok:bool,error?:string,results?:array,detail_enabled?:bool}
 */
function land_search(array $filters): array
{
    $divisionId = (int) ($filters['division_id'] ?? 0);
    $districtId = (int) ($filters['district_id'] ?? 0);
    $upazilaId  = (int) ($filters['upazila_id'] ?? 0);
    $moujaId    = (int) ($filters['mouja_id'] ?? 0);
    $khotianNo  = trim(land_bn_to_en((string) ($filters['khotian_no'] ?? '')));
    $holdingNo  = trim(land_bn_to_en((string) ($filters['holding_no'] ?? '')));

    if ($moujaId <= 0 || $khotianNo === '') {
        return ['ok' => false, 'error' => 'মৌজা এবং খতিয়ান নং দুটোই আবশ্যক।'];
    }

    $params = [
        'division_id'  => $divisionId,
        'district_id'  => $districtId,
        'upazila_id'   => $upazilaId,
        'mouja_id'     => $moujaId,
        'khotian_no'   => $khotianNo,
        'holding_no'   => $holdingNo,
        'holding_type' => 0,
    ];

    $response = land_api_decode(land_search_holdings($params));

    if (($response['status'] ?? null) === false) {
        $message = trim((string) ($response['msg'] ?? ''));

        // The representative endpoint only answers "not found"; the public
        // location endpoint carries the portal's real message (and can still
        // return holdings when the representative route is unavailable).
        if ($message === 'not found' || $message === '') {
            $public = land_api_decode(land_search_public($params));
            if (($public['status'] ?? null) === true && is_array($public['data'] ?? null) && $public['data'] !== []) {
                $response = $public;
                $message = '';
            } else {
                $publicMessage = trim((string) ($public['msg'] ?? ''));
                if ($publicMessage !== '' && $publicMessage !== 'not found') {
                    $message = $publicMessage;
                } else {
                    $message = 'এই মৌজায় এই খতিয়ান নং-এর কোনো হোল্ডিং পাওয়া যায়নি।';
                }
            }
        }

        if (($response['status'] ?? null) !== true) {
            return [
                'ok'    => false,
                'error' => $message !== '' ? $message : 'অনুসন্ধানে সমস্যা হয়েছে।',
            ];
        }
    }

    $data = is_array($response['data'] ?? null) ? $response['data'] : [];
    if ($data === []) {
        return [
            'ok'    => false,
            'error' => 'এই মৌজায় এই খতিয়ান নং-এর কোনো হোল্ডিং পাওয়া যায়নি।',
        ];
    }

    $rows = [];
    foreach (array_slice($data, 0, LAND_MAX_RESULTS) as $holding) {
        if (!is_array($holding)) continue;

        $mouja = is_array($holding['moujas'] ?? null) ? $holding['moujas'] : [];

        $rows[] = [
            'id'         => (int) ($holding['id'] ?? 0),
            'holding_no' => (string) ($holding['holding_no'] ?? ''),
            'khotian_no' => (string) ($holding['khotian_no'] ?? $khotianNo),
            'mouja'      => (string) ($mouja['name_bd'] ?? ''),
            'jl_no'      => (string) ($mouja['jl_no'] ?? ''),
        ];
    }

    return [
        'ok'             => true,
        'results'        => $rows,
        'detail_enabled' => true,
        'total'          => count($data),
    ];
}

/** Raw holding-list API call, kept separate so it can be unit-tested. */
function land_search_holdings(array $params): array
{
    return land_http(LAND_API_BASE . '/holding-list-for-representative-payment', 'POST', $params, LAND_SESSION_COOKIE);
}

/** Public holding list by location — no session cookie required. */
function land_search_public(array $params): array
{
    return land_http(LAND_API_BASE . '/holding-list-by-location', 'POST', $params);
}

function land_b64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/** Mint the short-lived token the upstream holding-detail endpoint expects. */
function land_make_jwt(int $holdingId): string
{
    if (LAND_JWT_SECRET === '') return '';

    $header  = land_b64url_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $iat     = time();
    $payload = land_b64url_encode(json_encode(['req' => $holdingId, 'iat' => $iat, 'exp' => $iat + 3600]));
    $signature = land_b64url_encode(hash_hmac('sha256', $header . '.' . $payload, LAND_JWT_SECRET, true));

    return $header . '.' . $payload . '.' . $signature;
}

/**
 * Fetch owner and land rows for one holding.
 *
 * @return array{ok:bool,error?:string,owners?:array,land?:array,total_land?:float,khotian_no?:string}
 */
function land_detail(int $holdingId): array
{
    if ($holdingId <= 0) {
        return ['ok' => false, 'error' => 'সঠিক হোল্ডিং আইডি পাওয়া যায়নি।'];
    }
    if (LAND_JWT_SECRET === '') {
        return ['ok' => false, 'error' => 'বিস্তারিত তথ্য এই ইনস্টলেশনে কনফিগার করা নেই।'];
    }

    $token = land_make_jwt($holdingId);
    $json  = [];
    foreach ([0, 1] as $ownerType) {
        $json = land_api_decode(land_http(
            LAND_API_BASE . '/holding',
            'POST',
            ['id' => $token, 'owner_type' => $ownerType],
            LAND_SESSION_COOKIE
        ));
        if (!empty($json['holding']) && ($json['http'] ?? 0) === 200) {
            break;
        }
        if (in_array((int) ($json['http'] ?? 0), [502, 503, 504, 0], true)) {
            break;
        }
    }

    if (empty($json['holding']) || !is_array($json['holding'])) {
        return ['ok' => false, 'error' => 'এই হোল্ডিং-এর বিস্তারিত তথ্য পাওয়া যায়নি।'];
    }

    $owners = [];
    $ownersRaw = is_array($json['land_tax_of_owners'] ?? null) ? $json['land_tax_of_owners'] : [];
    foreach ($ownersRaw as $owner) {
        if (!is_array($owner)) continue;
        $owners[] = [
            'name'         => (string) ($owner['name'] ?? ''),
            'father_name'  => (string) ($owner['father_name'] ?? ''),
            'land_portion' => (string) ($owner['land_portion'] ?? ''),
        ];
    }

    $land = [];
    $landRaw = is_array($json['general_info'] ?? null) ? $json['general_info'] : [];
    foreach ($landRaw as $row) {
        if (!is_array($row)) continue;
        $land[] = [
            'dag_no'         => (string) ($row['dag_no'] ?? ''),
            'land_type_name' => (string) ($row['land_type_name'] ?? ''),
            'amount_of_land' => (string) ($row['amount_of_land'] ?? ''),
        ];
    }

    $total = round(array_sum(array_map(
        static fn(array $row): float => (float) ($row['amount_of_land'] ?? 0),
        $land
    )), 2);

    return [
        'ok'          => true,
        'owners'      => $owners,
        'land'        => $land,
        'total_land'  => $total,
        'khotian_no'  => (string) ($json['holding']['khotian_no'] ?? ''),
    ];
}
