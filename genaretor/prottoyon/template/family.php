<?php
$user = $user ?? null;
$certificate = $certificate ?? null;
$certType = $certType ?? null;
$familyMembers = $family_members ?? [];
?>
<div class="cert-text cert-text-block">
  এই মর্মে প্রত্যয়ন করা যাইতেছে যে, <strong><?= e($user->name_bn ?? 'নাম') ?></strong>,
  পিতাঃ <?= e($user->father_name ?? 'পিতার নাম') ?>,
  মাতাঃ <?= e($user->mother_name ?? 'মাতার নাম') ?> —
  <strong>গ্রামঃ <?= e($user->village ?? 'গ্রামের নাম') ?>, ওয়ার্ড নংঃ <?= e($user->ward_no ?? '০১') ?>,
  উপজেলাঃ <?= e($user->upazila_name ?? 'উপজেলার নাম') ?>, জেলাঃ <?= e($user->district_name ?? 'জেলার নাম') ?></strong>
  ঠিকানায় পরিবারসহ স্থায়ীভাবে বসবাস করেন।
</div>

<div class="cert-text">
  উক্ত ব্যক্তি অত্র ইউনিয়নের একজন সুপরিচিত বাসিন্দা।
  তাঁর পরিবারের সদস্যগণ অত্র ইউনিয়নের স্থায়ী বাসিন্দা।
  তাঁর জাতীয় পরিচয়পত্র (NID) নংঃ <strong><?= e(en2bnNumber($user->nid ?? '') ?: 'N/A') ?></strong>।
</div>

<p class="wish">— তাঁর পরিবারের ভবিষ্যৎ জীবন সুখময়, সমৃদ্ধ ও উজ্জ্বল হোক। —</p>
