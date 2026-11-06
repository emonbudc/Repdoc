<?php
declare(strict_types=1);

$logo = prottoyon_office_logo_uri($office ?? []);
$metaNoLabel = $isEn ? 'Certificate No:' : 'সনদ নং:-';
$metaDateLabel = $isEn ? 'Date of Issue:' : 'ইস্যুর তারিখ:-';
$intro = $isEn
    ? 'This is to certify that ' . $c['name'] . ', father/spouse: ' . $c['father'] . ', mother: ' . $c['mother'] . ', is a permanent resident of this area. Permanent address: ' . $c['permanent_address_en'] . ' Present address: ' . $c['present_address_en'] . ' The members of the family of the above-mentioned person are listed below:'
    : 'এই মর্মে প্রত্যয়ন করা যাইতেছে যে, ' . $c['name'] . ', পিতা/স্বামীঃ ' . $c['father'] . ', মাতাঃ ' . $c['mother'] . ' অত্র এলাকার একজন স্থায়ী বাসিন্দা। তাহার স্থায়ী ঠিকানাঃ- ' . $c['permanent_address'] . ' বর্তমান ঠিকানাঃ- ' . $c['present_address'] . ' উল্লিখিত ব্যক্তির পরিবারের সদস্যগণের তালিকা নিম্নরূপঃ';
$closing = $isEn
    ? 'No member other than the persons listed above belongs to this family. If the applicant intentionally omits any member, the approving authority shall not be responsible in any way and this certificate shall be treated as revoked.'
    : 'উল্লিখিত সদস্য/সদস্যগণ ছাড়া আর কোনো সদস্য নাই। আবেদনকারী যদি ইচ্ছাকৃতভাবে কোনো সদস্য বাদ দিয়ে থাকেন তাহলে অনুমোদনকারী কোনো ভাবেই দায়ী থাকিবেন না এবং উক্ত সনদ বাতিল বলিয়া গণ্য করা হইবে।';
$wish = $isEn ? 'I wish all members of the family every success and well-being.' : 'আমি সকল সদস্যগণের মঙ্গল কামনা করিতেছি।';
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
.page { width: 595.28pt; height: 841.89pt; position: relative; overflow: hidden; background: #fff; page-break-after: always; }
.page:last-child { page-break-after: auto; }
.frame-outer { position: absolute; top: 30.6pt; left: 30.6pt; width: 534.08pt; height: 780.69pt; border: 0.6pt solid #000; }
.frame-inner { position: absolute; top: 34pt; left: 34pt; width: 527.28pt; height: 773.89pt; border: 0.6pt solid #000; }
.watermark { position: absolute; top: 279.2pt; left: 155.9pt; width: 283.5pt; height: 283.5pt; opacity: 0.2; z-index: 0; }
.layer { position: relative; z-index: 1; }
.seal { position: absolute; top: 40.5pt; left: 0; right: 0; text-align: center; }
.seal img { width: 60pt; height: 60pt; object-fit: contain; }
.watermark img { width: 100%; height: 100%; object-fit: contain; }
.office { position: absolute; top: 100.5pt; left: 0; right: 0; text-align: center; font-size: 14.2pt; line-height: 16pt; }
.office-address { position: absolute; top: 116.8pt; left: 0; right: 0; text-align: center; font-size: 9.8pt; line-height: 12pt; }
.doc-title { position: absolute; top: 129.3pt; left: 0; right: 0; text-align: center; font-size: 14.2pt; line-height: 16pt; }
.meta { position: absolute; top: 151.7pt; left: 58.1pt; width: 479.1pt; border-collapse: collapse; table-layout: fixed; font-size: 11.4pt; line-height: 13.5pt; }
.meta td { padding: 0; vertical-align: top; }
.meta .left { text-align: left; padding-left: 2.3pt; }
.meta .right { text-align: right; }
.body { position: absolute; top: 183.3pt; left: 57.8pt; width: 479.4pt; }
.intro { margin: 0; font-size: 11.6pt; line-height: 18.5pt; text-align: justify; }
.members { width: 479.1pt; margin-top: 11.3pt; border-collapse: collapse; table-layout: fixed; font-size: 9.4pt; }
.members th, .members td { border: 0.6pt solid #000; padding: 0; height: 13.9pt; vertical-align: middle; text-align: center; }
.members th { height: 13.8pt; font-weight: normal; }
.members td.name { text-align: left; padding-left: 6.4pt; }
.closing { margin: 10.3pt 0 0 0; font-size: 11.6pt; line-height: 18.5pt; text-align: justify; }
.wish { margin: 13.1pt 0 0 0; font-size: 11.6pt; line-height: 18.5pt; text-align: justify; }
.sig-right { position: absolute; top: 713.3pt; right: 57.1pt; width: 76.9pt; border-top: 1pt solid #000; padding-top: 1.4pt; text-align: right; font-size: 11.4pt; line-height: 13.7pt; }
.qr { position: absolute; top: 714.82pt; left: 55.1pt; width: 67.5pt; height: 67.5pt; }
.verify { position: absolute; left: 58.1pt; width: 479.1pt; top: 792.1pt; text-align: center; font-size: 9pt; line-height: 10pt; color: #575656; }
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
<div class="frame-outer"></div>
<div class="frame-inner"></div>
<div class="watermark"><img src="<?= $e($logo) ?>" alt=""></div>
<div class="layer">

<div class="seal"><img src="<?= $e($logo) ?>" alt=""></div>
<div class="office"><?= $e($c['office_name']) ?></div>
<div class="office-address"><?= $e($isEn ? $c['office_address_en'] : $c['office_address']) ?></div>
<div class="doc-title"><?= $e($c['title']) ?></div>

<table class="meta">
<tbody>
<tr>
<td class="left"><?= $e($metaNoLabel) ?> <?= $e($c['certificate_no']) ?></td>
<td class="right"><?= $e($metaDateLabel) ?> <?= $e($c['issue_date']) ?></td>
</tr>
</tbody>
</table>

<div class="body">
<p class="intro"><?= $e($intro) ?></p>

<table class="members">
<colgroup>
<col style="width:38pt"><col style="width:180pt"><col style="width:58pt"><col style="width:33.7pt"><col style="width:90pt"><col style="width:79.4pt">
</colgroup>
<thead>
<tr>
<th><?= $isEn ? 'No.' : 'ক্রমিক নং' ?></th>
<th><?= $isEn ? 'Name' : 'নাম' ?></th>
<th><?= $isEn ? 'Date of birth' : 'জন্ম তারিখ' ?></th>
<th><?= $isEn ? 'Age' : 'বয়স' ?></th>
<th><?= $isEn ? 'Relation' : 'সম্পর্ক' ?></th>
<th><?= $isEn ? 'Occupation' : 'পেশা' ?></th>
</tr>
</thead>
<tbody>
<?php foreach ($c['members'] as $i => $member): ?>
<tr>
<td><?= $e($isEn ? (string) ($i + 1) : prottoyon_bn_digits((string) ($i + 1))) ?></td>
<td class="name"><?= $e($isEn ? $member['name_en'] : $member['name']) ?></td>
<td><?= $e($member['dob']) ?></td>
<td><?= $e($member['age']) ?></td>
<td><?= $e($member['relation']) ?></td>
<td><?= $e($member['occupation']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<p class="closing"><?= $e($closing) ?></p>
<p class="wish"><?= $e($wish) ?></p>
</div>

<div class="sig-right">
<div><?= $e($isEn ? $c['signer_name_en'] : $c['signer_name']) ?></div>
<div><?= $e($isEn ? $c['signer_role_en'] : $c['signer_role']) ?></div>
<div><?= $e($isEn ? $c['signer_office_en'] : $c['signer_office']) ?></div>
<div><?= $e($signAddress) ?></div>
</div>
<img class="qr" src="<?= $e($c['qr_url']) ?>" alt="">
<div class="verify"><?= $e($c['verify_label']) ?> <?= $e($c['verify_url']) ?></div>

</div>
</div>
</body>
</html>
