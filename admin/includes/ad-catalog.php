<?php
declare(strict_types=1);

function repdoc_ad_catalog(): array
{
    return [
        'home_top_banner' => [
            'label' => 'Top leaderboard',
            'size' => '728 × 90',
            'where' => 'Above the homepage tools and at the top of the birth form.',
            'width' => 728,
            'height' => 90,
            'kind' => 'banner',
        ],
        'home_middle_banner' => [
            'label' => 'In-content banner',
            'size' => '468 × 60',
            'where' => 'Inside homepage content and the birth form band.',
            'width' => 468,
            'height' => 60,
            'kind' => 'banner',
        ],
        'sidebar_right' => [
            'label' => 'Sidebar rectangle',
            'size' => '300 × 250',
            'where' => 'Right sidebar and the birth form result area.',
            'width' => 300,
            'height' => 250,
            'kind' => 'banner',
        ],
        'footer_banner' => [
            'label' => 'Footer leaderboard',
            'size' => '728 × 90',
            'where' => 'Bottom of the page.',
            'width' => 728,
            'height' => 90,
            'kind' => 'banner',
        ],
        'home_rail_tall' => [
            'label' => 'Tall skyscraper',
            'size' => '160 × 600',
            'where' => 'Tall banner in the homepage sidebar.',
            'width' => 160,
            'height' => 600,
            'kind' => 'banner',
        ],
        'home_rail_medium' => [
            'label' => 'Medium rail',
            'size' => '160 × 300',
            'where' => 'Short banner in the homepage sidebar.',
            'width' => 160,
            'height' => 300,
            'kind' => 'banner',
        ],
        'home_mobile_banner' => [
            'label' => 'Mobile banner',
            'size' => '320 × 50',
            'where' => 'Mobile screens only. Automatically used at the top of the birth form.',
            'width' => 320,
            'height' => 50,
            'kind' => 'banner',
        ],
        'home_native_banner' => [
            'label' => 'Native banner',
            'size' => 'Responsive',
            'where' => 'Blends into the homepage tools and the birth form content.',
            'width' => 0,
            'height' => 0,
            'kind' => 'native',
        ],
        'sitewide_social_bar' => [
            'label' => 'Social bar',
            'size' => 'Sitewide script',
            'where' => 'Floating bar loaded on every page.',
            'width' => 0,
            'height' => 0,
            'kind' => 'script',
        ],
        'article_top' => [
            'label' => 'Article top',
            'size' => '728 × 90',
            'where' => 'Top of article and detail pages.',
            'width' => 728,
            'height' => 90,
            'kind' => 'banner',
        ],
        'article_bottom' => [
            'label' => 'Article bottom',
            'size' => '728 × 90',
            'where' => 'Below article and detail content.',
            'width' => 728,
            'height' => 90,
            'kind' => 'banner',
        ],
    ];
}

function repdoc_ad_example(string $slug, string $fallbackName = '', string $fallbackDescription = ''): array
{
    $catalog = repdoc_ad_catalog();
    if (isset($catalog[$slug])) {
        $info = $catalog[$slug];
        if ($fallbackName !== '') {
            $info['name'] = $fallbackName;
        }
        if ($fallbackDescription !== '') {
            $info['where'] = $fallbackDescription;
        }
        return $info;
    }

    return [
        'label' => $fallbackName !== '' ? $fallbackName : ucfirst(str_replace(['-', '_'], ' ', $slug)),
        'size' => 'Custom size',
        'where' => $fallbackDescription !== '' ? $fallbackDescription : 'Custom placement created from the admin panel.',
        'width' => 300,
        'height' => 250,
        'kind' => 'custom',
        'name' => $fallbackName,
    ];
}

function repdoc_ad_demo_dimensions(int $width, int $height): array
{
    if ($width < 1 || $height < 1) {
        return [92, 52];
    }

    $scale = min(92 / $width, 58 / $height);
    return [max(10, (int) round($width * $scale)), max(8, (int) round($height * $scale))];
}

function repdoc_ad_type_for_slug(string $slug): string
{
    $catalog = repdoc_ad_catalog();
    $kind = $catalog[$slug]['kind'] ?? 'banner';
    if ($kind === 'native') {
        return 'native';
    }
    if ($kind === 'script') {
        return 'socialbar';
    }
    if (in_array($slug, ['sidebar_right', 'footer_banner'], true)) {
        return 'sidebar';
    }
    return 'banner';
}
