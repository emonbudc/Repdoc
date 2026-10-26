<?php
declare(strict_types=1);

/**
 * Repdoc — prottoyon v2 domain helpers.
 *
 * The 23 current certificate types live in prottoyon_registry(). The 41
 * pre-v2 types remain readable through prottoyon_legacy_types(); their old
 * templates are never rewritten.
 */

function prottoyon_registry(): array
{
    $f = static function (string $k, string $label, string $type = 'text', bool $required = false, array $options = []): array {
        return ['k' => $k, 'label' => $label, 'type' => $type, 'required' => $required, 'options' => $options];
    };

    return [
        'nagorik' => [
            'title' => 'নাগরিকত্ব প্রত্যয়নপত্র',
            'title_en' => 'Citizenship Certificate',
            'description' => 'জাতীয়তা ও নাগরিকত্বের প্রত্যয়ন।',
            'group' => 'পরিচয় ও নাগরিকত্ব',
            'icon' => 'id',
            'fields' => [
                $f('citizenship', 'নাগরিকত্বের ধরন', 'select', true, ['জন্মসূত্রে' => 'জন্মসূত্রে', 'বিবাহসূত্রে' => 'বিবাহসূত্রে', 'অন্য' => 'অন্য']),
                $f('resident_since', 'বসবাসের সূচনা', 'text'),
            ],
        ],
        'new-voter' => [
            'title' => 'নতুন ভোটার প্রত্যয়নপত্র',
            'title_en' => 'New Voter Certificate',
            'description' => 'নতুন ভোটার হিসেবে অন্তর্ভুক্তির প্রত্যয়ন।',
            'group' => 'পরিচয় ও নাগরিকত্ব',
            'icon' => 'vote',
            'fields' => [
                $f('voter_area', 'ভোটার এলাকা', 'text', true),
                $f('previous_voter_no', 'পূর্বের ভোটার নম্বর', 'text'),
            ],
        ],
        'voter' => [
            'title' => 'ভোটার তথ্য প্রত্যয়নপত্র',
            'title_en' => 'Voter Information Certificate',
            'description' => 'ভোটার তালিকার তথ্যের প্রত্যয়ন।',
            'group' => 'পরিচয় ও নাগরিকত্ব',
            'icon' => 'vote',
            'fields' => [
                $f('voter_area', 'ভোটার এলাকা', 'text', true),
                $f('voter_no', 'ভোটার নম্বর', 'text'),
                $f('voter_year', 'ভোটার তালিকার বছর', 'text'),
            ],
        ],
        'character' => [
            'title' => 'চারিত্রিক সনদপত্র',
            'title_en' => 'Character Certificate',
            'description' => 'চারিত্রিক ও সামাজিক অবস্থার প্রত্যয়ন।',
            'group' => 'পরিচয় ও নাগরিকত্ব',
            'icon' => 'shield',
            'fields' => [
                $f('known_since', 'পরিচিতির সূচনা', 'text'),
                $f('character_note', 'চারিত্রিক বিবরণ', 'textarea'),
            ],
        ],
        'vumihin' => [
            'title' => 'ভূমিহীন প্রত্যয়নপত্র',
            'title_en' => 'Landless Certificate',
            'description' => 'ভূমিহীন হিসেবে প্রত্যয়ন।',
            'group' => 'ভূমি ও সম্পত্তি',
            'icon' => 'land',
            'fields' => [
                $f('land_description', 'জমির বিবরণ', 'textarea'),
                $f('khatian_no', 'খতিয়ান নম্বর', 'text'),
            ],
        ],
        'onapotti' => [
            'title' => 'অনাপত্তি সনদপত্র',
            'title_en' => 'No Objection Certificate',
            'description' => 'ব্যবসা/স্থাপনা/ভূমি সংক্রান্ত অনাপত্তি।',
            'group' => 'ভূমি ও সম্পত্তি',
            'icon' => 'check',
            'fields' => [
                $f('land_owner', 'জমির মালিক', 'text'),
                $f('land_description', 'জমির বিবরণ', 'textarea', true),
                $f('khatian_no', 'খতিয়ান নম্বর', 'text'),
                $f('land_amount', 'জমির পরিমাণ', 'text'),
            ],
        ],
        'rastakhonon' => [
            'title' => 'রাস্তা খনন প্রত্যয়নপত্র',
            'title_en' => 'Road Excavation Certificate',
            'description' => 'রাস্তা খনন/ভূমি প্রদান সংক্রান্ত প্রত্যয়ন।',
            'group' => 'ভূমি ও সম্পত্তি',
            'icon' => 'road',
            'fields' => [
                $f('road_name', 'রাস্তার নাম', 'text', true),
                $f('road_length', 'রাস্তার দৈর্ঘ্য', 'text'),
                $f('land_donated', 'প্রদত্ত জমির পরিমাণ', 'text'),
            ],
        ],
        'nodibanga' => [
            'title' => 'নদী ভাঙন প্রত্যয়নপত্র',
            'title_en' => 'River Erosion Certificate',
            'description' => 'নদী ভাঙনে ক্ষতিগ্রস্ততার প্রত্যয়ন।',
            'group' => 'ভূমি ও সম্পত্তি',
            'icon' => 'water',
            'fields' => [
                $f('river_name', 'নদীর নাম', 'text', true),
                $f('bank_description', 'ভাঙনের বিবরণ', 'textarea'),
                $f('land_amount', 'ক্ষতিগ্রস্ত জমির পরিমাণ', 'text'),
            ],
        ],
        'trade' => [
            'title' => 'ট্রেড লাইসেন্স প্রত্যয়নপত্র',
            'title_en' => 'Trade Licence Certificate',
            'description' => 'ব্যবসা প্রতিষ্ঠানের লাইসেন্স ও ফি/ট্যাক্স বিবরণ।',
            'group' => 'ব্যবসা ও আয়',
            'icon' => 'briefcase',
            'fees' => true,
            'fields' => [
                $f('business_name', 'ব্যবসা প্রতিষ্ঠানের নাম', 'text', true),
                $f('business_type', 'ব্যবসার ধরন', 'text', true),
                $f('business_address', 'ব্যবসার ঠিকানা', 'textarea', true),
                $f('license_year', 'লাইসেন্সের বছর', 'text'),
                $f('capital', 'মূলধন', 'number'),
                $f('fiscal_year', 'অর্থবছর', 'text', true),
                $f('expiry_date', 'মেয়াদ শেষের তারিখ', 'date'),
            ],
        ],
        'yearlyincome' => [
            'title' => 'বার্ষিক আয় প্রত্যয়নপত্র',
            'title_en' => 'Annual Income Certificate',
            'description' => 'বার্ষিক আয়ের প্রত্যয়ন।',
            'group' => 'ব্যবসা ও আয়',
            'icon' => 'money',
            'fields' => [
                $f('income_year', 'আয়ের বছর', 'text', true),
                $f('income_amount', 'বার্ষিক আয় (টাকা)', 'number', true),
                $f('income_source', 'আয়ের উৎস', 'text'),
            ],
        ],
        'onumoti' => [
            'title' => 'অনুমতি প্রত্যয়নপত্র',
            'title_en' => 'Permission Certificate',
            'description' => 'কোনো কাজ/অনুষ্ঠানের অনুমতি সংক্রান্ত প্রত্যয়ন।',
            'group' => 'অন্যান্য',
            'icon' => 'check',
            'fields' => [
                $f('consent_subject', 'বিষয়', 'text', true),
                $f('consent_details', 'বিস্তারিত', 'textarea', true),
            ],
        ],
        'family' => [
            'title' => 'পারিবারিক সনদপত্র',
            'title_en' => 'Family Certificate',
            'description' => 'পরিবারের সদস্যদের তালিকা ও পরিচিতি।',
            'group' => 'পরিবার ও সমাজ',
            'icon' => 'family',
            'members' => 'family',
            'fields' => [],
        ],
        'warish' => [
            'title' => 'ওয়ারিশ সনদপত্র',
            'title_en' => 'Heir Certificate',
            'description' => 'মৃত ব্যক্তির ওয়ারিশদের তালিকা।',
            'group' => 'পরিবার ও সমাজ',
            'icon' => 'family',
            'members' => 'warish',
            'fields' => [
                $f('deceased_name', 'মৃত ব্যক্তির নাম', 'text', true),
                $f('death_date', 'মৃত্যুর তারিখ', 'date', true),
            ],
        ],
        'death' => [
            'title' => 'মৃত্যু প্রত্যয়নপত্র',
            'title_en' => 'Death Certificate',
            'description' => 'মৃত্যুর তথ্যের প্রত্যয়ন।',
            'group' => 'পরিবার ও সমাজ',
            'icon' => 'death',
            'fields' => [
                $f('deceased_name', 'মৃত ব্যক্তির নাম', 'text', true),
                $f('death_date', 'মৃত্যুর তারিখ', 'date', true),
                $f('death_place', 'মৃত্যুর স্থান', 'text'),
                $f('burial_place', 'সমাধিস্থল', 'text'),
            ],
        ],
        'obibahito' => [
            'title' => 'অবিবাহিত প্রত্যয়নপত্র',
            'title_en' => 'Unmarried Certificate',
            'description' => 'অবিবাহিত অবস্থার প্রত্যয়ন।',
            'group' => 'পরিবার ও সমাজ',
            'icon' => 'ring',
            'fields' => [
                $f('age', 'বয়স', 'number'),
                $f('marriage_absence', 'বিবাহ না হওয়ার বিবরণ', 'textarea'),
            ],
        ],
        'bibahito' => [
            'title' => 'বিবাহিত প্রত্যয়নপত্র',
            'title_en' => 'Married Certificate',
            'description' => 'বিবাহিত অবস্থার প্রত্যয়ন।',
            'group' => 'পরিবার ও সমাজ',
            'icon' => 'ring',
            'fields' => [
                $f('spouse_name', 'স্বামী/স্ত্রীর নাম', 'text', true),
                $f('marriage_date', 'বিবাহের তারিখ', 'date'),
                $f('marriage_place', 'বিবাহের স্থান', 'text'),
                $f('marriage_reg_no', 'বিবাহ নিবন্ধন নম্বর', 'text'),
            ],
        ],
        'ekoinam' => [
            'title' => 'একই নাম প্রত্যয়নপত্র',
            'title_en' => 'Same Name Certificate',
            'description' => 'একই ব্যক্তি/নামের পরিচয়ের প্রত্যয়ন।',
            'group' => 'পরিবার ও সমাজ',
            'icon' => 'id',
            'fields' => [
                $f('previous_name', 'অপর নাম', 'text'),
                $f('certificate_purpose', 'উদ্দেশ্য', 'text'),
            ],
        ],
        'punobibaho' => [
            'title' => 'পুনর্বিবাহ প্রত্যয়নপত্র',
            'title_en' => 'Remarriage Certificate',
            'description' => 'পুনর্বিবাহের তথ্যের প্রত্যয়ন।',
            'group' => 'পরিবার ও সমাজ',
            'icon' => 'ring',
            'fields' => [
                $f('previous_spouse_name', 'পূর্বের স্বামী/স্ত্রীর নাম', 'text'),
                $f('spouse_death_date', 'পূর্বের স্বামী/স্ত্রীর মৃত্যুর তারিখ', 'date'),
                $f('marriage_date', 'পুনর্বিবাহের তারিখ', 'date', true),
            ],
        ],
        'protibondi' => [
            'title' => 'প্রতিবন্ধী প্রত্যয়নপত্র',
            'title_en' => 'Disability Certificate',
            'description' => 'প্রতিবন্ধিতার ধরন ও মাত্রার প্রত্যয়ন।',
            'group' => 'পরিবার ও সমাজ',
            'icon' => 'access',
            'fields' => [
                $f('disability_type', 'প্রতিবন্ধিতার ধরন', 'text', true),
                $f('disability_percent', 'প্রতিবন্ধিতার হার (%)', 'number'),
                $f('disability_card_no', 'প্রতিবন্ধী কার্ড নম্বর', 'text'),
            ],
        ],
        'prottyon' => [
            'title' => 'প্রত্যয়নপত্র (প্রত্যায়নকারী)',
            'title_en' => 'Attestation Certificate',
            'description' => 'নির্দিষ্ট প্রত্যায়নকারীর মাধ্যমে প্রদত্ত প্রত্যয়ন।',
            'group' => 'অন্যান্য',
            'icon' => 'pen',
            'fields' => [
                $f('certified_by', 'প্রত্যায়নকারীর নাম', 'text', true),
                $f('certificate_text', 'প্রত্যায়নের বিবরণ', 'textarea'),
            ],
        ],
        'sonaton' => [
            'title' => 'সনাতন প্রত্যয়নপত্র',
            'title_en' => 'Sanatan Certificate',
            'description' => 'ধর্মীয় পরিচয়/উপাসনালয় সংক্রান্ত প্রত্যয়ন।',
            'group' => 'অন্যান্য',
            'icon' => 'temple',
            'fields' => [
                $f('religion_name', 'ধর্মের নাম', 'text'),
                $f('religious_place', 'উপাসনালয়ের নাম', 'text'),
            ],
        ],
        'anumanik-boyosh-protyon' => [
            'title' => 'আনুমানিক বয়স প্রত্যয়নপত্র',
            'title_en' => 'Estimated Age Certificate',
            'description' => 'আনুমানিক বয়সের প্রত্যয়ন।',
            'group' => 'অন্যান্য',
            'icon' => 'calendar',
            'fields' => [
                $f('estimated_age', 'আনুমানিক বয়স', 'number', true),
                $f('age_reason', 'বয়স নির্ধারণের কারণ', 'textarea'),
            ],
        ],
        'protyonpotro-onnanno' => [
            'title' => 'প্রত্যয়নপত্র (অন্যান্য)',
            'title_en' => 'Other Certificate',
            'description' => 'অন্যান্য বিষয়ের প্রত্যয়ন।',
            'group' => 'অন্যান্য',
            'icon' => 'file',
            'fields' => [
                $f('subject', 'বিষয়', 'text', true),
                $f('details', 'বিস্তারিত', 'textarea', true),
            ],
        ],
    ];
}

function prottoyon_types(): array
{
    $out = [];
    foreach (prottoyon_registry() as $key => $type) {
        $extras = [];
        foreach (($type['fields'] ?? []) as $field) {
            $extras[] = ['k' => $field['k'], 'label' => $field['label']];
        }
        $type['extras'] = $extras;
        $out[$key] = $type;
    }
    return $out;
}

function prottoyon_legacy_types(): array
{
    $T = static fn(string $title, string $en): array => [
        'title' => $title,
        'title_en' => $en,
        'description' => 'পুরাতন সনদ — শুধু পড়া ও প্রিন্ট।',
        'group' => 'পুরাতন সনদ',
        'legacy' => true,
        'fields' => [],
        'extras' => [],
    ];
    return [
        'agriculture' => $T('কৃষি প্রত্যয়নপত্র', 'Agriculture'),
        'annual-income' => $T('বার্ষিক আয় প্রত্যয়নপত্র', 'Annual income'),
        'birth' => $T('জন্ম প্রত্যয়নপত্র', 'Birth'),
        'character' => $T('চারিত্রিক সনদপত্র', 'Character'),
        'childless' => $T('নিঃসন্তান প্রত্যয়নপত্র', 'Childless'),
        'citizenship' => $T('নাগরিকত্ব সনদপত্র', 'Citizenship'),
        'community' => $T('সম্প্রদায় প্রত্যয়নপত্র', 'Community'),
        'death' => $T('মৃত্যু প্রত্যয়নপত্র', 'Death'),
        'disabled' => $T('প্রতিবন্ধী প্রত্যয়নপত্র', 'Disabled'),
        'family' => $T('পারিবারিক সনদপত্র', 'Family'),
        'financial-insolvency' => $T('আর্থিক অসচ্ছলতা প্রত্যয়নপত্র', 'Financial insolvency'),
        'freedom-fighter' => $T('মুক্তিযোদ্ধা সন্তান প্রত্যয়নপত্র', 'Freedom fighter'),
        'guardian-consent' => $T('অভিভাবকের সম্মতিপত্র', 'Guardian consent'),
        'heir' => $T('ওয়ারিশ সনদপত্র', 'Heir'),
        'holding-tax' => $T('হোল্ডিং ট্যাক্স প্রত্যয়নপত্র', 'Holding tax'),
        'landless' => $T('ভূমিহীন প্রত্যয়নপত্র', 'Landless'),
        'living-person' => $T('জীবিত থাকার প্রত্যয়নপত্র', 'Living person'),
        'married' => $T('বিবাহিত প্রত্যয়নপত্র', 'Married'),
        'miscellaneous' => $T('বিবিধ প্রত্যয়নপত্র', 'Miscellaneous'),
        'monthly-income' => $T('মাসিক আয় প্রত্যয়নপত্র', 'Monthly income'),
        'nationality' => $T('জাতীয়তা সনদপত্র', 'Nationality'),
        'new-voter' => $T('নতুন ভোটার প্রত্যয়নপত্র', 'New voter'),
        'nid-correction' => $T('এনআইডি সংশোধন প্রত্যয়নপত্র', 'NID correction'),
        'no-objection' => $T('অনাপত্তি সনদপত্র', 'No objection'),
        'orphan' => $T('এতিম প্রত্যয়নপত্র', 'Orphan'),
        'passport' => $T('পাসপোর্ট প্রত্যয়নপত্র', 'Passport'),
        'permanent-resident' => $T('স্থায়ী বাসিন্দা সনদপত্র', 'Permanent resident'),
        'power-of-attorney' => $T('আমমোক্তারনামা প্রত্যয়নপত্র', 'Power of attorney'),
        'premises-license' => $T('প্রাঙ্গণ লাইসেন্স প্রত্যয়নপত্র', 'Premises license'),
        'professional' => $T('পেশাজীবী প্রত্যয়নপত্র', 'Professional'),
        'remarriage' => $T('পুনর্বিবাহ প্রত্যয়নপত্র', 'Remarriage'),
        'rohingya' => $T('অ-রোহিঙ্গা প্রত্যয়নপত্র', 'Rohingya'),
        'same-person' => $T('একই ব্যক্তি প্রত্যয়নপত্র', 'Same person'),
        'succession' => $T('উত্তরাধিকার সনদপত্র', 'Succession'),
        'trade-license' => $T('ট্রেড লাইসেন্স প্রত্যয়নপত্র', 'Trade licence'),
        'tribal' => $T('উপজাতি প্রত্যয়নপত্র', 'Tribal'),
        'unemployment' => $T('বেকারত্ব প্রত্যয়নপত্র', 'Unemployment'),
        'unmarried' => $T('অবিবাহিত প্রত্যয়নপত্র', 'Unmarried'),
        'voter-info' => $T('ভোটার তথ্য প্রত্যয়নপত্র', 'Voter info'),
        'voter-transfer' => $T('ভোটার স্থানান্তর প্রত্যয়নপত্র', 'Voter transfer'),
        'widow' => $T('বিধবা প্রত্যয়নপত্র', 'Widow'),
    ];
}

function prottoyon_all_types(): array
{
    return prottoyon_types() + prottoyon_legacy_types();
}

function prottoyon_type(?string $key): ?array
{
    $key = (string) $key;
    if ($key === '') return null;
    $types = prottoyon_registry();
    return $types[$key] ?? null;
}

function prottoyon_type_title(string $key, string $fallback = ''): string
{
    $all = prottoyon_all_types();
    return (string) ($all[$key]['title'] ?? ($fallback !== '' ? $fallback : $key));
}

function prottoyon_tool_key(string $type): string
{
    return 'prottoyon_' . $type;
}

function prottoyon_type_from_tool_key(string $toolKey): string
{
    return preg_replace('/^prottoyon_/', '', $toolKey) ?? $toolKey;
}

function prottoyon_bn_digits($value): string
{
    return str_replace(['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'], ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'], (string) $value);
}

function prottoyon_en_digits($value): string
{
    return str_replace(['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'], ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'], (string) $value);
}

function prottoyon_clean($value, int $max = 5000): string
{
    $value = trim((string) $value);
    $value = str_replace(["\0", "\r\n", "\r"], ['', "\n", "\n"], $value);
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value) ?? $value;
    return mb_substr($value, 0, $max);
}

function prottoyon_format_date($date, bool $withTime = false): string
{
    $ts = strtotime((string) $date);
    if ($ts === false) return prottoyon_bn_digits((string) $date);
    $months = [
        'January' => 'জানুয়ারি', 'February' => 'ফেব্রুয়ারি', 'March' => 'মার্চ',
        'April' => 'এপ্রিল', 'May' => 'মে', 'June' => 'জুন', 'July' => 'জুলাই',
        'August' => 'আগস্ট', 'September' => 'সেপ্টেম্বর', 'October' => 'অক্টোবর',
        'November' => 'নভেম্বর', 'December' => 'ডিসেম্বর',
    ];
    $day = (int) date('j', $ts);
    $suffix = 'ই';
    if (in_array($day, [1, 21, 31], true)) $suffix = 'লা';
    elseif (in_array($day, [2, 3, 22, 23], true)) $suffix = 'রা';
    elseif (in_array($day, [4, 24], true)) $suffix = 'ঠা';
    $out = prottoyon_bn_digits((string) $day) . $suffix . ' ' . ($months[date('F', $ts)] ?? date('F', $ts)) . ' ' . prottoyon_bn_digits(date('Y', $ts));
    if ($withTime) $out .= ', ' . prottoyon_bn_digits(date('h:i', $ts));
    return $out;
}

function prottoyon_date_input($date): string
{
    $ts = strtotime((string) $date);
    return $ts === false ? '' : date('Y-m-d', $ts);
}

function prottoyon_default_personal(): array
{
    return [
        'name_bn' => '', 'name_en' => '',
        'father_bn' => '', 'father_en' => '',
        'mother_bn' => '', 'mother_en' => '',
        'dob' => '', 'gender' => '', 'religion' => '', 'occupation' => '', 'education' => '',
        'marital_status' => '', 'resident_status' => '', 'nid' => '', 'brn' => '', 'passport_no' => '',
        'mobile' => '', 'email' => '', 'spouse_name' => '', 'spouse_name_en' => '',
        'applicant_name' => '', 'applicant_phone' => '',
    ];
}

function prottoyon_default_address(): array
{
    return [
        'address_bn' => '', 'address_en' => '',
        'village_bn' => '', 'village_en' => '',
        'rbs_bn' => '', 'rbs_en' => '', 'holding_no' => '',
        'ward' => '', 'post_office_bn' => '', 'post_office_en' => '',
        'union_bn' => '', 'union_en' => '',
        'upazila_bn' => '', 'upazila_en' => '',
        'district_bn' => '', 'district_en' => '',
        'division_bn' => '', 'division_en' => '', 'postcode' => '',
    ];
}

function prottoyon_decode_row(array $row): array
{
    $raw = [];
    if (!empty($row['data'])) {
        $decoded = json_decode((string) $row['data'], true);
        if (is_array($decoded)) $raw = $decoded;
    }

    if (isset($raw['personal']) && is_array($raw['personal'])) {
        $data = [
            'v' => (int) ($raw['v'] ?? 2),
            'type' => (string) ($raw['type'] ?? prottoyon_type_from_tool_key((string) ($row['tool_key'] ?? ''))),
            'personal' => array_merge(prottoyon_default_personal(), $raw['personal']),
            'present' => array_merge(prottoyon_default_address(), is_array($raw['present'] ?? null) ? $raw['present'] : []),
            'permanent' => array_merge(prottoyon_default_address(), is_array($raw['permanent'] ?? null) ? $raw['permanent'] : []),
            'extra' => is_array($raw['extra'] ?? null) ? $raw['extra'] : [],
            'members' => is_array($raw['members'] ?? null) ? $raw['members'] : [],
            'fees' => is_array($raw['fees'] ?? null) ? $raw['fees'] : [],
            'documents' => is_array($raw['documents'] ?? null) ? $raw['documents'] : [],
            'meta' => is_array($raw['meta'] ?? null) ? $raw['meta'] : [],
            'purpose' => (string) ($raw['purpose'] ?? ''),
        ];
        return $data;
    }

    $personal = prottoyon_default_personal();
    $map = [
        'name_bn' => ['name_bn'], 'name_en' => ['name_en'], 'father_bn' => ['father_bn', 'father_name'],
        'father_en' => ['father_en'], 'mother_bn' => ['mother_bn', 'mother_name'],
        'mother_en' => ['mother_en'], 'dob' => ['dob', 'birth_date'], 'gender' => ['gender'],
        'religion' => ['religion'], 'occupation' => ['occupation'], 'education' => ['education'],
        'nid' => ['nid'], 'brn' => ['brn', 'birth_reg_no'], 'passport_no' => ['passport_no'],
        'mobile' => ['mobile', 'phone'], 'email' => ['email'], 'spouse_name' => ['spouse_name'],
        'spouse_name_en' => ['spouse_name_en'], 'applicant_name' => ['applicant_name'],
        'applicant_phone' => ['applicant_phone'], 'marital_status' => ['marital_status'],
    ];
    foreach ($map as $dest => $keys) {
        foreach ($keys as $k) {
            if (array_key_exists($k, $raw) && $raw[$k] !== '') {
                $personal[$dest] = prottoyon_clean($raw[$k]);
                break;
            }
        }
    }

    $present = prottoyon_default_address();
    $addressMap = [
        'village_bn' => ['village', 'village_bn'], 'rbs_bn' => ['rbs', 'rbs_bn'], 'holding_no' => ['holding_no'],
        'ward' => ['ward', 'ward_no'], 'post_office_bn' => ['post_office', 'post_office_bn'],
        'union_bn' => ['union', 'union_bn'], 'upazila_bn' => ['upazila', 'upazila_bn'],
        'district_bn' => ['district', 'district_bn'], 'division_bn' => ['division', 'division_bn'],
        'address_bn' => ['present_address', 'address_bn'], 'postcode' => ['postcode'],
    ];
    foreach ($addressMap as $dest => $keys) {
        foreach ($keys as $k) {
            if (array_key_exists($k, $raw) && $raw[$k] !== '') {
                $present[$dest] = prottoyon_clean($raw[$k]);
                break;
            }
        }
    }
    $permanent = prottoyon_default_address();
    if (!empty($raw['permanent_address'])) $permanent['address_bn'] = prottoyon_clean($raw['permanent_address']);
    if (!empty($raw['permanent'])) $permanent['address_bn'] = prottoyon_clean($raw['permanent']);

    return [
        'v' => 1,
        'type' => (string) ($raw['certificate_type'] ?? $raw['type'] ?? prottoyon_type_from_tool_key((string) ($row['tool_key'] ?? ''))),
        'personal' => $personal,
        'present' => $present,
        'permanent' => $permanent,
        'extra' => $raw,
        'members' => is_array($raw['members'] ?? null) ? $raw['members'] : [],
        'fees' => is_array($raw['fees'] ?? null) ? $raw['fees'] : [],
        'documents' => [],
        'meta' => ['legacy' => true],
        'purpose' => (string) ($raw['purpose'] ?? ''),
        'legacy' => $raw,
    ];
}

function prottoyon_value(array $data, string $path, string $default = ''): string
{
    $segments = explode('.', $path);
    $value = $data;
    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) return $default;
        $value = $value[$segment];
    }
    if (is_array($value)) return $default;
    return trim((string) $value) !== '' ? (string) $value : $default;
}

function prottoyon_collect_input(array $post): array
{
    $errors = [];
    $typeKey = prottoyon_clean($post['type'] ?? '', 80);
    $type = prottoyon_type($typeKey);
    if ($type === null) {
        return ['data' => [], 'errors' => ['সনদের ধরন সঠিক নয়।']];
    }

    $personal = prottoyon_default_personal();
    foreach (array_keys($personal) as $key) {
        $personal[$key] = prottoyon_clean($post['personal'][$key] ?? '', $key === 'email' ? 190 : 500);
    }
    $personal['nid'] = prottoyon_en_digits($personal['nid']);
    $personal['brn'] = prottoyon_en_digits($personal['brn']);
    $personal['mobile'] = prottoyon_en_digits($personal['mobile']);

    $addresses = [];
    foreach (['present', 'permanent'] as $which) {
        $address = prottoyon_default_address();
        foreach (array_keys($address) as $key) {
            $address[$key] = prottoyon_clean($post[$which][$key] ?? '', 500);
        }
        $addresses[$which] = $address;
    }
    if (!empty($post['same_address'])) {
        $addresses['permanent'] = $addresses['present'];
    } elseif (trim(implode('', $addresses['permanent'])) === '') {
        $addresses['permanent'] = $addresses['present'];
    }

    $extra = [];
    foreach (($type['fields'] ?? []) as $field) {
        $key = $field['k'];
        $extra[$key] = prottoyon_clean($post['extra'][$key] ?? '', $field['type'] === 'textarea' ? 5000 : 500);
    }

    $members = [];
    $memberNames = $post['members']['name'] ?? [];
    if (is_array($memberNames)) {
        foreach ($memberNames as $i => $name) {
            $member = [
                'name' => prottoyon_clean($name, 300),
                'relation' => prottoyon_clean($post['members']['relation'][$i] ?? '', 200),
                'age' => prottoyon_clean($post['members']['age'][$i] ?? '', 50),
                'nid' => prottoyon_en_digits(prottoyon_clean($post['members']['nid'][$i] ?? '', 50)),
                'share' => prottoyon_clean($post['members']['share'][$i] ?? '', 100),
                'occupation' => prottoyon_clean($post['members']['occupation'][$i] ?? '', 200),
            ];
            if (trim($member['name'] . $member['relation']) !== '') $members[] = $member;
        }
    }

    $fees = [];
    $feeNames = $post['fees']['name'] ?? [];
    if (is_array($feeNames)) {
        foreach ($feeNames as $i => $name) {
            $fee = [
                'name' => prottoyon_clean($name, 300),
                'amount' => prottoyon_clean($post['fees']['amount'][$i] ?? '', 50),
            ];
            if (trim($fee['name'] . $fee['amount']) !== '') $fees[] = $fee;
        }
    }

    $data = [
        'v' => 2,
        'type' => $typeKey,
        'personal' => $personal,
        'present' => $addresses['present'],
        'permanent' => $addresses['permanent'],
        'extra' => $extra,
        'members' => $members,
        'fees' => $fees,
        'documents' => [],
        'purpose' => prottoyon_clean($post['purpose'] ?? '', 1000),
        'meta' => ['language' => 'bn', 'submitted_at' => date('c')],
    ];

    if ($personal['name_bn'] === '') $errors[] = 'আবেদনকারীর নাম (বাংলা) দিন।';
    if ($personal['dob'] === '') {
        $errors[] = 'জন্ম তারিখ দিন।';
    } elseif (strtotime($personal['dob']) === false) {
        $errors[] = 'জন্ম তারিখ সঠিক নয়।';
    }
    if ($addresses['present']['village_bn'] === '') $errors[] = 'বর্তমান ঠিকানার গ্রাম/মহল্লা দিন।';
    if ($addresses['present']['upazila_bn'] === '') $errors[] = 'বর্তমান ঠিকানার উপজেলা দিন।';
    if ($addresses['present']['district_bn'] === '') $errors[] = 'বর্তমান ঠিকানার জেলা দিন।';

    foreach (($type['fields'] ?? []) as $field) {
        if (!empty($field['required']) && ($extra[$field['k']] ?? '') === '') {
            $errors[] = $field['label'] . ' দিন।';
        }
    }
    if (($type['members'] ?? '') === 'warish' && count($members) === 0) {
        $errors[] = 'অন্তত একজন ওয়ারিশের নাম ও সম্পর্ক দিন।';
    }
    if (($type['members'] ?? '') === 'family' && count($members) === 0) {
        $errors[] = 'অন্তত একজন পারিবারিক সদস্যের তথ্য দিন।';
    }

    return ['data' => $data, 'errors' => $errors];
}

function prottoyon_union_code(array $office): string
{
    $code = prottoyon_en_digits((string) ($office['union_code'] ?? ''));
    $code = preg_replace('/\D/', '', $code) ?? '';
    return strlen($code) === 7 ? $code : '0000000';
}

function prottoyon_next_cert_no(array $office): array
{
    $unionCode = prottoyon_union_code($office);
    $prefix = date('y') . $unionCode;
    $db = repdoc_db();
    if (!$db) throw new RuntimeException('ডেটাবেস unavailable।');

    $mysql = ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql');
    $db->beginTransaction();
    try {
        if ($mysql) {
            $st = $db->prepare('SELECT last_value FROM number_sequences WHERE prefix = ? FOR UPDATE');
        } else {
            $st = $db->prepare('SELECT last_value FROM number_sequences WHERE prefix = ?');
        }
        $st->execute([$prefix]);
        $last = $st->fetchColumn();
        if ($last === false) {
            $db->prepare('INSERT INTO number_sequences (prefix, last_value) VALUES (?, 1)')->execute([$prefix]);
            $seq = 1;
        } else {
            $seq = (int) $last + 1;
            $db->prepare('UPDATE number_sequences SET last_value = ? WHERE prefix = ?')->execute([$seq, $prefix]);
        }
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        throw $e;
    }

    if ($seq > 99999999) throw new RuntimeException('এই বছরের সনদ নম্বরের সীমা শেষ।');
    return [
        'cert_no' => $prefix . str_pad((string) $seq, 8, '0', STR_PAD_LEFT),
        'union_code' => $unionCode,
        'sequence' => $seq,
        'fallback' => $unionCode === '0000000',
    ];
}

function prottoyon_app_id(): string
{
    $db = repdoc_db();
    for ($attempt = 0; $attempt < 6; $attempt++) {
        $id = 'PRT-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        if (!$db) return $id;
        try {
            $st = $db->prepare('SELECT COUNT(*) FROM document_logs WHERE ref = ?');
            $st->execute([$id]);
            if ((int) $st->fetchColumn() === 0) return $id;
        } catch (Throwable $e) {
            return $id;
        }
    }
    return 'PRT-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
}

function prottoyon_absolute_url(string $path): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    return $scheme . '://' . $host . repdoc_url($path);
}

function prottoyon_verify_url(string $certNo): string
{
    return prottoyon_absolute_url('verify/index.php?no=' . rawurlencode($certNo));
}

function prottoyon_office_defaults(): array
{
    return [
        'office_type' => 'union',
        'office_name' => '',
        'chairman_name' => '',
        'upazila' => '',
        'district' => '',
        'union_code' => '',
        'postcode' => '',
        'phone' => '',
        'email' => '',
        'website' => '',
        'logo_path' => '',
        'stamp_path' => '',
    ];
}

function prottoyon_office_for_row(array $row): array
{
    $office = prottoyon_office_defaults();
    if (!empty($row['office'])) {
        $decoded = json_decode((string) $row['office'], true);
        if (is_array($decoded)) {
            foreach (array_keys($office) as $key) {
                if (isset($decoded[$key])) $office[$key] = (string) $decoded[$key];
            }
        }
    }
    $profile = office_profile((int) ($row['user_id'] ?? 0));
    foreach (array_keys($office) as $key) {
        if (($office[$key] ?? '') === '' && ($profile[$key] ?? '') !== '') $office[$key] = (string) $profile[$key];
    }
    return $office;
}

function prottoyon_file_data_uri(string $path): string
{
    $path = ltrim($path, '/');
    if ($path === '') return '';
    $full = REPDOC_ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
    if (!is_file($full)) return '';
    $mime = function_exists('mime_content_type') ? (string) mime_content_type($full) : '';
    if (!str_starts_with($mime, 'image/')) return '';
    $raw = @file_get_contents($full);
    if ($raw === false) return '';
    return 'data:' . $mime . ';base64,' . base64_encode($raw);
}

function prottoyon_office_logo_uri(array $office): string
{
    $uri = prottoyon_file_data_uri((string) ($office['logo_path'] ?? ''));
    if ($uri !== '') return $uri;
    return prottoyon_file_data_uri('genaretor/prottoyon/assets/images/gov_logo.png');
}

function prottoyon_office_stamp_uri(array $office): string
{
    return prottoyon_file_data_uri((string) ($office['stamp_path'] ?? ''));
}

function prottoyon_document_url(string $path): string
{
    return repdoc_url('genaretor/prottoyon/file.php?f=' . rawurlencode($path));
}

function prottoyon_upload_mime(string $tmp): string
{
    if (class_exists('finfo')) {
        $fi = new finfo(FILEINFO_MIME_TYPE);
        return (string) $fi->file($tmp);
    }
    return function_exists('mime_content_type') ? (string) mime_content_type($tmp) : '';
}

function prottoyon_store_upload(array $file, int $userId, string $kind = 'document', int $maxBytes = 5242880, bool $imagesOnly = false): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'skip' => true, 'message' => ''];
    }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'message' => 'ফাইল আপলোড হয়নি।'];
    }
    if ((int) ($file['size'] ?? 0) > $maxBytes) {
        $limitMb = max(1, (int) round($maxBytes / 1048576));
        return ['ok' => false, 'message' => 'ফাইলের আকার ' . $limitMb . ' MB-এর কম হতে হবে।'];
    }

    $mime = prottoyon_upload_mime((string) ($file['tmp_name'] ?? ''));
    $allowed = [
        'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];
    if ($imagesOnly) unset($allowed['application/pdf']);
    if (!isset($allowed[$mime])) {
        return ['ok' => false, 'message' => $imagesOnly ? 'শুধু JPG, PNG বা WEBP ছবি গ্রহণ করা হয়।' : 'শুধু JPG, PNG, WEBP বা PDF ফাইল গ্রহণ করা হয়।'];
    }

    $dir = REPDOC_ROOT . '/love/uploads/prottoyon/' . $userId;
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        return ['ok' => false, 'message' => 'আপলোড ফোল্ডার তৈরি হয়নি।'];
    }
    $name = preg_replace('/[^a-zA-Z0-9._-]/', '-', (string) ($file['name'] ?? 'file')) ?: 'file';
    $name = substr($name, 0, 80);
    $stored = $kind . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
    $full = $dir . '/' . $stored;
    if (!move_uploaded_file((string) ($file['tmp_name'] ?? ''), $full)) {
        return ['ok' => false, 'message' => 'ফাইল সংরক্ষণ হয়নি।'];
    }
    return [
        'ok' => true,
        'skip' => false,
        'path' => 'love/uploads/prottoyon/' . $userId . '/' . $stored,
        'name' => $name,
        'mime' => $mime,
    ];
}

function prottoyon_delete_upload(string $path): void
{
    $path = ltrim(str_replace('\\', '/', $path), '/');
    if ($path === '' || !str_starts_with($path, 'love/uploads/prottoyon/')) return;
    $full = REPDOC_ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
    if (is_file($full)) @unlink($full);
}

function prottoyon_store_office_upload(array $file, int $userId, string $kind): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'skip' => true, 'message' => ''];
    }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'message' => 'ফাইল আপলোড হয়নি।'];
    }
    if ((int) ($file['size'] ?? 0) > 3 * 1024 * 1024) {
        return ['ok' => false, 'message' => 'লোগো/সিলের আকার ৩ MB-এর কম হতে হবে।'];
    }
    $mime = prottoyon_upload_mime((string) ($file['tmp_name'] ?? ''));
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) {
        return ['ok' => false, 'message' => 'লোগো/সিলের জন্য JPG, PNG বা WEBP দিন।'];
    }
    $dir = REPDOC_ROOT . '/love/uploads/office/' . $userId;
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        return ['ok' => false, 'message' => 'আপলোড ফোল্ডার তৈরি হয়নি।'];
    }
    $stored = $kind . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
    $full = $dir . '/' . $stored;
    if (!move_uploaded_file((string) ($file['tmp_name'] ?? ''), $full)) {
        return ['ok' => false, 'message' => 'ফাইল সংরক্ষণ হয়নি।'];
    }
    return ['ok' => true, 'skip' => false, 'path' => 'love/uploads/office/' . $userId . '/' . $stored];
}

function prottoyon_refund(int $userId, int $amount, string $reason, string $ref = ''): void
{
    if ($amount <= 0) return;
    if (function_exists('adjust_balance')) {
        adjust_balance($userId, $amount, $reason, 0);
    }
}

if (!function_exists('en2bnNumber')) {
    function en2bnNumber($str) { return prottoyon_bn_digits($str); }
}
if (!function_exists('prottoyon_formatDate')) {
    function prottoyon_formatDate($date) { return prottoyon_format_date($date); }
}
if (!function_exists('formatDate')) {
    function formatDate($date, $format = 'd F, Y') { return prottoyon_format_date($date); }
}
