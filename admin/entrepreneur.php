<?php
declare(strict_types=1);

/**
 * Repdoc — admin: entrepreneur applications.
 *
 * Review UDC entrepreneur account claims submitted via
 * Entrepreneur/apply/. Approve / reject / delete (POST → flash → redirect,
 * same pattern as users.php).
 */

require_once __DIR__ . '/includes/layout.php';

$db = repdoc_db();

// ── Handle actions (POST → flash → redirect, so a refresh never re-posts) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Security token mismatch — please try again.');
        redirect('entrepreneur.php');
    }

    $action = (string) ($_POST['action'] ?? '');
    $appId  = (int) ($_POST['app_id'] ?? 0);
    $back   = 'entrepreneur.php?' . http_build_query(array_filter([
        'q'      => (string) ($_POST['return_q'] ?? ''),
        'status' => (string) ($_POST['return_status'] ?? ''),
    ], static fn($v) => $v !== ''));

    if ($appId <= 0 && $action !== 'create_udc') {
        flash_set('error', 'Application not found.');
        redirect($back);
    }

    try {
        switch ($action) {
            case 'create_udc':
                $fullname = trim((string) ($_POST['fullname'] ?? ''));
                $mobile   = trim((string) ($_POST['mobile'] ?? ''));
                $email    = trim((string) ($_POST['email'] ?? ''));
                $password = (string) ($_POST['password'] ?? '');
                $union    = trim((string) ($_POST['union_parishad'] ?? ''));
                $upazila  = trim((string) ($_POST['upazila'] ?? ''));
                $district = trim((string) ($_POST['district'] ?? ''));
                if ($fullname === '' || $mobile === '' || $email === '' || $union === '' || $upazila === '' || $district === '') {
                    flash_set('error', 'সব ঘর পূরণ করুন।');
                    break;
                }
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    flash_set('error', 'ইমেইল ঠিকানা সঠিক নয়।');
                    break;
                }
                if (strlen($password) < 6) {
                    flash_set('error', 'পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে।');
                    break;
                }
                $st = $db->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
                $st->execute([$email]);
                if ((int) $st->fetchColumn() > 0) {
                    flash_set('error', 'এই ইমেইলে ইতিমধ্যে অ্যাকাউন্ট আছে।');
                    break;
                }
                $db->beginTransaction();
                try {
                    $db->prepare("INSERT INTO users (fullname, email, phone, password, role, status, balance, created_at)
                                  VALUES (?, ?, ?, ?, 'user', 'approved', 0, ?)")
                       ->execute([$fullname, $email, $mobile, password_hash($password, PASSWORD_DEFAULT), date('Y-m-d H:i:s')]);
                    $newUid = (int) $db->lastInsertId();
                    $db->prepare("INSERT INTO entrepreneur_apps (fullname, mobile, email, union_parishad, upazila, district, message, status, chairman_name_bn, chairman_name_en, leader_title, created_at)
                                  VALUES (?, ?, ?, ?, ?, ?, ?, 'approved', ?, ?, ?, ?)")
                       ->execute([$fullname, $mobile, $email, $union, $upazila, $district, 'Created by admin',
                           trim((string) ($_POST['chairman_name_bn'] ?? '')), trim((string) ($_POST['chairman_name_en'] ?? '')),
                           trim((string) ($_POST['leader_title'] ?? 'chairman')), date('Y-m-d H:i:s')]);
                    $db->commit();
                } catch (Throwable $ex) {
                    if ($db->inTransaction()) $db->rollBack();
                    throw $ex;
                }
                $chairBn = trim((string) ($_POST['chairman_name_bn'] ?? ''));
                $chairEn = trim((string) ($_POST['chairman_name_en'] ?? ''));
                $officeSaved = save_office_profile($newUid, [
                    'office_type'   => 'union',
                    'office_name'   => trim((string) ($_POST['office_name'] ?? $union)),
                    'chairman_name' => $chairBn !== '' ? $chairBn : $chairEn,
                    'upazila'       => $upazila,
                    'district'      => $district,
                ]);
                flash_set('ok', 'UDC অ্যাকাউন্ট তৈরি হয়েছে (লগইন: ' . $email . ')' . ($officeSaved ? '' : ' — অফিস তথ্য সেভ হয়নি।'));
                break;

            case 'approve':
                $st = $db->prepare('SELECT * FROM entrepreneur_apps WHERE id = ?');
                $st->execute([$appId]);
                $app = $st->fetch() ?: null;
                if (!$app) {
                    flash_set('error', 'Application not found.');
                    break;
                }
                $db->prepare("UPDATE entrepreneur_apps SET status = 'approved' WHERE id = ?")->execute([$appId]);
                // Link office profile when the login account already exists.
                if (!empty($app['email'])) {
                    $st = $db->prepare('SELECT id FROM users WHERE email = ?');
                    $st->execute([$app['email']]);
                    $auid = (int) ($st->fetchColumn() ?: 0);
                    if ($auid > 0) {
                        save_office_profile($auid, [
                            'office_type'   => 'union',
                            'office_name'   => (string) ($app['union_parishad'] ?? ''),
                            'chairman_name' => (string) (($app['chairman_name_bn'] ?? '') !== '' ? $app['chairman_name_bn'] : ($app['chairman_name_en'] ?? '')),
                            'upazila'       => (string) ($app['upazila'] ?? ''),
                            'district'      => (string) ($app['district'] ?? ''),
                        ]);
                    }
                }
                flash_set('ok', 'Application approved.');
                break;

            case 'toggle_archive':
                $st = $db->prepare('SELECT archive_access FROM entrepreneur_apps WHERE id = ?');
                $st->execute([$appId]);
                $current = $st->fetchColumn();
                if ($current === false) {
                    flash_set('error', 'Application not found.');
                    break;
                }
                $grant = ((int) $current === 1) ? 0 : 1;
                $db->prepare('UPDATE entrepreneur_apps SET archive_access = ? WHERE id = ?')->execute([$grant, $appId]);
                flash_set('ok', $grant === 1 ? 'Document Archive access granted.' : 'Document Archive access revoked.');
                break;

            case 'reject':
                $db->prepare("UPDATE entrepreneur_apps SET status = 'rejected' WHERE id = ?")->execute([$appId]);
                flash_set('ok', 'Application rejected.');
                break;

            case 'delete':
                $db->prepare('DELETE FROM entrepreneur_apps WHERE id = ?')->execute([$appId]);
                flash_set('ok', 'Application deleted.');
                break;

            case 'edit_app':
                $fullname = trim((string) ($_POST['fullname'] ?? ''));
                $mobile   = trim((string) ($_POST['mobile'] ?? ''));
                $email    = trim((string) ($_POST['email'] ?? ''));
                $union    = trim((string) ($_POST['union_parishad'] ?? ''));
                $upazila  = trim((string) ($_POST['upazila'] ?? ''));
                $district = trim((string) ($_POST['district'] ?? ''));
                $message  = trim((string) ($_POST['message'] ?? ''));
                $chairBn  = trim((string) ($_POST['chairman_name_bn'] ?? ''));
                $chairEn  = trim((string) ($_POST['chairman_name_en'] ?? ''));
                $leader   = trim((string) ($_POST['leader_title'] ?? ''));
                if ($fullname === '' || $mobile === '' || $union === '' || $upazila === '' || $district === '') {
                    flash_set('error', 'সব আবশ্যক ঘর পূরণ করুন।');
                    break;
                }
                if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    flash_set('error', 'ইমেইল ঠিকানা সঠিক নয়।');
                    break;
                }
                $st = $db->prepare('SELECT * FROM entrepreneur_apps WHERE id = ?');
                $st->execute([$appId]);
                $app = $st->fetch() ?: null;
                if (!$app) {
                    flash_set('error', 'Application not found.');
                    break;
                }
                $oldEmail = (string) ($app['email'] ?? '');
                if ($email !== '' && $email !== $oldEmail) {
                    $st = $db->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
                    $st->execute([$email]);
                    if ((int) $st->fetchColumn() > 0) {
                        flash_set('error', 'এই ইমেইলে অন্য অ্যাকাউন্ট আছে।');
                        break;
                    }
                }
                $db->beginTransaction();
                try {
                    $db->prepare('UPDATE entrepreneur_apps SET fullname = ?, mobile = ?, email = ?, union_parishad = ?, upazila = ?, district = ?, message = ?, chairman_name_bn = ?, chairman_name_en = ?, leader_title = ? WHERE id = ?')
                       ->execute([$fullname, $mobile, $email, $union, $upazila, $district, $message, $chairBn, $chairEn, $leader, $appId]);
                    // Keep the linked login account in sync.
                    if ($oldEmail !== '') {
                        $db->prepare('UPDATE users SET fullname = ?, email = ?, phone = ? WHERE email = ?')
                           ->execute([$fullname, $email, $mobile, $oldEmail]);
                    }
                    $db->commit();
                } catch (Throwable $ex) {
                    if ($db->inTransaction()) $db->rollBack();
                    throw $ex;
                }
                // Keep the office profile in sync when the account exists.
                if ($email !== '') {
                    $st = $db->prepare('SELECT id FROM users WHERE email = ?');
                    $st->execute([$email]);
                    $auid = (int) ($st->fetchColumn() ?: 0);
                    if ($auid > 0) {
                        $prof = office_profile($auid);
                        save_office_profile($auid, [
                            'office_type'   => $prof['office_type'],
                            'office_name'   => $prof['office_name'] !== '' ? $prof['office_name'] : $union,
                            'chairman_name' => $chairBn !== '' ? $chairBn : $chairEn,
                            'upazila'       => $upazila,
                            'district'      => $district,
                        ]);
                    }
                }
                flash_set('ok', 'সব তথ্য আপডেট হয়েছে।');
                break;

            case 'reset_pw':
                $newPw = (string) ($_POST['new_password'] ?? '');
                if (strlen($newPw) < 6) {
                    flash_set('error', 'নতুন পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে।');
                    break;
                }
                $st = $db->prepare('SELECT * FROM entrepreneur_apps WHERE id = ?');
                $st->execute([$appId]);
                $app = $st->fetch() ?: null;
                if (!$app || empty($app['email'])) {
                    flash_set('error', 'Application not found.');
                    break;
                }
                $st = $db->prepare('SELECT id FROM users WHERE email = ?');
                $st->execute([$app['email']]);
                $auid = (int) ($st->fetchColumn() ?: 0);
                if ($auid <= 0) {
                    flash_set('error', 'এই ইমেইলে কোনো লগইন অ্যাকাউন্ট নেই।');
                    break;
                }
                $db->prepare('UPDATE users SET password = ? WHERE id = ?')
                   ->execute([password_hash($newPw, PASSWORD_DEFAULT), $auid]);
                flash_set('ok', 'পাসওয়ার্ড রিসেট হয়েছে (' . $app['email'] . ')।');
                break;

            default:
                flash_set('error', 'Unknown action.');
        }
    } catch (Throwable $e) {
        flash_set('error', 'Operation failed — please try again.');
    }

    redirect($back);
}

// ── Filters ──────────────────────────────────────────────────────
$q      = trim((string) ($_GET['q'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));

$apps  = [];
$total = 0;
$pending = 0;

if ($db) {
    $where  = [];
    $params = [];

    if ($q !== '') {
        $where[] = '(fullname LIKE ? OR mobile LIKE ? OR email LIKE ? OR union_parishad LIKE ? OR upazila LIKE ? OR district LIKE ?)';
        $like = '%' . $q . '%';
        array_push($params, $like, $like, $like, $like, $like, $like);
    }
    if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
        $where[] = 'status = ?';
        $params[] = $status;
    }

    $sql = 'SELECT * FROM entrepreneur_apps';
    if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
    $sql .= ' ORDER BY (status = \'pending\') DESC, id DESC LIMIT 200';

    try {
        $st = $db->prepare($sql);
        $st->execute($params);
        $apps = $st->fetchAll();
        $total = (int) $db->query('SELECT COUNT(*) FROM entrepreneur_apps')->fetchColumn();
        $pending = (int) $db->query("SELECT COUNT(*) FROM entrepreneur_apps WHERE status = 'pending'")->fetchColumn();
    } catch (Throwable $e) {
        $db = null;
    }
}

// Login accounts linked by email (for password reset + badge).
$accountByEmail = [];
if ($db && $apps) {
    try {
        $emails = array_values(array_unique(array_filter(array_map(static fn($a) => trim((string) ($a['email'] ?? '')), $apps))));
        if ($emails) {
            $st = $db->prepare('SELECT id, email FROM users WHERE email IN (' . implode(',', array_fill(0, count($emails), '?')) . ')');
            $st->execute($emails);
            foreach ($st->fetchAll() as $u) {
                $accountByEmail[(string) $u['email']] = (int) $u['id'];
            }
        }
    } catch (Throwable $e) {}
}

admin_page_start('Entrepreneur Applications', 'entrepreneur.php');
admin_notice();
?>

<div class="admin-page-head">
    <h1 class="admin-title">Entrepreneur Applications</h1>
    <p class="admin-sub">Review UDC entrepreneur account claims. Total <?= number_format($total) ?> applications, <?= number_format($pending) ?> pending. Grant Document Archive access per approved user.</p>
</div>

<?php if (!$db): ?>
    <div class="alert alert-error" role="alert">Could not connect to the database.</div>
<?php else: ?>

<form class="filter-bar" method="get" action="entrepreneur.php">
    <div class="field">
        <label for="q">Search</label>
        <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Name, mobile, union">
    </div>
    <div class="field">
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="">All</option>
            <?php foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $val => $label): ?>
                <option value="<?= e($val) ?>"<?= $status === $val ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field" style="flex:0 0 auto">
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    </div>
    <div class="field" style="flex:0 0 auto">
        <a class="btn btn-secondary btn-sm" href="entrepreneur.php">Reset</a>
    </div>
</form>

<div class="panel">
    <div class="panel-head">Create UDC account <span class="count">Admin</span></div>
    <div class="panel-body">
        <form method="post" action="entrepreneur.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create_udc">
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px">
                <div class="field"><label for="nu_fullname">Full name *</label><input id="nu_fullname" name="fullname" required maxlength="100"></div>
                <div class="field"><label for="nu_mobile">Mobile *</label><input id="nu_mobile" name="mobile" required maxlength="20"></div>
                <div class="field"><label for="nu_email">Email (login) *</label><input id="nu_email" type="email" name="email" required maxlength="100"></div>
                <div class="field"><label for="nu_password">Password (min 6) *</label><input id="nu_password" type="password" name="password" required minlength="6"></div>
                <div class="field"><label for="nu_division">Division *</label><select id="nu_division" required><option value="">Select division</option></select></div>
                <div class="field"><label for="nu_district">District *</label><select id="nu_district" name="district" required><option value="">Select division first</option></select></div>
                <div class="field"><label for="nu_upazila">Upazila *</label><select id="nu_upazila" name="upazila" required><option value="">Select district first</option></select></div>
                <div class="field"><label for="area_type">Area type *</label><select id="area_type" required><option value="union">Union</option><option value="municipality">Pouroshava</option><option value="city">City Corp.</option></select></div>
                <div class="field" id="nuUnionWrap"><label for="nu_union">Union Parishad *</label><select id="nu_union" name="union_parishad" required><option value="">Select upazila first</option></select></div>
                <div class="field" id="nuPouroWrap" hidden><label for="nu_pouro">Pouroshava *</label><select id="nu_pouro" name="union_parishad" disabled><option value="">Select district first</option></select></div>
                <div class="field" id="nuCityWrap" hidden><label for="nu_city">City Corporation *</label><select id="nu_city" name="union_parishad" disabled><option value="">Select district first</option></select></div>
                <div class="field"><label for="nu_office">Office name</label><input id="nu_office" name="office_name" maxlength="150" placeholder="Default: union name"></div>
                <div class="field"><label for="nu_chairbn">Chairman name (Bangla) *</label><input id="nu_chairbn" name="chairman_name_bn" maxlength="100" required></div>
                <div class="field"><label for="nu_chairen">Chairman name (English)</label><input id="nu_chairen" name="chairman_name_en" maxlength="100"></div>
                <div class="field"><label for="nu_leader">Post</label><select id="nu_leader" name="leader_title"><option value="chairman">Chairman</option><option value="panel">Panel Chairman</option><option value="mayor">Mayor</option><option value="admin">Administrator</option></select></div>
            </div>
            <p style="margin-top:14px"><button type="submit" class="btn btn-primary btn-sm">Create UDC account</button>
            <span class="cell-sub">Account is approved instantly with office profile.</span></p>
        </form>
        <script>
        (function () {
            var GEO = <?= json_encode(repdoc_url('genaretor/prottoyon/api/geo.php')) ?>;
            var divSel = document.getElementById('nu_division');
            var disSel = document.getElementById('nu_district');
            var upaSel = document.getElementById('nu_upazila');
            var uniSel = document.getElementById('nu_union');
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
            function get(action, params) {
                var q = 'action=' + encodeURIComponent(action);
                Object.keys(params).forEach(function (k) { q += '&' + k + '=' + encodeURIComponent(params[k]); });
                return fetch(GEO + '?' + q, { credentials: 'same-origin' }).then(function (r) { return r.json(); }).catch(function () { return []; });
            }
            divSel.addEventListener('change', function () {
                var id = selId(divSel);
                fill(disSel, [], 'Loading…');
                fill(upaSel, [], 'Select district first');
                fill(uniSel, [], 'Select upazila first');
                if (!id) { fill(disSel, [], 'Select division first'); return; }
                get('districts', { division_id: id }).then(function (rows) { fill(disSel, rows, 'Select district'); });
            });
            disSel.addEventListener('change', function () {
                var id = selId(disSel);
                fill(upaSel, [], 'Loading…');
                fill(uniSel, [], 'Select upazila first');
                var p = document.getElementById('nu_pouro');
                if (p) fill(p, [], 'Loading…');
                if (!id) { fill(upaSel, [], 'Select district first'); if (p) fill(p, [], 'Select district first'); var c0 = document.getElementById('nu_city'); if (c0) fill(c0, [], 'Select district first'); return; }
                get('upazilas', { district_id: id }).then(function (rows) { fill(upaSel, rows, 'Select upazila'); });
                if (p) get('municipalities', { district_id: id }).then(function (rows) { fill(p, rows, 'Select pouroshava'); });
                var c1 = document.getElementById('nu_city');
                if (c1) { fill(c1, [], 'Loading…'); get('city-corporations', { district_id: id }).then(function (rows) { fill(c1, rows.length ? rows : [], rows.length ? 'Select city corporation' : 'No city corporation here'); }); }
            });
            upaSel.addEventListener('change', function () {
                var id = selId(upaSel);
                fill(uniSel, [], 'Loading…');
                if (!id) { fill(uniSel, [], 'Select upazila first'); return; }
                get('unions', { upazila_id: id }).then(function (rows) { fill(uniSel, rows, 'Select union'); });
            });
            get('divisions', {}).then(function (rows) { fill(divSel, rows, 'Select division'); });
            /* Union / Pouroshava / City toggle */
            var unionWrap = document.getElementById('nuUnionWrap');
            var pouroWrap = document.getElementById('nuPouroWrap');
            var cityWrap = document.getElementById('nuCityWrap');
            var pouroInput = document.getElementById('nu_pouro');
            var cityInput = document.getElementById('nu_city');
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
        })();
        </script>
    </div>
</div>

<div class="panel">
    <div class="panel-head">Application list <span class="count"><?= count($apps) ?> shown</span></div>

    <?php if (!$apps): ?>
        <div class="empty-state-sm">No applications match this filter.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Applicant</th>
                        <th>Address</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($apps as $a): ?>
                    <?php $aid = (int) $a['id']; ?>
                    <tr>
                        <td>
                            <span class="cell-name"><?= e($a['fullname'] ?: '—') ?></span>
                            <span class="cell-sub">#<?= $aid ?> · <?= e($a['mobile']) ?></span>
                            <?php if (!empty($a['email'])): ?>
                                <span class="cell-sub"><?= e($a['email']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($a['chairman_name_bn']) || !empty($a['chairman_name_en'])): ?>
                                <span class="cell-sub">Chairman: <?= e(trim((($a['chairman_name_bn'] ?? '') . ' ' . ($a['chairman_name_en'] ?? '')))) ?><?= !empty($a['leader_title']) ? ' (' . e($a['leader_title']) . ')' : '' ?></span>
                            <?php endif; ?>
                            <span class="cell-sub">Date: <?= e((string) $a['created_at']) ?></span>
                        </td>
                        <td>
                            <span class="cell-name"><?= e($a['union_parishad'] ?: '—') ?></span>
                            <span class="cell-sub"><?= e(trim($a['upazila'] . ', ' . $a['district'], ', ')) ?></span>
                        </td>
                        <td class="cell-sub"><?= e($a['message'] ?: '—') ?></td>
                        <td>
                            <?php $appStatusLabels = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected']; ?>
                            <span class="pill pill-<?= e($a['status']) ?>"><?= e($appStatusLabels[$a['status']] ?? $a['status']) ?></span>
                        </td>
                        <td>
                            <div class="cell-actions">
                                <?php $hasAccount = !empty($a['email']) && isset($accountByEmail[trim((string) $a['email'])]); ?>
                                <details>
                                    <summary style="cursor:pointer;color:var(--primary);font-weight:600;font-size:13px">Edit all info</summary>
                                    <form method="post" action="entrepreneur.php" style="margin-top:8px;display:grid;gap:6px;min-width:220px">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="edit_app">
                                        <input type="hidden" name="app_id" value="<?= $aid ?>">
                                        <input type="hidden" name="return_q" value="<?= e($q) ?>">
                                        <input type="hidden" name="return_status" value="<?= e($status) ?>">
                                        <input name="fullname" value="<?= e($a['fullname']) ?>" required placeholder="Full name" maxlength="100">
                                        <input name="mobile" value="<?= e($a['mobile']) ?>" required placeholder="Mobile" maxlength="20">
                                        <input type="email" name="email" value="<?= e($a['email']) ?>" placeholder="Email" maxlength="100">
                                        <input name="union_parishad" value="<?= e($a['union_parishad']) ?>" required placeholder="Union" maxlength="100">
                                        <input name="upazila" value="<?= e($a['upazila']) ?>" required placeholder="Upazila" maxlength="100">
                                        <input name="district" value="<?= e($a['district']) ?>" required placeholder="District" maxlength="100">
                                        <input name="chairman_name_bn" value="<?= e($a['chairman_name_bn'] ?? '') ?>" placeholder="Chairman (Bangla)" maxlength="100">
                                        <input name="chairman_name_en" value="<?= e($a['chairman_name_en'] ?? '') ?>" placeholder="Chairman (English)" maxlength="100">
                                        <select name="leader_title">
                                            <?php foreach (['chairman' => 'Chairman', 'panel' => 'Panel Chairman', 'mayor' => 'Mayor', 'admin' => 'Administrator'] as $v => $l): ?>
                                                <option value="<?= e($v) ?>"<?= ($a['leader_title'] ?? '') === $v ? ' selected' : '' ?>><?= e($l) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input name="message" value="<?= e($a['message']) ?>" placeholder="Message" maxlength="255">
                                        <button type="submit" class="btn btn-primary btn-sm">Save all</button>
                                    </form>
                                </details>
                                <?php if ($hasAccount): ?>
                                    <span class="pill pill-approved">Account ✓</span>
                                    <details>
                                        <summary style="cursor:pointer;color:var(--primary);font-weight:600;font-size:13px">Reset password</summary>
                                        <form method="post" action="entrepreneur.php" style="margin-top:8px;display:flex;gap:6px;flex-wrap:wrap">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="reset_pw">
                                            <input type="hidden" name="app_id" value="<?= $aid ?>">
                                            <input type="hidden" name="return_q" value="<?= e($q) ?>">
                                            <input type="hidden" name="return_status" value="<?= e($status) ?>">
                                            <input type="password" name="new_password" required minlength="6" placeholder="New password (min 6)" style="max-width:160px">
                                            <button type="submit" class="btn btn-secondary btn-sm" onclick="return confirm('Reset login password for <?= e($a['email']) ?>?')">Set</button>
                                        </form>
                                    </details>
                                <?php else: ?>
                                    <span class="pill pill-pending">No account</span>
                                <?php endif; ?>
                                <?php if ($a['status'] === 'approved'): ?>
                                    <?php $archiveAccess = (int) ($a['archive_access'] ?? 0) === 1; ?>
                                    <span class="pill <?= $archiveAccess ? 'pill-approved' : 'pill-pending' ?>"><?= $archiveAccess ? 'Archive ✓' : 'No archive' ?></span>
                                    <form method="post" action="entrepreneur.php" style="margin:0">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="toggle_archive">
                                        <input type="hidden" name="app_id" value="<?= $aid ?>">
                                        <input type="hidden" name="return_q" value="<?= e($q) ?>">
                                        <input type="hidden" name="return_status" value="<?= e($status) ?>">
                                        <button type="submit" class="btn btn-secondary btn-sm"><?= $archiveAccess ? 'Revoke archive' : 'Allow archive' ?></button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($a['status'] !== 'approved'): ?>
                                    <form method="post" action="entrepreneur.php">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="app_id" value="<?= $aid ?>">
                                        <input type="hidden" name="return_q" value="<?= e($q) ?>">
                                        <input type="hidden" name="return_status" value="<?= e($status) ?>">
                                        <button type="submit" class="btn btn-primary btn-sm">Approve</button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($a['status'] !== 'rejected'): ?>
                                    <form method="post" action="entrepreneur.php">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="app_id" value="<?= $aid ?>">
                                        <input type="hidden" name="return_q" value="<?= e($q) ?>">
                                        <input type="hidden" name="return_status" value="<?= e($status) ?>">
                                        <button type="submit" class="btn btn-secondary btn-sm">Reject</button>
                                    </form>
                                <?php endif; ?>

                                <form method="post" action="entrepreneur.php"
                                      onsubmit="return confirm('This application will be deleted. Continue?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="app_id" value="<?= $aid ?>">
                                    <input type="hidden" name="return_q" value="<?= e($q) ?>">
                                    <input type="hidden" name="return_status" value="<?= e($status) ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php endif; ?>
<?php admin_page_end(); ?>
