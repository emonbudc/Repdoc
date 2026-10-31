# Location Keyword Research — Repdoc

**Prepared:** 1 November 2026  
**Dataset:** 7 location-modified keywords from Google Suggest  
**Related files:** `repdoc_keyword_database.csv`, `COMPLETE_KEYWORD_MAPPING.md`

---

## 1. What was found

Only a small number of location-modified suggestions appeared across 79 seeds:

| Keyword | Topic | Location |
|---|---|---|
| নাগরিক সনদ ফরম ঢাকা উত্তর সিটি কর্পোরেশন | নাগরিক সনদ | Dhaka North City Corporation |
| সনদ যাচাই ফরম রাজশাহী শিক্ষা বোর্ড | Verification | Rajshahi Education Board |
| ট্রেড লাইসেন্স আবেদন চট্টগ্রাম | Trade licence | Chattogram |
| উত্তরাধিকার সনদ খাগড়াছড়ি | ওয়ারিশ সনদ | Khagrachhari |
| স্থায়ী বাসিন্দা সনদ আবেদন খাগড়াছড়ি | স্থায়ী বাসিন্দা | Khagrachhari |
| স্থায়ী বাসিন্দা সনদ খাগড়াছড়ি | স্থায়ী বাসিন্দা | Khagrachhari |
| স্থায়ী বাসিন্দা সনদ রাঙ্গামাটি | স্থায়ী বাসিন্দা | Rangamati |

This is not enough evidence to justify a large location-page programme.

---

## 2. Why location pages are tempting — and dangerous

A common SEO mistake is to generate:

- “নাগরিক সনদ ঢাকা”
- “নাগরিক সনদ চট্টগ্রাম”
- “ওয়ারিশ সনদ খাগড়াছড়ি”
- one page for every district, upazila and union

This creates doorway pages: near-identical content with a swapped place name. It adds little value, can dilute crawl budget, and may be treated as spam.

For Repdoc, it would be especially risky because the actual authority, form, fee and process vary by local office. A generic page with a city name would be both thin and potentially misleading.

---

## 3. What the location queries actually mean

### 3.1 Authority-specific intent

“ঢাকা উত্তর সিটি কর্পোরেশন” and “রাজশাহী শিক্ষা বোর্ড” are not just city modifiers. They identify a specific issuing authority. A useful page would need:

- the correct authority name
- the services that authority actually provides
- its current process and links
- a verified contact/office path
- a clear distinction between the authority and Repdoc

Repdoc does not currently maintain that level of verified local detail.

### 3.2 Local office uncertainty

“খাগড়াছড়ি” and “রাঙ্গামাটি” suggestions for স্থায়ী বাসিন্দা likely reflect users looking for the local office or a local form. A national guide can answer the general process, but it cannot guarantee the local office’s current requirements.

### 3.3 Commercial intent

“ট্রেড লাইসেন্স আবেদন চট্টগ্রাম” may come from a business owner looking for a local licence service. Repdoc can explain the process and offer a draft, but should not imply it is a Chattogram licensing office or agent.

---

## 4. Recommended approach

### 4.1 Keep one strong national guide per topic

Do not create separate pages for each location. Keep:

- `/sonod/nagorik-sonod/`
- `/sonod/warish-sonod/`
- `/sonod/sthayi-basinda-sonod/`
- `/sonod/trade-license/`
- `/sonod/sonod-verification/`

Each guide already says the authority varies by area and tells the reader to confirm with the local office.

### 4.2 Add a short “authority varies” block

Where useful, add a consistent section:

> আপনার ঠিকানার ওপর নির্ভর করে ইউনিয়ন পরিষদ, পৌরসভা, সিটি কর্পোরেশন বা জেলা প্রশাসন প্রযোজ্য হতে পারে। আবেদনের আগে স্থানীয় অফিসের সর্বশেষ তালিকা ও ফি যাচাই করুন।

This satisfies the location intent without creating a thin page.

### 4.3 Use the important-links directory

Repdoc already has `/importantlinks/`, a curated directory of government and service links. If local authority links are added there, they should be:

- verified
- categorised
- labelled with the authority name
- checked periodically

That is more useful than a fake city landing page.

### 4.4 Only build authority pages when there is unique value

A dedicated page could be justified later if Repdoc maintains:

- a verified list of services for that authority
- current forms or official links
- local office contact details
- a genuinely different process
- regular updates

Without that, do not publish.

---

## 5. Location page quality checklist

If a location page is ever considered, it must pass all of these:

- [ ] The page names a real authority, not just a city.
- [ ] The page explains which services that authority actually provides.
- [ ] The page has information not available on the national guide.
- [ ] The page links to the official authority source.
- [ ] The page does not imply Repdoc is the authority or an agent.
- [ ] The page has a named update/review process.
- [ ] The page is not one of hundreds of near-identical pages.
- [ ] The page has a clear user task beyond “find a certificate in X”.

If any box is unchecked, do not publish the page.

---

## 6. Internal-link strategy without doorway pages

- Link location mentions in guides to `/importantlinks/`, not to fake local pages.
- Use the guide hub to explain that the office depends on the address.
- Use the verification guide to explain that official verification is authority-specific.
- If an authority directory is added, link only the relevant authority, not every guide.

---

## 7. Risks

| Risk | Why it matters |
|---|---|
| Doorway-page classification | Mass location pages with duplicate content |
| Wrong authority | A user follows the wrong office and wastes time |
| Stale local details | Office names, fees and forms change |
| Implied agency | Users may think Repdoc is a local licence agent |
| Crawl dilution | Hundreds of thin pages compete with strong guides |

---

## 8. Priority

**Current priority: low.** The dataset shows only 7 location-modified suggestions. The effort is better spent on:

1. strengthening the 12 national guides
2. improving document checklists
3. adding verified official links to `/importantlinks/`
4. measuring real query data

Revisit location pages only if Search Console shows substantial location-modified impressions and Repdoc can maintain verified local information.

---

## 9. Bottom line

Location intent exists, but it is thin and authority-specific. The correct response is **one strong guide plus a clear “check your local office” boundary**, not a network of city pages. Repdoc should earn trust by being accurate, not by pretending to have a local office in every district.
