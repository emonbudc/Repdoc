(function () {
    'use strict';

    const $ = (sel, root) => (root || document).querySelector(sel);
    const $$ = (sel, root) => Array.from((root || document).querySelectorAll(sel));

    // ── Toast ──
    function showToast(title, text, type = 'success') {
        const container = $('#toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast-item ${type}`;
        const icons = {
            success: '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>',
            info: '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
            warning: '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
            error: '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>'
        };
        toast.innerHTML = `<div class="toast-icon-box">${icons[type] || icons.info}</div><div class="toast-message-box"><div class="toast-title">${escapeHtml(title)}</div><div class="toast-text">${escapeHtml(text || '')}</div></div>`;
        container.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(40px)';
            setTimeout(() => toast.remove(), 300);
        }, 3800);
    }

    function escapeHtml(str) {
        if (str == null) return '';
        return String(str).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m]);
    }

    // ── Single light theme (enterprise console): no theme switching. ──

    // ── API helper ──
    async function api(endpoint, options = {}) {
        const res = await fetch(endpoint, options);
        const data = await res.json();
        return data;
    }

    // ── Tab Switching ──
    function initTabs() {
        $$('.mgmt-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                $$('.mgmt-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                const target = tab.getAttribute('data-tab');
                $$('.mgmt-content').forEach(c => c.hidden = true);
                $(`#tab${target.charAt(0).toUpperCase() + target.slice(1)}`).hidden = false;
                // Edit tab: show all records immediately (no typing needed)
                if (target === 'edit' && $('#editResults').hidden) {
                    editPage = 1;
                    searchEdit();
                }
            });
        });
    }

    // ══════════════════════════════════════════════════════════════════
    // Tab 1: Add Record
    // ══════════════════════════════════════════════════════════════════
    function initAddForm() {
        $('#addForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = $('#addForm');
            const formData = new FormData(form);
            const name = formData.get('name');
            if (!name || !name.trim()) {
                showToast('ত্রুটি', 'নাম আবশ্যক', 'error');
                return;
            }

            const btn = $('#btnAddSubmit');
            btn.disabled = true;
            btn.innerHTML = '<div class="spinner" style="width:16px;height:16px;border-width:2px;"></div> যোগ হচ্ছে...';

            try {
                const data = await api('api/manage.php', {
                    method: 'POST',
                    body: formData
                });

                if (data.ok) {
                    showToast('সফল', `রেকর্ড যোগ হয়েছে (ID: ${data.id})`, 'success');
                    form.reset();
                    refreshStats();
                } else {
                    showToast('ত্রুটি', data.error || 'যোগ করা যায়নি', 'error');
                }
            } catch (err) {
                showToast('ত্রুটি', 'সার্ভার সংযোগ ব্যর্থ', 'error');
            }

            btn.disabled = false;
            btn.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg><span>রেকর্ড যোগ করুন</span>';
        });
    }

    // ══════════════════════════════════════════════════════════════════
    // Tab 2: Edit / Delete
    // ══════════════════════════════════════════════════════════════════
    let editPage = 1;
    let editingId = null;

    function initEditSearch() {
        $('#btnEditSearch').addEventListener('click', () => { editPage = 1; searchEdit(); });
        $('#editSearchInput').addEventListener('keydown', (e) => {
            if (e.key === 'Enter') { editPage = 1; searchEdit(); }
        });
    }

    async function searchEdit() {
        const q = $('#editSearchInput').value.trim();
        const field = $('#editSearchField').value;

        const params = new URLSearchParams({ action: 'list', q, field, page: editPage, per_page: 20 });
        let data;
        try {
            data = await api(`api/manage.php?${params}`);
        } catch (e) {
            showToast('ত্রুটি', 'সংযোগ ব্যর্থ হয়েছে', 'error');
            return;
        }

        if (!data.ok) {
            showToast('ত্রুটি', data.error, 'error');
            return;
        }

        $('#editResults').hidden = false;
        $('#editFormPanel').hidden = true;
        $('#editResultsCount').textContent = `${data.total}টি রেকর্ড পাওয়া গেছে`;

        const list = $('#editResultsList');
        if (!data.results.length) {
            list.innerHTML = '<div class="mgmt-empty">কোনো রেকর্ড পাওয়া যায়নি</div>';
            $('#editPagination').innerHTML = '';
            return;
        }

        list.innerHTML = data.results.map(r => `
            <div class="mgmt-result-row" data-id="${r.id}">
                <div class="mgmt-result-info">
                    <div class="mgmt-result-name">${escapeHtml(r.name || '—')}${r.name_en ? ` <span style="color:var(--primary);font-size:0.8rem;">(${escapeHtml(r.name_en)})</span>` : ''}</div>
                    <div class="mgmt-result-meta">
                        <span class="mono">${escapeHtml(r.registration_number || '—')}</span>
                        <span>•</span>
                        <span>পিতা: ${escapeHtml(r.father_name || '—')}</span>
                        <span>•</span>
                        <span class="badge-status ${r.status || 'pending'}" style="font-size:0.68rem;">${r.status === 'verified' ? 'যাচাইকৃত' : r.status === 'incomplete' ? 'অসম্পূর্ণ' : 'বাকি'}</span>
                    </div>
                </div>
                <div class="mgmt-result-actions">
                    <button type="button" class="btn-secondary btn-edit-row" data-id="${r.id}" style="padding:6px 12px;font-size:0.8rem;">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        সম্পাদনা
                    </button>
                    <button type="button" class="btn-danger btn-delete-row" data-id="${r.id}" data-name="${escapeHtml(r.name)}" style="padding:6px 12px;font-size:0.8rem;">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        মুছুন
                    </button>
                </div>
            </div>
        `).join('');

        // Attach events
        $$('.btn-edit-row', list).forEach(btn => {
            btn.addEventListener('click', () => openEditForm(parseInt(btn.getAttribute('data-id'))));
        });
        $$('.btn-delete-row', list).forEach(btn => {
            btn.addEventListener('click', () => {
                const id = parseInt(btn.getAttribute('data-id'));
                const name = btn.getAttribute('data-name');
                confirmDelete(id, name);
            });
        });

        // Pagination
        renderEditPagination(data.page, data.pages);
    }

    function renderEditPagination(page, pages) {
        if (pages <= 1) { $('#editPagination').innerHTML = ''; return; }
        let html = '';
        if (page > 1) html += `<button class="page-btn-item" data-p="${page - 1}">‹</button>`;
        for (let i = Math.max(1, page - 2); i <= Math.min(pages, page + 2); i++) {
            html += `<button class="page-btn-item ${i === page ? 'active' : ''}" data-p="${i}">${i}</button>`;
        }
        if (page < pages) html += `<button class="page-btn-item" data-p="${page + 1}">›</button>`;
        $('#editPagination').innerHTML = html;
        $$('.page-btn-item', $('#editPagination')).forEach(btn => {
            btn.addEventListener('click', () => { editPage = parseInt(btn.getAttribute('data-p')); searchEdit(); });
        });
    }

    async function openEditForm(id) {
        const data = await api(`api/manage.php?action=get&id=${id}`);
        if (!data.ok) {
            showToast('ত্রুটি', data.error, 'error');
            return;
        }

        editingId = id;
        const r = data.record;
        $('#editResults').hidden = true;
        $('#editFormPanel').hidden = false;
        $('#editRecordId').textContent = `#${id}`;
        $('#editFormId').value = id;

        $('#ef_reg').value = r.registration_number || '';
        $('#ef_name').value = r.name || '';
        $('#ef_name_en').value = r.name_en || '';
        $('#ef_father').value = r.father_name || '';
        $('#ef_father_en').value = r.father_name_en || '';
        $('#ef_mother').value = r.mother_name || '';
        $('#ef_mother_en').value = r.mother_name_en || '';
        $('#ef_dob').value = r.date_of_birth || '';
        $('#ef_gender').value = r.gender || 'MALE';
        $('#ef_ward').value = r.ward || '';
        $('#ef_bp').value = r.birth_place || '';
        $('#ef_bp_en').value = r.place_of_birth_en || '';
        $('#ef_addr').value = r.permanent_address || '';
        $('#ef_addr_en').value = r.permanent_address_en || '';
        $('#ef_reg_date').value = r.registration_date || '';
        $('#ef_cert_date').value = r.certificate_date || '';
        $('#ef_page').value = r.page_number || '';
        $('#ef_serial').value = r.serial || '';
        $('#ef_src').value = r.source_file || '';
        $('#ef_status').value = r.status || 'pending';

        $('#editFormPanel').scrollIntoView({ behavior: 'smooth' });
    }

    function initEditForm() {
        $('#btnCloseEdit').addEventListener('click', () => {
            $('#editFormPanel').hidden = true;
            $('#editResults').hidden = false;
            editingId = null;
        });

        $('#editForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!editingId) return;

            const formData = new FormData($('#editForm'));
            const btn = $('#btnEditSubmit');
            btn.disabled = true;

            try {
                const data = await api('api/manage.php', { method: 'POST', body: formData });
                if (data.ok) {
                    showToast('সফল', 'রেকর্ড আপডেট হয়েছে', 'success');
                    searchEdit();
                } else {
                    showToast('ত্রুটি', data.error, 'error');
                }
            } catch (err) {
                showToast('ত্রুটি', 'সার্ভার সংযোগ ব্যর্থ', 'error');
            }
            btn.disabled = false;
        });
    }

    // ── Delete ──
    let deleteTargetId = null;

    function confirmDelete(id, name) {
        deleteTargetId = id;
        $('#confirmRecordInfo').textContent = `ID: ${id} — ${name}`;
        $('#confirmModal').hidden = false;
        document.body.classList.add('modal-open');
    }

    function initDelete() {
        $$('[data-close-confirm]').forEach(el => {
            el.addEventListener('click', () => {
                $('#confirmModal').hidden = true;
                document.body.classList.remove('modal-open');
                deleteTargetId = null;
            });
        });

        $('#btnConfirmDelete').addEventListener('click', async () => {
            if (!deleteTargetId) return;
            const btn = $('#btnConfirmDelete');
            btn.disabled = true;

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', deleteTargetId);

            try {
                const data = await api('api/manage.php', { method: 'POST', body: formData });
                if (data.ok) {
                    showToast('সফল', 'রেকর্ড মুছে ফেলা হয়েছে', 'success');
                    $('#confirmModal').hidden = true;
                    document.body.classList.remove('modal-open');
                    deleteTargetId = null;
                    searchEdit();
                    refreshStats();
                } else {
                    showToast('ত্রুটি', data.error, 'error');
                }
            } catch (err) {
                showToast('ত্রুটি', 'সার্ভার সংযোগ ব্যর্থ', 'error');
            }
            btn.disabled = false;
        });

        $('#btnDeleteRecord').addEventListener('click', () => {
            if (!editingId) return;
            const name = $('#ef_name').value;
            confirmDelete(editingId, name);
        });
    }

    // ══════════════════════════════════════════════════════════════════
    // Tab 3: Search & Replace
    // ══════════════════════════════════════════════════════════════════
    function initReplace() {
        $('#btnPreviewReplace').addEventListener('click', previewReplace);
        $('#btnExecuteReplace').addEventListener('click', openReplaceConfirm);

        $$('[data-close-replace-confirm]').forEach(el => {
            el.addEventListener('click', closeReplaceConfirm);
        });

        $('#btnConfirmReplace').addEventListener('click', doExecuteReplace);

        // Match mode toggle
        $$('#tabReplace [data-match-mode]').forEach(btn => {
            btn.addEventListener('click', function () {
                $$('#tabReplace [data-match-mode]').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            });
        });
    }

    function getReplaceMatchMode() {
        const active = $('#tabReplace [data-match-mode].active');
        return active ? active.getAttribute('data-match-mode') : 'partial';
    }

    async function previewReplace() {
        const find = $('#replaceFind').value.trim();
        const replace = $('#replaceWith').value;
        if (!find) {
            showToast('ত্রুটি', 'অনুসন্ধানের টেক্সট দিন', 'error');
            return;
        }

        const field = $('#replaceField').value;
        const matchMode = getReplaceMatchMode();
        const params = new URLSearchParams({ action: 'find', find, replace, field, match_mode: matchMode });
        const data = await api(`api/manage.php?${params}`);

        if (!data.ok) {
            showToast('ত্রুটি', data.error, 'error');
            return;
        }

        const preview = $('#replacePreview');
        preview.hidden = false;
        $('#replacePreviewCount').textContent = `${data.match_count}টি রেকর্ডে পরিবর্তন হবে` + (data.preview_capped ? ' (প্রথম ২০০টি দেখানো হচ্ছে)' : '');
        $('#btnExecuteReplace').disabled = data.match_count === 0;

        const list = $('#replacePreviewList');
        if (!data.matches.length) {
            list.innerHTML = '<div class="mgmt-empty">কোনো ম্যাচ পাওয়া যায়নি</div>';
            return;
        }

        const findRegex = new RegExp('(' + escapeHtml(find).replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
        const fieldLabels = {
            name: 'নাম', name_en: 'নাম (EN)', father_name: 'পিতা', father_name_en: 'পিতা (EN)',
            mother_name: 'মাতা', mother_name_en: 'মাতা (EN)', registration_number: 'নিবন্ধন নং',
            ward: 'ওয়ার্ড', birth_place: 'জন্মস্থান', place_of_birth_en: 'জন্মস্থান (EN)',
            permanent_address: 'ঠিকানা', permanent_address_en: 'ঠিকানা (EN)',
            date_of_birth: 'জন্ম তারিখ', source_file: 'সোর্স ফাইল'
        };

        list.innerHTML = data.matches.map(m => {
            const changesHtml = (m.changes || []).map(c => {
                const origHighlighted = escapeHtml(c.original).replace(findRegex, '<mark>$1</mark>');
                const replHighlighted = escapeHtml(c.replaced).replace(findRegex, '<mark>$1</mark>');
                return `
                    <div class="mgmt-change-row">
                        <span class="mgmt-change-field">${fieldLabels[c.field] || c.field}</span>
                        <div class="mgmt-change-values">
                            <span class="mgmt-change-old">${origHighlighted}</span>
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" style="flex-shrink:0;color:var(--accent-emerald);"><polyline points="9 18 15 12 9 6"/></svg>
                            <span class="mgmt-change-new">${replHighlighted}</span>
                        </div>
                    </div>
                `;
            }).join('');

            return `
                <div class="mgmt-result-row">
                    <div class="mgmt-result-info">
                        <div class="mgmt-result-meta">
                            <span class="mono" style="color:var(--primary);">ID: ${m.id}</span>
                            <span>•</span>
                            <span class="mono" style="font-size:0.75rem;">${escapeHtml(m.reg_num || '—')}</span>
                            <span>•</span>
                            <span class="badge-status ${m.status || 'pending'}" style="font-size:0.68rem;">${m.status === 'verified' ? 'যাচাইকৃত' : m.status === 'incomplete' ? 'অসম্পূর্ণ' : 'বাকি'}</span>
                        </div>
                        <div class="mgmt-result-name" style="font-size:0.9rem; margin-top:4px;">${escapeHtml(m.name || '—')}${m.name_en ? ` <span style="color:var(--primary);font-size:0.78rem;">(${escapeHtml(m.name_en)})</span>` : ''}</div>
                        <div class="mgmt-result-meta" style="margin-top:2px;">
                            <span>পিতা: ${escapeHtml(m.father_name || '—')}</span>
                            <span>•</span>
                            <span>ওয়ার্ড: ${escapeHtml(m.ward || '—')}</span>
                        </div>
                        <div class="mgmt-changes-list" style="margin-top:10px;">${changesHtml}</div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function openReplaceConfirm() {
        const find = $('#replaceFind').value.trim();
        const replace = $('#replaceWith').value;
        const field = $('#replaceField').value;
        if (!find) return;

        const fieldLabels = {
            name: 'নাম (বাংলা)', name_en: 'নাম (English)', father_name: 'পিতার নাম (বাংলা)',
            father_name_en: 'পিতার নাম (English)', mother_name: 'মাতার নাম (বাংলা)',
            mother_name_en: 'মাতার নাম (English)', registration_number: 'নিবন্ধন নম্বর',
            ward: 'ওয়ার্ড', birth_place: 'জন্মস্থান', place_of_birth_en: 'Place of Birth',
            permanent_address: 'স্থায়ী ঠিকানা', permanent_address_en: 'Permanent Address',
            date_of_birth: 'জন্ম তারিখ', source_file: 'সোর্স ফাইল'
        };
        const fieldLabel = field ? (fieldLabels[field] || field) : 'সকল ফিল্ড';

        const body = $('#replaceConfirmBody');
        body.innerHTML = `
            <p style="font-size:0.95rem; color:var(--text-secondary); margin-bottom:20px;">আপনি কি নিশ্চিত এই পরিবর্তনগুলো করতে চান?</p>
            <div style="background:var(--bg-subtle); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:16px; margin-bottom:16px;">
                <div style="display:flex; align-items:center; justify-content:center; gap:12px; flex-wrap:wrap;">
                    <div style="text-align:center;">
                        <div style="font-size:0.72rem; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.04em; margin-bottom:4px;">খুঁজুন</div>
                        <div style="font-size:1rem; font-weight:700; color:var(--accent-rose); background:var(--accent-rose-light); padding:6px 14px; border-radius:var(--radius-sm);">${escapeHtml(find)}</div>
                    </div>
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="var(--accent-emerald)" stroke-width="2.5" style="flex-shrink:0;"><polyline points="9 18 15 12 9 6"/></svg>
                    <div style="text-align:center;">
                        <div style="font-size:0.72rem; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.04em; margin-bottom:4px;">রিপ্লেস</div>
                        <div style="font-size:1rem; font-weight:700; color:var(--accent-emerald); background:var(--accent-emerald-light); padding:6px 14px; border-radius:var(--radius-sm);">${escapeHtml(replace || '(ফাঁকা)')}</div>
                    </div>
                </div>
            </div>
            <div style="display:flex; align-items:center; justify-content:center; gap:8px; font-size:0.82rem; color:var(--text-muted);">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/></svg>
                <span>ফিল্ড: <strong style="color:var(--primary);">${escapeHtml(fieldLabel)}</strong></span>
            </div>
            <p style="margin-top:16px; font-size:0.82rem; color:var(--accent-rose); font-weight:600; display:flex; align-items:center; justify-content:center; gap:6px;">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                এই কাজটি পূর্বাবস্থায় ফেরানো যাবে না
            </p>
        `;

        $('#replaceConfirmModal').hidden = false;
        document.body.classList.add('modal-open');
    }

    function closeReplaceConfirm() {
        $('#replaceConfirmModal').hidden = true;
        document.body.classList.remove('modal-open');
    }

    async function doExecuteReplace() {
        const find = $('#replaceFind').value.trim();
        const replace = $('#replaceWith').value;
        const field = $('#replaceField').value;
        const matchMode = getReplaceMatchMode();
        closeReplaceConfirm();

        const formData = new FormData();
        formData.append('action', 'replace');
        formData.append('find', find);
        formData.append('replace', replace);
        formData.append('field', field);
        formData.append('match_mode', matchMode);

        const btn = $('#btnExecuteReplace');
        btn.disabled = true;

        try {
            const data = await api('api/manage.php', { method: 'POST', body: formData });
            if (data.ok) {
                showToast('সফল', data.message, 'success');
                previewReplace();
                refreshStats();
            } else {
                showToast('ত্রুটি', data.error, 'error');
            }
        } catch (err) {
            showToast('ত্রুটি', 'সার্ভার সংযোগ ব্যর্থ', 'error');
        }
        btn.disabled = false;
    }

    // ── Stats Refresh ──
    async function refreshStats() {
        try {
            const data = await api('api/manage.php?action=stats');
            if (data.ok) {
                const el = $('#statTotal');
                el.setAttribute('data-val', data.total);
                el.textContent = data.total.toLocaleString('en-US');
            }
        } catch (e) {}
    }

    // ── Init ──
    function init() {
        initTabs();
        initAddForm();
        initEditSearch();
        initEditForm();
        initDelete();
        initReplace();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
