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
