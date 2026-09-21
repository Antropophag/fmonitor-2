# Code review: OBJECT-DETAILS-EDITING-001 — v3

- Reviewer: independent reviewer `/root/gate3_review`
- Specification/test author: root delivery agent
- Implementation author: separate executor
- Reviewed source: base `9523bca002eec559db14ecf946d3c297989cb18e` plus snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T174914Z-3b0e490222/snapshot/source.patch`, SHA-256 `46b2b022d57d6123af6438dbfe19c727fbc4ee4edef9bf9c8fae5743b4abeeb1` (candidate `17e705a78895945295c8e92a42dfa41333fb0dfe0ec6eeb057979279ee447684`)
- Prior review: `reviews/code/OBJECT-DETAILS-EDITING-001-v2.md`; Gate 3 v5 is `CHANGES_REQUESTED`
- Verification: six mapped exact-source tests GREEN; authoritative exact-source CI/import GREEN not supplied
- Verdict: `CHANGES_REQUESTED`

## Prior v2 findings disposition

1. **Object scope — OPEN.** `ObjectDetailsEditApplication.php:21` now accepts `$objectId`, but `scope_case` is joined only by that ID and is unrelated to the actor. Any active FKR operator/manager with global `objects.read` and `objects.details.edit` passes for every existing object. Parameterizing the existence check does not create actor-object authorization.
2. **Inspect-first migration — FIXED in code.** `ObjectDetailsEditingSchemaMigration.php:19,26-40` validates exact existing/current or approved predecessor columns, all required table indexes, constraints and trigger shapes before mutation under a named lock. Post-mutation validation covers the full current shape.
3. **Corrected behavior test protection — OPEN.** The six test bodies are unchanged and still do not protect Bitrix, references, null clearing, chronology, replay authorization, event metadata or migration validation.
4. **Reference labels — OPEN, MEDIUM.** Catalogue codes `7` and `9` still have display labels equal to their codes; canonical human-readable provenance is not established by test/evidence.
5. **CI/import — OPEN.** No authoritative exact-source CI/import GREEN is present.

## Findings

1. **BLOCKER — horizontal object authorization remains absent.** The authorization aggregation is actor/global-role based; `JOIN fm2_installation_cases scope_case ON scope_case.legacy_installation_object_id=?` proves only that an object exists. There is no membership, assignment or canonical access predicate tying `scope_case` to the actor. Enforce the repository's object-specific authorization seam before replay/mutation, or obtain an explicit normative decision that these business roles intentionally have global scope and amend A5 accordingly.
2. **HIGH — substantial implementation remains unreviewable through the tests.** All corrected consumer, schema, history, reference, projection and replay paths can regress while the six mapped tests remain GREEN. Gate 3 v5 enumerates the required public-seam cases; test changes require a new plan and independent approval.
3. **MEDIUM — reference display truth is unresolved.** Confirm reference codes and labels from the canonical imported/reference evidence; do not present raw codes `7`/`9` as coherent display labels unless that is explicitly the source truth.
4. **MEDIUM — PR-ready verification is incomplete.** Exact-source CI, including the previously UNKNOWN import obligation, must be GREEN after corrections.

## Positive verification

The prior migration blocker is resolved: preflight is now mutation-free for unsupported existing shapes and validates indexes on edits, requests and events. Previously fixed Bitrix effective lookup, event case/snapshot metadata, reference validation/selects, nullable queue behavior, compound chronology, pre-replay authorization, permission ownership and controller composition remain present.

## Required changes

Resolve object-specific authorization, add and independently approve the missing Gate 3 coverage, then run authoritative exact-source CI/import and return the resulting exact snapshot for final review.
