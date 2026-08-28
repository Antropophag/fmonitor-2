# Test review: TERMINOLOGY-OBJECT-001

- Reviewer: `Codex agent /root/terminology_object_001_test_review` (independent; did not author the specification or test migration)
- Test author: `Codex agent /root`, working session `2026-08-28`
- Reviewed commit: `working tree before implementation` (HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`)
- Specification: [`specs/TERMINOLOGY-OBJECT-001.md`](../../specs/TERMINOLOGY-OBJECT-001.md), version `0.1`, `APPROVED 2026-08-28`
- Public seam: `FMonitor2\InstallationProcess\InstallationProcess::prepareAssignmentOrder(...)`, `::getInstallationObjectProcess(installationObjectId)`, and `::getSecurityAudit(installationObjectId, actorId)`
- Test corpus: all 16 files under [`tests/InstallationProcess/`](../../tests/InstallationProcess/) and [`tests/Support/InMemoryInstallationProcessEnvironment.php`](../../tests/Support/InMemoryInstallationProcessEnvironment.php)
- Red commands: `php -d display_errors=1 -d error_reporting=E_ALL tests/InstallationProcess/order_prepare_002_test.php` and `php -d display_errors=1 -d error_reporting=E_ALL tests/InstallationProcess/terminology_object_001_test.php`
- Intended failures: production does not yet expose `prepareAssignmentOrder(...)` and still exposes the forbidden `prepareOrder(...)`
- Verdict: `APPROVED`

## Captured red result

```text
Fatal error: Uncaught Error: Call to undefined method FMonitor2\InstallationProcess\InstallationProcess::prepareAssignmentOrder() in tests/InstallationProcess/order_prepare_002_test.php:68
Stack trace:
#0 {main}
  thrown in tests/InstallationProcess/order_prepare_002_test.php on line 68
```

Exit code: `255`.

The dedicated public-interface replacement test independently fails as follows:

```text
Fatal error: Uncaught TestFailure: TERMINOLOGY-OBJECT-001 must remove the ambiguous prepareOrder interface.
Expected: false
Actual: true in tests/bootstrap.php:27
Stack trace:
#0 tests/InstallationProcess/terminology_object_001_test.php(12): assertSameValue()
#1 {main}
  thrown in tests/bootstrap.php on line 27
```

Exit code: `255`.

All migrated test files and the in-memory environment pass `php -l`.

## Findings

- **Traceability:** the 15 behavioral tests consistently call the approved new command seam and, where state is observable, the new query seam. Their command arguments and projections use `installationObjectId`, `installationObjectSnapshot`, `assignmentOrderVersion`, and `assignmentOrderDate`; state and task literals use `needs_assignment_order`, `assignment_order_prepared`, and `prepare_assignment_order`. Required-data examples use `INSTALLATION_OBJECT_REQUIRED_DATA_MISSING` and the approved Russian messages about the installation object. No old public method call remains in the test corpus.
- **Preserved behavior:** the migrated expectations retain the previously approved authorization, composition, security-audit, required-data, successful preparation, installer eligibility, and engineer eligibility contracts. The changes observed are terminology substitutions required by this specification; artifact bytes and SHA-256 literals remain unchanged as explicitly required.
- **Genuine missing seam:** the focused example reaches the command invocation and terminates on the absent production method `prepareAssignmentOrder`. This is the intended missing production behavior, rather than a bootstrap, syntax, namespace, or fixture lookup failure.
- **Fixture consistency:** the environment's seed, replace, query, and event methods now consistently use the declared `$installationObjectProcesses` property. A rerun under `E_ALL` emits no warning or deprecation before the intended missing-method failure.
- **Assertion language:** the required-data assertion now says `the installation object address`. Stable `ORDER-PREPARE-*` identifiers and unchanged normative artifact bytes remain the only relevant `order` occurrences in this test area.
- **Old-production guard:** the dedicated test cites `TERMINOLOGY-OBJECT-001` and checks all four interface statements literally: both old method names must be absent and both replacements must exist. `method_exists` is intentionally stricter than callability for the removals, so retaining an old private/protected shim also fails. The behavioral tests separately invoke the replacements, proving that mere non-public declarations cannot satisfy the new public seam.
- **Interface-test sensitivity:** current production still exposes `prepareOrder`, and the independently rerun test fails on exactly that first forbidden method (`Expected: false`, `Actual: true`). After it is removed, the next exact assertion detects `getOrderProcess`; subsequent assertions detect either missing replacement. A compatibility alias, partial rename, or simultaneous old/new API therefore cannot pass the combined corpus.
- **Sensitivity and independence:** exact literal expectations still cover the same 15 approved behaviors and would detect changed validation ordering, rejection contracts, audit projections, state/task transitions, immutable snapshots, assignment metadata, artifacts, or gate opening. Expected values remain specification literals rather than values sourced from production.

## Required changes

None.

Gate 3 is approved. Production implementation may perform only the agreed terminology migration, preserving all 15 reviewed behavioral expectations. The old public methods must be removed rather than retained as aliases.
