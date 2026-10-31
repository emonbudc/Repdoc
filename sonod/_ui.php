<?php
declare(strict_types=1);

if (!function_exists('repdoc_sonod_styles')) {
    function repdoc_sonod_styles(): void
    {
        ?>
    <style>
        .sonod-page { background: var(--bg, #fffefb); }
        .sonod-wrap { max-width: 1040px; margin: 0 auto; padding: 28px 20px 72px; }
        .sonod-crumbs { font-size: .86rem; color: var(--text-muted, #667085); margin: 0 0 20px; }
        .sonod-crumbs a { color: inherit; text-decoration: none; }
        .sonod-crumbs a:hover { text-decoration: underline; }
        .sonod-hero { padding: 18px 0 10px; }
        .sonod-kicker { display: inline-flex; align-items: center; gap: 8px; font-size: .76rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #0f766e; background: #ccfbf1; border-radius: 999px; padding: 6px 12px; }
        .sonod-hero h1 { font-size: clamp(1.7rem, 4vw, 2.6rem); line-height: 1.28; margin: 14px 0 12px; max-width: 900px; }
        .sonod-lead { font-size: 1.06rem; line-height: 1.8; color: var(--text-muted, #475467); max-width: 860px; margin: 0; }
        .sonod-note { border-left: 4px solid #14b8a6; background: #f0fdfa; color: #134e4a; border-radius: 8px; padding: 14px 16px; line-height: 1.7; margin: 20px 0; font-size: .94rem; }
        .sonod-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; margin: 20px 0 8px; }
        .sonod-card { display: flex; flex-direction: column; gap: 8px; background: #fff; border: 1px solid #e4e7ec; border-radius: 14px; padding: 20px; text-decoration: none; color: inherit; transition: border-color .18s ease, transform .18s ease, box-shadow .18s ease; }
        .sonod-card:hover { border-color: #14b8a6; transform: translateY(-2px); box-shadow: 0 10px 24px rgba(15, 118, 110, .10); }
        .sonod-card strong { font-size: 1.05rem; }
        .sonod-card span { color: #475467; line-height: 1.65; font-size: .93rem; }
        .sonod-card em { margin-top: auto; font-style: normal; font-size: .86rem; font-weight: 700; color: #0f766e; }
        .sonod-group { margin-top: 34px; }
        .sonod-group h2 { font-size: 1.35rem; margin: 0 0 4px; }
        .sonod-group p { margin: 0; color: #667085; }
        .sonod-section { margin-top: 34px; }
        .sonod-section h2 { font-size: 1.3rem; margin: 0 0 10px; }
        .sonod-section h3 { font-size: 1.02rem; margin: 20px 0 6px; }
        .sonod-section p, .sonod-section li { line-height: 1.78; color: #344054; }
        .sonod-section ul, .sonod-section ol { padding-left: 22px; margin: 8px 0 0; }
        .sonod-meta { display: flex; flex-wrap: wrap; gap: 10px 18px; margin: 14px 0 0; color: #667085; font-size: .86rem; }
        .sonod-aka { margin: 10px 0 0; color: #475467; font-size: .92rem; line-height: 1.65; }
        .sonod-panel { background: #fff; border: 1px solid #e4e7ec; border-radius: 14px; padding: 22px; margin-top: 18px; }
        .sonod-cta { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px; }
        .sonod-cta a { display: inline-flex; align-items: center; justify-content: center; min-height: 42px; padding: 0 18px; border-radius: 9px; font-weight: 700; text-decoration: none; }
        .sonod-cta .primary { background: #0f766e; color: #fff; }
        .sonod-cta .secondary { border: 1px solid #cbd5e1; color: #0f172a; background: #fff; }
        .sonod-faq { margin-top: 8px; }
        .sonod-faq details { border-bottom: 1px solid #e4e7ec; padding: 14px 0; }
        .sonod-faq summary { cursor: pointer; font-weight: 700; color: #1d2939; }
        .sonod-faq p { margin: 8px 0 0; }
        .sonod-related { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 12px; }
        .sonod-related a { border: 1px solid #d0d5dd; background: #fff; color: #0f172a; text-decoration: none; border-radius: 999px; padding: 8px 14px; font-size: .9rem; }
        .sonod-related a:hover { border-color: #14b8a6; color: #0f766e; }
        .sonod-source { font-size: .86rem; color: #667085; margin-top: 30px; }
        @media (max-width: 640px) { .sonod-wrap { padding-top: 18px; } .sonod-panel { padding: 18px; } }
    </style>
        <?php
    }
}

if (!function_exists('repdoc_sonod_disclaimer')) {
    function repdoc_sonod_disclaimer(): void
    {
        ?>
        <p class="sonod-note"><strong>জরুরি:</strong> Repdoc একটি স্বতন্ত্র ডকুমেন্ট-টুলকিট, সরকারি পোর্টাল নয়। এখানে দেওয়া তথ্য সাধারণ ধারণার জন্য; সরকারি সনদ ইস্যু, ফি, সময় ও নিয়ম সংশ্লিষ্ট ইউনিয়ন পরিষদ, পৌরসভা, সিটি কর্পোরেশন বা দপ্তরের সর্বশেষ নির্দেশনা অনুযায়ীই প্রযোজ্য।</p>
        <?php
    }
}

if (!function_exists('repdoc_sonod_card')) {
    function repdoc_sonod_card(string $slug, array $guide): void
    {
        ?>
        <a class="sonod-card" href="<?= e(repdoc_url('sonod/' . $slug . '/')) ?>">
            <strong><?= e((string) $guide['label']) ?></strong>
            <span><?= e((string) $guide['desc']) ?></span>
            <em>গাইড পড়ুন →</em>
        </a>
        <?php
    }
}
