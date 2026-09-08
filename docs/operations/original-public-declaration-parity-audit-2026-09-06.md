# ORIGINAL-UPLOAD-001 v61 public declaration parity audit

Date: 2026-09-06. Reviewer: separately tasked agent `/root/admission_oracle_gate3`.
Repository HEAD: `8bf040c72898ca9182267ac0b08bef9fceb7f3bb`.
Requested source checkpoint: `738b9adf00e6e808e0a89f5899ce5db412daa7e6`.
There is no AssignmentOrderOriginal production diff between that checkpoint and
the reviewed HEAD.

Scope is read-only declaration inventory. No product behavior, test, production
code, specification, database or external system was changed. PHP lint and
source/reflection-compatible inspection only were used.

## Method and boundary

Every namespace-level enum, interface, DTO, exception and public factory shown
in a normative PHP block of active `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v61 was
compared with all `app/AssignmentOrderOriginal/*.php` declarations and the
InstallationProcess schema classes they load. Test bootstrap and direct
`require_once` paths were inspected so a class implemented outside Runtime was
not falsely reported missing.

Illustrative method bodies inside otherwise normative declarations were not
treated as byte-level implementation requirements. Verification-only seams are
still public contract declarations when the spec explicitly names them; they
are classified separately below rather than mistaken for runtime application
dependencies. Extra internal concrete values/helpers are not findings unless
they replace or contradict a promised public declaration.

## Priority 0 — command and observer contract mismatches

### P0-1. PDF inspection construction is too open and incomplete

Normative declaration:

- `AssignmentOrderOriginalPdfInspection::__construct(...)` is private;
- factories are `passive()`, `invalid()`, `unsafe()` and `failed()`;
- `failed()` returns `INSPECTOR_FAILED`.

Actual Runtime has a public constructor and only the first three factories.
`AssignmentOrderOriginalPdfStatus::INSPECTOR_FAILED` itself exists. A caller can
currently construct arbitrary inspection values directly, while an inspector
cannot use the promised total `failed()` factory.

Required bounded correction: make the constructor private and add exact public
`failed(): self`. Existing inspectors/tests that call the public constructor must
move to the four factories through their own Gate 2/3 correction.

### P0-2. Three storage observer enum cases are absent

`AssignmentOrderOriginalStorageEvent` lacks:

- `DIGEST_LOCK_ACQUIRED='digest_lock_acquired'`;
- `DELETE_BEGIN='delete_begin'`;
- `DELETE_DONE='delete_done'`.

The eight upload-stage cases match, including values. These three cases are
required by the maintenance storage observer contract and must be added exactly;
their event-emission behavior requires separate executable review.

### P0-3. Three approved fault points are absent

`AssignmentOrderOriginalFaultPoint` lacks:

- `REQUEST_LOOKUP='request_lookup'`;
- `FINGERPRINT_LOOKUP='fingerprint_lookup'`;
- `LINEAGE_LOOKUP='lineage_lookup'`.

All other v61 cases are present with exact backing values. The missing cases are
part of the public deterministic verification port, not optional internal names.
Add them before a combined declaration/behavior review; do not convert them to
environment or production selectors.

### P0-4. Parser algorithm constant is not public as declared

The normative `FMonitorPassivePdfInspector` exposes
`public const ALGORITHM_ID='fmonitor-passive-pdf-v1'` and `algorithmId()` returns
it. Actual code returns the same literal but has no public `ALGORITHM_ID` constant
(its only constant is private `ACTIVE`). This is a public declaration mismatch
even though current behavior returns the right string.

## Priority 1 — private storage and maintenance public surface

### P1-1. Orphan DTO and kind enum are missing

These normative declarations do not exist in any loaded production file:

- `AssignmentOrderOriginalOrphanCandidate` with public readonly fields
  `(AssignmentOrderOriginalOrphanKind $kind, string $opaqueIdentity,
  ?string $sha256, int $byteSize, string $createdOrFinalizedAtUtc)`;
- `AssignmentOrderOriginalOrphanKind` with exact cases
  `ABANDONED_STAGE` and `FINALIZED_CONTENT`.

`AssignmentOrderOriginalPrivateOrphanFixtureKind` is a different verification
fixture enum and does not satisfy the storage-page contract.

### P1-2. Orphan page and digest lock interfaces are empty

Actual Runtime declares both names but no methods. Normative methods are:

```text
AssignmentOrderOriginalOrphanPage:
  status(): AssignmentOrderOriginalStorageStatus
  candidates(): array
  nextCursor(): ?string

AssignmentOrderOriginalDigestLock:
  status(): AssignmentOrderOriginalStorageStatus
  opaqueIdentity(): string
  release(): void
```

This prevents the maintenance application from depending on the promised typed
storage boundary. Add the exact methods and update implementations/fixtures only
through reviewed tests.

### P1-3. Public private-storage factory is missing

`AssignmentOrderOriginalPrivateStorageFactory::create(string
$absolutePrivateRoot, AssignmentOrderOriginalStorageObserver $observer,
AssignmentOrderOriginalFaultInjector $faults): AssignmentOrderOriginalPrivateStorage`
does not exist. Direct construction of `AssignmentOrderOriginalFileStorage` in
other compositions is not this promised public factory.

### P1-4. Maintenance interfaces and DTOs are missing

The following normative declarations are absent:

- `AssignmentOrderOriginalMaintenanceApplication`;
- `AssignmentOrderOriginalMaintenanceAuthorizer`;
- `AssignmentOrderOriginalMaintenanceCommit` with the exact eleven public
  readonly constructor properties;
- `AssignmentOrderOriginalMaintenanceResultLookup`;
- `AssignmentOrderOriginalMaintenanceRepository`;
- `AssignmentOrderOriginalMaintenanceDependencies` with the exact eight public
  readonly typed dependencies;
- `ProductionAssignmentOrderOriginalMaintenanceFactory`.

`AssignmentOrderOriginalMaintenanceResult` exists, and its required methods
match. The actual `AssignmentOrderOriginalMaintenanceService` does not implement
the missing application interface and directly owns mysqli/config/auth/clock/
faults instead of the normative dependency ports.

### P1-5. Maintenance verification factory signatures/modifiers mismatch

Normative:

```text
final AssignmentOrderOriginalMaintenanceVerificationFactory::create(
    AssignmentOrderOriginalMaintenanceDependencies $dependencies
): AssignmentOrderOriginalMaintenanceApplication
```

Actual factory extends a non-final
`AssignmentOrderOriginalRealMaintenanceVerificationFactory` and inherits
`create(mysqli, ProductionConfig, MaintenanceAuthorization, Clock,
FaultInjector): AssignmentOrderOriginalMaintenanceService`. It therefore has a
different arity, named parameters, types and return contract.

The separate normative real-maintenance verification factory does promise those
five arguments, but is declared `final`; actual makes it non-final solely to
inherit from it. Both public declarations must coexist without inheritance:
make the real factory final with its five-argument seam, and give the ordinary
verification factory its exact one-dependency method.

### P1-6. Private orphan fixture is a class where an interface is promised

Normative `AssignmentOrderOriginalPrivateOrphanFixture` is an interface with
`create(Command): void`. Actual code declares a final concrete class of the same
name. Its factory consequently returns that concrete replacement. The method
signature is behaviorally similar, but class kind and substitutability are part
of the public contract. Introduce the interface and a separately named private
implementation; retain the exact factory return interface.

## Priority 2 — evidence reader and worker verification seams

### P2-1. Evidence-reader config public property names mismatch

Normative constructor/property names:

```text
databaseHost, databasePort, databaseName, databaseUser,
databasePasswordFile, tablePrefix, privateStorageRoot, safeLogFile
```

Actual names:

```text
host, port, database, user,
passwordFile, tablePrefix, privateStorageRoot, safeLogFile
```

Types, order and readonly/public visibility otherwise match. Positional callers
hide this mismatch, but PHP named construction is public behavior.

### P2-2. Evidence exception message is wrong

Normative `AssignmentOrderOriginalEvidenceUnavailable` constructor fixes message
`Assignment-order original evidence unavailable.`, code `0`, previous `null`.
Actual constructor uses `AssignmentOrderOriginalEvidenceUnavailable`. Class and
inheritance match; public fixed exception value does not.

### P2-3. Evidence reader interface is replaced by a concrete class

Normative `AssignmentOrderOriginalEvidenceReader` is an interface with eleven
methods: the ten canonical JSON readers plus `close(): void`. Actual code declares
a final concrete class with that name. It has the listed methods, but the factory
return is the concrete replacement and the promised interface does not exist.
Rename the implementation and implement the exact public interface. This seam is
verification-only and must not be wired as a second command repository.

### P2-4. Worker DTO and byte-stream factory are missing

The following verification declarations are absent:

- `AssignmentOrderOriginalByteStreamFactory::fromBase64(string):
  AssignmentOrderOriginalByteStream`;
- readonly `AssignmentOrderOriginalWorkerConfig` with exact twelve public
  constructor properties: `databaseDsn`, `databaseUser`,
  `databasePasswordFile`, `tablePrefix`, `privateStorageRoot`, `safeLogFile`,
  `clockUtc`, `rootIdSequenceCsv`, `revisionIdSequenceCsv`, `inspectorMode`,
  `?faultPoint`, `barrierEvent`.

The worker currently parses an untyped array internally. That does not satisfy
the normative public DTO/factory declarations.

### P2-5. Worker bootstrap named parameters mismatch

Normative `AssignmentOrderOriginalVerificationWorkerBootstrap::run` parameters:

```text
$configJsonPath, $commandReadFd, $barrierReadFd,
$barrierWriteFd, $resultWriteFd
```

Actual names:

```text
$configPath, $commandFd, $releaseFd, $readyFd, $resultFd
```

All types/order/return type match. Named arguments do not. The logical direction
names matter because the spec distinguishes two full-duplex barrier endpoints.

## Confirmed matching core declarations

The following active command surface matches in name, kind, cases or signatures
at the reviewed source, subject to behavior review rather than declaration fixes:

- command mode/status/reason enums;
- authorization, composition lookup, stream-read, PDF-status, storage-status,
  commit-status, lookup-status and now-correct four-case ID-status enums;
- lifecycle enum;
- upload, command, stream-read, composition snapshot, ID result, accepted commit,
  attempt commit and production config constructor property order/types;
- application/result/authorizer/composition/clock/ID/stream/inspector/content/
  lease/stage/storage outcome/private storage/storage observer/repository/
  lineage/reference/lifecycle/fault/safe-log/request-safe-log/delivery interfaces;
- verification factory and production command factory public methods;
- schema migration status/result/application/observer/factory surface and
  verification fixture public methods, loaded through their separate files;
- maintenance status/reason enums, reconcile command, maintenance result methods,
  maintenance authorization and private-orphan fixture command/kind;
- evidence-reader factory method name/arity and the canonical method set on its
  current concrete reader;
- safe-log owner/policy declarations governed by their separate approved
  amendment.

`AssignmentOrderOriginalCurrentEvidenceLookup` and
`AssignmentOrderOriginalAssignmentLineageRepository` are additive internal
interfaces supporting later v61 behavior; they do not replace a missing named
normative declaration and are not findings.

## Recommended bounded correction order

1. Correct P0-1 through P0-4 with one declaration-only RED/Gate 3 package. This
   is the smallest prerequisite for the next combined command review.
2. Correct P1 as one coherent maintenance/storage-port package. Adding enum cases
   alone is insufficient while the page/lock/application ports remain absent.
3. Correct P2 as a verification-composition declaration package, preserving its
   isolation from production runtime selection.
4. Run an exact reflection/declaration verifier after direct Runtime import and
   again after loading each separate factory file. It should compare enum cases,
   class/interface/final/readonly kind, constructor visibility, public property
   names/types/readonly status, public method names/staticness/parameter names,
   types/defaults/return types and factory arity.
5. Only after declaration GREEN, run the already required behavioral regressions
   and independent combined code review. Declaration parity does not approve the
   implementation bodies.

Do not satisfy missing public interfaces with test-only aliases or conditional
declarations. Do not move verification-only factories into production
composition. Any correction that changes an outcome, authorization, persistence
or lifecycle rule returns to Gate 1; the inventory above requires no such product
decision.

## Exact reviewed hashes

```text
d65470e2e1da510aa1ebe6d9cce6549b8fffcc6bf66035716623eb0cad61f890  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
d8ca5ba2f38d463b8f84b0e91b045412f5c625a0694f0fe51e037ed98453d00a  app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php
1a59ecc5ec45470ff76a6c043e29c67bdb89b641b298851540a050b46d6fe394  app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php
0a9c81f0cd173ae1e75262bcae5e3ae88b6d662476eb284785564b5008a4456c  app/AssignmentOrderOriginal/AssignmentOrderOriginalFileStorage.php
232c56f7009ee4416a35d3942dc2ee46053dc383fb0c11e769f418337f9a158d  app/AssignmentOrderOriginal/MariaDbAssignmentOrderOriginalEvidence.php
8a7e9a58199afbebaf2d2e82ca758bbeb4f9d44909ff5fbc953a6c6432b6e634  app/AssignmentOrderOriginal/MariaDbMaintenanceService.php
8b7f409708d2719e45b047129deace754a5b592dfd9acceff6fd65e77dc464fa  app/AssignmentOrderOriginal/AssignmentOrderOriginalEvidenceReaderFactory.php
8b7f409708d2719e45b047129deace754a5b592dfd9acceff6fd65e77dc464fa  app/AssignmentOrderOriginal/AssignmentOrderOriginalPrivateOrphanFixtureFactory.php
45bca621e33f34c3417436d7f075605a63cbfeeaffa26356c8fff4a593ae1ec6  app/AssignmentOrderOriginal/AssignmentOrderOriginalRealMaintenanceVerificationFactory.php
952a302ac7d27ed009af9e3331780617cff26a0e045a96edc4815f77d2beb714  app/AssignmentOrderOriginal/AssignmentOrderOriginalVerificationWorkerBootstrap.php
```
