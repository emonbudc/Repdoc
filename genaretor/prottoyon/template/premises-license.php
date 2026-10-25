<?php
$user = $user ?? null;
$certificate = $certificate ?? null;
$certType = $certType ?? null;
?>
<div class="cert-text cert-text-block">
  এই মর্মে প্রত্যয়ন করা যাইতেছে যে, <strong><?= e($user->establishment_name ?: 'প্রতিষ্ঠানের নাম') ?></strong> নামক প্রতিষ্ঠানটি
  <strong><?= e($user->establishment_address ?: ($user->village ?? 'ঠিকানা')) ?></strong> এ অবস্থিত।
</div>

<div class="cert-text">
  প্রতিষ্ঠানের মালিক <strong><?= e($user->name_bn ?? 'নাম') ?></strong>,
  পিতাঃ <?= e($user->father_name ?? 'পিতার নাম') ?>।
  উক্ত প্রিমিসেস লাইসেন্স প্রদানের জন্য কোন বাধা নাই।
  তাঁর জাতীয় পরিচয়পত্র (NID) নংঃ <strong><?= e(en2bnNumber($user->nid ?? '') ?: 'N/A') ?></strong>।
</div>

<p class="wish">— প্রতিষ্ঠানের সাফল্য কামনা করছি। —</p>
