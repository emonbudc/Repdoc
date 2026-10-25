<?php
declare(strict_types=1);

/**
 * Repdoc — shared file-library helpers and chrome.
 *
 * Used by the three public catalogue pages (showcase/, officefiles/,
 * offlinefiles/) so they share one visual language with the homepage:
 * Counter-Glass tokens, stamped header, mint footer, ledger buttons.
 *
 * Include after src/bootstrap.php and src/filemeta.php.
 */

if (!function_exists('repdoc_catalog_logo_url')) {
    /** Official software logo for a format (or the Office group). */
    function repdoc_catalog_logo_url(string $type): string
    {
        static $files = [
            'office' => 'microsoft-office.svg',
            'word'   => 'word.svg',
            'excel'  => 'excel.svg',
            'ppt'    => 'powerpoint.svg',
            'psd'    => 'photoshop.svg',
            'ai'     => 'illustrator.svg',
            'plp'    => 'pixellab.png',
        ];
        $file = $files[$type] ?? $files['office'];
        return repdoc_url('src/assets/image/file-logos/' . $file);
    }
}

if (!function_exists('repdoc_catalog_logo_img')) {
    function repdoc_catalog_logo_img(string $type, int $size = 48, string $class = ''): string
    {
        $class = trim($class);
        return '<img src="' . e(repdoc_catalog_logo_url($type)) . '" alt="" width="' . $size . '" height="' . $size . '"'
            . ' loading="lazy" decoding="async"' . ($class !== '' ? ' class="' . e($class) . '"' : '') . '>';
    }
}

if (!function_exists('repdoc_catalog_logo_icon')) {
    function repdoc_catalog_logo_icon(string $type, string $class = '', string $soft = '#F2FBF5'): string
    {
        $class = trim('cat-format-logo ' . $class);
        return '<span class="' . e($class) . '" style="background:' . e($soft) . '" aria-hidden="true">'
            . repdoc_catalog_logo_img($type, 40)
            . '</span>';
    }
}

if (!function_exists('repdoc_catalog_human_size')) {
    function repdoc_catalog_human_size(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1024 * 1024) return number_format($bytes / 1024, 1) . ' KB';
        if ($bytes < 1024 * 1024 * 1024) return number_format($bytes / (1024 * 1024), 1) . ' MB';
        return number_format($bytes / (1024 * 1024 * 1024), 2) . ' GB';
    }
}

if (!function_exists('repdoc_catalog_bn_digits')) {
    function repdoc_catalog_bn_digits(string $value): string
    {
        return strtr($value, ['0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪', '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯']);
    }
}

if (!function_exists('repdoc_catalog_date')) {
    function repdoc_catalog_date(int $timestamp): string
    {
        if ($timestamp <= 0) return '—';
        $months = [1 => 'জানু', 2 => 'ফেব', 3 => 'মার্চ', 4 => 'এপ্রিল', 5 => 'মে', 6 => 'জুন', 7 => 'জুলাই', 8 => 'আগস্ট', 9 => 'সেপ্ট', 10 => 'অক্টো', 11 => 'নভে', 12 => 'ডিসে'];
        return repdoc_catalog_bn_digits(date('d', $timestamp)) . ' ' . $months[(int) date('n', $timestamp)] . ' ' . repdoc_catalog_bn_digits(date('Y', $timestamp));
    }
}

if (!function_exists('repdoc_catalog_title')) {
    function repdoc_catalog_title(string $name): string
    {
        $stem = (string) pathinfo($name, PATHINFO_FILENAME);
        $stem = str_replace(['-', '_'], ' ', $stem);
        $stem = (string) preg_replace('/\s+/u', ' ', trim($stem));
        if ($stem !== '' && preg_match('/^[\x20-\x7E]+$/', $stem)) {
            $words = explode(' ', $stem);
            foreach ($words as $i => $word) {
                if ($word !== '' && !preg_match('/^[A-Z0-9]{2,}$/', $word)) {
                    $words[$i] = mb_convert_case($word, MB_CASE_TITLE, 'UTF-8');
                }
            }
            $stem = implode(' ', $words);
        }
        return $stem !== '' ? $stem : $name;
    }
}

if (!function_exists('repdoc_catalog_search_index')) {
    function repdoc_catalog_search_index(array $file, array $formats, array $groups): string
    {
        $meta = $formats[$file['type']] ?? [];
        $group = $groups[$meta['group'] ?? ''] ?? [];
        return mb_strtolower(
            $file['name'] . ' ' . repdoc_catalog_title($file['name']) . ' ' . $file['type'] . ' ' . $file['ext'] . ' '
            . ($meta['label'] ?? '') . ' ' . ($meta['full'] ?? '') . ' ' . ($group['label'] ?? '') . ' '
            . strip_tags((string) ($file['description'] ?? '')),
            'UTF-8'
        );
    }
}

if (!function_exists('repdoc_catalog_preview_art')) {
    /** Generated preview when no uploaded thumbnail exists yet. */
    function repdoc_catalog_preview_art(string $type): string
    {
        $logo = e(repdoc_catalog_logo_url($type));
        $chip = '<img class="oc-chip oc-chip-logo" src="' . $logo . '" alt="" width="24" height="24" loading="lazy" decoding="async">';
        $stamp = '<img class="oc-brand-stamp" src="' . $logo . '" alt="" width="38" height="38" loading="lazy" decoding="async">';

        switch ($type) {
            case 'word':
                return '<span aria-hidden="true" class="oc-art art-word">'
                    . '<span class="oc-paper paper-word">'
                    . $chip
                    . '<span class="oc-line" style="width:88%"></span>'
                    . '<span class="oc-line" style="width:66%"></span>'
                    . '<span class="oc-line" style="width:78%"></span>'
                    . '<span class="oc-line" style="width:72%"></span>'
                    . '<span class="oc-line" style="width:44%"></span>'
                    . '</span></span>';
            case 'excel':
                return '<span aria-hidden="true" class="oc-art art-excel">'
                    . '<span class="oc-paper paper-excel">'
                    . $chip
                    . '<span class="oc-sheet-grid">'
                    . str_repeat('<i></i>', 15)
                    . '</span></span></span>';
            case 'ppt':
                return '<span aria-hidden="true" class="oc-art art-ppt">'
                    . '<span class="oc-paper paper-ppt">'
                    . $chip
                    . '<span class="oc-slide"><span class="oc-slide-title"></span><span class="oc-slide-line"></span><span class="oc-slide-line short"></span></span>'
                    . '<span class="oc-slide-dots"><i></i><i></i><i></i></span>'
                    . '</span></span>';
            case 'psd':
                return '<span aria-hidden="true" class="oc-art art-psd">'
                    . $stamp
                    . '<span class="oc-visit visit-back"></span>'
                    . '<span class="oc-visit visit-front"><strong>BUSINESS<br>CARD</strong><small>YOUR BRAND</small></span>'
                    . '</span>';
            case 'ai':
                return '<span aria-hidden="true" class="oc-art art-ai">'
                    . $stamp
                    . '<span class="oc-brandsheet"><span class="oc-orbit"></span><strong>BRAND<br>LOGO</strong><small>DESIGN AGENCY</small></span>'
                    . '</span>';
            default:
                return '<span aria-hidden="true" class="oc-art art-plp">'
                    . $stamp
                    . '<span class="oc-poster"><span class="oc-poster-kicker">পোস্টার</span><strong>নবাবর্ষ</strong></span>'
                    . '</span>';
        }
    }
}

if (!function_exists('repdoc_catalog_scan')) {
    /** Scan one format folder; returns newest-first file rows. */
    function repdoc_catalog_scan(string $dir, string $type, array $exts): array
    {
        $out = [];
        if (!is_dir($dir)) return $out;
        foreach (@scandir($dir) ?: [] as $name) {
            if ($name === '.' || $name === '..') continue;
            if (str_starts_with($name, '.')) continue;
            $ext = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, $exts, true)) continue;
            $path = $dir . DIRECTORY_SEPARATOR . $name;
            if (!is_file($path)) continue;
            $out[] = [
                'name'  => $name,
                'type'  => $type,
                'ext'   => $ext,
                'size'  => (int) @filesize($path),
                'mtime' => (int) @filemtime($path),
            ];
        }
        usort($out, static fn(array $a, array $b): int => $b['mtime'] <=> $a['mtime']);
        return $out;
    }
}

if (!function_exists('repdoc_catalog_sprite')) {
    function repdoc_catalog_sprite(): void
    {
        ?>
        <svg class="sprite" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <g id="cat-i-search"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></g>
                <g id="cat-i-arrow"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></g>
                <g id="cat-i-chevron"><polyline points="6 9 12 15 18 9"/></g>
                <g id="cat-i-download"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></g>
                <g id="cat-i-lock"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></g>
                <g id="cat-i-check"><polyline points="20 6 9 17 4 12"/></g>
                <g id="cat-i-file"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></g>
                <g id="cat-i-grid"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></g>
                <g id="cat-i-menu"><line x1="4" y1="7" x2="20" y2="7"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="17" x2="20" y2="17"/></g>
                <g id="cat-i-close"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></g>
                <g id="cat-i-shield"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></g>
                <g id="cat-i-mobile"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></g>
                <g id="cat-i-bolt"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></g>
                <g id="cat-i-link"><path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/></g>
                <g id="cat-i-send"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4z"/></g>
                <g id="cat-i-wallet"><path d="M3 7.5A2.5 2.5 0 0 1 5.5 5h11A2.5 2.5 0 0 1 19 7.5"/><rect x="3" y="7.5" width="18" height="12" rx="2"/><circle cx="16.5" cy="13.5" r="1.2"/></g>
                <g id="cat-i-login"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></g>
                <g id="cat-i-external"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></g>
                <g id="cat-i-clock"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 16 14"/></g>
                <g id="cat-i-folder"><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></g>
                <g id="cat-i-user"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></g>
            </defs>
        </svg>
        <?php
    }
}

if (!function_exists('repdoc_catalog_header')) {
    function repdoc_catalog_header(array $ctx): void
    {
        $home      = (string) ($ctx['home'] ?? repdoc_url('/'));
        $login     = (string) ($ctx['login'] ?? repdoc_url('public/login/'));
        $apply     = (string) ($ctx['apply'] ?? repdoc_url('Entrepreneur/apply/'));
        $dashboard = (string) ($ctx['dashboard'] ?? $login);
        $user      = $ctx['user'] ?? null;
        $balance   = (int) ($ctx['balance'] ?? 0);
        $active    = (string) ($ctx['active'] ?? '');
        $showcase  = repdoc_url('showcase/');
        $office    = repdoc_url('officefiles/');
        $offline   = repdoc_url('offlinefiles/');
        $important = repdoc_url('importantlinks/');
        $guide     = $active === 'importantlinks' ? 'লিংক গাইড' : 'ডাউনলোড গাইড';
        $current   = static fn(string $key): string => $active === $key ? ' aria-current="page"' : '';
        ?>
        <header class="site-header" id="top">
            <div class="container header-inner">
                <a class="brand brand-ledger" href="<?= e($home) ?>" aria-label="Repdoc — হোম">
                    <img class="brand-logo" src="<?= e(repdoc_url('src/assets/image/logo.png')) ?>" alt="" width="120" height="40" decoding="async">
                    <span class="brand-word"><strong>Repdoc</strong><small>ফাইল লাইব্রেরি · অপারেটর টুলকিট</small></span>
                </a>

                <nav class="site-nav" id="siteNav" aria-label="প্রধান নেভিগেশন">
                    <a class="nav-link" href="<?= e($home) ?>">হোম</a>
                    <details class="nav-dropdown">
                        <summary class="nav-link">টেমপ্লেট</summary>
                        <div class="nav-dropdown-menu cat-nav-menu">
                            <a class="nav-menu-link" href="<?= e($showcase) ?>"<?= $current('showcase') ?>>সব টেমপ্লেট শোকেস</a>
                            <a class="nav-menu-link" href="<?= e($office) ?>"<?= $current('officefiles') ?>>MS Office ফাইল</a>
                            <a class="nav-menu-link" href="<?= e($offline) ?>"<?= $current('offlinefiles') ?>>PSD / AI / PLP ফাইল</a>
                            <a class="nav-menu-all" href="<?= e($home) ?>#tools">সব টুল দেখুন</a>
                        </div>
                    </details>
                    <a class="nav-link" href="#library">ফাইল লাইব্রেরি</a>
                    <a class="nav-link" href="<?= e($important) ?>"<?= $current('importantlinks') ?>>গুরুত্বপূর্ণ লিংক</a>
                    <a class="nav-link" href="#guide"><?= e($guide) ?></a>
                    <a class="nav-link" href="<?= e($home) ?>#about">সম্পর্কে</a>
                </nav>

                <div class="header-actions">
                    <button type="button" class="icon-btn" id="catSearchJump" aria-label="ফাইল খুঁজুন">
                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#cat-i-search"/></svg>
                    </button>
                    <?php if ($user !== null): ?>
                        <span class="user-chip" title="ব্যালেন্স">
                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#cat-i-wallet"/></svg>
                            <span class="balance"><?= (int) $balance ?></span>
                            <span class="sr-only">ক্রেডিট ব্যালেন্স</span>
                        </span>
                        <a class="btn btn-primary btn-login" href="<?= e($dashboard) ?>">
                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#cat-i-external"/></svg>
                            <span>ড্যাশবোর্ড</span>
                        </a>
                    <?php else: ?>
                        <a class="btn btn-primary btn-login" href="<?= e($login) ?>" aria-label="লগ ইন">
                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#cat-i-login"/></svg>
                            <span>লগ ইন</span>
                        </a>
                    <?php endif; ?>
                    <button type="button" class="icon-btn nav-toggle" id="navToggle"
                            aria-label="মেনু খুলুন" aria-controls="siteNav" aria-expanded="false">
                        <svg class="icon nav-toggle-open" viewBox="0 0 24 24" aria-hidden="true"><use href="#cat-i-menu"/></svg>
                        <svg class="icon nav-toggle-close" viewBox="0 0 24 24" aria-hidden="true"><use href="#cat-i-close"/></svg>
                    </button>
                </div>
            </div>
        </header>
        <?php
    }
}

if (!function_exists('repdoc_catalog_footer')) {
    function repdoc_catalog_footer(array $ctx): void
    {
        $home   = (string) ($ctx['home'] ?? repdoc_url('/'));
        $login  = (string) ($ctx['login'] ?? repdoc_url('public/login/'));
        $apply  = (string) ($ctx['apply'] ?? repdoc_url('Entrepreneur/apply/'));
        $year   = date('Y');
        ?>
        <footer class="site-footer footer-mint">
            <div class="container footer-grid footer-grid--5">
                <div class="footer-brand">
                    <a class="brand footer-brandmark" href="<?= e($home) ?>" aria-label="Repdoc — হোম">
                        <img class="brand-logo" src="<?= e(repdoc_url('src/assets/image/logo.png')) ?>" alt="Repdoc" width="132" height="44" loading="lazy" decoding="async">
                    </a>
                    <p class="footer-tagline">বাংলাদেশ · অপারেটর ডকুমেন্ট টুলকিট</p>
                    <p class="footer-about">ফ্রি ডকুমেন্ট টেমপ্লেট ও ফরম — Word, Excel, PDF, PSD, AI, PLP আকারে। এক অ্যাকাউন্টেই সব টুল।</p>
                    <p class="footer-legal">স্বতন্ত্র টুলস সাইট — সরকারি পোর্টাল নয়।</p>
                    <ul class="social-links social-links--icons" aria-label="সোশ্যাল মিডিয়া">
                        <li><a href="https://github.com/Repdoc-BD" target="_blank" rel="noopener" aria-label="Repdoc on GitHub">
                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 19c-4 1.5-4-2.5-6-3m12 5v-3.5c0-1 .1-1.4-.5-2 2.8-.3 5.5-1.4 5.5-6a4.6 4.6 0 0 0-1.3-3.2 4.2 4.2 0 0 0-.1-3.2s-1.1-.3-3.5 1.3a12 12 0 0 0-6.2 0C6.5 2.8 5.4 3.1 5.4 3.1a4.2 4.2 0 0 0-.1 3.2A4.6 4.6 0 0 0 4 9.5c0 4.6 2.7 5.7 5.5 6-.6.6-.6 1.2-.5 2V21"/></svg>
                        </a></li>
                        <li><a href="https://www.facebook.com/Repdocbdofficial" target="_blank" rel="noopener" aria-label="Repdoc on Facebook">
                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                        </a></li>
                        <li><a href="https://www.youtube.com/@Repdocbdofficial" target="_blank" rel="noopener" aria-label="Repdoc on YouTube">
                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M22.5 7.2a2.8 2.8 0 0 0-2-2C18.7 4.7 12 4.7 12 4.7s-6.7 0-8.5.5a2.8 2.8 0 0 0-2 2A29 29 0 0 0 1 12a29 29 0 0 0 .5 4.8 2.8 2.8 0 0 0 2 2c1.8.5 8.5.5 8.5.5s6.7 0 8.5-.5a2.8 2.8 0 0 0 2-2A29 29 0 0 0 23 12a29 29 0 0 0-.5-4.8z"/><polygon points="10 15 15 12 10 9"/></svg>
                        </a></li>
                        <li><a href="https://x.com/Repdocbdofficial" target="_blank" rel="noopener" aria-label="Repdoc on X">
                            <svg class="icon icon-brand" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" stroke="none" d="M17.5 3h3.1l-6.8 7.8L21.8 21h-6.3l-4.9-6.4L5 21H1.9l7.3-8.3L1.6 3h6.4l4.4 5.9L17.5 3zm-1.1 16.1h1.7L7.2 4.8H5.4l11 14.3z"/></svg>
                        </a></li>
                        <li><a href="https://t.me/Repdocbdofficial" target="_blank" rel="noopener" aria-label="Repdoc on Telegram">
                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M22 3 2 10.4l6.1 2.3L10.4 19l4-4.6 5.1 3.8z"/><path d="m8.1 12.7 9.9-7.3-7.6 8.4"/></svg>
                        </a></li>
                    </ul>
                </div>

                <nav class="footer-col" aria-label="দ্রুত লিংক">
                    <h3 class="footer-head">
                        <span class="footer-head-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#cat-i-link"/></svg></span>
                        দ্রুত লিংক
                    </h3>
                    <ul>
                        <li><a href="<?= e($home) ?>">হোম পেজ</a></li>
                        <li><a href="<?= e(repdoc_url('showcase/')) ?>">টেমপ্লেট শোকেস</a></li>
                        <li><a href="<?= e(repdoc_url('officefiles/')) ?>">MS Office ফাইল</a></li>
                        <li><a href="<?= e(repdoc_url('offlinefiles/')) ?>">PSD / AI / PLP ফাইল</a></li>
                        <li><a href="<?= e($home) ?>#how">কীভাবে কাজ করে</a></li>
                    </ul>
                </nav>

                <nav class="footer-col" aria-label="সেবাসমূহ">
                    <h3 class="footer-head">
                        <span class="footer-head-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#cat-i-file"/></svg></span>
                        সেবাসমূহ
                    </h3>
                    <ul>
                        <li><a href="<?= e(repdoc_url('genaretor/cvmaker/')) ?>">সিভি মেকার</a></li>
                        <li><a href="<?= e(repdoc_url('genaretor/prottoyon/')) ?>">প্রত্যয়ন টেমপ্লেট</a></li>
                        <li><a href="<?= e(repdoc_url('genaretor/certificate/')) ?>">সনদ টেমপ্লেট</a></li>
                        <li><a href="<?= e(repdoc_url('importantlinks/')) ?>">গুরুত্বপূর্ণ লিংক</a></li>
                        <li><a href="<?= e($home) ?>#tools">সব টুলস</a></li>
                    </ul>
                </nav>

                <nav class="footer-col" aria-label="সহায়তা">
                    <h3 class="footer-head">
                        <span class="footer-head-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#cat-i-shield"/></svg></span>
                        সহায়তা
                    </h3>
                    <ul>
                        <li><a href="#guide">ডাউনলোড গাইড</a></li>
                        <li><a href="<?= e(repdoc_url('privacy/')) ?>">গোপনীয়তা নীতি</a></li>
                        <li><a href="<?= e($apply) ?>">অ্যাকাউন্টের জন্য আবেদন</a></li>
                        <li><a href="<?= e($login) ?>">লগ ইন</a></li>
                        <li><a href="<?= e($home) ?>#about">আমাদের সম্পর্কে</a></li>
                    </ul>
                </nav>

                <div class="footer-col footer-contact">
                    <h3 class="footer-head">
                        <span class="footer-head-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#cat-i-send"/></svg></span>
                        যোগাযোগ
                    </h3>
                    <ul>
                        <li><a href="https://t.me/Repdocbdofficial" target="_blank" rel="noopener">
                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M22 3 2 10.4l6.1 2.3L10.4 19l4-4.6 5.1 3.8z"/><path d="m8.1 12.7 9.9-7.3-7.6 8.4"/></svg>
                            <span>টেলিগ্রাম সাপোর্ট</span>
                        </a></li>
                        <li><a href="https://www.facebook.com/Repdocbdofficial" target="_blank" rel="noopener">
                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                            <span>ফেসবুক পেজ</span>
                        </a></li>
                        <li><a href="https://github.com/Repdoc-BD" target="_blank" rel="noopener">
                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 19c-4 1.5-4-2.5-6-3m12 5v-3.5c0-1 .1-1.4-.5-2 2.8-.3 5.5-1.4 5.5-6a4.6 4.6 0 0 0-1.3-3.2 4.2 4.2 0 0 0-.1-3.2s-1.1-.3-3.5 1.3a12 12 0 0 0-6.2 0C6.5 2.8 5.4 3.1 5.4 3.1a4.2 4.2 0 0 0-.1 3.2A4.6 4.6 0 0 0 4 9.5c0 4.6 2.7 5.7 5.5 6-.6.6-.6 1.2-.5 2V21"/></svg>
                            <span>গিটহাব রিপো</span>
                        </a></li>
                        <li><a href="https://www.youtube.com/@Repdocbdofficial" target="_blank" rel="noopener">
                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M22.5 7.2a2.8 2.8 0 0 0-2-2C18.7 4.7 12 4.7 12 4.7s-6.7 0-8.5.5a2.8 2.8 0 0 0-2 2A29 29 0 0 0 1 12a29 29 0 0 0 .5 4.8 2.8 2.8 0 0 0 2 2c1.8.5 8.5.5 8.5.5s6.7 0 8.5-.5a2.8 2.8 0 0 0 2-2A29 29 0 0 0 23 12a29 29 0 0 0-.5-4.8z"/><polygon points="10 15 15 12 10 9"/></svg>
                            <span>ইউটিউব চ্যানেল</span>
                        </a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-base">
                <div class="container footer-base-inner">
                    <p>&copy; <?= e($year) ?> Repdoc. সর্বস্বত্ব সংরক্ষিত — বাংলাদেশ থেকে।</p>
                    <a class="back-top" href="#top">
                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><polyline points="6 15 12 9 18 15"/></svg>
                        <span>উপরে যান</span>
                    </a>
                </div>
            </div>
        </footer>
        <?php
    }
}

if (!function_exists('repdoc_catalog_render')) {
    /**
     * Render the whole catalogue body: hero, format filters, file grid,
     * sidebar, download guide, closing CTA and footer.
     */
    function repdoc_catalog_render(array $args): void
    {
        $files      = $args['files'] ?? [];
        $formats    = $args['formats'] ?? [];
        $groups     = $args['groups'] ?? [];
        $counts     = $args['counts'] ?? ['all' => count($files)];
        $typeCounts = $args['type_counts'] ?? [];
        $hero       = $args['hero'] ?? [];
        $guide      = $args['guide'] ?? [];
        $active     = (string) ($args['active'] ?? '');
        $home       = (string) ($args['home'] ?? repdoc_url('/'));
        $login      = (string) ($args['login'] ?? repdoc_url('public/login/'));
        $apply      = (string) ($args['apply'] ?? repdoc_url('Entrepreneur/apply/'));
        $dashboard  = (string) ($args['dashboard'] ?? $login);
        $user       = $args['user'] ?? null;
        $downloads  = !empty($args['downloads']);
        $filterMode = (string) ($args['filter_mode'] ?? 'group');
        $nextPath   = (string) ($args['next'] ?? '/' . trim($active, '/') . '/');

        $bn          = 'repdoc_catalog_bn_digits';
        $totalFiles  = count($files);
        $totalBytes  = array_sum(array_map(static fn(array $f): int => (int) ($f['size'] ?? 0), $files));
        $lastUpdated = $files !== [] ? max(array_map(static fn(array $f): int => (int) ($f['mtime'] ?? 0), $files)) : 0;
        $sizeLabel   = $totalBytes > 0 ? repdoc_catalog_human_size($totalBytes) : '০ B';

        $filterRows = [];
        if ($filterMode === 'type') {
            foreach ($formats as $key => $meta) {
                $filterRows[] = [
                    'key'   => (string) $key,
                    'label' => (string) ($meta['label'] ?? $key),
                    'sub'   => (string) ($meta['full'] ?? ''),
                    'logo'  => (string) $key,
                    'soft'  => (string) ($meta['soft'] ?? '#F2FBF5'),
                    'count' => (int) ($typeCounts[$key] ?? 0),
                ];
            }
        } else {
            $softByGroup = [];
            foreach ($formats as $formatMeta) {
                $groupKey = (string) ($formatMeta['group'] ?? '');
                if ($groupKey !== '' && !isset($softByGroup[$groupKey])) {
                    $softByGroup[$groupKey] = (string) ($formatMeta['soft'] ?? '#F2FBF5');
                }
            }
            foreach ($groups as $key => $meta) {
                $filterRows[] = [
                    'key'   => (string) $key,
                    'label' => (string) ($meta['label'] ?? $key),
                    'sub'   => (string) ($meta['sub'] ?? ''),
                    'logo'  => $key === 'office' ? 'office' : (string) $key,
                    'soft'  => (string) ($softByGroup[$key] ?? '#F2FBF5'),
                    'count' => (int) ($counts[$key] ?? 0),
                ];
            }
        }

        $formatCount = count($formats);
        $heroTitle   = (string) ($hero['title'] ?? 'ফাইল লাইব্রেরি');
        $heroAccent  = (string) ($hero['accent'] ?? '');
        $heroLead    = (string) ($hero['lead'] ?? '');
        $heroImage   = (string) ($hero['image'] ?? repdoc_url('src/assets/image/officefiles-hero.png'));
        $heroAlt     = (string) ($hero['image_alt'] ?? 'Repdoc ফাইল লাইব্রেরি');
        $heroCaption = (string) ($hero['image_caption'] ?? 'রেডি-টু-ইউজ টেমপ্লেট');
        $searchHint  = (string) ($hero['search_placeholder'] ?? 'ফাইলের নাম, ফরম্যাট বা কীওয়ার্ড লিখুন…');
        $kicker      = (string) ($hero['kicker'] ?? 'ফাইল লাইব্রেরি');
        $kickerNo    = (string) ($hero['kicker_no'] ?? 'Repdoc');

        $emptyTitle = (string) ($args['empty_title'] ?? 'ফাইল কালেকশন প্রস্তুত হচ্ছে');
        $emptyText  = (string) ($args['empty_text'] ?? 'খুব শীঘ্রই নতুন টেমপ্লেট এখানে যুক্ত হবে। অ্যাকাউন্ট নিলে নতুন ফাইল প্রকাশের সাথে সাথেই ডাউনলোড করতে পারবেন।');

        repdoc_catalog_sprite();
        repdoc_catalog_header([
            'home'      => $home,
            'login'     => $login,
            'apply'     => $apply,
            'dashboard' => $dashboard,
            'user'      => $user,
            'balance'   => $args['balance'] ?? 0,
            'active'    => $active,
        ]);
        ?>
        <main>
            <section class="cat-hero" aria-labelledby="catHeroTitle">
                <div class="container cat-hero-inner">
                    <div class="cat-hero-copy">
                        <p class="reg-kicker cat-kicker"><span class="reg-no" aria-hidden="true"><?= e($kickerNo) ?></span><?= e($kicker) ?></p>
                        <h1 class="reg-title cat-title" id="catHeroTitle"><?= e($heroTitle) ?><?php if ($heroAccent !== ''): ?><span class="reg-title-accent"><?= e($heroAccent) ?></span><?php endif; ?></h1>
                        <span class="reg-rule cat-rule" aria-hidden="true"></span>
                        <?php if ($heroLead !== ''): ?><p class="cat-lead"><?= e($heroLead) ?></p><?php endif; ?>

                        <form class="cat-search" id="catSearchForm" role="search">
                            <svg class="icon cat-search-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#cat-i-search"/></svg>
                            <label class="sr-only" for="catFileSearch">ফাইলের নাম, ক্যাটাগরি বা কীওয়ার্ড লিখুন</label>
                            <input id="catFileSearch" type="search" placeholder="<?= e($searchHint) ?>" autocomplete="off" enterkeyhint="search">
                            <button type="button" class="cat-search-clear" id="catSearchClear" aria-label="সার্চ মুছুন" hidden>
                                <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#cat-i-close"/></svg>
                            </button>
                            <button type="submit" class="btn btn-ledger cat-search-btn">খুঁজুন</button>
                        </form>

                        <div class="cat-hero-stats" aria-label="লাইব্রেরির সারসংক্ষেপ">
                            <div class="cat-hero-stat"><strong><?= e($bn((string) $totalFiles)) ?></strong><span>ফাইল</span></div>
                            <div class="cat-hero-stat"><strong><?= e($bn((string) $formatCount)) ?></strong><span>ফরম্যাট</span></div>
                            <div class="cat-hero-stat"><strong><?= e($bn($sizeLabel)) ?></strong><span>মোট সাইজ</span></div>
                        </div>

                        <?php if ($user === null): ?>
                            <p class="cat-hero-note">
                                <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#cat-i-shield"/></svg>
                                <?= $downloads ? 'ডাউনলোড করতে' : 'ডাউনলোডের জন্য' ?> <a href="<?= e($login) ?>">লগ ইন</a> করুন — এক অ্যাকাউন্টেই সব টুল।
                            </p>
                        <?php endif; ?>
                    </div>

                    <figure class="cat-hero-art">
                        <span class="cat-art-frame">
                            <img src="<?= e($heroImage) ?>" alt="<?= e($heroAlt) ?>" width="600" height="378" fetchpriority="high" decoding="async">
                        </span>
                        <figcaption class="cat-art-tag"><?= e($heroCaption) ?></figcaption>
                    </figure>
                </div>
            </section>

            <section class="cat-formats" aria-labelledby="catFormatsHeading">
                <h2 class="sr-only" id="catFormatsHeading">ফরম্যাট বেছে নিন</h2>
                <div class="container cat-format-grid">
                    <button type="button" class="cat-format is-active" data-filter="all" aria-pressed="true"<?= $totalFiles === 0 ? ' disabled' : '' ?>>
                        <span class="cat-format-logo cat-format-all" aria-hidden="true">
                            <svg class="icon" viewBox="0 0 24 24"><use href="#cat-i-grid"/></svg>
                        </span>
                        <span class="cat-format-text"><strong>সব ফাইল</strong><small>সব ফরম্যাট</small></span>
                        <span class="cat-format-count"><?= e($bn((string) $totalFiles)) ?></span>
                    </button>
                    <?php foreach ($filterRows as $row): ?>
                        <button type="button" class="cat-format" data-filter="<?= e($row['key']) ?>" aria-pressed="false"<?= $totalFiles === 0 ? ' disabled' : '' ?>>
                            <?= repdoc_catalog_logo_icon($row['logo'], '', $row['soft']) ?>
                            <span class="cat-format-text"><strong><?= e($row['label']) ?></strong><small><?= e($row['sub']) ?></small></span>
                            <span class="cat-format-count"><?= e($bn((string) $row['count'])) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="cat-library" id="library" aria-labelledby="catLibraryHeading">
                <div class="container cat-layout">
                    <div class="cat-main">
                        <div class="cat-toolbar">
                            <div>
                                <p class="kicker">ডিজিটাল আর্কাইভ</p>
                                <h2 id="catLibraryHeading"><?= e((string) ($args['heading'] ?? 'সব ফাইল')) ?></h2>
                                <p class="cat-result" id="catResult" role="status" aria-live="polite">
                                    <strong id="catResultCount"><?= e($bn((string) $totalFiles)) ?></strong>টি ফাইল দেখানো হচ্ছে
                                </p>
                            </div>
                            <div class="cat-toolbar-actions">
                                <label class="cat-sort" for="catSort">
                                    <span>সাজান</span>
                                    <select id="catSort"<?= $totalFiles === 0 ? ' disabled' : '' ?>>
                                        <option value="new">নতুন আগে</option>
                                        <option value="old">পুরোনো আগে</option>
                                        <option value="name">নাম অনুযায়ী</option>
                                        <option value="size">সাইজ অনুযায়ী</option>
                                    </select>
                                </label>
                                <button type="button" class="cat-reset" id="catReset" hidden>
                                    <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#cat-i-close"/></svg>
                                    রিসেট
                                </button>
                            </div>
                        </div>

                        <?php if ($files === []): ?>
                            <div class="cat-empty" role="status">
                                <div class="cat-empty-logos" aria-hidden="true">
                                    <?= repdoc_catalog_logo_icon('office', '', '#FFF6EC') ?>
                                    <?= repdoc_catalog_logo_icon('psd', '', '#EEF4FE') ?>
                                    <?= repdoc_catalog_logo_icon('plp', '', '#F5F0FE') ?>
                                </div>
                                <h3><?= e($emptyTitle) ?></h3>
                                <p><?= e($emptyText) ?></p>
                                <div class="cat-empty-actions">
                                    <a class="btn btn-primary" href="<?= e($apply) ?>">অ্যাকাউন্টের জন্য আবেদন</a>
                                    <a class="btn btn-secondary" href="<?= e($home) ?>#tools">সব টুল দেখুন</a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="cat-file-grid" id="catFileGrid">
                                <?php foreach ($files as $i => $f):
                                    $meta = $formats[$f['type']] ?? [];
                                    $group = (string) ($meta['group'] ?? $f['type']);
                                    $isNew = (int) $f['mtime'] > 0 && (int) $f['mtime'] > time() - 14 * 86400;
                                    $title = repdoc_catalog_title((string) $f['name']);

                                    if ($downloads) {
                                        if ($user === null) {
                                            $actionUrl = $login . '?next=' . rawurlencode($nextPath);
                                            $actionLabel = 'লগইন করে ডাউনলোড';
                                            $actionIcon = 'cat-i-lock';
                                            $actionClass = ' is-locked';
                                        } else {
                                            $actionUrl = repdoc_url($meta['hub'] . '/download.php') . '?type=' . rawurlencode((string) $f['type']) . '&file=' . rawurlencode((string) $f['name']);
                                            $actionLabel = 'ডাউনলোড করুন';
                                            $actionIcon = 'cat-i-download';
                                            $actionClass = '';
                                        }
                                    } else {
                                        $actionUrl = repdoc_url($meta['hub'] . '/');
                                        $actionLabel = 'হাব খুলুন';
                                        $actionIcon = 'cat-i-arrow';
                                        $actionClass = ' is-hub';
                                    }
                                ?>
                                    <article class="cat-file-card"
                                             data-type="<?= e((string) $f['type']) ?>"
                                             data-group="<?= e($group) ?>"
                                             data-search="<?= e((string) $f['search']) ?>"
                                             data-name="<?= e(mb_strtolower($title, 'UTF-8')) ?>"
                                             data-size="<?= (int) $f['size'] ?>"
                                             data-mtime="<?= (int) $f['mtime'] ?>"
                                             style="--i:<?= (int) ($i % 12) ?>">
                                        <div class="cat-file-thumb">
                                            <?php if (!empty($f['thumb'])): ?>
                                                <img class="cat-thumb-img" src="<?= e($f['thumb']) ?>" alt="<?= e($title) ?> — প্রিভিউ" width="480" height="360" loading="lazy" decoding="async">
                                            <?php else: ?>
                                                <?= repdoc_catalog_preview_art((string) $f['type']) ?>
                                            <?php endif; ?>
                                            <span class="cat-file-badge">
                                                <?= repdoc_catalog_logo_img((string) $f['type'], 15) ?>
                                                <?= e((string) ($meta['label'] ?? $f['type'])) ?>
                                            </span>
                                            <?php if ($isNew): ?><span class="cat-new">নতুন</span><?php endif; ?>
                                        </div>
                                        <div class="cat-file-body">
                                            <p class="cat-file-eyebrow"><?= e((string) ($meta['full'] ?? $f['type'])) ?> · <?= e(strtoupper((string) $f['ext'])) ?></p>
                                            <h3 class="cat-file-name"><?= e($title) ?></h3>
                                            <p class="cat-file-desc" title="<?= e((string) $f['description']) ?>"><?= e((string) $f['description']) ?></p>
                                            <div class="cat-file-meta">
                                                <span class="cat-ext"><?= e(strtoupper((string) $f['ext'])) ?></span>
                                                <span><?= e($bn(repdoc_catalog_human_size((int) $f['size']))) ?></span>
                                                <span class="cat-meta-dot" aria-hidden="true">•</span>
                                                <span><?= e(repdoc_catalog_date((int) $f['mtime'])) ?></span>
                                            </div>
                                        </div>
                                        <div class="cat-file-foot">
                                            <a class="cat-download<?= e($actionClass) ?>" href="<?= e($actionUrl) ?>"<?= $user === null && $downloads ? ' title="ডাউনলোড করতে লগ ইন করুন"' : '' ?>>
                                                <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#<?= e($actionIcon) ?>"/></svg>
                                                <?= e($actionLabel) ?>
                                            </a>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>

                            <div class="cat-noresult" id="catNoResult" role="status" hidden>
                                <img class="cat-noresult-art" src="<?= e(repdoc_url('src/assets/image/boyfromwhole.png')) ?>" alt="" width="180" height="180" loading="lazy" decoding="async">
                                <h3>কোনো ফাইল পাওয়া যায়নি</h3>
                                <p>অন্য নাম, বিবরণ বা ফরম্যাট দিয়ে আবার খুঁজুন।</p>
                                <button type="button" class="btn btn-secondary" id="catNoReset">সব ফাইল দেখুন</button>
                            </div>
                        <?php endif; ?>
                    </div>

                    <aside class="cat-sidebar" aria-label="লাইব্রেরি তথ্য ও বিজ্ঞাপন">
                        <div class="cat-side-card">
                            <h3 class="cat-side-title">
                                <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#cat-i-folder"/></svg>
                                লাইব্রেরি এক নজরে
                            </h3>
                            <ul class="cat-stat-list">
                                <li><span>ফাইল</span><strong><?= e($bn((string) $totalFiles)) ?>টি</strong></li>
                                <li><span>ফরম্যাট</span><strong><?= e($bn((string) $formatCount)) ?>টি</strong></li>
                                <li><span>মোট সাইজ</span><strong><?= e($bn($sizeLabel)) ?></strong></li>
                                <li><span>সর্বশেষ যোগ</span><strong><?= e(repdoc_catalog_date($lastUpdated)) ?></strong></li>
                            </ul>
                        </div>

                        <div class="cat-side-card">
                            <h3 class="cat-side-title">
                                <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#cat-i-grid"/></svg>
                                ফরম্যাট অনুযায়ী
                            </h3>
                            <div class="cat-side-formats">
                                <button type="button" class="cat-side-format is-active" data-filter="all" aria-pressed="true"<?= $totalFiles === 0 ? ' disabled' : '' ?>>
                                    <span class="cat-side-format-name">সব ফাইল</span>
                                    <span class="cat-side-count"><?= e($bn((string) $totalFiles)) ?></span>
                                </button>
                                <?php foreach ($filterRows as $row): ?>
                                    <button type="button" class="cat-side-format" data-filter="<?= e($row['key']) ?>" aria-pressed="false"<?= $totalFiles === 0 ? ' disabled' : '' ?>>
                                        <?= repdoc_catalog_logo_img($row['logo'], 22) ?>
                                        <span class="cat-side-format-name"><?= e($row['label']) ?></span>
                                        <span class="cat-side-count"><?= e($bn((string) $row['count'])) ?></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="cat-side-card cat-ad" aria-label="বিজ্ঞাপনের স্থান">
                            <span>Ad Space</span>
                            <small>বিজ্ঞাপনের জন্য যোগাযোগ করুন</small>
                        </div>

                        <div class="cat-side-card cat-join">
                            <span class="cat-join-icon" aria-hidden="true">
                                <svg class="icon" viewBox="0 0 24 24"><use href="#cat-i-bolt"/></svg>
                            </span>
                            <h3>সব ফাইল আনলক করুন</h3>
                            <p>একটি অপারেটর অ্যাকাউন্টেই সব টেমপ্লেট, সব ফরম্যাট ও সব টুল।</p>
                            <?php if ($user !== null): ?>
                                <a class="btn btn-primary" href="<?= e($dashboard) ?>">ড্যাশবোর্ডে যান</a>
                            <?php else: ?>
                                <a class="btn btn-primary" href="<?= e($apply) ?>">অ্যাকাউন্টের জন্য আবেদন</a>
                                <a class="cat-join-link" href="<?= e($login) ?>">আগে থেকে অ্যাকাউন্ট আছে? লগ ইন</a>
                            <?php endif; ?>
                        </div>
                    </aside>
                </div>
            </section>

            <section class="cat-guide" id="guide" aria-labelledby="catGuideHeading">
                <div class="container">
                    <div class="section-head cat-guide-head">
                        <div>
                            <p class="kicker">প্রক্রিয়া</p>
                            <h2 id="catGuideHeading">ডাউনলোড করবেন যেভাবে</h2>
                            <p class="section-sub">তিন ধাপে পছন্দের টেমপ্লেট নিজের কাজে লাগান।</p>
                        </div>
                    </div>
                    <ol class="cat-steps">
                        <?php foreach (array_values($guide) as $i => $step): ?>
                            <li class="cat-step">
                                <div class="cat-step-top">
                                    <span class="cat-step-num" aria-hidden="true"><?= e($bn((string) ($i + 1))) ?></span>
                                    <span class="cat-step-icon" aria-hidden="true">
                                        <svg class="icon" viewBox="0 0 24 24"><use href="#<?= e((string) ($step['icon'] ?? 'cat-i-file')) ?>"/></svg>
                                    </span>
                                </div>
                                <h3><?= e((string) ($step['title'] ?? '')) ?></h3>
                                <p><?= e((string) ($step['text'] ?? '')) ?></p>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            </section>

            <section class="cat-closing-band" aria-labelledby="catClosingHeading">
                <div class="container">
                    <div class="cat-closing">
                        <div class="cat-closing-copy">
                            <p class="kicker">এক অ্যাকাউন্ট, সব ফাইল</p>
                            <h2 id="catClosingHeading"><?= e((string) ($args['closing_title'] ?? 'শুরু করতে প্রস্তুত?')) ?></h2>
                            <p><?= e((string) ($args['closing_text'] ?? 'অপারেটর অ্যাকাউন্টের জন্য আবেদন করুন, লগ ইন করে সব টেমপ্লেট ডাউনলোড করুন।')) ?></p>
                        </div>
                        <div class="cat-closing-actions">
                            <?php if ($user !== null): ?>
                                <a class="btn btn-ledger" href="<?= e($dashboard) ?>">ড্যাশবোর্ডে যান</a>
                                <a class="btn btn-ledger-outline" href="<?= e($home) ?>#tools">সব টুল দেখুন</a>
                            <?php else: ?>
                                <a class="btn btn-ledger" href="<?= e($apply) ?>">অ্যাকাউন্টের জন্য আবেদন</a>
                                <a class="btn btn-ledger-outline" href="<?= e($login) ?>">লগ ইন</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
        </main>
        <?php
        repdoc_catalog_footer([
            'home'  => $home,
            'login' => $login,
            'apply' => $apply,
        ]);
    }
}
