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
  উক্ত ব্যক্তি <strong><?= e($user->occupation ?? 'পেশাজীবী') ?></strong> পেশায় নিয়োজিত।
  তিনি এই পেশায় <strong>দক্ষ ও প্রশিক্ষিত</strong> বলে জানা যায়।
  তাঁর জাতীয় পরিচয়পত্র (NID) নংঃ <strong><?= e(en2bnNumber($user->nid ?? '') ?: 'N/A') ?></strong>।
</div>

<div class="cert-text">
  উক্ত পেশাগত দক্ষতা যাচাই করা হয়েছে এবং তা সত্য বলে প্রত্যয়ন করা যাইতেছে।
</div>

<p class="wish">— তাঁর পেশাগত জীবন সুখময়, সমৃদ্ধ ও উজ্জ্বল হোক। —</p>
