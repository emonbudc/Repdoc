<?php
declare(strict_types=1);

/**
 * Repdoc — admin: transaction ledger.
 * Every credit, debit and tool charge, newest first.
 */

require_once __DIR__ . '/includes/layout.php';

$db = repdoc_db();

$userId = (int) ($_GET['user_id'] ?? 0);
$limit  = (int) ($_GET['limit'] ?? 100);
$limit  = max(10, min(500, $limit));

$rows  = [];
$user  = null;
$total = 0;

if ($db) {
    try {
        if ($userId > 0) {
            $st = $db->prepare('SELECT id, fullname, email, balance FROM users WHERE id = ?');
            $st->execute([$userId]);
            $user = $st->fetch() ?: null;
        }

        $sql = 'SELECT t.*, u.fullname, u.email FROM transactions t
                LEFT JOIN users u ON u.id = t.user_id';
        $params = [];
        if ($userId > 0) {
            $sql .= ' WHERE t.user_id = ?';
            $params[] = $userId;
        }
        $sql .= ' ORDER BY t.id DESC LIMIT ' . $limit;

        $st = $db->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll();
    } catch (Throwable $e) {
        $db = null;
    }
}

// Count separately, so a failure above cannot hide the table.
if ($db) {
    try {
        if ($userId > 0) {
            $st = $db->prepare('SELECT COUNT(*) FROM transactions WHERE user_id = ?');
            $st->execute([$userId]);
            $total = (int) $st->fetchColumn();
        } else {
            $total = (int) $db->query('SELECT COUNT(*) FROM transactions')->fetchColumn();
        }
    } catch (Throwable $e) {
        $total = count($rows);
    }
}

admin_page_start('Transactions', 'transactions.php');
admin_notice();
?>

<div class="admin-page-head">
    <h1 class="admin-title">Transaction log</h1>
    <p class="admin-sub">
        <?php if ($user): ?>
            <?= e($user['fullname'] ?: $user['email']) ?> — current balance
            <span class="balance"><?= (int) $user['balance'] ?></span> ·
            <a href="transactions.php" style="color:var(--primary);font-weight:600">View all transactions</a>
        <?php else: ?>
            Every balance change on the site — <?= number_format($total) ?> entries total.
        <?php endif; ?>
    </p>
</div>

<?php if (!$db): ?>
    <div class="alert alert-error" role="alert">Could not connect to the database.</div>
<?php else: ?>

<form class="filter-bar" method="get" action="transactions.php">
    <div class="field">
        <label for="user_id">User ID</label>
        <input type="number" id="user_id" name="user_id" min="0" value="<?= $userId > 0 ? (int) $userId : '' ?>" placeholder="e.g. 3">
    </div>
    <div class="field">
        <label for="limit">Max entries</label>
        <select id="limit" name="limit">
            <?php foreach ([50, 100, 200, 500] as $n): ?>
                <option value="<?= $n ?>"<?= $limit === $n ? ' selected' : '' ?>><?= $n ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field" style="flex:0 0 auto">
        <button type="submit" class="btn btn-primary btn-sm">View</button>
    </div>
</form>

<div class="panel">
    <div class="panel-head">Transactions <span class="count">Showing <?= count($rows) ?></span></div>

    <?php if (!$rows): ?>
        <div class="empty-state-sm">No transactions. A tool priced at 0 is never charged, and admins are never charged.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr><th>#</th><th>User</th><th>Change</th><th>Balance</th><th>Tool</th><th>Reason</th><th>By</th><th>Time</th></tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $tx): ?>
                    <tr>
                        <td class="cell-sub"><?= (int) $tx['id'] ?></td>
                        <td>
                            <a class="cell-name" href="transactions.php?user_id=<?= (int) $tx['user_id'] ?>">
                                <?= e($tx['fullname'] ?: ('#' . (int) $tx['user_id'])) ?>
                            </a>
                            <span class="cell-sub"><?= e((string) $tx['email']) ?></span>
                        </td>
                        <td class="<?= ((int) $tx['delta']) >= 0 ? 'delta-plus' : 'delta-minus' ?>">
                            <?= ((int) $tx['delta']) >= 0 ? '+' : '' ?><?= (int) $tx['delta'] ?>
                        </td>
                        <td class="balance"><?= (int) $tx['balance_after'] ?></td>
                        <td class="cell-sub"><?= e((string) $tx['tool_key']) ?: '—' ?></td>
                        <td><?= e((string) $tx['reason']) ?></td>
                        <td class="cell-sub"><?= ((int) $tx['actor_id']) > 0 ? 'admin #' . (int) $tx['actor_id'] : '—' ?></td>
                        <td class="cell-sub"><?= e((string) $tx['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php endif; ?>
<?php admin_page_end(); ?>
