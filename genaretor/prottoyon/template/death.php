<?php
$user = $user ?? null;
$certificate = $certificate ?? null;
$certType = $certType ?? null;
$deceasedName = $user->deceased_name ?? $user->name_bn;
$deathDate = $user->death_date ?? '';
?>
<div class="cert-text cert-text-block">
  এই মর্মে প্রত্যয়ন করা যাইতেছে যে, <strong><?= e($deceasedName) ?></strong>,
  পিতাঃ <?= e($user->father_name ?? 'পিতার নাম') ?>,
  মাতাঃ <?= e($user->mother_name ?? 'মাতার নাম') ?> —
  <strong>গ্রামঃ <?= e($user->village ?? 'গ্রামের নাম') ?>, ওয়ার্ড নংঃ <?= e($user->ward_no ?? '০১') ?>,
  উপজেলাঃ <?= e($user->upazila_name ?? 'উপজেলার নাম') ?>, জেলাঃ <?= e($user->district_name ?? 'জেলার নাম') ?></strong>
  ঠিকানার বাসিন্দা ছিলেন।
</div>

<div class="cert-text">
  উক্ত ব্যক্তি <strong><?= e($deathDate ? formatDate($deathDate) : 'তারিখ') ?></strong> তারিখে মৃত্যুবরণ করেছেন।
  তাঁর জাতীয় পরিচয়পত্র (NID) নংঃ <strong><?= e(en2bnNumber($user->nid ?? '') ?: 'N/A') ?></strong>।
</div>

<div class="cert-text">
  এই মৃত্যু প্রত্যয়ন সনদ ইস্যু করা হয়েছে।
</div>

<p class="wish">— যমুনা স্মরণীয় হোক। —</p>