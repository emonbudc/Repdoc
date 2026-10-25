# UDC Document Archive

`landing.php` is the Counter-Glass archive page for UDC operators. It uses
`tokens.css`, `shell.css` and `home.css` from the public homepage, so it
follows the same theme as the rest of the site.

## Who can open it

- Administrators (always).
- Approved UDC accounts whose application has **Archive access** enabled.

Admins grant or revoke access from **Admin → Entrepreneur Apps** with the
**Allow archive / Revoke archive** button. The value is stored in
`entrepreneur_apps.archive_access` (migration in `src/bootstrap.php`).
Users without access are redirected to their dashboard with a notice.

## Template files

The blank images live in `Entrepreneur/data/`. Direct web access to that
folder is denied by `Entrepreneur/data/.htaccess`; files are streamed by
`download.php` after the same access check, so a copied link cannot bypass
the page.

If the site is served by nginx instead of Apache, add the equivalent rule to
the server block:

```nginx
location ^~ /Entrepreneur/data/ {
    deny all;
}
```

To add a template, add the file to `Entrepreneur/data/`, add a card to
`$cards` in `landing.php`, and add its exact filename to `$allowed` in
`download.php`.
