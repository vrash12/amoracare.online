# Account workflow deployment — 2026-10-11

Deployed the requested administrator, prospective-parent, and reviewer fixes to https://amoracare.online on Hostinger. The feature source is commit `9031841`. `scripts/build_hostinger_account_release.py` records the compatibility adjustments used for the older installation.

## Deployed behavior

- Adoption case forms search child and prospective-parent choices; matching navigation uses compact controls.
- Administrator user forms use required first/last names, optional middle name and suffix, capitalization, and required-field markers.
- New administrator-created and administrator-reset passwords are temporary. Each user can change their own password. Password changes invalidate stale sessions.
- Parent document summaries and rows use visible action/review status icons. The dashboard badge “Secure Applicant Portal” was removed.
- All three portals provide password and terms links. The terms include reviewer responsibilities and use version `2026-10-11`.
- Deleted cases are excluded from reviewer lists and counts. Following an old authorized-case link redirects to the list with an explanation; attempts to submit reviews, notes, or document decisions or download documents for that case are blocked.

## Compatibility scope

The deployed Git baseline was `09e4f87`, with separately installed homepage and FAQ patches. Its runtime is PHP 8.3.35 / Laravel 13.21.1, and it uses the `web` session guard. No Brevo API key or sender is configured. The release therefore retains the live login controller, existing web guard, homepage, FAQ, Composer files, and dependencies. It does not install the separate OTP, public application/QR, multi-guard login, or automatic account-inactivity features from the newer development application.

The generated 37-file patch includes the account controller, model/name additions, account/terms routes, portal views/assets, and reviewer deletion fixes. The generator adapts middleware and route/config files for the legacy guard and omits UI claims about undeployed OTP/inactivity behavior.

The live database was imported without baseline migration history. Only these reviewed additive migrations were run, using explicit `--path` arguments:

```
php artisan migrate --force \
  --path=database/migrations/2026_09_26_000000_add_account_setup_to_users_table.php \
  --path=database/migrations/2026_09_26_000001_add_name_parts_to_users_table.php
```

They add eight user fields. Existing names are not split or rewritten. Existing passwords are not reset or guessed to be temporary. New accounts and future administrator password resets set the temporary-password flag. Existing users must accept the current terms on their next portal access.

Do not run unrestricted migrations, `migrate:fresh`, seeders, or `composer setup` against this installation. Retain the production application key, database credentials, storage, and dependencies. Production environment flags were corrected to `APP_ENV=production` and `APP_DEBUG=false`; these were the only changed environment values.

## Backup and deployment

Before deployment, complete private application and database backups were saved at:

`/home/u330835917/backups/amoracare-20261011-044003/`

The private release directory is:

`/home/u330835917/deployments/amoracare-account-20261011/`

It contains the release archive, SHA-256 manifest, staging installation, original versions of affected files, deployment/rollback scripts, and verification results. These directories are outside the public document root. SSH was already usable; hosting SSH settings were not changed.

The release was validated in a private staging copy with the live dependencies. During a brief maintenance window, each file was copied to a temporary destination and renamed, the two migrations ran, and configuration, route, and view caches were rebuilt. Maintenance mode was then disabled. `public/js` was explicitly given mode `755`, and public assets use `644`, so the web server can serve the new search script.

## Verification

- Development suite: 68 tests / 941 assertions passed before deployment.
- Legacy-configuration fixture: 19 focused tests / 239 assertions passed. These exercise password/setup/terms enforcement, stale-session rejection, name creation/reset, and deleted/orphaned reviewer cases using the production web guard.
- All 37 uploaded files match their SHA-256 manifest; all PHP files passed syntax checks with the Hostinger PHP executable.
- Production views and routes compiled successfully. Fourteen affected GET routes rendered HTTP 200 through the installed Laravel kernel for all three roles. These checks used an in-memory session driver and temporary model attributes inside a transaction that was rolled back; no passwords or terms acceptance records were changed.
- Public HTTPS homepage, login, terms, privacy, dashboard CSS, legal CSS, and child-search JavaScript returned HTTP 200. The three changed public assets match the release hashes.
- Core record counts are unchanged. All 22 files in `storage/app` match their predeployment hashes. Homepage and FAQ code and Composer files match their backups. The application is live with debug disabled.

The verification did not change real account passwords, accept terms on anyone’s behalf, or delete real cases. Interactive password changes and case-deletion failure scenarios were tested in the disposable local fixture.

## Rollback

Run from the verified private Laravel root, with `AMORA_RELEASE_DIR` set to the release directory above:

```
php artisan down --retry=60
php "$AMORA_RELEASE_DIR/rollback.php"
php artisan optimize:clear
php artisan up
```

The rollback script restores the affected source and environment files only. It retains the additive database fields and does not restore an older database over current records or replace uploaded documents. The complete database backup is available for a separately assessed recovery if needed.
