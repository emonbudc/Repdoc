<?php
declare(strict_types=1);

/**
 * Repdoc — prottoyon certificate pack.
 *
 * Bilingual (Bangla / English) A4 certificate designs brought in from the
 * prottoyon reference set. Portrait designs cover family, heir, succession,
 * holding tax and trade licence; every other certificate type renders on the
 * landscape design with the standard prottoyon paragraph.
 */

function prottoyon_pack_design(string $typeKey): string
{
    return match ($typeKey) {
        'family' => 'family',
        'warish' => 'warish',
        'succession' => 'succession',
        'holding-tax' => 'holding-tax',
        'trade', 'trade-license' => 'trade-license',
        default => 'landscape',
    };
}

function prottoyon_pack_en_relation(string $value): string
{
    $key = trim($value);
    if ($key === '') return '';
    $map = [
        'নিজ' => 'Self',
        'পিতা' => 'Father',
        'মাতা' => 'Mother',
        'পুত্র' => 'Son',
        'কন্যা' => 'Daughter',
        'স্বামী' => 'Husband',
        'স্ত্রী' => 'Wife',
        'ভাই' => 'Brother',
        'বোন' => 'Sister',
        'সহোদর ভাই' => 'Brother',
        'সহোদর বোন' => 'Sister',
        'বড় ভাই' => 'Elder brother',
        'ছোট ভাই' => 'Younger brother',
        'বড় বোন' => 'Elder sister',
        'ছোট বোন' => 'Younger sister',
        'দাদা' => 'Grandfather',
        'দাদী' => 'Grandmother',
        'নানা' => 'Maternal grandfather',
        'নানী' => 'Maternal grandmother',
        'নাতি' => 'Grandson',
        'নাতনি' => 'Granddaughter',
        'পুত্রবধূ' => 'Daughter-in-law',
        'জামাই' => 'Son-in-law',
        'ভাগিনা' => 'Nephew',
        'ভাগিনী' => 'Niece',
        'চাচা' => 'Uncle',
        'চাচী' => 'Aunt',
        'মামা' => 'Maternal uncle',
        'মামী' => 'Maternal aunt',
        'খালা' => 'Maternal aunt',
        'ফুফু' => 'Paternal aunt',
        'শ্বশুর' => 'Father-in-law',
        'শাশুড়ি' => 'Mother-in-law',
        'প্রতিবেশী' => 'Neighbour',
        'অন্যান্য' => 'Other',
    ];
    return $map[$key] ?? $key;
}

function prottoyon_pack_amount_number(string $value): float
{
    $value = str_replace(['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'], ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'], $value);
    $value = str_replace([',', '৳', ' '], '', $value);
    if (preg_match('/-?\d+(?:\.\d+)?/', $value, $m) !== 1) return 0.0;
    return (float) $m[0];
}

function prottoyon_pack_money(float $amount, bool $isEn): string
{
    $text = number_format($amount, 2, '.', '');
    return $isEn ? $text : prottoyon_bn_digits($text);
}

function prottoyon_pack_office(array $office, bool $isEn): array
{
    $nameBn = trim((string) ($office['office_name'] ?? ''));
    $nameEn = trim((string) ($office['office_name_en'] ?? ''));
    $upazilaBn = trim((string) ($office['upazila'] ?? ''));
    $upazilaEn = trim((string) ($office['upazila_en'] ?? ''));
    $districtBn = trim((string) ($office['district'] ?? ''));
    $districtEn = trim((string) ($office['district_en'] ?? ''));
    $chairBn = trim((string) ($office['chairman_name'] ?? ''));
    $chairEn = trim((string) ($office['chairman_name_en'] ?? ''));

    $name = $isEn && $nameEn !== '' ? $nameEn : $nameBn;
    $upazila = $isEn && $upazilaEn !== '' ? $upazilaEn : $upazilaBn;
    $district = $isEn && $districtEn !== '' ? $districtEn : $districtBn;
    $address = implode(', ', array_values(array_filter(
        [$upazila, $district, $isEn ? 'Bangladesh' : 'বাংলাদেশ'],
        static fn(string $v): bool => $v !== ''
    )));

    return [
        'name' => $name,
        'name_en' => $nameEn !== '' ? $nameEn : $nameBn,
        'address' => $address,
        'address_en' => implode(', ', array_values(array_filter(
            [$upazilaEn !== '' ? $upazilaEn : $upazilaBn, $districtEn !== '' ? $districtEn : $districtBn, 'Bangladesh'],
            static fn(string $v): bool => $v !== ''
        ))),
        'chairman' => $isEn && $chairEn !== '' ? $chairEn : $chairBn,
        'chairman_en' => $chairEn !== '' ? $chairEn : $chairBn,
        'role' => $isEn
            ? (($office['office_type'] ?? 'union') === 'union' ? 'Chairman' : 'Administrator')
            : (($office['office_type'] ?? 'union') === 'union' ? 'চেয়ারম্যান' : 'প্রশাসক'),
        'role_en' => ($office['office_type'] ?? 'union') === 'union' ? 'Chairman' : 'Administrator',
        'office_type' => (string) ($office['office_type'] ?? 'union'),
        'phone' => (string) ($office['phone'] ?? ''),
        'email' => (string) ($office['email'] ?? ''),
        'website' => (string) ($office['website'] ?? ''),
    ];
}

function prottoyon_pack_members(array $data, bool $isEn): array
{
    $out = [];
    foreach (($data['members'] ?? []) as $member) {
        if (!is_array($member)) continue;
        $name = trim((string) ($member['name'] ?? ''));
        $relation = trim((string) ($member['relation'] ?? ''));
        if ($name === '' && $relation === '') continue;

        $dobRaw = trim((string) ($member['dob'] ?? ''));
        if ($dobRaw !== '') {
            $dob = $isEn ? prottoyon_certificate_date_en($dobRaw) : prottoyon_format_date($dobRaw);
        } else {
            $dob = '';
        }
        $age = trim((string) ($member['age'] ?? ''));
        $nid = trim((string) ($member['nid'] ?? ''));

        $out[] = [
            'name' => $name,
            'name_en' => trim((string) ($member['name_en'] ?? '')) !== '' ? (string) $member['name_en'] : $name,
            'relation' => $isEn ? prottoyon_pack_en_relation($relation) : $relation,
            'dob' => $dob,
            'age' => $age !== '' ? ($isEn ? prottoyon_en_digits($age) : prottoyon_bn_digits($age)) : '',
            'nid' => $nid !== '' ? ($isEn ? prottoyon_en_digits($nid) : prottoyon_bn_digits($nid)) : '',
            'share' => trim((string) ($member['share'] ?? '')) !== ''
                ? ($isEn ? prottoyon_en_digits((string) $member['share']) : prottoyon_bn_digits((string) $member['share']))
                : '',
            'occupation' => trim((string) ($member['occupation'] ?? '')),
        ];
    }
    return $out;
}

function prottoyon_pack_fees(array $data, bool $isEn): array
{
    $out = [];
    foreach (($data['fees'] ?? []) as $fee) {
        if (!is_array($fee)) continue;
        $name = trim((string) ($fee['name'] ?? ''));
        $amount = trim((string) ($fee['amount'] ?? ''));
        if ($name === '' && $amount === '') continue;
        $out[] = [
            'name' => $name,
            'amount' => $amount !== '' ? ($isEn ? prottoyon_en_digits($amount) : prottoyon_bn_digits($amount)) : '',
        ];
    }
    return $out;
}

function prottoyon_pack_actions(string $backUrl, string $base, string $lang, bool $isEn, string $extra = ''): string
{
    $sep = str_contains($base, '?') ? '&' : '?';
    $bnUrl = $base !== '' ? $base . $sep . 'lang=bn' : $backUrl;
    $enUrl = $base !== '' ? $base . $sep . 'lang=en' : $backUrl;

    $html = '<div class="pack-actions">';
    $html .= '<button type="button" class="primary" onclick="window.print()">' . ($isEn ? 'Print / Save as PDF' : 'প্রিন্ট / PDF সংরক্ষণ') . '</button>';
    $html .= '<a href="' . e($backUrl) . '">' . ($isEn ? 'Back' : 'ফিরে যান') . '</a>';
    $html .= '<a href="' . e($bnUrl) . '"' . ($lang === 'bn' ? ' aria-current="page"' : '') . '>বাংলা</a>';
    $html .= '<a href="' . e($enUrl) . '"' . ($lang === 'en' ? ' aria-current="page"' : '') . '>English</a>';
    $html .= $extra;
    return $html . '</div>';
}

function prottoyon_render_pack(array $row, array $data, array $options = []): string
{
    $typeKey = (string) ($data['type'] ?? prottoyon_type_from_tool_key((string) ($row['tool_key'] ?? '')));
    $type = prottoyon_type($typeKey) ?? [];
    $office = prottoyon_office_for_row($row);
    $lang = (($options['lang'] ?? 'bn') === 'en') ? 'en' : 'bn';
    $isEn = $lang === 'en';
    $e = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

    $certNo = (string) ($row['cert_no'] ?? '');
    $issueRaw = (string) ($row['created_at'] ?? date('Y-m-d H:i:s'));
    $issueBn = prottoyon_format_date($issueRaw);
    $issueEn = prottoyon_certificate_date_en($issueRaw);

    $p = $data['personal'] ?? [];
    $x = $data['extra'] ?? [];
    $pick = static function (array $source, string $bnKey, string $enKey = ''): string {
        $bn = trim((string) ($source[$bnKey] ?? ''));
        $en = $enKey !== '' ? trim((string) ($source[$enKey] ?? '')) : '';
        return $bn !== '' ? $bn : $en;
    };
    $pickEn = static function (array $source, string $bnKey, string $enKey): string {
        $en = trim((string) ($source[$enKey] ?? ''));
        return $en !== '' ? $en : trim((string) ($source[$bnKey] ?? ''));
    };

    $nameBn = $pick($p, 'name_bn', 'name_en');
    $nameEn = $pickEn($p, 'name_bn', 'name_en');
    $fatherBn = $pick($p, 'father_bn', 'father_en');
    $fatherEn = $pickEn($p, 'father_bn', 'father_en');
    $motherBn = $pick($p, 'mother_bn', 'mother_en');
    $motherEn = $pickEn($p, 'mother_bn', 'mother_en');
    $spouseBn = $pick($p, 'spouse_name', 'spouse_name_en');
    $spouseEn = $pickEn($p, 'spouse_name', 'spouse_name_en');

    $present = prottoyon_certificate_address($data, 'present', $isEn);
    $permanent = prottoyon_certificate_address($data, 'permanent', $isEn);
    $presentBn = prottoyon_certificate_address($data, 'present', false);
    $presentEn = prottoyon_certificate_address($data, 'present', true);
    $permanentBn = prottoyon_certificate_address($data, 'permanent', false);
    $permanentEn = prottoyon_certificate_address($data, 'permanent', true);

    $officePack = prottoyon_pack_office($office, $isEn);
    $members = prottoyon_pack_members($data, $isEn);
    $fees = prottoyon_pack_fees($data, $isEn);

    $verifyUrl = prottoyon_verify_url($certNo);
    $qrUrl = repdoc_url('genaretor/prottoyon/qr.php?text=' . rawurlencode($verifyUrl));
    $base = (string) ($options['self_url'] ?? '');
    $backUrl = (string) ($options['back_url'] ?? repdoc_url('genaretor/prottoyon/applications.php'));
    $actionsHtml = prottoyon_pack_actions($backUrl, $base, $lang, $isEn, (string) ($options['actions'] ?? ''));
    $notice = (string) ($options['notice'] ?? '');
    $assetBase = repdoc_url('genaretor/prottoyon/assets/');

    $nid = trim((string) ($p['nid'] ?? ''));
    $brn = trim((string) ($p['brn'] ?? ''));
    $religion = trim((string) ($p['religion'] ?? ''));
    $occupation = trim((string) ($p['occupation'] ?? ''));
    $incomeAmount = trim((string) ($x['income_amount'] ?? ''));
    $incomeYear = trim((string) ($x['income_year'] ?? ''));
    $incomeWords = trim((string) ($x['income_words'] ?? ''));
    $deceased = trim((string) ($x['deceased_name'] ?? '')) ?: $nameBn;
    $deathRaw = trim((string) ($x['death_date'] ?? ''));
    $deathBn = $deathRaw !== '' ? prottoyon_format_date($deathRaw) : '';
    $deathEn = $deathRaw !== '' ? prottoyon_certificate_date_en($deathRaw) : '';
    $businessName = trim((string) ($x['business_name'] ?? ''));
    $businessNameEn = trim((string) ($x['business_name_en'] ?? '')) ?: $businessName;
    $businessType = trim((string) ($x['business_type'] ?? ''));
    $businessTypeEn = trim((string) ($x['business_type_en'] ?? '')) ?: $businessType;
    $businessAddress = trim((string) ($x['business_address'] ?? ''));
    $businessAddressEn = trim((string) ($x['business_address_en'] ?? '')) ?: $businessAddress;
    $businessNature = trim((string) ($x['business_nature'] ?? ''));
    $licenseYear = trim((string) ($x['license_year'] ?? ''));
    $fiscalYear = trim((string) ($x['fiscal_year'] ?? ''));
    $capital = trim((string) ($x['capital'] ?? ''));
    $expiryRaw = trim((string) ($x['expiry_date'] ?? ''));
    $expiryBn = $expiryRaw !== '' ? prottoyon_format_date($expiryRaw) : '';
    $expiryEn = $expiryRaw !== '' ? prottoyon_certificate_date_en($expiryRaw) : '';
    $guardian = trim((string) ($x['guardian_name'] ?? ''));
    $organization = trim((string) ($x['organization'] ?? ''));

    $total = 0.0;
    foreach (($data['fees'] ?? []) as $fee) {
        if (is_array($fee)) $total += prottoyon_pack_amount_number((string) ($fee['amount'] ?? ''));
    }

    $title = $isEn
        ? (string) ($type['title_en'] ?? prottoyon_type_title($typeKey, $typeKey))
        : (string) ($type['title'] ?? prottoyon_type_title($typeKey, $typeKey));

    $cert = [
        'type' => $typeKey,
        'lang' => $lang,
        'title' => $title,
        'office_name' => $officePack['name'],
        'office_name_en' => $officePack['name_en'],
        'office_address' => $officePack['address'],
        'office_address_en' => $officePack['address_en'],
        'certificate_no' => $isEn ? prottoyon_en_digits($certNo) : prottoyon_bn_digits($certNo),
        'certificate_no_en' => prottoyon_en_digits($certNo),
        'issue_date' => $isEn ? $issueEn : $issueBn,
        'issue_date_en' => $issueEn,
        'issue_time' => date('h:i A', strtotime($issueRaw) ?: time()),
        'name' => $isEn ? $nameEn : $nameBn,
        'name_en' => $nameEn,
        'father' => $isEn ? $fatherEn : $fatherBn,
        'father_en' => $fatherEn,
        'mother' => $isEn ? $motherEn : $motherBn,
        'mother_en' => $motherEn,
        'spouse' => $isEn ? $spouseEn : $spouseBn,
        'spouse_en' => $spouseEn,
        'nid' => $nid !== '' ? ($isEn ? prottoyon_en_digits($nid) : prottoyon_bn_digits($nid)) : '',
        'nid_en' => prottoyon_en_digits($nid),
        'brn' => $brn,
        'religion' => $religion,
        'occupation' => $occupation,
        'present_address' => $isEn ? $presentEn : $presentBn,
        'present_address_en' => $presentEn,
        'permanent_address' => $isEn ? $permanentEn : $permanentBn,
        'permanent_address_en' => $permanentEn,
        'income' => $incomeAmount !== '' ? ($isEn ? prottoyon_en_digits($incomeAmount) : prottoyon_bn_digits($incomeAmount)) : '',
        'income_year' => $incomeYear,
        'income_words' => $incomeWords,
        'deceased_name' => $deceased,
        'father_spouse_name' => $isEn ? $fatherEn : $fatherBn,
        'death_date' => $isEn ? $deathEn : $deathBn,
        'business_name' => $isEn ? $businessNameEn : $businessName,
        'business_name_en' => $businessNameEn,
        'business_type' => $isEn ? $businessTypeEn : $businessType,
        'business_type_en' => $businessTypeEn,
        'business_address' => $isEn ? $businessAddressEn : $businessAddress,
        'business_address_en' => $businessAddressEn,
        'business_nature' => $businessNature,
        'license_year' => $licenseYear,
        'fiscal_year' => $fiscalYear,
        'capital' => $capital,
        'expiry_date' => $isEn ? $expiryEn : $expiryBn,
        'expiry_date_en' => $expiryEn,
        'guardian' => $guardian,
        'organization' => $organization,
        'members' => $members,
        'fees' => $fees,
        'total_amount' => prottoyon_pack_money($total, $isEn),
        'total_amount_en' => prottoyon_pack_money($total, true),
        'signer_name' => $officePack['chairman'],
        'signer_name_en' => $officePack['chairman_en'],
        'signer_role' => $officePack['role'],
        'signer_role_en' => $officePack['role_en'],
        'signer_office' => $officePack['name'],
        'signer_office_en' => $officePack['name_en'],
        'signer_address' => $officePack['address'],
        'signer_address_en' => $officePack['address_en'],
        'verify_url' => $verifyUrl,
        'verify_url_en' => $verifyUrl,
        'verify_label' => $isEn ? 'Verify:' : 'যাচাইকরণ:',
        'qr_url' => $qrUrl,
        'present' => $isEn ? $presentEn : $presentBn,
        'permanent' => $isEn ? $permanentEn : $permanentBn,
        'present_bn' => $presentBn,
        'present_en' => $presentEn,
        'permanent_bn' => $permanentBn,
        'permanent_en' => $permanentEn,
    ];

    $c = $cert;
    $design = prottoyon_pack_design($typeKey);
    $file = __DIR__ . '/../genaretor/prottoyon/template/pack/' . $design . '.php';
    if (!is_file($file)) {
        $file = __DIR__ . '/../genaretor/prottoyon/template/pack/landscape.php';
    }

    ob_start();
    require $file;
    return (string) ob_get_clean();
}
