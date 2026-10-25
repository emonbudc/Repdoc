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
  উপজেলাঃ <?= e($user->upazila_name ?? 'উপজেলার নাম') ?>, জেলাঃ <?= e($user->district_name ?? 'জেলার নাম') ?>, বাংলাদেশ</strong>
  ঠিকানায় স্থায়ীভাবে বসবাস করেন।
  তিনি জন্মসূত্রে <strong>গণপ্রজাতন্ত্রী বাংলাদেশের একজন স্থায়ী নাগরিক</strong>।
  তাঁর জাতীয় পরিচয়পত্র (NID) নংঃ <strong><?= e(en2bnNumber($user->nid ?? '') ?: 'N/A') ?></strong>।
</div>

<div class="cert-text">
  উক্ত ব্যক্তি অত্র ইউনিয়নের একজন সুপরিচিত বাসিন্দা এবং তাঁর বিরুদ্ধে অত্র পরিষদের
  জ্ঞাতসারে কোন ফৌজদারি মামলা বা অভিযোগ নাই।
</div>

<p class="wish">— তাঁর ভবিষ্যৎ জীবন সুখময়, समৃদ্ধ ও উজ্জ্বল হোক। —</p>
