# Page: Search console (index.php) — deltas over MASTER

- Workflow order: breadcrumb → registry meta strip → search panel (primary) →
  results table → pagination. Search is the visual anchor; nothing above it
  competes (no hero, no big stat numerals).
- "Record list" nav browses the whole database: clears filters, sets 100
  per page, uses the empty-query fast path (SQL COUNT + LIMIT/OFFSET).
  Manual search / reset leaves browse mode; pagination keeps it.
- Match mode segmented control: partial (default) vs exact; persisted to URL.
- Advanced filters: name, BRN, father, mother, DOB, gender, ward, status —
  exactly the fields `SearchEngine::searchAdvanced` supports. No new fields.
- BRN validation: accept Bangla + English digits, strip spaces; must be
  exactly 17 digits when filled (error otherwise). DOB: accept DD/MM/YYYY,
  DD-MM-YYYY, YYYY-MM-DD; error summary lists each problem with field link.
- Table columns (fixed): select, BRN, name+parents, DOB+age, gender,
  ward/address, status, actions. Sortable: BRN, name, DOB, ward.
- Detail modal tabs: personal / parents / registration / audit / certificate.
  Certificate tab prints the bordered sheet only.
- Verify modal keeps existing captcha flow (api/captcha.php) unchanged;
  wording stays factual: "BDRIS captcha lookup" as a function, not identity.
