# ChecklistSync projection reader loading — independent integration Gate 3 review

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/http_auth_uppercase_rereview`
- Reviewed commit: `dc361aa5bb6a438d72d4a5d6abebe830527a9d70`
- Regression source: `cd72c4cdaa4619a7642453d6ce3319a502927aeb`
- Public seam: direct, previously reviewed `ChecklistSync::accept(...)` and `ChecklistSync::projection(...)` characterization entrypoints
- Verdict: **APPROVED**

## Finding and boundary decision

The five existing characterization paths are valid and sensitive. They load
`app/PilotHttp/ChecklistSync.php` directly by design and exercise its public
behavior against isolated MariaDB/storage fixtures. Their prior independent
Gate 3/Gate 5 records approve this direct public seam, deterministic expected
values, namespace isolation, replay/concurrency checks and bounded cleanup.
They are not private-method tests and do not depend on production HTTP
bootstrap side effects.

Commit `cd72c4c` moved projection revision/installer-snapshot reads into the new
`MariaDbChecklistProjectionStateReader`, then instantiated that class from
`ChecklistSync::projection()` without loading its file. Every reviewed direct
entrypoint consequently reaches the same exact runtime error:

```text
Class "FMonitor2\PilotHttp\MariaDbChecklistProjectionStateReader" not found
at app/PilotHttp/ChecklistSync.php:41
```

This is a production composition regression, not broken fixture setup. The
entrypoints successfully create their owned schemas/fixtures and reach the
public projection seam; their meta-tests classify the child failure as
`REGRESSION_FAILURE`, preserve ambient/foreign state and clean owned state.
The identical fault across independently shaped current-crew, upload,
rejection, revoke and limit/concurrency scenarios is strong sensitivity to the
missing dependency ownership.

`ChecklistSync.php` is the correct loading boundary. It already explicitly
owns `require_once` for `MariaDbInstallationCaseIdResolver` and the complete
InspectionEvidence collaborator set required when this public adapter is loaded
without the application autoloader. Requiring every approved rapid-pilot
entrypoint to know the new private composition collaborator would duplicate
production dependency knowledge in five test/oracle callers and leave other
direct consumers fragile. Relying only on `app/autoload.php` would silently
change the established standalone public-file contract and would not repair
these reviewed entrypoints.

The smallest conforming Gate 4 production correction is therefore exactly:

```php
require_once __DIR__.'/MariaDbChecklistProjectionStateReader.php';
```

in `app/PilotHttp/ChecklistSync.php`, beside its existing direct collaborator
require. No test expectation, SQL, projection behavior, HTTP route, domain
state, migration, or rapid-pilot file needs to change.

## Independent RED reproduction

The following four approved meta-tests were run against the active disposable
MariaDB endpoint; all fail twice or on their first nominal child invocation
with the exact missing-class regression and no product assertion drift:

```text
tests/Verification/characterize_inspection_photo_upload_001_test.php
tests/Verification/characterize_inspection_photo_rejections_001_test.php
tests/Verification/characterize_inspection_photo_revoke_001_test.php
tests/Verification/characterize_inspection_photo_limit_concurrency_001_test.php

Expected meta-test status: 0
Actual child status: 1
Child stderr: REGRESSION_FAILURE: Class
"FMonitor2\PilotHttp\MariaDbChecklistProjectionStateReader" not found
```

The direct current-crew verifier was also run with its actual database
configuration:

```text
$ FMONITOR_DB_HOST=127.0.0.1 \
  FMONITOR_DB_PORT=23306 \
  FMONITOR_DB_NAME=fmonitor2_test \
  FMONITOR_DB_USER=root \
  FMONITOR_DB_PASSWORD=fmonitor2_test_root_local \
  php rapid-pilot/verify-checklist-current-crew.php

PHP Fatal error: Uncaught Error: Class
"FMonitor2\PilotHttp\MariaDbChecklistProjectionStateReader" not found
in app/PilotHttp/ChecklistSync.php:41
```

The earlier invocation with unrelated `FMONITOR_TEST_*` variables failed at DB
authentication and is excluded from RED evidence; the corrected invocation
above reaches the intended public seam.

## Reviewed entrypoints and sensitivity

- `verify-checklist-current-crew.php` requires exact latest registered crew
  while preserving historical installer attribution.
- photo upload requires accepted/replayed projection and exact stored metadata;
  it cannot pass without `projection()`.
- photo rejections require zero accepted facts/revision/blob state after each
  rejected command, observed through `projection()` and independent fixture
  audits.
- photo revoke requires the immutable upload/revoke sequence and projected
  absence after revocation.
- photo limit/concurrency requires winner-neutral serialization, exact revision,
  projection count and no loser mutation.

An empty/stub replacement reader, swallowed missing class, bypassed projection,
or caller-local fake cannot satisfy these independent result matrices. The
tests therefore cover much more than class existence while isolating this RED
at the earliest newly missing dependency.

## Reviewed hashes

```text
22ea2d7f0455f1778522f807ef9e0ead1303c5205762b2ba1eee1e124f87dfeb  app/PilotHttp/ChecklistSync.php
ff41533ddd438697cc3e2bb609be6e2ba97fb1784da9751cd074615ce46d28f4  app/PilotHttp/MariaDbChecklistProjectionStateReader.php
361732cf785361aaecc525ec2b0e9d574890e46a177afdfb53ed6b14428e7eb8  rapid-pilot/verify-checklist-current-crew.php
9a96af122b9971a2c0e34b28ae7538b97f296b40250ec39bf2a656852edd5125  rapid-pilot/verify-checklist-photo-upload.php
cff96c450a7ceb4a01fd2de4094ba0cd0b4ad80f2e24437f3964777db943f64c  rapid-pilot/verify-checklist-photo-rejections.php
d88c7fd2c785e76ddbad7bae9d9fdfb554b2aec808c092a18ce42f8c79163a5b  rapid-pilot/verify-checklist-photo-revoke.php
db717907d77c5c5c6a303fff2d7e3138a5170b8dc18fc72ad511fdeeb0ef3f91  rapid-pilot/verify-checklist-photo-limit-concurrency.php
fafc95fc890d03e51a1c441bab4f966f9fad34c55a854ee3bb7db5d25fbea850  tests/Verification/characterize_inspection_photo_upload_001_test.php
5ac59151062fa3b98a2b62d4508c9d40c2f9a2f33eb758d0109bea7e04dc5446  tests/Verification/characterize_inspection_photo_rejections_001_test.php
672212f3162af10e2b38d1dc4431dabc49793cc7a44edc353bbbc52a73c7a51f  tests/Verification/characterize_inspection_photo_revoke_001_test.php
76390192a1b9d8afb7481d31a400284262fb7f718dbcdf05b69c9f94f4cb37cc  tests/Verification/characterize_inspection_photo_limit_concurrency_001_test.php
```

## Verdict

**APPROVED.** The demonstrated RED is deterministic, reaches the reviewed
public seam and fails for the missing production composition dependency. Gate 4
may add only the one `require_once` above, then must run all five focused paths
and the relevant characterization aggregation unchanged before independent
Gate 5 review.
