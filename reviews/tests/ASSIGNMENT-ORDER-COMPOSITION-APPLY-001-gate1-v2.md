# Independent Gate 1 amendment review — ASSIGNMENT-ORDER-COMPOSITION-APPLY-001

- Verdict: **APPROVED**
- Reviewer: `/root/bitrix_gate3`, separately tasked agent; not an author of the specification, tests or implementation.
- Date: 2026-09-07.
- Reviewed source commit: `169bdd8321164adf7e9c61c4ab24585523bea90e`.
- Specification: version 0.2, SHA-256 `a6505221f7a7ec9727c815296333ab9dadb608363579d9016302045e6173ca79`.
- Prior Gate 1 record: `ASSIGNMENT-ORDER-COMPOSITION-APPLY-001-gate1.md`, `CHANGES_REQUESTED`, retained unchanged.
- Controlling owner decision: SHA-256 `6f94ba37818d46769f56a4b003432fc161b80b16fb24834ddd6e5546933ddf19`; both confirmed decisions remain accepted.
- Public seam: `AssignmentOrderApplication::applyAssignmentOrderOriginal(...)`, plus the trusted readonly current/history reader.

## Amendment disposition

Both prior concurrency findings are resolved.

The pre-lock accepted-request read is now explicitly only a hint. After acquiring the case lock and rechecking current actor authority, the command must perform an authoritative locked accepted-request lookup before expected sequence, original, lifecycle or eligibility checks. A matching fingerprint with coherent immutable backing replays the exact prior payload; a mismatch returns `request_id_conflict`; corrupt backing returns `dependency_unavailable`; only absence advances to sequence and current-state validation. Thus two same-object callers that both miss preflight serialize to one application/event and one replay, with two attempt audits, rather than allowing the loser to become `application_changed`.

The contract also closes the global UUID race across different case locks. A proven unique-key race is handled only after confirmed rollback and a fresh authorized lookup. Matching coherent backing replays, a different fingerprint conflicts, and missing/corrupt/unavailable lookup fails closed. Other SQL/constraint errors cannot be relabeled as request conflicts, no blind mutation retry occurs, and the terminal audit is written through its separate owned transaction. The request fingerprint includes object identity, so concurrent reuse across different objects deterministically conflicts.

Current authority and workforce eligibility now have an observable native concurrency rule. The owner transaction uses READ COMMITTED fresh locking reads, takes the case lock first, then locks participating actor/engineer user-role-grant rows and workforce metadata, referenced completed run and selected catalog rows in declared stable order. Positive proof rows remain protected until commit or rollback. A revoke or dismissal committed before the corresponding locked read determines the refusal; a writer beginning afterward waits, the application may commit from the held proof, and subsequent commands see the new state. Confirmed timeout/deadlock rollback maps to `persistence_failure`, without wait-policy mutation or internal retry. This defines the application decision's linearization point without pretending the case lock serializes IAM or sync publication.

## Complete contract assessment

The module remains deep and well placed: callers supply only command identities, expected sequence and actor, while one application interface owns authority, current-original confirmation, immutable selected composition, employment proof, lifecycle rules, replay, atomic append and audit. The exact reader exposes frozen current/history application evidence without letting HTTP, screens or downstream consumers reconstruct effective assignments from selection, latest PDF, current catalog or legacy slots.

Initial application, sequential replacement, same-day ordering, pre-opening correction and post-opening prospective replacement are unambiguous. Reapplication can move a corrected date in either direction before opening while respecting the last different applied order; after opening it is rejected, whereas a new order must not predate actual start. No application path rewrites earlier application, original, selection, opening, checklist or inspection attribution.

The owner-approved unknown-employment rule remains exact: a missing start date stays null and requires coherent full current 1C ZUP → Bitrix proof; known periods still enforce dates; contradictory partial metadata fails closed; no freshness threshold is invented. The inherited selected crew bound is now explicit as 1..500 in the application payload.

Result and reason shapes, scalar validation, validation precedence, request fingerprint, audit obligations, rollback/unknown-commit recovery and allocation exhaustion are observable. The readonly interface fixes payload field sets, immutable snapshots, history cursor behavior, absent-object distinctions and corruption/no-partial-result handling. Prefix purity, idle connection behavior, append-only ownership and the separately gated storage prerequisite remain within scope; no schema version is reserved.

## OpenSpec evidence

The amended design reflects the locked replay lookup, cross-case recovery, READ COMMITTED locking reads and stable lock order. The proposal, tasks and delta remain aligned. Independent strict validation passes:

```text
openspec validate apply-assignment-order-original-to-composition --strict
Change 'apply-assignment-order-original-to-composition' is valid
```

```text
6c3a7d84c35d955463a156d3ab34da32055f5070b8d63dda02fb6383f85a1333  openspec/changes/apply-assignment-order-original-to-composition/proposal.md
c4ac2fef400490eca7289cea81e54bee2ef36695bc6288a4c7438238e70180f0  openspec/changes/apply-assignment-order-original-to-composition/design.md
ddfa4cc0194c9e1bca077dc866d5b7d72c97de8fe06cfd640e7668a21925d2af  openspec/changes/apply-assignment-order-original-to-composition/tasks.md
61d2e18aa32b0a61602a77e1c1e7f1e365393fed28d5c1578a3da00fd4c770cf  openspec/changes/apply-assignment-order-original-to-composition/specs/pilot/assignment-order-composition-application/spec.md
```

No blocking ambiguity remains. Gate 1 v0.2 is approved. The compatible storage schema must complete its separate gates before native command/read RED; independent Gate 3, minimal GREEN, regressions/architecture checks and independent Gate 5 remain required. No specification, test, schema or production code was changed by this reviewer; only this review record was added.
