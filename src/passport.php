<?php
declare(strict_types=1);

/**
 * Repdoc — passport photo tool settings.
 *
 * Stored in love/passport_settings.json and read by the tool itself
 * (tools/passport/lib.php) and by the admin page (admin/passport.php):
 *   prompt           default AI prompt offered on the tool page
 *   access           'udc'  → UDC operators + admins
 *                    'admin' → administrators only
 *   retention_hours  how long generated/uploaded copies are kept
 */

if (!function_exists('repdoc_passport_settings_file')) {
    function repdoc_passport_settings_file(): string
    {
        return dirname(__DIR__) . '/love/passport_settings.json';
    }
}

if (!function_exists('repdoc_passport_builtin_prompt')) {
    /** The original fixed identity-preserving prompt (used until an admin changes it). */
    function repdoc_passport_builtin_prompt(): string
    {
        return 'Edit this photo into a clean professional passport-size photo. '
            . 'Preserve my exact facial identity, face structure, skin tone, eyes, nose, lips, beard, and natural expression without any alteration. '
            . 'Keep the head position straight and realistic. '
            . 'Use a clean white background, formal clothing, even studio lighting, natural skin texture, sharp details, and realistic proportions. '
            . 'Do not beautify or reshape my face.';
    }
}

if (!function_exists('repdoc_passport_text')) {
    function repdoc_passport_text(string $value, int $max = 3000): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", trim($value));
        $value = strip_tags($value);
        $value = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
        return trim(mb_substr($value, 0, $max, 'UTF-8'));
    }
}

if (!function_exists('repdoc_passport_access_options')) {
    function repdoc_passport_access_options(): array
    {
        return [
            'udc'   => 'UDC অপারেটর + অ্যাডমিন',
            'admin' => 'শুধু অ্যাডমিন',
        ];
    }
}

if (!function_exists('repdoc_passport_defaults')) {
    function repdoc_passport_defaults(): array
    {
        return [
            'prompt'          => repdoc_passport_builtin_prompt(),
            'access'          => 'udc',
            'retention_hours' => 24,
        ];
    }
}

if (!function_exists('repdoc_passport_settings')) {
    /** Stored settings merged over the defaults (never throws). */
    function repdoc_passport_settings(): array
    {
        $out  = repdoc_passport_defaults();
        $file = repdoc_passport_settings_file();
        if (!is_file($file)) return $out;

        $decoded = json_decode((string) @file_get_contents($file), true);
        if (!is_array($decoded)) return $out;

        $prompt = repdoc_passport_text((string) ($decoded['prompt'] ?? ''), 3000);
        if ($prompt !== '') $out['prompt'] = $prompt;

        $access = (string) ($decoded['access'] ?? '');
        if (isset(repdoc_passport_access_options()[$access])) $out['access'] = $access;

        $hours = (int) ($decoded['retention_hours'] ?? 0);
        if ($hours >= 1 && $hours <= 168) $out['retention_hours'] = $hours;

        return $out;
    }
}

if (!function_exists('repdoc_passport_prompt')) {
    function repdoc_passport_prompt(): string
    {
        return (string) repdoc_passport_settings()['prompt'];
    }
}

if (!function_exists('repdoc_passport_retention_seconds')) {
    function repdoc_passport_retention_seconds(): int
    {
        return max(1, (int) repdoc_passport_settings()['retention_hours']) * 3600;
    }
}

if (!function_exists('repdoc_passport_save')) {
    /**
     * Normalize + persist the settings (atomic write, same pattern as links).
     *
     * @return array{ok:bool}
     */
    function repdoc_passport_save(array $input): array
    {
        $options = repdoc_passport_access_options();

        $prompt  = repdoc_passport_text((string) ($input['prompt'] ?? ''), 3000);
        if ($prompt === '') $prompt = repdoc_passport_builtin_prompt();
        if (mb_strlen($prompt, 'UTF-8') < 40) $prompt = repdoc_passport_builtin_prompt();

        $access = (string) ($input['access'] ?? 'udc');
        if (!isset($options[$access])) $access = 'udc';

        $hours = (int) ($input['retention_hours'] ?? 24);
        $hours = max(1, min(168, $hours));

        $payload = [
            'prompt'          => $prompt,
            'access'          => $access,
            'retention_hours' => $hours,
            'updated_at'      => date('Y-m-d H:i:s'),
        ];
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) return ['ok' => false];

        $file = repdoc_passport_settings_file();
        $dir  = dirname($file);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) return ['ok' => false];

        $tmp = $file . '.tmp';
        if (@file_put_contents($tmp, $json . "\n") === false) return ['ok' => false];
        if (!@rename($tmp, $file)) {
            @unlink($tmp);
            return ['ok' => false];
        }
        @chmod($file, 0644);
        return ['ok' => true];
    }
}

if (!function_exists('repdoc_passport_provider_status')) {
    /**
     * Which image providers are configured (never returns key values).
     *
     * @return array{mode:string,pollinations:bool,gemini:bool}
     */
    function repdoc_passport_provider_status(): array
    {
        return [
            'mode'         => strtolower(trim(repdoc_env('PASSPORT_IMAGE_PROVIDER', 'auto'))) ?: 'auto',
            'pollinations' => repdoc_env('POLLINATIONS_API_KEY') !== '',
            'gemini'       => repdoc_env('GEMINI_API_KEY') !== '',
        ];
    }
}
