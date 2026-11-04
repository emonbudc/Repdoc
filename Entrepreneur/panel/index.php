<?php
declare(strict_types=1);

/**
 * Repdoc — UDC panel dashboard.
 * Live clock, account details, balance and quick links at a glance.
 */

require_once __DIR__ . '/includes/layout.php';

$me = current_user();
$db = repdoc_db();

$stats = ['balance' => 0, 'last_login' => ''];

$udcTools = udc_tools();

if ($db) {
    try {
        $stats['balance'] = user_balance($me['id']);
        $st = $db->prepare('SELECT last_login_at FROM users WHERE id = ?');
        $st->execute([$me['id']]);
        $stats['last_login'] = (string) ($st->fetchColumn() ?: '');
    } catch (Throwable $e) {
        $dbErr = $e->getMessage();
        $db = null;
    }
}

$lastLogin = $stats['last_login'] !== '' && strtotime($stats['last_login'])
    ? prottoyon_bn_digits(date('d/m/Y h:i A', (int) strtotime($stats['last_login'])))
    : '—';
$displayName = $me['fullname'] !== '' ? $me['fullname'] : $me['email'];
$ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
if ($ip === '') $ip = '—';

udc_page_start('ড্যাশবোর্ড', 'index.php');
udc_notice();
?>

<div class="admin-page-head dash-head">
    <div>
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

<div class="stat-grid stat-grid-3">
    <div class="stat-card stat-clock">
        <span class="stat-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#a-clock"/></svg></span>
        <span class="stat-text">
            <span class="v" id="dashClockTime">--:--:--</span>
            <span class="s" id="dashClockDate">--/--/----</span>
            <span class="s" id="dashClockDay">আজকে —</span>
        </span>
    </div>
    <div class="stat-card stat-user">
        <span class="stat-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#a-user"/></svg></span>
        <span class="stat-text">
            <span class="k">নাম</span>
            <span class="v"><?= e($displayName) ?></span>
            <span class="s">সর্বশেষ লগইন - <?= e($lastLogin) ?></span>
            <span class="s">আইপি - <?= e($ip) ?></span>
        </span>
    </div>
    <div class="stat-card">
        <span class="stat-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#a-wallet"/></svg></span>
        <span class="stat-text"><span class="k">ব্যালেন্স</span><span class="v"><?= number_format($stats['balance']) ?></span><span class="s">ক্রেডিট</span></span>
    </div>
</div>

<div class="dash-grid">
    <div class="dash-main">
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
    </div>

    <div class="dash-side">
        <div class="panel">
            <div class="panel-head">দ্রুত লিংক</div>
            <div class="panel-body quick-grid">
                <a class="quick-link" href="<?= e(repdoc_url('Entrepreneur/panel/prottoyons.php')) ?>"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-award"/></svg>প্রত্যয়নসমূহ</a>
                <a class="quick-link" href="<?= e(repdoc_url('genaretor/prottoyon/')) ?>"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-file"/></svg>নতুন প্রত্যয়ন</a>
                <a class="quick-link" href="<?= e(repdoc_url('Entrepreneur/panel/office.php')) ?>"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-lock"/></svg>অফিস সেটিংস</a>
                <?php if (has_archive_access()): ?>
                    <a class="quick-link" href="<?= e(repdoc_url('Entrepreneur/panel/landing.php')) ?>"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-file"/></svg>ডকুমেন্ট আর্কাইভ</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<script>
(function () {
    var timeEl = document.getElementById('dashClockTime');
    var dateEl = document.getElementById('dashClockDate');
    var dayEl = document.getElementById('dashClockDay');
    if (!timeEl || !dateEl || !dayEl) return;

    var bnDigits = '০১২৩৪৫৬৭৮৯';
    var weekdays = {
        Sunday: 'রবিবার',
        Monday: 'সোমবার',
        Tuesday: 'মঙ্গলবার',
        Wednesday: 'বুধবার',
        Thursday: 'বৃহস্পতিবার',
        Friday: 'শুক্রবার',
        Saturday: 'শনিবার'
    };

    function toBn(value) {
        return String(value).replace(/[0-9]/g, function (digit) {
            return bnDigits.charAt(parseInt(digit, 10));
        });
    }

    function tick() {
        var now = new Date();
        var time = new Intl.DateTimeFormat('en-US', {
            timeZone: 'Asia/Dhaka',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        }).format(now);
        var date = new Intl.DateTimeFormat('en-GB', {
            timeZone: 'Asia/Dhaka',
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        }).format(now);
        var weekday = new Intl.DateTimeFormat('en-US', {
            timeZone: 'Asia/Dhaka',
            weekday: 'long'
        }).format(now);

        timeEl.textContent = toBn(time);
        dateEl.textContent = toBn(date);
        dayEl.textContent = 'আজকে ' + (weekdays[weekday] || weekday);
    }

    tick();
    setInterval(tick, 1000);
})();
</script>
<?php udc_page_end(); ?>
