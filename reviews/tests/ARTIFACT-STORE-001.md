# Test review: ARTIFACT-STORE-001

- Reviewer: `Codex agent /root/migration_test_review` (independent; did not author the specification, test, filesystem fixture, factory, renderer, service, or production implementation)
- Test author: `Codex agent /root`, working session `2026-08-28`
- Reviewed commit: working tree at HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Specification: [`specs/ARTIFACT-STORE-001.md`](../../specs/ARTIFACT-STORE-001.md), version `0.1`, `APPROVED 2026-08-28`
- Public/storage seams: public `prepareAssignmentOrder(...)`, factory-created `AssignmentOrderArtifactService::download(...)`, and configured `ContentAddressedArtifactStore` filesystem boundary
- Red command: `php tests/InstallationProcess/artifact_store_001_test.php`
- Initial verdict: `CHANGES_REQUESTED`
- Current verdict: `APPROVED`

## v0.2 secure-root and concurrency review

- Current v0.2 verdict: `APPROVED`

Focused evidence:

```text
php tests/InstallationProcess/production_composition_001_test.php
PASS: PRODUCTION-COMPOSITION-001
```

Exit code: `0`.

```text
php tests/InstallationProcess/artifact_store_001_test.php
PHP Fatal error:  Uncaught TestFailure: Missing required artifact storage root must fail closed. in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/artifact_store_001_test.php:114
Stack trace:
#0 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/artifact_store_001_test.php on line 114
```

Exit code: `255`.

The artifact RED is intended and specific: all prior durable prepare/download/security/corruption assertions plus the new secure-root fixtures pass first; current factory still accepts composition without the now-required storage root. The dependent composition regression is green with an explicit secure root.

- **Secure-root sensitivity present:** exact current-owner/mode assertions cover secure roots; direct store construction rejects a root symlink, an ancestor symlink, a group-writable root, and a secure child beneath a group-writable ancestor. Missing root, `/tmp`, relative, `/`, home root, and invalid factory roots require one redacted result. The swap store records root identity, publishes once, then rename/replacement must reject both write and read and leave replacement empty.
- **Deterministic identity swap:** the original root is atomically renamed to a unique sibling and a new `0700` directory is created at the old path. Exact dev/inode mismatch is deterministic without a timing race; separate cleanup covers replacement and original trees.
- **Concurrent first publish:** two forked independent stores target the same initially empty root and exact bytes. Both must return identical independent size/hash literals, and the final tree must contain exactly one full SHA blob with no temp/partial files. This is sensitive to non-idempotent mkdir/publish and overwrite races.
- **Prior coverage preserved:** public prepare/reload/download, exact bytes/metadata, auth-first hidden blob, invalid/traversal/unavailable/corrupt cases, full storage-failure rejection projection, external-source deletion, SHA layout and sequential idempotence remain unchanged before the new hardening cases.
- **Blocking shared-parent mutation:** both artifact and composition tests unconditionally run `chmod(.../.test-artifacts, 0700)` even when that directory pre-existed. Neither records/restores its prior mode nor removes it when created. A test must not silently change a shared workspace directory owned by the user/another run. Track whether the parent was created; only set mode on a newly created parent, otherwise `lstat` and fail closed if it is a symlink/wrong owner/insecure. Restore any deliberately changed pre-existing state (prefer no change) and remove a newly created parent only if still empty after child cleanup. Apply the same repair to the dependent composition test.
- **Blocking effective-account mismatch:** v0.2 explicitly defines the trusted account with `posix_geteuid()`, but the home-root fixture calls `posix_getpwuid(posix_getuid())`. Under a setuid/effective-account distinction this tests the real user's home while production correctly validates the effective account, causing misleading results. Resolve the home using the same independently asserted `posix_geteuid()` as the contract and cross-check its realpath/current `HOME` precondition explicitly.
- **Blocking child-reaping reliability:** the parent asserts each `pcntl_waitpid` result inside the same loop. If the first child exits nonzero, `assertSameValue` throws immediately and the second child is never waited for; `finally` can then delete `concurrentRoot` while that child is still writing and leave an unreaped process. Likewise a second `pcntl_fork()` failure leaves the first child live before the throw. Spawn bookkeeping must collect/reap every successfully created PID before any status assertion, including fork-failure/error paths; cleanup must occur only after all children are known exited. Assert `pcntl_*` availability before creating filesystem/DB fixtures or provide a deterministic supported runner.
- **No broad/destructive targets:** all adversarial roots themselves are unique descendants under the repo/home and cleanup enumerates exact paths; `/tmp`, `/`, and home are passed only to constructors expected to reject and are never mutated. That property should remain after harness repair.

## Required v0.2 changes

1. Make shared `.test-artifacts` parent handling non-mutating for pre-existing directories and symmetric for newly created parents in both artifact and composition tests.
2. Use effective UID, not real UID, for trusted-home fixture resolution and verify the same prerequisites as the v0.2 contract.
3. Rework concurrent-child handling so every created child is reaped before assertions or filesystem cleanup, including partial fork failure.
4. Preserve the current exact missing-root RED and all prior/new security expectations, then request fresh independent Gate 3 review. Gate 4 for v0.2 must not proceed yet.

## Fresh harness re-review (2026-08-28)

- Latest verdict: `CHANGES_REQUESTED`
- Shared-parent handling is now non-mutating: a pre-existing `.test-artifacts` is inspected with `lstat` and rejected unless it is a real, effective-UID-owned, non-group/other-writable directory. Only a parent created by this run is removed, and only after exact child cleanup and an emptiness check. The dependent composition harness has the same bounded behavior.
- Child bookkeeping now waits for every successfully recorded PID before checking fork success or any child status. Thus a partial second-fork failure and a nonzero first child cannot trigger filesystem cleanup while an already-created child is live. The final tree assertion still independently detects temp/partial files and non-idempotent publication.
- The secure-root, ancestor symlink/writeability, inode-swap, exact SHA layout, sequential/concurrent idempotence, prior public prepare/reload/download, authorization-first, corruption, and storage-failure sensitivities remain present.
- **Remaining blocker — effective-home fixture is still inconsistent:** the setup correctly derives `$trustedHome` with `posix_getpwuid(posix_geteuid())`, but the actual forbidden-home case at line 112 re-derives `$userHome` with `posix_getpwuid(posix_getuid())`. It also never performs the v0.2 contract's required `HOME`-when-set realpath equality cross-check. Under differing real/effective UIDs, the assertion can exercise the wrong home and either miss the effective-account boundary or fail for an unrelated reason. Reuse the already validated effective-account `pw_dir` for the invalid-root case and explicitly validate `realpath(getenv('HOME')) === $trustedHome` when `HOME` is set.

Fresh exact execution:

```text
php tests/InstallationProcess/artifact_store_001_test.php
PHP Fatal error:  Uncaught TestFailure: Missing required artifact storage root must fail closed. in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/artifact_store_001_test.php:114
Stack trace:
#0 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/artifact_store_001_test.php on line 114
```

Exit code: `255`. This is still the single intended production RED after all preceding assertions. Gate 4 remains blocked until the effective-home harness mismatch is corrected and independently re-reviewed.

## Final focused harness re-review (2026-08-28)

- Latest verdict: `APPROVED`
- The remaining effective-account blocker is resolved. The forbidden-home fixture now reuses `$trustedHome`, which was derived from `posix_getpwuid(posix_geteuid())`, rather than resolving the real UID. When `HOME` is set and non-empty, the test independently requires its `realpath` to equal that trusted effective-account home before exercising the boundary.
- Shared-parent validation remains read-only for a pre-existing parent and symmetric for a parent created by this run. Concurrent-child handling still records and waits for every successfully forked PID before any fork/status assertion, so cleanup cannot race an already-created writer.
- All previously reviewed public prepare/reload/download, authorization-first, exact bytes/hash/layout, corruption, storage failure, symlink/writeability/ancestor, inode-swap, sequential idempotence, concurrent publication, bounded cleanup, and no-`/tmp` sensitivity remains unchanged.

Exact RED reproduced:

```text
php tests/InstallationProcess/artifact_store_001_test.php
PHP Fatal error:  Uncaught TestFailure: Missing required artifact storage root must fail closed. in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/artifact_store_001_test.php:114
Stack trace:
#0 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/artifact_store_001_test.php on line 114
```

Exit code: `255`. The failure is the single intended missing-production-seam RED after all preceding assertions. Gate 3 for `ARTIFACT-STORE-001 v0.2` is approved for Gate 4 implementation.

## Signature/permission restart review (2026-08-28)

- Restart verdict: `APPROVED`
- Exact RED:

```text
php tests/InstallationProcess/artifact_store_001_test.php
PHP Fatal error:  Uncaught TestFailure: Pre-existing mode-0755 shard directory must not be accepted. in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/artifact_store_001_test.php:118
Stack trace:
#0 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/artifact_store_001_test.php on line 118
```

Exit code: `255`.

The RED is exact and sensitive to the reviewed Gate 5 permission gap. The unsafe-shard root is a unique current-effective-UID-owned `0700` fixture; only its pre-existing `sha256` child is deliberately `0755`, which is broader than the specified `0750` ceiling without relying on ownership failure. The unsafe-blob fixture independently creates current-effective-UID-owned `0750` shard components and an exact matching blob (`sha256('unsafe blob bytes') = 2cabac685682dd289347363a39bfb5e9efbdd60d61ef98b8edf63d3055fa90a7`) at mode `0666`, isolating the immutable-file permission ceiling from hash/content/path failures. Both require the exact redacted storage exception.

Both adversarial trees are random unique descendants of the already validated workspace parent, are enumerated in the existing strict descendant cleanup, and do not chmod or mutate shared/pre-existing filesystem objects. Root ownership/modes and every deliberate child mode are asserted before the public store seam. All prior secure-root, ancestor/symlink/swap, concurrent reap/idempotence, prepare/reload/download, authorization-first, corruption, failure-state, exact hash/layout, and bounded cleanup coverage remains intact. The artifact permission restart is approved for implementation.

## Required-constructor harness re-review (2026-08-28)

- Latest verdict: `CHANGES_REQUESTED`

Current execution is green:

```text
php tests/InstallationProcess/artifact_store_001_test.php
PASS: ARTIFACT-STORE-001
```

Exit code: `0`. The earlier reviewed permission RED at line 118 remains valid historical TDD evidence and the mode-`0755` shard plus mode-`0666` matching-blob assertions remain unchanged and now pass against the partial implementation.

The constructor-boundary correction itself is appropriate: after v0.2 makes the third `string` parameter required, omitting it cannot produce a config object and PHP must raise `ArgumentCountError`; expecting the old factory `InvalidArgumentException` at that call site would be impossible.

However, replacing the old missing-root case with only this constructor assertion weakens required v0.2 observability. Section 2 explicitly requires **both** `create(...)` and `createArtifactService(...)` to reject empty and uninitialized `artifactStorageRoot` with exact `InvalidArgumentException("Invalid artifact storage root.")` before adapters/SQL. The artifact test now exercises neither seam with an empty root and never exercises `createArtifactService` with an uninitialized config. The dependent composition test covers only `create` with an uninitialized config and accepts any `InvalidArgumentException` without checking the exact message; it does not close these service/empty-root gaps.

Required correction: retain the valid omitted-argument `ArgumentCountError` assertion, and additionally pass (1) an explicit empty-string config and (2) a constructor-bypassed uninitialized config through both public factory methods, requiring the exact redacted exception. For the uninitialized/empty service cases, use a recording/query-count boundary or another independent observation sufficient to prove rejection before SQL. Preserve the current green permission assertions and their recorded original RED evidence. Fresh Gate 3 approval is required before Gate 4/5 continuation.

## Validation-matrix re-review (2026-08-28)

- Latest verdict: `APPROVED`

The omission assertion remains correctly located at the typed constructor boundary and requires `ArgumentCountError`. A separate 2×2 matrix now exercises explicit empty-string and constructor-bypassed uninitialized configs through both `ProductionInstallationProcessFactory::create(...)` and `createArtifactService(...)`. Every cell independently records the thrown object and requires the exact class `InvalidArgumentException` plus exact redacted message `Invalid artifact storage root.`.

The matrix deliberately uses an already closed real `mysqli` connection. Therefore the expected validation result can occur only before connection/charset/SQL work; touching the connection would expose a different driver/initialization failure and fail the exact class/message assertions. The invalid config instances and expected literals are constructed independently of factory implementation. All four invocations are executed even when an earlier cell returns the wrong exception because the harness captures `Throwable` before asserting.

Exact RED reproduced:

```text
php tests/InstallationProcess/artifact_store_001_test.php
PHP Fatal error:  Uncaught TestFailure: create uninitialized config failure must be exact, redacted and contain no driver leakage.
Expected: 'Invalid artifact storage root.'
Actual: 'Invalid production installation process configuration.' in /home/antropophag/code/fmonitor-2/tests/bootstrap.php:27
Stack trace:
#0 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/artifact_store_001_test.php(116): assertSameValue()
#1 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/bootstrap.php on line 27
```

Exit code: `255`. This is an exact, deterministic v0.2 redaction RED. The prior permission RED evidence and current shard/blob assertions remain unchanged; no prior coverage or cleanup/isolation guarantee is weakened. Gate 3 is approved.

## Allowed-0755-root restart review (2026-08-28)

- Restart verdict: `APPROVED`

The new positive fixture accurately distinguishes deployment-owned root/ancestor policy from store-managed content policy. Its unique configured root is explicitly created and verified as current-effective-UID owned with exact mode `0755`; this satisfies the root contract's `mode & 0022 == 0`. Direct public `store` and verified `read` require exact independent size/hash/bytes. The resulting managed descendants are then walked and required to be no broader than `0750` for shard directories and `0640` for blobs, preserving the stricter managed-object ceilings. Existing negative fixtures still reject a `0755` **managed shard**, a `0666` matching blob, and group-writable roots/ancestors, so the positive case cannot weaken those distinctions.

The same `0755` root is subsequently passed through `ProductionInstallationProcessFactory::create(...)` and a real public prepare for isolated object `4515`; exact prepare result and complete artifact metadata prove coherent renderer/store/factory composition rather than constructor-only acceptance. The root is a random strict descendant of the validated shared parent and is included in the existing exact descendant-guarded cleanup. No pre-existing mode or shared path is mutated.

Exact RED reproduced:

```text
php tests/InstallationProcess/artifact_store_001_test.php
PHP Fatal error:  Uncaught InvalidArgumentException: Invalid artifact storage root. in /home/antropophag/code/fmonitor-2/app/InstallationProcess/ContentAddressedArtifactStore.php:20
Stack trace:
#0 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/artifact_store_001_test.php(101): FMonitor2\InstallationProcess\ContentAddressedArtifactStore->__construct()
#1 {main}
  thrown in /home/antropophag/code/fmonitor-2/app/InstallationProcess/ContentAddressedArtifactStore.php on line 20
```

Exit code: `255`. Failure occurs at the positive `0755` root boundary before its store operation; all preceding baseline assertions pass. The restart test is approved.

## Re-review after Gate 2 correction

All three prior blockers are resolved.

Before closing the preparation connection, the revised test deletes every legacy object row, the Workforce row, engineer user/role, and engineer capability. It explicitly verifies that only actor `18` with prepare capability and forbidden actor `19` with open capability remain. The fresh factory service must still return both exact blobs, so artifact lookup/hydration cannot depend on LegacyObject, Workforce, engineer directory, renderer state, or the original process object.

The storage-failure case restores only the independently required preparation sources for object `4514` and then asserts the complete public projection. It retains `needs_assignment_order`, no orders/assignments/tasks, false gates, and exactly one inherited `assignment_order_prepare_rejected` event with fixed time/actor and minimal payload. This correctly follows `ORDER-PREPARE-007`: rejection audit is required and is not partial success state; no version, artifact metadata, assignment, success event, or opening fact may remain.

Root validation now includes `/tmp`, relative path, filesystem root `/`, the resolved current user's home root, and a symlink created wholly under the workspace test parent. Every case requires the exact redacted invalid-root exception. Cleanup first unlinks only the exact symlink, then removes its exact target and the two other unique roots through the strict realpath-descendant guard. No `/tmp` fixture or broad recursive target is used.

Fresh RED:

```text
PHP Fatal error:  Uncaught TestFailure: Store must contain only two SHA-derived regular files and no filename/object/version path components.
Expected: array (
  0 => 'sha256/68/27/682749a063958eb102f5b184c4dfe6c21a009f77932b3b68b3b92e340adf4928',
  1 => 'sha256/da/33/da33d58efd35c6211d850446ee9f159526c9ba779fbdd9355b68ac35806ee3ac',
)
Actual: array (
) in /home/antropophag/code/fmonitor-2/tests/bootstrap.php:27
Stack trace:
#0 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/artifact_store_001_test.php(93): assertSameValue()
#1 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/bootstrap.php on line 27
```

Exit code: `255`.

Public production preparation and exact HTML metadata still complete before the intended missing blob-storage observation. SHA layout/idempotence, independent bytes, auth-first hidden availability, invalid/traversal/unavailable requests, same-size corruption, stable redacted errors, no process SQL assertions, and isolated workspace/database cleanup remain intact.

**Gate 3 verdict: `APPROVED`. Gate 4 may proceed without changing the reviewed expectations.**

## Captured red result

```text
PHP Fatal error:  Uncaught TestFailure: Store must contain only two SHA-derived regular files and no filename/object/version path components.
Expected: array (
  0 => 'sha256/68/27/682749a063958eb102f5b184c4dfe6c21a009f77932b3b68b3b92e340adf4928',
  1 => 'sha256/da/33/da33d58efd35c6211d850446ee9f159526c9ba779fbdd9355b68ac35806ee3ac',
)
Actual: array (
) in /home/antropophag/code/fmonitor-2/tests/bootstrap.php:27
Stack trace:
#0 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/artifact_store_001_test.php(92): assertSameValue()
#1 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/bootstrap.php on line 27
```

Exit code: `255`.

Production v1-v4 migrations, real prefixed sources, factory composition, public preparation, production HTML rendering, process persistence, and exact public artifact metadata all succeed before the intended missing blob-storage behavior is observed. The RED is not caused by root creation, MariaDB setup, renderer output, or an inherited prepare regression.

## Findings

- **Workspace-safe fixture:** both unique roots are created under repository `.test-artifacts`, never `/tmp`; cleanup resolves both parent and target, requires the target to remain a strict descendant, walks child-first without following directory symlinks, and removes only exact unique roots. Database cleanup is similarly exact and isolated. The shared test-parent directory itself is intentionally retained as a bounded workspace fixture parent.
- **SHA-derived layout and idempotence:** exact file names require only `sha256/68/27/<order-hash>` and `sha256/da/33/<appendix-hash>`, excluding filename, type, object, version, temporary files, and symlinks from successful storage. Preparing a second object with identical bytes must leave the file count at two, making duplicate/path-by-object writes observable. Expected hashes and paths are fixed literals, not derived from downloaded/production bytes.
- **Public prepare and independent bytes:** production factory/process is used; exact command result and public metadata match the approved HTML renderer. The two full HTML byte literals independently include one LF and later serve as exact download expectations. The test does not invoke renderer or a private store path builder.
- **Fresh download and authorization-first:** original process/connection is destroyed before a factory-created service downloads both types. Removing the appendix blob before actor `19` calls download makes any projection/store access after failed authorization observable; exact `FORBIDDEN` hides availability and capability converse is meaningful because actor `19` has only `installation.open`.
- **Request/path validation:** zero object/version, traversal-like `../order`, and wrong-case type must produce the exact redacted invalid-request exception after authorized entry. Missing version maps to the single exact unavailable exception. Filenames never enter fixture filesystem path construction; corruption targets only independently fixed SHA paths.
- **Integrity corruption:** same-size first-byte corruption preserves the metadata size while changing content/hash, so an implementation checking only existence/size fails. Exact unavailable exception prevents corrupt byte return and hides expected/actual path/hash details.
- **Storage failure mapping:** a regular `sha256` blocker under a separately valid root forces filesystem storage failure. Exact inherited render-failure result and empty orders assertion show no successful version/metadata is exposed. Full no-partial-state coverage is incomplete and blocking below.
- **Resolved external-free reload:** descriptive external rows are removed and their absence/remaining authorization-only rows are asserted before fresh service downloads both exact blobs.
- **Resolved storage-failure projection:** complete public equality includes the exact required rejection audit while excluding every partial success/process mutation.
- **Resolved root-validation gaps:** filesystem root, resolved home root, and workspace symlink join `/tmp`/relative rejection with exact safe cleanup.
- **Bounded-read and atomic-write boundary:** same-size corruption proves post-read hash verification, and exact file listing rejects leftover temp files after success/idempotence. The `metadata.size + 1`, flush/atomic publication, directory/file mode ceilings, lstat/fstat race defense, and oversized-file boundedness are difficult to prove fully through a real filesystem success tracer and remain mandatory Gate 5 implementation invariants. A small `size + 1`/oversized corruption unavailable case would improve behavioral sensitivity without attempting unsafe memory-pressure testing.
- **Root/config integration:** the config's third explicit root is used by both factory paths; a separate failure root and invalid-root cases prevent silently ignoring it. Exact exception text contains no configured path.
- **No process SQL side channel:** direct SQL creates migrations/preconditions and authorization/source fixtures only. Version/artifact process state is observed through public process projection/service; filesystem mutation is limited to approved blob availability/corruption preconditions.
- **Determinism/isolation:** HTML bytes, hashes, metadata, IDs, dates, clock, expected results, and fixture blockers are fixed. Random values affect only unique DB/prefix/root names under the workspace. Cleanup targets only those exact roots/database.
- **Scope:** no HTTP/range/streaming, public links, new capability, cloud/DB blob storage, orphan GC, backup, regeneration/PDF, or deletion semantics are claimed.

## Previously required changes (resolved)

1. Completed: fresh downloads run without descriptive external rows and with exact authorization-only rows verified.
2. Completed: object `4514` has a full exact failed projection with only the normative rejection audit.
3. Completed: `/`, home root, and workspace symlink are rejected and safely cleaned.
4. Completed: all prior strengths and intended RED remain and were independently rerun.
