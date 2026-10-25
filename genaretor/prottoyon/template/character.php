<?php
$user = $user ?? null;
$certificate = $certificate ?? null;
$certType = $certType ?? null;
?>
<div class="cert-text">
  এই মর্মে প্রত্যয়ন করা যাইতেছে যে, <strong><?= e($user->name_bn ?? 'নাম') ?></strong>,
  <?php if (!empty($user->birth_reg_no) && empty($user->nid)): ?>
  জন্ম নিবন্ধন নংঃ <strong><?= e(en2bnNumber($user->birth_reg_no)) ?></strong>,
  <?php endif; ?>
  পিতাঃ <?= e($user->father_name ?? 'পিতার নাম') ?>,
  মাতাঃ <?= e($user->mother_name ?? 'মাতার নাম') ?> —
  <strong>গ্রামঃ <?= e($user->village ?? 'গ্রামের নাম') ?>, ওয়ার্ড নংঃ <?= e($user->ward_no ?? '০১') ?>,
  পোস্ট অফিসঃ <?= e($user->post_office ?? 'পোস্ট অফিস') ?>,
  উপজেলাঃ <?= e($user->upazila_name ?? 'উপজেলার নাম') ?>, জেলাঃ <?= e($user->district_name ?? 'জেলার নাম') ?>,
  বাংলাদেশ</strong>
  ঠিকানায় স্থায়ীভাবে বসবাস করেন।
  <?php if (!empty($user->nid)): ?>
  তাঁর জাতীয় পরিচয়পত্র (NID) নংঃ <strong><?= e(en2bnNumber($user->nid)) ?></strong>।
  <?php elseif (!empty($user->birth_reg_no)): ?>
  তাঁর জন্ম নিবন্ধন নংঃ <strong><?= e(en2bnNumber($user->birth_reg_no)) ?></strong>।
  <?php endif; ?>
  তিনি অত্র এলাকার একজন স্থায়ী বাসিন্দা এবং জন্মসূত্রে
  <strong>গণপ্রজাতন্ত্রী বাংলাদেশের একজন সৎ, নিরীহ ও স্থায়ী নাগরিক</strong>।
  তাঁর চরিত্র সম্পর্কে এলাকায় কোন বিরূপ মন্তব্য নাই এবং তাঁর বিরুদ্ধে অত্র পরিষদের
  জ্ঞাতসারে কোন ফৌজদারি মামলা বা অভিযোগ নাই।
</div>

<p class="wish">— আমি তাহার উজ্জ্বল ভবিষ্যৎ ও সর্বাঙ্গীণ মঙ্গল কামনা করিতেছি। —</p>
