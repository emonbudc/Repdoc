<?php
require_once __DIR__ . '/auth/config.php';

// Output escaping (XSS hardening for certificate fields).
if (!function_exists('e')) {
    function e($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
}
// Allow verified certificate printing from main portal without mandatory login.
// If user is not logged in but a valid POST payload is present, render the certificate directly.
// This satisfies: once certificate is verified, print button renders demo/output.php in new tab.
if (!isLoggedIn() && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    // For GET or empty requests, still require login to prevent unauthenticated browsing
    // but for POST from verified flow, allow rendering without redirect.
    // Keep login redirect only for non-POST access when not authenticated.
    if (empty($_POST['birth_registration_number']) && empty($_POST['name_bn'])) {
        header('Location: auth/login.php');
        exit;
    }
}
// Helpers for auto-translation of Bangla fields to English (Permanent Address etc.)
function isBanglaTextPHP($s) { return preg_match('/[\x{0980}-\x{09FF}]/u', $s); }
function transliteratePhoneticPHP($text) {
    $map = [
        'খা'=>'Kha','ঘা'=>'Gha','ছা'=>'Chha','ঝা'=>'Jha','ঠা'=>'Tha','ঢা'=>'Dha','ফা'=>'Pha','ভা'=>'Bha','শা'=>'Sha','ষা'=>'Sha',
        'খ'=>'Kh','ঘ'=>'Gh','ং'=>'Ng','ছ'=>'Chh','ঝ'=>'Jh','ঞ'=>'Ny','ঠ'=>'Th','ঢ'=>'Dh','ফ'=>'Ph','ভ'=>'Bh','শ'=>'Sh','ষ'=>'Sh',
        'ণ'=>'N','ত'=>'T','থ'=>'Th','দ'=>'D','ধ'=>'Dh','ন'=>'N',
        'া'=>'a','ি'=>'i','ী'=>'i','ু'=>'u','ূ'=>'u','ে'=>'e','ৈ'=>'oi','ো'=>'o','ৌ'=>'ou','্'=>'',
        'ক'=>'K','গ'=>'G','চ'=>'Ch','জ'=>'J','ট'=>'T','ড'=>'D','প'=>'P','ব'=>'B','ম'=>'M','য'=>'J','র'=>'R','ল'=>'L','স'=>'S','হ'=>'H','ড়'=>'R','ঢ়'=>'Rh','য়'=>'Y',
        'অ'=>'A','আ'=>'A','ই'=>'I','ঈ'=>'I','উ'=>'U','ঊ'=>'U','ঋ'=>'R','এ'=>'E','ঐ'=>'Oi','ও'=>'O','ঔ'=>'Ou',
    ];
    uksort($map, function($a,$b){ return mb_strlen($b,'UTF-8') - mb_strlen($a,'UTF-8'); });
    return strtr($text, $map);
}
function banglaToEnglishAddressPHP($bn) {
    if (!$bn || !isBanglaTextPHP($bn)) return $bn;
    // Normalize digits, replace Bangla visarga "ঃ" with ":" for English (instead of  use :), and remove Division/Bangladesh
    $b2e = ['০'=>'0','১'=>'1','২'=>'2','৩'=>'3','৪'=>'4','৫'=>'5','৬'=>'6','৭'=>'7','৮'=>'8','৯'=>'9'];
    $s = strtr($bn, $b2e);
    $s = str_replace(['ঃ','ঃঃ'], ':', $s);
    $parts = explode(',', $s);
    $filtered = [];
    foreach ($parts as $p) {
        if (preg_match('/বিভাগ|বাংলাদেশ|Division|Bangladesh/i', $p)) continue;
        $filtered[] = $p;
    }
    $s = implode(',', $filtered);
    $s = trim(preg_replace('/\s*,\s*,/', ',', $s), " ,");
    // Prefix replacements for certificate format
    $s = preg_replace('/গ্রাম\s*:/u', 'Village:', $s);
    $s = preg_replace('/গ্রাম/u', 'Village', $s);
    $s = preg_replace('/ডাকঘর\s*:/u', 'Post:', $s);
    $s = preg_replace('/ডাকঘর/u', 'Post', $s);
    $s = preg_replace('/ওয়ার্ড\s*নং\s*-/u', 'Ward no -', $s);
    $s = preg_replace('/ওয়ার্ড\s*নং\s*-/u', 'Ward no -', $s);
    $s = preg_replace('/ওয়ার্ড\s*-/u', 'Ward no -', $s);
    $s = preg_replace('/ওয়ার্ড\s*-/u', 'Ward no -', $s);
    $s = preg_replace('/ওয়ার্ড/u', 'Ward', $s);
    $s = preg_replace('/ওয়ার্ড/u', 'Ward', $s);
    $dict = [
        'মোহাম্মদ'=>'Mohammad','মোছাঃ'=>'Mst.','মোসাঃ'=>'Mst.','মোসাম্মৎ'=>'Mosammat','মোঃ'=>'Md.','রহিম'=>'Rahim','করিম'=>'Karim','কারিম'=>'Karim','রহমান'=>'Rahman','হোসেন'=>'Hossain','হুসেন'=>'Hossain','আলী'=>'Ali','আলি'=>'Ali','বেগম'=>'Begum','খাতুন'=>'Khatun','আক্তার'=>'Akter','আকতার'=>'Akter','ইসলাম'=>'Islam','হাসান'=>'Hasan','হুসাইন'=>'Husain','ফাতেমা'=>'Fatema','আয়েশা'=>'Ayesha','সোহেল'=>'Sohel','রানা'=>'Rana','শাকিব'=>'Shakib','সাকিব'=>'Sakib','শাকিল'=>'Shakil','মাসুদ'=>'Masud','রাকিব'=>'Rakib','রুবেল'=>'Rubel','মিয়া'=>'Mia','মন্ডল'=>'Mondol','সরকার'=>'Sarker','চৌধুরী'=>'Chowdhury','শেখ'=>'Sheikh','উদ্দিন'=>'Uddin','উদ্দীন'=>'Uddin','খান'=>'Khan','হক'=>'Haque','সিদ্দিক'=>'Siddique','আহমেদ'=>'Ahmed','মাহমুদ'=>'Mahmud','আব্দুল'=>'Abdul','আব্দুর'=>'Abdur','নূর'=>'Noor',
        'গ্রাম'=>'Village','ইউনিয়ন'=>'Union','ইউনিয়ন'=>'Union','থানা'=>'Thana','উপজেলা'=>'Upazila','জেলা'=>'Zila','জিলা'=>'Zila','পৌরসভা'=>'Pourashava','সিটি'=>'City','কর্পোরেশন'=>'Corporation','রোড'=>'Road','বাড়ি'=>'Bari','বাড়ি'=>'Bari','হোল্ডিং'=>'Holding','মহল্লা'=>'Mahalla','পাড়া'=>'Para','পাড়া'=>'Para','বাজার'=>'Bazar','হাট'=>'Hat','চর'=>'Char','নগর'=>'Nagar','পুর'=>'Pur','গঞ্জ'=>'Ganj','বালুয়াহাট'=>'Baluahat','বালুয়াহাট'=>'Baluahat','বালুয়া'=>'Balua','বালুয়া'=>'Balua','গবর'=>'Gobar','গবার'=>'Gobar','গোবর'=>'Gobar','বগুড়া'=>'Bogura','সোনাতলা'=>'Sonatola','বগুরা'=>'Bogura','রাজশাহী'=>'Rajshahi',
    ];
    $segs = preg_split('/(\s+|,|:)/u', $s, -1, PREG_SPLIT_DELIM_CAPTURE);
    $out = '';
    foreach ($segs as $tok) {
        if (preg_match('/^\s+$/u', $tok) || $tok===',' || $tok===':') { $out .= $tok; continue; }
        $clean = trim($tok, "।.,;");
        if (isset($dict[$clean])) { $out .= $dict[$clean]; continue; }
        $base = preg_replace('/[^ঀ-৿A-Za-z0-9]+/u','',$clean);
        if (isset($dict[$base])) { $out .= $dict[$base]; continue; }
        if (isBanglaTextPHP($tok)) $out .= transliteratePhoneticPHP($tok);
        else $out .= $tok;
    }
    $out = preg_replace('/\s+/u',' ', $out);
    $out = preg_replace('/\s+,/u',',', $out);
    $out = preg_replace('/\s*:\s*/u',': ', $out);
    $out = preg_replace('/Ward no\s*-/u','Ward no -', $out);
    // Title-case English words, keep Village/Post/Ward
    $parts2 = explode(',', $out);
    $parts2 = array_map(function($p){
        $p=trim($p);
        if ($p==='') return '';
        $words = preg_split('/\s+/u', $p);
        $words = array_map(function($w){
            if (preg_match('/^(Village|Post|Ward|no|Union|Thana|Upazila|Zila)$/i',$w)) return ucfirst(strtolower($w));
            if (preg_match('/^[A-Za-z.:-]+$/',$w)) return ucfirst(strtolower($w));
            return $w;
        }, $words);
        return implode(' ', $words);
    }, $parts2);
    $out = implode(', ', array_filter(array_map('trim',$parts2), function($v){return $v!=='';}));
    return trim(preg_replace('/\s+/u',' ', $out));
}
function ensureEnglishFieldPHP($banglaFallback, $englishField) {
    if ($englishField !== '' && $englishField !== null && !isBanglaTextPHP($englishField)) return $englishField;
    if ($englishField !== '' && isBanglaTextPHP($englishField)) return banglaToEnglishAddressPHP($englishField);
    if ($banglaFallback !== '' && isBanglaTextPHP($banglaFallback)) return banglaToEnglishAddressPHP($banglaFallback);
    return $banglaFallback;
}
function stripDivisionCountryPHP($s) {
    if (!$s) return $s;
    $parts = explode(',', $s);
    $filtered = [];
    foreach ($parts as $p) {
        if (preg_match('/বিভাগ|বাংলাদেশ|Division|Bangladesh/i', $p)) continue;
        $filtered[] = $p;
    }
    $out = implode(',', $filtered);
    $out = trim(preg_replace('/\s*,\s*,/', ',', $out), " ,");
    $out = preg_replace('/\s+/u', ' ', $out);
    return trim($out);
}
function lastTwoWordsPHP($s) {
    $s = trim((string)$s);
    if ($s === '') return $s;
    if (strpos($s, ',') !== false) {
        $parts = explode(',', $s);
        $parts = array_map('trim', $parts);
        $parts = array_values(array_filter($parts, fn($v)=>$v!==''));
        if (count($parts) >= 2) {
            $last = array_slice($parts, -2);
            return implode(', ', $last);
        }
        return $parts[0] ?? $s;
    } else {
        $words = preg_split('/\s+/u', $s);
        $words = array_values(array_filter($words, fn($v)=>$v!=='' && $v!==':'));
        if (count($words) >= 2) {
            $last = array_slice($words, -2);
            return implode(' ', $last);
        }
        return $s;
    }
}

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
$document_title = "Birth Certificate";
$dob = $dobt = $name_bn = $father_name_bn = $mother_name_bn = $mother_name_en = $father_name_en = $nibkar = $thanaandzilla = $name_en = $gender = $issuedate = $givendate = $bp_bn = $bp_en = $addressx = $address = $birth_registration_number = "";
$is_duplicate = $is_corrected = false;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dob = $_POST['dob'] ?? '';
    $dobt = $_POST['dobt'] ?? '';
    $name_bn = $_POST['name_bn'] ?? '';
    $father_name_bn = $_POST['father_name_bn'] ?? '';
    $mother_name_bn = $_POST['mother_name_bn'] ?? '';
    $mother_name_en = ucwords(strtolower($_POST['mother_name_en'] ?? ''), " \t\r\n\f\v'-");
    $father_name_en = ucwords(strtolower($_POST['father_name_en'] ?? ''), " \t\r\n\f\v'-");
    $nibkar = ucwords(strtolower($_POST['nibkar'] ?? ''), " \t\r\n\f\v'-");
    $thanaandzilla = $_POST['thanaandzilla'] ?? '';
    $name_en = ucwords(strtolower($_POST['name_en'] ?? ''), " \t\r\n\f\v'-");
    $gender = $_POST['gender'] ?? '';
    $issuedate = $_POST['issuedate'] ?? date('Y-m-d');
    $givendate = $_POST['givendate'] ?? date('Y-m-d');
    $bp_bn = $_POST['bp_bn'] ?? '';
    $bp_en = $_POST['bp_en'] ?? '';
    $addressx = $_POST['addressx'] ?? '';
    $address = ucwords(strtolower($_POST['address'] ?? ''), " \t\r\n\f\v'-");
    $birth_registration_number = $_POST['birth_registration_number'] ?? '';
    // Hide Division and Country on Bangla as well (per requirement)
    $bp_bn = stripDivisionCountryPHP($bp_bn);
    $addressx = stripDivisionCountryPHP($addressx);
    // English Permanent Address: autofill from Bangla via transliteration (only field allowed to autofill) - other English fields only everify server
    $raw_address = $_POST['address'] ?? '';
    if ($address === '' || isBanglaTextPHP($address) || isBanglaTextPHP($raw_address)) {
        $fallbackBn = $addressx !== '' ? $addressx : $bp_bn;
        $candidate = $raw_address !== '' ? $raw_address : $fallbackBn;
        $translated = banglaToEnglishAddressPHP($candidate);
        if ($translated !== '' && !isBanglaTextPHP($translated)) $address = $translated;
    }
    $address = stripDivisionCountryPHP($address);
    $bp_en = stripDivisionCountryPHP($bp_en);
    // Birthplace prints exactly as stored in the database (no truncation).
    // Fixed district/country line when the record has no English birthplace.
    if ($bp_en === '') $bp_en = 'Bogura, Bangladesh';
    // Other English fields (name_en, father/mother, bp_en) remain empty if everify did not provide - do not auto-transliterate

    $document_title = "Birth Certificate - " . $name_en . " | " . $birth_registration_number;
    $is_duplicate = isset($_POST['is_duplicate']) ? true : false;
    $is_corrected = isset($_POST['is_corrected']) ? true : false;

    // Server-side fallback: generate dobt (In Word) if not provided, empty, or looks like a raw date (e.g. 2010-00-01)
    $isDateLike = preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($dobt)) || preg_match('/^\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}$/', trim($dobt));
    if (($dobt === '' || $isDateLike) && $dob !== '') {
        $ts = strtotime($dob);
        if ($ts) {
            $d = (int)date('j', $ts); $m = (int)date('n', $ts); $y = (int)date('Y', $ts);
            $ordinals = ['First','Second','Third','Fourth','Fifth','Sixth','Seventh','Eighth','Ninth','Tenth','Eleventh','Twelfth','Thirteenth','Fourteenth','Fifteenth','Sixteenth','Seventeenth','Eighteenth','Nineteenth','Twentieth','Twenty First','Twenty Second','Twenty Third','Twenty Fourth','Twenty Fifth','Twenty Sixth','Twenty Seventh','Twenty Eighth','Twenty Ninth','Thirtieth','Thirty First'];
            $months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
            // Helpers for year to text (mirrors demo/index.php logic)
            $numToTextFn = function($n) use (&$numToTextFn) {
                $units = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen','Seventeen','Eighteen','Nineteen'];
                $tens = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
                if ($n==0) return 'Zero';
                if ($n<20) return $units[$n];
                if ($n<100) { $t=intdiv($n,10); $u=$n%10; return $u? $tens[$t].' '.$units[$u] : $tens[$t]; }
                if ($n<1000) { $h=intdiv($n,100); $r=$n%100; return $r? $units[$h].' Hundred '.$numToTextFn($r) : $units[$h].' Hundred'; }
                $th=intdiv($n,1000); $r=$n%1000; return $r? $numToTextFn($th).' Thousand '.$numToTextFn($r) : $numToTextFn($th).' Thousand';
            };
            $yearText = '';
            if ($y >= 2000 && $y < 3000) { $rem=$y%1000; $yearText = $rem==0? 'Two Thousand' : 'Two Thousand '.$numToTextFn($rem); }
            elseif ($y >= 1900 && $y < 2000) { $rem=$y%100; $yearText = $rem==0? 'Nineteen Hundred' : 'Nineteen Hundred '.$numToTextFn($rem); }
            else { $th=intdiv($y,1000); $rem=$y%1000; $yearText = $rem==0? $numToTextFn($th).' Thousand' : $numToTextFn($th).' Thousand '.$numToTextFn($rem); }
            if ($d>=1 && $d<=31 && $m>=1 && $m<=12) {
                $dobt = $ordinals[$d-1] . ' of ' . $months[$m-1] . ' ' . $yearText;
            }
        }
    }

    // Proper case formatting (Abcd Abcd format) - only if values present
    if ($name_bn !== '') $name_bn = mb_convert_case($name_bn, MB_CASE_TITLE, 'UTF-8');
    if ($father_name_bn !== '') $father_name_bn = mb_convert_case($father_name_bn, MB_CASE_TITLE, 'UTF-8');
    if ($mother_name_bn !== '') $mother_name_bn = mb_convert_case($mother_name_bn, MB_CASE_TITLE, 'UTF-8');
    if ($bp_bn !== '') $bp_bn = mb_convert_case($bp_bn, MB_CASE_TITLE, 'UTF-8');
    if ($bp_en !== '') $bp_en = ucwords(strtolower($bp_en), " \t\r\n\f\v'-");
    if ($addressx !== '') $addressx = mb_convert_case($addressx, MB_CASE_TITLE, 'UTF-8');
    if ($dobt !== '') $dobt = mb_convert_case($dobt, MB_CASE_TITLE, 'UTF-8');
    if ($gender !== '') $gender = ucwords(strtolower($gender));
    if ($thanaandzilla !== '') $thanaandzilla = mb_convert_case($thanaandzilla, MB_CASE_TITLE, 'UTF-8');
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo e($document_title); ?></title>
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

        .seal{
            font-family: 'Times new Roman';
            font-size: 17px;
            color: #0003b1;
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
<div class="no-print" style="text-align:center;padding:20px 0;display:flex;justify-content:center;gap:12px;">
    <button onclick="window.print()" style="padding:12px 40px;font-size:16px;background:#00d47e;color:#000;border:none;border-radius:8px;cursor:pointer;font-weight:600;">
        🖨️ Print Certificate
    </button>
</div>
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

    <p style="position:absolute;top:250px;left:436px;white-space:nowrap" class="fontv2"><?php echo e($thanaandzilla); ?></p>
    <p style="position:absolute;top:222px;left:420px;white-space:nowrap" class="fontv2"><?php echo e($nibkar); ?></p>
    <!--<p style="font-family:Arial; opacity:0.5;position:absolute;top:235px;left:127px;white-space:nowrap" class="ft0x">IDDE</p>-->
    
    
    
    <p style="position:absolute;top:363px;left:105px;white-space:nowrap;font-size:14px" class="fontv2">Date of Registration</p>
    <p style="position:absolute;top:387px;left:105px;white-space:nowrap;font-size:14px" class="fontv2"><?php echo date('d/m/Y', strtotime($givendate)); ?></p>
    <p id="brn-label" style="position: absolute; top: 363px; left: 353px; white-space: nowrap; font-size: 22px;" class="brnox">Birth Registration Number</p>
    <p style="position:absolute;top:363px;left:698px;white-space:nowrap;font-size:14px" class="fontv2">Date of Issuance</p>
    <p style="position:absolute;top:387px;left:698px;white-space:nowrap;font-size:14px" class="fontv2"><?php echo date('d/m/Y', strtotime($issuedate)); ?></p>
    <p style="font-weight: bold;position:absolute;top:387px;left:379px;white-space:nowrap" class="brnumber"><?php echo e($birth_registration_number); ?></p>
    <p style="position:absolute;top:588px;left:105px;white-space:nowrap" class="ft06">মাতা</p>
    <p style="position:absolute;top:633px;left:105px;white-space:nowrap" class="ft06">মাতার জাতীয়তা</p>
    <p style="position:absolute;top:723px;left:105px;white-space:nowrap" class="ft06">পিতার জাতীয়তা</p>
    <p style="position:absolute;top:768px;left:105px;white-space:nowrap" class="ft06">জন্মস্থান</p>
    <p style="position:absolute;top:678px;left:105px;white-space:nowrap" class="ft06">পিতা</p>
    <p style="position:absolute;top:543px;left:260px;white-space:nowrap" class="ft06"><?php echo e($name_bn); ?></p>
    <p style="position:absolute;top:588px;left:260px;white-space:nowrap" class="ft06"><?php echo e($mother_name_bn); ?></p>
    <p style="position:absolute;top:633px;left:260px;white-space:nowrap" class="ft06">বাংলাদেশী</p>
    <p style="position:absolute;top:678px;left:260px;white-space:nowrap" class="fathername"><?php echo e($father_name_bn); ?></p>
    <p style="position:absolute;top:723px;left:260px;white-space:nowrap" class="ft06">বাংলাদেশী</p>
    <p style="position:absolute;top:454px;left:602px;white-space:nowrap" class="fontv2">Sex : <?php echo e($gender); ?></p>
    <p style="position:absolute;top:483px;left:258px;white-space:nowrap;font-size:17px" class="fontv2italic"><?php echo e($dobt); ?></p>
    <p style="position:absolute;top:544px;left:600px;white-space:nowrap" class="fontv2"><?php echo e($name_en); ?></p>
    <p style="position:absolute;top:589px;left:600px;white-space:nowrap" class="fontv2"><?php echo e($mother_name_en); ?></p>
    <p style="position:absolute;top:679px;left:600px;white-space:nowrap" class="fontv2"><?php echo e($father_name_en); ?></p>
    <p style="position:absolute;top:544px;left:465px;white-space:nowrap" class="fontv2">Name</p>
    <p style="position:absolute;top:588px;left:465px;white-space:nowrap" class="fontv2">Mother</p>
    <p style="position:absolute;top:679px;left:465px;white-space:nowrap" class="fontv2">Father</p>
    <p style="position:absolute;top:634px;left:600px;white-space:nowrap" class="fontv2">Bangladeshi</p>
    <p style="position:absolute;top:724px;left:600px;white-space:nowrap" class="fontv2">Bangladeshi</p>
    <p style="position:absolute;top:724px;left:465px;white-space:nowrap" class="fontv2">Nationality</p>
    <p style="position:absolute;top:769px;left:465px;white-space:nowrap" class="fontv2">Place of Birth</p>
    <p style="position:absolute;top:768px;left:260px;white-space:nowrap" class="jonmosthanbn"><?php echo e($bp_bn); ?></p>
    <p style="position:absolute;top:769px;left:600px;white-space:nowrap" class="fontv2"><?php echo e($bp_en); ?></p>
    <p style="position:absolute;top:634px;left:465px;white-space:nowrap" class="fontv2">Nationality</p>
    <p style="position:absolute;top:544px;left:586px;white-space:nowrap" class="ft02">:</p>
    <p style="position:absolute;top:679px;left:586px;white-space:nowrap" class="ft02">:</p>
    <p style="position:absolute;top:589px;left:586px;white-space:nowrap" class="ft02">:</p>
    <p style="position:absolute;top:634px;left:586px;white-space:nowrap" class="ft02">:</p>
    <p style="position:absolute;top:724px;left:586px;white-space:nowrap" class="ft02">:</p>
    <p style="position:absolute;top:769px;left:586px;white-space:nowrap" class="ft02">:</p>
    <p style="position:absolute;top:454px;left:105px;white-space:nowrap" class="fontv2">Date of Birth</p>
    <p style="position:absolute;top:454px;left:244px;white-space:nowrap" class="ft02">:</p>
    <p style="position:absolute;top:454px;left:258px;white-space:nowrap" class="fontv2"><?php echo date('d/m/Y', strtotime($dob)); ?></p>
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
    <?php echo e($addressx); ?>
</p>

    <p style="position:absolute; top:829px; left:600px; white-space:normal; width:250px; word-wrap:break-word; overflow-wrap:break-word;" class="fontv2">
    <?php echo e($address); ?>
</p>
    <p style="position:absolute;top:829px;left:465px;white-space:nowrap" class="fontv2">Permanent<br/>Address</p>
    <p style="position:absolute;top:829px;left:586px;white-space:nowrap" class="ft02">:</p>
    <p style="position:absolute;top:826px;left:245px;white-space:nowrap" class="ft08">:</p>
    <p style="position:absolute;top:828px;left:105px;white-space:nowrap" class="ft06">স্থায়ী ঠিকানা</p>
    <p style="position:absolute;top:1054px;left:148px;white-space:nowrap;font-size:15px" class="fontv2">Seal &amp; Signature</p>
    <p style="position:absolute;top:1082px;left:131px;white-space:nowrap;font-size:15px" class="fontv2bold">Assistant to Registrar</p>
    <p style="position:absolute;top:1105px;left:117px;white-space:nowrap;font-size:15px" class="fontv2">(Preparation, Verification)</p>

    <p style="position:absolute;top:1125px;left:145px;white-space:nowrap;font-size:18px" class="seal"><b>Md Mafiul Hasan<b></p>
    <p style="position:absolute;top:1140px;left:180px;white-space:nowrap;font-size:18px" class="seal"><b>AACO<b></p>
    <p style="position:absolute;top:1158px;left:121px;white-space:nowrap;font-size:16px" class="seal"><b>No.2 Balua Union Parishad<b></p>
    <p style="position:absolute;top:1173px;left:148px;white-space:nowrap;font-size:16px" class="seal"><b>Sonatola, Bogura<b></p>
    



    <p style="position:absolute;top:1054px;left:691px;white-space:nowrap;font-size:15px" class="fontv2">Seal &amp; Signature</p>
    <p style="position:absolute;top:1082px;left:718px;white-space:nowrap;font-size:15px" class="fontv2bold">Registrar</p>
    <p style="position:absolute;top:1185px;left:120px;white-space:nowrap;font-size:11.5px" class="fontv2">This certificate is generated from bdris.gov.bd, and to verify this certificate, please scan the above QR Code &amp; Bar Code.</p>
    

    <p style="position:absolute;top:1100px;left:691px;white-space:nowrap;font-size:18px" class="seal"><b>Md Abdur Razzak<b></p>
    <p style="position:absolute;top:1117px;left:703px;white-space:nowrap;font-size:15px" class="seal"><b>Panel Chairman-1<b></p>
    <p style="position:absolute;top:1135px;left:673px;white-space:nowrap;font-size:15px" class="seal"><b>No.2 Balua Union Parishad<b></p>
    <p style="position:absolute;top:1153px;left:699px;white-space:nowrap;font-size:16px" class="seal"><b>Sonatola, Bogura<b></p>
    



    
    <?php if ($is_duplicate): ?>
    <p style="font-size:15px; position:absolute;top:55px;left:655px;font-weight:reguler;" class="boxduplicate">প্রতিলিপি/Duplicate</p>
    <?php endif; ?>
    
    <?php if ($is_corrected): ?>
    <p style="font-size:15px; position:absolute;top:55px;left:655px;font-weight:reguler;" class="boxcorrected">সংশোধিত/Corrected</p>
    <?php endif; ?>

</div>

</body>
</html>