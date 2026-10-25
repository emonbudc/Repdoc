<?php
declare(strict_types=1);

/**
 * Repdoc — admin dashboard.
 * Overview of users, credits in circulation and recent wallet/tool activity.
 */

require_once __DIR__ . '/includes/layout.php';

$db = repdoc_db();

$stats = [
    'users'       => 0,
    'pending'     => 0,
    'approved'    => 0,
    'balance'     => 0,
    'pendingApps' => 0,
];
$recentTx    = [];
$recentUsage = [];
$topSpenders = [];

if ($db) {
    try {
        $stats['users']    = (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $stats['pending']  = (int) $db->query("SELECT COUNT(*) FROM users WHERE status = 'pending'")->fetchColumn();
        $stats['approved'] = (int) $db->query("SELECT COUNT(*) FROM users WHERE status = 'approved'")->fetchColumn();
        $stats['balance']  = (int) $db->query('SELECT COALESCE(SUM(balance), 0) FROM users')->fetchColumn();
        $stats['pendingApps'] = (int) $db->query("SELECT COUNT(*) FROM entrepreneur_apps WHERE status = 'pending'")->fetchColumn();

        $recentTx = $db->query('SELECT t.*, u.fullname, u.email FROM transactions t
                                LEFT JOIN users u ON u.id = t.user_id
                                ORDER BY t.id DESC LIMIT 8')->fetchAll();

        $recentUsage = $db->query('SELECT g.*, u.fullname, u.email FROM usage_log g
                                   LEFT JOIN users u ON u.id = g.user_id
                                   ORDER BY g.id DESC LIMIT 8')->fetchAll();

        $topSpenders = $db->query('SELECT u.id, u.fullname, u.email, u.balance,
                                          COALESCE(-SUM(CASE WHEN t.delta < 0 THEN t.delta ELSE 0 END), 0) AS spent
                                   FROM users u
                                   LEFT JOIN transactions t ON t.user_id = u.id
                                   GROUP BY u.id, u.fullname, u.email, u.balance
                                   ORDER BY spent DESC LIMIT 5')->fetchAll();
    } catch (Throwable $e) {
        $db = null;
    }
}

admin_page_start('Dashboard', 'index.php');
admin_notice();
?>

<div class="admin-page-head dash-head">
    <div>
        <p class="kicker">Overview</p>
        <h1 class="admin-title">Dashboard</h1>
        <p class="admin-sub">Users, credits and recent activity at a glance.</p>
    </div>
    <?php if ($stats['pending'] + $stats['pendingApps'] > 0): ?>
        <a class="btn btn-primary btn-sm" href="users.php?status=pending">Review pending</a>
    <?php endif; ?>
</div>

<?php if (!$db): ?>
    <div class="alert alert-error" role="alert">
        Could not connect to the database. Try running <a href="../src/setup.php">/src/setup.php</a> once.
    </div>
<?php else: ?>

<div class="stat-grid">
    <div class="stat-card">
        <span class="stat-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#a-users"/></svg></span>
        <span class="stat-text"><span class="k">Total users</span><span class="v"><?= number_format($stats['users']) ?></span><span class="s"><?= number_format($stats['approved']) ?> active</span></span>
    </div>
    <div class="stat-card">
        <span class="stat-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#a-id"/></svg></span>
        <span class="stat-text"><span class="k">Pending approval</span><span class="v"><?= number_format($stats['pending']) ?></span><span class="s"><?= number_format($stats['pendingApps']) ?> entrepreneur apps</span></span>
    </div>
    <div class="stat-card">
        <span class="stat-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#a-check"/></svg></span>
        <span class="stat-text"><span class="k">Active users</span><span class="v"><?= number_format($stats['approved']) ?></span><span class="s">approved accounts</span></span>
    </div>
    <div class="stat-card">
        <span class="stat-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#a-wallet"/></svg></span>
        <span class="stat-text"><span class="k">Credits in circulation</span><span class="v"><?= number_format($stats['balance']) ?></span><span class="s">across all users</span></span>
    </div>
</div>

<div class="dash-grid">
    <div class="dash-main">
<div class="panel">
    <div class="panel-head">
        Recent transactions
        <span class="count">Last 8</span>
        <a class="panel-link" href="transactions.php">View all</a>
    </div>
    <?php if ($recentTx): ?>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr><th>User</th><th>Change</th><th>Balance</th><th>Reason</th><th>Time</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($recentTx as $tx): ?>
                        <tr>
                            <td>
                                <span class="cell-name"><?= e($tx['fullname'] ?: '—') ?></span>
                                <span class="cell-sub"><?= e($tx['email'] ?: '') ?></span>
                            </td>
                            <td class="<?= ((int) $tx['delta']) >= 0 ? 'delta-plus' : 'delta-minus' ?>">
                                <?= ((int) $tx['delta']) >= 0 ? '+' : '' ?><?= (int) $tx['delta'] ?>
                            </td>
                            <td class="balance"><?= (int) $tx['balance_after'] ?></td>
                            <td><?= e($tx['reason']) ?><?= $tx['tool_key'] ? ' · ' . e($tx['tool_key']) : '' ?></td>
                            <td class="cell-sub"><?= e((string) $tx['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state-sm">No transactions yet. (When a tool price is 0, no charge is made.)</div>
    <?php endif; ?>
</div>

<div class="panel">
    <div class="panel-head">
        Tool usage log
        <span class="count">Last 8</span>
    </div>
    <?php if ($recentUsage): ?>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>User</th><th>Tool</th><th>Result</th><th>Detail</th><th>Time</th></tr></thead>
                <tbody>
                    <?php foreach ($recentUsage as $row): ?>
                        <tr>
                            <td>
                                <span class="cell-name"><?= e($row['fullname'] ?: '—') ?></span>
                                <span class="cell-sub"><?= e($row['email'] ?: '') ?></span>
                            </td>
                            <td><?= e($row['tool_key']) ?></td>
                            <td>
                                <?php
                                $status = (string) $row['status'];
                                $cls = $status === 'ok' ? 'pill-approved' : ($status === 'denied_insufficient' ? 'pill-pending' : 'pill-rejected');
                                $statusLabels = ['ok' => 'OK', 'denied_insufficient' => 'Insufficient', 'error' => 'Error'];
                                ?>
                                <span class="pill <?= $cls ?>"><?= e($statusLabels[$status] ?? ucfirst($status)) ?></span>
                            </td>
                            <td class="cell-sub"><?= e((string) $row['detail']) ?></td>
                            <td class="cell-sub"><?= e((string) $row['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state-sm">No tool usage yet.</div>
    <?php endif; ?>
</div>
    </div><!-- /dash-main -->

    <div class="dash-side">
<div class="panel">
    <div class="panel-head">Needs attention</div>
    <div class="panel-body attention-list">
        <?php if ($stats['pending'] > 0): ?>
            <a class="attention-row" href="users.php?status=pending">
                <span class="attention-n"><?= number_format($stats['pending']) ?></span>
                <span class="attention-t">users awaiting approval</span>
                <span class="attention-go" aria-hidden="true">→</span>
            </a>
        <?php endif; ?>
        <?php if ($stats['pendingApps'] > 0): ?>
            <a class="attention-row" href="entrepreneur.php">
                <span class="attention-n"><?= number_format($stats['pendingApps']) ?></span>
                <span class="attention-t">entrepreneur apps awaiting review</span>
                <span class="attention-go" aria-hidden="true">→</span>
            </a>
        <?php endif; ?>
        <?php if ($stats['pending'] + $stats['pendingApps'] === 0): ?>
            <p class="empty-state-sm" style="padding:8px 0">All clear — nothing needs review.</p>
        <?php endif; ?>
    </div>
</div>

<div class="panel">
    <div class="panel-head">Top credit users <span class="count">Top 5</span></div>
    <?php if ($topSpenders): ?>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>User</th><th>Credits used</th><th>Current balance</th></tr></thead>
                <tbody>
                    <?php foreach ($topSpenders as $row): ?>
                        <tr>
                            <td>
                                <span class="cell-name"><?= e($row['fullname'] ?: '—') ?></span>
                                <span class="cell-sub"><?= e($row['email'] ?: '') ?></span>
                            </td>
                            <td class="balance"><?= (int) $row['spent'] ?></td>
                            <td class="balance"><?= (int) $row['balance'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state-sm">No data.</div>
    <?php endif; ?>
</div>

<div class="panel">
    <div class="panel-head">Quick links</div>
    <div class="panel-body quick-grid">
        <a class="quick-link" href="users.php"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-users"/></svg>Users</a>
        <a class="quick-link" href="entrepreneur.php"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-id"/></svg>Entrepreneur apps</a>
        <a class="quick-link" href="tools.php"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-layout"/></svg>Tools</a>
        <a class="quick-link" href="../tools/passport/"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-id"/></svg>Passport Photo</a>
        <a class="quick-link" href="files.php"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-file"/></svg>File Packs</a>
        <a class="quick-link" href="pricing.php"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-tag"/></svg>Pricing</a>
    </div>
</div>
    </div><!-- /dash-side -->
</div><!-- /dash-grid -->

<?php endif; ?>
<?php admin_page_end(); ?>
