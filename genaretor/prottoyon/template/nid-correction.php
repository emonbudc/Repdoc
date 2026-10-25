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
  উক্ত ব্যক্তির জাতীয় পরিচয়পত্রের (NID) নিম্নলিখিত তথ্য সংশোধন করা হয়েছে —
</div>

<div class="cert-text">
  পূর্বের তথ্যঃ <strong><?= e($user->previous_info ?: 'পূর্বের তথ্য') ?></strong><br>
  সংশোধিত তথ্যঃ <strong><?= e($user->corrected_info ?: 'সংশোধিত তথ্য') ?></strong>
</div>

<div class="cert-text">
  সংশোধনটি সঠিক ও যথাযথ প্রক্রিয়ায় সম্পন্ন হয়েছে।
  সংশোধিত তথ্যের ভিত্তিতে জাতীয় পরিচয়পত্র (NID) নংঃ <strong><?= e(en2bnNumber($user->nid ?? '') ?: 'N/A') ?></strong> হালনাগাদের জন্য
  অনুরোধ করা হলো।
</div>

<p class="wish">— ধন্যবাদ। —</p>
