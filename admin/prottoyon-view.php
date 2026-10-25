<?php
declare(strict_types=1);

/**
 * Repdoc — admin: view / download (print) one generated prottoyon.
 * Re-renders the saved snapshot, so later office changes don't alter history.
 * Revoked certificates show a বাতিল watermark.
 */

require_once __DIR__ . '/includes/layout.php';
require_once dirname(__DIR__) . '/genaretor/prottoyon/types.php';
repdoc_security_headers();

$id = (int) ($_GET['id'] ?? 0);
$db = repdoc_db();
$row = null;

if ($db && $id > 0) {
    try {
        $st = $db->prepare('SELECT * FROM document_logs WHERE id = ?');
        $st->execute([$id]);
        $row = $st->fetch() ?: null;
        if ($row && strpos((string) $row['tool_key'], 'prottoyon_') !== 0) $row = null;
    } catch (Throwable $e) {
        $row = null;
    }
}

if (!$row) {
    admin_page_start('Prottoyon', 'prottoyon.php');
    echo '<div class="alert alert-error" role="alert">সনদ পাওয়া যায়নি।</div>';
    echo '<p><a class="btn btn-secondary btn-sm" href="prottoyon.php">← ফিরে যান</a></p>';
    admin_page_end();
    exit;
}

if (!function_exists('en2bnNumber')) {
    function en2bnNumber($str) {
        return str_replace(['0','1','2','3','4','5','6','7','8','9'], ['০','১','২','৩','৪','৫','৬','৭','৮','৯'], (string) $str);
    }
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
if (!function_exists('formatDate')) {
    function formatDate($date, $format = 'd F, Y') { return prottoyon_formatDate($date); }
}

$types = prottoyon_types();
$tkey  = preg_replace('/^prottoyon_/', '', (string) $row['tool_key']);
if (!isset($types[$tkey])) $tkey = 'miscellaneous';

$data = [];
if (!empty($row['data'])) {
    $d = json_decode((string) $row['data'], true);
    if (is_array($d)) $data = $d;
}
$office = [];
if (!empty($row['office'])) {
    $o = json_decode((string) $row['office'], true);
    if (is_array($o)) $office = $o;
}
$val = static fn(string $k, string $d = '') => isset($data[$k]) ? trim((string) $data[$k]) : $d;

$user = new stdClass();
$user->name_bn = $val('name_bn', 'নাম');
$user->father_name = $val('father_bn', '');
$user->mother_name = $val('mother_bn', '');
$user->dob = $val('dob', '');
$user->gender = $val('gender', '');
$user->religion = $val('religion', '');
$user->nid = $val('nid', '');
$user->birth_reg_no = $val('brn', '');
$user->mobile = $val('mobile', '');
$user->village = $val('village', '');
$user->ward_no = $val('ward', '');
$user->post_office = $val('post_office', '');
$user->upazila_name = $val('upazila', '');
$user->district_name = $val('district', '');
$user->occupation = $val('occupation', '');
foreach ($types[$tkey]['extras'] as $ex) {
    $k = $ex['k'];
    $user->$k = $val($k, '');
}

$union = new stdClass();
$union->name_bn = ($office['office_name'] ?? '') !== '' ? (string) $office['office_name'] : 'ইউনিয়ন পরিষদ';
$union->upazila_name = ($office['upazila'] ?? '') !== '' ? (string) $office['upazila'] : '[উপজেলার নাম]';
$union->district_name = ($office['district'] ?? '') !== '' ? (string) $office['district'] : '[জেলার নাম]';
$union->office_type = (string) ($office['office_type'] ?? 'union');
$union->logo_url = 'assets/images/gov_logo.png';

$refNo = (string) ($row['cert_no'] ?? '');
if ($refNo === '' || !preg_match('/^\d{11}$/', $refNo)) $refNo = '00000000000';
$certificate = new stdClass();
$certificate->certificate_no = $refNo;
$certificate->issue_date = substr((string) $row['created_at'], 0, 10);
$certType = new stdClass();
$certType->name_bn = $types[$tkey]['title'];
$chairman = ($office['chairman_name'] ?? '') !== '' ? (string) $office['chairman_name'] : 'চেয়ারম্যানের নাম';

$docTitle = $types[$tkey]['title'] . ' - ' . $user->name_bn;
$fragFile = dirname(__DIR__) . '/genaretor/prottoyon/template/' . $tkey . '.php';
if (!is_file($fragFile)) $fragFile = dirname(__DIR__) . '/genaretor/prottoyon/template/default.php';
$revoked = (($row['status'] ?? 'valid') === 'revoked');
$baseHref = repdoc_url('genaretor/prottoyon/');
if (substr($baseHref, -1) !== '/') $baseHref .= '/';
$actionsHtml = '<button type="button" class="primary" onclick="window.print()">🖨 Print / PDF</button>'
    . '<a href="' . e(repdoc_url('admin/prottoyon.php')) . '">← ফিরে যান</a>';

require dirname(__DIR__) . '/genaretor/prottoyon/render.php';
