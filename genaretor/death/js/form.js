/* ── Inline SVG helper ──
   The page ships one <symbol> sprite instead of an icon font, so dynamically
   added icons are injected as <svg><use href="#id"/></svg> markup. */
function icon(name, extraClass) {
    return '<svg class="icon' + (extraClass ? ' ' + extraClass : '') + '" viewBox="0 0 24 24" aria-hidden="true"><use href="#' + name + '"/></svg>';
}

/* ── Mobile nav toggle ── */
function toggleNav() {
    const nav = document.getElementById('siteNav');
    if (!nav) return;
    const open = nav.classList.toggle('is-open');
    const btn  = document.querySelector('.nav-toggle');
    if (btn) {
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        btn.setAttribute('aria-label', open ? 'মেনু বন্ধ করুন' : 'মেনু খুলুন');
    }
}

/* ── Theme toggle ── */
function applyThemeUI(theme) {
    const label = document.getElementById('themeToggleLabel');
    if (label) label.textContent = theme === 'dark' ? 'Light' : 'Dark';
}
function toggleTheme() {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const next = isDark ? 'light' : 'dark';
    if (next === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
    } else {
        document.documentElement.removeAttribute('data-theme');
    }
    const meta = document.querySelector('meta[name="theme-color"]');
    if (meta) meta.setAttribute('content', next === 'dark' ? '#0B0F19' : '#F8FAFC');
    try { localStorage.setItem('siteTheme', next); } catch (e) {}
    applyThemeUI(next);
}
applyThemeUI(document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light');

/* ── Form sections ── */
function scrollToSection(index) {
    const sections = document.querySelectorAll('.form-section');
    if (sections[index]) {
        sections[index].scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function updateStepper() {
    const steps = document.querySelectorAll('.step-item');
    steps.forEach((step, i) => {
        const fields = document.querySelectorAll(`.form-section[data-section="${i}"] input[required], .form-section[data-section="${i}"] select[required]`);
        let allFilled = true;
        fields.forEach(f => { if (!f.value) allFilled = false; });
        step.classList.toggle('completed', allFilled);
        step.classList.remove('active');
    });
    const scrollPos = window.scrollY + 200;
    let activeIdx = 0;
    document.querySelectorAll('.form-section').forEach((sec, i) => {
        const top = sec.offsetTop;
        if (scrollPos >= top) activeIdx = i;
    });
    steps.forEach((s, i) => s.classList.toggle('active', i === activeIdx));
}

/* ── Progress bar ── */
function updateProgressBar() {
    const fields = document.querySelectorAll('#deathForm input[required], #deathForm select[required]');
    let filled = 0;
    fields.forEach(f => { if (f.value) filled++; });
    const pct = fields.length > 0 ? Math.round((filled / fields.length) * 100) : 0;
    const bar = document.getElementById('formProgress');
    if (!bar) return;
    bar.style.width = pct + '%';
    bar.style.background = pct < 30 ? 'var(--danger)' : pct < 70 ? 'var(--warning)' : 'var(--success)';
}

function refreshAll() {
    updateProgressBar();
    updateStepper();
}

/* ── Verify gate: nothing in the form is usable before a successful verify ── */
let formVerified = false;

function setFormLocked(locked) {
    formVerified = !locked;
    document.querySelectorAll('#deathForm input, #deathForm select, #deathForm textarea').forEach(el => {
        el.disabled = locked;
    });
    ['resetBtn', 'submitBtn', 'clearDraftBtn'].forEach(id => {
        const btn = document.getElementById(id);
        if (btn) btn.disabled = locked;
    });
    const note = document.getElementById('formLockNote');
    if (note) note.style.display = locked ? 'flex' : 'none';
    const card = document.getElementById('formCard');
    if (card) card.hidden = locked;
    if (!locked) refreshAll();
}

/* ── Field validation ── */
document.querySelectorAll('#deathForm input[required], #deathForm select[required]').forEach(el => {
    el.addEventListener('blur', () => {
        el.classList.remove('valid');
        el.classList.toggle('invalid', !el.value);
        if (el.value) el.classList.add('valid');
        refreshAll();
    });
    el.addEventListener('input', () => {
        if (el.value) {
            el.classList.remove('invalid');
            el.classList.add('valid');
        } else {
            el.classList.remove('valid');
        }
        refreshAll();
    });
    el.addEventListener('change', refreshAll);
});

/* ── Stagger reveal ── */
document.addEventListener('DOMContentLoaded', () => {
    const groups = document.querySelectorAll('.form-group');
    groups.forEach((g, i) => {
        setTimeout(() => g.classList.add('revealed'), 60 + i * 35);
    });

    const today = new Date().toISOString().split('T')[0];
    if (!document.getElementById('reg_date').value) document.getElementById('reg_date').value = today;
    if (!document.getElementById('issue_date').value) document.getElementById('issue_date').value = today;

    refreshAll();
    setFormLocked(true);

    if (window.REPDOC_DEATH_DEMO) {
        startDemoMode(window.REPDOC_DEATH_DEMO);
    } else {
        restoreDraft();
        initDirectFetch();
    }

    setInterval(autoSaveDraft, 3000);
});

document.getElementById('deathForm').addEventListener('submit', (e) => {
    if (!formVerified) {
        e.preventDefault();
        const note = document.getElementById('formLockNote');
        if (note) {
            note.style.display = 'flex';
            note.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return;
    }
    const fields = document.querySelectorAll('#deathForm input[required], #deathForm select[required]');
    let ok = true;
    fields.forEach(f => {
        if (!f.value) { f.classList.add('invalid'); f.classList.remove('valid'); ok = false; }
    });
    if (!ok) {
        e.preventDefault();
        alert('অনুগ্রহ করে সমস্ত প্রয়োজনীয় ক্ষেত্র পূরণ করুন।');
        return;
    }
    localStorage.removeItem('deathCertDraft');
    document.getElementById('loadingOverlay').style.display = 'flex';
});

/* ── Auto-save Draft (localStorage) ── */
function autoSaveDraft() {
    const fields = document.querySelectorAll('#deathForm input, #deathForm select');
    const data = {};
    fields.forEach(f => { data[f.id || f.name] = f.value; });
    localStorage.setItem('deathCertDraft', JSON.stringify(data));
    const indicator = document.getElementById('saveIndicator');
    if (!indicator) return;
    indicator.className = 'save-indicator saved';
    indicator.innerHTML = icon('i-check-circle') + ' খসড়া সংরক্ষিত ' + new Date().toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
    clearTimeout(indicator._timeout);
    indicator._timeout = setTimeout(() => {
        indicator.className = 'save-indicator';
        indicator.innerHTML = icon('i-circle') + ' স্বয়ংক্রিয় সংরক্ষণ প্রস্তুত';
    }, 3000);
}

function restoreDraft() {
    const saved = localStorage.getItem('deathCertDraft');
    if (!saved) return;
    try {
        const data = JSON.parse(saved);
        let restored = 0;
        for (const [id, val] of Object.entries(data)) {
            if (!val) continue;
            const el = document.getElementById(id);
            if (el) {
                el.value = val;
                el.dispatchEvent(new Event('input'));
                restored++;
            }
        }
        if (restored > 0) {
            const indicator = document.getElementById('saveIndicator');
            indicator.className = 'save-indicator saved';
            indicator.innerHTML = icon('i-refresh') + ' খসড়া ফিরিয়ে আনা হয়েছে (' + restored + ' টি ঘর)';
            setTimeout(() => {
                indicator.className = 'save-indicator';
                indicator.innerHTML = icon('i-circle') + ' স্বয়ংক্রিয় সংরক্ষণ প্রস্তুত';
            }, 4000);
            refreshAll();
        }
    } catch(e) {}
}

/* ── Reset ── */
document.getElementById('resetBtn').addEventListener('click', () => {
    if (confirm('আপনি কি নিশ্চিত যে আপনি ফর্মটি রিসেট করতে চান?')) {
        document.getElementById('deathForm').reset();
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('reg_date').value = today;
        document.getElementById('issue_date').value = today;
        document.querySelectorAll('#deathForm input, #deathForm select').forEach(i => { i.classList.remove('invalid'); i.classList.remove('valid'); });
        localStorage.removeItem('deathCertDraft');
        refreshAll();
        updateDodText();
    }
});

/* ── Clear Draft ── */
function clearAutoSaveDraft() {
    if (confirm('সংরক্ষিত খসড়া মুছে ফেলতে চান কি?')) {
        localStorage.removeItem('deathCertDraft');
        document.getElementById('deathForm').reset();
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('reg_date').value = today;
        document.getElementById('issue_date').value = today;
        document.querySelectorAll('#deathForm input, #deathForm select').forEach(i => { i.classList.remove('invalid'); i.classList.remove('valid'); });
        const indicator = document.getElementById('saveIndicator');
        indicator.className = 'save-indicator saved';
        indicator.innerHTML = icon('i-trash') + ' খসড়া মুছে ফেলা হয়েছে';
        setTimeout(() => {
            indicator.className = 'save-indicator';
            indicator.innerHTML = icon('i-circle') + ' স্বয়ংক্রিয় সংরক্ষণ প্রস্তুত';
        }, 3000);
        refreshAll();
        updateDodText();
    }
}

/* ── Date of death → text ── */
function convertYearToText(year) {
    if (year >= 2000 && year < 3000) {
        const rem = year % 1000;
        if (rem === 0) return 'Two Thousand';
        return `Two Thousand ${numToText(rem)}`;
    }
    if (year >= 1900 && year < 2000) {
        const tens = year % 100;
        return tens === 0 ? 'Nineteen Hundred' : `Nineteen Hundred ${numToText(tens)}`;
    }
    const th = Math.floor(year / 1000), rem = year % 1000;
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

function updateDodText() {
    const dodEl  = document.getElementById('dod');
    const dodtEl = document.getElementById('dodt');
    if (!dodEl || !dodtEl) return;
    if (!dodEl.value) { dodtEl.value = ''; return; }
    const [y, m, d] = dodEl.value.split('-').map(Number);
    const ordinals = ['First','Second','Third','Fourth','Fifth','Sixth','Seventh','Eighth','Ninth','Tenth',
        'Eleventh','Twelfth','Thirteenth','Fourteenth','Fifteenth','Sixteenth','Seventeenth',
        'Eighteenth','Nineteenth','Twentieth','Twenty First','Twenty Second','Twenty Third',
        'Twenty Fourth','Twenty Fifth','Twenty Sixth','Twenty Seventh','Twenty Eighth',
        'Twenty Ninth','Thirtieth','Thirty First'];
    const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    dodtEl.value = `${ordinals[d-1]} of ${months[m-1]} ${convertYearToText(y)}`;
    refreshAll();
}

/* ── Direct Fetch (BDRIS UDRN verification) ── */
let fetchSessionActive = false;

async function initDirectFetch() {
    if (fetchSessionActive) return;
    const statusEl = document.getElementById('fetchStatus');
    statusEl.style.display = 'block';
    statusEl.innerHTML = icon('i-spinner','spin') + ' CAPTCHA লোড হচ্ছে…';
    statusEl.style.color = 'var(--text-muted)';

    try {
        const resp = await fetch('api/fetch-death.php?action=init');
        const data = await resp.json();
        if (!data.ok) {
            statusEl.innerHTML = (data.error || 'Failed to load captcha');
            statusEl.style.color = 'var(--danger)';
            return;
        }
        document.getElementById('fetchCaptchaImg').src = 'data:' + (data.captcha_mime || 'image/png') + ';base64,' + data.captcha_base64;
        document.getElementById('fetchCaptchaToken').value = data.captcha_token;
        fetchSessionActive = true;
        statusEl.style.display = 'none';
    } catch (err) {
        statusEl.innerHTML = 'Network error: ' + err.message;
        statusEl.style.color = 'var(--danger)';
    }
}

async function refreshCaptcha() {
    fetchSessionActive = false;
    const statusEl = document.getElementById('fetchStatus');
    statusEl.style.display = 'block';
    statusEl.innerHTML = icon('i-spinner','spin') + ' CAPTCHA Refresh হচ্ছে…';
    statusEl.style.color = 'var(--text-muted)';
    document.getElementById('fetchCaptchaInput').value = '';
    await initDirectFetch();
}

function clearDirectFetch() {
    document.getElementById('fetchUbrn').value = '';
    document.getElementById('fetchDod').value = '';
    document.getElementById('fetchCaptchaInput').value = '';
    document.getElementById('fetchStatus').style.display = 'none';
    document.getElementById('deathForm').reset();
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('reg_date').value = today;
    document.getElementById('issue_date').value = today;
    document.querySelectorAll('#deathForm input, #deathForm select').forEach(i => { i.classList.remove('invalid'); i.classList.remove('valid'); });
    localStorage.removeItem('deathCertDraft');
    refreshAll();
    setFormLocked(true);
}

/* Accept ISO (YYYY-MM-DD), dd/mm/yyyy and dd Mon yyyy. Returns YYYY-MM-DD. */
function parseAnyDate(str) {
    if (!str) return '';
    const iso = String(str).match(/\b(\d{4})-(\d{2})-(\d{2})\b/);
    if (iso) return `${iso[1]}-${iso[2]}-${iso[3]}`;
    const dmy = String(str).match(/\b(\d{1,2})[\/.-](\d{1,2})[\/.-](\d{4})\b/);
    if (dmy) return `${dmy[3]}-${dmy[2].padStart(2,'0')}-${dmy[1].padStart(2,'0')}`;
    const mm = {
        jan:1,feb:2,mar:3,apr:4,may:5,jun:6,jul:7,aug:8,sep:9,oct:10,nov:11,dec:12
    };
    const txt = String(str).match(/\b(\d{1,2})\s+([A-Za-z]{3,9})\s+(\d{4})\b/);
    if (txt) {
        const m = mm[txt[2].slice(0,3).toLowerCase()];
        return m ? `${txt[3]}-${String(m).padStart(2,'0')}-${txt[1].padStart(2,'0')}` : '';
    }
    return '';
}

function fillDeathRecord(r, fallbackDod) {
    let filled = 0;

    function setVal(id, val) {
        if (!val) return;
        const el = document.getElementById(id);
        if (!el) return;
        el.value = val;
        el.dispatchEvent(new Event('input'));
        el.dispatchEvent(new Event('change'));
        filled++;
    }

    setVal('death_registration_number', r.drn || '');
    setVal('name_bn', r.name_bn || '');
    setVal('name_en', r.name_en || '');
    setVal('mother_name_bn', r.mother_name_bn || '');
    setVal('mother_name_en', r.mother_name_en || '');
    setVal('mother_nat_bn', r.mother_nat_bn || 'বাংলাদেশী');
    setVal('mother_nat_en', r.mother_nat_en || 'Bangladeshi');
    setVal('father_name_bn', r.father_name_bn || '');
    setVal('father_name_en', r.father_name_en || '');
    setVal('father_nat_bn', r.father_nat_bn || 'বাংলাদেশী');
    setVal('father_nat_en', r.father_nat_en || 'Bangladeshi');
    setVal('pod_bn', r.pod_bn || '');
    setVal('pod_en', r.pod_en || '');
    setVal('cause_bn', r.cause_bn || '');
    setVal('cause_en', r.cause_en || '');

    const dobParsed = parseAnyDate(r.dob);
    if (dobParsed) setVal('dob', dobParsed);

    const dodParsed = parseAnyDate(r.dod) || fallbackDod;
    if (dodParsed) {
        const el = document.getElementById('dod');
        el.value = dodParsed;
        el.dispatchEvent(new Event('change'));
        updateDodText();
        filled++;
    }

    if (r.sex) {
        const el = document.getElementById('gender');
        const v = String(r.sex).toUpperCase();
        el.value = (v.indexOf('MALE') !== -1 && v.indexOf('FE') === -1) ? 'Male' : 'Female';
        el.dispatchEvent(new Event('change'));
        filled++;
    }

    const regParsed = parseAnyDate(r.reg_date);
    const issParsed = parseAnyDate(r.issue_date);
    if (regParsed) setVal('reg_date', regParsed);
    if (issParsed) setVal('issue_date', issParsed);

    if (r.reg_office) {
        setVal('nibkar', r.reg_office);
    }

    refreshAll();
    return filled;
}

function startDemoMode(record) {
    const filled = fillDeathRecord(record, record.dod);

    const verify = document.querySelector('.verify-card');
    if (verify) verify.style.display = 'none';

    setFormLocked(false);

    const card = document.getElementById('formCard');
    if (card && !document.getElementById('demoNotice')) {
        const notice = document.createElement('div');
        notice.id = 'demoNotice';
        notice.className = 'bulk-status';
        notice.style.cssText = 'display:block;margin:16px 0 0;color:var(--success)';
        notice.innerHTML = icon('i-check-circle') + ' ডেমো রেকর্ড লোড হয়েছে — যাচাই ছাড়াই সম্পাদনা করে সনদ তৈরি করুন।';
        card.insertBefore(notice, card.firstChild);
    }

    setTimeout(() => {
        document.getElementById('deathForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 400);
}

async function doDirectFetch() {
    const udrn = document.getElementById('fetchUbrn').value.trim();
    const dod = document.getElementById('fetchDod').value.trim();
    const captcha = document.getElementById('fetchCaptchaInput').value.trim();
    const statusEl = document.getElementById('fetchStatus');

    if (!udrn || !dod || !captcha) {
        statusEl.style.display = 'block';
        statusEl.innerHTML = 'অনুগ্রহ করে সব ফিল্ড পূরণ করুন।';
        statusEl.style.color = 'var(--warning)';
        return;
    }
    if (!/^\d{17}$/.test(udrn)) {
        statusEl.style.display = 'block';
        statusEl.innerHTML = 'মৃত্যু নিবন্ধন নম্বর ১৭ অংকের হতে হবে।';
        statusEl.style.color = 'var(--warning)';
        return;
    }

    statusEl.style.display = 'block';
    statusEl.innerHTML = icon('i-spinner','spin') + ' BDRIS থেকে ডাটা ফেচ করা হচ্ছে…';
    statusEl.style.color = 'var(--text-muted)';

    const formData = new FormData();
    formData.append('udrn', udrn);
    formData.append('death_date', dod);
    formData.append('captcha_answer', captcha);
    formData.append('captcha_token', document.getElementById('fetchCaptchaToken').value);

    try {
        const resp = await fetch('api/fetch-death.php', { method: 'POST', body: formData });
        const data = await resp.json();

        if (!data.ok) {
            const errMsg = data.errors ? data.errors.join('; ') : (data.error || 'Unknown error');
            statusEl.innerHTML = errMsg;
            statusEl.style.color = 'var(--danger)';
            if (data.captcha_expired) {
                setTimeout(refreshCaptcha, 1500);
            }
            return;
        }

        const filled = fillDeathRecord(data.record, dod);

        statusEl.innerHTML = icon('i-check-circle') + ' ' + filled + 'টি ফিল্ড পূরণ হয়েছে — বাকি তথ্য যাচাই করে সম্পূর্ণ করুন।';
        statusEl.style.color = 'var(--success)';

        setFormLocked(false);
        setTimeout(() => {
            document.getElementById('deathForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 600);
    } catch (err) {
        statusEl.innerHTML = 'Network error: ' + err.message;
        statusEl.style.color = 'var(--danger)';
    }
}
