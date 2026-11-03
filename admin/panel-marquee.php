<?php
declare(strict_types=1);

/**
 * Repdoc — admin: entrepreneur panel marquee.
 *
 * One announcement bar shown under the UDC panel header. Text, optional
 * link, scroll speed and the enabled switch all live here.
 */

require_once __DIR__ . '/includes/layout.php';
require_once dirname(__DIR__) . '/src/panel_marquee.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Session expired — please try again.');
        redirect('panel-marquee.php');
    }

    $action = (string) ($_POST['action'] ?? 'save');

    if ($action === 'reset') {
        $result = repdoc_marquee_save(repdoc_marquee_defaults());
        flash_set($result['ok'] ? 'ok' : 'error', $result['ok']
            ? 'মার্কি সেটিংস রিসেট হয়েছে।'
            : 'love/panel_marquee.json লেখা যায়নি — ফোল্ডারের অনুমতি দেখুন।');
        redirect('panel-marquee.php');
    }

    $text    = (string) ($_POST['text'] ?? '');
    $enabled = !empty($_POST['enabled']);
    $result  = repdoc_marquee_save([
        'enabled'    => $enabled,
        'text'       => $text,
        'link'       => (string) ($_POST['link'] ?? ''),
        'link_label' => (string) ($_POST['link_label'] ?? ''),
        'speed'      => (string) ($_POST['speed'] ?? '30'),
    ]);

    if (!$result['ok']) {
        flash_set('error', 'love/panel_marquee.json লেখা যায়নি — ফোল্ডারের অনুমতি দেখুন।');
    } elseif ($enabled && !$result['enabled']) {
        flash_set('warn', 'মেসেজ খালি থাকায় মার্কি চালু করা হয়নি — টেক্সট লিখে সেভ করুন।');
    } else {
        flash_set('ok', 'প্যানেল মার্কি সেভ হয়েছে।');
    }
    redirect('panel-marquee.php');
}

$settings = repdoc_marquee_settings();
$preview  = repdoc_marquee_text((string) ($_POST['text'] ?? $settings['text']));
$previewLink  = repdoc_marquee_url((string) ($_POST['link'] ?? $settings['link']));
$previewLabel = repdoc_marquee_text((string) ($_POST['link_label'] ?? $settings['link_label']), 80);
$previewSpeed = max(10, min(120, (int) ($_POST['speed'] ?? $settings['speed'])));

admin_page_start('Panel Marquee', 'panel-marquee.php');
admin_notice();
?>

<style>
    .mq-grid { display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr); gap: 16px; align-items: start; }
    .mq-form { display: grid; gap: 12px; }
    .mq-form label { display: grid; gap: 6px; }
    .mq-form label > span { color: var(--text-muted); font-size: .72rem; font-weight: 700; letter-spacing: .03em; }
    .mq-form textarea,
    .mq-form input[type="text"],
    .mq-form input[type="url"],
    .mq-form input[type="number"] {
        width: 100%; min-height: 44px; padding: 10px 12px;
        border: 1px solid var(--border-color); border-radius: 8px; background: #fff;
        color: var(--text-primary); font: inherit; font-size: .88rem;
    }
    .mq-form textarea { min-height: 120px; line-height: 1.6; resize: vertical; }
    .mq-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .mq-switch { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border: 1px solid var(--border-color); border-radius: 10px; background: var(--bg-base); }
    .mq-switch input { width: 18px; height: 18px; accent-color: var(--primary); }
    .mq-switch strong { display: block; font-size: .86rem; }
    .mq-switch small { display: block; margin-top: 2px; color: var(--text-muted); font-size: .74rem; }
    .mq-note { color: var(--text-muted); font-size: .8rem; line-height: 1.65; }
    .mq-note code { font-family: var(--font-mono); font-size: .74rem; }
    .mq-preview-stage { padding: 14px; border: 1px dashed var(--border-color); border-radius: 12px; background: var(--bg-base); }
    .mq-preview-top { display: flex; align-items: center; gap: 8px; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 10px 10px 0 0; background: #fff; color: var(--text-muted); font-size: .74rem; font-weight: 600; }
    .mq-preview-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--border-strong); }
    .mq-preview-dot.is-on { background: var(--success); }
    .mq-preview-panel { border: 1px solid var(--border-color); border-top: 0; border-radius: 0 0 10px 10px; overflow: hidden; background: #fff; }
    .mq-preview-empty { padding: 18px; text-align: center; color: var(--text-muted); font-size: .82rem; }
    @media (max-width: 1000px) { .mq-grid { grid-template-columns: 1fr; } }
    @media (max-width: 560px) { .mq-row { grid-template-columns: 1fr; } }
</style>

<div class="admin-page-head">
    <div>
        <h1 class="admin-title">Panel Marquee</h1>
        <p class="admin-sub">
            এন্ট্রাপ্রেনিউর (UDC) প্যানেলের হেডারের ঠিক নিচে স্ক্রলিং নোটিশ।
            এখানে যা সেভ করবেন, সব UDC অপারেটর প্যানেলে সাথে সাথে দেখবে।
        </p>
    </div>
</div>

<div class="mq-grid">
    <div class="panel">
        <div class="panel-head">মেসেজ ও সেটিংস</div>
        <div class="panel-body">
            <form class="mq-form" method="post" action="panel-marquee.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save">

                <label class="mq-switch">
                    <input type="checkbox" name="enabled" value="1"<?= $settings['enabled'] ? ' checked' : '' ?>>
                    <span>
                        <strong>মার্কি চালু রাখুন</strong>
                        <small>বন্ধ থাকলে প্যানেলে কিছুই দেখাবে না।</small>
                    </span>
                </label>

                <label>
                    <span>মেসেজ (সর্বোচ্চ ৫০০ অক্ষর)</span>
                    <textarea name="text" rows="4" maxlength="500" placeholder="যেমন: আজ বিকাল ৫টার পর নতুন দাম কার্যকর হবে।"><?= e((string) $settings['text']) ?></textarea>
                </label>

                <div class="mq-row">
                    <label>
                        <span>লিংক (ঐচ্ছিক)</span>
                        <input type="text" name="link" value="<?= e((string) $settings['link']) ?>" placeholder="https://... অথবা /Entrepreneur/panel/...">
                    </label>
                    <label>
                        <span>লিংক লেবেল</span>
                        <input type="text" name="link_label" maxlength="80" value="<?= e((string) $settings['link_label']) ?>" placeholder="বিস্তারিত দেখুন">
                    </label>
                </div>

                <label>
                    <span>স্ক্রল গতি (সেকেন্ড, ১০–১২০ — কম মানে দ্রুত)</span>
                    <input type="number" name="speed" min="10" max="120" step="1" value="<?= (int) $settings['speed'] ?>">
                </label>

                <div class="panel-actions" style="display:flex;gap:8px;flex-wrap:wrap">
                    <button type="submit" class="btn btn-primary">সেভ করুন</button>
                    <button type="submit" class="btn btn-secondary" name="action" value="reset" formnovalidate>রিসেট</button>
                </div>

            </form>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head">লাইভ প্রিভিউ</div>
        <div class="panel-body">
            <div class="mq-preview-stage">
                <div class="mq-preview-top">
                    <span class="mq-preview-dot<?= $settings['enabled'] ? ' is-on' : '' ?>"></span>
                    <?= $settings['enabled'] ? 'প্যানেলে দেখানো হচ্ছে' : 'এখন বন্ধ' ?>
                </div>
                <div class="mq-preview-panel">
                    <?php if ($preview === ''): ?>
                        <div class="mq-preview-empty">মেসেজ লিখলে এখানে প্রিভিউ দেখা যাবে।</div>
                    <?php else: ?>
                        <div class="panel-marquee" role="status" aria-label="নোটিশ">
                            <span class="panel-marquee-badge">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 11v2a1 1 0 0 0 1 1h2l4 4V6L6 10H4a1 1 0 0 0-1 1z"/><path d="M15 8a5 5 0 0 1 0 8"/><path d="M18 5a9 9 0 0 1 0 14"/></svg>
                                নোটিশ
                            </span>
                            <div class="panel-marquee-viewport">
                                <div class="panel-marquee-track" style="--panel-marquee-duration:<?= $previewSpeed ?>s">
                                    <?php for ($i = 0; $i < 2; $i++): ?>
                                        <span class="panel-marquee-copy"<?= $i === 1 ? ' aria-hidden="true"' : '' ?>>
                                            <span><?= e($preview) ?></span>
                                            <?php if ($previewLink !== ''): ?>
                                                <a href="<?= e($previewLink) ?>"><?= e($previewLabel !== '' ? $previewLabel : 'বিস্তারিত') ?></a>
                                            <?php endif; ?>
                                        </span>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <p class="mq-note" style="margin-top:12px">
                প্রিভিউটি সেভ করা সেটিংস দেখায়। সেভ করার আগে পরিবর্তন দেখতে চাইলে ফর্ম পূরণ করে সেভ করুন —
                সেভ হলেই প্যানেলে লাইভ হয়ে যাবে।
            </p>
        </div>
    </div>
</div>
<?php
admin_page_end();
