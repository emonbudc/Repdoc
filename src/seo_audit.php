<?php
declare(strict_types=1);

/**
 * Repdoc — SEO & website health audit.
 *
 * Read-only inspection of the public pages, robots.txt, sitemap.xml and the
 * runtime. Nothing here writes to the site; the only side effect is the
 * short-lived JSON cache in /love/ (denied to the web by love/.htaccess).
 *
 * Page metadata is parsed from the PHP source so the audit stays cheap on
 * free hosting. Live HTTP probes are limited to the homepage (three crawler
 * user agents) plus robots.txt, sitemap.xml, the OG image and the favicon.
 */

require_once __DIR__ . '/seo.php';

if (!function_exists('repdoc_seo_registry')) {
    /**
     * Public pages the audit knows about.
     *
     * `indexable` mirrors the product decision: login/register stay indexable
     * and in the sitemap; gated tools and the apply/verify forms carry noindex.
     */
    function repdoc_seo_registry(): array
    {
        return [
            ['path' => '', 'file' => 'index.php', 'label' => 'Home', 'indexable' => true],
            ['path' => 'about/', 'file' => 'about/index.php', 'label' => 'About', 'indexable' => true],
            ['path' => 'showcase/', 'file' => 'showcase/index.php', 'label' => 'Template Showcase', 'indexable' => true],
            ['path' => 'officefiles/', 'file' => 'officefiles/index.php', 'label' => 'Office Files', 'indexable' => true],
            ['path' => 'offlinefiles/', 'file' => 'offlinefiles/index.php', 'label' => 'Offline Files', 'indexable' => true],
            ['path' => 'software/', 'file' => 'software/index.php', 'label' => 'Software', 'indexable' => true],
            ['path' => 'importantlinks/', 'file' => 'importantlinks/index.php', 'label' => 'Important Links', 'indexable' => true],
            ['path' => 'privacy/', 'file' => 'privacy/index.php', 'label' => 'Privacy', 'indexable' => true],
            ['path' => 'genaretor/certificate/', 'file' => 'genaretor/certificate/index.php', 'label' => 'Certificate Generator', 'indexable' => true],
            ['path' => 'public/login/', 'file' => 'public/login/index.php', 'label' => 'Login', 'indexable' => true],
            ['path' => 'public/register/', 'file' => 'public/register/index.php', 'label' => 'Register', 'indexable' => true],
            ['path' => 'Entrepreneur/apply/', 'file' => 'Entrepreneur/apply/index.php', 'label' => 'Entrepreneur Apply', 'indexable' => false],
            ['path' => 'verify/', 'file' => 'verify/index.php', 'label' => 'Certificate Verify', 'indexable' => false],
            ['path' => 'genaretor/birth/', 'file' => 'genaretor/birth/index.php', 'label' => 'Birth Generator', 'indexable' => false],
            ['path' => 'genaretor/cvmaker/', 'file' => 'genaretor/cvmaker/index.php', 'label' => 'CV Maker', 'indexable' => false],
            ['path' => 'tools/land/', 'file' => 'tools/land/index.php', 'label' => 'Land Records', 'indexable' => false],
            ['path' => 'tools/passport/', 'file' => 'tools/passport/index.php', 'label' => 'Passport Photo', 'indexable' => false],
        ];
    }
}

if (!function_exists('repdoc_audit_check')) {
    function repdoc_audit_check(string $label, string $status, string $detail = ''): array
    {
        return ['label' => $label, 'status' => $status, 'detail' => $detail];
    }
}

if (!function_exists('repdoc_audit_worst')) {
    function repdoc_audit_worst(array $checks): string
    {
        $rank = ['info' => 0, 'pass' => 1, 'warn' => 2, 'fail' => 3];
        $worst = 'pass';
        foreach ($checks as $c) {
            $s = (string) ($c['status'] ?? 'info');
            if (($rank[$s] ?? 0) > ($rank[$worst] ?? 0)) $worst = $s;
        }
        return $worst;
    }
}

if (!function_exists('repdoc_audit_score')) {
    function repdoc_audit_score(array $checks): int
    {
        $sum = 0.0;
        $n = 0;
        foreach ($checks as $c) {
            $s = (string) ($c['status'] ?? 'info');
            if ($s === 'info') continue;
            $sum += $s === 'pass' ? 1.0 : ($s === 'warn' ? 0.5 : 0.0);
            $n++;
        }
        return $n > 0 ? (int) round(100 * $sum / $n) : 0;
    }
}

if (!function_exists('repdoc_audit_counts')) {
    function repdoc_audit_counts(array $checks): array
    {
        $out = ['pass' => 0, 'warn' => 0, 'fail' => 0, 'info' => 0];
        foreach ($checks as $c) {
            $s = (string) ($c['status'] ?? 'info');
            if (isset($out[$s])) $out[$s]++;
        }
        return $out;
    }
}

if (!function_exists('repdoc_audit_eval_expr')) {
    /** Resolve a PHP string expression made of quoted literals and known vars. */
    function repdoc_audit_eval_expr(string $expr, array $vars = []): string
    {
        $expr = trim($expr);
        foreach ($vars as $name => $val) {
            $quoted = "'" . str_replace(['\\', "'"], ['\\\\', "\\'"], (string) $val) . "'";
            $expr = preg_replace('/\$' . preg_quote((string) $name, '/') . '\b/', $quoted, $expr);
        }
        if (preg_match_all("/'((?:\\\\.|[^'\\\\])*)'|\"((?:\\\\.|[^\"\\\\])*)\"/s", $expr, $m, PREG_SET_ORDER)) {
            $out = '';
            foreach ($m as $part) {
                $single = (string) ($part[1] ?? '');
                $double = (string) ($part[2] ?? '');
                $out .= stripcslashes($single !== '' ? $single : $double);
            }
            return trim($out);
        }
        return '';
    }
}

if (!function_exists('repdoc_audit_vars')) {
    function repdoc_audit_vars(string $src): array
    {
        $vars = [];
        if (preg_match('/\$BRAND\s*=\s*[\'"]([^\'"]*)[\'"]\s*;/', $src, $m)) {
            $vars['BRAND'] = $m[1];
        }
        return $vars;
    }
}

if (!function_exists('repdoc_audit_title')) {
    function repdoc_audit_title(string $src, array $vars): ?string
    {
        if (preg_match('/\$SEO_TITLE\s*=\s*(.+?);/s', $src, $m)) {
            $t = repdoc_audit_eval_expr($m[1], $vars);
            if ($t !== '') return $t;
        }
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $src, $m)) {
            $t = preg_replace_callback('/<\?=.*?\?>/s', static function (array $mm) use ($vars): string {
                if (preg_match('/\$([A-Za-z_]\w*)/', $mm[0], $v) && isset($vars[$v[1]])) {
                    return (string) $vars[$v[1]];
                }
                return '';
            }, $m[1]);
            $t = trim(html_entity_decode(strip_tags((string) $t), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($t !== '') return $t;
        }
        return null;
    }
}

if (!function_exists('repdoc_audit_description')) {
    function repdoc_audit_description(string $src, array $vars): ?string
    {
        if (preg_match('/<meta\s+name=["\']description["\']\s+content=["\'](.*?)["\']\s*\/?>/is', $src, $m)) {
            $d = preg_replace_callback('/<\?=.*?\?>/s', static function (array $mm) use ($vars): string {
                if (preg_match('/\$([A-Za-z_]\w*)/', $mm[0], $v) && isset($vars[$v[1]])) {
                    return (string) $vars[$v[1]];
                }
                return '';
            }, $m[1]);
            $d = trim(html_entity_decode((string) $d, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($d !== '') return $d;
        }
        if (preg_match('/\$SEO_DESC\s*=\s*(.+?);/s', $src, $m)) {
            $d = repdoc_audit_eval_expr($m[1], $vars);
            if ($d !== '') return $d;
        }
        return null;
    }
}

if (!function_exists('repdoc_audit_canonical_path')) {
    function repdoc_audit_canonical_path(string $src): ?string
    {
        if (preg_match('/\$CANON\s*=\s*repdoc_canonical\(\s*[\'"]([^\'"]*)[\'"]\s*\)/', $src, $m)) {
            return trim($m[1], '/');
        }
        if (preg_match('/repdoc_seo_head\s*\(/', $src)) {
            return '';
        }
        if (preg_match('/repdoc_canonical\(\s*\)/', $src)) {
            return '';
        }
        if (preg_match('/repdoc_canonical\(\s*[\'"]([^\'"]*)[\'"]\s*\)/', $src, $m)) {
            return trim($m[1], '/');
        }
        if (preg_match('/<link[^>]+rel=["\']canonical["\'][^>]+href=["\']([^"\']+)["\']/i', $src, $m)) {
            $href = $m[1];
            return str_starts_with($href, 'http') ? trim((string) parse_url($href, PHP_URL_PATH), '/') : trim($href, '/');
        }
        return null;
    }
}

if (!function_exists('repdoc_audit_social')) {
    function repdoc_audit_social(string $src): array
    {
        $found = [];
        if (preg_match_all('/property=["\']og:([a-z_]+)["\']/i', $src, $m)) {
            foreach ($m[1] as $key) $found['og:' . strtolower((string) $key)] = true;
        }
        if (preg_match('/name=["\']twitter:card["\']/i', $src)) {
            $found['twitter:card'] = true;
        }

        $helper = (bool) preg_match('/repdoc_seo_head\s*\(/', $src);
        $opts = [];
        if ($helper) {
            $start = strpos($src, 'repdoc_seo_head(');
            $block = $start !== false ? substr($src, (int) $start, 1800) : '';
            foreach (['title', 'description', 'canonical', 'image'] as $key) {
                if (preg_match('/[\'"]' . $key . '[\'"]\s*=>/', $block)) $opts[] = $key;
            }
        }

        return ['found' => $found, 'helper' => $helper, 'opts' => $opts];
    }
}

if (!function_exists('repdoc_audit_h1')) {
    function repdoc_audit_h1(string $src): array
    {
        $count = (int) preg_match_all('/<h1\b/i', $src);
        if ($count === 1) return [$count, 'pass', '1 H1'];
        if ($count === 0) return [0, 'warn', 'No H1 found'];
        $first = stripos($src, '<h1');
        $last = strripos($src, '<h1');
        $between = ($first !== false && $last !== false && $last > $first)
            ? substr($src, $first, $last - $first)
            : '';
        if (preg_match('/<\?php\s*(else|elseif)\b/i', $between)) {
            return [$count, 'pass', $count . ' H1s in exclusive branches'];
        }
        return [$count, 'warn', $count . ' H1 tags found'];
    }
}

if (!function_exists('repdoc_audit_jsonld')) {
    function repdoc_audit_jsonld(string $src): array
    {
        if (preg_match('/repdoc_jsonld\s*\(/', $src)) {
            return ['pass', 'JSON-LD via repdoc_jsonld()'];
        }
        if (preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $src, $m)) {
            foreach ($m[1] as $json) {
                json_decode((string) $json, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return ['fail', 'Invalid JSON-LD block'];
                }
            }
            return ['pass', count($m[1]) . ' JSON-LD block(s)'];
        }
        return ['warn', 'No JSON-LD'];
    }
}

if (!function_exists('repdoc_audit_has_noindex')) {
    function repdoc_audit_has_noindex(string $src): bool
    {
        if (preg_match('/<meta\s+name=["\']robots["\'][^>]*content=["\'][^"\']*noindex/i', $src)) return true;
        if (preg_match('/[\'"]noindex[\'"]\s*=>\s*true/', $src)) return true;
        return false;
    }
}

if (!function_exists('repdoc_audit_parse_robots')) {
    function repdoc_audit_parse_robots(string $txt): array
    {
        $groups = [];
        $idx = -1;
        $lastWasAgent = false;
        $sitemap = null;

        foreach (preg_split('/\R/', $txt) as $line) {
            $line = trim((string) preg_replace('/#.*/', '', (string) $line));
            if ($line === '') {
                $lastWasAgent = false;
                continue;
            }
            if (preg_match('/^user-agent:\s*(.+)$/i', $line, $m)) {
                if (!$lastWasAgent || $idx < 0) {
                    $idx++;
                    $groups[$idx] = ['agents' => [], 'allow' => [], 'disallow' => []];
                }
                $groups[$idx]['agents'][] = strtolower(trim($m[1]));
                $lastWasAgent = true;
                continue;
            }
            if (preg_match('/^(allow|disallow):\s*(.*)$/i', $line, $m)) {
                if ($idx >= 0) {
                    $groups[$idx][strtolower($m[1])][] = trim($m[2]);
                }
                $lastWasAgent = false;
                continue;
            }
            if (preg_match('/^sitemap:\s*(\S+)$/i', $line, $m)) {
                $sitemap = trim($m[1]);
            }
            $lastWasAgent = false;
        }

        $allow = [];
        $disallow = [];
        foreach ($groups as $group) {
            if (in_array('*', $group['agents'], true)) {
                $allow = array_merge($allow, $group['allow']);
                $disallow = array_merge($disallow, $group['disallow']);
            }
        }

        return ['allow' => $allow, 'disallow' => $disallow, 'sitemap' => $sitemap];
    }
}

if (!function_exists('repdoc_audit_robots_allows')) {
    function repdoc_audit_robots_allows(string $path, array $rules): bool
    {
        $path = '/' . ltrim($path, '/');
        $best = -1;
        $allowed = true;

        foreach (($rules['allow'] ?? []) as $rule) {
            $rule = (string) $rule;
            if ($rule === '') continue;
            if (str_starts_with($path, $rule) && strlen($rule) >= $best) {
                $best = strlen($rule);
                $allowed = true;
            }
        }
        foreach (($rules['disallow'] ?? []) as $rule) {
            $rule = (string) $rule;
            if ($rule === '') continue;
            if (str_starts_with($path, $rule) && strlen($rule) > $best) {
                $best = strlen($rule);
                $allowed = false;
            }
        }

        return $allowed;
    }
}

if (!function_exists('repdoc_audit_parse_sitemap')) {
    function repdoc_audit_parse_sitemap(string $xml): array
    {
        $out = ['valid' => false, 'urls' => [], 'error' => ''];
        if (trim($xml) === '') {
            $out['error'] = 'sitemap.xml is missing or empty';
            return $out;
        }

        $previous = libxml_use_internal_errors(true);
        $doc = simplexml_load_string($xml);
        if ($doc === false) {
            $out['error'] = 'sitemap.xml is not valid XML';
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            return $out;
        }
        libxml_use_internal_errors($previous);

        $out['valid'] = true;
        foreach ($doc->url as $url) {
            $loc = trim((string) $url->loc);
            if ($loc === '') continue;
            $out['urls'][] = ['loc' => $loc, 'lastmod' => trim((string) $url->lastmod)];
        }

        return $out;
    }
}

if (!function_exists('repdoc_audit_url_path')) {
    function repdoc_audit_url_path(string $url): string
    {
        $base = rtrim(REPDOC_PUBLIC_BASE, '/');
        if ($base !== '' && str_starts_with($url, $base)) {
            return trim(substr($url, strlen($base)), '/');
        }
        return trim((string) parse_url($url, PHP_URL_PATH), '/');
    }
}

if (!function_exists('repdoc_audit_probe')) {
    function repdoc_audit_probe(string $url, string $ua, int $timeout = 6): array
    {
        $out = ['ok' => false, 'status' => 0, 'size' => 0, 'headers' => [], 'body' => '', 'error' => ''];

        if (function_exists('curl_init')) {
            $headers = [];
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 3,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_USERAGENT      => $ua,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_ENCODING       => '',
                CURLOPT_HEADERFUNCTION => static function ($handle, string $line) use (&$headers): int {
                    $length = strlen($line);
                    $pos = strpos($line, ':');
                    if ($pos !== false) {
                        $headers[strtolower(trim(substr($line, 0, $pos)))] = trim(substr($line, $pos + 1));
                    }
                    return $length;
                },
            ]);
            $body = curl_exec($ch);
            $out['status'] = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $out['size'] = (int) curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
            $out['headers'] = $headers;
            if (is_string($body)) {
                $out['ok'] = $out['status'] > 0;
                $out['body'] = substr($body, 0, 500000);
            } else {
                $out['error'] = (string) curl_error($ch);
            }
            return $out;
        }

        $context = stream_context_create([
            'http' => [
                'method'          => 'GET',
                'header'          => 'User-Agent: ' . $ua . "\r\n",
                'timeout'         => $timeout,
                'follow_location' => 1,
                'max_redirects'   => 3,
                'ignore_errors'   => true,
            ],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);
        $body = @file_get_contents($url, false, $context);
        $raw = $http_response_header ?? [];
        foreach ($raw as $line) {
            if (preg_match('#^HTTP/\S+\s+(\d+)#', $line, $m)) {
                $out['status'] = (int) $m[1];
            }
            $pos = strpos($line, ':');
            if ($pos !== false) {
                $out['headers'][strtolower(trim(substr($line, 0, $pos)))] = trim(substr($line, $pos + 1));
            }
        }
        if (is_string($body)) {
            $out['ok'] = $out['status'] > 0;
            $out['size'] = strlen($body);
            $out['body'] = substr($body, 0, 500000);
        } else {
            $out['error'] = 'request failed';
        }

        return $out;
    }
}

if (!function_exists('repdoc_audit_wall')) {
    function repdoc_audit_wall(string $body): bool
    {
        if ($body === '') return false;
        return str_contains($body, 'aes.js')
            || str_contains($body, 'slowAES')
            || str_contains($body, '__test')
            || stripos($body, 'requires Javascript') !== false;
    }
}

if (!function_exists('repdoc_audit_ua')) {
    function repdoc_audit_ua(string $kind): string
    {
        return match ($kind) {
            'googlebot'  => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
            'twitterbot' => 'Twitterbot/1.0',
            'facebook'   => 'facebookexternalhit/1.1',
            default      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36',
        };
    }
}

if (!function_exists('repdoc_audit_health')) {
    function repdoc_audit_health(array $probes): array
    {
        $groups = ['runtime' => [], 'filesystem' => [], 'security' => [], 'content' => []];

        $php = PHP_VERSION;
        $phpStatus = version_compare($php, '8.1.0', '>=') ? 'pass' : (version_compare($php, '8.0.0', '>=') ? 'warn' : 'fail');
        $groups['runtime']['php_version'] = repdoc_audit_check('PHP version', $phpStatus, $php . ' (project targets 8.5)');

        foreach (['pdo', 'mbstring', 'json', 'openssl', 'curl', 'simplexml', 'gd', 'fileinfo'] as $ext) {
            $loaded = extension_loaded($ext);
            $groups['runtime']['ext_' . $ext] = repdoc_audit_check(
                'Extension: ' . $ext,
                $loaded ? 'pass' : 'fail',
                $loaded ? 'loaded' : 'missing'
            );
        }

        $driverExt = repdoc_uses_mysql() ? 'pdo_mysql' : 'pdo_sqlite';
        $driverLoaded = extension_loaded($driverExt);
        $groups['runtime']['ext_driver'] = repdoc_audit_check(
            'Database driver: ' . $driverExt,
            $driverLoaded ? 'pass' : 'fail',
            $driverLoaded ? 'loaded' : 'missing'
        );

        $db = repdoc_db();
        if ($db instanceof PDO) {
            $driver = (string) $db->getAttribute(PDO::ATTR_DRIVER_NAME);
            $version = '';
            try {
                $version = $driver === 'mysql'
                    ? (string) $db->query('SELECT VERSION()')->fetchColumn()
                    : (string) $db->query('SELECT sqlite_version()')->fetchColumn();
            } catch (Throwable $e) {
                $version = '';
            }
            $groups['runtime']['db'] = repdoc_audit_check(
                'Database connection',
                'pass',
                $driver . ($version !== '' ? ' ' . $version : '') . (repdoc_uses_mysql() ? ' (MySQL)' : ' (SQLite)')
            );
        } else {
            $groups['runtime']['db'] = repdoc_audit_check('Database connection', 'fail', 'Could not connect');
        }

        $displayErrors = strtolower((string) ini_get('display_errors'));
        $displayOff = in_array($displayErrors, ['0', 'off', ''], true);
        $groups['runtime']['display_errors'] = repdoc_audit_check(
            'display_errors',
            $displayOff ? 'pass' : 'warn',
            $displayOff ? 'off' : 'on — errors may leak to visitors'
        );

        $exposePhp = strtolower((string) ini_get('expose_php'));
        $exposeOff = in_array($exposePhp, ['0', 'off', ''], true);
        $groups['runtime']['expose_php'] = repdoc_audit_check(
            'expose_php',
            $exposeOff ? 'pass' : 'warn',
            $exposeOff ? 'off' : 'on — X-Powered-By advertises PHP'
        );

        $cookieHttpOnly = (bool) ini_get('session.cookie_httponly');
        $groups['runtime']['cookie_httponly'] = repdoc_audit_check(
            'Session cookie HttpOnly',
            $cookieHttpOnly ? 'pass' : 'warn',
            $cookieHttpOnly ? 'enabled' : 'not set'
        );

        $cookieSecure = (bool) ini_get('session.cookie_secure');
        $groups['runtime']['cookie_secure'] = repdoc_audit_check(
            'Session cookie Secure',
            $cookieSecure ? 'pass' : 'warn',
            $cookieSecure ? 'enabled' : 'not set'
        );

        $sameSite = (string) ini_get('session.cookie_samesite');
        $groups['runtime']['cookie_samesite'] = repdoc_audit_check(
            'Session cookie SameSite',
            in_array(strtolower($sameSite), ['lax', 'strict'], true) ? 'pass' : 'warn',
            $sameSite !== '' ? $sameSite : 'not set'
        );

        $loveWritable = is_dir(REPDOC_LOVE) && is_writable(REPDOC_LOVE);
        $groups['filesystem']['love_writable'] = repdoc_audit_check(
            'love/ writable',
            $loveWritable ? 'pass' : 'fail',
            $loveWritable ? 'writable (cache + SQLite)' : 'not writable'
        );

        if (!repdoc_uses_mysql()) {
            $dbWritable = is_file(CENTRAL_DB_PATH) && is_writable(CENTRAL_DB_PATH);
            $groups['filesystem']['db_writable'] = repdoc_audit_check(
                'SQLite database writable',
                $dbWritable ? 'pass' : 'warn',
                $dbWritable ? basename(CENTRAL_DB_PATH) : 'not writable'
            );
        }

        $free = @disk_free_space(REPDOC_ROOT);
        if ($free === false) {
            $groups['filesystem']['disk'] = repdoc_audit_check('Disk space', 'info', 'disk_free_space() unavailable');
        } else {
            $mb = (int) floor(((float) $free) / 1048576);
            $groups['filesystem']['disk'] = repdoc_audit_check(
                'Disk space',
                $mb > 200 ? 'pass' : ($mb > 50 ? 'warn' : 'fail'),
                number_format($mb) . ' MB free'
            );
        }

        $exposed = [
            'index.php.bak' => ['fail', 'Homepage backup is downloadable'],
            '.env' => ['fail', 'Environment file in the web root'],
            'src/setup.php' => ['warn', 'Setup script still deployed (guarded after first admin)'],
            'tools/birthsearch/debug.php' => ['warn', 'Debug endpoint deployed'],
            'tools/birthsearch/test_ocr_debug.php' => ['warn', 'Debug endpoint deployed'],
        ];
        foreach ($exposed as $relative => [$status, $detail]) {
            $present = is_file(REPDOC_ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative));
            if ($relative === 'index.php.bak' && $present) {
                $backupProbe = $probes['backup'] ?? [];
                $backupBody = (string) ($backupProbe['body'] ?? '');
                $leaked = ($backupProbe['status'] ?? 0) === 200
                    && !repdoc_audit_wall($backupBody)
                    && str_contains($backupBody, '<?php');
                $groups['filesystem']['exposed_' . md5($relative)] = repdoc_audit_check(
                    'Exposed: ' . $relative,
                    $leaked ? 'fail' : 'pass',
                    $leaked ? 'Backup source is downloadable' : 'present but not served by the host'
                );
                continue;
            }
            $groups['filesystem']['exposed_' . md5($relative)] = repdoc_audit_check(
                'Exposed: ' . $relative,
                $present ? $status : 'pass',
                $present ? $detail : 'not present'
            );
        }

        $loveHtaccess = REPDOC_LOVE . DIRECTORY_SEPARATOR . '.htaccess';
        $loveProtected = false;
        if (is_file($loveHtaccess)) {
            $rules = (string) @file_get_contents($loveHtaccess);
            $loveProtected = stripos($rules, 'Require all denied') !== false || stripos($rules, 'Deny from all') !== false;
        }
        $groups['filesystem']['love_protected'] = repdoc_audit_check(
            'love/.htaccess deny rule',
            $loveProtected ? 'pass' : 'fail',
            $loveProtected ? 'databases denied to the web' : 'missing deny rule'
        );

        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443')
            || str_starts_with(REPDOC_PUBLIC_BASE, 'https://');
        $groups['security']['https'] = repdoc_audit_check(
            'HTTPS',
            $https ? 'pass' : 'fail',
            $https ? 'served over HTTPS' : 'not HTTPS'
        );

        $headers = $probes['googlebot']['headers'] ?? [];
        if (!$headers) $headers = $probes['chrome']['headers'] ?? [];
        $headerChecks = [
            'content-security-policy' => 'Content-Security-Policy',
            'x-frame-options' => 'X-Frame-Options',
            'x-content-type-options' => 'X-Content-Type-Options',
            'referrer-policy' => 'Referrer-Policy',
        ];
        foreach ($headerChecks as $key => $label) {
            if (!$headers) {
                $groups['security']['header_' . $key] = repdoc_audit_check($label, 'info', 'live probe unavailable');
                continue;
            }
            $present = isset($headers[$key]) && $headers[$key] !== '';
            $groups['security']['header_' . $key] = repdoc_audit_check(
                $label,
                $present ? 'pass' : 'warn',
                $present ? 'present' : 'missing'
            );
        }

        $adminLayout = REPDOC_ROOT . '/admin/includes/layout.php';
        $adminNoindex = is_file($adminLayout)
            && str_contains((string) @file_get_contents($adminLayout), 'meta name="robots" content="noindex"');
        $groups['security']['admin_noindex'] = repdoc_audit_check(
            'Admin pages noindex',
            $adminNoindex ? 'pass' : 'warn',
            $adminNoindex ? 'admin layout carries noindex' : 'noindex not found in admin layout'
        );

        $rootHtaccess = (string) @file_get_contents(REPDOC_ROOT . '/.htaccess');
        $hasErrorDoc = str_contains($rootHtaccess, 'ErrorDocument 404');
        $groups['security']['error_document'] = repdoc_audit_check(
            '404 ErrorDocument',
            $hasErrorDoc ? 'pass' : 'warn',
            $hasErrorDoc ? 'configured in .htaccess' : 'not configured'
        );

        $hsts = $headers['strict-transport-security'] ?? '';
        $groups['security']['hsts'] = repdoc_audit_check(
            'Strict-Transport-Security',
            $hsts !== '' ? 'pass' : 'info',
            $hsts !== '' ? 'present' : 'not sent (optional on shared hosting)'
        );

        $robotsProbe = $probes['robots'] ?? [];
        $robotsBody = (string) ($robotsProbe['body'] ?? '');
        $robotsOk = ($robotsProbe['status'] ?? 0) === 200 && stripos($robotsBody, 'user-agent') !== false;
        $groups['content']['robots_http'] = repdoc_audit_check(
            'robots.txt reachable',
            $robotsOk ? 'pass' : 'warn',
            $robotsOk ? 'HTTP 200 · text' : 'HTTP ' . (int) ($robotsProbe['status'] ?? 0) . ' · unexpected body'
        );

        $sitemapProbe = $probes['sitemap'] ?? [];
        $sitemapBody = (string) ($sitemapProbe['body'] ?? '');
        $sitemapOk = ($sitemapProbe['status'] ?? 0) === 200 && str_contains($sitemapBody, '<urlset');
        $groups['content']['sitemap_http'] = repdoc_audit_check(
            'sitemap.xml reachable',
            $sitemapOk ? 'pass' : 'warn',
            $sitemapOk ? 'HTTP 200 · XML' : 'HTTP ' . (int) ($sitemapProbe['status'] ?? 0) . ' · non-XML response'
        );

        $ogProbe = $probes['og_image'] ?? [];
        $ogType = strtolower((string) ($ogProbe['headers']['content-type'] ?? ''));
        $ogOk = ($ogProbe['status'] ?? 0) === 200 && str_starts_with($ogType, 'image/');
        $groups['content']['og_image_http'] = repdoc_audit_check(
            'OG image reachable',
            $ogOk ? 'pass' : 'warn',
            $ogOk ? 'HTTP 200 · ' . $ogType : 'HTTP ' . (int) ($ogProbe['status'] ?? 0)
        );

        $faviconProbe = $probes['favicon'] ?? [];
        $groups['content']['favicon_http'] = repdoc_audit_check(
            'Favicon reachable',
            ($faviconProbe['status'] ?? 0) === 200 ? 'pass' : 'warn',
            ($faviconProbe['status'] ?? 0) === 200 ? 'HTTP 200' : 'HTTP ' . (int) ($faviconProbe['status'] ?? 0)
        );

        return $groups;
    }
}

if (!function_exists('repdoc_audit_flatten')) {
    function repdoc_audit_flatten(array $groups): array
    {
        $out = [];
        foreach ($groups as $group) {
            if (!is_array($group)) continue;
            foreach ($group as $check) {
                if (is_array($check) && isset($check['status'])) $out[] = $check;
            }
        }
        return $out;
    }
}

if (!function_exists('repdoc_audit_run')) {
    function repdoc_audit_run(): array
    {
        $root = REPDOC_ROOT;
        $base = repdoc_canonical();

        $robotsTxt = is_file($root . '/robots.txt') ? (string) @file_get_contents($root . '/robots.txt') : '';
        $robots = repdoc_audit_parse_robots($robotsTxt);
        $robotsExists = is_file($root . '/robots.txt');

        $sitemapTxt = is_file($root . '/sitemap.xml') ? (string) @file_get_contents($root . '/sitemap.xml') : '';
        $sitemap = repdoc_audit_parse_sitemap($sitemapTxt);
        $sitemapPaths = [];
        foreach ($sitemap['urls'] as $entry) {
            $sitemapPaths[repdoc_audit_url_path((string) $entry['loc'])] = (string) $entry['lastmod'];
        }

        $probes = [
            'chrome'    => repdoc_audit_probe($base, repdoc_audit_ua('chrome')),
            'googlebot' => repdoc_audit_probe($base, repdoc_audit_ua('googlebot')),
            'twitterbot' => repdoc_audit_probe($base, repdoc_audit_ua('twitterbot')),
            'robots'    => repdoc_audit_probe($base . 'robots.txt', repdoc_audit_ua('chrome')),
            'sitemap'   => repdoc_audit_probe($base . 'sitemap.xml', repdoc_audit_ua('googlebot')),
            'og_image'  => repdoc_audit_probe($base . 'src/assets/image/logo.png', repdoc_audit_ua('chrome')),
            'favicon'   => repdoc_audit_probe($base . 'public/favicon.png', repdoc_audit_ua('chrome')),
            'backup'    => repdoc_audit_probe($base . 'index.php.bak', repdoc_audit_ua('chrome')),
        ];

        $wallChrome = repdoc_audit_wall((string) $probes['chrome']['body']);
        $wallGoogle = repdoc_audit_wall((string) $probes['googlebot']['body']);
        $wallTwitter = repdoc_audit_wall((string) $probes['twitterbot']['body']);
        $googleReal = (bool) $probes['googlebot']['ok']
            && !$wallGoogle
            && stripos((string) $probes['googlebot']['body'], '<title') !== false;

        $host = [];
        if (!$probes['chrome']['ok']) {
            $host['wall'] = repdoc_audit_check('Browser UA access', 'info', 'Public URL unreachable from the server: ' . ($probes['chrome']['error'] ?: 'unknown error'));
        } else {
            $host['wall'] = repdoc_audit_check(
                'Browser UA access',
                $wallChrome ? 'warn' : 'pass',
                $wallChrome
                    ? 'Host bot wall served (aes.js / __test) — third-party checkers and some social bots are blocked'
                    : 'Real page served (' . number_format((int) $probes['chrome']['size']) . ' bytes)'
            );
        }
        $host['googlebot'] = repdoc_audit_check(
            'Googlebot access',
            $googleReal ? 'pass' : ($probes['googlebot']['ok'] ? 'fail' : 'info'),
            $googleReal
                ? 'Real page served (' . number_format((int) $probes['googlebot']['size']) . ' bytes)'
                : ($probes['googlebot']['ok'] ? 'Googlebot received the bot wall or an empty page' : 'Probe failed')
        );
        $host['twitterbot'] = repdoc_audit_check(
            'Twitterbot access',
            $probes['twitterbot']['ok'] ? ($wallTwitter ? 'warn' : 'pass') : 'info',
            !$probes['twitterbot']['ok']
                ? 'Probe failed'
                : ($wallTwitter ? 'Challenge served — X/Twitter card previews will fail' : 'Real page served')
        );
        $host['facebook'] = repdoc_audit_check(
            'Facebook crawler access',
            $wallChrome ? 'info' : 'pass',
            $wallChrome ? 'Shares the same host-level wall as other non-whitelisted bots' : 'No wall detected'
        );

        $pages = [];
        $titleIndex = [];
        $descIndex = [];

        foreach (repdoc_seo_registry() as $page) {
            $file = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, (string) $page['file']);
            $src = is_file($file) ? (string) @file_get_contents($file) : '';
            $vars = repdoc_audit_vars($src);
            $title = repdoc_audit_title($src, $vars);
            $desc = repdoc_audit_description($src, $vars);
            $path = trim((string) $page['path'], '/');
            $robotsPath = '/' . $path . (str_ends_with((string) $page['path'], '/') ? '/' : '');
            $indexable = (bool) $page['indexable'];
            $checks = [];

            if ($src === '') {
                foreach (['title', 'description', 'viewport', 'canonical', 'social', 'h1', 'jsonld', 'indexability', 'sitemap', 'robots'] as $key) {
                    $checks[$key] = repdoc_audit_check(ucfirst($key), 'fail', 'Source file missing: ' . $page['file']);
                }
                $pages[] = [
                    'path' => $path, 'label' => $page['label'], 'file' => $page['file'],
                    'indexable' => $indexable, 'checks' => $checks,
                    'status' => 'fail', 'score' => 0,
                    'title_value' => null, 'desc_value' => null,
                ];
                continue;
            }

            if ($title === null) {
                $checks['title'] = repdoc_audit_check('Title', 'fail', 'No <title> found');
            } else {
                $len = mb_strlen($title, 'UTF-8');
                $status = ($len < 15 || $len > 60) ? 'warn' : 'pass';
                $checks['title'] = repdoc_audit_check('Title', $status, $len . ' chars — ' . $title);
                $titleIndex[$title][] = (string) $page['label'];
            }

            if ($desc === null || trim($desc) === '') {
                $checks['description'] = $indexable
                    ? repdoc_audit_check('Description', 'fail', 'No meta description found')
                    : repdoc_audit_check('Description', 'info', 'Gated page — description optional');
                $desc = null;
            } else {
                $len = mb_strlen($desc, 'UTF-8');
                $status = ($len < 70 || $len > 160) ? 'warn' : 'pass';
                $detail = $len . ' chars — ' . $desc;
                if (preg_match('/Repdoc\s*[—\-–]\s*Repdoc/ui', $desc)) {
                    $status = 'warn';
                    $detail = 'Duplicated brand: ' . $desc;
                }
                $checks['description'] = repdoc_audit_check('Description', $status, $detail);
                $descIndex[$desc][] = (string) $page['label'];
            }

            $checks['viewport'] = preg_match('/<meta\s+name=["\']viewport["\']/i', $src)
                ? repdoc_audit_check('Viewport', 'pass', 'present')
                : repdoc_audit_check('Viewport', 'fail', 'No viewport meta tag');

            $canonPath = repdoc_audit_canonical_path($src);
            if ($canonPath === null) {
                $checks['canonical'] = $indexable
                    ? repdoc_audit_check('Canonical', 'fail', 'No canonical URL')
                    : repdoc_audit_check('Canonical', 'info', 'Gated page — canonical optional');
            } elseif ($canonPath === '' || $canonPath === $path) {
                $checks['canonical'] = repdoc_audit_check('Canonical', 'pass', repdoc_canonical($path));
            } else {
                $checks['canonical'] = repdoc_audit_check('Canonical', 'warn', 'Points to /' . $canonPath . ' instead of /' . $path);
            }

            $social = repdoc_audit_social($src);
            $socialOpts = $social['opts'];
            if ($social['helper'] && count($socialOpts) === 4) {
                $checks['social'] = repdoc_audit_check('Social tags', 'pass', 'OG + Twitter via repdoc_seo_head()');
            } elseif ($social['helper']) {
                $missing = array_values(array_diff(['title', 'description', 'canonical', 'image'], $socialOpts));
                $checks['social'] = repdoc_audit_check('Social tags', 'warn', 'repdoc_seo_head() missing: ' . implode(', ', $missing));
            } elseif (isset($social['found']['og:title'], $social['found']['og:description'], $social['found']['og:image'])) {
                $checks['social'] = repdoc_audit_check('Social tags', 'pass', 'Literal Open Graph tags present');
            } else {
                $checks['social'] = $indexable
                    ? repdoc_audit_check('Social tags', 'fail', 'No Open Graph tags')
                    : repdoc_audit_check('Social tags', 'info', 'Gated page — social tags optional');
            }

            $h1Src = $src;
            if (str_contains($src, 'repdoc_catalog_render(')) {
                $catalogFile = $root . '/src/catalog.php';
                if (is_file($catalogFile)) {
                    $h1Src .= (string) @file_get_contents($catalogFile);
                }
            }
            [$h1Count, $h1Status, $h1Detail] = repdoc_audit_h1($h1Src);
            $checks['h1'] = repdoc_audit_check('H1', $h1Status, $h1Detail);

            [$ldStatus, $ldDetail] = repdoc_audit_jsonld($src);
            if (!$indexable && $ldStatus === 'warn') {
                $ldStatus = 'info';
                $ldDetail = 'Gated page — JSON-LD optional';
            }
            $checks['jsonld'] = repdoc_audit_check('JSON-LD', $ldStatus, $ldDetail);

            $hasNoindex = repdoc_audit_has_noindex($src);
            if ($indexable) {
                $checks['indexability'] = $hasNoindex
                    ? repdoc_audit_check('Indexability', 'fail', 'noindex on an indexable page')
                    : repdoc_audit_check('Indexability', 'pass', 'indexable');
            } else {
                $checks['indexability'] = $hasNoindex
                    ? repdoc_audit_check('Indexability', 'pass', 'noindex (gated)')
                    : repdoc_audit_check('Indexability', 'warn', 'Gated page lacks noindex');
            }

            if (!$sitemap['valid']) {
                $checks['sitemap'] = repdoc_audit_check('Sitemap', 'warn', 'sitemap.xml missing or invalid — cannot verify');
            } else {
                $inSitemap = array_key_exists($path, $sitemapPaths);
                if ($indexable) {
                    $checks['sitemap'] = $inSitemap
                        ? repdoc_audit_check('Sitemap', 'pass', 'listed in sitemap.xml')
                        : repdoc_audit_check('Sitemap', 'fail', 'Indexable page missing from sitemap.xml');
                } else {
                    $checks['sitemap'] = $inSitemap
                        ? repdoc_audit_check('Sitemap', 'warn', 'Gated page is listed in sitemap.xml')
                        : repdoc_audit_check('Sitemap', 'pass', 'not in sitemap');
                }
            }

            if (!$robotsExists) {
                $checks['robots'] = repdoc_audit_check('Robots', 'warn', 'robots.txt missing — cannot verify');
            } else {
                $allowed = repdoc_audit_robots_allows($robotsPath, $robots);
                if ($indexable) {
                    $checks['robots'] = $allowed
                        ? repdoc_audit_check('Robots', 'pass', 'allowed by robots.txt')
                        : repdoc_audit_check('Robots', 'fail', 'blocked by robots.txt');
                } else {
                    $checks['robots'] = $allowed
                        ? repdoc_audit_check('Robots', 'info', 'robots allows; noindex is required')
                        : repdoc_audit_check('Robots', 'pass', 'blocked by robots.txt');
                }
            }

            $pages[] = [
                'path' => $path,
                'label' => (string) $page['label'],
                'file' => (string) $page['file'],
                'indexable' => $indexable,
                'checks' => $checks,
                'status' => repdoc_audit_worst($checks),
                'score' => repdoc_audit_score($checks),
                'title_value' => $title,
                'desc_value' => $desc,
            ];
        }

        foreach ($pages as &$page) {
            $title = $page['title_value'] ?? null;
            if (is_string($title) && count($titleIndex[$title] ?? []) > 1) {
                $page['checks']['title'] = repdoc_audit_check(
                    'Title',
                    'warn',
                    'Duplicate title used on: ' . implode(', ', $titleIndex[$title])
                );
            }
            $desc = $page['desc_value'] ?? null;
            if (is_string($desc) && count($descIndex[$desc] ?? []) > 1) {
                $page['checks']['description'] = repdoc_audit_check(
                    'Description',
                    'warn',
                    'Duplicate description used on: ' . implode(', ', $descIndex[$desc])
                );
            }
            $page['status'] = repdoc_audit_worst($page['checks']);
            $page['score'] = repdoc_audit_score($page['checks']);
        }
        unset($page);

        $site = [];
        $site['robots_file'] = repdoc_audit_check(
            'robots.txt',
            $robotsExists ? 'pass' : 'fail',
            $robotsExists ? 'present' : 'missing'
        );
        $site['robots_sitemap'] = repdoc_audit_check(
            'robots.txt Sitemap line',
            !empty($robots['sitemap']) ? (str_starts_with((string) $robots['sitemap'], rtrim($base, '/')) ? 'pass' : 'warn') : 'fail',
            !empty($robots['sitemap']) ? (string) $robots['sitemap'] : 'no Sitemap: line'
        );
        $site['robots_open'] = repdoc_audit_check(
            'robots.txt does not block everything',
            in_array('/', $robots['disallow'] ?? [], true) ? 'fail' : 'pass',
            in_array('/', $robots['disallow'] ?? [], true) ? 'Disallow: / found' : 'public paths allowed'
        );

        $site['sitemap_file'] = repdoc_audit_check(
            'sitemap.xml',
            $sitemap['valid'] ? 'pass' : 'fail',
            $sitemap['valid'] ? count($sitemap['urls']) . ' URLs' : (string) $sitemap['error']
        );

        $httpsUrls = true;
        $lastmodOk = true;
        foreach ($sitemap['urls'] as $entry) {
            if (!str_starts_with((string) $entry['loc'], 'https://')) $httpsUrls = false;
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $entry['lastmod'])) $lastmodOk = false;
        }
        $site['sitemap_https'] = repdoc_audit_check(
            'sitemap URLs use HTTPS',
            $sitemap['valid'] ? ($httpsUrls ? 'pass' : 'fail') : 'warn',
            $sitemap['valid'] ? ($httpsUrls ? 'all absolute HTTPS' : 'non-HTTPS URL found') : 'cannot verify'
        );
        $site['sitemap_lastmod'] = repdoc_audit_check(
            'sitemap lastmod format',
            $sitemap['valid'] ? ($lastmodOk ? 'pass' : 'warn') : 'warn',
            $sitemap['valid'] ? ($lastmodOk ? 'all YYYY-MM-DD' : 'some entries are not YYYY-MM-DD') : 'cannot verify'
        );

        $registryPaths = [];
        foreach (repdoc_seo_registry() as $page) {
            $registryPaths[trim((string) $page['path'], '/')] = (string) $page['file'];
        }
        $unknown = [];
        foreach ($sitemapPaths as $sitemapPath => $lastmod) {
            if (!array_key_exists($sitemapPath, $registryPaths)) $unknown[] = '/' . $sitemapPath;
        }
        $site['sitemap_known'] = repdoc_audit_check(
            'sitemap URLs map to known pages',
            $unknown ? 'warn' : 'pass',
            $unknown ? 'Unknown: ' . implode(', ', $unknown) : 'all URLs known'
        );

        $stale = [];
        foreach ($sitemapPaths as $sitemapPath => $lastmod) {
            if (!isset($registryPaths[$sitemapPath]) || $lastmod === '') continue;
            $file = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $registryPaths[$sitemapPath]);
            if (!is_file($file)) continue;
            $mtime = date('Y-m-d', (int) filemtime($file));
            if ($mtime > $lastmod) $stale[] = '/' . $sitemapPath . ' (file ' . $mtime . ', sitemap ' . $lastmod . ')';
        }
        $site['sitemap_stale'] = repdoc_audit_check(
            'sitemap lastmod is current',
            $stale ? 'warn' : 'pass',
            $stale ? 'Stale: ' . implode(', ', $stale) : 'dates are current'
        );

        $favicon = $root . '/public/favicon.png';
        $faviconInfo = is_file($favicon) ? @getimagesize($favicon) : false;
        $site['favicon'] = repdoc_audit_check(
            'Favicon',
            $faviconInfo !== false ? 'pass' : 'fail',
            $faviconInfo !== false ? $faviconInfo[0] . '×' . $faviconInfo[1] . ' px' : 'public/favicon.png missing'
        );

        $ogImage = $root . '/src/assets/image/logo.png';
        $ogInfo = is_file($ogImage) ? @getimagesize($ogImage) : false;
        $site['og_image'] = repdoc_audit_check(
            'Default OG image',
            $ogInfo !== false ? 'pass' : 'fail',
            $ogInfo !== false ? $ogInfo[0] . '×' . $ogInfo[1] . ' px' : 'src/assets/image/logo.png missing'
        );

        $site['llms'] = repdoc_audit_check(
            'llms.txt',
            is_file($root . '/Llms.txt') ? 'pass' : 'warn',
            is_file($root . '/Llms.txt') ? 'present' : 'missing'
        );

        $site['base'] = repdoc_audit_check(
            'Canonical base URL',
            str_starts_with(REPDOC_PUBLIC_BASE, 'https://') && !str_contains(REPDOC_PUBLIC_BASE, 'localhost') ? 'pass' : 'warn',
            REPDOC_PUBLIC_BASE
        );

        $dupTitles = [];
        foreach ($titleIndex as $title => $labels) {
            if (count($labels) > 1) $dupTitles[] = $title;
        }
        $dupDescs = [];
        foreach ($descIndex as $desc => $labels) {
            if (count($labels) > 1) $dupDescs[] = mb_substr($desc, 0, 60, 'UTF-8') . '…';
        }
        $site['duplicate_titles'] = repdoc_audit_check(
            'Unique titles',
            $dupTitles ? 'warn' : 'pass',
            $dupTitles ? count($dupTitles) . ' duplicate title(s)' : 'all unique'
        );
        $site['duplicate_descriptions'] = repdoc_audit_check(
            'Unique descriptions',
            $dupDescs ? 'warn' : 'pass',
            $dupDescs ? count($dupDescs) . ' duplicate description(s)' : 'all unique'
        );

        $health = repdoc_audit_health($probes);

        $seoChecks = array_values($site);
        foreach ($host as $check) $seoChecks[] = $check;
        foreach ($pages as $page) {
            foreach ($page['checks'] as $check) $seoChecks[] = $check;
        }
        $healthChecks = repdoc_audit_flatten($health);

        $seoCounts = repdoc_audit_counts($seoChecks);
        $healthCounts = repdoc_audit_counts($healthChecks);

        return [
            'generated_at' => time(),
            'base' => $base,
            'live' => (bool) ($probes['chrome']['ok'] || $probes['googlebot']['ok']),
            'pages' => $pages,
            'site' => $site,
            'host' => $host,
            'health' => $health,
            'scores' => [
                'seo' => repdoc_audit_score($seoChecks),
                'health' => repdoc_audit_score($healthChecks),
            ],
            'counts' => [
                'seo' => $seoCounts,
                'health' => $healthCounts,
            ],
        ];
    }
}

if (!function_exists('repdoc_seo_audit')) {
    /**
     * Cached audit. $force bypasses the 15-minute cache and refreshes it.
     */
    function repdoc_seo_audit(bool $force = false): array
    {
        $cacheFile = REPDOC_LOVE . DIRECTORY_SEPARATOR . 'seo-audit.json';
        $cacheVersion = 2;
        $ttl = 900;

        if (!$force && is_file($cacheFile)) {
            $raw = @file_get_contents($cacheFile);
            $json = $raw !== false ? json_decode($raw, true) : null;
            if (is_array($json) && ($json['version'] ?? 0) === $cacheVersion
                && isset($json['generated_at'], $json['data'])
                && (time() - (int) $json['generated_at']) < $ttl) {
                $data = $json['data'];
                $data['cached'] = true;
                return $data;
            }
        }

        $data = repdoc_audit_run();
        $data['cached'] = false;

        if (is_dir(REPDOC_LOVE) && is_writable(REPDOC_LOVE)) {
            @file_put_contents(
                $cacheFile,
                json_encode(['version' => $cacheVersion, 'generated_at' => time(), 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                LOCK_EX
            );
        }

        return $data;
    }
}
