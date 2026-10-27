<?php
declare(strict_types=1);

/**
 * Repdoc — admin: all tools directory.
 *
 * Internal listing of every tool on the site (including ones hidden from the
 * public homepage) so admins can open and verify each entry point.
 * This page is admin-only via require_admin() in the shared layout.
 */

require_once __DIR__ . '/includes/layout.php';

/** Full internal registry: name, category, url, and on-disk entry to verify.
 *  Shared with the sidebar Tools dropdown (admin_tool_registry()). */
$allTools = admin_tool_registry();

/** A tool is 'live' only when its entry file really exists on disk. */
foreach ($allTools as &$t) {
    $t['live'] = is_file(__DIR__ . '/' . $t['check']);
}
unset($t);

$liveCount = count(array_filter($allTools, static fn(array $t): bool => $t['live']));

admin_page_start('Tools', 'tools.php');
admin_notice();
?>

<div class="admin-page-head">
    <h1 class="admin-title">Tools</h1>
    <p class="admin-sub">Internal listing of every tool on the site — admins only. Total <?= number_format(count($allTools)) ?>  <?= number_format($liveCount) ?> live.</p>
</div>

<div class="panel">
    <div class="panel-head">All tools <span class="count"><?= count($allTools) ?> </span></div>
    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr><th>Tool</th><th>Category</th><th>Status</th><th>Open</th></tr>
            </thead>
            <tbody>
            <?php foreach ($allTools as $t): ?>
                <tr>
                    <td><span class="cell-name"><?= e($t['name']) ?></span></td>
                    <td><?= e($t['cat']) ?></td>
                    <td>
                        <?php if ($t['live']): ?>
                            <span class="pill pill-approved">live</span>
                        <?php else: ?>
                            <span class="pill pill-user">folder</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($t['live']): ?>
                            <a class="btn btn-secondary btn-sm" href="<?= e($t['url']) ?>" target="_blank" rel="noopener" target="_blank" rel="noopener">Open</a>
                        <?php else: ?>
                            <span class="pill pill-rejected">entry missing</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="panel-body" style="padding-top:0">
        <p class="admin-sub" style="margin:0">
            ‘live’ means the entry file really exists on disk (verified); ‘folder’ means no entry file — the link is disabled.
            Only template-based tools are shown on the public homepage.
        </p>
    </div>
</div>

<?php admin_page_end(); ?>
