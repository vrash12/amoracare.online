# PROBLEM.pdf items 1–3 — 2026-10-21

Implemented and deployed the first three issues from the user's `Downloads/PROBLEM.pdf` to https://amoracare.online.

## Resulting behavior

1. Parent application timeline: progress uses the eight normal workflow stages. Draft starts at 0%; the final stage shows **Completed** and 100%. Closed and cancelled are separate stopped outcomes and show no completion percentage. Unknown statuses also receive no guessed percentage. The existing database value `finalized` is retained and labelled **Completed** in the administrator's status choices and parent view; no stored case statuses were rewritten. Progress represents the recorded stage, not elapsed time or legal approval.
2. Parent AI guidance: ordered lists retain each source number when explanatory paragraphs, blank lines, or bullet lists split the response. Intentional restarts are retained. HTML escaping and streaming behavior remain intact.
3. Administrator timestamps: document verification, case notes (including the partial view), parent account/matching timestamps, and user creation/login timestamps display in Asia/Manila with a PHT label. UTC storage is unchanged, and conversions use Carbon copies. Existing audit-log and matching-run views already converted to the display timezone. Calendar-only dates such as birth dates remain date-only.

## Validation

- Full development PHP suite: 88 tests, 1,091 assertions passed.
- JavaScript formatter: four tests passed for interrupted lists, bullet lists, intentional restarts, HTML escaping, and streaming.
- Production PHP 8.3.35 / installed Laravel compatibility checks passed in staging and after deployment: all ten case statuses rendered, completed progress was 100%, cancelled/closed progress was absent, AI list-number attributes were present, and admin user/document/note timestamps converted correctly across midnight without mutating storage values.
- Browser previews rendered the production templates with unsaved test models. Confirmed Completed/100%, cancelled/no percentage, numbered answers 1–2–3, and PHT timestamps. These previews are test fixtures, not new evaluator or adoption records.
- Production views compiled successfully; public homepage and login returned HTTP 200. Protected application and admin case routes returned the expected HTTP 302 for an unauthenticated request.
- No database records, credentials, environment settings, or uploaded documents were changed. No migration was needed.

## Deployment

Private release: `/home/u330835917/deployments/problem-1-3-20261021/`.

The release contains the ten-file patch, SHA-256 manifest, original files, verification/deployment scripts, deployment log, and deployed manifest. A predeployment hash check prevents overwriting independently changed files. Deployment uses temporary files and renames, a brief maintenance window, view-cache rebuilding, postdeployment verification, and automatic source rollback on failure. The older production session guard and omitted OTP/inactivity features were preserved.

Local release preparation and comparison: `tmp/prepare_problem_release.py` and `tmp/problem-production-review.diff` relative to the repository's parent workspace. Private verification previews and screenshots are in `tmp/problem-preview/`.
