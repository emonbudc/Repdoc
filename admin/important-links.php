<?php
declare(strict_types=1);

/**
 * Repdoc — admin: important links directory.
 * Add, edit, reorder and remove the links shown on importantlinks/.
 */

require_once __DIR__ . '/includes/layout.php';
require_once dirname(__DIR__) . '/src/links.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Session expired — please try again.');
        redirect('important-links.php');
    }

    $action  = (string) ($_POST['action'] ?? 'save');
    $titles  = $_POST['title'] ?? [];
    $urls    = $_POST['url'] ?? [];
    $descs   = $_POST['desc'] ?? [];
    $cats    = $_POST['category'] ?? [];
    $removes = $_POST['remove'] ?? [];

    if (!is_array($titles))  $titles = [];
    if (!is_array($urls))    $urls = [];
    if (!is_array($descs))   $descs = [];
    if (!is_array($cats))    $cats = [];
    if (!is_array($removes)) $removes = [];

    $rows = [];
    $removed = 0;
    foreach ($titles as $key => $title) {
        $key = (string) $key;
        if (isset($removes[$key]) && (string) $removes[$key] !== '') {
            $removed++;
            continue;
        }

        $row = [
            'title'    => (string) $title,
            'url'      => (string) ($urls[$key] ?? ''),
            'desc'     => (string) ($descs[$key] ?? ''),
            'category' => (string) ($cats[$key] ?? 'other'),
        ];
        if (repdoc_link_text($row['title'], 120) === ''
            && repdoc_link_raw_url($row['url']) === ''
            && repdoc_link_text($row['desc'], 400) === '') {
            continue;
        }
        $rows[] = $row;
    }

    if ($action === 'delete' && $removed === 0) {
        flash_set('warn', 'Check at least one link to delete.');
        redirect('important-links.php');
    }

    $result = repdoc_links_save($rows);
    if (!$result['ok']) {
        flash_set('error', 'Could not write love/important_links.json. Check that the folder is writable.');
        redirect('important-links.php');
    }

    $parts = [];
    if ($removed > 0) {
        $parts[] = 'Deleted ' . $removed . ' ' . ($removed === 1 ? 'link' : 'links') . '.';
    }
    $parts[] = 'Saved ' . $result['saved'] . ' ' . ($result['saved'] === 1 ? 'link' : 'links') . '.';
    if ($result['invalid'] > 0) {
        $parts[] = $result['invalid'] . ' ' . ($result['invalid'] === 1 ? 'entry needs' : 'entries need') . ' attention.';
    }
    flash_set($result['invalid'] > 0 ? 'warn' : 'ok', implode(' ', $parts));
    redirect('important-links.php');
}

$links = repdoc_links_load();
$validCount = 0;
foreach ($links as $link) {
    if (!empty($link['valid'])) $validCount++;
}
$invalidCount = count($links) - $validCount;
$displayLinks = $links !== [] ? $links : [[
    'title' => '', 'url' => '', 'desc' => '', 'category' => 'other', 'valid' => false, 'error' => '',
]];
$lastUpdated = repdoc_links_last_updated();

admin_page_start('Important Links', 'important-links.php');
admin_notice();
?>
<style>
.link-admin-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
.link-admin-actions { display: flex; flex-wrap: wrap; gap: 8px; }
.link-admin-stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
    margin-bottom: var(--space-5);
}
.link-admin-stat {
    padding: 14px 16px;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    background: var(--bg-surface);
    box-shadow: var(--shadow-sm);
}
.link-admin-stat .k { display: block; color: var(--text-muted); font-size: .74rem; font-weight: 600; }
.link-admin-stat .v {
    display: block;
    margin-top: 3px;
    color: var(--text-primary);
    font-family: var(--font-mono);
    font-size: 1.28rem;
    font-weight: 700;
}
.link-admin-stat.is-warn { border-color: var(--warning); background: var(--warning-bg); }
.link-editor { counter-reset: link-row; display: flex; flex-direction: column; gap: 10px; }
.link-row {
    display: grid;
    grid-template-columns: 30px minmax(0, 1.05fr) minmax(0, 1.35fr) 145px 130px;
    grid-template-areas:
        "no title url cat side"
        "no desc desc desc desc";
    gap: 8px 10px;
    align-items: end;
    padding: 12px;
    border: 1px solid var(--border-color);
    border-radius: 10px;
    background: var(--bg-base);
}
.link-row.is-invalid { border-color: var(--danger); box-shadow: inset 3px 0 0 var(--danger); }
.link-row-no { grid-area: no; align-self: start; padding-top: 3px; color: var(--text-muted); font-family: var(--font-mono); font-size: .72rem; font-weight: 700; }
.link-row-no::before { counter-increment: link-row; content: counter(link-row, decimal-leading-zero); }
.link-field { display: flex; flex-direction: column; gap: 4px; min-width: 0; }
.link-field > span {
    color: var(--text-muted);
    font-size: .67rem;
    font-weight: 700;
    letter-spacing: .05em;
    text-transform: uppercase;
}
.link-field input,
.link-field select,
.link-field textarea {
    width: 100%;
    min-height: 38px;
    padding: 8px 10px;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    background: #fff;
    color: var(--text-primary);
    font: inherit;
    font-size: .84rem;
}
.link-field textarea { min-height: 58px; resize: vertical; line-height: 1.5; }
.link-field input:focus,
.link-field select:focus,
.link-field textarea:focus {
    outline: none;
    border-color: var(--border-focus);
    box-shadow: 0 0 0 3px var(--primary-light);
}
.link-title { grid-area: title; }
.link-url { grid-area: url; }
.link-cat { grid-area: cat; }
.link-desc { grid-area: desc; }
.link-row-side {
    grid-area: side;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    gap: 8px;
    align-self: stretch;
}
.link-remove {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    color: var(--danger);
    font-size: .74rem;
    font-weight: 600;
    white-space: nowrap;
    cursor: pointer;
}
.link-remove input { accent-color: var(--danger); }
.link-move { display: flex; gap: 4px; }
.link-move button {
    display: inline-grid;
    place-items: center;
    width: 28px;
    height: 28px;
    border: 1px solid var(--border-color);
    border-radius: 5px;
    background: #fff;
    color: var(--text-muted);
    font: inherit;
    font-size: .9rem;
    line-height: 1;
    cursor: pointer;
}
.link-move button:hover { border-color: var(--primary); color: var(--primary); background: var(--primary-light); }
.link-field-error { color: var(--danger); font-size: .7rem; font-weight: 600; line-height: 1.4; }
.link-form-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 14px;
    padding-top: 14px;
    border-top: 1px dashed var(--border-color);
}
.link-form-save { display: flex; flex-wrap: wrap; gap: 8px; margin-inline-start: auto; }
.link-form-save .link-delete { order: -1; }
.link-editor-empty {
    margin: 12px 0 0;
    padding: 13px;
    border: 1px dashed var(--border-strong);
    border-radius: 7px;
    color: var(--text-muted);
    font-size: .82rem;
}
.link-editor-empty[hidden] { display: none; }
.link-help-list { display: flex; flex-direction: column; gap: 7px; padding: 0; margin: 0; list-style: none; }
.link-help-list li { position: relative; padding-inline-start: 17px; color: var(--text-muted); font-size: .82rem; line-height: 1.55; }
.link-help-list li::before { content: ""; position: absolute; top: .52em; inset-inline-start: 2px; width: 6px; height: 6px; border-radius: 50%; background: var(--primary); }
@media (max-width: 900px) {
    .link-row {
        grid-template-columns: 26px minmax(0, 1fr);
        grid-template-areas:
            "no title"
            "no url"
            "no cat"
            "no desc"
            "no side";
    }
    .link-row-side { flex-direction: row; align-items: center; justify-content: space-between; }
}
@media (max-width: 640px) {
    .link-admin-stats { grid-template-columns: 1fr; }
    .link-form-save { width: 100%; margin-inline-start: 0; }
    .link-form-save .btn { flex: 1 1 auto; }
    .link-admin-actions { width: 100%; }
    .link-admin-actions .btn { flex: 1 1 auto; justify-content: center; }
}
</style>

<div class="admin-page-head link-admin-head">
    <div>
        <p class="kicker">Publishing</p>
        <h1 class="admin-title">Important Links</h1>
        <p class="admin-sub">Add, edit, reorder and remove the links shown on the public directory.</p>
    </div>
    <div class="link-admin-actions">
        <a class="btn btn-secondary btn-sm" href="../importantlinks/" target="_blank" rel="noopener">
            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-external"/></svg>
            View public page
        </a>
    </div>
</div>

<div class="link-admin-stats">
    <div class="link-admin-stat">
        <span class="k">Total rows</span>
        <span class="v"><?= e(number_format(count($links))) ?></span>
    </div>
    <div class="link-admin-stat">
        <span class="k">Live on site</span>
        <span class="v"><?= e(number_format($validCount)) ?></span>
    </div>
    <div class="link-admin-stat<?= $invalidCount > 0 ? ' is-warn' : '' ?>">
        <span class="k">Needs attention</span>
        <span class="v"><?= e(number_format($invalidCount)) ?></span>
    </div>
</div>

<div class="panel">
    <div class="panel-head">
        Link directory
        <span class="count"><?= e(number_format(count($links))) ?> rows</span>
        <span class="cell-sub" style="margin-inline-start:auto">
            <?= $lastUpdated > 0 ? 'Updated ' . e(date('d M Y, H:i', $lastUpdated)) : 'Not published yet' ?>
        </span>
    </div>
    <div class="panel-body">
        <form method="post" action="important-links.php" id="linksForm">
            <?= csrf_field() ?>

            <div class="link-editor" id="linkRows">
                <?php foreach ($displayLinks as $i => $link): ?>
                    <?php
                    $error = (string) ($link['error'] ?? '');
                    $category = repdoc_link_category((string) ($link['category'] ?? 'other'));
                    ?>
                    <div class="link-row<?= $error !== '' ? ' is-invalid' : '' ?>">
                        <span class="link-row-no" aria-hidden="true"></span>

                        <label class="link-field link-title">
                            <span>Title</span>
                            <input type="text" name="title[<?= e((string) $i) ?>]"
                                   value="<?= e((string) ($link['title'] ?? '')) ?>"
                                   maxlength="120" placeholder="e.g. জাতীয় তথ্য বাতায়ন">
                        </label>

                        <label class="link-field link-url">
                            <span>URL</span>
                            <input type="text" name="url[<?= e((string) $i) ?>]"
                                   value="<?= e((string) ($link['url'] ?? '')) ?>"
                                   maxlength="1000" placeholder="https://example.gov.bd/">
                            <?php if ($error !== ''): ?>
                                <small class="link-field-error"><?= e($error) ?></small>
                            <?php endif; ?>
                        </label>

                        <label class="link-field link-cat">
                            <span>Category</span>
                            <select name="category[<?= e((string) $i) ?>]">
                                <?php foreach (repdoc_link_categories() as $key => $meta): ?>
                                    <option value="<?= e($key) ?>"<?= $category === $key ? ' selected' : '' ?>>
                                        <?= e($meta['admin']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <div class="link-row-side">
                            <label class="link-remove">
                                <input type="checkbox" name="remove[<?= e((string) $i) ?>]" value="1">
                                Remove
                            </label>
                            <div class="link-move">
                                <button type="button" data-move="up" aria-label="Move link up" title="Move up">↑</button>
                                <button type="button" data-move="down" aria-label="Move link down" title="Move down">↓</button>
                            </div>
                        </div>

                        <label class="link-field link-desc">
                            <span>Description</span>
                            <textarea name="desc[<?= e((string) $i) ?>]" rows="2" maxlength="400"
                                      placeholder="Short note shown under the link title (optional)"><?= e((string) ($link['desc'] ?? '')) ?></textarea>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>

            <p class="link-editor-empty" id="linksEmptyNote"<?= $links !== [] ? ' hidden' : '' ?>>
                No links yet. Use “Add link” to create the first row.
            </p>

            <div class="link-form-foot">
                <button type="button" class="btn btn-secondary" id="linkAdd">+ Add link</button>
                <div class="link-form-save">
                    <button type="submit" class="btn btn-primary" name="action" value="save">Save links</button>
                    <button type="submit" class="btn btn-secondary link-delete" name="action" value="delete"
                            onclick="return confirm('Delete the checked links?');">Delete checked</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-head">Link rules</div>
    <div class="panel-body">
        <ul class="link-help-list">
            <li>Use a full <code>https://</code> address for external sites; site-relative paths starting with <code>/</code> also work.</li>
            <li>Only <code>http</code> and <code>https</code> links are accepted. Unsafe schemes are rejected.</li>
            <li>Invalid rows stay visible and editable here, but they are not shown on the public page.</li>
            <li>Use ↑ and ↓ to reorder rows — the public page keeps this order. Check “Remove” and save to delete.</li>
        </ul>
    </div>
</div>

<template id="linkRowTemplate">
    <div class="link-row">
        <span class="link-row-no" aria-hidden="true"></span>

        <label class="link-field link-title">
            <span>Title</span>
            <input type="text" name="title[__key__]" value="" maxlength="120" placeholder="e.g. জাতীয় তথ্য বাতায়ন">
        </label>

        <label class="link-field link-url">
            <span>URL</span>
            <input type="text" name="url[__key__]" value="" maxlength="1000" placeholder="https://example.gov.bd/">
        </label>

        <label class="link-field link-cat">
            <span>Category</span>
            <select name="category[__key__]">
                <?php foreach (repdoc_link_categories() as $key => $meta): ?>
                    <option value="<?= e($key) ?>"<?= $key === 'other' ? ' selected' : '' ?>><?= e($meta['admin']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <div class="link-row-side">
            <label class="link-remove">
                <input type="checkbox" name="remove[__key__]" value="1">
                Remove
            </label>
            <div class="link-move">
                <button type="button" data-move="up" aria-label="Move link up" title="Move up">↑</button>
                <button type="button" data-move="down" aria-label="Move link down" title="Move down">↓</button>
            </div>
        </div>

        <label class="link-field link-desc">
            <span>Description</span>
            <textarea name="desc[__key__]" rows="2" maxlength="400"
                      placeholder="Short note shown under the link title (optional)"></textarea>
        </label>
    </div>
</template>

<script>
(function () {
    'use strict';

    var rows = document.getElementById('linkRows');
    var template = document.getElementById('linkRowTemplate');
    var addButton = document.getElementById('linkAdd');
    var emptyNote = document.getElementById('linksEmptyNote');
    var counter = 0;

    if (!rows || !template || !addButton) return;

    function syncEmpty() {
        if (emptyNote) emptyNote.hidden = rows.querySelector('.link-row') !== null;
    }

    addButton.addEventListener('click', function () {
        var key = 'new-' + (++counter);
        var wrapper = document.createElement('div');
        wrapper.innerHTML = template.innerHTML.replace(/__key__/g, key).trim();
        var row = wrapper.firstElementChild;
        if (!row) return;

        rows.appendChild(row);
        syncEmpty();
        var firstInput = row.querySelector('input[name^="title"]');
        if (firstInput) firstInput.focus();
    });

    rows.addEventListener('click', function (event) {
        var button = event.target.closest('[data-move]');
        if (!button) return;

        var row = button.closest('.link-row');
        if (!row) return;

        if (button.getAttribute('data-move') === 'up' && row.previousElementSibling) {
            rows.insertBefore(row, row.previousElementSibling);
        } else if (button.getAttribute('data-move') === 'down' && row.nextElementSibling) {
            rows.insertBefore(row.nextElementSibling, row);
        }
        syncEmpty();

        var firstInput = row.querySelector('input[name^="title"]');
        if (firstInput) firstInput.focus();
    });

    syncEmpty();
})();
</script>
<?php admin_page_end(); ?>
