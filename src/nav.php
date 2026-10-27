<?php
declare(strict_types=1);

/**
 * Shared header navigation for the generator pages.
 * Include after src/bootstrap.php.
 */

if (!function_exists('repdoc_nav_tool_groups')) {
    function repdoc_nav_tool_groups(): array
    {
        return [
            [
                'label_bn' => 'পরিচয়',
                'label_en' => 'Identity',
                'items' => [
                    ['key' => 'nid',       'label_bn' => 'জাতীয় পরিচয়পত্র (NID)', 'label_en' => 'National ID (NID)', 'url' => 'genaretor/nid/'],
                    ['key' => 'birth',     'label_bn' => 'জন্ম নিবন্ধন',            'label_en' => 'Birth Certificate',  'url' => 'genaretor/birth/'],
                    ['key' => 'smartnid',  'label_bn' => 'স্মার্ট NID',             'label_en' => 'Smart NID',          'url' => 'genaretor/smartnid/'],
                    ['key' => 'passport',  'label_bn' => 'পাসপোর্ট সাইজ ছবি',      'label_en' => 'Passport Photo',     'url' => 'tools/passport/'],
                ],
            ],
            [
                'label_bn' => 'শিক্ষা',
                'label_en' => 'Education',
                'items' => [
                    ['key' => 'certificate', 'label_bn' => 'শিক্ষা সনদ', 'label_en' => 'Education Certificates', 'url' => 'genaretor/certificate/'],
                    ['key' => 'prottoyon',   'label_bn' => 'প্রত্যয়ন',   'label_en' => 'Attestation',            'url' => 'genaretor/prottoyon/'],
                ],
            ],
            [
                'label_bn' => 'ডকুমেন্টস',
                'label_en' => 'Documents',
                'items' => [
                    ['key' => 'cvmaker', 'label_bn' => 'সিভি মেকার',  'label_en' => 'CV Maker',     'url' => 'genaretor/cvmaker/'],
                    ['key' => 'land',    'label_bn' => 'ভূমি খতিয়ান', 'label_en' => 'Land Records', 'url' => 'tools/land/'],
                ],
            ],
            [
                'label_bn' => 'টেমপ্লেট',
                'label_en' => 'Templates',
                'items' => [
                    ['key' => 'showcase',     'label_bn' => 'টেমপ্লেট শোকেস', 'label_en' => 'Template Showcase', 'url' => 'showcase/'],
                    ['key' => 'officefiles',  'label_bn' => 'Word / Excel',    'label_en' => 'Word / Excel',      'url' => 'officefiles/'],
                    ['key' => 'offlinefiles', 'label_bn' => 'PSD / AI',        'label_en' => 'PSD / AI',          'url' => 'offlinefiles/'],
                    ['key' => 'software',     'label_bn' => 'সফটওয়্যার',        'label_en' => 'Software',          'url' => 'software/'],
                ],
            ],
        ];
    }
}

if (!function_exists('repdoc_render_site_nav')) {
    function repdoc_render_site_nav(string $current = '', string $lang = 'bn'): void
    {
        $en = $lang === 'en';
        $pick = static fn(string $bn, string $valueEn): string => $en ? $valueEn : $bn;
        ?>
        <nav class="site-nav" id="siteNav" aria-label="<?= $en ? 'Primary navigation' : 'প্রধান নেভিগেশন' ?>">
            <a class="nav-link" href="<?= e(repdoc_url('/')) ?>"><?= $pick('হোম', 'Home') ?></a>
            <details class="nav-dropdown">
                <summary class="nav-link"><?= $pick('টুলস', 'Tools') ?></summary>
                <div class="nav-dropdown-menu">
                    <?php foreach (repdoc_nav_tool_groups() as $group): ?>
                        <div class="nav-menu-group">
                            <span class="nav-menu-heading"><?= e($pick($group['label_bn'], $group['label_en'])) ?></span>
                            <?php foreach ($group['items'] as $item): ?>
                                <a class="nav-menu-link"
                                   href="<?= e(repdoc_url($item['url'])) ?>"<?= $current === $item['key'] ? ' aria-current="page"' : '' ?>><?= e($pick($item['label_bn'], $item['label_en'])) ?></a>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                    <a class="nav-menu-all" href="<?= e(repdoc_url('/')) ?>#tools"><?= $pick('সব টুল দেখুন', 'See all tools') ?></a>
                </div>
            </details>
            <a class="nav-link" href="<?= e(repdoc_url('/')) ?>#featured"><?= $pick('জনপ্রিয়', 'Popular') ?></a>
            <a class="nav-link" href="<?= e(repdoc_url('/')) ?>#how"><?= $pick('কীভাবে কাজ করে', 'How it works') ?></a>
            <a class="nav-link" href="<?= e(repdoc_url('/')) ?>#about"><?= $pick('সম্পর্কে', 'About') ?></a>
        </nav>
        <script>
            (function () {
                var menus = document.querySelectorAll('.nav-dropdown');
                if (!menus.length) return;
                document.addEventListener('click', function (event) {
                    menus.forEach(function (menu) {
                        if (!menu.contains(event.target)) menu.removeAttribute('open');
                    });
                });
                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape') {
                        menus.forEach(function (menu) { menu.removeAttribute('open'); });
                    }
                });
            })();
        </script>
        <?php
    }
}
