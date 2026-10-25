<?php
$user = $user ?? null;
$certificate = $certificate ?? null;
$certType = $certType ?? null;
?>
<div class="cert-text cert-text-block">
  এই মর্মে প্রত্যয়ন করা যাইতেছে যে, <strong><?= e($user->principal_name ?: ($user->name_bn ?? 'নাম')) ?></strong>,
  পিতাঃ <?= e($user->father_name ?? 'পিতার নাম') ?>,
  মাতাঃ <?= e($user->mother_name ?? 'মাতার নাম') ?> —
  <strong>গ্রামঃ <?= e($user->village ?? 'গ্রামের নাম') ?>, ওয়ার্ড নংঃ <?= e($user->ward_no ?? '০১') ?>
  ঠিকানায় স্থায়ীভাবে বসবাস করেন।
</div>

<div class="cert-text">
  উক্ত ব্যক্তি <strong><?= e($user->attorney_name ?: 'প্রতিনিধির নাম') ?></strong>-কে
  <?= e($user->powers ?: 'আইনগত ক্ষমতা') ?>-এর জন্য <strong>ক্ষমতা অর্পণ</strong> করেছেন।
  এই অর্পণ স্বেচ্ছায় ও সঠিক প্রক্রিয়ায় সম্পন্ন হয়েছে।
  তাঁর জাতীয় পরিচয়পত্র (NID) নংঃ <strong><?= e(en2bnNumber($user->nid ?? '') ?: 'N/A') ?></strong>।
</div>

<p class="wish">— সবার মঙ্গল হোক। —</p>
