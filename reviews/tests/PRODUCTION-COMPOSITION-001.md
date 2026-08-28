# Test review: PRODUCTION-COMPOSITION-001

- Reviewer: `Codex agent /root/migration_test_review` (independent; did not author the specification, test, factory/config/clock, adapters, or production implementation)
- Test author: `Codex agent /root`, working session `2026-08-28`
- Reviewed commit: working tree at HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Specification: [`specs/PRODUCTION-COMPOSITION-001.md`](../../specs/PRODUCTION-COMPOSITION-001.md), version `0.1`, `APPROVED 2026-08-28`
- Composition/public seams: `ProductionInstallationProcessFactory::create(...)`, returned `InstallationProcess` prepare/confirm/open/projection methods, `Clock`, and `SystemClock::now()`
- Red command: `php tests/InstallationProcess/production_composition_001_test.php`
- Initial v0.1 verdict: `APPROVED`
- Current v0.2 test verdict: `APPROVED`

## v0.2 dependent storage-root review

### Fresh harness re-review (2026-08-28)

The prior shared-parent blocker is resolved. The test records whether it created `.test-artifacts`; it does not chmod or otherwise mutate a pre-existing parent, validates real-directory/type, effective UID, protected mode, and effective-account home containment before creating its unique child, removes only that exact child through a strict realpath-descendant cleanup guard, and removes a newly created parent only when empty. The full composition sensitivity described below is unchanged.

Fresh exact execution:

```text
php tests/InstallationProcess/production_composition_001_test.php
PASS: PRODUCTION-COMPOSITION-001
```

Exit code: `0`. The dependent v0.2 test is approved; the artifact test's remaining effective-home fixture defect does not occur in this composition harness.

### Signature restart review (2026-08-28)

- Restart verdict: `CHANGES_REQUESTED`
- Exact RED:

```text
php tests/InstallationProcess/production_composition_001_test.php
PHP Fatal error:  Uncaught TestFailure: Artifact storage root must be non-nullable.
Expected: false
Actual: true in /home/antropophag/code/fmonitor-2/tests/bootstrap.php:27
Stack trace:
#0 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/production_composition_001_test.php(49): assertSameValue()
#1 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/bootstrap.php on line 27
```

Exit code: `255`.

The reflection seam correctly detects the known nullable/default third parameter without consulting implementation values. However, v0.2 specifies that config has exactly three mandatory string fields named, in order, `processTablePrefix`, `legacyTablePrefix`, and `artifactStorageRoot`. The new assertion inspects only parameter index 2. A constructor whose first two parameters became nullable/defaulted/mistyped/renamed, or which exposed an additional parameter, could still pass this signature check while violating that exact public contract (the later happy-path calls do not prove those type/default properties).

Required correction: assert the complete independent reflection projection for all constructor parameters—exact count/order/name, builtin `string`, non-nullability, and no default for each of the three fields. Preserve the current third-field RED and all existing full-chain, prefix/decoy, persistence reload, clock, config/charset/closed-connection, secure-root and cleanup assertions. Gate 4 for the composition signature restart must not proceed until fresh independent Gate 3 approval.

### Final signature re-review (2026-08-28)

- Latest restart verdict: `APPROVED`

The reflection assertion now requires exactly three constructor parameters and independently checks each approved position/name (`processTablePrefix`, `legacyTablePrefix`, `artifactStorageRoot`), builtin named `string` type, non-nullability, and absence of a default. It is sensitive to extra/reordered/renamed fields, union or class types, nullable types, and optional defaults without deriving expectations from production implementation. Existing full production chain and isolation coverage is unchanged.

Exact RED reproduced:

```text
php tests/InstallationProcess/production_composition_001_test.php
PHP Fatal error:  Uncaught TestFailure: Production config artifactStorageRoot must be non-nullable.
Expected: false
Actual: true in /home/antropophag/code/fmonitor-2/tests/bootstrap.php:27
Stack trace:
#0 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/production_composition_001_test.php(49): assertSameValue()
#1 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/bootstrap.php on line 27
```

Exit code: `255`. This is the intended exact public-signature RED; the composition restart is approved for Gate 4.

### Allowed-0755-root restart review (2026-08-28)

- Restart verdict: `APPROVED`

The composition fixture now creates its unique configured artifact root with exact mode `0755`, verifies current-effective-UID ownership and the absence of group/other write bits, and passes it through the real factory plus complete prepare/confirm/open, stored HTML metadata/layout, reload, prefix/decoy, clock and failure-validation chain. This directly tests the spec's configured-root rule rather than the stricter `0750` ceiling that applies only to store-managed shard directories. Existing artifact tests independently retain the managed shard/blob ceilings and negative writable-root/ancestor cases.

The root remains a random strict descendant of the validated, non-mutated workspace parent. Existing realpath-descendant cleanup removes only this run's exact root and conditionally removes only a parent created by this run.

Exact RED reproduced:

```text
php tests/InstallationProcess/production_composition_001_test.php
PHP Fatal error:  Uncaught InvalidArgumentException: Invalid artifact storage root. in /home/antropophag/code/fmonitor-2/app/InstallationProcess/ContentAddressedArtifactStore.php:20
Stack trace:
#0 /home/antropophag/code/fmonitor-2/app/InstallationProcess/ProductionInstallationProcessFactory.php(14): FMonitor2\InstallationProcess\ContentAddressedArtifactStore->__construct()
#1 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/production_composition_001_test.php(75): FMonitor2\InstallationProcess\ProductionInstallationProcessFactory::create()
#2 {main}
  thrown in /home/antropophag/code/fmonitor-2/app/InstallationProcess/ContentAddressedArtifactStore.php on line 20
```

Exit code: `255`. The failure is the intended production-factory rejection of the valid `0755` configured root. Gate 3 restart is approved.

The revised composition behavior itself is green and correctly passes an explicit secure `artifactStorageRoot`; exact production chain, storing HTML metadata, prefixes/decoys, external equality/deletion reload, clocks, config/charset handling, and full projection remain intact.

```text
php tests/InstallationProcess/production_composition_001_test.php
PASS: PRODUCTION-COMPOSITION-001
```

Exit code: `0`.

One harness blocker prevents fresh v0.2 approval: line 47 unconditionally changes the shared repository `.test-artifacts` parent to mode `0700`, even when it pre-existed, and never restores that user-owned state or removes a parent it created. The composition test must share the safe-parent repair required by the artifact review: record creation, set mode only for a newly created parent, validate rather than mutate a pre-existing real directory, remove only the unique child root, and remove a newly created parent only if empty. It must also reject a symlink parent before creating its child.

This is a test-isolation correction only; no production expectation should change. After repair, rerun the green composition regression and request fresh independent Gate 3 approval alongside `ARTIFACT-STORE-001 v0.2`.

## Captured red result

```text
PHP Fatal error:  Uncaught Error: Class "FMonitor2\InstallationProcess\ProductionInstallationProcessConfig" not found in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/production_composition_001_test.php:68
Stack trace:
#0 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/production_composition_001_test.php on line 68
```

Exit code: `255`.

The real MariaDB database, v1-v4 process migrations, both prefixed external namespaces, production configuration rows, initial process case, and all opposite-namespace decoys are created before the intended absent production config/factory boundary is reached. The RED is not a database, migration, prefix, or fixture failure.

## Findings

- **Factory-only composition seam:** the behavior test instantiates no process environment or external adapter. It supplies only one `mysqli`, the approved two-prefix config, and a `Clock`, then requires the factory to return exactly `InstallationProcess`. Every prepare/confirm/open action and observation crosses the deep public module seam; caller access to internal delegates is neither required nor exposed.
- **Exact production public chain:** strict prepare, registration, and opening result literals exercise production legacy snapshot, Workforce prepare/current lookup, distinct directory capabilities/engineer lookup, HTML renderer, process persistence, and all three clock moments. Missing, permissive, swapped, fallback, or manually faked production delegates cannot satisfy the complete chain.
- **Prefix routing and decoys:** process and legacy prefixes are distinct valid random identifiers. Identical-ID decoys deliberately place a wrong object and inactive users/roles under the process prefix, plus dismissed Workforce and wrong capabilities under the legacy prefix. Exact successful facts/authorization prove legacy adapters use only `legacyTablePrefix`, while process persistence, Workforce, and capabilities use only `processTablePrefix`. `users_rights2roles` is absent, so a fallback query fails.
- **External equality:** exact before/after SQL literals cover every real and decoy external table in both namespaces: object/users/roles, Workforce, and capabilities. Commands cannot rewrite source/configuration rows or decoys. No SQL assertion targets installation cases, orders, installers, artifacts, tasks, or events.
- **Fresh composition and deleted-source reload:** after success the test deletes the five real external-source/configuration row sets, closes the original connection, and drops all original process/clock references. A fresh factory and connection receive a clock that throws on any read. Exact public projection must still reload solely from process tables; any legacy, Workforce, directory, renderer, clock, or in-memory rehydration fails or produces missing/wrong facts.
- **Full HTML projection:** independent strict equality covers root working/open fields, one registered version and registration metadata, immutable object/installer/engineer snapshots, assignments, no tasks, both true gates, and exactly three ordered events. Artifact filenames/media types/sizes/SHA-256 are the approved HTML values, distinguishing `ProductionHtmlAssignmentOrderRenderer` from the older PDF-like deterministic fixture. Bytes are not leaked into projection/events.
- **Clock routing:** the injected sequence exposes only the three approved instants and throws on any fourth read. Exact command/event timestamps and Moscow-derived preparation date require the sequence to route through the composition in command order; reload's throwing clock proves observation is clock-free.
- **SystemClock contract:** direct public `SystemClock::now()` output must match exact RFC3339 seconds-plus-offset syntax and round-trip identically through `DateTimeImmutable`. Default timezone is captured and must remain unchanged. These checks are bounded to format/timezone side effects rather than unstable wall-clock equality.
- **Config and prefix validation:** both invalid-character and 33-byte process prefixes, invalid legacy prefix, and reflection-created uninitialized config must throw `InvalidArgumentException`. PHP parameter types independently enforce `mysqli`, config, and `Clock|null`. Prefixes never originate in process commands.
- **Charset/closed connection fail closed:** the test connection helper uses utf8mb4 for fixture correctness. More importantly, a separately closed connection passed to the factory must be converted to the exact redacted `RuntimeException` message rather than leaking a driver/credential/SQL/table detail; a factory that performs no initialization charset operation cannot satisfy this boundary.
- **Expected-value independence:** command results, legacy normalization, workforce/user facts, HTML metadata/hashes, full projection, events, clock sequence, prefix constraints, and error message are specification literals. None is derived from factory output, process rows, adapter state, or current wall time.
- **Determinism and isolation:** business facts and sequence time are fixed. Random database/token values affect only unique safe namespaces; the exact database is dropped in `finally`. Decoys and external deletions are confined to that disposable database, so parallel runs and demo/legacy state are untouched.
- **Scope:** factory creation and the successful production chain/reload are covered without claiming HTTP/session bootstrap, auto-migration, pooling, byte storage/download, PDF, tasks, rejected commands, concurrency/failure recovery, logging, or a DI framework.

## Required changes

None. The test is traceable, deterministic, independently literal, red for the intended missing composition boundary, and sensitive to the approved adapter wiring, namespaces, persistence, renderer, clocks, validation, and read-only boundaries. Gate 4 may proceed without changing reviewed expectations.
