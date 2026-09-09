# Gate 5 code review: OTIZ-SETTLEMENT-001 complete delivery

- Date: `2026-09-10`
- Reviewer: separately tasked agent `/root/final_review`; did not author the specification, tests, or implementation
- Reviewed exact candidate: `5103cd7b9c3cd1f474f104e77ba6acb24b73b2fc`
- Baseline: `origin/main` at `a6bafb1d4b6c2c9de524dd6637df914034eee9a2`
- Specification: `specs/OTIZ-SETTLEMENT-001.md` and `openspec/changes/otiz-settlement-owner/specs/otiz/settlement-owner/spec.md`
- Consolidated correction Gate 3: `reviews/tests/OTIZ-SETTLEMENT-001-final-corrections.md`
- Verdict: **APPROVED**

## Standards

No findings.

The integrated diff preserves the repository's required ownership boundary:
settlement money and its audit events are appended only by
`MariaDbOtizSettlement` behind `OtizSettlement`; Yii and rapid are adapters.
The removed rapid transaction/closure/event helpers are not replaced by another
HTTP writer. Read projections and XLSX/navigation routes issue reads only.
Authorization is current and fail-closed before receipt replay, runtime commands
operate with DML-only credentials, and no historical v22/v23 recovery profile is
modified. The compact formatting and temporary parallel read adapters match the
existing migration code style; duplicated export presentation is bounded by the
documented strangler transition and carries no financial calculation ownership.

## Spec

No findings.

The canonical owner implements the three specified operations with strict input
validation, current `otiz.manage` admission, actor/operation fingerprint receipts,
stable exact replay, conflict rejection, durable successful no-op, append-only
reversal, and exact error outcomes. Snapshot validation and sorted per-object
locks execute in one owned transaction; global signed closure totals enforce the
legacy-object budget across snapshots without subtracting `closed_before_cents`
twice. Concurrent bulk/reversal behavior and rollback preserve atomicity.

Schema v24 adds exactly the lock and receipt tables, is registered in the
canonical catalogue, and is reflected in demo/readiness/private-prefix setup.
Current recovery expects 71 tables and 39 AUTO_INCREMENT entries; exact v22/v23
profiles remain immutable and their rehearsals forward-migrate only after restore.
The runtime image installs the shared locked Composer production dependencies and
required mysqli, PDO MySQL, and pcntl extensions while retaining nginx/FPM,
storage, and asset behavior.

Yii and rapid submit to the same owner. All retained forms supply distinct
canonical operation UUIDs, while missing or malformed values are rejected rather
than replaced server-side. The Yii snapshot preserves global-budget summaries,
trace/exclusion meaning, allocations/issues, all closure components, linked
reversals, working financial navigation, and real XLSX export. These reads do not
introduce publication actions or write facts.

## Verification evidence reviewed

The exact candidate plan check returned `CHANGE_VERIFICATION_OK`. The delivery
record reports GREEN focused owner/schema/concurrency, authorization, Yii HTTP,
native browser, calculation/publication/register, demo/private-prefix, exact
v22/v23 recovery, runtime packaging/compatibility, architecture, and dependency
rendering checks. The final correction at `c1a7c614` passed its approved HTTP,
real-browser navigation/download, and packaged-runtime tests, plus architecture
PASS 7 and the isolated compatibility harness. Earlier bounded Gate 5 approvals
remain applicable to unchanged core (`13ebd7ec`), recovery (`bd709838`), and
deployment policy (`64dddf3b`) bytes. Full exact-source CI remains the required
post-review integration gate and is not represented as already complete.

## Required changes

None.

Summary: Standards 0 findings; Spec 0 findings.

## Verification-catalogue carry-forward

The approval carries forward to `275523761032dc742ee8809403a3a3c919562977`.
After the reviewed production candidate and review records, this commit changes
only `tests/Verification/verification_ci_001_test.py`: its exact expected E2E
list now includes the already registered
`yii2_otiz_settlement_browser_001_test.php` and
`runtime_settlement_compatibility_001_test.php`. Both entries already exist in
`tools/verification/suites.tsv` and are classified as E2E in
`tools/verification/categories.json`; no entry is removed or reordered. The
previously failing method is GREEN, the complete development-setup suite reports
8 PASS, and the regenerated exact-head verification plan reports
`CHANGE_VERIFICATION_OK`. This setup-only correction introduces no production,
specification, or business-test change. Verdict remains **APPROVED**, with no
additional finding.

## PR CI correction carry-forward

The approval further carries forward through `1e6339f7bca51826aed36332faee463c31b6c2d7`.
The failed PR run `34412982874` had one primary regression only: the calendar
verifier still expected the previous terminal catalogue v23. Commit `f748c3f4`
changes that fixture's exact expectation to v24 and appends migration 24 while
preserving its calendar data, DOM, determinism, and overflow assertions; its
focused execution is GREEN.

The required-plan regeneration then exposed a planner defect for that already
registered verifier outside `tests/`. The root-authored test at `d3b2c67d` is
independently **APPROVED at Gate 3**: through the public CLI it requires the
inventory-registered rapid verifier exactly once with its PHP argv and focused
rationale, and separately proves an arbitrary unregistered sibling script is
still rejected. Its RED failed at the intended `test path outside tests` guard
after valid setup.

The minimal implementation at `1e6339f7` permits an outside-`tests/` path only at
the two call sites where membership in the loaded verification inventory has
already been established. Acceptance mappings and boundary tests retain the
original path restriction; repository-path, runtime, inventory-category, argv,
and fail-closed boundary validation remain unchanged. All 12 planner tests pass,
including the new positive and negative cases. The full PR plan against
`origin/main` was regenerated at this exact head and independently returns
`CHANGE_VERIFICATION_OK`. No settlement, financial, HTTP, schema, recovery, or
runtime packaging byte changed. Verdict remains **APPROVED**, with no additional
finding; a new exact-head full CI remains required.
