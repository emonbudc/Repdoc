# Keyword Gap Analysis — Repdoc vs prottoyon.gov.bd

**Prepared:** 1 November 2026  
**Primary competitor:** `https://prottoyon.gov.bd/`  
**Repdoc canonical:** `https://repdoc.rf.gd/`  
**Keyword database:** `repdoc_keyword_database.csv`

---

## 1. Summary

The competitor’s advantage is breadth of official service coverage and domain trust. Repdoc’s advantage is clarity, page structure, metadata, sitemap coverage, and the ability to explain a certificate before someone applies.

This pass closed the biggest gap: Repdoc now has a public certificate-guide layer. It still does not have, and should not fake:

- official certificate issuance
- official application tracking
- government fee payment
- authority-issued verification
- a complete directory of every local office

The correct strategy is to win the **preparation and verification-clarity** layer, not to imitate the official transaction layer.

---

## 2. Coverage comparison

| Need / query theme | prottoyon.gov.bd | Repdoc before this pass | Repdoc now |
|---|---|---|---|
| নাগরিক সনদ explanation | Functional pages, weak headings | None | `/sonod/nagorik-sonod/` |
| ওয়ারিশ সনদ | Functional pages | None | `/sonod/warish-sonod/` |
| চারিত্রিক সনদ | Functional pages | None | `/sonod/charitrik-sonod/` |
| জাতীয়তা সনদ | Service coverage | None | `/sonod/jatiyota-sonod/` |
| স্থায়ী বাসিন্দা | Service coverage | None | `/sonod/sthayi-basinda-sonod/` |
| পারিবারিক সনদ | Service coverage | None | `/sonod/paribarik-sonod/` |
| আয় সনদ | Service coverage | None | `/sonod/income-sonod/` |
| ভূমিহীন সনদ | Service coverage | None | `/sonod/bhumihin-sonod/` |
| প্রতিবন্ধী সনদ | Service coverage | None | `/sonod/protibondhi-sonod/` |
| মৃত্যু প্রত্যয়ন | Service coverage | None | `/sonod/mrityu-pratyayon/` |
| ট্রেড লাইসেন্স | Service coverage | None | `/sonod/trade-license/` |
| সনদ যাচাই explanation | Verification page | None | `/sonod/sonod-verification/` + `/verify/` |
| Blank public verify page indexable | Yes | No (`noindex`) | Yes |
| Official application | Yes | No | No, intentionally |
| Application tracking | Yes | No | No |
| Government fee payment | Yes | No | No |
| 50+ certificate categories | Yes | 23 generator types | 23 types + 12 guides |
| Local authority directories | Union/municipality pages | Important links only | Important links only |
| Public FAQ content | Yes, no H1/H2 | No dedicated FAQ | FAQ on every guide |
| XML sitemap | 404 | 214 URLs | 228 URLs |
| Canonical tags | Missing | Present | Present |
| Open Graph | Missing | Present | Present |
| JSON-LD | Missing | Present | Extended |
| English route | Yes | Partial site language | Bangla guides + English aliases |

---

## 3. Gaps that matter

### 3.1 Public certificate knowledge breadth

**Competitor:** 50+ certificate categories across its service workflow.  
**Repdoc:** 23 generator types; 12 public guides covering 11 certificate themes plus verification.  
**Gap:** Repdoc has no public guide for several document types in its own generator, such as:

- নতুন ভোটার
- ভোটার তথ্য
- অনাপত্তি
- রাস্তা খনন
- নদী ভাঙন
- অনুমতি
- অবিবাহিত / বিবাহিত
- একই নাম
- পুনর্বিবাহ
- আনুমানিক বয়স
- সনাতন
- প্রত্যয়ন (প্রত্যায়নকারী)
- প্রত্যয়নপত্র (অন্যান্য)

**Recommendation:** Do not create 13 thin guides immediately. Add guides only where Google Suggest or Search Console shows real query demand. The existing generator pages already serve users who need those formats.

### 3.2 Application status

**Competitor:** Tracks by application ID.  
**Repdoc:** No official application workflow.  
**Gap:** Search terms such as আবেদন স্ট্যাটাস, সনদ ট্র্যাকিং and aavedan status are real.  
**Recommendation:** Do not pretend to check official applications. If Repdoc later adds its own document-draft history, it can publish a clearly labelled “Repdoc draft status” page. Until then, the verification guide should explain the boundary.

### 3.3 Fees and timelines

**Competitor:** Has a fee/payment workflow, but public fee detail is not consistently presented.  
**Repdoc:** No fee numbers.  
**Gap:** High-frequency queries like “ট্রেড লাইসেন্স করতে কত টাকা লাগে”.  
**Recommendation:** Explain variability and direct users to the authority. A wrong static number is worse than no number.

### 3.4 Local authority coverage

**Competitor:** Has union, municipality and city corporation coverage pages.  
**Repdoc:** Has an important-links directory, not a structured authority directory.  
**Gap:** Location-modified queries such as “উত্তরাধিকার সনদ খাগড়াছড়ি”.  
**Recommendation:** Do not mass-produce location pages. Add verified authority links to `/importantlinks/` only if they can be maintained.

### 3.5 English-language depth

**Competitor:** Has an English route, though metadata is weak.  
**Repdoc:** Bangla-first; new guides include English/Banglish aliases but are not full English pages.  
**Gap:** 220 English/Banglish keywords.  
**Recommendation:** Keep aliases. Add English summaries to the top 3 guides only if Search Console shows Latin-script impressions.

### 3.6 Official trust

**Competitor:** `.gov.bd` domain and official workflow.  
**Repdoc:** Independent domain.  
**Gap:** Cannot be closed by SEO.  
**Recommendation:** Make the independent status explicit, accurate and consistent. Do not use government seals, official colour schemes, or “সরকারি” claims.

### 3.7 Content freshness

**Competitor:** Functional pages change with the service, but public content is not strongly dated.  
**Repdoc:** New guides display an update date.  
**Opportunity:** Build a real review cadence and update dates only when the content is actually reviewed.

---

## 4. Repdoc advantages to exploit

### 4.1 Better page structure

Every new guide has one H1, multiple H2s, a visible FAQ, breadcrumbs and schema. The competitor’s key pages have no H1/H2.

### 4.2 Better crawlability

Repdoc has a valid sitemap with 228 URLs, canonical tags, Open Graph, and image sitemap entries. The competitor’s sitemap returns 404.

### 4.3 Better preparation content

Repdoc can answer the “কি লাগে”, “কিভাবে”, “কোথায়” and “যাচাই” questions in plain Bangla without the pressure of an official transaction.

### 4.4 Verification clarity

Repdoc now has two complementary pages:

- `/sonod/sonod-verification/` explains the concept.
- `/verify/` performs a Repdoc document lookup.

The blank verification page is indexable; lookup results are noindex. This is a clean privacy/search balance.

### 4.5 Draft-positioning

Repdoc does not need to pretend to issue certificates. Position the generator as a formatting and print tool. That is a legitimate, defensible job.

---

## 5. Keyword coverage by page

| Page | Primary keyword theme | Supporting intent |
|---|---|---|
| `/sonod/` | সনদ, প্রত্যয়ন, সনদ আবেদন | Hub, comparison, process |
| `/sonod/nagorik-sonod/` | নাগরিক সনদ, নাগরিকত্ব | Documents, authority |
| `/sonod/warish-sonod/` | ওয়ারিশ, উত্তরাধিকার | Heir list, documents |
| `/sonod/charitrik-sonod/` | চারিত্রিক সনদ | Form, documents |
| `/sonod/jatiyota-sonod/` | জাতীয়তা সনদ | Difference from citizenship |
| `/sonod/sthayi-basinda-sonod/` | স্থায়ী বাসিন্দা | Address proof |
| `/sonod/paribarik-sonod/` | পারিবারিক সনদ | Member list |
| `/sonod/income-sonod/` | আয় সনদ | Income proof |
| `/sonod/bhumihin-sonod/` | ভূমিহীন সনদ | Eligibility |
| `/sonod/protibondhi-sonod/` | প্রতিবন্ধী সনদ | Medical verification |
| `/sonod/mrityu-pratyayon/` | মৃত্যু সনদ, প্রত্যয়ন | Registration vs attestation |
| `/sonod/trade-license/` | ট্রেড লাইসেন্স | Renewal, documents |
| `/sonod/sonod-verification/` | সনদ যাচাই, QR | Official vs Repdoc |
| `/verify/` | Repdoc certificate lookup | Certificate number, QR |
| `/genaretor/certificate/` | 23 certificate templates | Tool access |
| `/showcase/` | Document templates | Template discovery |

---

## 6. Priority gap matrix

| Gap | Impact | Effort | Priority |
|---|---|---|---|
| Production clean-URL validation | High | Low | Now |
| Search Console query data | High | Low/medium | Now |
| Full guides for high-demand generator types | Medium | Medium | Next |
| English summaries for top 3 guides | Medium | Medium | Later |
| Authority-specific directory | Medium | High (maintenance) | Only with verified data |
| Official application tracking | High user demand, but not Repdoc’s job | Very high | Do not fake |
| Fee/timeline database | High demand | High maintenance | Not recommended |
| Location doorway pages | Low value, high risk | High | Do not build |
| More template pages | Low SEO value without content | Medium | Not a priority |

---

## 7. What not to do

- Do not create a page for every certificate name in the competitor’s title.
- Do not create a page for every district.
- Do not copy the competitor’s government branding.
- Do not claim QR verification of official certificates.
- Do not publish fee tables without a maintenance owner.
- Do not add “application status” unless a real Repdoc feature exists.
- Do not translate all guides into English just to chase 220 suggestions.

---

## 8. Bottom line

Repdoc has closed the most important gap: it now has a public, structured, Bangla-first answer layer for the certificate queries it can honestly serve. The remaining gaps are mostly **transactional or authority-specific**, and they should not be closed with imitation.

The next competitive edge is evidence: validate the clean URLs in production, get real query data, and deepen the guides that earn impressions. Breadth can follow demand, not precede it.
