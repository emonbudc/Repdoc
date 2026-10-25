<?php
declare(strict_types=1);

/**
 * Repdoc — admin: tool prices.
 *
 * Each tool costs this many credits per use. 0 = free (no ledger entry, nothing
 * deducted). Admins are never charged.
 */

require_once __DIR__ . '/includes/layout.php';

$db   = repdoc_db();
$me   = current_user();
$selfId = (int) $me['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Security token mismatch.');
        redirect('pricing.php');
    }

    $key    = (string) ($_POST['tool_key'] ?? '');
    $price  = (int) ($_POST['price'] ?? 0);
    $active = isset($_POST['active']);

    if ($key === '' || !array_key_exists($key, repdoc_default_tools())) {
        flash_set('error', 'Unknown tool.');
        redirect('pricing.php');
    }

    if (set_tool_price($key, $price, $active)) {
        flash_set('ok', 'Price updated: ' . $key);
    } else {
        flash_set('error', 'Could not save the price.');
    }
    redirect('pricing.php');
}

$tools = tool_prices();

admin_page_start('Tool Prices', 'pricing.php');
admin_notice();
?>

<div class="admin-page-head">
    <h1 class="admin-title">Tool Prices</h1>
    <p class="admin-sub">
        This many credits are deducted per tool use. 0 means the tool is free (no charge). Admins are never charged.
    </p>
</div>

<?php if (!$db): ?>
    <div class="alert alert-error" role="alert">Could not connect to the database, so prices cannot be saved.</div>
<?php else: ?>

<?php foreach ($tools as $key => $tool): ?>
    <form class="panel" method="post" action="pricing.php">
        <div class="panel-head">
            <?= e($tool['name']) ?>
            <span class="count"><?= e($key) ?></span>
        </div>
        <div class="panel-body">
            <?= csrf_field() ?>
            <input type="hidden" name="tool_key" value="<?= e($key) ?>">

            <div class="field-row">
                <div class="field">
                    <label for="price-<?= e($key) ?>">Credits per use</label>
                    <input type="number" id="price-<?= e($key) ?>" name="price" min="0" step="1"
                           value="<?= (int) $tool['price'] ?>">
                </div>
                <div class="field" style="flex:0 0 auto;align-self:flex-end">
                    <label style="display:flex;align-items:center;gap:8px;min-height:44px">
                        <input type="checkbox" name="active" value="1" style="width:auto;min-height:0"
                               <?= ((int) $tool['active']) === 1 ? 'checked' : '' ?>>
                        Active
                    </label>
                </div>
                <div class="field" style="flex:0 0 auto;align-self:flex-end">
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </div>
        </div>
    </form>
<?php endforeach; ?>

<?php endif; ?>
<?php admin_page_end(); ?>
