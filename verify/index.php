<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';
require_once dirname(__DIR__) . '/src/nav.php';
repdoc_security_headers();

$me = current_user();
$no = isset($_GET['no']) && is_string($_GET['no']) ? trim($_GET['no']) : '';
$no = preg_replace('/\D/', '', prottoyon_en_digits($no)) ?? '';
$error = '';
$row = null;
$data = [];

$now = time();
$_SESSION['pp_verify_attempts'] = array_values(array_filter($_SESSION['pp_verify_attempts'] ?? [], static fn($t) => is_int($t) && $t > $now - 600));
if ($no !== '') {
    if (count($_SESSION['pp_verify_attempts']) >= 30) {
        $error = 'অনেকবার চেষ্টা করা হয়েছে। কিছুক্ষণ পর আবার চেষ্টা করুন।';
    } else {
        $_SESSION['pp_verify_attempts'][] = $now;
        $db = repdoc_db();
        if ($db) {
            try {
                $st = $db->prepare("SELECT * FROM document_logs WHERE cert_no = ? AND tool_key LIKE 'prottoyon!_%' ESCAPE '!' LIMIT 1");
                $st->execute([$no]);
                $row = $st->fetch() ?: null;
                if ($row) $data = prottoyon_decode_row($row);
            } catch (Throwable $e) {
                $error = 'যাচাই করা যাচ্ছে না — আবার চেষ্টা করুন।';
            }
        } else {
            $error = 'ডেটাবেস unavailable।';
        }
        if (!$row && $error === '') $error = 'এই নম্বরে কোনো প্রত্যয়নপত্র পাওয়া যায়নি।';
    }
}

$office = $row ? prottoyon_office_for_row($row) : [];
$typeKey = $row ? (string) ($data['type'] ?? prottoyon_type_from_tool_key((string) $row['tool_key'])) : '';
$title = $row ? prottoyon_type_title($typeKey) : '';
$revoked = $row && (($row['status'] ?? 'valid') === 'revoked');
$appName = $row ? prottoyon_value($data, 'personal.name_bn') : '';
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#FFFEFB">
    <title>সনদ যাচাই — Repdoc</title>
    <link rel="icon" type="image/png" href="<?= e(repdoc_url('public/favicon.png')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Bengali:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/tokens.css?v=2')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/shell.css?v=2')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/home.css?v=4')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('genaretor/prottoyon/assets/css/prottoyon.css?v=2')) ?>">
</head>
<body class="pp-page">
    <header class="site-header">
        <div class="container header-inner">
            <a class="brand" href="<?= e(repdoc_url('/')) ?>" aria-label="Repdoc — হোম">
                <img class="brand-logo" src="<?= e(repdoc_url('src/assets/image/logo.png')) ?>" alt="" width="38" height="38">
                <span class="brand-text"><strong>Repdoc</strong><small>সনদ যাচাই</small></span>
            </a>
            <?php repdoc_render_site_nav('verify'); ?>
            <div class="header-actions">
                <?php if ($me): ?>
                    <a class="btn btn-primary btn-login" href="<?= e(repdoc_panel_home()) ?>">ড্যাশবোর্ড</a>
                <?php else: ?>
                    <a class="btn btn-primary btn-login" href="<?= e(repdoc_url('public/login/')) ?>">লগইন</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="pp-main container">
        <div class="pp-verify">
            <div class="pp-verify-card">
                <div class="pp-verify-head">
                    <div>
                        <span class="pp-kicker">Public verification</span>
                        <h1>সনদ যাচাই</h1>
                        <p class="pp-card-sub">১৭ ডিজিটের নতুন সনদ নম্বর অথবা পুরাতন ১১ ডিজিটের নম্বর দিন।</p>
                    </div>
                    <span class="pp-badge <?= $revoked ? 'pp-badge-bad' : ($row ? 'pp-badge-ok' : '') ?>"><?= $row ? ($revoked ? 'বাতিল' : 'বৈধ') : 'অপেক্ষমাণ' ?></span>
                </div>

                <form method="get" action="<?= e(repdoc_url('verify/')) ?>" style="display:flex;flex-wrap:wrap;gap:10px;margin-top:18px">
                    <div class="pp-field" style="flex:1 1 280px">
                        <label for="no">সনদ নম্বর</label>
                        <input id="no" name="no" value="<?= e($no) ?>" inputmode="numeric" autocomplete="off" placeholder="যেমন: 26123456700000001" required>
                    </div>
                    <div class="pp-field" style="flex:0 0 auto;justify-content:flex-end"><button type="submit" class="pp-btn pp-btn-primary">যাচাই করুন</button></div>
                </form>

                <?php if ($error !== ''): ?>
                    <div class="pp-alert pp-alert-error" role="alert" style="margin-top:18px"><?= e($error) ?></div>
                <?php endif; ?>

                <?php if ($row): ?>
                    <table class="pp-verify-table">
                        <tr><th>সনদ নম্বর</th><td><b><?= e((string) $row['cert_no']) ?></b></td></tr>
                        <tr><th>সনদের ধরন</th><td><?= e($title) ?></td></tr>
                        <tr><th>আবেদনকারীর নাম</th><td><?= e($appName !== '' ? $appName : '—') ?></td></tr>
                        <tr><th>ইস্যুকারী অফিস</th><td><?= e(($office['office_name'] ?? '') ?: '—') ?><?= ($office['upazila'] ?? '') !== '' ? ', ' . e((string) $office['upazila']) : '' ?><?= ($office['district'] ?? '') !== '' ? ', ' . e((string) $office['district']) : '' ?></td></tr>
                        <tr><th>ইস্যুর তারিখ</th><td><?= e(prottoyon_format_date((string) $row['created_at'])) ?></td></tr>
                        <tr><th>অবস্থা</th><td><span class="pp-badge <?= $revoked ? 'pp-badge-bad' : 'pp-badge-ok' ?>"><?= $revoked ? 'বাতিল' : 'বৈধ' ?></span></td></tr>
                    </table>
                    <div class="pp-verify-actions">
                        <button type="button" class="pp-btn pp-btn-primary" onclick="window.print()">প্রিন্ট / PDF সংরক্ষণ</button>
                    </div>
                    <p class="pp-note" style="margin-top:16px">এই পেজে শুধু যাচাইয়ের তথ্য দেখানো হয়। ব্যক্তিগত এনআইডি, ঠিকানা বা সংযুক্তি প্রকাশ করা হয় না।</p>
                <?php else: ?>
                    <div class="pp-note" style="margin-top:18px">সনদে ছাপা QR কোড স্ক্যান করলে এই পেজটি সরাসরি খুলবে। সনদ নম্বর হাতে থাকলে উপরে লিখে যাচাই করুন।</div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="site-footer">
        <div class="container footer-bottom">
            <span>Repdoc — প্রত্যয়ন পত্র ব্যবস্থাপনা</span>
            <span><a class="back-top" href="<?= e(repdoc_url('/')) ?>">হোমে ফিরুন ↑</a></span>
        </div>
    </footer>
    <style>
        .pp-verify-actions { margin-top: 16px; }
        @media print {
            .site-header, .site-footer, .pp-verify form, .pp-verify-actions, .pp-note { display: none !important; }
            .pp-page, .pp-main { background: #fff !important; padding: 0 !important; }
            .pp-verify { padding: 0 !important; }
            .pp-verify-card { border: 0 !important; box-shadow: none !important; max-width: none !important; }
        }
    </style>
    <script src="<?= e(repdoc_url('src/assets/js/home.js?v=1')) ?>"></script>
</body>
</html>
