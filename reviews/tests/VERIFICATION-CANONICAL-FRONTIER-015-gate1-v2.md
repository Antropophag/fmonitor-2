# Independent Gate 1 amendment — VERIFICATION-CANONICAL-FRONTIER-015

- Verdict: **APPROVED**
- Reviewer: `/root/original_gate1_v3`, separately tasked agent; not an author of the reviewed amendment or workforce test changes.
- Date: 2026-09-07
- Reviewed HEAD: `29397bd27e3af856e626908da722f474a272fe39`.
- Specification: version 0.2, SHA-256 `a3c4ff2baa3e5c5c469c29d2fe43bc3d60cdc41423463366a8ce230e98fc9b39`.
- OpenSpec design SHA-256: `8ebd5d07d3ddf2f78030a15daba93e1ecf77a75f9bf0022d7734bd0af4b63f84`.
- Previous approval: `VERIFICATION-CANONICAL-FRONTIER-015-gate1.md` in this directory.

## Amendment and disposition

No blocking findings. The new section makes one inherited metadata transition explicit for the full v1–v4/partial-v5 to v15 recovery: the old capability CHECK becomes `ck_fm2_process_user_capability_v5`, retaining its four existing literals and adding exactly `assignment_order.original.upload` and `assignment_order.original.correct`.

This expectation is supported independently by the already-reviewed original-audit13 approval and the existing test-only `ProductionOriginalAuditCatalogV13::checks()` literal. That oracle specifies the exact six-value allowlist and constraint name. The amendment does not infer expected values from actual migration output, permit a new grant, or change production behavior.

The preservation rule remains strict. The verifier must first prove that the old exact clause occurs once in the relevant predecessor DDL, then substitute only that clause in the expected snapshot. It must compare the complete resulting expected SHOW CREATE TABLE and every original row against the post-recovery snapshot. No table may be excluded, and no generic normalization or removal of constraints is allowed. Consequently, unrelated column/index/constraint/default/counter changes and row mutations still fail the original comparison.

Read-only inspection of the current workforce recovery confirms its blanket per-table before/after comparison is the precise assertion requiring this clarification. Completed-repeat and early-conflict comparisons remain unchanged. All other bounds and Gate 1 assessments in the prior record remain applicable.

## Review limits

Read the amended specification and design, the existing test-only CHECK oracle and the workforce recovery/preservation code. Reused the prior independently inspected approval evidence. Only this review record was added; no tests, production code or fixtures were changed or executed.

Gate 1 v0.2 is **APPROVED** for this narrow amendment. The new test expectation still requires independent Gate 3 review and the existing native GREEN/Gate 5/full-verification obligations. This approval does not declare the workforce recovery test passing.
