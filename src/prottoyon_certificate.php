<?php
declare(strict_types=1);

require_once __DIR__ . '/prottoyon.php';

function prottoyon_render_saved(array $row, array $options = []): string
{
    $data = prottoyon_decode_row($row);
    $typeKey = (string) ($data['type'] ?? prottoyon_type_from_tool_key((string) ($row['tool_key'] ?? '')));
    if ((int) ($data['v'] ?? 1) >= 2 && prottoyon_type($typeKey) !== null) {
        return prottoyon_render_certificate_v2($row, $data, $options);
    }
    return prottoyon_render_certificate_legacy($row, $data, $options);
}

function prottoyon_certificate_docs_html(array $data, array $row): string
{
    $items = [];
    $photo = (string) ($row['photo'] ?? '');
    if ($photo !== '') {
        $items[] = ['label' => 'ফটো', 'path' => $photo];
    }
    foreach (($data['documents'] ?? []) as $doc) {
        if (!is_array($doc) || empty($doc['path'])) continue;
        $items[] = ['label' => (string) ($doc['name'] ?? 'সংযুক্তি'), 'path' => (string) $doc['path']];
    }
    if ($items === []) return '';
    $html = '<div class="pv2-docs" aria-label="সংযুক্তি">';
    $html .= '<strong>সংযুক্তি:</strong> ';
    foreach ($items as $item) {
        $html .= '<a href="' . e(prottoyon_document_url($item['path'])) . '" target="_blank" rel="noopener">' . e($item['label']) . '</a> ';
    }
    return $html . '</div>';
}

function prottoyon_render_certificate_v2(array $row, array $data, array $options = []): string
{
    $typeKey = (string) $data['type'];
    $type = prottoyon_type($typeKey) ?? [];
    $office = prottoyon_office_for_row($row);
    $lang = (($options['lang'] ?? 'bn') === 'en') ? 'en' : 'bn';
    $isEn = $lang === 'en';
    $certNo = (string) ($row['cert_no'] ?? '');
    $issueDate = (string) ($row['created_at'] ?? '');
    $title = $isEn ? (string) ($type['title_en'] ?? $typeKey) : (string) ($type['title'] ?? $typeKey);
    $revoked = (($row['status'] ?? 'valid') === 'revoked');
    $verifyUrl = prottoyon_verify_url($certNo);
    $logo = prottoyon_office_logo_uri($office);
    $stamp = prottoyon_office_stamp_uri($office);
    $backUrl = (string) ($options['back_url'] ?? repdoc_url('genaretor/prottoyon/applications.php'));
    $extraActions = (string) ($options['actions'] ?? '');
    $docsHtml = prottoyon_certificate_docs_html($data, $row);
    $notice = (string) ($options['notice'] ?? '');

    ob_start();
    require __DIR__ . '/../genaretor/prottoyon/template/v2/default.php';
    $body = (string) ob_get_clean();

    $langUrlBn = '?id=' . (int) ($row['id'] ?? 0) . '&lang=bn';
    $langUrlEn = '?id=' . (int) ($row['id'] ?? 0) . '&lang=en';
    $base = (string) ($options['self_url'] ?? '');
    if ($base !== '') {
        $langUrlBn = $base . (str_contains($base, '?') ? '&' : '?') . 'lang=bn';
        $langUrlEn = $base . (str_contains($base, '?') ? '&' : '?') . 'lang=en';
    }

    $actions = '<button type="button" class="primary" onclick="window.print()">' . ($isEn ? 'Print / Save as PDF' : 'প্রিন্ট / PDF সংরক্ষণ') . '</button>';
    $actions .= '<a href="' . e($backUrl) . '">' . ($isEn ? 'Back' : 'ফিরে যান') . '</a>';
    $actions .= '<a href="' . e($langUrlBn) . '"' . ($lang === 'bn' ? ' aria-current="page"' : '') . '>বাংলা</a>';
    $actions .= '<a href="' . e($langUrlEn) . '"' . ($lang === 'en' ? ' aria-current="page"' : '') . '>English</a>';
    $actions .= $extraActions;

    $html = '<!DOCTYPE html><html lang="' . ($isEn ? 'en' : 'bn') . '"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
        . '<meta name="robots" content="noindex">'
        . '<title>' . e($title . ' — ' . prottoyon_value($data, 'personal.name_bn', '')) . '</title>'
        . '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
        . '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Noto+Serif+Bengali:wght@400;600;700;800&display=swap" rel="stylesheet">'
        . '<link rel="stylesheet" href="' . e(repdoc_url('src/assets/css/tokens.css?v=1')) . '">'
        . '<link rel="stylesheet" href="' . e(repdoc_url('genaretor/prottoyon/assets/css/certificate-v2.css?v=2')) . '">'
        . '</head><body class="pv2-page">'
        . ($notice !== '' ? '<div class="pv2-notice">' . $notice . '</div>' : '')
        . '<div class="pv2-actions">' . $actions . '</div>'
        . ($docsHtml !== '' ? '<div class="pv2-docs-wrap">' . $docsHtml . '</div>' : '')
        . $body
        . '<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>'
        . '<script>(function(){try{var el=document.getElementById("pv2Qr");if(el&&window.QRCode){new QRCode(el,{text:' . json_encode($verifyUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ',width:112,height:112,correctLevel:QRCode.CorrectLevel.M});}}catch(e){}})();</script>'
        . '</body></html>';
    return $html;
}

function prottoyon_render_certificate_legacy(array $row, array $data, array $options = []): string
{
    $typeKey = (string) ($data['type'] ?? prottoyon_type_from_tool_key((string) ($row['tool_key'] ?? '')));
    $office = prottoyon_office_for_row($row);
    $legacy = is_array($data['legacy'] ?? null) ? $data['legacy'] : [];

    $user = new stdClass();
    $defaults = [
        'name_bn' => 'name_bn', 'father_name' => 'father_bn', 'mother_name' => 'mother_bn',
        'dob' => 'dob', 'gender' => 'gender', 'religion' => 'religion', 'nid' => 'nid',
        'birth_reg_no' => 'brn', 'mobile' => 'mobile', 'village' => 'village_bn', 'ward_no' => 'ward',
        'post_office' => 'post_office_bn', 'upazila_name' => 'upazila_bn', 'district_name' => 'district_bn',
        'occupation' => 'occupation',
    ];
    foreach ($defaults as $prop => $path) {
        $user->$prop = prottoyon_value($data, 'present.' . $path, prottoyon_value($data, 'personal.' . $path, ''));
    }
    foreach ($legacy as $key => $value) {
        if (is_scalar($value) && !isset($user->$key)) $user->$key = (string) $value;
    }
    foreach (($data['personal'] ?? []) as $key => $value) {
        if (is_scalar($value) && !isset($user->$key)) $user->$key = (string) $value;
    }

    $union = new stdClass();
    $union->name_bn = ($office['office_name'] ?? '') !== '' ? (string) $office['office_name'] : 'ইউনিয়ন পরিষদ';
    $union->upazila_name = ($office['upazila'] ?? '') !== '' ? (string) $office['upazila'] : '[উপজেলার নাম]';
    $union->district_name = ($office['district'] ?? '') !== '' ? (string) $office['district'] : '[জেলার নাম]';
    $union->office_type = (string) ($office['office_type'] ?? 'union');
    $union->logo_url = repdoc_url('genaretor/prottoyon/assets/images/gov_logo.png');

    $refNo = (string) ($row['cert_no'] ?? '');
    $certificate = new stdClass();
    $certificate->certificate_no = $refNo;
    $certificate->issue_date = substr((string) ($row['created_at'] ?? date('Y-m-d')), 0, 10);
    $certType = new stdClass();
    $certType->name_bn = prottoyon_type_title($typeKey);
    $chairman = ($office['chairman_name'] ?? '') !== '' ? (string) $office['chairman_name'] : 'চেয়ারম্যানের নাম';
    $revoked = (($row['status'] ?? 'valid') === 'revoked');
    $baseHref = repdoc_url('genaretor/prottoyon/');
    if (substr($baseHref, -1) !== '/') $baseHref .= '/';
    $backUrl = (string) ($options['back_url'] ?? repdoc_url('genaretor/prottoyon/applications.php'));
    $actionsHtml = '<button type="button" class="primary" onclick="window.print()">প্রিন্ট / PDF সংরক্ষণ</button>'
        . '<a href="' . e($backUrl) . '">ফিরে যান</a>';
    $legacyNotice = (string) ($options['notice'] ?? '');

    $fragFile = __DIR__ . '/../genaretor/prottoyon/template/' . $typeKey . '.php';
    if (!is_file($fragFile)) $fragFile = __DIR__ . '/../genaretor/prottoyon/template/default.php';
    $docTitle = $certType->name_bn . ' - ' . $user->name_bn;

    ob_start();
    require __DIR__ . '/../genaretor/prottoyon/render-legacy.php';
    return (string) ob_get_clean();
}

function prottoyon_certificate_address(array $data, string $which, bool $isEn): string
{
    $row = $data[$which] ?? [];
    $ward = trim((string) ($row['ward'] ?? ''));
    $holding = trim((string) ($row['holding_no'] ?? ''));
    $pairs = $isEn
        ? [['village_en', 'village_bn'], ['rbs_en', 'rbs_bn'], ['post_office_en', 'post_office_bn'], ['union_en', 'union_bn'], ['upazila_en', 'upazila_bn'], ['district_en', 'district_bn'], ['division_en', 'division_bn']]
        : [['village_bn'], ['rbs_bn'], ['post_office_bn'], ['union_bn'], ['upazila_bn'], ['district_bn'], ['division_bn']];
    $parts = [];
    if ($ward !== '') $parts[] = ($isEn ? 'Ward ' : 'ওয়ার্ড ') . ($isEn ? prottoyon_en_digits($ward) : prottoyon_bn_digits($ward));
    $afterHold = [];
    foreach ($pairs as $i => $keys) {
        $value = '';
        foreach ($keys as $key) {
            $candidate = trim((string) ($row[$key] ?? ''));
            if ($candidate !== '') { $value = $isEn ? prottoyon_en_digits($candidate) : $candidate; break; }
        }
        if ($value === '') continue;
        if ($i <= 1) $parts[] = $value;
        else $afterHold[] = $value;
    }
    if ($holding !== '') {
        $parts[] = ($isEn ? 'Holding ' : 'হোল্ডিং নং ') . ($isEn ? prottoyon_en_digits($holding) : prottoyon_bn_digits($holding));
    }
    $parts = array_merge($parts, $afterHold);
    if ($parts === []) {
        $fallback = trim((string) ($row[$isEn ? 'address_en' : 'address_bn'] ?? ''));
        if ($fallback !== '') return $fallback;
    }
    return implode(', ', $parts);
}

function prottoyon_certificate_date_en(string $date): string
{
    $ts = strtotime($date);
    return $ts === false ? $date : date('d F Y', $ts);
}

function prottoyon_certificate_paragraph(array $data, string $typeKey, bool $isEn = false): string
{
    $p = $data['personal'] ?? [];
    $x = $data['extra'] ?? [];
    $name = $isEn ? (trim((string) ($p['name_en'] ?? '')) ?: (string) ($p['name_bn'] ?? '')) : (string) ($p['name_bn'] ?? '');
    $father = $isEn ? ((string) ($p['father_en'] ?? '') ?: (string) ($p['father_bn'] ?? '')) : (string) ($p['father_bn'] ?? '');
    $mother = $isEn ? ((string) ($p['mother_en'] ?? '') ?: (string) ($p['mother_bn'] ?? '')) : (string) ($p['mother_bn'] ?? '');
    $address = prottoyon_certificate_address($data, 'present', $isEn);
    $date = static fn(string $value): string => $isEn ? prottoyon_certificate_date_en($value) : prottoyon_format_date($value);
    $g = static fn(string $key): string => trim((string) ($x[$key] ?? ''));
    $d = static fn(string $key): string => $date($g($key));

    if ($isEn) {
        $base = 'This is to certify that ' . $name . ', son/daughter of ' . ($father !== '' ? $father : '—') . ' and ' . ($mother !== '' ? $mother : '—') . ', a resident of ' . ($address !== '' ? $address : '—') . ', is known to me. ';
        switch ($typeKey) {
            case 'nagorik': return $base . 'He/She is a citizen of Bangladesh by ' . ($g('citizenship') !== '' ? strtolower($g('citizenship')) : 'birth') . '.';
            case 'trade': return $base . 'He/She operates the business "' . $g('business_name') . '" (' . $g('business_type') . ') at ' . $g('business_address') . '. The licence year is ' . $g('license_year') . ' and the fiscal year is ' . $g('fiscal_year') . '.';
            case 'warish': return 'This is to certify that the following persons are the heirs of the late ' . $g('deceased_name') . ', who died on ' . $d('death_date') . '. ' . $base;
            case 'family': return $base . 'The members of his/her family are listed below.';
            case 'death': return 'This is to certify that ' . $g('deceased_name') . ' died on ' . $d('death_date') . ' at ' . $g('death_place') . '. ' . $base;
            case 'obibahito': return $base . 'He/She is unmarried.';
            case 'bibahito': return $base . 'He/She married ' . $g('spouse_name') . ' on ' . $d('marriage_date') . '.';
            case 'ekoinam': return $base . 'He/She is also known as ' . $g('previous_name') . ' and is the same person.';
            case 'punobibaho': return $base . 'He/She remarried on ' . $d('marriage_date') . '.';
            case 'protibondi': return $base . 'He/She has ' . $g('disability_type') . ' disability' . ($g('disability_percent') !== '' ? ' (' . $g('disability_percent') . '%)' : '') . '.';
            case 'prottyon': return 'I, ' . ($g('certified_by') !== '' ? $g('certified_by') : $name) . ', certify that ' . $base . ($g('certificate_text') !== '' ? ' ' . $g('certificate_text') : '');
            case 'vumihin': return $base . 'He/She is landless. ' . $g('land_description');
            case 'new-voter': return $base . 'He/She is a new voter in ' . $g('voter_area') . '.';
            case 'voter': return $base . 'He/She is a voter in ' . $g('voter_area') . ($g('voter_no') !== '' ? ', voter no. ' . $g('voter_no') : '') . '.';
            case 'character': return $base . 'His/Her character and social conduct are satisfactory. ' . $g('character_note');
            case 'yearlyincome': return $base . 'His/Her annual income is BDT ' . $g('income_amount') . ' for the year ' . $g('income_year') . '.';
            case 'onapotti': return $base . 'I have no objection to the matter described herein. ' . $g('land_description');
            case 'onumoti': return $base . 'Permission is granted for: ' . $g('consent_subject') . '. ' . $g('consent_details');
            case 'rastakhonon': return $base . 'The road named ' . $g('road_name') . ' has been excavated/land donated for it. ' . $g('road_length');
            case 'nodibanga': return $base . 'He/She is affected by river erosion of the ' . $g('river_name') . '. ' . $g('bank_description');
            case 'sonaton': return $base . 'He/She follows the ' . $g('religion_name') . ' faith and worships at ' . $g('religious_place') . '.';
            case 'anumanik-boyosh-protyon': return $base . 'His/Her estimated age is ' . $g('estimated_age') . ' years. ' . $g('age_reason');
            case 'protyonpotro-onnanno': return $base . 'Subject: ' . $g('subject') . '. ' . $g('details');
        }
        return $base . 'This certificate is issued on his/her request for the stated purpose.';
    }

    $base = 'এই মর্মে প্রত্যয়ন করা যাইতেছে যে, ' . ($name !== '' ? $name : '—') . ', পিতা: ' . ($father !== '' ? $father : '—') . ', মাতা: ' . ($mother !== '' ? $mother : '—') . ', বর্তমান ঠিকানা: ' . ($address !== '' ? $address : '—') . '। তিনি আমার জানামতে একজন সৎ ও সুপরিচিত ব্যক্তি। ';
    switch ($typeKey) {
        case 'nagorik': return $base . 'তিনি ' . ($g('citizenship') !== '' ? $g('citizenship') : 'জন্মসূত্রে') . ' বাংলাদেশের নাগরিক।';
        case 'trade': return $base . 'তিনি “‘' . $g('business_name') . '”' . ' নামে ' . $g('business_type') . ' ব্যবসা পরিচালনা করেন। ব্যবসার ঠিকানা: ' . $g('business_address') . '। লাইসেন্সের বছর: ' . $g('license_year') . ', অর্থবছর: ' . $g('fiscal_year') . '।';
        case 'warish': return 'এই মর্মে প্রত্যয়ন করা যাইতেছে যে, মৃত ' . $g('deceased_name') . ' ' . $d('death_date') . ' তারিখে মৃত্যুবরণ করেন। তাঁর ওয়ারিশগণ নিম্নে তালিকাভুক্ত। ' . $base;
        case 'family': return $base . 'তাঁর পরিবারের সদস্যদের তালিকা নিম্নে দেওয়া হলো।';
        case 'death': return 'এই মর্মে প্রত্যয়ন করা যাইতেছে যে, ' . $g('deceased_name') . ' ' . $d('death_date') . ' তারিখে ' . $g('death_place') . '-এ মৃত্যুবরণ করেন। ' . $base;
        case 'obibahito': return $base . 'তিনি এখন পর্যন্ত অবিবাহিত।';
        case 'bibahito': return $base . 'তিনি ' . $g('spouse_name') . '-এর সাথে ' . $d('marriage_date') . ' তারিখে বিবাহবন্ধনে আবদ্ধ হন।';
        case 'ekoinam': return $base . 'তিনি “‘' . $g('previous_name') . '”' . ' নামেও পরিচিত এবং উভয় নাম একই ব্যক্তিকে নির্দেশ করে।';
        case 'punobibaho': return $base . 'তিনি ' . $d('marriage_date') . ' তারিখে পুনর্বিবাহ করেন।';
        case 'protibondi': return $base . 'তিনি ' . $g('disability_type') . ' ধরনের প্রতিবন্ধিতায় আক্রান্ত' . ($g('disability_percent') !== '' ? ' (' . prottoyon_bn_digits($g('disability_percent')) . '%)' : '') . '।';
        case 'prottyon': return 'আমি, ' . ($g('certified_by') !== '' ? $g('certified_by') : $name) . ', এই মর্মে প্রত্যয়ন করছি যে, ' . $base . ($g('certificate_text') !== '' ? ' ' . $g('certificate_text') : '');
        case 'vumihin': return $base . 'তিনি ভূমিহীন। ' . $g('land_description');
        case 'new-voter': return $base . 'তিনি ' . $g('voter_area') . ' এলাকায় নতুন ভোটার হিসেবে অন্তর্ভুক্ত হয়েছেন।';
        case 'voter': return $base . 'তিনি ' . $g('voter_area') . ' এলাকার ভোটার' . ($g('voter_no') !== '' ? ', ভোটার নম্বর: ' . $g('voter_no') : '') . '।';
        case 'character': return $base . 'তাঁর চারিত্রিক ও সামাজিক আচরণ সন্তোষজনক। ' . $g('character_note');
        case 'yearlyincome': return $base . 'তাঁর ' . $g('income_year') . ' সালের বার্ষিক আয় ' . prottoyon_bn_digits($g('income_amount')) . ' টাকা।';
        case 'onapotti': return $base . 'উক্ত বিষয়ে আমার কোনো আপত্তি নেই। ' . $g('land_description');
        case 'onumoti': return $base . 'নিম্নোক্ত বিষয়ের জন্য অনুমতি প্রদান করা হইল: ' . $g('consent_subject') . '। ' . $g('consent_details');
        case 'rastakhonon': return $base . '“‘' . $g('road_name') . '”' . ' রাস্তা খনন/জমি প্রদান করা হইয়াছে। ' . $g('road_length');
        case 'nodibanga': return $base . 'তিনি ' . $g('river_name') . ' নদীর ভাঙনে ক্ষতিগ্রস্ত হয়েছেন। ' . $g('bank_description');
        case 'sonaton': return $base . 'তিনি ' . $g('religion_name') . ' ধর্মাবলম্বী এবং ' . $g('religious_place') . '-এ উপাসনা করেন।';
        case 'anumanik-boyosh-protyon': return $base . 'তাঁর আনুমানিক বয়স ' . prottoyon_bn_digits($g('estimated_age')) . ' বছর। ' . $g('age_reason');
        case 'protyonpotro-onnanno': return $base . 'বিষয়: ' . $g('subject') . '। ' . $g('details');
    }
    return $base . 'তাঁর আবেদনের ভিত্তিতে এই প্রত্যয়নপত্র প্রদান করা হইল।';
}
