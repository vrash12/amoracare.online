# Problem1 and special-needs matching deployment — 2026-10-21

Pushed source commit `0929816d592f14871cb733f43da6a13c53240082` to `origin/main` and deployed the relevant 13-file release to https://amoracare.online.

The release adds explicit No-special-needs/Has-special-needs choices, clears categories when No is saved, excludes special-needs children from parents who decline them, and rechecks compatibility when converting an old recommendation. It also deploys overall child-profile totals, date-scoped donor counts, administrator-only recommendation deletion, and readable category values from Problem1.pdf. Existing special-needs scoring bonuses are retained.

## Verification

- Development suite: 114 PHP tests and 1,292 assertions passed; five browser checks passed.
- Hostinger PHP 8.3.35 / Laravel 13.21.1: all 23 release checks passed before and after activation. These checks use a dedicated SQLite memory database and unsaved test accounts, including actual controller saves, preference combinations, totals across pages/filters, recoverable deletion, converted-record protection, non-admin rejection, and report rendering.
- Applied only migration `2026_10_21_000002_add_soft_deletes_to_adoption_matching_results`; older pending baseline migrations were not executed against the existing installation.
- Source hashes match the reviewed deployment manifest. The installed login/session routes were preserved; only the new recommendation DELETE route was added to the production route file.
- Exact database snapshots before and after deployment matched all pre-existing fields and records in the ten checked business tables. No child, user, case, donation, profile, or recommendation was edited or deleted by deployment or verification. The nullable deletion column and its migration entry are the only intended database additions.
- A read-only check against live records confirmed child totals of 55 overall, 37 ready, 44 with matching results, and six needing attention; the current report date range showed eight distinct contributing donors. These are point-in-time checks, not fixed application values.
- Public homepage and login returned HTTP 200. Child Profiles, Reports, and Matching returned HTTP 302 for anonymous requests. The new totals, donor label, deletion control, route, and migration were verified against the live installation. Maintenance mode is off, compiled views were rebuilt, and queue workers received a restart signal.

## Release and recovery

Private release directory: `/home/u330835917/deployments/problem1-20261021-034372/`.

It retains the release archive, SHA-256 manifest, original source files, private database snapshot, deployment scripts/log, and deployment metadata. The maintenance window used temporary-file renames and automatic source rollback on failure.

To roll back source, enter maintenance mode, restore only the manifest-listed files from `original/`, clear route and view caches, restart queue workers, and leave maintenance mode. Preserve the database and uploaded files. The additive nullable deletion column can remain; do not roll back it or restore old database snapshots after users have begun deleting recommendations, since doing so could reactivate deleted recommendations.

The production route file intentionally retains the site's previously deployed login setup rather than introducing unrelated local authentication/application routes.
