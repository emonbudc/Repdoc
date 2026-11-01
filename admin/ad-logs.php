<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$db = repdoc_db();
$logs = [];

if ($db) {
    try {
        $logs = $db->query('SELECT al.*, a.name AS ad_name, p.name AS position_name
                            FROM ad_logs al
                            LEFT JOIN ads a ON a.id = al.ad_id
                            LEFT JOIN ad_positions p ON p.id = al.position_id
                            ORDER BY al.id DESC LIMIT 100')->fetchAll();
    } catch (Throwable $e) {
        $db = null;
    }
}

admin_page_start('Ad Logs', 'ad-logs.php');
admin_notice();
?>

<div class="admin-page-head">
    <div>
        <p class="kicker">Monetization</p>
        <h1 class="admin-title">Ad Logs</h1>
        <p class="admin-sub">Recent ad impressions across your configured slots.</p>
    </div>
</div>

<?php if (!$db): ?>
    <div class="alert alert-error" role="alert">Database connection unavailable.</div>
<?php else: ?>
    <div class="panel">
        <div class="panel-head">Recent impressions</div>
        <div class="panel-body">
            <?php if (empty($logs)): ?>
                <div class="empty-state-sm">No ad impressions have been recorded yet.</div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="data">
                        <thead>
                            <tr>
                                <th>Ad</th>
                                <th>Position</th>
                                <th>Page</th>
                                <th>Device</th>
                                <th>IP</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= e((string) ($log['ad_name'] ?? 'Unknown')) ?></td>
                                    <td><?= e((string) ($log['position_name'] ?? '—')) ?></td>
                                    <td><?= e((string) ($log['page_slug'] ?? 'all')) ?></td>
                                    <td><?= e((string) ($log['device_type'] ?? 'all')) ?></td>
                                    <td><?= e((string) ($log['user_ip'] ?? '')) ?></td>
                                    <td><?= e((string) ($log['viewed_at'] ?? '')) ?></td>
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
