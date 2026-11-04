<?php
declare(strict_types=1);

/**
 * Repdoc — admin: changelog, notable plan and TODO.
 *
 * Visual list with details and resource links (not markdown files).
 */

require_once __DIR__ . '/includes/layout.php';
require_once dirname(__DIR__) . '/src/todo.php';

// Seed defaults on first run.
repdoc_todo_seed_defaults();

// ── POST: add / toggle done / delete ────────────────────────────────
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Session expired — please try again.');
        redirect('changelog.php');
    }
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'add') {
        $task = (string) ($_POST['task'] ?? '');
        $detail = (string) ($_POST['detail'] ?? '');
        if (repdoc_todo_add($task, $detail)) {
            flash_set('ok', 'TODO added.');
        } else {
            flash_set('error', 'Task text is required.');
        }
        redirect('changelog.php');
    }
    $id = (string) ($_POST['id'] ?? '');
    if ($action === 'toggle') {
        if (repdoc_todo_toggle_done($id)) {
            flash_set('ok', 'TODO updated.');
        } else {
            flash_set('error', 'Could not update TODO.');
        }
        redirect('changelog.php');
    }
    if ($action === 'delete') {
        if (repdoc_todo_delete($id)) {
            flash_set('ok', 'TODO deleted.');
        } else {
            flash_set('error', 'Could not delete TODO.');
        }
        redirect('changelog.php');
    }
    flash_set('error', 'Unknown action.');
    redirect('changelog.php');
}

$todoItems = repdoc_todo_load_all();

/**
 * Changelog entries, newest first.
 * Each: date, title, details (string[]), links (label => url relative to admin/ or absolute).
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

/**
 * TODO items, grouped.
 */
$todoGroups = [
    'Prottoyon v2' => [
        ['task' => 'Schema & helpers', 'detail' => 'number_sequences table, indexes, office columns, canonical type registry.', 'status' => 'Pending'],
        ['task' => 'Pricing & labels', 'detail' => '23 prottoyon types, aliases, homepage/nav/UDC copy.', 'status' => 'Pending'],
        ['task' => 'Frontend shell', 'detail' => 'Homepage header/nav/footer, tokens, type picker, form/list/verify styles.', 'status' => 'Pending'],
        ['task' => 'Form & submission', 'detail' => 'Validation, uploads, 17-digit number, wallet charge, log insert.', 'status' => 'Pending'],
        ['task' => 'Renderer', 'detail' => 'v2 templates, both addresses, Bangla/English, print CSS, QR.', 'status' => 'Pending'],
        ['task' => 'Applicant pages', 'detail' => 'List, view, reapply, ownership.', 'status' => 'Pending'],
        ['task' => 'Admin & UDC pages', 'detail' => 'Filters, view, print, revoke/restore.', 'status' => 'Pending'],
        ['task' => 'Public verification', 'detail' => 'verify/index.php by certificate number.', 'status' => 'Pending'],
        ['task' => 'Legacy fallback & migration checks', 'detail' => 'Old 41-type records remain readable.', 'status' => 'Pending'],
        ['task' => 'Security & QA pass', 'detail' => 'CSRF, ownership, uploads, no PDF endpoint.', 'status' => 'Pending'],
    ],
    'General' => [
        ['task' => 'php -l on changed files', 'detail' => 'Run syntax checks before deploy.', 'status' => 'Ongoing'],
        ['task' => 'Keep sidebars in sync', 'detail' => 'Update admin/UDC nav when adding pages.', 'status' => 'Ongoing'],
        ['task' => 'Office profile completeness', 'detail' => 'Union code, contacts, logo/stamp before issuing certs.', 'status' => 'Pending'],
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
                    <div class="empty-state-sm">No TODO items yet.</div>
                <?php else: ?>
                    <ul class="cl-todo-list">
                        <?php foreach ($todoItems as $t): ?>
                            <li class="cl-todo-item<?= $t['status'] === 'done' ? ' is-done' : '' ?>">
                                <span class="cl-todo-status cl-status-<?= e($t['status']) ?>"><?= e($t['status']) ?></span>
                                <div class="cl-todo-body">
                                    <span class="cl-todo-task"><?= e($t['task']) ?></span>
                                    <?php if ($t['detail'] !== ''): ?>
                                        <span class="cl-todo-detail"><?= e($t['detail']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="cl-todo-actions">
                                    <form method="post" action="changelog.php" style="margin:0">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?= e($t['id']) ?>">
                                        <button type="submit" class="btn btn-secondary btn-sm">
                                            <?= $t['status'] === 'done' ? 'Undo' : 'Mark done' ?>
                                        </button>
                                    </form>
                                    <form method="post" action="changelog.php" style="margin:0" onsubmit="return confirm('Delete this TODO?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= e($t['id']) ?>">
                                        <button type="submit" class="btn btn-secondary btn-sm" aria-label="Delete">
                                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-trash"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
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
    .cl-todo-group { margin: 12px 0 6px; font-size: .86rem; font-weight: 700; color: var(--text-primary, #1f2937); }
    .cl-todo-group:first-child { margin-top: 0; }
    .cl-todo-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 8px; }
    .cl-todo-item { display: grid; grid-template-columns: auto 1fr; gap: 4px 10px; align-items: baseline; padding: 8px 10px; border: 1px solid var(--border-color, #e2e4e9); border-radius: 8px; background: var(--bg-surface, #fff); }
    .cl-todo-status {
        grid-row: 1 / 3;
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
    .cl-todo-task { font-weight: 600; font-size: .86rem; color: var(--text-primary, #1f2937); }
    .cl-todo-detail { font-size: .78rem; color: var(--text-muted, #6b7280); }
    .cl-todo-item.is-done { opacity: .6; }
    .cl-todo-item.is-done .cl-todo-task { text-decoration: line-through; }
    .cl-todo-body { display: flex; flex-direction: column; gap: 2px; }
    .cl-todo-actions { grid-column: 2; display: flex; gap: 6px; margin-top: 4px; }
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

<?php admin_page_end(); ?>
