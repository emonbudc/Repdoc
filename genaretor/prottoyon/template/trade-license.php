<?php
$user = $user ?? null;
$certificate = $certificate ?? null;
$certType = $certType ?? null;
?>
<div class="cert-text">
  এই মর্মে প্রত্যয়ন করা যাইতেছে যে, <strong><?= e($user->name_bn ?? 'নাম') ?></strong>,
  পিতাঃ <?= e($user->father_name ?? 'পিতার নাম') ?>,
  মাতাঃ <?= e($user->mother_name ?? 'মাতার নাম') ?> —
  <strong>গ্রামঃ <?= e($user->village ?? 'গ্রামের নাম') ?>, ওয়ার্ড নংঃ <?= e($user->ward_no ?? '০১') ?>,
  উপজেলাঃ <?= e($user->upazila_name ?? 'উপজেলার নাম') ?>, জেলাঃ <?= e($user->district_name ?? 'জেলার নাম') ?></strong>
  ঠিকানায় বসবাস করেন এবং <strong><?= e($user->trade_name ?: 'ব্যবসার নাম') ?></strong> নামে ব্যবসা পরিচালনা করেন।
  ব্যবসার ঠিকানাঃ <?= e($user->trade_address ?: 'একই ঠিকানা') ?>।
  উক্ত ব্যবসা পরিচালনার জন্য তাঁর বিরুদ্ধে কোন অভিযোগ নাই।
  তাঁর জাতীয় পরিচয়পত্র (NID) নংঃ <strong><?= e(en2bnNumber($user->nid ?? '') ?: 'N/A') ?></strong>।
</div>

<p class="wish">— তাঁর ব্যবসা সাফল্যমণ্ডিত হোক। —</p>
