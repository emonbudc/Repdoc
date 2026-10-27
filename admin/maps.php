<?php
declare(strict_types=1);

/**
 * Repdoc — admin: mouza map browser (সারা বাংলাদেশ).
 *
 * Admin-only search over the Drive-hosted mouza maps: district → upazila →
 * mouza (name or JL) → sheet download. The files are never copied here; every
 * download goes through admin/maps-download.php, which logs and forwards to
 * Google Drive. Data comes from maps/data/manifest.php (see src/maps.php).
 */

require_once __DIR__ . '/includes/layout.php';
require_once dirname(__DIR__) . '/src/maps.php';

$self = 'maps.php';

if (repdoc_maps_district(0) === null) {
    admin_page_start('Mouza Maps', $self);
    echo '<div class="alert alert-warning">ম্যানিফেস্ট পাওয়া যায়নি — maps/data/manifest.php নেই।</div>';
    admin_page_end();
    exit;
}

/** Group districts under their division so the cascade can grow with the data. */
$divisions = [];
foreach (repdoc_maps_districts() as $idx => $row) {
    $key = (string) ($row['division_bn'] ?? '');
    if (!isset($divisions[$key])) {
        $divisions[$key] = [
            'bn'        => $key,
            'en'        => (string) ($row['division_en'] ?? ''),
            'districts' => [],
        ];
    }
    $divisions[$key]['districts'][] = [
        'idx' => (int) $idx,
        'bn'  => (string) ($row['bn'] ?? ''),
        'en'  => (string) ($row['en'] ?? ''),
    ];
}
$divisions = array_values($divisions);

$divisionIdx  = isset($_GET['div']) ? (int) $_GET['div'] : 0;
$division     = $divisions[$divisionIdx] ?? $divisions[0];
$divisionIdx  = array_search($division, $divisions, true) ?: 0;
$districtIdx  = isset($_GET['dist']) ? (int) $_GET['dist'] : (int) $division['districts'][0]['idx'];
$districtRow  = null;
foreach ($division['districts'] as $candidate) {
    if ($candidate['idx'] === $districtIdx) {
        $districtRow = $candidate;
        break;
    }
}
if ($districtRow === null) {
    $districtRow = $division['districts'][0];
    $districtIdx = (int) $districtRow['idx'];
}

$district = repdoc_maps_district($districtIdx);
$upazilas = repdoc_maps_upazilas($district);
$stats    = repdoc_maps_stats($district);

/** Default to the first upazila that actually has files. */
$defaultUpazila = 0;
foreach ($upazilas as $i => $upazila) {
    if ((array) ($upazila['mouzas'] ?? []) !== []) {
        $defaultUpazila = $i;
        break;
    }
}

$requested   = isset($_GET['upazila']) ? (int) $_GET['upazila'] : $defaultUpazila;
$upazilaIdx  = isset($upazilas[$requested]) ? $requested : $defaultUpazila;
$upazila     = $upazilas[$upazilaIdx];
$query       = trim((string) ($_GET['q'] ?? ''));
$mouzas      = repdoc_maps_filter($upazila, $query);
$sheetTotal  = 0;
foreach ($mouzas as $mouza) {
    $sheetTotal += count(is_array($mouza['sheets'] ?? null) ? $mouza['sheets'] : []);
}
$updatedAt = (string) (repdoc_maps_manifest()['updated_at'] ?? '');

admin_page_start('Mouza Maps', $self);
?>
<style>
    .mp-head { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 14px; margin-bottom: 16px; }
    .mp-head h1 { font-size: 1.5rem; line-height: 1.25; }
    .mp-head p { margin-top: 6px; color: var(--text-muted); font-size: .86rem; }
    .mp-stats { display: flex; flex-wrap: wrap; gap: 8px; }
    .mp-stat { min-width: 92px; padding: 9px 13px; border: 1px solid var(--border-color); border-radius: 10px; background: var(--bg-surface); box-shadow: var(--shadow-sm); }
    .mp-stat strong { display: block; font-family: var(--font-mono); font-size: 1.06rem; line-height: 1.3; color: var(--text-primary); }
    .mp-stat span { display: block; margin-top: 1px; color: var(--text-muted); font-size: .68rem; font-weight: 600; }
    .mp-toolbar { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 10px; margin-bottom: 18px; padding: 12px; border: 1px solid var(--border-color); border-radius: 12px; background: var(--bg-surface); }
    .mp-field { display: flex; flex-direction: column; gap: 4px; min-width: 168px; }
    .mp-field label { color: var(--text-muted); font-size: .72rem; font-weight: 700; letter-spacing: .02em; }
    .mp-field select, .mp-field input { height: 38px; padding: 0 10px; border: 1px solid var(--border-strong); border-radius: 8px; background: #fff; color: var(--text-primary); font: inherit; font-size: .84rem; }
    .mp-field input { min-width: 220px; }
    .mp-actions { display: flex; gap: 8px; margin-inline-start: auto; }
    .mp-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(248px, 1fr)); gap: 12px; }
    .mp-card { display: flex; flex-direction: column; gap: 8px; padding: 13px 14px; border: 1px solid var(--border-color); border-radius: 12px; background: var(--bg-surface); box-shadow: var(--shadow-sm); }
    .mp-card-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; }
    .mp-name { min-width: 0; color: var(--text-primary); font-size: .95rem; font-weight: 700; line-height: 1.35; overflow-wrap: anywhere; }
    .mp-jl { flex: none; padding: 2px 7px; border: 1px solid var(--border-strong); border-radius: 6px; background: var(--bg-muted); color: var(--text-muted); font-family: var(--font-mono); font-size: .62rem; font-weight: 700; white-space: nowrap; }
    .mp-alias { color: var(--text-muted); font-size: .7rem; line-height: 1.4; overflow-wrap: anywhere; }
    .mp-sheets { display: flex; flex-wrap: wrap; gap: 6px; margin-top: auto; padding-top: 4px; }
    .mp-sheet { display: inline-flex; align-items: center; gap: 5px; padding: 6px 10px; border: 1px solid var(--primary); border-radius: 7px; background: var(--primary-light); color: var(--primary); font-size: .74rem; font-weight: 700; text-decoration: none; }
    .mp-sheet:hover { background: var(--primary); color: #fff; }
    .mp-sheet .tag { padding: 1px 4px; border-radius: 4px; background: #fff; color: var(--primary); font-family: var(--font-mono); font-size: .56rem; font-weight: 800; }
    .mp-sheet:hover .tag { background: rgba(255, 255, 255, .22); color: #fff; }
    .mp-empty { padding: 38px 22px; border: 2px dashed var(--border-strong); border-radius: 12px; background: var(--bg-surface); text-align: center; }
    .mp-empty h3 { font-size: 1rem; color: var(--text-primary); }
    .mp-empty p { max-width: 520px; margin: 8px auto 0; color: var(--text-muted); font-size: .84rem; line-height: 1.65; }
    .mp-foot { margin-top: 16px; color: var(--text-muted); font-size: .74rem; line-height: 1.6; }
    .mp-foot a { color: var(--primary); font-weight: 600; }
    @media (max-width: 640px) {
        .mp-field, .mp-field input { min-width: 0; width: 100%; }
        .mp-actions { margin-inline-start: 0; width: 100%; }
        .mp-actions .btn { flex: 1; }
    }
</style>

<div class="admin-page-head mp-head">
        <div>
            <h1 class="admin-title">ম্যাপ ডাউনলোড — সারা বাংলাদেশ</h1>
            <p class="admin-sub">
                <?= e((string) ($district['division_bn'] ?? '')) ?> বিভাগ → <?= e((string) ($district['bn'] ?? '')) ?> জেলা →
                থানা → মৌজা বেছে নিন, তারপর ম্যাপের শিট ডাউনলোড করুন। ফাইলগুলো Google Drive থেকেই আসে।
            </p>
        </div>
        <div class="mp-stats">
            <div class="mp-stat"><strong><?= e(repdoc_maps_bn_digits($stats['upazilas'])) ?></strong><span>থানা</span></div>
            <div class="mp-stat"><strong><?= e(repdoc_maps_bn_digits($stats['mouzas'])) ?></strong><span>মৌজা</span></div>
            <div class="mp-stat"><strong><?= e(repdoc_maps_bn_digits($stats['files'])) ?></strong><span>ম্যাপ ফাইল</span></div>
        </div>
    </div>

    <form class="mp-toolbar" method="get" action="<?= e($self) ?>">
        <div class="mp-field">
            <label for="div">বিভাগ</label>
            <select name="div" id="div" onchange="this.form.submit()">
                <?php foreach ($divisions as $i => $row): ?>
                    <option value="<?= (int) $i ?>"<?= $i === $divisionIdx ? ' selected' : '' ?>>
                        <?= e((string) $row['bn']) ?><?= $row['en'] !== '' ? ' — ' . e((string) $row['en']) : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mp-field">
            <label for="dist">জেলা</label>
            <select name="dist" id="dist" onchange="this.form.submit()">
                <?php foreach ($division['districts'] as $row): ?>
                    <option value="<?= (int) $row['idx'] ?>"<?= (int) $row['idx'] === $districtIdx ? ' selected' : '' ?>>
                        <?= e((string) $row['bn']) ?><?= $row['en'] !== '' ? ' — ' . e((string) $row['en']) : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mp-field">
            <label for="upazila">থানা (উপজেলা)</label>
            <select name="upazila" id="upazila">
                <?php foreach ($upazilas as $i => $row): ?>
                    <?php $count = count((array) ($row['mouzas'] ?? [])); ?>
                    <option value="<?= (int) $i ?>"<?= $i === $upazilaIdx ? ' selected' : '' ?>>
                        <?= e((string) ($row['bn'] ?? '')) ?><?= ($row['en'] ?? '') !== '' ? ' — ' . e((string) $row['en']) : '' ?>
                        <?= $count > 0 ? ' (' . e(repdoc_maps_bn_digits($count)) . ' মৌজা)' : ' (ফাইল নেই)' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mp-field">
            <label for="q">মৌজা খুঁজুন</label>
            <input type="search" name="q" id="q" value="<?= e($query) ?>" placeholder="মৌজার নাম বা JL নম্বর (যেমন: ১২, Atkoriya)">
        </div>
        <div class="mp-actions">
            <button type="submit" class="btn btn-primary">খুঁজুন</button>
            <?php if ($query !== ''): ?>
                <a class="btn btn-secondary" href="<?= e($self . '?upazila=' . $upazilaIdx) ?>">রিসেট</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if ($sheetTotal === 0): ?>
        <div class="mp-empty">
            <h3><?= $query !== '' ? 'এই নামে কোনো মৌজা পাওয়া যায়নি' : 'এই থানার ম্যাপ এখনো যোগ হয়নি' ?></h3>
            <p>
                <?php if ($query !== ''): ?>
                    অন্য বানানে বা JL নম্বর দিয়ে খুঁজে দেখুন। রিসেট করে পুরো তালিকা দেখাও যাবে।
                <?php else: ?>
                    Drive-এ <strong><?= e((string) ($upazila['bn'] ?? '')) ?></strong> থানার ফোল্ডার তৈরি, কিন্তু ভেতরে এখনো কোনো ম্যাপ ফাইল নেই।
                    ফাইল যোগ হলে ম্যানিফেস্ট আপডেট করলেই এখানে দেখা যাবে।
                <?php endif; ?>
            </p>
        </div>
    <?php else: ?>
        <p class="mp-alias" style="margin-bottom:10px">
            <?= e((string) ($upazila['bn'] ?? '')) ?> থানায় <?= e(repdoc_maps_bn_digits(count($mouzas))) ?>টি মৌজা,
            মোট <?= e(repdoc_maps_bn_digits($sheetTotal)) ?>টি ম্যাপ শিট<?= $query !== '' ? ' (খোঁজার ফলাফল)' : '' ?>।
        </p>
        <div class="mp-grid">
            <?php foreach ($mouzas as $mouza): ?>
                <?php
                $sheets  = is_array($mouza['sheets'] ?? null) ? $mouza['sheets'] : [];
                $aliases = array_values(array_filter((array) ($mouza['aliases'] ?? []), static fn($a): bool => trim((string) $a) !== ''));
                ?>
                <article class="mp-card">
                    <div class="mp-card-top">
                        <h2 class="mp-name"><?= e((string) ($mouza['name'] ?? '')) ?></h2>
                        <span class="mp-jl">JL <?= e(repdoc_maps_bn_digits((int) ($mouza['jl'] ?? 0))) ?></span>
                    </div>
                    <?php if ($aliases !== []): ?>
                        <p class="mp-alias"><?= e(implode(' · ', array_map('strval', $aliases))) ?></p>
                    <?php endif; ?>
                    <div class="mp-sheets">
                        <?php foreach ($sheets as $sheet): ?>
                            <?php if (!is_array($sheet) || empty($sheet['id'])) continue; ?>
                            <a class="mp-sheet"
                               href="<?= e(repdoc_maps_download_url((string) $sheet['id'])) ?>"
                               target="_blank" rel="noopener"
                               title="<?= e((string) ($sheet['file'] ?? '')) ?>">
                                শিট <?= e(repdoc_maps_bn_digits((int) ($sheet['sheet'] ?? 1))) ?>
                                <?php if (!empty($sheet['rs'])): ?><span class="tag">RS</span><?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <p class="mp-foot">
        উৎস: Google Drive মৌজা ম্যাপ সংগ্রহ<?= $updatedAt !== '' ? ' · হালনাগাদ ' . e($updatedAt) : '' ?> ·
        <a href="<?= e(repdoc_maps_folder_url((string) ($upazila['drive_folder'] ?? ''))) ?>" target="_blank" rel="noopener">ড্রাইভ ফোল্ডার খুলুন</a>।
        নতুন ফাইল যোগ হলে <code>maps/data/manifest.php</code> আপডেট করতে হবে। এই পেজ শুধু অ্যাডমিনের জন্য।
    </p>
<?php
admin_page_end();
