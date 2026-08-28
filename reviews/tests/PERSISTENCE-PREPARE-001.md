# Test review: PERSISTENCE-PREPARE-001

- Reviewer: `Codex agent /root/persistence_test_review` (independent re-review after Gate 5 restart; did not author the specification, test, documentation changes, or production adapter)
- Test author: `Codex agent /root`, working session `2026-08-28`
- Reviewed commit: working tree at HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Specification: [`specs/PERSISTENCE-PREPARE-001.md`](../../specs/PERSISTENCE-PREPARE-001.md), version `0.3`, `APPROVED 2026-08-28`
- Schema contract: [`docs/fmonitor-2-pilot-data-model.md`](../../docs/fmonitor-2-pilot-data-model.md)
- Public seam: `FMonitor2\InstallationProcess\InstallationProcess::prepareAssignmentOrder(...)` and `::getInstallationObjectProcess(installationObjectId)` through a new module instance and a new MariaDB connection
- Red command: `php tests/InstallationProcess/persistence_prepare_001_test.php`
- Verdict: `APPROVED`

## Captured red result

```text
PHP Fatal error:  Uncaught TypeError: FMonitor2\InstallationProcess\MariaDbInstallationProcessEnvironment::__construct(): Argument #3 ($clock) must be of type object, string given, called in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/persistence_prepare_001_test.php on line 217 and defined in /home/antropophag/code/fmonitor-2/app/InstallationProcess/MariaDbInstallationProcessEnvironment.php:12
Stack trace:
#0 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/persistence_prepare_001_test.php(217): FMonitor2\InstallationProcess\MariaDbInstallationProcessEnvironment->__construct()
#1 {main}
  thrown in /home/antropophag/code/fmonitor-2/app/InstallationProcess/MariaDbInstallationProcessEnvironment.php on line 12
```

Exit code: `255`.

The independent rerun reproduced the supplied RED exactly. The uniquely prefixed documented process schema and initial case are created successfully; construction then reaches the rejected adapter's obsolete `(connection, renderer, clock, prefix)` boundary. This is intended missing v0.3 production behavior rather than a MariaDB, schema, bootstrap, or fixture failure.

## Findings

- **Reload source-of-truth sensitivity resolved:** the fresh adapter receives a distinct `$externalFactsForbiddenOnReload` object whose `__call()` throws `LogicException` for every external method invocation. The unchanged exact projection must therefore be reconstructed from persisted `fm2_*` facts; re-reading object/person snapshots, renderer output, authorization, or clock during observation fails immediately. This closes the prior false-positive path in which minimal IDs could be persisted and historical content rehydrated from unchanged external sources.
- **Previous schema blocker resolved:** the updated data-model document now explicitly defines `fm2_order_artifacts`, `previous_assignment_order_id`, `assignment_order_id`, normalized object/engineer/installer snapshot columns, workforce source/freshness columns, and artifact metadata. The fixture uses those documented names and types without the former JSON snapshot columns. Its six prefixed tables and relevant constraints align with the stated process schema.
- **Traceability:** the test cites approved `PERSISTENCE-PREPARE-001 v0.3`, invokes the exact command with the approved actor/object/composition, and retains the unchanged complete `ORDER-PREPARE-002` example-A command result and public projection. The initial SQL row represents the specified `needs_assignment_order` case at revision `1`; all external command facts and renderer bytes are deterministic delegate values explicitly permitted by v0.3.
- **Public seam:** SQL is confined to documented schema/precondition setup and cleanup; it is not used for assertions. Action and observation go only through `InstallationProcess`. The originating connection/module are discarded and observation uses a new connection, environment, and module, so ordinary PHP in-memory process state cannot satisfy the test.
- **Durability sensitivity that is present:** strict equality detects missing or duplicate versions/installers/artifacts/events, partial normalized hydration, wrong dates or hashes, missing preliminary assignments, an incorrect process state, open tasks, or changed work/checklist gates. A database write is required because a new connection reads the installation case and prepared version. The remaining hole is specifically that unchanged external delegates can supply the expected historical content on reload.
- **Expected-value independence:** command and projection expectations are literal approved values, not SQL queries or production output. Renderer byte literals are fixed while sizes and hashes are independently stated; prior verification confirms 42/36 bytes and both SHA-256 values. Random table names do not enter expected business values.
- **Determinism and isolation:** fixed source facts, clock, renderer bytes, fixture identity, and expectations make behavior deterministic. `bin2hex(random_bytes(6))` affects only a safe, uniquely scoped table prefix. Setup and cleanup touch only the exact prefixed `fm2_*` tables, permitting parallel runs without modifying demo, legacy, or one another's data.
- **Scope and rejected cases:** this is correctly limited to the successful durability tracer bullet. Production external-source integrations, concurrency, exact revision advancement, operation reconciliation, rollback, unknown commit, and new business rejections remain explicitly deferred by v0.3.

## Required changes

None. The specification, documented schema, test fixture, public expectations, durability sensitivity, and isolation now align. Gate 3 is approved; Gate 4 may proceed without changing the reviewed expectations.

## Review history

- Version `0.1`: `CHANGES_REQUESTED` for date conflict, overbroad claims, and shared table prefix.
- Version `0.2`: `APPROVED` after narrowing scope, aligning the date, isolating tables, and correcting the version citation.
- Version `0.3`, first restart review: `CHANGES_REQUESTED` for mismatch between fixture and documented `fm2_*` schema.
- Version `0.3`, second restart review: schema mismatch was resolved; `CHANGES_REQUESTED` because reload could still source historical snapshots/artifacts from the unchanged external delegate instead of persisted process facts.
- Version `0.3`, final re-review: the reload delegate forbids all external reads, the intended obsolete-constructor RED is reproduced with exit code `255`, and the verdict is `APPROVED`.
