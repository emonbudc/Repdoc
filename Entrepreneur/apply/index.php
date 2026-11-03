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
$identityType = '';
$orgName      = '';
$applicantRole = '';
$idAddress    = '';

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

    $identity = entrepreneur_identity_clean($_POST);
    $identityType  = $identity['identity_type'];
    $orgName       = $identity['org_name'];
    $applicantRole = $identity['applicant_role'];
    $idAddress     = $identity['id_address'];

    $docFile = $_FILES['id_doc'] ?? null;
    $docMissing = !is_array($docFile) || ($docFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE;

    $db = repdoc_db();

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'নিরাপত্তা টোকেন মিলেনি — পেজটি রিফ্রেশ করে আবার চেষ্টা করুন।';
    } elseif ($fullname === '' || $mobile === '' || $union === '' || $upazila === '' || $district === '' || $chairBn === '') {
        $error = 'সব আবশ্যক ঘর পূরণ করুন।';
    } elseif (($identityError = entrepreneur_identity_error($identity)) !== '') {
        $error = $identityError;
    } elseif ($docMissing) {
        $error = $identityType === 'office_staff' ? 'অফিস আইডি কার্ডের ছবি দিন।' : 'ট্রেড লাইসেন্সের ছবি দিন।';
    } elseif (repdoc_validate_image_upload($docFile) === '') {
        $error = 'ছবিটি JPG বা PNG হতে হবে (সর্বোচ্চ ২ MB)।';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'ইমেইল ঠিকানাটি সঠিক নয়।';
    } elseif (!$db) {
        $error = 'ডেটাবেস পাওয়া যায়নি — একবার /src/setup.php চালান।';
    } else {
        $stored = ['path' => ''];
        try {
            $db->beginTransaction();
            $db->prepare("INSERT INTO entrepreneur_apps
                          (fullname, mobile, email, union_parishad, upazila, district, message, status, chairman_name_bn, chairman_name_en, leader_title, identity_type, org_name, applicant_role, id_address, created_at)
                          VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?)")
               ->execute([$fullname, $mobile, $email, $union, $upazila, $district, $message, $chairBn, $chairEn, $leader, $identityType, $orgName, $applicantRole, $idAddress, date('Y-m-d H:i:s')]);
            $newAppId = (int) $db->lastInsertId();
            $stored = entrepreneur_store_identity_doc($docFile, $newAppId);
            if (!$stored['ok']) {
                throw new RuntimeException((string) $stored['message']);
            }
            $db->prepare('UPDATE entrepreneur_apps SET id_doc_path = ? WHERE id = ?')->execute([$stored['path'], $newAppId]);
            $db->commit();
            $success = true;
        } catch (Throwable $ex) {
            if ($db->inTransaction()) $db->rollBack();
            if (($stored['path'] ?? '') !== '') entrepreneur_remove_identity_doc((string) $stored['path']);
            $error = $ex instanceof RuntimeException ? $ex->getMessage() : 'আবেদন জমা হয়নি — আবার চেষ্টা করুন।';
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
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/apply.css?v=1')) ?>">
</head>
<body class="apply-body">
    <svg class="sprite" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <symbol id="i-check" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></symbol>
            <symbol id="i-shield" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11.2 14.2 15.5 9.9"/></symbol>
            <symbol id="i-file" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></symbol>
            <symbol id="i-mobile" viewBox="0 0 24 24"><rect x="7" y="2" width="10" height="20" rx="2"/><line x1="11" y1="18" x2="13" y2="18"/></symbol>
            <symbol id="i-bolt" viewBox="0 0 24 24"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></symbol>
            <symbol id="i-id" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><circle cx="8.5" cy="10" r="2"/><path d="M5 16.5a3.6 3.6 0 0 1 7 0"/><line x1="14.5" y1="9.5" x2="19" y2="9.5"/><line x1="14.5" y1="13.5" x2="19" y2="13.5"/></symbol>
            <symbol id="i-back" viewBox="0 0 24 24"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></symbol>
            <symbol id="i-login" viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></symbol>
            <symbol id="i-arrow" viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></symbol>
            <symbol id="i-home" viewBox="0 0 24 24"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/></symbol>
        </defs>
    </svg>

    <div class="apply-shell">
        <aside class="apply-rail" aria-label="আবেদনের ধাপ">
            <a class="apply-brand" href="<?= e(repdoc_url('/')) ?>" aria-label="Repdoc — হোমপেজ">
                <img src="<?= e(repdoc_url('src/assets/image/logo.png')) ?>" alt="Repdoc" width="96" height="28">
            </a>

            <p class="rail-label">Operator access</p>
            <ul class="rail-nav">
                <li class="is-current">
                    <a class="rail-link" href="#identity"><span class="num">01</span> পরিচয়</a>
                </li>
                <li>
                    <a class="rail-link" href="#contact"><span class="num">02</span> যোগাযোগ</a>
                </li>
                <li>
                    <a class="rail-link" href="#area"><span class="num">03</span> এলাকা</a>
                </li>
                <li>
                    <a class="rail-link" href="#review"><span class="num">04</span> যাচাই</a>
                </li>
            </ul>

            <div class="rail-note">
                <span class="rn-icon"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-shield"/></svg></span>
                <strong>নিরাপদ আবেদন</strong>
                <p>আপনার আইডি ডকুমেন্ট শুধু পরিচয় যাচাইয়ের জন্য ব্যবহার করা হয়।</p>
            </div>

            <div class="rail-foot">
                <a href="<?= e(repdoc_url('/')) ?>"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-back"/></svg> সাইটে ফিরুন</a>
                <a href="<?= e(repdoc_url('/public/login/')) ?>"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-login"/></svg> আগে থেকেই অ্যাকাউন্ট আছে?</a>
            </div>
        </aside>

        <div class="apply-content">
            <header class="apply-topbar">
                <a class="apply-topbar-brand" href="<?= e(repdoc_url('/')) ?>" aria-label="Repdoc — হোমপেজ">
                    <img src="<?= e(repdoc_url('src/assets/image/logo.png')) ?>" alt="Repdoc" height="26">
                </a>
                <p class="apply-crumb">Public <span aria-hidden="true">/</span> Operator application</p>
                <span class="topbar-status">অ্যাডমিন যাচাইয়ের অপেক্ষায়</span>
            </header>

            <main class="apply-main">
                <?php if ($success): ?>
                    <section class="panel success-panel" aria-labelledby="successTitle">
                        <span class="success-mark"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check"/></svg></span>
                        <h2 id="successTitle">আবেদন পৌঁছেছে</h2>
                        <p>আপনার অপারেটর আবেদন অ্যাডমিনের অনুমোদনের অপেক্ষায় আছে। যাচাই শেষে মোবাইলে জানানো হবে।</p>
                        <div class="success-actions">
                            <a class="btn btn-primary" href="<?= e(repdoc_url('/')) ?>">
                                <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-home"/></svg>
                                হোমপেজে ফিরুন
                            </a>
                        </div>
                    </section>
                <?php else: ?>
                    <div class="apply-head">
                        <span class="kicker"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-id"/></svg> Operator account</span>
                        <h1>অপারেটর অ্যাকাউন্টের আবেদন</h1>
                        <p>যেকোনো কম্পিউটার অপারেটর (ব্যবসা বা চাকরি) আবেদন করতে পারেন। অ্যাকাউন্ট চালুর আগে অ্যাডমিন অনুমোদন লাগবে।</p>
                    </div>

                    <div class="perk-grid" aria-label="সুবিধা">
                        <div class="perk"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-file"/></svg> রেডি টেমপ্লেট</div>
                        <div class="perk"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-shield"/></svg> নিরাপদ লগইন</div>
                        <div class="perk"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-mobile"/></svg> সব ডিভাইসে</div>
                        <div class="perk"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-bolt"/></svg> দ্রুত ডাউনলোড</div>
                    </div>

                    <?php if ($error !== ''): ?>
                        <div class="alert alert-error" role="alert"><?= e($error) ?></div>
                    <?php endif; ?>

                    <form method="post" enctype="multipart/form-data" novalidate>
                        <?= csrf_field() ?>

                        <section class="panel" id="identity" aria-labelledby="stepIdentity">
                            <div class="panel-head">
                                <span class="step-chip" aria-hidden="true">01</span>
                                <div>
                                    <h2 id="stepIdentity">পরিচয় ও প্রতিষ্ঠান</h2>
                                    <p>আইডেন্টিটির ধরন, প্রতিষ্ঠানের তথ্য ও ডকুমেন্ট দিন।</p>
                                </div>
                            </div>
                            <div class="panel-body">
                                <div class="field">
                                    <label for="identity_type">আইডেন্টিটির ধরন *</label>
                                    <select id="identity_type" name="identity_type" required>
                                        <option value="">ধরন বেছে নিন</option>
                                        <?php foreach (entrepreneur_identity_types() as $v => $l): ?>
                                            <option value="<?= e($v) ?>"<?= $identityType === $v ? ' selected' : '' ?>><?= e($l) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="field">
                                    <label for="org_name" id="orgLabel">প্রতিষ্ঠানের নাম *</label>
                                    <input type="text" id="org_name" name="org_name" value="<?= e($orgName) ?>" maxlength="190" required>
                                </div>

                                <div class="field" id="roleWrap">
                                    <label for="applicant_role">আপনার পদ / রোল *</label>
                                    <input type="text" id="applicant_role" name="applicant_role" value="<?= e($applicantRole) ?>" maxlength="100">
                                </div>

                                <div class="field">
                                    <label for="id_address">ঠিকানা *</label>
                                    <textarea id="id_address" name="id_address" rows="2" required><?= e($idAddress) ?></textarea>
                                </div>

                                <div class="field">
                                    <label for="id_doc" id="docLabel">আইডেন্টিটির ছবি *</label>
                                    <input type="file" id="id_doc" name="id_doc" accept="image/jpeg,image/png" required>
                                    <span class="hint">JPG বা PNG, সর্বোচ্চ ২ MB</span>
                                </div>
                            </div>
                        </section>

                        <section class="panel" id="contact" aria-labelledby="stepContact">
                            <div class="panel-head">
                                <span class="step-chip" aria-hidden="true">02</span>
                                <div>
                                    <h2 id="stepContact">যোগাযোগ</h2>
                                    <p>যাচাই ও অনুমোদনের জন্য আপনার সাথে যোগাযোগের তথ্য।</p>
                                </div>
                            </div>
                            <div class="panel-body">
                                <div class="field">
                                    <label for="fullname" id="nameLabel">আপনার নাম *</label>
                                    <input type="text" id="fullname" name="fullname" value="<?= e($fullname) ?>"
                                           autocomplete="name" required>
                                </div>

                                <div class="field-row">
                                    <div class="field">
                                        <label for="mobile">মোবাইল *</label>
                                        <input type="tel" id="mobile" name="mobile" value="<?= e($mobile) ?>"
                                               autocomplete="tel" required>
                                    </div>
                                    <div class="field">
                                        <label for="email">ইমেইল <span class="optional">(ঐচ্ছিক)</span></label>
                                        <input type="email" id="email" name="email" value="<?= e($email) ?>"
                                               autocomplete="email">
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="panel" id="area" aria-labelledby="stepArea">
                            <div class="panel-head">
                                <span class="step-chip" aria-hidden="true">03</span>
                                <div>
                                    <h2 id="stepArea">এলাকা ও প্রতিনিধি</h2>
                                    <p>আপনার কাজের এলাকা ও স্থানীয় জনপ্রতিনিধির তথ্য।</p>
                                </div>
                            </div>
                            <div class="panel-body">
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
                                    <label for="message">বার্তা <span class="optional">(ঐচ্ছিক)</span></label>
                                    <textarea id="message" name="message" rows="3"><?= e($message) ?></textarea>
                                </div>
                            </div>
                        </section>

                        <div class="submit-panel" id="review">
                            <div class="sp-copy">
                                <strong>জমা দেওয়ার আগে যাচাই করুন</strong>
                                <span>সব আবশ্যক ঘর পূরণ করেছেন এবং ডকুমেন্টটি স্পষ্ট কিনা দেখে নিন।</span>
                            </div>
                            <div class="submit-actions">
                                <a class="btn btn-secondary" href="<?= e(repdoc_url('/')) ?>">
                                    <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-home"/></svg>
                                    হোমপেজ
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    আবেদন জমা দিন
                                    <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-arrow"/></svg>
                                </button>
                            </div>
                        </div>
                    </form>

                    <script>
                    (function () {
                        var type = document.getElementById('identity_type');
                        if (!type) return;
                        var orgLabel = document.getElementById('orgLabel');
                        var roleWrap = document.getElementById('roleWrap');
                        var roleInput = document.getElementById('applicant_role');
                        var docLabel = document.getElementById('docLabel');
                        var nameLabel = document.getElementById('nameLabel');
                        function update() {
                            var office = type.value === 'office_staff';
                            var shop = type.value === 'shop_owner';
                            if (orgLabel) orgLabel.textContent = shop ? 'দোকানের নাম *' : 'অফিসের নাম *';
                            if (docLabel) docLabel.textContent = shop ? 'ট্রেড লাইসেন্সের ছবি *' : 'অফিস আইডি কার্ডের ছবি *';
                            if (nameLabel) nameLabel.textContent = shop ? 'মালিকের নাম *' : (office ? 'কর্মীর নাম *' : 'আপনার নাম *');
                            if (roleWrap) roleWrap.hidden = !office;
                            if (roleInput) roleInput.required = office;
                        }
                        type.addEventListener('change', update);
                        update();
                    })();
                    </script>
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

                <p class="apply-foot">Independent tools site — not a government portal.</p>
            </main>
        </div>
    </div>
</body>
</html>
