<?php
// Central auth + wallet (replaces the old per-tool auth/config.php).
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_login();
repdoc_security_headers();
// Get a random QR code image from the listed files
$qr_files = [
    'qr_codes/qr_0001.jpg',
    'qr_codes/qr_0002.JPG',
    'qr_codes/qr_0003.JPG',
    'qr_codes/qr_0004.JPG',
    'qr_codes/qr_0005.JPG',
    'qr_codes/qr_0006.JPG',
    'qr_codes/qr_0007.JPG',
    'qr_codes/qr_0008.JPG',
    'qr_codes/qr_0009.JPG',
    'qr_codes/qr_00010.JPG',
    'qr_codes/qr_00011.jpg',
    'qr_codes/qr_00012.JPG',
    'qr_codes/qr_00013.jpg',
    'qr_codes/qr_00014.jpg',
];
$random_qr = $qr_files[array_rand($qr_files)];

// Process form submission data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ── Billing: one credit per generated certificate (price 0 = free) ──
    $charge = charge_tool('birth_generator');
    if (!$charge['ok']) {
        http_response_code(402);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><meta charset="utf-8"><title>ব্যালেন্স পর্যাপ্ত নয়</title>'
           . '<p style="font:16px/1.7 system-ui,sans-serif;max-width:520px;margin:80px auto;text-align:center">'
           . e($charge['message'])
           . '<br><br><a href="index.php">ফর্মে ফিরে যান</a></p>';
        exit;
    }

    $dob = $_POST['dob'];
    $dobt = $_POST['dobt'];
    $name_bn = $_POST['name_bn'];
    $father_name_bn = $_POST['father_name_bn'];
    $mother_name_bn = $_POST['mother_name_bn'];
    $mother_name_en = ucwords(strtolower($_POST['mother_name_en']), " \t\r\n\f\v'-");
    $father_name_en = ucwords(strtolower($_POST['father_name_en']), " \t\r\n\f\v'-");
    $nibkar = ucwords(strtolower($_POST['nibkar']), " \t\r\n\f\v'-");
    $thanaandzilla = $_POST['thanaandzilla'];
    $name_en = ucwords(strtolower($_POST['name_en']), " \t\r\n\f\v'-");
    $gender = $_POST['gender'];
    $issuedate = $_POST['issuedate'];
    $givendate = $_POST['givendate'];
    $bp_bn = $_POST['bp_bn'];
    $bp_en = $_POST['bp_en'];
    $addressx = $_POST['addressx'];
    $address = ucwords(strtolower($_POST['address']), " \t\r\n\f\v'-");
    $birth_registration_number = $_POST['birth_registration_number'];
    // Full submission goes to the database (admin → Document Logs).
    log_document('birth_generator', (string) $birth_registration_number, (string) $dob, '', '', $_POST);
    $document_title = "Birth Certificate - " . $name_en . " | " . $birth_registration_number;
    $is_duplicate = isset($_POST['is_duplicate']) ? true : false;
    $is_corrected = isset($_POST['is_corrected']) ? true : false;

    // Proper case formatting (Abcd Abcd format)
    $name_bn = mb_convert_case($name_bn, MB_CASE_TITLE, 'UTF-8');
    $father_name_bn = mb_convert_case($father_name_bn, MB_CASE_TITLE, 'UTF-8');
    $mother_name_bn = mb_convert_case($mother_name_bn, MB_CASE_TITLE, 'UTF-8');
    $bp_bn = mb_convert_case($bp_bn, MB_CASE_TITLE, 'UTF-8');
    $bp_en = ucwords(strtolower($bp_en), " \t\r\n\f\v'-");
    $addressx = mb_convert_case($addressx, MB_CASE_TITLE, 'UTF-8');
    $dobt = mb_convert_case($dobt, MB_CASE_TITLE, 'UTF-8');
    $gender = ucwords(strtolower($gender));
    $thanaandzilla = mb_convert_case($thanaandzilla, MB_CASE_TITLE, 'UTF-8');
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars((string) $document_title, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="robots" content="noindex">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <style type="text/css">
        /* Define the @font-face rule for Kalpurush */
        @font-face {
            font-family: 'Kalpurush';
            src: url('fonts/kalpurush.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        /* Define the @font-face rule for Nikosh */
        @font-face {
            font-family: 'Nikosh';
            src: url('fonts/nikosh.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        @font-face {
            font-family: 'DejaVuSerif';
            src: url('fonts/DejaVuSerif.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        @font-face {
            font-family: 'font-v2';
            src: url('fonts/font-v2.otf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        p {margin: 0; padding: 0;}
        .boxduplicate {
            border: 1.8px solid #000;
            padding: 5px;
            width: 130px;
            font-size: 17px;
            font-family: 'Kalpurush', sans-serif;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: none;
            
            /* Add these properties to center the text */
            text-align: center;          /* Horizontal center */
            display: flex;              /* Enable flexbox */
            justify-content: center;    /* Horizontal center with flexbox */
            align-items: center;        /* Vertical center */
            height: 25px;              /* Set a specific height for the box */
        }

        .boxcorrected {
            border: 1.8px solid #000;
            padding: 5px;
            width: 135px;
            font-size: 17px;
            font-family: 'Kalpurush', sans-serif;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: none;
            
            /* Add these properties to center the text */
            text-align: center;          /* Horizontal center */
            display: flex;              /* Enable flexbox */
            justify-content: center;    /* Horizontal center with flexbox */
            align-items: center;        /* Vertical center */
            height: 25px;              /* Set a specific height for the box */
        }
        .fontv2{
            font-family: 'font-v2';
            font-size: 17px;
            color: #000000;
            font-weight: normal;
            font-style: normal;
            text-decoration: none;
            text-transform: none;
        }
        .fontv2italic{
            font-family: 'font-v2';
            font-size: 18px;
            color: #000000;
            font-weight: normal;
            font-style: italic;
            text-decoration: none;
            text-transform: none;
        }
        .fontv2bolditalic{
            font-family: 'font-v2';
            font-size: 18px;
            color: #000000;
            font-weight: bold;
            font-style: italic;
            text-decoration: none;
            text-transform: none;
        }
        .fontv2bold{
            font-family: 'font-v2';
            font-size: 18px;
            color: #000000;
            font-weight: bold;
            font-style: normal;
            text-decoration: none;
            text-transform: none;
        }

        .bcxnxx {font-size:18px; font-family:DejaVuSerif; color:#000000;}
        .brnox {font-size:18px; font-family:Nikosh; color:#000000;}
        .brnumber {font-size:17px; font-family:DejaVuSerif; color:#000000;}
        .xnxxok {font-size:17px; font-family:Helvetica; color:#000000;}
        .ft00 {font-size:15px; font-family:Helvetica; color:#000000;}
        .ft09 {font-size:11.5px; font-family:Helvetica; color:#000000;}
        .jonmosthanbn {font-size:18px; font-family:Kalpurush; color:#000000;}
        .ft01 {font-size:24px; font-family:Helvetica; color:#000000;}
        .ft02 {font-size:18px; font-family:Helvetica; color:#000000;}
        .ft03 {font-size:24px; font-family:'Kalpurush', sans-serif; color:#000000;}
        .ft0x {font-size:19px; font-family:'Kalpurush', sans-serif; color:#000000;}
        .ft06 {font-size:18px; font-family:Kalpurush; color:#000000;}
        .fathername {font-size:18px; font-family:Kalpurush; color:#000000;}
        .peraddress {font-size:16.3px; line-height: 1.2; font-family:Kalpurush; color:#000000;}
        .no-print { display: block; }
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body bgcolor=white>
<div id="page1-div" style="position:relative;width:892px;height:1263px;">
    <img src="img/br.jpg" alt="Back Logo" style="width: 650px; height: auto; display: block; margin: 50px auto;position:absolute;top:305px;left:130px;">
    <img src="img/deshiconx.png" alt="Hedavanga Sorkar" style="width: 100px; height: auto; display: block; margin: 0px auto;position:absolute;top:55px;left:400px;">
    <img src="<?php echo e($random_qr); ?>" alt="QR1" style="width: 165px; height: auto; display: block; margin: 50px auto;position:absolute;top:35px;left:60px;">
    <img src="barcodegen.php?data=<?= urlencode($birth_registration_number) ?>&width=2&height=60&embed=1" alt="Barcode" style="width:260px;height:auto;position:absolute;top:105px;left:550px;">
    <p style="position:absolute;top:160px;left:227px;white-space:nowrap" class="ft01">Government of the People's Republic of Bangladesh</p>
    <p style="position:absolute;top:196px;left:300px;white-space:nowrap;" class="fontv2">Office of the Registrar, Birth and Death Registration</p>
    <p style="font-weight: bold;position:absolute;top:296px;left:285px;white-space:nowrap" class="ft03">জন্ম নিবন্ধন সনদ</p>
    <p style="font-weight: bold;position:absolute;top:305px;left:440px;white-space:nowrap" class="bcxnxx">/ Birth Registration Certificate</p>

    <!--<p style="position:absolute;top:281px;left:466px;white-space:nowrap" class="fontv2">(Rule 9, 10)</p>-->
    <p style="position:absolute;top:276px;left:466px;white-space:nowrap;font-size:15px" class="fontv2">(Rule 9, 10)</p>

    <p style="position:absolute;top:250px;left:436px;white-space:nowrap" class="fontv2"><?php echo e($thanaandzilla ?? ''); ?></p>
    <p style="position:absolute;top:222px;left:420px;white-space:nowrap" class="fontv2"><?php echo e($nibkar ?? ''); ?></p>
    <!--<p style="font-family:Arial; opacity:0.5;position:absolute;top:235px;left:127px;white-space:nowrap" class="ft0x">IDDE</p>-->
    
    
    
    <p style="position:absolute;top:363px;left:105px;white-space:nowrap;font-size:14px" class="fontv2">Date of Registration</p>
    <p style="position:absolute;top:387px;left:105px;white-space:nowrap;font-size:14px" class="fontv2"><?php echo e(date('d/m/Y', strtotime($givendate ?? ''))); ?></p>
    <p id="brn-label" style="font-weight: reguler;position:absolute;top:363px;left:353px;white-space:nowrap" class="brnox">Birth Registration Number</p>
    <p style="position:absolute;top:363px;left:698px;white-space:nowrap;font-size:14px" class="fontv2">Date of Issuance</p>
    <p style="position:absolute;top:387px;left:698px;white-space:nowrap;font-size:14px" class="fontv2"><?php echo e(date('d/m/Y', strtotime($issuedate ?? ''))); ?></p>
    <p style="font-weight: bold;position:absolute;top:387px;left:379px;white-space:nowrap" class="brnumber"><?php echo e($birth_registration_number ?? ''); ?></p>
    <p style="position:absolute;top:588px;left:105px;white-space:nowrap" class="ft06">মাতা</p>
    <p style="position:absolute;top:633px;left:105px;white-space:nowrap" class="ft06">মাতার জাতীয়তা</p>
    <p style="position:absolute;top:723px;left:105px;white-space:nowrap" class="ft06">পিতার জাতীয়তা</p>
    <p style="position:absolute;top:768px;left:105px;white-space:nowrap" class="ft06">জন্মস্থান</p>
    <p style="position:absolute;top:678px;left:105px;white-space:nowrap" class="ft06">পিতা</p>
    <p style="position:absolute;top:543px;left:260px;white-space:nowrap" class="ft06"><?php echo e($name_bn ?? ''); ?></p>
    <p style="position:absolute;top:588px;left:260px;white-space:nowrap" class="ft06"><?php echo e($mother_name_bn ?? ''); ?></p>
    <p style="position:absolute;top:633px;left:260px;white-space:nowrap" class="ft06">বাংলাদেশী</p>
    <p style="position:absolute;top:678px;left:260px;white-space:nowrap" class="fathername"><?php echo e($father_name_bn ?? ''); ?></p>
    <p style="position:absolute;top:723px;left:260px;white-space:nowrap" class="ft06">বাংলাদেশী</p>
    <p style="position:absolute;top:454px;left:602px;white-space:nowrap" class="fontv2">Sex : <?php echo e($gender ?? ''); ?></p>
    <p style="position:absolute;top:483px;left:258px;white-space:nowrap;font-size:17px" class="fontv2italic"><?php echo e($dobt ?? ''); ?></p>
    <p style="position:absolute;top:544px;left:600px;white-space:nowrap" class="fontv2"><?php echo e($name_en ?? ''); ?></p>
    <p style="position:absolute;top:589px;left:600px;white-space:nowrap" class="fontv2"><?php echo e($mother_name_en ?? ''); ?></p>
    <p style="position:absolute;top:679px;left:600px;white-space:nowrap" class="fontv2"><?php echo e($father_name_en ?? ''); ?></p>
    <p style="position:absolute;top:544px;left:465px;white-space:nowrap" class="fontv2">Name</p>
    <p style="position:absolute;top:588px;left:465px;white-space:nowrap" class="fontv2">Mother</p>
    <p style="position:absolute;top:679px;left:465px;white-space:nowrap" class="fontv2">Father</p>
    <p style="position:absolute;top:634px;left:600px;white-space:nowrap" class="fontv2">Bangladeshi</p>
    <p style="position:absolute;top:724px;left:600px;white-space:nowrap" class="fontv2">Bangladeshi</p>
    <p style="position:absolute;top:724px;left:465px;white-space:nowrap" class="fontv2">Nationality</p>
    <p style="position:absolute;top:769px;left:465px;white-space:nowrap" class="fontv2">Place of Birth</p>
    <p style="position:absolute;top:768px;left:260px;white-space:nowrap" class="jonmosthanbn"><?php echo e($bp_bn ?? ''); ?></p>
    <p style="position:absolute;top:769px;left:600px;white-space:nowrap" class="fontv2"><?php echo e($bp_en ?? ''); ?></p>
    <p style="position:absolute;top:634px;left:465px;white-space:nowrap" class="fontv2">Nationality</p>
    <p style="position:absolute;top:544px;left:586px;white-space:nowrap" class="ft02">:</p>
    <p style="position:absolute;top:679px;left:586px;white-space:nowrap" class="ft02">:</p>
    <p style="position:absolute;top:589px;left:586px;white-space:nowrap" class="ft02">:</p>
    <p style="position:absolute;top:634px;left:586px;white-space:nowrap" class="ft02">:</p>
    <p style="position:absolute;top:724px;left:586px;white-space:nowrap" class="ft02">:</p>
    <p style="position:absolute;top:769px;left:586px;white-space:nowrap" class="ft02">:</p>
    <p style="position:absolute;top:454px;left:105px;white-space:nowrap" class="fontv2">Date of Birth</p>
    <p style="position:absolute;top:454px;left:244px;white-space:nowrap" class="ft02">:</p>
    <p style="position:absolute;top:454px;left:258px;white-space:nowrap" class="fontv2"><?php echo e(date('d/m/Y', strtotime($dob ?? ''))); ?></p>
    <p style="position:absolute;top:543px;left:105px;white-space:nowrap" class="ft06">নাম</p>
    <p style="position:absolute;top:543px;left:245px;white-space:nowrap" class="ft06">:</p>
    <p style="position:absolute;top:588px;left:245px;white-space:nowrap" class="ft06">:</p>
    <p style="position:absolute;top:633px;left:245px;white-space:nowrap" class="ft06">:</p>
    <p style="position:absolute;top:678px;left:245px;white-space:nowrap" class="ft06">:</p>
    <p style="position:absolute;top:723px;left:245px;white-space:nowrap" class="ft06">:</p>
    <p style="position:absolute;top:768px;left:245px;white-space:nowrap" class="ft06">:</p>
    <p style="position:absolute;top:484px;left:105px;white-space:nowrap" class="fontv2">In Word</p>
    <p style="position:absolute;top:484px;left:244px;white-space:nowrap" class="ft02">:</p>
    <p style="position:absolute; top:828px; left:258px; white-space:normal; width:200px; word-wrap:break-word; overflow-wrap:break-word;" class="peraddress">
    <?php echo e($addressx ?? ''); ?>
</p>

    <p style="position:absolute; top:829px; left:600px; white-space:normal; width:250px; word-wrap:break-word; overflow-wrap:break-word;" class="fontv2">
    <?php echo e($address ?? ''); ?>
</p>
    <p style="position:absolute;top:829px;left:465px;white-space:nowrap" class="fontv2">Permanent<br/>Address</p>
    <p style="position:absolute;top:829px;left:586px;white-space:nowrap" class="ft02">:</p>
    <p style="position:absolute;top:826px;left:245px;white-space:nowrap" class="ft08">:</p>
    <p style="position:absolute;top:828px;left:105px;white-space:nowrap" class="ft06">স্থায়ী ঠিকানা</p>
    <p style="position:absolute;top:1054px;left:148px;white-space:nowrap;font-size:15px" class="fontv2">Seal &amp; Signature</p>
    <p style="position:absolute;top:1082px;left:131px;white-space:nowrap;font-size:15px" class="fontv2bold">Assistant to Registrar</p>
    <p style="position:absolute;top:1105px;left:117px;white-space:nowrap;font-size:15px" class="fontv2">(Preparation, Verification)</p>
    <p style="position:absolute;top:1054px;left:691px;white-space:nowrap;font-size:15px" class="fontv2">Seal &amp; Signature</p>
    <p style="position:absolute;top:1082px;left:718px;white-space:nowrap;font-size:15px" class="fontv2bold">Registrar</p>
    <p style="position:absolute;top:1185px;left:120px;white-space:nowrap;font-size:11.5px" class="fontv2">This certificate is generated from bdris.gov.bd, and to verify this certificate, please scan the above QR Code &amp; Bar Code.</p>
    
    <?php if ($is_duplicate): ?>
    <p style="font-size:15px; position:absolute;top:55px;left:655px;font-weight:reguler;" class="boxduplicate">প্রতিলিপি/Duplicate</p>
    <?php endif; ?>
    
    <?php if ($is_corrected): ?>
    <p style="font-size:15px; position:absolute;top:55px;left:655px;font-weight:reguler;" class="boxcorrected">সংশোধিত/Corrected</p>
    <?php endif; ?>

</div>

<div class="no-print" style="text-align:center;padding:20px 0;display:flex;justify-content:center;gap:12px;">
    <button onclick="window.print()" style="padding:12px 40px;font-size:16px;background:#00d47e;color:#000;border:none;border-radius:8px;cursor:pointer;font-weight:600;">
        🖨️ Print Certificate
    </button>
    <button onclick="adjustFontSize()" style="padding:12px 24px;font-size:16px;background:#2563eb;color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:600;">
        🔤 Font Size
    </button>
</div>
<script>
function adjustFontSize() {
    var el = document.getElementById('brn-label');
    var sizes = ['18px', '20px', '22px'];
    var cur = el.style.fontSize || '18px';
    var idx = sizes.indexOf(cur);
    if (idx === -1) idx = 0;
    var next = sizes[(idx + 1) % sizes.length];
    el.style.fontSize = next;
}
</script>

</body>
</html>