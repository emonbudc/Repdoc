<?php
declare(strict_types=1);

/**
 * Repdoc — operator application.
 *
 * Public form for any computer operator with a business or job to claim an
 * operator account. Submissions land in the `entrepreneur_apps` table as
 * 'pending' for admin review (see admin/entrepreneur.php). Field names,
 * option values, and the geo API contract are unchanged.
 */

require_once dirname(__DIR__, 2) . '/src/bootstrap.php';

if (is_logged_in()) {
    redirect(repdoc_url('/'));
}

$error   = '';
$success = false;
$fullname = '';
$mobile   = '';
$email    = '';
$leader   = '';
$chairBn  = '';
$chairEn  = '';
$union    = '';
$upazila  = '';
$district = '';
$message  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim((string) ($_POST['fullname'] ?? ''));
    $mobile   = trim((string) ($_POST['mobile'] ?? ''));
    $email    = trim((string) ($_POST['email'] ?? ''));
    $leader   = trim((string) ($_POST['leader_title'] ?? ''));
    $chairBn  = trim((string) ($_POST['chairman_name_bn'] ?? ''));
    $chairEn  = trim((string) ($_POST['chairman_name_en'] ?? ''));
    $union    = trim((string) ($_POST['union_parishad'] ?? ''));
    $upazila  = trim((string) ($_POST['upazila'] ?? ''));
    $district = trim((string) ($_POST['district'] ?? ''));
    $message  = trim((string) ($_POST['message'] ?? ''));

    $db = repdoc_db();

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'নিরাপত্তা টোকেন মিলেনি — পেজটি রিফ্রেশ করে আবার চেষ্টা করুন।';
    } elseif ($fullname === '' || $mobile === '' || $union === '' || $upazila === '' || $district === '' || $chairBn === '') {
        $error = 'সব আবশ্যক ঘর পূরণ করুন।';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'ইমেইল ঠিকানাটি সঠিক নয়।';
    } elseif (!$db) {
        $error = 'ডেটাবেস পাওয়া যায়নি — একবার /src/setup.php চালান।';
    } else {
        try {
            $db->prepare("INSERT INTO entrepreneur_apps
                          (fullname, mobile, email, union_parishad, upazila, district, message, status, chairman_name_bn, chairman_name_en, leader_title, created_at)
                          VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?)")
               ->execute([$fullname, $mobile, $email, $union, $upazila, $district, $message, $chairBn, $chairEn, $leader, date('Y-m-d H:i:s')]);
            $success = true;
        } catch (Throwable $ex) {
            $error = 'আবেদন জমা হয়নি — আবার চেষ্টা করুন।';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>অপারেটর অ্যাকাউন্টের আবেদন — Repdoc</title>
    <meta name="robots" content="noindex">
    <link rel="icon" type="image/png" href="<?= e(repdoc_url('public/favicon.png')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Bengali:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/tokens.css?v=2')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/app.css?v=1')) ?>">
</head>
<body class="auth-body">
    <div style="max-width: 720px; margin: 0 auto; padding: var(--space-7) var(--gutter);">
        <a class="auth-brand" href="<?= e(repdoc_url('/')) ?>">
            <img src="<?= e(repdoc_url('src/assets/image/logo.png')) ?>" alt="" width="96" height="32">
            <span><strong>Repdoc</strong><small>repdoc.rf.gd</small></span>
        </a>

        <div class="field" style="margin: var(--space-4) 0; padding: var(--space-4); background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
            <p class="cta-eyebrow" style="color: var(--primary);"><span class="cta-eyebrow-mark" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#i-check"/></svg></span> সহজ · দ্রুত · নির্ভরযোগ্য</p>
            <h3 style="font-size: 1.3rem; font-weight: 700; margin: var(--space-3) 0 0;">শুরু করতে প্রস্তুত?</h3>
            <p style="color: var(--text-muted); font-size: .92rem; margin: var(--space-3) 0 0;">অপারেটর অ্যাকাউন্টের জন্য আবেদন করুন, লগ ইন করে যেকোনো টুল খুলুন।</p>
            <ul class="cta-perks" style="margin: var(--space-3) 0; padding: 0; list-style: none; display: flex; flex-wrap: wrap; gap: 8px;">
                <li><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-file"/></svg>রেডি টেমপ্লেট</li>
                <li><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-shield"/></svg>নিরাপদ লগইন</li>
                <li><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-mobile"/></svg>সব ডিভাইসে</li>
                <li><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-bolt"/></svg>দ্রুত ডাউনলোড</li>
            </ul>
            <div style="margin-top: var(--space-4); display: flex; gap: 8px;">
                <a class="btn btn-primary" href="<?= e(repdoc_url('Entrepreneur/apply/')) ?>" style="flex: 1;">অপারেটর হিসেবে আবেদন</a>
                <a class="btn btn-secondary" href="<?= e(repdoc_url('/public/login/')) ?>" style="flex: 1;">লগ ইন</a>
            </div>
        </div>

        <?php if ($success): ?>
            <h1>আবেদন পৌঁছেছে</h1>
            <div class="alert alert-success" role="status">
                আপনার অপারেটর আবেদন অ্যাডমিনের অনুমোদনের অপেক্ষায় আছে। যাচাই শেষে মোবাইলে জানানো হবে।
            </div>
            <div class="form-actions">
                <a class="btn btn-primary" href="<?= e(repdoc_url('/')) ?>">হোমপেজে ফিরুন</a>
            </div>
        <?php else: ?>
            <h1>অপারেটর অ্যাকাউন্টের আবেদন</h1>
            <p class="auth-sub">যেকোনো কম্পিউটার অপারেটর (ব্যবসা বা চাকরি) আবেদন করতে পারেন। অ্যাকাউন্ট চালুর আগে অ্যাডমিন অনুমোদন লাগবে।</p>

            <?php if ($error !== ''): ?>
                <div class="alert alert-error" role="alert"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" novalidate>
                <?= csrf_field() ?>

                <div class="field">
                    <label for="fullname">পুরো নাম *</label>
                    <input type="text" id="fullname" name="fullname" value="<?= e($fullname) ?>"
                           autocomplete="name" required autofocus>
                </div>

                <div class="field">
                    <label for="mobile">মোবাইল *</label>
                    <input type="tel" id="mobile" name="mobile" value="<?= e($mobile) ?>"
                           autocomplete="tel" required>
                </div>

                <div class="field">
                    <label for="email">ইমেইল <span style="font-weight:400;color:var(--text-muted)">(ঐচ্ছিক)</span></label>
                    <input type="email" id="email" name="email" value="<?= e($email) ?>"
                           autocomplete="email">
                </div>

                <div class="field">
                    <label for="leader_title">জনপ্রতিনিধি / প্রশাসকের পদ *</label>
                    <select id="leader_title" name="leader_title" required>
                        <option value="">পদ বেছে নিন</option>
                        <?php foreach (['chairman' => 'চেয়ারম্যান', 'panel' => 'প্যানেল চেয়ারম্যান', 'mayor' => 'মেয়র', 'admin' => 'প্রশাসক'] as $v => $l): ?>
                            <option value="<?= e($v) ?>"<?= $leader === $v ? ' selected' : '' ?>><?= e($l) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field-row">
                    <div class="field">
                        <label for="chairman_name_bn">চেয়ারম্যানের নাম (বাংলা) *</label>
                        <input type="text" id="chairman_name_bn" name="chairman_name_bn" value="<?= e($chairBn) ?>" placeholder="চেয়ারম্যানের নাম" required>
                    </div>
                    <div class="field">
                        <label for="chairman_name_en">চেয়ারম্যানের নাম (ইংরেজি)</label>
                        <input type="text" id="chairman_name_en" name="chairman_name_en" value="<?= e($chairEn) ?>" placeholder="Name in English">
                    </div>
                </div>

                <div class="field">
                    <label for="division">বিভাগ *</label>
                    <select id="division" name="division" required>
                        <option value="">বিভাগ বেছে নিন</option>
                    </select>
                </div>

                <div class="field-row">
                    <div class="field">
                        <label for="district">জেলা *</label>
                        <select id="district" name="district" required>
                            <option value="">আগে বিভাগ বেছে নিন</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="upazila">উপজেলা *</label>
                        <select id="upazila" name="upazila" required>
                            <option value="">আগে জেলা বেছে নিন</option>
                        </select>
                    </div>
                </div>

                <div class="field">
                    <label for="area_type">এলাকার ধরন *</label>
                    <select id="area_type" required>
                        <option value="union">ইউনিয়ন পরিষদ</option>
                        <option value="municipality">পৌরসভা</option>
                        <option value="city">সিটি কর্পোরেশন</option>
                    </select>
                </div>

                <div class="field" id="unionWrap">
                    <label for="union_parishad">ইউনিয়ন পরিষদ *</label>
                    <select id="union_parishad" name="union_parishad" required>
                        <option value="">আগে উপজেলা বেছে নিন</option>
                    </select>
                </div>

                <div class="field" id="pouroWrap" hidden>
                    <label for="pouro_name">পৌরসভা *</label>
                    <select id="pouro_name" name="union_parishad" disabled>
                        <option value="">আগে জেলা বেছে নিন</option>
                    </select>
                </div>

                <div class="field" id="cityWrap" hidden>
                    <label for="city_name">সিটি কর্পোরেশন *</label>
                    <select id="city_name" name="union_parishad" disabled>
                        <option value="">আগে জেলা বেছে নিন</option>
                    </select>
                </div>

                <div class="field">
                    <label for="message">বার্তা <span style="font-weight:400;color:var(--text-muted)">(ঐচ্ছিক)</span></label>
                    <textarea id="message" name="message" rows="3"><?= e($message) ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">আবেদন জমা দিন</button>
                    <a class="btn btn-secondary" href="<?= e(repdoc_url('/')) ?>">হোমপেজ</a>
                </div>
            </form>
            <script>
            (function () {
                var GEO = <?= json_encode(repdoc_url('genaretor/prottoyon/api/geo.php')) ?>;
                var prev = {
                    district: <?= json_encode($district) ?>,
                    upazila: <?= json_encode($upazila) ?>,
                    union: <?= json_encode($union) ?>
                };
                var divSel = document.getElementById('division');
                var disSel = document.getElementById('district');
                var upaSel = document.getElementById('upazila');
                var uniSel = document.getElementById('union_parishad');
                if (!divSel || !disSel || !upaSel || !uniSel) return;
                function fill(sel, rows, placeholder) {
                    sel.innerHTML = '';
                    var opt = document.createElement('option');
                    opt.value = '';
                    opt.textContent = placeholder;
                    sel.appendChild(opt);
                    rows.forEach(function (r) {
                        var o = document.createElement('option');
                        o.value = r.name_bn;
                        o.textContent = r.name_bn + ' (' + r.name_en + ')';
                        o.setAttribute('data-id', r.id);
                        sel.appendChild(o);
                    });
                }
                function selId(sel) {
                    var o = sel.options[sel.selectedIndex];
                    return o ? parseInt(o.getAttribute('data-id') || '0', 10) : 0;
                }
                function pick(sel, name) {
                    if (!name) return false;
                    var found = false;
                    Array.prototype.forEach.call(sel.options, function (o) {
                        if (o.value === name) { sel.value = name; found = true; }
                    });
                    return found;
                }
                function get(action, params) {
                    var q = 'action=' + encodeURIComponent(action);
                    Object.keys(params).forEach(function (k) { q += '&' + k + '=' + encodeURIComponent(params[k]); });
                    return fetch(GEO + '?' + q).then(function (r) { return r.json(); }).catch(function () { return []; });
                }
                function loadDistricts(divId, keep) {
                    fill(disSel, [], 'লোড হচ্ছে…');
                    fill(upaSel, [], 'আগে জেলা বেছে নিন');
                    fill(uniSel, [], 'আগে উপজেলা বেছে নিন');
                    if (!divId) { fill(disSel, [], 'আগে বিভাগ বেছে নিন'); return; }
                    get('districts', { division_id: divId }).then(function (rows) {
                        fill(disSel, rows, 'জেলা বেছে নিন');
                        if (keep && pick(disSel, keep)) loadUpazilas(selId(disSel), prev.upazila, prev.union);
                    });
                }
                function loadUpazilas(disId, keepU, keepN) {
                    fill(upaSel, [], 'লোড হচ্ছে…');
                    fill(uniSel, [], 'আগে উপজেলা বেছে নিন');
                    if (!disId) { fill(upaSel, [], 'আগে জেলা বেছে নিন'); return; }
                    get('upazilas', { district_id: disId }).then(function (rows) {
                        fill(upaSel, rows, 'উপজেলা বেছে নিন');
                        if (keepU && pick(upaSel, keepU)) loadUnions(selId(upaSel), keepN);
                    });
                }
                function loadUnions(upaId, keep) {
                    fill(uniSel, [], 'লোড হচ্ছে…');
                    if (!upaId) { fill(uniSel, [], 'আগে উপজেলা বেছে নিন'); return; }
                    get('unions', { upazila_id: upaId }).then(function (rows) {
                        fill(uniSel, rows, 'ইউনিয়ন বেছে নিন');
                        if (keep) pick(uniSel, keep);
                    });
                }
                function loadMunicipalities(disId, keep) {
                    var p = document.getElementById('pouro_name');
                    if (!p) return;
                    fill(p, [], 'লোড হচ্ছে…');
                    if (!disId) { fill(p, [], 'আগে জেলা বেছে নিন'); return; }
                    get('municipalities', { district_id: disId }).then(function (rows) {
                        fill(p, rows, 'পৌরসভা বেছে নিন');
                        if (keep) pick(p, keep);
                    });
                }
                function loadCities(disId) {
                    var c = document.getElementById('city_name');
                    if (!c) return;
                    fill(c, [], 'লোড হচ্ছে…');
                    if (!disId) { fill(c, [], 'আগে জেলা বেছে নিন'); return; }
                    get('city-corporations', { district_id: disId }).then(function (rows) {
                        fill(c, rows.length ? rows : [], rows.length ? 'সিটি কর্পোরেশন বেছে নিন' : 'এখানে সিটি কর্পোরেশন নেই');
                    });
                }
                divSel.addEventListener('change', function () { loadDistricts(selId(divSel), ''); });
                disSel.addEventListener('change', function () { var id = selId(disSel); loadUpazilas(id, '', ''); loadMunicipalities(id, ''); loadCities(id); });
                upaSel.addEventListener('change', function () { loadUnions(selId(upaSel), ''); });
                /* Union / Pouroshava / City toggle */
                var unionWrap = document.getElementById('unionWrap');
                var pouroWrap = document.getElementById('pouroWrap');
                var cityWrap = document.getElementById('cityWrap');
                var pouroInput = document.getElementById('pouro_name');
                var cityInput = document.getElementById('city_name');
                function areaMode() {
                    var s = document.getElementById('area_type');
                    return s ? s.value : 'union';
                }
                function applyAreaMode() {
                    var m = areaMode();
                    if (unionWrap) unionWrap.hidden = (m !== 'union');
                    if (pouroWrap) pouroWrap.hidden = (m !== 'municipality');
                    if (cityWrap) cityWrap.hidden = (m !== 'city');
                    if (uniSel) { uniSel.disabled = (m !== 'union'); uniSel.required = (m === 'union'); }
                    if (pouroInput) { pouroInput.disabled = (m !== 'municipality'); pouroInput.required = (m === 'municipality'); }
                    if (cityInput) { cityInput.disabled = (m !== 'city'); cityInput.required = (m === 'city'); }
                }
                var areaTypeSel = document.getElementById('area_type');
                if (areaTypeSel) areaTypeSel.addEventListener('change', applyAreaMode);
                applyAreaMode();
                get('divisions', {}).then(function (rows) {
                    fill(divSel, rows, 'বিভাগ বেছে নিন');
                    if (prev.district) {
                        get('locate', { district: prev.district, upazila: prev.upazila }).then(function (loc) {
                            if (!loc || !loc.division_id) return;
                            get('divisions', {}).then(function () {
                                Array.prototype.forEach.call(divSel.options, function (o) {
                                    if (o.getAttribute('data-id') === String(loc.division_id)) divSel.value = o.value;
                                });
                                if (divSel.value) loadDistricts(loc.division_id, prev.district);
                            });
                        });
                    }
                });
            })();
            </script>
        <?php endif; ?>
    </div>
    <p class="auth-sub" style="text-align:center">Independent tools site — not a government portal.</p>
</body>
</html>
