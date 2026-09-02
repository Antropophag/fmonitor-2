# Independent catalogue-prefix ceiling reconciliation review

Date: 2026-09-02  
Reviewer: fresh agent `prefix_reconciliation_review_20260902a`  
Artifact: `docs/operations/catalogue-prefix-ceiling-reconciliation.md`

## Scope and method

This was an independent evidence/planning consistency review only. I did not
modify the reconciliation, an OpenSpec artifact, an executable specification,
tests or production code. I checked the reconciliation against the authoritative
dirty worktree, including the current classification-provenance evidence and
OpenSpec change; all affected canonical-schema OpenSpec changes; the three
pending schema executable specifications; the approved workforce family-local
specification and test; the current production runner/composition contracts;
and current operations status.

This review does not approve Gate 1, RED, implementation, a migration version,
catalogue order, release contour or destructive repair of an existing database.

## Confirmed evidence

1. The arithmetic is correct. The literal basename
   `fm2_migration_classification_provenance` is 39 ASCII bytes, so an ASCII
   process prefix of 25 bytes produces a 64-character identifier and 26 bytes
   produces 65. The provenance evidence and its independent review identify
   this table as release-supporting rather than optional legacy-active data.

2. The affected planning inventory is accurate for the named schema slices.
   The dirty worktree still contains composed ceilings of 28 in object-detail,
   inspection-planning and migrated-evidence planning, 27 in
   migration-quarantine planning, and pending executable boundaries of 32, 29
   and 29 in `IDENTITY-ACCESS-SCHEMA-001`,
   `CHECKLIST-TEMPLATE-SCHEMA-001` and `INSPECTION-EVIDENCE-SCHEMA-001`.
   These must be superseded before their respective owner approvals.

3. The family-local exception is correctly preserved.
   `BITRIX-WORKFORCE-SCHEMA-001 v0.3` independently accepts 37 ASCII bytes and
   rejects 38 before DB access, while its focused migration test deliberately
   confirms that earlier v1-v4 family classes do not inherit that v5-local
   ceiling. A future composed-runner limit must not rewrite this direct-class
   contract.

4. The proposed 25/26 composed-runner boundary, identifier inventory,
   coexistence coverage and pre-mutation detection of already configured
   26-37-byte databases are directionally correct and remain configuration/
   deployment concerns rather than domain behavior.

## Blocking findings

1. **The required reconciliation matrix omits current normative top-level
   32-byte contracts.** `PRODUCTION-MIGRATION-RUNNER-001 v0.5` is `APPROVED`
   and accepts `FMONITOR_PROCESS_TABLE_PREFIX` through 32 bytes before opening
   the DB connection. `PRODUCTION-COMPOSITION-001` also requires every routing
   prefix to be `0..32`, and the pending `WORKFORCE-CANONICAL-RUNNER-001 v0.1`
   explicitly preserves the canonical runner's 32-byte contract. The document
   calls its table a required reconciliation matrix but neither inventories
   these contradictions nor states whether each is superseded, amended, or
   separated into process-versus-legacy prefix semantics. Updating only the
   listed schema changes would leave the public runner/factory contracts
   inconsistent with the claimed catalogue-wide maximum. Add explicit rows and
   required treatment for all three artifacts; preserve historical approvals
   as records, but identify the future versioned superseding contracts and
   distinguish `processTablePrefix` from the independently routed
   `legacyTablePrefix` where appropriate.

2. **The claimed authoritative planning source does not yet satisfy the
   reconciliation's own zero-DB-access contract.** The classification-
   provenance delta spec says a prefix over 25 bytes is rejected before DB
   *mutation* and its scenario excludes DDL, ledger writes and ambient mutation.
   It does not require rejection before connection/access. The design says
   validation occurs before SQL, which is closer but is not the normative
   observable scenario. In contrast, this reconciliation requires a 26-byte
   prefix to fail before DB connection/access, as do the object-detail evidence
   and the owner's stated ceiling. Either narrow the reconciliation's claim to
   the approved public seam actually planned, or—preferably—record that the
   classification-provenance OpenSpec spec must also be updated so its 26-byte
   scenario explicitly proves zero DB connection/access. It cannot be labelled
   the authoritative source with `keep` treatment while retaining the weaker
   requirement.

## Non-blocking precision notes

- MariaDB documents identifier limits in characters. The byte arithmetic is
  valid here only because the accepted prefix and every relevant basename are
  ASCII; retaining that qualification is important.
- Characterization references to a future 28-byte cap are correctly treated as
  stale commentary rather than authorization to change their current behavior
  or begin RED. Their next versioned revision should cite the superseding
  inventory.
- Existing evidence and review records should remain append-only. The required
  corrections belong in this current reconciliation and subsequent versioned
  planning/spec records, not in historical verdicts.

## Verdict

`CHANGES_REQUESTED`

The 25-byte conclusion is correct, but the reconciliation is not yet complete
enough to drive coherent planning updates because it omits active public
32-byte runner/composition contracts and overstates the zero-DB-access strength
of its designated authoritative OpenSpec source.
