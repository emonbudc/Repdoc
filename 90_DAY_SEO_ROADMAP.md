# 90-Day SEO Roadmap — Repdoc

**Prepared:** 1 November 2026  
**Start:** After this implementation is reviewed and deployed  
**Canonical:** `https://repdoc.rf.gd/`  
**Primary competitor:** `https://prottoyon.gov.bd/`

---

## 1. Objective

Turn the new public certificate-guide layer into measurable organic discovery, without pretending Repdoc is a government portal or publishing thin location pages.

### 1.1 Success definition

At the end of 90 days:

- The new pages are indexed and discovered through the sitemap.
- Search Console or server logs show which certificate queries actually reach Repdoc.
- The guides send qualified users to the generator or verification page.
- Content decisions are based on evidence, not keyword counts alone.
- Verification privacy remains intact.

### 1.2 No fabricated targets

This roadmap does **not** promise a traffic, ranking or revenue number. No reliable volume, difficulty or baseline data was available. Targets are process and learning targets.

---

## 2. Phase 0 — Before deployment (Days 0–2)

| Task | Owner | Output |
|---|---|---|
| Review working tree and diff | Developer | Clean, intentional change set |
| Confirm no secrets/temp files | Developer | Safe deployment |
| Verify `mod_rewrite` and `.htaccess` on staging | Developer | Clean URLs working |
| Test all 12 guides and `/verify/` | Developer | HTTP/schema/canonical sign-off |
| Test a real Repdoc certificate lookup | Developer | Privacy and accuracy check |
| Confirm `noindex` on lookup results | Developer | No private result indexing |
| Submit sitemap | SEO/developer | 228 URLs discovered |

**Stop rule:** Do not deploy if any guide returns a 500, if lookup results are indexable, or if the sitemap is invalid.

---

## 3. Days 1–14 — Stabilise and measure

### Week 1

| Task | Detail |
|---|---|
| Deploy | New `sonod/` pages, registry, `.htaccess`, `robots.txt`, `llms.txt` |
| Crawl check | `curl`/browser checks for all 14 new URLs |
| Sitemap check | 228 URLs, 21 images, valid XML |
| Canonical check | Self-canonical on every guide; verify result canonical to `/verify/` |
| Robots check | `/sonod/` and `/verify/` allowed; lookup results noindex |
| Mobile check | Hub cards, guide tables, verify form, nav overflow |
| Analytics | Confirm Search Console access or set up server-log reporting |

### Week 2

| Task | Detail |
|---|---|
| Indexing request | Request indexing for the hub and top 5 guides, if GSC is available |
| Query baseline | Record available impressions/clicks or mark “no data” |
| Internal-link audit | Hub → guides → tool/verify; certificate page → all guides |
| Broken-link check | All new internal links |
| Content QA | Read all 12 guides for accuracy, tone and non-government boundary |
| Fix list | Prioritise only real defects, not cosmetic rewrites |

**Deliverable:** a one-page baseline with indexing status, sitemap status and any available query data.

---

## 4. Days 15–30 — Learn from real queries

### 4.1 Data review

- Export Search Console queries, if available.
- Otherwise, use server logs, referrers and on-site search.
- Group queries by certificate topic and intent.
- Identify:
  - pages with impressions but low CTR
  - queries with no matching page
  - queries that are actually official-transaction intent
  - English/Banglish query volume

### 4.2 Content actions

| Signal | Action |
|---|---|
| High impressions, low CTR | Rewrite title/description for clarity |
| Query asks a question the page does not answer | Add a verified FAQ section |
| Query is a different certificate | Consider a new guide only if Repdoc supports it |
| Query is official status/tracking | Do not fake; explain the boundary |
| Query is location-specific | Do not create a doorway page |
| Query is English/Banglish | Improve alias line; consider an English summary |

### 4.3 Technical actions

- Fix any indexing errors.
- Check for duplicate canonical or sitemap issues.
- Confirm the verification page remains correctly indexed/noindexed.
- Review Core Web Vitals for the new pages, if field data exists.

**Deliverable:** a query-to-page gap list and a short content change log.

---

## 5. Days 31–60 — Deepen the winners

### 5.1 Double down on the top 3 clusters

Likely candidates, subject to data:

- নাগরিক সনদ
- ওয়ারিশ সনদ
- চারিত্রিক সনদ
- ট্রেড লাইসেন্স
- সনদ যাচাই

For each:

- Add a clearer document table.
- Add a “common mistakes” section.
- Add a “what to ask the office” checklist.
- Add a short “official vs Repdoc” comparison.
- Improve the internal links from related guides.

### 5.2 Build only evidence-led pages

Add a new guide only if:

- GSC shows repeated impressions for a certificate Repdoc supports, and
- the current hub does not answer the intent, and
- the page can be maintained accurately.

Candidate topics:

- নতুন ভোটার
- ভোটার তথ্য
- অনাপত্তি
- অবিবাহিত / বিবাহিত
- একই নাম
- আনুমানিক বয়স
- সনাতন
- প্রত্যয়নপত্র (অন্যান্য)

Do not publish all of them by default.

### 5.3 English/Banglish decision

At the end of this phase:

- If Latin-script impressions are material and informational, add English summaries to the top 3 guides.
- If not, keep the alias lines and defer full English pages.
- If English pages are added, implement `hreflang` and self-canonicals.

### 5.4 Link acquisition

- Share guides in relevant operator/community groups without spam.
- Link from social posts to the specific guide, not just the homepage.
- Ask partners to link to the guide, not with exact-match anchors.
- Do not buy links or mass-submit to directories.

**Deliverable:** two to four improved guides, a decision on English pages, and a small earned-link log.

---

## 6. Days 61–90 — Convert and consolidate

### 6.1 Conversion

- Add a clear CTA from each guide to the relevant tool.
- Check whether users understand the difference between a draft and an official certificate.
- Improve the certificate landing if guide traffic does not convert.
- Consider a “start with a template” block on high-traffic guides.

### 6.2 Consolidation

- Merge or retire pages that earn no impressions and answer no real question.
- Do not keep publishing just to increase page count.
- Refresh the 2–3 guides with declining impressions.
- Update dates only when content is genuinely reviewed.

### 6.3 Trust

- Re-check all official links in `/importantlinks/`.
- Re-check the non-government disclaimer.
- Re-check verification privacy.
- Re-check that no guide implies government issuance.

### 6.4 Quarter-end review

| Question | Evidence |
|---|---|
| Which guides are indexed? | GSC/index status |
| Which queries reach them? | GSC/server logs |
| Which guides send tool clicks? | Analytics/internal events |
| Which questions remain unanswered? | Query gap list |
| Which pages should be improved? | CTR/engagement |
| Which pages should be retired? | No impressions/use |
| Did verification privacy hold? | Index/noindex audit |
| Did any location/doorway temptation appear? | Content inventory |

**Deliverable:** quarterly SEO review and next-quarter plan.

---

## 7. Weekly operating rhythm

| Day | Task |
|---|---|
| Monday | Check indexing, errors and sitemap |
| Tuesday | Review query data |
| Wednesday | Improve one page or fix one issue |
| Thursday | Check internal links and conversion paths |
| Friday | Log changes and update the roadmap |

Do not rewrite pages daily. Search data needs time to accumulate.

---

## 8. KPI framework

### 8.1 Process KPIs

- New pages deployed: 14.
- Pages indexed: track, do not assume.
- Sitemap URLs: 228.
- Sitemap images: 21.
- Internal links from hub to guides: 12.
- Guides linked from certificate landing: 12.
- Lookup-result pages with `noindex`: 100%.

### 8.2 Learning KPIs

- Number of queries mapped to a page.
- Number of queries with no matching page.
- Number of queries deliberately declined (official status, fees, legal edge cases).
- Number of guides with at least one impression.
- Number of guides with at least one tool click.

### 8.3 Outcome KPIs

- Organic impressions.
- Organic clicks.
- CTR by guide.
- Tool clicks from guides.
- Verification page usage.
- Branded searches.
- Account registrations, if trackable.

No numeric target is set without a baseline.

---

## 9. Roles

| Role | Responsibility |
|---|---|
| Developer | Deployment, routing, schema, privacy, sitemap |
| Content lead | Accuracy, tone, updates, source checks |
| SEO lead | Query analysis, mapping, internal links, measurement |
| Product | Decision on new guides, English pages, tool features |
| Reviewer | Non-government boundary, legal/eligibility caution |

---

## 10. Dependencies

- Production/staging access with `.htaccess` support.
- Google Search Console access, or a server-log alternative.
- A real Repdoc certificate number for verification testing.
- A content reviewer for Bangla accuracy.
- A maintenance owner for official links and fees.
- A decision on whether Repdoc will ever offer a real draft-status feature.

---

## 11. Stop rules

Stop or change course if:

- The verification page starts indexing lookup results.
- A guide starts claiming government issuance or official verification.
- A fee or timeline number cannot be maintained.
- Location pages are proposed without unique verified value.
- English pages are proposed only because the keyword count looks large.
- Publishing speed starts to outrun accuracy review.
- The sitemap count diverges from the registry without explanation.

---

## 12. 90-day checklist

### Days 0–14

- [ ] Deploy
- [ ] Verify clean URLs
- [ ] Verify verification privacy
- [ ] Submit sitemap
- [ ] Record baseline
- [ ] Fix defects

### Days 15–30

- [ ] Review queries
- [ ] Map gaps
- [ ] Improve titles/CTAs where justified
- [ ] Confirm no official-status overreach

### Days 31–60

- [ ] Deepen top clusters
- [ ] Decide on English summaries
- [ ] Add only evidence-led guides
- [ ] Earn a few relevant links

### Days 61–90

- [ ] Improve conversion paths
- [ ] Consolidate weak pages
- [ ] Re-check trust and official links
- [ ] Write quarterly review
- [ ] Plan next quarter

---

## 13. Bottom line

The first 90 days are about **validation and learning**, not volume. Repdoc now has a credible content layer. The roadmap protects that credibility while finding out which certificate queries actually matter.

If the data is thin, do not invent demand. If the data is strong, deepen the pages that earned it.
