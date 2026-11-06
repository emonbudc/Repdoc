<?php
declare(strict_types=1);

$logo = prottoyon_office_logo_uri($office ?? []);
$bodyHtml = nl2br(e(prottoyon_certificate_paragraph($data, $c['type'], $isEn)));
$metaNoLabel = $isEn ? 'Certificate No:' : 'সনদ নং:-';
$metaDateLabel = $isEn ? 'Date of Issue:' : 'ইস্যুর তারিখ:-';
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
@page { size: A4 landscape; margin: 0; }
@font-face { font-family: 'Kalpurush'; src: url('<?= $e($assetBase) ?>fonts/kalpurush.ttf') format('truetype'); font-weight: normal; font-style: normal; }
@font-face { font-family: 'NotoSerif'; src: url('<?= $e($assetBase) ?>fonts/NotoSerif-Regular.ttf') format('truetype'); font-weight: normal; font-style: normal; }
@font-face { font-family: 'NotoSerif'; src: url('<?= $e($assetBase) ?>fonts/NotoSerif-Bold.ttf') format('truetype'); font-weight: 700; font-style: normal; }
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; background: #fff; }
body { color: #000; }
body.bn { font-family: 'Kalpurush', 'SolaimanLipi', sans-serif; }
body.en { font-family: 'NotoSerif', 'Kalpurush', serif; }
.page { width: 841.89pt; height: 595.28pt; position: relative; overflow: hidden; background: #fff; }
.frame-outer { position: absolute; top: 30.6pt; left: 30.6pt; width: 780.69pt; height: 534.08pt; border: 0.6pt solid #000; }
.frame-inner { position: absolute; top: 34pt; left: 34pt; width: 773.89pt; height: 527.28pt; border: 0.6pt solid #000; }
.watermark { position: absolute; top: 212.7pt; left: 336pt; width: 170pt; height: 170pt; opacity: 0.1; z-index: 0; }
.layer { position: relative; z-index: 1; }
.seal { position: absolute; top: 40.7pt; left: 383.3pt; width: 75.3pt; height: 75.3pt; }
.seal img, .watermark img { width: 100%; height: 100%; object-fit: contain; }
.office-name { position: absolute; top: 111pt; left: 0; right: 0; text-align: center; font-size: 14pt; line-height: 20pt; }
.office-address { position: absolute; top: 128.3pt; left: 0; right: 0; text-align: center; font-size: 10pt; line-height: 13pt; }
.doc-title { position: absolute; top: 139.7pt; left: 0; right: 0; text-align: center; font-size: 14pt; line-height: 20pt; }
body.en .office-name, body.en .doc-title, body.en .meta { font-weight: 700; }
.meta { position: absolute; top: 162.3pt; left: 60.4pt; right: 60.4pt; font-size: 11.33pt; line-height: 16pt; }
.meta .left { position: absolute; left: 0; top: 0; }
.meta .right { position: absolute; right: 0; top: 0; }
.body { position: absolute; top: 195.6pt; left: 58pt; width: 726.7pt; font-size: 12.7pt; line-height: 20.7pt; text-align: justify; }
.body p { margin: 0; }
.qr { position: absolute; top: 468pt; left: 55.3pt; width: 67.3pt; height: 67.3pt; }
.sign { position: absolute; top: 456pt; right: 57pt; width: 90pt; border-top: 0.5pt solid #000; padding-top: 8pt; text-align: center; font-size: 12.7pt; line-height: 16pt; }
.sign .name { font-weight: normal; }
body.en .sign .name { font-weight: 700; }
.verify { position: absolute; top: 542.5pt; left: 0; right: 0; text-align: center; color: #575656; font-size: 9.33pt; line-height: 12.6pt; }
.pack-actions { max-width: 841.89pt; margin: 14px auto; padding: 0 12px; display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; font-family: system-ui, sans-serif; }
.pack-actions button, .pack-actions a { font-size: 15px; font-weight: 600; cursor: pointer; padding: 11px 22px; min-height: 44px; border-radius: 8px; border: 1px solid #d1d5db; background: #fff; color: #111827; text-decoration: none; display: inline-flex; align-items: center; }
.pack-actions .primary { background: #0E6B4A; border-color: #0E6B4A; color: #fff; }
.pack-notice { max-width: 760px; margin: 14px auto; padding: 10px 14px; border: 1.5px solid #a9cdb8; border-radius: 8px; background: #e3f1e9; color: #0b4a33; font: 600 14px/1.5 system-ui, sans-serif; }
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
        <div class="office-name"><?= $e($c['office_name']) ?></div>
        <div class="office-address"><?= $e($isEn ? $c['office_address_en'] : $c['office_address']) ?></div>
        <div class="doc-title"><?= $e($c['title']) ?></div>
        <div class="meta">
            <span class="left"><?= $e($metaNoLabel) ?> <?= $e($c['certificate_no']) ?></span>
            <span class="right"><?= $e($metaDateLabel) ?> <?= $e($c['issue_date']) ?></span>
        </div>
        <div class="body"><?= $bodyHtml ?></div>
        <img class="qr" src="<?= $e($c['qr_url']) ?>" alt="">
        <div class="sign">
            <div class="name"><?= $e($isEn ? $c['signer_name_en'] : $c['signer_name']) ?></div>
            <div><?= $e($isEn ? $c['signer_role_en'] : $c['signer_role']) ?></div>
            <div><?= $e($isEn ? $c['signer_office_en'] : $c['signer_office']) ?></div>
            <div><?= $e($signAddress) ?></div>
        </div>
        <div class="verify"><?= $e($c['verify_label']) ?> <?= $e($c['verify_url']) ?></div>
    </div>
</div>
</body>
</html>
