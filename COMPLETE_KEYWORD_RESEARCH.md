# Complete Keyword Research — Repdoc

**Prepared:** 1 November 2026  
**Primary market:** Bangladesh, Bangla-first with English and Banglish variants  
**Primary competitor context:** `prottoyon.gov.bd`  
**Structured database:** `repdoc_keyword_database.csv` (548 unique keywords)

---

## 1. Method and data quality

### 1.1 Sources used

- Google Suggest autocomplete, collected on 31 October 2026.
- 79 seed queries across Bangla, English, Banglish and location variants.
- Manual SERP spot checks for selected high-intent queries.
- Repdoc’s existing page registry, 23 certificate types, and 12 new guides.
- Public competitor pages, robots.txt and sitemap probes.

### 1.2 Dataset size

| Metric | Value |
|---|---|
| Seed queries | 79 |
| Raw suggestions | 592 |
| Unique suggestions after deduplication | 548 |
| Bangla-script keywords | 328 |
| English/Banglish keywords | 220 |
| Keywords mapped to the guide hub | 147 |
| Keywords mapped to specific guides | 401 |
| Keywords with location modifiers | 7 |

> **Update 1 November 2026:** 95 birth-certificate keywords (`topic=jonmo-sonod`, `mapped_url=UNMAPPED`) and 360 office-files keywords extracted from the live `/officefiles/` page were appended after this research. They are not autocomplete-derived; the CSV now has 1,003 rows. See `COMPLETE_KEYWORD_MAPPING.md` §3.1–3.2.

### 1.3 What is **not** available

- Search volume.
- Keyword difficulty.
- CPC.
- Competition score.
- Click-through rate.
- Conversion rate.

These are marked `unavailable` in the CSV. No number in this report should be read as a traffic or revenue forecast.

### 1.4 Important interpretation rule

Google Suggest shows **how people phrase queries**, not how many people search them. A suggestion appearing first is not proof of higher volume. The priority model below is based on service relevance, intent, competitor weakness, and Repdoc’s ability to answer honestly.

Autocomplete can also return adjacent or transliterated noise (for example, “digital sand” from a ডিজিটাল সনদ seed). Those strings are retained in the raw CSV for transparency but are not prioritised and should not be targeted.

---

## 2. Intent distribution

| Intent | Unique keywords | Share | What the searcher wants |
|---|---:|---:|---|
| Informational | 263 | 48.0% | Definition, eligibility, rules, documents |
| Download/form | 123 | 22.4% | Form, PDF, Word, format, sample |
| Apply/renew | 89 | 16.2% | Application process, online application, renewal |
| Verify/check | 49 | 8.9% | Certificate verification, application status |
| Fee | 17 | 3.1% | Cost, fee, charges |
| Authority | 7 | 1.3% | Where to apply, which office |

The dominance of informational and form-download intent supports a guide-first strategy. Repdoc can answer those queries without pretending to issue certificates. Apply/renew and verify/check require careful boundaries.

---

## 3. Topic clusters

| Cluster | Keywords | Bangla | English/Banglish | Primary page |
|---|---:|---:|---:|---|
| Generic sonod hub | 147 | 71 | 76 | `/sonod/` |
| নাগরিক / নাগরিকত্ব | 76 | 48 | 28 | `/sonod/nagorik-sonod/` |
| ওয়ারিশ / উত্তরাধিকার | 62 | 49 | 13 | `/sonod/warish-sonod/` |
| Verification / status | 52 | 32 | 20 | `/sonod/sonod-verification/` + `/verify/` |
| Trade licence | 39 | 29 | 10 | `/sonod/trade-license/` |
| চারিত্রিক | 38 | 17 | 21 | `/sonod/charitrik-sonod/` |
| মৃত্যু / মৃত্যু প্রত্যয়ন | 38 | 22 | 16 | `/sonod/mrityu-pratyayon/` |
| জাতীয়তা | 23 | 9 | 14 | `/sonod/jatiyota-sonod/` |
| আয় | 22 | 12 | 10 | `/sonod/income-sonod/` |
| প্রতিবন্ধী | 17 | 9 | 8 | `/sonod/protibondhi-sonod/` |
| পারিবারিক | 14 | 10 | 4 | `/sonod/paribarik-sonod/` |
| ভূমিহীন | 10 | 10 | 0 | `/sonod/bhumihin-sonod/` |
| স্থায়ী বাসিন্দা | 10 | 10 | 0 | `/sonod/sthayi-basinda-sonod/` |

### 3.1 Priority tiers

**Tier 1 — build depth and internal links first**

- নাগরিক / নাগরিকত্ব
- ওয়ারিশ / উত্তরাধিকার
- চারিত্রিক
- ট্রেড লাইসেন্স
- সনদ যাচাই

Reasons: high query variety, strong informational intent, repeated form/download modifiers, and weak competitor content structure.

**Tier 2 — expand with checklists and FAQs**

- আয়
- মৃত্যু / মৃত্যু প্রত্যয়ন
- জাতীয়তা
- পারিবারিক
- ভূমিহীন
- প্রতিবন্ধী
- স্থায়ী বাসিন্দা

Reasons: clear document-checklist intent and useful long-tail, but more variation in local rules and eligibility.

**Tier 3 — hold or qualify**

- Application status.
- Exact fee amounts.
- Exact processing times.
- Location-specific service claims.
- Any request for official government tracking.

Reasons: Repdoc cannot verify or fulfil these reliably. A page can explain the concept, but must not promise an official status lookup or a fixed fee.

---

## 4. Recurring query modifiers

### 4.1 Bangla

| Modifier | Example | Content response |
|---|---|---|
| অনলাইন | নাগরিক সনদ অনলাইন | Explain that online availability varies by office |
| ডাউনলোড / PDF / Word | চারিত্রিক সনদ pdf | Offer a draft/print explanation, not a fake official PDF |
| ফরম / ফরম্যাট | ওয়ারিশ সনদ ফরম | Show what information a form usually asks for |
| কি লাগে | নাগরিক সনদ করতে কি কি লাগে | Document checklist |
| কিভাবে / কীভাবে | সনদ কিভাবে পাব | Step-by-step process |
| নিয়ম | ট্রেড লাইসেন্স আবেদন করার নিয়ম | Rules and sequence |
| কোথায় | নাগরিক সনদ কোথায় পাব | Authority finder explanation |
| যাচাই | সনদ যাচাই | Verification guide |
| স্ট্যাটাস | আবেদন স্ট্যাটাস | Explain application tracking boundaries |
| ফি / কত টাকা | ট্রেড লাইসেন্স আবেদন ফি | State that fees vary; direct to office |

### 4.2 English / Banglish

- citizenship certificate bangladesh
- character certificate bangladesh
- heir certificate bangladesh
- trade license bangladesh
- online certificate application bangladesh
- certificate verification bangladesh
- nagorik sonod
- warish sonod
- charitrik sonod
- jatiyota sonod
- sonod verify
- prottoyon online

These should be treated as secondary discovery paths, not as a reason to publish thin English duplicates of every Bangla guide.

---

## 5. High-value keyword examples

### 5.1 নাগরিক সনদ

- নাগরিক সনদ
- নাগরিক সনদপত্র
- নাগরিক সনদ অনলাইন
- নাগরিক সনদ আবেদন
- নাগরিক সনদ করতে কি কি লাগে
- নাগরিক সনদপত্র অনলাইন ডাউনলোড
- নাগরিকত্ব সনদ
- নাগরিকত্ব সনদ আবেদন ফরম
- ইউনিয়ন পরিষদ নাগরিক সনদ
- ডিজিটাল নাগরিক সনদ

### 5.2 ওয়ারিশ সনদ

- ওয়ারিশ সনদ
- ওয়ারিশ সনদপত্র
- ওয়ারিশ সনদের প্রয়োজনীয় কাগজপত্র
- ওয়ারিশ সনদ অনলাইন
- উত্তরাধিকার সনদ
- উত্তরাধিকার সনদ pdf
- উত্তরাধিকার সনদ আবেদন ফরম
- ইউনিয়ন পরিষদ ওয়ারিশ সনদ

### 5.3 চারিত্রিক সনদ

- চারিত্রিক সনদ
- চারিত্রিক সনদ ফরম
- চারিত্রিক সনদ ফরমেট
- চারিত্রিক সনদপত্র download
- চারিত্রিক সনদপত্র ওয়ার্ড ফাইল
- চারিত্রিক সনদ পেতে কি লাগে
- চারিত্রিক সনদ অনলাইন

### 5.4 ট্রেড লাইসেন্স

- ট্রেড লাইসেন্স
- ট্রেড লাইসেন্স আবেদন
- ট্রেড লাইসেন্স আবেদন ফরম
- ট্রেড লাইসেন্স আবেদন করতে কি কি লাগে
- ট্রেড লাইসেন্স আবেদন করার নিয়ম
- ট্রেড লাইসেন্স নবায়ন
- ট্রেড লাইসেন্স আবেদন ফি

### 5.5 Verification

- সনদ যাচাই
- সার্টিফিকেট যাচাই
- অনলাইনে সার্টিফিকেট যাচাই
- সনদ ট্র্যাকিং
- আবেদন স্ট্যাটাস
- sonod verify
- certificate verification bangladesh

---

## 6. Clusters Repdoc should not force

### 6.1 Exact fees

Queries such as “কত টাকা” are common, but fees vary by office, certificate type and time. A static number would become wrong. Answer with a clear “check the current office fee” statement and avoid fake precision.

### 6.2 Exact processing time

“কত দিনে পাব” cannot be answered nationally. Repdoc can explain the factors that affect timing, but should not promise a number.

### 6.3 Official application status

“আবেদন স্ট্যাটাস” is a real query, but Repdoc does not operate the government workflow. A page must not imply that entering an application ID will check an official Prottoyon application.

### 6.4 Location pages

Location suggestions exist, including Khagrachhari, Rangamati and Chattogram. Creating one page per district or union would be a doorway-page pattern with little unique value. Use one authoritative guide and, where useful, a short “authority varies by area” section.

### 6.5 Government-brand queries

Queries containing “সরকারি” or “prottoyon” may attract users looking for the official portal. Repdoc can rank for informational variants, but must not mimic the official brand or imply endorsement.

---

## 7. Keyword-to-page logic

The mapping follows four rules:

1. **One primary page per topic.** No competing pages for the same intent.
2. **Hub page for generic queries.** `/sonod/` captures broad “সনদ” and “প্রত্যয়ন” phrasing.
3. **Guide page for specific certificate queries.** Each guide owns its certificate cluster.
4. **Verification has two pages with different jobs.** `/sonod/sonod-verification/` explains verification; `/verify/` performs Repdoc document lookup.

Full URL-level mapping is in `COMPLETE_KEYWORD_MAPPING.md`.

---

## 8. Content gaps revealed by the data

| Gap | Evidence | Recommended response |
|---|---|---|
| Public guide layer | Hundreds of informational suggestions | 12 guides added in this pass |
| Verification clarity | 52 verification/status keywords | Verification guide + indexable blank verify page |
| Form/download intent | 123 download/form keywords | Add form-field explanations and printable checklists, not fake official PDFs |
| English/Banglish | 220 keywords | “Also searched as” aliases now visible; consider full English summaries later |
| Location intent | 7 location-modified keywords | One guide per topic; no mass doorway pages |
| Fees/timelines | 17 fee keywords | Explain variability; do not invent numbers |
| Application status | Multiple status suggestions | Only add if Repdoc has a real status feature |

---

## 9. Measurement plan

Because volume data is unavailable, measure with first-party signals:

- Google Search Console impressions and queries, if access exists.
- Server-log search terms and referrers.
- On-site search terms.
- Clicks from the guide hub to each guide.
- Clicks from guides to the generator and verification page.
- Indexed-page count and sitemap discovery.
- Query-page impressions after 28 and 56 days.

Do not judge success by publishing volume. Judge it by whether the right guide earns impressions for the right certificate intent and sends qualified users to a real Repdoc tool.

---

## 10. Bottom line

The keyword landscape is broad but coherent. The strongest opportunity is not “more certificates”; it is **clearer answers around the certificates Repdoc already supports**. Bangla informational and form-preparation queries are the core. English/Banglish and verification queries are valuable secondary layers. Location, fee, timeline and official-status queries need strict boundaries.

The CSV is the source of truth for individual keywords. This report is the strategic layer above it.
