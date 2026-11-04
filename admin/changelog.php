<?php
declare(strict_types=1);

/**
 * Repdoc — admin: changelog, notable plan and TODO.
 *
 * Visual list with details and resource links (not markdown files).
 * TODO is the main content; Changelog is placed in the side column.
 */

require_once __DIR__ . '/includes/layout.php';
require_once dirname(__DIR__) . '/src/todo.php';

// Seed defaults on first run.
repdoc_todo_seed_defaults();

// ── POST: add / toggle done / delete ────────────────────────────────
$isAjax = (($_POST['_ajax'] ?? '') === '1');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => 'Session expired — please try again.']);
            exit;
        }
        flash_set('error', 'Session expired — please try again.');
        redirect('changelog.php');
    }
    $action = (string) ($_POST['action'] ?? '');
    $response = ['ok' => false, 'message' => 'Unknown action.', 'action' => $action];
    if ($action === 'add') {
        $task = (string) ($_POST['task'] ?? '');
        $detail = (string) ($_POST['detail'] ?? '');
        if (repdoc_todo_add($task, $detail)) {
            $items = repdoc_todo_load_all();
            $newItem = $items[0] ?? null;
            $response = [
                'ok' => true,
                'message' => 'TODO added.',
                'action' => 'add',
                'html' => $newItem ? repdoc_todo_render_item($newItem) : '',
            ];
        } else {
            $response = ['ok' => false, 'message' => 'Task text is required.', 'action' => 'add'];
        }
    } else {
        $id = (string) ($_POST['id'] ?? '');
        if ($action === 'toggle') {
            if (repdoc_todo_toggle_done($id)) {
                $updated = null;
                foreach (repdoc_todo_load_all() as $it) {
                    if ($it['id'] === $id) { $updated = $it; break; }
                }
                $response = [
                    'ok' => true,
                    'message' => 'TODO updated.',
                    'action' => 'toggle',
                    'id' => $id,
                    'html' => $updated ? repdoc_todo_render_item($updated) : '',
                ];
            } else {
                $response = ['ok' => false, 'message' => 'Could not update TODO.', 'action' => 'toggle'];
            }
        } elseif ($action === 'delete') {
            if (repdoc_todo_delete($id)) {
                $response = ['ok' => true, 'message' => 'TODO deleted.', 'action' => 'delete', 'id' => $id];
            } else {
                $response = ['ok' => false, 'message' => 'Could not delete TODO.', 'action' => 'delete'];
            }
        }
    }
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (!empty($response['ok'])) {
        flash_set('ok', (string) $response['message']);
    } else {
        flash_set('error', (string) $response['message']);
    }
    redirect('changelog.php');
}

$todoItems = repdoc_todo_load_all();

/**
 * Changelog entries, newest first.
 */
$changelog = [
    [
        'date' => '2026-11-04',
        'title' => 'UDC panel dashboard subtitle removed',
        'details' => [
            'Removed the subtitle line "আপনার প্রত্যয়ন ও ব্যালেন্স এক নজরে।" from the UDC dashboard head.',
            'Kept the UDC Panel kicker and dashboard title intact.',
        ],
        'links' => [
            'UDC dashboard' => '../Entrepreneur/panel/index.php',
        ],
    ],
    [
        'date' => '2026-11-04',
        'title' => 'Admin changelog section added',
        'details' => [
            'New admin page "Changelog" with visual lists for changelog, notable plan and TODO.',
            'Added sidebar entry under Manage; no markdown files are rendered.',
        ],
        'links' => [
            'Changelog page' => 'changelog.php',
            'Admin layout' => 'includes/layout.php',
        ],
    ],
    [
        'date' => '2026-10-28',
        'title' => 'Ad placements unified across generators',
        'details' => [
            'Unified tool header and restyled generator forms in the green theme.',
            'Added green-bordered ad frames to generator tools.',
            'Birth form ads made responsive with a desktop rail; death generator ad layout matched to birth.',
        ],
        'links' => [
            'Birth generator' => '../genaretor/birth/index.php',
            'Death generator' => '../genaretor/death/index.php',
            'Ad manager' => 'ads.php',
        ],
    ],
    [
        'date' => '2026-10-27',
        'title' => 'Admin ad manager simplified',
        'details' => [
            'Simplified admin ad manager and placement examples.',
            'Added four responsive ad placements to the birth form.',
        ],
        'links' => [
            'Ad manager' => 'ads.php',
            'Placements' => 'ad-positions.php',
        ],
    ],
    [
        'date' => '2026-10-27',
        'title' => 'Panel sidebars flattened and grouped',
        'details' => [
            'Flattened single-item sidebar groups in both admin and UDC panels.',
            'Grouped panel sidebar tools by folder (Genaretor, Tools, Showcase, Offline/Office Files).',
            'Reordered admin sidebar and removed duplicate entries.',
        ],
        'links' => [
            'Admin layout' => 'includes/layout.php',
        ],
    ],
    [
        'date' => '2026-10-26',
        'title' => 'Auth and panel redesign in green theme',
        'details' => [
            'Redesigned login and entrepreneur apply pages in the admin green theme.',
            'Reused the login brand panel as the apply page sidebar.',
            'Matched UDC panel theme to green auth pages.',
        ],
        'links' => [
            'Login' => '../public/login.php',
            'Apply' => '../Entrepreneur/apply/index.php',
            'UDC panel' => '../Entrepreneur/panel/index.php',
        ],
    ],
    [
        'date' => '2026-10-26',
        'title' => 'Office settings moved into UDC panel',
        'details' => [
            'Moved office settings into the UDC panel with panel design.',
            'Removed office card from UDC dashboard; moved all tools link into Tools submenu.',
        ],
        'links' => [
            'Office settings' => '../Entrepreneur/panel/office.php',
            'UDC dashboard' => '../Entrepreneur/panel/index.php',
        ],
    ],
    [
        'date' => '2026-10-25',
        'title' => 'Admin-managed marquee added',
        'details' => [
            'Added admin-managed marquee to the entrepreneur panel.',
            'Removed panel marquee description texts and input note.',
            'Hold panel marquee for two seconds before scrolling.',
        ],
        'links' => [
            'Panel marquee' => 'panel-marquee.php',
        ],
    ],
    [
        'date' => '2026-10-25',
        'title' => 'SEO & Health page added',
        'details' => [
            'New admin page for SEO and health checks.',
        ],
        'links' => [
            'SEO & Health' => 'seo-health.php',
        ],
    ],
    [
        'date' => '2026-10-24',
        'title' => 'Professional avatars in panels',
        'details' => [
            'Used professional portrait avatars in admin and entrepreneur panels.',
            'Cache-busted panel avatar images.',
        ],
        'links' => [
            'Admin panel' => 'index.php',
        ],
    ],
];

/**
 * Notable plan items.
 */
$plan = [
    [
        'title' => 'Prottoyon v2 port',
        'summary' => 'Replace the 41-type prottoyon generator with a 23-type digital-union-style application module.',
        'details' => [
            'Keep Repdoc login, wallet, admin and UDC access; issue immediately (no approval queue).',
            'Print-only output: no external PDF library, no download button (window.print()).',
            'New 17-digit certificate numbers; legacy 11-digit stay valid.',
            'Reuse document_logs as the application ledger; no second application table.',
            'Public verification by certificate number; revoked certs show a "বাতিল" watermark.',
            'Application screens follow the Repdoc homepage design system; printed cert stays digital-union-style.',
            'Both present and permanent addresses, Bangla + English print variants.',
        ],
        'links' => [
            'Full plan' => '../Plan/plan.MD',
            'Prottoyon (admin)' => 'prottoyon.php',
        ],
    ],
    [
        'title' => 'Ad & generator theming',
        'summary' => 'Unify generator headers/forms in the green theme and standardize ad placements.',
        'details' => [
            'Green-bordered ad frames across generator tools.',
            'Responsive birth form ads with desktop rail.',
            'Shared placement examples in admin ad manager.',
        ],
        'links' => [
            'Ad manager' => 'ads.php',
            'Placements' => 'ad-positions.php',
        ],
    ],
];

admin_page_start('Changelog', 'changelog.php');
admin_notice();
?>

<div class="admin-page-head">
    <div>
        <p class="kicker">Project</p>
        <h1 class="admin-title">Changelog</h1>
        <p class="admin-sub">Recent changes, the notable plan, and the pending TODO list for Repdoc.</p>
    </div>
</div>

<div class="dash-grid">
    <div class="dash-main">
        <div class="panel">
            <div class="panel-head">
                TODO
                <span class="count"><?= count($todoItems) ?> tasks</span>
            </div>
            <div class="panel-body">
                <form method="post" action="changelog.php" class="todo-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add">
                    <input type="text" name="task" required maxlength="200" placeholder="New task…" class="todo-input">
                    <input type="text" name="detail" maxlength="500" placeholder="Details (optional)" class="todo-input">
                    <button type="submit" class="btn btn-primary btn-sm">Add</button>
                </form>

                <?php if ($todoItems === []): ?>
                    <div class="empty-state-sm" id="todoEmpty">No TODO items yet.</div>
                <?php else: ?>
                    <ul class="cl-todo-list" id="todoList">
                        <?php foreach ($todoItems as $t): ?>
                            <?= repdoc_todo_render_item($t) ?>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="panel">
            <div class="panel-head">
                Notable plan
                <span class="count"><?= count($plan) ?> items</span>
            </div>
            <div class="panel-body">
                <?php foreach ($plan as $p): ?>
                    <div class="cl-plan">
                        <h3 class="cl-plan-title"><?= e($p['title']) ?></h3>
                        <p class="cl-plan-summary"><?= e($p['summary']) ?></p>
                        <ul class="cl-details">
                            <?php foreach ($p['details'] as $d): ?>
                                <li><?= e($d) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if (!empty($p['links'])): ?>
                            <div class="cl-links">
                                <?php foreach ($p['links'] as $label => $url): ?>
                                    <a class="cl-link" href="<?= e($url) ?>"><?= e($label) ?> ↗</a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="dash-side">
        <div class="panel">
            <div class="panel-head">
                Changelog
                <span class="count"><?= count($changelog) ?> entries</span>
            </div>
            <div class="panel-body">
                <ul class="cl-list">
                    <?php foreach ($changelog as $entry): ?>
                        <li class="cl-item">
                            <div class="cl-item-head">
                                <span class="cl-date"><?= e($entry['date']) ?></span>
                                <span class="cl-title"><?= e($entry['title']) ?></span>
                            </div>
                            <ul class="cl-details">
                                <?php foreach ($entry['details'] as $d): ?>
                                    <li><?= e($d) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php if (!empty($entry['links'])): ?>
                                <div class="cl-links">
                                    <?php foreach ($entry['links'] as $label => $url): ?>
                                        <a class="cl-link" href="<?= e($url) ?>"><?= e($label) ?> ↗</a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="panel">
            <div class="panel-head">Quick links</div>
            <div class="panel-body quick-grid">
                <a class="quick-link" href="index.php"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-grid"/></svg>Dashboard</a>
                <a class="quick-link" href="../Plan/plan.MD" target="_blank" rel="noopener"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-file"/></svg>Full plan file</a>
            </div>
        </div>
    </div>
</div>

<style>
    .cl-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 14px; }
    .cl-item {
        padding: 12px 14px;
        border: 1px solid var(--border-color, #e2e4e9);
        border-radius: 10px;
        background: var(--bg-surface, #fff);
    }
    .cl-item-head { display: flex; align-items: baseline; gap: 10px; flex-wrap: wrap; margin-bottom: 6px; }
    .cl-date {
        font-family: var(--font-mono, monospace);
        font-size: .74rem;
        color: var(--text-muted, #6b7280);
        background: var(--bg-base, #f6f7f9);
        padding: 2px 8px;
        border-radius: 6px;
    }
    .cl-title { font-weight: 700; font-size: .92rem; color: var(--text-primary, #1f2937); }
    .cl-details { margin: 4px 0 8px 18px; padding: 0; color: var(--text-muted, #6b7280); font-size: .84rem; line-height: 1.55; }
    .cl-details li { margin-bottom: 2px; }
    .cl-links { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 6px; }
    .cl-link {
        font-size: .78rem;
        font-weight: 600;
        color: var(--primary, #2f7d5a);
        text-decoration: none;
        padding: 3px 8px;
        border: 1px solid var(--border-color, #e2e4e9);
        border-radius: 6px;
        background: var(--bg-base, #f6f7f9);
    }
    .cl-link:hover { border-color: var(--primary, #2f7d5a); }
    .cl-plan { padding: 12px 0; border-bottom: 1px dashed var(--border-color, #e2e4e9); }
    .cl-plan:last-child { border-bottom: none; }
    .cl-plan-title { margin: 0 0 4px; font-size: 1rem; font-weight: 700; color: var(--text-primary, #1f2937); }
    .cl-plan-summary { margin: 0 0 6px; font-size: .86rem; color: var(--text-muted, #6b7280); }
    .cl-todo-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 8px; }
    .cl-todo-item { display: grid; grid-template-columns: auto 1fr auto; gap: 4px 10px; align-items: center; padding: 8px 10px; border: 1px solid var(--border-color, #e2e4e9); border-radius: 8px; background: var(--bg-surface, #fff); }
    .cl-todo-item.is-done { opacity: .6; }
    .cl-todo-item.is-done .cl-todo-task { text-decoration: line-through; }
    .cl-todo-status {
        align-self: center;
        font-size: .68rem;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 999px;
        text-transform: uppercase;
        letter-spacing: .03em;
    }
    .cl-status-pending { background: #fef3c7; color: #92400e; }
    .cl-status-ongoing { background: #dbeafe; color: #1e40af; }
    .cl-status-done { background: #dcfce7; color: #166534; }
    .cl-todo-body { display: flex; flex-direction: column; gap: 2px; }
    .cl-todo-task { font-weight: 600; font-size: .86rem; color: var(--text-primary, #1f2937); }
    .cl-todo-detail { font-size: .78rem; color: var(--text-muted, #6b7280); }
    .cl-todo-actions { display: flex; gap: 6px; align-self: center; }
    .todo-form { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; }
    .todo-input {
        flex: 1 1 160px;
        min-height: 36px;
        padding: 6px 10px;
        border: 1px solid var(--border-color, #e2e4e9);
        border-radius: 8px;
        background: #fff;
        color: var(--text-primary, #1f2937);
        font: inherit;
        font-size: .84rem;
    }
</style>

<script>
(function () {
    var list = document.getElementById('todoList');
    var empty = document.getElementById('todoEmpty');
    var addForm = document.querySelector('.todo-form');

    function ensureList() {
        if (list) return list;
        list = document.createElement('ul');
        list.className = 'cl-todo-list';
        list.id = 'todoList';
        if (empty && empty.parentNode) {
            empty.parentNode.replaceChild(list, empty);
            empty = null;
        } else if (addForm && addForm.parentNode) {
            addForm.parentNode.insertBefore(list, addForm.nextSibling);
        }
        return list;
    }

    function submitForm(form) {
        var data = new FormData(form);
        data.set('_ajax', '1');
        var submitter = form.querySelector('[type="submit"]');
        if (submitter) submitter.disabled = true;
        fetch(form.getAttribute('action') || 'changelog.php', {
            method: 'POST',
            body: data,
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        }).then(function (r) { return r.json(); }).then(function (res) {
            if (!res || !res.ok) {
                alert((res && res.message) || 'Request failed.');
                return;
            }
            if (res.action === 'add') {
                ensureList();
                if (res.html && list) list.insertAdjacentHTML('afterbegin', res.html);
                if (addForm) addForm.reset();
            } else if (res.action === 'toggle') {
                if (list) {
                    var item = list.querySelector('li[data-id="' + res.id + '"]');
                    if (item && res.html) item.outerHTML = res.html;
                }
            } else if (res.action === 'delete') {
                if (list) {
                    var item = list.querySelector('li[data-id="' + res.id + '"]');
                    if (item) item.remove();
                    if (list.children.length === 0) {
                        var ph = document.createElement('div');
                        ph.className = 'empty-state-sm';
                        ph.id = 'todoEmpty';
                        ph.textContent = 'No TODO items yet.';
                        list.parentNode.replaceChild(ph, list);
                        list = null;
                        empty = ph;
                    }
                }
            }
        }).catch(function () {
            alert('Connection problem.');
        }).finally(function () {
            if (submitter) submitter.disabled = false;
        });
    }

    if (addForm) {
        addForm.addEventListener('submit', function (e) {
            e.preventDefault();
            submitForm(addForm);
        });
    }
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || !form.matches || !form.matches('form')) return;
        if (form === addForm) return;
        var li = form.closest ? form.closest('li.cl-todo-item') : null;
        if (!li) return;
        if (!list || !list.contains(li)) return;
        e.preventDefault();
        submitForm(form);
    });
})();
</script>
