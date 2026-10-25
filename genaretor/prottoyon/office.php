<?php
declare(strict_types=1);

/**
 * Repdoc — UDC office profile.
 * Approved entrepreneurs set their chairman/pouro admin + office identity
 * once after login; every prottoyon they generate shows this office.
 */

require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_login();

$user = current_user();
$udc  = is_udc();
$saved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $udc) {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'নিরাপত্তা টোকেন মিলেনি — আবার চেষ্টা করুন।');
    } else {
        $saved = save_office_profile($user['id'], [
            'office_type'   => trim((string) ($_POST['office_type'] ?? 'union')),
            'office_name'   => trim((string) ($_POST['office_name'] ?? '')),
            'chairman_name' => trim((string) ($_POST['chairman_name'] ?? '')),
            'upazila'       => trim((string) ($_POST['upazila'] ?? '')),
            'district'      => trim((string) ($_POST['district'] ?? '')),
        ]);
        flash_set($saved ? 'ok' : 'error', $saved ? 'অফিস তথ্য সংরক্ষণ হয়েছে।' : 'সংরক্ষণ হয়নি — আবার চেষ্টা করুন।');
    }
    redirect(repdoc_url('genaretor/prottoyon/office.php'));
}

$profile = office_profile();
$flash = flash_get();
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>অফিস সেটিংস — Repdoc</title>
    <meta name="robots" content="noindex">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Bengali:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/tokens.css?v=1')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/shell.css?v=1')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/app.css?v=1')) ?>">
    <script>
        (function () {
            try {
                if (localStorage.getItem('siteTheme') === 'dark') {
                    document.documentElement.setAttribute('data-theme', 'dark');
                }
            } catch (e) {}
        })();
    </script>
    <style>
        .office-wrap { max-width: 720px; margin: 0 auto; padding: 32px 20px 64px; }
        .office-wrap h1 { font-size: 24px; margin: 0 0 8px; }
        .office-wrap .sub { color: var(--text-muted); margin: 0 0 24px; }
        .panel { background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 14px; padding: 24px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .field { display: flex; flex-direction: column; }
        .field.full { grid-column: 1 / -1; }
        .field label { font-size: 14px; font-weight: 600; margin-bottom: 8px; }
        .field input, .field select {
            font: inherit; font-size: 16px; color: var(--text-primary); background: var(--bg-base);
            border: 1.5px solid var(--border-color); border-radius: 10px; padding: 12px 14px; min-height: 48px; width: 100%;
        }
        .field input:focus, .field select:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-light); }
        @media (max-width: 640px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="office-wrap">
        <p><a href="<?= e(repdoc_url('genaretor/prottoyon/')) ?>" style="color:var(--primary);font-weight:600">← প্রত্যয়ন</a></p>
        <h1>অফিস সেটিংস</h1>
        <p class="sub">লগইনের পর একবার সেট করুন — আপনার বানানো প্রতিটি সনদে এই অফিসের নাম ও চেয়ারম্যান/পৌর প্রশাসকের নাম দেখাবে।</p>

        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type'] === 'ok' ? 'success' : 'error') ?>" role="status"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <?php if (!$udc): ?>
            <div class="panel">
                <p>এই পেজ শুধু অনুমোদিত উদ্যোক্তাদের (UDC) জন্য। উদ্যোক্তা আবেদন অনুমোদিত হলে এখানে অফিস তথ্য সেট করতে পারবেন।</p>
                <p><a class="btn btn-primary" href="<?= e(repdoc_url('Entrepreneur/apply/')) ?>">উদ্যোক্তা আবেদন</a></p>
            </div>
        <?php else: ?>
            <form class="panel" method="POST" action="office.php">
                <?= csrf_field() ?>
                <div class="grid">
                    <div class="field">
                        <label for="office_type">অফিসের ধরন</label>
                        <select id="office_type" name="office_type">
                            <?php foreach (['union' => 'ইউনিয়ন পরিষদ', 'municipality' => 'পৌরসভা', 'city_corporation' => 'সিটি কর্পোরেশন'] as $v => $l): ?>
                                <option value="<?= e($v) ?>"<?= $profile['office_type'] === $v ? ' selected' : '' ?>><?= e($l) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="office_name">অফিসের নাম</label>
                        <input type="text" id="office_name" name="office_name" value="<?= e($profile['office_name']) ?>" placeholder="যেমন: বালুয়াহাট ইউনিয়ন পরিষদ" required>
                    </div>
                    <div class="field">
                        <label for="chairman_name">চেয়ারম্যান / পৌর প্রশাসকের নাম</label>
                        <input type="text" id="chairman_name" name="chairman_name" value="<?= e($profile['chairman_name']) ?>" placeholder="নাম লিখুন" required>
                    </div>
                    <div class="field">
                        <label for="upazila">উপজেলা / থানা</label>
                        <input type="text" id="upazila" name="upazila" value="<?= e($profile['upazila']) ?>" placeholder="উপজেলা">
                    </div>
                    <div class="field full">
                        <label for="district">জেলা</label>
                        <input type="text" id="district" name="district" value="<?= e($profile['district']) ?>" placeholder="জেলা">
                    </div>
                </div>
                <p style="margin-top:20px"><button type="submit" class="btn btn-primary">সংরক্ষণ করুন</button></p>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
