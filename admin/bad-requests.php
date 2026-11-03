<?php
declare(strict_types=1);

/**
 * Repdoc — admin: unauthorized / bad request tracker.
 *
 * Rows are written by 404.php (wired as ErrorDocument 403 and 404), so every
 * missing or blocked URL is recorded: path, query, IP, user agent, referer,
 * hit count and first/last seen. Paths that look like automated probing
 * (wp-login, .env, phpmyadmin, /admin/login, …) are tagged `probe`.
 */

require_once __DIR__ . '/includes/layout.php';

$db = repdoc_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Security token mismatch.');
        redirect('bad-requests.php');
    }

    if ((string) ($_POST['action'] ?? '') === 'clear') {
        try {
            $db->exec('DELETE FROM bad_requests');
            flash_set('ok', 'Bad request log cleared.');
        } catch (Throwable $e) {
            flash_set('error', 'Could not clear the log.');
        }
        redirect('bad-requests.php');
    }
}

$filterStatus = (string) ($_GET['status'] ?? 'all');
if (!in_array($filterStatus, ['all', '403', '404'], true)) $filterStatus = 'all';

$filterDays = (string) ($_GET['days'] ?? '7');
if (!in_array($filterDays, ['1', '7', '30', 'all'], true)) $filterDays = '7';

$probeOnly = (string) ($_GET['probe'] ?? '') === '1';
$search    = trim((string) ($_GET['q'] ?? ''));

$rows  = [];
$stats = ['hits' => 0, 'unique' => 0, 'today' => 0, 'probes' => 0];

if ($db) {
    try {
        $stats['hits']   = (int) $db->query('SELECT COALESCE(SUM(hits), 0) FROM bad_requests')->fetchColumn();
        $stats['unique'] = (int) $db->query('SELECT COUNT(*) FROM bad_requests')->fetchColumn();
        $stats['probes'] = (int) $db->query('SELECT COUNT(*) FROM bad_requests WHERE probe = 1')->fetchColumn();

        $st = $db->prepare('SELECT COALESCE(SUM(hits), 0) FROM bad_requests WHERE last_seen >= ?');
        $st->execute([date('Y-m-d 00:00:00')]);
        $stats['today'] = (int) $st->fetchColumn();

        $where  = [];
        $params = [];

        if ($filterStatus !== 'all') {
            $where[]  = 'status = ?';
            $params[] = (int) $filterStatus;
        }
        if ($probeOnly) {
            $where[] = 'probe = 1';
        }
        if ($filterDays !== 'all') {
            $where[]  = 'last_seen >= ?';
            $params[] = date('Y-m-d 00:00:00', strtotime('-' . ((int) $filterDays - 1) . ' days'));
        }
        if ($search !== '') {
            $where[] = '(path LIKE ? OR query LIKE ? OR ip LIKE ? OR user_agent LIKE ?)';
            $like = '%' . $search . '%';
            array_push($params, $like, $like, $like, $like);
        }

        $sql = 'SELECT * FROM bad_requests'
             . ($where !== [] ? ' WHERE ' . implode(' AND ', $where) : '')
             . ' ORDER BY last_seen DESC, hits DESC LIMIT 300';
        $st = $db->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll();
    } catch (Throwable $e) {
        $db = null;
    }
}

/** Short human label for a user agent. */
if (!function_exists('badreq_device')) {
    function badreq_device(string $ua): string
    {
        if ($ua === '') return '—';
        $map = [
            'bot|crawl|spider|slurp|curl|wget|python|go-http|java' => 'Bot',
            'android'            => 'Android',
            'iphone|ipad|ipod'   => 'iOS',
            'windows'            => 'Windows',
            'mac os x|macintosh' => 'macOS',
            'linux'              => 'Linux',
        ];
        $low = strtolower($ua);
        foreach ($map as $needle => $label) {
            if (preg_match('~' . $needle . '~', $low)) return $label;
        }
        return 'Other';
    }
}

admin_page_start('Bad Requests', 'bad-requests.php');
admin_notice();
?>

<div class="admin-page-head">
    <div>
        <p class="kicker">Security</p>
        <h1 class="admin-title">Bad request tracker</h1>
        <p class="admin-sub">Every blocked or missing URL — including debug probes like <code>/admin/login</code>, <code>/wp-login.php</code> and <code>/.env</code>.</p>
    </div>
    <?php if ($db && $stats['unique'] > 0): ?>
        <form method="post" action="bad-requests.php" onsubmit="return confirm('Clear the whole bad request log?');">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="clear">
            <button type="submit" class="btn btn-secondary btn-sm">Clear log</button>
        </form>
    <?php endif; ?>
</div>

<?php if (!$db): ?>
    <div class="alert alert-error" role="alert">Database connection unavailable.</div>
<?php else: ?>

<div class="stat-grid">
    <div class="stat-card">
        <span class="stat-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#a-shield"/></svg></span>
        <span class="stat-text"><span class="k">Total hits</span><span class="v"><?= number_format($stats['hits']) ?></span><span class="s">403 + 404</span></span>
    </div>
    <div class="stat-card">
        <span class="stat-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#a-link"/></svg></span>
        <span class="stat-text"><span class="k">Unique targets</span><span class="v"><?= number_format($stats['unique']) ?></span><span class="s">path + IP</span></span>
    </div>
    <div class="stat-card">
        <span class="stat-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#a-log"/></svg></span>
        <span class="stat-text"><span class="k">Today</span><span class="v"><?= number_format($stats['today']) ?></span><span class="s">hits</span></span>
    </div>
    <div class="stat-card">
        <span class="stat-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#a-search"/></svg></span>
        <span class="stat-text"><span class="k">Probes</span><span class="v"><?= number_format($stats['probes']) ?></span><span class="s">scan patterns</span></span>
    </div>
</div>

<form class="filter-bar" method="get" action="bad-requests.php">
    <div class="field">
        <label for="q">Search</label>
        <input type="search" id="q" name="q" value="<?= e($search) ?>" placeholder="path, query, IP or user agent">
    </div>
    <div class="field" style="flex:0 0 150px">
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="all"<?= $filterStatus === 'all' ? ' selected' : '' ?>>All</option>
            <option value="403"<?= $filterStatus === '403' ? ' selected' : '' ?>>403</option>
            <option value="404"<?= $filterStatus === '404' ? ' selected' : '' ?>>404</option>
        </select>
    </div>
    <div class="field" style="flex:0 0 150px">
        <label for="days">Period</label>
        <select id="days" name="days">
            <option value="1"<?= $filterDays === '1' ? ' selected' : '' ?>>Today</option>
            <option value="7"<?= $filterDays === '7' ? ' selected' : '' ?>>7 days</option>
            <option value="30"<?= $filterDays === '30' ? ' selected' : '' ?>>30 days</option>
            <option value="all"<?= $filterDays === 'all' ? ' selected' : '' ?>>All time</option>
        </select>
    </div>
    <div class="field" style="flex:0 0 auto">
        <label for="probe">Filter</label>
        <label class="tp-price-active" style="min-height:42px" for="probe">
            <input type="checkbox" id="probe" name="probe" value="1"<?= $probeOnly ? ' checked' : '' ?>>
            Probes only
        </label>
    </div>
    <div class="field" style="flex:0 0 auto;align-self:flex-end">
        <button type="submit" class="btn btn-primary btn-sm">Apply</button>
        <a class="btn btn-secondary btn-sm" href="bad-requests.php">Reset</a>
    </div>
</form>

<div class="panel">
    <div class="panel-head">
        Log
        <span class="count"><?= number_format(count($rows)) ?> rows</span>
    </div>
    <div class="panel-body">
        <?php if ($rows === []): ?>
            <div class="empty-state-sm">No records for this filter — nothing missing or blocked has been tracked yet.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Path</th>
                            <th>Hits</th>
                            <th>IP</th>
                            <th>Device</th>
                            <th>Last seen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <?php
                            $status = (int) $row['status'];
                            $statusClass = $status === 403 ? 'pill-rejected' : 'pill-pending';
                            $path = (string) $row['path'];
                            $query = (string) ($row['query'] ?? '');
                            $ua = (string) ($row['user_agent'] ?? '');
                            ?>
                            <tr>
                                <td>
                                    <span class="pill <?= $statusClass ?>"><?= $status ?></span>
                                    <?php if ((int) $row['probe'] === 1): ?>
                                        <span class="pill pill-pending" title="Probe / debug pattern">probe</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="cell-name" style="font-family:var(--font-mono);word-break:break-all"><?= e($path) ?></span>
                                    <?php if ($query !== ''): ?>
                                        <span class="cell-sub" style="font-family:var(--font-mono);word-break:break-all">?<?= e($query) ?></span>
                                    <?php endif; ?>
                                    <?php if ((string) ($row['referer'] ?? '') !== ''): ?>
                                        <span class="cell-sub">ref: <?= e((string) $row['referer']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="balance"><?= (int) $row['hits'] ?></span></td>
                                <td class="cell-sub" style="font-family:var(--font-mono)"><?= e((string) $row['ip']) ?></td>
                                <td class="cell-sub" title="<?= e($ua) ?>">
                                    <?= e(badreq_device($ua)) ?>
                                    <?php if ((string) $row['method'] !== 'GET'): ?>
                                        <span class="cell-sub"><?= e((string) $row['method']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="cell-sub" title="first seen <?= e((string) $row['first_seen']) ?>"><?= e((string) $row['last_seen']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php endif; ?>

<?php admin_page_end(); ?>
