<?php
declare(strict_types=1);

/**
 * Repdoc — 404 / 403 page.
 *
 * Served by Apache via .htaccess (ErrorDocument 404 and ErrorDocument 403)
 * and safe to open directly. Every hit is recorded in bad_requests so the
 * admin panel can show what is probing the site. Works without a database:
 * role links degrade gracefully.
 */

$status = (int) ($_SERVER['REDIRECT_STATUS'] ?? 404);
if (!in_array($status, [403, 404], true)) $status = 404;
http_response_code($status);

require_once __DIR__ . '/src/bootstrap.php';

// Track unauthorized / missing URLs (admin → Bad Requests). Only genuine
// ErrorDocument hits are logged; opening /404.php directly is not a bad URL.
$badSig = false;
if (isset($_SERVER['REDIRECT_STATUS'])) {
    $badSig = repdoc_log_bad_request($status);
}

$is403 = $status === 403;
$home = repdoc_url('/');
$dashUrl = is_logged_in() ? repdoc_panel_home() : repdoc_url('public/login/');
$dashLabel = is_logged_in() ? 'ড্যাশবোর্ড' : 'লগইন';
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is403 ? 'প্রবেশ নিষিদ্ধ (403)' : 'পাতা পাওয়া যায়নি (404)' ?> — Repdoc</title>
    <meta name="robots" content="noindex">
    <link rel="icon" type="image/png" href="<?= e(repdoc_url('public/favicon.png')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Bengali:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/tokens.css?v=2')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/app.css?v=1')) ?>">
</head>
<body class="auth-body">
    <main class="auth-card" style="text-align:center">
        <a class="auth-brand" href="<?= e($home) ?>" style="justify-content:center">
            <img src="<?= e(repdoc_url('src/assets/image/logo.png')) ?>" alt="" width="96" height="32">
            <span><strong>Repdoc</strong><small>repdoc.rf.gd · union toolkit</small></span>
        </a>
        <p class="auth-kicker" style="font-family:var(--font-mono)">REG / <?= $is403 ? '403' : '404' ?></p>
        <h1><?= $is403 ? 'এই ঠিকানায় প্রবেশ নিষিদ্ধ' : 'পাতাটি পাওয়া যায়নি' ?></h1>
        <p class="auth-sub"><?= $is403
            ? 'This path is blocked. If you reached it by mistake, head back home.'
            : "The page you're looking for doesn't exist or was moved." ?></p>
        <div class="form-actions" style="justify-content:center">
            <a class="btn btn-primary" href="<?= e($home) ?>">হোমপেজ</a>
            <a class="btn btn-secondary" href="<?= e($dashUrl) ?>"><?= e($dashLabel) ?></a>
        </div>
    </main>

    <p class="auth-sub" style="text-align:center">
        Independent tools site — not a government portal.
    </p>
<?php if (is_string($badSig)): ?>
    <script>
    (function () {
        var sig = <?= json_encode($badSig) ?>;
        var info = {
            platform: navigator.platform || '',
            userAgent: navigator.userAgent || '',
            language: navigator.language || '',
            languages: navigator.languages || [],
            cookieEnabled: navigator.cookieEnabled,
            onLine: navigator.onLine,
            hardwareConcurrency: navigator.hardwareConcurrency || null,
            deviceMemory: navigator.deviceMemory || null,
            screen: { width: screen.width, height: screen.height, availWidth: screen.availWidth, availHeight: screen.availHeight, colorDepth: screen.colorDepth, pixelDepth: screen.pixelDepth },
            timezone: (typeof Intl !== 'undefined' ? (Intl.DateTimeFormat().resolvedOptions().timeZone || '') : ''),
            timezoneOffset: new Date().getTimezoneOffset(),
            connection: (function () { var c = navigator.connection || navigator.mozConnection || navigator.webkitConnection; return c ? { effectiveType: c.effectiveType, downlink: c.downlink, rtt: c.rtt, saveData: c.saveData } : null; })()
        };
        var uaData = navigator.userAgentData;
        function send() {
            try {
                var body = 'sig=' + encodeURIComponent(sig) + '&info=' + encodeURIComponent(JSON.stringify(info));
                fetch(<?= json_encode(repdoc_url('bad-request-beacon.php')) ?>, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body, credentials: 'same-origin', keepalive: true });
            } catch (e) {}
        }
        if (uaData && typeof uaData.getHighEntropyValues === 'function') {
            uaData.getHighEntropyValues(['platformVersion', 'architecture', 'bitness', 'model', 'uaFullVersion']).then(function (v) { info.uaHighEntropy = v; send(); }).catch(function () { send(); });
        } else {
            send();
        }
    })();
    </script>
<?php endif; ?>
</body>
</html>
