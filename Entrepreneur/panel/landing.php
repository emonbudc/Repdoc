<?php
declare(strict_types=1);

/**
 * Repdoc — UDC document archive.
 *
 * Lists the cards from love/archive_items.json (edited in Admin → Document
 * Archive) inside the shared panel shell, so the archive looks and behaves
 * like the rest of the UDC / admin panels. Access is restricted to admins and
 * UDC users explicitly granted archive access (entrepreneur_apps.archive_access).
 */

require_once __DIR__ . '/includes/layout.php';
require_once dirname(__DIR__, 2) . '/src/archive.php';

if (!has_archive_access()) {
    flash_set('error', 'ডকুমেন্ট আর্কাইভ ব্যবহারের জন্য অ্যাডমিনের অনুমোদন প্রয়োজন।');
    redirect(repdoc_url('Entrepreneur/panel/'));
}

/** Bengali digits for counters. */
function archive_bn(int|string $value): string
{
    return strtr((string) $value, [
        '0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪',
        '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯',
    ]);
}

// The card list lives in love/archive_items.json — edited from the admin panel
// (Admin → Document Archive). Built-in defaults are used until it is saved.
$cards = repdoc_archive_items();

$counts = ['all' => count($cards), 'prottoyon' => 0, 'certificate' => 0, 'download' => 0];

foreach ($cards as &$card) {
    // Download cards read the real file; everything else is always available.
    if ($card['kind'] === 'download') {
        $card['available'] = repdoc_archive_file_ok((string) $card['file']);
        $card['meta'] = repdoc_archive_file_label((string) $card['file']);
    } else {
        $card['available'] = true;
        $card['meta'] = (string) ($card['meta'] ?? '');
    }

    $card['search'] = mb_strtolower(implode(' ', [
        (string) $card['title'],
        (string) $card['desc'],
        (string) $card['tag'],
        (string) $card['meta'],
        (string) $card['cat'],
        (string) ($card['bn'] ?? ''),
    ]), 'UTF-8');

    $cat = (string) $card['cat'];
    if (!isset($counts[$cat])) $counts[$cat] = 0;
    $counts[$cat]++;
    if ($card['kind'] === 'download') $counts['download']++;
}
unset($card);

udc_page_start('ডকুমেন্ট আর্কাইভ', 'landing.php');
udc_notice();
?>

<style>#archiveGrid .tool-card[hidden] { display: none; }</style>

<div class="admin-page-head dash-head">
    <div>
        <p class="kicker">UDC · আর্কাইভ</p>
        <h1 class="admin-title">প্রত্যয়ন &amp; সার্টিফিকেট</h1>
        <p class="admin-sub">রেডি টেমপ্লেট ও জেনারেটর — ব্ল্যাংক ফাইল ডাউনলোড করুন, অথবা তথ্য দিয়ে সনদ তৈরি করুন।</p>
    </div>
    <a class="btn btn-secondary btn-sm" href="<?= e(repdoc_url('Entrepreneur/panel/index.php')) ?>">ড্যাশবোর্ডে ফিরুন</a>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <span class="stat-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#a-file"/></svg></span>
        <span class="stat-text"><span class="k">মোট আইটেম</span><span class="v"><?= archive_bn($counts['all']) ?></span><span class="s">আর্কাইভে</span></span>
    </div>
    <div class="stat-card">
        <span class="stat-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#a-award"/></svg></span>
        <span class="stat-text"><span class="k">প্রত্যয়ন</span><span class="v"><?= archive_bn($counts['prottoyon']) ?></span><span class="s">অনলাইন জেনারেটর</span></span>
    </div>
    <div class="stat-card">
        <span class="stat-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#a-check"/></svg></span>
        <span class="stat-text"><span class="k">সার্টিফিকেট</span><span class="v"><?= archive_bn($counts['certificate']) ?></span><span class="s">শিক্ষা ও অন্যান্য</span></span>
    </div>
    <div class="stat-card">
        <span class="stat-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#a-download"/></svg></span>
        <span class="stat-text"><span class="k">ডাউনলোড ফাইল</span><span class="v"><?= archive_bn($counts['download']) ?></span><span class="s">Word / PSD / ছবি</span></span>
    </div>
</div>

<div class="filter-bar">
    <div class="field">
        <label for="archiveSearch">খুঁজুন</label>
        <input type="search" id="archiveSearch" placeholder="নাম বা ট্যাগ… (যেমন: জন্ম, কবিন, সার্টিফিকেট)" autocomplete="off">
    </div>
    <div class="field" style="flex:0 0 240px">
        <label for="archiveCat">ক্যাটাগরি</label>
        <select id="archiveCat">
            <option value="all">সব (<?= archive_bn($counts['all']) ?>)</option>
            <option value="prottoyon">প্রত্যয়ন (<?= archive_bn($counts['prottoyon']) ?>)</option>
            <option value="certificate">সার্টিফিকেট (<?= archive_bn($counts['certificate']) ?>)</option>
        </select>
    </div>
</div>

<div class="panel">
    <div class="panel-head">
        ডকুমেন্ট তালিকা
        <span class="count" id="archiveCount"><?= archive_bn($counts['all']) ?>টি</span>
    </div>

    <div class="panel-body tools-grid" id="archiveGrid">
        <?php foreach ($cards as $card): ?>
            <?php
            $external = $card['kind'] === 'external';
            $href = '#';
            // Download cards stay disabled when the blank file is missing.
            if ($card['kind'] !== 'download' || $card['available']) {
                $href = repdoc_archive_href($card);
            }
            $actionIcon = $card['kind'] === 'download' ? 'a-download' : ($external ? 'a-external' : 'i-arrow');
            ?>
            <article class="tool-card"
                     data-cat="<?= e($card['cat']) ?>"
                     data-search="<?= e($card['search']) ?>">
                <span class="tool-icon" aria-hidden="true">
                    <svg class="icon" viewBox="0 0 24 24"><use href="#<?= e($card['icon']) ?>"/></svg>
                </span>
                <span class="tool-name"><?= e($card['title']) ?></span>
                <span class="tool-cat"><?= e($card['tag']) ?></span>
                <?php if ($card['desc'] !== ''): ?>
                    <span style="font-size:.82rem;line-height:1.6;color:var(--text-muted)"><?= e($card['desc']) ?></span>
                <?php endif; ?>
                <?php if ($card['meta'] !== ''): ?>
                    <span style="font-size:.72rem;color:var(--text-muted);font-family:var(--font-mono)"><?= e($card['meta']) ?></span>
                <?php endif; ?>
                <span style="margin-top:auto;padding-top:6px">
                    <?php if ($card['kind'] === 'download' && !$card['available']): ?>
                        <span class="btn btn-secondary btn-sm" aria-disabled="true" style="opacity:.55;cursor:not-allowed">ফাইল নেই</span>
                    <?php else: ?>
                        <a class="btn btn-secondary btn-sm"
                           href="<?= e($href) ?>"
                           <?= $external ? 'target="_blank" rel="noopener noreferrer"' : '' ?>
                           <?= $card['kind'] === 'download' ? 'download="' . e((string) $card['file']) . '"' : '' ?>>
                            <?= e($card['action']) ?>
                        </a>
                    <?php endif; ?>
                </span>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="panel-body" id="archiveEmpty" role="status" hidden>
        <p class="empty-state-sm">কোনো ডকুমেন্ট পাওয়া যায়নি — অন্য শব্দ বা ক্যাটাগরি চেষ্টা করুন।</p>
    </div>
</div>

<script>
(function () {
    'use strict';

    var grid = document.getElementById('archiveGrid');
    var search = document.getElementById('archiveSearch');
    var cat = document.getElementById('archiveCat');
    var empty = document.getElementById('archiveEmpty');
    var count = document.getElementById('archiveCount');
    if (!grid || !search || !cat) return;

    var cards = Array.prototype.slice.call(grid.querySelectorAll('.tool-card[data-cat]'));

    function bn(value) {
        return String(value).replace(/[0-9]/g, function (digit) {
            return '০১২৩৪৫৬৭৮৯'[digit];
        });
    }

    function applyFilter() {
        var query = search.value.toLowerCase().trim();
        var activeCat = cat.value;
        var visible = 0;

        cards.forEach(function (card) {
            var matchesCat = activeCat === 'all' || card.getAttribute('data-cat') === activeCat;
            var matchesQuery = query === '' || (card.getAttribute('data-search') || '').indexOf(query) !== -1;
            var show = matchesCat && matchesQuery;
            card.hidden = !show;
            if (show) visible++;
        });

        if (empty) empty.hidden = visible !== 0;
        if (count) count.textContent = bn(visible) + 'টি';
    }

    search.addEventListener('input', applyFilter);
    cat.addEventListener('change', applyFilter);
    applyFilter();
})();
</script>

<?php udc_page_end(); ?>
