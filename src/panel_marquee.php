<?php
declare(strict_types=1);

/**
 * Repdoc — entrepreneur panel marquee.
 *
 * A single admin-managed announcement shown under the UDC panel header.
 * Stored in love/panel_marquee.json (web-denied), read by
 * Entrepreneur/panel/includes/layout.php.
 */

if (!function_exists('repdoc_marquee_file')) {
    function repdoc_marquee_file(): string
    {
        return dirname(__DIR__) . '/love/panel_marquee.json';
    }
}

if (!function_exists('repdoc_marquee_defaults')) {
    function repdoc_marquee_defaults(): array
    {
        return [
            'enabled'    => false,
            'text'       => '',
            'link'       => '',
            'link_label' => '',
            'speed'      => 30,
        ];
    }
}

if (!function_exists('repdoc_marquee_text')) {
    function repdoc_marquee_text(string $value, int $max = 500): string
    {
        $value = str_replace(["\r\n", "\r", "\n", "\t"], ' ', trim($value));
        $value = strip_tags($value);
        $value = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
        $value = (string) preg_replace('/\s+/u', ' ', $value);
        return trim(mb_substr(trim($value), 0, $max, 'UTF-8'));
    }
}

if (!function_exists('repdoc_marquee_url')) {
    /** Absolute http(s) URL or a site-root-relative path; anything else is rejected. */
    function repdoc_marquee_url(string $value): string
    {
        $value = trim($value);
        if ($value === '') return '';
        if (preg_match('#^https?://#i', $value)) return mb_substr($value, 0, 500, 'UTF-8');
        if (str_starts_with($value, '/') && !str_starts_with($value, '//')) {
            return mb_substr($value, 0, 500, 'UTF-8');
        }
        return '';
    }
}

if (!function_exists('repdoc_marquee_settings')) {
    /** Stored settings merged over defaults (never throws). */
    function repdoc_marquee_settings(): array
    {
        $out  = repdoc_marquee_defaults();
        $file = repdoc_marquee_file();
        if (!is_file($file)) return $out;

        $decoded = json_decode((string) @file_get_contents($file), true);
        if (!is_array($decoded)) return $out;

        $out['enabled']    = !empty($decoded['enabled']);
        $out['text']       = repdoc_marquee_text((string) ($decoded['text'] ?? ''));
        $out['link']       = repdoc_marquee_url((string) ($decoded['link'] ?? ''));
        $out['link_label'] = repdoc_marquee_text((string) ($decoded['link_label'] ?? ''), 80);
        $out['speed']      = max(10, min(120, (int) ($decoded['speed'] ?? 30)));

        if ($out['text'] === '') $out['enabled'] = false;
        return $out;
    }
}

if (!function_exists('repdoc_marquee_save')) {
    /**
     * @return array{ok:bool,enabled:bool}
     */
    function repdoc_marquee_save(array $input): array
    {
        $text = repdoc_marquee_text((string) ($input['text'] ?? ''));
        $data = [
            'enabled'    => !empty($input['enabled']) && $text !== '',
            'text'       => $text,
            'link'       => repdoc_marquee_url((string) ($input['link'] ?? '')),
            'link_label' => repdoc_marquee_text((string) ($input['link_label'] ?? ''), 80),
            'speed'      => max(10, min(120, (int) ($input['speed'] ?? 30))),
        ];

        $file = repdoc_marquee_file();
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($json === false) return ['ok' => false, 'enabled' => (bool) $data['enabled']];

        $ok = @file_put_contents($file, $json . "\n") !== false;
        return ['ok' => $ok, 'enabled' => (bool) $data['enabled']];
    }
}
