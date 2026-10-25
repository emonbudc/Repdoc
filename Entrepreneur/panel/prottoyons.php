<?php
declare(strict_types=1);

/**
 * Repdoc — UDC panel: own prottoyon logs with view/download (print).
 */

require_once __DIR__ . '/includes/layout.php';
require_once dirname(__DIR__, 2) . '/genaretor/prottoyon/types.php';

$me = current_user();
$db = repdoc_db();
$types = prottoyon_types();

$type  = (string) ($_GET['type'] ?? 'all');
if ($type !== 'all' && !isset($types[$type])) $type = 'all';
$limit = (int) ($_GET['limit'] ?? 100);
$limit = max(10, min(500, $limit));

$rows  = [];
$total = 0;

if ($db) {
    try {
        $sql = 'SELECT * FROM document_logs WHERE user_id = ? AND tool_key LIKE \'prottoyon!_%\' ESCAPE \'!\'';
        $params = [$me['id']];
        if ($type !== 'all') {
            $sql .= ' AND tool_key = ?';
            $params[] = 'prottoyon_' . $type;
        }
        $sql .= ' ORDER BY id DESC LIMIT ' . $limit;
        $st = $db->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll();

        $csql = 'SELECT COUNT(*) FROM document_logs WHERE user_id = ? AND tool_key LIKE \'prottoyon!_%\' ESCAPE \'!\'';
        if ($type !== 'all') {
            $st = $db->prepare($csql . ' AND tool_key = ?');
            $st->execute([$me['id'], 'prottoyon_' . $type]);
            $total = (int) $st->fetchColumn();
        } else {
            $st = $db->prepare($csql);
            $st->execute([$me['id']]);
            $total = (int) $st->fetchColumn();
        }
    } catch (Throwable $e) {
        $dbErr = $e->getMessage();
        $db = null;
    }
}

udc_page_start('প্রত্যয়নসমূহ', 'prottoyons.php');
udc_notice();
?>

<div class="admin-page-head">
    <div>
        <h1 class="admin-title">আমার প্রত্যয়ন</h1>
        <p class="admin-sub"><?= number_format($total) ?>টি সনদ — দেখুন ও প্রিন্ট/ডাউনলোড করুন।</p>
    </div>
    <a class="btn btn-primary btn-sm" href="<?= e(repdoc_url('genaretor/prottoyon/')) ?>">+ নতুন প্রত্যয়ন</a>
</div>

<?php if (!$db): ?>
    <div class="alert alert-error" role="alert">ডেটাবেস পাওয়া যাচ্ছে না।<?= isset($dbErr) && $dbErr !== '' ? ' (' . e($dbErr) . ')' : '' ?></div>
<?php else: ?>

<form class="filter-bar" method="get" action="prottoyons.php">
    <div class="field">
        <label for="type">সনদ</label>
        <select id="type" name="type">
            <option value="all"<?= $type === 'all' ? ' selected' : '' ?>>সবগুলো</option>
            <?php foreach ($types as $key => $t): ?>
                <option value="<?= e($key) ?>"<?= $type === $key ? ' selected' : '' ?>><?= e($t['title']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="limit">সর্বোচ্চ</label>
        <select id="limit" name="limit">
            <?php foreach ([50, 100, 200, 500] as $n): ?>
                <option value="<?= $n ?>"<?= $limit === $n ? ' selected' : '' ?>><?= $n ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field" style="flex:0 0 auto">
        <button type="submit" class="btn btn-primary btn-sm">দেখুন</button>
    </div>
</form>

<div class="panel">
    <div class="panel-head">তালিকা <span class="count"><?= count($rows) ?>টি</span></div>
    <?php if (!$rows): ?>
        <div class="empty-state-sm">কোনো প্রত্যয়ন পাওয়া যায়নি।</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>#</th><th>সনদ</th><th>সনদ নম্বর</th><th>স্ট্যাটাস</th><th>সময়</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <?php $tkey = preg_replace('/^prottoyon_/', '', (string) $row['tool_key']); ?>
                    <tr>
                        <td class="cell-sub"><?= (int) $row['id'] ?></td>
                        <td><span class="pill pill-approved"><?= e($types[$tkey]['title'] ?? $row['tool_key']) ?></span></td>
                        <td class="balance"><?= e((string) ($row['cert_no'] ?? '')) ?: '—' ?></td>
                        <td><?= ($row['status'] ?? 'valid') === 'revoked' ? '<span class="pill pill-rejected">বাতিল</span>' : '<span class="pill pill-approved">বৈধ</span>' ?></td>
                        <td class="cell-sub"><?= e((string) $row['created_at']) ?></td>
                        <td style="white-space:nowrap">
                            <a class="btn btn-secondary btn-sm" href="view.php?id=<?= (int) $row['id'] ?>" target="_blank" rel="noopener">View / Print</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php endif; ?>
<?php udc_page_end(); ?>
