<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
require_login();

$me = current_user();
$typeKey = isset($_GET['type']) && is_string($_GET['type']) ? trim($_GET['type']) : '';
$type = prottoyon_type($typeKey);
if ($type === null) {
    flash_set('error', 'সনদের ধরন পাওয়া যায়নি।');
    redirect(repdoc_url('genaretor/prottoyon/'));
}

if (is_udc() && ($me['role'] ?? '') !== 'admin') {
    $profile = office_profile();
    if (($profile['office_name'] ?? '') === '' || ($profile['upazila'] ?? '') === '' || ($profile['district'] ?? '') === '') {
        flash_set('error', 'আগে অফিস সেটিংস সম্পূর্ণ করুন — নিজের অফিস ছাড়া প্রত্যয়ন দেওয়া যাবে না।');
        redirect(repdoc_url('genaretor/prottoyon/office.php'));
    }
}

$data = [
    'v' => 2,
    'type' => $typeKey,
    'personal' => prottoyon_default_personal(),
    'present' => prottoyon_default_address(),
    'permanent' => prottoyon_default_address(),
    'extra' => [],
    'members' => [],
    'fees' => [],
    'documents' => [],
    'purpose' => '',
    'meta' => [],
];

$old = $_SESSION['prottoyon_old'] ?? null;
if (is_array($old) && ($old['type'] ?? '') === $typeKey) {
    $data = array_replace_recursive($data, $old);
}
unset($_SESSION['prottoyon_old']);
$errors = $_SESSION['prottoyon_errors'] ?? [];
unset($_SESSION['prottoyon_errors']);

$reapplyId = (int) ($_GET['reapply'] ?? 0);
if ($old === null && $reapplyId > 0 && repdoc_db()) {
    try {
        $st = repdoc_db()->prepare('SELECT * FROM document_logs WHERE id = ? AND user_id = ?');
        $st->execute([$reapplyId, $me['id']]);
        $row = $st->fetch();
        if ($row && str_starts_with((string) $row['tool_key'], 'prottoyon_')) {
            $decoded = prottoyon_decode_row($row);
            if ((int) ($decoded['v'] ?? 1) >= 2) {
                $decoded['type'] = $typeKey;
                $data = array_replace_recursive($data, $decoded);
            }
        }
    } catch (Throwable $e) {}
}

$submissionToken = bin2hex(random_bytes(16));
$_SESSION['pp_tokens'][$submissionToken] = ['type' => $typeKey, 'time' => time()];
if (count($_SESSION['pp_tokens'] ?? []) > 20) {
    $_SESSION['pp_tokens'] = array_slice($_SESSION['pp_tokens'], -20, null, true);
}
$price = tool_price(prottoyon_tool_key($typeKey));
$balance = user_balance((int) $me['id']);
$val = static fn(string $section, string $key, string $default = ''): string => (string) ($data[$section][$key] ?? $default);
$field = static function (string $name, string $label, string $value = '', string $type = 'text', bool $required = false, array $attrs = []): void {
    $id = 'f_' . preg_replace('/[^a-z0-9]+/i', '_', $name);
    $requiredAttr = $required ? ' required' : '';
    $options = $attrs['options'] ?? [];
    unset($attrs['options']);
    $attr = '';
    foreach ($attrs as $k => $v) $attr .= ' ' . e($k) . '="' . e((string) $v) . '"';
    echo '<div class="pp-field' . ($type === 'textarea' ? ' pp-field-full' : '') . '">';
    echo '<label for="' . e($id) . '">' . e($label) . ($required ? ' <span class="pp-req">*</span>' : '') . '</label>';
    if ($type === 'textarea') {
        echo '<textarea id="' . e($id) . '" name="' . e($name) . '"' . $requiredAttr . $attr . '>' . e($value) . '</textarea>';
    } elseif ($type === 'select') {
        echo '<select id="' . e($id) . '" name="' . e($name) . '"' . $requiredAttr . $attr . '>';
        foreach ($options as $ov => $ol) echo '<option value="' . e((string) $ov) . '"' . ((string) $ov === $value ? ' selected' : '') . '>' . e((string) $ol) . '</option>';
        echo '</select>';
    } else {
        echo '<input id="' . e($id) . '" type="' . e($type) . '" name="' . e($name) . '" value="' . e($value) . '"' . $requiredAttr . $attr . '>';
    }
    echo '</div>';
};
$addressFields = [
    'village_bn' => 'গ্রাম / মহল্লা', 'rbs_bn' => 'রোড / ব্লক / সেক্টর', 'holding_no' => 'হোল্ডিং নং',
    'ward' => 'ওয়ার্ড নং', 'post_office_bn' => 'পোস্ট অফিস', 'union_bn' => 'ইউনিয়ন',
    'upazila_bn' => 'উপজেলা / থানা', 'district_bn' => 'জেলা', 'division_bn' => 'বিভাগ', 'postcode' => 'পোস্ট কোড',
    'village_en' => 'Village / Area (English)', 'rbs_en' => 'Road / Block / Sector (English)',
    'post_office_en' => 'Post office (English)', 'union_en' => 'Union (English)',
    'upazila_en' => 'Upazila (English)', 'district_en' => 'District (English)',
];
$memberRows = $data['members'] !== [] ? $data['members'] : array_fill(0, 3, ['name' => '', 'relation' => '', 'age' => '', 'nid' => '', 'share' => '', 'occupation' => '']);
$feeRows = $data['fees'] !== [] ? $data['fees'] : array_fill(0, 3, ['name' => '', 'amount' => '']);

prottoyon_page_start($type['title'], 'form');
?>
<nav class="pp-toolbar" aria-label="ধাপ">
    <div>
        <span class="pp-kicker">ধাপ ২ · তথ্য পূরণ</span>
        <h1 style="margin:0;font-size:1.5rem"><?= e($type['title']) ?></h1>
        <p class="pp-card-sub"><?= e($type['title_en']) ?> · <?= e($type['description']) ?></p>
    </div>
    <a class="pp-btn" href="<?= e(repdoc_url('genaretor/prottoyon/')) ?>">← সনদ বদলান</a>
</nav>

<?php if ($errors): ?>
    <div class="pp-alert pp-alert-error" role="alert">
        <strong>সংরক্ষণ করা যায়নি:</strong>
        <ul style="margin:6px 0 0;padding-left:20px"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="post" action="<?= e(repdoc_url('genaretor/prottoyon/output.php')) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="type" value="<?= e($typeKey) ?>">
    <input type="hidden" name="submission_token" value="<?= e($submissionToken) ?>">

    <section class="pp-card">
        <div class="pp-card-head"><div><h2>আবেদনকারীর তথ্য</h2><p class="pp-card-sub">যে ব্যক্তির জন্য সনদ, তাঁর তথ্য দিন।</p></div><span class="pp-badge">আবশ্যক: নাম ও জন্ম তারিখ</span></div>
        <div class="pp-form-grid">
            <?php
            $field('personal[name_bn]', 'নাম (বাংলা)', $val('personal', 'name_bn'), 'text', true);
            $field('personal[name_en]', 'Name (English)', $val('personal', 'name_en'));
            $field('personal[father_bn]', 'পিতার নাম (বাংলা)', $val('personal', 'father_bn'));
            $field('personal[father_en]', 'Father’s name (English)', $val('personal', 'father_en'));
            $field('personal[mother_bn]', 'মাতার নাম (বাংলা)', $val('personal', 'mother_bn'));
            $field('personal[mother_en]', 'Mother’s name (English)', $val('personal', 'mother_en'));
            $field('personal[dob]', 'জন্ম তারিখ', prottoyon_date_input($val('personal', 'dob')), 'date', true);
            $field('personal[gender]', 'লিঙ্গ', $val('personal', 'gender'), 'select', false, ['options' => ['' => 'নির্বাচন করুন', 'পুরুষ' => 'পুরুষ', 'মহিলা' => 'মহিলা', 'অন্য' => 'অন্য']]);
            $field('personal[religion]', 'ধর্ম', $val('personal', 'religion'), 'select', false, ['options' => ['' => 'নির্বাচন করুন', 'ইসলাম' => 'ইসলাম', 'হিন্দু' => 'হিন্দু', 'খ্রিস্টান' => 'খ্রিস্টান', 'বৌদ্ধ' => 'বৌদ্ধ', 'অন্য' => 'অন্য']]);
            $field('personal[occupation]', 'পেশা', $val('personal', 'occupation'));
            $field('personal[education]', 'শিক্ষা', $val('personal', 'education'));
            $field('personal[marital_status]', 'বৈবাহিক অবস্থা', $val('personal', 'marital_status'), 'select', false, ['options' => ['' => 'নির্বাচন করুন', 'অবিবাহিত' => 'অবিবাহিত', 'বিবাহিত' => 'বিবাহিত', 'বিধবা/বিপত্নীক' => 'বিধবা/বিপত্নীক', 'অন্য' => 'অন্য']]);
            $field('personal[resident_status]', 'বসবাসের ধরন', $val('personal', 'resident_status'), 'select', false, ['options' => ['' => 'নির্বাচন করুন', 'স্থায়ী' => 'স্থায়ী', 'অস্থায়ী' => 'অস্থায়ী']]);
            $field('personal[spouse_name]', 'স্বামী/স্ত্রীর নাম', $val('personal', 'spouse_name'));
            $field('personal[nid]', 'জাতীয় পরিচয়পত্র নম্বর', $val('personal', 'nid'), 'text', false, ['inputmode' => 'numeric']);
            $field('personal[brn]', 'জন্ম নিবন্ধন নম্বর', $val('personal', 'brn'), 'text', false, ['inputmode' => 'numeric']);
            $field('personal[passport_no]', 'পাসপোর্ট নম্বর', $val('personal', 'passport_no'));
            $field('personal[mobile]', 'মোবাইল নম্বর', $val('personal', 'mobile'), 'tel', false, ['inputmode' => 'tel']);
            $field('personal[email]', 'ইমেইল', $val('personal', 'email'), 'email');
            $field('personal[applicant_name]', 'আবেদনকারীর নাম (যদি ভিন্ন)', $val('personal', 'applicant_name'));
            $field('personal[applicant_phone]', 'আবেদনকারীর মোবাইল', $val('personal', 'applicant_phone'), 'tel');
            ?>
        </div>
    </section>

    <section class="pp-card">
        <div class="pp-card-head"><div><h2>ঠিকানা</h2><p class="pp-card-sub">বর্তমান ও স্থায়ী ঠিকানা — সনদে দুটোই ছাপা হবে।</p></div></div>
        <label class="pp-check"><input type="checkbox" id="same_address" name="same_address" value="1"<?= ($data['present'] == $data['permanent'] && implode('', $data['present']) !== '') ? ' checked' : '' ?>> স্থায়ী ঠিকানা বর্তমান ঠিকানার মতোই</label>
        <h3 style="margin:16px 0 8px;font-size:.95rem">বর্তমান ঠিকানা</h3>
        <div class="pp-address-grid">
            <?php foreach ($addressFields as $key => $label): ?>
                <div class="pp-field"><label for="present_<?= e($key) ?>"><?= e($label) ?><?= in_array($key, ['village_bn', 'upazila_bn', 'district_bn'], true) ? ' <span class="pp-req">*</span>' : '' ?></label><input id="present_<?= e($key) ?>" name="present[<?= e($key) ?>]" value="<?= e($val('present', $key)) ?>"<?= in_array($key, ['village_bn', 'upazila_bn', 'district_bn'], true) ? ' required' : '' ?>></div>
            <?php endforeach; ?>
        </div>
        <div class="pp-field pp-field-full" style="margin-top:12px"><label for="present_address_bn">ঠিকানার বিস্তারিত</label><textarea id="present_address_bn" name="present[address_bn]"><?= e($val('present', 'address_bn')) ?></textarea></div>

        <h3 style="margin:18px 0 8px;font-size:.95rem">স্থায়ী ঠিকানা</h3>
        <div class="pp-address-grid">
            <?php foreach ($addressFields as $key => $label): ?>
                <div class="pp-field"><label for="permanent_<?= e($key) ?>"><?= e($label) ?></label><input id="permanent_<?= e($key) ?>" name="permanent[<?= e($key) ?>]" value="<?= e($val('permanent', $key)) ?>"></div>
            <?php endforeach; ?>
        </div>
        <div class="pp-field pp-field-full" style="margin-top:12px"><label for="permanent_address_bn">ঠিকানার বিস্তারিত</label><textarea id="permanent_address_bn" name="permanent[address_bn]"><?= e($val('permanent', 'address_bn')) ?></textarea></div>
    </section>

    <?php if ($type['fields'] !== [] || !empty($type['members']) || !empty($type['fees'])): ?>
        <section class="pp-card">
            <div class="pp-card-head"><div><h2><?= e($type['title']) ?>-এর তথ্য</h2><p class="pp-card-sub">এই সনদের জন্য প্রয়োজনীয় তথ্য দিন।</p></div></div>
            <?php if ($type['fields'] !== []): ?>
                <div class="pp-form-grid">
                    <?php foreach ($type['fields'] as $extraField): ?>
                        <?php
                        $key = $extraField['k'];
                        $value = (string) ($data['extra'][$key] ?? '');
                        if ($extraField['type'] === 'date') $value = prottoyon_date_input($value);
                        $attrs = [];
                        if ($extraField['type'] === 'select') $attrs['options'] = $extraField['options'];
                        $field('extra[' . $key . ']', $extraField['label'], $value, $extraField['type'], (bool) $extraField['required'], $attrs);
                        ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($type['fees'])): ?>
                <h3 style="margin:18px 0 8px;font-size:.95rem">ফি / ট্যাক্সের বিবরণ</h3>
                <div class="pp-table-wrap">
                    <table class="pp-table">
                        <thead><tr><th style="width:44px">#</th><th>বিবরণ</th><th style="width:160px">টাকা</th><th style="width:54px"></th></tr></thead>
                        <tbody id="ppFeeRows">
                        <?php foreach ($feeRows as $i => $fee): ?>
                            <tr><td data-row-no><?= $i + 1 ?></td><td><input name="fees[name][]" value="<?= e((string) ($fee['name'] ?? '')) ?>" placeholder="যেমন: ট্রেড লাইসেন্স ফি"></td><td><input name="fees[amount][]" value="<?= e((string) ($fee['amount'] ?? '')) ?>" inputmode="decimal"></td><td><button type="button" class="pp-btn" data-remove-row aria-label="সরান">×</button></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="pp-btn pp-add-row" data-add-fee>+ ফি যোগ করুন</button>
            <?php endif; ?>

            <?php if (!empty($type['members'])): ?>
                <h3 style="margin:18px 0 8px;font-size:.95rem"><?= ($type['members'] ?? '') === 'warish' ? 'ওয়ারিশদের তালিকা' : 'পারিবারিক সদস্য' ?></h3>
                <div class="pp-table-wrap">
                    <table class="pp-table">
                        <thead><tr><th style="width:44px">#</th><th>নাম</th><th>সম্পর্ক</th><th style="width:80px">বয়স</th><th>এনআইডি</th><th><?= ($type['members'] ?? '') === 'warish' ? 'অংশ' : 'পেশা' ?></th><th style="width:54px"></th></tr></thead>
                        <tbody id="ppMemberRows">
                        <?php foreach ($memberRows as $i => $member): ?>
                            <tr><td data-row-no><?= $i + 1 ?></td><td><input name="members[name][]" value="<?= e((string) ($member['name'] ?? '')) ?>"></td><td><input name="members[relation][]" value="<?= e((string) ($member['relation'] ?? '')) ?>" placeholder="পিতা/মাতা/পুত্র…"></td><td><input name="members[age][]" value="<?= e((string) ($member['age'] ?? '')) ?>" inputmode="numeric"></td><td><input name="members[nid][]" value="<?= e((string) ($member['nid'] ?? '')) ?>" inputmode="numeric"></td><td><input name="members[<?= ($type['members'] ?? '') === 'warish' ? 'share' : 'occupation' ?>][]" value="<?= e((string) (($type['members'] ?? '') === 'warish' ? ($member['share'] ?? '') : ($member['occupation'] ?? ''))) ?>"></td><td><button type="button" class="pp-btn" data-remove-row aria-label="সরান">×</button></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="pp-btn pp-add-row" data-add-member>+ সদস্য যোগ করুন</button>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <section class="pp-card">
        <div class="pp-card-head"><div><h2>উদ্দেশ্য ও সংযুক্তি</h2><p class="pp-card-sub">ঐচ্ছিক, তবে সনদে উদ্দেশ্য লেখা থাকলে সুবিধা হয়।</p></div></div>
        <div class="pp-form-grid">
            <div class="pp-field pp-field-full"><label for="purpose">আবেদনের উদ্দেশ্য</label><textarea id="purpose" name="purpose"><?= e((string) ($data['purpose'] ?? '')) ?></textarea></div>
            <div class="pp-field"><label for="ppPhoto">ফটো (ঐচ্ছিক)</label><input id="ppPhoto" type="file" name="photo" accept="image/jpeg,image/png,image/webp"><span class="pp-help" id="ppPhotoName">JPG, PNG বা WEBP, সর্বোচ্চ ২ MB।</span></div>
            <div class="pp-field"><label for="ppDocs">সংযুক্তি (একাধিক)</label><input id="ppDocs" type="file" name="documents[]" accept="image/jpeg,image/png,image/webp,application/pdf" multiple><span class="pp-help">NID/জন্মনিবন্ধন/সহায়ক কাগজ — PDF বা ছবি, সর্বোচ্চ ৫টি।</span></div>
        </div>
        <p class="pp-note" style="margin-top:14px">জমা দিলে সনদ স্বয়ংক্রিয়ভাবে তৈরি হবে। এরপর প্রিন্ট বা “Save as PDF” দিয়ে সংরক্ষণ করুন। কোনো অনুমোদনের অপেক্ষা নেই।</p>
    </section>

    <div class="pp-actions">
        <div class="pp-price-note">
            এই সনদের মূল্য: <b><?= $price > 0 ? prottoyon_bn_digits((string) $price) . ' ক্রেডিট' : 'ফ্রি' ?></b>
            · আপনার ব্যালেন্স: <?= prottoyon_bn_digits((string) $balance) ?>
            <?php if ($price > $balance && ($me['role'] ?? '') !== 'admin'): ?><br><span style="color:var(--accent)">ব্যালেন্স যথেষ্ট নয় — অ্যাডমিনের সাথে যোগাযোগ করুন।</span><?php endif; ?>
        </div>
        <button type="submit" class="pp-btn pp-btn-primary"<?= ($price > $balance && ($me['role'] ?? '') !== 'admin') ? ' disabled' : '' ?>>আবেদন জমা দিন ও সনদ তৈরি করুন →</button>
    </div>
</form>

<template id="ppMemberTemplate">
    <tr><td data-row-no>1</td><td><input name="members[name][]" value=""></td><td><input name="members[relation][]" value=""></td><td><input name="members[age][]" value="" inputmode="numeric"></td><td><input name="members[nid][]" value="" inputmode="numeric"></td><td><input name="members[<?= ($type['members'] ?? '') === 'warish' ? 'share' : 'occupation' ?>][]" value=""></td><td><button type="button" class="pp-btn" data-remove-row aria-label="সরান">×</button></td></tr>
</template>
<template id="ppFeeTemplate">
    <tr><td data-row-no>1</td><td><input name="fees[name][]" value=""></td><td><input name="fees[amount][]" value="" inputmode="decimal"></td><td><button type="button" class="pp-btn" data-remove-row aria-label="সরান">×</button></td></tr>
</template>
<?php prottoyon_page_end(); ?>
