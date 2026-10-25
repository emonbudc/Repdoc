/* ═══════════════════════════════════════════════════════════════
   Repdoc — file-library interactions
   Vanilla JS, no dependencies. Used by showcase/, officefiles/ and
   offlinefiles/. Progressive: every card is in the HTML already.
   ═══════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    var BN = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];

    function bn(value) {
        return String(value).replace(/[0-9]/g, function (digit) {
            return BN[+digit];
        });
    }

    function num(value) {
        var parsed = parseInt(value, 10);
        return isNaN(parsed) ? 0 : parsed;
    }

    /* ── Header: mobile nav + dropdown ─────────────────────────── */
    var nav = document.getElementById('siteNav');
    var navToggle = document.getElementById('navToggle');
    var navOpenIcon = navToggle ? navToggle.querySelector('.nav-toggle-open') : null;
    var navCloseIcon = navToggle ? navToggle.querySelector('.nav-toggle-close') : null;

    function setNav(open) {
        if (!nav || !navToggle) return;
        nav.classList.toggle('is-open', open);
        navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        navToggle.setAttribute('aria-label', open ? 'মেনু বন্ধ করুন' : 'মেনু খুলুন');
        if (navOpenIcon) navOpenIcon.style.display = open ? 'none' : '';
        if (navCloseIcon) navCloseIcon.style.display = open ? '' : 'none';
    }

    if (nav && navToggle) {
        navToggle.addEventListener('click', function () {
            setNav(!nav.classList.contains('is-open'));
        });
        nav.addEventListener('click', function (event) {
            if (event.target.closest('a')) setNav(false);
        });
        window.addEventListener('resize', function () {
            if (window.innerWidth > 900) setNav(false);
        });
    }

    var dropdowns = Array.prototype.slice.call(document.querySelectorAll('.nav-dropdown'));
    document.addEventListener('click', function (event) {
        dropdowns.forEach(function (dropdown) {
            if (!dropdown.contains(event.target)) dropdown.removeAttribute('open');
        });
    });
    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        setNav(false);
        dropdowns.forEach(function (dropdown) { dropdown.removeAttribute('open'); });
    });

    /* ── Search shelf shortcut ─────────────────────────────────── */
    var search = document.getElementById('catFileSearch');
    var searchForm = document.getElementById('catSearchForm');
    var searchClear = document.getElementById('catSearchClear');
    var searchJump = document.getElementById('catSearchJump');

    if (searchJump && search) {
        searchJump.addEventListener('click', function () {
            setNav(false);
            dropdowns.forEach(function (dropdown) { dropdown.removeAttribute('open'); });
            search.scrollIntoView({ behavior: 'smooth', block: 'center' });
            window.setTimeout(function () { search.focus(); }, 220);
        });
    }

    /* ── File filtering, live search and sorting ───────────────── */
    var grid = document.getElementById('catFileGrid');
    if (!grid) return;

    var cards = Array.prototype.slice.call(grid.querySelectorAll('.cat-file-card'));
    var filters = Array.prototype.slice.call(document.querySelectorAll('[data-filter]'));
    var resultCount = document.getElementById('catResultCount');
    var noResult = document.getElementById('catNoResult');
    var reset = document.getElementById('catReset');
    var noReset = document.getElementById('catNoReset');
    var sortSelect = document.getElementById('catSort');
    var library = document.getElementById('library');
    var activeFilter = 'all';

    function query() {
        return search ? search.value.trim().toLowerCase() : '';
    }

    function matchesFilter(card) {
        if (activeFilter === 'all') return true;
        return card.getAttribute('data-type') === activeFilter ||
            card.getAttribute('data-group') === activeFilter;
    }

    function syncFilterButtons() {
        filters.forEach(function (button) {
            var value = button.getAttribute('data-filter') || 'all';
            var active = value === activeFilter;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    }

    function syncReset() {
        var filtered = activeFilter !== 'all' || query() !== '' ||
            (sortSelect && sortSelect.value !== 'new');
        if (reset) reset.hidden = !filtered;
    }

    function apply(shouldScroll) {
        var needle = query();
        var shown = 0;

        cards.forEach(function (card) {
            var haystack = (card.getAttribute('data-search') || '').toLowerCase();
            var show = matchesFilter(card) && (needle === '' || haystack.indexOf(needle) !== -1);
            card.hidden = !show;
            if (show) shown++;
        });

        if (resultCount) resultCount.textContent = bn(shown);
        if (noResult) noResult.hidden = !(shown === 0 && cards.length > 0);
        if (searchClear) searchClear.hidden = needle === '';

        syncFilterButtons();
        syncReset();

        if (shouldScroll && library) {
            library.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    function setFilter(value) {
        activeFilter = value || 'all';
        apply(true);
    }

    function resetAll(shouldScroll) {
        activeFilter = 'all';
        if (search) search.value = '';
        if (sortSelect) {
            sortSelect.value = 'new';
            sortCards('new');
        }
        apply(shouldScroll);
    }

    filters.forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            setFilter(button.getAttribute('data-filter') || 'all');
            setNav(false);
            dropdowns.forEach(function (dropdown) { dropdown.removeAttribute('open'); });
        });
    });

    if (searchForm && search) {
        searchForm.addEventListener('submit', function (event) {
            event.preventDefault();
            apply(true);
        });
    }

    if (search) {
        search.addEventListener('input', function () { apply(false); });
        search.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                search.value = '';
                apply(false);
            }
        });
    }

    if (searchClear) {
        searchClear.addEventListener('click', function () {
            if (!search) return;
            search.value = '';
            apply(false);
            search.focus();
        });
    }

    [reset, noReset].forEach(function (button) {
        if (!button) return;
        button.addEventListener('click', function () { resetAll(true); });
    });

    /* ── Sorting ───────────────────────────────────────────────── */
    function sortCards(mode) {
        cards.sort(function (a, b) {
            if (mode === 'old') {
                return num(a.getAttribute('data-mtime')) - num(b.getAttribute('data-mtime'));
            }
            if (mode === 'name') {
                return (a.getAttribute('data-name') || '').localeCompare(
                    b.getAttribute('data-name') || '', 'bn'
                );
            }
            if (mode === 'size') {
                return num(a.getAttribute('data-size')) - num(b.getAttribute('data-size'));
            }
            return num(b.getAttribute('data-mtime')) - num(a.getAttribute('data-mtime'));
        });

        var fragment = document.createDocumentFragment();
        cards.forEach(function (card) {
            card.style.animation = 'none';
            fragment.appendChild(card);
        });
        grid.appendChild(fragment);
        window.requestAnimationFrame(function () {
            cards.forEach(function (card) { card.style.animation = ''; });
        });
    }

    if (sortSelect) {
        sortSelect.addEventListener('change', function () {
            sortCards(sortSelect.value);
            syncReset();
        });
    }

    apply(false);
})();
