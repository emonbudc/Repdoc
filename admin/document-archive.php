<?php
declare(strict_types=1);

/**
 * Repdoc — admin: UDC document archive editor.
 *
 * Manages the content items (cards) and their links on the archive landing
 * page Entrepreneur/panel/landing.php: title, description, category, stamp,
 * icon, tint, and the target (a blank file in Entrepreneur/data, an external
 * link, a live prottoyon generator type, or another page on this site).
 *
 * Rows live in love/archive_items.json (src/archive.php); the public archive
 * page reads the same file, so saving here changes the live page immediately.
 */

require_once __DIR__ . '/includes/layout.php';
require_once dirname(__DIR__) . '/src/archive.php';

/** Posted row fields: input name => stored key. */
const ARCHIVE_FIELDS = [
    'title'  => 'title',
    'desc'   => 'desc',
    'cat'    => 'cat',
    'tag'    => 'tag',
    'icon'   => 'icon',
    'tint'   => 'tint',
    'kind'   => 'kind',
    'file'   => 'file',
    'url'    => 'url',
    'type'   => 'type',
    'meta'   => 'meta',
    'alabel' => 'action',
    'bn'     => 'bn',
];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Session expired — please try again.');
        redirect('document-archive.php');
    }

    $action = (string) ($_POST['action'] ?? 'save');
    $titles = $_POST['title'] ?? [];
    $removes = $_POST['remove'] ?? [];
    if (!is_array($titles)) $titles = [];
    if (!is_array($removes)) $removes = [];

    $rows = [];
    foreach (array_keys($titles) as $key) {
        $key = (string) $key;
        $row = ['_key' => $key];
        foreach (ARCHIVE_FIELDS as $input => $field) {
            $values = $_POST[$input] ?? [];
            $row[$field] = is_array($values) ? (string) ($values[$key] ?? '') : '';
        }
        // A brand-new row with nothing typed into it is discarded.
        if (trim($row['title']) === '' && trim($row['desc']) === '' && trim($row['file']) === '' && trim($row['url']) === '') {
            continue;
        }
        $rows[] = $row;
    }

    // Reorder: one step per submit, before anything is deleted.
    $up   = (string) ($_POST['move_up'] ?? '');
    $down = (string) ($_POST['move_down'] ?? '');
    if ($up !== '' || $down !== '') {
        $target = $up !== '' ? $up : $down;
        foreach ($rows as $i => $row) {
            if ($row['_key'] !== $target) continue;
            $swap = $up !== '' ? $i - 1 : $i + 1;
            if ($swap >= 0 && $swap < count($rows)) {
                $tmp = $rows[$swap];
                $rows[$swap] = $rows[$i];
                $rows[$i] = $tmp;
            }
            break;
        }
    }

    $removed = 0;
    $kept = [];
    foreach ($rows as $row) {
        if (isset($removes[$row['_key']]) && (string) $removes[$row['_key']] !== '') {
            $removed++;
            continue;
        }
        unset($row['_key']);
        $kept[] = $row;
    }

    if ($action === 'delete' && $removed === 0) {
        flash_set('warn', 'কোনো আইটেম বাছাই করা হয়নি — বাদ দেওয়ার ঘরটা টিক দিন।');
        redirect('document-archive.php');
    }

    $result = repdoc_archive_save($kept);
    if (!$result['ok']) {
        flash_set('error', 'love/archive_items.json লেখা যায়নি — ফোল্ডারটা লেখার অনুমতি পাচ্ছে কি না দেখুন।');
        redirect('document-archive.php');
    }

    $parts = [];
    if ($removed > 0)  $parts[] = $removed . 'টি আইটেম বাদ দেওয়া হয়েছে।';
    $parts[] = 'সেভ হয়েছে ' . $result['saved'] . 'টি আইটেম।';
    if ($result['invalid'] > 0) {
        $parts[] = $result['invalid'] . 'টি আইটেমে নজর দেওয়া দরকার (লাল চিহ্ন দেখুন)।';
    }
    flash_set($result['invalid'] > 0 ? 'warn' : 'ok', implode(' ', $parts));
    redirect('document-archive.php');
}

$items = repdoc_archive_items();
$rows  = $items !== [] ? $items : [[
    'title' => '', 'desc' => '', 'cat' => 'certificate', 'tag' => '', 'icon' => 'i-file', 'tint' => 'tint-blue',
    'kind' => 'download', 'file' => '', 'url' => '', 'type' => 'birth', 'meta' => '', 'action' => '',
    'bn' => '', 'valid' => false, 'error' => '',
]];

$stats = ['total' => count($items), 'certificate' => 0, 'prottoyon' => 0, 'download' => 0, 'external' => 0, 'generator' => 0, 'page' => 0, 'missing' => 0];
foreach ($items as $item) {
    if (isset($stats[$item['cat']]))  $stats[$item['cat']]++;
    if (isset($stats[$item['kind']])) $stats[$item['kind']]++;
    if ($item['kind'] === 'download' && !repdoc_archive_file_ok((string) $item['file'])) $stats['missing']++;
}

$dataFiles  = repdoc_archive_files();
$categories = repdoc_archive_categories();
$kinds      = repdoc_archive_kinds();
$icons      = repdoc_archive_icons();
$tints      = repdoc_archive_tints();
$genGroups  = [];
foreach (repdoc_archive_generator_types() as $key => $meta) {
    $genGroups[(string) $meta['group']][(string) $key] = (string) $meta['label'];
}
$updatedAt  = repdoc_archive_last_updated();

/** One editor row. Keeps PHP and JS rendering identical. */
function archive_row_html(string $key, array $item, array $ctx): void
{
    $cats = $ctx['cats']; $kinds = $ctx['kinds']; $icons = $ctx['icons']; $tints = $ctx['tints']; $genGroups = $ctx['gen'];
    $valid = !empty($item['valid']);
    $name = static fn(string $field): string => $field . '[' . e($key) . ']';
    ?>
    <div class="da-item<?= $valid ? '' : ' is-invalid' ?>" data-kind="<?= e((string) $item['kind']) ?>">
        <div class="da-item-head">
            <span class="da-no" aria-hidden="true"></span>
            <span class="da-item-title"><?= e((string) ($item['title'] !== '' ? $item['title'] : 'নতুন আইটেম')) ?></span>
            <span class="da-pill" data-kind-pill><?= e((string) ($kinds[(string) $item['kind']] ?? '')) ?></span>
            <?php if (!$valid && (string) $item['error'] !== ''): ?>
                <span class="da-error"><?= e((string) $item['error']) ?></span>
            <?php endif; ?>
            <span class="da-item-tools">
                <button type="submit" class="da-move" name="move_up" value="<?= e($key) ?>" title="উপরে নিন" aria-label="উপরে নিন">↑</button>
                <button type="submit" class="da-move" name="move_down" value="<?= e($key) ?>" title="নিচে নিন" aria-label="নিচে নিন">↓</button>
                <label class="da-remove"><input type="checkbox" name="remove[<?= e($key) ?>]" value="1"> বাদ</label>
            </span>
        </div>

        <div class="da-grid">
            <label class="da-field da-col-2">
                <span>নাম (কার্ডের শিরোনাম)</span>
                <input type="text" name="<?= $name('title') ?>" value="<?= e((string) $item['title']) ?>" maxlength="120">
            </label>
            <label class="da-field da-col-3">
                <span>বর্ণনা</span>
                <input type="text" name="<?= $name('desc') ?>" value="<?= e((string) $item['desc']) ?>" maxlength="300">
            </label>
            <label class="da-field">
                <span>বিভাগ</span>
                <select name="cat[<?= e($key) ?>]">
                    <?php foreach ($cats as $value => $meta): ?>
                        <option value="<?= e((string) $value) ?>"<?= $item['cat'] === $value ? ' selected' : '' ?>><?= e((string) $meta['admin']) ?> · <?= e((string) $meta['bn']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="da-field">
                <span>স্ট্যাম্প</span>
                <input type="text" name="<?= $name('tag') ?>" value="<?= e((string) $item['tag']) ?>" maxlength="40">
            </label>

            <label class="da-field da-col-2">
                <span>ধরন (লিংক কোথায় যাবে)</span>
                <select name="kind[<?= e($key) ?>]" data-kind-select>
                    <?php foreach ($kinds as $value => $label): ?>
                        <option value="<?= e($value) ?>"<?= $item['kind'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="da-field da-col-2" data-when="download">
                <span>ফাইল (Entrepreneur/data)</span>
                <input type="text" name="<?= $name('file') ?>" value="<?= e((string) $item['file']) ?>" list="da-files" maxlength="160" placeholder="oldblank.PNG">
            </label>
            <label class="da-field da-col-3" data-when="external page">
                <span>লিংক (https://… অথবা সাইটের পাথ যেমন genaretor/certificate/)</span>
                <input type="text" name="<?= $name('url') ?>" value="<?= e((string) $item['url']) ?>" maxlength="1000" placeholder="https://drive.google.com/…">
            </label>
            <label class="da-field da-col-2" data-when="generator">
                <span>জেনারেটর টাইপ</span>
                <select name="type[<?= e($key) ?>]">
                    <?php foreach ($genGroups as $group => $options): ?>
                        <optgroup label="<?= e($group) ?>">
                            <?php foreach ($options as $value => $label): ?>
                                <option value="<?= e($value) ?>"<?= $item['type'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="da-field">
                <span>আইকন</span>
                <select name="icon[<?= e($key) ?>]">
                    <?php foreach ($icons as $value => $label): ?>
                        <option value="<?= e($value) ?>"<?= $item['icon'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="da-field">
                <span>রং</span>
                <select name="tint[<?= e($key) ?>]">
                    <?php foreach ($tints as $value => $label): ?>
                        <option value="<?= e($value) ?>"<?= $item['tint'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="da-field">
                <span>বাটনের লেখা</span>
                <input type="text" name="<?= $name('alabel') ?>" value="<?= e((string) $item['action']) ?>" maxlength="40" placeholder="ডাউনলোড">
            </label>
            <label class="da-field">
                <span>মেটা লাইন</span>
                <input type="text" name="<?= $name('meta') ?>" value="<?= e((string) $item['meta']) ?>" maxlength="80" placeholder="ডাউনলোডে ফাইলের সাইজ নিজে বসে">
            </label>
            <label class="da-field da-col-3">
                <span>বাংলা সার্চ শব্দ</span>
                <input type="text" name="<?= $name('bn') ?>" value="<?= e((string) $item['bn']) ?>" maxlength="200" placeholder="যেমন: জন্ম সনদ জন্মনিবন্ধন">
            </label>
        </div>
    </div>
    <?php
}

admin_page_start('Document Archive', 'document-archive.php');
admin_notice();
?>

<style>
    .da-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
    .da-head-actions { display: flex; flex-wrap: wrap; gap: 8px; }
    .da-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px; margin-bottom: var(--space-5); }
    .da-stat { padding: 14px 16px; border: 1px solid var(--border-color); border-radius: var(--radius-md); background: var(--bg-surface); box-shadow: var(--shadow-sm); }
    .da-stat .k { display: block; color: var(--text-muted); font-size: .72rem; font-weight: 600; }
    .da-stat .v { display: block; margin-top: 3px; color: var(--text-primary); font-family: var(--font-mono); font-size: 1.24rem; font-weight: 700; }
    .da-stat.is-warn { border-color: var(--warning); background: var(--warning-bg); }
    .da-editor { counter-reset: da-row; display: flex; flex-direction: column; gap: 12px; }
    .da-item { padding: 12px 14px; border: 1px solid var(--border-color); border-radius: 12px; background: var(--bg-base); }
    .da-item.is-invalid { border-color: var(--danger); box-shadow: inset 3px 0 0 var(--danger); }
    .da-item-head { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 10px; }
    .da-no { counter-increment: da-row; color: var(--text-muted); font-family: var(--font-mono); font-size: .74rem; font-weight: 700; }
    .da-no::before { content: counter(da-row, decimal-leading-zero); }
    .da-item-title { font-weight: 700; font-size: .95rem; min-width: 0; overflow-wrap: anywhere; }
    .da-pill { padding: 2px 9px; border-radius: 999px; background: var(--primary-light); color: var(--primary); font-size: .68rem; font-weight: 700; }
    .da-error { color: var(--danger); font-size: .74rem; font-weight: 600; }
    .da-item-tools { margin-inline-start: auto; display: flex; align-items: center; gap: 6px; }
    .da-move { width: 30px; height: 30px; display: inline-grid; place-items: center; border: 1px solid var(--border-color); border-radius: 8px; background: #fff; color: var(--text-muted); cursor: pointer; font: inherit; }
    .da-move:hover { border-color: var(--primary); color: var(--primary); background: var(--primary-light); }
    .da-remove { display: inline-flex; align-items: center; gap: 5px; color: var(--danger); font-size: .76rem; font-weight: 600; cursor: pointer; }
    .da-remove input { accent-color: var(--danger); }
    .da-grid { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 8px 10px; align-items: end; }
    .da-field { display: flex; flex-direction: column; gap: 4px; min-width: 0; }
    .da-field > span { color: var(--text-muted); font-size: .66rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; }
    .da-field input, .da-field select { width: 100%; min-height: 38px; padding: 8px 10px; border: 1px solid var(--border-color); border-radius: 6px; background: #fff; color: var(--text-primary); font: inherit; font-size: .84rem; }
    .da-field input:focus, .da-field select:focus { outline: none; border-color: var(--border-focus); box-shadow: 0 0 0 3px var(--primary-light); }
    .da-col-2 { grid-column: span 2; }
    .da-col-3 { grid-column: span 3; }
    .da-item[data-kind="download"] .da-field[data-when="external page"],
    .da-item[data-kind="download"] .da-field[data-when="generator"],
    .da-item[data-kind="external"] .da-field[data-when="download"],
    .da-item[data-kind="external"] .da-field[data-when="generator"],
    .da-item[data-kind="page"] .da-field[data-when="download"],
    .da-item[data-kind="page"] .da-field[data-when="generator"],
    .da-item[data-kind="generator"] .da-field[data-when="download"],
    .da-item[data-kind="generator"] .da-field[data-when="external page"] { display: none; }
    .da-foot { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; margin-top: 14px; padding-top: 14px; border-top: 1px dashed var(--border-color); }
    .da-foot-note { color: var(--text-muted); font-size: .78rem; line-height: 1.6; }
    .da-save { display: flex; flex-wrap: wrap; gap: 8px; }
    .da-help { display: flex; flex-direction: column; gap: 7px; margin: 0; padding: 0; list-style: none; }
    .da-help li { position: relative; padding-inline-start: 17px; color: var(--text-muted); font-size: .82rem; line-height: 1.55; }
    .da-help li::before { content: ""; position: absolute; top: .52em; inset-inline-start: 2px; width: 6px; height: 6px; border-radius: 50%; background: var(--primary); }
    @media (max-width: 1100px) { .da-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    @media (max-width: 860px)  { .da-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .da-col-3 { grid-column: span 2; } }
    @media (max-width: 560px)  { .da-grid { grid-template-columns: 1fr; } .da-col-2, .da-col-3 { grid-column: span 1; } }
</style>

<div class="admin-page-head da-head">
    <div>
        <h1 class="admin-title">Document Archive</h1>
        <p class="admin-sub">
            ডকুমেন্ট আর্কাইভ পেজের (Entrepreneur/panel/landing.php) কার্ড, লিংক, আইকন আর রং এখান থেকে বদলান।
            সেভ করলেই লাইভ পেজে দেখা যাবে। ফাইল দিতে হবে <code>Entrepreneur/data/</code> ফোল্ডারে।
        </p>
    </div>
    <div class="da-head-actions">
        <a class="btn btn-secondary" href="../Entrepreneur/panel/landing.php" target="_blank" rel="noopener">ভিজিট সাইট</a>
        <button type="submit" form="daForm" class="btn btn-primary">সেভ করুন</button>
    </div>
</div>

<div class="da-stats">
    <div class="da-stat"><span class="k">মোট আইটেম</span><span class="v"><?= (int) $stats['total'] ?></span></div>
    <div class="da-stat"><span class="k">সার্টিফিকেট</span><span class="v"><?= (int) $stats['certificate'] ?></span></div>
    <div class="da-stat"><span class="k">প্রত্যয়ন</span><span class="v"><?= (int) $stats['prottoyon'] ?></span></div>
    <div class="da-stat"><span class="k">জেনারেটর</span><span class="v"><?= (int) $stats['generator'] ?></span></div>
    <div class="da-stat"><span class="k">ফাইল ডাউনলোড</span><span class="v"><?= (int) $stats['download'] ?></span></div>
    <div class="da-stat<?= $stats['missing'] > 0 ? ' is-warn' : '' ?>"><span class="k">ফাইল পাওয়া যায়নি</span><span class="v"><?= (int) $stats['missing'] ?></span></div>
</div>

<form class="panel" id="daForm" method="post" action="document-archive.php">
    <div class="panel-head">
        কার্ড তালিকা
        <span class="count"><?= count($rows) ?> আইটেম</span>
        <?php if ($updatedAt > 0): ?>
            <span class="count">হালনাগাদ <?= e(date('d M Y, H:i', $updatedAt)) ?></span>
        <?php endif; ?>
    </div>
    <div class="panel-body">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">

        <datalist id="da-files">
            <?php foreach ($dataFiles as $file): ?>
                <option value="<?= e($file) ?>"></option>
            <?php endforeach; ?>
        </datalist>

        <div class="da-editor" id="daEditor">
            <?php
            $ctx = ['cats' => $categories, 'kinds' => $kinds, 'icons' => $icons, 'tints' => $tints, 'gen' => $genGroups];
            foreach ($rows as $i => $item) {
                archive_row_html((string) $i, $item, $ctx);
            }
            ?>
        </div>

        <div class="da-foot">
            <p class="da-foot-note">
                ↑ ↓ দিয়ে ক্রম বদলান · “বাদ” টিক দিয়ে সেভ করলে আইটেম মুছে যাবে।
                ফাইল কলামে <code>Entrepreneur/data/</code>-র নাম লিখুন (নতুন ফাইল আপলোড করতে হবে FTP/হোস্টিং থেকে)।
            </p>
            <div class="da-save">
                <button type="button" class="btn btn-secondary btn-sm" id="daAdd">＋ নতুন আইটেম</button>
                <button type="submit" class="btn btn-primary btn-sm">সেভ করুন</button>
            </div>
        </div>
    </div>
</form>

<div class="panel">
    <div class="panel-head">এই পেজ কীভাবে কাজ করে</div>
    <div class="panel-body">
        <ul class="da-help">
            <li><strong>ফাইল ডাউনলোড</strong> — কার্ডের বাটন <code>Entrepreneur/panel/download.php?file=…</code> দিয়ে ফাইলটা দেয়; ফাইল না থাকলে কার্ডে “ফাইল নেই” দেখায়।</li>
            <li><strong>বাইরের লিংক</strong> — Google Drive/Docs লিংক সরাসরি খোলে (নতুন ট্যাবে)।</li>
            <li><strong>জেনারেটর</strong> — প্রোট্যায়ন জেনারেটরের নির্দিষ্ট ফরম খোলে (<code>?type=…</code>)।</li>
            <li><strong>সাইটের পেজ</strong> — যেমন <code>genaretor/certificate/</code>; পুরো <code>https://</code> লিংকও চলবে।</li>
            <li>সব তথ্য থাকে <code>love/archive_items.json</code>-এ; ফাইলটা না থাকলে পুরনো ডিফল্ট তালিকা দেখানো হয়।</li>
        </ul>
    </div>
</div>

<template id="daTemplate">
    <?php
    archive_row_html('__key__', [
        'title' => '', 'desc' => '', 'cat' => 'certificate', 'tag' => '', 'icon' => 'i-file', 'tint' => 'tint-blue',
        'kind' => 'download', 'file' => '', 'url' => '', 'type' => 'birth', 'meta' => '', 'action' => '',
        'bn' => '', 'valid' => true, 'error' => '',
    ], ['cats' => $categories, 'kinds' => $kinds, 'icons' => $icons, 'tints' => $tints, 'gen' => $genGroups]);
    ?>
</template>

<script>
(function () {
    var editor = document.getElementById('daEditor');
    var tpl = document.getElementById('daTemplate');
    var addBtn = document.getElementById('daAdd');
    if (!editor || !tpl || !addBtn) return;

    var seq = 0;

    function syncItem(item) {
        var select = item.querySelector('[data-kind-select]');
        var pill = item.querySelector('[data-kind-pill]');
        if (!select) return;
        item.setAttribute('data-kind', select.value);
        if (pill) pill.textContent = select.options[select.selectedIndex] ? select.options[select.selectedIndex].textContent : '';
    }

    editor.addEventListener('change', function (ev) {
        var select = ev.target.closest ? ev.target.closest('[data-kind-select]') : null;
        if (select) syncItem(select.closest('.da-item'));
    });

    addBtn.addEventListener('click', function () {
        var key = 'n' + (++seq) + '_' + Date.now();
        var html = tpl.innerHTML.split('__key__').join(key);
        var holder = document.createElement('div');
        holder.innerHTML = html.trim();
        var item = holder.firstElementChild;
        if (!item) return;

        // Re-number the new row's fields from the template placeholder.
        editor.appendChild(item);
        syncItem(item);

        var title = item.querySelector('input[name^="title["]');
        if (title) title.focus();
        item.scrollIntoView({ block: 'center', behavior: 'smooth' });
    });

    Array.prototype.forEach.call(editor.querySelectorAll('.da-item'), syncItem);
})();
</script>

<?php admin_page_end(); ?>
