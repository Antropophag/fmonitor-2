# Test review: PERSISTENCE-OPEN-001

- Reviewer: `Codex agent /root/migration_test_review` (independent; did not author the specification, test, fixture, or production implementation)
- Test author: `Codex agent /root`, working session `2026-08-28`
- Reviewed commit: working tree at HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Specification: [`specs/PERSISTENCE-OPEN-001.md`](../../specs/PERSISTENCE-OPEN-001.md), version `0.1`, `APPROVED 2026-08-28`
- Inherited behavior: [`specs/OPEN-INSTALLATION-001.md`](../../specs/OPEN-INSTALLATION-001.md) v0.2, [`specs/PERSISTENCE-REGISTRATION-001.md`](../../specs/PERSISTENCE-REGISTRATION-001.md) v0.2, and `WORKFORCE-CATALOG-001`
- Public command/observation seam: `prepareAssignmentOrder(...) → confirmOrderRegistration(...) → openInstallation(...)`, then `getInstallationObjectProcess(...)` through a new module and MariaDB connection
- Red command: `php tests/InstallationProcess/persistence_open_001_test.php`
- Initial verdict: `CHANGES_REQUESTED`
- Current verdict: `APPROVED`

## Re-review after Gate 2 correction

Both prior blockers are resolved in the current test.

The narrow tracking wrapper records calls while delegating every lookup to the real `MariaDbWorkforceCatalog`. Its log is reset immediately after public preparation; confirmation performs no Workforce lookup, then opening must leave the exact log `[1042]`. Thus the observation cannot be satisfied by the preparation lookup and proves the production current-catalog route used by opening, without replacing the SQL-backed catalog with an in-memory fake.

The exact external before/after snapshot now includes `fm_maintable`, `users`, `users_roles`, prefixed `fm2_workforce_catalog`, and prefixed `fm2_process_user_capabilities`. Any write to the created external source/configuration tables is visible. No SQL assertion targets installation cases, orders, installers, artifacts, tasks, or events; those remain observed exclusively through the complete public reload projection.

Fresh RED:

```text
PHP Fatal error:  Uncaught Error: Call to undefined method FMonitor2\InstallationProcess\MariaDbInstallationProcessEnvironment::actorCanOpenInstallation() in /home/antropophag/code/fmonitor-2/app/InstallationProcess/InstallationProcess.php:514
Stack trace:
#0 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/persistence_open_001_test.php(138): FMonitor2\InstallationProcess\InstallationProcess->openInstallation()
#1 {main}
  thrown in /home/antropophag/code/fmonitor-2/app/InstallationProcess/InstallationProcess.php on line 514
```

Exit code: `255`.

Production migrations and the durable public preparation/registration chain complete before the intended missing opening authorization/persistence seam. The full independent projection, root opening fields, exact events/cardinalities/no tasks, immutable history, fresh connection with forbidden external reads, and unique database isolation remain intact.

**Gate 3 verdict: `APPROVED`. Gate 4 may proceed without changing the reviewed expectations.**

## Captured red result

```text
PHP Fatal error:  Uncaught Error: Call to undefined method FMonitor2\InstallationProcess\MariaDbInstallationProcessEnvironment::actorCanOpenInstallation() in /home/antropophag/code/fmonitor-2/app/InstallationProcess/InstallationProcess.php:514
Stack trace:
#0 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/persistence_open_001_test.php(123): FMonitor2\InstallationProcess\InstallationProcess->openInstallation()
#1 {main}
  thrown in /home/antropophag/code/fmonitor-2/app/InstallationProcess/InstallationProcess.php on line 514
```

Exit code: `255`.

The production v1-v3 migrations and uniquely isolated database setup complete, and the public production prepare and confirmation commands both persist successfully before the intended missing opening-authorization/persistence seam is reached. This is not a bootstrap, schema, Workforce setup, or inherited-persistence failure.

## Findings

- **Public chain and traceability:** the test cites approved v0.1, inserts only the initial empty case and external source/sentinel rows, then creates all order/registration facts through the exact public commands before one public opening call. Exact command results preserve the inherited examples and fixed timestamps.
- **Production Workforce assembly:** `MariaDbWorkforceCatalog` is genuinely constructed over the same production MariaDB connection/prefix. A transparent recording wrapper is reset after preparation and proves the opening path performs exactly one SQL-backed lookup for `1042`.
- **Full fresh reload:** the original module, facts/workforce delegates, environment, and connection are discarded. A new connection and adapter with an all-methods-forbidden external delegate must hydrate the complete exact projection. PHP in-memory state or external rehydration cannot pass.
- **Root/opening sensitivity:** strict literals require durable root `working`, `actualStartDate`, `openedAt`, and `openedByUserId`, plus true opening/checklist gates. Merely deriving booleans or returning the opening result without storing root fields fails after reconnect.
- **History, events, cardinality, and tasks:** exact equality retains one registered version, complete registration metadata, immutable object/installer/engineer snapshots, order facts, two artifacts, two assignments, and the two prior events; it adds exactly one minimal opening event in order and requires `openTasks = []`. Missing, duplicated, reordered, partially hydrated, or rewritten public facts fail without process-table SQL assertions.
- **Expected-value independence:** all results, root fields, snapshots, hashes/sizes, assignments, gates, and events are fixed literals from the approved domain/persistence examples. They are not computed from SQL results, command output, adapter state, or current Workforce output.
- **Forbidden reload reads:** the fresh delegate's `__call` throws for authorization, clock, renderer, legacy, Workforce, and directory access. Complete historical hydration must come from persisted `fm2_*` state.
- **Atomicity scope:** a successful fresh-connection projection proves the complete committed logical result. Rollback, unknown commit, concurrency, and physical row identity are correctly deferred; the required physical update/append strategy remains a Gate 5 invariant.
- **Resolved current-Workforce sensitivity gap:** the revised exact `[1042]` tracking assertion is isolated from preparation and delegates to production catalog SQL, so skipping, duplicating, or misrouting the opening lookup fails.
- **Resolved external immutability gap:** exact equality now covers all five created external table groups. Accidental legacy, user, role, workforce, or capability writes cannot pass.
- **Isolation/determinism:** business facts, clock sequence, renderer bytes, IDs, and expectations are fixed. A cryptographically unique database name and exact `DROP DATABASE` in `finally` isolate parallel runs and cleanup.
- **Scope:** one durable success is appropriate; corrupted composition, rejection outcomes, tasks, retries/concurrency, rollback/reconciliation, production auth/legacy/user/renderer assembly, and UI remain excluded.

## Previously required changes (resolved)

1. Completed: the real catalog is wrapped narrowly, preparation reads are reset, and opening requires exact `[1042]`.
2. Completed: legacy, users, roles, workforce, and capabilities all participate in exact external equality; process tables remain public-seam-only.
3. Completed: all prior strengths and the intended RED are preserved and independently rerun.
