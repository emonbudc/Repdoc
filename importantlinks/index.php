<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/bootstrap.php';
require_once dirname(__DIR__) . '/src/seo.php';

$SEO_TITLE = 'গুরুত্বপূর্ণ লিংক — Repdoc';
$CANON = repdoc_canonical('importantlinks/');
$HOME = repdoc_url('/');

$linksFile = dirname(__DIR__) . '/love/important_links.json';
$links = [];
if (is_file($linksFile)) {
    $raw = @file_get_contents($linksFile);
    $dec = json_decode((string)$raw, true);
    if (is_array($dec)) $links = $dec;
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($SEO_TITLE) ?></title>
</head>
<body>
<main style="max-width:900px;margin:40px auto;padding:0 20px;font-family:sans-serif">
<h1>গুরুত্বপূর্ণ লিংক</h1>
<p>দরকারি ওয়েবসাইট ও সেবার লিংক এক জায়গায়।</p>
<ul>
<?php foreach ($links as $l): ?>
  <li><a href="<?= e($l['url'] ?? '#') ?>" target="_blank" rel="noopener"><?= e($l['title'] ?? '') ?></a>
  <?php if (!empty($l['desc'])): ?><small> — <?= e($l['desc']) ?></small><?php endif; ?>
  </li>
<?php endforeach; ?>
<?php if ($links === []): ?>
  <li>কোনো লিংক যুক্ত হয়নি।</li>
<?php endif; ?>
</ul>
<p><a href="<?= e($HOME) ?>">হোমে ফিরুন</a></p>
</main>
</body>
</html>
PHP;
filePath">
/storage/emulated/0/htdocs/Repdoc/importantlinks/index.php