<?php
// Central auth + wallet (replaces the old per-tool auth/config.php).
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_login();
repdoc_security_headers();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: index.php');
    exit;
}

// ── Billing: one credit per generated certificate (price 0 = free) ──
$charge = charge_tool('death_generator');
if (!$charge['ok']) {
    http_response_code(402);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>ব্যালেন্স পর্যাপ্ত নয়</title>'
       . '<p style="font:16px/1.7 system-ui,sans-serif;max-width:520px;margin:80px auto;text-align:center">'
       . e($charge['message'])
       . '<br><br><a href="index.php">ফর্মে ফিরে যান</a></p>';
    exit;
}

$reg_date     = $_POST['reg_date'] ?? '';
$issue_date   = $_POST['issue_date'] ?? '';
$reg_code     = trim($_POST['reg_code'] ?? 'ZTDEI');
$nibkar       = ucwords(strtolower(trim($_POST['nibkar'] ?? '')), " \t\r\n\f\v'-");
$thanaandzilla = trim($_POST['thanaandzilla'] ?? '');
$drn          = trim($_POST['death_registration_number'] ?? '');
$gender       = ucwords(strtolower(trim($_POST['gender'] ?? '')));
$dob          = $_POST['dob'] ?? '';
$name_bn      = mb_convert_case(trim($_POST['name_bn'] ?? ''), MB_CASE_TITLE, 'UTF-8');
$name_en      = ucwords(strtolower(trim($_POST['name_en'] ?? '')), " \t\r\n\f\v'-");
$mother_name_bn = mb_convert_case(trim($_POST['mother_name_bn'] ?? ''), MB_CASE_TITLE, 'UTF-8');
$mother_name_en = ucwords(strtolower(trim($_POST['mother_name_en'] ?? '')), " \t\r\n\f\v'-");
$mother_nat_bn = mb_convert_case(trim($_POST['mother_nat_bn'] ?? 'বাংলাদেশী'), MB_CASE_TITLE, 'UTF-8');
$mother_nat_en = ucwords(strtolower(trim($_POST['mother_nat_en'] ?? 'Bangladeshi')), " \t\r\n\f\v'-");
$father_name_bn = mb_convert_case(trim($_POST['father_name_bn'] ?? ''), MB_CASE_TITLE, 'UTF-8');
$father_name_en = ucwords(strtolower(trim($_POST['father_name_en'] ?? '')), " \t\r\n\f\v'-");
$father_nat_bn = mb_convert_case(trim($_POST['father_nat_bn'] ?? 'বাংলাদেশী'), MB_CASE_TITLE, 'UTF-8');
$father_nat_en = ucwords(strtolower(trim($_POST['father_nat_en'] ?? 'Bangladeshi')), " \t\r\n\f\v'-");
$dod          = $_POST['dod'] ?? '';
$dodt         = mb_convert_case(trim($_POST['dodt'] ?? ''), MB_CASE_TITLE, 'UTF-8');
$pod_bn       = mb_convert_case(trim($_POST['pod_bn'] ?? ''), MB_CASE_TITLE, 'UTF-8');
$pod_en       = ucwords(strtolower(trim($_POST['pod_en'] ?? '')), " \t\r\n\f\v/'-");
$cause_bn     = mb_convert_case(trim($_POST['cause_bn'] ?? ''), MB_CASE_TITLE, 'UTF-8');
$cause_en     = ucwords(strtolower(trim($_POST['cause_en'] ?? '')), " \t\r\n\f\v'-");
$is_duplicate = isset($_POST['is_duplicate']);
$is_corrected = isset($_POST['is_corrected']);

// Full submission goes to the database (admin → Document Logs).
log_document('death_generator', $drn, $dob, '', '', $_POST);
$document_title = 'Death Certificate - ' . $name_en . ' | ' . $drn;

$fmt_date = static function (string $value): string {
    $ts = strtotime($value);
    return $ts ? date('d/m/Y', $ts) : '';
};

$values = [
    'd-reg-code'        => $reg_code,
    'd-date-reg'        => $fmt_date($reg_date),
    'd-date-issue'      => $fmt_date($issue_date),
    'd-reg-num'         => $drn,
    'd-dob'             => $fmt_date($dob),
    'd-name-bn'         => $name_bn,
    'd-name-en'         => $name_en,
    'd-sex'             => 'Sex : ' . $gender,
    'd-mother-bn'       => $mother_name_bn,
    'd-mother-en'       => $mother_name_en,
    'd-mother-nat-bn'   => $mother_nat_bn,
    // The template's English nationality rows are positioned opposite the
    // Bengali rows, so these two values are mapped by visual row.
    'd-mother-nat-en'   => $father_nat_en,
    'd-father-bn'       => $father_name_bn,
    'd-father-en'       => $father_name_en,
    'd-father-nat-bn'   => $father_nat_bn,
    'd-father-nat-en'   => $mother_nat_en,
    'd-dod'             => $fmt_date($dod),
    'd-dod-word'        => $dodt,
    'd-place-bn'        => $pod_bn,
    'd-place-en'        => $pod_en,
    'd-cause-bn'        => ': ' . $cause_bn,
    'd-cause-en'        => $cause_en,
];

// Certificate template: the original pdf2htmlEX layout, filled server-side.
$html = @file_get_contents(__DIR__ . '/deatha.html');
if ($html === false) {
    http_response_code(500);
    echo 'Certificate template is missing.';
    exit;
}

// Drop the client-side data-entry panel and its script — this page is the print view.
$html = preg_replace('/<!-- ===== DATA ENTRY FORM PANEL ===== -->.*?<\/script>\s*/s', '', $html);

$html = str_replace(
    '<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />',
    '<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />' . "\n    " . '<meta name="robots" content="noindex">',
    $html
);

foreach ($values as $id => $value) {
    $pattern = '/(<div\b[^>]*\bid="' . preg_quote($id, '/') . '"[^>]*>)(.*?)(<\/div>)/s';
    $html = preg_replace_callback($pattern, static function (array $m) use ($value): string {
        return $m[1] . e($value) . $m[3];
    }, $html, 1);
}

// Registration office lines are plain text, not IDs.
$html = preg_replace('/>Balua Union Parishad</', '>' . e($nibkar) . '<', $html, 1);
$html = preg_replace('/>Sonatola, Bogura</', '>' . e($thanaandzilla) . '<', $html, 1);

// The source template only labels the upper English nationality row.
$html = str_replace(
    '<div id="d-mother-nat-en"',
    '<div class="t m0 xf h2 y19 ff1 fs1 fc0 sc0 ls0 ws0">Nationality</div>' . "\n                " . '<div id="d-mother-nat-en"',
    $html
);

// Duplicate / corrected stamps, matching the birth certificate placement.
$marks = '';
if ($is_duplicate) {
    $marks .= '<div style="position:absolute;top:55px;right:40px;border:1.8px solid #000;padding:5px 10px;font-family:\'Bangla\',\'BanglaCustom\',sans-serif;font-size:17px;">প্রতিলিপি/Duplicate</div>';
}
if ($is_corrected) {
    $marks .= '<div style="position:absolute;top:95px;right:40px;border:1.8px solid #000;padding:5px 10px;font-family:\'Bangla\',\'BanglaCustom\',sans-serif;font-size:17px;">সংশোধিত/Corrected</div>';
}
if ($marks !== '') {
    $html = str_replace('<div class="pi"', $marks . '<div class="pi"', $html);
}

$toolbar = <<<'HTML'
<style>
    @media print { #death-toolbar { display: none !important; } }
    body { background: #e5e7eb; margin: 0; }
</style>
<div id="death-toolbar" style="text-align:center;padding:20px 0;display:flex;justify-content:center;gap:12px;">
    <button onclick="window.print()" style="padding:12px 40px;font-size:16px;background:#00d47e;color:#000;border:none;border-radius:8px;cursor:pointer;font-weight:600;">
        🖨️ Print Certificate
    </button>
</div>
HTML;
$html = str_replace('</body>', $toolbar . "\n" . '</body>', $html);

echo $html;
