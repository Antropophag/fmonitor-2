# Test review: PERSISTENCE-REGISTRATION-001

- Reviewer: `Codex agent /root/migration_test_review` (independent; did not author the specification, test, fixture, or production implementation)
- Test author: `Codex agent /root`, working session `2026-08-28`
- Reviewed commit: working tree at HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Specification: [`specs/PERSISTENCE-REGISTRATION-001.md`](../../specs/PERSISTENCE-REGISTRATION-001.md), version `0.2`, `APPROVED 2026-08-28`
- Inherited behavior: [`specs/REGISTRATION-CONFIRM-001.md`](../../specs/REGISTRATION-CONFIRM-001.md) and [`specs/PERSISTENCE-PREPARE-001.md`](../../specs/PERSISTENCE-PREPARE-001.md)
- Public seam: `InstallationProcess::prepareAssignmentOrder(...)`, `::confirmOrderRegistration(...)`, and `::getInstallationObjectProcess(...)` through a new module and MariaDB connection
- Red command: `php tests/InstallationProcess/persistence_registration_001_test.php`
- Initial verdict (v0.1): `CHANGES_REQUESTED`
- Current verdict (v0.2): `APPROVED`

## v0.2 re-review

Version 0.2 explicitly separates the publicly observable logical invariant from the physical append-only implementation invariant. The exact public reload must show the same sole version `1`, unchanged prepared facts/cardinalities, and exactly one appended registration event; physical row identity and the required `UPDATE` strategy are correctly assigned to Gate 5 diff/code review because the public projection intentionally does not expose internal row IDs.

The revised test removes the direct SQL count assertion over process tables. SQL against process persistence is now confined to production migrations and the initial empty-case fixture. The complete exact projection still rejects an extra version, installer, artifact, preparation event, or registration event and detects missing/partial registration persistence. SQL equality remains only for the explicitly approved external sentinel tables, where it observes the separate no-external-writes contract.

Fresh exact RED:

```text
PHP Fatal error:  Uncaught Error: Call to undefined method FMonitor2\InstallationProcess\MariaDbInstallationProcessEnvironment::actorCanConfirmOrderRegistration() in /home/antropophag/code/fmonitor-2/app/InstallationProcess/InstallationProcess.php:438
Stack trace:
#0 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/persistence_registration_001_test.php(70): FMonitor2\InstallationProcess\InstallationProcess->confirmOrderRegistration()
#1 {main}
  thrown in /home/antropophag/code/fmonitor-2/app/InstallationProcess/InstallationProcess.php on line 438
```

Exit code: `255`.

The public production preparation completes first. The failure remains at the intended absent registration authorization/persistence seam. Migration setup, isolated database cleanup, fixed command literals, external-table immutability, independent full expected projection, disposal of the original connection/state, fresh-connection durability observation, and reload delegate forbidding all external calls remain unchanged and sound.

**Gate 3 verdict: `APPROVED`. Gate 4 may proceed with the v0.2 expectations unchanged.**

## Captured red result

```text
PHP Fatal error:  Uncaught Error: Call to undefined method FMonitor2\InstallationProcess\MariaDbInstallationProcessEnvironment::actorCanConfirmOrderRegistration() in /home/antropophag/code/fmonitor-2/app/InstallationProcess/InstallationProcess.php:438
Stack trace:
#0 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/persistence_registration_001_test.php(70): FMonitor2\InstallationProcess\InstallationProcess->confirmOrderRegistration()
#1 {main}
  thrown in /home/antropophag/code/fmonitor-2/app/InstallationProcess/InstallationProcess.php on line 438
```

Exit code: `255`.

All three production migrations, the isolated database fixture, the production MariaDB adapter, and the public persisted preparation complete before the intended failure. The RED is therefore at the missing production registration-authorization/persistence adapter seam, not at bootstrap, schema setup, or inherited preparation.

## Findings

- **Traceability and successful behavior:** the revised test cites v0.2, creates only the empty case directly, prepares version `1` through the inherited public command, advances a fixed clock, and calls the exact approved registration command once. Strict command-result literals cover trimming, version, status, timestamp, actor, source, null external ID, and unchanged process state.
- **Real migration and durability setup:** v1-v3 production migrations are applied to a fresh database with the unique `t_pr001_<random>` name and one valid `process_` prefix. The originating connection, module, environment, and facts delegate are discarded; observation uses a new connection/module/adapter. In-memory-only registration cannot pass.
- **Full reload sensitivity:** `expectedPersistedRegistration()` is a complete independent literal inherited from the two approved examples. Exact equality observes one registered version, preserved snapshots and order facts, one installer, two artifacts, two preliminary assignments, unchanged gates/tasks, and exactly the ordered preparation and registration events. Missing updates, extra public facts, duplicate hydrated facts, partial persistence, or wrong event payloads fail.
- **No external rehydration or delegate writes:** the reload delegate throws for every method, so historical facts must come from `fm2_*`. During both commands the deterministic delegate exposes only the approved read/authorization/render/clock methods; an attempted external write has no callable method and fails. The before/after source-table literals additionally catch writes to legacy, users/roles, workforce, and capabilities tables.
- **Expected-value independence:** results, projection, snapshots, artifact sizes/hashes, timestamps, and event payloads are fixed specification literals. They are not computed from command output, adapter rows, renderer output, or production constants.
- **Determinism and isolation:** clocks, source facts, renderer bytes, IDs, and expected values are fixed. A cryptographically unique database is created and the exact database alone is dropped in `finally`, so parallel runs cannot share process/source tables or cleanup targets.
- **Atomicity scope:** the test proves a completely committed durable success visible on a fresh connection. It appropriately does not claim rollback, unknown-commit, or concurrency behavior, which v0.1 explicitly defers. A single successful final observation cannot independently prove the physical number of commits, but that implementation constraint remains reviewable at Gate 5.
- **Resolved public-seam contradiction:** v0.2 and the revised test prohibit and remove process-table SQL assertions. Cardinalities remain observable through strict public projection equality; approved SQL equality is limited to external sentinel tables for the distinct no-write requirement.
- **Resolved sensitivity overclaim:** v0.2 accurately states that public equality does not distinguish hidden row identity. It assigns the physical `UPDATE`/no-reinsert append-only invariant to Gate 5 while retaining all observable same-version/no-duplicate requirements at Gate 2.
- **Scope otherwise:** production authorization mapping, invalid/retry/concurrent registration, rollback/reconciliation, integration source, UI, and opening/checklist behavior are correctly excluded from this one durability tracer.

## Previously required changes (resolved)

1. Completed in v0.2: physical row preservation is explicitly an implementation invariant reviewed at Gate 5; public Gate 2 covers the exact logical version/history.
2. Completed: the redundant process-table count query was removed.
3. Completed: all prior strengths and the intended RED were preserved and independently rerun.
