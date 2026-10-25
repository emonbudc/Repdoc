<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/SearchEngine.php';

$db = getDB();
$engine = new SearchEngine($db);

$format = strtolower(trim($_GET['format'] ?? 'csv'));
$idsParam = trim($_GET['ids'] ?? '');

$query = trim($_GET['q'] ?? '');
$filters = [
    'name' => trim($_GET['name'] ?? ''),
    'father_name' => trim($_GET['father'] ?? ''),
    'mother_name' => trim($_GET['mother'] ?? ''),
    'dob' => trim($_GET['dob'] ?? ''),
    'gender' => trim($_GET['gender'] ?? ''),
    'reg_num' => trim($_GET['reg_num'] ?? ''),
    'ward' => trim($_GET['ward'] ?? ''),
    'source_file' => trim($_GET['source_file'] ?? ''),
    'status' => trim($_GET['status'] ?? ''),
];

$records = [];

if ($idsParam !== '') {
    $ids = array_filter(array_map('intval', explode(',', $idsParam)));
    if (!empty($ids)) {
        $inList = implode(',', $ids);
        $res = $db->query("SELECT * FROM records WHERE id IN ($inList)");
        while ($row = $res->fetch()) {
            $row['status'] = $engine->determineStatus($row);
            $row['age'] = $engine->calculateAge($row['date_of_birth'] ?? '');
            $row['gender_display'] = $engine->getGenderDisplay($row['gender'] ?? '');
            $records[] = $row;
        }
    }
} else {
    // Export based on search parameters (up to 1000 records)
    $res = $engine->searchAdvanced($query, $filters, 1, 1000);
    $records = $res['results'];
}

$filename = 'bdris_birth_records_' . date('Ymd_His');

// ── Billing: one credit per export that actually returns records (0 = free) ──
if (count($records) > 0) {
    $charge = charge_tool('birth_export');
    if (!$charge['ok']) {
        http_response_code(402);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok'      => false,
            'error'   => 'insufficient_balance',
            'message' => $charge['message'],
            'balance' => $charge['balance'],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if ($format === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.json"');
    echo json_encode(['count' => count($records), 'exported_at' => date('Y-m-d H:i:s'), 'records' => $records], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Default: CSV format with UTF-8 BOM for Excel compatibility with Bangla
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '.csv"');

$output = fopen('php://output', 'w');
// Write UTF-8 BOM for Bengali characters in Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

fputcsv($output, [
    'ID',
    'নাম (Name)',
    'জন্ম নিবন্ধন নম্বর (BRN)',
    'লিঙ্গ (Gender)',
    'জন্ম তারিখ (Date of Birth)',
    'বয়স (Age)',
    'পিতার নাম (Father)',
    'মাতার নাম (Mother)',
    'জন্মস্থান (Birth Place)',
    'স্থায়ী ঠিকানা (Address)',
    'ওয়ার্ড (Ward)',
    'স্ট্যাটাস (Status)',
    'নিবন্ধন তারিখ (Reg Date)',
    'রেজিস্টার বই (Register Book)',
    'পৃষ্ঠা (Page)',
    'ক্রমিক (Serial)'
], ',', '"', '\\');

foreach ($records as $r) {
    fputcsv($output, [
        $r['id'] ?? '',
        $r['name'] ?? '',
        $r['registration_number'] ?? '',
        $engine->getGenderDisplay($r['gender'] ?? ''),
        $r['date_of_birth'] ?? '',
        $r['age'] ?? $engine->calculateAge($r['date_of_birth'] ?? ''),
        $r['father_name'] ?? '',
        $r['mother_name'] ?? '',
        $r['birth_place'] ?? '',
        $r['permanent_address'] ?? '',
        $r['ward'] ?? '',
        strtoupper($r['status'] ?? 'VERIFIED'),
        $r['registration_date'] ?? '',
        $r['source_file'] ?? '',
        $r['page_number'] ?? '',
        $r['serial'] ?? ''
    ], ',', '"', '\\');
}

fclose($output);
exit;
