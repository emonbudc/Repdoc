<?php
declare(strict_types=1);

/**
 * Repdoc — passport size photo tool (UDC + admin).
 *
 * Upload a portrait, let the configured free image model turn it into a clean
 * white-background passport photo, then crop/print at the exact 35 × 45 mm
 * size. Generated files live under love/passport/<user>/ and are served by
 * file.php — never exposed as direct web paths.
 */

define('REPDOC_PASSPORT', true);
require_once __DIR__ . '/lib.php';

require_login();
if (!passport_access_allowed()) {
    redirect(repdoc_url('Entrepreneur/apply/'));
}
repdoc_security_headers();

$me      = current_user();
$userId  = (int) $me['id'];
$price   = tool_price('passport_photo');
$balance = user_balance($userId);

passport_cleanup($userId);

$error       = '';
$notice      = '';
$noticeType  = 'info';
$promptValue = passport_default_prompt();
$result      = null;

/* ────────────────────────────── POST actions ───────────────────────────── */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'নিরাপত্তা টোকেন মিলছে না — পেজটি রিফ্রেশ করে আবার চেষ্টা করুন।';
    } elseif ($action === 'delete') {
        $name = (string) ($_POST['file'] ?? '');
        if (passport_delete($userId, $name)) {
            $current = $_SESSION['passport_result']['file'] ?? '';
            if ($current === $name) unset($_SESSION['passport_result']);
            flash_set('ok', 'ছবিটি মুছে ফেলা হয়েছে।');
        } else {
            flash_set('error', 'ছবিটি পাওয়া যায়নি বা মুছে ফেলা যায়নি।');
        }
        redirect('index.php');
    } elseif ($action === 'generate') {
        $prompt = trim((string) ($_POST['prompt'] ?? ''));
        $prompt = str_replace("\0", '', $prompt);
        if ($prompt === '') {
            $prompt = passport_default_prompt();
        }
        if (function_exists('mb_strlen') ? mb_strlen($prompt, 'UTF-8') > 3000 : strlen($prompt) > 3000) {
            $prompt = function_exists('mb_substr')
                ? mb_substr($prompt, 0, 3000, 'UTF-8')
                : substr($prompt, 0, 3000);
        }
        $promptValue = $prompt;

        if (empty($_FILES['photo']) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
            $error = 'আপলোডটি সার্ভারের সীমার চেয়ে বড়। ছোট একটি ছবি দিয়ে আবার চেষ্টা করুন।';
        } elseif (!is_admin() && $price > 0 && $balance < $price) {
            $error = 'ব্যালেন্স পর্যাপ্ত নয় — প্রয়োজন ' . number_format($price) . ', আছে ' . number_format($balance) . '।';
        } else {
            $saved = passport_save_upload($_FILES['photo'] ?? [], $userId);

            if (!$saved['ok']) {
                $error = (string) ($saved['error'] ?? 'ছবিটি নেওয়া যায়নি।');
            } else {
                $chain = passport_provider_chain();

                @set_time_limit(300);

                try {
                    if ($chain === []) {
                        // No AI key: still give a usable passport crop from the original.
                        $binary = @file_get_contents($saved['path']);
                        $gen = $binary === false
                            ? ['ok' => false, 'error' => 'উৎস ছবি পড়া যায়নি।']
                            : ['ok' => true, 'provider' => 'basic', 'model' => 'basic', 'binary' => $binary, 'mime' => $saved['mime']];
                    } else {
                        $gen = passport_generate($saved['path'], $saved['mime'], $prompt);
                    }

                    if (empty($gen['ok'])) {
                        $error = 'ছবি তৈরি করা যায়নি। ' . (string) ($gen['error'] ?? '');
                    } else {
                        $stored = passport_store_generated($userId, (string) $gen['binary'], (string) ($gen['mime'] ?? 'image/jpeg'));

                        if (!$stored['ok']) {
                            $error = (string) ($stored['error'] ?? 'ছবি সংরক্ষণ করা যায়নি।');
                        } else {
                            $ref = 'passport_' . bin2hex(random_bytes(8));
                            $charge = charge_tool('passport_photo', $ref);

                            if (!$charge['ok']) {
                                passport_delete($userId, (string) $stored['name']);
                                $error = (string) ($charge['message'] ?? 'চার্জ করা যায়নি।');
                            } else {
                                $provider = (string) ($gen['provider'] ?? 'basic');
                                $model    = (string) ($gen['model'] ?? $provider);

                                log_usage($userId, 'passport_photo', 'ok', $provider . '/' . $model . ' ' . $stored['width'] . 'x' . $stored['height']);

                                $_SESSION['passport_result'] = [
                                    'file'       => (string) $stored['name'],
                                    'provider'   => $provider,
                                    'model'      => $model,
                                    'width'      => (int) $stored['width'],
                                    'height'     => (int) $stored['height'],
                                    'charged'    => (int) ($charge['charged'] ?? 0),
                                    'created_at' => time(),
                                ];

                                if ($provider === 'basic') {
                                    flash_set('warn', 'AI মডেল কনফিগার নেই — মূল ছবি থেকেই পাসপোর্ট ক্রপ তৈরি হয়েছে।');
                                } else {
                                    flash_set('ok', 'পাসপোর্ট ছবি তৈরি হয়েছে। এখন ক্রপ ঠিক করে ডাউনলোড করুন।');
                                }

                                // redirect() exits, so clean the source before leaving.
                                if (!empty($saved['path']) && is_file($saved['path'])) {
                                    @unlink($saved['path']);
                                }
                                redirect('index.php?done=1');
                            }
                        }
                    }
                } catch (Throwable $e) {
                    $error = 'অপ্রত্যাশিত সমস্যা: ' . $e->getMessage();
                } finally {
                    if (!empty($saved['path']) && is_file($saved['path'])) {
                        @unlink($saved['path']);
                    }
                }
            }
        }
    }
}

/* ────────────────────────────── read state ─────────────────────────────── */

if (!empty($_SESSION['passport_result']) && is_array($_SESSION['passport_result'])) {
    $candidate = $_SESSION['passport_result'];
    if (!empty($candidate['file']) && passport_file_path($userId, (string) $candidate['file']) !== '') {
        $result = $candidate;
    } else {
        unset($_SESSION['passport_result']);
    }
}

$flash = flash_get();
if ($flash !== null && $error === '') {
    $notice     = (string) ($flash['message'] ?? '');
    $noticeType = match ((string) ($flash['type'] ?? '')) {
        'ok'    => 'success',
        'warn'  => 'warning',
        'error' => 'error',
        default => 'info',
    };
    if ($noticeType === 'error') {
        $error  = $notice;
        $notice = '';
    }
}

$chain   = passport_provider_chain();
$aiReady = $chain !== [];
$hasKey  = false;
foreach ($chain as $provider) {
    if ($provider === 'pollinations' && repdoc_env('POLLINATIONS_API_KEY') !== '') $hasKey = true;
    if ($provider === 'gemini' && repdoc_env('GEMINI_API_KEY') !== '')             $hasKey = true;
}

$panelUrl   = repdoc_panel_home();
$logoUrl    = repdoc_url('src/assets/image/logo.png');
$resultUrl  = $result !== null ? 'file.php?f=' . rawurlencode((string) $result['file']) : '';
$resultMeta = $result !== null ? passport_provider_label((string) $result['provider']) : '';
$resultModel = $result !== null ? (string) ($result['model'] ?? '') : '';
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#F8FAFC">
    <title>পাসপোর্ট সাইজ ছবি — Repdoc</title>
    <link rel="icon" type="image/png" href="<?= e(repdoc_url('public/favicon.png')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Bengali:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/tokens.css?v=1')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/app.css?v=1')) ?>">
    <style>
        .pp-page {
            min-height: 100vh;
            background:
                radial-gradient(900px 420px at 12% -10%, var(--primary-light), transparent 60%),
                var(--bg-base);
            padding: 24px 0 64px;
        }
        .pp-shell { width: min(1120px, calc(100% - 28px)); margin: 0 auto; }
        .pp-top {
            display: flex; align-items: center; justify-content: space-between;
            gap: 14px; margin-bottom: 20px;
        }
        .pp-brand { display: flex; align-items: center; gap: 11px; min-width: 0; }
        .pp-brand img { height: 36px; width: auto; }
        .pp-brand strong { display: block; font-size: 1rem; letter-spacing: -.01em; }
        .pp-brand small { display: block; color: var(--text-muted); font-size: .76rem; }
        .pp-hero { margin-bottom: 18px; }
        .pp-kicker {
            display: inline-block; margin-bottom: 8px; padding: 4px 10px;
            border-radius: var(--radius-full); background: var(--primary-light);
            color: var(--primary); font-size: .7rem; font-weight: 800;
            letter-spacing: .08em; text-transform: uppercase;
        }
        .pp-hero h1 { font-size: clamp(1.5rem, 3.2vw, 2.2rem); margin-bottom: 7px; }
        .pp-hero p { max-width: 76ch; color: var(--text-muted); font-size: .92rem; }
        .pp-stat {
            display: inline-flex; flex-wrap: wrap; gap: 8px 16px; margin-top: 12px;
            padding: 9px 14px; border: 1px solid var(--border-color);
            background: var(--bg-surface); border-radius: var(--radius-full);
            font-size: .78rem; color: var(--text-muted); box-shadow: var(--shadow-sm);
        }
        .pp-stat b { color: var(--text-primary); }
        .pp-layout { display: grid; grid-template-columns: minmax(0, .9fr) minmax(0, 1.1fr); gap: 18px; align-items: start; }
        .pp-preview {
            display: flex; align-items: center; gap: 12px; margin-top: 10px;
            padding: 10px; border: 1px solid var(--border-color);
            border-radius: var(--radius-md); background: var(--bg-base);
        }
        .pp-preview img {
            width: 86px; height: 110px; object-fit: cover;
            border-radius: 8px; border: 1px solid var(--border-color); background: #fff;
        }
        .pp-preview .pp-preview-meta { min-width: 0; flex: 1 1 auto; font-size: .8rem; color: var(--text-muted); }
        .pp-preview .pp-preview-meta strong { display: block; color: var(--text-primary); font-size: .86rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .pp-prompt { margin: 16px 0; border: 1px solid var(--border-color); border-radius: var(--radius-md); background: var(--bg-base); }
        .pp-prompt summary { cursor: pointer; padding: 11px 14px; font-weight: 700; font-size: .84rem; }
        .pp-prompt .pp-prompt-body { padding: 0 14px 14px; }
        .pp-prompt textarea {
            width: 100%; min-height: 140px; resize: vertical;
            font-family: var(--font-mono); font-size: .78rem; line-height: 1.55;
        }
        .pp-result { display: grid; grid-template-columns: minmax(0, 340px) minmax(0, 1fr); gap: 20px; align-items: start; }
        .pp-editor {
            position: relative; width: 100%; max-width: 340px; margin: 0 auto;
            aspect-ratio: 35 / 45; background: #fff; border: 1px solid var(--border-strong);
            border-radius: 10px; overflow: hidden; box-shadow: var(--shadow-md);
        }
        .pp-editor canvas { display: block; width: 100%; height: 100%; touch-action: none; cursor: grab; }
        .pp-editor canvas:active { cursor: grabbing; }
        .pp-guide { position: absolute; inset: 0; pointer-events: none; }
        .pp-oval {
            position: absolute; left: 50%; top: 9%; width: 56%; height: 74%;
            transform: translateX(-50%); border: 1.5px dashed rgba(15, 23, 42, .34);
            border-radius: 50%;
        }
        .pp-guide::after {
            content: ""; position: absolute; left: 0; right: 0; top: 83%;
            border-top: 1px dashed rgba(15, 23, 42, .22);
        }
        .pp-controls { min-width: 0; }
        .pp-controls .field { margin-bottom: 12px; }
        .pp-controls .field label { display: flex; justify-content: space-between; gap: 8px; }
        .pp-controls input[type="range"] { width: 100%; accent-color: var(--primary); }
        .pp-value { font-family: var(--font-mono); font-size: .72rem; color: var(--text-muted); }
        .pp-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 6px; }
        .pp-actions .btn { min-height: 42px; }
        .pp-delete { margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border-color); }
        .pp-meta {
            margin: 0 0 12px; padding: 9px 12px; border-radius: 8px;
            background: var(--bg-base); border: 1px solid var(--border-color);
            font-size: .76rem; color: var(--text-muted); line-height: 1.55;
        }
        .pp-meta code { font-family: var(--font-mono); color: var(--text-primary); }
        .pp-note { margin-top: 14px; font-size: .78rem; color: var(--text-muted); }
        .pp-specs { display: flex; flex-wrap: wrap; gap: 6px 16px; margin-top: 10px; font-size: .75rem; color: var(--text-muted); }
        .pp-busy {
            position: fixed; inset: 0; z-index: 999;
            display: flex; align-items: center; justify-content: center;
            background: rgba(15, 23, 42, .48); backdrop-filter: blur(3px);
            padding: 20px;
        }
        .pp-busy[hidden] { display: none; }
        .pp-busy-card {
            display: flex; flex-direction: column; align-items: center; gap: 8px;
            width: min(380px, 100%); padding: 26px 22px; text-align: center;
            background: var(--bg-surface); border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg); border: 1px solid var(--border-color);
        }
        .pp-busy-card strong { font-size: 1.02rem; }
        .pp-busy-card span { color: var(--text-muted); font-size: .84rem; }
        .pp-spinner {
            width: 34px; height: 34px; border-radius: 50%;
            border: 3px solid var(--primary-light); border-top-color: var(--primary);
            animation: pp-spin .8s linear infinite;
        }
        @keyframes pp-spin { to { transform: rotate(360deg); } }
        @media (max-width: 900px) {
            .pp-layout, .pp-result { grid-template-columns: 1fr; }
            .pp-editor { max-width: 300px; }
        }
        @media (prefers-reduced-motion: reduce) {
            .pp-spinner { animation: none; }
        }
    </style>
</head>
<body class="admin-body">
<div class="pp-page">
    <div class="pp-shell">
        <div class="pp-top">
            <a class="pp-brand" href="<?= e($panelUrl) ?>">
                <img src="<?= e($logoUrl) ?>" alt="Repdoc" width="36" height="36">
                <span>
                    <strong>পাসপোর্ট সাইজ ছবি</strong>
                    <small>Passport Photo · UDC &amp; Admin</small>
                </span>
            </a>
            <a class="btn btn-secondary btn-sm" href="<?= e($panelUrl) ?>">← ড্যাশবোর্ড</a>
        </div>

        <div class="pp-hero">
            <span class="pp-kicker">AI Photo Tool</span>
            <h1>একটি ছবি দিন, পাসপোর্ট সাইজ ছবি নিন</h1>
            <p>
                মুখের গঠন, ত্বকের রং ও স্বাভাবিক ভাব অপরিবর্তিত রেখে সাদা ব্যাকগ্রাউন্ডের
                প্রফেশনাল পাসপোর্ট ছবি তৈরি হয়। তারপর ক্রপ ঠিক করে ৩৫×৪৫ মিমি (৩০০ DPI)
                JPEG বা ৪×৬ প্রিন্ট শিট ডাউনলোড করুন।
            </p>
            <div class="pp-stat">
                <span>ব্যালেন্স: <b><?= number_format($balance) ?></b></span>
                <span>মূল্য: <b><?= is_admin() ? 'অ্যাডমিন ফ্রি' : ($price > 0 ? number_format($price) . ' ক্রেডিট' : 'ফ্রি') ?></b></span>
                <span>মডেল: <b><?= $aiReady ? e(passport_provider_label((string) ($chain[0] ?? 'basic'))) : 'Basic crop' ?></b></span>
            </div>
        </div>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if ($notice !== ''): ?>
            <div class="alert alert-<?= e($noticeType) ?>" role="status"><?= e($notice) ?></div>
        <?php endif; ?>
        <?php if (!$hasKey): ?>
            <div class="alert alert-warning" role="note">
                <?php if (is_admin()): ?>
                    AI মডেল কনফিগার করা নেই — ছবি তৈরি হবে শুধু বেসিক পাসপোর্ট ক্রপ হিসেবে।
                    ফ্রি মডেল চালু করতে <code>POLLINATIONS_API_KEY</code> সেট করুন
                    (<code>PASSPORT_IMAGE_PROVIDER</code> = <code>auto</code>)।
                <?php else: ?>
                    AI মডেল এখনো চালু হয়নি — আপাতত বেসিক পাসপোর্ট ক্রপ তৈরি হবে। অ্যাডমিনকে জানান।
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="pp-layout">
            <div class="panel">
                <div class="panel-head">১. ছবি আপলোড <span class="count">JPEG / PNG</span></div>
                <div class="panel-body">
                    <form method="post" action="index.php" enctype="multipart/form-data" id="ppForm">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="generate">

                        <div class="field">
                            <label for="ppPhoto">সামনের দিকের পরিষ্কার ছবি</label>
                            <input type="file" id="ppPhoto" name="photo" accept="image/jpeg,image/png" required>
                            <span class="hint">মুখ সম্পূর্ণ দেখা যেতে হবে। সর্বোচ্চ ৮ MB, সর্বোচ্চ ৬০০০ পিক্সেল।</span>
                        </div>

                        <div class="pp-preview" id="ppPreview" hidden>
                            <img id="ppPreviewImg" alt="নির্বাচিত ছবি">
                            <span class="pp-preview-meta"><strong id="ppFileName"></strong>নির্বাচিত ছবি</span>
                            <button type="button" class="btn btn-secondary btn-sm" id="ppClear">মুছুন</button>
                        </div>

                        <details class="pp-prompt">
                            <summary>AI প্রম্পট (ডিফল্ট)</summary>
                            <div class="pp-prompt-body">
                                <div class="field">
                                    <textarea name="prompt" id="ppPrompt" rows="9" spellcheck="false"><?= e($promptValue) ?></textarea>
                                    <span class="hint">ডিফল্ট প্রম্পটটিই সবচেয়ে ভালো ফল দেয়। দরকার হলে এডিট করুন।</span>
                                </div>
                            </div>
                        </details>

                        <button type="submit" class="btn btn-primary" id="ppSubmit" style="width:100%">
                            ছবি তৈরি করুন
                        </button>
                        <p class="pp-note">
                            ছবিটি শুধু এই টুলের জন্য প্রস্তুত করা হয়। AI মডেল ব্যবহার করলে ছবিটি
                            সংশ্লিষ্ট প্রোভাইডারে প্রসেস হয়।
                        </p>
                    </form>
                </div>
            </div>

            <div class="panel">
                <div class="panel-head">
                    <?= $result !== null ? '২. ক্রপ ও ডাউনলোড' : 'ফলাফল' ?>
                    <?php if ($result !== null): ?>
                        <span class="count"><?= e($resultMeta) ?></span>
                    <?php endif; ?>
                </div>
                <div class="panel-body">
                    <?php if ($result === null): ?>
                        <p class="pp-note" style="margin-top:0">
                            ছবি তৈরি হলে এখানে প্রিভিউ আসবে। জুম, উপর-নিচ ও বাম-ডান স্লাইডার দিয়ে
                            মুখ ঠিক জায়গায় বসিয়ে পাসপোর্ট সাইজে ডাউনলোড করতে পারবেন।
                        </p>
                        <div class="pp-specs">
                            <span>পাসপোর্ট সাইজ: ৩৫×৪৫ মিমি</span>
                            <span>রেজোলিউশন: ৪১৩×৫৩১ px</span>
                            <span>প্রিন্ট: ৪×৬ ইঞ্চি শিট</span>
                        </div>
                    <?php else: ?>
                        <div class="pp-result" id="ppResult" data-src="<?= e($resultUrl) ?>">
                            <div>
                                <div class="pp-editor">
                                    <canvas id="ppCanvas" width="700" height="900" aria-label="Passport crop preview"></canvas>
                                    <div class="pp-guide" aria-hidden="true"><span class="pp-oval"></span></div>
                                </div>
                                <p class="pp-note" style="margin-top:10px">
                                    ছবিটি ড্র্যাগ করুন বা স্লাইডার ব্যবহার করুন। ডাউনলোডে গাইড লাইন থাকবে না।
                                </p>
                            </div>
                            <div class="pp-controls">
                                <p class="pp-meta">
                                    মডেল: <code><?= e($resultMeta) ?><?= $resultModel !== '' && $resultModel !== 'basic' ? ' · ' . e($resultModel) : '' ?></code><br>
                                    ফাইল: <code><?= e((string) $result['file']) ?></code><br>
                                    জেনারেটেড: <?= (int) $result['width'] ?>×<?= (int) $result['height'] ?> px
                                    <?php if ((int) ($result['charged'] ?? 0) > 0): ?>
                                        · চার্জ: <?= number_format((int) $result['charged']) ?> ক্রেডিট
                                    <?php endif; ?>
                                </p>

                                <div class="field">
                                    <label for="ppZoom">জুম <span class="pp-value" id="ppZoomVal">100%</span></label>
                                    <input type="range" id="ppZoom" min="1" max="3" step="0.01" value="1">
                                </div>
                                <div class="field">
                                    <label for="ppPosY">উপর / নিচ <span class="pp-value" id="ppPosYVal">0</span></label>
                                    <input type="range" id="ppPosY" min="-100" max="100" step="1" value="0">
                                </div>
                                <div class="field">
                                    <label for="ppPosX">বাম / ডান <span class="pp-value" id="ppPosXVal">0</span></label>
                                    <input type="range" id="ppPosX" min="-100" max="100" step="1" value="0">
                                </div>

                                <div class="pp-actions">
                                    <button type="button" class="btn btn-primary" id="ppDownload">পাসপোর্ট সাইজ ডাউনলোড</button>
                                    <button type="button" class="btn btn-secondary" id="ppSheet">৪×৬ প্রিন্ট শিট</button>
                                    <button type="button" class="btn btn-secondary" id="ppReset">রিসেট</button>
                                    <a class="btn btn-secondary" href="<?= e($resultUrl . '&download=1') ?>">AI ছবি (ফুল)</a>
                                </div>

                                <div class="pp-specs">
                                    <span>৩৫×৪৫ মিমি</span>
                                    <span>৪১৩×৫৩১ px @ ৩০০ DPI</span>
                                    <span>সাদা ব্যাকগ্রাউন্ড</span>
                                </div>

                                <form method="post" action="index.php" class="pp-delete"
                                      onsubmit="return confirm('এই ছবিটি মুছে ফেলবেন?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="file" value="<?= e((string) $result['file']) ?>">
                                    <button type="submit" class="btn btn-secondary btn-sm">ছবিটি মুছে ফেলুন</button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <noscript>
            <p class="pp-note">ক্রপ এডিটর ও ডাউনলোডের জন্য JavaScript প্রয়োজন। উপরের “AI ছবি (ফুল)” লিংক থেকে মূল ছবি নামাতে পারবেন।</p>
        </noscript>
    </div>
</div>

<div class="pp-busy" id="ppBusy" hidden role="status" aria-live="polite">
    <div class="pp-busy-card">
        <span class="pp-spinner" aria-hidden="true"></span>
        <strong>AI ছবি তৈরি হচ্ছে…</strong>
        <span>২০–৬০ সেকেন্ড সময় লাগতে পারে। পেজটি বন্ধ করবেন না।</span>
    </div>
</div>

<script>
(function () {
    'use strict';

    var form = document.getElementById('ppForm');
    var input = document.getElementById('ppPhoto');
    var preview = document.getElementById('ppPreview');
    var previewImg = document.getElementById('ppPreviewImg');
    var fileName = document.getElementById('ppFileName');
    var clearBtn = document.getElementById('ppClear');
    var busy = document.getElementById('ppBusy');
    var objectUrl = '';

    function clearPreview() {
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
            objectUrl = '';
        }
        if (preview) preview.hidden = true;
        if (previewImg) previewImg.removeAttribute('src');
        if (fileName) fileName.textContent = '';
        if (input) input.value = '';
    }

    if (input) {
        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file) { clearPreview(); return; }
            if (objectUrl) URL.revokeObjectURL(objectUrl);
            objectUrl = URL.createObjectURL(file);
            if (previewImg) previewImg.src = objectUrl;
            if (fileName) fileName.textContent = file.name;
            if (preview) preview.hidden = false;
        });
    }
    if (clearBtn) clearBtn.addEventListener('click', clearPreview);

    function loadViaImage(file) {
        return new Promise(function (resolve, reject) {
            var url = URL.createObjectURL(file);
            var img = new Image();
            img.onload = function () { URL.revokeObjectURL(url); resolve(img); };
            img.onerror = function () { URL.revokeObjectURL(url); reject(new Error('image')); };
            img.src = url;
        });
    }

    async function normalize(file) {
        if (!file || !file.type || file.type.indexOf('image/') !== 0) return null;

        var bitmap = null;
        try {
            if (typeof createImageBitmap === 'function') {
                bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
            }
        } catch (err) { bitmap = null; }

        if (!bitmap) {
            try { bitmap = await loadViaImage(file); } catch (err) { return null; }
        }

        var iw = bitmap.naturalWidth || bitmap.width || 0;
        var ih = bitmap.naturalHeight || bitmap.height || 0;
        if (!iw || !ih) return null;

        var max = 2048;
        var scale = Math.min(1, max / Math.max(iw, ih));
        var w = Math.max(1, Math.round(iw * scale));
        var h = Math.max(1, Math.round(ih * scale));

        var canvas = document.createElement('canvas');
        canvas.width = w;
        canvas.height = h;
        var ctx = canvas.getContext('2d');
        if (!ctx) return null;
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, w, h);
        ctx.imageSmoothingEnabled = true;
        ctx.imageSmoothingQuality = 'high';
        ctx.drawImage(bitmap, 0, 0, w, h);

        if (bitmap && typeof bitmap.close === 'function') bitmap.close();

        return await new Promise(function (resolve) {
            canvas.toBlob(function (blob) { resolve(blob); }, 'image/jpeg', 0.92);
        });
    }

    if (form) {
        form.addEventListener('submit', async function (event) {
            if (form.dataset.ready === '1') return;
            event.preventDefault();

            var file = input && input.files ? input.files[0] : null;
            if (!file) {
                form.dataset.ready = '1';
                form.submit();
                return;
            }

            var submit = document.getElementById('ppSubmit');
            if (submit) submit.disabled = true;

            try {
                var blob = await normalize(file);
                if (blob && blob.size > 0) {
                    var dt = new DataTransfer();
                    dt.items.add(new File([blob], 'passport-source.jpg', { type: 'image/jpeg' }));
                    input.files = dt.files;
                }
            } catch (err) { /* fall back to the original upload */ }

            form.dataset.ready = '1';
            if (busy) busy.hidden = false;
            form.submit();
        });
    }

    var root = document.getElementById('ppResult');
    var canvas = document.getElementById('ppCanvas');
    if (!root || !canvas) return;

    var ctx = canvas.getContext('2d');
    var state = { img: null, zoom: 1, x: 0, y: 0 };
    var zoomInput = document.getElementById('ppZoom');
    var posYInput = document.getElementById('ppPosY');
    var posXInput = document.getElementById('ppPosX');
    var zoomVal = document.getElementById('ppZoomVal');
    var posYVal = document.getElementById('ppPosYVal');
    var posXVal = document.getElementById('ppPosXVal');

    function drawCrop(context, W, H, s) {
        context.save();
        context.fillStyle = '#ffffff';
        context.fillRect(0, 0, W, H);
        if (!s.img) { context.restore(); return; }
        var iw = s.img.naturalWidth || s.img.width || 0;
        var ih = s.img.naturalHeight || s.img.height || 0;
        if (!iw || !ih) { context.restore(); return; }
        var base = Math.max(W / iw, H / ih);
        var scale = base * s.zoom;
        var dw = iw * scale;
        var dh = ih * scale;
        var extraX = Math.max(0, dw - W) / 2;
        var extraY = Math.max(0, dh - H) / 2;
        var dx = (W - dw) / 2 + s.x * extraX;
        var dy = (H - dh) / 2 + s.y * extraY;
        context.imageSmoothingEnabled = true;
        context.imageSmoothingQuality = 'high';
        context.drawImage(s.img, dx, dy, dw, dh);
        context.restore();
    }

    function render() {
        drawCrop(ctx, canvas.width, canvas.height, state);
    }

    function syncLabels() {
        if (zoomVal) zoomVal.textContent = Math.round(state.zoom * 100) + '%';
        if (posYVal) posYVal.textContent = String(Math.round(state.y * 100));
        if (posXVal) posXVal.textContent = String(Math.round(state.x * 100));
    }

    if (zoomInput) zoomInput.addEventListener('input', function () {
        state.zoom = Math.max(1, Math.min(3, parseFloat(zoomInput.value) || 1));
        syncLabels();
        render();
    });
    if (posYInput) posYInput.addEventListener('input', function () {
        state.y = Math.max(-1, Math.min(1, (parseFloat(posYInput.value) || 0) / 100));
        syncLabels();
        render();
    });
    if (posXInput) posXInput.addEventListener('input', function () {
        state.x = Math.max(-1, Math.min(1, (parseFloat(posXInput.value) || 0) / 100));
        syncLabels();
        render();
    });

    var drag = null;
    canvas.addEventListener('pointerdown', function (event) {
        drag = { x: event.clientX, y: event.clientY, sx: state.x, sy: state.y };
        if (canvas.setPointerCapture) canvas.setPointerCapture(event.pointerId);
    });
    canvas.addEventListener('pointermove', function (event) {
        if (!drag) return;
        var rect = canvas.getBoundingClientRect();
        var dx = (event.clientX - drag.x) / Math.max(1, rect.width);
        var dy = (event.clientY - drag.y) / Math.max(1, rect.height);
        state.x = Math.max(-1, Math.min(1, drag.sx + dx * 2));
        state.y = Math.max(-1, Math.min(1, drag.sy + dy * 2));
        if (posXInput) posXInput.value = String(Math.round(state.x * 100));
        if (posYInput) posYInput.value = String(Math.round(state.y * 100));
        syncLabels();
        render();
    });
    canvas.addEventListener('pointerup', function () { drag = null; });
    canvas.addEventListener('pointercancel', function () { drag = null; });

    function downloadBlob(blob, name) {
        if (!blob) return;
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = name;
        document.body.appendChild(a);
        a.click();
        a.remove();
        setTimeout(function () { URL.revokeObjectURL(url); }, 3000);
    }

    function exportCrop(W, H, name) {
        var out = document.createElement('canvas');
        out.width = W;
        out.height = H;
        var octx = out.getContext('2d');
        if (!octx) return;
        drawCrop(octx, W, H, state);
        out.toBlob(function (blob) { downloadBlob(blob, name); }, 'image/jpeg', 0.95);
    }

    var resetBtn = document.getElementById('ppReset');
    if (resetBtn) resetBtn.addEventListener('click', function () {
        state.zoom = 1;
        state.x = 0;
        state.y = 0;
        if (zoomInput) zoomInput.value = '1';
        if (posXInput) posXInput.value = '0';
        if (posYInput) posYInput.value = '0';
        syncLabels();
        render();
    });

    var downloadBtn = document.getElementById('ppDownload');
    if (downloadBtn) downloadBtn.addEventListener('click', function () {
        exportCrop(413, 531, 'passport-35x45mm.jpg');
    });

    var sheetBtn = document.getElementById('ppSheet');
    if (sheetBtn) sheetBtn.addEventListener('click', function () {
        var W = 1200, H = 1800;
        var pw = 413, ph = 531;
        var cols = 2, rows = 3, gap = 24;
        var totalW = cols * pw + (cols - 1) * gap;
        var totalH = rows * ph + (rows - 1) * gap;
        var left = (W - totalW) / 2;
        var top = (H - totalH) / 2 - 20;

        var out = document.createElement('canvas');
        out.width = W;
        out.height = H;
        var octx = out.getContext('2d');
        if (!octx) return;

        octx.fillStyle = '#ffffff';
        octx.fillRect(0, 0, W, H);

        for (var r = 0; r < rows; r++) {
            for (var c = 0; c < cols; c++) {
                var x = left + c * (pw + gap);
                var y = top + r * (ph + gap);
                octx.save();
                octx.translate(x, y);
                drawCrop(octx, pw, ph, state);
                octx.restore();
                octx.strokeStyle = '#d6d3d1';
                octx.lineWidth = 1;
                octx.strokeRect(x + 0.5, y + 0.5, pw - 1, ph - 1);
            }
        }

        octx.fillStyle = '#475569';
        octx.font = '600 22px system-ui, sans-serif';
        octx.textAlign = 'center';
        octx.fillText('35 × 45 mm · 300 DPI · Repdoc', W / 2, H - 46);

        out.toBlob(function (blob) { downloadBlob(blob, 'passport-4x6-print-sheet.jpg'); }, 'image/jpeg', 0.95);
    });

    var img = new Image();
    img.onload = function () {
        state.img = img;
        syncLabels();
        render();
    };
    img.onerror = function () {
        var note = document.createElement('p');
        note.className = 'pp-note';
        note.textContent = 'ছবিটি লোড করা যায়নি — আবার তৈরি করুন।';
        root.appendChild(note);
    };
    img.src = root.getAttribute('data-src') || '';
})();
</script>
</body>
</html>
