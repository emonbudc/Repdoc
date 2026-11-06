<?php
declare(strict_types=1);

$logo = prottoyon_office_logo_uri($office ?? []);
$x = $data['extra'] ?? [];
$v = static fn(string $key): string => trim((string) ($x[$key] ?? ''));
$money = static function (string $value) use ($isEn): string {
    $raw = trim($value);
    if ($raw === '') return '';
    $amount = prottoyon_pack_amount_number($raw);
    $text = number_format($amount, 2, '.', '');
    return $isEn ? prottoyon_en_digits($text) : prottoyon_bn_digits($text);
};
$dateValue = static function (string $value) use ($isEn): string {
    if (trim($value) === '') return '';
    return $isEn ? prottoyon_certificate_date_en($value) : prottoyon_format_date($value);
};

$wardNo = $v('ward_no');
$holdingNo = $v('holding_no');
$taxYear = $v('tax_year');
$arrears = $v('arrears');
$currentTax = $v('current_tax');
$rebate = $v('rebate');
$paidAmount = $v('paid_amount');
$outstanding = $v('outstanding');
$paymentDate = $dateValue($v('payment_date'));
$holdingArea = $v('holding_area');

$wardLabel = $isEn ? 'Ward No' : 'ওয়ার্ড নং';
$holdingLabel = $isEn ? 'Holding No' : 'হোল্ডিং নং';
$taxpayerLabel = $isEn ? 'Taxpayer' : 'করদাতার নাম';
$fatherLabel = $isEn ? 'Father/Spouse' : 'পিতা/স্বামী';
$motherLabel = $isEn ? 'Mother' : 'মাতা';
$addressLabel = $isEn ? 'Address' : 'ঠিকানা';
$arrearsLabel = $isEn ? 'Arrears up to last year' : 'গত বছর পর্যন্ত বকেয়া';
$currentLabel = $isEn ? 'Current year tax' : 'চলতি বছরের ধার্য্য';
$rebateLabel = $isEn ? 'Rebate' : 'ছাড়';
$totalLabel = $isEn ? 'Total paid' : 'মোট পরিশোধিত';
$outstandingLabel = $isEn ? 'Outstanding' : 'বর্তমান বকেয়া';
$areaLabel = $isEn ? 'Holding area' : 'হোল্ডিং এর আয়তন';
$taxYearLabel = $isEn ? 'Tax year' : 'করের বছর';
$paidDateLabel = $isEn ? 'Date of deposit' : 'জমার তারিখ';

$intro = $isEn
    ? 'This is to certify that ' . $c['name'] . ', father/spouse: ' . $c['father'] . ', mother: ' . $c['mother'] . ', is the owner/occupant of holding no. ' . ($holdingNo !== '' ? $holdingNo : '—') . ', ward no. ' . ($wardNo !== '' ? $wardNo : '—') . ', under this Union Parishad. The particulars of the holding tax are given below:'
    : 'এই মর্মে প্রত্যয়ন করা যাইতেছে যে, ' . $c['name'] . ', পিতা/স্বামীঃ ' . $c['father'] . ', মাতাঃ ' . $c['mother'] . ', অত্র ইউনিয়ন পরিষদের অন্তর্ভুক্ত ' . ($wardNo !== '' ? $wardNo : '—') . ' নং ওয়ার্ডের ' . ($holdingNo !== '' ? $holdingNo : '—') . ' নং হোল্ডিং এর মালিক/বাসিন্দা। উক্ত হোল্ডিং এর করের বিবরণ নিম্নরূপঃ';

$description = $isEn
    ? 'According to the office records, the holding tax for the year ' . ($taxYear !== '' ? $taxYear : '—') . ' has been assessed as shown above. The amount of BDT ' . ($paidAmount !== '' ? $money($paidAmount) : '—') . ' has been deposited into the Union Parishad treasury. Any amount remaining outstanding must be paid in due course.'
    : 'কার্যালয়ের হিসাব অনুযায়ী ' . ($taxYear !== '' ? $taxYear : '—') . ' কর বছরের উল্লিখিত হোল্ডিং ট্যাক্স ধার্য্য করা হইয়াছে। উক্ত হোল্ডিং এর বিপরীতে ' . ($paidAmount !== '' ? $money($paidAmount) : '—') . ' টাকা ইউনিয়ন পরিষদের কোষাগারে জমা হইয়াছে। বকেয়া থাকিলে নিয়ম অনুযায়ী পরিশোধ করিতে হইবে।';

$note = $isEn
    ? 'Note: This certificate has been issued on the basis of the holding tax records of the Union Parishad. If any information is found to be incorrect, this certificate shall be treated as revoked.'
    : 'বিঃদ্রঃ এই সনদটি ইউনিয়ন পরিষদের হোল্ডিং ট্যাক্স হিসাবের ভিত্তিতে ইস্যু করা হইয়াছে। কোনো তথ্য ভুল প্রমাণিত হইলে সনদটি বাতিল বলিয়া গণ্য করা হইবে।';

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
.page { width: 595.3pt; height: 841.9pt; position: relative; overflow: hidden; background: #fff; page-break-after: always; }
.page:last-child { page-break-after: auto; }
.watermark { position: absolute; top: 277.3pt; left: 150.6pt; width: 283.5pt; height: 283.5pt; opacity: 0.15; z-index: 0; }
.layer { position: relative; z-index: 1; }
.watermark img { width: 100%; height: 100%; object-fit: contain; }
.seal { position: absolute; top: 45.4pt; left: 0; right: 0; text-align: center; }
.seal img { width: 90pt; height: 90pt; object-fit: contain; }
.office { position: absolute; top: 132.3pt; left: 0; right: 0; text-align: center; font-size: 14.2pt; line-height: 16pt; }
.office-address { position: absolute; top: 151.4pt; left: 0; right: 0; text-align: center; font-size: 10pt; line-height: 12pt; }
.doc-title { position: absolute; top: 159pt; left: 0; right: 0; text-align: center; font-size: 14.2pt; line-height: 16pt; }
.meta { position: absolute; top: 181.9pt; left: 58pt; width: 479.1pt; border-collapse: collapse; table-layout: fixed; font-size: 11.4pt; line-height: 13.5pt; }
.meta td { padding-bottom: 5.2pt; vertical-align: top; }
.meta .label { width: 183.3pt; padding-left: 8pt; }
.meta .dot { width: 8.2pt; }
.meta .colon { width: 6.6pt; }
.meta .value { padding-left: 4.1pt; }
.intro { position: absolute; top: 229.2pt; left: 59.4pt; width: 471.5pt; margin: 0; font-size: 11.5pt; line-height: 18.6pt; text-align: justify; }
.tax-table { position: absolute; top: 283.6pt; left: 39.4pt; width: 514.8pt; border-collapse: collapse; table-layout: fixed; font-size: 10pt; }
.tax-table td { border: 0.7pt solid #000; height: 22.5pt; padding: 0 4.7pt; vertical-align: middle; }
.tax-table .k { width: 132.9pt; }
.tax-table .v { width: 124.4pt; }
.tax-table .k2 { width: 132.9pt; }
.description { position: absolute; top: 564.6pt; left: 58.9pt; width: 479.4pt; margin: 0; font-size: 11.6pt; line-height: 18.6pt; text-align: justify; }
.note { position: absolute; top: 627.8pt; left: 58.9pt; width: 479.4pt; margin: 0; font-size: 10.4pt; line-height: 16.2pt; text-align: justify; }
.qr { position: absolute; top: 702.4pt; left: 44.8pt; width: 67.5pt; height: 67.5pt; }
.verify-label { position: absolute; top: 762.9pt; left: 105.3pt; width: 358.5pt; text-align: center; font-size: 9pt; line-height: 10pt; color: #575656; }
.verify-url { position: absolute; top: 772.4pt; left: 99.7pt; width: 369.6pt; text-align: center; font-size: 9pt; line-height: 10pt; color: #066295; text-decoration: underline; }
.sig { position: absolute; top: 737.8pt; right: 71pt; width: 96pt; text-align: center; font-size: 10.5pt; line-height: 15.1pt; }
.sig .name { font-weight: normal; }
body.en .sig .name { font-weight: 700; }
.sig .line { margin-top: 2pt; border-top: 0.5pt solid #000; padding-top: 2.3pt; }
.pack-actions { max-width: 595.3pt; margin: 14px auto; padding: 0 12px; display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; font-family: system-ui, sans-serif; }
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
<div class="watermark"><img src="<?= $e($logo) ?>" alt=""></div>
<div class="layer">

<div class="seal"><img src="<?= $e($logo) ?>" alt=""></div>
<div class="office"><?= $e($c['office_name']) ?></div>
<div class="office-address"><?= $e($isEn ? $c['office_address_en'] : $c['office_address']) ?></div>
<div class="doc-title"><?= $e($c['title']) ?></div>

<table class="meta">
<tbody>
<tr><td class="label"><?= $e($isEn ? 'Certificate No' : 'সনদ নং') ?></td><td class="dot">:</td><td class="value"><?= $e($c['certificate_no']) ?></td></tr>
<tr><td class="label"><?= $e($isEn ? 'Date of issue' : 'ইস্যুর তারিখ') ?></td><td class="dot">:</td><td class="value"><?= $e($c['issue_date']) ?></td></tr>
<tr><td class="label"><?= $e($wardLabel) ?></td><td class="dot">:</td><td class="value"><?= $e($isEn ? prottoyon_en_digits($wardNo) : prottoyon_bn_digits($wardNo)) ?></td></tr>
<tr><td class="label"><?= $e($holdingLabel) ?></td><td class="dot">:</td><td class="value"><?= $e($isEn ? prottoyon_en_digits($holdingNo) : prottoyon_bn_digits($holdingNo)) ?></td></tr>
<tr><td class="label"><?= $e($taxpayerLabel) ?></td><td class="dot">:</td><td class="value"><?= $e($c['name']) ?></td></tr>
<tr><td class="label"><?= $e($fatherLabel) ?></td><td class="dot">:</td><td class="value"><?= $e($c['father']) ?></td></tr>
<tr><td class="label"><?= $e($motherLabel) ?></td><td class="dot">:</td><td class="value"><?= $e($c['mother']) ?></td></tr>
<tr><td class="label"><?= $e($addressLabel) ?></td><td class="dot">:</td><td class="value"><?= $e($c['present_address']) ?></td></tr>
</tbody>
</table>

<p class="intro"><?= $e($intro) ?></p>

<table class="tax-table">
<tbody>
<tr>
<td class="k"><?= $e($taxYearLabel) ?></td><td class="v"><?= $e($taxYear) ?></td>
<td class="k2"><?= $e($areaLabel) ?></td><td><?= $e($holdingArea !== '' ? $holdingArea . ' ' . ($isEn ? 'sq ft' : 'বর্গফুট') : '') ?></td>
</tr>
<tr>
<td class="k"><?= $e($arrearsLabel) ?></td><td class="v"><?= $e($money($arrears)) ?></td>
<td class="k2"><?= $e($currentLabel) ?></td><td><?= $e($money($currentTax)) ?></td>
</tr>
<tr>
<td class="k"><?= $e($rebateLabel) ?></td><td class="v"><?= $e($money($rebate)) ?></td>
<td class="k2"><?= $e($totalLabel) ?></td><td><?= $e($money($paidAmount)) ?></td>
</tr>
<tr>
<td class="k"><?= $e($outstandingLabel) ?></td><td class="v"><?= $e($money($outstanding)) ?></td>
<td class="k2"><?= $e($paidDateLabel) ?></td><td><?= $e($paymentDate) ?></td>
</tr>
</tbody>
</table>

<p class="description"><?= $e($description) ?></p>
<p class="note"><?= $e($note) ?></p>

<img class="qr" src="<?= $e($c['qr_url']) ?>" alt="">
<div class="verify-label"><?= $e($isEn ? 'Verify this certificate online:' : 'সনদের সত্যতা যাচাই করুন:') ?></div>
<div class="verify-url"><?= $e($c['verify_url']) ?></div>

<div class="sig">
<div class="name"><?= $e($isEn ? $c['signer_name_en'] : $c['signer_name']) ?></div>
<div><?= $e($isEn ? $c['signer_role_en'] : $c['signer_role']) ?></div>
<div class="line"><?= $e($isEn ? $c['signer_office_en'] : $c['signer_office']) ?></div>
<div><?= $e($signAddress) ?></div>
</div>

</div>
</div>
</body>
</html>
