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

/* ── Theme toggle (light is the site default; dark is the attribute) ──
   Shares the `siteTheme` key with the homepage, so the choice follows the user
   across every page. The sun/moon icons are swapped by CSS from [data-theme]. */
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
// Sync the toggle button UI with the theme applied pre-paint
applyThemeUI(document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light');

/* ── Form sections ── */
const SECTION_IDS = ['issuedate','dob','name_bn','mother_name_bn','addressx'];

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
    // Set active based on scroll position
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
    const fields = document.querySelectorAll('#birthForm input[required], #birthForm select[required]');
    let filled = 0;
    fields.forEach(f => { if (f.value) filled++; });
    const pct = fields.length > 0 ? Math.round((filled / fields.length) * 100) : 0;
    const bar = document.getElementById('formProgress');
    bar.style.width = pct + '%';
    bar.style.background = pct < 30 ? 'var(--danger)' : pct < 70 ? 'var(--warning)' : 'var(--success)';
}

/* ── Progress + stepper + preview update ── */
function refreshAll() {
    updateProgressBar();
    updatePreview();
    updateStepper();
}

/* ── Verify gate: nothing in the form is usable before a successful verify ── */
let formVerified = false;

function setFormLocked(locked) {
    formVerified = !locked;
    document.querySelectorAll('#birthForm input, #birthForm select, #birthForm textarea').forEach(el => {
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

/* ── Field validation + update all ── */
document.querySelectorAll('#birthForm input[required], #birthForm select[required]').forEach(el => {
    el.addEventListener('blur', () => {
        el.classList.remove('valid');
        el.classList.toggle('invalid', !el.value);
        if (el.value) el.classList.add('valid');
        refreshAll();
        updatePreview();
        updateStepper();
    });
    el.addEventListener('input', () => {
        if (el.value) {
            el.classList.remove('invalid');
            el.classList.add('valid');
        } else {
            el.classList.remove('valid');
        }
        refreshAll();
        updatePreview();
        updateStepper();
    });
    el.addEventListener('change', () => {
        refreshAll();
        updatePreview();
        updateStepper();
    });
});

/* ── Stagger reveal ── */
document.addEventListener('DOMContentLoaded', () => {
    const groups = document.querySelectorAll('.form-group');
    groups.forEach((g, i) => {
        setTimeout(() => g.classList.add('revealed'), 60 + i * 35);
    });

    const today = new Date().toISOString().split('T')[0];
    if (!document.getElementById('issuedate').value) document.getElementById('issuedate').value = today;
    if (!document.getElementById('givendate').value) document.getElementById('givendate').value = today;

    refreshAll();
    updatePreview();
    updateStepper();

    // Lock the form until a successful verify-and-fill
    setFormLocked(true);

    // Restore draft (values only — stays locked until verified)
    restoreDraft();

    // Captcha loads immediately since verify is the only entry point
    initDirectFetch();

    // Auto-save every 3s
    setInterval(autoSaveDraft, 3000);
});

// Block submit before verification (defense in depth — button is also disabled)
document.getElementById('birthForm').addEventListener('submit', (e) => {
    if (!formVerified) {
        e.preventDefault();
        const note = document.getElementById('formLockNote');
        if (note) {
            note.style.display = 'flex';
            note.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
});

/* ── Real-time Certificate Preview ── */
function togglePreview() {
    const panel = document.getElementById('certPreview');
    const btn = document.getElementById('previewToggle');
    if (!panel || !btn) return;
    panel.classList.toggle('visible');
    const visible = panel.classList.contains('visible');
    btn.classList.toggle('active', visible);
    btn.setAttribute('aria-pressed', visible ? 'true' : 'false');
    const label = btn.querySelector('.preview-toggle-label');
    if (label) label.textContent = visible ? 'Hide Preview' : 'Live Preview';
}

function updatePreview() {
    const updated = document.getElementById('previewUpdated');
    if (!updated && !document.getElementById('certPreview')) return;
    const map = {
        'issuedate': 'pv_issuedate',
        'givendate': 'pv_givendate',
        'birth_registration_number': 'pv_brn',
        'gender': 'pv_gender',
        'name_bn': 'pv_name_bn',
        'name_en': 'pv_name_en',
        'dob': 'pv_dob',
        'bp_bn': 'pv_bp',
        'mother_name_bn': 'pv_mother',
        'father_name_bn': 'pv_father',
        'addressx': 'pv_address'
    };
    for (const [srcId, destId] of Object.entries(map)) {
        const src = document.getElementById(srcId);
        const dest = document.getElementById(destId);
        if (src && dest) {
            const val = src.value || '';
            dest.textContent = val || '—';
            dest.className = 'preview-value' + (val ? '' : ' empty');
        }
    }
    if (updated) updated.textContent = 'updated ' + new Date().toLocaleTimeString();
}

/* ── Auto-save Draft (localStorage) ── */
function autoSaveDraft() {
    const fields = document.querySelectorAll('#birthForm input, #birthForm select');
    const data = {};
    fields.forEach(f => { data[f.id || f.name] = f.value; });
    localStorage.setItem('birthCertDraft', JSON.stringify(data));
    const indicator = document.getElementById('saveIndicator');
    indicator.className = 'save-indicator saved';
    indicator.innerHTML = icon('i-check-circle') + ' খসড়া সংরক্ষিত ' + new Date().toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
    clearTimeout(indicator._timeout);
    indicator._timeout = setTimeout(() => {
        indicator.className = 'save-indicator';
        indicator.innerHTML = icon('i-circle') + ' স্বয়ংক্রিয় সংরক্ষণ প্রস্তুত';
    }, 3000);
}

function restoreDraft() {
    const saved = localStorage.getItem('birthCertDraft');
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
            updatePreview();
            refreshAll();
            updateStepper();
            if (document.getElementById('dob').value) updateDobText();
        }
    } catch(e) {}
}

/* ── Reset ── */
document.getElementById('resetBtn').addEventListener('click', () => {
    if (confirm('আপনি কি নিশ্চিত যে আপনি ফর্মটি রিসেট করতে চান?')) {
        document.getElementById('birthForm').reset();
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('issuedate').value = today;
        document.getElementById('givendate').value = today;
        document.querySelectorAll('input, select').forEach(i => { i.classList.remove('invalid'); i.classList.remove('valid'); });
        localStorage.removeItem('birthCertDraft');
        refreshAll();
        updatePreview();
        updateStepper();
        updateDobText();
    }
});

/* ── Clear Draft ── */
function clearAutoSaveDraft() {
    if (confirm('সংরক্ষিত খসড়া মুছে ফেলতে চান কি?')) {
        localStorage.removeItem('birthCertDraft');
        document.getElementById('birthForm').reset();
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('issuedate').value = today;
        document.getElementById('givendate').value = today;
        document.querySelectorAll('input, select').forEach(i => { i.classList.remove('invalid'); i.classList.remove('valid'); });
        const indicator = document.getElementById('saveIndicator');
        indicator.className = 'save-indicator saved';
        indicator.innerHTML = icon('i-trash') + ' খসড়া মুছে ফেলা হয়েছে';
        setTimeout(() => {
            indicator.className = 'save-indicator';
            indicator.innerHTML = icon('i-circle') + ' স্বয়ংক্রিয় সংরক্ষণ প্রস্তুত';
        }, 3000);
        refreshAll();
        updatePreview();
        updateStepper();
        updateDobText();
    }
}

/* ── Submit ── */
document.getElementById('birthForm').addEventListener('submit', function(e) {
    const fields = document.querySelectorAll('input[required], select[required]');
    let ok = true;
    fields.forEach(f => {
        if (!f.value) { f.classList.add('invalid'); f.classList.remove('valid'); ok = false; }
    });
    if (!ok) {
        e.preventDefault();
        alert('অনুগ্রহ করে সমস্ত প্রয়োজনীয় ক্ষেত্র পূরণ করুন।');
        return;
    }
    localStorage.removeItem('birthCertDraft');
    document.getElementById('loadingOverlay').style.display = 'flex';
});

/* ── Bulk fill ── */
function fillFormFromBulk() {
    const raw = document.getElementById('bulkDataInput').value;
    if (!raw.trim()) { showStatus('bulkFillStatus', 'কোনো ডাটা পাওয়া যায়নি।', 'warn'); return; }

    const mapping = {
        'জন্ম নিবন্ধন নম্বর':  { id: 'birth_registration_number' },
        'জন্ম তারিখ':           { id: 'dob',      type: 'date' },
        'নিবন্ধনের তারিখ':      { id: 'issuedate', type: 'date' },
        'লিঙ্গ':                { id: 'gender',    type: 'select' },
        'পুরো নাম':             { id: 'name_bn' },
        'পুরো নাম ইংরেজিতে':    { id: 'name_en' },
        'মাতার নাম':            { id: 'mother_name_bn' },
        'মাতার নাম ইংরেজিতে':   { id: 'mother_name_en' },
        'পিতার নাম':            { id: 'father_name_bn' },
        'পিতার নাম ইংরেজিতে':   { id: 'father_name_en' },
        'জন্মস্থান':            { id: 'bp_bn',  type: 'birthplace_bn' },
        'জন্মস্থান (ইংরেজি)':   { id: 'bp_en',  type: 'birthplace_en' },
        'জন্মস্থান (ইংরেজিতে)': { id: 'bp_en',  type: 'birthplace_en' },
        'স্থায়ী ঠিকানা':       { id: 'addressx' },
        'স্থায়ী ঠিকানা (ইংরেজি)':   { id: 'address' },
        'স্থায়ী ঠিকানা (ইংরেজিতে)': { id: 'address' },
    };

    const lines = raw.split('\n').map(l => l.trim()).filter(l => l);
    let filled = 0;
    for (let i = 0; i < lines.length; i++) {
        const label = lines[i].replace(/\s+/g, ' ').trim();
        const value = lines[i + 1] ? lines[i + 1].trim() : '';
        if (!value) continue;
        const fi = mapping[label];
        if (!fi) continue;
        const el = document.getElementById(fi.id);
        if (!el) continue;

        if (fi.type === 'date') {
            const parts = value.split('/');
            if (parts.length === 3) {
                el.value = `${parts[2]}-${parts[1].padStart(2,'0')}-${parts[0].padStart(2,'0')}`;
                el.dispatchEvent(new Event('change'));
                filled++;
            }
        } else if (fi.type === 'select') {
            const v = value.toUpperCase();
            el.value = (v === 'MALE' || v === 'পুরুষ') ? 'Male' : (v === 'FEMALE' || v === 'মহিলা') ? 'Female' : '';
            el.dispatchEvent(new Event('change'));
            filled++;
        } else if (fi.type === 'birthplace_bn' || fi.type === 'birthplace_en') {
            const parts = value.split(',').map(p => p.trim()).filter(p => p);
            const country  = parts[parts.length - 1];
            const district = parts[parts.length - 3];
            el.value = (district && country) ? `${district}, ${country}` : value;
            el.dispatchEvent(new Event('input'));
            filled++;
        } else {
            el.value = value;
            el.dispatchEvent(new Event('input'));
            filled++;
        }
        i++;
    }

    const iss = document.getElementById('issuedate');
    const giv = document.getElementById('givendate');
    if (iss.value && !giv.value) giv.value = iss.value;

    refreshAll();
    updatePreview();
    updateStepper();
    filled > 0
        ? showStatus('bulkFillStatus', `${filled}টি ফিল্ড সফলভাবে পূরণ হয়েছে!`, 'ok')
        : showStatus('bulkFillStatus', 'কোনো ফিল্ড মেলেনি। ডাটা ফরম্যাট চেক করুন।', 'err');
}

/* ── PDF ── */
function handlePdfSelect(input) {
    const file = input.files[0];
    if (!file) return;
    document.getElementById('pdfFileName').textContent = file.name;
    document.getElementById('pdfFillBtn').style.display = 'flex';
}

const dropZone = document.getElementById('pdfDropZone');
if (dropZone) {
    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
    dropZone.addEventListener('dragleave', ()=> dropZone.classList.remove('drag-over'));
    dropZone.addEventListener('drop', e => {
        e.preventDefault(); dropZone.classList.remove('drag-over');
        const file = e.dataTransfer.files[0];
        if (file && file.type === 'application/pdf') {
            document.getElementById('pdfFileInput').files = e.dataTransfer.files;
            document.getElementById('pdfFileName').textContent = file.name;
            document.getElementById('pdfFillBtn').style.display = 'flex';
        }
    });
}

async function fillFormFromPdf() {
    const input = document.getElementById('pdfFileInput');
    if (!input.files[0]) { showStatus('pdfStatus','কোনো PDF সিলেক্ট করা হয়নি।','warn'); return; }
    showStatus('pdfStatus', icon('i-spinner','spin') + ' PDF পড়া হচ্ছে…', 'ok', true);

    try {
        const arrayBuffer = await input.files[0].arrayBuffer();
        const pdf = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;
        let fullText = '';
        for (let i = 1; i <= pdf.numPages; i++) {
            const page = await pdf.getPage(i);
            const tc   = await page.getTextContent();
            fullText  += tc.items.map(it => it.str).join(' ') + '\n';
        }

        function fixBrokenBangla(str) {
            return str
                .replace(/\u09be\s+/g,'\u09be').replace(/\u09bf\s+/g,'\u09bf')
                .replace(/\u09c0\s+/g,'\u09c0').replace(/\u09c1\s+/g,'\u09c1')
                .replace(/\u09c2\s+/g,'\u09c2').replace(/\u09c7\s+/g,'\u09c7')
                .replace(/\u09cb\s+/g,'\u09cb').replace(/\u09cd\s+/g,'\u09cd')
                .replace(/\u09bc\s+/g,'\u09bc')
                .replace(/([\u0980-\u09ff])\s+([\u0980-\u09ff])/g,'$1$2')
                .replace(/([\u0980-\u09ff])\s+([\u0980-\u09ff])/g,'$1$2')
                .trim();
        }

        const monthMap = {JANUARY:1,FEBRUARY:2,MARCH:3,APRIL:4,MAY:5,JUNE:6,JULY:7,AUGUST:8,SEPTEMBER:9,OCTOBER:10,NOVEMBER:11,DECEMBER:12};
        function parseEngDate(str) {
            const m = str.match(/(\d{1,2})\s+([A-Z]+)\s+(\d{4})/);
            if (!m) return '';
            const mon = monthMap[m[2]];
            return mon ? `${m[3]}-${String(mon).padStart(2,'0')}-${m[1].padStart(2,'0')}` : '';
        }

        const t = fullText;
        const regNoMatch   = t.match(/\b(\d{17})\b/);
        const genderMatch  = t.match(/\b(MALE|FEMALE)\b/i);
        const dateMatches  = [...t.matchAll(/(\d{1,2})\s+(JANUARY|FEBRUARY|MARCH|APRIL|MAY|JUNE|JULY|AUGUST|SEPTEMBER|OCTOBER|NOVEMBER|DECEMBER)\s+(\d{4})/gi)];
        const nameEnMatch  = t.match(/REGISTERED PERSON\s+NAME\s+([A-Z][A-Z\s]+?)(?=\s{2,}|\n|জন্ম)/i);
        const motherEnMatch= t.match(/MOTHER'S NAME\s+([A-Z][A-Z\s]+?)(?=\s{2,}|\n|MOTHER'S\s+NAT)/i);
        const fatherEnMatch= t.match(/FATHER'S NAME\s+([A-Z][A-Z\s]+?)(?=\s{2,}|\n|FATHER'S\s+NAT)/i);
        const pobMatch     = t.match(/PLACE OF BIRTH\s+([A-Z]+)/i);
        const nameBnMatch  = t.match(/নি[\s]*বন্ধি[\s]*ত[\s]*ব্য[\s\S]{0,10}না[\s]*ম\s+([\u0980-\u09ff\s]+?)(?=REGISTERED|\n.*REGISTERED)/i);
        const motherBnMatch= t.match(/মা[\s]*তা[\s]*র[\s]+না[\s]*ম\s+([\u0980-\u09ff\s]+?)(?=MOTHER|\n.*MOTHER)/i);
        const fatherBnMatch= t.match(/পি[\s]*তা[\s]*র[\s]+না[\s]*ম\s+([\u0980-\u09ff\s]+?)(?=FATHER|\n.*FATHER)/i);
        const pobBnMatch   = t.match(/জন্ম[\s]*স্থা[\s]*ন\s+([\u0980-\u09ff]+)/i);

        const districtEn  = pobMatch    ? pobMatch[1].trim() : '';
        const districtBn  = pobBnMatch  ? fixBrokenBangla(pobBnMatch[1]) : '';

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

        setVal('birth_registration_number', regNoMatch ? regNoMatch[1] : '');
        if (dateMatches[0]) { setVal('issuedate', parseEngDate(dateMatches[0][0])); }
        if (dateMatches[1]) { setVal('givendate', parseEngDate(dateMatches[1][0])); }
        if (dateMatches[2]) {
            const el = document.getElementById('dob');
            const v  = parseEngDate(dateMatches[2][0]);
            if (v) { el.value = v; el.dispatchEvent(new Event('change')); filled++; }
        }
        if (genderMatch) {
            const el = document.getElementById('gender');
            el.value = genderMatch[1].toUpperCase() === 'MALE' ? 'Male' : 'Female';
            el.dispatchEvent(new Event('change'));
            filled++;
        }
        setVal('name_bn',        nameBnMatch   ? fixBrokenBangla(nameBnMatch[1])   : '');
        setVal('name_en',        nameEnMatch   ? nameEnMatch[1].trim()             : '');
        setVal('mother_name_bn', motherBnMatch ? fixBrokenBangla(motherBnMatch[1]) : '');
        setVal('mother_name_en', motherEnMatch ? motherEnMatch[1].trim()           : '');
        setVal('father_name_bn', fatherBnMatch ? fixBrokenBangla(fatherBnMatch[1]) : '');
        setVal('father_name_en', fatherEnMatch ? fatherEnMatch[1].trim()           : '');
        if (districtBn) { setVal('bp_bn',    `${districtBn}, বাংলাদেশ`);  setVal('addressx', `${districtBn}, বাংলাদেশ`); }
        if (districtEn) { setVal('bp_en',    `${districtEn}, BANGLADESH`); setVal('address',  `${districtEn}, BANGLADESH`); }

        refreshAll();
        updatePreview();
        updateStepper();
        showStatus('pdfStatus', `${filled}টি ফিল্ড পূরণ হয়েছে!`, 'ok');
    } catch(err) {
        showStatus('pdfStatus', 'ত্রুটি: ' + err.message, 'err');
    }
}

/* ── Status helper ── */
function showStatus(id, msg, type, persist) {
    const el = document.getElementById(id);
    el.style.display = 'block';
    el.style.color   = type === 'ok' ? 'var(--success)' : type === 'warn' ? 'var(--warning)' : 'var(--danger)';
    el.innerHTML     = msg;
    if (!persist) setTimeout(() => { el.style.display = 'none'; }, 5000);
}

/* ── DOB → text ── */
function updateDobText() {
    const dobEl  = document.getElementById('dob');
    const dobtEl = document.getElementById('dobt');
    if (!dobEl || !dobtEl) return;
    if (!dobEl.value) { dobtEl.value = ''; return; }
    const [y, m, d] = dobEl.value.split('-').map(Number);
    const ordinals = ['First','Second','Third','Fourth','Fifth','Sixth','Seventh','Eighth','Ninth','Tenth',
        'Eleventh','Twelfth','Thirteenth','Fourteenth','Fifteenth','Sixteenth','Seventeenth',
        'Eighteenth','Nineteenth','Twentieth','Twenty First','Twenty Second','Twenty Third',
        'Twenty Fourth','Twenty Fifth','Twenty Sixth','Twenty Seventh','Twenty Eighth',
        'Twenty Ninth','Thirtieth','Thirty First'];
    const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    dobtEl.value = `${ordinals[d-1]} of ${months[m-1]} ${convertYearToText(y)}`;
    refreshAll();
}

/* ── Direct Fetch ── */
let fetchSessionActive = false;

async function initDirectFetch() {
    if (fetchSessionActive) return;
    const statusEl = document.getElementById('fetchStatus');
    statusEl.style.display = 'block';            statusEl.innerHTML = icon('i-spinner','spin') + ' CAPTCHA লোড হচ্ছে…';
    statusEl.style.color = 'var(--text-muted)';

    try {
        const resp = await fetch('api/fetch-birth.php?action=init');
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
    document.getElementById('fetchDob').value = '';
    document.getElementById('fetchCaptchaInput').value = '';
    document.getElementById('fetchStatus').style.display = 'none';
    // Verification cleared — wipe the form and lock it again
    document.getElementById('birthForm').reset();
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('issuedate').value = today;
    document.getElementById('givendate').value = today;
    document.querySelectorAll('#birthForm input, #birthForm select').forEach(i => { i.classList.remove('invalid'); i.classList.remove('valid'); });
    localStorage.removeItem('birthCertDraft');
    refreshAll();
    updateStepper();
    setFormLocked(true);
}

async function doDirectFetch() {
    const ubrn = document.getElementById('fetchUbrn').value.trim();
    const dob = document.getElementById('fetchDob').value.trim();
    const captcha = document.getElementById('fetchCaptchaInput').value.trim();
    const statusEl = document.getElementById('fetchStatus');

    if (!ubrn || !dob || !captcha) {
        statusEl.style.display = 'block';
        statusEl.innerHTML = 'অনুগ্রহ করে সব ফিল্ড পূরণ করুন।';
        statusEl.style.color = 'var(--warning)';
        return;
    }

    if (!/^\d{17}$/.test(ubrn)) {
        statusEl.style.display = 'block';
        statusEl.innerHTML = 'জন্ম নিবন্ধন নম্বর ১৭ অংকের হতে হবে।';
        statusEl.style.color = 'var(--warning)';
        return;
    }

    statusEl.style.display = 'block';        statusEl.innerHTML = icon('i-spinner','spin') + ' BDRIS থেকে ডাটা ফেচ করা হচ্ছে…';
    statusEl.style.color = 'var(--text-muted)';

    const formData = new FormData();
    formData.append('ubrn', ubrn);
    formData.append('birth_date', dob);
    formData.append('captcha_answer', captcha);
    formData.append('captcha_token', document.getElementById('fetchCaptchaToken').value);

    try {
        const resp = await fetch('api/fetch-birth.php', { method: 'POST', body: formData });
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

        const r = data.record;
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

        function parseDDMonthYYYY(str) {
            if (!str) return '';
            const mm = {
                'January':1,'February':2,'March':3,'April':4,'May':5,'June':6,
                'July':7,'August':8,'September':9,'October':10,'November':11,'December':12
            };
            const mt = str.match(/(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})/);
            if (!mt) return '';
            const d = mt[1].padStart(2,'0'), mon = mm[mt[2]], y = mt[3];
            return mon ? `${y}-${String(mon).padStart(2,'0')}-${d}` : '';
        }

        setVal('birth_registration_number', r.brn || '');
        setVal('name_bn', r.name_bn || '');
        setVal('name_en', r.name_en || '');
        setVal('mother_name_bn', r.mother_name_bn || '');
        setVal('mother_name_en', r.mother_name_en || '');
        setVal('father_name_bn', r.father_name_bn || '');
        setVal('father_name_en', r.father_name_en || '');

        const dobParsed = parseDDMonthYYYY(r.dob);
        if (dobParsed) {
            const el = document.getElementById('dob');
            el.value = dobParsed;
            el.dispatchEvent(new Event('change'));
            filled++;
        }

        if (r.sex) {
            const el = document.getElementById('gender');
            const v = r.sex.toUpperCase();
            el.value = (v === 'MALE' || v === 'পুরুষ') ? 'Male' : (v === 'FEMALE' || v === 'মহিলা') ? 'Female' : '';
            el.dispatchEvent(new Event('change'));
            filled++;
        }

        const pobBn = r.place_of_birth_bn || '';
        const pobEn = r.place_of_birth_en || '';
        const appendCountry = (val, suffix) => val && !val.includes(suffix) ? val + suffix : val;
        if (pobBn) {
            const bnVal = appendCountry(pobBn, ', বাংলাদেশ');
            setVal('bp_bn', bnVal);
            setVal('addressx', bnVal);
        }
        if (pobEn) {
            const enVal = appendCountry(pobEn, ', Bangladesh');
            setVal('bp_en', enVal);
            setVal('address', enVal);
        }

        const regDateParsed = parseDDMonthYYYY(r.reg_date);
        const issDateParsed = parseDDMonthYYYY(r.issuance_date);
        if (regDateParsed) setVal('givendate', regDateParsed);
        if (issDateParsed) setVal('issuedate', issDateParsed);
        const toTitleCase = (str) => str ? str.replace(/\w\S*/g, w => w[0].toUpperCase() + w.slice(1).toLowerCase()) : '';
        if (r.reg_office) {
            setVal('nibkar', toTitleCase(r.reg_office));
        }

        const taz = document.getElementById('thanaandzilla');
        if (taz && !taz.value) {
            taz.value = 'Sonatola, Bogura';
            taz.dispatchEvent(new Event('input'));
            filled++;
        }

        const iss = document.getElementById('issuedate');
        const giv = document.getElementById('givendate');
        if (iss.value && !giv.value) giv.value = iss.value;
        if (giv.value && !iss.value) iss.value = giv.value;

        refreshAll();
        updatePreview();
        updateStepper();
        statusEl.innerHTML = icon('i-check-circle') + ' ' + filled + 'টি ফিল্ড সফলভাবে পূরণ হয়েছে!';
        statusEl.style.color = 'var(--success)';

        // Verification succeeded — unlock the form for editing and submit
        setFormLocked(false);
        setTimeout(() => {
            document.getElementById('birthForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 600);
    } catch (err) {
        statusEl.innerHTML = 'Network error: ' + err.message;
        statusEl.style.color = 'var(--danger)';
    }
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
