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
  উক্ত ব্যক্তি <strong><?= e($user->deceased_name ? $user->deceased_name . '-এর' : 'মৃত ব্যক্তির') ?></strong> বৈধ ওয়ারিশ।
  উক্ত ওয়ারিশ সূত্রে তিনি সম্পত্তি ও দায়-দায়িত্ব গ্রহণে সক্ষম।
  তাঁর জাতীয় পরিচয়পত্র (NID) নংঃ <strong><?= e(en2bnNumber($user->nid ?? '') ?: 'N/A') ?></strong>।
</div>

<p class="wish">— তাঁর ভবিষ্যৎ জীবন সুখময়, সমৃদ্ধ ও উজ্জ্বল হোক। —</p>
