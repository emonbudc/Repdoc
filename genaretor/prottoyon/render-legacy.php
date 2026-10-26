<?php
declare(strict_types=1);

if (!isset($docTitle, $refNo, $certificate, $certType, $union, $chairman, $fragFile, $revoked, $actionsHtml)) {
    http_response_code(500);
    exit('Legacy renderer variables missing.');
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($docTitle) ?></title>
<meta name="robots" content="noindex">
<base href="<?= e($baseHref ?? repdoc_url('genaretor/prottoyon/')) ?>">
<link href="https://fonts.maateen.me/solaiman-lipi/font.css" rel="stylesheet">
<link href="<?= e(repdoc_url('genaretor/prottoyon/assets/css/certificate.css?v=1')) ?>" rel="stylesheet">
<style>
    .cv-actions { max-width: 297mm; margin: 14px auto; padding: 0 12px; display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; font-family: system-ui, sans-serif; }
    .cv-actions button, .cv-actions a { font-size: 15px; font-weight: 600; cursor: pointer; padding: 11px 22px; min-height: 44px; border-radius: 8px; border: 1px solid #d1d5db; background: #fff; color: #111827; text-decoration: none; }
    .cv-actions .primary { background: #0E6B4A; border-color: #0E6B4A; color: #fff; }
    .revoked-stamp { position: absolute; top: 42%; left: 50%; z-index: 5; transform: translate(-50%, -50%) rotate(-18deg); border: 5px solid #DC2626; color: #DC2626; font-size: 54px; font-weight: 800; letter-spacing: 6px; padding: 6px 28px; border-radius: 10px; opacity: .85; pointer-events: none; background: rgba(255,255,255,.55); }
    @media print { .cv-actions { display: none !important; } }
</style>
</head>
<body>
<?php if (!empty($legacyNotice)): ?><div style="max-width:297mm;margin:14px auto;padding:0 12px;font:14px/1.5 system-ui,sans-serif"><?= $legacyNotice ?></div><?php endif; ?>
<?php if (($actionsHtml ?? '') !== ''): ?>
<div class="cv-actions"><?= $actionsHtml ?></div>
<?php endif; ?>
<div class="cert">
    <?php if (!empty($revoked)): ?><div class="revoked-stamp">বাতিল</div><?php endif; ?>
    <div class="watermark"><img src="<?= e(repdoc_url('genaretor/prottoyon/assets/images/gov_logo.png')) ?>" alt="জলছাপ"></div>
    <div class="header">
        <div class="header-logo"><img src="<?= e($union->logo_url) ?>" alt="গণপ্রজাতন্ত্রী বাংলাদেশ সরকার"></div>
        <div class="header-right">
            <div class="govt-tagline">গণপ্রজাতন্ত্রী বাংলাদেশ সরকার</div>
            <div class="org-name"><?= e($union->name_bn) ?></div>
            <div class="org-addr"><?= e($union->upazila_name) ?>, <?= e($union->district_name) ?>, বাংলাদেশ</div>
            <div class="header-divider"></div>
        </div>
    </div>
    <div class="cert-title-row"><span class="cert-type"><?= e($certType->name_bn) ?></span></div>
    <div class="meta">
        <div class="meta-item"><span class="meta-label">সনদ নম্বরঃ</span> <span class="meta-value"><?= e(en2bnNumber($refNo)) ?></span></div>
        <div class="meta-item"><span class="meta-label">ইস্যুর তারিখঃ</span> <span class="meta-value"><?= e(prottoyon_formatDate($certificate->issue_date)) ?></span></div>
    </div>
    <div class="main"><div class="left-col"><?php include $fragFile; ?></div></div>
    <div class="footer-box">
        <div class="footer">
            <div class="qr-section">
                <div id="qrcode"></div>
                <div class="qr-label"><?= e($refNo) ?></div>
            </div>
            <div class="sig">
                <div class="sig-line"><span class="sig-dots">· · · · ·</span></div>
                <div class="sig-name"><?= e($chairman) ?></div>
                <div class="sig-role"><?= $union->office_type === 'union' ? 'চেয়ারম্যান' : 'প্রশাসক' ?></div>
                <div class="sig-role"><?= e($union->name_bn) ?></div>
            </div>
        </div>
        <div class="bottom-bar">সনদ নম্বর: <?= e($refNo) ?></div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
(function () {
    try {
        new QRCode(document.getElementById("qrcode"), {
            text: <?= json_encode($refNo, JSON_UNESCAPED_UNICODE) ?>,
            width: 90, height: 90, colorDark: "#000000", colorLight: "#ffffff"
        });
    } catch (e) {}
})();
</script>
</body>
</html>
