# Independent reconciliation of OTIZ findings A01–A04

Date: 2026-09-08
Source: `38ffe30a393f9b085cbbcc6a6f61564e5c8a4b1e` (`main`)
Scope: current source only; no production/runtime database, stand, secrets, issue, PR, or production-code mutation was used.

## Result

All four findings remain valid against the current source. A01, A02, and A04 are established statically through reachable HTTP call paths. A03 is established both statically and by a small pure arithmetic reproduction. No claim is made that a damaged snapshot, duplicated payment, or request-time schema creation has occurred in the user database.

The highest risk is A02 because an accepted stale snapshot can record a second full payment for the same object, and the database serializes only rows belonging to each snapshot. A01 makes malformed financial inputs accept-able; A03 makes the accepted/exported allocation internally disagree with the recorded payable pool; A04 prevents enforcing a DDL-free web principal and masks missing deployment preparation.

## A01 — confirmed P1: an incomplete draft can be accepted

`POST /pilot/otiz/calculate` is reachable only after route admission and authenticated local-session handling, then constructs `RapidPilotOtiz` and dispatches the request (`rapid-pilot/router.php:209-210`, `rapid-pilot/router.php:243-246`). The constructor also requires an active user with `otiz.manage` (`rapid-pilot/Otiz.php:34-45`), and mutating requests require CSRF (`rapid-pilot/Otiz.php:85-91`). These are real preconditions, but none proves snapshot completeness.

The calculator inserts a visible `draft` with zero totals and literal `content_hash='pending'` before reading its object inputs (`rapid-pilot/Otiz.php:169-175`). It then persists object rows, issues, and allocation rows incrementally (`rapid-pilot/Otiz.php:176-197`) and publishes totals/hash only after the loop (`rapid-pilot/Otiz.php:199-201`). There is no surrounding `begin_transaction()` in `calculate()`. Thus, under normal autocommit, an exception after line 173 leaves at least the draft durable; an exception during the loop can leave an arbitrary prefix of objects and allocations durable.

Acceptance locks the snapshot but checks only `status='draft'` and absence of an open blocker (`rapid-pilot/Otiz.php:116-124`). It does not reject `content_hash='pending'`, zero/unpublished totals, missing calculation event, incomplete child rows, or an input/version mismatch. A failure immediately after the initial insert is the minimal counterexample: it leaves no blocker rows, so every acceptance predicate succeeds. Knowledge of the id is not a meaningful safety barrier because the payments page selects the latest snapshot without filtering out incomplete drafts (`rapid-pilot/Otiz.php:204-214`).

Evidence class: static proof. No database failure injection was run.

Narrow correction: make calculation publication one explicit application operation. Either build all rows and publish the snapshot in one transaction, or persist `building` separately and atomically transition to `ready` only after child counts, totals, final hash, rules/input version, and calculation event are complete. The acceptance operation must accept only `ready`, never infer readiness from the absence of blockers.

Done means controlled failures after header creation, after the first object/allocation, and immediately before publication leave no accept-able snapshot; retry has a defined idempotent result; the public acceptance route refuses every non-ready state; and existing accepted history is not rewritten.

## A02 — confirmed P1, highest risk: payment balance is not protected across snapshots

There is a partial safeguard that narrows the finding: calculation evidence does include closures for the object across all snapshots up to the report date (`rapid-pilot/Otiz.php:175-180`, `rapid-pilot/Otiz.php:565-568`), and `PremiumCalculation` subtracts that net evidence from accrued amount (`rapid-pilot/legacy-migration/PremiumCalculation.php:30-37`). Therefore a snapshot calculated *after* a payment normally starts from the reduced balance.

That safeguard does not cover two snapshots calculated before either is paid. Payment completion locks the chosen snapshot and its object rows (`rapid-pilot/Otiz.php:142-149`), then sums closures only for the same `(snapshot_id, object_id)` and inserts the full remaining snapshot pool (`rapid-pilot/Otiz.php:150-152`). Manual closure uses the same snapshot-local sum (`rapid-pilot/Otiz.php:132-140`). Two different snapshots lock disjoint rows and neither operation re-reads the object-wide closure balance used by calculation.

The schema permits multiple draft/accepted snapshots for the same report date and gives payment closures only ordinary indexes on object/date and snapshot. Its sole uniqueness constraint prevents a second reversal of one closure; it does not constrain the original payment entitlement or idempotency across snapshots (`app/InstallationProcess/PilotOtizSchemaMigration.php:12`, `app/InstallationProcess/PilotOtizSchemaMigration.php:17`). Consequently, snapshots S1 and S2 prepared before payment can each hold pool P. Completing S1 inserts P. Completing S2 sees zero closures attached to S2 and inserts P again. Sequential execution is sufficient; concurrent execution is also unprotected because S1 and S2 lock different snapshot/object rows.

The UI later aggregates payments by object across closures (`rapid-pilot/Otiz.php:280-297`), so it can display the overpayment after it is recorded; this read projection does not prevent it. The operation records accounting/payment-completion facts and does not itself send money, which limits external consequence but not financial-ledger corruption.

Evidence class: static proof of the sequential and concurrency-permitting paths. No live MariaDB reproduction was run.

Narrow public seam: introduce one OTIZ-owned `completeObjectPayment`/`completeSnapshotPayments` application operation. Its repository transaction must lock a stable object-level entitlement/balance row (or compare-and-swap its version), sum all non-reversed closure facts for that object, reject/stale superseded snapshot entitlements, and append a closure with a business idempotency key. HTTP should call this operation and must not calculate residual balance itself.

Done means two precomputed snapshots cannot make total non-reversed closures exceed the currently authorized object entitlement under sequential or real parallel completion; retry after a lost response returns the same business result; a reversal restores only its exact entitlement; late recalculation creates a new explicit entitlement rather than silently reusing a stale pool; and old closure/reversal history remains append-only.

## A03 — confirmed P1 for financial accuracy: allocation rounding is not conserved

`PremiumCalculation` exposes the whole pool as `distributableCents` when there are no exclusions (`rapid-pilot/legacy-migration/PremiumCalculation.php:36-37`, `rapid-pilot/legacy-migration/PremiumCalculation.php:46-66`). The snapshot writer stores that value as `distributed_cents` and computes `undistributed_cents` before allocating members (`rapid-pilot/Otiz.php:185-187`). Each member then independently receives `intdiv(distributed * weight, sumWeights)`; there is no remainder pass and no later reconciliation to the actual sum of allocation rows (`rapid-pilot/Otiz.php:190-197`).

Pure reproduction using the exact expression with `distributed=100` and weights `[1,1,1]` produced:

```json
{"distributed_cents":100,"allocation_sum_cents":99,"lost_cents":1}
```

This is user-visible and financially material: the XLSX exports the object pool and the member allocation rows independently (`rapid-pilot/Otiz.php:462-473`), while payment completion records the entire object pool rather than the sum of allocation rows (`rapid-pilot/Otiz.php:147-152`). The database has no conservation constraint between object and allocation tables (`app/InstallationProcess/PilotOtizSchemaMigration.php:13-14`).

Evidence class: static proof plus pure synthetic arithmetic reproduction; no DB behavior was exercised.

Narrow correction: put deterministic integer allocation behind the same OTIZ calculation application seam. Approve either a deterministic largest-remainder/tie-break rule that allocates every distributable cent, or explicitly add the unallocated rounding remainder to `undistributed_cents`. Persist values derived from the actual allocation result.

Done means for zero, equal, and unequal weights, `sum(allocation.amount_cents) + explicit_undistributed_cents == pool_cents`; tie-breaking is stable under retry and input ordering; export, payment completion, and displayed totals consume the same conserved result; historical accepted snapshots are not silently recalculated.

## A04 — confirmed P1 before runtime privilege separation: five DDL statements are reached by every OTIZ request

The core OTIZ bootstrap is correctly read-only: constructor line 42 calls `ensureSchema()`, which delegates to `assertOtizReady()` (`rapid-pilot/Otiz.php:42`, `rapid-pilot/Otiz.php:553-562`). That readiness check verifies the seven core OTIZ tables and the reversal index (`app/InstallationProcess/MariaDbPilotLegacyObjectSchemaReadiness.php:44-53`). The audit is right not to attribute core OTIZ DDL to this bootstrap.

Immediately afterward, however, the same constructor unconditionally invokes both legacy migration ledgers (`rapid-pilot/Otiz.php:43-45`). `MigratedEvidenceDecisionLedger::ensureSchema()` executes one `CREATE TABLE IF NOT EXISTS` and calls a projection store whose `ensureSchema()` executes three more (`rapid-pilot/legacy-migration/MigratedEvidenceDecisionLedger.php:17-22`, `rapid-pilot/legacy-migration/MigratedEvidenceProjectionStore.php:8-10`). `MigrationQuarantineDecisionLedger::ensureSchema()` executes a fifth (`rapid-pilot/legacy-migration/MigrationQuarantineDecisionLedger.php:4-8`). This happens for GET as well as POST because construction precedes `handle()` dispatch (`rapid-pilot/router.php:243-246`, `rapid-pilot/Otiz.php:69-80`). Authentication and `otiz.manage` are required first, but a normal authorized page view still reaches all five DDL statements.

Core readiness does not cover these five legacy/reconciliation tables. Missing them therefore triggers lazy creation instead of a clear deployment readiness failure. The architecture checker enumerates `app`, `rapid-pilot`, `bin`, and `public` but drops every path containing `legacy-migration` (`tools/architecture/check.py:21-24`, `tools/architecture/check.py:72-81`), exactly excluding these runtime dependencies even though `rapid-pilot/Otiz.php` requires them (`rapid-pilot/Otiz.php:5-15`).

Evidence class: static reachability proof. No DDL-denied database principal was used.

Narrow correction: add these tables to canonical production migration/readiness ownership, remove constructor-time reachability of all five DDL statements through the two ledger `ensureSchema()` calls, and make ledger/projection runtime adapters fail closed on an absent or incompatible schema. Adjust the architecture checker so reachable production dependencies cannot disappear solely because of their directory name.

Done means an authenticated GET and each OTIZ command succeed with a web principal lacking `CREATE`, `ALTER`, and `DROP`; absent/drifted reconciliation schema returns a fixed readiness failure without mutation; canonical migration creates or validates the exact tables separately while preserving rows; and a source/runtime test proves that no dependency reachable from `RapidPilotOtiz` owns DDL.

## Ordering

A02 should be fixed before treating OTIZ payment completion as trustworthy. A01 and A03 belong in the same bounded calculation-publication seam because acceptance must consume only a fully built, conserved result. A04 can proceed independently as a deployment/schema slice and should not expand into a wholesale rewrite of the OTIZ UI.
