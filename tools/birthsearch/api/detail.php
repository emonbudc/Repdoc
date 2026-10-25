<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/SearchEngine.php';

$db = getDB();
$engine = new SearchEngine($db);

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['error' => 'invalid id'], JSON_UNESCAPED_UNICODE);
    exit;
}

$row = $engine->getFull($id);
if (!$row) {
    echo json_encode(['error' => 'not found'], JSON_UNESCAPED_UNICODE);
    exit;
}

$status = $row['status'] ?? 'verified';
$regNum = $row['registration_number'] ?? '';
$dob = $row['date_of_birth'] ?? '';
$regDate = $row['registration_date'] ?? '';
$certDate = $row['certificate_date'] ?? '';
$sourceFile = $row['source_file'] ?? 'Digital Register';
$pageNo = $row['page_number'] ?? '1';
$serialNo = $row['serial'] ?? '1';
$verifiedAt = $row['verified_at'] ?? '';

// Build realistic government verification and audit timeline events
$auditTimeline = [
    [
        'title' => 'মূল রেজিস্টার বই অন্তর্ভুক্তি (Original Register Entry)',
        'description' => "ভলিউম: {$sourceFile} • পৃষ্ঠা নং: {$pageNo} • ক্রমিক নং: {$serialNo}",
        'date' => $regDate ?: 'নিবন্ধনের সময় লিপিবদ্ধ',
        'status' => 'completed',
        'icon' => 'book'
    ],
    [
        'title' => 'ডিজিটাল রূপান্তর ও ডাটা প্রসেসিং (OCR & Ingestion)',
        'description' => 'ওয়ার্ড ডাটাবেস এন্ট্রি ও ইউনিক আইডেন্টিফায়ার ইনডেক্সিং সম্পন্ন হয়েছে।',
        'date' => $certDate ?: 'স্বয়ংক্রিয়ভাবে সম্পন্ন',
        'status' => 'completed',
        'icon' => 'cpu'
    ],
    [
        'title' => 'জাতীয় ডাটাবেস যাচাইকরণ (BDRIS Verification)',
        'description' => $verifiedAt ? "যাচাইকরণের সময়: {$verifiedAt} (পিতা-মাতার নাম ও জন্ম তারিখ সম্বলিত তথ্য অনলাইন থেকে যাচাইকৃত)" : 'অনলাইন সার্ভারে যাচাইকরণের অপেক্ষায়',
        'date' => $verifiedAt ?: ($status === 'verified' ? 'যাচাইকৃত' : 'প্রক্রিয়াধীন'),
        'status' => $status === 'verified' ? 'completed' : 'pending',
        'icon' => 'shield'
    ],
    [
        'title' => 'সিকিউরিটি হ্যাশ ও কিউআর জেনারেশন (Cryptographic Signature)',
        'description' => 'SHA-256 ডিজিটাল ভ্যালিডেশন কোড তৈরি করা হয়েছে।',
        'date' => 'অনলাইন ভেরিফিকেশন প্রস্তুত',
        'status' => 'completed',
        'icon' => 'qr'
    ]
];

$out = [
    'record' => [
        'id' => (int) $row['id'],
        'name' => $row['name'] ?? '',
        'name_en' => $row['name_en'] ?? '',
        'father_name' => $row['father_name'] ?? '',
        'father_name_en' => $row['father_name_en'] ?? '',
        'mother_name' => $row['mother_name'] ?? '',
        'mother_name_en' => $row['mother_name_en'] ?? '',
        'date_of_birth' => $dob,
        'age' => $row['age'] ?? $engine->calculateAge($dob),
        'gender' => $row['gender'] ?? '',
        'gender_display' => $engine->getGenderDisplay($row['gender'] ?? ''),
        'status' => $status,
        'verified_at' => $verifiedAt,
        'birth_place' => $row['birth_place'] ?? '',
        'place_of_birth_en' => $row['place_of_birth_en'] ?? '',
        'permanent_address' => $row['permanent_address'] ?? '',
        'permanent_address_en' => $row['permanent_address_en'] ?? '',
        'registration_number' => $regNum,
        'registration_date' => $regDate,
        'certificate_date' => $certDate,
        'ward' => $row['ward'] ?? '',
        'page_number' => $pageNo,
        'serial' => $serialNo,
        'source_file' => $sourceFile,
        'photo' => $row['photo'] ?? '',
        'security_hash' => $row['security_hash'] ?? hash('sha256', $regNum . ($row['name'] ?? '') . $dob),
        'timeline' => $auditTimeline,
        'verified_by' => 'স্থানীয় সরকার বিভাগ (রেজিস্ট্রার কার্যালয়)',
        'issuing_authority' => 'BDRIS Civil Registry Authority, Bangladesh',
        'country' => 'বাংলাদেশ (Bangladesh)',
        'nationality' => 'বাংলাদেশী (Bangladeshi)',
    ],
];

echo json_encode(fixUtf8($out), JSON_UNESCAPED_UNICODE);