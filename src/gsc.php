<?php
declare(strict_types=1);

/**
 * Repdoc — Google Search Console client.
 *
 * Auth: one OAuth refresh token stored in love/gsc-oauth.json (0600, web-denied).
 * The token is exchanged for a short-lived access token through the Google
 * OAuth Playground endpoint, so no client secret ships with the site.
 *
 * Responses are cached in love/gsc-cache.json; the admin page is cache-first
 * and only calls Google when the operator presses a refresh/inspect button.
 */

require_once __DIR__ . '/seo.php';

if (!defined('REPDOC_GSC_TOKEN_FILE')) {
    $gscDir = defined('REPDOC_LOVE') ? REPDOC_LOVE : dirname(__DIR__) . DIRECTORY_SEPARATOR . 'love';
    define('REPDOC_GSC_TOKEN_FILE', $gscDir . DIRECTORY_SEPARATOR . 'gsc-oauth.json');
    define('REPDOC_GSC_CACHE_FILE', $gscDir . DIRECTORY_SEPARATOR . 'gsc-cache.json');
}

if (!function_exists('repdoc_gsc_site')) {
    /** Verified GSC property: canonical origin with a trailing slash. */
    function repdoc_gsc_site(): string
    {
        $base = function_exists('repdoc_canonical') ? repdoc_canonical() : 'https://repdoc.rf.gd/';
        return rtrim($base, '/') . '/';
    }
}

if (!function_exists('repdoc_gsc_http')) {
    /**
     * @param array<int,string> $headers
     * @return array{ok:bool,status:int,body:string,error:string}
     */
    function repdoc_gsc_http(string $method, string $url, array $headers = [], ?string $body = null, int $timeout = 20): array
    {
        $method = strtoupper($method);
        $headers[] = 'User-Agent: RepdocSEO/1.0 (+' . rtrim(repdoc_gsc_site(), '/') . ')';

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_CUSTOMREQUEST  => $method,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);
            if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            $raw    = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $error  = curl_error($ch);
            return [
                'ok'     => $raw !== false && $status >= 200 && $status < 300,
                'status' => $status,
                'body'   => is_string($raw) ? $raw : '',
                'error'  => $raw === false ? ($error !== '' ? $error : 'network error') : '',
            ];
        }

        $ctx = stream_context_create([
            'http' => [
                'method'        => $method,
                'header'        => implode("\r\n", $headers),
                'content'       => $body ?? '',
                'timeout'       => $timeout,
                'ignore_errors' => true,
            ],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);
        $raw = @file_get_contents($url, false, $ctx);
        $status = $raw === false ? 0 : 200;
        return [
            'ok'     => $raw !== false && $status >= 200 && $status < 300,
            'status' => $status,
            'body'   => is_string($raw) ? $raw : '',
            'error'  => $raw === false ? 'network error' : '',
        ];
    }
}

if (!function_exists('repdoc_gsc_decode')) {
    /** @return array{ok:bool,status:int,data:?array,error:string,raw:string} */
    function repdoc_gsc_decode(array $res): array
    {
        $json = $res['body'] !== '' ? json_decode($res['body'], true) : null;
        $error = '';
        if ($res['status'] === 0) {
            $error = $res['error'] !== '' ? $res['error'] : 'request failed';
        } elseif (!$res['ok']) {
            $error = 'HTTP ' . $res['status'];
            if (is_array($json)) {
                if (isset($json['error']) && is_array($json['error'])) {
                    $error = (string) ($json['error']['message'] ?? $error);
                } elseif (isset($json['error']) && is_string($json['error'])) {
                    $error = $json['error'];
                } elseif (isset($json['error_description']) && is_string($json['error_description'])) {
                    $error = $json['error_description'];
                }
            }
        }
        return [
            'ok'     => $res['ok'] && is_array($json),
            'status' => $res['status'],
            'data'   => is_array($json) ? $json : null,
            'error'  => $error,
            'raw'    => $res['body'],
        ];
    }
}

if (!function_exists('repdoc_gsc_token_load')) {
    function repdoc_gsc_token_load(): ?string
    {
        $file = REPDOC_GSC_TOKEN_FILE;
        if (!is_file($file)) return null;
        $raw = @file_get_contents($file);
        $json = is_string($raw) ? json_decode($raw, true) : null;
        $token = is_array($json) ? trim((string) ($json['refresh_token'] ?? '')) : '';
        return $token !== '' ? $token : null;
    }
}

if (!function_exists('repdoc_gsc_token_save')) {
    function repdoc_gsc_token_save(string $refreshToken): array
    {
        $refreshToken = trim($refreshToken);
        if ($refreshToken === '' || strlen($refreshToken) < 20) {
            return ['ok' => false, 'error' => 'That does not look like a refresh token.'];
        }
        $dir = dirname(REPDOC_GSC_TOKEN_FILE);
        if (!is_dir($dir) || !is_writable($dir)) {
            return ['ok' => false, 'error' => 'love/ is not writable.'];
        }
        $payload = json_encode([
            'refresh_token' => $refreshToken,
            'connected_at'  => time(),
            'property'      => repdoc_gsc_site(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $tmp = REPDOC_GSC_TOKEN_FILE . '.tmp.' . bin2hex(random_bytes(6));
        if ($payload === false || @file_put_contents($tmp, $payload . "\n") === false) {
            return ['ok' => false, 'error' => 'Could not write the token file.'];
        }
        @chmod($tmp, 0600);
        if (!@rename($tmp, REPDOC_GSC_TOKEN_FILE)) {
            @unlink($tmp);
            return ['ok' => false, 'error' => 'Could not replace the token file.'];
        }
        @chmod(REPDOC_GSC_TOKEN_FILE, 0600);
        return ['ok' => true, 'error' => ''];
    }
}

if (!function_exists('repdoc_gsc_token_delete')) {
    function repdoc_gsc_token_delete(): void
    {
        @unlink(REPDOC_GSC_TOKEN_FILE);
        if (session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION['repdoc_gsc_access']);
        }
    }
}

if (!function_exists('repdoc_gsc_refresh')) {
    /** @return array{ok:bool,access_token:string,expires_in:int,error:string} */
    function repdoc_gsc_refresh(string $refreshToken): array
    {
        $body = json_encode([
            'token_uri'     => 'https://oauth2.googleapis.com/token',
            'refresh_token' => $refreshToken,
        ]);
        $res = repdoc_gsc_http('POST', 'https://developers.google.com/oauthplayground/refreshAccessToken', [
            'Content-Type: application/json',
            'Accept: application/json',
            'Origin: https://developers.google.com',
            'Referer: https://developers.google.com/oauthplayground/',
        ], $body === false ? null : $body, 25);
        $out = repdoc_gsc_decode($res);
        $token = is_array($out['data']) ? (string) ($out['data']['access_token'] ?? '') : '';
        if (!$out['ok'] || $token === '') {
            return ['ok' => false, 'access_token' => '', 'expires_in' => 0, 'error' => $out['error'] !== '' ? $out['error'] : 'No access token returned.'];
        }
        return [
            'ok'           => true,
            'access_token' => $token,
            'expires_in'   => max(60, (int) ($out['data']['expires_in'] ?? 3600)),
            'error'        => '',
        ];
    }
}

if (!function_exists('repdoc_gsc_access')) {
    /** @return array{ok:bool,token:string,error:string} */
    function repdoc_gsc_access(bool $force = false): array
    {
        if (!$force && session_status() === PHP_SESSION_ACTIVE) {
            $cached = $_SESSION['repdoc_gsc_access'] ?? null;
            if (is_array($cached) && !empty($cached['token']) && (int) ($cached['expires'] ?? 0) > time() + 60) {
                return ['ok' => true, 'token' => (string) $cached['token'], 'error' => ''];
            }
        }
        $refresh = repdoc_gsc_token_load();
        if ($refresh === null) {
            return ['ok' => false, 'token' => '', 'error' => 'Search Console is not connected yet.'];
        }
        $res = repdoc_gsc_refresh($refresh);
        if (!$res['ok']) {
            return ['ok' => false, 'token' => '', 'error' => $res['error']];
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['repdoc_gsc_access'] = [
                'token'   => $res['access_token'],
                'expires' => time() + $res['expires_in'],
            ];
        }
        return ['ok' => true, 'token' => $res['access_token'], 'error' => ''];
    }
}

if (!function_exists('repdoc_gsc_request')) {
    /** @return array{ok:bool,status:int,data:?array,error:string,raw:string} */
    function repdoc_gsc_request(string $token, string $method, string $url, ?array $payload = null): array
    {
        $headers = [
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
        ];
        $body = null;
        if ($payload !== null) {
            $body = json_encode($payload);
            $headers[] = 'Content-Type: application/json';
        }
        $res = repdoc_gsc_http($method, $url, $headers, $body === false ? null : $body, 25);
        return repdoc_gsc_decode($res);
    }
}

if (!function_exists('repdoc_gsc_api')) {
    /** Authenticated call; retries once with a fresh token on 401. */
    function repdoc_gsc_api(string $method, string $url, ?array $payload = null): array
    {
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $access = repdoc_gsc_access($attempt > 0);
            if (!$access['ok']) {
                return ['ok' => false, 'status' => 0, 'data' => null, 'error' => $access['error'], 'raw' => ''];
            }
            $res = repdoc_gsc_request($access['token'], $method, $url, $payload);
            if ($res['status'] !== 401 || $attempt > 0) {
                return $res;
            }
        }
        return ['ok' => false, 'status' => 0, 'data' => null, 'error' => 'Authentication failed.', 'raw' => ''];
    }
}

if (!function_exists('repdoc_gsc_validate')) {
    /**
     * Exchange a pasted refresh token and confirm the property is visible.
     * @return array{ok:bool,error:string,permission:string,property:string}
     */
    function repdoc_gsc_validate(string $refreshToken): array
    {
        $res = repdoc_gsc_refresh($refreshToken);
        if (!$res['ok']) {
            return ['ok' => false, 'error' => $res['error'], 'permission' => '', 'property' => ''];
        }
        $sites = repdoc_gsc_request($res['access_token'], 'GET', 'https://searchconsole.googleapis.com/webmasters/v3/sites');
        if (!$sites['ok']) {
            return ['ok' => false, 'error' => $sites['error'], 'permission' => '', 'property' => ''];
        }
        $site = repdoc_gsc_site();
        $permission = '';
        foreach ((array) ($sites['data']['siteEntry'] ?? []) as $entry) {
            if (is_array($entry) && (string) ($entry['siteUrl'] ?? '') === $site) {
                $permission = (string) ($entry['permissionLevel'] ?? '');
                break;
            }
        }
        if ($permission === '') {
            return ['ok' => false, 'error' => 'Token works, but ' . $site . ' is not in its Search Console sites.', 'permission' => '', 'property' => $site];
        }
        return ['ok' => true, 'error' => '', 'permission' => $permission, 'property' => $site];
    }
}

if (!function_exists('repdoc_gsc_sites')) {
    function repdoc_gsc_sites(): array
    {
        return repdoc_gsc_api('GET', 'https://searchconsole.googleapis.com/webmasters/v3/sites');
    }
}

if (!function_exists('repdoc_gsc_sitemaps')) {
    function repdoc_gsc_sitemaps(): array
    {
        $url = 'https://www.googleapis.com/webmasters/v3/sites/' . rawurlencode(repdoc_gsc_site()) . '/sitemaps';
        return repdoc_gsc_api('GET', $url);
    }
}

if (!function_exists('repdoc_gsc_sitemap_submit')) {
    function repdoc_gsc_sitemap_submit(): array
    {
        $feed = repdoc_gsc_site() . 'sitemap.xml';
        $url  = 'https://www.googleapis.com/webmasters/v3/sites/' . rawurlencode(repdoc_gsc_site()) . '/sitemaps/' . rawurlencode($feed);
        return repdoc_gsc_api('PUT', $url);
    }
}

if (!function_exists('repdoc_gsc_perf')) {
    /** @param array<int,string> $dimensions */
    function repdoc_gsc_perf(array $dimensions = [], int $days = 30, int $rowLimit = 10): array
    {
        $end   = strtotime('-2 days');
        $start = strtotime('-' . max(1, $days) . ' days', $end);
        $payload = [
            'startDate' => date('Y-m-d', $start),
            'endDate'   => date('Y-m-d', $end),
            'rowLimit'  => $rowLimit,
        ];
        if ($dimensions !== []) {
            $payload['dimensions'] = array_values($dimensions);
        }
        $url = 'https://www.googleapis.com/webmasters/v3/sites/' . rawurlencode(repdoc_gsc_site()) . '/searchAnalytics/query';
        return repdoc_gsc_api('POST', $url, $payload);
    }
}

if (!function_exists('repdoc_gsc_inspect')) {
    /** @return array{ok:bool,error:string,item:array} */
    function repdoc_gsc_inspect(string $url): array
    {
        $res = repdoc_gsc_api('POST', 'https://searchconsole.googleapis.com/v1/urlInspection/index:inspect', [
            'inspectionUrl' => $url,
            'siteUrl'       => repdoc_gsc_site(),
        ]);
        if (!$res['ok']) {
            return ['ok' => false, 'error' => $res['error'], 'item' => []];
        }
        $result = (array) ($res['data']['inspectionResult'] ?? []);
        $index  = (array) ($result['indexStatusResult'] ?? []);
        return [
            'ok'    => true,
            'error' => '',
            'item'  => [
                'url'             => $url,
                'verdict'         => (string) ($index['verdict'] ?? 'VERDICT_UNSPECIFIED'),
                'coverage'        => (string) ($index['coverageState'] ?? 'Unknown'),
                'robots'          => (string) ($index['robotsTxtState'] ?? ''),
                'fetch'           => (string) ($index['pageFetchState'] ?? ''),
                'crawl'           => (string) ($index['lastCrawlTime'] ?? ''),
                'googleCanonical' => (string) ($index['googleCanonical'] ?? ''),
                'userCanonical'   => (string) ($index['userCanonical'] ?? ''),
                'sitemaps'        => array_values((array) ($index['sitemap'] ?? [])),
            ],
        ];
    }
}

if (!function_exists('repdoc_gsc_cache_read')) {
    function repdoc_gsc_cache_read(): array
    {
        $empty = ['version' => 1, 'entries' => []];
        if (!is_file(REPDOC_GSC_CACHE_FILE)) return $empty;
        $raw = @file_get_contents(REPDOC_GSC_CACHE_FILE);
        $json = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($json) || ($json['version'] ?? 0) !== 1 || !is_array($json['entries'] ?? null)) {
            return $empty;
        }
        return $json;
    }
}

if (!function_exists('repdoc_gsc_cache_write')) {
    function repdoc_gsc_cache_write(array $cache): bool
    {
        $dir = dirname(REPDOC_GSC_CACHE_FILE);
        if (!is_dir($dir) || !is_writable($dir)) return false;
        $cache['version'] = 1;
        $json = json_encode($cache, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) return false;
        $tmp = REPDOC_GSC_CACHE_FILE . '.tmp.' . bin2hex(random_bytes(6));
        if (@file_put_contents($tmp, $json . "\n") === false) return false;
        @chmod($tmp, 0600);
        if (!@rename($tmp, REPDOC_GSC_CACHE_FILE)) {
            @unlink($tmp);
            return false;
        }
        @chmod(REPDOC_GSC_CACHE_FILE, 0600);
        return true;
    }
}

if (!function_exists('repdoc_gsc_cache_put')) {
    /** @param array<string,array> $entries keyed by cache slot: ['data'=>..., 'at'=>...] */
    function repdoc_gsc_cache_put(array $entries): bool
    {
        $cache = repdoc_gsc_cache_read();
        foreach ($entries as $key => $entry) {
            $cache['entries'][$key] = [
                'at'   => (int) ($entry['at'] ?? time()),
                'data' => $entry['data'] ?? null,
            ];
        }
        return repdoc_gsc_cache_write($cache);
    }
}

if (!function_exists('repdoc_gsc_cache_clear')) {
    function repdoc_gsc_cache_clear(): void
    {
        @unlink(REPDOC_GSC_CACHE_FILE);
    }
}
