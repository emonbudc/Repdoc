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

The tool reads everything from the environment. Nothing is hardcoded and no
portal cookie or secret is committed to this repository.

| Variable | Required | Purpose |
| --- | --- | --- |
| `LAND_API_BASE` | No | API root. Defaults to `https://api.ldtax.gov.bd/api/v1`. |
| `LAND_SESSION_COOKIE` | For searches | Session cookie of a logged-in land-portal account, used only for the upstream request. |
| `LAND_JWT_SECRET` | For details | Enables the optional holding-detail lookup. Without it, search still returns the holding list. |
| `LAND_MAX_RESULTS` | No | Caps returned rows (default 25, max 50). |

Set these in the hosting config (or local environment) — never in a committed
file. Live searches need `LAND_SESSION_COOKIE`; leave `LAND_JWT_SECRET` empty
to show the holding list without owner/land details.

## Data and limits

- The API is external and may be slow or unavailable; the client uses short
  timeouts and shows a visible error instead of a blank result.
- This is an independent tools page, not a government portal. Official work
  should use `land.gov.bd`.
