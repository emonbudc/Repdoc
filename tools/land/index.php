<?php
declare(strict_types=1);

/**
 * Repdoc — land record search.
 *
 * Search-only port of github.com/emonbaluahat/land. It exposes the same
 * cascade (division → district → upazila → mouja) and khôtian lookup, but
 * drops the upstream homepage, ads, ad-block guard, admin panel, and print
 * templates. Owner details are optional and require LAND_JWT_SECRET.
 *
 * This is an independent tools page — not a government portal.
 */

require_once __DIR__ . '/lib.php';

require_login();
if (!is_udc() && !is_admin()) {
    redirect(repdoc_url('Entrepreneur/apply/'));
}
repdoc_security_headers();

$self = (string) ($_SERVER['SCRIPT_NAME'] ?? '/tools/land/index.php');

/* ────────────────────────────── JSON actions ───────────────────────────── */

$action = (string) ($_GET['action'] ?? '');
if ($action !== '') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');

    $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

    if ($action === 'divisions' || $action === 'districts' || $action === 'upazilas' || $action === 'moujas') {
        $data = match ($action) {
            'divisions' => land_get_divisions(),
            'districts' => land_get_districts((int) ($_GET['division_id'] ?? 0)),
            'upazilas'  => land_get_upazilas((int) ($_GET['district_id'] ?? 0)),
            'moujas'    => land_get_moujas((int) ($_GET['upazila_id'] ?? 0)),
        };

        echo json_encode(['data' => $data], $jsonFlags);
        exit;
    }

    if ($action === 'search' || $action === 'detail') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
            http_response_code(419);
            echo json_encode(['ok' => false, 'error' => 'সেশন টোকেন সঠিক নয়। পেজ রিফ্রেশ করে আবার চেষ্টা করুন।'], $jsonFlags);
            exit;
        }

        if ($action === 'search') {
            echo json_encode(land_search($_POST), $jsonFlags);
            exit;
        }

        echo json_encode(land_detail((int) ($_POST['holding_id'] ?? 0)), $jsonFlags);
        exit;
    }

    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Unknown action.'], $jsonFlags);
    exit;
}

$panelUrl = repdoc_panel_home();
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#F8FAFC">
    <title>ভূমি খতিয়ান অনুসন্ধান — Repdoc</title>
    <link rel="icon" type="image/png" href="<?= e(repdoc_url('public/favicon.png')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Bengali:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/tokens.css?v=2')) ?>">
    <style>
        .land-page {
            min-height: 100vh;
            background:
                radial-gradient(900px 420px at 15% -10%, var(--primary-light), transparent 60%),
                var(--bg-base);
            padding: 28px 0 64px;
        }
        .land-shell { width: min(1120px, calc(100% - 32px)); margin: 0 auto; }
        .land-top {
            display: flex; align-items: center; justify-content: space-between;
            gap: 16px; margin-bottom: 22px;
        }
        .land-brand { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .land-brand img { height: 36px; width: auto; }
        .land-brand span { display: block; font-weight: 800; letter-spacing: -.01em; }
        .land-brand small { display: block; color: var(--text-muted); font-size: .78rem; }
        .land-back {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 9px 14px; border: 1px solid var(--border-strong);
            border-radius: var(--radius-full); background: var(--bg-surface);
            color: var(--text-primary); font-size: .88rem; font-weight: 600;
            box-shadow: var(--shadow-sm);
        }
        .land-back:hover { border-color: var(--border-focus); color: var(--primary); }
        .land-hero { margin-bottom: 22px; }
        .land-kicker {
            display: inline-block; margin-bottom: 8px; padding: 4px 10px;
            border-radius: var(--radius-full); background: var(--primary-light);
            color: var(--primary); font-size: .72rem; font-weight: 700;
            letter-spacing: .08em; text-transform: uppercase;
        }
        .land-hero h1 { font-size: clamp(1.6rem, 3.2vw, 2.35rem); margin-bottom: 8px; }
        .land-hero p { max-width: 70ch; color: var(--text-muted); }
        .land-card {
            background: var(--bg-surface); border: 1px solid var(--border-color);
            border-radius: var(--radius-lg); box-shadow: var(--shadow-md);
            padding: clamp(16px, 2.4vw, 26px);
        }
        .land-form-grid {
            display: grid; grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }
        .land-field { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
        .land-field label { font-size: .8rem; font-weight: 700; color: var(--text-muted); }
        .land-field select,
        .land-field input {
            width: 100%; min-height: 44px; padding: 10px 12px;
            border: 1px solid var(--border-strong); border-radius: var(--radius-sm);
            background: var(--bg-surface); color: var(--text-primary); font: inherit;
        }
        .land-field select:focus,
        .land-field input:focus {
            outline: 3px solid color-mix(in srgb, var(--border-focus) 22%, transparent);
            outline-offset: 1px; border-color: var(--border-focus);
        }
        .land-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; margin-top: 18px; }
        .land-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            min-height: 44px; padding: 10px 18px; border: 0; border-radius: var(--radius-sm);
            background: var(--primary); color: #fff; font-weight: 700; cursor: pointer;
            transition: background var(--transition-fast), transform var(--transition-fast);
        }
        .land-btn:hover { background: var(--primary-hover); }
        .land-btn:active { transform: translateY(1px); }
        .land-btn:disabled { opacity: .65; cursor: wait; }
        .land-btn-secondary {
            background: var(--bg-surface); color: var(--text-primary);
            border: 1px solid var(--border-strong);
        }
        .land-btn-secondary:hover { background: var(--bg-muted); color: var(--text-primary); }
        .land-hint { color: var(--text-muted); font-size: .85rem; }
        .land-hint.is-error { color: var(--danger); }
        .land-note {
            margin-top: 16px; padding: 10px 12px; border-radius: var(--radius-sm);
            background: var(--warning-bg); color: var(--text-primary);
            border: 1px solid color-mix(in srgb, var(--warning) 30%, transparent);
            font-size: .86rem;
        }
        .land-results { margin-top: 22px; }
        .land-results-head {
            display: flex; flex-wrap: wrap; align-items: baseline;
            justify-content: space-between; gap: 8px; margin-bottom: 10px;
        }
        .land-results-head h2 { font-size: 1.15rem; }
        .land-results-head span { color: var(--text-muted); font-size: .86rem; }
        .land-grid { display: grid; gap: 12px; }
        .land-result {
            border: 1px solid var(--border-color); border-radius: var(--radius-md);
            background: var(--bg-surface); padding: 14px 16px;
            box-shadow: var(--shadow-sm);
        }
        .land-result-head {
            display: flex; flex-wrap: wrap; align-items: center;
            justify-content: space-between; gap: 10px;
        }
        .land-result-head strong { font-size: 1rem; }
        .land-chips { display: flex; flex-wrap: wrap; gap: 6px; }
        .land-chip {
            display: inline-block; padding: 3px 9px; border-radius: var(--radius-full);
            background: var(--bg-muted); color: var(--text-muted);
            font-size: .76rem; font-weight: 600;
        }
        .land-detail { margin-top: 14px; border-top: 1px dashed var(--border-color); padding-top: 12px; }
        .land-detail h3 { font-size: .92rem; margin: 12px 0 6px; }
        .land-table-wrap { overflow-x: auto; }
        .land-table { width: 100%; border-collapse: collapse; font-size: .86rem; }
        .land-table th, .land-table td {
            padding: 8px 10px; border-bottom: 1px solid var(--border-color);
            text-align: left; vertical-align: top;
        }
        .land-table th { background: var(--bg-muted); color: var(--text-muted); font-size: .76rem; }
        .land-detail-total { margin-top: 8px; font-size: .88rem; }
        .land-error {
            padding: 12px 14px; border-radius: var(--radius-sm);
            background: var(--danger-bg); color: var(--text-primary);
            border: 1px solid color-mix(in srgb, var(--danger) 28%, transparent);
            font-size: .9rem;
        }
        .land-legal {
            margin-top: 24px; color: var(--text-muted); font-size: .8rem;
            border-top: 1px solid var(--border-color); padding-top: 14px;
        }
        .land-legal a { color: var(--primary); font-weight: 600; }
        @media (max-width: 900px) {
            .land-form-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 560px) {
            .land-form-grid { grid-template-columns: 1fr; }
            .land-top { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
<div class="land-page">
    <div class="land-shell">
        <header class="land-top">
            <a class="land-brand" href="<?= e($panelUrl) ?>" aria-label="Repdoc — ড্যাশবোর্ড">
                <img src="<?= e(repdoc_url('src/assets/image/logo.png')) ?>" alt="Repdoc">
                <span>ভূমি খতিয়ান অনুসন্ধান<small>Repdoc · অপারেটর টুল</small></span>
            </a>
            <a class="land-back" href="<?= e($panelUrl) ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                ড্যাশবোর্ডে ফিরুন
            </a>
        </header>

        <section class="land-hero" aria-labelledby="landHeading">
            <span class="land-kicker">Land Record</span>
            <h1 id="landHeading">মৌজা ও খতিয়ান নং দিয়ে হোল্ডিং খুঁজুন</h1>
            <p>বিভাগ, জেলা, উপজেলা ও মৌজা বেছে নিয়ে খতিয়ান নং দিন। ফলাফল সরকারি ভূমি তথ্যভান্ডার (api.ldtax.gov.bd) থেকে দেখা হয়।</p>
        </section>

        <section class="land-card" aria-labelledby="searchHeading">
            <h2 id="searchHeading" class="sr-only">খতিয়ান অনুসন্ধান ফরম</h2>

            <form id="landForm" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="land-form-grid">
                    <div class="land-field">
                        <label for="division_id">বিভাগ</label>
                        <select name="division_id" id="division_id" required>
                            <option value="">লোড হচ্ছে…</option>
                        </select>
                    </div>
                    <div class="land-field">
                        <label for="district_id">জেলা</label>
                        <select name="district_id" id="district_id" required>
                            <option value="">— নির্বাচন করুন —</option>
                        </select>
                    </div>
                    <div class="land-field">
                        <label for="upazila_id">উপজেলা</label>
                        <select name="upazila_id" id="upazila_id" required>
                            <option value="">— নির্বাচন করুন —</option>
                        </select>
                    </div>
                    <div class="land-field">
                        <label for="mouja_id">মৌজা</label>
                        <select name="mouja_id" id="mouja_id" required>
                            <option value="">— নির্বাচন করুন —</option>
                        </select>
                    </div>
                    <div class="land-field">
                        <label for="khotian_no">খতিয়ান নং</label>
                        <input type="text" name="khotian_no" id="khotian_no" inputmode="numeric" placeholder="যেমন ৭০" required>
                    </div>
                    <div class="land-field">
                        <label for="holding_no">হোল্ডিং নং (ঐচ্ছিক)</label>
                        <input type="text" name="holding_no" id="holding_no" inputmode="numeric" placeholder="যেমন ৬৭">
                    </div>
                </div>

                <div class="land-actions">
                    <button type="submit" class="land-btn" id="searchBtn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <span id="searchLabel">খতিয়ান অনুসন্ধান</span>
                    </button>
                    <span class="land-hint" id="statusHint" role="status" aria-live="polite">মৌজা + খতিয়ান নং দিন</span>
                </div>
            </form>

            <p class="land-note">
                এটি স্বতন্ত্র টুলস পেজ — সরকারি পোর্টাল নয়। সরকারি কাজে
                <a href="https://land.gov.bd" target="_blank" rel="noopener noreferrer">land.gov.bd</a>
                ব্যবহার করুন।
            </p>
            <?php if (is_admin() && LAND_SESSION_COOKIE === ''): ?>
                <p class="land-note">
                    Admin setup: set <code>LAND_SESSION_COOKIE</code> in the hosting environment to enable live searches.
                    Set <code>LAND_JWT_SECRET</code> to enable owner/land details.
                </p>
            <?php endif; ?>
        </section>

        <section class="land-results" id="resultsSection" aria-labelledby="resultsHeading" hidden>
            <div class="land-results-head">
                <h2 id="resultsHeading">ফলাফল</h2>
                <span id="resultsSummary"></span>
            </div>
            <div id="results" class="land-grid" aria-live="polite"></div>
        </section>

        <p class="land-legal">
            তথ্যের উৎস: <a href="https://land.gov.bd" target="_blank" rel="noopener noreferrer">land.gov.bd</a> ·
            এই পেজ কোনো ফি নেয় না এবং কোনো সরকারি সেবা প্রদান করে না।
        </p>
    </div>
</div>

<script>
(function () {
    'use strict';

    var SELF = <?= json_encode($self, JSON_UNESCAPED_SLASHES) ?>;
    var CSRF = <?= json_encode(csrf_token()) ?>;
    var division = document.getElementById('division_id');
    var district = document.getElementById('district_id');
    var upazila = document.getElementById('upazila_id');
    var mouja = document.getElementById('mouja_id');
    var khotian = document.getElementById('khotian_no');
    var statusHint = document.getElementById('statusHint');
    var searchBtn = document.getElementById('searchBtn');
    var searchLabel = document.getElementById('searchLabel');
    var resultsSection = document.getElementById('resultsSection');
    var resultsBox = document.getElementById('results');
    var resultsHeading = document.getElementById('resultsHeading');
    var resultsSummary = document.getElementById('resultsSummary');

    function esc(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function bn(value) {
        return String(value == null ? '' : value).replace(/[0-9]/g, function (d) {
            return '০১২৩৪৫৬৭৮৯'[d];
        });
    }

    function setHint(text, isError) {
        statusHint.textContent = text;
        statusHint.classList.toggle('is-error', !!isError);
    }

    function setOptions(select, items, label) {
        select.innerHTML = '<option value="">— নির্বাচন করুন —</option>';
        items.forEach(function (item) {
            var option = document.createElement('option');
            option.value = item.id;
            option.textContent = label(item);
            select.appendChild(option);
        });
        select.disabled = items.length === 0;
    }

    async function getJSON(url) {
        var response = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!response.ok) throw new Error('HTTP ' + response.status);
        return response.json();
    }

    async function loadDivisions() {
        try {
            var json = await getJSON(SELF + '?action=divisions');
            setOptions(division, json.data || [], function (item) {
                return item.name_bn || item.name_en || '';
            });
        } catch (error) {
            setOptions(division, [], function () { return ''; });
            setHint('বিভাগ লোড করা যায়নি: ' + error.message, true);
        }
    }

    async function loadDistricts() {
        setOptions(district, [], function () { return ''; });
        setOptions(upazila, [], function () { return ''; });
        setOptions(mouja, [], function () { return ''; });
        if (!division.value) return;
        try {
            var json = await getJSON(SELF + '?action=districts&division_id=' + encodeURIComponent(division.value));
            setOptions(district, json.data || [], function (item) {
                return item.name_bn || item.name_en || '';
            });
        } catch (error) {
            setHint('জেলা লোড করা যায়নি: ' + error.message, true);
        }
    }

    async function loadUpazilas() {
        setOptions(upazila, [], function () { return ''; });
        setOptions(mouja, [], function () { return ''; });
        if (!district.value) return;
        try {
            var json = await getJSON(SELF + '?action=upazilas&district_id=' + encodeURIComponent(district.value));
            setOptions(upazila, json.data || [], function (item) {
                return item.name_bd || item.name_bn || item.name_en || '';
            });
        } catch (error) {
            setHint('উপজেলা লোড করা যায়নি: ' + error.message, true);
        }
    }

    async function loadMoujas() {
        setOptions(mouja, [], function () { return ''; });
        if (!upazila.value) return;
        try {
            var json = await getJSON(SELF + '?action=moujas&upazila_id=' + encodeURIComponent(upazila.value));
            setOptions(mouja, json.data || [], function (item) {
                return (item.name_bd || '') + (item.jl_no ? ' — জেএল ' + item.jl_no : '');
            });
        } catch (error) {
            setHint('মৌজা লোড করা যায়নি: ' + error.message, true);
        }
    }

    function renderError(message) {
        resultsSection.hidden = false;
        resultsHeading.textContent = 'অনুসন্ধান ব্যর্থ';
        resultsSummary.textContent = '';
        resultsBox.innerHTML = '<div class="land-error">' + esc(message || 'অজানা সমস্যা') + '</div>';
    }

    function detailButton(id) {
        return '<button type="button" class="land-btn land-btn-secondary" data-detail="' + esc(id) + '">' +
            '<span>বিস্তারিত দেখুন</span></button>';
    }

    function renderResults(data) {
        resultsSection.hidden = false;
        resultsHeading.textContent = 'ফলাফল';

        if (!data.ok) {
            renderError(data.error);
            return;
        }

        var rows = data.results || [];
        resultsSummary.textContent = (data.total && data.total > rows.length)
            ? 'প্রথম ' + bn(rows.length) + 'টি দেখানো হচ্ছে, মোট ' + bn(data.total) + 'টি'
            : bn(rows.length) + 'টি হোল্ডিং';

        if (!rows.length) {
            resultsBox.innerHTML = '<div class="land-error">কোনো হোল্ডিং পাওয়া যায়নি।</div>';
            return;
        }

        resultsBox.innerHTML = rows.map(function (row) {
            return '<article class="land-result" data-holding="' + esc(row.id) + '">' +
                '<div class="land-result-head">' +
                    '<strong>হোল্ডিং #' + esc(bn(row.holding_no) || '-') + '</strong>' +
                    '<div class="land-chips">' +
                        '<span class="land-chip">খতিয়ান ' + esc(bn(row.khotian_no) || '-') + '</span>' +
                        '<span class="land-chip">মৌজা ' + esc(row.mouja || '-') + '</span>' +
                        '<span class="land-chip">জেএল ' + esc(bn(row.jl_no) || '-') + '</span>' +
                    '</div>' +
                '</div>' +
                (data.detail_enabled ? '<div class="land-detail" hidden></div>' + detailButton(row.id) : '') +
            '</article>';
        }).join('');

        resultsBox.querySelectorAll('[data-detail]').forEach(function (button) {
            button.addEventListener('click', function () {
                loadDetail(button);
            });
        });
    }

    async function loadDetail(button) {
        var card = button.closest('.land-result');
        var panel = card ? card.querySelector('.land-detail') : null;
        var id = button.getAttribute('data-detail');
        if (!panel || !id) return;

        button.disabled = true;
        panel.hidden = false;
        panel.innerHTML = '<p class="land-hint">বিস্তারিত লোড হচ্ছে…</p>';

        try {
            var body = new URLSearchParams();
            body.set('csrf_token', CSRF);
            body.set('holding_id', id);
            var response = await fetch(SELF + '?action=detail', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8', 'Accept': 'application/json' },
                body: body.toString()
            });
            var data = await response.json();
            if (!data.ok) {
                panel.innerHTML = '<div class="land-error">' + esc(data.error || 'বিস্তারিত তথ্য পাওয়া যায়নি।') + '</div>';
                return;
            }

            var html = '';
            if (data.owners && data.owners.length) {
                html += '<h3>মালিকের তথ্য</h3><div class="land-table-wrap"><table class="land-table"><thead><tr>' +
                    '<th>মালিকের নাম</th><th>পিতার নাম</th><th>অংশ</th></tr></thead><tbody>' +
                    data.owners.map(function (owner) {
                        return '<tr><td>' + esc(owner.name) + '</td><td>' + esc(owner.father_name) + '</td><td>' + esc(bn(owner.land_portion)) + '</td></tr>';
                    }).join('') + '</tbody></table></div>';
            }

            if (data.land && data.land.length) {
                html += '<h3>জমির তথ্য</h3><div class="land-table-wrap"><table class="land-table"><thead><tr>' +
                    '<th>দাগ নং</th><th>জমির শ্রেণী</th><th>পরিমাণ (শতক)</th></tr></thead><tbody>' +
                    data.land.map(function (row) {
                        return '<tr><td>' + esc(bn(row.dag_no)) + '</td><td>' + esc(row.land_type_name) + '</td><td>' + esc(bn(row.amount_of_land)) + '</td></tr>';
                    }).join('') + '</tbody></table></div>' +
                    '<p class="land-detail-total">সর্বমোট: <strong>' + esc(bn(data.total_land)) + '</strong> শতক</p>';
            }

            panel.innerHTML = html || '<p class="land-hint">এই হোল্ডিং-এর জন্য কোনো বিস্তারিত তথ্য পাওয়া যায়নি।</p>';
        } catch (error) {
            panel.innerHTML = '<div class="land-error">নেটওয়ার্ক সমস্যা: ' + esc(error.message) + '</div>';
        } finally {
            button.disabled = false;
        }
    }

    document.getElementById('landForm').addEventListener('submit', async function (event) {
        event.preventDefault();

        if (!division.value || !district.value || !upazila.value || !mouja.value || !khotian.value.trim()) {
            setHint('বিভাগ, জেলা, উপজেলা, মৌজা এবং খতিয়ান নং — সব পূরণ করুন।', true);
            return;
        }

        searchBtn.disabled = true;
        searchLabel.textContent = 'অনুসন্ধান চলছে…';
        setHint('অনুসন্ধান চলছে, একটু অপেক্ষা করুন…', false);

        try {
            var response = await fetch(SELF + '?action=search', {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: new FormData(this)
            });
            var data = await response.json();
            renderResults(data);
            setHint(data.ok ? 'সম্পন্ন' : 'ব্যর্থ', !data.ok);
            if (data.ok) resultsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } catch (error) {
            renderError('নেটওয়ার্ক সমস্যা: ' + error.message);
            setHint('ব্যর্থ', true);
        } finally {
            searchBtn.disabled = false;
            searchLabel.textContent = 'খতিয়ান অনুসন্ধান';
        }
    });

    division.addEventListener('change', loadDistricts);
    district.addEventListener('change', loadUpazilas);
    upazila.addEventListener('change', loadMoujas);

    loadDivisions();
})();
</script>
</body>
</html>
