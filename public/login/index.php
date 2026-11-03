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
    <meta name="theme-color" content="#F3FAF6">
    <?php
    $SEO_TITLE = 'লগইন — Repdoc অ্যাকাউন্টে প্রবেশ করুন';
    $SEO_DESC  = 'Repdoc-এ লগইন করুন — একটি অ্যাকাউন্টেই সব ডকুমেন্ট টুল: সিভি, প্রত্যয়ন ও সনদ। UDC উদ্যোক্তা ও অপারেটরদের টুলকিট।';
    $CANON     = repdoc_canonical('public/login/');
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
    <link rel="apple-touch-icon" sizes="180x180" href="<?= e(repdoc_url('public/apple-touch-icon.png')) ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= e(repdoc_url('public/icon-192.png')) ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="<?= e(repdoc_url('public/icon-512.png')) ?>">
    <link rel="manifest" href="<?= e(repdoc_url('public/manifest.webmanifest')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Bengali:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/tokens.css?v=2')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/apply.css?v=1')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/login.css?v=1')) ?>">
</head>
<body class="apply-body login-body">
    <svg class="sprite" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <symbol id="i-check" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></symbol>
            <symbol id="i-shield" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11.2 14.2 15.5 9.9"/></symbol>
            <symbol id="i-file" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></symbol>
            <symbol id="i-mobile" viewBox="0 0 24 24"><rect x="7" y="2" width="10" height="20" rx="2"/><line x1="11" y1="18" x2="13" y2="18"/></symbol>
            <symbol id="i-bolt" viewBox="0 0 24 24"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></symbol>
            <symbol id="i-id" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><circle cx="8.5" cy="10" r="2"/><path d="M5 16.5a3.6 3.6 0 0 1 7 0"/><line x1="14.5" y1="9.5" x2="19" y2="9.5"/><line x1="14.5" y1="13.5" x2="19" y2="13.5"/></symbol>
            <symbol id="i-back" viewBox="0 0 24 24"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></symbol>
            <symbol id="i-login" viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></symbol>
            <symbol id="i-arrow" viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></symbol>
            <symbol id="i-lock" viewBox="0 0 24 24"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></symbol>
        </defs>
    </svg>

    <div class="login-shell">
        <aside class="login-brandpanel" aria-label="Repdoc পরিচিতি">
            <a class="apply-brand" href="<?= e(repdoc_url('/')) ?>" aria-label="Repdoc — হোমপেজ">
                <img src="<?= e(repdoc_url('src/assets/image/logo.png')) ?>" alt="Repdoc" width="96" height="28">
            </a>

            <div class="login-pitch">
                <span class="kicker"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-bolt"/></svg> Operator toolkit</span>
                <h1>কাগজপত্র তৈরি এখন কয়েক মিনিটের কাজ</h1>
                <p>রেডি টেমপ্লেট, প্রত্যয়ন, সনদ আর সিভি — সব এক অ্যাকাউন্টে, যেকোনো ডিভাইস থেকে।</p>
                <ul class="login-points">
                    <li>
                        <span class="num">01</span>
                        <div><strong>প্রত্যয়ন ও সনদ</strong><span class="txt">রেডি টেমপ্লেট থেকে Word ও PDF ডকুমেন্ট।</span></div>
                    </li>
                    <li>
                        <span class="num">02</span>
                        <div><strong>সিভি মেকার</strong><span class="txt">তথ্য দিলেই তৈরি জীবনবৃত্তান্ত।</span></div>
                    </li>
                    <li>
                        <span class="num">03</span>
                        <div><strong>এক অ্যাকাউন্ট</strong><span class="txt">একটি লগইনেই সব টুল ব্যবহার।</span></div>
                    </li>
                </ul>
            </div>

            <p class="login-panel-foot">
                <a href="<?= e(repdoc_url('/')) ?>"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-back"/></svg> সাইটে ফিরুন</a>
                <a href="<?= e(repdoc_url('Entrepreneur/apply/')) ?>"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-id"/></svg> অপারেটর আবেদন</a>
            </p>
        </aside>

        <div class="login-formside">
            <header class="apply-topbar login-topbar">
                <a class="apply-topbar-brand" href="<?= e(repdoc_url('/')) ?>" aria-label="Repdoc — হোমপেজ">
                    <img src="<?= e(repdoc_url('src/assets/image/logo.png')) ?>" alt="Repdoc" height="26">
                </a>
                <p class="apply-crumb">Public <span aria-hidden="true">/</span> Sign in</p>
                <span class="topbar-status">নিরাপদ লগইন</span>
            </header>

            <main class="login-main">
                <div class="apply-head login-head">
                    <span class="kicker"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-lock"/></svg> Member access</span>
                    <h1>লগইন করুন</h1>
                    <p>সব টুল ব্যবহারের জন্য একটি অ্যাকাউন্টই যথেষ্ট।</p>
                </div>

                <?php if ($error !== ''): ?>
                    <div class="alert alert-error" role="alert"><?= e($error) ?></div>
                <?php endif; ?>

                <?php if ($next !== ''): ?>
                    <div class="alert alert-info">লগইন শেষে আপনাকে আগের পাতায় ফিরিয়ে নেওয়া হবে।</div>
                <?php endif; ?>

                <section class="panel login-card" aria-labelledby="loginPanelTitle">
                    <div class="panel-head">
                        <span class="step-chip" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#i-lock"/></svg></span>
                        <div>
                            <h2 id="loginPanelTitle">অ্যাকাউন্টে প্রবেশ</h2>
                            <p>ইমেইল ও পাসওয়ার্ড দিয়ে সাইন ইন করুন।</p>
                        </div>
                    </div>
                    <div class="panel-body">
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

                            <button type="submit" class="btn btn-primary login-submit">
                                লগইন
                                <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-arrow"/></svg>
                            </button>
                        </form>

                        <div class="login-alt">
                            <span><strong>অ্যাকাউন্ট নেই?</strong> অপারেটর হিসেবে আবেদন করুন।</span>
                            <a href="<?= e(repdoc_url('Entrepreneur/apply/')) ?>">
                                আবেদন করুন
                                <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-arrow"/></svg>
                            </a>
                        </div>
                    </div>
                </section>

                <p class="apply-foot">Independent tools site — not a government portal.</p>
            </main>
        </div>
    </div>
</body>
</html>
