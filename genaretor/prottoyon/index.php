<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
require_login();

$me = current_user();
$isAdmin = ($me['role'] ?? '') === 'admin';
if (is_udc() && !$isAdmin) {
    $profile = office_profile();
    if (($profile['office_name'] ?? '') === '' || ($profile['upazila'] ?? '') === '' || ($profile['district'] ?? '') === '') {
        flash_set('error', 'আগে অফিস সেটিংস সম্পূর্ণ করুন — নিজের অফিস ছাড়া প্রত্যয়ন দেওয়া যাবে না।');
        redirect(repdoc_url('genaretor/prottoyon/office.php'));
    }
}

$types = prottoyon_registry();
$prices = tool_prices();
$db = repdoc_db();
$count = 0;
$recent = [];
if ($db) {
    try {
        $st = $db->prepare("SELECT COUNT(*) FROM document_logs WHERE user_id = ? AND tool_key LIKE 'prottoyon!_%' ESCAPE '!'");
        $st->execute([$me['id']]);
        $count = (int) $st->fetchColumn();

        $st = $db->prepare("SELECT * FROM document_logs WHERE user_id = ? AND tool_key LIKE 'prottoyon!_%' ESCAPE '!' ORDER BY id DESC LIMIT 5");
        $st->execute([$me['id']]);
        $recent = $st->fetchAll();
    } catch (Throwable $e) {}
}

$grouped = [];
foreach ($types as $key => $type) {
    $grouped[$type['group']][] = ['key' => $key] + $type;
}

$icons = [
    'id' => 'পরি', 'vote' => 'ভো', 'shield' => 'চর', 'land' => 'ভূ', 'check' => 'অ',
    'road' => 'রা', 'water' => 'ন', 'briefcase' => 'ব্য', 'money' => 'আ', 'family' => 'প',
    'death' => 'মৃ', 'ring' => 'বি', 'access' => 'প্র', 'pen' => 'প্র', 'temple' => 'ধ',
    'calendar' => 'ব', 'file' => 'অ',
];

prottoyon_page_start('প্রত্যয়ন পত্র', 'home');
?>
<section class="pp-hero">
    <div class="pp-hero-copy">
        <span class="pp-kicker">Repdoc · প্রত্যয়ন</span>
        <h1>২৩ ধরনের সনদ,<br>এক জায়গায়।</h1>
        <p>নাগরিকত্ব, ট্রেড লাইসেন্স, ওয়ারিশ, পারিবারিক, আয়, ভূমি ও আরও প্রত্যয়ন — তথ্য দিন, প্রিন্ট করুন, QR দিয়ে যাচাই করুন।</p>
        <div class="pp-hero-actions">
            <a class="btn btn-ledger" href="#ppTypes">সনদ বেছে নিন</a>
            <a class="btn btn-ledger-outline" href="<?= e(repdoc_url('genaretor/prottoyon/applications.php')) ?>">আমার আবেদন</a>
        </div>
    </div>
    <aside class="pp-hero-side">
        <div>
            <h2>আপনার হিসাব</h2>
            <p>প্রতিটি সনদের দাম নিচে দেখানো হয়েছে। ব্যালেন্স থেকে কাটা হবে।</p>
        </div>
        <div>
            <div class="pp-stat"><span>সনদের ধরন</span><b><?= prottoyon_bn_digits('23') ?></b></div>
            <div class="pp-stat"><span>আমার আবেদন</span><b><?= prottoyon_bn_digits((string) $count) ?></b></div>
            <div class="pp-stat"><span>ব্যালেন্স</span><b><?= prottoyon_bn_digits((string) user_balance((int) $me['id'])) ?></b></div>
        </div>
        <a class="btn btn-ledger-outline" href="<?= e(repdoc_url('verify/')) ?>" style="background:#fff">সনদ যাচাই করুন</a>
    </aside>
</section>

<section id="ppTypes">
    <div class="pp-toolbar">
        <div>
            <span class="pp-kicker">সনদের তালিকা</span>
            <h2>কোন সনদ প্রয়োজন?</h2>
        </div>
        <div class="pp-search">
            <label class="pp-label" for="ppTypeSearch">খুঁজুন</label>
            <input type="search" id="ppTypeSearch" placeholder="যেমন: নাগরিকত্ব, ট্রেড, ওয়ারিশ…" autocomplete="off">
        </div>
    </div>

    <?php foreach ($grouped as $group => $items): ?>
        <div class="pp-type-group">
            <h3><?= e($group) ?></h3>
            <div class="pp-type-grid">
                <?php foreach ($items as $item): ?>
                    <?php
                    $key = $item['key'];
                    $price = (int) ($prices['prottoyon_' . $key]['price'] ?? 0);
                    $searchText = $key . ' ' . $item['title'] . ' ' . $item['title_en'] . ' ' . $item['description'];
                    ?>
                    <a class="pp-type-card" href="<?= e(repdoc_url('genaretor/prottoyon/form.php?type=' . rawurlencode($key))) ?>" data-search="<?= e($searchText) ?>">
                        <span class="pp-type-icon"><?= e($icons[$item['icon']] ?? 'স') ?></span>
                        <h4><?= e($item['title']) ?></h4>
                        <p><?= e($item['description']) ?></p>
                        <span class="pp-type-meta">
                            <span class="pp-badge"><?= e($item['title_en']) ?></span>
                            <span class="pp-badge pp-badge-ok"><?= $price > 0 ? prottoyon_bn_digits((string) $price) . ' ক্রেডিট' : 'ফ্রি' ?></span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</section>

<?php if ($recent !== []): ?>
<section class="pp-recent">
    <div class="pp-toolbar">
        <div>
            <span class="pp-kicker">সাম্প্রতিক</span>
            <h2 style="margin:0;font-size:1.2rem">আপনার সর্বশেষ আবেদন</h2>
        </div>
        <a class="pp-btn" href="<?= e(repdoc_url('genaretor/prottoyon/applications.php')) ?>">সব দেখুন →</a>
    </div>
    <div class="pp-list">
        <?php foreach ($recent as $row): ?>
            <?php
            $rkey = prottoyon_type_from_tool_key((string) $row['tool_key']);
            $rdecoded = prottoyon_decode_row($row);
            $rlegacy = (int) ($rdecoded['v'] ?? 1) < 2;
            $rrevoked = (($row['status'] ?? 'valid') === 'revoked');
            $rname = trim((string) ($rdecoded['personal']['name_bn'] ?? $rdecoded['name_bn'] ?? ''));
            ?>
            <article class="pp-list-row">
                <div>
                    <h3><?= e(prottoyon_type_title($rkey, (string) $row['tool_key'])) ?><?= $rlegacy ? ' <span class="pp-badge">পুরাতন</span>' : '' ?></h3>
                    <div class="pp-list-meta">
                        <?php if ($rname !== ''): ?><span><?= e($rname) ?></span><?php endif; ?>
                        <span>সনদ নম্বর: <b><?= e((string) ($row['cert_no'] ?: '—')) ?></b></span>
                        <span><?= e(prottoyon_format_date((string) $row['created_at'], true)) ?></span>
                        <span class="pp-badge <?= $rrevoked ? 'pp-badge-bad' : 'pp-badge-ok' ?>"><?= $rrevoked ? 'বাতিল' : 'বৈধ' ?></span>
                    </div>
                </div>
                <div class="pp-list-actions">
                    <a class="pp-btn" href="<?= e(repdoc_url('genaretor/prottoyon/view.php?id=' . (int) $row['id'])) ?>">দেখুন / প্রিন্ট</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
<?php prottoyon_page_end(); ?>
