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
  ঠিকানায় স্থায়ীভাবে বসবাস করেন।
</div>

<div class="cert-text">
  উক্ত ব্যক্তি <strong>এতিম</strong> — তাঁর পিতা-মাতা জীবিত নাই।
  তিনি সরকারি ও বেসরকারি সহায়তা ও সুরক্ষা প্রাপ্তির জন্য যোগ্য।
  <?php if (!empty($user->nid)): ?>
  তাঁর জাতীয় পরিচয়পত্র (NID) নংঃ <strong><?= e(en2bnNumber($user->nid)) ?></strong>।
  <?php elseif (!empty($user->birth_reg_no)): ?>
  তাঁর জন্ম নিবন্ধন নংঃ <strong><?= e($user->birth_reg_no) ?></strong>।
  <?php else: ?>
  তাঁর জন্ম নিবন্ধন নংঃ <strong>N/A</strong>।
  <?php endif; ?>
</div>

<p class="wish">— তাঁর ভবিষ্যৎ জীবন সুন্দর হোক। —</p>
