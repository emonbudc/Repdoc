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
  পোস্ট অফিসঃ <?= e($user->post_office ?? 'পোস্ট অফিস') ?>,
  উপজেলাঃ <?= e($user->upazila_name ?? 'উপজেলার নাম') ?>, জেলাঃ <?= e($user->district_name ?? 'জেলার নাম') ?>, বাংলাদেশ</strong>
  ঠিকানায় <strong>স্থায়ীভাবে বসবাস</strong> করেন।
</div>

<div class="cert-text">
  তিনি <?= e($user->residence_duration ? 'গত ' . $user->residence_duration : 'দীর্ঘদিন') ?> ধরে এখানে বসবাস করছেন।
  তিনি স্থানীয় এলাকার একজন সুপরিচিত ও সৎ ব্যক্তি।
  তাঁর জাতীয় পরিচয়পত্র (NID) নংঃ <strong><?= e(en2bnNumber($user->nid ?? '') ?: 'N/A') ?></strong>।
  তাঁর বিরুদ্ধে অত্র পরিষদের জ্ঞাতসারে কোন ফৌজদারি মামলা নাই।
</div>

<div class="cert-text">
  যে কোন সরকারি, আধা-সরকারি বা বেসরকারি কাজে তাঁকে সহযোগিতা করার জন্য
  সংশ্লিষ্ট সকলকে বিনীতভাবে অনুরোধ করা হচ্ছে।
</div>

<p class="wish">— তাঁর ভবিষ্যৎ জীবন সুখময়, সমৃদ্ধ ও উজ্জ্বল হোক। —</p>
