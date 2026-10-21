# Problem1.pdf implementation — 2026-10-21

Implemented in the local project and deployed to https://amoracare.online on 2026-10-21. The new migration is applied. See `problem1-production-20261021.md` for deployment verification and recovery details.

1. The Child Profiles cards now count all non-deleted child profiles across pages, independently of list filters. Ready for matching also excludes children with an active adoption case. Matching-result totals count distinct child profiles and exclude deleted results. Need-attention totals also cover all pages.
2. Reports now shows **Donors in date range**, counting distinct non-deleted donor profiles with recorded or verified, non-deleted donations in the selected period. Orphan profiles and cancelled donations do not inflate this card. Donation records remain a separate metric. Date boundaries include the full first and last days in report totals, charts, recent donations, and detailed/export queries.
3. Each unconverted matching recommendation has a **Delete Recommendation** button with confirmation, CSRF protection, and an administrator-only DELETE endpoint. Deletion is recoverable through the stored soft-delete record. Converted recommendations cannot be deleted. Runs, children, parents, and adoption cases are preserved. Queued explanation jobs skip deleted recommendations through the model's normal query scope.
4. Structured matching values show readable lists. Empty categories display **Not applicable** for children without special needs and **Not recorded** for children marked as having special needs. Other empty lists display **None**.

Applied database migration: `2026_10_21_000002_add_soft_deletes_to_adoption_matching_results.php`. It ran before activating code that queries matching results and added a nullable `deleted_at` column; existing recommendations remain active.

Validation: 114 PHP tests / 1,292 assertions passed. Five browser checks passed for delete confirmation, CSRF field, DELETE action, and category presentation. Browser fixtures use sample records and are not evaluator testing evidence. Earlier special-needs form and compatibility changes are also deployed; this task does not change scoring bonuses.
