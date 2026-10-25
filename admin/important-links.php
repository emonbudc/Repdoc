<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/layout.php';
$linksFile = dirname(__DIR__) . '/love/important_links.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) { flash_set('error','Session expired'); redirect('important-links.php'); }
    $titles = $_POST['title'] ?? [];
    $urls = $_POST['url'] ?? [];
    $descs = $_POST['desc'] ?? [];
    $out = [];
    foreach ($titles as $i => $t) {
        $t = trim((string)$t);
        $u = trim((string)($urls[$i] ?? ''));
        if ($t === '' || $u === '') continue;
        $out[] = ['title'=>$t,'url'=>$u,'desc'=>trim((string)($descs[$i] ?? ''))];
    }
    @file_put_contents($linksFile, json_encode($out, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    flash_set('success','Links saved.');
    redirect('important-links.php');
}

$links = [];
if (is_file($linksFile)) {
    $dec = json_decode((string)@file_get_contents($linksFile), true);
    if (is_array($dec)) $links = $dec;
}
admin_header('Important Links');
?>
<div class="admin-page-head"><h1 class="admin-title">Important Links</h1></div>
<form method="post">
<?= csrf_field() ?>
<div id="rows">
<?php foreach ($links as $l): ?>
<div style="display:grid;grid-template-columns:1fr 2fr 1fr;gap:8px;margin-bottom:8px">
<input name="title[]" value="<?= e($l['title'] ?? '') ?>" placeholder="Title">
<input name="url[]" value="<?= e($l['url'] ?? '') ?>" placeholder="URL">
<input name="desc[]" value="<?= e($l['desc'] ?? '') ?>" placeholder="Description">
</div>
<?php endforeach; ?>
</div>
<button type="button" onclick="addRow()">Add row</button>
<button type="submit" class="btn btn-primary">Save</button>
</form>
<script>
function addRow(){
  var d=document.getElementById('rows');
  var div=document.createElement('div');
  div.style='display:grid;grid-template-columns:1fr 2fr 1fr;gap:8px;margin-bottom:8px';
  div.innerHTML='<input name="title[]" placeholder="Title"><input name="url[]" placeholder="URL"><input name="desc[]" placeholder="Description">';
  d.appendChild(div);
}
</script>
<?php admin_footer(); ?>
PHP;
filePath">
/storage/emulated/0/htdocs/Repdoc/admin/important-links.php