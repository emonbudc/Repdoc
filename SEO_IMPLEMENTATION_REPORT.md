# SEO Implementation Report — Repdoc

**Prepared:** 1 November 2026  
**Workspace:** `/storage/emulated/0/htdocs/Repdoc`  
**Canonical:** `https://repdoc.rf.gd/`  
**Deployment status:** Implemented and verified locally; **not committed, pushed, or deployed** in this pass.

---

## 1. Summary

This pass added a public certificate-guide layer, made the blank verification page indexable, kept certificate lookup results private, and wired the new pages into navigation, the sitemap, `llms.txt` and `robots.txt`.

### 1.1 Before and after

| Item | Before | After |
|---|---:|---:|
| Sitemap URLs | 214 | 228 |
| Sitemap image entries | 21 | 21 |
| Public certificate guides | 0 | 12 |
| Public guide hub | 0 | 1 |
| Indexable verify landing | No | Yes |
| Verify result pages | Noindex | Noindex |
| Guide routes | None | `/sonod/` + `/sonod/{slug}/` |

The 21 image entries include the 12 homepage images from the previous image-SEO task. No duplicate hero images were added for the new guides.

---

## 2. New files

| File | Purpose |
|---|---|
| `src/sonod_guides.php` | Data source for 12 guides, groups, aliases, FAQs and related links |
| `sonod/index.php` | Public guide hub |
| `sonod/guide.php` | Single-guide template |
| `sonod/_ui.php` | Shared guide styles, disclaimer and card renderer |
| `repdoc_keyword_database.csv` | 1,003-keyword database (548 autocomplete-derived core + 455 post-audit additions) with honest `unavailable` metrics |
| `SEO_AUDIT_REPORT.md` | Audit report |
| `COMPETITOR_ANALYSIS_PROTTOYON.md` | Competitor analysis |
| `COMPLETE_KEYWORD_RESEARCH.md` | Keyword strategy |
| `BANGLA_KEYWORD_RESEARCH.md` | Bangla clusters |
| `ENGLISH_KEYWORD_RESEARCH.md` | English/Banglish clusters |
| `LOCATION_KEYWORD_RESEARCH.md` | Location intent and doorway-page policy |
| `QUESTION_KEYWORD_RESEARCH.md` | Question intent and FAQ structure |
| `KEYWORD_GAP_ANALYSIS.md` | Repdoc vs competitor gaps |
| `COMPLETE_KEYWORD_MAPPING.md` | URL-level keyword map |
| `CONTENT_STRATEGY.md` | Editorial and trust strategy |
| `SEO_IMPLEMENTATION_REPORT.md` | This report |
| `90_DAY_SEO_ROADMAP.md` | Execution plan |

---

## 3. Modified files

### 3.1 `src/seo.php`

- Loads `src/sonod_guides.php`.
- Converts the registry return into a `$pages` array.
- Registers `/sonod/` as indexable, in-sitemap, priority `0.9`.
- Registers all 12 guides as indexable, in-sitemap, priority `0.7`.
- Changes `/verify/` from `locked`/noindex/out-of-sitemap to indexable/in-sitemap.
- Keeps `/verify/` image empty so the image sitemap does not repeat the same hero image.

### 3.2 `src/sitemap.php`

- Already supported multiple images per URL from the prior image task.
- Now automatically picks up the 14 new registry entries.
- No guide-specific code was added; the registry remains the source of truth.

### 3.3 `sonod/index.php`

- `CollectionPage`, `BreadcrumbList`, `ItemList` and `FAQPage` schema.
- Grouped guide cards.
- “How to choose the right certificate” section.
- Links to the certificate generator and verification page.
- Visible non-government disclaimer.
- Catalog header/footer for visual consistency.

### 3.4 `sonod/guide.php`

- Validates the slug.
- Returns a 404 for unknown guides.
- Renders definition, uses, document checklist, steps, Repdoc boundary, FAQ and related guides.
- Emits `Article`, `BreadcrumbList` and `FAQPage` JSON-LD.
- Displays an English/Banglish alias line.
- Uses one H1 and structured H2s.
- Explicitly says Repdoc is not a government portal.

### 3.5 `src/sonod_guides.php`

- 12 guides, each with:
  - label
  - title
  - SEO title
  - description
  - English/Banglish aliases
  - intro
  - definition
  - uses
  - documents
  - steps
  - Repdoc boundary
  - FAQs
  - related guides
  - group
- All content is original Bangla and avoids fixed fees, fixed timelines, government claims and QR overclaims.

### 3.6 `.htaccess`

Added:

```apache
RewriteCond %{REQUEST_URI} !/$
RewriteRule ^sonod/([a-z0-9-]+)$ sonod/$1/ [R=301,L]
RewriteRule ^sonod/([a-z0-9-]+)/?$ sonod/guide.php?slug=$1 [L,QSA]
```

This provides clean `/sonod/{slug}/` URLs and a trailing-slash redirect.

### 3.7 `verify/index.php`

- Added `src/seo.php`.
- Blank page is indexable with canonical, OG, `WebPage`, breadcrumb and FAQ schema.
- Any `no` parameter switches to `noindex,follow`.
- Canonical always points to `/verify/`, never to a query result.
- Added visible explanatory content and FAQ.
- Added links to the verification guide and guide hub.
- Kept private fields out of the result table.
- Lookup rate limit and database query remain unchanged.

### 3.8 Internal linking

- `src/catalog.php`: added “সনদ গাইড” to the main navigation and footer; added “সনদ যাচাই” to the footer.
- `src/nav.php`: added certificate-guide link to generator navigation.
- `index.php`: added homepage navigation and footer links.
- `genaretor/certificate/index.php`: added links to all 12 guides and the hub.
- `llms.txt`: added the guide hub, all guides and verification.
- `robots.txt`: added `Allow: /sonod/` and `Allow: /verify/`.

### 3.9 Metadata improvements

| File | Change |
|---|---|
| `about/index.php` | More descriptive title |
| `officefiles/index.php` | Title now names Word, Excel and PowerPoint |
| `offlinefiles/index.php` | Title now names PSD, AI and PLP design templates |
| `importantlinks/index.php` | Title now names government services, education and jobs |

Homepage title and description were left unchanged because they already target the broader template/tool intent.

---

## 4. Verification

### 4.1 PHP syntax

All modified PHP files passed `php -l`:

- `src/sonod_guides.php`
- `sonod/_ui.php`
- `sonod/index.php`
- `sonod/guide.php`
- `src/seo.php`
- `verify/index.php`
- `genaretor/certificate/index.php`
- `src/catalog.php`
- `src/nav.php`
- `index.php`
- `about/index.php`
- `officefiles/index.php`
- `offlinefiles/index.php`
- `importantlinks/index.php`

### 4.2 HTTP checks

Local PHP server: `127.0.0.1:8127`, document root `Repdoc`, temporary router for clean guide URLs and sitemap.

| URL | Status | Notes |
|---|---|---|
| `/sonod/` | 200 | Indexable, canonical correct |
| `/sonod/nagorik-sonod/` | 200 | Indexable |
| `/sonod/warish-sonod/` | 200 | Indexable |
| `/sonod/charitrik-sonod/` | 200 | Indexable |
| `/sonod/jatiyota-sonod/` | 200 | Indexable |
| `/sonod/sthayi-basinda-sonod/` | 200 | Indexable |
| `/sonod/paribarik-sonod/` | 200 | Indexable |
| `/sonod/income-sonod/` | 200 | Indexable |
| `/sonod/bhumihin-sonod/` | 200 | Indexable |
| `/sonod/protibondhi-sonod/` | 200 | Indexable |
| `/sonod/mrityu-pratyayon/` | 200 | Indexable |
| `/sonod/trade-license/` | 200 | Indexable |
| `/sonod/sonod-verification/` | 200 | Indexable |
| `/verify/` | 200 | Indexable |
| `/verify/?no=…` | 200 | `noindex,follow` |
| `/genaretor/certificate/` | 200 | Guide links shown |
| Unknown guide | 404 | Noindex |

### 4.3 Sitemap

- Valid XML.
- 228 `<url>` entries.
- 21 `<image:image>` entries.
- All 14 new URLs present.
- No duplicate guide images added.

### 4.4 Structured data

All JSON-LD blocks parsed successfully. New pages emit:

- Hub: `Organization`, `WebSite`, `CollectionPage`, `BreadcrumbList`, `ItemList`, `FAQPage`.
- Guide: `Organization`, `WebSite`, `Article`, `BreadcrumbList`, `FAQPage`.
- Verify blank: `Organization`, `WebSite`, `WebPage`, `BreadcrumbList`, `FAQPage`.
- Verify lookup: site graph only; no FAQ/WebPage schema.

### 4.5 Internal links

Local checks of the hub, selected guides and verify page found no broken internal links.

---

## 5. What was not done

- **No commit.** The working tree remains uncommitted.
- **No push or deployment.** Production was not changed.
- **No production crawl.** The audit environment could not complete a TLS request to the production host.
- **No `.htaccess` live test.** The local built-in server bypasses Apache rewrite rules.
- **No Search Console data.** GSC files exist in the repo, but they were not used in this pass.
- **No full English pages.** Only visible English/Banglish aliases were added.
- **No official application tracking.** Repdoc does not operate that workflow.
- **No location pages.**
- **No fee/time tables.**

---

## 6. Risks and follow-ups

| Risk | Follow-up |
|---|---|
| Clean URLs may behave differently on Apache | Test `/sonod/{slug}/` and `/sonod/{slug}` on staging/production |
| Direct `guide.php?slug=` is reachable | Canonical points to clean URL; consider blocking direct access later |
| Local verification page might expose data if indexed with query | Confirm `noindex` on all non-blank states; audit after deployment |
| Guide content could become stale | Assign an owner and quarterly review |
| English/Banglish alias line is not a full translation | Measure Latin-script impressions before building English pages |
| New terms may confuse users | Keep the non-government disclaimer visible on every guide |
| Sitemap count changed | Re-submit sitemap after deployment |
| Image sitemap duplication | New guides intentionally have no sitemap image |

---

## 7. Deployment checklist

- [ ] Review `git status` and diff.
- [ ] Confirm no secrets or temp files are staged.
- [ ] Deploy the new `sonod/` directory.
- [ ] Deploy `src/sonod_guides.php`.
- [ ] Deploy modified PHP, `.htaccess`, `robots.txt`, `llms.txt`.
- [ ] Confirm `mod_rewrite` is enabled and `.htaccess` is honoured.
- [ ] Test `/sonod/`, all 12 guides, and `/sonod/{slug}` redirect.
- [ ] Test `/verify/` and a real Repdoc certificate number.
- [ ] Confirm lookup result returns `noindex,follow`.
- [ ] Confirm sitemap returns 228 URLs and valid XML.
- [ ] Resubmit sitemap in Search Console, if available.
- [ ] Spot-check mobile layout and navigation overflow.
- [ ] Check server error log for warnings.

---

## 8. Bottom line

The implementation is complete at the code level and verified locally. It adds a substantive public content layer without compromising verification privacy or pretending to be a government service.

The remaining work is deployment validation and measurement, not more speculative publishing.
