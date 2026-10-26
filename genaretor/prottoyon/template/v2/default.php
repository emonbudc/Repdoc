<?php
declare(strict_types=1);

$p = $data['personal'] ?? [];
$present = $data['present'] ?? [];
$permanent = $data['permanent'] ?? [];
$name = $isEn ? (trim((string) ($p['name_en'] ?? '')) ?: (string) ($p['name_bn'] ?? '')) : (string) ($p['name_bn'] ?? '');
$father = $isEn ? ((string) ($p['father_en'] ?? '') ?: (string) ($p['father_bn'] ?? '')) : (string) ($p['father_bn'] ?? '');
$mother = $isEn ? ((string) ($p['mother_en'] ?? '') ?: (string) ($p['mother_bn'] ?? '')) : (string) ($p['mother_bn'] ?? '');
$addrPresent = prottoyon_certificate_address($data, 'present', $isEn);
$addrPermanent = prottoyon_certificate_address($data, 'permanent', $isEn);
$dateOut = static fn(string $value): string => $isEn ? prottoyon_certificate_date_en($value) : prottoyon_format_date($value);
$label = static fn(string $bn, string $en): string => $isEn ? $en : $bn;

$rows = [];
$rows[] = [$label('নাম', 'Name'), $name];
if ($father !== '') $rows[] = [$label('পিতা', 'Father'), $father];
if ($mother !== '') $rows[] = [$label('মাতা', 'Mother'), $mother];
if (($p['dob'] ?? '') !== '') $rows[] = [$label('জন্ম তারিখ', 'Date of birth'), $dateOut((string) $p['dob'])];
if (($p['nid'] ?? '') !== '') $rows[] = [$label('জাতীয় পরিচয়পত্র নম্বর', 'NID no.'), $isEn ? (string) $p['nid'] : prottoyon_bn_digits((string) $p['nid'])];
if (($p['brn'] ?? '') !== '') $rows[] = [$label('জন্ম নিবন্ধন নম্বর', 'Birth registration no.'), $isEn ? (string) $p['brn'] : prottoyon_bn_digits((string) $p['brn'])];
if (($p['passport_no'] ?? '') !== '') $rows[] = [$label('পাসপোর্ট নম্বর', 'Passport no.'), (string) $p['passport_no']];
if (($p['occupation'] ?? '') !== '') $rows[] = [$label('পেশা', 'Occupation'), (string) $p['occupation']];
if (($p['resident_status'] ?? '') !== '') $rows[] = [$label('বসবাসের ধরন', 'Resident status'), (string) $p['resident_status']];
if (($p['religion'] ?? '') !== '') $rows[] = [$label('ধর্ম', 'Religion'), (string) $p['religion']];
if (($p['gender'] ?? '') !== '') $rows[] = [$label('লিঙ্গ', 'Gender'), (string) $p['gender']];
if (($p['mobile'] ?? '') !== '') $rows[] = [$label('মোবাইল', 'Mobile'), $isEn ? (string) $p['mobile'] : prottoyon_bn_digits((string) $p['mobile'])];
if ($addrPresent !== '') $rows[] = [$label('বর্তমান ঠিকানা', 'Present address'), $addrPresent];
if ($addrPermanent !== '') $rows[] = [$label('স্থায়ী ঠিকানা', 'Permanent address'), $addrPermanent];
if (($data['purpose'] ?? '') !== '') $rows[] = [$label('উদ্দেশ্য', 'Purpose'), (string) $data['purpose']];

foreach (($type['fields'] ?? []) as $field) {
    $key = $field['k'];
    $value = trim((string) ($data['extra'][$key] ?? ''));
    if ($value === '') continue;
    $fieldLabel = $isEn ? ucwords(str_replace(['-', '_'], ' ', $key)) : $field['label'];
    if (in_array($field['type'], ['date'], true)) $value = $dateOut($value);
    elseif (!$isEn && in_array($field['type'], ['number'], true)) $value = prottoyon_bn_digits($value);
    $rows[] = [$fieldLabel, $value];
}

$members = $data['members'] ?? [];
$fees = $data['fees'] ?? [];
$isWarish = ($type['members'] ?? '') === 'warish';
?>
<div class="pv2-sheet">
    <?php if ($revoked): ?><div class="pv2-revoked"><?= $isEn ? 'REVOKED' : 'বাতিল' ?></div><?php endif; ?>
    <div class="pv2-watermark" aria-hidden="true"><?= e($office['office_name'] ?: 'প্রত্যয়ন') ?></div>

    <header class="pv2-head">
        <div class="pv2-logo"><img src="<?= e($logo) ?>" alt=""></div>
        <div class="pv2-head-copy">
            <div class="pv2-gov"><?= $isEn ? 'Government of the People’s Republic of Bangladesh' : 'গণপ্রজাতন্ত্রী বাংলাদেশ সরকার' ?></div>
            <h1><?= e($office['office_name'] ?: $label('ইউনিয়ন পরিষদ', 'Union Parishad')) ?></h1>
            <p><?= e(trim(($office['upazila'] ?: '') . ', ' . ($office['district'] ?: ''), ', ')) ?><?= ($office['upazila'] || $office['district']) ? ', ' . ($isEn ? 'Bangladesh' : 'বাংলাদেশ') : '' ?></p>
            <?php if ($office['phone'] || $office['email'] || $office['website']): ?>
                <p class="pv2-contact"><?= e(implode(' · ', array_filter([$office['phone'], $office['email'], $office['website']]))) ?></p>
            <?php endif; ?>
        </div>
        <?php if ($stamp !== ''): ?><div class="pv2-head-stamp"><img src="<?= e($stamp) ?>" alt=""></div><?php endif; ?>
    </header>

    <div class="pv2-title">
        <span class="pv2-title-rule"></span>
        <h2><?= e($title) ?></h2>
        <span class="pv2-title-en"><?= $isEn ? 'Certificate' : 'Certificate' ?></span>
    </div>

    <div class="pv2-meta">
        <span><?= $isEn ? 'Certificate No.' : 'সনদ নম্বর' ?>: <b><?= e($certNo) ?></b></span>
        <span><?= $isEn ? 'Issue date' : 'ইস্যুর তারিখ' ?>: <b><?= e($dateOut($issueDate)) ?></b></span>
        <?php if (!empty($data['meta']['app_id'])): ?><span><?= $isEn ? 'Application' : 'আবেদন' ?>: <b><?= e((string) $data['meta']['app_id']) ?></b></span><?php endif; ?>
    </div>

    <div class="pv2-body">
        <p class="pv2-para"><?= nl2br(e(prottoyon_certificate_paragraph($data, $typeKey, $isEn))) ?></p>

        <table class="pv2-info">
            <?php foreach ($rows as [$rowLabel, $rowValue]): ?>
                <tr><th><?= e($rowLabel) ?></th><td><?= e($rowValue) ?></td></tr>
            <?php endforeach; ?>
        </table>

        <?php if ($members !== []): ?>
            <table class="pv2-table">
                <thead><tr>
                    <th>#</th><th><?= $label('নাম', 'Name') ?></th><th><?= $label('সম্পর্ক', 'Relation') ?></th>
                    <th><?= $label('বয়স', 'Age') ?></th><th><?= $label('এনআইডি', 'NID') ?></th>
                    <th><?= $isWarish ? $label('অংশ', 'Share') : $label('পেশা', 'Occupation') ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($members as $i => $member): ?>
                    <tr>
                        <td><?= e($isEn ? (string) ($i + 1) : prottoyon_bn_digits((string) ($i + 1))) ?></td>
                        <td><?= e((string) ($member['name'] ?? '')) ?></td>
                        <td><?= e((string) ($member['relation'] ?? '')) ?></td>
                        <td><?= e($isEn ? (string) ($member['age'] ?? '') : prottoyon_bn_digits((string) ($member['age'] ?? ''))) ?></td>
                        <td><?= e((string) ($member['nid'] ?? '')) ?></td>
                        <td><?= e((string) ($isWarish ? ($member['share'] ?? '') : ($member['occupation'] ?? ''))) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if ($fees !== []): ?>
            <table class="pv2-table pv2-fees">
                <thead><tr><th><?= $label('ফি/ট্যাক্সের বিবরণ', 'Fee / tax description') ?></th><th><?= $label('টাকা', 'Amount (BDT)') ?></th></tr></thead>
                <tbody>
                <?php foreach ($fees as $fee): ?>
                    <tr><td><?= e((string) ($fee['name'] ?? '')) ?></td><td><?= e($isEn ? (string) ($fee['amount'] ?? '') : prottoyon_bn_digits((string) ($fee['amount'] ?? ''))) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p class="pv2-note"><?= $label('উপরের ফি/ট্যাক্স ঘোষণামূলক; পরিশোধের প্রমাণ পৃথকভাবে সংরক্ষণ করুন।', 'The fees/taxes above are declaratory; keep payment evidence separately.') ?></p>
        <?php endif; ?>
    </div>

    <div class="pv2-bottom">
        <div class="pv2-qr">
            <div id="pv2Qr"></div>
            <div class="pv2-qr-fallback"><?= e($verifyUrl) ?></div>
        </div>
        <div class="pv2-sign">
            <?php if ($stamp !== ''): ?><img class="pv2-sign-stamp" src="<?= e($stamp) ?>" alt=""><?php endif; ?>
            <div class="pv2-sign-line"></div>
            <strong><?= e($office['chairman_name'] ?: $label('চেয়ারম্যানের নাম', 'Chairman')) ?></strong>
            <span><?= $office['office_type'] === 'union' ? $label('চেয়ারম্যান', 'Chairman') : $label('প্রশাসক', 'Administrator') ?></span>
            <span><?= e($office['office_name'] ?: '') ?></span>
        </div>
    </div>

    <footer class="pv2-footer">
        <p><b><?= $label('নির্দেশনা:', 'Instructions:') ?></b>
            <?= $label('এই সনদের সত্যতা যাচাই করুন QR কোড স্ক্যান করে অথবা নিচের ঠিকানায়।', 'Verify this certificate by scanning the QR code or visiting the address below.') ?>
        </p>
        <p><?= e($verifyUrl) ?> · <?= e($office['website'] ?: '') ?></p>
    </footer>
</div>
