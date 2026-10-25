<?php
declare(strict_types=1);

// Central auth + wallet — same pattern as genaretor/birth/output.php.
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_login();

// ── Billing: one charge per generated CV (price 0 = free, admins free) ──
// Live preview posts (live_preview=1) never charge, log, save files, or show buttons.
$isLive = isset($_POST['live_preview']);
if ($_SERVER["REQUEST_METHOD"] == "POST" && !$isLive) {
    $charge = charge_tool('cvmaker');
    if (!$charge['ok']) {
        http_response_code(402);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><meta charset="utf-8"><title>Insufficient balance</title>'
           . '<p style="font:16px/1.7 system-ui,sans-serif;max-width:520px;margin:80px auto;text-align:center">'
           . e($charge['message'])
           . '<br><br><a href="index.php">Back to form</a></p>';
        exit;
    }
}

// ── Photo upload (optional, ≤2MB; skipped in live preview; server-side validated) ──
$photoPath = '';
if (!$isLive && isset($_FILES['photo']) && ($_FILES['photo']['error'] ?? 1) == 0) {
    $safe = repdoc_validate_image_upload($_FILES['photo'], 2 * 1024 * 1024);
    if ($safe !== '') {
        $dir = __DIR__ . '/uploads/';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $photoPath = 'uploads/' . $safe;
        if (!@move_uploaded_file($_FILES['photo']['tmp_name'], $dir . basename($photoPath))) {
            $photoPath = '';
        }
    }
}

function post($k, $d = '') { return isset($_POST[$k]) ? trim((string) $_POST[$k]) : $d; }
function postArr($k) {
    $v = $_POST[$k] ?? [];
    if (!is_array($v)) return [];
    return array_map(static fn($x) => trim((string) $x), $v);
}

$CV_KEYS = ['modern', 'classic', 'minimal', 'bangla', 'executive', 'creative', 'corporate', 'portfolio', 'slate', 'elite', 'bold', 'standard', 'dynamic', 'word', 'insight'];
$template = post('template', 'modern');
if (!in_array($template, $CV_KEYS, true)) $template = 'modern';

$fullName = post('full_name', 'Your Name');
$nameBn   = post('name_bn', '');
$jobTitle = post('job_title', '');
$email    = post('email', '');
$phone    = post('phone', '');
$address  = post('present_address', '');
$permAddr = post('permanent_address', '');
$fatherName = post('father_name', '');
$motherName = post('mother_name', '');
$dob      = post('dob', '');
$religion = post('religion', '');
$gender   = post('gender', '');
$blood    = post('blood_group', '');
$nationality = post('nationality', '');
$nidNum   = post('nid', '');
$summary  = post('summary', '');
$refName  = post('ref_name', '');
$refOrg   = post('ref_org', '');
$refPhone = post('ref_phone', '');
$decl     = post('declaration', '');

$eduD = postArr('edu_degree'); $eduI = postArr('edu_institute');
$eduY = postArr('edu_year');   $eduR = postArr('edu_result');
$edu = [];
foreach ($eduD as $i => $deg) {
    $ins = $eduI[$i] ?? '';
    if ($deg === '' && $ins === '') continue;
    $edu[] = ['deg' => $deg, 'ins' => $ins, 'year' => $eduY[$i] ?? '', 'res' => $eduR[$i] ?? ''];
}

$expT = postArr('exp_title'); $expC = postArr('exp_company');
$expP = postArr('exp_period'); $expD = postArr('exp_detail'); $expL = postArr('exp_location');
$exp = [];
foreach ($expT as $i => $t) {
    $c = $expC[$i] ?? ''; $p = $expP[$i] ?? ''; $d = $expD[$i] ?? ''; $l = $expL[$i] ?? '';
    if ($t === '' && $c === '' && $p === '' && $d === '' && $l === '') continue;
    $exp[] = ['title' => $t, 'co' => $c, 'period' => $p, 'detail' => $d, 'loc' => $l];
}

$certN = postArr('cert_name'); $certY = postArr('cert_year');
$certs = [];
foreach ($certN as $i => $n) {
    $y = $certY[$i] ?? '';
    if ($n === '' && $y === '') continue;
    $certs[] = ['name' => $n, 'year' => $y];
}
$interests = array_values(array_filter(array_map('trim', explode(',', post('interests')))));

$skills = array_values(array_filter(array_map('trim', explode(',', post('skills')))));
$langs  = array_values(array_filter(array_map('trim', explode(',', post('languages')))));

// Live preview mode (template chooser iframes): render rich mock data,
// no charge, no log, no action buttons.
$preview = isset($_GET['preview']) ? (string) $_GET['preview'] : '';
if (in_array($preview, $CV_KEYS, true)) {
    $template = $preview;
    $fullName = 'Tanvir Ahmed';
    $nameBn   = 'তানভীর আহমেদ';
    $jobTitle = 'Graphic Designer';
    $email    = 'tanvir.ahmed@example.com';
    $phone    = '01712-345678';
    $address  = 'House 12, Road 5, Dhanmondi, Dhaka';
    $permAddr = 'Vill: Rampur, P.O: Rampur-1230, Upazila: Savar, Dhaka';
    $fatherName = 'Abdul Karim';
    $motherName = 'Nasrin Begum';
    $dob      = '15 Mar 1998';
    $religion = 'ইসলাম';
    $gender   = 'পুরুষ';
    $blood    = 'O+';
    $nationality = 'বাংলাদেশী';
    $nidNum   = '19901234567890123';
    $summary  = 'Creative graphic designer with 4 years of experience in branding and print media. Skilled at turning ideas into clean, impactful visuals.';
    $edu = [
        ['deg' => 'B.Sc in Computer Science', 'ins' => 'National University', 'year' => '2020', 'res' => 'CGPA 3.55'],
        ['deg' => 'HSC (Science)', 'ins' => 'Dhaka College', 'year' => '2016', 'res' => 'GPA 5.00'],
    ];
    $exp = [
        ['title' => 'Graphic Designer', 'co' => 'Creative IT', 'period' => '2022 — Present', 'detail' => 'Brand identities, social media kits and marketing print materials.', 'loc' => 'Dhaka, Bangladesh'],
        ['title' => 'Junior Designer', 'co' => 'Print House', 'period' => '2020 — 2022', 'detail' => 'Layout design for brochures, banners and event materials.', 'loc' => 'Dhaka, Bangladesh'],
    ];
    $certs = [
        ['name' => 'Google UX Design Professional', 'year' => '2023'],
        ['name' => 'Product Design Foundations', 'year' => '2022'],
    ];
    $interests = ['Travel', 'Photography', 'Reading'];
    $skills = ['Photoshop', 'Illustrator', 'MS Word', 'Communication'];
    $langs  = ['Bangla', 'English'];
    $refName  = 'Engr. Mahmud Hasan';
    $refOrg   = 'Manager, ABC Company';
    $refPhone = '01811-223344';
    $decl     = '1';
    $photoPath = '';
}

// Full submission goes to the database (admin → Document Logs).
if ($_SERVER["REQUEST_METHOD"] == "POST" && !$isLive) {
    log_document('cvmaker', $fullName, $dob,
        $photoPath !== '' ? 'genaretor/cvmaker/' . $photoPath : '', '', $_POST);
}

$initials = mb_strtoupper(mb_substr(preg_replace('/\s+/', '', $fullName), 0, 2, 'UTF-8'), 'UTF-8');
$docTitle = 'CV - ' . $fullName;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($docTitle) ?></title>
<meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: #525659; font-family: 'Inter', 'Noto Sans Bengali', Arial, sans-serif; color: #1f2937; }
    .cv-actions {
        max-width: 210mm; margin: 16px auto; padding: 0 12px;
        display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;
    }
    .cv-actions button, .cv-actions a {
        font: inherit; font-size: 15px; font-weight: 600; cursor: pointer;
        padding: 11px 22px; min-height: 44px; border-radius: 8px;
        border: 1px solid #d1d5db; background: #fff; color: #111827; text-decoration: none;
    }
    .cv-actions .primary { background: #0369A1; border-color: #0369A1; color: #fff; }
    .cv-sheet {
        width: 210mm; min-height: 290mm; margin: 0 auto 32px; background: #fff;
        box-shadow: 0 4px 24px rgba(0,0,0,.4); overflow: hidden;
    }
    /* ── MODERN ── */
    .tpl-modern { display: flex; min-height: 290mm; }
    .tpl-modern .side { width: 68mm; background: #0f172a; color: #e2e8f0; padding: 10mm 7mm; flex: none; }
    .tpl-modern .side .ph {
        width: 38mm; height: 38mm; border-radius: 50%; overflow: hidden; margin: 0 auto 6mm;
        background: #0369A1; color: #fff; display: flex; align-items: center; justify-content: center;
        font-size: 28px; font-weight: 800; border: 3px solid #38bdf8;
    }
    .tpl-modern .side .ph img { width: 100%; height: 100%; object-fit: cover; }
    .tpl-modern .side h3 { font-size: 13px; letter-spacing: 2px; text-transform: uppercase; color: #38bdf8; margin: 7mm 0 3mm; border-bottom: 1px solid #334155; padding-bottom: 2mm; }
    .tpl-modern .side p, .tpl-modern .side li { font-size: 12.5px; line-height: 1.7; list-style: none; word-break: break-word; }
    .tpl-modern .main { flex: 1; padding: 10mm 9mm; }
    .tpl-modern .main h1 { font-size: 30px; font-weight: 800; color: #0f172a; line-height: 1.2; }
    .tpl-modern .main .role { font-size: 15px; color: #0369A1; font-weight: 600; margin: 2mm 0 5mm; }
    .tpl-modern .main h2 { font-size: 14px; letter-spacing: 2px; text-transform: uppercase; color: #0f172a; border-bottom: 2px solid #0369A1; padding-bottom: 2mm; margin: 7mm 0 3mm; }
    .tpl-modern .main p { font-size: 13.5px; line-height: 1.75; }
    .tpl-modern .item { margin-bottom: 4mm; }
    .tpl-modern .item .t { font-weight: 700; font-size: 14px; }
    .tpl-modern .item .s { font-size: 12.5px; color: #475569; }
    /* ── CLASSIC ── */
    .tpl-classic { padding: 12mm 14mm; font-family: Georgia, 'Times New Roman', serif; }
    .tpl-classic header { text-align: center; border-bottom: 3px double #1f2937; padding-bottom: 5mm; margin-bottom: 6mm; }
    .tpl-classic header h1 { font-size: 30px; letter-spacing: 1px; }
    .tpl-classic header .role { font-style: italic; font-size: 15px; margin: 2mm 0; }
    .tpl-classic header .contact { font-size: 12.5px; font-family: Arial, sans-serif; }
    .tpl-classic .cph { width: 32mm; height: 38mm; object-fit: cover; border: 1px solid #9ca3af; margin: 0 auto 4mm; display: block; }
    .tpl-classic h2 { font-size: 16px; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid #1f2937; margin: 6mm 0 3mm; }
    .tpl-classic p, .tpl-classic li { font-size: 13.5px; line-height: 1.75; }
    .tpl-classic ul { margin-left: 6mm; }
    .tpl-classic .item { margin-bottom: 3mm; }
    /* ── MINIMAL ── */
    .tpl-minimal { padding: 14mm 16mm; }
    .tpl-minimal .top { display: flex; gap: 8mm; align-items: center; border-bottom: 3px solid #0369A1; padding-bottom: 6mm; margin-bottom: 6mm; }
    .tpl-minimal .mph {
        width: 30mm; height: 30mm; border-radius: 8px; overflow: hidden; flex: none;
        background: #eff6ff; color: #0369A1; display: flex; align-items: center; justify-content: center;
        font-size: 24px; font-weight: 800;
    }
    .tpl-minimal .mph img { width: 100%; height: 100%; object-fit: cover; }
    .tpl-minimal h1 { font-size: 28px; font-weight: 800; }
    .tpl-minimal .role { color: #475569; font-size: 14px; margin-top: 1mm; }
    .tpl-minimal h2 { font-size: 13px; text-transform: uppercase; letter-spacing: 2.5px; color: #0369A1; margin: 7mm 0 3mm; }
    .tpl-minimal p { font-size: 13.5px; line-height: 1.8; color: #334155; }
    .tpl-minimal .contact { font-size: 12.5px; color: #475569; margin-top: 2mm; }
    .tpl-minimal .chips { display: flex; flex-wrap: wrap; gap: 2mm; }
    .tpl-minimal .chips span { font-size: 12.5px; background: #eff6ff; border: 1px solid #bae6fd; border-radius: 999px; padding: 1mm 4mm; }
    .tpl-minimal .item { margin-bottom: 4mm; }
    .tpl-minimal .item .t { font-weight: 700; }
    .tpl-minimal .item .s { font-size: 12.5px; color: #64748b; }
    /* ── Tables (classic + minimal education / experience) ── */
    .cv-table { width: 100%; border-collapse: collapse; margin: 3mm 0 5mm; font-size: 13px; line-height: 1.6; }
    .cv-table th, .cv-table td { border: 1px solid #6b7280; padding: 2mm 3mm; text-align: left; vertical-align: top; }
    .cv-table th { background: #f3f4f6; font-size: 12.5px; }
    .tpl-minimal .cv-table th, .tpl-minimal .cv-table td { border: none; border-bottom: 1px solid #cbd5e1; padding: 2.5mm 2mm; }
    .tpl-minimal .cv-table th { background: none; color: #0369A1; text-transform: uppercase; font-size: 11px; letter-spacing: 1.5px; }
    /* ── EXECUTIVE (dark band + gold) ── */
    .tpl-executive .xband { background: #111827; color: #fff; padding: 10mm 12mm; display: flex; gap: 7mm; align-items: center; }
    .tpl-executive .xband .xph { width: 30mm; height: 30mm; border-radius: 50%; overflow: hidden; flex: none; background: #b45309; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: 800; border: 2px solid #d4a017; }
    .tpl-executive .xband .xph img { width: 100%; height: 100%; object-fit: cover; }
    .tpl-executive .xband h1 { font-size: 28px; letter-spacing: .5px; }
    .tpl-executive .xband .role { color: #e8c15a; font-size: 14px; margin-top: 1mm; }
    .tpl-executive .xband .contact { font-size: 12px; color: #d1d5db; margin-top: 2mm; }
    .tpl-executive .xbody { padding: 8mm 12mm; }
    .tpl-executive h2 { font-size: 13px; letter-spacing: 2px; text-transform: uppercase; color: #92400e; border-bottom: 2px solid #d4a017; padding-bottom: 2mm; margin: 6mm 0 3mm; }
    .tpl-executive p { font-size: 13.5px; line-height: 1.75; }
    .tpl-executive .item { margin-bottom: 3.5mm; }
    /* ── CREATIVE (teal accent bar + chips) ── */
    .tpl-creative { padding: 10mm 12mm 10mm 16mm; border-left: 8mm solid #0d9488; }
    .tpl-creative .chead { display: flex; gap: 6mm; align-items: center; margin-bottom: 5mm; }
    .tpl-creative .cph { width: 28mm; height: 28mm; border-radius: 20px; overflow: hidden; flex: none; background: #ccfbf1; color: #0f766e; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 800; }
    .tpl-creative .cph img { width: 100%; height: 100%; object-fit: cover; }
    .tpl-creative h1 { font-size: 27px; color: #0f172a; }
    .tpl-creative .role { display: inline-block; background: #0d9488; color: #fff; font-size: 13px; font-weight: 600; border-radius: 999px; padding: 1mm 5mm; margin-top: 2mm; }
    .tpl-creative h2 { font-size: 15px; color: #0f766e; margin: 6mm 0 3mm; }
    .tpl-creative p { font-size: 13.5px; line-height: 1.75; }
    .tpl-creative .chips { display: flex; flex-wrap: wrap; gap: 2mm; }
    .tpl-creative .chips span { font-size: 12.5px; background: #f0fdfa; border: 1.5px solid #0d9488; color: #0f766e; border-radius: 8px; padding: 1mm 4mm; font-weight: 600; }
    .tpl-creative .item { margin-bottom: 3.5mm; }
    /* ── CORPORATE (navy bar, tables) ── */
    .tpl-corporate .cbar { background: #1e3a8a; color: #fff; padding: 8mm 12mm; display: flex; justify-content: space-between; align-items: center; gap: 5mm; }
    .tpl-corporate .cbar h1 { font-size: 26px; font-family: Georgia, serif; }
    .tpl-corporate .cbar .contact { font-size: 12px; text-align: right; line-height: 1.8; }
    .tpl-corporate .cbody { padding: 7mm 12mm; }
    .tpl-corporate h2 { font-size: 14px; color: #1e3a8a; text-transform: uppercase; letter-spacing: 1px; margin: 6mm 0 3mm; border-bottom: 1px solid #1e3a8a; padding-bottom: 2mm; }
    .tpl-corporate p { font-size: 13.5px; line-height: 1.75; }
    /* ── PORTFOLIO (centered + timeline) ── */
    .tpl-portfolio { padding: 10mm 14mm; text-align: center; }
    .tpl-portfolio .pph { width: 34mm; height: 34mm; border-radius: 50%; overflow: hidden; margin: 0 auto 4mm; background: #ede9fe; color: #6d28d9; display: flex; align-items: center; justify-content: center; font-size: 26px; font-weight: 800; border: 3px solid #8b5cf6; }
    .tpl-portfolio .pph img { width: 100%; height: 100%; object-fit: cover; }
    .tpl-portfolio h1 { font-size: 28px; }
    .tpl-portfolio .role { color: #7c3aed; font-weight: 600; margin: 1mm 0 2mm; }
    .tpl-portfolio .contact { font-size: 12.5px; color: #475569; }
    .tpl-portfolio h2 { font-size: 13px; letter-spacing: 3px; text-transform: uppercase; color: #6d28d9; margin: 7mm 0 4mm; }
    .tpl-portfolio h2::after { content: ""; display: block; width: 16mm; height: 3px; background: #8b5cf6; margin: 2mm auto 0; border-radius: 2px; }
    .tpl-portfolio .tl { text-align: left; border-left: 3px solid #c4b5fd; margin-left: 4mm; padding-left: 6mm; }
    .tpl-portfolio .tl .item { margin-bottom: 4mm; position: relative; }
    .tpl-portfolio .tl .item::before { content: ""; position: absolute; left: -8.2mm; top: 2mm; width: 3.5mm; height: 3.5mm; border-radius: 50%; background: #7c3aed; }
    .tpl-portfolio p { font-size: 13.5px; line-height: 1.75; }
    /* ── SLATE (gray panels) ── */
    .tpl-slate { padding: 10mm 12mm; }
    .tpl-slate header { background: #f1f5f9; border-left: 6px solid #475569; padding: 5mm 6mm; margin-bottom: 5mm; }
    .tpl-slate header h1 { font-size: 27px; color: #1e293b; }
    .tpl-slate header .role { color: #475569; font-weight: 600; }
    .tpl-slate header .contact { font-size: 12.5px; color: #64748b; margin-top: 1mm; }
    .tpl-slate h2 { font-size: 14px; color: #1e293b; background: #e2e8f0; padding: 2mm 4mm; border-radius: 4px; margin: 6mm 0 3mm; }
    .tpl-slate p { font-size: 13.5px; line-height: 1.75; }
    .tpl-slate .item { margin-bottom: 3mm; }
    /* ── ELITE (black luxury, serif) ── */
    .tpl-elite { padding: 12mm 16mm; font-family: Georgia, serif; }
    .tpl-elite header { text-align: center; margin-bottom: 6mm; }
    .tpl-elite header h1 { font-size: 30px; letter-spacing: 4px; text-transform: uppercase; }
    .tpl-elite header .rule { width: 30mm; height: 2px; background: #000; margin: 3mm auto; }
    .tpl-elite header .contact { font-size: 12.5px; font-family: Arial, sans-serif; color: #374151; }
    .tpl-elite h2 { font-size: 14px; letter-spacing: 3px; text-transform: uppercase; text-align: center; margin: 7mm 0 4mm; }
    .tpl-elite p, .tpl-elite li { font-size: 13.5px; line-height: 1.8; }
    .tpl-elite ul { list-style: none; text-align: center; }
    .tpl-elite .item { text-align: center; margin-bottom: 3mm; }
    /* ── BOLD (two-tone blocks) ── */
    .tpl-bold .btop { background: #ea580c; color: #fff; padding: 9mm 12mm; }
    .tpl-bold .btop h1 { font-size: 32px; font-weight: 800; text-transform: uppercase; line-height: 1.1; }
    .tpl-bold .btop .role { font-size: 15px; font-weight: 600; margin-top: 2mm; }
    .tpl-bold .bsub { background: #134e4a; color: #fff; padding: 3mm 12mm; font-size: 12.5px; }
    .tpl-bold .bbody { padding: 7mm 12mm; }
    .tpl-bold h2 { display: inline-block; font-size: 14px; text-transform: uppercase; background: #134e4a; color: #fff; padding: 1.5mm 5mm; border-radius: 4px; margin: 6mm 0 3mm; }
    .tpl-bold p { font-size: 13.5px; line-height: 1.75; }
    .tpl-bold .chips { display: flex; flex-wrap: wrap; gap: 2mm; }
    .tpl-bold .chips span { background: #134e4a; color: #fff; font-size: 12.5px; border-radius: 4px; padding: 1mm 4mm; font-weight: 600; }
    .tpl-bold .item { margin-bottom: 3.5mm; }
    /* ── STANDARD (plain applicant) ── */
    .tpl-standard { padding: 12mm 14mm; }
    .tpl-standard h1 { font-size: 26px; }
    .tpl-standard .contact { font-size: 13px; color: #374151; margin: 2mm 0 4mm; border-bottom: 2px solid #111827; padding-bottom: 4mm; }
    .tpl-standard h2 { font-size: 15px; text-decoration: underline; margin: 6mm 0 3mm; }
    .tpl-standard p { font-size: 13.5px; line-height: 1.75; }
    .tpl-standard .item { margin-bottom: 3mm; }
    /* ── DYNAMIC (right rail) ── */
    .tpl-dynamic { display: flex; min-height: 290mm; }
    .tpl-dynamic .main { flex: 1; padding: 10mm 9mm; }
    .tpl-dynamic .main h1 { font-size: 29px; color: #0c4a6e; }
    .tpl-dynamic .main .role { color: #0284c7; font-weight: 600; margin: 2mm 0 5mm; }
    .tpl-dynamic .main h2 { font-size: 14px; text-transform: uppercase; letter-spacing: 1.5px; color: #0c4a6e; border-bottom: 2px solid #0284c7; padding-bottom: 2mm; margin: 6mm 0 3mm; }
    .tpl-dynamic .main p { font-size: 13.5px; line-height: 1.75; }
    .tpl-dynamic .main .item { margin-bottom: 3.5mm; }
    .tpl-dynamic .rail { width: 62mm; background: #0284c7; color: #fff; padding: 10mm 6mm; flex: none; }
    .tpl-dynamic .rail .rph { width: 32mm; height: 32mm; border-radius: 8px; overflow: hidden; margin-bottom: 5mm; background: #fff; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 800; }
    .tpl-dynamic .rail .rph img { width: 100%; height: 100%; object-fit: cover; }
    .tpl-dynamic .rail h3 { font-size: 12px; letter-spacing: 2px; text-transform: uppercase; border-bottom: 1px solid rgba(255,255,255,.5); padding-bottom: 2mm; margin: 6mm 0 3mm; }
    .tpl-dynamic .rail p, .tpl-dynamic .rail li { font-size: 12.5px; line-height: 1.7; list-style: none; word-break: break-word; }
    /* ── INSIGHT (sidebar dossier + timeline main) ── */
    .tpl-insight { display: flex; min-height: 290mm; }
    .tpl-insight .iside { width: 64mm; background: #1e293b; color: #e2e8f0; padding: 8mm 6mm; flex: none; }
    .tpl-insight .iside .iph { width: 30mm; height: 30mm; border-radius: 50%; overflow: hidden; margin: 0 auto 5mm; background: #2563eb; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 800; border: 2px solid #60a5fa; }
    .tpl-insight .iside .iph img { width: 100%; height: 100%; object-fit: cover; }
    .tpl-insight .iside h3 { font-size: 11px; letter-spacing: 2px; text-transform: uppercase; color: #93c5fd; border-bottom: 1px solid #475569; padding-bottom: 2mm; margin: 6mm 0 3mm; }
    .tpl-insight .iside p, .tpl-insight .iside li { font-size: 12px; line-height: 1.8; list-style: none; word-break: break-word; }
    .tpl-insight .iside li::before { content: "• "; color: #60a5fa; }
    .tpl-insight .imain { flex: 1; padding: 9mm 9mm; }
    .tpl-insight .imain h1 { font-size: 27px; color: #0f172a; }
    .tpl-insight .imain .role { font-size: 13.5px; color: #334155; margin: 1mm 0 4mm; }
    .tpl-insight .imain .sum { font-size: 13px; line-height: 1.75; border-bottom: 1px solid #cbd5e1; padding-bottom: 4mm; }
    .tpl-insight .imain h2 { font-size: 12px; letter-spacing: 2px; text-transform: uppercase; color: #0f172a; margin: 6mm 0 3mm; }
    .tpl-insight .irow { display: flex; justify-content: space-between; gap: 4mm; font-size: 13px; font-weight: 600; }
    .tpl-insight .irow .loc { font-weight: 400; color: #64748b; }
    .tpl-insight .irole { color: #1d4ed8; font-weight: 700; font-size: 13.5px; margin-top: 1mm; display: flex; justify-content: space-between; gap: 4mm; }
    .tpl-insight .irole .dt { color: #475569; font-weight: 400; font-size: 12.5px; }
    .tpl-insight .imain ul { margin: 2mm 0 4mm 5mm; font-size: 13px; line-height: 1.75; }
    .tpl-insight .imain p { font-size: 13px; line-height: 1.75; }
    /* ── WORD (plain document style) ── */
    .tpl-word { padding: 14mm 16mm; font-family: 'Times New Roman', Times, serif; color: #000; }
    .tpl-word h1 { font-size: 24px; }
    .tpl-word .contact { font-size: 13px; margin: 2mm 0 4mm; }
    .tpl-word h2 { font-size: 15px; font-weight: bold; margin: 6mm 0 2mm; }
    .tpl-word p, .tpl-word li { font-size: 13.5px; line-height: 1.7; }
    .tpl-word ul { margin-left: 7mm; }
    .tpl-word table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
    .tpl-word table td { padding: 1.5mm 2mm; vertical-align: top; }
    /* ── BANGLA (জীবনবৃত্তান্ত) ── */
    .tpl-bangla { padding: 10mm 12mm; font-family: 'Noto Sans Bengali', 'Hind Siliguri', Arial, sans-serif; }
    .tpl-bangla .b-title { text-align: center; font-size: 26px; font-weight: 700; color: #0f172a; border-bottom: 3px double #0f172a; padding-bottom: 4mm; margin-bottom: 5mm; }
    .tpl-bangla .b-head { display: flex; gap: 6mm; align-items: flex-start; margin-bottom: 5mm; }
    .tpl-bangla .b-head .b-name { flex: 1; }
    .tpl-bangla .b-head .b-name h1 { font-size: 24px; margin-bottom: 2mm; }
    .tpl-bangla .b-head .b-name p { font-size: 13px; color: #334155; line-height: 1.8; }
    .tpl-bangla .bph { width: 32mm; height: 38mm; object-fit: cover; border: 1px solid #64748b; flex: none; }
    .tpl-bangla h2 { font-size: 16px; font-weight: 700; background: #f1f5f9; border-right: 4px solid #0369A1; padding: 2mm 3mm; margin: 6mm 0 3mm; }
    .tpl-bangla p { font-size: 13.5px; line-height: 1.9; }
    .tpl-bangla .b-info { width: 100%; border-collapse: collapse; margin: 3mm 0 5mm; font-size: 13.5px; }
    .tpl-bangla .b-info td { border: 1px solid #94a3b8; padding: 2mm 3mm; vertical-align: top; line-height: 1.8; }
    .tpl-bangla .b-info td.k { width: 38mm; font-weight: 600; background: #f8fafc; }
    .tpl-bangla .b-sign { display: flex; justify-content: space-between; margin-top: 12mm; font-size: 13.5px; }
    .tpl-bangla .b-sign div { text-align: center; }
    .tpl-bangla .b-sign .line { border-top: 1px solid #0f172a; padding-top: 1mm; min-width: 45mm; }
    @media print {
        @page { size: A4; margin: 0; }
        body { background: #fff; }
        .cv-actions { display: none !important; }
        .cv-sheet { width: 100%; margin: 0; box-shadow: none; }
    }
</style>
<?php if ($preview !== '' || $isLive): ?>
<style>body { background: #fff !important; } .cv-sheet { box-shadow: none !important; margin: 0 auto !important; }</style>
<?php endif; ?>
</head>
<body>
<?php if ($preview === '' && !$isLive): ?>
<div class="cv-actions">
    <button type="button" class="primary" onclick="window.print()">🖨 Print / PDF</button>
    <a href="index.php">← Back to form</a>
</div>
<?php endif; ?>

<div class="cv-sheet">
<?php if ($template === 'classic'): ?>
    <div class="tpl-classic">
        <header>
            <?php if ($photoPath !== ''): ?><img class="cph" src="<?= e($photoPath) ?>" alt="Photo"><?php endif; ?>
            <h1><?= e($fullName) ?></h1>
            <?php if ($jobTitle !== ''): ?><div class="role"><?= e($jobTitle) ?></div><?php endif; ?>
            <div class="contact"><?= e($email) ?><?= $phone !== '' ? ' · ' . e($phone) : '' ?><?= $address !== '' ? ' · ' . e($address) : '' ?></div>
        </header>
        <?php if ($summary !== ''): ?><h2>Objective</h2><p><?= nl2br(e($summary)) ?></p><?php endif; ?>
        <?php if ($exp): ?><h2>Experience</h2>
        <table class="cv-table">
            <thead><tr><th>Title</th><th>Company</th><th>Period</th><th>Details</th></tr></thead>
            <tbody><?php foreach ($exp as $x): ?>
                <tr><td><strong><?= e($x['title']) ?></strong></td><td><?= e($x['co']) ?></td><td><?= e($x['period']) ?></td><td><?= e($x['detail']) ?></td></tr>
            <?php endforeach; ?></tbody>
        </table>
        <?php endif; ?>
        <?php if ($edu): ?><h2>Education</h2>
        <table class="cv-table">
            <thead><tr><th>Degree</th><th>Institute</th><th>Year</th><th>Result</th></tr></thead>
            <tbody><?php foreach ($edu as $d): ?>
                <tr><td><strong><?= e($d['deg']) ?></strong></td><td><?= e($d['ins']) ?></td><td><?= e($d['year']) ?></td><td><?= e($d['res']) ?></td></tr>
            <?php endforeach; ?></tbody>
        </table>
        <?php endif; ?>
        <?php if ($skills): ?><h2>Skills</h2><p><?= e(implode(', ', $skills)) ?></p><?php endif; ?>
        <?php if ($langs): ?><h2>Languages</h2><p><?= e(implode(', ', $langs)) ?></p><?php endif; ?>
        <?php if ($dob !== '' || $fatherName !== '' || $motherName !== '' || $permAddr !== ''): ?><h2>Personal</h2><p><?php
            $bits = [];
            if ($dob !== '') $bits[] = 'Date of Birth: ' . $dob;
            if ($fatherName !== '') $bits[] = "Father's Name: " . $fatherName;
            if ($motherName !== '') $bits[] = "Mother's Name: " . $motherName;
            if ($permAddr !== '') $bits[] = 'Permanent Address: ' . $permAddr;
            echo e(implode(' | ', $bits));
        ?></p><?php endif; ?>
    </div>
<?php elseif ($template === 'minimal'): ?>
    <div class="tpl-minimal">
        <div class="top">
            <div class="mph"><?php if ($photoPath !== ''): ?><img src="<?= e($photoPath) ?>" alt="Photo"><?php else: ?><?= e($initials) ?><?php endif; ?></div>
            <div>
                <h1><?= e($fullName) ?></h1>
                <?php if ($jobTitle !== ''): ?><div class="role"><?= e($jobTitle) ?></div><?php endif; ?>
                <div class="contact"><?= e($email) ?><?= $phone !== '' ? ' · ' . e($phone) : '' ?><?= $address !== '' ? '<br>Present: ' . e($address) : '' ?><?= $permAddr !== '' ? '<br>Permanent: ' . e($permAddr) : '' ?><?= ($fatherName !== '' || $motherName !== '') ? '<br>Parents: ' . e(trim($fatherName . ($fatherName !== '' && $motherName !== '' ? ' & ' : '') . $motherName)) : '' ?></div>
            </div>
        </div>
        <?php if ($summary !== ''): ?><h2>Summary</h2><p><?= nl2br(e($summary)) ?></p><?php endif; ?>
        <?php if ($exp): ?><h2>Experience</h2>
        <table class="cv-table">
            <thead><tr><th>Title</th><th>Company</th><th>Period</th><th>Details</th></tr></thead>
            <tbody><?php foreach ($exp as $x): ?>
                <tr><td><strong><?= e($x['title']) ?></strong></td><td><?= e($x['co']) ?></td><td><?= e($x['period']) ?></td><td><?= e($x['detail']) ?></td></tr>
            <?php endforeach; ?></tbody>
        </table>
        <?php endif; ?>
        <?php if ($edu): ?><h2>Education</h2>
        <table class="cv-table">
            <thead><tr><th>Degree</th><th>Institute</th><th>Year</th><th>Result</th></tr></thead>
            <tbody><?php foreach ($edu as $d): ?>
                <tr><td><strong><?= e($d['deg']) ?></strong></td><td><?= e($d['ins']) ?></td><td><?= e($d['year']) ?></td><td><?= e($d['res']) ?></td></tr>
            <?php endforeach; ?></tbody>
        </table>
        <?php endif; ?>
        <?php if ($skills): ?><h2>Skills</h2><div class="chips"><?php foreach ($skills as $s): ?><span><?= e($s) ?></span><?php endforeach; ?></div><?php endif; ?>
        <?php if ($langs): ?><h2>Languages</h2><p><?= e(implode(', ', $langs)) ?></p><?php endif; ?>
    </div>
<?php elseif ($template === 'bangla'): ?>
    <div class="tpl-bangla">
        <div class="b-title">জীবনবৃত্তান্ত</div>
        <div class="b-head">
            <div class="b-name">
                <h1><?= e($nameBn !== '' ? $nameBn : $fullName) ?></h1>
                <p><?= e($email) ?><?= $phone !== '' ? '<br>মোবাইল: ' . e($phone) : '' ?></p>
            </div>
            <?php if ($photoPath !== ''): ?><img class="bph" src="<?= e($photoPath) ?>" alt="ছবি"><?php endif; ?>
        </div>
        <h2>ব্যক্তিগত তথ্যাবলি</h2>
        <table class="b-info">
            <?php
            $brows = [
                'নাম' => ($nameBn !== '' ? $nameBn . ' (' . $fullName . ')' : $fullName),
                'পিতার নাম' => $fatherName, 'মাতার নাম' => $motherName,
                'জন্ম তারিখ' => $dob, 'ধর্ম' => $religion, 'লিঙ্গ' => $gender,
                'রক্তের গ্রুপ' => $blood, 'জাতীয়তা' => $nationality, 'এনআইডি নম্বর' => $nidNum,
                'মোবাইল' => $phone, 'ইমেইল' => $email,
                'বর্তমান ঠিকানা' => $address, 'স্থায়ী ঠিকানা' => $permAddr,
            ];
            foreach ($brows as $k => $v): if ($v === '') continue; ?>
            <tr><td class="k"><?= e($k) ?></td><td><?= e($v) ?></td></tr>
            <?php endforeach; ?>
        </table>
        <?php if ($summary !== ''): ?><h2>সারসংক্ষেপ</h2><p><?= nl2br(e($summary)) ?></p><?php endif; ?>
        <?php if ($edu): ?><h2>শিক্ষাগত যোগ্যতা</h2>
        <table class="b-info">
            <tr><td class="k">ডিগ্রি / পরীক্ষা</td><td class="k">প্রতিষ্ঠান</td><td class="k">সাল</td><td class="k">ফলাফল</td></tr>
            <?php foreach ($edu as $d): ?>
            <tr><td><strong><?= e($d['deg']) ?></strong></td><td><?= e($d['ins']) ?></td><td><?= e($d['year']) ?></td><td><?= e($d['res']) ?></td></tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
        <?php if ($exp): ?><h2>কাজের অভিজ্ঞতা</h2>
        <table class="b-info">
            <tr><td class="k">পদবি</td><td class="k">প্রতিষ্ঠান</td><td class="k">সময়কাল</td><td class="k">বিবরণ</td></tr>
            <?php foreach ($exp as $x): ?>
            <tr><td><strong><?= e($x['title']) ?></strong></td><td><?= e($x['co']) ?></td><td><?= e($x['period']) ?></td><td><?= e($x['detail']) ?></td></tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
        <?php if ($skills): ?><h2>দক্ষতা</h2><p><?= e(implode(', ', $skills)) ?></p><?php endif; ?>
        <?php if ($langs): ?><h2>ভাষা জ্ঞান</h2><p><?= e(implode(', ', $langs)) ?></p><?php endif; ?>
        <?php if ($refName !== '' || $refOrg !== '' || $refPhone !== ''): ?><h2>রেফারেন্স</h2>
        <p><?= e($refName) ?><?= $refOrg !== '' ? '<br>' . e($refOrg) : '' ?><?= $refPhone !== '' ? '<br>মোবাইল: ' . e($refPhone) : '' ?></p>
        <?php endif; ?>
        <?php if ($decl !== ''): ?><h2>অঙ্গীকারনামা</h2>
        <p>আমি অঙ্গীকার করছি যে উপরে প্রদত্ত সকল তথ্য সত্য ও সঠিক।</p>
        <div class="b-sign">
            <div><div class="line">&nbsp;</div>তারিখ</div>
            <div><div class="line">&nbsp;</div>স্বাক্ষর</div>
        </div>
        <?php endif; ?>
    </div>
<?php elseif ($template === 'elite'): ?>
    <div class="tpl-elite">
        <header><h1><?= e($fullName) ?></h1><div class="rule"></div>
        <div class="contact"><?= e($jobTitle) ?><?= $jobTitle !== '' ? '<br>' : '' ?><?= e($email) ?><?= $phone !== '' ? ' · ' . e($phone) : '' ?><?= $address !== '' ? '<br>' . e($address) : '' ?></div></header>
        <?php if ($summary !== ''): ?><h2>Profile</h2><p><?= nl2br(e($summary)) ?></p><?php endif; ?>
        <?php if ($exp): ?><h2>Experience</h2><?php foreach ($exp as $x): ?>
        <div class="item"><strong><?= e($x['title']) ?></strong><?= $x['co'] !== '' ? ', ' . e($x['co']) : '' ?><?= $x['period'] !== '' ? ' — ' . e($x['period']) : '' ?></div>
        <?php endforeach; endif; ?>
        <?php if ($edu): ?><h2>Education</h2><?php foreach ($edu as $d): ?>
        <div class="item"><strong><?= e($d['deg']) ?></strong>, <?= e($d['ins']) ?><?= $d['year'] !== '' ? ' — ' . e($d['year']) : '' ?></div>
        <?php endforeach; endif; ?>
        <?php if ($skills): ?><h2>Expertise</h2><ul><li><?= e(implode(' · ', $skills)) ?></li></ul><?php endif; ?>
    </div>
<?php elseif ($template === 'bold'): ?>
    <div class="tpl-bold">
        <div class="btop"><h1><?= e($fullName) ?></h1><?php if ($jobTitle !== ''): ?><div class="role"><?= e($jobTitle) ?></div><?php endif; ?></div>
        <div class="bsub"><?= e($email) ?><?= $phone !== '' ? ' · ' . e($phone) : '' ?><?= $address !== '' ? ' · ' . e($address) : '' ?></div>
        <div class="bbody">
            <?php if ($summary !== ''): ?><h2>Snapshot</h2><p><?= nl2br(e($summary)) ?></p><?php endif; ?>
            <?php if ($exp): ?><h2>Work</h2><?php foreach ($exp as $x): ?>
            <div class="item"><strong><?= e($x['title']) ?></strong><?= $x['co'] !== '' ? ' @ ' . e($x['co']) : '' ?><?= $x['period'] !== '' ? ' (' . e($x['period']) . ')' : '' ?><?php if ($x['detail'] !== ''): ?><br><?= e($x['detail']) ?><?php endif; ?></div>
            <?php endforeach; endif; ?>
            <?php if ($edu): ?><h2>Study</h2><?php foreach ($edu as $d): ?>
            <div class="item"><strong><?= e($d['deg']) ?></strong><?= $d['ins'] !== '' ? ' — ' . e($d['ins']) : '' ?><?= $d['year'] !== '' ? ' (' . e($d['year']) . ')' : '' ?></div>
            <?php endforeach; endif; ?>
            <?php if ($skills): ?><h2>Stack</h2><div class="chips"><?php foreach ($skills as $s): ?><span><?= e($s) ?></span><?php endforeach; ?></div><?php endif; ?>
        </div>
    </div>
<?php elseif ($template === 'standard'): ?>
    <div class="tpl-standard">
        <h1><?= e($fullName) ?></h1>
        <div class="contact"><?php if ($jobTitle !== ''): ?><?= e($jobTitle) ?><br><?php endif; ?><?= e($email) ?><?= $phone !== '' ? ' | ' . e($phone) : '' ?><?= $address !== '' ? '<br>' . e($address) : '' ?></div>
        <?php if ($summary !== ''): ?><h2>Career Objective</h2><p><?= nl2br(e($summary)) ?></p><?php endif; ?>
        <?php if ($exp): ?><h2>Employment History</h2><?php foreach ($exp as $x): ?>
        <div class="item"><strong><?= e($x['title']) ?></strong>, <?= e($x['co']) ?> (<?= e($x['period']) ?>)<br><?= e($x['detail']) ?></div>
        <?php endforeach; endif; ?>
        <?php if ($edu): ?><h2>Educational Background</h2><?php foreach ($edu as $d): ?>
        <div class="item"><strong><?= e($d['deg']) ?></strong>, <?= e($d['ins']) ?>, <?= e($d['year']) ?><?= $d['res'] !== '' ? ', ' . e($d['res']) : '' ?></div>
        <?php endforeach; endif; ?>
        <?php if ($skills): ?><h2>Skills</h2><p><?= e(implode(', ', $skills)) ?></p><?php endif; ?>
        <?php if ($langs): ?><h2>Languages</h2><p><?= e(implode(', ', $langs)) ?></p><?php endif; ?>
    </div>
<?php elseif ($template === 'dynamic'): ?>
    <div class="tpl-dynamic">
        <div class="main">
            <h1><?= e($fullName) ?></h1>
            <?php if ($jobTitle !== ''): ?><div class="role"><?= e($jobTitle) ?></div><?php endif; ?>
            <?php if ($summary !== ''): ?><h2>About</h2><p><?= nl2br(e($summary)) ?></p><?php endif; ?>
            <?php if ($exp): ?><h2>Experience</h2><?php foreach ($exp as $x): ?>
            <div class="item"><strong><?= e($x['title']) ?></strong><?= $x['co'] !== '' ? ' · ' . e($x['co']) : '' ?><br><span style="color:#0284c7"><?= e($x['period']) ?></span><?php if ($x['detail'] !== ''): ?><br><?= e($x['detail']) ?><?php endif; ?></div>
            <?php endforeach; endif; ?>
            <?php if ($edu): ?><h2>Education</h2><?php foreach ($edu as $d): ?>
            <div class="item"><strong><?= e($d['deg']) ?></strong><br><?= e($d['ins']) ?><?= $d['year'] !== '' ? ' · ' . e($d['year']) : '' ?></div>
            <?php endforeach; endif; ?>
        </div>
        <aside class="rail">
            <div class="rph"><?php if ($photoPath !== ''): ?><img src="<?= e($photoPath) ?>" alt="Photo"><?php else: ?><?= e($initials) ?><?php endif; ?></div>
            <h3>Contact</h3>
            <?php if ($email !== ''): ?><p><?= e($email) ?></p><?php endif; ?>
            <?php if ($phone !== ''): ?><p><?= e($phone) ?></p><?php endif; ?>
            <?php if ($address !== ''): ?><p><?= e($address) ?></p><?php endif; ?>
            <?php if ($skills): ?><h3>Skills</h3><ul><?php foreach ($skills as $s): ?><li><?= e($s) ?></li><?php endforeach; ?></ul><?php endif; ?>
            <?php if ($langs): ?><h3>Languages</h3><ul><?php foreach ($langs as $l): ?><li><?= e($l) ?></li><?php endforeach; ?></ul><?php endif; ?>
        </aside>
    </div>
<?php elseif ($template === 'word'): ?>
    <div class="tpl-word">
        <h1><?= e($fullName) ?></h1>
        <?php if ($jobTitle !== ''): ?><p><strong><?= e($jobTitle) ?></strong></p><?php endif; ?>
        <div class="contact"><?= e($email) ?><?= $phone !== '' ? ' | ' . e($phone) : '' ?><?= $address !== '' ? '<br>' . e($address) : '' ?></div>
        <?php if ($summary !== ''): ?><h2>Objective</h2><p><?= nl2br(e($summary)) ?></p><?php endif; ?>
        <?php if ($exp): ?><h2>Experience</h2>
        <table><?php foreach ($exp as $x): ?><tr><td style="width:32mm"><strong><?= e($x['period']) ?></strong></td><td><strong><?= e($x['title']) ?></strong>, <?= e($x['co']) ?><br><?= e($x['detail']) ?></td></tr><?php endforeach; ?></table><?php endif; ?>
        <?php if ($edu): ?><h2>Education</h2>
        <table><?php foreach ($edu as $d): ?><tr><td style="width:32mm"><strong><?= e($d['year']) ?></strong></td><td><strong><?= e($d['deg']) ?></strong>, <?= e($d['ins']) ?><?= $d['res'] !== '' ? ', ' . e($d['res']) : '' ?></td></tr><?php endforeach; ?></table><?php endif; ?>
        <?php if ($skills): ?><h2>Skills</h2><p><?= e(implode(', ', $skills)) ?></p><?php endif; ?>
        <?php if ($langs): ?><h2>Languages</h2><p><?= e(implode(', ', $langs)) ?></p><?php endif; ?>
    </div>
<?php elseif ($template === 'executive'): ?>
    <div class="tpl-executive">
        <div class="xband">
            <div class="xph"><?php if ($photoPath !== ''): ?><img src="<?= e($photoPath) ?>" alt="Photo"><?php else: ?><?= e($initials) ?><?php endif; ?></div>
            <div><h1><?= e($fullName) ?></h1><?php if ($jobTitle !== ''): ?><div class="role"><?= e($jobTitle) ?></div><?php endif; ?>
            <div class="contact"><?= e($email) ?><?= $phone !== '' ? ' · ' . e($phone) : '' ?><?= $address !== '' ? '<br>' . e($address) : '' ?></div></div>
        </div>
        <div class="xbody">
            <?php if ($summary !== ''): ?><h2>Profile</h2><p><?= nl2br(e($summary)) ?></p><?php endif; ?>
            <?php if ($exp): ?><h2>Experience</h2><?php foreach ($exp as $x): ?>
            <div class="item"><strong><?= e($x['title']) ?></strong><?= $x['co'] !== '' ? ' — ' . e($x['co']) : '' ?><?= $x['period'] !== '' ? ' (' . e($x['period']) . ')' : '' ?><?php if ($x['detail'] !== ''): ?><br><?= e($x['detail']) ?><?php endif; ?></div>
            <?php endforeach; endif; ?>
            <?php if ($edu): ?><h2>Education</h2><?php foreach ($edu as $d): ?>
            <div class="item"><strong><?= e($d['deg']) ?></strong><?= $d['ins'] !== '' ? ' — ' . e($d['ins']) : '' ?><?= $d['year'] !== '' ? ' (' . e($d['year']) . ')' : '' ?><?= $d['res'] !== '' ? ' — ' . e($d['res']) : '' ?></div>
            <?php endforeach; endif; ?>
            <?php if ($skills): ?><h2>Skills</h2><p><?= e(implode(', ', $skills)) ?></p><?php endif; ?>
            <?php if ($langs): ?><h2>Languages</h2><p><?= e(implode(', ', $langs)) ?></p><?php endif; ?>
        </div>
    </div>
<?php elseif ($template === 'creative'): ?>
    <div class="tpl-creative">
        <div class="chead">
            <div class="cph"><?php if ($photoPath !== ''): ?><img src="<?= e($photoPath) ?>" alt="Photo"><?php else: ?><?= e($initials) ?><?php endif; ?></div>
            <div><h1><?= e($fullName) ?></h1><?php if ($jobTitle !== ''): ?><div><span class="role"><?= e($jobTitle) ?></span></div><?php endif; ?>
            <p style="font-size:12.5px;color:#475569;margin-top:2mm"><?= e($email) ?><?= $phone !== '' ? ' · ' . e($phone) : '' ?></p></div>
        </div>
        <?php if ($summary !== ''): ?><h2>About Me</h2><p><?= nl2br(e($summary)) ?></p><?php endif; ?>
        <?php if ($exp): ?><h2>Experience</h2><?php foreach ($exp as $x): ?>
        <div class="item"><strong><?= e($x['title']) ?></strong><?= $x['co'] !== '' ? ' @ ' . e($x['co']) : '' ?><?= $x['period'] !== '' ? ' (' . e($x['period']) . ')' : '' ?><?php if ($x['detail'] !== ''): ?><br><?= e($x['detail']) ?><?php endif; ?></div>
        <?php endforeach; endif; ?>
        <?php if ($edu): ?><h2>Education</h2><?php foreach ($edu as $d): ?>
        <div class="item"><strong><?= e($d['deg']) ?></strong><?= $d['ins'] !== '' ? ' — ' . e($d['ins']) : '' ?><?= $d['year'] !== '' ? ' (' . e($d['year']) . ')' : '' ?></div>
        <?php endforeach; endif; ?>
        <?php if ($skills): ?><h2>Skills</h2><div class="chips"><?php foreach ($skills as $s): ?><span><?= e($s) ?></span><?php endforeach; ?></div><?php endif; ?>
        <?php if ($langs): ?><h2>Languages</h2><p><?= e(implode(', ', $langs)) ?></p><?php endif; ?>
        <?php if ($address !== ''): ?><h2>Address</h2><p><?= e($address) ?></p><?php endif; ?>
    </div>
<?php elseif ($template === 'corporate'): ?>
    <div class="tpl-corporate">
        <div class="cbar">
            <div><h1><?= e($fullName) ?></h1><?php if ($jobTitle !== ''): ?><div><?= e($jobTitle) ?></div><?php endif; ?></div>
            <div class="contact"><?= e($email) ?><?= $phone !== '' ? '<br>' . e($phone) : '' ?><?= $address !== '' ? '<br>' . e($address) : '' ?></div>
        </div>
        <div class="cbody">
            <?php if ($summary !== ''): ?><h2>Professional Summary</h2><p><?= nl2br(e($summary)) ?></p><?php endif; ?>
            <?php if ($exp): ?><h2>Employment History</h2>
            <table class="cv-table"><thead><tr><th>Position</th><th>Organization</th><th>Period</th></tr></thead><tbody>
            <?php foreach ($exp as $x): ?><tr><td><strong><?= e($x['title']) ?></strong><br><?= e($x['detail']) ?></td><td><?= e($x['co']) ?></td><td><?= e($x['period']) ?></td></tr><?php endforeach; ?>
            </tbody></table><?php endif; ?>
            <?php if ($edu): ?><h2>Academic Qualifications</h2>
            <table class="cv-table"><thead><tr><th>Degree</th><th>Institute</th><th>Year</th><th>Result</th></tr></thead><tbody>
            <?php foreach ($edu as $d): ?><tr><td><strong><?= e($d['deg']) ?></strong></td><td><?= e($d['ins']) ?></td><td><?= e($d['year']) ?></td><td><?= e($d['res']) ?></td></tr><?php endforeach; ?>
            </tbody></table><?php endif; ?>
            <?php if ($skills): ?><h2>Competencies</h2><p><?= e(implode(', ', $skills)) ?></p><?php endif; ?>
            <?php if ($langs): ?><h2>Languages</h2><p><?= e(implode(', ', $langs)) ?></p><?php endif; ?>
        </div>
    </div>
<?php elseif ($template === 'portfolio'): ?>
    <div class="tpl-portfolio">
        <div class="pph"><?php if ($photoPath !== ''): ?><img src="<?= e($photoPath) ?>" alt="Photo"><?php else: ?><?= e($initials) ?><?php endif; ?></div>
        <h1><?= e($fullName) ?></h1>
        <?php if ($jobTitle !== ''): ?><div class="role"><?= e($jobTitle) ?></div><?php endif; ?>
        <div class="contact"><?= e($email) ?><?= $phone !== '' ? ' · ' . e($phone) : '' ?><?= $address !== '' ? '<br>' . e($address) : '' ?></div>
        <?php if ($summary !== ''): ?><h2>About</h2><p><?= nl2br(e($summary)) ?></p><?php endif; ?>
        <?php if ($exp): ?><h2>Journey</h2><div class="tl"><?php foreach ($exp as $x): ?>
        <div class="item"><strong><?= e($x['title']) ?></strong><?= $x['co'] !== '' ? ' · ' . e($x['co']) : '' ?><br><span style="color:#7c3aed"><?= e($x['period']) ?></span><?php if ($x['detail'] !== ''): ?><br><?= e($x['detail']) ?><?php endif; ?></div>
        <?php endforeach; ?></div><?php endif; ?>
        <?php if ($edu): ?><h2>Education</h2><?php foreach ($edu as $d): ?>
        <div class="item"><strong><?= e($d['deg']) ?></strong><br><?= e($d['ins']) ?><?= $d['year'] !== '' ? ' · ' . e($d['year']) : '' ?></div>
        <?php endforeach; endif; ?>
        <?php if ($skills): ?><h2>Skills</h2><p><?= e(implode(', ', $skills)) ?></p><?php endif; ?>
    </div>
<?php elseif ($template === 'slate'): ?>
    <div class="tpl-slate">
        <header><h1><?= e($fullName) ?></h1><?php if ($jobTitle !== ''): ?><div class="role"><?= e($jobTitle) ?></div><?php endif; ?>
        <div class="contact"><?= e($email) ?><?= $phone !== '' ? ' · ' . e($phone) : '' ?><?= $address !== '' ? ' · ' . e($address) : '' ?></div></header>
        <?php if ($summary !== ''): ?><h2>Summary</h2><p><?= nl2br(e($summary)) ?></p><?php endif; ?>
        <?php if ($exp): ?><h2>Experience</h2><?php foreach ($exp as $x): ?>
        <div class="item"><strong><?= e($x['title']) ?></strong><?= $x['co'] !== '' ? ' — ' . e($x['co']) : '' ?><?= $x['period'] !== '' ? ' (' . e($x['period']) . ')' : '' ?><?php if ($x['detail'] !== ''): ?><br><?= e($x['detail']) ?><?php endif; ?></div>
        <?php endforeach; endif; ?>
        <?php if ($edu): ?><h2>Education</h2><?php foreach ($edu as $d): ?>
        <div class="item"><strong><?= e($d['deg']) ?></strong><?= $d['ins'] !== '' ? ' — ' . e($d['ins']) : '' ?><?= $d['year'] !== '' ? ' (' . e($d['year']) . ')' : '' ?><?= $d['res'] !== '' ? ' — ' . e($d['res']) : '' ?></div>
        <?php endforeach; endif; ?>
        <?php if ($skills): ?><h2>Skills</h2><p><?= e(implode(', ', $skills)) ?></p><?php endif; ?>
        <?php if ($langs): ?><h2>Languages</h2><p><?= e(implode(', ', $langs)) ?></p><?php endif; ?>
    </div>
<?php elseif ($template === 'insight'): ?>
    <div class="tpl-insight">
        <aside class="iside">
            <div class="iph"><?php if ($photoPath !== ''): ?><img src="<?= e($photoPath) ?>" alt="Photo"><?php else: ?><?= e($initials) ?><?php endif; ?></div>
            <h3>Personal Info</h3>
            <?php if ($email !== ''): ?><p><?= e($email) ?></p><?php endif; ?>
            <?php if ($phone !== ''): ?><p><?= e($phone) ?></p><?php endif; ?>
            <?php if ($address !== ''): ?><p><?= e($address) ?></p><?php endif; ?>
            <?php if ($skills): ?><h3>Skills</h3><ul><?php foreach ($skills as $s): ?><li><?= e($s) ?></li><?php endforeach; ?></ul><?php endif; ?>
            <?php if ($certs): ?><h3>Certifications</h3><ul><?php foreach ($certs as $c): ?><li><?= e($c['name']) ?><?= $c['year'] !== '' ? ' — ' . e($c['year']) : '' ?></li><?php endforeach; ?></ul><?php endif; ?>
            <?php if ($interests): ?><h3>Interests</h3><ul><?php foreach ($interests as $in): ?><li><?= e($in) ?></li><?php endforeach; ?></ul><?php endif; ?>
            <?php if ($langs): ?><h3>Languages</h3><ul><?php foreach ($langs as $l): ?><li><?= e($l) ?></li><?php endforeach; ?></ul><?php endif; ?>
        </aside>
        <div class="imain">
            <h1><?= e($fullName) ?></h1>
            <?php if ($jobTitle !== ''): ?><div class="role"><?= e($jobTitle) ?></div><?php endif; ?>
            <?php if ($summary !== ''): ?><h2>Summary</h2><p class="sum"><?= nl2br(e($summary)) ?></p><?php endif; ?>
            <?php if ($exp): ?><h2>Experience</h2><?php foreach ($exp as $x): ?>
            <div class="irow"><span><?= e($x['co']) ?></span><?php if ($x['loc'] !== ''): ?><span class="loc"><?= e($x['loc']) ?></span><?php endif; ?></div>
            <div class="irole"><span><?= e($x['title']) ?></span><?php if ($x['period'] !== ''): ?><span class="dt"><?= e($x['period']) ?></span><?php endif; ?></div>
            <?php if ($x['detail'] !== ''): ?><ul><li><?= e($x['detail']) ?></li></ul><?php endif; ?>
            <?php endforeach; endif; ?>
            <?php if ($edu): ?><h2>Education</h2><?php foreach ($edu as $d): ?>
            <div class="irow"><span><?= e($d['ins']) ?></span><?php if ($d['year'] !== ''): ?><span class="loc"><?= e($d['year']) ?></span><?php endif; ?></div>
            <div class="irole"><span><?= e($d['deg']) ?></span><?php if ($d['res'] !== ''): ?><span class="dt"><?= e($d['res']) ?></span><?php endif; ?></div>
            <?php endforeach; endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="tpl-modern">
        <aside class="side">
            <div class="ph"><?php if ($photoPath !== ''): ?><img src="<?= e($photoPath) ?>" alt="Photo"><?php else: ?><?= e($initials) ?><?php endif; ?></div>
            <h3>Contact</h3>
            <?php if ($email !== ''): ?><p><?= e($email) ?></p><?php endif; ?>
            <?php if ($phone !== ''): ?><p><?= e($phone) ?></p><?php endif; ?>
            <?php if ($address !== ''): ?><p>Present: <?= e($address) ?></p><?php endif; ?>
            <?php if ($permAddr !== ''): ?><p>Permanent: <?= e($permAddr) ?></p><?php endif; ?>
            <?php if ($dob !== ''): ?><p>DOB: <?= e($dob) ?></p><?php endif; ?>
            <?php if ($fatherName !== '' || $motherName !== ''): ?><h3>Parents</h3><?php endif; ?>
            <?php if ($fatherName !== ''): ?><p>Father: <?= e($fatherName) ?></p><?php endif; ?>
            <?php if ($motherName !== ''): ?><p>Mother: <?= e($motherName) ?></p><?php endif; ?>
            <?php if ($skills): ?><h3>Skills</h3><ul><?php foreach ($skills as $s): ?><li><?= e($s) ?></li><?php endforeach; ?></ul><?php endif; ?>
            <?php if ($langs): ?><h3>Languages</h3><ul><?php foreach ($langs as $l): ?><li><?= e($l) ?></li><?php endforeach; ?></ul><?php endif; ?>
        </aside>
        <div class="main">
            <h1><?= e($fullName) ?></h1>
            <?php if ($jobTitle !== ''): ?><div class="role"><?= e($jobTitle) ?></div><?php endif; ?>
            <?php if ($summary !== ''): ?><h2>Summary</h2><p><?= nl2br(e($summary)) ?></p><?php endif; ?>
            <?php if ($exp): ?><h2>Experience</h2><?php foreach ($exp as $x): ?>
                <div class="item"><div class="t"><?= e($x['title']) ?><?= $x['co'] !== '' ? ' · ' . e($x['co']) : '' ?></div><div class="s"><?= e($x['period']) ?></div><?php if ($x['detail'] !== ''): ?><p><?= e($x['detail']) ?></p><?php endif; ?></div>
            <?php endforeach; endif; ?>
            <?php if ($edu): ?><h2>Education</h2><?php foreach ($edu as $d): ?>
                <div class="item"><div class="t"><?= e($d['deg']) ?></div><div class="s"><?= e($d['ins']) ?><?= $d['year'] !== '' ? ' · ' . e($d['year']) : '' ?><?= $d['res'] !== '' ? ' · ' . e($d['res']) : '' ?></div></div>
            <?php endforeach; endif; ?>
        </div>
    </div>
<?php endif; ?>
</div>
</body>
</html>
