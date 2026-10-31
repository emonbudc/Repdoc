<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';
require_once dirname(__DIR__) . '/src/seo.php';
require_once dirname(__DIR__) . '/src/nav.php';
repdoc_security_headers();

$me = current_user();
$no = isset($_GET['no']) && is_string($_GET['no']) ? trim($_GET['no']) : '';
$no = preg_replace('/\D/', '', prottoyon_en_digits($no)) ?? '';
$isLookup = $no !== '';
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

$SEO_TITLE = 'সনদ যাচাই — Repdoc প্রত্যয়ন যাচাই';
$SEO_DESC  = 'Repdoc-এ তৈরি প্রত্যয়নপত্রের সনদ নম্বর দিয়ে বৈধতা ও অবস্থা যাচাই করুন। QR কোড স্ক্যান করলেও এই পেজ খোলে। সরকারি সনদ সংশ্লিষ্ট কর্তৃপক্ষের মাধ্যমে যাচাই করুন।';
$CANON     = repdoc_canonical('verify/');
$faqs = [
    [
        'q' => 'Repdoc-এ সনদ যাচাই কীভাবে করবেন?',
        'a' => 'সনদের ওপর ছাপা সনদ নম্বরটি উপরের ঘরে লিখে “যাচাই করুন” চাপুন। QR কোড থাকলে স্ক্যান করলেও এই পেজটি সরাসরি খুলবে।',
    ],
    [
        'q' => 'যাচাই পেজে কী কী তথ্য দেখা যায়?',
        'a' => 'সনদ নম্বর, সনদের ধরন, আবেদনকারীর নাম, ইস্যুকারী অফিস, ইস্যুর তারিখ ও বর্তমান অবস্থা দেখা যায়। ব্যক্তিগত NID, ঠিকানা বা সংযুক্তি দেখানো হয় না।',
    ],
    [
        'q' => 'সরকারি সনদও কি এখানে যাচাই করা যায়?',
        'a' => 'না। Repdoc শুধু নিজের তৈরি প্রত্যয়নের যাচাই দেখায়। সরকারি সনদের বৈধতা সংশ্লিষ্ট ইস্যুকারী কর্তৃপক্ষের মাধ্যমে যাচাই করতে হবে।',
    ],
];
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FFFEFB">
    <title><?= e($SEO_TITLE) ?></title>
    <meta name="description" content="<?= e($SEO_DESC) ?>">
    <?php
    repdoc_seo_head([
        'title'       => $SEO_TITLE,
        'description' => $SEO_DESC,
        'canonical'   => $CANON,
        'type'        => 'website',
        'image'       => repdoc_canonical('src/assets/image/hero.webp'),
        'imageWidth'  => 900,
        'imageHeight' => 600,
        'imageAlt'    => 'Repdoc সনদ যাচাই পেজ',
        'twitterCard' => 'summary_large_image',
        'locale'      => 'bn_BD',
        'noindex'     => $isLookup,
    ]);
    if (!$isLookup) {
        repdoc_jsonld([
            '@type'       => 'WebPage',
            '@id'         => $CANON . '#page',
            'name'        => $SEO_TITLE,
            'url'         => $CANON,
            'description' => $SEO_DESC,
            'inLanguage'  => 'bn',
            'isPartOf'    => ['@id' => repdoc_canonical() . '#website'],
            'breadcrumb'  => ['@id' => $CANON . '#breadcrumb'],
        ]);
        repdoc_jsonld([
            '@type'           => 'BreadcrumbList',
            '@id'             => $CANON . '#breadcrumb',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => repdoc_canonical()],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'সনদ যাচাই', 'item' => $CANON],
            ],
        ]);
        repdoc_jsonld([
            '@type'      => 'FAQPage',
            '@id'        => $CANON . '#faq',
            'mainEntity' => array_map(
                static fn(array $item): array => [
                    '@type'          => 'Question',
                    'name'           => $item['q'],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['a']],
                ],
                $faqs
            ),
        ]);
    }
    ?>
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
                    <div class="pp-note" style="margin-top:18px">সনদে ছাপা QR কোড স্ক্যান করলে এই পেজটি সরাসরি খুলবে। সনদ নম্বর হাতে থাকলে উপরে লিখে যাচাই করুন। <a href="<?= e(repdoc_url('sonod/sonod-verification/')) ?>">সনদ যাচাইয়ের নিয়ম পড়ুন</a>।</div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!$isLookup): ?>
            <section class="pp-verify-help" aria-labelledby="verifyHelpTitle">
                <h2 id="verifyHelpTitle">Repdoc সনদ যাচাই কীভাবে কাজ করে</h2>
                <div class="pp-verify-help-grid">
                    <div>
                        <h3>১. সনদ নম্বর দিন</h3>
                        <p>সনদের ওপর ছাপা ১৭ ডিজিটের নতুন নম্বর অথবা পুরাতন ১১ ডিজিটের নম্বর উপরের ঘরে লিখুন।</p>
                    </div>
                    <div>
                        <h3>২. তথ্য মিলিয়ে নিন</h3>
                        <p>সনদের ধরন, আবেদনকারীর নাম, ইস্যুকারী অফিস, তারিখ ও বর্তমান অবস্থা দেখুন।</p>
                    </div>
                    <div>
                        <h3>৩. সন্দেহ হলে যোগাযোগ করুন</h3>
                        <p>তথ্য মিলছে না বা সনদ বাতিল দেখালে সংশ্লিষ্ট ইস্যুকারী অফিসের সাথে যোগাযোগ করুন।</p>
                    </div>
                </div>
                <div class="pp-verify-help-note">
                    <strong>সরকারি সনদ?</strong> Repdoc শুধু নিজের তৈরি প্রত্যয়নের যাচাই দেখায়। সরকারি সনদের বৈধতা সংশ্লিষ্ট সরকারি কর্তৃপক্ষের মাধ্যমে যাচাই করতে হবে।
                    <a href="<?= e(repdoc_url('sonod/')) ?>">সনদ গাইড দেখুন</a>
                </div>
                <div class="pp-verify-help-faq">
                    <?php foreach ($faqs as $faq): ?>
                        <details>
                            <summary><?= e($faq['q']) ?></summary>
                            <p><?= e($faq['a']) ?></p>
                        </details>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <footer class="site-footer">
        <div class="container footer-bottom">
            <span>Repdoc — প্রত্যয়ন পত্র ব্যবস্থাপনা</span>
            <span><a class="back-top" href="<?= e(repdoc_url('sonod/')) ?>">সনদ গাইড</a> · <a class="back-top" href="<?= e(repdoc_url('/')) ?>">হোমে ফিরুন ↑</a></span>
        </div>
    </footer>
    <style>
        .pp-verify-actions { margin-top: 16px; }
        .pp-verify-help { max-width: 760px; margin: 28px auto 8px; background: #fff; border: 1px solid #e4e7ec; border-radius: 14px; padding: 24px; }
        .pp-verify-help h2 { margin: 0 0 16px; font-size: 1.35rem; }
        .pp-verify-help-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 16px; }
        .pp-verify-help-grid h3 { margin: 0 0 6px; font-size: 1rem; }
        .pp-verify-help-grid p { margin: 0; line-height: 1.7; color: #475467; font-size: .94rem; }
        .pp-verify-help-note { margin-top: 18px; padding: 12px 14px; border-radius: 8px; background: #f0fdfa; color: #134e4a; line-height: 1.7; font-size: .93rem; }
        .pp-verify-help-note a { color: #0f766e; font-weight: 700; }
        .pp-verify-help-faq { margin-top: 16px; }
        .pp-verify-help-faq details { border-top: 1px solid #e4e7ec; padding: 12px 0 0; margin-top: 12px; }
        .pp-verify-help-faq summary { cursor: pointer; font-weight: 700; }
        .pp-verify-help-faq p { margin: 8px 0 0; line-height: 1.7; color: #475467; }
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
