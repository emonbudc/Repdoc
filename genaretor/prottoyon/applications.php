<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
require_login();

$me = current_user();
$allTypes = prottoyon_all_types();
$type = isset($_GET['type']) && is_string($_GET['type']) ? trim($_GET['type']) : 'all';
if ($type !== 'all' && !isset($allTypes[$type])) $type = 'all';
$limit = (int) ($_GET['limit'] ?? 100);
$limit = max(10, min(500, $limit));

$db = repdoc_db();
$rows = [];
$total = 0;
$dbError = '';
if ($db) {
    try {
        $where = "user_id = ? AND tool_key LIKE 'prottoyon!_%' ESCAPE '!'";
        $params = [(int) $me['id']];
        if ($type !== 'all') {
            $where .= ' AND tool_key = ?';
            $params[] = 'prottoyon_' . $type;
        }
        $st = $db->prepare('SELECT * FROM document_logs WHERE ' . $where . ' ORDER BY id DESC LIMIT ' . $limit);
        $st->execute($params);
        $rows = $st->fetchAll();

        $countSql = 'SELECT COUNT(*) FROM document_logs WHERE ' . $where;
        $st = $db->prepare($countSql);
        $st->execute($params);
        $total = (int) $st->fetchColumn();
    } catch (Throwable $e) {
        $dbError = $e->getMessage();
    }
}

prottoyon_page_start('আমার আবেদন', 'applications');
?>
<div class="pp-toolbar">
    <div>
        <span class="pp-kicker">আর্কাইভ</span>
        <h1 style="margin:0;font-size:1.55rem">আমার প্রত্যয়ন</h1>
        <p class="pp-card-sub"><?= prottoyon_bn_digits((string) $total) ?>টি সনদ — নতুন ও পুরাতন সব এখানে।</p>
    </div>
    <a class="pp-btn pp-btn-primary" href="<?= e(repdoc_url('genaretor/prottoyon/')) ?>">+ নতুন সনদ</a>
</div>

<?php if ($dbError !== ''): ?>
    <div class="pp-alert pp-alert-error" role="alert">ডেটাবেস সমস্যা: <?= e($dbError) ?></div>
<?php endif; ?>

<section class="pp-card">
    <form class="pp-toolbar" method="get" action="applications.php" style="margin-bottom:12px">
        <div class="pp-field" style="flex:1 1 220px">
            <label for="type">সনদের ধরন</label>
            <select id="type" name="type">
                <option value="all"<?= $type === 'all' ? ' selected' : '' ?>>সব সনদ</option>
                <?php foreach ($allTypes as $key => $item): ?>
                    <option value="<?= e($key) ?>"<?= $type === $key ? ' selected' : '' ?>><?= e($item['title']) ?><?= !empty($item['legacy']) ? ' (পুরাতন)' : '' ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="pp-field" style="flex:0 1 130px">
            <label for="limit">সর্বোচ্চ</label>
            <select id="limit" name="limit">
                <?php foreach ([50, 100, 200, 500] as $n): ?><option value="<?= $n ?>"<?= $limit === $n ? ' selected' : '' ?>><?= $n ?></option><?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="pp-btn">দেখুন</button>
    </form>

    <?php if ($rows === []): ?>
        <div class="pp-empty">
            <p>এখনো কোনো প্রত্যয়ন তৈরি হয়নি।</p>
            <a class="pp-btn pp-btn-primary" href="<?= e(repdoc_url('genaretor/prottoyon/')) ?>">প্রথম সনদ তৈরি করুন</a>
        </div>
    <?php else: ?>
        <div class="pp-list">
            <?php foreach ($rows as $row): ?>
                <?php
                $tkey = prottoyon_type_from_tool_key((string) $row['tool_key']);
                $title = prottoyon_type_title($tkey, (string) $row['tool_key']);
                $revoked = (($row['status'] ?? 'valid') === 'revoked');
                $decoded = prottoyon_decode_row($row);
                $isNew = ((int) ($decoded['v'] ?? 1) >= 2) && prottoyon_type($tkey) !== null;
                ?>
                <article class="pp-list-row">
                    <div>
                        <h3><?= e($title) ?> <?= $isNew ? '' : '<span class="pp-badge">পুরাতন</span>' ?></h3>
                        <div class="pp-list-meta">
                            <span>সনদ নম্বর: <b><?= e((string) ($row['cert_no'] ?: '—')) ?></b></span>
                            <span><?= e(prottoyon_format_date((string) $row['created_at'], true)) ?></span>
                            <span class="pp-badge <?= $revoked ? 'pp-badge-bad' : 'pp-badge-ok' ?>"><?= $revoked ? 'বাতিল' : 'বৈধ' ?></span>
                        </div>
                    </div>
                    <div class="pp-list-actions">
                        <a class="pp-btn" href="<?= e(repdoc_url('genaretor/prottoyon/view.php?id=' . (int) $row['id'])) ?>">দেখুন / প্রিন্ট</a>
                        <?php if ($isNew): ?>
                            <a class="pp-btn" href="<?= e(repdoc_url('genaretor/prottoyon/form.php?type=' . rawurlencode($tkey) . '&reapply=' . (int) $row['id'])) ?>">আবার আবেদন</a>
                        <?php endif; ?>
                        <?php if (($row['cert_no'] ?? '') !== ''): ?>
                            <a class="pp-btn" href="<?= e(repdoc_url('verify/index.php?no=' . rawurlencode((string) $row['cert_no']))) ?>">যাচাই</a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php prottoyon_page_end(); ?>
