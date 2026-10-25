<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/SearchEngine.php';

$db = getDB();
$engine = new SearchEngine($db);

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

$sortBy = trim($_GET['sort_by'] ?? 'relevance');
$sortDir = strtolower(trim($_GET['sort_dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';
$page = max(1, (int) ($_GET['p'] ?? 1));
$perPage = max(5, min(100, (int) ($_GET['per_page'] ?? RESULTS_PER_PAGE)));
$matchMode = trim($_GET['match_mode'] ?? 'partial');
if (!in_array($matchMode, ['exact', 'partial'], true)) $matchMode = 'partial';

$result = $engine->searchAdvanced($query, $filters, $page, $perPage, $sortBy, $sortDir, $matchMode);

$out = [];
$out['total'] = $result['total'];
$out['page'] = $result['page'];
$out['pages'] = $result['pages'];
$out['per_page'] = $perPage;
$out['query'] = $query;
$out['lang'] = $result['lang'] ?? '';
$out['sort_by'] = $sortBy;
$out['sort_dir'] = $sortDir;
$out['match_mode'] = $matchMode;

$out['results'] = array_map(function ($row) use ($engine, $query) {
    return $engine->addHighlights([
        'id' => (int) $row['id'],
        'name' => $row['name'] ?? '',
        'name_en' => $row['name_en'] ?? '',
        'father_name' => $row['father_name'] ?? '',
        'father_name_en' => $row['father_name_en'] ?? '',
        'mother_name' => $row['mother_name'] ?? '',
        'mother_name_en' => $row['mother_name_en'] ?? '',
        'date_of_birth' => $row['date_of_birth'] ?? '',
        'age' => $row['age'] ?? $engine->calculateAge($row['date_of_birth'] ?? ''),
        'gender' => $row['gender'] ?? '',
        'gender_display' => $engine->getGenderDisplay($row['gender'] ?? ''),
        'status' => $row['status'] ?? $engine->determineStatus($row),
        'verified_at' => $row['verified_at'] ?? '',
        'birth_place' => $row['birth_place'] ?? '',
        'place_of_birth_en' => $row['place_of_birth_en'] ?? '',
        'permanent_address' => $row['permanent_address'] ?? '',
        'registration_number' => $row['registration_number'] ?? '',
        'registration_date' => $row['registration_date'] ?? '',
        'certificate_date' => $row['certificate_date'] ?? '',
        'ward' => $row['ward'] ?? '',
        'page_number' => $row['page_number'] ?? '',
        'serial' => $row['serial'] ?? '',
        'source_file' => $row['source_file'] ?? '',
        'photo' => $row['photo'] ?? '',
        'match_score' => $row['match_score'] ?? 0,
    ], $query);
}, $result['results']);

// ── Billing: one credit per executed search (price 0 = free, admins exempt) ──
if ($query !== '' || implode('', $filters) !== '') {
    $charge = charge_tool('birth_search');
    if (!$charge['ok']) {
        http_response_code(402);
        echo json_encode(fixUtf8([
            'ok'      => false,
            'error'   => 'insufficient_balance',
            'message' => $charge['message'],
            'balance' => $charge['balance'],
        ]), JSON_UNESCAPED_UNICODE);
        exit;
    }
    $out['charged'] = $charge['charged'];
    $out['balance'] = $charge['balance'];
}

echo json_encode(fixUtf8($out), JSON_UNESCAPED_UNICODE);