<?php
declare(strict_types=1);

/**
 * Repdoc — runtime index controls.
 *
 * Admin overrides live in love/seo-controls.json (web-denied, not tracked).
 * Defaults come from repdoc_page_registry(); an override can force noindex or
 * re-enable a default-noindex page. Login-gated pages stay locked because
 * their app layout sends its own noindex meta.
 */

if (!defined('REPDOC_SEO_CONTROLS_FILE')) {
    $seoControlsDir = defined('REPDOC_LOVE') ? REPDOC_LOVE : dirname(__DIR__) . DIRECTORY_SEPARATOR . 'love';
    define('REPDOC_SEO_CONTROLS_FILE', $seoControlsDir . DIRECTORY_SEPARATOR . 'seo-controls.json');
}

if (!function_exists('repdoc_seo_path_key')) {
    /** Normalize a project path to a registry key: '' for home, no slashes. */
    function repdoc_seo_path_key(string $path): string
    {
        $path = trim($path);
        $cut = strcspn($path, '?#');
        if ($cut < strlen($path)) {
            $path = substr($path, 0, $cut);
        }
        $path = rawurldecode($path);
        return trim($path, '/');
    }
}

if (!function_exists('repdoc_seo_registry_map')) {
    /** @return array<string,array> registry pages keyed by normalized path. */
    function repdoc_seo_registry_map(): array
    {
        if (!function_exists('repdoc_page_registry')) {
            return [];
        }
        $map = [];
        foreach (repdoc_page_registry() as $page) {
            $map[repdoc_seo_path_key((string) ($page['path'] ?? ''))] = $page;
        }
        return $map;
    }
}

if (!function_exists('repdoc_seo_controls_defaults')) {
    function repdoc_seo_controls_defaults(): array
    {
        return ['version' => 1, 'updated_at' => 0, 'pages' => []];
    }
}

if (!function_exists('repdoc_seo_controls_load')) {
    function repdoc_seo_controls_load(): array
    {
        if (isset($GLOBALS['__repdoc_seo_controls'])) {
            return $GLOBALS['__repdoc_seo_controls'];
        }

        $data = repdoc_seo_controls_defaults();
        $file = REPDOC_SEO_CONTROLS_FILE;

        if (is_file($file)) {
            $raw = @file_get_contents($file);
            $json = is_string($raw) ? json_decode($raw, true) : null;
            if (is_array($json)) {
                if (!empty($json['pages']) && is_array($json['pages'])) {
                    foreach ($json['pages'] as $path => $entry) {
                        if (!is_array($entry) || !array_key_exists('indexable', $entry)) continue;
                        $data['pages'][repdoc_seo_path_key((string) $path)] = [
                            'indexable'  => (bool) $entry['indexable'],
                            'updated_at' => (int) ($entry['updated_at'] ?? 0),
                        ];
                    }
                }
                $data['updated_at'] = (int) ($json['updated_at'] ?? 0);
            }
        }

        $GLOBALS['__repdoc_seo_controls'] = $data;
        return $data;
    }
}

if (!function_exists('repdoc_seo_controls_save')) {
    function repdoc_seo_controls_save(array $data): bool
    {
        $file = REPDOC_SEO_CONTROLS_FILE;
        $dir  = dirname($file);
        if (!is_dir($dir) || !is_writable($dir)) {
            return false;
        }

        $data['version']    = 1;
        $data['updated_at'] = time();
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) {
            return false;
        }

        $tmp = $file . '.tmp.' . bin2hex(random_bytes(6));
        if (@file_put_contents($tmp, $json . "\n") === false) {
            return false;
        }
        @chmod($tmp, 0600);

        if (!@rename($tmp, $file)) {
            @unlink($tmp);
            return false;
        }
        @chmod($file, 0600);

        $GLOBALS['__repdoc_seo_controls'] = $data;
        return true;
    }
}

if (!function_exists('repdoc_seo_page_state')) {
    /**
     * Effective state for one registry path.
     *
     * @return array{path:string,label:string,known:bool,default:bool,indexable:bool,locked:bool,override:?bool,in_sitemap:bool}
     */
    function repdoc_seo_page_state(string $path): array
    {
        $key    = repdoc_seo_path_key($path);
        $map    = repdoc_seo_registry_map();
        $page   = $map[$key] ?? null;
        $data   = repdoc_seo_controls_load();
        $entry  = $data['pages'][$key] ?? null;

        $known    = is_array($page);
        $default  = $known ? (bool) ($page['indexable'] ?? false) : true;
        $locked   = $known ? (bool) ($page['locked'] ?? false) : false;
        $override = is_array($entry) && array_key_exists('indexable', $entry) ? (bool) $entry['indexable'] : null;
        $sitemap  = $known ? (bool) ($page['sitemap'] ?? false) : true;

        $indexable = $locked ? false : ($override ?? $default);

        return [
            'path'       => $key,
            'label'      => $known ? (string) ($page['label'] ?? $key) : $key,
            'known'      => $known,
            'default'    => $default,
            'indexable'  => $indexable,
            'locked'     => $locked,
            'override'   => $override,
            'in_sitemap' => !$locked && $sitemap && $indexable,
        ];
    }
}

if (!function_exists('repdoc_seo_page_states')) {
    /** @return array<int,array> every registry page in registry order. */
    function repdoc_seo_page_states(): array
    {
        if (!function_exists('repdoc_page_registry')) {
            return [];
        }
        $out = [];
        foreach (repdoc_page_registry() as $page) {
            $out[] = repdoc_seo_page_state((string) ($page['path'] ?? ''));
        }
        return $out;
    }
}

if (!function_exists('repdoc_seo_page_indexable')) {
    function repdoc_seo_page_indexable(string $path): bool
    {
        return (bool) repdoc_seo_page_state($path)['indexable'];
    }
}

if (!function_exists('repdoc_seo_set_indexable')) {
    /**
     * Store an override. Returns ['ok'=>bool,'error'=>string].
     */
    function repdoc_seo_set_indexable(string $path, bool $indexable): array
    {
        $key = repdoc_seo_path_key($path);
        $map = repdoc_seo_registry_map();
        if (!isset($map[$key])) {
            return ['ok' => false, 'error' => 'Unknown page.'];
        }
        if (!empty($map[$key]['locked'])) {
            return ['ok' => false, 'error' => 'This page is gated; its app layout always sends noindex.'];
        }

        $data = repdoc_seo_controls_load();
        $data['pages'][$key] = ['indexable' => $indexable, 'updated_at' => time()];
        if (!repdoc_seo_controls_save($data)) {
            return ['ok' => false, 'error' => 'Could not write love/seo-controls.json.'];
        }
        return ['ok' => true, 'error' => ''];
    }
}

if (!function_exists('repdoc_seo_reset_indexable')) {
    function repdoc_seo_reset_indexable(string $path): array
    {
        $key  = repdoc_seo_path_key($path);
        $data = repdoc_seo_controls_load();
        if (!isset($data['pages'][$key])) {
            return ['ok' => true, 'error' => ''];
        }
        unset($data['pages'][$key]);
        if (!repdoc_seo_controls_save($data)) {
            return ['ok' => false, 'error' => 'Could not write love/seo-controls.json.'];
        }
        return ['ok' => true, 'error' => ''];
    }
}
