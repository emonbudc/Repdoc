Repdoc office files — how to publish a pack
============================================

Public page : officefiles/  (auto-lists everything below)
Download    : officefiles/download.php?type=word&file=NAME (login-gated)

Folders (extensions allowed):
  word/   →  .doc  .docx  .rtf  .odt  .zip
  excel/  →  .xls  .xlsx  .csv  .zip
  ppt/    →  .ppt  .pptx  .zip

How to add a file:
  1. Upload the file into the matching folder, e.g. officefiles/word/application-form.docx
  2. Keep the filename simple: a-z, 0-9, dash, dot (no spaces needed, Bangla ok)
  3. No code change needed — the page lists it at once (newest first),
     with size + date + Word/Excel/PowerPoint badge, description, search and filter included.

Descriptions & thumbnails (admin):
  - Open admin/files.php -> File Packs.
  - Use "Add description" on any file row, or fill the optional description
    and 4:3 thumbnail fields while uploading.
  - Descriptions also drive the PSD / AI / PLP rows on the same public page.
  - descriptions.json (next to this README) ships the Bengali copy that each
    bundled file shows until an admin saves a DB description; key format is
    "type:filename", e.g. "word:Application-Bangla-Asad.doc".

Notes:
  - Listing is public, download requires an approved operator login.
  - Empty folders show an honest "coming soon" state — never a broken list.
  - Do not put .php or other executables here; only the extensions above are served.
