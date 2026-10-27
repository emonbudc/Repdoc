# Software Downloads — implementation plan

## Goal

Add a public, login-free software downloads section at `/software/`, modeled on the
existing `/officefiles/` catalogue but with admin-managed entries instead of
auto-scanned folders.

Admin controls:

- software list (add, edit, reorder, activate/deactivate, delete)
- thumbnail per entry
- one external download link per entry

Public visitors can browse, search, filter and download without logging in.

## Current behaviour analysed

- `/officefiles/index.php` is public and auto-lists files from `officefiles/{word,excel,ppt}/`.
- `/officefiles/download.php` is login-gated by `require_login()` at line 19.
- `admin/files.php` uploads packs, edits descriptions and thumbnails.
- Thumbnails are stored in `file-thumbs/{type}/{hash}.ext` by `src/filemeta.php`.
- `src/catalog.php` renders all catalogue pages and currently hardcodes login-gated
  download actions and login notices.

## Chosen design

- Store software metadata in `love/software.json` (same pattern as important links).
  No database migration is needed.
- Store thumbnails with the existing `repdoc_file_thumb_save('software', $entryId, $file)`
  helper under `file-thumbs/software/`.
- External download links only. Repdoc does not host executables and adds no
  `download.php` endpoint for software.
- Platforms: Windows, Android, macOS, Linux, Other.
- One download URL per software entry.
- Public route: `/software/` — “সফটওয়্যার ডাউনলোড”.

## Files

### Add

- `Plan/software_plan.md` — this plan.
- `Plan/software_audit.md` — post-implementation audit.
- `src/software.php` — JSON store, validation, platform metadata, CRUD helpers.
- `software/index.php` — public catalogue page.
- `admin/software.php` — admin add/edit/reorder/toggle/delete editor.
- `src/assets/image/file-logos/software.svg` — generic software logo.
- `love/software.json` — empty JSON array, created/updated by admin.

### Modify

- `src/catalog.php` — public download mode, row-level external actions, optional
  title/version/size-label fields, custom hero/sidebar stats, optional join card,
  custom closing and empty actions, software fallback art, software nav/footer links.
- `src/assets/css/catalog.css` — generic software fallback card art.
- `src/nav.php` — software link in the generator pages’ template menu.
- `index.php` — homepage tool registry entry, software category and icon.
- `sitemap.xml` — `/software/` entry.
- `robots.txt` — explicit `Allow: /software/`.
- `admin/includes/layout.php` — Software nav item and download icon.
- `admin/index.php` — Software quick link.

## Data model

Each JSON row:

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

Validation rules:

- `name`: required, max 120 characters.
- `platform`: one of `windows`, `android`, `macos`, `linux`, `other`.
- `version`: optional, max 40 characters.
- `size`: optional human-readable label, max 40 characters.
- `description`: optional, max 800 characters.
- `url`: required, absolute `http`/`https` URL, max 1000 characters, no userinfo,
  whitespace or control characters.
- `active`: boolean, defaults to true.
- `id`: 16 lowercase hex characters, generated with `random_bytes(8)`.

## Admin workflow

`admin/software.php`:

- Statistics: total entries, active entries, platforms in use, entries needing attention.
- Add panel: name, platform, version, size, description, download URL, thumbnail, active.
- Edit mode via `?edit=ID`: same fields, current thumbnail preview, remove-thumbnail checkbox.
- List table: thumbnail, name/platform/version, host/URL, size/date, status, actions.
- Actions: edit, move up/down, toggle active, delete (with confirmation).
- Thumbnail upload: JPG/PNG/WebP/GIF, max 2 MB, through the existing validated helper.
- Every POST is CSRF-protected and uses POST/Redirect/GET with flash messages.
- Deleting an entry also deletes its thumbnail.
- Invalid URLs and images are rejected; no executable uploads.

## Public page

`software/index.php`:

- Loads only active and valid entries in admin order.
- Uses the shared catalogue look and interactions.
- Platform filter cards, live search, sort by new/old/name/size.
- Cards show uploaded thumbnail or generic software art, name, platform, version,
  size, description and update date.
- Download button links directly to the external URL, opens in a new tab, and uses
  `rel="noopener noreferrer nofollow"`.
- No login notice, no lock icon, no login redirect.
- Software-specific hero, stats, guide, empty state and closing band.
- SEO: canonical URL, WebPage/Breadcrumb/ItemList JSON-LD, sitemap entry.

## Shared renderer changes

`src/catalog.php`:

- `public_downloads` flag: renders a login-free download note instead of the login note.
- Per-row `action_url`, `action_label`, `action_target`, `action_rel`: use the row action
  before the login-gated one.
- Per-row `title`, `version`, `size_label`, `ext_label`: correct software display.
- Optional `hero_stats` and `sidebar_stats`.
- Optional `join_card` and `closing_actions`, `empty_actions`.
- Generic software preview art.

## Verification

- Run `php -l` on every changed/new PHP file.
- Start `php -S 127.0.0.1:8080` and request `/software/` logged out.
- Confirm no login redirect, lock icon or login-gated download link.
- Test admin create/edit/reorder/toggle/delete and thumbnail upload/replace/remove.
- Test invalid URL and invalid thumbnail rejection.
- Confirm inactive entries disappear from public view.
- Confirm `/officefiles/`, `/offlinefiles/` and `/importantlinks/` still render.
- Review the final diff.
