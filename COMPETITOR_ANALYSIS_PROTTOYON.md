# Competitor Analysis — prottoyon.gov.bd

**Prepared:** 1 November 2026  
**Primary competitor:** `https://prottoyon.gov.bd/`  
**Comparison site:** Repdoc, canonical `https://repdoc.rf.gd/`  
**Method:** Public HTML, robots.txt, sitemap probes, indexed-page discovery, page-structure review, and service-workflow reading. No private dashboard, admin route, or authenticated process was accessed.

---

## 1. Why this competitor matters

`prottoyon.gov.bd` is the closest service-level match to Repdoc’s certificate and attestation tools. It targets the same broad demand: citizens who need a certificate, do not know the exact process, and search in Bangla for terms such as নাগরিক সনদ, ওয়ারিশ সনদ, চারিত্রিক সনদ, আয় সনদ and সনদ যাচাই.

Its advantage is not superior web publishing. It is **institutional trust**. A `.gov.bd` domain, a visible government workflow, and the ability to issue an approved certificate create a level of trust Repdoc cannot honestly copy. Repdoc’s opportunity is different: be the clearest independent explainer and draft-preparation tool, while repeatedly stating that official issuance belongs to the relevant authority.

---

## 2. What the competitor actually is

### 2.1 Homepage architecture

The homepage is a thin wrapper:

- HTTP 200.
- Approximately **3.7 KB** of HTML.
- Title contains a long list of certificate names.
- No meta description.
- No H1 or H2.
- No canonical.
- No Open Graph or Twitter tags.
- No JSON-LD.
- The visible application is loaded through an iframe from `prottoyon.live`, with a `secretSourceOlUrl=prottoyon.gov.bd` parameter.
- Googlebot receives the same wrapper HTML, not a server-rendered service page.

This means the strongest page on the most authoritative domain is **not** a strong content asset. It is a gateway.

### 2.2 Subpage architecture

Key subpages are server-rendered on the `gov.bd` domain, including:

- `/faq`
- `/terms`
- `/verifyCertificate`
- `/searchPage`
- `/union/covered`
- `/municipality/covered`
- `/register/citizen`
- `/generateCertificate`
- notices pages

These pages have unique titles in the pattern `প্রত্যয়ন | …`, but they repeat the same meta description, have **zero H1 and H2 headings**, and do not output canonical, Open Graph, Twitter or JSON-LD tags.

### 2.3 Crawl controls

- `robots.txt` is permissive: essentially `User-agent: *` and `Allow: /`.
- `/sitemap.xml` returns 404.
- `/sitemap_index.xml` returns 404.
- No XML sitemap was found.
- Indexed URLs are still discoverable through internal links and search-engine crawling.

### 2.4 Service model

The public pages describe a citizen workflow:

1. Citizen registration.
2. Profile completion.
3. Selection of a certificate type.
4. Application submission.
5. Ward secretary / chairman / relevant officer review.
6. Approval or rejection.
7. PDF generation.
8. Office printing and manual signature.
9. Collection by the citizen.
10. Verification by certificate or application number.
11. Application tracking by application ID.

The FAQ makes an important distinction: Prottoyon is an **online management system**, not the direct issuer of the certificate. The legal authority remains the local office. That distinction is useful for Repdoc’s own positioning and should be reflected accurately, without implying Repdoc performs the same government process.

### 2.5 Coverage claims

The homepage title and service pages reference a very large list of certificates, reported as 50+ categories. Observed certificate and attestation categories include:

- নাগরিকত্ব / নাগরিক সনদ
- ওয়ারিশ / উত্তরাধিকার
- চারিত্রিক
- স্থায়ী বাসিন্দা
- পারিবারিক
- আয়
- ভূমিহীন
- প্রতিবন্ধী
- মৃত্যু
- অবিবাহিত / বিবাহিত
- ট্রেড লাইসেন্স
- প্রত্যয়ন / প্রত্যয়নপত্র
- অন্যান্য স্থানীয় সরকারি প্রত্যয়ন

This breadth is a content advantage. It also creates a long tail of thin pages and duplicated metadata.

---

## 3. SEO strengths

### 3.1 Official-domain trust

For queries involving government services, users and search systems may treat a `.gov.bd` result as more authoritative. This is the competitor’s strongest moat.

### 3.2 Exact service vocabulary

The title and body copy use the same words citizens search: সনদ, প্রত্যয়ন, ওয়ারিশ, নাগরিকত্ব, চারিত্রিক, যাচাই. The vocabulary match is strong even when the markup is weak.

### 3.3 Indexed service pages

Despite no sitemap, key pages are indexed, including:

- `/faq`
- `/terms`
- `/verifyCertificate`
- `/searchPage`
- `/union/covered`
- `/municipality/covered`
- `/register/citizen`
- notices

### 3.4 Complete workflow

The competitor satisfies transactional intent after the click: apply, track, verify, and collect. Repdoc must not pretend to offer the same official transaction. It can only support drafting, preparation and explanation.

### 3.5 Brand and domain match

The domain itself contains the service name, and the brand is repeated in titles. This helps relevance for branded searches such as “prottoyon online”.

---

## 4. SEO weaknesses

| Area | Observation | Consequence |
|---|---|---|
| Homepage content | iframe wrapper, ~3.7 KB | Almost no indexable text or internal context |
| Homepage headings | No H1/H2 | Weak topical structure |
| Meta description | Missing on homepage; duplicated on subpages | Poor snippet differentiation |
| Canonical | Missing | Domain/parameter variants are not consolidated |
| Social metadata | Missing | Weak sharing previews |
| Structured data | Missing | No Organization, FAQ, Service, Breadcrumb or Article markup |
| XML sitemap | 404 | Slower discovery of long-tail pages |
| Heading structure | Subpages have zero H1/H2 | Poor semantic hierarchy |
| Content depth | Procedural pages are functional, not explanatory | Thin answers to “কী লাগে”, “কোথায়”, “কত দিন” queries |
| URL/parameter hygiene | Public verification and search pages can produce parameter URLs | Duplicate or private-result indexing risk |
| Iframe dependence | Main app lives off-domain in an iframe | Link equity and content stay split |
| Language coverage | English route exists, but page-level metadata is not optimised | Weak bilingual discovery |

### 4.1 Important nuance

These weaknesses do **not** mean the competitor is easy to outrank for every query. Official trust can outweigh cleaner markup. Repdoc should compete where the query is informational, checklist-driven, or draft-preparation-driven, not where the user explicitly needs an official government transaction.

---

## 5. Index footprint and SERP observations

Indexed pages observed through public search results included FAQ, terms, verification, search, citizen registration, covered unions, covered municipalities, and notices. This shows that the domain has enough authority and internal linking to get functional pages indexed without a sitemap.

Spot checks for three high-intent Bangla queries showed a mixed SERP:

- **নাগরিক সনদ** — results included official/library-style pages and general information, not a single dominant service page.
- **ওয়ারিশ সনদ** — news-style results appeared alongside service pages.
- **চারিত্রিক সনদ** — a private form/template page appeared, showing that non-government pages can compete.

These are observations, not rank tracking. No reliable position, volume, CTR or difficulty data was available. Other domains that appeared in these SERPs were not analysed as competitors; the brief limits primary competitor analysis to `prottoyon.gov.bd`.

---

## 6. Comparison with Repdoc

| Dimension | prottoyon.gov.bd | Repdoc |
|---|---|---|
| Domain trust | Government domain | Independent domain |
| Official issuance | Yes, through local authority workflow | No |
| Public content | Thin wrapper + functional pages | Growing guide layer + tool pages |
| Metadata | Weak/inconsistent | Centralised helpers, canonical, OG, schema |
| Sitemap | 404 | Dynamic XML, 228 URLs |
| Headings | Missing on key pages | One H1 per page, structured H2s |
| Structured data | None observed | Organization, WebSite, Article, FAQ, Breadcrumb, ItemList |
| Certificate breadth | 50+ types | 23 generator types + 12 guides |
| Verification | Certificate/application number | Blank page indexable; results noindex |
| Application tracking | Yes | Not offered as an official service |
| Location coverage | Union/municipality/city corporation directories | No doorway pages |
| Language | Bangla + English route | Bangla-first with English/Banglish aliases |
| Best angle | Official transaction | Clarity, checklists, drafting, verification of Repdoc documents |

---

## 7. What Repdoc should copy — and what it must not

### Copy

- Exact citizen vocabulary.
- Clear certificate-type naming.
- A visible distinction between the platform and the issuing authority.
- Verification as a first-class public utility.
- Simple, task-focused page structure.

### Do not copy

- Keyword-stuffed titles.
- Missing headings and descriptions.
- iframe-only content.
- Thin pages for every certificate variant.
- Government visual identity, seals, or implied official status.
- Location pages with no unique local value.
- Any claim that Repdoc can issue, approve, or track an official government certificate.

---

## 8. Strategic opportunities

1. **Own the “before you apply” query.** Most competitor pages explain the transaction, not the preparation. Repdoc’s guides answer “কী লাগে”, “কোথায়”, “কত দিন”, “কীভাবে যাচাই”.
2. **Own Bangla long-tail variants.** সনদপত্র, প্রত্যয়নপত্র, ফরম, ডাউনলোড, নিয়ম, কাগজপত্র.
3. **Own transliterated search.** Nagorik sonod, warish sonod, charitrik sonod, jatiyota sonod, sonod verify.
4. **Own verification clarity.** Explain what a QR code proves, what it does not prove, and how to verify a Repdoc document versus an official one.
5. **Own draft preparation.** Position the generator as a formatting and print tool, not an issuing authority.
6. **Use the competitor’s weakness against it carefully.** Do not attack the government service. Explain the process better and link users to the right authority.

---

## 9. Threats

- A future competitor page could add proper headings, metadata and a sitemap, closing the technical gap.
- Google may prefer the official domain for transactional queries regardless of content quality.
- Users may assume any “সনদ” site is official; Repdoc must prevent that confusion.
- Repdoc cannot satisfy “application status” intent without a real feature.
- Certificate rules, fees and office names vary by location and change over time; stale guides could damage trust.

---

## 10. Bottom line

`prottoyon.gov.bd` is a **trust competitor**, not an SEO-content competitor. It has the official workflow and the domain authority, but weak public publishing.

Repdoc should not try to be a replacement government portal. It should be the best independent, Bangla-first answer to “what is this certificate, what do I need, how do I prepare, and how do I verify it?” That is a defensible position that complements the official process instead of pretending to be it.
