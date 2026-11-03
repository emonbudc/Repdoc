# Land record search (`tools/land/`)

Search-only integration of [emonbaluahat/land](https://github.com/emonbaluahat/land).
It keeps the upstream lookup flow — division → district → upazila → mouja,
then khôtian/holding search — and deliberately leaves out the upstream
homepage, advertising, ad-block guard, admin panel, and print templates.

## What it does

- Cascading location selects backed by `api.ldtax.gov.bd`.
- Khôtian search against the land portal's holding-list endpoint.
- Optional per-holding owner/land details.
- Result cards with Bengali numerals, empty/error states, and a link to the
  official `land.gov.bd` portal.

The page requires an approved operator (UDC) or admin login. It is linked from
the operator panel and the admin tools directory, and is not listed on the
public homepage.

## Configuration

Search works with no configuration. The public location endpoint needs no
key, and the representative endpoint plus detail lookup use the same defaults
as the upstream project. Environment variables are only overrides.

| Variable | Required | Purpose |
| --- | --- | --- |
| `LAND_API_BASE` | No | API root. Defaults to `https://api.ldtax.gov.bd/api/v1`. |
| `LAND_SESSION_COOKIE` | No | Overrides the built-in portal session cookie. |
| `LAND_JWT_SECRET` | No | Overrides the built-in detail-lookup secret. |
| `LAND_MAX_RESULTS` | No | Caps returned rows (default 25, max 50). |

The representative endpoint is tried first; when it reports `not found` the
public `holding-list-by-location` endpoint is checked, and its message is
shown if the portal itself is unavailable.

## Data and limits

- The API is external and may be slow or unavailable; the client uses short
  timeouts and shows a visible error instead of a blank result.
- This is an independent tools page, not a government portal. Official work
  should use `land.gov.bd`.
