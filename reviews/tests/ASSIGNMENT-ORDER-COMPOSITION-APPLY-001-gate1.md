# Independent Gate 1 review — ASSIGNMENT-ORDER-COMPOSITION-APPLY-001

- Verdict: **CHANGES_REQUESTED**
- Reviewer: `/root/bitrix_gate3`, separately tasked agent; not an author of the specification, tests or implementation.
- Date: 2026-09-07.
- Reviewed source commit: `ab30ba4ef38ac1619b6b28d7e15840caa1de446f`.
- Specification: version 0.1, SHA-256 `81496d1a1fc5aaf3a54712bb1c7aaedfdf37cd4e0bed97463fef3c7a60e0115d`.
- Controlling owner decision: SHA-256 `6f94ba37818d46769f56a4b003432fc161b80b16fb24834ddd6e5546933ddf19`; both recorded decisions are accepted and were not reopened.
- Public seam: `AssignmentOrderApplication::applyAssignmentOrderOriginal(...)`, plus the trusted readonly current/history reader.

## Material findings

1. **The prescribed validation order contradicts the identical-request concurrency outcome.** Section 5 promises that concurrent identical UUIDs create one fact and the loser returns `replayed` after the case lock is released. Section 7, however, orders the accepted-request lookup only before the lock, then orders locked authority immediately followed by expected-sequence validation. Both callers can miss the pre-lock ledger lookup; after the winner commits, the loser acquires the case lock and observes a changed sequence, which the stated order maps to `application_changed`. Nothing normatively requires a second accepted-request/fingerprint/backing lookup under the case lock before expected sequence. Add that locked lookup and its exact precedence: matching coherent accepted request → replay, changed fingerprint → `request_id_conflict`, absent → expected-sequence/current-state checks. State how unavailable/corrupt locked ledger backing maps. This is necessary to make the promised same-UUID race executable rather than implementation-dependent.

2. **“Current” authority and workforce evidence lack a concurrency observation rule.** Section 2 requires authority twice, with the second read under the application transaction, and section 3 requires current catalog/full-publication proof under the case lock. IAM and workforce publication writers do not share the application case lock. The contract therefore does not determine the result when a grant/status/publication changes after the outer read or after the transaction begins but before eligibility/commit. Depending on transaction isolation and first consistent read, conforming adapters could use an older transaction snapshot, a later committed row, or locking reads and produce different observable outcomes/audit. Define the authoritative observation point and consistency mechanism for the second authority check and the complete workforce row+metadata+run proof. At minimum, state whether a coherent transaction snapshot is the accepted basis and when it is established, or require fresh/locking reads with an explicit race outcome. Also state the linearization rule for revocation or dismissal committed after that authoritative read but before application commit. The owner’s unknown-date decision remains unchanged; this clarification only determines which real snapshot proves current eligibility.

## Contract assessment otherwise

The module is deep: one command accepts only identities, an expected application sequence and actor, while the implementation owns current original confirmation, selected immutable composition, eligibility proof, lifecycle checks, append-only application/event/audit persistence, replay and recovery. Callers cannot supply crew, dates or current state. The separate read interface exposes exact frozen application evidence without letting screens or HTTP reconstruct authority from selection, original, legacy slots or current catalog.

Initial, sequential and pre-opening reapplication behavior is otherwise concrete. Same-day sequential orders are ordered by application sequence; older effective dates are rejected; corrected same-order dates may move in either direction before opening but cannot precede the last different applied order. After opening, reapplication is rejected while a prospective new order remains possible only at or after actual start. Previous applications, original revisions, selected snapshots, checklist attribution and opening facts remain immutable.

The confirmed unknown-employment rule is faithfully represented. Unknown `employedFrom` never receives an invented date and is accepted only with a coherent completed full current 1C ZUP → Bitrix delivery proof. Known periods still enforce their dates and cannot ignore contradictory partial integration metadata. Snapshot provenance is frozen for later readback; no freshness threshold, publication behavior or selection-policy switch is invented.

Result shapes, safe reasons, scalar constraints, fingerprint input, audit fields, rollback/unknown-outcome recovery, allocation exhaustion and validation precedence are mostly exact and observable. The reader defines current/history payloads, pagination, immutable snapshots, absent-object distinctions, corruption behavior and no-partial-read failure. Factory purity, idle-connection discipline, no caller transaction mutation, no legacy writes, and absence of PDF/network/HTTP concerns preserve the owning application seam.

## OpenSpec and scope evidence

The OpenSpec proposal, design, tasks and delta align with the intended owner decisions and preserve the separate schema prerequisite without reserving a migration version. Independent strict validation passes:

```text
openspec validate apply-assignment-order-original-to-composition --strict
Change 'apply-assignment-order-original-to-composition' is valid
```

```text
6c3a7d84c35d955463a156d3ab34da32055f5070b8d63dda02fb6383f85a1333  openspec/changes/apply-assignment-order-original-to-composition/proposal.md
cae50beb69d1ad3d494cae9fdad24b9a0d20f8a1cb8b2490ad853c3efedf1dd6  openspec/changes/apply-assignment-order-original-to-composition/design.md
ddfa4cc0194c9e1bca077dc866d5b7d72c97de8fe06cfd640e7668a21925d2af  openspec/changes/apply-assignment-order-original-to-composition/tasks.md
61d2e18aa32b0a61602a77e1c1e7f1e365393fed28d5c1578a3da00fd4c770cf  openspec/changes/apply-assignment-order-original-to-composition/specs/pilot/assignment-order-composition-application/spec.md
```

No schema, test or production implementation was reviewed or changed. Return to Gate 1 to close the two concurrency semantics above; then obtain a new independent executable-specification review before the separate storage and command test gates proceed. Only this review record was added.
