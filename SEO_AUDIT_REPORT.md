# Repdoc SEO Audit Report

**Prepared:** 1 November 2026  
**Scope:** Public pages of the Repdoc codebase at `/storage/emulated/0/htdocs/Repdoc`, with `https://prottoyon.gov.bd/` as the primary competitor.  
**Production canonical used by the code:** `https://repdoc.rf.gd/`  
**Important domain note:** The brief mentions `repdoc.freedev.app`, but `.htaccess` and all canonical helpers redirect that host to `repdoc.rf.gd`. This audit keeps `repdoc.rf.gd` as canonical. Changing it requires a deliberate migration, not a copy edit.

---

## 1. Executive summary

Repdoc already has a usable technical SEO foundation: clean canonical helpers, page-level metadata, a dynamic sitemap, schema support, and 204 public Word-template pages. The largest organic gap was not basic crawlability. It was **query coverage**: the site had a generator for 23 certificate types but almost no public, informational pages answering the high-intent searches people make before using such a generator.

The competitor, `prottoyon.gov.bd`, does not win on technical SEO. Its homepage is a thin iframe wrapper, it has no sitemap, no canonical tags, no Open Graph tags, no schema, and its subpages have no H1 or H2. It wins mainly on **official-domain trust** and on having a complete citizen workflow. Repdoc cannot and should not imitate government status. It can compete by being clearer, faster to read, more searchable in Bangla, and explicit about the difference between a document draft and an officially issued certificate.

This pass added 14 indexable public URLs:

- 1 certificate-guide hub: `/sonod/`
- 12 certificate guides: `/sonod/{slug}/`
- 1 verification landing page: `/verify/`

The sitemap now contains **228 URLs** and **21 image entries**, up from 214 URLs. The 12 homepage images remain in the image sitemap. The verification page is indexable only in its blank state; lookup-result URLs remain `noindex,follow`.

---

## 2. What was audited

### 2.1 Code and configuration

- `src/seo.php` — central canonical, social, robots and JSON-LD helpers.
- `src/seo_controls.php` — per-page indexability overrides.
- `src/sitemap.php` and root `sitemap.php` — dynamic XML sitemap.
- `.htaccess` — host redirects, clean URLs, blocked source paths.
- `robots.txt` — crawl directives.
- `index.php`, `about/`, `showcase/`, `officefiles/`, `offlinefiles/`, `importantlinks/`, `software/`, `privacy/`.
- `genaretor/certificate/index.php` — public certificate landing.
- `genaretor/prottoyon/` and `src/prottoyon.php` — gated 23-type generator.
- `verify/index.php` — public certificate verification.
- `admin/seo-health.php` and `src/seo_audit.php` — existing read-only SEO inspection tooling.

### 2.2 Live/local rendering

Repdoc was rendered through a local PHP 8.5.12 server. Production `curl` from the audit environment failed with an OpenSSL `unexpected eof` error, so no production response was treated as verified in this pass. The local server exercised the same PHP templates and router mappings, but `.htaccess` behaviour itself was reviewed as source only.

Verification commands included:

- `php -l` on every modified PHP file.
- HTTP 200 checks for the hub, all 12 guides, the blank verify page, and the certificate landing.
- HTTP 404 check for an unknown guide slug.
- XML parsing of the generated sitemap.
- JSON-LD parsing of every new page.
- Canonical and robots checks for blank versus query verification URLs.

---

## 3. Baseline findings

### 3.1 Indexable public surface

| Area | Finding |
|---|---|
| Page registry | 228 sitemap URLs after this pass: 214 previous + 14 new |
| Template pages | 204 public Word-template detail pages |
| Other formats | Excel, PowerPoint, PSD, AI and PLP folders contain only `.gitkeep`; no real files to index |
| Certificate types | 23 types in the generator registry |
| Public tool landing | `/genaretor/certificate/` is indexable but thin |
| Gated tools | NID, birth, CV, smart NID, land, passport and Prottoyon generator are intentionally noindex |
| Software | `/software/` is intentionally noindex; left unchanged |
| Verification | Was noindex; now indexable in blank state, query results noindex |
| Showcase | ~420 images, mostly UI icons; 418 empty alts are intentional for decorative icons |

### 3.2 Technical strengths

- Canonical base URL is centralised in `src/seo.php`.
- Indexability has one registry and one runtime override path.
- Sitemap is generated from the registry + live templates, not maintained by hand.
- Open Graph, Twitter cards, canonical, robots and JSON-LD are reusable.
- Host and HTTPS redirects are already in `.htaccess`.
- Gated source files, logs and databases are blocked.
- The current homepage uses `max-image-preview:large`.
- Image sitemap support accepts multiple images per page.

### 3.3 Technical weaknesses

| Severity | Issue | Impact |
|---|---|---|
| High | No public informational layer around certificate queries before this pass | High-intent searches had no landing page |
| High | `/verify/` was fully noindex | A useful, unique public utility was invisible |
| Medium | Homepage title/description are broad template language | Weak alignment with certificate-intent queries |
| Medium | No English/Banglish landing content for transliterated searches | Missed `nagorik sonod`, `warish sonod`, `sonik`-style variants |
| Medium | No application-status page or official tracking integration | Cannot satisfy “status check” intent; must be stated honestly |
| Medium | Location-intent keywords have no local landing pages | Tempting doorway-page trap; not recommended |
| Low | Some page titles are generic (`সম্পর্কে — Repdoc`) | Improved in this pass |
| Low | Direct `guide.php?slug=` URLs are reachable | Canonical points to the clean URL; acceptable but could be blocked later |
| Low | Built-in PHP server does not exercise `.htaccess` | Clean-URL behaviour still needs a production/staging check |

### 3.4 Structured-data state

- Sitewide `Organization` and `WebSite` graph: present.
- Homepage: `Organization`, `WebSite`, `WebApplication`, `HowTo`.
- New hub: `CollectionPage`, `BreadcrumbList`, `ItemList`, `FAQPage`.
- Each new guide: `Article`, `BreadcrumbList`, `FAQPage`.
- Verification blank page: `WebPage`, `BreadcrumbList`, `FAQPage`.
- No fake ratings, review counts, government affiliations, or author identities were added.

---

## 4. Changes made in this pass

### 4.1 New certificate-guide hub

- URL: `/sonod/`
- File: `sonod/index.php`
- Data: `src/sonod_guides.php`
- Shared UI: `sonod/_ui.php`
- Purpose: one crawlable, linkable hub for 12 high-intent certificate topics.
- Includes grouping by identity, family, income, business and verification.
- Includes visible FAQ, a “how to choose the right certificate” section, and links to the generator and verification page.

### 4.2 New certificate guides

| URL | Primary intent |
|---|---|
| `/sonod/nagorik-sonod/` | নাগরিক/নাগরিকত্ব সনদ |
| `/sonod/warish-sonod/` | ওয়ারিশ/উত্তরাধিকার সনদ |
| `/sonod/charitrik-sonod/` | চারিত্রিক সনদ |
| `/sonod/jatiyota-sonod/` | জাতীয়তা সনদ |
| `/sonod/sthayi-basinda-sonod/` | স্থায়ী বাসিন্দা সনদ |
| `/sonod/paribarik-sonod/` | পারিবারিক সনদ |
| `/sonod/income-sonod/` | আয়/বার্ষিক আয় সনদ |
| `/sonod/bhumihin-sonod/` | ভূমিহীন সনদ |
| `/sonod/protibondhi-sonod/` | প্রতিবন্ধী সনদ |
| `/sonod/mrityu-pratyayon/` | মৃত্যু সনদ/প্রত্যায়ন |
| `/sonod/trade-license/` | ট্রেড লাইসেন্স |
| `/sonod/sonod-verification/` | সনদ/QR যাচাই |

Each guide includes: definition, common uses, document checklist, application steps, a Repdoc draft/print section, FAQ, related-guide links and a non-government disclaimer. Each also includes a visible English/Banglish “also searched as” line to support transliterated queries without creating thin duplicate pages.

### 4.3 Verification page

- Removed the blanket `noindex` from `/verify/`.
- Blank page now uses `index,follow,max-image-preview:large`.
- Any URL with a `no` parameter uses `noindex,follow`, keeping certificate lookup results out of search.
- Canonical always points to `https://repdoc.rf.gd/verify/`, not to a lookup query.
- Added title, description, Open Graph, `WebPage`, breadcrumb and FAQ schema.
- Added visible explanatory content and FAQ.
- Added links to the verification guide and the Sonod guide hub.
- The certificate number, applicant name, office, date and status remain the only fields shown; NID, address and attachments stay private.

### 4.4 Internal linking

- Added “সনদ গাইড” to the main site navigation on catalog pages.
- Added “Certificate guides” to the generator navigation.
- Added guide and verify links to the homepage footer.
- Added “সনদ গাইড” and “সনদ যাচাই” to the catalog footer.
- Added all guide links to `/genaretor/certificate/`.
- Added `/sonod/` and all guides to `llms.txt`.
- Added `Allow: /sonod/` and `Allow: /verify/` to `robots.txt`.

### 4.5 Registry, routing and sitemap

- Registered the hub and all 12 guides in `repdoc_page_registry()`.
- Registered `/verify/` as indexable and in-sitemap.
- Added clean `.htaccess` routing for `/sonod/{slug}/`.
- Added a 301 from `/sonod/{slug}` to `/sonod/{slug}/`.
- Sitemap now includes all 14 new URLs.
- New guide pages do not add duplicate hero images to the image sitemap; the 12 homepage images and existing page images remain.

---

## 5. Verification results

| Check | Result |
|---|---|
| PHP lint, all modified files | Pass |
| `/sonod/` | 200, indexable, canonical correct |
| 12 guide URLs | All 200, indexable, canonical correct |
| Unknown guide slug | 404, noindex |
| `/verify/` blank | 200, indexable, canonical correct |
| `/verify/?no=…` | 200, `noindex,follow`, canonical to blank verify page |
| Sitemap XML | Valid; 228 URLs |
| Sitemap images | 21 entries; 12 homepage images retained |
| JSON-LD | All new blocks parse as valid JSON |
| Navigation/footer links | Resolve locally |
| Homepage image sitemap | Unchanged and complete |
| `.htaccess` | Source-reviewed; production clean-URL test still pending |

---

## 6. What was deliberately not changed

- **Canonical domain.** It remains `repdoc.rf.gd`; the `freedev.app` host redirects there.
- **`/software/` noindex.** Flipping it without unique content would add a thin page.
- **Gated tools.** NID, birth, CV, land, passport and Prottoyon remain gated and noindex.
- **Showcase decorative icons.** Bulk-changing empty `alt=""` on UI icons would hurt accessibility.
- **Government comparisons.** No page claims Repdoc is a government portal, nor copies the competitor’s branding.
- **Location doorway pages.** No “sonod in every district” pages were created.

---

## 7. Remaining priorities

1. **Validate clean URLs on production or staging.** The local built-in server bypasses `.htaccess`; confirm `/sonod/{slug}/` on Apache.
2. **Add a public application-status explainer only if Repdoc has a real status feature.** Do not promise official Prottoyon tracking.
3. **Add English/Banglish summaries** to the guides if analytics show non-Bangla script queries. The current “also searched as” lines help but are not full English pages.
4. **Measure real queries.** Use Google Search Console if access is available; otherwise use server logs and on-site search. This audit did not use GSC data.
5. **Do not chase location pages** unless there is a real local service differentiator. Use one strong national guide per certificate type.
6. **Monitor verification privacy.** Ensure query-result pages never become indexable through an admin override or future template change.

---

## 8. Limitations

- No reliable search volume, keyword difficulty, CPC or traffic estimates were available. These are marked `unavailable` in the keyword database.
- No production crawl was completed because of the TLS error from the audit environment.
- The competitor’s internal dashboard and application flow are not public and were not tested.
- No claim is made about competitor QR verification because no public evidence of it was found.
- The sitemap and page counts reflect the local codebase at the time of this report, not a post-deployment crawl.

---

## 9. Bottom line

Repdoc now has a credible public content layer for the certificate queries it can honestly serve. The technical work is deliberately conservative: index the useful blank verification page, keep private lookups out of search, publish 12 substantive guides, link them sitewide, and keep the government boundary explicit.

The next win is not more pages. It is **real query data plus production validation**, then improving the guides that actually earn impressions.
