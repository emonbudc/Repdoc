# BDRIS Design System — MASTER (Global Source of Truth)

> Generated with UI UX Pro Max `--design-system "government civil registry search dashboard trustworthy"`.
> Verified results: Pattern **Minimal Single Column**, Style **Accessible & Ethical + Minimalism & Swiss Style**,
> Product **Government/Public Service → Executive Dashboard + Service Directory + Search**.
> Project decision (user-approved): adopt Accessible Navy palette, **keep current Bengali font stack**.
> `os.link` persist failed on this filesystem, so this file was written manually to the same path.

## 1. Visual style
- Accessible & Ethical + Minimalism & Swiss. Flat, high-contrast, functional. No ornate decoration.
- Light + dark supported. Low motion, fast loading, grid-based, essential elements only.
- Avoid: ornate design, low contrast, AI purple/pink gradients, one-size animation.

## 2. Colors — "Accessible Navy"
| Role | Light | Dark | Existing var |
|---|---|---|---|
| Primary navy text | #0F172A | #F8FAFC | --text-primary, --ring |
| CTA / link / focus | #0369A1 | #38BDF8 | --primary, --border-focus |
| CTA hover | #075985 | #7DD3FC | --primary-hover |
| CTA wash | #EFF6FF | rgba(56,189,248,.15) | --primary-light |
| Surface / card | #FFFFFF | #111827 | --bg-surface |
| Base bg | #F8FAFC | #0B0F19 | --bg-base |
| Muted bg / text | #E8ECF1 / #475569 | #1E293B / #CBD5E1 | --bg-muted, --text-muted |
| Border | #E2E8F0 | #1F293D | --border-color |
| Success | #047857 on #ECFDF5 | #34D399 on rgba(16,185,129,.18) | status-verified-* |
| Warning | #B45309 on #FFFBEB | #FBBF24 on rgba(245,158,11,.18) | status-pending-* |
| Error | #BE123C on #FFF1F2 | #FB7185 on rgba(244,63,94,.18) | status-incomplete-* |
| Destructive | #DC2626 on #FFF | #FB7185 | --destructive, btn-danger |
| Highlight mark | #FEF08A bg light | #854D0E bg + #FEF08A text dark | mark |

Rules: body text ≥4.5:1 both themes; non-text UI boundaries ≥3:1; certificate sheet stays #FFF/#0F172A for print.
CTA gradient: `linear-gradient(135deg,#0369A1,#075985)` light; dark `linear-gradient(135deg,#075985,#38BDF8)`.

## 3. Typography (KEPT — do not add new Google Fonts)
- `--font-sans: 'Inter','Noto Sans Bengali',system` body 15px, scale 0.72–1.05rem, lh 1.55.
- `--font-heading: 'Plus Jakarta Sans','Noto Sans Bengali'` H1 1.12rem / H2 1.3rem, 800, -0.01em.
- `--font-mono: 'JetBrains Mono'` BRN/dates/IDs, 0.02em.
- Bengali: never <13px for Bengali, keep BN indicator, preserve Bangla digits in placeholders.

## 4. Spacing / radii / elevation / layout
- 4/8dp rhythm: 8/16/24/32/48. Section gap 24px. Container 1380px, padding 24/20/48.
- Radii: 8/12/16/20/full (keep). Shadows xs–xl sparingly; prefer borders over shadows.
- Motion tokens only: `--transition-fast .15s / --transition-normal .25s`.
- `prefers-reduced-motion`: disable emblemShine, livePulse, shimmer, modalScaleUp.

## 5. Icons
- Phosphor outline only (magnifying-glass, house, list, arrow-left, trash verified). 18/22px tokens, 1.5–2px stroke.
- No emoji as icons (remove 📄📋🖨️ from export menu → SVG).
- Decorative `aria-hidden="true"`; icon-buttons get `aria-label`; expose `aria-pressed/expanded/selected`.

## 6. Navigation
- Flat app (index + manage). Sticky topbar 70px (60px ≤1024px). `scroll-margin-top` on results.
- ≤1024px: nav becomes horizontal scroll menu — never `display:none`.
- Preserve keyboard: `/` focus search, `Ctrl+K` help, `T` theme, `V` view, `Esc` close. Use pushState, never break Back.
- Headings sequential h1→h2→h3.

## 7. Components (global rules)
- Buttons: primary CTA white 700, min-height 44px coarse; secondary 1.5px border; danger #DC2626.
- Disabled: `opacity:.5 + cursor:not-allowed + disabled` attr (ux:disabled).
- Focus: `:focus-visible` 3px ring `var(--border-focus)` + 2px offset, both themes.
- Pills: single-select `aria-pressed`. Chips: wrap, removable `aria-label`.
- Table: desktop `overflow-x:auto`; sticky header offset = measured topbar; sortable `aria-sort`.
- Cards: 2-col → 1-col ≤1024px; selected = CTA border + wash.
- Modals: `role=dialog aria-modal`, labelledby, focus trap + return focus, ESC.
- Toasts: `role=status`, bottom-right → full-width ≤768px.
- Skeletons: stable rows + `aria-busy`, no flicker <300ms (ux:loading).
- Forms: labels always, inline errors + top `role=alert` summary with field links on fail (ux:error-summary).

## 8. States (every interactive control)
loading / empty / error / disabled / pressed / focus — defined per page file. Error uses `role=alert` + retry; empty uses guidance + reset (ux:no-results).

## 9. Accessibility + responsive
- Touch ≥44px (iOS) / 48dp (Android); coarse-pointer enlargements kept.
- Test: 375/768/1024/1440 + landscape; Dynamic Type 200%; reduced-motion; dark-mode retest.
- Checklist: no emoji icons, cursor-pointer clickables, 150–300ms hovers, 4.5:1 text, visible focus, reduced-motion, responsive.

## 10. File hierarchy
- This MASTER.md = global truth.
- `pages/index.md` overrides for search dashboard. `pages/manage.md` future. `pages/certificate.md` print override.
- Page files override Master; else Master applies.
