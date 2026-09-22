# Production FAQ repair — 2026-09-17

The live Hostinger installation had an older `ParentAiGuidanceController` with no predefined FAQ handling. Its chatbot sent every question to the separate Django AI service.

## Applied changes

- Deployed `app/Http/Controllers/Parent/ParentAiGuidanceController.php` and `resources/views/parent/ai/index.blade.php` through authenticated Hostinger File Manager.
- Added 14 clickable saved FAQs, exact matching for the three reported questions, and a legacy `/chat` fallback for FAQ button requests. No route deployment or migration was required.
- Scoped chat history to each user and added safe AI-unavailable responses without exposing upstream error bodies or private context.
- Preserved environment settings, accounts, database, uploads, dependencies, and routes.

## Rollback

Original files were copied and their presence verified before replacement:

- `app/Http/Controllers/Parent/faq-backup-20260917/ParentAiGuidanceController.php`
- `resources/views/parent/ai/faq-backup-20260917/index.blade.php`

Restore the corresponding file from each private backup directory if necessary.

## Verification

- Local full suite: 47 tests, 673 assertions passed.
- Focused FAQ/privacy suite: 15 tests, 398 assertions passed.
- Controller syntax, rendered Blade, both inline JavaScript scripts, and whitespace checks passed.
- Uploaded view content matched the local release after newline normalization.
- In the parent session provided by the user, production successfully returned saved answers for domestic adoption requirements, the Home Study Report, and the exact typed question about browsing child profiles or matching rankings.
- Production homepage and login remained accessible.

## Separate unresolved AI service failure

The production "Need more help? Ask AI" request still returned the safe AI-unavailable message. The configured Google Cloud Run endpoint also returned `500 Server Error` when opened directly. Saved FAQs are working; this does not establish recovery of AI-generated replies. Cloud Run logs and deployment health must be inspected separately. Local gcloud credentials were expired, so the CLI could not retrieve service diagnostics.
