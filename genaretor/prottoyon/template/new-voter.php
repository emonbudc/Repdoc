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
  উক্ত ব্যক্তি বাংলাদেশের একজন <strong>নতুন ভোটার</strong> হিসেবে নিবন্ধনের জন্য যোগ্য।
  তাঁর জন্ম তারিখঃ <strong><?= e($user->dob ? formatDate($user->dob) : '[জন্ম তারিখ]') ?></strong> খ্রিস্টাব্দ।
  তিনি ভোটার তালিকায় অন্তর্ভুক্তির জন্য প্রয়োজনীয় সকল শর্ত পূরণ করেছেন।
</div>

<p class="wish">— তাঁর নাগরিক জীবন সুন্দর হোক। —</p>
