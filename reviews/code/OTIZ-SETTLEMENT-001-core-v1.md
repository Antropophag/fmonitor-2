# OTIZ-SETTLEMENT-001 — bounded migration and replay core review

- Date: `2026-09-09`
- Reviewer: separately tasked agent `/root/settlement_review`
- Reviewed exact candidate: `42001adc2bb8f2d89471f9d587199288e41fad40`
- Baseline: `7252600d`
- Scope: canonical v24 settlement schema and concurrent exact receipt replay
- Verdict: **CHANGES_REQUESTED**

## C1 — BLOCKING: receipt recovery can bypass current authorization

`app/Otiz/MariaDbOtizSettlement.php:72` catches every `DomainException`, rolls
back, and returns a matching saved receipt. `execute()` calls `authorize()`
before its initial receipt lookup. Consequently `FORBIDDEN` thrown for an
inactive actor or an actor whose `otiz.manage` permission was revoked enters
this catch. If that actor has a prior successful receipt for the same operation
and fingerprint, the catch returns the historical success instead of preserving
`FORBIDDEN`.

The normative contract requires an active actor with current `otiz.manage` for
each public command. Replay stability does not waive current authorization.
This creates an authorization bypass through all three operations.

Return to Gate 2 for a public-seam regression: perform a successful operation,
revoke the actor's current permission (and separately cover inactive status if
the existing matrix does not), replay the exact same actor/UUID/fingerprint,
and require `FORBIDDEN` with no new closure, event, or receipt. The test should
also retain an authorized concurrent exact replay so a correction cannot remove
the required post-race receipt recovery. Obtain independent test approval before
changing production. The implementation must preserve authorization precedence
when recovering a receipt after rollback.

## Correct portions retained

`OtizSettlementSchemaMigration` now implements the canonical mysqli migration
contract, validates both ledger tables against exact schema fingerprints before
creating missing tables, returns fail-closed `SCHEMA_MIGRATION_CONFLICT`, and is
registered as contiguous catalogue successor v24. Existing incompatible tables
are rejected before any missing table is created.

`MariaDbOtizSettlement::execute()` otherwise handles the concurrent exact-replay path
that loses with a domain refusal after the financial-object lock. It rolls back
the losing transaction, rereads the actor+operation receipt, returns the saved
result only for an identical request fingerprint, returns
`OPERATION_CONFLICT` for another fingerprint, and otherwise preserves the
original refusal. This retains append-only facts and one successful receipt.

The independently approved rendezvous test is sensitive to this correction and
passes without changing its expected results. Schema clean, populated v23
upgrade, repeat, history preservation, and incompatible fingerprint behavior
also pass. Independent focused output and plan validation are recorded in
`reviews/tests/OTIZ-SETTLEMENT-001-canonical-setup-v1.md`.

Reviewed hashes:

```text
4946d3516703d09481ef617d352a8803e9f991ad09bf88cba8e871d06df78479  app/InstallationProcess/OtizSettlementSchemaMigration.php
64d7ef141b75568f48b4bff59869e8c297d98725116b1c120cc9fb373833e171  app/InstallationProcess/ProductionPilotMigrationCatalogue.php
af51ec6efaaeffda7f3a502a8301a7d537f0567523c5cb02469762c0c61ce3bd  app/Otiz/MariaDbOtizSettlement.php
b8113779efa60cec99763ab4ab6a62256cb13008bfac0f1d3c896b0016ce6dd8  tests/Otiz/settlement_concurrency_001_test.php
54917a0b48fc729f9e8c1da0539efe0d7a5c094fa8f573e9b01fa9fc1814f7ec  tests/Otiz/settlement_owner_001_test.php
9132621a9ebf94efc676f78f321e37590ba09a7446e9432644b4b49fee382305  tests/InstallationProcess/otiz_settlement_schema_001_test.php
```

The bounded core review is **CHANGES_REQUESTED** and does not authorize this
production diff to advance unchanged. It is also not final Gate 5 for
OTIZ-SETTLEMENT-001. HTTP commands, compatibility
adapter/old-writer removal, all changed entry points, broader regressions, and
the exact final candidate remain outside this review. They require their own
approved RED/GREEN evidence and final independent Gate 5 review before the
delivery can be declared complete.
