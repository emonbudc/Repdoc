# Repdoc Prottoyon Module — Implementation Audit

Date: 2026-10-26
Plan: `Plan/plan.MD`
Status: implemented, smoke-tested, test data cleaned up.

## Delivered

- Replaced the old 41-type landscape generator with a 23-type application module.
- Added type picker (with search and recent applications), application form, POST submit handler, user archive, certificate view/reapply, private file handler, public verification, admin list/view/search/revoke, and UDC list/view.
- Kept the separate `genaretor/birth/` tool unchanged; `birth` is not in the new picker.
- Kept all 41 historical certificate templates readable for old `document_logs` rows.
- Kept Repdoc login, wallet, admin, and approved-UDC access.
- Added homepage-style application UI and a separate DU-style A4 portrait certificate.
- No PDF library, Composer dependency, `vendor/`, server-side PDF generation, or download button was added.

## Current Type Registry

23 current keys:

| Key | Bangla title |
|---|---|
| `nagorik` | নাগরিকত্ব প্রত্যয়নপত্র |
| `new-voter` | নতুন ভোটার প্রত্যয়নপত্র |
| `voter` | ভোটার তথ্য প্রত্যয়নপত্র |
| `character` | চারিত্রিক সনদপত্র |
| `vumihin` | ভূমিহীন প্রত্যয়নপত্র |
| `onapotti` | অনাপত্তি সনদপত্র |
| `rastakhonon` | রাস্তা খনন প্রত্যয়নপত্র |
| `nodibanga` | নদী ভাঙন প্রত্যয়নপত্র |
| `trade` | ট্রেড লাইসেন্স প্রত্যয়নপত্র |
| `yearlyincome` | বার্ষিক আয় প্রত্যয়নপত্র |
| `onumoti` | অনুমতি প্রত্যয়নপত্র |
| `family` | পারিবারিক সনদপত্র |
| `warish` | ওয়ারিশ সনদপত্র |
| `death` | মৃত্যু প্রত্যয়নপত্র |
| `obibahito` | অবিবাহিত প্রত্যয়নপত্র |
| `bibahito` | বিবাহিত প্রত্যয়নপত্র |
| `ekoinam` | একই নাম প্রত্যয়নপত্র |
| `punobibaho` | পুনর্বিবাহ প্রত্যয়নপত্র |
| `protibondi` | প্রতিবন্ধী প্রত্যয়নপত্র |
| `prottyon` | প্রত্যয়নপত্র (প্রত্যায়নকারী) |
| `sonaton` | সনাতন প্রত্যয়নপত্র |
| `anumanik-boyosh-protyon` | আনুমানিক বয়স প্রত্যয়নপত্র |
| `protyonpotro-onnanno` | প্রত্যয়নপত্র (অন্যান্য) |

Historical keys: 41 templates in `prottoyon_legacy_types()`.
Three keys (`character`, `death`, `family`) exist in both the current registry and historical template set because old rows used the same `tool_key`. Current labels win in `prottoyon_all_types()`; old rows are detected by `data.v < 2` and render through the legacy template.
Effective aggregate count: 60 unique keys (23 current + 41 historical − 3 shared).

## Data Model

`document_logs` remains the certificate ledger.

New/used fields:

- `tool_key`: `prottoyon_<current-key>`.
- `ref`: new rows use the application ID `PRT-YYMMDD-XXXXXX`; old rows keep their original ref.
- `dob`, `photo`, `signature`, `created_at` unchanged.
- `data`: versioned JSON with `{v:2, type, personal, present, permanent, extra, members, fees, documents, purpose, meta}`.
- `cert_no`: 17-digit new number or legacy 11-digit number.
- `status`: `valid` or `revoked`.
- `office`: JSON office snapshot at issue time.

### Database changes

- `office_profiles` gained `union_code`, `postcode`, `phone`, `email`, `website`, `logo_path`, `stamp_path`.
- Added `number_sequences(prefix VARCHAR(16) PRIMARY KEY, last_value INT NOT NULL DEFAULT 0)`.
- Added migrations for the new office columns in `repdoc_ensure_schema()`.
- Added default tool rows for the 23 current keys.

## Certificate Numbering

- Format: `YY + union_code(7) + sequence(8)`.
- Sequence allocation is transactional via `number_sequences`.
- Missing/invalid union code falls back to `0000000`; the certificate metadata records `union_code_fallback`.
- A valid 7-digit union code produced `26123456700000001` in testing.
- Missing union code produced `26000000000000001`.
- Legacy 11-digit numbers remain searchable/printable and are not rewritten.

## Application Flow

1. Type picker (`index.php`) lists 23 cards with search/filter and the user's five most recent submissions.
2. Form (`form.php?type=<key>`) collects personal, present and permanent address, type extras, trade fees, members, purpose, photo, and documents.
3. Submit (`output.php`) validates CSRF and one-time `submission_token`, rejects unknown types, validates fields, stores uploads, allocates the number, charges the wallet, writes the log, and redirects to view.
4. If validation, numbering, charging, or logging fails, uploaded files are deleted; a wallet refund is issued when a charge succeeded but logging failed.
5. View (`view.php`) is ownership-checked; v2 rows get a screen-only reapply link; admin and UDC users get the appropriate back link.
6. Public verify (`verify/index.php`) looks up 11- or 17-digit numbers, shows office/type/name/date/status, and offers print only.
7. Admin (`admin/prottoyon.php`, `admin/prottoyon-view.php`) lists/filters/searches, views, revokes, and restores.
8. UDC (`Entrepreneur/panel/prottoyons.php`, `view.php`) has its own list/view.

## Uploads

- Applicant photo: JPG/PNG/WebP, max 2 MB, image MIME only.
- Supporting documents: JPG/PNG/WebP/PDF, max 5 MB each, max 5 files.
- Office logo/stamp: JPG/PNG/WebP, max 3 MB.
- Random server-side filenames; stored under `love/uploads/prottoyon/<user_id>/` or `love/uploads/office/<user_id>/`.
- Documents are served by authenticated `file.php` with owner/admin authorization.
- Public verification exposes no documents or photo.
- Office logo/stamp are embedded as data URIs in the certificate for reliable printing.

## Certificate Rendering

- `prottoyon_render_saved()` dispatches by data version:
  - `v >= 2` and current type → v2 template.
  - otherwise → legacy template.
- Single `genaretor/prottoyon/template/v2/default.php` renders all 23 types with registry-driven fields, member tables, fee tables, present/permanent addresses, office header, QR, signature, and footer.
- Bangla/English switch reloads with `?lang=bn|en`; print prints the current language.
- Revoked certificates show a red `বাতিল` / `REVOKED` overlay.
- `certificate-v2.css` is A4 portrait and hides screen controls in print.
- Legacy files under `genaretor/prottoyon/template/*.php` are preserved and rendered through `render-legacy.php`.

## Security Review

- CSRF on all state-changing forms.
- Type allowlist via `prottoyon_type()`.
- Required-field and date validation.
- Length limits via `prottoyon_clean()`.
- Ownership checks for view/reapply/file.
- Admin-only revoke/restore and admin panel.
- Approved-UDC requirement for office settings and official office issuance.
- Application IDs use `PRT-YYMMDD-XXXXXX` with DB collision checks.
- One-time submission token prevents duplicate charge/log.
- Wallet debit is conditional and transactional; failed log triggers refund.
- Upload MIME sniffing, extension allowlist, size limits, random names.
- Public verification is `noindex` and rate-limited by session (30 attempts / 10 minutes).
- Private uploads are outside public routes; `file.php` checks owner/admin and realpath containment.

## Testing Performed

- `php -l` passed for every new/changed PHP file, including templates and both admin/UDC views.
- `node --check` passed for `prottoyon.js`.
- Bootstrap loaded with `types=23`, `legacy=41`, `all=60`.
- Schema checks confirmed `number_sequences` and all new `office_profiles` columns.
- Rendered all 23 v2 certificates in Bangla and English: 23/23 pass.
- Rendered all 41 historical templates with flat sample data: 41/41 pass.
- HTTP-tested login, 23 type-picker cards, recent-applications section, all 23 GET forms, a family submission, certificate view (including the v2 reapply link), user applications list, public verification, and regular-user office restriction.
- Submitted a certificate with new address fields (`rbs`, `holding_no`, `union`, `resident_status`); DB and rendered view contained them.
- Confirmed new `ref` is an application ID and searchable through admin SQL.
- Confirmed a v1 legacy row shows the `পুরাতন` badge, has no reapply link, and renders the legacy template.
- Rendered all 27 actual existing prottoyon rows through `prottoyon_render_saved()`: 27/27 pass.
- Earlier HTTP pass covered admin list/view/revoke and UDC page restrictions.
- Test users/logs/sequences/transactions were removed; DB returned to 5 users, 89 logs, 0 sequence rows.
- PHP built-in test server was stopped.

## Deviations and Known Limitations

1. **No unique index on `document_logs.cert_no`.** The existing DB has 89 `document_logs` rows total (27 prottoyon rows), 63 with empty `cert_no`, so a global unique index would fail. New uniqueness is enforced by `number_sequences`; old empty numbers remain untouched. This is recorded as a deliberate deviation from the plan's later unique-index step.
2. **Single v2 template instead of six Bangla + six English layouts.** Per-type content comes from the registry, paragraph generator, member/fee tables, and conditional blocks. This reduces duplication and still passed all 23 type renders.
3. **Three overlapping current/historical keys** (`character`, `death`, `family`). They cannot be separated by `tool_key`; rendering uses `data.v` to choose v2 or legacy.
4. **Address model is expanded but not identical to the plan's example.** Implemented fields include village, RBS/road-block-sector, holding, ward, post office, union, upazila, district, division, postcode, and English equivalents. The plan's exact example also mentioned separate union fields; those are present.
5. **Member table is simplified.** It stores name, relation, age, NID, and share/occupation. It does not have separate English name, marital-status, or alive/deceased columns from the plan's broader member-table example.
6. **Trade tax categories are represented by the free-form fee table** (`fees[].name`, `fees[].amount`) plus fiscal year/expiry fields, rather than separate columns for each statutory tax type. All values remain printed declarations and are not wallet charges.
7. **Public verification is a summary**, not a full certificate preview; it shows only verification fields, with a print button. It deliberately does not expose NID, address, photo, or documents.
8. **Rate limiting is session-based**, not IP-based. This matches the current implementation; a shared-NAT/cleared-session bypass is possible.
9. **QR generation uses the cdnjs `qrcodejs` library.** If the CDN is unavailable, the certificate still prints the human-readable verification URL; the QR square may be empty.
10. **Certificate-number sequence can gap** if a number is allocated and the wallet charge or log write then fails. The number is not reused; there is no duplicate certificate number.
11. **Office logo/stamp upload was not exercised through a real HTTP multipart upload** in the final pass. The storage/validation code was inspected and linted; the rest of office settings was HTTP-tested.
12. **No browser pixel/screenshot review** was performed. CSS and rendered HTML were reviewed and HTTP-tested; print layout should still be visually checked in Chrome before production.
13. **`robots.txt` / `sitemap.xml` were not changed.** The public verify page is allowed by the existing default `Allow: /` and is marked `noindex` by meta tag; noindex pages are not sitemap candidates.
14. **`src/catalog.php` was not changed.** It contains shared file-library helpers, not the prottoyon tool registry; the homepage tool card copy was updated instead.
15. **Old rows with empty `cert_no` cannot be publicly verified** because there is no number to look up. They remain visible/printable from authorized admin/UDC/user views.

## Out of Scope / Not Added

- External PDF generation or stored PDF files.
- Certificate download buttons.
- Approval/pending/rejection workflow for certificate issuance.
- Trade-license renewal history or renewal applications.
- Public unauthenticated certificate submission.
- Reworking Repdoc's login/wallet system.
- Full digital-union translation administration.
- Public exposure of supporting documents.

## Final State

- Implementation is complete for the requested 23-type port.
- No test accounts or test submissions remain.
- No local PHP server is running.
- `love/repdoc.db` is modified by schema migrations; the tracked DB file now contains the new columns/table but no test rows.
- Recommended next step: visual print check of one Bangla and one English certificate in Chrome, then deploy.
