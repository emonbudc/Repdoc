<?php
$user = $user ?? null;
$certificate = $certificate ?? null;
$certType = $certType ?? null;
?>
<div class="cert-text cert-text-block">
  এই মর্মে প্রত্যয়ন করা যাইতেছে যে, <strong><?= e($user->name_bn ?? 'নাম') ?></strong>,
  পিতাঃ <?= e($user->father_name ?? 'পিতার নাম') ?>,
  মাতাঃ <?= e($user->mother_name ?? 'মাতার নাম') ?> —
  <strong>গ্রামঃ <?= e($user->village ?? 'গ্রামের নাম') ?>, ওয়ার্ড নংঃ <?= e($user->ward_no ?? '০১') ?></strong>
  ঠিকানায় স্থায়ীভাবে বসবাস করেন।
</div>

<div class="cert-text">
  তিনি জন্মসূত্রে <strong>গণপ্রজাতন্ত্রী বাংলাদেশের একজন সৎ, নিরীহ ও স্থায়ী নাগরিক</strong>।
  তাঁর চরিত্র সম্পর্কে এলাকায় কোন বিরূপ মন্তব্য নাই এবং তাঁর বিরুদ্ধে অত্র পরিষদের
  জ্ঞাতসারে কোন ফৌজদারি মামলা বা অভিযোগ নাই।
</div>

<p class="wish">— তাঁর ভবিষ্যৎ জীবন সুখময়, সমৃদ্ধ ও উজ্জ্বল হোক। —</p>