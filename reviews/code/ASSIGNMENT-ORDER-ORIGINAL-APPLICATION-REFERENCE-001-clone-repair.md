# Code review: original-reference cloned issuer repair

- Reviewer: `/root/original_gate5`, independently tasked agent; not implementation/test author.
- Implementation author: root implementation agent.
- Reviewed source: `45d7a668170e49e9edbba13977513a4c4fcd7e7f`.
- Specification: unchanged ASSIGNMENT-ORDER-ORIGINAL-APPLICATION-REFERENCE-001 v0.1, issuing-reader-instance invariant.
- Approved test review: `reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-APPLICATION-REFERENCE-001-clone.md`.
- Verdict: `APPROVED`; the P2 in the preserved clone-finding record is closed.

## Findings

No remaining blocking findings in this bounded correction. The complete production diff is a comment and public `__clone()` assigning a new empty WeakMap to the cloned reader. It isolates both existing and future issuance from the original map while preserving the shared borrowed native connection/source. Original reader references remain registered in the original map; a clone can issue its own references, but foreign references fail the existing unavailable check before SQL. Cloning adds no transaction, connection, configuration, filesystem or domain side effect.

The implementation exactly addresses both clone orderings identified in the prior finding. It does not weaken scope/lineage validation or make all guards unavailable. Repeated cloning also receives a fresh map through the same handler. Existing original-reader transaction and locking behavior is unchanged.

## Verification

Read the native issuer test and independently verified its SHA-256 is byte-identical to the Gate 3 approved draft: `df73f74e28687686f118fe214212c5734c756b4a0ff71370fb83b6647bf2721f`. It covers clone-before/after issuance at prefixes 0/25, both own-reference positive controls, both foreign-reference directions, metadata equality, caller transaction/sentinel preservation, rollback and unchanged facts/private files. Root adopted it only after the frozen full run became terminal.

Inspected `clone-green.log`: four healthy setup/PASS/cleanup cases. Inspected `clone-reference-regression.log`: all 14 original reference cases PASS with 14 cleanups and both real correction workers observed blocked by the guard. Native executions belong to root; this reviewer did not repeat broad DB suites. Independently ran lint for both changed PHP files and `git diff --check 45d7a66^ 45d7a66`: PASS.

Evidence root: `/Users/antropophag/.local/state/fmonitor2-verification/original-reference-20260907`. The previous exact-source full run at `18916ae` had architecture seven-rule PASS and ended with the known protected bootstrap/E2E failures. It predates this repair and is not represented as a successful full run of `45d7a66`.

## Scope

The cloned-issuer defect is closed; the prior CHANGES_REQUESTED record and original review history remain preserved. No parent application, HTTP, protected E2E, full `VERIFY_OK` or launch completion is implied. Only this review record was written; no production/test edits or commit were made by the reviewer.
