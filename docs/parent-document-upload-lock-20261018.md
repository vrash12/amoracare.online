# Parent document submission lock — 2026-10-18

Deployed to https://amoracare.online. Parents can initially upload a pending parent requirement without a file while its case remains open. Once a file exists, no parent replacement is permitted, including for submitted, under-review, verified, rejected, expired, pending-with-file, and not-required records. Closed, cancelled, finalized, and RACCO-approved cases remain locked.

The document checklist retains Details and Download for submitted files. Its replacement button/dialog and instructions permitting replacement were removed. The application summary and legal-guidance FAQs now direct parents to authorized staff for corrections. Administrator and reviewer document operations were not changed.

The upload endpoint applies the same model permission as the view, preserves ownership/scope checks, and rechecks the record under a database row lock before storing a first submission. A blocked replacement leaves the original file, review status, remarks, and verification metadata intact. Missing-file downloads now direct the parent to staff rather than suggesting a prohibited re-upload.

## Validation

- 31 focused development tests passed with 498 assertions, including the initial upload, repeat upload rejection, every existing-file status, missing-file submission, closed/approved cases, access boundaries, rendered controls, and FAQ responses.
- PHP formatting and whitespace checks passed.
- The five changed production files were prepared by applying the reviewed source diff to copies of the live files, preserving differences in the older Hostinger account installation.
- Hostinger PHP 8.3 syntax checks, view compilation, and staged/deployed rendering checks passed. All five deployed hashes match the release manifest.
- Production-runtime checks used unsaved in-memory models and an array session. No production database records or uploaded files were changed by verification. The preview screenshot is explicitly test data, not an actual evaluator record or authenticated-user capture.
- HTTPS homepage and login returned 200 after deployment. Opening the protected document page in the browser redirected to login; an interactive production parent session was not available.

## Release and recovery

Private release directory: `/home/u330835917/deployments/parent-upload-20261018-1935/`.

The directory contains the manifest, patch, original versions of all five changed files, deployment/verification scripts, and deployment metadata. A brief maintenance window was used for atomic file replacement and rebuilding compiled views. No database migration, dependency update, account change, or environment change was required.

To roll back, enter maintenance mode and restore only the five files listed in `patch/manifest.json` from the matching paths under `original/`, then clear/rebuild Blade views and leave maintenance mode. Preserve the current database and uploaded files. Do not restore an old database for this source-only change.
