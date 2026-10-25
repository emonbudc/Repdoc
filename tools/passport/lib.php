<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli' && !defined('REPDOC_PASSPORT')) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Not found.';
    exit;
}

/**
 * Repdoc — passport size photo tool.
 *
 * UDC operators and administrators upload a portrait. A free image model
 * (Pollinations / FLUX.1 Kontext by default, Gemini as an optional fallback)
 * turns it into a studio-style passport photo with the identity untouched.
 * Generated files are kept in the protected love/passport/<user>/ directory
 * (love/.htaccess denies direct access) and streamed back through file.php.
 *
 * Environment (all optional — the tool degrades to a plain crop without them):
 *   POLLINATIONS_API_KEY          free key from https://enter.pollinations.ai/keys
 *   PASSPORT_IMAGE_PROVIDER       auto | basic | pollinations | gemini
 *   PASSPORT_POLLINATIONS_MODEL   default black-forest-labs/flux.1-kontext-pro
 *   PASSPORT_POLLINATIONS_SIZE    default 896x1152
 *   PASSPORT_GEMINI_MODEL         default gemini-3.1-flash-image
 */

if (!defined('REPDOC_ROOT')) {
    define('REPDOC_ROOT', dirname(__DIR__, 2));
}
require_once REPDOC_ROOT . '/src/bootstrap.php';

const PASSPORT_MAX_BYTES     = 8388608; // 8 MB upload limit
const PASSPORT_MAX_DIMENSION = 6000;    // reject absurd camera dumps
const PASSPORT_RETENTION     = 86400;   // private copies are deleted after 24h

/** The fixed identity-preserving prompt used by default. */
function passport_default_prompt(): string
{
    return 'Edit this photo into a clean professional passport-size photo. '
        . 'Preserve my exact facial identity, face structure, skin tone, eyes, nose, lips, beard, and natural expression without any alteration. '
        . 'Keep the head position straight and realistic. '
        . 'Use a clean white background, formal clothing, even studio lighting, natural skin texture, sharp details, and realistic proportions. '
        . 'Do not beautify or reshape my face.';
}

/** Only approved UDC operators and administrators may use this tool. */
function passport_access_allowed(): bool
{
    return is_udc() || is_admin();
}

/** Absolute private directory for one user's passport files. */
function passport_user_dir(int $userId): string
{
    $dir = REPDOC_LOVE . DIRECTORY_SEPARATOR . 'passport' . DIRECTORY_SEPARATOR . $userId;
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir;
}

/** Drop old private copies so shared hosting stays small. */
function passport_cleanup(int $userId, int $maxAge = PASSPORT_RETENTION): void
{
    $dir = passport_user_dir($userId);
    $cutoff = time() - max(60, $maxAge);

    foreach (['pass_*', 'src_*'] as $pattern) {
        $files = @glob($dir . DIRECTORY_SEPARATOR . $pattern);
        if (!is_array($files)) continue;
        foreach ($files as $file) {
            if (is_file($file) && @filemtime($file) < $cutoff) {
                @unlink($file);
            }
        }
    }
}

/**
 * Validate + move an uploaded portrait into the private directory.
 *
 * @return array{ok:bool,error?:string,path?:string,mime?:string,width?:int,height?:int,ext?:string}
 */
function passport_save_upload(array $file, int $userId): array
{
    $safe = repdoc_validate_image_upload($file, PASSPORT_MAX_BYTES);
    if ($safe === '') {
        return ['ok' => false, 'error' => 'ছবিটি JPEG বা PNG হতে হবে এবং ৮ MB-এর কম হতে হবে।'];
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_file($tmp)) {
        return ['ok' => false, 'error' => 'আপলোড করা ছবিটি পাওয়া যায়নি। আবার চেষ্টা করুন।'];
    }

    $info = @getimagesize($tmp);
    if ($info === false) {
        return ['ok' => false, 'error' => 'ছবিটি পড়া যায়নি। অন্য একটি ছবি দিন।'];
    }

    $width  = (int) ($info[0] ?? 0);
    $height = (int) ($info[1] ?? 0);
    if ($width < 200 || $height < 200) {
        return ['ok' => false, 'error' => 'ছবির রেজোলিউশন কমপক্ষে ২০০×২০০ পিক্সেল হতে হবে।'];
    }
    if ($width > PASSPORT_MAX_DIMENSION || $height > PASSPORT_MAX_DIMENSION) {
        return ['ok' => false, 'error' => 'ছবিটি অনেক বড় (সর্বোচ্চ ৬০০০ পিক্সেল)। ছোট করে আবার চেষ্টা করুন।'];
    }

    $ext  = strtolower((string) pathinfo($safe, PATHINFO_EXTENSION)) === 'png' ? 'png' : 'jpg';
    $mime = $ext === 'png' ? 'image/png' : 'image/jpeg';
    $dest = passport_user_dir($userId) . DIRECTORY_SEPARATOR . 'src_' . bin2hex(random_bytes(12)) . '.' . $ext;

    $moved = @move_uploaded_file($tmp, $dest);
    if (!$moved) {
        // CLI / test harness path; a real browser upload always takes move_uploaded_file().
        $moved = @copy($tmp, $dest);
    }
    if (!$moved) {
        return ['ok' => false, 'error' => 'ছবি সংরক্ষণ করা যায়নি। আবার চেষ্টা করুন।'];
    }
    @chmod($dest, 0644);

    return ['ok' => true, 'path' => $dest, 'mime' => $mime, 'width' => $width, 'height' => $height, 'ext' => $ext];
}

/** Provider order for this request. Empty = plain crop (no AI key configured). */
function passport_provider_chain(): array
{
    $forced = strtolower(trim(repdoc_env('PASSPORT_IMAGE_PROVIDER', 'auto')));

    if ($forced === 'basic') return [];
    if ($forced === 'pollinations' || $forced === 'gemini') return [$forced];

    $chain = [];
    if (repdoc_env('POLLINATIONS_API_KEY') !== '') $chain[] = 'pollinations';
    if (repdoc_env('GEMINI_API_KEY') !== '')     $chain[] = 'gemini';
    return $chain;
}

function passport_provider_label(string $provider): string
{
    return match ($provider) {
        'pollinations' => 'Pollinations · FLUX.1 Kontext',
        'gemini'       => 'Google Gemini Image',
        default        => 'Basic crop (no AI key)',
    };
}

/**
 * Run the image model. Returns raw image bytes, never a temp path.
 *
 * @return array{ok:bool,provider?:string,model?:string,binary?:string,mime?:string,error?:string}
 */
function passport_generate(string $srcPath, string $mime, string $prompt): array
{
    $chain  = passport_provider_chain();
    $errors = [];
    $seed   = (string) random_int(1, 999999999);

    foreach ($chain as $provider) {
        $out = $provider === 'pollinations'
            ? passport_provider_pollinations($srcPath, $mime, $prompt, $seed)
            : passport_provider_gemini($srcPath, $mime, $prompt);

        if (!empty($out['ok'])) {
            return $out;
        }

        $errors[] = passport_provider_label($provider) . ': ' . (string) ($out['error'] ?? 'failed');

        // A forced provider is the operator's choice; auto mode tries the next one.
        if (strtolower(trim(repdoc_env('PASSPORT_IMAGE_PROVIDER', 'auto'))) !== 'auto') {
            break;
        }
    }

    return [
        'ok'       => false,
        'error'    => $errors ? implode(' | ', $errors) : 'AI মডেল কনফিগার করা নেই।',
        'providers' => $chain,
    ];
}

/** Small cURL wrapper with sane timeouts. */
function passport_curl(string $url, array $options = []): array
{
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'status' => 0, 'body' => '', 'type' => '', 'error' => 'cURL unavailable'];
    }

    $defaults = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 3,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT        => 90,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT      => 'Repdoc-PassportPhoto/1.0',
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, $options + $defaults);
    $body   = curl_exec($ch);
    $err    = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $type   = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

    if ($body === false) {
        return ['ok' => false, 'status' => $status, 'body' => '', 'type' => $type, 'error' => $err !== '' ? $err : 'request failed'];
    }
    return ['ok' => true, 'status' => $status, 'body' => (string) $body, 'type' => $type, 'error' => ''];
}

/** Pull the first image part out of a Gemini generateContent response. */
function passport_extract_gemini_image(array $json): ?array
{
    $candidates = $json['candidates'] ?? [];
    if (!is_array($candidates)) return null;

    foreach ($candidates as $candidate) {
        $parts = $candidate['content']['parts'] ?? [];
        if (!is_array($parts)) continue;
        foreach ($parts as $part) {
            $inline = $part['inlineData'] ?? $part['inline_data'] ?? null;
            if (!is_array($inline)) continue;
            $data = (string) ($inline['data'] ?? '');
            if ($data === '') continue;
            $binary = base64_decode($data, true);
            if ($binary === false || $binary === '') continue;
            $mime = (string) ($inline['mimeType'] ?? $inline['mime_type'] ?? 'image/png');
            return ['binary' => $binary, 'mime' => $mime];
        }
    }
    return null;
}

/** Google Gemini image editing (optional paid fallback; key already in repo env). */
function passport_provider_gemini(string $srcPath, string $mime, string $prompt): array
{
    $key = repdoc_env('GEMINI_API_KEY');
    if ($key === '') {
        return ['ok' => false, 'provider' => 'gemini', 'error' => 'GEMINI_API_KEY সেট করা নেই।'];
    }

    $raw = @file_get_contents($srcPath);
    if ($raw === false) {
        return ['ok' => false, 'provider' => 'gemini', 'error' => 'উৎস ছবি পড়া যায়নি।'];
    }

    $forced = trim(repdoc_env('PASSPORT_GEMINI_MODEL'));
    $models = $forced !== '' ? [$forced] : ['gemini-3.1-flash-image'];

    $payload = [
        'contents' => [[
            'role'  => 'user',
            'parts' => [
                ['text' => $prompt],
                ['inline_data' => ['mime_type' => $mime, 'data' => base64_encode($raw)]],
            ],
        ]],
        'generationConfig' => [
            'responseModalities' => ['TEXT', 'IMAGE'],
            'imageConfig'        => ['aspectRatio' => '3:4'],
        ],
    ];

    $lastError = '';
    foreach ($models as $model) {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
            . rawurlencode($model) . ':generateContent';

        $res = passport_curl($url, [
            CURLOPT_POST       => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'x-goog-api-key: ' . $key],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);

        if (!$res['ok']) {
            $lastError = $res['error'];
            continue;
        }

        // Older endpoints reject imageConfig; retry once without it.
        if ($res['status'] === 400 && stripos($res['body'], 'imageConfig') !== false) {
            unset($payload['generationConfig']['imageConfig']);
            $res = passport_curl($url, [
                CURLOPT_POST       => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'x-goog-api-key: ' . $key],
                CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ]);
        }

        $json = json_decode($res['body'], true);

        if ($res['status'] === 200 && is_array($json)) {
            $image = passport_extract_gemini_image($json);
            if ($image !== null) {
                return ['ok' => true, 'provider' => 'gemini', 'model' => $model] + $image;
            }
            $lastError = 'মডেল কোনো ছবি ফেরত দেয়নি।';
        } else {
            $msg = is_array($json) ? (string) ($json['error']['message'] ?? '') : '';
            $lastError = $msg !== '' ? $msg : ('HTTP ' . $res['status']);
        }

        if (in_array($res['status'], [401, 403, 429], true)) break;
    }

    return ['ok' => false, 'provider' => 'gemini', 'error' => $lastError !== '' ? $lastError : 'Gemini image generation ব্যর্থ হয়েছে।'];
}

/** Pollinations image edits (free model; needs a free Pollinations key). */
function passport_provider_pollinations(string $srcPath, string $mime, string $prompt, string $seed): array
{
    $key = repdoc_env('POLLINATIONS_API_KEY');
    if ($key === '') {
        return ['ok' => false, 'provider' => 'pollinations', 'error' => 'POLLINATIONS_API_KEY সেট করা নেই।'];
    }
    if (!class_exists('CURLFile')) {
        return ['ok' => false, 'provider' => 'pollinations', 'error' => 'cURL file upload unavailable.'];
    }

    $model = trim(repdoc_env('PASSPORT_POLLINATIONS_MODEL')) ?: 'black-forest-labs/flux.1-kontext-pro';
    $size  = trim(repdoc_env('PASSPORT_POLLINATIONS_SIZE')) ?: '896x1152';
    $url   = 'https://gen.pollinations.ai/v1/images/edits';

    $post = [
        'model'  => $model,
        'prompt' => $prompt,
        'seed'   => $seed,
        'size'   => $size,
        'image'  => new CURLFile($srcPath, $mime, 'source.' . ($mime === 'image/png' ? 'png' : 'jpg')),
    ];

    $res = passport_curl($url, [
        CURLOPT_POST       => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $key, 'Accept: application/json, image/*'],
        CURLOPT_POSTFIELDS => $post,
    ]);

    if (!$res['ok']) {
        return ['ok' => false, 'provider' => 'pollinations', 'error' => $res['error']];
    }

    // Some deployments reject explicit dimensions; retry without them.
    if ($res['status'] === 400 && stripos($res['body'], 'size') !== false) {
        unset($post['size']);
        $res = passport_curl($url, [
            CURLOPT_POST       => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $key, 'Accept: application/json, image/*'],
            CURLOPT_POSTFIELDS => $post,
        ]);
    }

    if ($res['status'] >= 200 && $res['status'] < 300) {
        if (str_contains(strtolower($res['type']), 'image/')) {
            return [
                'ok'       => true,
                'provider' => 'pollinations',
                'model'    => $model,
                'binary'   => $res['body'],
                'mime'     => str_contains(strtolower($res['type']), 'png') ? 'image/png' : 'image/jpeg',
            ];
        }

        $json = json_decode($res['body'], true);
        if (is_array($json)) {
            $b64 = (string) ($json['data'][0]['b64_json'] ?? $json['b64_json'] ?? '');
            if ($b64 !== '') {
                $binary = base64_decode($b64, true);
                if ($binary !== false && $binary !== '') {
                    return [
                        'ok'       => true,
                        'provider' => 'pollinations',
                        'model'    => $model,
                        'binary'   => $binary,
                        'mime'     => 'image/png',
                    ];
                }
            }

            $imageUrl = (string) (
                $json['data'][0]['url']
                ?? $json['images'][0]['url']
                ?? $json['images'][0]
                ?? $json['url']
                ?? ''
            );
            if ($imageUrl !== '' && !str_starts_with($imageUrl, 'http')) {
                $imageUrl = 'https://gen.pollinations.ai' . (str_starts_with($imageUrl, '/') ? '' : '/') . $imageUrl;
            }
            if ($imageUrl !== '' && str_starts_with($imageUrl, 'http')) {
                $remoteOpts = [];
                $remoteHost = strtolower((string) parse_url($imageUrl, PHP_URL_HOST));
                if ($remoteHost === 'gen.pollinations.ai') {
                    $remoteOpts[CURLOPT_HTTPHEADER] = ['Authorization: Bearer ' . $key];
                }
                $remote = passport_curl($imageUrl, $remoteOpts);
                if ($remote['ok'] && $remote['status'] >= 200 && $remote['status'] < 300 && $remote['body'] !== '') {
                    return [
                        'ok'       => true,
                        'provider' => 'pollinations',
                        'model'    => $model,
                        'binary'   => $remote['body'],
                        'mime'     => str_contains(strtolower($remote['type']), 'png') ? 'image/png' : 'image/jpeg',
                    ];
                }
                return ['ok' => false, 'provider' => 'pollinations', 'error' => 'Generated image URL could not be fetched.'];
            }
        }
    }

    $json = json_decode($res['body'], true);
    $msg  = is_array($json)
        ? (string) ($json['error']['message'] ?? (is_string($json['error'] ?? null) ? $json['error'] : ''))
        : '';
    return [
        'ok'       => false,
        'provider' => 'pollinations',
        'error'    => $msg !== '' ? $msg : ('HTTP ' . $res['status']),
    ];
}

/**
 * Verify + store model output in the user's private directory.
 *
 * @return array{ok:bool,name?:string,path?:string,mime?:string,width?:int,height?:int,error?:string}
 */
function passport_store_generated(int $userId, string $binary, string $mime): array
{
    if ($binary === '') {
        return ['ok' => false, 'error' => 'AI মডেল খালি ছবি ফেরত দিয়েছে।'];
    }

    $info = @getimagesizefromstring($binary);
    if ($info === false) {
        return ['ok' => false, 'error' => 'AI মডেল বৈধ ছবি ফেরত দেয়নি। আবার চেষ্টা করুন।'];
    }

    $width  = (int) ($info[0] ?? 0);
    $height = (int) ($info[1] ?? 0);
    if ($width < 256 || $height < 256) {
        return ['ok' => false, 'error' => 'জেনারেট করা ছবির রেজোলিউশন কম — আবার চেষ্টা করুন।'];
    }

    $type = (int) ($info[2] ?? 0);
    $ext  = match ($type) {
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_WEBP => 'webp',
        default        => '',
    };
    $mime = match ($ext) {
        'png'  => 'image/png',
        'webp' => 'image/webp',
        default => 'image/jpeg',
    };
    if ($ext === '') {
        return ['ok' => false, 'error' => 'ছবির ফরম্যাট সমর্থিত নয় — JPEG/PNG/WebP প্রয়োজন।'];
    }

    $name = 'pass_' . bin2hex(random_bytes(16)) . '.' . $ext;
    $path = passport_user_dir($userId) . DIRECTORY_SEPARATOR . $name;

    if (@file_put_contents($path, $binary) === false) {
        return ['ok' => false, 'error' => 'ছবি সংরক্ষণ করা যায়নি। ডিস্কে জায়গা আছে কিনা দেখুন।'];
    }
    @chmod($path, 0644);

    return [
        'ok'     => true,
        'name'   => $name,
        'path'   => $path,
        'mime'   => $mime,
        'width'  => $width,
        'height' => $height,
    ];
}

/** Resolve a stored passport file safely (blocks path traversal). */
function passport_file_path(int $userId, string $name): string
{
    if (!preg_match('/^pass_[a-f0-9]{32}\.(jpg|png|webp)$/', $name)) {
        return '';
    }

    $dir  = passport_user_dir($userId);
    $path = $dir . DIRECTORY_SEPARATOR . $name;
    $real = realpath($path);
    $base = realpath($dir);

    if ($real === false || $base === false) return '';
    if (!str_starts_with($real, $base . DIRECTORY_SEPARATOR)) return '';
    if (!is_file($real) || !is_readable($real)) return '';

    return $real;
}

/** Delete one of this user's generated files. */
function passport_delete(int $userId, string $name): bool
{
    $path = passport_file_path($userId, $name);
    if ($path === '') return false;
    return @unlink($path);
}
