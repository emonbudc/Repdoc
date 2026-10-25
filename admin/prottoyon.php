<?php
declare(strict_types=1);

/**
 * Repdoc — admin: prottoyon submissions.
 * All 41 certificate types, newest first — ref, DOB and full submitted info.
 */

require_once __DIR__ . '/includes/layout.php';
require_once dirname(__DIR__) . '/genaretor/prottoyon/types.php';

$db = repdoc_db();
$types = prottoyon_types();

// Revoke / restore action.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'নিরাপত্তা টোকেন মিলেনি।');
    } else {
        $rid = (int) ($_POST['id'] ?? 0);
        $act = (string) ($_POST['act'] ?? '');
        if ($rid > 0 && in_array($act, ['revoke', 'restore'], true)) {
            try {
                $db->prepare("UPDATE document_logs SET status = ? WHERE id = ? AND tool_key LIKE 'prottoyon!_%' ESCAPE '!'")
                   ->execute([$act === 'revoke' ? 'revoked' : 'valid', $rid]);
                flash_set('ok', $act === 'revoke' ? 'সনদ বাতিল হয়েছে।' : 'সনদ পুনর্বহাল হয়েছে।');
            } catch (Throwable $e) {
                flash_set('error', 'আপডেট হয়নি।');
            }
        }
    }
    redirect(repdoc_url('admin/prottoyon.php') . '?type=' . rawurlencode((string) ($_GET['type'] ?? 'all')));
}

$type  = (string) ($_GET['type'] ?? 'all');
if ($type !== 'all' && !isset($types[$type])) $type = 'all';
$limit = (int) ($_GET['limit'] ?? 100);
$limit = max(10, min(500, $limit));

// Approved entrepreneur emails → UDC badge.
$udcEmails = [];
if ($db) {
    try {
        $udcEmails = $db->query("SELECT email FROM entrepreneur_apps WHERE status = 'approved'")->fetchAll(PDO::FETCH_COLUMN) ?: [];
    } catch (Throwable $e) {}
}

$rows  = [];
$total = 0;

if ($db) {
    try {
        $sql = 'SELECT d.*, u.fullname, u.email FROM document_logs d
                LEFT JOIN users u ON u.id = d.user_id
                WHERE d.tool_key LIKE \'prottoyon!_%\' ESCAPE \'!\'';
        $params = [];
        if ($type !== 'all') {
            $sql .= ' AND d.tool_key = ?';
            $params[] = 'prottoyon_' . $type;
        }
        $sql .= ' ORDER BY d.id DESC LIMIT ' . $limit;

        $st = $db->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll();

        $csql = 'SELECT COUNT(*) FROM document_logs WHERE tool_key LIKE \'prottoyon!_%\' ESCAPE \'!\'';
        if ($type !== 'all') {
            $st = $db->prepare($csql . ' AND tool_key = ?');
            $st->execute(['prottoyon_' . $type]);
            $total = (int) $st->fetchColumn();
        } else {
            $total = (int) $db->query($csql)->fetchColumn();
        }
    } catch (Throwable $e) {
        $db = null;
    }
}

admin_page_start('Prottoyon Logs', 'prottoyon.php');
admin_notice();
?>

<div class="admin-page-head">
    <div>
        <h1 class="admin-title">Prottoyon Logs</h1>
        <p class="admin-sub">41 certificate types — <?= number_format($total) ?> submissions total. Newest first.</p>
    </div>
    <a class="btn btn-primary btn-sm" href="pricing.php">প্রাইস সেট</a>
</div>

<?php if (!$db): ?>
    <div class="alert alert-error" role="alert">Could not connect to the database.</div>
<?php else: ?>

<form class="filter-bar" method="get" action="prottoyon.php">
    <div class="field">
        <label for="type">Certificate</label>
        <select id="type" name="type">
            <option value="all"<?= $type === 'all' ? ' selected' : '' ?>>All 41 types</option>
            <?php foreach ($types as $key => $t): ?>
                <option value="<?= e($key) ?>"<?= $type === $key ? ' selected' : '' ?>><?= e($t['title']) ?></option>
            <?php endforeach; ?>
        </select>
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
    <div class="panel-head">Submissions <span class="count">Showing <?= count($rows) ?></span></div>

    <?php if (!$rows): ?>
        <div class="empty-state-sm">No prottoyon submissions yet.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr><th>#</th><th>User</th><th>Certificate</th><th>Cert No</th><th>Status</th><th>সব তথ্য</th><th>Time</th><th>Action</th></tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <?php
                    $tkey = preg_replace('/^prottoyon_/', '', (string) $row['tool_key']);
                    $info = [];
                    if (!empty($row['data'])) {
                        $decoded = json_decode((string) $row['data'], true);
                        if (is_array($decoded)) $info = $decoded;
                    }
                    ?>
                    <tr>
                        <td class="cell-sub"><?= (int) $row['id'] ?></td>
                        <td>
                            <span class="cell-name"><?= e($row['fullname'] ?: ('#' . (int) $row['user_id'])) ?></span>
                            <span class="cell-sub"><?= e((string) $row['email']) ?></span>
                            <?php if (in_array((string) $row['email'], $udcEmails, true)): ?>
                                <span class="pill pill-approved">UDC</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="pill pill-approved"><?= e($types[$tkey]['title'] ?? $row['tool_key']) ?></span></td>
                        <td class="cell-sub"><?= e((string) ($row['cert_no'] ?? '')) ?: '—' ?></td>
                        <td>
                            <?php if (($row['status'] ?? 'valid') === 'revoked'): ?>
                                <span class="pill pill-rejected">বাতিল</span>
                            <?php else: ?>
                                <span class="pill pill-approved">বৈধ</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($info): ?>
                                <details>
                                    <summary style="cursor:pointer;color:var(--primary);font-weight:600">দেখুন (<?= count($info) ?>)</summary>
                                    <div class="cell-sub" style="max-width:280px">
                                        <?php foreach ($info as $k => $v): ?>
                                            <div><strong><?= e((string) $k) ?>:</strong> <?= e(is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE)) ?></div>
                                        <?php endforeach; ?>
                                    </div>
                                </details>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td class="cell-sub"><?= e((string) $row['created_at']) ?></td>
                        <td style="white-space:nowrap">
                            <a class="btn btn-secondary btn-sm" href="prottoyon-view.php?id=<?= (int) $row['id'] ?>" target="_blank" rel="noopener">View</a>
                            <form method="post" action="prottoyon.php?type=<?= e($type) ?>" style="display:inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                <input type="hidden" name="act" value="<?= ($row['status'] ?? 'valid') === 'revoked' ? 'restore' : 'revoke' ?>">
                                <button type="submit" class="btn btn-sm" onclick="return confirm('নিশ্চিত?')"><?= ($row['status'] ?? 'valid') === 'revoked' ? 'Restore' : 'Revoke' ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php endif; ?>
<?php admin_page_end(); ?>
