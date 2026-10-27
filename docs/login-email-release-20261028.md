# Password sign-in and reusable deleted-account emails

User requirement: remove the sign-in OTP step, allow email reuse after deletion, and reject duplicates of existing accounts (including inactive and pending accounts).

## Behavior

- Active users sign in with email and password. Account approval, role access, temporary-password changes, terms acceptance, and login throttling remain enforced.
- Local public registration retains its separate email verification. Sign-in does not falsely set `email_verified_at`.
- Emails are trimmed and stored in lowercase. User and parent creation/editing, plus local public registration, reject existing addresses case-insensitively. The database unique index remains the concurrency safeguard.
- Soft deletion preserves the account ID and related history, archives its original email in a hidden `deleted_email` column, replaces its unique login address with an internal tombstone, and clears its remember token in one database update.
- Recreating an account uses a new ID. Restoring an old account is rejected if another account now owns its original email.
- The migration releases previously deleted accounts' emails and normalizes existing accounts. It aborts if normalization would merge existing addresses. Rolling the migration back is refused when an archived email has been reused.

## Validation

- Full local suite: 122 tests passed, 1,352 assertions.
- Focused checks cover password login for all three roles, account setup, duplicate creation/editing, deletion/recreation, restoration, pending/inactive rejection, public-registration verification, and migration backfill.
- Production staging verification: 29 assertions against an isolated SQLite memory database using the live PHP/runtime and the staged patch. No real accounts are created by this check.
- Production preflight: 72 account rows, including 16 soft-deleted accounts; no normalized collisions among non-deleted accounts; unique email index present.

## Production compatibility

Production uses a single `web` authentication guard and already has password-only login. The repository uses role-specific guards and previously required sign-in OTP. Copying all local authentication files would introduce unrelated changes. `scripts/releases/login-email/prepare.py` applies a narrow, checked patch to captured live originals while deploying the shared email rule, deletion trait, and migration unchanged.

The production patch normalizes login emails, preserves its existing password login, refreshes the password-session marker, adds the same five-attempt-per-minute login limit, updates stale OTP instructions, and applies the email changes to both administrative account forms.

The private release directory holds original file backups and a database snapshot. Deployment checks source hashes, verifies the staged patch, enters maintenance, runs only the targeted migration, replaces the reviewed files, clears routes/views, compiles views, verifies deployed behavior, compares existing records against the snapshot, and brings the application back online. No environment or vendor files are deployed.

## Completed production deployment

- Source commit: `5ef5c1a` (pushed to `origin/main`).
- Private release: `/home/u330835917/deployments/login-email-20261028`.
- Targeted migration completed successfully; 16 previously deleted account emails were released.
- All ten deployed files passed PHP syntax checks and manifest hash verification. Staged and deployed behavior each passed 29 isolated assertions using the server runtime.
- Database comparison confirmed all 72 account rows remain. Existing account changes were limited to the intended email normalization/archive and deleted-account remember-token clearing. Child, parent profile, case, document, matching, and donation data matched the pre-deployment snapshot exactly.
- Views compiled, route/view caches refreshed, queue restart signaled, and maintenance mode ended successfully.
- Public HTTPS checks: home and login returned HTTP 200; login contains email/password inputs and no OTP input.
- Verification used isolated test records, not real users' credentials. Backups and the deployment manifest remain outside the public web directory.
