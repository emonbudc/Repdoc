<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
require_login();

$me = current_user();
$canEdit = is_udc() || is_admin();
$profile = office_profile();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canEdit) {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'নিরাপত্তা টোকেন মিলেনি — আবার চেষ্টা করুন।');
        redirect(repdoc_url('genaretor/prottoyon/office.php'));
    }

    $unionCode = preg_replace('/\D/', '', prottoyon_en_digits((string) ($_POST['union_code'] ?? ''))) ?? '';
    if (strlen($unionCode) !== 7) {
        flash_set('error', 'ইউনিয়ন কোড ঠিক ৭ ডিজিটের হতে হবে।');
        redirect(repdoc_url('genaretor/prottoyon/office.php'));
    }

    $logoPath = (string) ($profile['logo_path'] ?? '');
    $stampPath = (string) ($profile['stamp_path'] ?? '');
    if (isset($_FILES['logo']) && is_array($_FILES['logo'])) {
        $stored = prottoyon_store_office_upload($_FILES['logo'], (int) $me['id'], 'logo');
        if (!empty($stored['ok'])) $logoPath = (string) $stored['path'];
        elseif (empty($stored['skip'])) {
            flash_set('error', (string) ($stored['message'] ?? 'লোগো আপলোড হয়নি।'));
            redirect(repdoc_url('genaretor/prottoyon/office.php'));
        }
    }
    if (isset($_FILES['stamp']) && is_array($_FILES['stamp'])) {
        $stored = prottoyon_store_office_upload($_FILES['stamp'], (int) $me['id'], 'stamp');
        if (!empty($stored['ok'])) $stampPath = (string) $stored['path'];
        elseif (empty($stored['skip'])) {
            flash_set('error', (string) ($stored['message'] ?? 'সিল আপলোড হয়নি।'));
            redirect(repdoc_url('genaretor/prottoyon/office.php'));
        }
    }

    $saved = save_office_profile((int) $me['id'], [
        'office_type' => (string) ($_POST['office_type'] ?? 'union'),
        'office_name' => prottoyon_clean($_POST['office_name'] ?? '', 190),
        'chairman_name' => prottoyon_clean($_POST['chairman_name'] ?? '', 190),
        'upazila' => prottoyon_clean($_POST['upazila'] ?? '', 190),
        'district' => prottoyon_clean($_POST['district'] ?? '', 190),
        'union_code' => $unionCode,
        'postcode' => prottoyon_clean($_POST['postcode'] ?? '', 16),
        'phone' => prottoyon_clean($_POST['phone'] ?? '', 32),
        'email' => prottoyon_clean($_POST['email'] ?? '', 190),
        'website' => prottoyon_clean($_POST['website'] ?? '', 190),
        'logo_path' => $logoPath,
        'stamp_path' => $stampPath,
    ]);
    flash_set($saved ? 'ok' : 'error', $saved ? 'অফিস তথ্য সংরক্ষণ হয়েছে।' : 'সংরক্ষণ হয়নি — আবার চেষ্টা করুন।');
    redirect(repdoc_url('genaretor/prottoyon/office.php'));
}

$profile = office_profile();
$logoUri = prottoyon_office_logo_uri($profile);
$stampUri = prottoyon_office_stamp_uri($profile);

prottoyon_page_start('অফিস সেটিংস', 'office');
?>
<div class="pp-toolbar">
    <div>
        <span class="pp-kicker">Issuer profile</span>
        <h1 style="margin:0;font-size:1.55rem">অফিস সেটিংস</h1>
        <p class="pp-card-sub">এই তথ্য আপনার তৈরি প্রতিটি সনদের হেডার, সনদ নম্বর ও স্বাক্ষরে ব্যবহার হবে।</p>
    </div>
    <a class="pp-btn" href="<?= e(repdoc_url('genaretor/prottoyon/')) ?>">← প্রত্যয়নে ফিরুন</a>
</div>

<?php if (!$canEdit): ?>
    <div class="pp-card">
        <p>এই পেজ শুধু অনুমোদিত উদ্যোক্তা (UDC) ও অ্যাডমিনের জন্য। উদ্যোক্তা আবেদন অনুমোদিত হলে এখানে অফিস তথ্য সেট করতে পারবেন।</p>
        <p><a class="pp-btn pp-btn-primary" href="<?= e(repdoc_url('Entrepreneur/apply/')) ?>">উদ্যোক্তা আবেদন</a></p>
    </div>
<?php else: ?>
    <form class="pp-card" method="post" action="office.php" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="pp-card-head"><div><h2>অফিসের পরিচয়</h2><p class="pp-card-sub">সনদে ঠিক এই নাম ও ঠিকানা ছাপা হবে।</p></div></div>
        <div class="pp-form-grid">
            <div class="pp-field">
                <label for="office_type">অফিসের ধরন</label>
                <select id="office_type" name="office_type">
                    <?php foreach (['union' => 'ইউনিয়ন পরিষদ', 'municipality' => 'পৌরসভা', 'city_corporation' => 'সিটি কর্পোরেশন'] as $v => $l): ?>
                        <option value="<?= e($v) ?>"<?= ($profile['office_type'] ?? '') === $v ? ' selected' : '' ?>><?= e($l) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="pp-field">
                <label for="office_name">অফিসের নাম <span class="pp-req">*</span></label>
                <input type="text" id="office_name" name="office_name" value="<?= e((string) ($profile['office_name'] ?? '')) ?>" placeholder="যেমন: বালুয়াহাট ইউনিয়ন পরিষদ" required>
            </div>
            <div class="pp-field">
                <label for="chairman_name">চেয়ারম্যান / প্রশাসকের নাম <span class="pp-req">*</span></label>
                <input type="text" id="chairman_name" name="chairman_name" value="<?= e((string) ($profile['chairman_name'] ?? '')) ?>" required>
            </div>
            <div class="pp-field">
                <label for="union_code">ইউনিয়ন কোড (৭ ডিজিট) <span class="pp-req">*</span></label>
                <input type="text" id="union_code" name="union_code" value="<?= e((string) ($profile['union_code'] ?? '')) ?>" inputmode="numeric" maxlength="7" placeholder="যেমন: 1234567" required>
                <span class="pp-help">সনদ নম্বরের ২য় অংশ — ১৭ ডিজিটের নম্বর তৈরি করতে ব্যবহৃত হয়।</span>
            </div>
            <div class="pp-field">
                <label for="upazila">উপজেলা / থানা <span class="pp-req">*</span></label>
                <input type="text" id="upazila" name="upazila" value="<?= e((string) ($profile['upazila'] ?? '')) ?>" required>
            </div>
            <div class="pp-field">
                <label for="district">জেলা <span class="pp-req">*</span></label>
                <input type="text" id="district" name="district" value="<?= e((string) ($profile['district'] ?? '')) ?>" required>
            </div>
            <div class="pp-field">
                <label for="postcode">পোস্ট কোড</label>
                <input type="text" id="postcode" name="postcode" value="<?= e((string) ($profile['postcode'] ?? '')) ?>" inputmode="numeric">
            </div>
            <div class="pp-field">
                <label for="phone">ফোন</label>
                <input type="tel" id="phone" name="phone" value="<?= e((string) ($profile['phone'] ?? '')) ?>" inputmode="tel">
            </div>
            <div class="pp-field">
                <label for="email">ইমেইল</label>
                <input type="email" id="email" name="email" value="<?= e((string) ($profile['email'] ?? '')) ?>">
            </div>
            <div class="pp-field">
                <label for="website">ওয়েবসাইট</label>
                <input type="text" id="website" name="website" value="<?= e((string) ($profile['website'] ?? '')) ?>" placeholder="example.gov.bd">
            </div>
        </div>

        <div class="pp-card-head" style="margin-top:22px"><div><h2>লোগো ও সিল</h2><p class="pp-card-sub">ঐচ্ছিক — না দিলে সরকারি লোগো ব্যবহার হবে।</p></div></div>
        <div class="pp-form-grid">
            <div class="pp-field">
                <label for="logo">লোগো (JPG/PNG/WEBP)</label>
                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
                    <img class="pp-office-logo" src="<?= e($logoUri) ?>" alt="বর্তমান লোগো">
                    <input type="file" id="logo" name="logo" accept="image/jpeg,image/png,image/webp">
                </div>
            </div>
            <div class="pp-field">
                <label for="stamp">সিল / স্বাক্ষর (JPG/PNG/WEBP)</label>
                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
                    <?php if ($stampUri !== ''): ?><img class="pp-office-logo" src="<?= e($stampUri) ?>" alt="বর্তমান সিল"><?php else: ?><span class="pp-badge">সিল নেই</span><?php endif; ?>
                    <input type="file" id="stamp" name="stamp" accept="image/jpeg,image/png,image/webp">
                </div>
            </div>
        </div>
        <p class="pp-help" style="margin-top:8px">ছবি সর্বোচ্চ ৩ MB। লোগো সনদের হেডারে, সিল স্বাক্ষরের পাশে বসবে।</p>

        <?php if (strlen((string) ($profile['union_code'] ?? '')) !== 7): ?>
            <div class="pp-alert pp-alert-error" style="margin-top:16px">ইউনিয়ন কোড ৭ ডিজিট না থাকলে নতুন সনদ নম্বরে <b>0000000</b> ব্যবহার হবে। সঠিক কোড দিন।</div>
        <?php endif; ?>

        <div class="pp-actions" style="margin-top:20px">
            <span class="pp-help">সব তথ্য সনদের snapshot হিসেবে সংরক্ষিত হবে; পরে বদলালে পুরাতন সনদ বদলাবে না।</span>
            <button type="submit" class="pp-btn pp-btn-primary">সংরক্ষণ করুন</button>
        </div>
    </form>
<?php endif; ?>
<?php prottoyon_page_end(); ?>
