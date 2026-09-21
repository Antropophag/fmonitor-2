# Code review: OBJECT-DETAILS-EDITING-001 — v7

- Reviewer: independent reviewer `/root/gate3_review`
- Specification/test author: root delivery agent
- Implementation author: separate executor
- Reviewed source: base `9523bca002eec559db14ecf946d3c297989cb18e` plus snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T182750Z-a5b3740ccf/snapshot/source.patch`, SHA-256 `6dc80a31f98043e0a5c10b97871d9f22e9cc6268189f0be2f000cb5a682575ac` (candidate `231fa5dda09b487ca3b891d8c04584b6f8111616245f7682531415180ec2be1b`)
- Prior review: `reviews/code/OBJECT-DETAILS-EDITING-001-v6.md`; Gate 3 v9 is `CHANGES_REQUESTED`
- Verification: thirteen mapped exact-source commands GREEN, including real import, consumer/frontier/recovery regressions, two-process race and Playwright save journey; full exact-source CI not yet supplied
- Verdict: `CHANGES_REQUESTED`

## Prior v6 disposition

1. **Sensitive test coverage — PARTIALLY FIXED.** Real concurrent writers and browser save/no-write behavior are now protected; import/card/queue/ERP/OTIZ/schema/recovery regressions execute. Manual override propagation through several mapped consumers and #222-specific restore continuity remain unasserted.
2. **Full exact-source verification — PARTIALLY FIXED.** Import is now locally GREEN, eliminating the former UNKNOWN. Canonical full CI remains pending.

## Findings

1. **HIGH — Gate 3 still lacks focused tests for several changed code paths.** Generic card/queue/ERP/OTIZ regressions do not seed manual overrides, and generic recovery does not restore override/event/request facts. Those paths can regress while all thirteen commands remain GREEN. Gate 3 v9 specifies the minimal focused corrections.
2. **MEDIUM — exact-source full CI remains required.** Run the planner-selected CI consumer only after the final test delta is independently approved.

## Code assessment

No production defect was found. The implementation remains conformant on the reviewed paths, and the new exact-source race and Playwright tests materially strengthen confidence. Final approval is withheld solely because Gate 3 still has substantial focused gaps and canonical CI has not run.

## Required changes

Complete and independently approve the remaining Gate 3 cases, then obtain authoritative exact-source CI GREEN and return the exact candidate for final review.
