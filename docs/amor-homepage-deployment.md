# AMOR Village homepage in AmoraCare

The WordPress homepage design is now a standalone Laravel Blade view. It uses the existing `/` route, real photographs, local fonts, wavy section dividers, organic photo frames and a browser accessibility panel. The AmoraCare section connects to the existing parent application, login and dashboard routes. Authenticated users retain the original multi-guard selection and CSRF-protected targeted logout.

The WordPress sample-news cards were replaced with working AmoraCare entry points. Other WordPress pages are represented by homepage sections; WordPress, Elementor and their database are not dependencies of this Laravel page.

## Local preview

From the Laravel app directory, run `php artisan serve --host=127.0.0.1 --port=8090 --no-reload`, then open http://127.0.0.1:8090/ . The preview is local to this computer. No WordPress sign-in is needed to see the homepage.

## What to deploy to the existing Hostinger Laravel installation

The prepared ZIP contains only these files and this guide, with paths relative to the Laravel app root:

- `resources/views/welcome.blade.php`
- `resources/views/partials/amor-brand.blade.php`
- `resources/views/partials/amor-accessibility.blade.php`
- `public/amor-village/` (styles, scripts, local font/license, decorative SVGs and five WebP photographs)

The existing `resources/views/partials/legal-links.blade.php`, route names and guard configuration are reused. They already exist in the inspected local application. Confirm the deployed revision also has them before applying the patch.

1. Back up the current deployed `welcome.blade.php`. Confirm the actual Laravel root and the public document root in the existing Hostinger installation. The remote path and active deployment configuration have not been verified.
2. Upload the new `public/amor-village/` directory first, then the two partials, and replace `welcome.blade.php` last. Use an atomic release if the existing deployment supports one.
3. If Hostinger uses a separate `public_html` directory for Laravel's public files, place the contents of `public/amor-village/` in `public_html/amor-village/`. Keep Blade templates in the private Laravel `resources/views` directory. Do not put the private Laravel application or the complete ZIP in a public directory.
4. From the deployed Laravel root, refresh compiled templates using `php artisan view:cache` with the same PHP executable used by the site. Laravel documents its `public/index.php` entry point and view caching in the [deployment guide](https://laravel.com/framework/docs/13.x/deployment).
5. Open the live homepage in a private window. Confirm all photos and styles load over HTTPS, then check the application link, login, the relevant existing dashboard, privacy/terms links and mobile menu. Check the active session's logout through the existing workflow when appropriate.

This patch needs no database migration, WordPress installation, npm build or Composer dependency change. Preserve the production `.env`, application key, database, uploaded documents, `vendor`, routes, `index.php` and `.htaccess`. Do not run this repository's `composer setup` for this update: that script generates a key and executes migrations.

The existing locked dependencies require **PHP 8.4 or newer**, including Endroid QR Code 6.1.3 and Symfony 8. This requirement already existed before the homepage change. The homepage adds no dependencies. Its `asset()` and `route()` calls use the deployed Laravel URL configuration; no local WordPress URLs are embedded.

## Rollback

Restore the backed-up deployed `welcome.blade.php`, then run `php artisan view:cache` again. The new assets and partials can remain unused. No database rollback is involved. The pre-edit local view was saved outside the public directory at `C:\Users\MAURICIO\.codex\amor-local\laravel-homepage-backup\welcome-before-amor-port.blade.php`.

## Photography

Four photographs were supplied by the project owner and carried over from the WordPress design:

| Asset | Original supplied filename |
| --- | --- |
| `amor-community-support.webp` | `319813064_471286441754352_9014741760705717011_n.jpg` |
| `amor-shared-activity.webp` | `774515288_1463852372456657_5734666126283338732_n.jpg` |
| `amor-community-gathering.webp` | `774407278_1463855545789673_6101532345111390563_n.jpg` |
| `amor-shared-moments.webp` | `773735964_1463852162456678_617985224333077577_n.jpg` |

`children-playing-manila.webp` is the previously selected [photograph by Zachary Angeles on Pexels](https://www.pexels.com/photo/asia-fujifilm-street-photo-street-photography-27848701/), with a visible photographer credit and [license link](https://www.pexels.com/license/). Its caption distinguishes children in Manila from AMOR residents. No AI-generated photographs are referenced by the new homepage. The Nunito Sans font license is bundled with the font.

## Verification

Blade compilation and JavaScript syntax checks passed. Existing homepage, public legal/application and independent multi-role session tests passed (5 tests, 53 assertions). All 13 public assets and the homepage returned HTTP 200 from the local Laravel server. Desktop and 390px mobile screenshots confirmed the real photography, section waves, frames and responsive navigation. No broken internal anchors were found in the rendered page.

Additional homepage authentication tests passed (9 tests, 86 assertions), covering guests, each supported guard, preferred sessions and fallback behavior without creating database records. In total, 14 relevant tests and 139 assertions passed. PHP formatting and whitespace checks passed. After recovering the browser connection, 200% text at 320px showed no horizontal page overflow; high contrast produced black text on white surfaces; reset restored the default size; the statement link opened the disclosure and focused its summary; and Escape closed the accessibility dialog while leaving an open mobile menu intact. These are development checks, not a claim of audited accessibility conformance. Production deployment has not been performed.
