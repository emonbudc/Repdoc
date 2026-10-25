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
  উক্ত ব্যক্তি জন্মসূত্রে <strong>গণপ্রজাতন্ত্রী বাংলাদেশের নাগরিক</strong>।
  তিনি <strong>রোহিঙ্গা নন</strong> — এটি যথাযথ তদন্ত ও নথি পর্যালোচনার মাধ্যমে নিশ্চিত করা হয়েছে।
  তাঁর জাতীয় পরিচয়পত্র (NID) নংঃ <strong><?= e(en2bnNumber($user->nid ?? '') ?: 'N/A') ?></strong> এবং
  তাঁর পিতার নামঃ <?= e($user->father_name ?? 'পিতার নাম') ?>,
  মাতার নামঃ <?= e($user->mother_name ?? 'মাতার নাম') ?>।
</div>

<div class="cert-text">
  উক্ত প্রত্যয়ন যেকোন সরকারি কাজে ব্যবহার করা যেতে পারে।
</div>

<p class="wish">— দেশপ্রেমে উদ্বুদ্ধ হোন। —</p>
