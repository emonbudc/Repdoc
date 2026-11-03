/* ═══════════════════════════════════════════════════════════════
   Repdoc — homepage interactions
   Vanilla JS only (no dependencies) so the InfinityFree host can
   serve the file as-is. Every control has a keyboard equivalent.
   ═══════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    /* ── Mobile navigation ─────────────────────────────────────── */
    var navToggle = document.getElementById('navToggle');
    var siteNav = document.getElementById('siteNav');
    function setNav(open) {
        if (!navToggle || !siteNav) return;
        siteNav.classList.toggle('is-open', open);
        navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        navToggle.setAttribute('aria-label', open ? 'মেনু বন্ধ করুন' : 'মেনু খুলুন');
    }

    if (navToggle && siteNav) {
        navToggle.addEventListener('click', function () {
            setNav(!siteNav.classList.contains('is-open'));
        });
        siteNav.addEventListener('click', function (ev) {
            if (ev.target.closest('a')) setNav(false);
        });
        window.addEventListener('resize', function () {
            if (window.innerWidth > 900) setNav(false);
        });
    }

    /* ── Category filter (no search box on this page) ──────────── */
    var grid = document.getElementById('toolGrid');
    var emptyState = document.getElementById('emptyState');
    var resetBtn = document.getElementById('resetSearch');
    var catChips = Array.prototype.slice.call(document.querySelectorAll('.chip-cat'));

    if (grid) {
        var cards = Array.prototype.slice.call(grid.querySelectorAll('.tool-card'));
        var activeCat = 'all';

        function apply() {
            var visible = 0;

            cards.forEach(function (card) {
                var ok = activeCat === 'all' || card.getAttribute('data-cat') === activeCat;
                card.hidden = !ok;
                if (ok) visible++;
            });

            if (emptyState) emptyState.hidden = visible !== 0;
        }

        function setCat(cat) {
            activeCat = cat;
            catChips.forEach(function (c) {
                var on = c.getAttribute('data-cat') === cat;
                c.classList.toggle('is-active', on);
                c.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            apply();
        }

        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                setCat('all');
            });
        }

        catChips.forEach(function (chip) {
            chip.addEventListener('click', function () {
                setCat(chip.getAttribute('data-cat'));
            });
        });

        /* Global Escape: close the mobile menu. */
        document.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape') setNav(false);
        });

        apply();
    }
})();
