<?php
declare(strict_types=1);

$logo = prottoyon_office_logo_uri($office ?? []);
$x = $data['extra'] ?? [];
$v = static fn(string $key): string => trim((string) ($x[$key] ?? ''));
$money = static function (string $value) use ($isEn): string {
    $raw = trim($value);
    if ($raw === '') return $isEn ? '0.00' : '০.০০';
    $text = number_format(prottoyon_pack_amount_number($raw), 2, '.', '');
    return $isEn ? prottoyon_en_digits($text) : prottoyon_bn_digits($text);
};
$digits = static function (string $value) use ($isEn): string {
    return $isEn ? prottoyon_en_digits($value) : prottoyon_bn_digits($value);
};
$dateValue = static function (string $value) use ($isEn): string {
    if (trim($value) === '') return '';
    return $isEn ? prottoyon_certificate_date_en($value) : prottoyon_format_date($value);
};

$photo = $v('photo_image');
if ($photo === '' && !empty($row['photo'])) {
    $photo = prottoyon_document_url((string) $row['photo']);
}
$area = $v('area');
$nature = $c['business_nature'];
if ($isEn) {
    $natureMap = ['একক' => 'Sole', 'যৌথ' => 'Joint', 'অন্যান্য' => 'Other'];
    $nature = $natureMap[$nature] ?? $nature;
}
$wardNo = $v('ward_no');
$tin = $v('tin');
$bin = $v('bin');
$phone = $v('phone');
$email = $v('email');
$scheduleSerial = $v('schedule_serial');
$scheduleText = $v('schedule_text') ?: ($isEn ? 'As per Union Parishad Model Tax Schedule 2013' : 'ইউনিয়ন পরিষদ আদর্শ কর তফিসল ২০১৩ মোতাবেক');
$fiscalYear = $v('fiscal_year') ?: $c['fiscal_year'];
$startDate = $dateValue($v('start_date'));
$expiryText = $v('expiry_text');
if ($expiryText === '') {
    $expiryDate = $c['expiry_date'];
    $expiryText = $isEn
        ? 'The licence shall remain valid up to ' . ($expiryDate !== '' ? $expiryDate : '—') . '.'
        : 'অত্র ট্রেড লাইসেন্স এর মেয়াদ ' . ($fiscalYear !== '' ? $fiscalYear : '—') . ' পর্যন্ত বলবৎ থাকিবে।';
}

$addrFields = [
    'holding' => $isEn ? 'Holding No' : 'হোল্ডিং নং',
    'road' => $isEn ? 'Road No/Name' : 'রোড নং/নাম',
    'village' => $isEn ? 'Village/Area' : 'গ্রাম/মহল্লা',
    'post_office' => $isEn ? 'Post Office' : 'ডাকঘর',
    'post_code' => $isEn ? 'Post Code' : 'পোস্ট কোড',
    'thana' => $isEn ? 'Thana' : 'থানা',
    'upazila' => $isEn ? 'Upazila' : 'উপজেলা',
    'district' => $isEn ? 'District' : 'জেলা',
    'division' => $isEn ? 'Division' : 'বিভাগ',
    'country' => $isEn ? 'Country' : 'দেশ',
];
$addrMap = [
    'holding' => 'holding_no', 'road' => 'rbs_bn', 'village' => 'village_bn',
    'post_office' => 'post_office_bn', 'post_code' => 'postcode', 'thana' => 'upazila_bn',
    'upazila' => 'upazila_bn', 'district' => 'district_bn', 'division' => 'division_bn',
];
$addrValue = static function (string $which, string $key) use ($data, $addrMap, $v, $isEn): string {
    $extra = $v($which . '_' . $key);
    if ($extra !== '') return $extra;
    $source = $data[$which] ?? [];
    $field = $addrMap[$key] ?? '';
    if ($field !== '' && trim((string) ($source[$field] ?? '')) !== '') {
        $value = trim((string) $source[$field]);
        return $isEn ? prottoyon_en_digits($value) : $value;
    }
    if ($key === 'village') return (string) ($isEn ? ($source['address_en'] ?? '') : ($source['address_bn'] ?? ''));
    if ($key === 'country') return $isEn ? 'Bangladesh' : 'বাংলাদেশ';
    return '';
};
$presentAddr = [];
$permanentAddr = [];
foreach ($addrFields as $key => $label) {
    $presentAddr[$key] = $addrValue('present', $key);
    $permanentAddr[$key] = $addrValue('permanent', $key);
}

$feeAmount = static function (string $key, int $index) use ($c, $v): string {
    $value = $v($key);
    if ($value !== '') return $value;
    $fee = $c['fees'][$index] ?? [];
    return is_array($fee) ? trim((string) ($fee['amount'] ?? '')) : '';
};
$feeRows = [
    ['fee_new', $isEn ? 'New' : 'নতুন', 0, 'fee_signboard', $isEn ? 'Signboard' : 'সাইনবোর্ড (পরিচিতিমূলক)', 4],
    ['fee_renewal', $isEn ? 'Renewal' : 'নবায়ন', 1, 'fee_profession', $isEn ? 'Profession tax' : 'পেশা ব্যবসা ও বৃদ্ধির উপর কর', 5],
    ['fee_arrears', $isEn ? 'Arrears' : 'বকেয়া', 2, 'fee_vat', $isEn ? 'VAT' : 'ভ্যাট', 6],
    ['fee_surcharge', $isEn ? 'Surcharge' : 'সারচার্জ', 3, 'fee_correction', $isEn ? 'Correction fee' : 'সংশোধনী ফি', 7],
];
$feeTotal = $v('fee_total');
$feeTotalView = $feeTotal !== '' ? $money($feeTotal) : $c['total_amount'];
$expiryLabel = $isEn ? 'Valid until' : 'মেয়াদ';
$scheduleLabel = $isEn ? 'Model Tax Schedule 2013, serial no' : 'আদর্শ কর তফিসল, ২০১৩ এর ক্রমিক নং';
$signAddress = $isEn ? $c['signer_address_en'] : $c['signer_address'];
?>
<!DOCTYPE html>
<html lang="<?= $e($c['lang']) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $e($c['title']) ?></title>
<meta name="robots" content="noindex">
<style>
@page { size: A4 portrait; margin: 0; }
@font-face { font-family: 'Kalpurush'; src: url('<?= $e($assetBase) ?>fonts/kalpurush.ttf') format('truetype'); font-weight: normal; font-style: normal; }
@font-face { font-family: 'NotoSerif'; src: url('<?= $e($assetBase) ?>fonts/NotoSerif-Regular.ttf') format('truetype'); font-weight: normal; font-style: normal; }
@font-face { font-family: 'NotoSerif'; src: url('<?= $e($assetBase) ?>fonts/NotoSerif-Bold.ttf') format('truetype'); font-weight: 700; font-style: normal; }
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; background: #fff; }
body { color: #000; }
body.bn { font-family: 'Kalpurush', 'SolaimanLipi', sans-serif; }
body.en { font-family: 'NotoSerif', 'Kalpurush', serif; }
.page { width: 595.28pt; height: 841.5pt; position: relative; overflow: hidden; background: #fff; page-break-after: always; }
.page:last-child { page-break-after: auto; }
.frame { position: absolute; top: 30.6pt; left: 30.6pt; width: 534.08pt; height: 780.69pt; border: 0.6pt solid #000; }
.frame-inner { position: absolute; top: 34pt; left: 34pt; width: 527.28pt; height: 773.89pt; border: 0.6pt solid #000; }
.watermark { position: absolute; top: 279.2pt; left: 155.9pt; width: 283.5pt; height: 283.5pt; opacity: 0.2; z-index: 0; }
.layer { position: relative; z-index: 1; }
.h-left, .h-mid, .h-right { position: absolute; top: 35.6pt; height: 139.6pt; border: 0.6pt solid #000; background: #fff; }
.h-left { left: 61.875pt; width: 115.875pt; }
.h-mid { left: 178.5pt; width: 238.28pt; }
.h-right { left: 417.53pt; width: 115.875pt; }
.qr { position: absolute; top: 31.5pt; left: 1.5pt; width: 63.75pt; height: 63.75pt; }
.issue { position: absolute; left: 1.5pt; bottom: 2.5pt; font-size: 8.2pt; line-height: 10pt; white-space: nowrap; }
.office { position: absolute; top: 21pt; left: 0; right: 0; text-align: center; font-size: 14.2pt; line-height: 16pt; }
.office-address { position: absolute; top: 37pt; left: 0; right: 0; text-align: center; font-size: 9.8pt; line-height: 12pt; }
.seal { position: absolute; top: 49.6pt; left: 0; right: 0; text-align: center; }
.seal img { width: 60pt; height: 60pt; }
.doc-title { position: absolute; top: 109pt; left: 0; right: 0; text-align: center; font-size: 11pt; line-height: 13pt; }
.license { position: absolute; top: 121.5pt; left: 0; right: 0; text-align: center; font-size: 8.2pt; line-height: 10pt; }
.photo-frame { position: absolute; top: 28.6pt; left: 21.8pt; width: 92.25pt; height: 97.5pt; border: 0.6pt solid #000; text-align: center; overflow: hidden; }
.photo-frame img { width: 90pt; height: 95.2pt; display: block; margin: 1.1pt auto 0; object-fit: cover; }
.photo-empty { height: 97.5pt; line-height: 97.5pt; color: #777; font-size: 10pt; }
.schedule { position: absolute; top: 176pt; left: 58.1pt; width: 479.1pt; height: 17.2pt; border: 0.6pt solid #000; background: #fff; }
.schedule span { position: absolute; left: 4.1pt; top: 3.4pt; font-size: 7.5pt; line-height: 9pt; }
.form { position: absolute; top: 193.2pt; left: 58.1pt; width: 479.1pt; height: 427.8pt; border-left: 0.6pt solid #000; border-right: 0.6pt solid #000; border-bottom: 0.6pt solid #000; background: transparent; overflow: hidden; }
.fields { width: 468pt; margin-left: 9.9pt; border-collapse: collapse; table-layout: fixed; }
.fields td { font-size: 9pt; line-height: 10.6pt; vertical-align: bottom; padding: 0 0 1.8pt 0; white-space: normal; }
body.en .fields td, body.en .split td, body.en .addr-left td, body.en .addr-right td { font-size: 8.2pt; line-height: 9.8pt; }
.fields td.num { width: 9.6pt; padding-left: 0; }
.fields td.lab { width: 130.9pt; border-bottom: 0.5pt solid #000; }
.fields td.gap { width: 0.8pt; padding: 0; }
.fields td.colon { width: 10.6pt; border-bottom: 0.5pt solid #000; text-align: center; }
.fields td.gap2 { width: 0.4pt; padding: 0; }
.fields td.val { width: 315.7pt; }
.fields td.lg { font-size: 12pt; line-height: 13pt; }
.fields td.val u, .mini td.val u { text-decoration: underline; text-decoration-thickness: 0.5pt; }
.fields tr.lg td { font-size: 12pt; line-height: 13pt; }
.fields tr.h13 td, .fields tr.h14 td { border-bottom: 0; }
.fields tr.h13 td.lab { border-bottom: 0.5pt solid #000; }
.fields .noborder { border-bottom: 0; }
.split { width: 468pt; margin-left: 9.9pt; border-collapse: collapse; table-layout: fixed; }
.split td { font-size: 9pt; line-height: 10.6pt; vertical-align: bottom; padding: 0 0 1.8pt 0; }
.split td.num { width: 9.6pt; }
.split td.lab-l { width: 130.9pt; border-bottom: 0.5pt solid #000; }
.split td.gap { width: 0.8pt; padding: 0; }
.split td.colon { width: 10.6pt; border-bottom: 0.5pt solid #000; text-align: center; }
.split td.gap2 { width: 0.4pt; padding: 0; }
.split td.val-l { width: 96.3pt; }
.split td.lab-r { width: 110pt; border-bottom: 0.5pt solid #000; }
.split td.val-r { width: 97.6pt; }
.split td.lab-r-wide { width: 131.7pt; border-bottom: 0.5pt solid #000; }
.split td.val-r-wide { width: 75.9pt; }
.split td u { text-decoration: underline; text-decoration-thickness: 0.5pt; }
.split tr.row11 td { font-size: 9pt; }
.addr-head { width: 458.4pt; margin-left: 19.5pt; border-top: 0.6pt solid #000; border-bottom: 0.6pt solid #000; height: 13.3pt; position: relative; font-size: 9pt; line-height: 13.3pt; }
.addr-head .l { position: absolute; left: 0.9pt; top: 0; }
.addr-head .r { position: absolute; left: 234.5pt; top: 0; }
.addr-wrap { width: 458.4pt; margin-left: 19.5pt; border-collapse: collapse; table-layout: fixed; }
.addr-wrap > tbody > tr > td { vertical-align: top; padding: 0; }
.addr-left { width: 230.4pt; border-collapse: collapse; table-layout: fixed; }
.addr-right { width: 223.9pt; border-collapse: collapse; table-layout: fixed; }
.addr-gap { width: 4.1pt; }
.addr-left td, .addr-right td { font-size: 9pt; line-height: 9.5pt; vertical-align: bottom; padding: 0 0 0.8pt 0; height: 10.8pt; }
.addr-left td.lab, .addr-right td.lab { border-bottom: 0.5pt solid #000; }
.addr-left td.gap, .addr-right td.gap { width: 0.6pt; padding: 0; }
.addr-left td.colon, .addr-right td.colon { border-bottom: 0.5pt solid #000; text-align: center; }
.addr-left td.gap2, .addr-right td.gap2 { width: 0.4pt; padding: 0; }
.addr-left td.lab { width: 80.6pt; }
.addr-left td.colon { width: 10.6pt; }
.addr-left td.val { width: 138.2pt; }
.addr-right td.lab { width: 77.3pt; }
.addr-right td.colon { width: 10.6pt; }
.addr-right td.val { width: 135pt; }
.addr-left td u, .addr-right td u { text-decoration: underline; text-decoration-thickness: 0.5pt; }
.fee { width: 366.465pt; margin-left: 19.15pt; border-left: 0.6pt solid #000; border-right: 0.6pt solid #000; border-collapse: collapse; table-layout: fixed; box-sizing: border-box; }
.fee td { font-size: 9pt; line-height: 10.8pt; vertical-align: middle; padding: 0; }
.fee td.lab { border-bottom: 0.5pt solid #000; }
.fee td.gap { padding: 0; }
.fee td.colon { border-bottom: 0.5pt solid #000; }
.fee tr.r1 td, .fee tr.r2 td { height: 21.6pt; }
.fee tr.r3 td, .fee tr.r4 td, .fee tr.r5 td { height: 10.8pt; }
.fee tr.r6 td { height: 11.55pt; }
.fee td u { text-decoration: underline; text-decoration-thickness: 0.5pt; }
.expiry { position: absolute; left: 19.5pt; right: 15pt; bottom: 4.5pt; font-size: 12pt; line-height: 14pt; text-align: justify; white-space: nowrap; }
.sig-left { position: absolute; top: 738.2pt; left: 60pt; width: 82pt; border-top: 1pt solid #000; padding-top: 2pt; font-size: 9.2pt; line-height: 11pt; text-align: center; }
.sig-right { position: absolute; top: 738.2pt; right: 56.3pt; width: 84pt; border-top: 1pt solid #000; padding-top: 2pt; font-size: 9.2pt; line-height: 11pt; text-align: right; }
.sig-right .name { font-size: 10.4pt; }
body.en .sig-right .name { font-weight: 700; }
.verify { position: absolute; left: 58.1pt; width: 479.1pt; top: 792pt; text-align: center; font-size: 8.6pt; line-height: 10pt; }
.pack-actions { max-width: 595.28pt; margin: 14px auto; padding: 0 12px; display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; font-family: system-ui, sans-serif; }
.pack-actions button, .pack-actions a { font-size: 15px; font-weight: 600; cursor: pointer; padding: 11px 22px; min-height: 44px; border-radius: 8px; border: 1px solid #d1d5db; background: #fff; color: #111827; text-decoration: none; display: inline-flex; align-items: center; }
.pack-actions .primary { background: #0E6B4A; border-color: #0E6B4A; color: #fff; }
.pack-notice { max-width: 540px; margin: 14px auto; padding: 10px 14px; border: 1.5px solid #a9cdb8; border-radius: 8px; background: #e3f1e9; color: #0b4a33; font: 600 14px/1.5 system-ui, sans-serif; }
@media screen { body { background: #e9e9e9; padding: 1px 0; } .page { margin: 14px auto; box-shadow: 0 0 8px rgba(0,0,0,.25); } }
@media print { .pack-actions, .pack-notice { display: none !important; } body { background: #fff !important; } .page { margin: 0 !important; box-shadow: none !important; } }
</style>
</head>
<body class="<?= $e($c['lang']) ?>">
<?php if ($notice !== ''): ?><div class="pack-notice"><?= $notice ?></div><?php endif; ?>
<?= $actionsHtml ?>
<div class="page">
<div class="frame"></div>
<div class="frame-inner"></div>
<div class="watermark"><img src="<?= $e($logo) ?>" alt=""></div>
<div class="layer">

<div class="h-left">
<img class="qr" src="<?= $e($c['qr_url']) ?>" alt="">
<div class="issue">
<div><?= $e($isEn ? 'Licence issue details' : 'লাইসেন্স ইস্যুর বিবরণ') ?></div>
<div><?= $e($isEn ? 'Date of issue:' : 'ইস্যুর তারিখ:') ?> <?= $e($c['issue_date']) ?></div>
<div><?= $e($isEn ? 'Time of issue:' : 'ইস্যুর সময়:') ?> <?= $e($c['issue_time']) ?></div>
</div>
</div>

<div class="h-mid">
<div class="office"><?= $e($c['office_name']) ?></div>
<div class="office-address"><?= $e($isEn ? $c['office_address_en'] : $c['office_address']) ?></div>
<div class="seal"><img src="<?= $e($logo) ?>" alt=""></div>
<div class="doc-title"><?= $e($c['title']) ?></div>
<div class="license"><?= $e($isEn ? 'Trade Licence No:' : 'ট্রেড লাইসেন্স নং:') ?> <?= $e($c['certificate_no']) ?></div>
</div>

<div class="h-right">
<div class="photo-frame">
<?php if ($photo !== ''): ?>
<img src="<?= $e($photo) ?>" alt="">
<?php else: ?>
<div class="photo-empty"><?= $isEn ? 'Photo' : 'ছবি' ?></div>
<?php endif; ?>
</div>
</div>

<div class="schedule"><span><?= $e($scheduleText) ?></span></div>

<div class="form">
<table class="fields">
<tbody>
<tr><td class="num"><?= $isEn ? '1.' : '১।' ?></td><td class="lab"><?= $isEn ? 'Name of establishment' : 'ব্যবসা প্রতিষ্ঠানের নাম' ?></td><td class="gap"></td><td class="colon">:</td><td class="gap2"></td><td class="val lg"><u><?= $e($c['business_name']) ?></u></td></tr>
<tr><td class="num"><?= $isEn ? '2.' : '২।' ?></td><td class="lab"><?= $isEn ? 'Owner' : 'মালিক/স্বত্ত্বাধিকারী' ?></td><td class="gap"></td><td class="colon">:</td><td class="gap2"></td><td class="val"><u><?= $e($c['name']) ?></u></td></tr>
<tr><td class="num"><?= $isEn ? '3.' : '৩।' ?></td><td class="lab"><?= $isEn ? "Mother's name" : 'মাতার নাম' ?></td><td class="gap"></td><td class="colon">:</td><td class="gap2"></td><td class="val"><u><?= $e($c['mother']) ?></u></td></tr>
<tr><td class="num"><?= $isEn ? '4.' : '৪।' ?></td><td class="lab"><?= $isEn ? "Father's name" : 'পিতার নাম' ?></td><td class="gap"></td><td class="colon">:</td><td class="gap2"></td><td class="val"><u><?= $e($c['father']) ?></u></td></tr>
<tr><td class="num"><?= $isEn ? '5.' : '৫।' ?></td><td class="lab"><?= $isEn ? 'Spouse name' : 'স্বামী/স্ত্রীর নাম' ?></td><td class="gap"></td><td class="colon">:</td><td class="gap2"></td><td class="val"><u><?= $e($c['spouse']) ?></u></td></tr>
<tr><td class="num"><?= $isEn ? '6.' : '৬।' ?></td><td class="lab"><?= $isEn ? 'Nature (sole/joint/other)' : 'প্রতিষ্ঠানের প্রকৃতি (একক/যৌথ/অন্যান্য)' ?></td><td class="gap"></td><td class="colon">:</td><td class="gap2"></td><td class="val"><u><?= $e($nature) ?></u></td></tr>
<tr><td class="num"><?= $isEn ? '7.' : '৭।' ?></td><td class="lab"><?= $isEn ? 'Type of business' : 'ব্যবসার ধরণ' ?></td><td class="gap"></td><td class="colon">:</td><td class="gap2"></td><td class="val"><u><?= $e($c['business_type']) ?></u></td></tr>
<tr><td class="num"><?= $isEn ? '8.' : '৮।' ?></td><td class="lab"><?= $isEn ? 'Business address' : 'ব্যবসার ঠিকানা' ?></td><td class="gap"></td><td class="colon">:</td><td class="gap2"></td><td class="val"><u><?= $e($c['business_address']) ?></u></td></tr>
</tbody>
</table>

<table class="split">
<tbody>
<tr><td class="num"><?= $isEn ? '9.' : '৯।' ?></td><td class="lab-l"><?= $isEn ? '(a) Area (if applicable)' : '(ক) অঞ্চল (প্রযোজ্য ক্ষেত্রে)' ?></td><td class="gap"></td><td class="colon">:</td><td class="gap2"></td><td class="val-l"><u><?= $e($area) ?></u></td><td class="lab-r"><?= $isEn ? '(b) Ward No' : '(খ) ওয়ার্ড নং' ?></td><td class="gap"></td><td class="colon">:</td><td class="gap2"></td><td class="val-r"><u><?= $e($digits($wardNo)) ?></u></td></tr>
</tbody>
</table>

<table class="split">
<tbody>
<tr><td class="num"><?= $isEn ? '10.' : '১০।' ?></td><td class="lab-l"><?= $isEn ? '(a) Birth reg./NID/Passport' : '(ক) জন্ম নিবন্ধন/এনআইডি/পাসপোর্ট' ?></td><td class="gap"></td><td class="colon">:</td><td class="gap2"></td><td class="val-l"><u><?= $e($c['nid']) ?></u></td><td class="lab-r"><?= $isEn ? '(b) TIN' : '(খ) টিআইএন' ?></td><td class="gap"></td><td class="colon">:</td><td class="gap2"></td><td class="val-r"><u><?= $e($digits($tin)) ?></u></td></tr>
<tr><td class="num"></td><td class="lab-l noborder"></td><td class="gap"></td><td class="colon noborder"></td><td class="gap2"></td><td class="val-l"></td><td class="lab-r"><?= $isEn ? '(c) BIN' : '(গ) বিআইএন' ?></td><td class="gap"></td><td class="colon">:</td><td class="gap2"></td><td class="val-r"><u><?= $e($digits($bin)) ?></u></td></tr>
<tr><td class="num"></td><td class="lab-l"><?= $isEn ? '(d) Mobile No' : '(ঘ) মোবাইল নং' ?></td><td class="gap"></td><td class="colon">:</td><td class="gap2"></td><td class="val-l"><u><?= $e($digits($phone)) ?></u></td><td class="lab-r"><?= $isEn ? '(e) Email' : '(ঙ) ইমেইল' ?></td><td class="gap"></td><td class="colon">:</td><td class="gap2"></td><td class="val-r"><u><?= $e($email) ?></u></td></tr>
</tbody>
</table>

<table class="split">
<tbody>
<tr class="row11"><td class="num"><?= $isEn ? '11.' : '১১।' ?></td><td class="lab-l"><?= $isEn ? '(a) Fiscal year' : '(ক) অর্থ বছর' ?></td><td class="gap"></td><td class="colon">:</td><td class="gap2"></td><td class="val-l"><u><?= $e($fiscalYear) ?></u></td><td class="lab-r-wide"><?= $isEn ? '(b) Business start date' : '(খ) ব্যবসা শুরুর তারিখ' ?></td><td class="gap"></td><td class="colon">:</td><td class="gap2"></td><td class="val-r-wide"><u><?= $e($startDate) ?></u></td></tr>
</tbody>
</table>

<div class="addr-head"><span class="l"><?= $isEn ? 'Present address of owner' : 'মালিক/স্বত্ত্বাধিকারী বর্তমান ঠিকানা' ?></span><span class="r"><?= $isEn ? 'Permanent address of owner' : 'মালিক/স্বত্ত্বাধিকারী স্থায়ী ঠিকানা' ?></span></div>
<table class="addr-wrap">
<tbody>
<tr>
<td class="addr-left">
<table>
<tbody>
<?php foreach ($addrFields as $key => $label): ?>
<tr><td class="lab"><?= $e($label) ?></td><td class="gap"></td><td class="colon">:</td><td class="gap2"></td><td class="val"><u><?= $e($key === 'holding' || $key === 'post_code' ? $digits($presentAddr[$key]) : $presentAddr[$key]) ?></u></td></tr>
<?php endforeach; ?>
</tbody>
</table>
</td>
<td class="addr-gap"></td>
<td class="addr-right">
<table>
<tbody>
<?php foreach ($addrFields as $key => $label): ?>
<tr><td class="lab"><?= $e($label) ?></td><td class="gap"></td><td class="colon">:</td><td class="gap2"></td><td class="val"><u><?= $e($key === 'holding' || $key === 'post_code' ? $digits($permanentAddr[$key]) : $permanentAddr[$key]) ?></u></td></tr>
<?php endforeach; ?>
</tbody>
</table>
</td>
</tr>
</tbody>
</table>

<table class="fields">
<tbody>
<tr class="h13"><td class="num"><?= $isEn ? '12.' : '১২।' ?></td><td class="lab"><?= $e($scheduleLabel) ?></td><td class="gap"></td><td class="colon">:</td><td class="gap2" colspan="2"><u><?= $e($scheduleSerial) ?></u></td></tr>
<tr class="h14"><td class="num"><?= $isEn ? '13.' : '১৩।' ?></td><td class="lab" colspan="5"><?= $isEn ? 'Trade licence fee (new/renewal)' : 'ট্রেড লাইসেন্স ফি (নতুন/নবায়ন)' ?></td></tr>
</tbody>
</table>

<table class="fee">
<colgroup>
<col style="width:80.625pt"><col style="width:0.75pt"><col style="width:16.021pt"><col style="width:0.75pt"><col style="width:132pt"><col style="width:0.75pt"><col style="width:85.5pt"><col style="width:0.75pt"><col style="width:16.021pt"><col style="width:0.75pt"><col style="width:32.548pt">
</colgroup>
<tbody>
<?php foreach ($feeRows as $index => $row): ?>
<tr class="r<?= $index + 1 ?>">
<td class="lab"><?= $e($row[1]) ?></td><td class="gap"></td><td class="colon">:</td><td class="gap"></td><td class="val"><u><?= $e($money($feeAmount($row[0], $row[2]))) ?></u></td>
<td class="gap"></td><td class="lab"><?= $e($row[4]) ?></td><td class="gap"></td><td class="colon">:</td><td class="gap"></td><td class="val"><u><?= $e($money($feeAmount($row[3], $row[5]))) ?></u></td>
</tr>
<?php endforeach; ?>
<tr class="r6">
<td colspan="5"></td>
<td></td><td class="lab"><?= $isEn ? 'Grand total' : 'সর্বমোট' ?></td><td class="gap"></td><td class="colon">:</td><td class="gap"></td><td class="val"><u><?= $e($feeTotalView) ?></u></td>
</tr>
</tbody>
</table>

<div class="expiry"><?= $e($expiryText) ?></div>
</div>

<div class="sig-left"><?= $e($isEn ? 'Licensing authority' : 'লাইসেন্সিং কর্তৃপক্ষ') ?></div>
<div class="sig-right">
<div class="name"><?= $e($isEn ? $c['signer_name_en'] : $c['signer_name']) ?></div>
<div><?= $e($isEn ? $c['signer_role_en'] : $c['signer_role']) ?></div>
<div><?= $e($isEn ? $c['signer_office_en'] : $c['signer_office']) ?></div>
<div><?= $e($signAddress) ?></div>
</div>
<div class="verify"><?= $e($isEn ? 'Verify this certificate at:' : 'এই সনদটি যাচাই করতে ভিজিট করুন:') ?> <?= $e($c['verify_url']) ?></div>

</div>
</div>
</body>
</html>
