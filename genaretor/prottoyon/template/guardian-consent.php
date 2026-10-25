<?php
$user = $user ?? null;
$certificate = $certificate ?? null;
$certType = $certType ?? null;
?>
<div class="cert-text cert-text-block">
  এই মর্মে প্রত্যয়ন করা যাইতেছে যে, <strong><?= e($user->guardian_name ?: 'অভিভাবকের নাম') ?></strong>,
  <strong><?= e($user->name_bn ?? 'নাম') ?></strong>-এর <?= e($user->guardian_relation ?: 'অভিভাবক') ?> —
  <strong>গ্রামঃ <?= e($user->village ?? 'গ্রামের নাম') ?>, ওয়ার্ড নংঃ <?= e($user->ward_no ?? '০১') ?>,
  উপজেলাঃ <?= e($user->upazila_name ?? 'উপজেলার নাম') ?>, জেলাঃ <?= e($user->district_name ?? 'জেলার নাম') ?></strong>
  ঠিকানায় স্থায়ীভাবে বসবাস করেন।
</div>

<div class="cert-text">
  উক্ত অভিভাবক <strong><?= e($user->guardian_name ?: 'অভিভাবক') ?></strong>
  <?= e($user->name_bn ?? 'নাম') ?>-এর বৈধ অভিভাবক হিসেবে সম্মতি প্রদান করেছেন।
</div>

<p class="wish">— পরিবারের ভবিষ্যৎ জীবন সুখময়, সমৃদ্ধ ও উজ্জ্বল হোক। —</p>
