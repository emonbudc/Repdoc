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
    $back   = 'users.php?' . http_build_query(array_filter([
        'q'      => (string) ($_POST['return_q'] ?? ''),
        'status' => (string) ($_POST['return_status'] ?? ''),
        'role'   => (string) ($_POST['return_role'] ?? ''),
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

    redirect($back);
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

admin_page_start('Users & Balance', 'users.php');
admin_notice();
?>

<div class="admin-page-head dash-head">
    <div>
        <p class="kicker">Accounts</p>
        <h1 class="admin-title">Users & Balance</h1>
        <p class="admin-sub">Approve accounts, set roles and manage credit balances. <?= number_format($totalUsers) ?> users total.</p>
    </div>
</div>

<?php if (!$db): ?>
    <div class="alert alert-error" role="alert">Could not connect to the database.</div>
<?php else: ?>

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
    <div class="panel-head">User list <span class="count">Showing <?= count($users) ?></span></div>

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
                <?php foreach ($users as $u): ?>
                    <?php $uid = (int) $u['id']; $isSelf = $uid === $selfId; ?>
                    <?php $uInitial = strtoupper((string) mb_substr($u['fullname'] !== '' ? $u['fullname'] : $u['email'], 0, 1, 'UTF-8')); ?>
                    <tr>
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
                                        aria-expanded="false" aria-controls="menu-<?= $uid ?>"
                                        aria-label="Actions for <?= e($u['email']) ?>">
                                    <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-dots"/></svg>
                                    <span>Actions</span>
                                </button>
                                <div class="action-menu" id="menu-<?= $uid ?>" hidden>
                                    <p class="action-menu-head"><?= e($u['fullname'] !== '' ? $u['fullname'] : $u['email']) ?></p>
                                <?php if ($u['status'] !== 'approved'): ?>
                                    <form method="post" action="users.php">
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
                                        <form method="post" action="users.php" class="action-menu-form">
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
                                        <form method="post" action="users.php" class="action-menu-form">
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
                                        <form method="post" action="users.php">
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

                                    <form method="post" action="users.php">
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

                                    <form method="post" action="users.php"
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
                <?php endforeach; ?>
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

<?php endif; ?>

<script>
/* Full-row action menu: one Actions button opens a popup with everything.
   Fixed positioning escapes the table scroll container. */
(function () {
    function closeAll(except) {
        document.querySelectorAll('.action-menu').forEach(function (card) {
            if (card !== except && !card.hidden) {
                card.hidden = true;
                var b = document.querySelector('[aria-controls="' + card.id + '"]');
                if (b) b.setAttribute('aria-expanded', 'false');
            }
        });
    }

    function place(card, btn) {
        var r = btn.getBoundingClientRect();
        var w = Math.min(300, window.innerWidth - 16);
        var left = Math.min(r.left, window.innerWidth - w - 8);
        card.style.width = w + 'px';
        card.style.left = Math.max(8, left) + 'px';
        var top = r.bottom + 6;
        var h = card.offsetHeight || 300;
        if (top + h > window.innerHeight - 8) {
            top = Math.max(8, r.top - h - 6);
        }
        card.style.top = top + 'px';
    }

    function currentOpen() {
        var card = document.querySelector('.action-menu:not([hidden])');
        if (!card) return null;
        var btn = document.querySelector('[data-action-menu][aria-controls="' + card.id + '"]');
        return { card: card, btn: btn };
    }

    /* Keep the open popup glued to its button across scrolls/resizes
       (including the browser's focus scroll and the mobile keyboard). */
    function reposition() {
        var o = currentOpen();
        if (o && o.btn) place(o.card, o.btn);
    }

    document.querySelectorAll('[data-action-menu]').forEach(function (btn) {
        btn.addEventListener('click', function (ev) {
            ev.stopPropagation();
            var card = document.getElementById(btn.getAttribute('aria-controls'));
            if (!card) return;
            var willOpen = card.hidden;
            closeAll(card);
            card.hidden = !willOpen;
            btn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            if (willOpen) {
                place(card, btn);
                var first = card.querySelector('input');
                if (first) first.focus();
            }
        });
    });

    document.addEventListener('click', function (ev) {
        if (!ev.target.closest('.action-menu') && !ev.target.closest('[data-action-menu]')) {
            closeAll(null);
        }
    });
    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape') closeAll(null);
    });
    /* Focus leaving the popup + its button entirely also closes it. */
    document.addEventListener('focusin', function (ev) {
        var o = currentOpen();
        if (o && !o.card.contains(ev.target) && !(ev.target.closest && ev.target.closest('[data-action-menu]'))) {
            closeAll(null);
        }
    });
    window.addEventListener('scroll', reposition, { capture: true, passive: true });
    window.addEventListener('resize', reposition);
})();
</script>
<?php admin_page_end(); ?>
