# PR #285 — corrections delivery state

- Scope: existing PR `#285`, branch `codex/fmonitor-otiz-v2`, change `redesign-otiz-settlements-v2`, contract `OTIZ-SETTLEMENT-V2-001`.
- Audit baseline: Git head `518ce410051aac5c2c0e3a31fb8b86b265f52d46`.
- Reproduced: F01 lost Kss; F02 overlapping prebuilt drafts; F03 immediate/destructive replacement; F04 stale payment export after #257; F05 non-working XLSX/stable-recipient totals; F06 stale evidence binding.
- Corrected: canonical Kss cents; append-only entitlement/admission frontiers with v36→v37 restart-safe backfill; baseline/concurrency guards; separate replacement draft and atomic acceptance; durable #257 generations for accept/export/payment; zero-obligation denial; complete typed/styled XLSX and positive stable-recipient aggregation.
- Gate 3: approved, including supplemental test-delta reviews in `reviews/tests/OTIZ-SETTLEMENT-V2-CORRECTIONS-*`.
- Final review: `APPROVED` for source `a2714f687f828f717c335e7844040bf7f6fa13625e1d497f1b647e6b964da46f`; record `reviews/code/OTIZ-SETTLEMENT-V2-CORRECTIONS-FINAL-REVIEW.md`.
- Focused verification: all 11 mapped acceptance commands GREEN; HTTP qualification, architecture check, migration runner and recovery-forward checks GREEN. Full local `make test` / `make verify` was not run by owner decision.
- Remaining: commit/push the same branch, bind final committed source, exact-source CI, update PR evidence. No merge/deploy/backfill.

## Rereview R01–R08 — 2026-09-27

- Actual starting head: `cdca2ebd25a8c8e252083bf776a98e242899a3b9`; PR `#285` remains open on `codex/fmonitor-otiz-v2`. The prior exact-source CI run `36312063138` is green for that committed head only and does not cover this correction candidate.
- Reproduced on real database seams: R01 allocates a canonical 30→40% increment as `2,437,500 + 4,062,500` cents instead of only `6,500,000` to the installer who performed the new work; R05 lets two distinct authorized actors accept the same right under explicit `REPEATABLE READ`; portfolio year derives from the wrong date. The workbook regression stays GREEN.
- Root-authored correction candidate now specifies/tests replacement completeness and corrected attribution, admission-preserving edits and atomic refresh, zero/no-delta/blocked/UNKNOWN partitioning, erroneous-mark versus financial reversal, late knowledge, exact saved-revision XLSX metadata/styles/stable recipients, canonical plan-year and non-multiplied economy totals.
- Gate 3 rereview: independently `APPROVED` after reproducing all three controlled RED failures and closing test/setup findings.
- Initial implementation source `c15f9f5463df571a6f3cb925f2f065756f01e98e4474344b536bc999c706370a` received supplemental `CHANGES_REQUESTED`; all seven findings were then corrected, including HTTP replacement refresh/new rights, documentary prepared-at cutoff, canonical deduction/empty-draft guards, share scaling and public reversal kind. The supplemental candidate awaits a fresh exact-source binding below.
- A later supplemental review found two further gaps; the current candidate now rejects A→B replacement when copied personal facts would be silently lost, and derives immutable `acceptedBy`/`acceptedAt` from the append-only acceptance event rather than the draft creator.
- Focused verification: all 11 mapped acceptance commands are GREEN; the distinct-actor race is additionally stable across repeated runs. HTTP global-call qualification and `make architecture-check` are GREEN. Independent supplemental/final review is `APPROVED`; the exact source is owned by the active harness binding. Commit/push and new exact-source CI remain.
- Prohibited actions remain unchanged: no full local `make test`/`make verify`, merge, deploy, historical backfill or working-data mutation.
