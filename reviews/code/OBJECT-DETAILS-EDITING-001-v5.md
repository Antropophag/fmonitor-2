# Code review: OBJECT-DETAILS-EDITING-001 — v5

- Reviewer: independent reviewer `/root/gate3_review`
- Specification/test author: root delivery agent
- Implementation author: separate executor
- Reviewed source: base `9523bca002eec559db14ecf946d3c297989cb18e` plus snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T175940Z-ec1ac34f2f/snapshot/source.patch`, SHA-256 `ba2172821aedb891fdb76985db5ad0d0892400f60ff30365be80fef6269a0ed1` (candidate `fd2f06dd0e9ef76e452654bc029ebdc3fca35dca40998cc371a215de8a202247`)
- Prior review: `reviews/code/OBJECT-DETAILS-EDITING-001-v4.md`; Gate 3 v7 is `CHANGES_REQUESTED`
- Verification: six mapped exact-source tests GREEN, including expanded ERP-candidate/Bitrix and repeat/incompatible-index paths; full exact-source CI/import GREEN absent
- Verdict: `CHANGES_REQUESTED`

## Prior v4 disposition

1. **Missing test protection — PARTIALLY FIXED.** Effective projection, ERP candidates, Bitrix object lookup, source preservation, exact migration repeat and one incompatible index are now protected. Import, queue/card, ERP matching, OTIZ, schema lifecycle/recovery, concurrency/history and full HTTP/browser behavior remain unprotected.
2. **Reference display provenance — OPEN, MEDIUM.** No canonical evidence was added for raw-code display labels `7` and `9`.
3. **CI/import — OPEN.** Authoritative exact-source CI remains pending.

## Findings

1. **HIGH — Gate 3 remains materially incomplete.** The implementation's most sensitive guarantees—real concurrency/idempotency/history, import preservation, complete consumer propagation, recovery continuity and rejection/security UI paths—still lack specification-level tests. Gate 3 v7 enumerates the required cases; final approval cannot precede their independent review.
2. **MEDIUM — reference display truth remains unverified.** Confirm `pittype=7` and `pitmaterial=9` labels against canonical reference evidence or represent them truthfully as raw/unknown display rather than implying resolved labels.
3. **MEDIUM — full exact-source verification is incomplete.** Run the planner-selected CI consumer, including import and frontier obligations, after the test delta is approved. UNKNOWN or merely unrun remains non-approval.

## Code assessment

No new production defect was found in this delta. Earlier production blockers remain resolved/superseded: owner-approved global authorization precedes replay/mutation, migration inspects before mutation, Bitrix/ERP use effective factory number, event snapshots include case/reference metadata, null clearing and compound chronology are implemented, and controller/persistence ownership is centralized. The review remains `CHANGES_REQUESTED` because mandatory Gate 3 evidence is not complete, not because the expanded tests exposed a new code defect.

## Required changes

Complete and independently approve Gate 3 coverage, resolve reference display evidence, then obtain authoritative exact-source CI/import GREEN and return the exact candidate for final review.
