<?php
declare(strict_types=1);

/**
 * Repdoc — registration.
 *
 * Creates an account with status 'pending' and a zero balance. An administrator
 * must approve it (and usually add credits) before the tools unlock.
 */

require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_once dirname(__DIR__, 2) . '/src/seo.php';

if (is_logged_in()) {
    redirect(repdoc_url('/'));
}

$error   = '';
$success = false;
$fullname = '';
$email    = '';
$phone    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // General user signup is paused: only UDC applications are approved now.
    $error = 'Only UDC applications are being approved now — user section coming soon.';
}

$signupOpen = false; // Flip to true to reopen general user signup.
if ($signupOpen && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim((string) ($_POST['fullname'] ?? ''));
    $email    = trim((string) ($_POST['email'] ?? ''));
    $phone    = trim((string) ($_POST['phone'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirm  = (string) ($_POST['confirm_password'] ?? '');

    $db = repdoc_db();

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'নিরাপত্তা টোকেন মিলেনি — পেজটি রিফ্রেশ করে আবার চেষ্টা করুন।';
    } elseif ($fullname === '' || $email === '' || $password === '') {
        $error = 'সব ঘর পূরণ করুন।';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'ইমেইল ঠিকানাটি সঠিক নয়।';
    } elseif (strlen($password) < 6) {
        $error = 'পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে।';
    } elseif ($password !== $confirm) {
        $error = 'দুটি পাসওয়ার্ড মিলছে না।';
    } elseif (!$db) {
        $error = 'ডেটাবেস পাওয়া যায়নি — একবার /src/setup.php চালান।';
    } else {
        try {
            $st = $db->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
            $st->execute([$email]);
            if ((int) $st->fetchColumn() > 0) {
                $error = 'এই ইমেইল দিয়ে আগেই অ্যাকাউন্ট আছে।';
            } else {
                $db->prepare("INSERT INTO users (fullname, email, phone, password, role, status, balance, created_at)
                              VALUES (?, ?, ?, ?, 'user', 'pending', 0, ?)")
                   ->execute([$fullname, $email, $phone, password_hash($password, PASSWORD_DEFAULT), date('Y-m-d H:i:s')]);
                $success = true;
            }
        } catch (Throwable $ex) {
            $error = 'নিবন্ধন সম্পন্ন হয়নি — আবার চেষ্টা করুন।';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>নিবন্ধন — Repdoc</title>
    <meta name="description" content="Repdoc-এ নিবন্ধন করুন — নতুন অ্যাকাউন্ট খুলে সব ডকুমেন্ট টুল ব্যবহার করুন। বর্তমানে শুধু UDC উদ্যোক্তাদের আবেদন অনুমোদিত হচ্ছে।">
    <meta name="theme-color" content="#F8FAFC">
    <?php
    $SEO_TITLE = 'নিবন্ধন — Repdoc';
    $SEO_DESC  = 'Repdoc-এ নিবন্ধন করুন — নতুন অ্যাকাউন্ট খুলে সব ডকুমেন্ট টুল ব্যবহার করুন। বর্তমানে শুধু UDC উদ্যোক্তাদের আবেদন অনুমোদিত হচ্ছে।';
    $CANON     = repdoc_canonical('public/register/');
    repdoc_seo_head([
        'title'       => $SEO_TITLE,
        'description' => $SEO_DESC,
        'canonical'   => $CANON,
        'type'        => 'website',
        'imageAlt'    => 'Repdoc — ফ্রি ডকুমেন্ট টেমপ্লেট ও ফরম',
        'locale'      => 'bn_BD',
    ]);
    repdoc_jsonld([
        '@type'       => 'WebPage',
        '@id'         => $CANON . '#page',
        'name'        => $SEO_TITLE,
        'url'         => $CANON,
        'description' => $SEO_DESC,
        'inLanguage'  => 'bn',
        'isPartOf'    => ['@id' => repdoc_canonical() . '#website'],
    ]);
    ?>
    <link rel="icon" type="image/png" href="<?= e(repdoc_url('public/favicon.png')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Bengali:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/tokens.css?v=2')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/app.css?v=1')) ?>">
</head>
<body class="auth-body">
    <div style="max-width: 720px; margin: 0 auto; padding: var(--space-7) var(--gutter);">
        <a class="auth-brand" href="<?= e(repdoc_url('/')) ?>">
            <img src="<?= e(repdoc_url('src/assets/image/logo.png')) ?>" alt="" width="96" height="32">
            <span><strong>Repdoc</strong><small>repdoc.rf.gd</small></span>
        </a>

        <?php if ($success): ?>
            <h1>নিবন্ধন জমা হয়েছে</h1>
            <div class="alert alert-success" role="status">
                আপনার অ্যাকাউন্টটি অ্যাডমিনের অনুমোদনের অপেক্ষায় আছে। অনুমোদনের পরে লগইন করতে পারবেন।
            </div>
            <div class="form-actions">
                <a class="btn btn-primary" href="<?= e(repdoc_url('public/login/')) ?>">লগইনে যান</a>
                <a class="btn btn-secondary" href="<?= e(repdoc_url('/')) ?>">হোমপেজ</a>
            </div>
        <?php else: ?>
            <h1>নতুন অ্যাকাউন্ট</h1>
            <p class="auth-sub">নিবন্ধনের পরে অ্যাডমিন অনুমোদন দিলে টুলগুলো চালু হবে।</p>

            <div class="alert alert-warning" role="note">Only UDC applications are being approved now — user section coming soon.</div>

            <?php if ($error !== ''): ?>
                <div class="alert alert-error" role="alert"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" novalidate>
                <?= csrf_field() ?>

                <fieldset disabled style="border:0;padding:0;margin:0;min-width:0">
                <div class="field">
                    <label for="fullname">পুরো নাম</label>
                    <input type="text" id="fullname" name="fullname" value="<?= e($fullname) ?>"
                           autocomplete="name" autofocus>
                </div>

                <div class="field">
                    <label for="email">ইমেইল</label>
                    <input type="email" id="email" name="email" value="<?= e($email) ?>"
                           autocomplete="email">
                </div>

                <div class="field">
                    <label for="phone">মোবাইল <span style="font-weight:400;color:var(--text-muted)">(ঐচ্ছিক)</span></label>
                    <input type="tel" id="phone" name="phone" value="<?= e($phone) ?>" autocomplete="tel">
                </div>

                <div class="field">
                    <label for="password">পাসওয়ার্ড</label>
                    <input type="password" id="password" name="password" autocomplete="new-password">
                    <span class="hint">কমপক্ষে ৬ অক্ষর।</span>
                </div>

                <div class="field">
                    <label for="confirm_password">পাসওয়ার্ড আবার লিখুন</label>
                    <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password">
                </div>
                </fieldset>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" disabled aria-disabled="true">নিবন্ধন করুন</button>
                    <a class="btn btn-secondary" href="<?= e(repdoc_url('Entrepreneur/apply/')) ?>">UDC আবেদন</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
    <p class="auth-sub" style="text-align:center">স্বতন্ত্র টুলস সাইট — সরকারি পোর্টাল নয়।</p>
</body>
</html>
