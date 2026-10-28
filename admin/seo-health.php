<?php
declare(strict_types=1);

/**
 * Repdoc — admin SEO & website health.
 * Cached crawler audit, runtime index controls and a Search Console dashboard.
 */

require_once __DIR__ . '/includes/layout.php';
require_once dirname(__DIR__) . '/src/seo_audit.php';
require_once dirname(__DIR__) . '/src/gsc.php';

@set_time_limit(60);

$tab = (string) ($_GET['tab'] ?? 'seo');
if (!in_array($tab, ['seo', 'health', 'controls', 'search'], true)) {
    $tab = 'seo';
}

$postAction = (string) ($_POST['action'] ?? (isset($_POST['audit_refresh']) ? 'audit_refresh' : ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $postAction !== '') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Security check failed. Reload the page and try again.');
        redirect('seo-health.php?tab=' . $tab);
    }

    switch ($postAction) {
        case 'audit_refresh':
            $fresh = repdoc_seo_audit(true);
            flash_set('ok', 'Audit re-run — ' . count($fresh['pages']) . ' pages checked.');
            break;

        case 'seo_toggle':
            $path = (string) ($_POST['path'] ?? '');
            $mode = (string) ($_POST['mode'] ?? '');
            if ($mode === 'reset') {
                $result = repdoc_seo_reset_indexable($path);
            } elseif ($mode === 'index' || $mode === 'noindex') {
                $result = repdoc_seo_set_indexable($path, $mode === 'index');
            } else {
                $result = ['ok' => false, 'error' => 'Unknown toggle action.'];
            }
            if ($result['ok']) {
                @unlink(REPDOC_LOVE . DIRECTORY_SEPARATOR . 'seo-audit.json');
                flash_set('ok', 'Index control saved for /' . $path . '.');
            } else {
                flash_set('error', $result['error']);
            }
            redirect('seo-health.php?tab=controls');

        case 'gsc_connect':
            @set_time_limit(90);
            $token = trim((string) ($_POST['refresh_token'] ?? ''));
            if ($token === '') {
                flash_set('error', 'Paste a refresh token first.');
            } else {
                $check = repdoc_gsc_validate($token);
                if (!$check['ok']) {
                    flash_set('error', 'Token rejected: ' . $check['error']);
                } else {
                    $save = repdoc_gsc_token_save($token);
                    if (!$save['ok']) {
                        flash_set('error', $save['error']);
                    } else {
                        repdoc_gsc_cache_clear();
                        $entries = [];
                        $sitemaps = repdoc_gsc_sitemaps();
                        if ($sitemaps['ok']) $entries['sitemaps'] = ['data' => $sitemaps['data']];
                        $totals = repdoc_gsc_perf([], 30, 1);
                        if ($totals['ok']) $entries['totals'] = ['data' => $totals['data']];
                        $queries = repdoc_gsc_perf(['query'], 30, 10);
                        if ($queries['ok']) $entries['queries'] = ['data' => $queries['data']];
                        $pages = repdoc_gsc_perf(['page'], 30, 10);
                        if ($pages['ok']) $entries['pages'] = ['data' => $pages['data']];
                        if ($entries) repdoc_gsc_cache_put($entries);
                        flash_set('ok', 'Connected to ' . $check['property'] . ' (' . $check['permission'] . ').');
                    }
                }
            }
            redirect('seo-health.php?tab=search');

        case 'gsc_disconnect':
            repdoc_gsc_token_delete();
            repdoc_gsc_cache_clear();
            flash_set('ok', 'Search Console disconnected.');
            redirect('seo-health.php?tab=search');

        case 'gsc_sync':
            @set_time_limit(120);
            $entries = [];
            $errors = [];
            $sitemaps = repdoc_gsc_sitemaps();
            if ($sitemaps['ok']) $entries['sitemaps'] = ['data' => $sitemaps['data']];
            else $errors[] = 'sitemaps: ' . $sitemaps['error'];
            $totals = repdoc_gsc_perf([], 30, 1);
            if ($totals['ok']) $entries['totals'] = ['data' => $totals['data']];
            else $errors[] = 'totals: ' . $totals['error'];
            $queries = repdoc_gsc_perf(['query'], 30, 10);
            if ($queries['ok']) $entries['queries'] = ['data' => $queries['data']];
            else $errors[] = 'queries: ' . $queries['error'];
            $pages = repdoc_gsc_perf(['page'], 30, 10);
            if ($pages['ok']) $entries['pages'] = ['data' => $pages['data']];
            else $errors[] = 'pages: ' . $pages['error'];
            if ($entries) repdoc_gsc_cache_put($entries);
            if ($errors) {
                flash_set('warn', 'Partial sync — ' . implode('; ', $errors));
            } else {
                flash_set('ok', 'Search Console synced.');
            }
            redirect('seo-health.php?tab=search');

        case 'gsc_inspect':
            @set_time_limit(180);
            $requested = trim((string) ($_POST['url'] ?? ''));
            $cachedInspect = repdoc_gsc_cache_read();
            $items = $cachedInspect['entries']['inspect']['data']['items'] ?? [];
            $items = is_array($items) ? $items : [];

            $targets = [];
            if ($requested !== '') {
                foreach (repdoc_page_registry() as $page) {
                    $candidate = repdoc_canonical((string) $page['path']);
                    if ($candidate === $requested) {
                        $targets[] = $candidate;
                        break;
                    }
                }
            } else {
                $all = [];
                foreach (repdoc_page_registry() as $page) {
                    $all[] = repdoc_canonical((string) $page['path']);
                }
                $fresh = array_values(array_filter($all, static fn(string $url): bool => !isset($items[$url])));
                $targets = array_slice($fresh !== [] ? $fresh : $all, 0, 5);
            }

            if ($targets === []) {
                flash_set('error', 'Pick a page to inspect.');
                redirect('seo-health.php?tab=search');
            }

            $done = 0;
            $errors = [];
            foreach ($targets as $url) {
                $inspect = repdoc_gsc_inspect($url);
                if (!$inspect['ok']) {
                    $errors[] = $url . ': ' . $inspect['error'];
                    continue;
                }
                $items[$url] = $inspect['item'];
                $done++;
                usleep(150000);
            }

            if ($done > 0) {
                repdoc_gsc_cache_put(['inspect' => ['data' => ['site' => repdoc_gsc_site(), 'items' => $items]]]);
            }
            if ($errors) {
                flash_set('warn', $done . ' inspected; ' . count($errors) . ' failed. ' . $errors[0]);
            } elseif ($done === 1 && $requested !== '') {
                flash_set('ok', 'Inspected /' . ltrim((string) parse_url($requested, PHP_URL_PATH), '/') . '.');
            } else {
                flash_set('ok', $done . ' URLs inspected — press again for the next batch.');
            }
            redirect('seo-health.php?tab=search');

        case 'gsc_sitemap_submit':
            $submit = repdoc_gsc_sitemap_submit();
            if ($submit['ok']) {
                $sitemaps = repdoc_gsc_sitemaps();
                if ($sitemaps['ok']) {
                    repdoc_gsc_cache_put(['sitemaps' => ['data' => $sitemaps['data']]]);
                }
                flash_set('ok', 'Sitemap submitted to Search Console.');
            } else {
                flash_set('error', 'Sitemap submit failed: ' . $submit['error']);
            }
            redirect('seo-health.php?tab=search');
    }

    redirect('seo-health.php?tab=' . $tab);
}

$data = in_array($tab, ['seo', 'health'], true)
    ? repdoc_seo_audit(false)
    : ['base' => repdoc_canonical(), 'pages' => [], 'generated_at' => 0, 'cached' => true, 'counts' => [], 'scores' => []];
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

$controlStates = repdoc_seo_page_states();
$controlCounts = ['index' => 0, 'noindex' => 0, 'locked' => 0, 'sitemap' => 0, 'overrides' => 0];
foreach ($controlStates as $state) {
    if ($state['locked']) {
        $controlCounts['locked']++;
    } elseif ($state['indexable']) {
        $controlCounts['index']++;
    } else {
        $controlCounts['noindex']++;
    }
    if ($state['in_sitemap']) $controlCounts['sitemap']++;
    if ($state['override'] !== null) $controlCounts['overrides']++;
}

$gscConnected = repdoc_gsc_token_load() !== null;
$gscCache = repdoc_gsc_cache_read();
$gscEntries = $gscCache['entries'] ?? [];
$gscSitemaps = $gscEntries['sitemaps']['data'] ?? null;
$gscTotals = $gscEntries['totals']['data'] ?? null;
$gscQueries = $gscEntries['queries']['data'] ?? null;
$gscPages = $gscEntries['pages']['data'] ?? null;
$inspectData = $gscEntries['inspect']['data'] ?? null;
$inspectItems = is_array($inspectData['items'] ?? null) ? $inspectData['items'] : [];
$gscSyncedAt = (int) ($gscEntries['totals']['at'] ?? 0);

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
        <p class="admin-sub">Crawler-facing metadata, index controls and Search Console.</p>
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
    <a class="audit-tab<?= $tab === 'controls' ? ' is-active' : '' ?>" href="seo-health.php?tab=controls" role="tab" aria-selected="<?= $tab === 'controls' ? 'true' : 'false' ?>">Index Controls</a>
    <a class="audit-tab<?= $tab === 'search' ? ' is-active' : '' ?>" href="seo-health.php?tab=search" role="tab" aria-selected="<?= $tab === 'search' ? 'true' : 'false' ?>">Search Console</a>
</div>

<?php if (in_array($tab, ['seo', 'health'], true)): ?>
<div class="audit-meta">
    <span>Last run <?= e(date('M j, Y · H:i', (int) $data['generated_at'])) ?></span>
    <span><?= $data['cached'] ? 'cached (15 min)' : 'fresh run' ?></span>
    <span><?= count($data['pages']) ?> pages</span>
    <span>Base <code><?= e($data['base']) ?></code></span>
</div>
<?php endif; ?>

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

<?php elseif ($tab === 'health'): ?>

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

<?php elseif ($tab === 'controls'): ?>

<div class="stat-grid">
    <div class="stat-card">
        <span class="stat-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#a-check"/></svg></span>
        <span class="stat-text"><span class="k">Indexable</span><span class="v"><?= (int) $controlCounts['index'] ?></span><span class="s">allowed in search</span></span>
    </div>
    <div class="stat-card">
        <span class="stat-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#a-x"/></svg></span>
        <span class="stat-text"><span class="k">Noindex</span><span class="v"><?= (int) $controlCounts['noindex'] ?></span><span class="s">hidden from search</span></span>
    </div>
    <div class="stat-card">
        <span class="stat-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#a-lock"/></svg></span>
        <span class="stat-text"><span class="k">Gated</span><span class="v"><?= (int) $controlCounts['locked'] ?></span><span class="s">locked noindex</span></span>
    </div>
    <div class="stat-card">
        <span class="stat-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#a-link"/></svg></span>
        <span class="stat-text"><span class="k">In sitemap</span><span class="v"><?= (int) $controlCounts['sitemap'] ?></span><span class="s">submitted URLs</span></span>
    </div>
</div>

<?php if ($controlCounts['overrides'] > 0): ?>
    <div class="audit-note">
        <div>
            <strong><?= (int) $controlCounts['overrides'] ?> manual override<?= $controlCounts['overrides'] === 1 ? '' : 's' ?> active.</strong>
            Everything else follows the registry defaults. “Reset” removes an override; “Gated” pages stay noindex because their app layout enforces it.
        </div>
    </div>
<?php endif; ?>

<div class="panel">
    <div class="panel-head">Index controls <span class="count"><?= count($controlStates) ?> pages</span></div>
    <div class="table-wrap">
        <table class="data audit-table">
            <thead>
                <tr>
                    <th>Page</th>
                    <th>Default</th>
                    <th>Robots</th>
                    <th>Sitemap</th>
                    <th>State</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($controlStates as $state): ?>
                    <?php
                    $stateClass = $state['locked'] ? 'info' : ($state['indexable'] ? 'pass' : 'warn');
                    $stateLabel = $state['locked'] ? 'Gated' : ($state['indexable'] ? 'Indexable' : 'Noindex');
                    $pageUrl = $data['base'] . $state['path'];
                    ?>
                    <tr>
                        <td class="audit-page-cell">
                            <span class="cell-name"><?= e($state['label']) ?></span>
                            <span class="audit-path"><a href="<?= e($pageUrl) ?>" target="_blank" rel="noopener">/<?= e($state['path']) ?></a></span>
                        </td>
                        <td><?= $state['default'] ? 'Index' : 'Noindex' ?></td>
                        <td><code><?= $state['indexable'] ? 'index,follow' : 'noindex,follow' ?></code></td>
                        <td><?= $state['in_sitemap'] ? 'Yes' : 'No' ?></td>
                        <td>
                            <span class="pill pill-<?= $stateClass ?>"><?= e($stateLabel) ?></span>
                            <?php if ($state['override'] !== null): ?>
                                <span class="cell-sub">manual</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($state['locked']): ?>
                                <span class="cell-sub">Gated page — always noindex</span>
                            <?php else: ?>
                                <form method="post" action="seo-health.php?tab=controls" style="display:inline-flex;gap:6px;margin:0;flex-wrap:wrap">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="seo_toggle">
                                    <input type="hidden" name="path" value="<?= e($state['path']) ?>">
                                    <?php if ($state['indexable']): ?>
                                        <button class="btn btn-secondary btn-sm" type="submit" name="mode" value="noindex">Noindex</button>
                                    <?php else: ?>
                                        <button class="btn btn-primary btn-sm" type="submit" name="mode" value="index">Allow indexing</button>
                                    <?php endif; ?>
                                    <?php if ($state['override'] !== null): ?>
                                        <button class="btn btn-secondary btn-sm" type="submit" name="mode" value="reset">Reset</button>
                                    <?php endif; ?>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="audit-legend">Changes apply on the next request. Noindex removes the page from <code>/sitemap.xml</code>; enabling indexing adds it back. Gated pages and allowed-indexing pages keep their own app-level noindex rules.</p>
</div>

<?php else: ?>

<?php
$fmtInt = static fn($value): string => number_format((float) $value);
$fmtPct = static fn($value): string => number_format(((float) $value) * 100, 2) . '%';
$fmtPos = static fn($value): string => number_format((float) $value, 1);
$fmtWhen = static fn(int $ts): string => $ts > 0 ? date('M j · H:i', $ts) : 'never';
$fmtIso = static fn(string $iso): string => $iso !== '' ? date('M j, Y', (int) strtotime($iso)) : '—';
$verdictPill = static function (string $verdict): array {
    return match ($verdict) {
        'PASS' => ['pass', 'Pass'],
        'PARTIAL' => ['warn', 'Partial'],
        'FAIL' => ['fail', 'Fail'],
        default => ['info', 'Unknown'],
    };
};
?>

<?php if (!$gscConnected): ?>

<div class="panel">
    <div class="panel-head">Connect Search Console</div>
    <div class="panel-body">
        <div class="audit-note">
            <div>
                <strong>One-time setup.</strong>
                Create an OAuth refresh token for the Google account that owns <code><?= e(repdoc_gsc_site()) ?></code>, then paste it below.
                The token is stored in <code>love/gsc-oauth.json</code> (0600, web-denied) and exchanged server-side; no client secret is stored on the site.
            </div>
        </div>
        <form method="post" action="seo-health.php?tab=search" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;margin-top:14px">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="gsc_connect">
            <div class="field" style="flex:1 1 380px;min-width:260px">
                <label for="gscRefresh">Refresh token</label>
                <input id="gscRefresh" type="password" name="refresh_token" autocomplete="off" spellcheck="false" required>
            </div>
            <button class="btn btn-primary btn-sm" type="submit">Validate &amp; connect</button>
        </form>
    </div>
</div>

<?php else: ?>

<div class="audit-actions" style="margin-bottom:18px">
    <form method="post" action="seo-health.php?tab=search">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="gsc_sync">
        <button class="btn btn-primary btn-sm" type="submit">Refresh data</button>
    </form>
    <form method="post" action="seo-health.php?tab=search">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="gsc_sitemap_submit">
        <button class="btn btn-secondary btn-sm" type="submit">Submit sitemap</button>
    </form>
    <form method="post" action="seo-health.php?tab=search">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="gsc_inspect">
        <button class="btn btn-secondary btn-sm" type="submit">Inspect 5 pages</button>
    </form>
    <form method="post" action="seo-health.php?tab=search" onsubmit="return confirm('Disconnect Search Console and delete the stored token?')">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="gsc_disconnect">
        <button class="btn btn-secondary btn-sm" type="submit">Disconnect</button>
    </form>
</div>

<div class="audit-meta">
    <span>Property <code><?= e(repdoc_gsc_site()) ?></code></span>
    <span>Performance synced <?= e($fmtWhen($gscSyncedAt)) ?></span>
    <span>Coverage synced <?= e($fmtWhen((int) ($gscEntries['inspect']['at'] ?? 0))) ?></span>
</div>

<?php
$trow = is_array($gscTotals['rows'][0] ?? null) ? $gscTotals['rows'][0] : [];
$clicks = (int) ($trow['clicks'] ?? 0);
$impressions = (int) ($trow['impressions'] ?? 0);
$ctr = (float) ($trow['ctr'] ?? 0);
$position = (float) ($trow['position'] ?? 0);
?>

<div class="stat-grid">
    <div class="stat-card">
        <span class="stat-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#a-search"/></svg></span>
        <span class="stat-text"><span class="k">Clicks · 30d</span><span class="v"><?= $fmtInt($clicks) ?></span><span class="s">from Search</span></span>
    </div>
    <div class="stat-card">
        <span class="stat-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#a-dots"/></svg></span>
        <span class="stat-text"><span class="k">Impressions</span><span class="v"><?= $fmtInt($impressions) ?></span><span class="s">times shown</span></span>
    </div>
    <div class="stat-card">
        <span class="stat-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#a-check"/></svg></span>
        <span class="stat-text"><span class="k">CTR</span><span class="v"><?= $fmtPct($ctr) ?></span><span class="s">click-through rate</span></span>
    </div>
    <div class="stat-card">
        <span class="stat-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#a-pulse"/></svg></span>
        <span class="stat-text"><span class="k">Avg position</span><span class="v"><?= $fmtPos($position) ?></span><span class="s">average rank</span></span>
    </div>
</div>

<div class="panel">
    <div class="panel-head">Sitemaps <span class="count"><?= e($fmtWhen((int) ($gscEntries['sitemaps']['at'] ?? 0))) ?></span></div>
    <?php $sitemapRows = is_array($gscSitemaps['sitemap'] ?? null) ? $gscSitemaps['sitemap'] : []; ?>
    <?php if (!$sitemapRows): ?>
        <div class="panel-body"><p class="cell-sub">No sitemap data yet. Press “Submit sitemap” or “Refresh data”.</p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data audit-table">
                <thead><tr><th>Sitemap</th><th>Last submitted</th><th>Last downloaded</th><th>Errors</th><th>Warnings</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach ($sitemapRows as $sm): ?>
                        <?php
                        $smErrors = (int) ($sm['errors'] ?? 0);
                        $smWarnings = (int) ($sm['warnings'] ?? 0);
                        $smPending = !empty($sm['isPending']);
                        $smClass = $smErrors > 0 ? 'fail' : ($smWarnings > 0 ? 'warn' : 'pass');
                        $smLabel = $smErrors > 0 ? 'Errors' : ($smWarnings > 0 ? 'Warnings' : ($smPending ? 'Pending' : 'OK'));
                        if ($smPending && $smErrors === 0 && $smWarnings === 0) $smClass = 'info';
                        ?>
                        <tr>
                            <td class="audit-page-cell"><span class="audit-path"><a href="<?= e((string) ($sm['path'] ?? '')) ?>" target="_blank" rel="noopener"><?= e((string) ($sm['path'] ?? '')) ?></a></span></td>
                            <td><?= e($fmtIso((string) ($sm['lastSubmitted'] ?? ''))) ?></td>
                            <td><?= e($fmtIso((string) ($sm['lastDownloaded'] ?? ''))) ?></td>
                            <td><?= $smErrors ?></td>
                            <td><?= $smWarnings ?></td>
                            <td><span class="pill pill-<?= $smClass ?>"><?= e($smLabel) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
$queryRows = is_array($gscQueries['rows'] ?? null) ? $gscQueries['rows'] : [];
$pageRows = is_array($gscPages['rows'] ?? null) ? $gscPages['rows'] : [];
$perfTable = static function (array $rows, string $keyLabel, bool $isUrl) use ($fmtInt, $fmtPct, $fmtPos): void {
    ?>
    <div class="table-wrap">
        <table class="data audit-table">
            <thead><tr><th><?= e($keyLabel) ?></th><th>Clicks</th><th>Impressions</th><th>CTR</th><th>Position</th></tr></thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <?php
                    $key = (string) ($row['keys'][0] ?? '');
                    if ($isUrl) {
                        $parts = parse_url($key);
                        $parts = is_array($parts) ? $parts : [];
                        $key = $parts['path'] ?? $key;
                        if (isset($parts['query'])) $key .= '?' . $parts['query'];
                    }
                    ?>
                    <tr>
                        <td class="audit-page-cell"><?= e($key) ?></td>
                        <td><?= $fmtInt($row['clicks'] ?? 0) ?></td>
                        <td><?= $fmtInt($row['impressions'] ?? 0) ?></td>
                        <td><?= $fmtPct($row['ctr'] ?? 0) ?></td>
                        <td><?= $fmtPos($row['position'] ?? 0) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
};
?>

<div class="panel">
    <div class="panel-head">Top queries <span class="count"><?= count($queryRows) ?></span></div>
    <?php if (!$queryRows): ?>
        <div class="panel-body"><p class="cell-sub">No query data for the last 30 days.</p></div>
    <?php else: ?>
        <?php $perfTable($queryRows, 'Query', false); ?>
    <?php endif; ?>
</div>

<div class="panel">
    <div class="panel-head">Top pages <span class="count"><?= count($pageRows) ?></span></div>
    <?php if (!$pageRows): ?>
        <div class="panel-body"><p class="cell-sub">No page data for the last 30 days.</p></div>
    <?php else: ?>
        <?php $perfTable($pageRows, 'Page', true); ?>
    <?php endif; ?>
</div>

<div class="panel">
    <div class="panel-head">Coverage <span class="count"><?= count($inspectItems) ?> inspected</span></div>
    <div class="panel-body" style="padding-bottom:0">
        <p class="cell-sub">Search Console has no bulk coverage API. Each inspection is one slow call, so inspect a single page from its row or batch five at a time. Results are cached until you re-check them.</p>
    </div>
    <div class="table-wrap">
        <table class="data audit-table">
            <thead><tr><th>Page</th><th>Coverage</th><th>Verdict</th><th>Last crawl</th><th>Canonical</th><th>Action</th></tr></thead>
            <tbody>
                <?php foreach (repdoc_page_registry() as $regPage): ?>
                    <?php
                    $inspectUrl = repdoc_canonical((string) $regPage['path']);
                    $item = $inspectItems[$inspectUrl] ?? null;
                    [$vClass, $vLabel] = $item !== null ? $verdictPill((string) ($item['verdict'] ?? '')) : ['info', 'Not inspected'];
                    $canonicalMismatch = $item !== null
                        && ($item['googleCanonical'] ?? '') !== ''
                        && ($item['userCanonical'] ?? '') !== ''
                        && $item['googleCanonical'] !== $item['userCanonical'];
                    ?>
                    <tr>
                        <td class="audit-page-cell">
                            <span class="cell-name"><?= e((string) $regPage['label']) ?></span>
                            <span class="audit-path"><a href="<?= e($inspectUrl) ?>" target="_blank" rel="noopener">/<?= e((string) $regPage['path']) ?></a></span>
                        </td>
                        <td class="cell-sub"><?= e((string) ($item['coverage'] ?? '—')) ?></td>
                        <td><span class="pill pill-<?= $vClass ?>"><?= e($vLabel) ?></span></td>
                        <td><?= e($item !== null ? $fmtIso((string) ($item['crawl'] ?? '')) : '—') ?></td>
                        <td>
                            <?php if ($item === null): ?>
                                —
                            <?php elseif ($canonicalMismatch): ?>
                                <span class="pill pill-warn" title="Google chose <?= e((string) $item['googleCanonical']) ?>">Mismatch</span>
                            <?php else: ?>
                                <span class="cell-sub">OK</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="post" action="seo-health.php?tab=search" style="margin:0">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="gsc_inspect">
                                <input type="hidden" name="url" value="<?= e($inspectUrl) ?>">
                                <button class="btn btn-secondary btn-sm" type="submit"><?= $item === null ? 'Inspect' : 'Re-check' ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

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
