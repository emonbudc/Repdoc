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
                    ['key' => 'death',     'label_bn' => 'মৃত্যু নিবন্ধন',           'label_en' => 'Death Certificate',  'url' => 'genaretor/death/'],
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

if (!function_exists('repdoc_render_tool_header')) {
    function repdoc_render_tool_header(string $current, string $subtitle, int $balance, bool $isAdmin, string $balanceTitle = 'ক্রেডিট ব্যালেন্স'): void
    {
        ?>
        <header class="site-header">
            <div class="container header-inner">
                <a class="brand" href="<?= e(repdoc_url('/')) ?>" aria-label="Repdoc — হোম">
                    <img class="brand-logo" src="<?= e(repdoc_url('src/assets/image/logo.png')) ?>" alt="" width="34" height="34">
                    <span class="brand-text">
                        <strong>Repdoc</strong>
                        <small><?= e($subtitle) ?></small>
                    </span>
                </a>

                <?php repdoc_render_site_nav($current); ?>

                <div class="header-actions">
                    <button type="button" class="icon-btn theme-toggle" id="themeToggle" onclick="toggleTheme()"
                            aria-label="থিম পরিবর্তন" title="থিম পরিবর্তন">
                        <svg class="icon icon-sun" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-sun"/></svg>
                        <svg class="icon icon-moon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-moon"/></svg>
                        <span class="sr-only" id="themeToggleLabel">Dark</span>
                    </button>

                    <span class="user-chip" title="<?= e($balanceTitle) ?>">
                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-wallet"/></svg>
                        <span class="balance"><?= (int) $balance ?></span>
                    </span>

                    <?php if ($isAdmin): ?>
                        <a class="btn btn-secondary btn-login" href="<?= e(repdoc_url('admin/')) ?>">
                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-settings"/></svg>
                            <span>অ্যাডমিন</span>
                        </a>
                    <?php endif; ?>

                    <a class="btn btn-primary btn-login" href="<?= e(repdoc_url('public/logout.php')) ?>" aria-label="লগআউট">
                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-logout"/></svg>
                        <span>লগআউট</span>
                    </a>

                    <button type="button" class="icon-btn nav-toggle" onclick="toggleNav()"
                            aria-label="মেনু খুলুন" aria-expanded="false" aria-controls="siteNav">
                        <svg class="icon nav-toggle-open" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-menu"/></svg>
                        <svg class="icon nav-toggle-close" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
                    </button>
                </div>
            </div>
        </header>
        <?php
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
            <a class="nav-link" href="<?= e(repdoc_url('sonod/')) ?>"><?= $pick('সনদ গাইড', 'Certificate guides') ?></a>
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
