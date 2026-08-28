# Test review: LEGACY-OBJECT-SNAPSHOT-001

- Reviewer: `Codex agent /root/migration_test_review` (independent; did not author the specification, test, or production delegate)
- Test author: `Codex agent /root`, working session `2026-08-28`
- Reviewed commit: working tree at HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Specification: [`specs/LEGACY-OBJECT-SNAPSHOT-001.md`](../../specs/LEGACY-OBJECT-SNAPSHOT-001.md), current version `0.2`, `APPROVED 2026-08-28`
- Inherited contracts: [`specs/PERSISTENCE-PREPARE-001.md`](../../specs/PERSISTENCE-PREPARE-001.md) v0.3 and [`specs/MIGRATION-PROCESS-001.md`](../../specs/MIGRATION-PROCESS-001.md) v0.1
- Public seam: `InstallationProcess::prepareAssignmentOrder(...)` and `::getInstallationObjectProcess(...)` through production MariaDB process persistence and a production legacy-object delegate
- Red command: `php tests/InstallationProcess/legacy_object_snapshot_001_test.php`
- Initial verdict for v0.1: `CHANGES_REQUESTED`
- Current verdict for v0.2: `APPROVED`

## Re-review of specification/test v0.2

The approved v0.2 specification and revised Gate 2 test resolve every initial blocker:

- example B is now a separately approved, command-reachable scenario with a zero-date adjusted finish and repeated-zero PТО value; it no longer asserts successful preparation in the presence of a nonzero Act PТО;
- examples A and B each use a clean independent database, the same object identity and initial process state, and the exact public `prepareAssignmentOrder(...)` seam;
- both reload assertions compare the complete independently literal `PERSISTENCE-PREPARE-001` projection, including one version, object/person snapshots, exact artifact filenames/sizes/hashes, preliminary assignments, empty tasks, closed work/checklist gates, and one exact success event;
- renderer filenames and bytes now align with the inherited projection;
- example B proves zero-date adjusted-finish fallback to the competing base plan and trims/interprets repeated PТО zeros as absent, while its distinct `workdatefinish` remains excluded.

Expected-value independence is preserved. `expectedLegacyProjection()` is only a presentation helper around fixed literals; the two expected snapshots are declared independently in `$examples`, not obtained from SQL, the delegate, the command result, PHP date parsing or trimming. Strict full-array equality rejects missing, extra or reordered public facts.

Each scenario proves source immutability before deleting its fixture intentionally for reload. The originating module, composite delegate and legacy delegate are released, the connection is closed, and a new module/connection receives a delegate that throws for every external read. The exact full projection must therefore come from persisted `fm2_*` facts and cannot be reconstructed from legacy or PHP memory.

Isolation and determinism are adequate. A and B use distinct random hexadecimal database names and fixed source facts, IDs, renderer bytes and time. Each `finally` closes its active connection and drops only that exact database, including after the observed RED. Parallel executions cannot share legacy or process tables.

The reviewer reran `php tests/InstallationProcess/legacy_object_snapshot_001_test.php`. The revised test remains RED at the intended missing production delegate seam:

```text
PHP Fatal error:  Uncaught Error: Class "FMonitor2\InstallationProcess\MariaDbLegacyInstallationObject" not found in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/legacy_object_snapshot_001_test.php:79
Stack trace:
#0 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/legacy_object_snapshot_001_test.php on line 79
```

Exit code: `255`. The first isolated database, production migration, legacy fixture, process precondition and immutable source snapshot are created before the absent class fails; setup itself is not the cause.

No blocking findings remain. Gate 3 for `LEGACY-OBJECT-SNAPSHOT-001 v0.2` is `APPROVED`; Gate 4 may proceed without changing the reviewed expectations.

## Initial v0.1 review history (`CHANGES_REQUESTED`)

### Captured red result

```text
PHP Fatal error:  Uncaught Error: Class "FMonitor2\InstallationProcess\MariaDbLegacyInstallationObject" not found in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/legacy_object_snapshot_001_test.php:61
Stack trace:
#0 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/legacy_object_snapshot_001_test.php on line 61
```

Exit code: `255`.

The independent run reaches a newly created isolated MariaDB database, applies the reviewed production process migration, creates the legacy/process preconditions, and then fails at the absent production delegate. This is the intended missing behavior rather than broken SQL setup or a pre-applied implementation.

### Findings

- **Traceability and approved seam:** the test cites `LEGACY-OBJECT-SNAPSHOT-001 v0.1`, invokes the real public command with approved IDs, uses production process persistence, and delegates only the legacy object lookup to the proposed production adapter. Other facts, renderer bytes and time are fixed boundary delegates as permitted by the slice.
- **Example A mapping sensitivity:** leading/trailing whitespace on address, entrance and registration number makes the literal trimmed expectation sensitive to normalization. Distinct adjusted, base-plan and actual-finish dates make `2026-12-18` sensitive to adjusted-date priority and prevent `workdatefinish = 2026-11-30` from masquerading as plan. Timestamp suffixes make the start/finish expectations sensitive to calendar-component extraction without timezone conversion. The PТО zero-date is independently expected as `null`.
- **Example B mapping sensitivity that is present:** whitespace-only `workdateendadjusted` competes with `plan_finish_date = 2027-01-09 23:59:59`, and a different `workdatefinish = 2026-12-01` catches use of actual completion as plan. Nonempty `ptoactdate` is expected as the literal calendar date `2027-01-12`.
- **Blocking specification contradiction:** test line 76 invokes `prepareAssignmentOrder(4513, ...)` for example B and requires the full command to be accepted. Specification section 5 explicitly says example B fixes only delegate mapping and *does not* approve successful preparation when an unstarted legacy object already has an Act PТО; its public command result is outside this slice and belongs to a separate import/quality gate. The test therefore invents unapproved product behavior at the public seam. Gate 1 must resolve how example B is observed without approving that transition before Gate 2 can require a result.
- **Blocking inherited-projection gap:** section 6 requires the complete persisted projection, assignments, artifacts, one event, and closed work/checklist gates inherited from `PERSISTENCE-PREPARE-001`. After reload, the test asserts only `assignmentOrders[0].installationObjectSnapshot` for each object. An implementation can source and persist the six snapshot fields correctly while regressing the rest of the inherited result and still pass. The fixture artifact filenames also differ from the inherited literal filenames, so the currently unasserted full projection is not the approved one.
- **Blocking zero-value sensitivity gap:** the normative mapping treats `NULL`, empty, one-or-more string/numeric zeros, and zero-date/date-time as absent for adjusted finish and PТО. Current fixtures cover whitespace adjusted finish and a PТО zero-date, but not an adjusted-finish zero-date or zero string. An implementation that falls back for whitespace yet emits `0000-00-00` or `0` as `plannedFinishDate` satisfies both current examples. Add at least one command-reachable fixture with a zero-date/zero adjusted finish, a valid base plan and an absent PТО; include a repeated-zero PТО value if the intended normalization of that form is to be executable in this slice.
- **Persistence reload:** the test deletes both legacy rows, closes the originating connection, destroys module/delegate references, creates a new connection/module, and supplies an external delegate that throws on every method. The two snapshot assertions therefore require process persistence and cannot be satisfied by PHP memory or a repeated legacy read.
- **Read-only sensitivity:** exact equality of every legacy fixture column before both commands and immediately afterward catches ordinary legacy updates, inserts or deletes, including writes to unmentioned columns such as `workdatefinish`. The subsequent deletion is explicit test setup for the reload proof, not production behavior. This does not detect a delegate that writes and restores the same bytes within its call, but that is not a plausible minimal false positive requiring transaction-log instrumentation in this slice.
- **Expected-value independence:** expected dates, trimmed strings, null, command result and source rows are fixed literals from the approved examples rather than values computed through SQL date functions, PHP trim, production delegate output or command output. The missing full inherited projection is an absence, not a tautological expectation.
- **MariaDB isolation and deterministic cleanup:** a random hexadecimal database name contains the entire unprefixed legacy fixture and prefixed process tables, allowing parallel runs without touching demo, legacy production or another test. Facts, IDs, timestamps and renderer bytes are fixed. `finally` closes the active connection and drops only the exact database through the admin test connection, including after the observed RED.
- **Rejected cases and scope:** no new public failure code is correctly introduced. Missing-row/infrastructure behavior and authorization ordering are explicitly outside this successful source-integration tracer. The current extra accepted example B, however, crosses the stated scope boundary rather than merely omitting a rejected case.

### Required changes

1. Return to Gate 1 and resolve the observation seam for example B. Do not assert successful preparation with a non-null PТО unless the specification explicitly approves that product transition. A separately confirmed adapter observation seam or a command-reachable mapping fixture without the legacy PТО conflict are possible designs, but the test must follow the approved choice.
2. For the approved successful example A, assert the complete independently literal `PERSISTENCE-PREPARE-001` projection after deleting legacy data and rebuilding with a new connection/forbidden external delegate. Align renderer metadata literals with that inherited projection.
3. Add a command-reachable independently literal case that proves adjusted-finish fallback for a zero-date or repeated-zero value (not only whitespace), and proves the corresponding PТО zero/repeated-zero normalization intended by section 3, without introducing the unresolved Act-PТО transition.
4. Rerun `php tests/InstallationProcess/legacy_object_snapshot_001_test.php`, retain RED for the absent/missing legacy delegate behavior, and request a fresh independent Gate 3 review. Gate 4 must not begin yet.
