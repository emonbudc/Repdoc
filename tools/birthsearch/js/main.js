(function () {
    'use strict';

    // Utility DOM Selectors
    const $ = (sel, root) => (root || document).querySelector(sel);
    const $$ = (sel, root) => Array.from((root || document).querySelectorAll(sel));

    // Application State
    const state = Object.assign({
        q: '',
        name: '',
        father: '',
        mother: '',
        dob: '',
        gender: '',
        reg_num: '',
        ward: '',
        source_file: '',
        status: '',
        sortBy: 'relevance',
        sortDir: 'asc',
        view: 'table',
        perPage: 50,
        page: 1,
        openId: 0,
        matchMode: 'partial'
    }, window.__INITIAL || {});

    const selectedIds = new Set();
    let currentResults = [];
    let currentRecord = null;
    let verifiedPrintRecord = null;
    let activeTab = 'tabPersonal';
    let suggestTimer = null;
    let searchAbortCtrl = null;

    // DOM Elements Cache
    const els = {
        searchInput: $('#searchInput'),
        searchFieldBox: $('#searchFieldBox'),
        clearSearchBtn: $('#clearSearchBtn'),
        langIndicator: $('#langIndicator'),
        btnRunSearch: $('#btnRunSearch'),
        advFilterToggle: $('#advFilterToggle'),
        advancedFiltersPanel: $('#advancedFiltersPanel'),
        btnResetFilters: $('#btnResetFilters'),
        btnApplyAdvFilters: $('#btnApplyAdvFilters'),
        suggestionsDropdown: $('#suggestionsDropdown'),
        btnExportMenu: $('#btnExportMenu'),
        exportMenuDropdown: $('#exportMenuDropdown'),
        exportCSV: $('#exportCSV'),
        exportJSON: $('#exportJSON'),
        exportPrint: $('#exportPrint'),
        resultsSection: $('#resultsSection'),
        resultsCountReadout: $('#resultsCountReadout'),
        resultsRangeReadout: $('#resultsRangeReadout'),
        activeFilterChips: $('#activeFilterChips'),
        perPageSelect: $('#perPageSelect'),
        viewModeTableBtn: $('#viewModeTableBtn'),
        viewModeCardsBtn: $('#viewModeCardsBtn'),
        bulkActionBanner: $('#bulkActionBanner'),
        bulkSelectedCountText: $('#bulkSelectedCountText'),
        btnBulkCopyIds: $('#btnBulkCopyIds'),
        btnBulkExport: $('#btnBulkExport'),
        btnBulkClear: $('#btnBulkClear'),
        loadingSkeletons: $('#loadingSkeletons'),
        noResultsState: $('#noResultsState'),
        resultsErrorState: $('#resultsErrorState'),
        btnRetrySearch: $('#btnRetrySearch'),
        tableViewWrapper: $('#tableViewWrapper'),
        dataTable: $('#dataTable'),
        dataTableBody: $('#dataTableBody'),
        selectAllCheckbox: $('#selectAllCheckbox'),
        cardsViewWrapper: $('#cardsViewWrapper'),
        paginationContainer: $('#paginationContainer'),
        paginationRangeText: $('#paginationRangeText'),
        paginationInner: $('#paginationInner'),
        recordDetailModal: $('#recordDetailModal'),
        modalCandidateName: $('#modalCandidateName'),
        modalCandidateSub: $('#modalCandidateSub'),
        modalBodyContent: $('#modalBodyContent'),
        btnModalVerifyTrigger: $('#btnModalVerifyTrigger'),
        btnPrintModalCert: $('#btnPrintModalCert'),
        btnCopyRecordJSON: $('#btnCopyRecordJSON'),
        verifyModal: $('#verifyModal'),
        verifyCandName: $('#verifyCandName'),
        verifyCandBrn: $('#verifyCandBrn'),
        verifyCandDob: $('#verifyCandDob'),
        verifyRecordId: $('#verifyRecordId'),
        verifyUbrnInput: $('#verifyUbrnInput'),
        verifyDobInput: $('#verifyDobInput'),
        verifyCaptchaToken: $('#verifyCaptchaToken'),
        verifyCaptchaAnswer: $('#verifyCaptchaAnswer'),
        captchaImgContainer: $('#captchaImgContainer'),
        btnRefreshCaptcha: $('#btnRefreshCaptcha'),
        btnVerifyModalPrint: $('#btnVerifyModalPrint'),
        btnSubmitVerification: $('#btnSubmitVerification'),
        verifyStatusBox: $('#verifyStatusBox'),
        toastContainer: $('#toastContainer'),
    };

    // =========================================================================
    // Theme Management (Light / Dark)
    // =========================================================================
    // =========================================================================
    // Search input validation (BRN = 17 digits, DOB = real calendar date)
    // Uses ux:error-summary pattern: inline field errors + focusable summary.
    // =========================================================================
    const BN_DIGITS = '০১২৩৪৫৬৭৮৯';
    function toEnDigits(str) {
        return String(str || '').replace(/[০-৯]/g, ch => String(BN_DIGITS.indexOf(ch)));
    }

    function validateSearchFilters(f) {
        const errors = [];
        const brnRaw = toEnDigits(f.reg_num).replace(/[\s-]/g, '');
        if (f.reg_num && !/^\d{17}$/.test(brnRaw)) {
            errors.push({
                fieldId: 'fRegNum',
                message: 'জন্ম নম্বর ১৭ সংখ্যার হতে হবে।'
            });
        }
        if (f.dob) {
            const dobNorm = toEnDigits(f.dob).replace(/\./g, '/').replace(/-/g, '/');
            const dmy = dobNorm.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
            const ymd = dobNorm.match(/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/);
            let ok = false;
            if (dmy || ymd) {
                const d = dmy ? { y: +dmy[3], m: +dmy[2], d: +dmy[1] } : { y: +ymd[1], m: +ymd[2], d: +ymd[3] };
                if (d.y >= 1900 && d.y <= 2100 && d.m >= 1 && d.m <= 12 && d.d >= 1 && d.d <= 31) ok = true;
            }
            if (!ok) {
                errors.push({
                    fieldId: 'fDob',
                    message: 'তারিখ ঠিক নয় (দিন/মাস/বছর)।'
                });
            }
        }
        return errors;
    }

    function showSearchErrors(errors) {
        const box = document.getElementById('formErrorSummary');
        ['fRegNum', 'fDob'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.removeAttribute('aria-invalid');
        });
        if (!errors.length) {
            if (box) box.hidden = true;
            return true;
        }
        errors.forEach(e => {
            const el = document.getElementById(e.fieldId);
            if (el) el.setAttribute('aria-invalid', 'true');
        });
        if (box) {
            box.innerHTML = '<strong>শর্তে সমস্যা আছে</strong><ul>' +
                errors.map(e => `<li><a href="#${e.fieldId}">${escapeHtml(e.message)}</a></li>`).join('') +
                '</ul>';
            box.hidden = false;
            box.focus();
        }
        return false;
    }

    // =========================================================================
    // Toast Notification Dispatcher
    // =========================================================================
    function showToast(title, text, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast-item ${type}`;
        toast.setAttribute('role', type === 'error' ? 'alert' : 'status');

        let iconSvg = '';
        if (type === 'success') {
            iconSvg = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>';
        } else if (type === 'info') {
            iconSvg = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>';
        } else if (type === 'warning') {
            iconSvg = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>';
        } else {
            iconSvg = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>';
        }

        toast.innerHTML = `
            <div class="toast-icon-box">${iconSvg}</div>
            <div class="toast-message-box">
                <div class="toast-title">${escapeHtml(title)}</div>
                <div class="toast-text">${escapeHtml(text || '')}</div>
            </div>
        `;

        els.toastContainer.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(40px)';
            setTimeout(() => toast.remove(), 300);
        }, 3800);
    }

    // =========================================================================
    // Statistics Counter Animation
    // =========================================================================
    function animateCounters() {
        $$('[data-val]').forEach(el => {
            const target = parseInt(el.getAttribute('data-val'), 10) || 0;
            if (target <= 0) return;
            const duration = 1200;
            const start = 0;
            const startTime = performance.now();

            function update(now) {
                const elapsed = now - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const ease = 1 - Math.pow(1 - progress, 3);
                const current = Math.floor(start + (target - start) * ease);
                el.textContent = current.toLocaleString('en-US');
                if (progress < 1) {
                    requestAnimationFrame(update);
                } else {
                    el.textContent = target.toLocaleString('en-US');
                }
            }
            requestAnimationFrame(update);
        });
    }

    // =========================================================================
    // Language & Input Detection
    // =========================================================================
    function detectLang(text) {
        for (let i = 0; i < text.length; i++) {
            const cp = text.codePointAt(i);
            if (cp >= 0x0980 && cp <= 0x09FF) return 'bangla';
        }
        return 'english';
    }

    function updateSearchInputTools() {
        const val = els.searchInput.value.trim();
        els.clearSearchBtn.hidden = val.length === 0;
        if (val.length > 0) {
            const isBn = detectLang(val) === 'bangla';
            els.langIndicator.textContent = isBn ? 'BN' : 'EN';
            els.langIndicator.hidden = false;
        } else {
            els.langIndicator.hidden = true;
        }
    }

    // =========================================================================
    // Auto-Suggestions Handling
    // =========================================================================
    function fetchSuggestions(query) {
        if (!query || query.length < 2) {
            hideSuggestions();
            return;
        }

        fetch(`api/suggest.php?q=${encodeURIComponent(query)}&limit=8`)
            .then(res => res.json())
            .then(data => {
                if (els.searchInput.value.trim() !== query) return;
                renderSuggestions(data.suggestions || []);
            })
            .catch(() => hideSuggestions());
    }

    function renderSuggestions(items) {
        if (!items.length) {
            hideSuggestions();
            return;
        }

        els.suggestionsDropdown.innerHTML = items.map(item => `
            <button type="button" class="suggestion-row" role="option" data-name="${escapeHtml(item.name)}" data-id="${item.id || 0}">
                <div class="suggestion-left">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <div>
                        <div class="suggestion-name">${escapeHtml(item.name)} ${item.name_en ? `<span style="font-size:0.76rem; color:var(--primary); font-weight:600;">(${escapeHtml(item.name_en)})</span>` : ''}</div>
                        ${item.father ? `<div class="suggestion-meta">পিতা: ${escapeHtml(item.father)} ${item.ward ? `• ওয়ার্ড: ${escapeHtml(item.ward)}` : ''}</div>` : ''}
                    </div>
                </div>
                ${item.reg_num ? `<span class="brn-badge mono">${escapeHtml(item.reg_num)}</span>` : ''}
            </button>
        `).join('');

        els.suggestionsDropdown.hidden = false;
        els.searchInput.setAttribute('aria-expanded', 'true');

        $$('.suggestion-row', els.suggestionsDropdown).forEach(btn => {
            btn.addEventListener('click', function () {
                const name = btn.getAttribute('data-name');
                els.searchInput.value = name;
                updateSearchInputTools();
                hideSuggestions();
                runSearch(1);
            });
        });
    }

    function hideSuggestions() {
        els.suggestionsDropdown.hidden = true;
        els.suggestionsDropdown.innerHTML = '';
        if (els.searchInput) els.searchInput.setAttribute('aria-expanded', 'false');
    }

    // =========================================================================
    // Search Execution & Data Fetching
    // =========================================================================
    function readAllFormFilters() {
        return {
            name: $('#fName') ? $('#fName').value.trim() : '',
            father: $('#fFather') ? $('#fFather').value.trim() : '',
            mother: $('#fMother') ? $('#fMother').value.trim() : '',
            dob: $('#fDob') ? $('#fDob').value.trim() : '',
            gender: $('#fGender') ? $('#fGender').value : '',
            reg_num: $('#fRegNum') ? $('#fRegNum').value.trim() : '',
            ward: $('#fWard') ? $('#fWard').value.trim() : '',
            status: $('#fStatus') ? $('#fStatus').value : '',
        };
    }

    function syncFormInputsFromState() {
        els.searchInput.value = state.q || '';
        if ($('#fName')) $('#fName').value = state.name || '';
        if ($('#fFather')) $('#fFather').value = state.father || '';
        if ($('#fMother')) $('#fMother').value = state.mother || '';
        if ($('#fDob')) $('#fDob').value = state.dob || '';
        if ($('#fGender')) $('#fGender').value = state.gender || '';
        if ($('#fRegNum')) $('#fRegNum').value = state.reg_num || '';
        if ($('#fWard')) $('#fWard').value = state.ward || '';
        if ($('#fStatus')) $('#fStatus').value = state.status || '';
        if (els.perPageSelect) els.perPageSelect.value = String(state.perPage || 50);

        updateSearchInputTools();

        const hasAdv = state.name || state.father || state.mother || state.dob || state.gender || state.reg_num || state.ward || state.status;
        if (hasAdv) {
            els.advancedFiltersPanel.hidden = false;
            els.advFilterToggle.setAttribute('aria-expanded', 'true');
        }
    }

    function updateUrlState() {
        const params = new URLSearchParams();
        if (state.q) params.set('q', state.q);
        if (state.name) params.set('name', state.name);
        if (state.father) params.set('father', state.father);
        if (state.mother) params.set('mother', state.mother);
        if (state.dob) params.set('dob', state.dob);
        if (state.gender) params.set('gender', state.gender);
        if (state.reg_num) params.set('reg_num', state.reg_num);
        if (state.ward) params.set('ward', state.ward);
        if (state.status) params.set('status', state.status);
        if (state.sortBy && state.sortBy !== 'relevance') params.set('sort_by', state.sortBy);
        if (state.sortDir && state.sortDir !== 'asc') params.set('sort_dir', state.sortDir);
        if (state.view && state.view !== 'table') params.set('view', state.view);
        if (state.page > 1) params.set('p', state.page);

        const qs = params.toString();
        const newUrl = window.location.pathname + (qs ? '?' + qs : '');
        history.pushState({}, '', newUrl);
    }

    function hasActiveFilters() {
        return state.name || state.father || state.mother || state.dob || state.gender || state.reg_num || state.ward || state.status;
    }

    function runSearch(page = 1) {
        state.page = page;
        state.q = els.searchInput.value.trim();
        Object.assign(state, readAllFormFilters());

        const hasInput = state.q || state.name || state.father || state.mother || state.dob || state.gender || state.reg_num || state.ward || state.status || state.showAll;

        if (!showSearchErrors(validateSearchFilters(state))) {
            return;
        }

        updateUrlState();
        hideSuggestions();

        if (!hasInput) {
            showSearchErrors([]);
            els.loadingSkeletons.hidden = true;
            els.noResultsState.hidden = true;
            if (state.view === 'table') {
                els.tableViewWrapper.hidden = true;
            } else {
                els.cardsViewWrapper.hidden = true;
            }
            els.paginationContainer.hidden = true;
            els.resultsSection.querySelector('.results-header-bar').hidden = true;
            els.searchActionsGroup = els.searchActionsGroup || document.querySelector('.search-actions-group');
            if (els.searchActionsGroup) els.searchActionsGroup.hidden = true;
            els.activeFilterChips.innerHTML = '';
            currentResults = [];
            return;
        }

        els.resultsSection.querySelector('.results-header-bar').hidden = false;
        els.searchActionsGroup = els.searchActionsGroup || document.querySelector('.search-actions-group');
        if (els.searchActionsGroup) els.searchActionsGroup.hidden = false;
        els.loadingSkeletons.hidden = false;
        els.noResultsState.hidden = true;
        if (els.resultsErrorState) els.resultsErrorState.hidden = true;
        els.resultsSection.setAttribute('aria-busy', 'true');
        if (els.btnRunSearch) { els.btnRunSearch.disabled = true; els.btnRunSearch.setAttribute('aria-busy', 'true'); }
        if (state.view === 'table') {
            els.tableViewWrapper.hidden = true;
        } else {
            els.cardsViewWrapper.hidden = true;
        }
        els.paginationContainer.hidden = true;

        if (searchAbortCtrl) {
            searchAbortCtrl.abort();
        }
        searchAbortCtrl = new AbortController();

        const params = new URLSearchParams({
            q: state.q,
            name: state.name,
            father: state.father,
            mother: state.mother,
            dob: state.dob,
            gender: state.gender,
            reg_num: state.reg_num,
            ward: state.ward,
            status: state.status,
            sort_by: state.sortBy,
            sort_dir: state.sortDir,
            p: state.page,
            per_page: state.perPage,
            match_mode: state.matchMode
        });

        fetch(`api/search.php?${params.toString()}`, { signal: searchAbortCtrl.signal })
            .then(res => res.json())
            .then(data => {
                els.loadingSkeletons.hidden = true;
                els.resultsSection.setAttribute('aria-busy', 'false');
                if (els.btnRunSearch) { els.btnRunSearch.disabled = false; els.btnRunSearch.removeAttribute('aria-busy'); }
                currentResults = data.results || [];
                renderResults(currentResults, data.total || 0, data.page || 1, data.pages || 1, data.per_page || 50);
                updateActiveFilterChips();
            })
            .catch(err => {
                if (err.name === 'AbortError') return;
                els.loadingSkeletons.hidden = true;
                els.resultsSection.setAttribute('aria-busy', 'false');
                if (els.btnRunSearch) { els.btnRunSearch.disabled = false; els.btnRunSearch.removeAttribute('aria-busy'); }
                if (els.resultsErrorState) els.resultsErrorState.hidden = false;
                showToast('অনুসন্ধানে ত্রুটি', 'সংযোগ ব্যর্থ হয়েছে', 'error');
            });
    }

    // =========================================================================
    // Active Filter Chips & Result Readout
    // =========================================================================
    function updateActiveFilterChips() {
        const chips = [];
        if (state.q) chips.push({ key: 'q', label: `অনুসন্ধান: "${state.q}"` });
        if (state.name) chips.push({ key: 'name', label: `নাম: ${state.name}` });
        if (state.reg_num) chips.push({ key: 'reg_num', label: `নিবন্ধন: ${state.reg_num}` });
        if (state.father) chips.push({ key: 'father', label: `পিতা: ${state.father}` });
        if (state.mother) chips.push({ key: 'mother', label: `মাতা: ${state.mother}` });
        if (state.dob) chips.push({ key: 'dob', label: `জন্ম: ${state.dob}` });
        if (state.gender) chips.push({ key: 'gender', label: `লিঙ্গ: ${state.gender === 'female' ? 'মহিলা' : 'পুরুষ'}` });
        if (state.ward) chips.push({ key: 'ward', label: `ওয়ার্ড: ${state.ward}` });
        if (state.status) chips.push({ key: 'status', label: `স্ট্যাটাস: ${state.status}` });

        els.activeFilterChips.innerHTML = chips.map(c => `
            <span class="filter-chip">
                <span>${escapeHtml(c.label)}</span>
                <button type="button" class="filter-chip-remove" data-clear-key="${c.key}" title="ফিল্টার মুছুন" aria-label="ফিল্টার মুছুন: ${escapeHtml(c.label)}"><svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </span>
        `).join('');

        $$('.filter-chip-remove', els.activeFilterChips).forEach(btn => {
            btn.addEventListener('click', function () {
                const key = btn.getAttribute('data-clear-key');
                if (key === 'q') els.searchInput.value = '';
                if (key === 'name' && $('#fName')) $('#fName').value = '';
                if (key === 'reg_num' && $('#fRegNum')) $('#fRegNum').value = '';
                if (key === 'father' && $('#fFather')) $('#fFather').value = '';
                if (key === 'mother' && $('#fMother')) $('#fMother').value = '';
                if (key === 'dob' && $('#fDob')) $('#fDob').value = '';
                if (key === 'gender' && $('#fGender')) $('#fGender').value = '';
                if (key === 'ward' && $('#fWard')) $('#fWard').value = '';
                if (key === 'status' && $('#fStatus')) $('#fStatus').value = '';
                runSearch(1);
            });
        });
    }

    // =========================================================================
    // Rendering Results (Table and Cards with Bilingual Fields)
    // =========================================================================
    function renderResults(results, total, page, pages, perPage) {
        if (!results.length) {
            els.noResultsState.hidden = false;
            els.tableViewWrapper.hidden = true;
            els.cardsViewWrapper.hidden = true;
            els.paginationContainer.hidden = true;
            els.resultsCountReadout.textContent = 'কোনো রেকর্ড নেই';
            els.resultsRangeReadout.textContent = '';
            return;
        }

        els.noResultsState.hidden = true;
        const from = (page - 1) * perPage + 1;
        const to = Math.min(page * perPage, total);
        els.resultsCountReadout.textContent = `${total.toLocaleString('en-US')}টি রেকর্ড`;
        els.resultsRangeReadout.textContent = `(${from}–${to})`;

        if (state.view === 'table') {
            renderTableRows(results);
            els.tableViewWrapper.hidden = false;
            els.cardsViewWrapper.hidden = true;
        } else {
            renderCardItems(results);
            els.cardsViewWrapper.hidden = false;
            els.tableViewWrapper.hidden = true;
        }

        renderPagination(total, page, pages, perPage);
        updateBulkUI();
    }

    function renderTableRows(results) {
        els.dataTableBody.innerHTML = results.map(r => {
            const isSelected = selectedIds.has(r.id);
            const genderClass = (r.gender || '').toLowerCase();
            const statusClass = (r.status || 'pending').toLowerCase();
            const isVerified = (statusClass === 'verified');

            const fatherEnDisplay = r.father_name_en ? ` (${escapeHtml(r.father_name_en)})` : '';
            const motherEnDisplay = r.mother_name_en ? ` (${escapeHtml(r.mother_name_en)})` : '';

            return `
            <tr class="${isSelected ? 'selected' : ''}" data-row-id="${r.id}">
                <td class="cell-checkbox" data-label="নির্বাচন">
                    <input type="checkbox" class="custom-checkbox row-select-checkbox" data-id="${r.id}" ${isSelected ? 'checked' : ''}>
                </td>
                <td data-label="নিবন্ধন নম্বর">
                    <div class="brn-badge mono">
                        <span>${escapeHtml(r.registration_number || '—')}</span>
                        <button type="button" class="copy-brn-btn" data-copy-val="${escapeHtml(r.registration_number)}" title="নম্বর কপি করুন">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                        </button>
                    </div>
                </td>
                <td data-label="নাম (বাংলা ও English)">
                    <div class="candidate-cell">
                        <span class="candidate-name">${r.name_hl || escapeHtml(r.name)}</span>
                        ${r.name_en ? `<span class="candidate-name-en">${r.name_en_hl || escapeHtml(r.name_en)}</span>` : ''}
                        <span class="candidate-parentage">পিতা: ${r.father_hl || escapeHtml(r.father_name || '—')}${fatherEnDisplay} • মাতা: ${r.mother_hl || escapeHtml(r.mother_name || '—')}${motherEnDisplay}</span>
                    </div>
                </td>
                <td data-label="জন্ম তারিখ">
                    <div>
                        <span class="mono" style="font-weight:600;">${escapeHtml(r.date_of_birth || '—')}</span>
                        ${r.age ? `<span class="age-pill">${escapeHtml(r.age)}</span>` : ''}
                    </div>
                </td>
                <td data-label="লিঙ্গ">
                    <span class="gender-tag ${genderClass}">${escapeHtml(r.gender_display || r.gender)}</span>
                </td>
                <td data-label="স্থায়ী ঠিকানা">
                    <div style="font-size:0.84rem;">
                        <b>${escapeHtml(r.permanent_address || '—')}</b>
                        <div style="color:var(--text-muted); font-size:0.76rem;">${r.ward ? `ওয়ার্ড: ${escapeHtml(r.ward)}` : ''}</div>
                    </div>
                </td>
                <td data-label="স্ট্যাটাস">
                    <span class="badge-status ${statusClass}" id="statusBadge-${r.id}">
                        <span style="width:6px; height:6px; border-radius:50%; background:currentColor;"></span>
                        <span>${isVerified ? 'যাচাইকৃত' : 'যাচাই বাকি'}</span>
                    </span>
                </td>
                <td data-label="অ্যাকশন" style="text-align: right;">
                    <div class="row-actions" style="justify-content: flex-end;">
                        <!-- Primary Verify Button -->
                        <button type="button" class="btn-verify-action ${isVerified ? 'is-verified' : ''} btn-trigger-verify" data-id="${r.id}" title="${isVerified ? 'Verified - re-check' : 'Verify record'}">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                            <span>${isVerified ? 'পুনঃযাচাই' : 'যাচাই করুন'}</span>
                        </button>
                        <button type="button" class="btn-action-icon btn-open-detail" data-id="${r.id}" title="বিস্তারিত প্রোফাইল">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                        <button type="button" class="btn-action-icon btn-quick-cert" data-id="${r.id}" title="দ্বিভাষিক সনদ প্রিভিউ">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                        </button>
                    </div>
                </td>
            </tr>
            `;
        }).join('');

        attachRowEvents();
    }

    function renderCardItems(results) {
        els.cardsViewWrapper.innerHTML = results.map(r => {
            const isSelected = selectedIds.has(r.id);
            const genderClass = (r.gender || '').toLowerCase();
            const statusClass = (r.status || 'pending').toLowerCase();
            const isVerified = (statusClass === 'verified');

            return `
            <article class="record-card-item ${isSelected ? 'selected' : ''}" data-card-id="${r.id}">
                <div class="card-topbar">
                    <div class="card-topbar-left">
                        <input type="checkbox" class="custom-checkbox row-select-checkbox" data-id="${r.id}" ${isSelected ? 'checked' : ''}>
                        <div class="card-name-block">
                            <h3>${r.name_hl || escapeHtml(r.name)}</h3>
                            ${r.name_en ? `<p class="mono">${r.name_en_hl || escapeHtml(r.name_en)}</p>` : ''}
                        </div>
                    </div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span class="gender-tag ${genderClass}">${escapeHtml(r.gender_display || r.gender)}</span>
                        <span class="badge-status ${statusClass}" id="cardStatusBadge-${r.id}">${isVerified ? 'যাচাইকৃত' : 'যাচাই বাকি'}</span>
                    </div>
                </div>

                <div class="card-info-grid">
                    <div class="card-info-item">
                        <span class="card-info-label">পিতার নাম</span>
                        <span class="card-info-val">${r.father_hl || escapeHtml(r.father_name || '—')} ${r.father_name_en ? `<small style="display:block; color:var(--text-muted);">(${escapeHtml(r.father_name_en)})</small>` : ''}</span>
                    </div>
                    <div class="card-info-item">
                        <span class="card-info-label">মাতার নাম</span>
                        <span class="card-info-val">${r.mother_hl || escapeHtml(r.mother_name || '—')} ${r.mother_name_en ? `<small style="display:block; color:var(--text-muted);">(${escapeHtml(r.mother_name_en)})</small>` : ''}</span>
                    </div>
                    <div class="card-info-item">
                        <span class="card-info-label">জন্ম তারিখ</span>
                        <span class="card-info-val mono">${escapeHtml(r.date_of_birth || '—')} ${r.age ? `(${r.age})` : ''}</span>
                    </div>
                    <div class="card-info-item">
                        <span class="card-info-label">স্থায়ী ঠিকানা</span>
                        <span class="card-info-val">${escapeHtml(r.permanent_address || '—')}</span>
                    </div>
                    ${r.ward ? `<div class="card-info-item"><span class="card-info-label">ওয়ার্ড</span><span class="card-info-val">${escapeHtml(r.ward)}</span></div>` : ''}
                </div>

                <div class="card-bottom-bar">
                    <div class="brn-badge mono">
                        <span>${escapeHtml(r.registration_number || '—')}</span>
                        <button type="button" class="copy-brn-btn" data-copy-val="${escapeHtml(r.registration_number)}" title="নম্বর কপি করুন">
                            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                        </button>
                    </div>

                    <div style="display:flex; gap:6px;">
                        <button type="button" class="btn-verify-action btn-trigger-verify" data-id="${r.id}">
                            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            <span>${isVerified ? 'পুনঃযাচাই' : 'যাচাই'}</span>
                        </button>
                        <button type="button" class="btn-secondary btn-open-detail" data-id="${r.id}" style="padding:6px 10px; font-size:0.8rem;">
                            <span>বিস্তারিত</span>
                        </button>
                        <button type="button" class="btn-primary btn-quick-cert" data-id="${r.id}" style="padding:6px 10px; font-size:0.8rem;">
                            <span>সনদ</span>
                        </button>
                    </div>
                </div>
            </article>
            `;
        }).join('');

        attachRowEvents();
    }

    function attachRowEvents() {
        // Checkboxes
        $$('.row-select-checkbox').forEach(cb => {
            cb.addEventListener('change', function () {
                const id = parseInt(cb.getAttribute('data-id'), 10);
                if (cb.checked) {
                    selectedIds.add(id);
                } else {
                    selectedIds.delete(id);
                }
                updateBulkUI();
            });
        });

        // Copy BRN button
        $$('.copy-brn-btn').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                const val = btn.getAttribute('data-copy-val');
                if (val) {
                    navigator.clipboard.writeText(val).then(() => {
                        showToast('কপি সম্পন্ন', `নম্বর কপি হয়েছে`, 'success');
                    });
                }
            });
        });

        // Trigger Verification Modal
        $$('.btn-trigger-verify').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                const id = parseInt(btn.getAttribute('data-id'), 10);
                openVerifyModal(id);
            });
        });

        // Open Detail Modal
        $$('.btn-open-detail').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                const id = parseInt(btn.getAttribute('data-id'), 10);
                openRecordModal(id, 'tabPersonal');
            });
        });

        // Quick Cert Modal
        $$('.btn-quick-cert').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                const id = parseInt(btn.getAttribute('data-id'), 10);
                openRecordModal(id, 'tabCertificate');
            });
        });
    }

    // =========================================================================
    // Live Captcha Verification Modal & API Handler
    // =========================================================================
    function openVerifyModal(recordId) {
        const rec = currentResults.find(r => r.id === recordId) || (currentRecord && currentRecord.id === recordId ? currentRecord : null);
        if (!rec) return;

        els.verifyRecordId.value = recordId;
        els.verifyCandName.textContent = rec.name + (rec.name_en ? ` (${rec.name_en})` : '');
        els.verifyCandBrn.textContent = rec.registration_number || '—';
        if (els.verifyCandDob) els.verifyCandDob.textContent = rec.date_of_birth || '—';
        els.verifyUbrnInput.value = rec.registration_number || '';
        
        // Convert DOB to YYYY-MM-DD
        els.verifyDobInput.value = formatToIsoDate(rec.date_of_birth || '');
        els.verifyCaptchaAnswer.value = '';
        els.verifyStatusBox.hidden = true;
        verifiedPrintRecord = null;
        if (els.btnVerifyModalPrint) els.btnVerifyModalPrint.disabled = true;

        els.verifyModal.hidden = false;
        document.body.classList.add('modal-open');

        loadCaptcha();
    }

    function closeVerifyModal() {
        els.verifyModal.hidden = true;
        if (els.recordDetailModal.hidden) {
            document.body.classList.remove('modal-open');
        }
    }

    function loadCaptcha() {
        els.captchaImgContainer.innerHTML = `
            <div class="spinner" style="width:20px; height:20px; border-width:2px;"></div>
            <span style="font-size:0.78rem; color:var(--text-muted);">ক্যাপচা আনা হচ্ছে...</span>
        `;
        els.verifyCaptchaToken.value = '';
        els.verifyCaptchaAnswer.value = '';

        fetch('api/captcha.php')
            .then(res => res.json())
            .then(data => {
                if (data.ok && data.captcha_base64) {
                    const mime = data.captcha_mime || 'image/png';
                    els.captchaImgContainer.innerHTML = `<img id="currentCaptchaImg" src="data:${mime};base64,${data.captcha_base64}" alt="BDRIS Captcha" style="max-height:46px;">`;
                    els.verifyCaptchaToken.value = data.captcha_token || '';
                    
                    autoSolveCaptcha(data.captcha_base64, mime);
                    
                    setTimeout(() => els.verifyCaptchaAnswer.focus(), 150);
                } else {
                    els.captchaImgContainer.innerHTML = `<span style="color:var(--accent-rose); font-size:0.78rem;">ক্যাপচা লোড ব্যর্থ হয়েছে।</span>`;
                }
            })
            .catch(() => {
                els.captchaImgContainer.innerHTML = `<span style="color:var(--accent-rose); font-size:0.78rem;">সার্ভার সংযোগ বিচ্ছিন্ন।</span>`;
            });
    }

    function autoSolveCaptcha(base64Image, mimeType) {
        const solveIndicator = document.createElement('div');
        solveIndicator.style.cssText = 'margin-top:8px; font-size:0.75rem; color:var(--primary); display:flex; align-items:center; gap:6px;';
        solveIndicator.innerHTML = `
            <div class="spinner" style="width:14px; height:14px; border-width:2px;"></div>
            <span>ক্যাপচা সমাধান করা হচ্ছে...</span>
        `;
        els.captchaImgContainer.appendChild(solveIndicator);

        fetch('api/captcha_solve.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                captcha_base64: base64Image,
                captcha_mime: mimeType
            })
        })
        .then(res => res.json())
        .then(data => {
            solveIndicator.remove();
            if (data.ok && data.answer) {
                els.verifyCaptchaAnswer.value = data.answer;
                showVerifyStatus(`ক্যাপচা সলভ করা হয়েছে: ${data.answer}`, 'success');
            } else {
                showVerifyStatus('ছবি দেখে উত্তর লিখুন।', 'warning');
            }
        })
        .catch(() => {
            solveIndicator.remove();
            showVerifyStatus('ছবি দেখে উত্তর লিখুন।', 'warning');
        });
    }

    function submitVerification() {
        const recordId = parseInt(els.verifyRecordId.value, 10);
        const ubrn = els.verifyUbrnInput.value.trim();
        const birthDate = els.verifyDobInput.value.trim();
        const captchaAnswer = els.verifyCaptchaAnswer.value.trim();
        const captchaToken = els.verifyCaptchaToken.value.trim();

        if (!ubrn || ubrn.length < 10) {
            showVerifyStatus('সঠিক জন্ম নিবন্ধন নম্বর দিন', 'error');
            els.verifyUbrnInput.focus();
            return;
        }
        if (!birthDate) {
            showVerifyStatus('জন্ম তারিখ দিন (YYYY-MM-DD)', 'error');
            els.verifyDobInput.focus();
            return;
        }
        if (!captchaAnswer) {
            showVerifyStatus('ক্যাপচা উত্তর লিখুন', 'error');
            els.verifyCaptchaAnswer.focus();
            return;
        }

        showVerifyStatus('বিডিআরআইএস সার্ভারে সংযোগ স্থাপন ও ডাটা যাচাই চলছে...', 'loading');
        els.btnSubmitVerification.disabled = true;

        const formData = new FormData();
        formData.append('record_id', recordId);
        formData.append('ubrn', ubrn);
        formData.append('birth_date', birthDate);
        formData.append('captcha_answer', captchaAnswer);
        formData.append('captcha_token', captchaToken);

        fetch('api/verify.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            // Print exact response for debug (console + UI)
            console.log('Exact verify response (debug):', data);
            console.log(JSON.stringify(data, null, 2));
            const _debugHtml = `<details style="margin-top:10px;" open><summary style="cursor:pointer;font-size:0.75rem;color:var(--text-muted);">🔍 Debug: Exact server response (for copy)</summary><pre style="max-height:180px;overflow:auto;font-size:0.68rem;background:var(--bg-subtle);border:1px solid var(--border-color);padding:8px;border-radius:6px;margin-top:6px;white-space:pre-wrap;word-break:break-all;">${escapeHtml(JSON.stringify(data, null, 2))}</pre></details>`;
            els.btnSubmitVerification.disabled = false;
            if (data.ok && data.record) {
                showVerifyStatus('যাচাই সফল — তথ্য আপডেট হয়েছে।', 'success');
                showToast('যাচাইকরণ সম্পন্ন!', `${data.record.name} আপডেট হয়েছে।`, 'success');

                // Update local dataset
                const idx = currentResults.findIndex(r => r.id === recordId);
                if (idx !== -1) {
                    currentResults[idx] = Object.assign(currentResults[idx], data.record, { status: 'verified' });
                }
                if (currentRecord && currentRecord.id === recordId) {
                    currentRecord = Object.assign(currentRecord, data.record, { status: 'verified' });
                    renderModalTabContent();
                } else if (!currentRecord) {
                    // If detail modal not open, set currentRecord to verified record for print handling
                    currentRecord = Object.assign({}, data.record, { status: 'verified', id: recordId });
                }
                // Ensure print button becomes visible in detail modal
                updatePrintButtonVisibility();

                // Refresh views dynamically
                if (state.view === 'table') {
                    renderTableRows(currentResults);
                } else {
                    renderCardItems(currentResults);
                }

                // Enable footer print button (verify modal)
                verifiedPrintRecord = (currentResults.find(r => r.id === recordId) || data.record);
                if (els.btnVerifyModalPrint) els.btnVerifyModalPrint.disabled = false;
                // Do NOT auto-close verify modal so user can click print; close only after print or manual close
            } else {
                showVerifyStatus(data.error || 'যাচাই ব্যর্থ — ক্যাপচা দেখুন।', 'error');
                els.verifyStatusBox.innerHTML += _debugHtml;
                loadCaptcha();
            }
        })
        .catch(err => {
            els.btnSubmitVerification.disabled = false;
            showVerifyStatus('সংযোগ ত্রুটি হয়েছে।', 'error');
            loadCaptcha();
        });
    }

    function showVerifyStatus(msg, type = 'info') {
        els.verifyStatusBox.className = `verify-status-box ${type}`;
        // Support HTML for debug (contains <details>), else plain text
        if (/<[a-z][\s\S]*>/i.test(msg)) {
            els.verifyStatusBox.innerHTML = msg;
        } else {
            els.verifyStatusBox.textContent = msg;
        }
        els.verifyStatusBox.hidden = false;
    }

    function formatToIsoDate(d) {
        if (!d) return '';
        let s = toEnglishDigitsJS ? toEnglishDigitsJS(d) : d;
        // Extract digits - handle DD/MM/YYYY, DD-MM-YYYY, YYYY-MM-DD with separators / . - space
        const clean = s.replace(/[\.\/ ]/g, '-');
        const parts = clean.split('-').map(p => p.trim()).filter(p => p !== '');
        if (parts.length === 3) {
            // Ensure parts are numeric
            const p0 = parts[0].replace(/\D/g,'');
            const p1 = parts[1].replace(/\D/g,'');
            const p2 = parts[2].replace(/\D/g,'');
            if (p0.length === 4) return `${p0}-${p1.padStart(2, '0')}-${p2.padStart(2, '0')}`;
            if (p2.length === 4) return `${p2}-${p1.padStart(2, '0')}-${p0.padStart(2, '0')}`;
        }
        // Fallback: try to find YYYY-MM-DD pattern inside string
        const isoMatch = s.match(/(\d{4})[^\d](\d{1,2})[^\d](\d{1,2})/);
        if (isoMatch) return `${isoMatch[1]}-${isoMatch[2].padStart(2,'0')}-${isoMatch[3].padStart(2,'0')}`;
        const dmyMatch = s.match(/(\d{1,2})[^\d](\d{1,2})[^\d](\d{4})/);
        if (dmyMatch) return `${dmyMatch[3]}-${dmyMatch[2].padStart(2,'0')}-${dmyMatch[1].padStart(2,'0')}`;
        return s;
    }

    // =========================================================================
    // Bulk Actions UI & Handlers
    // =========================================================================
    function updateBulkUI() {
        const count = selectedIds.size;
        if (count > 0) {
            els.bulkActionBanner.hidden = false;
            els.bulkSelectedCountText.textContent = `${count.toLocaleString('en-US')} টি রেকর্ড নির্বাচিত`;
        } else {
            els.bulkActionBanner.hidden = true;
        }

        $$('[data-row-id]').forEach(tr => {
            const id = parseInt(tr.getAttribute('data-row-id'), 10);
            tr.classList.toggle('selected', selectedIds.has(id));
        });

        $$('[data-card-id]').forEach(card => {
            const id = parseInt(card.getAttribute('data-card-id'), 10);
            card.classList.toggle('selected', selectedIds.has(id));
        });

        if (els.selectAllCheckbox && currentResults.length > 0) {
            const allSelected = currentResults.every(r => selectedIds.has(r.id));
            els.selectAllCheckbox.checked = allSelected;
            els.selectAllCheckbox.indeterminate = (!allSelected && count > 0);
        }
    }

    // =========================================================================
    // Pagination Component
    // =========================================================================
    function renderPagination(total, page, pages, perPage) {
        if (pages <= 1) {
            els.paginationContainer.hidden = true;
            return;
        }

        const from = (page - 1) * perPage + 1;
        const to = Math.min(page * perPage, total);
        els.paginationRangeText.textContent = `মোট ${total.toLocaleString('en-US')} টি রেকর্ডের ${from} থেকে ${to} দেখাচ্ছে`;

        const buttons = [];

        buttons.push(`
            <button type="button" class="page-btn-item" data-goto="${page - 1}" ${page <= 1 ? 'disabled' : ''} title="আগের পৃষ্ঠা">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            </button>
        `);

        const win = getPageNumbers(page, pages);
        win.forEach(p => {
            if (p === 'gap') {
                buttons.push('<span class="page-gap-indicator">…</span>');
            } else {
                buttons.push(`
                    <button type="button" class="page-btn-item ${p === page ? 'active' : ''}" data-goto="${p}">${p}</button>
                `);
            }
        });

        buttons.push(`
            <button type="button" class="page-btn-item" data-goto="${page + 1}" ${page >= pages ? 'disabled' : ''} title="পরের পৃষ্ঠা">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
        `);

        els.paginationInner.innerHTML = buttons.join('');
        els.paginationContainer.hidden = false;

        $$('.page-btn-item', els.paginationInner).forEach(btn => {
            btn.addEventListener('click', function () {
                const targetPage = parseInt(btn.getAttribute('data-goto'), 10);
                if (targetPage && targetPage !== state.page) {
                    runSearch(targetPage);
                    window.scrollToResults();
                }
            });
        });
    }

    function getPageNumbers(current, total) {
        const pages = [];
        if (total <= 7) {
            for (let i = 1; i <= total; i++) pages.push(i);
            return pages;
        }

        pages.push(1);
        let start = Math.max(2, current - 1);
        let end = Math.min(total - 1, current + 1);

        if (start > 2) pages.push('gap');
        for (let i = start; i <= end; i++) pages.push(i);
        if (end < total - 1) pages.push('gap');
        pages.push(total);

        return pages;
    }

    // =========================================================================
    // Record Detail Modal & Certificate Generator
    // =========================================================================
    function openRecordModal(id, initialTab = 'tabPersonal') {
        activeTab = initialTab;
        els.recordDetailModal.hidden = false;
        document.body.classList.add('modal-open');

        $$('.modal-tab-btn').forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-tab') === activeTab);
        });

        els.modalCandidateName.textContent = 'লোড হচ্ছে...';
        els.modalCandidateSub.textContent = `আইডি #${id}`;
        els.modalBodyContent.innerHTML = `
            <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:48px 0; gap:12px;">
                <div class="spinner"></div>
                <span style="color:var(--text-muted); font-weight:600;">ডাটাবেস থেকে তথ্য লোড হচ্ছে...</span>
            </div>
        `;

        fetch(`api/detail.php?id=${id}`)
            .then(res => res.json())
            .then(data => {
                if (data.error || !data.record) {
                    els.modalBodyContent.innerHTML = `<div class="empty-state-card"><p>রেকর্ড নেই।</p></div>`;
                    return;
                }
                currentRecord = data.record;
                els.modalCandidateName.textContent = currentRecord.name || 'অজানা নাম';
                els.modalCandidateSub.textContent = `নম্বর: ${currentRecord.registration_number || '—'}`;
                renderModalTabContent();
            })
            .catch(() => {
                els.modalBodyContent.innerHTML = `<div class="empty-state-card"><p>তথ্য লোড হয়নি।</p></div>`;
            });
    }

    function renderModalTabContent() {
        if (!currentRecord) return;
        const r = currentRecord;

        let contentHtml = '';

        if (activeTab === 'tabPersonal') {
            contentHtml = `
                <div class="tab-pane">
                    <div class="info-cards-grid">
                        <div class="info-detail-box">
                            <span class="info-detail-label">নাম (বাংলা)</span>
                            <span class="info-detail-value" style="font-size:1.1rem; color:var(--text-primary);">${escapeHtml(r.name || '—')}</span>
                        </div>
                        <div class="info-detail-box">
                            <span class="info-detail-label">নাম (English)</span>
                            <span class="info-detail-value mono" style="font-size:1.1rem; color:var(--primary);">${escapeHtml(r.name_en || '—')}</span>
                        </div>
                        <div class="info-detail-box">
                            <span class="info-detail-label">জন্ম তারিখ</span>
                            <span class="info-detail-value mono">${escapeHtml(r.date_of_birth || '—')} ${r.age ? `<span class="age-pill">${escapeHtml(r.age)}</span>` : ''}</span>
                        </div>
                        <div class="info-detail-box">
                            <span class="info-detail-label">লিঙ্গ</span>
                            <span class="info-detail-value">${escapeHtml(r.gender_display || r.gender)}</span>
                        </div>
                        <div class="info-detail-box">
                            <span class="info-detail-label">জন্মস্থান</span>
                            <span class="info-detail-value">${escapeHtml(r.birth_place || '—')}</span>
                        </div>
                        <div class="info-detail-box">
                            <span class="info-detail-label">জন্মস্থান (English)</span>
                            <span class="info-detail-value">${escapeHtml(r.place_of_birth_en || 'Bangladesh')}</span>
                        </div>
                        <div class="info-detail-box">
                            <span class="info-detail-label">জাতীয়তা</span>
                            <span class="info-detail-value">${escapeHtml(r.nationality || 'বাংলাদেশী (Bangladeshi)')}</span>
                        </div>
                        <div class="info-detail-box">
                            <span class="info-detail-label">দেশ</span>
                            <span class="info-detail-value">${escapeHtml(r.country || 'বাংলাদেশ')}</span>
                        </div>
                    </div>
                </div>
            `;
        } else if (activeTab === 'tabParents') {
            contentHtml = `
                <div class="tab-pane">
                    <div class="info-cards-grid">
                        <div class="info-detail-box">
                            <span class="info-detail-label">পিতার নাম</span>
                            <span class="info-detail-value">${escapeHtml(r.father_name || '—')}</span>
                        </div>
                        <div class="info-detail-box">
                            <span class="info-detail-label">পিতার নাম (English)</span>
                            <span class="info-detail-value mono" style="color:var(--primary); font-weight:700;">${escapeHtml(r.father_name_en || '—')}</span>
                        </div>
                        <div class="info-detail-box">
                            <span class="info-detail-label">মাতার নাম</span>
                            <span class="info-detail-value">${escapeHtml(r.mother_name || '—')}</span>
                        </div>
                        <div class="info-detail-box">
                            <span class="info-detail-label">মাতার নাম (English)</span>
                            <span class="info-detail-value mono" style="color:var(--primary); font-weight:700;">${escapeHtml(r.mother_name_en || '—')}</span>
                        </div>
                        <div class="info-detail-box full-width">
                            <span class="info-detail-label">ঠিকানা</span>
                            <span class="info-detail-value" style="font-weight:600;">${escapeHtml(r.permanent_address || '—')}</span>
                        </div>
                    </div>
                </div>
            `;
        } else if (activeTab === 'tabRegistration') {
            contentHtml = `
                <div class="tab-pane">
                    <div class="info-cards-grid">
                        <div class="info-detail-box full-width">
                            <span class="info-detail-label">নম্বর (BRN)</span>
                            <span class="info-detail-value mono" style="font-size:1.2rem; color:var(--primary);">${escapeHtml(r.registration_number || '—')}</span>
                        </div>
                        <div class="info-detail-box">
                            <span class="info-detail-label">নিবন্ধন তারিখ</span>
                            <span class="info-detail-value mono">${escapeHtml(r.registration_date || '—')}</span>
                        </div>
                        <div class="info-detail-box">
                            <span class="info-detail-label">সনদ তারিখ</span>
                            <span class="info-detail-value mono">${escapeHtml(r.certificate_date || '—')}</span>
                        </div>
                        <div class="info-detail-box">
                            <span class="info-detail-label">ওয়ার্ড</span>
                            <span class="info-detail-value">ওয়ার্ড ${escapeHtml(r.ward || '—')}</span>
                        </div>
                        <div class="info-detail-box">
                            <span class="info-detail-label">নিবন্ধন স্ট্যাটাস</span>
                            <span class="badge-status ${r.status || 'verified'}">${r.status === 'verified' ? 'যাচাইকৃত' : 'প্রক্রিয়াধীন'}</span>
                        </div>
                        <div class="info-detail-box full-width">
                            <span class="info-detail-label">বই ও পৃষ্ঠা</span>
                            <span class="info-detail-value" style="font-size:0.92rem;">ভলিউম: <b>${escapeHtml(r.source_file || '—')}</b> • পৃষ্ঠা নং: <b>${escapeHtml(r.page_number || '—')}</b> • ক্রমিক: <b>${escapeHtml(r.serial || '—')}</b></span>
                        </div>
                    </div>
                </div>
            `;
        } else if (activeTab === 'tabAudit') {
            const timeline = r.timeline || [];
            contentHtml = `
                <div class="tab-pane">
                    <div class="audit-timeline">
                        ${timeline.map(step => `
                            <div class="timeline-step ${step.status === 'completed' ? 'completed' : ''}">
                                <div class="timeline-dot"></div>
                                <div class="timeline-content-card">
                                    <div class="timeline-step-title">${escapeHtml(step.title)}</div>
                                    <div class="timeline-step-desc">${escapeHtml(step.description)}</div>
                                    <div class="timeline-step-date">${escapeHtml(step.date)}</div>
                                </div>
                            </div>
                        `).join('')}
                    </div>

                    <div style="margin-top:20px; padding:12px; background:var(--bg-subtle); border-radius:var(--radius-sm); border:1px solid var(--border-color); font-size:0.78rem; color:var(--text-muted);">
                        <b>ক্রিপ্টোগ্রাফিক নিরাপত্তা হ্যাশ:</b> <span class="mono">${escapeHtml(r.security_hash || 'SHA256-VALIDATED')}</span>
                    </div>
                </div>
            `;
        } else if (activeTab === 'tabCertificate') {
            contentHtml = `
                <div class="tab-pane">
                    <div class="official-certificate-sheet">
                        <div class="certificate-watermark">BD</div>
                        <div class="cert-header">
                            <div class="cert-govt-title">Government of the People's Republic of Bangladesh</div>
                            <div class="cert-sub-title">Office of the Registrar, Birth and Death Registration</div>
                            <div class="cert-main-heading">জন্ম নিবন্ধন সনদ / Birth Registration Certificate</div>
                            <div style="font-size:0.8rem; color:var(--text-muted);">[বিধি ৯, ১০ দ্রষ্টব্য / Rule 9, 10]</div>
                        </div>

                        <div class="cert-grid-fields">
                            <div class="cert-field-row" style="grid-column: span 2;">
                                <span class="cert-field-label">নম্বর (BRN):</span>
                                <span class="cert-field-value mono" style="font-size:1.15rem; color:#1e3a8a;">${escapeHtml(r.registration_number || '—')}</span>
                            </div>
                            <div class="cert-field-row">
                                <span class="cert-field-label">নাম (বাংলা):</span>
                                <span class="cert-field-value">${escapeHtml(r.name || '—')}</span>
                            </div>
                            <div class="cert-field-row">
                                <span class="cert-field-label">Name (English):</span>
                                <span class="cert-field-value mono">${escapeHtml(r.name_en || r.name)}</span>
                            </div>
                            <div class="cert-field-row">
                                <span class="cert-field-label">লিঙ্গ:</span>
                                <span class="cert-field-value">${escapeHtml(r.gender_display || r.gender)} / ${escapeHtml(r.gender || 'MALE')}</span>
                            </div>
                            <div class="cert-field-row">
                                <span class="cert-field-label">জন্ম তারিখ:</span>
                                <span class="cert-field-value mono">${escapeHtml(r.date_of_birth || '—')}</span>
                            </div>
                            <div class="cert-field-row">
                                <span class="cert-field-label">পিতার নাম:</span>
                                <span class="cert-field-value">${escapeHtml(r.father_name || '—')}</span>
                            </div>
                            <div class="cert-field-row">
                                <span class="cert-field-label">পিতার নাম (EN):</span>
                                <span class="cert-field-value mono">${escapeHtml(r.father_name_en || r.father_name || '—')}</span>
                            </div>
                            <div class="cert-field-row">
                                <span class="cert-field-label">মাতার নাম:</span>
                                <span class="cert-field-value">${escapeHtml(r.mother_name || '—')}</span>
                            </div>
                            <div class="cert-field-row">
                                <span class="cert-field-label">মাতার নাম (EN):</span>
                                <span class="cert-field-value mono">${escapeHtml(r.mother_name_en || r.mother_name || '—')}</span>
                            </div>
                            <div class="cert-field-row" style="grid-column: span 2;">
                                <span class="cert-field-label">ঠিকানা:</span>
                                <span class="cert-field-value">${escapeHtml(r.permanent_address || '—')}</span>
                            </div>
                            <div class="cert-field-row">
                                <span class="cert-field-label">ওয়ার্ড:</span>
                                <span class="cert-field-value">ওয়ার্ড ${escapeHtml(r.ward || '—')}</span>
                            </div>
                            <div class="cert-field-row">
                                <span class="cert-field-label">নিবন্ধন তারিখ:</span>
                                <span class="cert-field-value mono">${escapeHtml(r.registration_date || '—')}</span>
                            </div>
                        </div>

                        <div class="cert-signatures-row">
                            <div class="cert-qr-box">
                                <svg viewBox="0 0 100 100" width="80" height="80">
                                    <rect width="100" height="100" fill="#fff"/>
                                    <rect x="5" y="5" width="30" height="30" fill="#000"/>
                                    <rect x="10" y="10" width="20" height="20" fill="#fff"/>
                                    <rect x="15" y="15" width="10" height="10" fill="#000"/>
                                    <rect x="65" y="5" width="30" height="30" fill="#000"/>
                                    <rect x="70" y="10" width="20" height="20" fill="#fff"/>
                                    <rect x="75" y="15" width="10" height="10" fill="#000"/>
                                    <rect x="5" y="65" width="30" height="30" fill="#000"/>
                                    <rect x="10" y="70" width="20" height="20" fill="#fff"/>
                                    <rect x="15" y="75" width="10" height="10" fill="#000"/>
                                    <rect x="45" y="20" width="10" height="10" fill="#000"/>
                                    <rect x="45" y="45" width="10" height="10" fill="#000"/>
                                    <rect x="25" y="45" width="10" height="10" fill="#000"/>
                                    <rect x="65" y="65" width="15" height="15" fill="#000"/>
                                    <rect x="85" y="45" width="10" height="10" fill="#000"/>
                                    <rect x="45" y="75" width="10" height="10" fill="#000"/>
                                </svg>
                            </div>
                            <div class="cert-sign-line">
                                প্রস্তুতকারীর স্বাক্ষর
                            </div>
                            <div class="cert-sign-line">
                                নিবন্ধকের স্বাক্ষর ও সিল
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        els.modalBodyContent.innerHTML = contentHtml;

        // Inject print action inside certificate preview tab when verified
        if (activeTab === 'tabCertificate' && currentRecord) {
            const isVerified = currentRecord.status === 'verified' || !!currentRecord.verified_at;
            const container = els.modalBodyContent.querySelector('.official-certificate-sheet');
            if (container) {
                if (isVerified) {
                    // Append print button inside certificate preview if not already present
                    if (!els.modalBodyContent.querySelector('#btnCertPanePrint')) {
                        const printWrap = document.createElement('div');
                        printWrap.style.cssText = 'margin-top:18px; display:flex; justify-content:center;';
                        printWrap.innerHTML = `<button type="button" id="btnCertPanePrint" class="btn-primary" style="padding:10px 20px;"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg> <span>সনদ প্রিন্ট করুন (demo/output.php)</span></button>`;
                        container.appendChild(printWrap);
                        const paneBtn = printWrap.querySelector('#btnCertPanePrint');
                        if (paneBtn) paneBtn.addEventListener('click', () => openCertificatePrint(currentRecord));
                    }
                } else {
                    const banner = document.createElement('div');
                    banner.style.cssText = 'margin-top:14px; padding:10px; background:rgba(251,191,36,0.12); border:1px solid rgba(251,191,36,0.4); border-radius:8px; font-size:0.82rem; color:#92400e; text-align:center;';
                    banner.textContent = 'প্রিন্টের আগে যাচাই করুন';
                    container.appendChild(banner);
                }
            }
        }

        updatePrintButtonVisibility();
    }

    function closeRecordModal() {
        els.recordDetailModal.hidden = true;
        if (els.verifyModal.hidden) {
            document.body.classList.remove('modal-open');
        }
    }

    // =========================================================================
    // Keyboard Shortcuts & Global Help Window
    // =========================================================================
    window.scrollToResults = function () {
        els.resultsSection.scrollIntoView({ behavior: 'smooth' });
    };

    // Nav highlight follows the mode: dashboard (search) vs record list (browse-all).
    function setActiveNav(activeId) {
        ['navDashboard', 'navRecords'].forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            const on = (id === activeId);
            el.classList.toggle('active', on);
            if (on) el.setAttribute('aria-current', 'page');
            else el.removeAttribute('aria-current');
        });
    }

    // Record list: browse the whole database, 100 per page.
    window.showAllRecords = function () {
        state.showAll = true;
        state.page = 1;
        state.q = '';
        state.name = '';
        state.father = '';
        state.mother = '';
        state.dob = '';
        state.gender = '';
        state.reg_num = '';
        state.ward = '';
        state.status = '';
        state.perPage = 100;
        syncFormInputsFromState();
        runSearch(1);
        setActiveNav('navRecords');
        els.resultsSection.scrollIntoView();
    };

    window.resetAllFilters = function () {
        els.searchInput.value = '';
        if ($('#fName')) $('#fName').value = '';
        if ($('#fRegNum')) $('#fRegNum').value = '';
        if ($('#fFather')) $('#fFather').value = '';
        if ($('#fMother')) $('#fMother').value = '';
        if ($('#fDob')) $('#fDob').value = '';
        if ($('#fGender')) $('#fGender').value = '';
        if ($('#fWard')) $('#fWard').value = '';
        if ($('#fStatus')) $('#fStatus').value = '';
        $$('.quick-pill').forEach(p => p.classList.toggle('active', p.getAttribute('data-preset') === 'all'));
        state.showAll = false;
        setActiveNav('navDashboard');
        updateSearchInputTools();
        runSearch(1);
        showToast('ফিল্টার রিসেট', 'শর্ত মুছে ফেলা হয়েছে', 'info');
    };

    // =========================================================================
    // Certificate Print Helpers - renders demo/output.php in new tab after verification
    // =========================================================================
    function updatePrintButtonVisibility() {
        const isVerified = !!(currentRecord && (currentRecord.status === 'verified' || currentRecord.verified_at));
        if (els.btnPrintModalCert) {
            els.btnPrintModalCert.hidden = !isVerified;
            els.btnPrintModalCert.disabled = !isVerified;
            els.btnPrintModalCert.title = isVerified ? 'সনদ প্রিন্ট করুন' : 'প্রিন্ট করতে প্রথমে যাচাই করুন';
        }
    }

    function toEnglishDigitsJS(str) {
        if (!str) return '';
        const map = {'০':'0','১':'1','২':'2','৩':'3','৪':'4','৫':'5','৬':'6','৭':'7','৮':'8','৯':'9'};
        return String(str).replace(/[০-৯]/g, ch => map[ch] || ch);
    }

    function stripDivisionCountryJS(s) {
        if (!s) return s;
        const parts = String(s).split(',');
        const filtered = parts.filter(p => !/বিভাগ|বাংলাদেশ|Division|Bangladesh/i.test(p));
        return filtered.join(',').trim().replace(/^,|,$/g,'').replace(/\s*,\s*,/g,',').replace(/\s+/g,' ').trim();
    }

    function isBanglaTextJS(s) { return /[\u0980-\u09FF]/.test(s || ''); }

    function transliteratePhoneticJS(text) {
        const map = {
            'খা':'Kha','ঘা':'Gha','ছা':'Chha','ঝা':'Jha','ঠা':'Tha','ঢা':'Dha','ফা':'Pha','ভা':'Bha','শা':'Sha','ষা':'Sha',
            'খ':'Kh','ঘ':'Gh','ং':'Ng','ছ':'Chh','ঝ':'Jh','ঞ':'Ny','ঠ':'Th','ঢ':'Dh','ফ':'Ph','ভ':'Bh','শ':'Sh','ষ':'Sh',
            'ণ':'N','ত':'T','থ':'Th','দ':'D','ধ':'Dh','ন':'N',
            'া':'a','ি':'i','ী':'i','ু':'u','ূ':'u','ে':'e','ৈ':'oi','ো':'o','ৌ':'ou','্':'',
            'ক':'K','গ':'G','চ':'Ch','জ':'J','ট':'T','ড':'D','প':'P','ব':'B','ম':'M','য':'J','র':'R','ল':'L','স':'S','হ':'H','ড়':'R','ঢ়':'Rh','য়':'Y',
            'অ':'A','আ':'A','ই':'I','ঈ':'I','উ':'U','ঊ':'U','ঋ':'R','এ':'E','ঐ':'Oi','ও':'O','ঔ':'Ou'
        };
        const keys = Object.keys(map).sort((a,b)=>b.length-a.length);
        let out = String(text);
        keys.forEach(k=>{ out = out.split(k).join(map[k]); });
        return out;
    }

    function banglaToEnglishTransliterateJS(bn) {
        if (!bn || !isBanglaTextJS(bn)) return bn;
        // Normalize digits, replace Bangla visarga "ঃ" with ":" for English (instead of  use :), and clean division/country
        let s = toEnglishDigitsJS(String(bn)).replace(/ঃ/g, ':').replace(/ঃঃ/g, ':');
        // Remove Division and Bangladesh segments (e.g. ", Rajshahi Division, Bangladesh")
        let parts = s.split(',');
        parts = parts.filter(p => !/বিভাগ|বাংলাদেশ|Division|Bangladesh/i.test(p));
        s = parts.join(',').trim().replace(/^,|,$/g,'').replace(/\s*,\s*,/g,',');
        // Prefix replacements for certificate address format
        s = s.replace(/গ্রাম\s*:/g, 'Village:')
             .replace(/গ্রাম/g, 'Village')
             .replace(/ডাকঘর\s*:/g, 'Post:')
             .replace(/ডাকঘর/g, 'Post')
             .replace(/ওয়ার্ড\s*নং\s*-/g, 'Ward no -')
             .replace(/ওয়ার্ড\s*নং\s*-/g, 'Ward no -')
             .replace(/ওয়ার্ড\s*-/g, 'Ward no -')
             .replace(/ওয়ার্ড\s*-/g, 'Ward no -')
             .replace(/ওয়ার্ড/g, 'Ward')
             .replace(/ওয়ার্ড/g, 'Ward');
        const dict = {
            'মোহাম্মদ':'Mohammad','মোছাঃ':'Mst.','মোসাঃ':'Mst.','মোসাম্মৎ':'Mosammat','মোঃ':'Md.','রহিম':'Rahim','করিম':'Karim','কারিম':'Karim','রহমান':'Rahman','হোসেন':'Hossain','হুসেন':'Hossain','আলী':'Ali','আলি':'Ali','বেগম':'Begum','খাতুন':'Khatun','আক্তার':'Akter','আকতার':'Akter','ইসলাম':'Islam','হাসান':'Hasan','হুসাইন':'Husain','ফাতেমা':'Fatema','আয়েশা':'Ayesha','সোহেল':'Sohel','রানা':'Rana','শাকিব':'Shakib','সাকিব':'Sakib','শাকিল':'Shakil','মাসুদ':'Masud','রাকিব':'Rakib','রুবেল':'Rubel','মিয়া':'Mia','মন্ডল':'Mondol','সরকার':'Sarker','চৌধুরী':'Chowdhury','শেখ':'Sheikh','উদ্দিন':'Uddin','উদ্দীন':'Uddin','খান':'Khan','হক':'Haque','সিদ্দিক':'Siddique','আহমেদ':'Ahmed','মাহমুদ':'Mahmud','আব্দুল':'Abdul','আব্দুর':'Abdur','নূর':'Noor','জালাল':'Jalal','কামাল':'Kamal','জামাল':'Jamal','শাহ':'Shah','আলম':'Alam','জাহান':'Jahan','পারভীন':'Parvin','সুলতানা':'Sultana','নাসরিন':'Nasrin','রোকসানা':'Roksana',
            'গ্রাম':'Village','ইউনিয়ন':'Union','ইউনিয়ন':'Union','ওয়ার্ড':'Ward','ওয়ার্ড':'Ward','থানা':'Thana','উপজেলা':'Upazila','জেলা':'Zila','জিলা':'Zila','বিভাগ':'Division','বাংলাদেশ':'Bangladesh','পৌরসভা':'Pourashava','সিটি':'City','কর্পোরেশন':'Corporation','রোড':'Road','বাড়ি':'Bari','বাড়ি':'Bari','হোল্ডিং':'Holding','মহল্লা':'Mahalla','পাড়া':'Para','পাড়া':'Para','বাজার':'Bazar','হাট':'Hat','চর':'Char','নগর':'Nagar','পুর':'Pur','গঞ্জ':'Ganj','ডাঙ্গা':'Danga','কান্দি':'Kandi','বিল':'Bil','দিয়া':'Diya','দিয়া':'Diya','পূর্ব':'Purba','পশ্চিম':'Paschim','উত্তর':'Uttar','দক্ষিণ':'Dakshin','মধ্য':'Madhya','নতুন':'Natun','পুরাতন':'Puratan','ছোট':'Chhoto','বড়':'Bara','বড়':'Bara',
            'বালুয়াহাট':'Baluahat','বালুয়াহাট':'Baluahat','বালুয়া':'Balua','বালুয়া':'Balua','গবর':'Gobar','গবার':'Gobar','গোবর':'Gobar','বগুড়া':'Bogura','বগura':'Bogura','সোনাতলা':'Sonatola','বগুরা':'Bogura','রাজশাহী':'Rajshahi'
        };
        const segs = String(s).split(/(\s+|,|:)/);
        let out = segs.map(tok=>{
            if (/^\s+$/.test(tok) || tok===',' || tok===':') return tok;
            const clean = tok.replace(/[।.,;]/g,'');
            if (dict[clean]) return dict[clean];
            const base = clean.replace(/[^ঀ-৿A-Za-z0-9]+/g,'');
            if (dict[base]) return dict[base];
            if (isBanglaTextJS(tok)) return transliteratePhoneticJS(tok);
            return tok;
        }).join('');
        // Post-process: fix common phonetic artifacts and ensure proper spacing/casing
        out = out.replace(/\s+/g,' ').trim().replace(/\s+,/g,',').replace(/\s*:\s*/g,': ').replace(/Ward no\s*-/g,'Ward no -');
        // Title-case English parts but keep already correct dict words
        return out.split(',').map(p=>p.trim() ? p.trim().split(/\s+/).map(w=>{
            if (/^(Village|Post|Ward|no|Union|Thana|Upazila|Zila|Division|Bangladesh)$/i.test(w)) return w.charAt(0).toUpperCase()+w.slice(1).toLowerCase();
            if (/^[A-Za-z.:-]+$/.test(w)) return w.charAt(0).toUpperCase()+w.slice(1).toLowerCase();
            return w;
        }).join(' ') : '').join(', ').replace(/\s+/g,' ').trim();
    }

    function getEnglishFieldJS(banglaFallback, englishField) {
        if (englishField && englishField.trim() && !isBanglaTextJS(englishField)) return englishField.trim();
        if (englishField && englishField.trim() && isBanglaTextJS(englishField)) return banglaToEnglishTransliterateJS(englishField);
        if (banglaFallback && isBanglaTextJS(banglaFallback)) return banglaToEnglishTransliterateJS(banglaFallback);
        return banglaFallback || '';
    }

    function buildDobTextFromIso(isoDate) {
        if (!isoDate) return '';
        const eng = toEnglishDigitsJS(isoDate);
        const clean = formatToIsoDate(eng);
        const parts = clean.split('-');
        if (parts.length !== 3) return '';
        const y = parseInt(parts[0], 10);
        const m = parseInt(parts[1], 10);
        const d = parseInt(parts[2], 10);
        if (!y || !m || !d || m < 1 || m > 12 || d < 1 || d > 31) return '';
        const ordinals = ['First','Second','Third','Fourth','Fifth','Sixth','Seventh','Eighth','Ninth','Tenth',
            'Eleventh','Twelfth','Thirteenth','Fourteenth','Fifteenth','Sixteenth','Seventeenth',
            'Eighteenth','Nineteenth','Twentieth','Twenty First','Twenty Second','Twenty Third',
            'Twenty Fourth','Twenty Fifth','Twenty Sixth','Twenty Seventh','Twenty Eighth',
            'Twenty Ninth','Thirtieth','Thirty First'];
        const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        const yearText = convertYearToText(y);
        if (!ordinals[d-1] || !months[m-1] || !yearText) return '';
        return `${ordinals[d-1]} of ${months[m-1]} ${yearText}`;
    }

    function convertYearToText(year) {
        if (year >= 2000 && year < 3000) {
            const rem = year % 1000;
            if (rem === 0) return 'Two Thousand';
            if (rem < 100) return `Two Thousand ${numToText(rem)}`;
            return `Two Thousand ${numToText(rem)}`;
        }
        if (year >= 1900 && year < 2000) {
            const tens = year % 100;
            return tens === 0 ? 'Nineteen Hundred' : `Nineteen Hundred ${numToText(tens)}`;
        }
        const th = Math.floor(year / 1000);
        const rem = year % 1000;
        return rem === 0 ? `${numToText(th)} Thousand` : `${numToText(th)} Thousand ${numToText(rem)}`;
    }
    function numToText(n) {
        const units = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen','Seventeen','Eighteen','Nineteen'];
        const tens2 = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
        if (n === 0) return 'Zero';
        if (n < 20)  return units[n];
        if (n < 100) { const t=Math.floor(n/10),u=n%10; return u===0?tens2[t]:`${tens2[t]} ${units[u]}`; }
        if (n < 1000){ const h=Math.floor(n/100),r=n%100; return r===0?`${units[h]} Hundred`:`${units[h]} Hundred ${numToText(r)}`; }
        const th=Math.floor(n/1000),r=n%1000;
        return r===0?`${numToText(th)} Thousand`:`${numToText(th)} Thousand ${numToText(r)}`;
    }

    function openCertificatePrint(record) {
        if (!record) {
            showToast('প্রিন্ট ব্যর্থ', 'কোনো রেকর্ড নির্বাচিত নেই', 'error');
            return;
        }
        let dobIso = formatToIsoDate(record.date_of_birth || '');
        if (!dobIso || !/^\d{4}-\d{2}-\d{2}$/.test(dobIso)) {
            // Try alternate fields (dob, birth_date)
            dobIso = formatToIsoDate(record.dob || record.birth_date || '');
        }
        let dobt = buildDobTextFromIso(dobIso) || '';
        // If record.dobt looks like words (not a date), prefer it when build failed
        if (!dobt && record.dobt && !/^\d{4}-\d{2}-\d{2}$/.test(record.dobt.trim()) && !/^\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}$/.test(record.dobt.trim())) {
            dobt = record.dobt;
        }
        // If still empty or looks like a raw date, leave empty so server-side fallback generates correct "First of December..." words
        if (!dobt || /^\d{4}-\d{2}-\d{2}$/.test(dobt.trim())) {
            dobt = buildDobTextFromIso(dobIso) || '';
        }
        const issuedate = record.certificate_date ? formatToIsoDate(record.certificate_date) : (record.registration_date ? formatToIsoDate(record.registration_date) : new Date().toISOString().split('T')[0]);
        const givendate = record.registration_date ? formatToIsoDate(record.registration_date) : issuedate;
        const nibkar = `Balua Union Parishad`;
        const thanaandzilla = record.thanaandzilla || record.thana_and_zilla || 'Sonatola, Bogura';
        const rawBpBn = record.birth_place || '';
        const bpBn = stripDivisionCountryJS(rawBpBn);
        // English birthplace: only everify server response (do not autofill)
        const bpEn = stripDivisionCountryJS(record.place_of_birth_en || '');
        const rawAddrBn = record.permanent_address || record.permanent_address_en || rawBpBn;
        const addrBn = stripDivisionCountryJS(rawAddrBn);
        // English Permanent Address: autofill from Bangla via transliteration (only field allowed to autofill)
        let addrEn = stripDivisionCountryJS(record.permanent_address_en || '');
        if (!addrEn || isBanglaTextJS(addrEn)) {
            const fallbackBn = stripDivisionCountryJS(record.permanent_address || '');
            const candidate = fallbackBn || stripDivisionCountryJS(record.permanent_address_en || '') || addrBn;
            if (candidate && isBanglaTextJS(candidate)) {
                addrEn = banglaToEnglishTransliterateJS(candidate);
            } else if (candidate) {
                addrEn = candidate;
            }
            if (addrEn && isBanglaTextJS(addrEn)) addrEn = banglaToEnglishTransliterateJS(addrEn);
            addrEn = stripDivisionCountryJS(addrEn);
        }
        const genderVal = (record.gender || '').toUpperCase() === 'FEMALE' ? 'Female' : 'Male';

        const payload = {
            dob: dobIso || new Date().toISOString().split('T')[0],
            dobt: dobt,
            name_bn: record.name || '',
            father_name_bn: record.father_name || '',
            mother_name_bn: record.mother_name || '',
            // Only everify server English - do not autofill from Bangla
            mother_name_en: record.mother_name_en || '',
            father_name_en: record.father_name_en || '',
            nibkar: nibkar,
            thanaandzilla: thanaandzilla,
            name_en: record.name_en || '',
            gender: genderVal,
            issuedate: issuedate,
            givendate: givendate,
            bp_bn: bpBn,
            bp_en: bpEn,
            addressx: addrBn,
            address: addrEn,
            birth_registration_number: record.registration_number || ''
        };

        // Create hidden form targeting new tab -> demo/output.php
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'demo/output.php';
        form.target = '_blank';
        form.style.display = 'none';
        Object.keys(payload).forEach(key => {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = key;
            inp.value = payload[key];
            form.appendChild(inp);
        });
        document.body.appendChild(form);
        form.submit();
        setTimeout(() => form.remove(), 1000);
        showToast('প্রিন্ট প্রস্তুত', 'নতুন ট্যাবে খোলা হয়েছে', 'success');
    }

    // =========================================================================
    // Utilities & Escaping
    // =========================================================================
    function escapeHtml(str) {
        if (str == null) return '';
        return String(str).replace(/[&<>"']/g, function (m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m];
        });
    }

    // =========================================================================
    // Event Listeners Initialization
    // =========================================================================
    function initEvents() {
        // Counters & saved state (single light theme)
        animateCounters();
        syncFormInputsFromState();

        // Search Input Events
        els.searchInput.addEventListener('input', function () {
            updateSearchInputTools();
            clearTimeout(suggestTimer);
            const val = els.searchInput.value.trim();
            if (val.length >= 2) {
                suggestTimer = setTimeout(() => fetchSuggestions(val), 200);
            } else {
                hideSuggestions();
            }
        });

        els.searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                state.showAll = false;
                setActiveNav('navDashboard');
                hideSuggestions();
                runSearch(1);
            } else if (e.key === 'Escape') {
                hideSuggestions();
            } else if (e.key === 'ArrowDown') {
                const rows = $$('.suggestion-row', els.suggestionsDropdown);
                if (rows.length > 0) {
                    e.preventDefault();
                    rows[0].focus();
                }
            }
        });

        // Run Search CTA Button
        els.btnRunSearch.addEventListener('click', function () {
            state.showAll = false;
            setActiveNav('navDashboard');
            runSearch(1);
        });

        // Error-state retry button
        if (els.btnRetrySearch) {
            els.btnRetrySearch.addEventListener('click', function () {
                runSearch(state.page || 1);
            });
        }

        // Clear Search Button
        els.clearSearchBtn.addEventListener('click', function () {
            els.searchInput.value = '';
            updateSearchInputTools();
            hideSuggestions();
            els.searchInput.focus();
            if (state.q) runSearch(1);
        });

        // Quick Preset Pills
        $$('.quick-pill').forEach(pill => {
            pill.addEventListener('click', function () {
                $$('.quick-pill').forEach(p => { p.classList.remove('active'); p.setAttribute('aria-pressed', 'false'); });
                pill.classList.add('active');
                pill.setAttribute('aria-pressed', 'true');
                const preset = pill.getAttribute('data-preset');
                if (preset === 'all') {
                    if ($('#fGender')) $('#fGender').value = '';
                    if ($('#fStatus')) $('#fStatus').value = '';
                } else if (preset === 'male') {
                    if ($('#fGender')) $('#fGender').value = 'male';
                } else if (preset === 'female') {
                    if ($('#fGender')) $('#fGender').value = 'female';
                } else if (preset === 'verified') {
                    if ($('#fStatus')) $('#fStatus').value = 'verified';
                } else if (preset === 'pending') {
                    if ($('#fStatus')) $('#fStatus').value = 'pending';
                }
                runSearch(1);
            });
        });

        // Advanced Filter Drawer Toggle
        els.advFilterToggle.addEventListener('click', function () {
            const isHidden = els.advancedFiltersPanel.hidden;
            els.advancedFiltersPanel.hidden = !isHidden;
            els.advFilterToggle.setAttribute('aria-expanded', String(isHidden));
        });

        // Apply Advanced Filters Button
        els.btnApplyAdvFilters.addEventListener('click', function () {
            state.showAll = false;
            setActiveNav('navDashboard');
            runSearch(1);
        });

        // Reset Filters Button
        els.btnResetFilters.addEventListener('click', window.resetAllFilters);

        // View Mode Switcher
        els.viewModeTableBtn.addEventListener('click', function () {
            els.viewModeTableBtn.classList.add('active');
            els.viewModeTableBtn.setAttribute('aria-pressed', 'true');
            els.viewModeCardsBtn.classList.remove('active');
            els.viewModeCardsBtn.setAttribute('aria-pressed', 'false');
            state.view = 'table';
            renderResults(currentResults, currentResults.length, state.page, 1, state.perPage);
            updateUrlState();
        });

        els.viewModeCardsBtn.addEventListener('click', function () {
            els.viewModeCardsBtn.classList.add('active');
            els.viewModeCardsBtn.setAttribute('aria-pressed', 'true');
            els.viewModeTableBtn.classList.remove('active');
            els.viewModeTableBtn.setAttribute('aria-pressed', 'false');
            state.view = 'cards';
            renderResults(currentResults, currentResults.length, state.page, 1, state.perPage);
            updateUrlState();
        });

        // Search Mode Toggle
        $$('[data-match-mode]').forEach(btn => {
            btn.addEventListener('click', function () {
                $$('[data-match-mode]').forEach(b => { b.classList.remove('active'); b.setAttribute('aria-pressed', 'false'); });
                btn.classList.add('active');
                btn.setAttribute('aria-pressed', 'true');
                state.matchMode = btn.getAttribute('data-match-mode');
                if (state.q || hasActiveFilters()) runSearch(1);
            });
        });

        // Per Page Select
        els.perPageSelect.addEventListener('change', function () {
            state.perPage = parseInt(els.perPageSelect.value, 10) || 50;
            runSearch(1);
        });

        // Column Sorting Headers
        $$('.sortable', els.dataTable).forEach(th => {
            th.addEventListener('click', function () {
                const sortField = th.getAttribute('data-sort');
                if (state.sortBy === sortField) {
                    state.sortDir = state.sortDir === 'asc' ? 'desc' : 'asc';
                } else {
                    state.sortBy = sortField;
                    state.sortDir = 'asc';
                }
                runSearch(1);
            });
        });

        // Select All Checkbox
        els.selectAllCheckbox.addEventListener('change', function () {
            const checked = els.selectAllCheckbox.checked;
            currentResults.forEach(r => {
                if (checked) {
                    selectedIds.add(r.id);
                } else {
                    selectedIds.delete(r.id);
                }
            });
            updateBulkUI();
        });

        // Bulk Copy IDs
        els.btnBulkCopyIds.addEventListener('click', function () {
            const idsList = Array.from(selectedIds);
            const brnList = currentResults.filter(r => selectedIds.has(r.id)).map(r => r.registration_number || r.id);
            const textToCopy = brnList.join(', ');
            navigator.clipboard.writeText(textToCopy).then(() => {
                showToast('আইডি কপি সম্পন্ন', `${idsList.length}টি আইডি কপি হয়েছে`, 'success');
            });
        });

        // Bulk Export
        els.btnBulkExport.addEventListener('click', function () {
            const idsList = Array.from(selectedIds).join(',');
            window.location.href = `api/export.php?format=csv&ids=${encodeURIComponent(idsList)}`;
            showToast('এক্সপোর্ট শুরু হয়েছে', 'CSV ডাউনলোড হচ্ছে', 'info');
        });

        // Bulk Clear
        els.btnBulkClear.addEventListener('click', function () {
            selectedIds.clear();
            updateBulkUI();
        });

        // Export Dropdown
        els.btnExportMenu.addEventListener('click', function (e) {
            e.stopPropagation();
            els.exportMenuDropdown.hidden = !els.exportMenuDropdown.hidden;
            els.btnExportMenu.setAttribute('aria-expanded', String(!els.exportMenuDropdown.hidden));
        });

        els.exportCSV.addEventListener('click', function () {
            els.exportMenuDropdown.hidden = true;
            els.btnExportMenu.setAttribute('aria-expanded', 'false');
            const params = new URLSearchParams(state);
            params.set('format', 'csv');
            window.location.href = `api/export.php?${params.toString()}`;
            showToast('এক্সপোর্ট শুরু হয়েছে', 'CSV তৈরি হচ্ছে', 'info');
        });

        els.exportJSON.addEventListener('click', function () {
            els.exportMenuDropdown.hidden = true;
            els.btnExportMenu.setAttribute('aria-expanded', 'false');
            const params = new URLSearchParams(state);
            params.set('format', 'json');
            window.location.href = `api/export.php?${params.toString()}`;
            showToast('JSON প্রস্তুত', 'JSON ডাউনলোড হচ্ছে', 'info');
        });

        els.exportPrint.addEventListener('click', function () {
            els.exportMenuDropdown.hidden = true;
            els.btnExportMenu.setAttribute('aria-expanded', 'false');
            window.print();
        });

        // Modal Tabs Switcher
        $$('.modal-tab-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                $$('.modal-tab-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                activeTab = btn.getAttribute('data-tab');
                renderModalTabContent();
            });
        });

        // Modal Close triggers
        $$('[data-close-modal]').forEach(el => {
            el.addEventListener('click', closeRecordModal);
        });

        // Verify Modal Close triggers
        $$('[data-close-verify]').forEach(el => {
            el.addEventListener('click', closeVerifyModal);
        });

        // Captcha Refresh Button
        els.btnRefreshCaptcha.addEventListener('click', loadCaptcha);

        // Verification Submit Button (next to captcha)
        els.btnSubmitVerification.addEventListener('click', submitVerification);

        // Verify-modal footer: certificate print (enabled after verification)
        if (els.btnVerifyModalPrint) {
            els.btnVerifyModalPrint.addEventListener('click', function () {
                if (verifiedPrintRecord) openCertificatePrint(verifiedPrintRecord);
            });
        }

        // Hit Enter in verification tab (verify modal) to trigger Verify and Date update
        const verifyFormEl = document.getElementById('verifyForm');
        if (verifyFormEl) {
            verifyFormEl.addEventListener('submit', function(e) {
                e.preventDefault();
                if (!els.verifyModal.hidden) submitVerification();
            });
        }
        ['verifyUbrnInput', 'verifyDobInput', 'verifyCaptchaAnswer'].forEach(key => {
            const el = els[key];
            if (!el) return;
            el.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    e.stopPropagation();
                    submitVerification();
                }
            });
        });

        // Trigger Verification from Detail Modal
        els.btnModalVerifyTrigger.addEventListener('click', function () {
            if (currentRecord && currentRecord.id) {
                openVerifyModal(currentRecord.id);
            }
        });

        // Modal Certificate Print Button - renders demo/output.php in new tab after verification
        els.btnPrintModalCert.addEventListener('click', function () {
            if (!currentRecord || (currentRecord.status !== 'verified' && !currentRecord.verified_at)) {
                showToast('যাচাই প্রয়োজন', 'আগে যাচাই করুন', 'warning');
                return;
            }
            openCertificatePrint(currentRecord);
        });

        // Modal Copy JSON Button
        els.btnCopyRecordJSON.addEventListener('click', function () {
            if (currentRecord) {
                const str = JSON.stringify(currentRecord, null, 2);
                navigator.clipboard.writeText(str).then(() => {
                    showToast('কপি সম্পন্ন', 'JSON কপি হয়েছে', 'success');
                });
            }
        });

        // Global Click handling for dropdown dismissals
        document.addEventListener('click', function (e) {
            if (!els.searchFieldBox.contains(e.target) && !els.suggestionsDropdown.contains(e.target)) {
                hideSuggestions();
            }
            if (!els.btnExportMenu.contains(e.target) && !els.exportMenuDropdown.contains(e.target)) {
                els.exportMenuDropdown.hidden = true;
                els.btnExportMenu.setAttribute('aria-expanded', 'false');
            }
        });

        // Global Keyboard Shortcuts
        document.addEventListener('keydown', function (e) {
            const isInput = ['INPUT', 'SELECT', 'TEXTAREA'].includes(document.activeElement.tagName);

            if (e.key === '/' && !isInput) {
                e.preventDefault();
                els.searchInput.focus();
                els.searchInput.select();
            } else if (e.key === 'Escape') {
                hideSuggestions();
                closeRecordModal();
                closeVerifyModal();
            } else if (e.key === 'v' && !isInput) {
                if (state.view === 'table') {
                    els.viewModeCardsBtn.click();
                } else {
                    els.viewModeTableBtn.click();
                }
            }
        });

        // Popstate handler for browser back/forward
        window.addEventListener('popstate', function () {
            location.reload();
        });

        // Initial Search Trigger
        runSearch(state.page || 1);

        if (state.openId > 0) {
            setTimeout(() => openRecordModal(state.openId, 'tabPersonal'), 300);
        }
    }

    // Initialize application when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEvents);
    } else {
        initEvents();
    }
})();