<?php
declare(strict_types=1);

/**
 * Repdoc — prottoyon certificate types.
 *
 * Body layouts come from tpl/*.php (same output family as the reference
 * repo); each entry lists that template's extra inputs as key + Bangla
 * label. Keys match the template variables exactly.
 *
 * Common applicant fields (in the form): name_bn, father_bn, mother_bn,
 * dob, gender, religion, nid, brn, mobile, village, ward, upazila,
 * district, post_office, occupation, present_address, permanent_address.
 */
function prottoyon_types(): array
{
    $T = static fn(string $title, array $extras = []) => ['title' => $title, 'extras' => $extras];
    $x = static fn(string $k, string $label) => ['k' => $k, 'label' => $label];
    return [
        'agriculture' => $T('কৃষি প্রত্যয়নপত্র', [$x('land_amount', 'জমির পরিমাণ')]),
        'annual-income' => $T('বার্ষিক আয় প্রত্যয়নপত্র', [$x('annual_income', 'বার্ষিক আয় (টাকা)'), $x('occupation', 'পেশা')]),
        'birth' => $T('জন্ম প্রত্যয়নপত্র'),
        'character' => $T('চারিত্রিক সনদপত্র', [$x('post_office', 'পোস্ট অফিস')]),
        'childless' => $T('নিঃসন্তান প্রত্যয়নপত্র'),
        'citizenship' => $T('নাগরিকত্ব সনদপত্র'),
        'community' => $T('সম্প্রদায় প্রত্যয়নপত্র', [$x('community_name', 'সম্প্রদায়ের নাম')]),
        'death' => $T('মৃত্যু প্রত্যয়নপত্র', [$x('death_date', 'মৃত্যুর তারিখ'), $x('deceased_name', 'মৃত ব্যক্তির নাম')]),
        'disabled' => $T('প্রতিবন্ধী প্রত্যয়নপত্র', [$x('disability_type', 'প্রতিবন্ধিতার ধরন')]),
        'family' => $T('পারিবারিক সনদপত্র'),
        'financial-insolvency' => $T('আর্থিক অসচ্ছলতা প্রত্যয়নপত্র', [$x('occupation', 'পেশা')]),
        'freedom-fighter' => $T('মুক্তিযোদ্ধা সন্তান প্রত্যয়নপত্র', [$x('ff_number', 'মুক্তিযোদ্ধা নম্বর')]),
        'guardian-consent' => $T('অভিভাবকের সম্মতিপত্র', [$x('guardian_name', 'অভিভাবকের নাম'), $x('guardian_relation', 'সম্পর্ক'), $x('purpose', 'উদ্দেশ্য')]),
        'heir' => $T('ওয়ারিশ সনদপত্র', [$x('deceased_name', 'মৃত ব্যক্তির নাম')]),
        'holding-tax' => $T('হোল্ডিং ট্যাক্স প্রত্যয়নপত্র', [$x('holding_no', 'হোল্ডিং নম্বর')]),
        'landless' => $T('ভূমিহীন প্রত্যয়নপত্র', [$x('occupation', 'পেশা')]),
        'living-person' => $T('জীবিত থাকার প্রত্যয়নপত্র'),
        'married' => $T('বিবাহিত প্রত্যয়নপত্র', [$x('spouse_name', 'স্বামী/স্ত্রীর নাম')]),
        'miscellaneous' => $T('বিবিধ প্রত্যয়নপত্র', [$x('custom_text', 'বিবরণ')]),
        'monthly-income' => $T('মাসিক আয় প্রত্যয়নপত্র', [$x('monthly_income', 'মাসিক আয় (টাকা)'), $x('occupation', 'পেশা')]),
        'nationality' => $T('জাতীয়তা সনদপত্র'),
        'new-voter' => $T('নতুন ভোটার প্রত্যয়নপত্র'),
        'nid-correction' => $T('এনআইডি সংশোধন প্রত্যয়নপত্র', [$x('previous_info', 'ভুল তথ্য'), $x('corrected_info', 'সঠিক তথ্য')]),
        'no-objection' => $T('অনাপত্তি সনদপত্র', [$x('purpose', 'উদ্দেশ্য')]),
        'orphan' => $T('এতিম প্রত্যয়নপত্র', [$x('post_office', 'পোস্ট অফিস')]),
        'passport' => $T('পাসপোর্ট প্রত্যয়নপত্র'),
        'permanent-resident' => $T('স্থায়ী বাসিন্দা সনদপত্র', [$x('post_office', 'পোস্ট অফিস'), $x('residence_duration', 'বসবাসের মেয়াদ')]),
        'power-of-attorney' => $T('আমমোক্তারনামা প্রত্যয়নপত্র', [$x('principal_name', 'মোক্তারদাতার নাম'), $x('attorney_name', 'আমমোক্তারের নাম'), $x('powers', 'ক্ষমতাসমূহ')]),
        'premises-license' => $T('প্রাঙ্গণ লাইসেন্স প্রত্যয়নপত্র', [$x('establishment_name', 'প্রতিষ্ঠানের নাম'), $x('establishment_address', 'প্রতিষ্ঠানের ঠিকানা')]),
        'professional' => $T('পেশাজীবী প্রত্যয়নপত্র', [$x('occupation', 'পেশা')]),
        'remarriage' => $T('পুনর্বিবাহ প্রত্যয়নপত্র', [$x('spouse_name', 'স্বামী/স্ত্রীর নাম'), $x('remarriage_date', 'পুনর্বিবাহের তারিখ')]),
        'rohingya' => $T('অ-রোহিঙ্গা প্রত্যয়নপত্র'),
        'same-person' => $T('একই ব্যক্তি প্রত্যয়নপত্র', [$x('previous_name', 'অপর নাম'), $x('previous_details', 'বিস্তারিত')]),
        'succession' => $T('উত্তরাধিকার সনদপত্র', [$x('deceased_name', 'মৃত ব্যক্তির নাম')]),
        'trade-license' => $T('ট্রেড লাইসেন্স প্রত্যয়নপত্র', [$x('trade_name', 'ব্যবসার নাম'), $x('trade_address', 'ব্যবসার ঠিকানা')]),
        'tribal' => $T('উপজাতি প্রত্যয়নপত্র', [$x('tribe_name', 'উপজাতির নাম')]),
        'unemployment' => $T('বেকারত্ব প্রত্যয়নপত্র', [$x('occupation', 'পেশা')]),
        'unmarried' => $T('অবিবাহিত প্রত্যয়নপত্র'),
        'voter-info' => $T('ভোটার তথ্য প্রত্যয়নপত্র'),
        'voter-transfer' => $T('ভোটার স্থানান্তর প্রত্যয়নপত্র', [$x('previous_area', 'বর্তমান এলাকা'), $x('new_area', 'নতুন এলাকা')]),
        'widow' => $T('বিধবা প্রত্যয়নপত্র', [$x('late_spouse_name', 'স্বামীর নাম')]),
    ];
}
