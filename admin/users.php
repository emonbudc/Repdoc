<?php
declare(strict_types=1);

/**
 * Repdoc — admin: users & balances.
 *
 * Approve / reject / delete accounts, switch roles, set passwords and adjust
 * each user's credit balance (every change is written to the ledger).
 */

require_once __DIR__ . '/includes/layout.php';

$db    = repdoc_db();
$me    = current_user();
$selfId = (int) $me['id'];

// ── Handle actions (POST → flash → redirect, so a refresh never re-posts) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Security token mismatch — please try again.');
        redirect('users.php');
    }

    $action = (string) ($_POST['action'] ?? '');
    $userId = (int) ($_POST['user_id'] ?? 0);
    $returnQ      = (string) ($_POST['return_q'] ?? '');
    $returnStatus = (string) ($_POST['return_status'] ?? '');
    $returnRole   = (string) ($_POST['return_role'] ?? '');
    $back   = 'users.php?' . http_build_query(array_filter([
        'q'      => $returnQ,
        'status' => $returnStatus,
        'role'   => $returnRole,
    ], static fn($v) => $v !== ''));

    if ($userId <= 0) {
        flash_set('error', 'User not found.');
        redirect($back);
    }

    try {
        switch ($action) {
            case 'approve':
                $db->prepare("UPDATE users SET status = 'approved' WHERE id = ?")->execute([$userId]);
                flash_set('ok', 'User approved.');
                break;

            case 'reject':
                if ($userId === $selfId) {
                    flash_set('error', 'You cannot deactivate your own account.');
                    break;
                }
                $db->prepare("UPDATE users SET status = 'rejected' WHERE id = ?")->execute([$userId]);
                flash_set('ok', 'User deactivated (rejected).');
                break;

            case 'delete':
                if ($userId === $selfId) {
                    flash_set('error', 'You cannot delete your own account.');
                    break;
                }
                $db->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);
                flash_set('ok', 'User deleted. (Transaction records are kept.)');
                break;

            case 'role':
                if ($userId === $selfId) {
                    flash_set('error', 'You cannot change your own role.');
                    break;
                }
                $role = ((string) ($_POST['role'] ?? 'user')) === 'admin' ? 'admin' : 'user';
                $db->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $userId]);
                flash_set('ok', $role === 'admin' ? 'Made admin.' : 'Made regular user.');
                break;

            case 'password':
                $new = (string) ($_POST['new_password'] ?? '');
                if (strlen($new) < 6) {
                    flash_set('error', 'Password must be at least 6 characters.');
                    break;
                }
                $db->prepare('UPDATE users SET password = ? WHERE id = ?')
                   ->execute([password_hash($new, PASSWORD_DEFAULT), $userId]);
                flash_set('ok', 'Password changed.');
                break;

            case 'balance':
                $amount = (int) ($_POST['amount'] ?? 0);
                $note   = trim((string) ($_POST['note'] ?? ''));
                if ($amount === 0) {
                    flash_set('error', 'Enter an amount (positive to add, negative to deduct).');
                    break;
                }
                $result = adjust_balance($userId, $amount, $note !== '' ? $note : 'admin-adjust', $selfId);
                flash_set($result['ok'] ? 'ok' : 'error', $result['message']);
                break;

            default:
                flash_set('error', 'Unknown action.');
        }
    } catch (Throwable $e) {
        flash_set('error', 'Operation failed — please try again.');
    }

    if (admin_live_request()) {
        admin_live_json(admin_users_live_payload($db, $action, $userId, $selfId, $returnQ, $returnStatus, $returnRole));
    }

    redirect($back);
}

/** Fresh row for one user, or null. */
function admin_user_find(?PDO $db, int $userId): ?array
{
    if (!$db instanceof PDO || $userId <= 0) return null;
    try {
        $st = $db->prepare('SELECT * FROM users WHERE id = ?');
        $st->execute([$userId]);
        $row = $st->fetch();
        return $row ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

/** One table row — used by the initial render and by live action replies. */
function admin_user_row(array $u, int $selfId, string $q, string $status, string $role): string
{
    $uid = (int) $u['id'];
    $isSelf = $uid === $selfId;
    $uInitial = strtoupper((string) mb_substr($u['fullname'] !== '' ? $u['fullname'] : $u['email'], 0, 1, 'UTF-8'));
    $menuId = 'menu-' . $uid;
    ob_start();
    ?>
    <tr id="user-row-<?= $uid ?>">
        <td>
            <span class="user-cell">
                <span class="admin-avatar" aria-hidden="true"><?= e($uInitial !== '' ? $uInitial : 'U') ?></span>
                <span class="user-cell-text">
                    <span class="cell-name">
                        <?= e($u['fullname'] ?: '—') ?>
                        <?php if ($isSelf): ?><span class="pill pill-admin">You</span><?php endif; ?>
                    </span>
                    <span class="cell-sub">#<?= $uid ?> · <?= e($u['email']) ?></span>
                    <?php if (!empty($u['phone'])): ?>
                        <span class="cell-sub"><?= e($u['phone']) ?></span>
                    <?php endif; ?>
                    <span class="cell-sub">Joined: <?= e((string) $u['created_at']) ?></span>
                </span>
            </span>
        </td>

        <td>
            <?php $statusLabels = ['pending' => 'Pending', 'approved' => 'Active', 'rejected' => 'Closed']; ?>
            <span class="pill pill-<?= e($u['status']) ?>"><?= e($statusLabels[$u['status']] ?? $u['status']) ?></span>
            <span class="pill pill-<?= ((string) $u['role']) === 'admin' ? 'admin' : 'user' ?>"><?= e($u['role']) ?></span>
        </td>

        <td><span class="balance <?= ((int) $u['balance']) === 0 ? 'balance-zero' : '' ?>"><?= (int) $u['balance'] ?></span></td>

        <td>
            <div class="row-actions">
                <button type="button" class="btn btn-secondary btn-sm" data-action-menu
                        aria-expanded="false" aria-controls="<?= $menuId ?>"
                        aria-label="Actions for <?= e($u['email']) ?>">
                    <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-dots"/></svg>
                    <span>Actions</span>
                </button>
                <div class="action-menu" id="<?= $menuId ?>" hidden>
                    <p class="action-menu-head"><?= e($u['fullname'] !== '' ? $u['fullname'] : $u['email']) ?></p>
                <?php if ($u['status'] !== 'approved'): ?>
                    <form method="post" action="users.php" data-live>
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="user_id" value="<?= $uid ?>">
                        <input type="hidden" name="return_q" value="<?= e($q) ?>">
                        <input type="hidden" name="return_status" value="<?= e($status) ?>">
                        <input type="hidden" name="return_role" value="<?= e($role) ?>">
                        <button type="submit" class="btn btn-primary btn-sm" aria-label="Approve <?= e($u['email']) ?>">
                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-check"/></svg>
                            <span>Approve</span>
                        </button>
                    </form>
                <?php endif; ?>

                    <div class="action-menu-group">
                        <p class="action-menu-label">Adjust balance · <span class="balance"><?= (int) $u['balance'] ?></span></p>
                        <form method="post" action="users.php" class="action-menu-form" data-live>
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="balance">
                            <input type="hidden" name="user_id" value="<?= $uid ?>">
                            <input type="hidden" name="return_q" value="<?= e($q) ?>">
                            <input type="hidden" name="return_status" value="<?= e($status) ?>">
                            <input type="hidden" name="return_role" value="<?= e($role) ?>">
                            <input type="number" name="amount" value="" step="1"
                                   aria-label="Adjustment amount for <?= e($u['email']) ?>" placeholder="+ / -">
                            <input type="text" name="note" value=""
                                   aria-label="Reason" placeholder="Reason (optional)">
                            <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                        </form>
                    </div>

                    <div class="action-menu-group">
                        <p class="action-menu-label">Change password</p>
                        <form method="post" action="users.php" class="action-menu-form" data-live>
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="password">
                            <input type="hidden" name="user_id" value="<?= $uid ?>">
                            <input type="hidden" name="return_q" value="<?= e($q) ?>">
                            <input type="hidden" name="return_status" value="<?= e($status) ?>">
                            <input type="hidden" name="return_role" value="<?= e($role) ?>">
                            <input type="text" name="new_password" value=""
                                   aria-label="New password for <?= e($u['email']) ?>" placeholder="New password">
                            <button type="submit" class="btn btn-secondary btn-sm">Change</button>
                        </form>
                    </div>

                <?php if (!$isSelf): ?>
                    <?php if ($u['status'] !== 'rejected'): ?>
                        <form method="post" action="users.php" data-live>
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="user_id" value="<?= $uid ?>">
                            <input type="hidden" name="return_q" value="<?= e($q) ?>">
                            <input type="hidden" name="return_status" value="<?= e($status) ?>">
                            <input type="hidden" name="return_role" value="<?= e($role) ?>">
                            <button type="submit" class="btn btn-secondary btn-sm" aria-label="Deactivate <?= e($u['email']) ?>">
                                <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-x"/></svg>
                                <span>Deactivate</span>
                            </button>
                        </form>
                    <?php endif; ?>

                    <form method="post" action="users.php" data-live>
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="role">
                        <input type="hidden" name="user_id" value="<?= $uid ?>">
                        <input type="hidden" name="role" value="<?= ((string) $u['role']) === 'admin' ? 'user' : 'admin' ?>">
                        <input type="hidden" name="return_q" value="<?= e($q) ?>">
                        <input type="hidden" name="return_status" value="<?= e($status) ?>">
                        <input type="hidden" name="return_role" value="<?= e($role) ?>">
                        <button type="submit" class="btn btn-secondary btn-sm">
                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-users"/></svg>
                            <span><?= ((string) $u['role']) === 'admin' ? 'Make user' : 'Make admin' ?></span>
                        </button>
                    </form>

                    <form method="post" action="users.php" data-live
                          onsubmit="return confirm('This user will be deleted. Continue?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="user_id" value="<?= $uid ?>">
                        <input type="hidden" name="return_q" value="<?= e($q) ?>">
                        <input type="hidden" name="return_status" value="<?= e($status) ?>">
                        <input type="hidden" name="return_role" value="<?= e($role) ?>">
                        <button type="submit" class="btn btn-danger btn-sm" aria-label="Delete <?= e($u['email']) ?>">
                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-trash"/></svg>
                            <span>Delete</span>
                        </button>
                    </form>
                <?php endif; ?>
                </div><!-- /action-menu -->
            </div>
        </td>
    </tr>
    <?php
    return (string) ob_get_clean();
}

/** Reply for a live action: message + what to redraw. */
function admin_users_live_payload(?PDO $db, string $action, int $userId, int $selfId, string $q, string $status, string $role): array
{
    $flash = flash_get();
    $ok = ($flash['type'] ?? 'ok') !== 'error';
    $payload = ['ok' => $ok, 'message' => (string) ($flash['message'] ?? ($ok ? 'Saved.' : 'That action could not be completed.'))];

    if ($ok && $userId > 0) {
        if ($action === 'delete') {
            $payload['remove'] = '#user-row-' . $userId;
        } else {
            $fresh = admin_user_find($db, $userId);
            if ($fresh) {
                $payload['replace'] = ['selector' => '#user-row-' . $userId, 'html' => admin_user_row($fresh, $selfId, $q, $status, $role)];
            }
        }
    }

    return $payload + admin_live_counters($db);
}

// ── Filters ──────────────────────────────────────────────────────
$q      = trim((string) ($_GET['q'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$role   = trim((string) ($_GET['role'] ?? ''));

$users = [];
$totalUsers = 0;

if ($db) {
    $where  = [];
    $params = [];

    if ($q !== '') {
        $where[] = '(fullname LIKE ? OR email LIKE ? OR phone LIKE ?)';
        $like = '%' . $q . '%';
        array_push($params, $like, $like, $like);
    }
    if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
        $where[] = 'status = ?';
        $params[] = $status;
    }
    if (in_array($role, ['user', 'admin'], true)) {
        $where[] = 'role = ?';
        $params[] = $role;
    }

    $sql = 'SELECT * FROM users';
    if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
    $sql .= ' ORDER BY (status = \'pending\') DESC, id DESC LIMIT 200';

    try {
        $st = $db->prepare($sql);
        $st->execute($params);
        $users = $st->fetchAll();
        $totalUsers = (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    } catch (Throwable $e) {
        $db = null;
    }
}

$live = admin_live_counters($db);

$topBalances = [];
if ($db) {
    try {
        $topBalances = $db->query('SELECT id, fullname, email, balance FROM users ORDER BY balance DESC, id ASC LIMIT 5')->fetchAll();
    } catch (Throwable $e) {
        $topBalances = [];
    }
}

admin_page_start('Users & Balance', 'users.php');
admin_notice();
?>

<div class="admin-page-head dash-head">
    <div>
        <p class="kicker">Accounts</p>
        <h1 class="admin-title">Users & Balance</h1>
        <p class="admin-sub">Approve accounts, set roles and adjust credit balances. Actions apply on the spot — the page only reloads when you switch sections.</p>
    </div>
    <?php if ($live['pending'] > 0): ?>
        <a class="btn btn-primary btn-sm" href="users.php?status=pending" data-live-pending-pill>Review pending (<?= number_format($live['pending']) ?>)</a>
    <?php endif; ?>
</div>

<?php if (!$db): ?>
    <div class="alert alert-error" role="alert">Could not connect to the database.</div>
<?php else: ?>

<div class="stat-grid">
    <div class="stat-card">
        <span class="stat-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#a-users"/></svg></span>
        <span class="stat-text">
            <span class="k">Total users</span>
            <span class="v" data-live-stat="users"><?= $live['stats']['users'] ?></span>
            <span class="s"><?= $live['stats']['users_active'] ?> active accounts</span>
        </span>
    </div>
    <div class="stat-card">
        <span class="stat-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#a-lock"/></svg></span>
        <span class="stat-text">
            <span class="k">Pending approval</span>
            <span class="v" data-live-stat="users_pending"><?= $live['stats']['users_pending'] ?></span>
            <span class="s">waiting for review</span>
        </span>
    </div>
    <div class="stat-card">
        <span class="stat-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#a-check"/></svg></span>
        <span class="stat-text">
            <span class="k">Active users</span>
            <span class="v" data-live-stat="users_active"><?= $live['stats']['users_active'] ?></span>
            <span class="s">approved accounts</span>
        </span>
    </div>
    <div class="stat-card">
        <span class="stat-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#a-wallet"/></svg></span>
        <span class="stat-text">
            <span class="k">Credits in circulation</span>
            <span class="v" data-live-stat="users_balance"><?= $live['stats']['users_balance'] ?></span>
            <span class="s">across all users</span>
        </span>
    </div>
</div>

<div class="dash-grid">
<div class="dash-main">

<form class="filter-bar" method="get" action="users.php">
    <div class="field">
        <label for="q">Search</label>
        <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Name, email or phone">
    </div>
    <div class="field">
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="">All</option>
            <?php foreach (['pending' => 'Pending', 'approved' => 'Active', 'rejected' => 'Closed'] as $val => $label): ?>
                <option value="<?= e($val) ?>"<?= $status === $val ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="role">Role</label>
        <select id="role" name="role">
            <option value="">All</option>
            <option value="user"<?= $role === 'user' ? ' selected' : '' ?>>User</option>
            <option value="admin"<?= $role === 'admin' ? ' selected' : '' ?>>Admin</option>
        </select>
    </div>
    <div class="field" style="flex:0 0 auto">
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    </div>
    <div class="field" style="flex:0 0 auto">
        <a class="btn btn-secondary btn-sm" href="users.php">Reset</a>
    </div>
</form>

<div class="panel">
    <div class="panel-head">User list <span class="count">Showing <?= count($users) ?></span>
        <a class="panel-link" href="users.php?status=pending">Pending only</a>
    </div>

    <?php if (!$users): ?>
        <div class="empty-state-sm">No users match this filter.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Status</th>
                        <th>Balance</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u) echo admin_user_row($u, $selfId, $q, $status, $role); ?>
                </tbody>
            </table>
        </div>
        <div class="panel-body" style="padding-top:0">
            <p class="admin-sub" style="margin:0">
                A positive number adds credit, a negative number deducts it. Every change is stored in the transaction log.
            </p>
        </div>
    <?php endif; ?>
</div>

</div><!-- /dash-main -->

<div class="dash-side">
    <div class="panel">
        <div class="panel-head">Needs attention</div>
        <div class="panel-body attention-list">
            <?php if ($live['badges']['users'] > 0): ?>
                <a class="attention-row" href="users.php?status=pending">
                    <span class="attention-n" data-live-stat="users_pending"><?= $live['stats']['users_pending'] ?></span>
                    <span class="attention-t">users awaiting approval</span>
                    <span class="attention-go" aria-hidden="true">→</span>
                </a>
            <?php endif; ?>
            <?php if ($live['badges']['entrepreneur'] > 0): ?>
                <a class="attention-row" href="entrepreneur.php?status=pending">
                    <span class="attention-n" data-live-stat="apps_pending"><?= $live['stats']['apps_pending'] ?></span>
                    <span class="attention-t">entrepreneur apps awaiting review</span>
                    <span class="attention-go" aria-hidden="true">→</span>
                </a>
            <?php endif; ?>
            <?php if ($live['pending'] === 0): ?>
                <p class="empty-state-sm" style="padding:8px 0">All clear — nothing needs review.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head">Top balances <span class="count">Top 5</span>
            <a class="panel-link" href="transactions.php">Ledger</a>
        </div>
        <?php if ($topBalances): ?>
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>User</th><th>Credits</th></tr></thead>
                    <tbody>
                        <?php foreach ($topBalances as $row): ?>
                            <tr>
                                <td>
                                    <span class="cell-name"><?= e($row['fullname'] ?: '—') ?></span>
                                    <span class="cell-sub"><?= e((string) $row['email']) ?></span>
                                </td>
                                <td><span class="balance <?= ((int) $row['balance']) === 0 ? 'balance-zero' : '' ?>"><?= (int) $row['balance'] ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state-sm">No users yet.</div>
        <?php endif; ?>
    </div>

    <div class="panel">
        <div class="panel-head">Quick links</div>
        <div class="panel-body quick-grid">
            <a class="quick-link" href="entrepreneur.php"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-id"/></svg>Entrepreneur apps</a>
            <a class="quick-link" href="transactions.php"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-wallet"/></svg>Transactions</a>
            <a class="quick-link" href="documents.php"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-file"/></svg>Document log</a>
            <a class="quick-link" href="pricing.php"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-tag"/></svg>Tool prices</a>
        </div>
    </div>
</div><!-- /dash-side -->
</div><!-- /dash-grid -->

<?php endif; ?>

<?php admin_page_end(); ?>
