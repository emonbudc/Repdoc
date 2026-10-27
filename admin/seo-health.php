<?php
declare(strict_types=1);

/**
 * Repdoc — admin SEO & website health.
 * Read-only audit of crawler-facing metadata, host access and runtime health.
 */

require_once __DIR__ . '/includes/layout.php';
require_once dirname(__DIR__) . '/src/seo_audit.php';

@set_time_limit(60);

$tab = (string) ($_GET['tab'] ?? 'seo');
if (!in_array($tab, ['seo', 'health'], true)) {
    $tab = 'seo';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['audit_refresh'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Security check failed. Reload the page and try again.');
    } else {
        $fresh = repdoc_seo_audit(true);
        flash_set('ok', 'Audit re-run — ' . count($fresh['pages']) . ' pages checked.');
    }
    redirect('seo-health.php?tab=' . $tab);
}

$data = repdoc_seo_audit(false);
$counts = $data['counts'][$tab] ?? ['pass' => 0, 'warn' => 0, 'fail' => 0, 'info' => 0];
$score = (int) ($data['scores'][$tab] ?? 0);

$statusLabels = ['pass' => 'OK', 'warn' => 'Warn', 'fail' => 'Fail', 'info' => 'n/a'];

$pill = static function (array $check, string $short = '') use ($statusLabels): string {
    $status = (string) ($check['status'] ?? 'info');
    if (!isset($statusLabels[$status])) {
        $status = 'info';
    }
    $label = $short !== '' ? $short : $statusLabels[$status];
    $tip = trim(($check['label'] ?? '') . ' — ' . ($check['detail'] ?? ''), ' —');
    return '<span class="pill pill-' . $status . '" title="' . e($tip) . '">' . e($label) . '</span>';
};

$cell = static function (array $check) use ($statusLabels): string {
    $status = (string) ($check['status'] ?? 'info');
    if (!isset($statusLabels[$status])) {
        $status = 'info';
    }
    $glyph = ['pass' => 'OK', 'warn' => '!', 'fail' => '×', 'info' => '–'][$status];
    $tip = trim(($check['label'] ?? '') . ' — ' . ($check['detail'] ?? ''), ' —');
    return '<span class="audit-cell audit-' . $status . '" role="img" aria-label="' . e($statusLabels[$status]) . '" title="' . e($tip) . '">' . e($glyph) . '</span>';
};

$problemPages = 0;
foreach ($data['pages'] as $page) {
    if (in_array((string) $page['status'], ['warn', 'fail'], true)) {
        $problemPages++;
    }
}

$healthGroups = [
    'runtime'    => ['label' => 'Runtime & PHP', 'icon' => 'a-layout'],
    'filesystem' => ['label' => 'Files & exposure', 'icon' => 'a-file'],
    'security'   => ['label' => 'Security', 'icon' => 'a-lock'],
    'content'    => ['label' => 'Live content', 'icon' => 'a-link'],
];

$wallDetected = ($data['host']['wall']['status'] ?? '') === 'warn';

admin_page_start('SEO & Health', 'seo-health.php');
admin_notice();
?>

<div class="admin-page-head dash-head">
    <div>
        <p class="kicker">Search</p>
        <h1 class="admin-title">SEO &amp; Health</h1>
        <p class="admin-sub">Crawler-facing metadata, host access and runtime checks. Read-only.</p>
    </div>
    <div class="audit-actions">
        <a class="btn btn-secondary btn-sm" href="<?= e($data['base']) ?>" target="_blank" rel="noopener">
            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-external"/></svg>
            Open site
        </a>
        <form method="post" action="seo-health.php?tab=<?= e($tab) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="audit_refresh" value="1">
            <button class="btn btn-primary btn-sm" type="submit">Re-run audit</button>
        </form>
    </div>
</div>

<div class="audit-tabs" role="tablist" aria-label="Audit sections">
    <a class="audit-tab<?= $tab === 'seo' ? ' is-active' : '' ?>" href="seo-health.php" role="tab" aria-selected="<?= $tab === 'seo' ? 'true' : 'false' ?>">SEO Audit</a>
    <a class="audit-tab<?= $tab === 'health' ? ' is-active' : '' ?>" href="seo-health.php?tab=health" role="tab" aria-selected="<?= $tab === 'health' ? 'true' : 'false' ?>">Website Health</a>
</div>

<div class="audit-meta">
    <span>Last run <?= e(date('M j, Y · H:i', (int) $data['generated_at'])) ?></span>
    <span><?= $data['cached'] ? 'cached (15 min)' : 'fresh run' ?></span>
    <span><?= count($data['pages']) ?> pages</span>
    <span>Base <code><?= e($data['base']) ?></code></span>
</div>

<?php if ($tab === 'seo'): ?>

<div class="stat-grid">
    <div class="stat-card">
        <span class="stat-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#a-search"/></svg></span>
        <span class="stat-text"><span class="k">SEO score</span><span class="v"><?= $score ?>/100</span><span class="s">weighted pass rate</span></span>
    </div>
    <div class="stat-card">
        <span class="stat-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#a-check"/></svg></span>
        <span class="stat-text"><span class="k">Checks passed</span><span class="v"><?= number_format((int) $counts['pass']) ?></span><span class="s"><?= number_format((int) $counts['info']) ?> informational</span></span>
    </div>
    <div class="stat-card">
        <span class="stat-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#a-dots"/></svg></span>
        <span class="stat-text"><span class="k">Warnings</span><span class="v"><?= number_format((int) $counts['warn']) ?></span><span class="s">worth a look</span></span>
    </div>
    <div class="stat-card">
        <span class="stat-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#a-x"/></svg></span>
        <span class="stat-text"><span class="k">Failures</span><span class="v"><?= number_format((int) $counts['fail']) ?></span><span class="s">blocking a check</span></span>
    </div>
</div>

<div class="panel">
    <div class="panel-head">Host access <span class="count">bot wall</span></div>
    <div class="panel-body">
        <?php if ($wallDetected): ?>
            <div class="audit-note">
                <div>
                    <strong>Host bot wall detected.</strong>
                    Free hosting serves a JavaScript challenge to browser user agents that are not on its crawler whitelist.
                    Third-party checkers and some social card fetchers see that challenge instead of your HTML, so they can
                    report missing titles and descriptions even when the page has them. Googlebot and Bingbot are whitelisted.
                </div>
            </div>
        <?php endif; ?>
        <div class="table-wrap">
            <table class="data audit-table">
                <thead><tr><th>Check</th><th>Status</th><th>Detail</th></tr></thead>
                <tbody>
                    <?php foreach ($data['host'] as $check): ?>
                        <tr>
                            <td><?= e((string) $check['label']) ?></td>
                            <td><?= $pill($check) ?></td>
                            <td class="cell-sub"><?= e((string) $check['detail']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="panel">
    <div class="panel-head">Site-wide SEO <span class="count"><?= count($data['site']) ?> checks</span></div>
    <div class="table-wrap">
        <table class="data audit-table">
            <thead><tr><th>Check</th><th>Status</th><th>Detail</th></tr></thead>
            <tbody>
                <?php foreach ($data['site'] as $check): ?>
                    <tr>
                        <td><?= e((string) $check['label']) ?></td>
                        <td><?= $pill($check) ?></td>
                        <td class="cell-sub"><?= e((string) $check['detail']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="panel">
    <div class="panel-head">
        Page audit
        <span class="count"><?= count($data['pages']) ?> pages</span>
        <label class="audit-toggle">
            <input type="checkbox" id="auditProblemsOnly">
            Problems only (<?= $problemPages ?>)
        </label>
    </div>
    <div class="table-wrap">
        <table class="data audit-table" id="auditPages">
            <thead>
                <tr>
                    <th>Page</th>
                    <th>Title</th>
                    <th>Desc</th>
                    <th>Viewport</th>
                    <th>Canonical</th>
                    <th>Social</th>
                    <th>H1</th>
                    <th>JSON-LD</th>
                    <th>Index</th>
                    <th>Sitemap</th>
                    <th>Score</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['pages'] as $page): ?>
                    <?php $checks = $page['checks']; ?>
                    <tr data-status="<?= e((string) $page['status']) ?>">
                        <td class="audit-page-cell">
                            <span class="cell-name"><?= e((string) $page['label']) ?></span>
                            <span class="audit-path"><a href="<?= e($data['base'] . $page['path']) ?>" target="_blank" rel="noopener">/<?= e((string) $page['path']) ?></a></span>
                        </td>
                        <td><?= $cell($checks['title'] ?? []) ?></td>
                        <td><?= $cell($checks['description'] ?? []) ?></td>
                        <td><?= $cell($checks['viewport'] ?? []) ?></td>
                        <td><?= $cell($checks['canonical'] ?? []) ?></td>
                        <td><?= $cell($checks['social'] ?? []) ?></td>
                        <td><?= $cell($checks['h1'] ?? []) ?></td>
                        <td><?= $cell($checks['jsonld'] ?? []) ?></td>
                        <td><?= $cell($checks['indexability'] ?? []) ?></td>
                        <td><?= $cell($checks['sitemap'] ?? []) ?></td>
                        <td class="audit-score is-<?= e((string) $page['status']) ?>"><?= (int) $page['score'] ?></td>
                        <td><?= $pill(['status' => $page['status'], 'label' => 'Page status', 'detail' => 'Score ' . (int) $page['score'] . '/100']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="audit-legend">Hover any cell for the check detail. Gated pages are expected to report <code>n/a</code> for description, canonical and social tags.</p>
</div>

<?php else: ?>

<div class="stat-grid">
    <div class="stat-card">
        <span class="stat-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#a-pulse"/></svg></span>
        <span class="stat-text"><span class="k">Health score</span><span class="v"><?= $score ?>/100</span><span class="s">weighted pass rate</span></span>
    </div>
    <div class="stat-card">
        <span class="stat-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#a-check"/></svg></span>
        <span class="stat-text"><span class="k">Checks passed</span><span class="v"><?= number_format((int) $counts['pass']) ?></span><span class="s"><?= number_format((int) $counts['info']) ?> informational</span></span>
    </div>
    <div class="stat-card">
        <span class="stat-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#a-dots"/></svg></span>
        <span class="stat-text"><span class="k">Warnings</span><span class="v"><?= number_format((int) $counts['warn']) ?></span><span class="s">worth a look</span></span>
    </div>
    <div class="stat-card">
        <span class="stat-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#a-x"/></svg></span>
        <span class="stat-text"><span class="k">Failures</span><span class="v"><?= number_format((int) $counts['fail']) ?></span><span class="s">blocking a check</span></span>
    </div>
</div>

<?php foreach ($healthGroups as $key => $group): ?>
    <?php $checks = $data['health'][$key] ?? []; ?>
    <?php if (!$checks) continue; ?>
    <div class="panel">
        <div class="panel-head">
            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#<?= e($group['icon']) ?>"/></svg>
            <?= e($group['label']) ?>
            <span class="count"><?= count($checks) ?> checks</span>
        </div>
        <div class="table-wrap">
            <table class="data audit-table">
                <thead><tr><th>Check</th><th>Status</th><th>Detail</th></tr></thead>
                <tbody>
                    <?php foreach ($checks as $check): ?>
                        <tr>
                            <td><?= e((string) $check['label']) ?></td>
                            <td><?= $pill($check) ?></td>
                            <td class="cell-sub"><?= e((string) $check['detail']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endforeach; ?>

<?php endif; ?>

<script>
(function () {
    var box = document.getElementById('auditProblemsOnly');
    if (!box) return;
    box.addEventListener('change', function () {
        var rows = document.querySelectorAll('#auditPages tbody tr');
        for (var i = 0; i < rows.length; i++) {
            var status = rows[i].getAttribute('data-status');
            var bad = status === 'fail' || status === 'warn';
            rows[i].hidden = box.checked && !bad;
        }
    });
})();
</script>

<?php admin_page_end(); ?>
