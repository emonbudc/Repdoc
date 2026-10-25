# BDRIS — pages/index.md (Search Dashboard Override)

Overrides MASTER for `index.php`. All other rules from MASTER apply.

## Layout order
topbar → stats → search-panel (title + pills + input/CTA + mode + controls + advanced) → results header → bulk → table/cards → pagination → footer. Overlays: verifyModal, recordDetailModal (5 tabs), helpModal, toasts.

## Page tokens
- Stats: single blue card now; keep `.stats-grid-single max 420px`.
- Search CTA row: input flex + primary button `min-height 48px desktop / 44px coarse`.
- Mode toggle: segmented `view-toggle-group`, buttons `aria-pressed`, partial default.

## Components
- Suggestions: container `role=listbox`, rows `role=option`, debounced, arrow-key nav, ESC closes, click selects and runs search.
- Table: wrapper `overflow-x:auto`; thead sticky `top: var(--topbar-height)` — JS sets var to measured height on resize; `data-label` on every `td` for ≤768px card fallback; checkbox column hidden ≤768px only if card checkbox added (else keep with 44px target).
- Cards: mirror table fields (name, BRN, DOB/age, gender, ward, status, actions).
- Export menu: replace 📄📋🖨️ with inline SVG (doc/json/printer), `role=menu` + `menuitem`, ESC closes, focus return to trigger.
- Filter chips: wrap, each remove `aria-label="Remove filter X"`.
- Pagination: buttons `aria-label="Go to page N"`, `aria-current` on active, disabled prev/next at ends.

## States
- Search button: `disabled + aria-busy` while fetch pending; label switches to spinner + "খুঁজছে…".
- Loading: 5 skeleton rows, `aria-busy=true` on results region, min 300ms display to avoid flicker.
- Empty: illustration + "কোনো রেকর্ড পাওয়া যায়নি" + hint + reset button (keep existing copy).
- Error (new): `role=alert` box + retry button; JS `catch` path must render it (currently missing).
- Toasts: `role=status` / `role=alert` for errors.

## A11y notes
- `skip-link` to `#resultsSection` (add in Phase 1).
- Search input `type=search`, `aria-label`, `aria-expanded/controls` for suggestions, `aria-describedby` hint.
- View toggles + pills use `aria-pressed`. Sort headers use `aria-sort`.
- Verify modal inputs keep labels; captcha image gets `alt`; answer input `aria-describedby`.
