<?php
declare(strict_types=1);

/**
 * Repdoc — admin: unauthorized / bad request tracker.
 *
 * Rows are written by 404.php (wired as ErrorDocument 403 and 404), so every
 * missing or blocked URL is recorded with every available detail: path, query,
 * IP (and forwarded IPs), remote user / auth header, protocol, user agent,
 * platform, referer, cookies, all request headers, hostname, resolved user
 * profile (name + email when the probe came from a logged-in session) and
 * client-side details collected by the beacon. Paths that look like automated
 * probing (wp-login, .env, phpmyadmin, /admin/login, …) are tagged `probe`.
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
            $where[] = '(path LIKE ? OR query LIKE ? OR ip LIKE ? OR user_agent LIKE ? OR user_fullname LIKE ? OR user_email LIKE ? OR platform LIKE ?)';
            $like = '%' . $search . '%';
            array_push($params, $like, $like, $like, $like, $like, $like, $like);
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

/** Pretty-print a JSON blob for the detail view. */
function badreq_pretty(?string $json): string
{
    if ($json === null || $json === '') return '';
    $d = json_decode($json, true);
    if (!is_array($d)) return (string) $json;
    $out = json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    return $out === false ? (string) $json : $out;
}

admin_page_start('Bad Requests', 'bad-requests.php');
admin_notice();
?>

<div class="admin-page-head">
    <div>
        <p class="kicker">Security</p>
        <h1 class="admin-title">Bad request tracker</h1>
        <p class="admin-sub">Every blocked or missing URL with full request details — including debug probes like <code>/admin/login</code>, <code>/wp-login.php</code> and <code>/.env</code>. Click <strong>details</strong> on a row to expand.</p>
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
        <input type="search" id="q" name="q" value="<?= e($search) ?>" placeholder="path, query, IP, UA, name or email">
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
                            <th>Platform</th>
                            <th>User</th>
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
                            $platform = (string) ($row['platform'] ?? '');
                            if ($platform === '') $platform = badreq_device($ua);
                            $fullname = (string) ($row['user_fullname'] ?? '');
                            $email    = (string) ($row['user_email'] ?? '');
                            $userId   = (int) ($row['user_id'] ?? 0);
                            $ipFwd    = (string) ($row['ip_forwarded'] ?? '');
                            $remoteU   = (string) ($row['remote_user'] ?? '');
                            $authH     = (string) ($row['auth_header'] ?? '');
                            $proto     = (string) ($row['protocol'] ?? '');
                            $hostname  = (string) ($row['hostname'] ?? '');
                            $cookies   = (string) ($row['cookies'] ?? '');
                            $headers   = (string) ($row['headers'] ?? '');
                            $client    = (string) ($row['client_info'] ?? '');
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
                                    <details class="br-details">
                                        <summary>details</summary>
                                        <div class="br-detail">
                                            <div class="br-detail-grid">
                                                <div><span>Method</span><strong><?= e((string) $row['method']) ?></strong></div>
                                                <div><span>Protocol</span><strong><?= e($proto !== '' ? $proto : '—') ?></strong></div>
                                                <div><span>IP</span><strong><?= e((string) $row['ip']) ?></strong></div>
                                                <div><span>Forwarded IPs</span><strong><?= e($ipFwd !== '' ? $ipFwd : '—') ?></strong></div>
                                                <div><span>Hostname (rDNS)</span><strong><?= e($hostname !== '' ? $hostname : '—') ?></strong></div>
                                                <div><span>Remote user</span><strong><?= e($remoteU !== '' ? $remoteU : '—') ?></strong></div>
                                                <div><span>Auth header</span><strong><?= e($authH !== '' ? $authH : '—') ?></strong></div>
                                                <div><span>User agent</span><strong style="word-break:break-all"><?= e($ua) ?></strong></div>
                                                <div><span>First seen</span><strong><?= e((string) $row['first_seen']) ?></strong></div>
                                                <div><span>Last seen</span><strong><?= e((string) $row['last_seen']) ?></strong></div>
                                                <div><span>Hits</span><strong><?= (int) $row['hits'] ?></strong></div>
                                            </div>
                                            <?php if ($cookies !== ''): ?>
                                                <div class="br-block">
                                                    <div class="br-block-title">Cookies</div>
                                                    <pre><?= e(badreq_pretty($cookies)) ?></pre>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($headers !== ''): ?>
                                                <div class="br-block">
                                                    <div class="br-block-title">Request headers</div>
                                                    <pre><?= e(badreq_pretty($headers)) ?></pre>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($client !== ''): ?>
                                                <div class="br-block">
                                                    <div class="br-block-title">Client details (beacon)</div>
                                                    <pre><?= e(badreq_pretty($client)) ?></pre>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </details>
                                </td>
                                <td><span class="balance"><?= (int) $row['hits'] ?></span></td>
                                <td class="cell-sub" style="font-family:var(--font-mono)"><?= e((string) $row['ip']) ?></td>
                                <td class="cell-sub"><?= e($platform) ?></td>
                                <td class="cell-sub">
                                    <?php if ($fullname !== '' || $email !== ''): ?>
                                        <span class="cell-name"><?= e($fullname !== '' ? $fullname : '—') ?></span>
                                        <?php if ($email !== ''): ?>
                                            <span class="cell-sub" style="font-family:var(--font-mono)"><?= e($email) ?></span>
                                        <?php endif; ?>
                                        <?php if ($userId > 0): ?>
                                            <span class="cell-sub">id <?= $userId ?></span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
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

<style>
.br-details { margin-top: 8px; font-size: .78rem; }
.br-details > summary { cursor: pointer; color: var(--primary); font-weight: 600; list-style: none; }
.br-details > summary::-webkit-details-marker { display: none; }
.br-details > summary::before { content: '▸ '; }
.br-details[open] > summary::before { content: '▾ '; }
.br-detail { margin-top: 10px; padding: 12px; background: var(--bg-muted); border: 1px solid var(--border-color); border-radius: 8px; }
.br-detail-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 6px 14px; }
.br-detail-grid div { display: flex; flex-direction: column; gap: 2px; }
.br-detail-grid span { font-size: .68rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--text-muted); }
.br-detail-grid strong { font-family: var(--font-mono); font-size: .82rem; font-weight: 600; word-break: break-all; }
.br-block { margin-top: 12px; }
.br-block-title { font-size: .7rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: var(--text-muted); margin-bottom: 4px; }
.br-block pre { margin: 0; padding: 10px; background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 6px; font-family: var(--font-mono); font-size: .74rem; line-height: 1.5; white-space: pre-wrap; word-break: break-all; max-height: 260px; overflow: auto; }
</style>

<?php endif; ?>

<?php admin_page_end(); ?>
