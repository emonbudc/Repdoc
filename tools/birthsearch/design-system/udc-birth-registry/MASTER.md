# Balua Union Digital Center birth registry — MASTER (Global Source of Truth)

> UI UX Pro Max `--design-system "government civil registry records admin enterprise"`
> (variance 2 / motion 1 / density 8) → Pattern: Enterprise records console.
> Style: Minimalism & Swiss (light only). Typography: system Bengali pairing.
> Palette verified against https://www.bdris.gov.bd/ (white surfaces, BDRIS blue
> #4072b7 family, grey borders, flat Bootstrap-style controls, no dark mode,
> no gradients). Bayesian note: prior session file `design-system/bdris/MASTER.md`
> (Accessible Navy + gradients + dark theme) is SUPERSEDED by this file for all
> new work — its gradient/glow/dark-mode rules conflict with the verified
> Modern Enterprise direction and must not be reintroduced.

## 1. Direction
Modern Enterprise Software: a mature administrative records console for
government-service operators. Dense, structured, conventional. Single light
theme everywhere. This is an independent record-finding tool — never present
it as the official BDRIS portal (no seals, no "official portal" claims).

## 2. Color tokens (BDRIS-inspired, text ≥ 4.5:1 on white)
| Role | Value | Token |
|---|---|---|
| Primary / links / focus | #2F5DA3 (darkened BDRIS #4072b7 for contrast) | --brand |
| Primary hover / pressed | #264E8A | --brand-dark |
| Primary ink (headings accents) | #1D3A66 | --brand-ink |
| Primary wash (selected rows, active nav) | #EDF2FA | --brand-tint |
| Primary border | #B9CBE4 | --brand-border |
| Govt green strip / verified | #006A4E | --gov-green |
| Verified text/bg/border | #0B5C40 / #E6F3EC / #B7D9C7 | --ok-* |
| Ink / body / muted | #1B2430 / #4B5A6A / #66788A | --ink/--body/--muted |
| Page bg / surface / subtle | #F3EFE3 paper / #FCFBF4 / #ECE6D3 | --bg/--surface/--subtle |
| Borders (warm press grey) | #D5CFB9 / soft #E2DCC8 | --line/--line-soft |
| Danger | #B42318 / #FDECEA / #F0BEB8 | --danger-* |
| Warning | #8A4B00 / #FFF4E0 / #E8CF9E | --warn-* |
| Match highlight | #FFE98A flat | mark |

Banned: gradients, glow shadows, backdrop blur, pulse/shine/shimmer
animations, purple/pink AI palettes, dark-mode variables.

## 3. Typography
- Sans (UI controls, data tables, forms): 'Inter','Noto Sans Bengali' —
  base 14px. Tables and inputs stay sans for ledger-like legibility.
- Display serif (masthead, section titles, certificate headings, empty
  states): 'Noto Serif Bengali', serif — old-newspaper voice. Same size
  scale as sans (page h2 17px/700); never oversized.
- Mono (BRN, dates, IDs): ui-monospace stack.
- Bangla Unicode first; Bangla digits accepted in BRN/DOB inputs.

## 4. Shape / spacing / elevation
- Old-newspaper press character, restrained: flat paper surfaces, hairline
  warm-grey rules, one 2px ink press-rule under the breadcrumb bar, double
  3px rule above the footer. No textures, no vignettes, no deckled edges.
- Radii: inputs 3px, buttons/tags 3px, panels/modals 6px. No pill radii
  (status dot excepted), no 12–20px card radii.
- 4px spacing rhythm, dense dashboard density; container 1200px.
- Elevation: flat 1px borders everywhere; one restrained shadow token only
  for overlays (dropdown/modal/toast). No hover lift, no transforms.
- Motion: none except spinner. `prefers-reduced-motion` disables even that.

## 5. Components (all screens)
- Topbar 56px white + 3px govt-green strip; flat square brand mark; nav =
  text links with 2px active underline; right side = flat connection tag +
  plain operator label. No theme toggle, no avatars with gradients, no pulse.
- Breadcrumb strip under topbar on every page (Home / current section).
- Registry meta strip (replaces stat card): one thin bordered row —
  total records, source note; no trend pills, no progress bars, no big numerals.
- Search panel: bordered panel, labeled fields, BRN = 17-digit validated,
  DOB = DD/MM/YYYY validated; inline `.field-error` + focusable
  `role=alert` error summary linking to fields (ux:error-summary).
- Results: conventional table, sticky header, uppercase 12px headers,
  row hover tint only; BRN as bold mono + copy icon; status as flat tag;
  actions as small flat buttons/text. Bulk bar = flat navy, no shadow.
- Pagination: flat bordered buttons, current = filled brand.
- Modals: 6px, flat header with bottom border, underline tabs, key-value
  rows (not boxed cards), `role=dialog aria-modal`, Esc closes.
- Verify modal: factual wording about BDRIS captcha lookup; no gradient header.
- Toasts: flat, 3px left status bar, `role=status`.
- Empty/error/loading states distinct: guidance + reset action for empty;
  `role=alert` + retry for errors; static skeleton bars (aria-busy) for loading.
- Certificate sheet: single-rule bordered print sheet, no watermark/shadow.
- Icons: single 1.5–2px outline SVG set; no emoji icons (replace ✕/✓/⚠ glyphs
  in UI chrome with SVG or plain text).

## 6. Accessibility / responsive
- `:focus-visible` 2px brand outline; labels on all inputs; keyboard:
  `/` focuses search, `Ctrl+K` help, `V` view toggle, `Esc` closes.
- 375 / 768 / 1024 / 1440 checks; table → labelled stacked rows ≤768px;
  coarse-pointer targets ≥40px; print stylesheet keeps certificate only.

## 7. Hierarchy
- This MASTER.md = global truth. `pages/index.md` (search console) and
  `pages/manage.md` (record management) hold page deltas; page files win.
