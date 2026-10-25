<?php
$user = $user ?? null;
$certificate = $certificate ?? null;
$certType = $certType ?? null;
?>
<div class="cert-text cert-text-block">
  এই মর্মে প্রত্যয়ন করা যাইতেছে যে, <strong><?= e($user->name_bn ?? 'নাম') ?></strong>,
  পিতাঃ <?= e($user->father_name ?? 'পিতার নাম') ?>,
  মাতাঃ <?= e($user->mother_name ?? 'মাতার নাম') ?> —
  পূর্বের ঠিকানাঃ <strong><?= e($user->previous_area ?: 'পূর্বের ঠিকানা') ?></strong>
  থেকে বর্তমান ঠিকানাঃ <strong><?= e($user->new_area ?: ($user->village ?? 'নতুন ঠিকানা')) ?>, ওয়ার্ড নংঃ <?= e($user->ward_no ?? '০১') ?></strong>
  এ স্থানান্তরিত হয়েছেন।
</div>

<div class="cert-text">
  উক্ত ভোটার এলাকা স্থানান্তর সঠিক ও যথাযথ প্রক্রিয়ায় সম্পন্ন হয়েছে।
  তাঁর জাতীয় পরিচয়পত্র (NID) নংঃ <strong><?= e(en2bnNumber($user->nid ?? '') ?: 'N/A') ?></strong>।
</div>

<p class="wish">— নতুন এলাকায় শুভ হোক। —</p>
