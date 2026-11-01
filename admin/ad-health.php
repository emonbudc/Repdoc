<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$db = repdoc_db();
$globalEnabled = repdoc_ads_enabled();
$detectedDevice = repdoc_detect_device();
$slots = [
    'home_top_banner' => 'Desktop/top banner',
    'home_middle_banner' => 'Content banner',
    'sidebar_right' => 'Sidebar banner',
    'home_rail_tall' => 'Tall sidebar banner',
    'home_rail_medium' => 'Medium sidebar banner',
    'home_mobile_banner' => 'Mobile 320×50 banner',
    'home_native_banner' => 'Native banner',
    'sitewide_social_bar' => 'Social Bar',
];
$reports = [];
$probeTransaction = false;

if ($db) {
    try {
        $db->beginTransaction();
        $probeTransaction = $db->inTransaction();
    } catch (Throwable $e) {}

    $st = $db->prepare('SELECT a.name, a.status, a.is_active, a.device_target, a.start_date, a.end_date,
                               aa.page_slug, aa.device_type, aa.is_enabled
                        FROM ad_positions p
                        INNER JOIN ad_assignments aa ON aa.position_id = p.id
                        INNER JOIN ads a ON a.id = aa.ad_id
                        WHERE p.slug = ?
                        ORDER BY a.priority DESC, a.id DESC');

    foreach ($slots as $slug => $label) {
        $device = $slug === 'home_mobile_banner' ? 'mobile' : $detectedDevice;
        $eligible = [];
        $candidates = [];
        $rendererBytes = 0;
        try {
            $st->execute([$slug]);
            $candidates = $st->fetchAll();
            $rendererBytes = strlen(repdoc_render_ad_slot($slug, 'home', $device));
            $now = time();
            foreach ($candidates as $candidate) {
                $start = trim((string) ($candidate['start_date'] ?? ''));
                $end = trim((string) ($candidate['end_date'] ?? ''));
                $pageMatches = in_array((string) ($candidate['page_slug'] ?? 'all'), ['all', 'home'], true);
                $deviceMatches = in_array((string) ($candidate['device_target'] ?? 'all'), ['all', '', $device], true);
                $startMatches = $start === '' || (strtotime($start) !== false && strtotime($start) <= $now);
                $endMatches = $end === '' || (strtotime($end) !== false && strtotime($end) >= $now);
                if ($globalEnabled
                    && (int) ($candidate['is_enabled'] ?? 0) === 1
                    && (int) ($candidate['is_active'] ?? 0) === 1
                    && (string) ($candidate['status'] ?? '') === 'active'
                    && $pageMatches
                    && $deviceMatches
                    && $startMatches
                    && $endMatches) {
                    $eligible[] = (string) $candidate['name'];
                }
            }
        } catch (Throwable $e) {
            $candidates = [];
        }
        $reports[] = [
            'slug' => $slug,
            'label' => $label,
            'device' => $device,
            'candidate_count' => count($candidates),
            'eligible' => $eligible,
            'renderer_bytes' => $rendererBytes,
            'candidates' => $candidates,
        ];
    }

    if ($probeTransaction && $db->inTransaction()) {
        try { $db->rollBack(); } catch (Throwable $e) {}
    }
}

admin_page_start('Ad Health', 'ad-health.php');
admin_notice();
?>

<div class="admin-page-head">
    <div>
        <p class="kicker">Monetization</p>
        <h1 class="admin-title">Ad Health</h1>
        <p class="admin-sub">Check whether homepage placements pass the live renderer filters.</p>
    </div>
</div>

<?php if (!$db): ?>
    <div class="alert alert-error" role="alert">Database connection unavailable.</div>
<?php else: ?>
    <div class="panel">
        <div class="panel-head">Homepage eligibility</div>
        <div class="panel-body">
            <p>Global ads: <strong><?= $globalEnabled ? 'Enabled' : 'Disabled' ?></strong> · Detected device: <strong><?= e($detectedDevice) ?></strong></p>
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>Placement</th>
                            <th>Test device</th>
                            <th>Assignments</th>
                            <th>Eligible ads</th>
                            <th>Renderer bytes</th>
                            <th>Filter details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $report): ?>
                            <tr>
                                <td><strong><?= e($report['label']) ?></strong><br><small><?= e($report['slug']) ?></small></td>
                                <td><?= e($report['device']) ?></td>
                                <td><?= (int) $report['candidate_count'] ?></td>
                                <td><?= count($report['eligible']) ?></td>
                                <td><?= (int) $report['renderer_bytes'] ?></td>
                                <td>
                                    <?php if ($report['eligible']): ?>
                                        <?= e(implode(', ', $report['eligible'])) ?>
                                    <?php elseif ($report['candidates']): ?>
                                        <?php foreach ($report['candidates'] as $candidate): ?>
                                            <?= e((string) $candidate['name']) ?>:
                                            status=<?= e((string) ($candidate['status'] ?? '')) ?>,
                                            ad=<?= (int) ($candidate['is_active'] ?? 0) ?>,
                                            assignment=<?= (int) ($candidate['is_enabled'] ?? 0) ?>,
                                            page=<?= e((string) ($candidate['page_slug'] ?? '')) ?>,
                                            target=<?= e((string) ($candidate['device_target'] ?? '')) ?>,
                                            dates=<?= e((string) ($candidate['start_date'] ?? '')) ?>…<?= e((string) ($candidate['end_date'] ?? '')) ?><br>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        No assignment found for this position.
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php admin_page_end(); ?>
