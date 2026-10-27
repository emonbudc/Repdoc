# Software Downloads Module — Implementation Audit

Date: 2026-10-26
Plan: `Plan/software_plan.md`
Status: implemented, smoke-tested, test data cleaned up.

## Delivered

- Added a public, login-free software catalogue at `/software/`.
- Added admin management at `admin/software.php`: add, edit, reorder, activate/deactivate, delete, and thumbnail upload/remove.
- Software metadata is stored in `love/software.json`; no database migration or schema change was made.
- Each entry has one external download URL. Repdoc does not host, mirror, proxy, or serve installers.
- Thumbnails use the existing file-thumbnail store: `repdoc_file_thumb_save('software', $entryId, $file)` under `file-thumbs/software/`.
- Active and valid entries appear publicly; paused and invalid entries are admin-only.
- Added homepage, catalogue navigation, footer, sitemap, robots, and admin navigation entries.

## Files

### Added

- `Plan/software_plan.md` — implementation plan.
- `Plan/software_audit.md` — this audit.
- `src/software.php` — JSON store, platform metadata, validation, CRUD, reorder, toggle and delete helpers.
- `software/index.php` — public `/software/` catalogue.
- `admin/software.php` — admin editor and list.
- `src/assets/image/file-logos/software.svg` — generic software logo/fallback art.
- `love/software.json` — empty JSON array; admin writes entries here.
- `file-thumbs/software/` — created automatically on first thumbnail upload (empty in this delivery).

### Modified

- `src/catalog.php` — login-free action mode, per-row title/version/size/action fields, custom hero/sidebar stats, optional join card, custom empty/closing content, software fallback art, and software links.
- `src/assets/css/catalog.css` — `.art-software` card art.
- `src/nav.php` — software item in the generator template menu.
- `index.php` — homepage software category, tool card, icon, and footer link.
- `sitemap.xml` — `/software/` entry.
- `robots.txt` — explicit `Allow: /software/`.
- `admin/includes/layout.php` — Software Downloads nav item and download icon.
- `admin/index.php` — Software Downloads quick link.

Pre-existing untracked `.kiro/` and `README.md` were left untouched.

## Data Model

Each `love/software.json` row contains:

```json
{
  "id": "a1b2c3d4e5f60718",
  "name": "VLC Media Player",
  "platform": "windows",
  "version": "3.0.21",
  "size": "40 MB",
  "description": "Windows-এর জন্য হালকা ভিডিও প্লেয়ার।",
  "url": "https://example.com/vlc.exe",
  "active": true,
  "created_at": 1760000000,
  "updated_at": 1760000000
}
```

Validation:

- `name` required, maximum 120 characters.
- `platform` normalized to `windows`, `android`, `macos`, `linux`, or `other`.
- `version` optional, maximum 40 characters.
- `size` optional human-readable label, maximum 40 characters.
- `description` optional, maximum 800 characters, tags stripped.
- `url` required; absolute `http` or `https` only; maximum 1000 characters; userinfo, whitespace, and control characters rejected.
- `active` boolean.
- `id` is 16 lowercase hex characters from `random_bytes(8)`. Manually corrupted IDs are converted to a stable hash for admin recovery.

Platform labels and colors live in `repdoc_software_platforms()`.

## Admin Workflow

`admin/software.php`:

- Statistics: total, live, platforms in use, entries needing attention.
- Add/edit form: name, platform, version, size, external URL, description, thumbnail, published state.
- Edit mode: `?edit=ID`, current thumbnail preview, remove-thumbnail checkbox.
- List: thumbnail, name/platform/version, host/raw URL, size, updated date, status, reorder buttons, edit/delete.
- Reorder persists through `repdoc_software_move_entry()`.
- Toggle pauses or activates an entry; the public page updates immediately.
- Delete removes the JSON row and its thumbnail.
- Invalid manual rows show an `Invalid` pill and remain editable/deletable, but are never public.
- Every POST is CSRF-protected and uses POST/Redirect/GET with flash messages.
- Thumbnail upload uses the shared validated helper: JPG/PNG/WebP/GIF, maximum 2 MB.

## Public Page

`software/index.php`:

- Loads only valid, active entries in admin order.
- Reuses the shared catalogue shell, search, platform filters, sorting, cards, sidebar, guide, and footer.
- Platform filters: Windows, Android, macOS, Linux, Other.
- Live search covers name, version, description, platform, and host.
- Cards show uploaded thumbnail or generated software art, name, platform, extension hint, version, size, description, and update date.
- Download button links directly to the external URL with `target="_blank"` and `rel="noopener noreferrer nofollow"`.
- No login redirect, login-gated download, or `software/download.php` endpoint.
- Empty and filtered-empty states use software-specific copy.
- SEO: canonical URL, CollectionPage/Breadcrumb/ItemList JSON-LD, sitemap entry, robots allow.

## Security Review

- Admin page is protected by `require_admin()` through `admin/includes/layout.php`.
- CSRF token is checked on every state-changing POST.
- Download URLs are scheme-allowlisted and never fetched or executed server-side.
- Text fields are stripped, trimmed, length-capped, and escaped on output.
- Public rows are filtered by both validity and active state.
- External links use `noopener noreferrer nofollow`.
- Thumbnail MIME is verified with `getimagesize()`; size and image type are limited; filenames are hashed.
- `love/software.json` is outside public routes and covered by `love/.htaccess` deny rules.
- The delete confirmation no longer interpolates the entry name into JavaScript.
- No executable upload, no PHP-backed download endpoint, and no user-controlled path traversal surface were added.

## Testing Performed

- `php -l` passed for every new/changed PHP file:
  - `src/software.php`
  - `src/catalog.php`
  - `software/index.php`
  - `admin/software.php`
  - `admin/includes/layout.php`
  - `admin/index.php`
  - `index.php`
  - `src/nav.php`
- `git diff --check` passed.
- `sitemap.xml` parsed successfully.
- Logged-out HTTP pass, all `200` with no PHP fatal/warning/notice/deprecated output:
  - `/`
  - `/officefiles/`
  - `/offlinefiles/`
  - `/showcase/`
  - `/software/`
  - `/importantlinks/`
  - `/about/`
- `/software/` did not redirect to login and rendered no login-gated download action.
- Created a temporary VLC entry; verified:
  - JSON row and public card.
  - Version, size, platform, description, and update date display.
  - External href plus `target="_blank"` and `rel="noopener noreferrer nofollow"`.
  - ItemList JSON-LD contained the entry.
- Uploaded a real PNG through the admin form; public card switched from generated art to the uploaded thumbnail.
- Removed the thumbnail through the admin form; the stored file was deleted and the card returned to generated art.
- Toggled an entry inactive; it disappeared from `/software/`. Toggled it back; it returned.
- Deleted entries; JSON rows and thumbnails were removed.
- Rejected `javascript:alert(1)` with `Use a full http(s) download URL.` and no new row.
- Rejected a non-image file disguised as `image/png`; no thumbnail was stored and a warning flash appeared.
- Manually injected a corrupted row with a bad ID, platform, and URL; admin displayed `Invalid` without PHP errors, and the row remained editable/deletable.
- Direct helper test confirmed reorder behavior (`[B, A]` → `[A, B]`), toggle, save, and delete.
- Temporary entries, thumbnails, sessions, and test flashes were removed.
- `love/software.json` was returned to `[]`.
- `love/repdoc.db` was restored after admin tests; the DB has no test changes.
- The PHP built-in test server was stopped.

## Deviations and Known Limitations

1. **No advisory file lock on JSON writes.** Android/Termux FUSE rejects `LOCK_EX` with “Exclusive locks are not supported for this stream.” The writer uses a temporary file plus atomic `rename()` instead. Concurrent admin writes are not expected; last writer wins.
2. **One generic software logo for every platform.** `software.svg` is used for Windows, Android, macOS, Linux, Other, and the generic software preview. Platform identity comes from labels and colors, not five separate brand icons.
3. **New-entry validation errors do not repopulate the form.** The request redirects back to the blank add form with a flash message. Edit errors keep the stored row and can be corrected in place.
4. **No liveness or malware check on external URLs.** Repdoc validates URL shape only; availability and file safety belong to the publisher. This matches the external-link-only design.
5. **No pagination.** All entries render on one page. This is appropriate for a curated list but could need pagination if the collection grows large.
6. **`active` defaults to false when manually omitted from JSON.** The admin form defaults new entries to published, so normal admin-created rows are not affected.
7. **No visual screenshot/print review was performed.** Layout was HTTP- and HTML-validated, but the desktop/mobile card composition and uploaded-thumbnail cropping should get one browser check before production.

## Out of Scope / Not Added

- Hosting, mirroring, or re-uploading installers.
- A `software/download.php` endpoint.
- Virus scanning, file reputation checks, or download counters.
- Per-software multiple mirrors or operating-system sub-links.
- Database migration or new tables.
- Automatic import from existing file packs.
- Seed/demo software entries.

## Final State

- Implementation is complete for the requested public software downloads section.
- `love/software.json` is intentionally empty; add real entries from `admin/software.php`.
- No test data, thumbnails, sessions, or local server remain.
- `love/repdoc.db` is unchanged from its pre-test state.
- Recommended next step: add the first real entries in admin, then visually check `/software/` on desktop and mobile.
