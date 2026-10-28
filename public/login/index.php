<?php
declare(strict_types=1);

/**
 * Repdoc — universal login.
 *
 * The single sign-in page for every tool on the site. Tools redirect here with
 * `?next=<project-relative path>` so the user lands back where they were.
 */

require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_once dirname(__DIR__, 2) . '/src/seo.php';
repdoc_security_headers();

$next = (string) ($_GET['next'] ?? $_POST['next'] ?? '');

// Already signed in → back where they were heading, else their panel.
if (is_logged_in()) {
    redirect($next !== '' ? repdoc_safe_next($next) : repdoc_panel_home());
}

$error = '';
$email = '';
$msg   = (string) ($_GET['msg'] ?? '');

if ($msg === 'logged_out') {
    $error = 'আপনি সফলভাবে লগআউট হয়েছেন।';
} elseif ($msg === 'pending') {
    $error = 'আপনার অ্যাকাউন্ট এখনো অনুমোদিত নয়, অথবা অ্যাক্সেস বন্ধ করা হয়েছে।';
} elseif ($msg === 'expired') {
    $error = 'সেশন মেয়াদোত্তীর্ণ — আবার লগইন করুন।';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'নিরাপত্তা টোকেন মিলেনি — পেজটি রিফ্রেশ করে আবার চেষ্টা করুন।';
    } elseif ($email === '' || $password === '') {
        $error = 'ইমেইল ও পাসওয়ার্ড দুটোই দিতে হবে।';
    } else {
        $db = repdoc_db();
        if (!$db) {
            $error = 'ডেটাবেস পাওয়া যায়নি — একবার /src/setup.php চালান।';
        } else {
            $st = $db->prepare('SELECT * FROM users WHERE email = ?');
            $st->execute([$email]);
            $user = $st->fetch();

            if ($user && password_verify($password, (string) $user['password'])) {
                if ($user['status'] !== 'approved') {
                    $error = $user['status'] === 'pending'
                        ? 'আপনার অ্যাকাউন্ট এখনো অ্যাডমিনের অনুমোদনের অপেক্ষায় আছে।'
                        : 'আপনার অ্যাকাউন্টটি বন্ধ করা হয়েছে (rejected)।';
                } else {
                    login_user($user);
                    redirect($next !== '' ? repdoc_safe_next($next) : repdoc_panel_home());
                }
            } else {
                $error = 'ইমেইল বা পাসওয়ার্ড ভুল।';
            }
        }
    }
}

$flash = flash_get();
if ($flash && $error === '') {
    $error = (string) $flash['message'];
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>লগইন — Repdoc অ্যাকাউন্টে প্রবেশ করুন</title>
    <meta name="description" content="Repdoc-এ লগইন করুন — একটি অ্যাকাউন্টেই সব ডকুমেন্ট টুল: সিভি, প্রত্যয়ন ও সনদ। UDC উদ্যোক্তা ও অপারেটরদের টুলকিট।">
    <meta name="theme-color" content="#F8FAFC">
    <?php
    $SEO_TITLE = 'লগইন — Repdoc অ্যাকাউন্টে প্রবেশ করুন';
    $SEO_DESC  = 'Repdoc-এ লগইন করুন — একটি অ্যাকাউন্টেই সব ডকুমেন্ট টুল: সিভি, প্রত্যয়ন ও সনদ। UDC উদ্যোক্তা ও অপারেটরদের টুলকিট।';
    $CANON     = repdoc_canonical('public/login/');
    repdoc_seo_head([
        'title'       => $SEO_TITLE,
        'description' => $SEO_DESC,
        'canonical'   => $CANON,
        'type'        => 'website',
        'image'       => repdoc_canonical('src/assets/image/logo.png'),
        'imageWidth'  => 480,
        'imageHeight' => 160,
        'imageAlt'    => 'Repdoc logo',
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Bengali:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/tokens.css?v=2')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/app.css?v=1')) ?>">
</head>
<body class="auth-body">
    <main class="auth-card auth-split">
        <div class="auth-detail">
            <a class="auth-brand auth-brand--invert" href="<?= e(repdoc_url('/')) ?>">
                <img src="<?= e(repdoc_url('src/assets/image/logo.png')) ?>" alt="" width="96" height="32">
                <span><strong>Repdoc</strong><small>repdoc.freedev.app · অপারেটর টুলকিট</small></span>
            </a>
            <p class="auth-kicker">যেকোনো অপারেটরের টুলকিট</p>
            <h2>কাগজপত্র তৈরি এখন কয়েক মিনিটের কাজ</h2>
            <span class="auth-rule" aria-hidden="true"></span>
            <ol class="auth-points">
                <li><strong>প্রত্যয়ন ও সনদ</strong><span>রেডি টেমপ্লেট থেকে Word ও PDF ডকুমেন্ট।</span></li>
                <li><strong>সিভি মেকার</strong><span>তথ্য দিলেই তৈরি জীবনবৃত্তান্ত।</span></li>
                <li><strong>এক অ্যাকাউন্ট</strong><span>একটি লগইনেই সব টুল ব্যবহার।</span></li>
            </ol>
            <p class="auth-fine">স্বতন্ত্র টুলস সাইট — সরকারি পোর্টাল নয়।</p>
        </div>

        <div class="auth-formside">
            <h1>লগইন করুন</h1>
            <p class="auth-sub">সব টুল ব্যবহারের জন্য একটি অ্যাকাউন্টই যথেষ্ট।</p>

            <?php if ($error !== ''): ?>
                <div class="alert alert-error" role="alert"><?= e($error) ?></div>
            <?php endif; ?>

            <?php if ($next !== ''): ?>
                <div class="alert alert-info">লগইন শেষে আপনাকে আগের পাতায় ফিরিয়ে নেওয়া হবে।</div>
            <?php endif; ?>

            <form method="post" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="next" value="<?= e($next) ?>">

                <div class="field">
                    <label for="email">ইমেইল</label>
                    <input type="email" id="email" name="email" value="<?= e($email) ?>"
                           autocomplete="email" required autofocus>
                </div>

                <div class="field">
                    <label for="password">পাসওয়ার্ড</label>
                    <input type="password" id="password" name="password"
                           autocomplete="current-password" required>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">লগইন</button>
                    <a class="btn btn-secondary" href="<?= e(repdoc_url('/')) ?>">হোমপেজ</a>
                </div>
            </form>

            <p class="auth-sub" style="margin:var(--space-5) 0 0">
                অ্যাকাউন্ট নেই? <a href="<?= e(repdoc_url('Entrepreneur/apply/')) ?>" style="color:var(--primary);font-weight:600">অপারেটর আবেদন করুন</a>
            </p>
        </div>
    </main>
</body>
</html>
