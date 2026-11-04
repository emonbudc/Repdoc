<?php
declare(strict_types=1);

/**
 * Repdoc — admin: changelog, notable plan and TODO.
 */

require_once __DIR__ . '/includes/layout.php';

function changelog_read_file(string $path): string
{
    if (!is_file($path) || !is_readable($path)) {
        return '';
    }
    $raw = (string) file_get_contents($path);
    return trim($raw);
}

$root = dirname(__DIR__);
$changelogText = changelog_read_file($root . '/CHANGELOG.md');
$todoText = changelog_read_file($root . '/TODO.md');
$planPath = $root . '/Plan/plan.MD';
$planText = changelog_read_file($planPath);

// Condensed notable plan highlights (kept in sync with Plan/plan.MD).
$planHighlights = [
    'Replace the 41-type prottoyon generator with a 23-type digital-union-style application module.',
    'Keep Repdoc login, wallet, admin and UDC access; issue immediately (no approval queue).',
    'Print-only output: no external PDF library, no download button (window.print()).',
    'New 17-digit certificate numbers (YY + union_code(7) + sequence(8)); legacy 11-digit stay valid.',
    'Reuse document_logs as the application ledger; no second application table.',
    'Public verification by certificate number; revoked certs show a "বাতিল" watermark.',
    'Application screens follow the Repdoc homepage design system; printed cert stays digital-union-style.',
    'Both present and permanent addresses, Bangla + English print variants.',
    'Trade license prints fee/tax table and expiry; fees are declarations, not wallet charges.',
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
                <span class="count">CHANGELOG.md</span>
            </div>
            <div class="panel-body">
                <?php if ($changelogText === ''): ?>
                    <div class="empty-state-sm">No changelog file found.</div>
                <?php else: ?>
                    <pre class="cl-pre"><?= e($changelogText) ?></pre>
                <?php endif; ?>
            </div>
        </div>

        <div class="panel">
            <div class="panel-head">
                Notable plan
                <span class="count">Plan/plan.MD</span>
            </div>
            <div class="panel-body">
                <ul class="cl-list">
                    <?php foreach ($planHighlights as $line): ?>
                        <li><?= e($line) ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($planText !== ''): ?>
                    <details class="cl-details">
                        <summary>View full plan (<?= number_format(strlen($planText)) ?> chars)</summary>
                        <pre class="cl-pre"><?= e($planText) ?></pre>
                    </details>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="dash-side">
        <div class="panel">
            <div class="panel-head">
                TODO
                <span class="count">TODO.md</span>
            </div>
            <div class="panel-body">
                <?php if ($todoText === ''): ?>
                    <div class="empty-state-sm">No TODO file found.</div>
                <?php else: ?>
                    <pre class="cl-pre"><?= e($todoText) ?></pre>
                <?php endif; ?>
            </div>
        </div>

        <div class="panel">
            <div class="panel-head">Quick links</div>
            <div class="panel-body quick-grid">
                <a class="quick-link" href="index.php"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-grid"/></svg>Dashboard</a>
                <a class="quick-link" href="../Plan/plan.MD" target="_blank" rel="noopener"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-file"/></svg>Plan file</a>
            </div>
        </div>
    </div>
</div>

<style>
    .cl-pre {
        margin: 0;
        padding: 12px 14px;
        background: var(--bg-base, #f6f7f9);
        border: 1px solid var(--border-color, #e2e4e9);
        border-radius: 8px;
        font-family: var(--font-mono, monospace);
        font-size: .82rem;
        line-height: 1.55;
        color: var(--text-primary, #1f2937);
        white-space: pre-wrap;
        word-break: break-word;
        max-height: 480px;
        overflow: auto;
    }
    .cl-list {
        margin: 0 0 12px;
        padding-left: 18px;
        color: var(--text-primary, #1f2937);
        font-size: .88rem;
        line-height: 1.6;
    }
    .cl-details { margin-top: 8px; }
    .cl-details summary {
        cursor: pointer;
        color: var(--text-muted, #6b7280);
        font-size: .82rem;
        font-weight: 600;
    }
    .cl-details[open] summary { margin-bottom: 8px; }
</style>

<?php admin_page_end(); ?>
