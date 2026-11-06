<?php
declare(strict_types=1);

/**
 * Repdoc — UDC panel: office identity settings.
 * Logo, seal and issuer details used on every generated attestation.
 */

require_once __DIR__ . '/includes/layout.php';

$me = current_user();
$canEdit = is_udc() || is_admin();
$profile = office_profile();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canEdit) {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'নিরাপত্তা টোকেন মিলেনি — আবার চেষ্টা করুন।');
        redirect(repdoc_url('Entrepreneur/panel/office.php'));
    }

    $unionCode = preg_replace('/\D/', '', prottoyon_en_digits((string) ($_POST['union_code'] ?? ''))) ?? '';
    if (strlen($unionCode) !== 7) {
        flash_set('error', 'ইউনিয়ন কোড ঠিক ৭ ডিজিটের হতে হবে।');
        redirect(repdoc_url('Entrepreneur/panel/office.php'));
    }

    $logoPath = (string) ($profile['logo_path'] ?? '');
    $stampPath = (string) ($profile['stamp_path'] ?? '');
    if (isset($_FILES['logo']) && is_array($_FILES['logo'])) {
        $stored = prottoyon_store_office_upload($_FILES['logo'], (int) $me['id'], 'logo');
        if (!empty($stored['ok'])) $logoPath = (string) $stored['path'];
        elseif (empty($stored['skip'])) {
            flash_set('error', (string) ($stored['message'] ?? 'লোগো আপলোড হয়নি।'));
            redirect(repdoc_url('Entrepreneur/panel/office.php'));
        }
    }
    if (isset($_FILES['stamp']) && is_array($_FILES['stamp'])) {
        $stored = prottoyon_store_office_upload($_FILES['stamp'], (int) $me['id'], 'stamp');
        if (!empty($stored['ok'])) $stampPath = (string) $stored['path'];
        elseif (empty($stored['skip'])) {
            flash_set('error', (string) ($stored['message'] ?? 'সিল আপলোড হয়নি।'));
            redirect(repdoc_url('Entrepreneur/panel/office.php'));
        }
    }

    $saved = save_office_profile((int) $me['id'], [
        'office_type' => (string) ($_POST['office_type'] ?? 'union'),
        'office_name' => prottoyon_clean($_POST['office_name'] ?? '', 190),
        'office_name_en' => prottoyon_clean($_POST['office_name_en'] ?? '', 190),
        'chairman_name' => prottoyon_clean($_POST['chairman_name'] ?? '', 190),
        'chairman_name_en' => prottoyon_clean($_POST['chairman_name_en'] ?? '', 190),
        'upazila' => prottoyon_clean($_POST['upazila'] ?? '', 190),
        'upazila_en' => prottoyon_clean($_POST['upazila_en'] ?? '', 190),
        'district' => prottoyon_clean($_POST['district'] ?? '', 190),
        'district_en' => prottoyon_clean($_POST['district_en'] ?? '', 190),
        'union_code' => $unionCode,
        'postcode' => prottoyon_clean($_POST['postcode'] ?? '', 16),
        'phone' => prottoyon_clean($_POST['phone'] ?? '', 32),
        'email' => prottoyon_clean($_POST['email'] ?? '', 190),
        'website' => prottoyon_clean($_POST['website'] ?? '', 190),
        'logo_path' => $logoPath,
        'stamp_path' => $stampPath,
    ]);
    flash_set($saved ? 'ok' : 'error', $saved ? 'অফিস তথ্য সংরক্ষণ হয়েছে।' : 'সংরক্ষণ হয়নি — আবার চেষ্টা করুন।');
    redirect(repdoc_url('Entrepreneur/panel/office.php'));
}

$profile = office_profile();
$logoUri = prottoyon_office_logo_uri($profile);
$stampUri = prottoyon_office_stamp_uri($profile);

udc_page_start('অফিস সেটিংস', 'office.php');
udc_notice();
?>

<div class="admin-page-head">
    <div>
        <p class="kicker">UDC Panel</p>
        <h1 class="admin-title">অফিস সেটিংস</h1>
        <p class="admin-sub">এই তথ্য আপনার তৈরি প্রতিটি সনদের হেডার, সনদ নম্বর ও স্বাক্ষরে ব্যবহার হবে।</p>
    </div>
    <a class="btn btn-secondary btn-sm" href="<?= e(repdoc_url('genaretor/prottoyon/')) ?>">প্রত্যয়নে ফিরুন</a>
</div>

<?php if (!$canEdit): ?>
    <div class="alert alert-error" role="alert">এই পেজ শুধু অনুমোদিত উদ্যোক্তা (UDC) ও অ্যাডমিনের জন্য।</div>
<?php else: ?>
    <div class="panel">
        <div class="panel-head">অফিসের পরিচয়</div>
        <div class="panel-body">
            <form method="post" action="office.php" enctype="multipart/form-data">
                <?= csrf_field() ?>

                <div class="field-row">
                    <div class="field">
                        <label for="office_type">অফিসের ধরন</label>
                        <select id="office_type" name="office_type">
                            <?php foreach (['union' => 'ইউনিয়ন পরিষদ', 'municipality' => 'পৌরসভা', 'city_corporation' => 'সিটি কর্পোরেশন'] as $v => $l): ?>
                                <option value="<?= e($v) ?>"<?= ($profile['office_type'] ?? '') === $v ? ' selected' : '' ?>><?= e($l) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="office_name">অফিসের নাম <span style="color:var(--danger)">*</span></label>
                        <input type="text" id="office_name" name="office_name" value="<?= e((string) ($profile['office_name'] ?? '')) ?>" placeholder="যেমন: বালুয়াহাট ইউনিয়ন পরিষদ" required>
                    </div>
                </div>

                <div class="field-row">
                    <div class="field">
                        <label for="office_name_en">Office name (English)</label>
                        <input type="text" id="office_name_en" name="office_name_en" value="<?= e((string) ($profile['office_name_en'] ?? '')) ?>" placeholder="e.g. Baluahat Union Parishad">
                    </div>
                    <div class="field">
                        <label for="chairman_name_en">Chairman name (English)</label>
                        <input type="text" id="chairman_name_en" name="chairman_name_en" value="<?= e((string) ($profile['chairman_name_en'] ?? '')) ?>">
                    </div>
                </div>

                <div class="field-row">
                    <div class="field">
                        <label for="chairman_name">চেয়ারম্যান / প্রশাসকের নাম <span style="color:var(--danger)">*</span></label>
                        <input type="text" id="chairman_name" name="chairman_name" value="<?= e((string) ($profile['chairman_name'] ?? '')) ?>" required>
                    </div>
                    <div class="field">
                        <label for="union_code">ইউনিয়ন কোড (৭ ডিজিট) <span style="color:var(--danger)">*</span></label>
                        <input type="text" id="union_code" name="union_code" value="<?= e((string) ($profile['union_code'] ?? '')) ?>" inputmode="numeric" maxlength="7" placeholder="যেমন: 1234567" required>
                        <span class="hint">সনদ নম্বরের ২য় অংশ — ১৭ ডিজিটের নম্বর তৈরি করতে ব্যবহৃত হয়।</span>
                    </div>
                </div>

                <div class="field-row">
                    <div class="field">
                        <label for="upazila_en">Upazila (English)</label>
                        <input type="text" id="upazila_en" name="upazila_en" value="<?= e((string) ($profile['upazila_en'] ?? '')) ?>">
                    </div>
                    <div class="field">
                        <label for="district_en">District (English)</label>
                        <input type="text" id="district_en" name="district_en" value="<?= e((string) ($profile['district_en'] ?? '')) ?>">
                    </div>
                </div>

                <div class="field-row">
                    <div class="field">
                        <label for="upazila">উপজেলা / থানা <span style="color:var(--danger)">*</span></label>
                        <input type="text" id="upazila" name="upazila" value="<?= e((string) ($profile['upazila'] ?? '')) ?>" required>
                    </div>
                    <div class="field">
                        <label for="district">জেলা <span style="color:var(--danger)">*</span></label>
                        <input type="text" id="district" name="district" value="<?= e((string) ($profile['district'] ?? '')) ?>" required>
                    </div>
                </div>

                <div class="field-row">
                    <div class="field">
                        <label for="postcode">পোস্ট কোড</label>
                        <input type="text" id="postcode" name="postcode" value="<?= e((string) ($profile['postcode'] ?? '')) ?>" inputmode="numeric">
                    </div>
                    <div class="field">
                        <label for="phone">ফোন</label>
                        <input type="tel" id="phone" name="phone" value="<?= e((string) ($profile['phone'] ?? '')) ?>" inputmode="tel">
                    </div>
                </div>

                <div class="field-row">
                    <div class="field">
                        <label for="email">ইমেইল</label>
                        <input type="email" id="email" name="email" value="<?= e((string) ($profile['email'] ?? '')) ?>">
                    </div>
                    <div class="field">
                        <label for="website">ওয়েবসাইট</label>
                        <input type="text" id="website" name="website" value="<?= e((string) ($profile['website'] ?? '')) ?>" placeholder="example.gov.bd">
                    </div>
                </div>

                <div class="panel-head" style="padding:0 0 12px;border:0;background:none;margin-top:8px">লোগো ও সিল</div>

                <div class="field-row">
                    <div class="field">
                        <label for="logo">লোগো (JPG/PNG/WEBP)</label>
                        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
                            <img src="<?= e($logoUri) ?>" alt="বর্তমান লোগো" style="width:64px;height:64px;object-fit:contain;border:1px solid var(--border-color);border-radius:10px;background:#fff">
                            <input type="file" id="logo" name="logo" accept="image/jpeg,image/png,image/webp">
                        </div>
                    </div>
                    <div class="field">
                        <label for="stamp">সিল / স্বাক্ষর (JPG/PNG/WEBP)</label>
                        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
                            <?php if ($stampUri !== ''): ?>
                                <img src="<?= e($stampUri) ?>" alt="বর্তমান সিল" style="width:64px;height:64px;object-fit:contain;border:1px solid var(--border-color);border-radius:10px;background:#fff">
                            <?php else: ?>
                                <span class="hint">সিল নেই</span>
                            <?php endif; ?>
                            <input type="file" id="stamp" name="stamp" accept="image/jpeg,image/png,image/webp">
                        </div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;justify-content:flex-end;gap:16px;flex-wrap:wrap;margin-top:20px">
                    <button type="submit" class="btn btn-primary">সংরক্ষণ করুন</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
<?php udc_page_end();
