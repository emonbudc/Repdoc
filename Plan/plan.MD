# Repdoc Prottoyon Module Port Plan

## Goal

Replace Repdoc's current 41-type `prottoyon` generator with a port of digital-union's multi-type certificate application module:

- 23 certificate application types from digital-union's certificate sidebar.
- Repdoc login, wallet, admin, and UDC access retained.
- New application form, application list, view, reapply, and public verification.
- Digital-union-style A4 portrait certificates with both present and permanent addresses.
- Bangla and English print variants.
- Immediate issue after successful submission. No approval queue.
- Print-only output. No external PDF library and no download button.

## Reference Source

- Digital-union checkout: `/data/data/com.termux/files/home/digital-union`
- Relative digital-union paths in this plan are under that root.
- It is a reference implementation only; Repdoc does not load it at runtime and must not copy its `vendor/` or PDF stack.

## Locked Decisions

- **No external PDF library.** Do not add mPDF, TCPDF, Dompdf, Composer, `vendor/`, or any server-side PDF generator.
- **No download button.** Certificate screens use a Print / Save as PDF button that calls `window.print()`.
- **New certificate numbers:** 17 digits in the form `YY + union_code(7) + sequence(8)`.
- **Legacy certificate numbers:** existing 11-digit numbers stay valid, searchable, and printable.
- **Trade license:** port business details, fee/tax table, fiscal year, and expiry. Auto-issue. No renewal workflow and no approval.
- **Addresses:** print both present and permanent addresses, matching digital-union.
- **Type set:** digital-union's 23 sidebar certificate types. Birth registration remains Repdoc's separate birth tool.
- **Legacy types:** the old 41 prottoyon types stay readable for old logs, but are removed from the new application picker.
- **Storage:** reuse `document_logs`; do not create a second application table.
- **Public verification:** required, no login, print-only.
- **Frontend design:** all application screens use the same design system as the Repdoc homepage. The printed certificate remains digital-union-style and is separate from the site shell.
- **Trade fees:** printed declarations on the license. They are not charged through the Repdoc wallet; the normal per-type service price is the wallet charge.

## Frontend Design

The application UI must match the public Repdoc homepage, not digital-union's admin/Bootstrap UI.

### Source of Truth

- `index.php` (homepage layout, header, cards, chips, footer)
- `src/assets/css/tokens.css`
- `src/assets/css/shell.css`
- `src/assets/css/home.css`
- `src/assets/js/home.js`
- `src/nav.php`
- `src/assets/image/logo.png`
- `src/seo.php`

### Shared Shell

- Use the same `site-header`, brand logo, primary nav, login/wallet chip, mobile nav toggle, and `site-footer footer-mint` pattern as the homepage.
- Use `repdoc_render_site_nav('prottoyon')` for generator pages where the shared dropdown nav is appropriate.
- Keep the homepage's light, warm, "ledger" visual language.
- Default to the homepage light theme. Remove the current prottoyon page-level dark-theme toggle and duplicate inline theme styles.
- Do not introduce a new palette, font stack, component library, or build step.
- Use existing tokens for color, typography, spacing, radius, shadow, and container width.
- Reuse homepage button, chip, badge, card, section-head, and empty-state patterns.

### Type Picker

- Match the homepage tool grid: cards with icon, Bangla name, short description, optional badge, and arrow affordance.
- Use homepage-style category chips or grouping for the 23 types.
- Keep the type search/filter behavior.
- Each card opens `form.php?type=<key>`.
- Show recent applications below or beside the picker using the same card/list language.

### Application Form

- Use the homepage container width, section cards, headings, helper text, and button styles.
- Two-column desktop layout; one-column mobile.
- Preserve the current geo dropdown behavior and add the new address blocks.
- Use the same focus-visible treatment, error/success styling, and touch target sizing as the homepage.
- Bangla-first labels with English secondary labels where needed.
- No digital-union Bootstrap classes or visual style on screen.

### Application List and View

- Use homepage-style cards for mobile and a clean table/card hybrid for desktop.
- Use status badges consistent with Repdoc success/warning/danger tokens.
- Keep the primary action clear: view, print, or reapply.
- Certificate view uses the shared site shell on screen; print mode hides the shell and prints only the certificate.

### Public Verify

- Use the same Repdoc header/footer and card styling for the lookup form.
- Show the certificate preview in a homepage-style surface.
- Print button only; no download.
- `noindex` for the verification result page.

### Print Boundary

- The screen UI is Repdoc homepage design.
- The printed certificate is digital-union-style A4 portrait.
- Do not apply homepage header/footer, navigation, or screen cards to the printed page.
- Do not apply digital-union's screen/admin styling to Repdoc application pages.

### Responsive and Accessibility

- Mobile-first, no horizontal overflow.
- Collapsible nav matches the homepage.
- Visible keyboard focus on all controls.
- Sufficient contrast using existing tokens.
- Bangla text must render with Noto Sans Bengali and correct line height.
- All form controls have labels, errors, and accessible names.

## Current State Summary

### Repdoc

- Plain PHP 8.5.12, SQLite primary database, PDO sqlite/mysql, gd, mbstring, curl, dom, zip.
- No Composer, no `vendor/`, no PDF library, no public verification for prottoyon.
- Current generator: `genaretor/prottoyon/index.php`, 41 types, landscape A4.
- Current renderer: `genaretor/prottoyon/render.php`.
- Current submit/output: `genaretor/prottoyon/output.php`.
- Current registry/extras: `genaretor/prottoyon/types.php`.
- Current office profile: `genaretor/prottoyon/office.php`.
- Current storage: `document_logs` in `love/repdoc.db`.
- Current logs: 27 prottoyon records; no duplicate certificate numbers.
- Current output has no CSRF check; it charges before logging; unknown types are not strictly rejected.
- Current type keys use hyphens while default tool pricing uses underscores, so some price lookups return zero.
- Existing CSRF helpers: `csrf_token()`, `csrf_field()`, `csrf_verify()` in `src/bootstrap.php`.
- Existing auth: `current_user()`, `require_login()` in `src/bootstrap.php`.
- Existing wallet: `charge_tool()` in `src/bootstrap.php`.

### Digital-Union

- Certificate types are multi-type, with 23 sidebar application links.
- Certificate number format is 17 digits: `YY + union_code(7) + sequence(8)`.
- Templates: 6 Bangla certificate layouts, 6 English layouts, matching online-verify layouts.
- `default` templates handle the remaining types through conditionals.
- Special templates/layouts: `family`, `nagorik`, `new-voter`, `trade`, `warish`.
- Trade license includes business details, fee/tax table, fiscal year, validity, and renewal history.
- `birth` is a separate module.
- Reference paths:
  - `database/schema.sql`
  - `models/ExtraFields.php`
  - `models/ApplicationManager.php`
  - `controllers/ApplicationController.php`
  - `modules/Services/ApplicationService.php`
  - `modules/Services/NumberGeneratorService.php`
  - `helpers/pdfGenarate.php` (not ported)
  - `templates/applications/forms/default.twig`
  - `templates/applications/certificate/bangla/`
  - `templates/applications/certificate/english/`
  - `templates/applications/online-verify/`
  - `templates/partials/certificate_paragraph.twig`
  - `templates/partials/certificate_paragraph_english.twig`
  - `templates/partials/certificate_fields.twig`
  - `public/assets/css/sonod.css`
  - `public/assets/bg_hd.png`
  - `public/assets/fonts/`

## Scope

### In Scope

- 23-type application picker.
- Common personal information form.
- Present and permanent address blocks.
- Type-specific fields.
- Family/warish member repeater.
- Trade business details and fee table.
- Optional applicant photo and supporting documents.
- Immediate issuance without approval.
- 17-digit new certificate numbers.
- 11-digit legacy certificate support.
- Application list with filters and reapply.
- Ownership-checked view/print.
- Admin and UDC list/view/print/revoke.
- Public verification by certificate number.
- Legacy 41-type read-only rendering.
- Print CSS and print buttons.
- Wallet charging with idempotent reference.
- Input validation, CSRF, authorization, upload validation.

### Out of Scope

- Any external PDF library or generated PDF files.
- Server-side PDF storage.
- Download buttons for prottoyon certificates.
- Approval, pending, or rejection workflow.
- Trade license renewal history and renewal applications.
- Public unauthenticated application submission.
- Changes to unrelated tools or static file archives.
- Reworking Repdoc's wallet or login system.
- Full digital-union English/Bangla translation administration.
- Public exposure of uploaded supporting documents.

## Architecture

### Data Model

Reuse `document_logs` as the application and certificate ledger.

Fields used:

- `id`: internal row ID.
- `user_id`: applicant/operator.
- `tool_key`: `prottoyon_<type>`.
- `ref`: application ID, format `PRT-YYMMDD-XXXXXX`.
- `dob`: applicant date of birth.
- `photo`: optional applicant photo path.
- `signature`: optional signature path; normally empty.
- `data`: versioned JSON snapshot.
- `cert_no`: 17-digit new number or legacy 11-digit number.
- `status`: `valid` or `revoked`.
- `office`: JSON snapshot of office profile at issue time.
- `created_at`: issue timestamp.

Data JSON shape:

```json
{
  "v": 2,
  "type": "prottyon",
  "personal": {},
  "present": {},
  "permanent": {},
  "extra": {},
  "members": [],
  "documents": [],
  "meta": {
    "language": "bn",
    "issued_by": 12,
    "source": "new"
  }
}
```

Address object shape:

```json
{
  "village_bn": "",
  "village_en": "",
  "rbs_bn": "",
  "rbs_en": "",
  "holding_no": "",
  "ward_no": "",
  "postoffice_bn": "",
  "postoffice_en": "",
  "upazila_bn": "",
  "upazila_en": "",
  "district_bn": "",
  "district_en": "",
  "union_bn": "",
  "union_en": ""
}
```

### Number Sequences

Add a small sequence table:

```sql
CREATE TABLE IF NOT EXISTS number_sequences (
  prefix TEXT PRIMARY KEY,
  last_value INTEGER NOT NULL DEFAULT 0
);
```

New certificate number generation:

1. Read `union_code` from the office profile.
2. Validate exactly 7 digits.
3. If missing/invalid, use `0000000` and show an admin/UDC warning to set it.
4. Prefix is `date('y') . $unionCode` (9 characters).
5. Allocate the next 8-digit sequence atomically in a transaction.
6. Format as `prefix + str_pad(sequence, 8, '0', STR_PAD_LEFT)`.
7. Retry on unique collision.
8. Fail cleanly if sequence exceeds `99999999`.

Add a unique index on `document_logs.cert_no` after verifying no existing duplicates. Current prottoyon logs have no duplicates.

Legacy numbers remain unchanged. Verification accepts 11 or 17 digits and converts Bangla digits to English digits before lookup.

### Type Registry

Create one canonical registry with:

- `key`
- Bangla label
- English label
- price tool key
- required common fields
- extra field definitions
- member support flag
- trade support flag
- certificate template
- legacy flag

Canonical tool key: `prottoyon_<key>` using the hyphenated type key. Legacy underscore aliases must still resolve for old transactions.

### 23 Application Types

| Key | Bangla Label | Special Inputs |
|---|---|---|
| `nagorik` | নাগরিক সনদ | common only |
| `trade` | ট্রেড লাইসেন্স | business details, fee/tax table, fiscal year, expiry |
| `warish` | ওয়ারিশ সনদ | deceased details, member table |
| `character` | চারিত্রিক সনদ | purpose bn/en |
| `new-voter` | নতুন ভোটার | common only |
| `vumihin` | ভূমিহীন সনদ | landless reason bn/en |
| `death` | মৃত্যু প্রত্যয়ন | deceased name bn/en, death date, reason |
| `yearlyincome` | বার্ষিক আয় | earning type, amount, purpose |
| `family` | পারিবারিক সনদ | member table, member count |
| `obibahito` | অবিবাহিত সনদ | reason bn/en |
| `bibahito` | বিবাহিত সনদ | spouse name, marriage date |
| `ekoinam` | একই নামের প্রত্যয়ন | nickname bn/en |
| `punobibaho` | পুনঃ বিবাহ না হওয়া | previous marriage status bn/en |
| `protibondi` | প্রতিবন্ধী সনদ | disability type bn/en, percentage |
| `onapotti` | অনাপত্তি পত্র | business/land/trade-license fields |
| `prottyon` | প্রত্যয়নপত্র | `certified_by` |
| `onumoti` | অনুমতি পত্র | permission reason bn/en |
| `voter` | ভোটার স্থানান্তর | common only |
| `rastakhonon` | রাস্তা খনন | purpose bn/en, road type |
| `sonaton` | সনাতন ধর্ম | religious ID |
| `nodibanga` | নদী ভাঙন | affected reason bn/en |
| `anumanik-boyosh-protyon` | আনুমানিক বয়স | common only |
| `protyonpotro-onnanno` | প্রত্যয়নপত্র (অন্যান্য) | description |

`birth` is not in this list. It remains the separate birth registration tool.

`protyonpotro-onnanno` may be missing from digital-union `term_translations`; use the static Bangla/English labels above.

### Common Form Fields

Personal:

- Name bn/en
- Father name bn/en
- Mother name bn/en
- NID
- Birth registration number
- Passport number
- Date of birth
- Gender
- Occupation
- Education
- Religion
- Marital status
- Spouse name bn/en
- Resident status
- Applicant name
- Applicant phone/email
- Applicant photo (optional)
- Supporting documents (optional)

Present address:

- Village/mohalla bn/en
- Road/block/sector bn/en
- Holding number
- Ward number
- Post office bn/en
- Upazila bn/en
- District bn/en
- Union bn/en

Permanent address:

- Same fields as present address.
- UI must support "same as present" and keep both snapshots independently editable.

### Type-Specific Extras

- `purpose_bn`, `purpose_en`
- `reason_bn`, `reason_en`
- `description`
- `nickname_bn`, `nickname_en`
- `certified_by`
- `spouse_name_bn`, `spouse_name_en`
- `marriage_date`
- `previous_marriage_status_bn`, `previous_marriage_status_en`
- `disability_type_bn`, `disability_type_en`
- `disability_percentage`
- `earning_type`, `annual_income`
- `landless_reason_bn`, `landless_reason_en`
- `affected_reason_bn`, `affected_reason_en`
- `road_type`
- `religious_id`
- `deceased_name_bn`, `deceased_name_en`, `death_date`, `death_reason`
- `business_name_bn`, `business_name_en`
- `business_type`
- `business_ownership_type`
- `business_address`
- `trade_license_no`
- `business_tax_id`, `business_vat_id`
- `fiscal_year`
- `license_fee`
- `vat_amount`
- `occupation_tax`
- `income_tax`
- `signboard_tax`
- `surcharge`
- `total_fee`
- `business_start_date`
- `expiry_date`

Trade calculations:

- `total_fee` may be auto-calculated from the fee/tax fields, but the operator can override it.
- All values are printed declarations.
- No trade fee is deducted from the wallet.
- No renewal record is created.

### Member Table

For `family` and `warish`:

- Serial number
- Name bn/en
- Relation
- Birth date or age
- NID
- Marital status
- Alive/deceased

Store as `data.members`.

Validate at least one member for `family` and `warish`.

### Applicant Photo and Documents

- Photo: JPG/PNG/WebP, maximum 2 MB.
- Documents: PDF/JPG/PNG/WebP, maximum 5 MB each, maximum 5 files.
- Random file names.
- Store outside direct executable paths.
- Serve owner/admin/UDC files through an authenticated handler.
- Never expose supporting documents on the public verification page.
- Applicant photo is optional and is not printed on the certificate.

## Routes and Pages

### Applicant Routes

- `genaretor/prottoyon/index.php`
  - Type picker.
  - Recent applications.
  - Search/filter.
- `genaretor/prottoyon/form.php?type=<key>`
  - New application.
  - Edit draft/reapply prefill.
- `genaretor/prottoyon/output.php`
  - POST-only submit handler.
- `genaretor/prottoyon/applications.php`
  - Signed-in user's certificate list.
- `genaretor/prottoyon/view.php?id=<log_id>`
  - Ownership-checked certificate view.
  - Print button.
  - Reapply button.
- `genaretor/prottoyon/file.php`
  - Authenticated photo/document handler.

### Public Route

- `verify/index.php?no=<cert_no>`
  - Public lookup by certificate number.
  - Optional `type` filter.
  - Shows certificate, issue date, office, and status.
  - Revoked certificates show a red `বাতিল` watermark.
  - Print button only.
  - No download.
  - `noindex` meta tag.
  - Rate limiting by session/IP.

### Admin Routes

- `admin/prottoyon.php`
  - 23 types plus legacy filter.
  - Search by name, cert number, application ID, user.
  - View/print.
  - Revoke/restore.
  - No download.
- `admin/prottoyon-view.php`
  - Shared renderer.
  - Legacy fallback.
  - Revoke/restore controls.
  - Print button.

### UDC Routes

- `Entrepreneur/panel/prottoyons.php`
  - Own certificates only.
  - Type/status/search filters.
  - Print.
- `Entrepreneur/panel/view.php`
  - Shared renderer.
  - Ownership check.
  - Print.
  - Reapply.

## Submission Flow

1. Require login.
2. Validate CSRF.
3. Validate type against the 23-type allowlist.
4. Normalize POST data.
5. Validate common fields, addresses, extras, members, trade data, and uploads.
6. Generate application ID `PRT-YYMMDD-XXXXXX` with collision retry.
7. Generate 17-digit certificate number.
8. Begin database transaction.
9. Charge wallet with `charge_tool()` using the application ID as reference.
10. Insert `document_logs` row with `status = 'valid'`.
11. Commit.
12. On any failure after charge, refund through the existing wallet mechanism.
13. Redirect to `view.php?id=<id>`.

No row is inserted with `pending`. No approval action is required.

## Certificate Rendering

### Layout

- A4 portrait.
- Digital-union-style header, title, certificate number boxes, issue date, person table, address table, type paragraph, signature, footer, QR.
- Both present and permanent addresses.
- 17 digit boxes for new numbers; 11 boxes for legacy numbers.
- Bangla primary; English via `?lang=en` or a view toggle.
- `@page { size: A4 portrait; margin: 0; }`.
- Print background colors and images with `print-color-adjust: exact`.

### Templates

Create new templates under `genaretor/prottoyon/template/v2/`:

- `partials/header.php`
- `partials/person.php`
- `partials/address.php`
- `partials/paragraph.php`
- `partials/members.php`
- `partials/business.php`
- `partials/signature.php`
- `partials/footer.php`
- `default.php`
- `nagorik.php`
- `family.php`
- `new-voter.php`
- `trade.php`
- `warish.php`
- `en/default.php`
- `en/nagorik.php`
- `en/family.php`
- `en/new-voter.php`
- `en/trade.php`
- `en/warish.php`

The existing `genaretor/prottoyon/template/` folder remains as the legacy renderer. Do not delete or overwrite the 41 old template files.

### Shared Renderer

`genaretor/prottoyon/render.php` becomes a shared renderer:

- Detects `data.v === 2` and current type.
- Renders v2 templates.
- Falls back to legacy templates for old rows.
- Accepts `lang=bn|en`.
- Accepts `print=1` for auto-print after load.
- Never writes or streams a PDF.

### Assets

- Screen UI CSS: `genaretor/prottoyon/assets/css/prottoyon.css`, layered on the homepage tokens/shell.
- Print certificate CSS: `genaretor/prottoyon/assets/css/certificate-v2.css`.
- Background: copy/adapt digital-union `bg_hd.png`.
- Fonts: SolaimanLipi and Nikosh, subject to license review.
- Logo and watermark from existing Repdoc/digital-union assets.
- QR: reuse existing client-side `qrcodejs` CDN.
- QR must encode `verify/index.php?no=<cert_no>`.
- Print a readable verification URL below the QR as fallback.
- Ensure QR is rendered before print; wait for fonts and QR generation.

### Print Controls

- Every certificate view shows `Print / Save as PDF`.
- Button calls `window.print()`.
- Hide navigation, sidebars, buttons, and controls with `@media print`.
- Remove all prottoyon download buttons and download endpoints.
- Do not generate or store PDF files.

## Public Verification

Flow:

1. User opens `verify/`.
2. Enters certificate number.
3. Server strips non-digits, converts Bangla digits to English, and accepts 11 or 17 characters.
4. Search `document_logs` for `cert_no`.
5. Restrict results to `tool_key LIKE 'prottoyon_%'`.
6. If not found, show a neutral not-found state.
7. If found and `status = 'valid'`, render certificate and valid notice.
8. If `status = 'revoked'`, render certificate with `বাতিল` watermark and revoked notice.
9. Show Print button only.
10. Never expose internal IDs, wallet data, user IDs, documents, or admin data.

Privacy:

- `noindex, nofollow` meta.
- Rate limit repeated lookup attempts.
- Do not list recent certificates.
- Do not expose supporting documents.
- Applicant photo is not shown publicly.

## Legacy Compatibility

- Old 41-type rows remain in `document_logs`.
- New application picker only shows 23 types.
- Admin/UDC lists include a "Legacy (41 types)" filter.
- Public verification supports old 11-digit numbers and old types.
- Old rows use the existing 41 templates and landscape renderer.
- Old certificate numbers are not rewritten.
- Old tool keys and pricing aliases remain resolvable.
- Do not run destructive data migration.

## Pricing and Wallet

- Canonical key: `prottoyon_<hyphenated-type>`.
- Add/update price rows for the 23 types in the tools/default pricing registry.
- Fix the current underscore/hyphen lookup mismatch.
- Preserve aliases for legacy keys.
- Charge only after validation succeeds.
- Use the application ID as idempotency reference.
- Refund if logging fails.
- Trade license fee values are printed only; they do not affect wallet price.
- Admin/UDC pricing behavior stays consistent with current Repdoc rules.

## Schema Changes

Add to `src/bootstrap.php` migrations:

- `number_sequences` table.
- Unique index on `document_logs.cert_no` if no duplicates exist.
- Index on `document_logs.tool_key`.
- Index on `document_logs.user_id`.
- Index on `(user_id, tool_key)`.
- `office_profiles.union_code` (7 digits, nullable/default empty).
- `office_profiles.postcode`.
- `office_profiles.phone`.
- `office_profiles.email`.
- `office_profiles.website`.
- `office_profiles.logo_path`.
- `office_profiles.stamp_path`.
- Optional `office_profiles.member_name` for a second signature, if needed.

No new application table.

## Office Profile

Update `genaretor/prottoyon/office.php` to collect:

- Office type.
- Office name.
- Union code (7 digits).
- Chairman name.
- Member name (optional).
- Upazila.
- District.
- Postcode.
- Phone.
- Email.
- Website.
- Logo path.
- Stamp path.

Certificate header/footer hides fields that are empty. Missing union code does not block issuance but shows an admin/UDC warning.

## Security

- CSRF on all state-changing forms.
- Strict type allowlist; unknown type is rejected.
- Server-side required-field validation.
- Numeric/date validation.
- Length limits on all text fields.
- Ownership checks on view/reapply/file access.
- Admin/UDC role checks.
- Unique cert number and application ID collision handling.
- Atomic charge/log/refund.
- Upload MIME sniffing, extension allowlist, size limits, random names.
- No executable files in upload storage.
- Escape all output with `e()`.
- No raw JSON rendered into HTML.
- No public access to `love/` or upload storage.
- Rate limit public verification.
- `noindex` on verification.
- Do not log sensitive values.
- Preserve existing CSRF/session behavior.

## Files

### Create

- `Plan/plan.MD` (this document)
- `genaretor/prottoyon/form.php`
- `genaretor/prottoyon/applications.php`
- `genaretor/prottoyon/view.php`
- `genaretor/prottoyon/file.php`
- `genaretor/prottoyon/template/v2/` (all layouts and partials)
- `genaretor/prottoyon/assets/css/prottoyon.css` (screen UI using homepage tokens)
- `genaretor/prottoyon/assets/css/certificate-v2.css` (print certificate only)
- `verify/index.php`

### Modify

- `genaretor/prottoyon/index.php`
- `genaretor/prottoyon/output.php`
- `genaretor/prottoyon/render.php`
- `genaretor/prottoyon/types.php`
- `genaretor/prottoyon/office.php`
- `src/bootstrap.php`
- `src/catalog.php`
- `src/nav.php`
- `index.php`
- `admin/prottoyon.php`
- `admin/prottoyon-view.php`
- `Entrepreneur/panel/prottoyons.php`
- `Entrepreneur/panel/view.php`
- `robots.txt` if verify needs an allow/disallow rule
- `sitemap.xml` if public routes change

### Preserve

- `genaretor/prottoyon/template/*.php` (legacy 41 templates)
- Existing `document_logs` rows
- Existing 11-digit certificate numbers
- Existing wallet transactions
- Unrelated `Entrepreneur/panel/download.php` behavior for static archives

### Reuse for Frontend

- `src/assets/css/tokens.css`
- `src/assets/css/shell.css`
- `src/assets/css/home.css`
- `src/assets/js/home.js`
- `src/nav.php`
- `src/seo.php`
- `src/assets/image/logo.png`

Do not fork or duplicate the homepage design tokens into prottoyon-specific styles.

## Implementation Order

1. Schema and helpers: sequence table, indexes, office columns, canonical type registry.
2. Pricing and labels: 23 tools, aliases, homepage/nav/UDC copy.
3. Frontend shell: homepage header/nav/footer, tokens, type picker, form/list/verify screen styles.
4. Form and submission: validation, uploads, 17-digit number, wallet charge, log insert.
5. Renderer: v2 templates, both addresses, Bangla/English, print CSS, QR.
6. Applicant pages: list, view, reapply, ownership.
7. Admin and UDC pages: filters, view, print, revoke/restore.
8. Public verification page.
9. Legacy fallback and migration checks.
10. Security and QA pass.

## Test Plan

### Syntax

- `php -l` on every changed PHP file.

### Functional

- Login required.
- All 23 types appear once.
- Birth is separate.
- Each type submits and issues immediately.
- 17-digit number format is correct.
- Legacy 11-digit numbers still verify.
- Both addresses appear in Bangla and English.
- Family/warish member tables render.
- Trade fee table and expiry render.
- Print preview is one A4 portrait page.
- QR resolves to the correct verification URL.
- Public verification finds valid, revoked, unknown, and legacy certificates.
- Admin/UDC filters, ownership, print, revoke/restore work.
- Reapply pre-fills previous data and issues a new number.
- Wallet charge is correct and refunds on failure.

### Frontend

- Type picker visually matches the homepage tool grid, chips, badges, and buttons.
- Header, nav, logo, login/wallet chip, mobile nav, and footer match the homepage.
- Form, application list, view, and verification surfaces use homepage cards, typography, spacing, and controls.
- Homepage light theme is the default; no prottoyon-specific dark-theme toggle or duplicate token palette remains.
- No horizontal overflow at 360 px, 768 px, or 1280 px.
- Visible keyboard focus and readable Bangla text with Noto Sans Bengali.
- Print preview hides the site shell and shows only the certificate.

### Security

- CSRF rejection works.
- Unknown type is rejected.
- Ownership checks block other users.
- Upload type/size limits work.
- No public document access.
- No download button or PDF endpoint exists.
- No raw JSON/XSS in certificate pages.

### Regression

- Existing 27 prottoyon logs remain visible.
- Existing admin/UDC lists and filters still work.
- Existing non-prottoyon tools are unaffected.
- Existing static archive downloads still work.

## Definition of Done

- 23 digital-union-style certificate types are available.
- New certificates use 17-digit numbers; legacy 11-digit certificates remain valid.
- Applications, view, reapply, print, and public verification work.
- Application screens use the Repdoc homepage design system: shared header/nav/footer, tokens, cards, chips, buttons, light theme, and responsive nav.
- The printed certificate remains digital-union-style and does not include the homepage shell.
- Both addresses, Bangla/English output, trade fee table, and member tables render correctly.
- No external PDF library, no Composer, no vendor directory.
- No PDF download button or endpoint.
- Print button works on applicant, admin, UDC, and verification views.
- No approval workflow exists.
- Old 41-type records remain readable.
- All changed PHP files pass `php -l`.
- No unrelated files or data are modified.
