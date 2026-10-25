<?php
$user = $user ?? null;
$certificate = $certificate ?? null;
$certType = $certType ?? null;
?>
<div class="cert-text cert-text-block">
  এই মর্মে প্রত্যয়ন করা যাইতেছে যে, <strong><?= e($user->name_bn ?? 'নাম') ?></strong>,
  পিতাঃ <?= e($user->father_name ?? 'পিতার নাম') ?>,
  মাতাঃ <?= e($user->mother_name ?? 'মাতার নাম') ?> —
  <strong>গ্রামঃ <?= e($user->village ?? 'গ্রামের নাম') ?>, ওয়ার্ড নংঃ <?= e($user->ward_no ?? '০১') ?>,
  উপজেলাঃ <?= e($user->upazila_name ?? 'উপজেলার নাম') ?>, জেলাঃ <?= e($user->district_name ?? 'জেলার নাম') ?></strong>
  ঠিকানায় জন্মগ্রহণ করেছেন।
</div>

<div class="cert-text">
  উক্ত ব্যক্তির জন্ম তারিখঃ <strong><?= e($user->dob ? formatDate($user->dob) : 'তারিখ') ?></strong> খ্রিস্টাব্দ।
  <?php if (!empty($user->nid)): ?>
  জাতীয় পরিচয়পত্র (NID) নংঃ <strong><?= e(en2bnNumber($user->nid)) ?></strong>।
  <?php elseif (!empty($user->birth_reg_no)): ?>
  তাঁর জন্ম নিবন্ধন নংঃ <strong><?= e($user->birth_reg_no) ?></strong>।
  <?php endif; ?>
</div>

<div class="cert-text">
  তিনি <strong><?= e($user->gender ?? 'পুরুষ') ?></strong> এবং <strong><?= e($user->religion ?? 'ইসলাম') ?></strong> ধর্মাবলম্বী।
</div>

<p class="wish">— তাঁর ভবিষ্যৎ জীবন সুখময়, সমৃদ্ধ ও উজ্জ্বল হোক। —</p>
