<?php
declare(strict_types=1);

/**
 * Repdoc — admin: passport photo tool.
 *
 * Two jobs:
 *   1. Settings — the default AI prompt, who may use the tool (UDC + admins,
 *      or admins only) and how long generated files are kept. Stored in
 *      love/passport_settings.json and read by tools/passport/lib.php.
 *   2. Review — every generated photo under love/passport/<user>/, with the
 *      owner, size and age, a preview (streamed through tools/passport/file.php)
 *      and a delete action.
 */

require_once __DIR__ . '/includes/layout.php';
require_once dirname(__DIR__) . '/src/passport.php';

/** Absolute path of a user's passport folder (never created here). */
function admin_passport_dir(int $userId): string
{
    return dirname(__DIR__) . '/love/passport/' . $userId;
}

/** A stored file name is always pass_ or src_ plus a safe image extension. */
function admin_passport_filename(string $name): string
{
    $name = trim($name);
    if (!preg_match('/^(pass|src)_[A-Za-z0-9]{6,64}\.(jpg|jpeg|png|webp)$/i', $name)) return '';
    return $name;
}

/**
 * The tool only streams the names it generates (pass_ + 32 hex, src_ + 24 hex,
 * see passport_file_path()). Anything else can be listed and deleted here, but
 * has no preview and no download — so the page says so instead of showing a
 * broken image.
 */
function admin_passport_streamable(string $name): bool
{
    return (bool) preg_match('/^(pass_[a-f0-9]{32}|src_[a-f0-9]{24})\.(jpg|png|webp)$/', $name);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Session expired — please try again.');
        redirect('passport.php');
    }

    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'settings' || $action === 'restore_prompt') {
        $prompt = $action === 'restore_prompt'
            ? repdoc_passport_builtin_prompt()
            : (string) ($_POST['prompt'] ?? '');

        $result = repdoc_passport_save([
            'prompt'          => $prompt,
            'access'          => (string) ($_POST['access'] ?? 'udc'),
            'retention_hours' => (string) ($_POST['retention_hours'] ?? '24'),
        ]);
        if (!$result['ok']) {
            flash_set('error', 'love/passport_settings.json লেখা যায়নি — ফোল্ডারের অনুমতি দেখুন।');
        } elseif ($action === 'restore_prompt') {
            flash_set('ok', 'ডিফল্ট প্রম্পট ফিরিয়ে আনা হয়েছে।');
        } else {
            flash_set('ok', 'পাসপোর্ট টুলের সেটিংস সেভ হয়েছে।');
        }
        redirect('passport.php');
    }

    if ($action === 'delete_file') {
        $userId = (int) ($_POST['u'] ?? 0);
        $file   = admin_passport_filename((string) ($_POST['f'] ?? ''));
        $path   = ($userId > 0 && $file !== '') ? admin_passport_dir($userId) . '/' . $file : '';

        if ($path !== '' && is_file($path) && @unlink($path)) {
            flash_set('ok', 'ফাইলটি মুছে ফেলা হয়েছে (' . $file . ')।');
        } else {
            flash_set('warn', 'ফাইলটি পাওয়া যায়নি বা মুছে ফেলা যায়নি।');
        }
        redirect('passport.php' . ($userId > 0 ? '?u=' . $userId : ''));
    }

    if ($action === 'delete_user') {
        $userId = (int) ($_POST['u'] ?? 0);
        $dir    = $userId > 0 ? admin_passport_dir($userId) : '';
        $count  = 0;

        if ($dir !== '' && is_dir($dir)) {
            foreach ((array) @scandir($dir) as $entry) {
                $entry = (string) $entry;
                if (admin_passport_filename($entry) === '') continue;
                if (@unlink($dir . '/' . $entry)) $count++;
            }
        }
        flash_set($count > 0 ? 'ok' : 'warn', $count > 0
            ? 'ইউজার #' . $userId . '-এর ' . $count . 'টি ফাইল মুছে ফেলা হয়েছে।'
            : 'মুছে ফেলার মতো ফাইল পাওয়া যায়নি।');
        redirect('passport.php');
    }

    flash_set('warn', 'অজানা অনুরোধ — কিছুই বদলায়নি।');
    redirect('passport.php');
}

$settings  = repdoc_passport_settings();
$providers = repdoc_passport_provider_status();
$price     = tool_price('passport_photo');

/** Collect every stored file for every user folder. */
$files = [];
$dirs  = (array) @glob(dirname(__DIR__) . '/love/passport/*', GLOB_ONLYDIR);
foreach ($dirs as $dir) {
    $userId = (int) basename((string) $dir);
    if ($userId <= 0) continue;
    foreach ((array) @scandir((string) $dir) as $entry) {
        $entry = (string) $entry;
        $name  = admin_passport_filename($entry);
        if ($name === '') continue;
        $path = $dir . '/' . $entry;
        if (!is_file($path)) continue;

        $files[] = [
            'user'       => $userId,
            'name'       => $name,
            'kind'       => str_starts_with($name, 'pass_') ? 'generated' : 'upload',
            'streamable' => admin_passport_streamable($name),
            'size'       => (int) @filesize($path),
            'mtime'      => (int) @filemtime($path),
        ];
    }
}
usort($files, static fn(array $a, array $b): int => $b['mtime'] <=> $a['mtime']);

/** Owner names for the ids we show. */
$owners = [];
$ids = array_values(array_unique(array_map(static fn(array $f): int => (int) $f['user'], $files)));
if ($ids !== []) {
    $db = repdoc_db();
    if ($db) {
        try {
            $in = implode(',', array_fill(0, count($ids), '?'));
            $st = $db->prepare('SELECT id, fullname, email, role FROM users WHERE id IN (' . $in . ')');
            $st->execute($ids);
            foreach ($st->fetchAll() as $row) {
                $owners[(int) $row['id']] = $row;
            }
        } catch (Throwable $e) {}
    }
}

$filterUser = isset($_GET['u']) ? (int) $_GET['u'] : 0;
$query      = trim((string) ($_GET['q'] ?? ''));

$shown = [];
foreach ($files as $file) {
    if ($filterUser > 0 && (int) $file['user'] !== $filterUser) continue;
    if ($query !== '') {
        $owner = $owners[(int) $file['user']] ?? null;
        $haystack = mb_strtolower(
            $file['name'] . ' ' . $file['user'] . ' '
            . (string) ($owner['fullname'] ?? '') . ' ' . (string) ($owner['email'] ?? ''),
            'UTF-8'
        );
        if (mb_stripos($haystack, mb_strtolower($query, 'UTF-8')) === false) continue;
    }
    $shown[] = $file;
}

$totalSize = 0;
$generated = 0;
foreach ($files as $file) {
    $totalSize += (int) $file['size'];
    if ($file['kind'] === 'generated') $generated++;
}
$sizeLabel = static function (int $bytes): string {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1024 * 1024) return number_format($bytes / 1024, 1) . ' KB';
    return number_format($bytes / (1024 * 1024), 2) . ' MB';
};
$aged = static function (int $mtime): string {
    $diff = time() - $mtime;
    if ($diff < 60) return 'এইমাত্র';
    if ($diff < 3600) return intdiv($diff, 60) . ' মিনিট আগে';
    if ($diff < 86400) return intdiv($diff, 3600) . ' ঘণ্টা আগে';
    return intdiv($diff, 86400) . ' দিন আগে';
};

admin_page_start('Passport Photo', 'passport.php');
admin_notice();
?>

<style>
    .pp-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
    .pp-head-actions { display: flex; flex-wrap: wrap; gap: 8px; }
    .pp-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin-bottom: var(--space-5); }
    .pp-stat { padding: 14px 16px; border: 1px solid var(--border-color); border-radius: var(--radius-md); background: var(--bg-surface); box-shadow: var(--shadow-sm); }
    .pp-stat .k { display: block; color: var(--text-muted); font-size: .72rem; font-weight: 600; }
    .pp-stat .v { display: block; margin-top: 3px; color: var(--text-primary); font-family: var(--font-mono); font-size: 1.22rem; font-weight: 700; }
    .pp-grid { display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr); gap: 16px; align-items: start; }
    .pp-form { display: grid; gap: 12px; }
    .pp-form label { display: grid; gap: 6px; }
    .pp-form label > span { color: var(--text-muted); font-size: .72rem; font-weight: 700; letter-spacing: .03em; }
    .pp-form textarea, .pp-form select, .pp-form input {
        width: 100%; min-height: 44px; padding: 10px 12px;
        border: 1px solid var(--border-color); border-radius: 8px; background: #fff;
        color: var(--text-primary); font: inherit; font-size: .88rem;
    }
    .pp-form textarea { min-height: 150px; line-height: 1.6; resize: vertical; }
    .pp-form .pp-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .pp-form-actions { display: flex; gap: 8px; flex-wrap: wrap; }
    .pp-note { color: var(--text-muted); font-size: .8rem; line-height: 1.6; }
    .pp-providers { display: flex; flex-direction: column; gap: 8px; }
    .pp-provider { display: flex; align-items: center; gap: 8px; padding: 9px 11px; border: 1px solid var(--border-color); border-radius: 9px; background: var(--bg-base); font-size: .84rem; }
    .pp-dot { width: 9px; height: 9px; border-radius: 50%; background: var(--border-strong); flex: none; }
    .pp-dot.is-on { background: var(--success); }
    .pp-filters { display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; margin-bottom: 14px; }
    .pp-filters .field { margin-bottom: 0; flex: 1 1 220px; }
    .pp-table { display: flex; flex-direction: column; gap: 8px; }
    .pp-item { display: grid; grid-template-columns: 64px minmax(0, 1fr) auto; gap: 12px; align-items: center; padding: 10px; border: 1px solid var(--border-color); border-radius: 12px; background: var(--bg-base); }
    .pp-thumb { width: 60px; height: 60px; object-fit: cover; border-radius: 9px; border: 1px solid var(--border-color); background: #fff; display: block; }
    .pp-thumb-empty { display: grid; place-items: center; text-align: center; color: var(--text-muted); font-size: .6rem; line-height: 1.2; background: var(--bg-muted); }
    .pp-item-main { min-width: 0; }
    .pp-item-name { font-weight: 600; font-size: .88rem; overflow-wrap: anywhere; }
    .pp-item-meta { color: var(--text-muted); font-size: .74rem; font-family: var(--font-mono); }
    .pp-item-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .pp-chip { padding: 2px 8px; border-radius: 999px; font-size: .66rem; font-weight: 700; background: var(--primary-light); color: var(--primary); }
    .pp-chip.is-upload { background: var(--bg-muted); color: var(--text-muted); }
    @media (max-width: 1000px) { .pp-grid { grid-template-columns: 1fr; } }
    @media (max-width: 560px) { .pp-item { grid-template-columns: 52px minmax(0, 1fr); } .pp-item-actions { grid-column: 1 / -1; } .pp-form .pp-row { grid-template-columns: 1fr; } }
</style>

<div class="admin-page-head pp-head">
    <div>
        <h1 class="admin-title">Passport Photo</h1>
        <p class="admin-sub">
            টুলের ডিফল্ট AI প্রম্পট, কে ব্যবহার করতে পারবে আর ফাইল কতক্ষণ রাখা হবে — সব এখান থেকে।
            নিচে ইউজারদের তৈরি করা ছবিগুলো দেখা, ডাউনলোড আর মুছে ফেলা যাবে।
        </p>
    </div>
    <div class="pp-head-actions">
        <a class="btn btn-secondary" href="../tools/passport/" target="_blank" rel="noopener">ভিজিট সাইট</a>
        <a class="btn btn-secondary" href="pricing.php">দাম: <?= (int) $price ?> ক্রেডিট</a>
    </div>
</div>

<div class="pp-stats">
    <div class="pp-stat"><span class="k">সংরক্ষিত ফাইল</span><span class="v"><?= count($files) ?></span></div>
    <div class="pp-stat"><span class="k">তৈরি করা ছবি</span><span class="v"><?= $generated ?></span></div>
    <div class="pp-stat"><span class="k">মোট সাইজ</span><span class="v"><?= e($sizeLabel($totalSize)) ?></span></div>
    <div class="pp-stat"><span class="k">ইউজার ফোল্ডার</span><span class="v"><?= count(array_unique(array_map(static fn(array $f): int => (int) $f['user'], $files))) ?></span></div>
    <div class="pp-stat"><span class="k">রিটেনশন</span><span class="v"><?= (int) $settings['retention_hours'] ?> ঘণ্টা</span></div>
</div>

<div class="pp-grid">
    <div class="panel">
        <div class="panel-head">সেটিংস</div>
        <div class="panel-body">
            <form class="pp-form" method="post" action="passport.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="settings">

                <label>
                    <span>ডিফল্ট প্রম্পট (AI ছবি তৈরির নির্দেশনা)</span>
                    <textarea name="prompt" rows="8" maxlength="3000"><?= e((string) $settings['prompt']) ?></textarea>
                </label>

                <div class="pp-row">
                    <label>
                        <span>কে ব্যবহার করতে পারবে</span>
                        <select name="access">
                            <?php foreach (repdoc_passport_access_options() as $value => $label): ?>
                                <option value="<?= e($value) ?>"<?= $settings['access'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        <span>ফাইল কতক্ষণ রাখা হবে (ঘণ্টা, ১–১৬৮)</span>
                        <input type="number" name="retention_hours" min="1" max="168" step="1" value="<?= (int) $settings['retention_hours'] ?>">
                    </label>
                </div>

                <div class="pp-form-actions">
                    <button type="submit" class="btn btn-primary">সেটিংস সেভ করুন</button>
                    <button type="submit" class="btn btn-secondary" name="action" value="restore_prompt">ডিফল্ট প্রম্পট ফিরিয়ে আনুন</button>
                </div>

                <p class="pp-note">
                    প্রম্পট খুব ছোট হলে (৪০ অক্ষরের কম) সেভ হবে না — তখন পুরনো ডিফল্টটাই থাকবে।
                    ব্যবহারকারী চাইলে টুল পেজে নিজের প্রম্পট লিখতে পারবে; এটা শুধু ডিফল্ট।
                </p>
            </form>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head">ইমেজ প্রোভাইডার <span class="count"><?= e(strtoupper((string) $providers['mode'])) ?></span></div>
        <div class="panel-body">
            <div class="pp-providers">
                <div class="pp-provider"><span class="pp-dot<?= $providers['pollinations'] ? ' is-on' : '' ?>"></span> Pollinations / FLUX — <?= $providers['pollinations'] ? 'কী সেট আছে' : 'কী নেই' ?></div>
                <div class="pp-provider"><span class="pp-dot<?= $providers['gemini'] ? ' is-on' : '' ?>"></span> Gemini image — <?= $providers['gemini'] ? 'কী সেট আছে' : 'কী নেই' ?></div>
                <div class="pp-provider"><span class="pp-dot is-on"></span> কী না থাকলে সাধারণ ক্রপ (AI ছাড়া)</div>
            </div>
            <p class="pp-note" style="margin-top:12px">
                কীগুলো সার্ভারে থাকে (<code>love/hosting-config.php</code> বা হোস্টিং সার্ভারে set করা এনভায়রনমেন্ট) —
                <code>POLLINATIONS_API_KEY</code>, <code>GEMINI_API_KEY</code>, <code>PASSPORT_IMAGE_PROVIDER</code>।
                নিরাপত্তার জন্য এই পেজে কী-এর মান কখনো দেখানো হয় না।
            </p>
        </div>
    </div>
</div>

<div class="panel">
    <div class="panel-head">
        ইউজারদের ছবি
        <span class="count"><?= count($shown) ?> দেখানো হচ্ছে / <?= count($files) ?></span>
    </div>
    <div class="panel-body">
        <form class="pp-filters" method="get" action="passport.php">
            <div class="field">
                <label for="pp-q">খুঁজুন (নাম, ইমেইল বা ফাইল)</label>
                <input type="search" id="pp-q" name="q" value="<?= e($query) ?>" placeholder="যেমন: rahim@ বা pass_9f2c">
            </div>
            <div class="field">
                <label for="pp-u">ইউজার আইডি</label>
                <input type="number" id="pp-u" name="u" min="0" step="1" value="<?= $filterUser > 0 ? (int) $filterUser : '' ?>" placeholder="সব">
            </div>
            <div class="pp-item-actions">
                <button type="submit" class="btn btn-primary btn-sm">ফিল্টার</button>
                <?php if ($query !== '' || $filterUser > 0): ?>
                    <a class="btn btn-secondary btn-sm" href="passport.php">রিসেট</a>
                <?php endif; ?>
                <?php if ($filterUser > 0): ?>
                    <button type="submit" class="btn btn-danger btn-sm" form="ppDeleteUser">এই ইউজারের সব মুছুন</button>
                <?php endif; ?>
            </div>
        </form>

        <form id="ppDeleteUser" method="post" action="passport.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete_user">
            <input type="hidden" name="u" value="<?= (int) $filterUser ?>">
        </form>

        <?php if ($shown === []): ?>
            <div class="empty-state-sm">
                <?= $files === [] ? 'এখনো কোনো ছবি তৈরি হয়নি — অথবা রিটেনশন সময় পেরিয়ে সব মুছে গেছে।' : 'এই ফিল্টারে কোনো ফাইল নেই।' ?>
            </div>
        <?php else: ?>
            <div class="pp-table">
                <?php foreach ($shown as $file): ?>
                    <?php
                    $owner = $owners[(int) $file['user']] ?? null;
                    $url   = '../tools/passport/file.php?u=' . (int) $file['user'] . '&amp;f=' . rawurlencode((string) $file['name']);
                    ?>
                    <div class="pp-item">
                        <?php if ($file['streamable']): ?>
                            <img class="pp-thumb" src="<?= $url ?>" alt="<?= e((string) $file['name']) ?>" loading="lazy" width="60" height="60">
                        <?php else: ?>
                            <span class="pp-thumb pp-thumb-empty">প্রিভিউ নেই</span>
                        <?php endif; ?>
                        <div class="pp-item-main">
                            <div class="pp-item-name"><?= e((string) $file['name']) ?> <span class="pp-chip<?= $file['kind'] === 'generated' ? '' : ' is-upload' ?>"><?= $file['kind'] === 'generated' ? 'তৈরি' : 'আপলোড' ?></span></div>
                            <div class="pp-item-meta">
                                ইউজার #<?= (int) $file['user'] ?>
                                <?php if ($owner): ?>
                                    · <?= e((string) ($owner['fullname'] !== '' ? $owner['fullname'] : $owner['email'])) ?>
                                    <?php if (!empty($owner['email'])): ?>(<?= e((string) $owner['email']) ?>)<?php endif; ?>
                                <?php else: ?>
                                    · (ডেটাবেসে ইউজার নেই)
                                <?php endif; ?>
                            </div>
                            <div class="pp-item-meta"><?= e($sizeLabel((int) $file['size'])) ?> · <?= e($aged((int) $file['mtime'])) ?> · <?= e(date('d M Y, H:i', (int) $file['mtime'])) ?></div>
                            <?php if (!$file['streamable']): ?>
                                <div class="pp-item-meta">নামটা টুলের নিয়মে নেই (পুরনো ফাইল) — প্রিভিউ/ডাউনলোড হয় না, তবে মুছে ফেলা যাবে।</div>
                            <?php endif; ?>
                        </div>
                        <div class="pp-item-actions">
                            <?php if ($file['streamable']): ?>
                                <a class="btn btn-secondary btn-sm" href="<?= $url ?>&amp;download=1">ডাউনলোড</a>
                            <?php endif; ?>
                            <form method="post" action="passport.php">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete_file">
                                <input type="hidden" name="u" value="<?= (int) $file['user'] ?>">
                                <input type="hidden" name="f" value="<?= e((string) $file['name']) ?>">
                                <button type="submit" class="btn btn-danger btn-sm">মুছুন</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="pp-note" style="margin-top:14px">
                ফাইলগুলো থাকে <code>love/passport/&lt;ইউজার-আইডি&gt;/</code>-তে এবং সরাসরি ওয়েব-পাথ নয় —
                শুধু <code>tools/passport/file.php</code> দিয়ে স্ট্রিম হয় (মালিক বা অ্যাডমিন)।
                রিটেনশন পার হলে টুল খোলার সময় পুরনো ফাইল নিজে নিজেই মুছে যায়।
            </p>
        <?php endif; ?>
    </div>
</div>

<?php admin_page_end(); ?>
