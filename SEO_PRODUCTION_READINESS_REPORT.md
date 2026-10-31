# SEO Production Readiness Report — Repdoc

**Prepared:** 1 November 2026  
**Repository:** `/storage/emulated/0/htdocs/Repdoc`  
**Branch / HEAD:** `main` / `11c89cc5558f81ccd893fe0d175e41a13af14fdb`  
**Production domain:** `https://repdoc.rf.gd/`  
**Status:** Local validation complete. **Not committed, not pushed, not deployed.** Approval is required before any of those steps.

> **Verification labels used in this report**
>
> - **LOCAL** — tested against the working tree with Apache 2.4.69 and PHP 8.5.12 on this machine.
> - **PRODUCTION** — checked against the live host. Only limited, read-only checks were possible.
> - **BLOCKED** — could not be verified from this environment.

---

## 1. Executive summary

The existing SEO work is intact and production-ready at the code level. The local Apache + `.htaccess` test found **one real production defect** that the earlier PHP-built-in-server test could not catch: relative redirect targets in `.htaccess` were emitted as filesystem paths (for example `/storage/.../sonod/slug/`) instead of clean URLs. This affected the new `/sonod/{slug}` redirect, the legacy `/showcase/...` redirect, and the `/Llms.txt` redirect. All three are fixed and retested.

Two content defects were also found and fixed in `src/sonod_guides.php`:

- `প্রতারিণা` → `প্রতারণা` in the verification guide description.
- A stray Korean word `확인` inside the verification guide definition → `যাচাই`.

Additional hardening: `.htaccess` now denies web access to `*.md` and `*.csv`, so the 12 research reports, the keyword CSV, `AGENTS.md`, and other internal documentation cannot be downloaded if the repository is ever synced wholesale to the web root.

**Final local state:** 228/228 sitemap URLs returned HTTP 200, canonicalised correctly, indexable, with unique titles, descriptions and H1s. All JSON-LD parsed. All 12 guides and the hub passed the new-page audit. Sitemap XML is valid with 228 URLs and 21 image entries. Unknown guide slugs and arbitrary missing paths return real HTTP 404s. Lookup-result URLs remain `noindex,follow`.

**Remaining blockers are production-access issues, not code issues:** the live host could not be fully probed from this environment (TLS EOF from the openresty front end), and no FTP/SSH/database access was supplied. Production backup and the first production crawl are therefore manual steps listed in §7.

---

## 2. Phase 1 — Git and change inspection

### 2.1 Working tree state

`git status --short` shows **14 modified files** and **15 untracked entries / 17 new files** (the 12 reports, the keyword CSV, three files inside the `sonod/` directory, and `src/sonod_guides.php`).

Modified files:

| File | Change |
|---|---|
| `.htaccess` | New `/sonod/{slug}` clean-URL rules; redirect fixes; `.md`/`.csv` deny |
| `about/index.php` | More descriptive title |
| `genaretor/certificate/index.php` | Links to all 12 guides |
| `importantlinks/index.php` | More descriptive title |
| `index.php` | Header/footer guide links; image alt text |
| `llms.txt` | Certificate guide + verification entries |
| `officefiles/index.php` | More descriptive title |
| `offlinefiles/index.php` | More descriptive title |
| `robots.txt` | `Allow: /sonod/` and `Allow: /verify/` |
| `src/catalog.php` | Header/footer guide links |
| `src/nav.php` | Site-nav guide link |
| `src/seo.php` | Registers hub + 12 guides; makes blank `/verify/` indexable |
| `src/sitemap.php` | Uses registry additions automatically |
| `verify/index.php` | Indexable blank state; noindex lookup state; help section; JSON-LD |

Untracked paths:

| Path | Purpose |
|---|---|
| `sonod/index.php` | Guide hub |
| `sonod/guide.php` | Guide template |
| `sonod/_ui.php` | Shared guide UI, disclaimer, card renderer |
| `src/sonod_guides.php` | 12-guide data source (509 lines) |
| `repdoc_keyword_database.csv` | 1,003 keyword rows (548 audited core + 455 post-audit additions) |
| `90_DAY_SEO_ROADMAP.md` | Report 1 |
| `BANGLA_KEYWORD_RESEARCH.md` | Report 2 |
| `COMPETITOR_ANALYSIS_PROTTOYON.md` | Report 3 |
| `COMPLETE_KEYWORD_MAPPING.md` | Report 4 |
| `COMPLETE_KEYWORD_RESEARCH.md` | Report 5 |
| `CONTENT_STRATEGY.md` | Report 6 |
| `ENGLISH_KEYWORD_RESEARCH.md` | Report 7 |
| `KEYWORD_GAP_ANALYSIS.md` | Report 8 |
| `LOCATION_KEYWORD_RESEARCH.md` | Report 9 |
| `QUESTION_KEYWORD_RESEARCH.md` | Report 10 |
| `SEO_AUDIT_REPORT.md` | Report 11 |
| `SEO_IMPLEMENTATION_REPORT.md` | Report 12 |

### 2.2 Review outcome

- All 12 reports were reviewed for scope, claims and consistency. They consistently mark search volume, CPC and difficulty as unavailable and avoid traffic forecasts.
- No destructive or unrelated changes were found. No reset, checkout, clean, or overwrite was performed.
- Work preservation: a pre-deployment snapshot was created outside the web root (see §7.1).

### 2.3 Existing issues found during review

| Finding | Severity | Action |
|---|---|---|
| `.htaccess` relative redirects leaked the filesystem path | **High** | Fixed (see §3.2) |
| `প্রতারিণা` typo in verification guide | Low | Fixed |
| Korean `확인` inside Bangla guide text | Medium (localisation artifact) | Fixed |
| Article JSON-LD lacked `image`; `mainEntityOfPage` referenced an undefined `#page` node | Low | Fixed |
| `*.md` / `*.csv` reachable from the web root | Medium (information exposure) | Blocked in `.htaccess` |

---

## 3. Phase 2 — `.htaccess` and production compatibility

### 3.1 Test environment

Because PHP's built-in server ignores `.htaccess`, a real Apache instance was used:

- Apache **2.4.69** (`prefork` + `mod_php` 8.5.12), `AllowOverride All`.
- `DocumentRoot` set to the repository root.
- `mod_rewrite`, `mod_headers`, `mod_expires`, `mod_deflate`, `mod_negotiation`, `mod_dir`, `mod_mime` loaded.
- Requests sent with `Host: repdoc.rf.gd` and `X-Forwarded-Proto: https` to emulate the production reverse proxy.

**LOCAL only.** This is a close model of shared hosting, not the actual host.

### 3.2 Redirect defect found and fixed

The original rules used relative redirect substitutions:

```apache
RewriteRule ^sonod/([a-z0-9-]+)$ sonod/$1/ [R=301,L]
RewriteRule ^showcase/(word|excel|ppt|psd|ai|plp)/([a-z0-9-]+)$ showcase/$1/$2/ [R=301,L]
RewriteRule ^Llms\.txt$ llms.txt [R=301,L]
```

On Apache 2.4.69 in per-directory context these produced `Location` headers containing the filesystem path and an `http://` scheme, e.g.:

```text
Location: http://repdoc.rf.gd/storage/emulated/0/htdocs/Repdoc/sonod/nagorik-sonod/
```

That is a path-disclosure bug and a broken redirect. It is exactly the class of defect that a PHP built-in server cannot expose.

Fixed to explicit canonical HTTPS targets:

```apache
RewriteRule ^showcase/(word|excel|ppt|psd|ai|plp)/([a-z0-9-]+)$ https://repdoc.rf.gd/showcase/$1/$2/ [R=301,L]
RewriteRule ^sonod/([a-z0-9-]+)$ https://repdoc.rf.gd/sonod/$1/ [R=301,L]
RewriteRule ^Llms\.txt$ https://repdoc.rf.gd/llms.txt [R=301,L]
```

New rules added:

```apache
RewriteCond %{REQUEST_URI} !/$
RewriteRule ^sonod/([a-z0-9-]+)$ https://repdoc.rf.gd/sonod/$1/ [R=301,L]
RewriteRule ^sonod/([a-z0-9-]+)/?$ sonod/guide.php?slug=$1 [L,QSA]

RewriteRule \.(md|csv)$ - [F,L,NC]
```

### 3.3 Local Apache test results

| Test | Expected | Observed | Result |
|---|---|---|---|
| `/` | 200 | 200 | PASS |
| `/sonod/` | 200 | 200 | PASS |
| `/sonod` (no slash) | 301 → `/sonod/` | 301 → `http://repdoc.rf.gd/sonod/` (mod_dir), then HTTPS rule → `https://repdoc.rf.gd/sonod/` | PASS, 2 hops behind TLS proxy |
| `/sonod/nagorik-sonod` | 301 → canonical slash URL | 301 → `https://repdoc.rf.gd/sonod/nagorik-sonod/` | PASS |
| `/sonod/nagorik-sonod?utm_source=test` | 301, query preserved | 301 → `.../nagorik-sonod/?utm_source=test` | PASS |
| All 12 `/sonod/{slug}/` | 200 | 200 | PASS |
| `/sonod/{unknown}/` | 404 | 404 | PASS |
| `/sonod/guide.php?slug=...` | 200, canonical to pretty URL | 200, canonical correct | PASS |
| `/verify/` | 200, indexable | 200, `index,follow` | PASS |
| `/verify/?no=...` | 200, `noindex,follow` | 200, `noindex,follow` | PASS |
| `/verify` (no slash) | 301 → `/verify/` | 301 → `http://.../verify/` then HTTPS rule | PASS, 2 hops behind TLS proxy |
| `/showcase/word/{slug}` | 301 → slash URL | 301 → `https://repdoc.rf.gd/showcase/word/{slug}/` | PASS |
| `/showcase/word/{slug}/` | 200 | 200 | PASS |
| `/sitemap.xml` | 200 XML | 200 XML | PASS |
| `/Llms.txt` | 301 → `/llms.txt` | 301 → `https://repdoc.rf.gd/llms.txt` | PASS |
| `/robots.txt`, `/llms.txt` | 200 | 200 | PASS |
| `/index.php` | 301 → `/` | 301 → `https://repdoc.rf.gd/` | PASS |
| HTTP request | 301 → HTTPS | 301 → `https://repdoc.rf.gd/...` | PASS |
| `www.repdoc.rf.gd` | 301 → apex HTTPS | 301 → `https://repdoc.rf.gd/` | PASS |
| `repdoc.freedev.app` | 301 → apex HTTPS | 301 → `https://repdoc.rf.gd/` | PASS |
| Unknown path | real 404 | HTTP 404 | PASS |
| `/love/` | 403 | 403 | PASS |
| `/src/*.php` | 403 | 403 | PASS |
| `/AGENTS.md`, `/README.md`, `/repdoc_keyword_database.csv` | 403 after hardening | 403 | PASS |
| Static CSS | 200 | 200 | PASS |
| Redirect loops | none | none observed | PASS |
| Gated tools | 302 → login | 302 → login | PASS (expected) |

### 3.4 Production-specific limitations

- **PRODUCTION** front end is **openresty** (nginx family), not Apache. The backend that processes `.htaccess` is not directly observable. Local Apache models the rewrite contract, but the final proxy/TLS chain was not testable.
- **BLOCKED:** Dynamic production pages could not be fetched. `https://repdoc.rf.gd/robots.txt` returned 200, but `/`, `/sonod/`, `/verify/` and `/sitemap.xml` failed with `OpenSSL SSL_read: unexpected eof while reading`.
- **PRODUCTION** `robots.txt` was still the pre-deployment version (Last-Modified 28 Oct 2026, no `/sonod/` or `/verify/` allow lines). This confirms the SEO changes are **not live**.
- Clean URLs depend on `mod_rewrite`. The new rules sit inside `<IfModule mod_rewrite.c>`; if rewrite were unavailable the rules would silently not apply. Existing showcase/sitemap routing already relies on the same module, so this is not a new dependency.
- `Options -MultiViews` is inside `<IfModule mod_negotiation.c>`. It is pre-existing. Some shared hosts disable `Options` overrides; if that were the case the site would already have been affected before this deployment.
- Bare directory URLs (`/sonod`, `/verify`) are handled by `mod_dir`, which emits an `http://` slash redirect when the backend sees proxied HTTP. The following HTTPS rule then corrects it. No loop, but two hops. With direct TLS at Apache it is one hop.
- The `.htaccess` redirect targets hardcode `https://repdoc.rf.gd`; `REPDOC_PUBLIC_BASE` can override canonical URLs in PHP but not these redirects. This matches the pre-existing domain strategy.

---

## 4. Phase 3 — Technical SEO validation

### 4.1 Full sitemap crawl — LOCAL

Every URL in the generated sitemap was fetched from the local Apache instance with production headers.

| Check | Result |
|---|---|
| Sitemap URLs | **228** |
| Image entries | **21** (13 unique image URLs) |
| XML parses | PASS |
| `<loc>` values begin with `https://repdoc.rf.gd/` | 228 / 228 |
| Duplicate `<loc>` values | 0 |
| Sitemap URLs returning HTTP 200 | 228 / 228 |
| Sitemap URLs whose canonical matches the URL | 228 / 228 |
| Pages with `<meta name="robots" content="noindex...">` | 0 |
| Sitemap URLs blocked by `robots.txt` | 0 |
| Invalid `lastmod` / `changefreq` / `priority` values | 0 |
| Image URLs returning HTTP 200 | 13 / 13 unique |

### 4.2 Metadata and headings — LOCAL

For all **228** sitemap pages:

| Check | Result |
|---|---|
| Unique `<title>` | PASS (0 duplicate groups) |
| Unique meta description | PASS (0 duplicate groups) |
| Non-empty title and description | PASS |
| Exactly one `<h1>` | PASS (0 pages with count ≠ 1) |
| Unique H1 text | PASS (0 duplicate groups) |
| Canonical present and self-referencing | PASS |
| Open Graph title, description, URL, image | PASS |
| OG URL equals canonical | PASS |
| JSON-LD blocks decode as JSON with `@context` + `@type` | PASS |
| JSON-LD blocks per new page | 5–6 |

Heading hierarchy was inspected on the 14 new pages: guides use one H1 followed by seven H2 sections and no skipped levels; the verification page uses H1 → H2 → H3 in order. A full H2/H3-order crawl of all 228 pages was not completed; H1 count and uniqueness were checked on all 228.

### 4.3 New-page detail — LOCAL

| Page | Title length | Description length | H1 | Canonical | Robots |
|---|---:|---:|---|---|---|
| `/sonod/` | 61 | 147 | 1 | self | index |
| 12 guide pages | 46–61 | 95–122 | 1 each | self | index |
| `/verify/` | 34 | 159 | 1 | self | index |
| `/verify/?no=...` | 34 | 159 | 1 | `/verify/` | `noindex,follow` |

- Descriptions are within normal SERP truncation ranges.
- Guide titles include the certificate name and "Repdoc"; they do not claim government authority.
- The lookup page keeps the canonical to the blank `/verify/`, which is correct for a parameterised result state.

### 4.4 Indexing and privacy

- Blank `/verify/` is indexable and in the sitemap.
- Lookup-result URLs are `noindex,follow` and are **not** in the sitemap.
- No query-string URL appears in the sitemap.
- Gated tools (`/genaretor/prottoyon/`, `/genaretor/cvmaker/`, tools, admin) remain gated and out of the sitemap.
- `/love/` and `/src/*.php` return 403.
- `*.md` and `*.csv` are denied at the server level.
- `robots.txt` allows `/sonod/` and `/verify/` and does not block any sitemap URL.
- The lookup table exposes only: certificate number, certificate type, applicant name, issuing office, issue date, status. NID, address and attachments are not shown. A rate limit of 30 attempts per 10 minutes remains.

### 4.5 Internal linking and assets — LOCAL

Starting from the hub, all 12 guides, the blank verify page and one lookup result:

- 34 unique internal link targets checked.
- 0 broken content links.
- 0 broken images or OG images.
- Gated tool links intentionally return 302 to login.
- Hub links to all 12 guides; each guide links to three related guides, the hub, the certificate generator and the verification page.
- Navigation, footer, homepage and certificate-generator landing page all link to the new hub.

### 4.6 Structured-data fix

`sonod/guide.php` previously emitted an `Article` with no `image` and a `mainEntityOfPage` reference to an undefined `#page` node. It now emits:

- `mainEntityOfPage` as the canonical URL string.
- `image` as the existing 1200×630 `og-cover.png`.

Re-validated: Organization, WebSite, Article, BreadcrumbList and FAQPage all decode correctly.

---

## 5. Phase 4 — Content and keyword quality

### 5.1 Guide content

| Guide | Bangla words* | H2 sections | FAQs | Related guides |
|---|---:|---:|---:|---:|
| nagorik-sonod | 342 | 7 | 3 | 3 |
| warish-sonod | 302 | 7 | 3 | 3 |
| charitrik-sonod | 258 | 7 | 3 | 3 |
| jatiyota-sonod | 267 | 7 | 3 | 3 |
| sthayi-basinda-sonod | 227 | 7 | 3 | 3 |
| paribarik-sonod | 229 | 7 | 3 | 3 |
| income-sonod | 265 | 7 | 3 | 3 |
| bhumihin-sonod | 225 | 7 | 3 | 3 |
| protibondhi-sonod | 222 | 7 | 3 | 3 |
| mrityu-pratyayon | 239 | 7 | 3 | 3 |
| trade-license | 242 | 7 | 3 | 3 |
| sonod-verification | 292 | 7 | 3 | 3 |

\* Word count excludes shared header/footer/disclaimer and JSON-LD.

Findings:

- **Accuracy:** guides hedge on fees, timelines and issuing authority, and repeatedly tell the reader to confirm with the local office. No invented fees, dates or legal claims were found.
- **Affiliation:** no page claims Repdoc is a government office or portal. The hub, every guide and the verification page carry an explicit non-government disclaimer.
- **Originality:** each guide has distinct definitions, document lists, steps, FAQs and related links; there is no duplicated guide body.
- **Search intent:** each guide targets definition, documents, process, authority and verification questions. English/Banglish alias lines are present without creating thin English pages.
- **Keyword stuffing:** no repeated-keyword blocks or unnatural repetition found. Terms appear naturally in headings and body copy.
- **Internal linking:** hub ↔ guides ↔ verification/generator links are present and crawlable.
- **Terminology:** standard Bangla terms are used consistently (`সনদ`, `প্রত্যয়ন`, `ওয়ারিশ`, `ইস্যুকারী কর্তৃপক্ষ`).
- **Defects fixed:** `প্রতারিণা` → `প্রতারণা`; `확인` → `যাচাই`.

### 5.2 Keyword database

| Check | Result |
|---|---|
| Rows (audited core set) | 548 |
| Malformed rows (current 643) | 0 |
| Mapped URLs (audited core set) | 13 (hub + 12 guides) |
| Empty mapped URL (audited core set) | 0 (birth rows use explicit `UNMAPPED`) |
| Exact duplicate keyword strings (current 643) | 0 |
| Duplicate keyword + URL pairs (current 643) | 0 |
| Near-duplicate groups (audited core set) | 3 |
| Distinct seeds (audited core set) | 98; 99 current |
| Languages (audited core set) | 328 Bangla, 220 English/Banglish; current 375 / 268 |
| Intents (audited core set) | 263 informational, 123 download/form, 89 apply/renew, 49 verify/check, 17 fee, 7 authority |
| Priority (audited core set) | 404 high, 134 medium, 10 low |
| Volume / difficulty / CPC | `unavailable` on all 1,003 rows |
| Fabricated metrics | None found |

Near-duplicate groups (acceptable spelling/punctuation variants):

- `নাগরিক সনদ কিভাবে পাব` / `...পাবো`
- `অবিবাহিত সনদপত্র কোথায় পাব` / `...পাবো`
- `মৃত্যু প্রত্যয়ন পত্র` / `মৃত্যু প্রত্যয়ন পত্র।`

**Post-audit additions (1 November 2026):** (1) 95 birth-certificate keywords were appended with `topic=jonmo-sonod` and `mapped_url=UNMAPPED`; a 96th birth row (`বাংলাদেশে জন্ম সনদ`, also `UNMAPPED`) came from the office-files extraction. (2) 360 office-files keywords were added from the live `/officefiles/` page: 214 map to `/officefiles/`, 126 to individual `/showcase/word/.../` template pages, 18 to `/sonod/.../` guides, 1 to the homepage brand query, and 1 is the UNMAPPED birth row. Current CSV totals are 1,003 rows, 100 seeds, 542 Bangla / 461 English-Banglish, with 0 malformed rows, 0 exact duplicate keyword strings, 101 distinct mapped URLs (13 sonod + 1 officefiles hub + 86 showcase + 1 homepage), and 96 `UNMAPPED` birth rows. The deployed 228-URL set is unchanged. See `COMPLETE_KEYWORD_MAPPING.md` §3.1–3.2.

Mapping review notes (not changed):

- Verification-intent queries correctly map to `/sonod/sonod-verification/` even when they mention a specific certificate type.
- Two hub-level mappings are arguable rather than wrong: `online trade licence application bangladesh` → `/sonod/`, and `ইউনিয়ন পরিষদ আয়ের সনদ` → `/sonod/`. Both are broad enough for the hub, but could later move to the specific guide if Search Console shows guide-level intent.
- `citizenship certificate bangladesh` maps to `/sonod/jatiyota-sonod/`; citizenship is ambiguous between the nationality and citizen-certificate guides, so this is defensible.
- The CSV header contains a UTF-8 BOM. Excel handles it; a naive SQL importer may include the BOM in the first column name. Not changed because it aids Excel/Bangla compatibility.

No search volume, CPC or difficulty value was invented anywhere.

---

## 6. Phase 5 — Deployment preparation

### 6.1 Local pre-deployment snapshot (created)

Location:

```text
/data/data/com.termux/files/home/repdoc-backups/2026-11-01_pre-seo-deploy/
```

Contents:

| Item | Purpose |
|---|---|
| `repdoc_HEAD.tar.gz` (45 MB) | Full tracked tree at `HEAD` (`11c89cc`), usable for rollback |
| `tracked-changes.patch` | Complete diff of the 14 modified files |
| `new-files/untracked-seo-files.tar.gz` | `sonod/`, `src/sonod_guides.php`, 12 reports, keyword CSV |
| `db/repdoc.db`, `db/birth_register_src.db` | Local SQLite snapshots |
| `db/*.json` | Local runtime JSON state |
| `HEAD.txt`, `git-status.txt`, `deployment-manifest.sha256` | Provenance and integrity |

> This is a **local** snapshot. It is not a backup of the production host. A production file and database backup is still required before upload (§7.2).

### 6.2 Files that will change in production

Runtime files only — **18 files**:

| # | File | Action |
|---:|---|---|
| 1 | `.htaccess` | Replace |
| 2 | `robots.txt` | Replace |
| 3 | `llms.txt` | Replace |
| 4 | `index.php` | Replace |
| 5 | `about/index.php` | Replace |
| 6 | `genaretor/certificate/index.php` | Replace |
| 7 | `importantlinks/index.php` | Replace |
| 8 | `officefiles/index.php` | Replace |
| 9 | `offlinefiles/index.php` | Replace |
| 10 | `verify/index.php` | Replace |
| 11 | `src/seo.php` | Replace |
| 12 | `src/sitemap.php` | Replace |
| 13 | `src/catalog.php` | Replace |
| 14 | `src/nav.php` | Replace |
| 15 | `src/sonod_guides.php` | **New** |
| 16 | `sonod/index.php` | **New** |
| 17 | `sonod/guide.php` | **New** |
| 18 | `sonod/_ui.php` | **New** |

**Do not upload to the web root:**

- `AGENTS.md`, `README.md`
- the 12 SEO research/report `.md` files
- `repdoc_keyword_database.csv`
- `SEO_PRODUCTION_READINESS_REPORT.md`
- `.env.example`, `.github/`, `Plan/`, `.kiro/`, `.freebuff/`, `index.php.bak`

The new `.htaccess` deny rule blocks `.md` and `.csv` as a safety net, but these files still should not be uploaded.

### 6.3 Upload order (important)

1. Back up production files and database.
2. Upload `src/sonod_guides.php` **first** (`src/seo.php` requires it).
3. Upload `sonod/index.php`, `sonod/guide.php`, `sonod/_ui.php`.
4. Upload `src/seo.php`, `src/sitemap.php`, `src/catalog.php`, `src/nav.php`.
5. Upload `verify/index.php`, `index.php`, `about/index.php`, `importantlinks/index.php`, `officefiles/index.php`, `offlinefiles/index.php`, `genaretor/certificate/index.php`.
6. Upload `robots.txt` and `llms.txt`.
7. Upload `.htaccess` **last**.
8. Run the post-deployment checks in §7.4.

If `src/seo.php` is uploaded before `src/sonod_guides.php`, pages that load SEO helpers will fatal until the second file arrives. The order above prevents that.

### 6.4 Database migration

**None required.**

- No SQL files, schema changes, new tables, new columns or migrations were added.
- The guides are static PHP arrays.
- `/verify/` reads the existing `document_logs` table through existing `prottoyon_*` helpers.
- No new environment variables are required.

### 6.5 Interruption risk

| Risk | Assessment | Mitigation |
|---|---|---|
| Missing `src/sonod_guides.php` while `src/seo.php` is live | High impact, temporary | Upload order in §6.3 |
| `.htaccess` uploaded too early | New `/sonod/` routes 404 until files arrive | Upload last |
| Database | No writes or schema changes | None needed |
| Static edge cache (`robots.txt`, `llms.txt`) | Up to 1 hour (`Cache-Control: max-age=3600`) | Purge or verify with `?v=timestamp` |
| Existing tools/login | Not touched by this change set | Standard smoke test |
| File permissions | Local files are `660`; shared host may require `644`/`755` | Set `644` files, `755` directories if upload does not inherit |

Deploy during low traffic; no maintenance mode is required.

### 6.6 Rollback

1. Restore the production files from the hosting backup taken in §7.2 (or re-upload the 18 files from `repdoc_HEAD.tar.gz`, which contains `HEAD`).
2. Delete `sonod/` and `src/sonod_guides.php` if restoring from `HEAD`.
3. Restore `.htaccess`, `robots.txt` and `llms.txt` **last**.
4. No database rollback is needed because there were no schema or data changes.
5. Purge the host/edge cache for `robots.txt`, `llms.txt` and `sitemap.xml`.
6. Verify:
   - `/sonod/` returns 404 (old state),
   - `/sitemap.xml` returns 214 URLs (old state),
   - homepage, login, showcase and certificate generator still work.

---

## 7. Phase 6 — Deployment checklist and final verification

### 7.1 Production backup (manual, before upload)

- [ ] Download the current `htdocs/` tree via FTP/file manager (or the host's full-site backup).
- [ ] Export the production database via phpMyAdmin (structure + data) or `mysqldump`.
- [ ] Download `love/` runtime files if the host uses SQLite: `repdoc.db`, `birth_records.db`, `birth_register_src.db`, `important_links.json`, `software.json`, `passport_settings.json`.
- [ ] Download `love/seo-controls.json` if it exists (indexing overrides).
- [ ] Store the backup off-host with the date in the filename.
- [ ] Confirm the backup opens/imports before changing anything.

### 7.2 Pre-upload checklist

- [ ] Approval received from Freddy.
- [ ] (Optional) Commit the changes on `main` after approval; do not push until approved.
- [ ] Confirm production PHP version is 8.0+ (`str_starts_with` / `str_ends_with` are used). The codebase already requires PHP 8+.
- [ ] Confirm the host allows `.htaccess` overrides for rewrite/headers/expires.
- [ ] Exclude non-runtime `.md` / `.csv` / `AGENTS.md` from the upload set.
- [ ] Keep a copy of the 18 runtime files and their checksums.

### 7.3 Upload checklist

- [ ] Upload in the order in §6.3.
- [ ] `sonod/` directory permissions correct (`755`).
- [ ] `.htaccess` uploaded last.
- [ ] No `.md`/`.csv`/`.bak` files in the web root.
- [ ] HTTPS still enforced; no mixed-content warnings.

### 7.4 Post-deployment verification (PRODUCTION)

- [ ] `https://repdoc.rf.gd/` → 200.
- [ ] `https://repdoc.rf.gd/sonod/` → 200, indexable, canonical `https://repdoc.rf.gd/sonod/`.
- [ ] All 12 `https://repdoc.rf.gd/sonod/{slug}/` → 200.
- [ ] `/sonod/{slug}` (no trailing slash) → single 301 to the HTTPS slash URL, no filesystem path in `Location`.
- [ ] `/verify/` → 200 and indexable.
- [ ] `/verify/?no=...` → 200 and `noindex,follow`; no NID/address/attachments.
- [ ] `/about/`, `/showcase/`, `/officefiles/`, `/offlinefiles/`, `/importantlinks/`, `/genaretor/certificate/`, `/public/login/` → 200.
- [ ] `/sitemap.xml` → 228 URLs, 21 images, valid XML, HTTPS domain only.
- [ ] `/robots.txt` → contains `Allow: /sonod/` and `Allow: /verify/`.
- [ ] `/AGENTS.md`, `/README.md`, `/repdoc_keyword_database.csv` → 403.
- [ ] Unknown path → real HTTP 404.
- [ ] No redirect loop; `curl -I` on the key URLs.
- [ ] Google Search Console: submit `https://repdoc.rf.gd/sitemap.xml` (only after deployment).
- [ ] Rich Results Test on `/sonod/`, one guide and `/verify/`.
- [ ] Mobile check on the hub and one guide.
- [ ] Server error log clean for the first 24 hours.

### 7.5 Test register

**Passed — LOCAL**

- Git/change inventory and preservation.
- PHP lint on all 18 runtime files.
- 228 sitemap URLs: HTTP 200, canonical match, indexable, unique title/description/H1.
- JSON-LD parse on 228 pages; 5–6 blocks on each new page.
- Sitemap XML validity, 228 URLs, 21 images, production HTTPS domain.
- All 13 unique image URLs return 200.
- Robots: 0 sitemap URLs blocked.
- New-page link crawl: 0 broken content links; gated 302s expected.
- 404 handling, `.htaccess` redirects, HTTPS canonicalisation, trailing slash, no loops.
- `.md`/`.csv` deny.
- Guide content review; CSV duplicate/mapping review.

**Failed then fixed — LOCAL**

- `.htaccess` relative redirects emitted filesystem paths. Fixed and retested.
- Two Bangla/localisation defects in the verification guide. Fixed and retested.
- Article JSON-LD missing `image` and dangling `mainEntityOfPage` reference. Fixed and retested.

**Not verifiable — PRODUCTION / BLOCKED**

- Production `.htaccess` contents, Apache/LiteSpeed version and `AllowOverride` policy.
- Production TLS/proxy redirect chain (openresty front end; dynamic requests failed TLS).
- Production PHP version and MySQL schema.
- A real valid certificate lookup (no test certificate number/database access).
- Production Search Console indexing and rich-result status.
- Full H2/H3-order crawl of all 228 pages (H1 count/uniqueness was fully checked).

---

## 8. Files created by this readiness pass

| File | Purpose |
|---|---|
| `SEO_PRODUCTION_READINESS_REPORT.md` | This report |
| `/data/data/com.termux/files/home/repdoc-backups/2026-11-01_pre-seo-deploy/` | Local rollback snapshot |

No commit, push, or deployment has been performed.

---

## Appendix A — SHA-256 of runtime deployment files

Manifest file: `/data/data/com.termux/files/home/repdoc-backups/2026-11-01_pre-seo-deploy/deployment-manifest.sha256`

```text
37f3e57141dd2f1cd065fdca9cd59c4f82527c92a48d631743e5ea2589383021  .htaccess
```

The remaining 17 checksums are in the manifest. Re-generate them immediately before upload if any file is edited after this report.

---

## 9. Approval gate

Everything in this report is complete up to the point of commit, push and deployment.

**Awaiting Freddy's approval to:**

1. Commit the changes.
2. Push to the remote.
3. Upload the 18 runtime files to production.
4. Submit the sitemap in Search Console.

Until that approval arrives: no commit, no push, no FTP upload, no database changes, no Adsterra work.
