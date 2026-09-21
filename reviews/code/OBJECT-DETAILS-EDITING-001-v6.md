# Code review: OBJECT-DETAILS-EDITING-001 — v6

- Reviewer: independent reviewer `/root/gate3_review`
- Specification/test author: root delivery agent
- Implementation author: separate executor
- Reviewed source: base `9523bca002eec559db14ecf946d3c297989cb18e` plus snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T180638Z-cd84c814c4/snapshot/source.patch`, SHA-256 `09d2da72dbc98f0826393c3c3f9493de2954f76108229345e0d53621fd3ec4f9` (candidate `c9aa6ad619bf89c3f704c90307762b2bda414fbc326eacf201f2848a5efce370`)
- Prior review: `reviews/code/OBJECT-DETAILS-EDITING-001-v5.md`; Gate 3 v8 is `CHANGES_REQUESTED`
- Verification: six mapped exact-source tests GREEN; full exact-source CI/import GREEN absent
- Verdict: `CHANGES_REQUESTED`

## Prior v5 disposition

1. **Gate 3 protection — PARTIALLY FIXED.** Second correction/immutability, accepted counts, revoked replay and raw/null/zero rules are now protected. Consumer/import, schema/recovery, real concurrency, complete history and HTTP/browser gaps remain.
2. **Reference display provenance — FIXED for current evidence.** Tests explicitly define legacy codes `7`/`9` as unresolved truthful raw display rather than fabricated human-readable labels, matching the catalogue behavior.
3. **CI/import — OPEN.** Authoritative exact-source CI has not run GREEN.

## Findings

1. **HIGH — Gate 3 still omits sensitive implementation surfaces.** Import preservation, card/queue/ERP matching/OTIZ propagation, schema/recovery lifecycle, real concurrent writers, complete immutable snapshots/chronology and security/browser outcomes remain able to regress while mapped tests stay GREEN.
2. **MEDIUM — full exact-source verification remains incomplete.** Run all planner-selected CI obligations, including import/frontier checks, after Gate 3 corrections. UNKNOWN or unrun is not approval.

## Code assessment

No new production defect was found. Earlier production blockers remain resolved or superseded, and the expanded exact-source tests now materially protect replay authorization, accepted fact counts, first-event immutability and truthful unresolved reference/null/zero normalization. Final approval is withheld because Gate 3 remains materially incomplete and full CI is absent.

## Required changes

Complete and independently approve Gate 3, then obtain authoritative exact-source CI/import GREEN and return the exact candidate for final review.
