# Page: Record management (manage.php) — deltas over MASTER

- Three underline tabs: add / edit+delete / search+replace. Same topbar,
  breadcrumb, footer, tokens as index.
- Add/edit forms use exactly the record columns the manage API accepts
  (registration_number, name/name_en, father/mother + _en, dates, gender,
  ward, birth_place/place_of_birth_en, addresses, registration/certificate
  dates, page_number, serial, source_file, status). No new fields.
- Destructive actions (delete, replace-all) require the existing confirm
  modals; confirm headers are flat danger/warning washes, never gradients.
- Replace preview shows old → new per field with strikethrough, as today.
- Toasts reuse the global flat toast component.
