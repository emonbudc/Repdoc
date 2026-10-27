<?php
declare(strict_types=1);

/**
 * Repdoc — admin: control page for ONE tool.
 *
 * Included by admin/tool-<slug>.php, which sets $repdoc_tool_slug first.
 * Everything comes from the registry (admin_tool_registry()) so a tool page
 * always matches the sidebar list, the Tools directory and the price store:
 *
 *   - entry file + upload-free status check
 *   - credit price / active switch for every price key the tool is charged on
 *     (same `tools` table as admin/pricing.php; an inactive tool refuses to run)
 *   - usage + credits collected from usage_log / transactions
 *   - a "ভিজিট সাইট" shortcut back to the live tool
 */

require_once __DIR__ . '/layout.php';

$slug = (string) ($repdoc_tool_slug ?? '');
$tool = admin_tool_by_slug($slug);

/** Price keys this tool is charged under, with their stored price/status. */
function admin_tool_price_rows(array $tool, array $prices): array
{
    $rows = [];
    foreach (admin_tool_price_keys($tool) as $key) {
        $row = $prices[$key] ?? ['name' => $key, 'price' => 0, 'active' => 1];
        $rows[$key] = [
            'name'   => (string) ($row['name'] ?? $key),
            'price'  => (int) ($row['price'] ?? 0),
            'active' => (int) ($row['active'] ?? 1) === 1,
        ];
    }
    return $rows;
}

/** One editable price row — re-rendered on its own after a live save. */
function admin_tool_price_row(string $key, string $name, int $price, bool $active, bool $busy): string
{
    ob_start();
    ?>
    <div class="tp-price-row" id="price-row-<?= e(preg_replace('/[^a-z0-9_-]/i', '-', $key)) ?>">
        <div class="tp-price-name">
            <span class="cell-name"><?= e($name) ?></span>
            <span class="cell-sub"><?= e($key) ?></span>
        </div>
        <form method="post" data-live class="tp-price-form">
            <?= csrf_field() ?>
            <input type="hidden" name="tool_key" value="<?= e($key) ?>">
            <input type="number" name="price" min="0" step="1" value="<?= $price ?>"
                   aria-label="<?= e($name) ?> — credits per use">
            <label class="tp-price-active">
                <input type="checkbox" name="active" value="1"<?= $active ? ' checked' : '' ?>>
                <span class="pill <?= $active ? 'pill-approved' : 'pill-rejected' ?>" data-live-state><?= $active ? 'Active' : 'Off' ?></span>
            </label>
            <button type="submit" class="btn btn-secondary btn-sm">সেভ</button>
        </form>
    </div>
    <?php
    return (string) ob_get_clean();
}

if ($tool === null) {
    http_response_code(404);
    admin_page_start('Unknown tool', 'tools.php');
    echo '<div class="alert alert-error" role="alert">এই টুলটি রেজিস্ট্রিতে নেই।</div>';
    admin_page_end();
    exit;
}

$db       = repdoc_db();
$prices   = tool_prices();
$priceKeys = array_keys(admin_tool_price_rows($tool, $prices));
$pageFile = admin_tool_page($tool);

// ── Save one price / switch ───────────────────────────────────────
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $key    = (string) ($_POST['tool_key'] ?? '');
    $price  = max(0, (int) ($_POST['price'] ?? 0));
    $active = isset($_POST['active']);

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Security token mismatch — please try again.');
    } elseif (!in_array($key, $priceKeys, true)) {
        flash_set('error', 'এই টুলের জন্য অজানা প্রাইস কী।');
    } elseif ($db && set_tool_price($key, $price, $active)) {
        flash_set('ok', ($prices[$key]['name'] ?? $key) . ' — ' . number_format($price) . ' ক্রেডিট · ' . ($active ? 'চালু' : 'বন্ধ'));
    } else {
        flash_set('error', 'সেভ করা যায়নি — ডেটাবেস unavailable।');
    }

    if (admin_live_request()) {
        $flash = flash_get();
        $ok    = ($flash['type'] ?? 'ok') !== 'error';
        $payload = [
            'ok'      => $ok,
            'message' => (string) ($flash['message'] ?? ($ok ? 'সেভ হয়েছে।' : 'সেভ হয়নি।')),
        ];
        if ($ok && $key !== '') {
            $fresh = tool_prices();
            $payload['replace'] = [
                'selector' => '#price-row-' . preg_replace('/[^a-z0-9_-]/i', '-', $key),
                'html'     => admin_tool_price_row(
                    $key,
                    (string) ($fresh[$key]['name'] ?? $key),
                    (int) ($fresh[$key]['price'] ?? 0),
                    (int) ($fresh[$key]['active'] ?? 1) === 1,
                    false
                ),
            ];
        }
        admin_live_json($payload + admin_live_counters($db));
    }

    redirect($pageFile);
}

// ── Usage numbers ─────────────────────────────────────────────────
$calls = $okCalls = $credits = 0;
$lastUse = '';
$recent  = [];

if ($db && $priceKeys) {
    $ph = implode(',', array_fill(0, count($priceKeys), '?'));
    try {
        $st = $db->prepare("SELECT COUNT(*) FROM usage_log WHERE tool_key IN ($ph)");
        $st->execute($priceKeys);
        $calls = (int) $st->fetchColumn();

        $st = $db->prepare("SELECT COUNT(*) FROM usage_log WHERE status = 'ok' AND tool_key IN ($ph)");
        $st->execute($priceKeys);
        $okCalls = (int) $st->fetchColumn();

        $st = $db->prepare("SELECT COALESCE(SUM(-delta), 0) FROM transactions WHERE delta < 0 AND tool_key IN ($ph)");
        $st->execute($priceKeys);
        $credits = (int) $st->fetchColumn();

        $st = $db->prepare("SELECT MAX(created_at) FROM usage_log WHERE tool_key IN ($ph)");
        $st->execute($priceKeys);
        $lastUse = (string) ($st->fetchColumn() ?: '');

        $st = $db->prepare("SELECT l.*, u.email FROM usage_log l
                            LEFT JOIN users u ON u.id = l.user_id
                            WHERE l.tool_key IN ($ph)
                            ORDER BY l.id DESC LIMIT 10");
        $st->execute($priceKeys);
        $recent = $st->fetchAll();
    } catch (Throwable $e) {
        $recent = [];
    }
}

/** Bengali digits for the counters. */
function tp_bn(int|string $value): string
{
    return strtr((string) $value, [
        '0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪',
        '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯',
    ]);
}

$live = admin_live_counters($db);

admin_page_start($tool['name'], $pageFile);
admin_notice();
?>

<div class="admin-page-head dash-head">
    <div>
        <p class="kicker"><?= e((string) $tool['cat']) ?> tool</p>
        <h1 class="admin-title"><?= e((string) $tool['name']) ?></h1>
        <p class="admin-sub">
            <?= e((string) $tool['url']) ?> ·
            <?= $tool['live'] ? 'এন্ট্রি ফাইল আছে' : 'এন্ট্রি ফাইল পাওয়া যায়নি' ?> ·
            <?= $priceKeys ? count($priceKeys) . 'টি প্রাইস কী' : 'কোনো চার্জ নেই (ফ্রি টুল)' ?>
        </p>
    </div>
    <a class="btn btn-primary btn-sm" href="<?= e((string) $tool['url']) ?>" target="_blank" rel="noopener">ভিজিট সাইট</a>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <span class="stat-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#a-layout"/></svg></span>
        <span class="stat-text">
            <span class="k">মোট ব্যবহার</span>
            <span class="v"><?= number_format($calls) ?></span>
            <span class="s">usage log অনুযায়ী</span>
        </span>
    </div>
    <div class="stat-card">
        <span class="stat-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#a-check"/></svg></span>
        <span class="stat-text">
            <span class="k">সফল</span>
            <span class="v"><?= number_format($okCalls) ?></span>
            <span class="s">বাকিগুলো ব্যর্থ/ব্লকড</span>
        </span>
    </div>
    <div class="stat-card">
        <span class="stat-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#a-wallet"/></svg></span>
        <span class="stat-text">
            <span class="k">সংগৃহীত ক্রেডিট</span>
            <span class="v"><?= number_format($credits) ?></span>
            <span class="s">লেজার থেকে</span>
        </span>
    </div>
    <div class="stat-card">
        <span class="stat-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#a-log"/></svg></span>
        <span class="stat-text">
            <span class="k">শেষ ব্যবহার</span>
            <span class="v" style="font-size:1rem"><?= $lastUse !== '' ? e($lastUse) : 'কখনো নয়' ?></span>
            <span class="s"><?= $calls > 0 ? 'সর্বশেষ রেকর্ড' : 'এখনো কেউ ব্যবহার করেনি' ?></span>
        </span>
    </div>
</div>

<div class="dash-grid">
    <div class="dash-main">
        <div class="panel">
            <div class="panel-head">
                ক্রেডিট ও অ্যাক্সেস
                <span class="count"><?= count($priceKeys) ?> key</span>
                <a class="panel-link" href="pricing.php">সব টুলের দাম</a>
            </div>
            <?php if (!$priceKeys): ?>
                <div class="empty-state-sm">
                    এই টুলটি কোনো ক্রেডিট নেয় না — ব্যবহারে কিছু কাটা হয় না, তাই দাম বা Active সেট করার কিছু নেই।
                </div>
            <?php elseif (!$db): ?>
                <div class="empty-state-sm">ডেটাবেস unavailable — দাম এখন সেভ করা যাবে না।</div>
            <?php else: ?>
                <div class="tp-price-list">
                    <?php foreach (admin_tool_price_rows($tool, $prices) as $key => $row): ?>
                        <?= admin_tool_price_row((string) $key, $row['name'], $row['price'], $row['active'], false) ?>
                    <?php endforeach; ?>
                </div>
                <div class="panel-body" style="padding-top:0">
                    <p class="admin-sub" style="margin:0">
                        প্রতি ব্যবহারে এই কত ক্রেডিট কাটবে সেটাই দাম (০ = ফ্রি, কিছু কাটে না)। <strong>Off</strong> করে দিলে টুলটা চার্জ নেওয়ার সময়
                        “এই টুলটি বর্তমানে বন্ধ” বলে থেমে যাবে — অ্যাডমিনও বাদ যাবে না, কারণ এটা মেইনটেন্যান্স সুইচ।
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <div class="panel">
            <div class="panel-head">
                শেষ ব্যবহার
                <span class="count">শেষ ১০</span>
                <a class="panel-link" href="transactions.php">লেজার</a>
            </div>
            <?php if (!$recent): ?>
                <div class="empty-state-sm">এই টুলের কোনো ব্যবহার এখনো লগ হয়নি।</div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="data">
                        <thead><tr><th>ইউজার</th><th>টুল কী</th><th>ফলাফল</th><th>বিস্তারিত</th><th>সময়</th></tr></thead>
                        <tbody>
                            <?php foreach ($recent as $row): ?>
                                <?php
                                $status = (string) $row['status'];
                                $cls = $status === 'ok' ? 'pill-approved' : (str_starts_with($status, 'denied') ? 'pill-pending' : 'pill-rejected');
                                ?>
                                <tr>
                                    <td><span class="cell-sub"><?= e((string) ($row['email'] ?? '—')) ?></span></td>
                                    <td><span class="cell-sub"><?= e((string) $row['tool_key']) ?></span></td>
                                    <td><span class="pill <?= $cls ?>"><?= e($status) ?></span></td>
                                    <td><span class="cell-sub"><?= e((string) ($row['detail'] ?? '')) ?></span></td>
                                    <td><span class="cell-sub"><?= e((string) $row['created_at']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div><!-- /dash-main -->

    <div class="dash-side">
        <div class="panel">
            <div class="panel-head">টুল প্রোফাইল</div>
            <div class="panel-body">
                <p class="admin-sub" style="margin:0 0 10px">
                    <strong><?= e((string) $tool['name']) ?></strong><br>
                    ক্যাটাগরি: <?= e((string) $tool['cat']) ?><br>
                    এন্ট্রি: <code><?= e((string) $tool['check']) ?></code><br>
                    স্ট্যাটাস: <?= $tool['live'] ? 'লাইভ' : 'ফাইল নেই' ?>
                </p>
                <div class="quick-grid" style="padding:0">
                    <a class="quick-link" href="<?= e((string) $tool['url']) ?>" target="_blank" rel="noopener">
                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-external"/></svg>ভিজিট সাইট
                    </a>
                    <a class="quick-link" href="tools.php">
                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-layout"/></svg>সব টুল
                    </a>
                    <a class="quick-link" href="pricing.php">
                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-tag"/></svg>দাম তালিকা
                    </a>
                    <a class="quick-link" href="documents.php">
                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-log"/></svg>ডকুমেন্ট লগ
                    </a>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-head">নোট</div>
            <div class="panel-body">
                <p class="admin-sub" style="margin:0">
                    দাম বদলালে সাথে সাথে সব ইউজারের জন্য কার্যকর হয়। ক্রেডিট ০ হলে কোনো লেজার এন্ট্রি হয় না।
                    অ্যাডমিনদের কাছ থেকে সবসময়ই কিছু কাটা হয় না।
                </p>
                <p class="admin-sub" style="margin:10px 0 0">
                    মোট ইউজার <?= e(tp_bn($live['stats']['users'])) ?> · পেন্ডিং <?= e(tp_bn($live['stats']['users_pending'])) ?>
                </p>
            </div>
        </div>
    </div><!-- /dash-side -->
</div><!-- /dash-grid -->

<?php admin_page_end(); ?>
