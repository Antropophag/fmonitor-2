# Code review: OBJECT-DETAILS-EDITING-001 — v4

- Reviewer: independent reviewer `/root/gate3_review`
- Specification/test author: root delivery agent
- Implementation author: separate executor
- Reviewed source: base `9523bca002eec559db14ecf946d3c297989cb18e` plus snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T175504Z-9409e0c39b/snapshot/source.patch`, SHA-256 `521d8157abec88732524ecb79d786459679cb6e5ba0a3a1a947bf88ee87a52ef` (candidate `9f4a590ead5646b458f26d47b3042b1fa7494a595d9aa59b2553a58473e2cd3c`)
- Prior review: `reviews/code/OBJECT-DETAILS-EDITING-001-v3.md`; Gate 3 v6 is `CHANGES_REQUESTED`
- Verification: six mapped exact-source tests GREEN; full exact-source CI/import GREEN not supplied
- Verdict: `CHANGES_REQUESTED`

## Prior v3 disposition

1. **Actor-object authorization — INAPPLICABLE/SUPERSEDED.** Owner explicitly defined global scope for exact `fkr_operator`/`manager` actors with both `objects.read` and `objects.details.edit`. `ObjectDetailsEditApplication.php:11,21` checks active identity, exact business role, exact read/edit permissions and target pilot-object existence before request replay or mutation. This conforms to the amended contract; no assignment predicate is required.
2. **Test protection — OPEN.** Gate 3 v6 remains `CHANGES_REQUESTED`; corrected production surfaces remain substantially untested.
3. **Reference display truth — OPEN, MEDIUM.** Catalogue codes `7` and `9` still use their raw code as display without reviewed canonical evidence.
4. **CI/import — OPEN.** Authoritative exact-source CI/import GREEN is absent.

## Findings

1. **HIGH — final implementation approval is blocked by missing specification-level tests.** Bitrix effective lookup, import preservation, ERP/OTIZ propagation, schema lifecycle/recovery, real concurrency, immutable event snapshots, null clearing, compound chronology, replay authorization and complete HTTP/browser behavior can regress while all six mapped tests remain GREEN. Gate 3 v6 lists the required cases; test changes require plan regeneration and independent approval.
2. **MEDIUM — reference display provenance remains unverified.** Confirm that `pittype=7` and `pitmaterial=9` truthfully have display labels `7` and `9` in the canonical imported reference source, or model unknown/raw display explicitly.
3. **MEDIUM — PR-ready verification is incomplete.** Exact-source CI, including import and all planner-selected obligations, must be GREEN after test corrections; UNKNOWN is not approval.

## Positive verification

The production blockers from earlier reviews are resolved or superseded: global-scope authorization matches the owner decision and precedes replay/existence mutation; migration is inspect-first; Bitrix uses effective factory number; event history includes case and full snapshots; references use a catalogue/selects; nullable queue semantics, compound chronology, replay authorization, permission ownership and controller composition are corrected.

## Required changes

Complete and independently approve Gate 3 coverage, resolve reference display evidence, then obtain authoritative exact-source CI/import GREEN and return the exact snapshot for final review.
