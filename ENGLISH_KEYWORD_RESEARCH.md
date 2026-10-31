# English and Banglish Keyword Research — Repdoc

**Prepared:** 1 November 2026  
**Dataset:** 220 unique English/Banglish keywords from Google Suggest  
**Related files:** `COMPLETE_KEYWORD_RESEARCH.md`, `repdoc_keyword_database.csv`

---

## 1. Summary

English-script demand is meaningful but secondary to Bangla. It appears in three forms:

1. **Full English service queries** — `citizenship certificate bangladesh`, `character certificate bangladesh`, `trade license bangladesh`.
2. **Banglish transliteration** — `nagorik sonod`, `warish sonod`, `charitrik sonod`, `jatiyota sonod`, `sonod verify`.
3. **Document/template queries** — `certificate format bangladesh`, `warish sonod application pdf`, `character certificate bangla format doc`.

Repdoc is currently Bangla-first. The new guides include a visible English/Banglish “also searched as” line, but they are not full English pages. That is a deliberate first step, not a claim of complete English coverage.

### 1.1 English/Banglish intent distribution

| Intent | Keywords |
|---|---:|
| Informational | 128 |
| Download/form | 47 |
| Apply/renew | 30 |
| Verify/check | 13 |
| Fee | 2 |

---

## 2. Cluster distribution

| Cluster | Keywords | Notes |
|---|---:|---|
| Generic sonod / certificate | 76 | Mixed English and Banglish |
| নাগরিক / citizenship | 28 | Strong “certificate bangladesh” pattern |
| চারিত্রিক / character | 21 | Format, sample and PDF modifiers |
| মৃত্যু / death | 16 | Registration vs attestation confusion |
| Verification | 20 | Verify, check, tracking |
| জাতীয়তা / nationality | 14 | Dual citizenship and certificate variants |
| ওয়ারিশ / heir | 13 | Legal heir, cost, online |
| Trade licence | 10 | Application and renewal |
| Income | 10 | Certificate and application |
| প্রতিবন্ধী / disability | 8 | Certificate and Bangladesh modifiers |
| পারিবারিক / family | 4 | Smaller cluster |

---

## 3. High-value English/Banglish examples

### 3.1 Citizenship and national identity

- citizenship certificate bangladesh
- citizenship certificate bangla
- citizenship certificate form bangladesh
- citizenship certificate online application bangladesh
- dual citizenship certificate bangladesh
- national certificate bangladesh
- nagorik sonod
- nagorik sonod application
- nagorik sonod online

**Mapped page:** `/sonod/nagorik-sonod/`

**Caution:** “dual citizenship” is a distinct legal topic. The current guide explains the general citizenship certificate and must not imply it covers dual-citizenship applications.

### 3.2 Character certificate

- character certificate bangladesh
- character certificate bangla format
- character certificate bangla format doc
- character certificate format bangladesh
- character certificate format bangladesh pdf
- character certificate meaning in bengali
- character certificate online bangladesh
- charitrik sonod
- charitrik sonod format

**Mapped page:** `/sonod/charitrik-sonod/`

### 3.3 Heir certificate

- heir certificate bangladesh
- legal heir certificate bangladesh
- legal heir certificate cost
- how to get heir certificate
- how to get heir certificate online
- union parishad warish certificate
- warish sonod
- warish sonod application
- warish sonod application form
- warish sonod application pdf

**Mapped page:** `/sonod/warish-sonod/`

**Caution:** “cost” queries cannot be answered with a fixed number. The guide explains fee variability.

### 3.4 Trade licence

- trade license bangladesh
- trade license online application bangladesh
- trade license renewal bangladesh
- trade license fee bangladesh
- trade licence bangladesh
- trade license bangla

**Mapped page:** `/sonod/trade-license/`

### 3.5 Verification

- certificate verification bangladesh
- certificate verify bangladesh
- online certificate verification bangladesh
- sonod verify
- sonod verification
- verify sonod online

**Mapped pages:** `/sonod/sonod-verification/` and `/verify/`

**Caution:** English “certificate verification bangladesh” may attract users looking for official government verification. Repdoc must state clearly that its verify page checks Repdoc-created documents, not government certificates.

### 3.6 Death, income, disability

- death certificate bangladesh
- death certificate online bangladesh
- death certificate application bangladesh
- income certificate bangladesh
- income certificate application bangladesh
- disability certificate bangladesh
- disability certificate online bangladesh

**Mapped pages:** `/sonod/mrityu-pratyayon/`, `/sonod/income-sonod/`, `/sonod/protibondhi-sonod/`

---

## 4. Banglish patterns

Banglish queries are usually direct transliterations of the Bangla certificate name, with an English task word:

| Banglish | Likely Bangla source | Page |
|---|---|---|
| nagorik sonod | নাগরিক সনদ | `/sonod/nagorik-sonod/` |
| warish sonod | ওয়ারিশ সনদ | `/sonod/warish-sonod/` |
| charitrik sonod | চারিত্রিক সনদ | `/sonod/charitrik-sonod/` |
| jatiyota sonod | জাতীয়তা সনদ | `/sonod/jatiyota-sonod/` |
| sonod application | সনদ আবেদন | `/sonod/` |
| sonod verify | সনদ যাচাই | `/sonod/sonod-verification/` |
| prottoyon online | প্রত্যয়ন অনলাইন | `/sonod/` |
| bangla sonod | বাংলা সনদ | `/sonod/` |

### 4.1 Implementation note

The 12 new guides display an “Also searched as” line containing the most important transliterations. This gives search engines and readers a clear bilingual signal without creating 12 thin English duplicates.

---

## 5. Should Repdoc build full English pages?

### 5.1 Arguments for

- 220 English/Banglish suggestions already exist.
- Some users prefer English for legal, employment and visa contexts.
- English pages can capture “certificate format bangladesh” and “application pdf” intent.
- An English summary can improve clarity for mixed-language readers.

### 5.2 Arguments against

- The primary audience and competitor are Bangla-first.
- Thin translated duplicates can cannibalise the Bangla pages.
- Local rules and office names are easier to state accurately in Bangla.
- Maintenance doubles for every fee/rule change.

### 5.3 Recommendation

Do **not** translate all 12 guides immediately. Instead:

1. Keep the Bangla guides as canonical.
2. Keep the visible English/Banglish alias line.
3. Add a short English summary block to the top three guides only if Search Console shows English-script impressions: citizenship, character and heir.
4. If full English pages are added later, use `hreflang` between the Bangla and English versions and give each a self-canonical.
5. Do not create English pages for queries Repdoc cannot satisfy, such as official application status or government verification.

---

## 6. English title and description patterns

If English pages are approved, use:

**Title:** `{Certificate Name} in Bangladesh — Documents, Process & Verification | Repdoc`

**Description:** `Learn what a {certificate name} usually requires in Bangladesh, the common application steps, and how Repdoc can help you prepare a draft. Independent toolkit — not a government portal.`

**H1:** `{Certificate Name} in Bangladesh: What It Is and How to Prepare`

Avoid:

- “Apply online” unless a real online application exists.
- “Government approved.”
- “Official certificate.”
- “Get your certificate in 24 hours.”
- “Free government certificate.”

---

## 7. Keyword mapping for English/Banglish

The CSV maps English keywords to the closest Bangla guide. This is intentional: the Bangla page is the canonical asset, and the alias line supplies transliterated relevance.

| English/Banglish cluster | Canonical Bangla page |
|---|---|
| citizenship, nagorik | `/sonod/nagorik-sonod/` |
| heir, warish | `/sonod/warish-sonod/` |
| character, charitrik | `/sonod/charitrik-sonod/` |
| nationality, jatiyota | `/sonod/jatiyota-sonod/` |
| trade license | `/sonod/trade-license/` |
| verification, sonod verify | `/sonod/sonod-verification/` |
| death certificate | `/sonod/mrityu-pratyayon/` |
| income certificate | `/sonod/income-sonod/` |
| disability certificate | `/sonod/protibondhi-sonod/` |
| generic sonod, prottoyon | `/sonod/` |

---

## 8. Risks

- **Intent mismatch:** English “certificate verification bangladesh” often means official verification. The Repdoc page must not overpromise.
- **Legal terminology:** “heir certificate”, “legal heir certificate” and “warish sonod” are related but not always interchangeable in every office. Use cautious language.
- **Dual citizenship:** a distinct legal process; do not fold it into a general citizenship certificate guide without a dedicated, accurate page.
- **Translation quality:** machine-style translation would hurt trust. If English pages are added, they need human review.
- **Cannibalisation:** an English page and a Bangla page targeting the same query can compete with each other without `hreflang` and clear intent separation.

---

## 9. Measurement

Track English/Banglish queries separately in Search Console, if available:

- impressions by script (Bangla vs Latin)
- queries containing `sonod`, `certificate`, `bangladesh`
- click-through rate for guide titles
- clicks from guide pages to the generator
- bounce/engagement on Bangla pages from English-script queries

If Latin-script impressions are negligible after 8–12 weeks, do not build full English pages. If they are material and the queries are informational, add English summaries to the top guides first.

---

## 10. Bottom line

English and Banglish demand is real but should not distort a Bangla-first site. The correct first move is the one already implemented: visible aliases on strong Bangla guides. Full English pages are a later, evidence-led decision, limited to topics Repdoc can answer honestly.
