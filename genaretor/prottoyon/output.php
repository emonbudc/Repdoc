<?php
declare(strict_types=1);

// Central auth + wallet — same pattern as other generators.
// Output mirrors the repo certificate layout (landscape A4, bg, header,
// title row, meta, fragment body, signature + QR footer).
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_once __DIR__ . '/types.php';
require_login();
repdoc_security_headers();

// ── UDC users can only issue from their own office ──
$__me = current_user();
if (is_udc() && (($__me['role'] ?? '') !== 'admin')) {
    $__prof = office_profile();
    if (($__prof['office_name'] ?? '') === '' || ($__prof['upazila'] ?? '') === '' || ($__prof['district'] ?? '') === '') {
        flash_set('error', 'আগে অফিস সেটিংস সম্পূর্ণ করুন — নিজের অফিস ছাড়া প্রত্যয়ন দেওয়া যাবে না।');
        redirect(repdoc_url('genaretor/prottoyon/office.php'));
    }
}

$types = prottoyon_types();
$type  = isset($_POST['cert_type']) ? (string) $_POST['cert_type'] : '';
if (!isset($types[$type])) {
    $type = 'miscellaneous';
}
$toolKey = 'prottoyon_' . $type;

// ── Billing: per-type price (0 = free, admins free) ──
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $charge = charge_tool($toolKey);
    if (!$charge['ok']) {
        http_response_code(402);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><meta charset="utf-8"><title>ব্যালেন্স পর্যাপ্ত নয়</title>'
           . '<p style="font:16px/1.7 system-ui,sans-serif;max-width:520px;margin:80px auto;text-align:center">'
           . e($charge['message'])
           . '<br><br><a href="index.php">ফর্মে ফিরে যান</a></p>';
        exit;
    }
}

if (!function_exists('ppost')) {
    function ppost($k, $d = '') { return isset($_POST[$k]) ? trim((string) $_POST[$k]) : $d; }
}
if (!function_exists('en2bnNumber')) {
    function en2bnNumber($str) {
        return str_replace(['0','1','2','3','4','5','6','7','8','9'], ['০','১','২','৩','৪','৫','৬','৭','৮','৯'], (string) $str);
    }
}
if (!function_exists('formatDate')) {
    function formatDate($date, $format = 'd F, Y') { return prottoyon_formatDate($date); }
}
if (!function_exists('prottoyon_formatDate')) {
    function prottoyon_formatDate($date) {
        $ts = strtotime((string) $date);
        if ($ts === false) return (string) $date;
        $bnMonths = ['January' => 'জানুয়ারি', 'February' => 'ফেব্রুয়ারি', 'March' => 'মার্চ',
            'April' => 'এপ্রিল', 'May' => 'মে', 'June' => 'জুন', 'July' => 'জুলাই',
            'August' => 'আগস্ট', 'September' => 'সেপ্টেম্বর', 'October' => 'অক্টোবর',
            'November' => 'নভেম্বর', 'December' => 'ডিসেম্বর'];
        $day = (int) date('j', $ts);
        $suffix = 'ই';
        if (in_array($day, [1, 21, 31], true)) $suffix = 'লা';
        elseif (in_array($day, [2, 3, 22, 23], true)) $suffix = 'রা';
        elseif (in_array($day, [4, 24], true)) $suffix = 'ঠা';
        return en2bnNumber((string) $day) . $suffix . ' ' . ($bnMonths[date('F', $ts)] ?? date('F', $ts)) . ' ' . en2bnNumber(date('Y', $ts));
    }
}

// ── Applicant object for template/<name>.php fragments ──
$user = new stdClass();
$user->name_bn = ppost('name_bn', 'নাম');
$user->father_name = ppost('father_bn', '');
$user->mother_name = ppost('mother_bn', '');
$user->dob = ppost('dob', '');
$user->gender = ppost('gender', '');
$user->religion = ppost('religion', '');
$user->nid = ppost('nid', '');
$user->birth_reg_no = ppost('brn', '');
$user->mobile = ppost('mobile', '');
$user->village = ppost('village', '');
$user->ward_no = ppost('ward', '');
$user->post_office = ppost('post_office', '');
$user->upazila_name = ppost('upazila', '');
$user->district_name = ppost('district', '');
$user->occupation = ppost('occupation', '');
foreach ($types[$type]['extras'] as $ex) {
    $k = $ex['k'];
    $user->$k = ppost($k, '');
}

// ── Office snapshot (UDC profile at generation time) ──
$office = office_profile();
if ($office['upazila'] === '' && $user->upazila_name !== '') $office['upazila'] = $user->upazila_name;
if ($office['district'] === '' && $user->district_name !== '') $office['district'] = $user->district_name;

// ── UDC users: addresses always come from the office (form is locked too) ──
$currentRole = (current_user()['role'] ?? '');
if (is_udc() && $currentRole !== 'admin') {
    if ($office['upazila'] !== '') {
        $user->upazila_name = $office['upazila'];
        $_POST['upazila'] = $office['upazila'];
    }
    if ($office['district'] !== '') {
        $user->district_name = $office['district'];
        $_POST['district'] = $office['district'];
    }
    if (($office['office_name'] ?? '') !== '') {
        $_POST['union'] = $office['office_name'];
    }
}

$union = new stdClass();
$union->name_bn = $office['office_name'] !== '' ? $office['office_name'] : 'ইউনিয়ন পরিষদ';
$union->upazila_name = $office['upazila'] !== '' ? $office['upazila'] : '[উপজেলার নাম]';
$union->district_name = $office['district'] !== '' ? $office['district'] : '[জেলার নাম]';
$union->office_type = $office['office_type'];
$union->logo_url = 'assets/images/gov_logo.png';

$refNo = '';
for ($i = 0; $i < 10 && $refNo === ''; $i++) {
    $cand = (string) random_int(10000000000, 99999999999); // 11-digit sonod number
    try {
        $dbChk = repdoc_db();
        $stChk = $dbChk->prepare('SELECT COUNT(*) FROM document_logs WHERE cert_no = ?');
        $stChk->execute([$cand]);
        if ((int) $stChk->fetchColumn() === 0) $refNo = $cand;
    } catch (Throwable $e) {
        $refNo = $cand;
    }
}
if ($refNo === '') $refNo = (string) random_int(10000000000, 99999999999);
$certificate = new stdClass();
$certificate->certificate_no = $refNo;
$certificate->issue_date = date('Y-m-d');
$certType = new stdClass();
$certType->name_bn = $types[$type]['title'];
$chairman = $office['chairman_name'] !== '' ? $office['chairman_name'] : 'চেয়ারম্যানের নাম';
$preparer = '';

$dob = $user->dob;
$nid = $user->nid;
$mobile = $user->mobile;

// Full submission goes to the database (admin → Prottoyon Logs).
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ref = $nid !== '' ? $nid : $mobile;
    log_document($toolKey, $ref, $dob, '', '', $_POST, $refNo, $office);
}

$docTitle = $types[$type]['title'] . ' - ' . $user->name_bn;
$fragFile = __DIR__ . '/template/' . $type . '.php';
if (!is_file($fragFile)) $fragFile = __DIR__ . '/template/default.php';
$revoked = false;
$baseHref = '';
$actionsHtml = '<button type="button" class="primary" onclick="window.print()">🖨 Print / PDF</button>'
    . '<a href="index.php">← ফর্মে ফিরে যান</a>';
require __DIR__ . '/render.php';
