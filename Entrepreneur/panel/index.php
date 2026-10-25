<?php
declare(strict_types=1);

/**
 * Repdoc — UDC panel dashboard.
 * Own prottoyon counts, balance and office status at a glance.
 */

require_once __DIR__ . '/includes/layout.php';

$me = current_user();
$db = repdoc_db();

$stats = ['total' => 0, 'valid' => 0, 'revoked' => 0, 'balance' => 0];
$office = office_profile();

/** All openable tools — single registry shared with the sidebar (layout.php). */
$udcTools = udc_tools();

if ($db) {
    try {
        $st = $db->prepare('SELECT COUNT(*) FROM document_logs WHERE user_id = ? AND tool_key LIKE \'prottoyon!_%\' ESCAPE \'!\'');
        $st->execute([$me['id']]);
        $stats['total'] = (int) $st->fetchColumn();
        $st = $db->prepare('SELECT COUNT(*) FROM document_logs WHERE user_id = ? AND tool_key LIKE \'prottoyon!_%\' ESCAPE \'!\' AND status = \'revoked\'');
        $st->execute([$me['id']]);
        $stats['revoked'] = (int) $st->fetchColumn();
        $stats['valid'] = $stats['total'] - $stats['revoked'];
        $stats['balance'] = user_balance($me['id']);
    } catch (Throwable $e) {
        $dbErr = $e->getMessage();
        $db = null;
    }
}

udc_page_start('ড্যাশবোর্ড', 'index.php');
udc_notice();
?>

<div class="admin-page-head dash-head">
    <div>
        <p class="kicker">UDC Panel</p>
        <h1 class="admin-title">ড্যাশবোর্ড</h1>
        <p class="admin-sub">আপনার প্রত্যয়ন ও ব্যালেন্স এক নজরে।</p>
    </div>
    <div style="display:flex; flex-wrap:wrap; gap:8px">
        <?php if (has_archive_access()): ?>
            <a class="btn btn-secondary btn-sm" href="<?= e(repdoc_url('Entrepreneur/panel/landing.php')) ?>">ডকুমেন্ট আর্কাইভ</a>
        <?php endif; ?>
        <a class="btn btn-primary btn-sm" href="<?= e(repdoc_url('genaretor/prottoyon/')) ?>">+ নতুন প্রত্যয়ন</a>
    </div>
</div>

<?php if (!$db): ?>
    <div class="alert alert-error" role="alert">ডেটাবেস পাওয়া যাচ্ছে না।<?= isset($dbErr) && $dbErr !== '' ? ' (' . e($dbErr) . ')' : '' ?></div>
<?php else: ?>

<div class="stat-grid">
    <div class="stat-card">
        <span class="stat-text"><span class="k">মোট প্রত্যয়ন</span><span class="v"><?= number_format($stats['total']) ?></span><span class="s"><?= number_format($stats['valid']) ?> বৈধ</span></span>
    </div>
    <div class="stat-card">
        <span class="stat-text"><span class="k">বাতিল</span><span class="v"><?= number_format($stats['revoked']) ?></span><span class="s">admin কর্তৃক</span></span>
    </div>
    <div class="stat-card">
        <span class="stat-text"><span class="k">ব্যালেন্স</span><span class="v"><?= number_format($stats['balance']) ?></span><span class="s">ক্রেডিট</span></span>
    </div>
    <div class="stat-card">
        <span class="stat-text"><span class="k">অফিস</span><span class="v" style="font-size:1rem"><?= e($office['office_name'] !== '' ? $office['office_name'] : 'সেট হয়নি') ?></span><span class="s"><a href="<?= e(repdoc_url('genaretor/prottoyon/office.php')) ?>">সেটিংস</a></span></span>
    </div>
</div>

<div class="panel">
    <div class="panel-head">সব টুলস <span class="count"><?= count($udcTools) ?>টি</span></div>
    <div class="panel-body tools-grid">
        <?php foreach ($udcTools as $t): ?>
            <a class="tool-card" href="<?= e(repdoc_url($t['url'])) ?>">
                <span class="tool-icon"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#<?= e($t['icon']) ?>"/></svg></span>
                <span class="tool-name"><?= e($t['name']) ?></span>
                <span class="tool-cat"><?= e($t['cat']) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<?php endif; ?>
<?php udc_page_end(); ?>
