# Assignment-order original production safe-log Gate 4 focused GREEN — 2026-09-05

Append-only implementation evidence for `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001`
v55. This record does not constitute Gate 5 review or completion of Gate 4 while
the two named stale maintenance fixtures still use the superseded two-argument
production config constructor.

## Approved inputs

- Approved RED commit: `848e082db54154574e6b4cabbf1597e4324eb147`.
- Independent Gate 3 approval and implementation base:
  `bbb6298cce223ec451d8030f62965fa6bc9d4ed5`.
- Tests, executable spec, OpenSpec artifacts, configuration and review records
  were not modified by this implementation pass.

## Minimal production change

- `AssignmentOrderOriginalProductionConfig` requires the exact third
  `safeLogFile` argument.
- Invalid production safe-log construction maps to the fixed
  `AssignmentOrderOriginalProductionConfigurationUnavailable` shape.
- Production factory validates and opens the safe log before private-root
  validation and before constructing any database adapter.
- Validation accepts only an absolute lexically canonical existing configured
  entry that is non-symlink, regular, effective-user-owned and exact `0600`;
  the factory does not create or repair it and binds its resolved identity.
- The real file observer retains an append-only descriptor, verifies opened
  identity against the configured entry, and receives request correlation from
  the public command seam before emitting cleanup/release diagnostics.

Pre-commit production file hashes:

- `app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php`:
  `c6ca5d3da46f861cc245f0b1b4684bdfe880e5bbe97c54e7e1c22fe835db18c3`.
- `app/AssignmentOrderOriginal/AssignmentOrderOriginalFileStorage.php`:
  `7f90d23d59ba193ed3fa5f917625e0da0d58176e2ae76c3e6edca73b4efc926f`.
- Production diff SHA-256 before this evidence file:
  `47d398f46d2b886c3d25d2de44cdd2982b573480fe82c7ef0a0a24d771202eb1`.

## Verification transcript

Focused reviewed production seam:

```text
$ php tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_PRODUCTION_BOUNDARY_OK
exit 0
```

Relevant unchanged upload/parser regressions:

```text
assignment_order_original_upload_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_INITIAL_OK
exit 0

assignment_order_original_upload_validation_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_AUTHORIZATION_OK
exit 0

assignment_order_original_upload_remaining_contract_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_SHARED_ORACLES_OK
exit 0

assignment_order_original_pdf_parser_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_PDF_BOUNDARY_OK
exit 0

assignment_order_original_pdf_parser_incremental_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_PDF_INCREMENTAL_RED_OK
exit 0
```

Boundary and diff checks:

```text
$ make architecture-check
exit 0
$ git diff --check
exit 0
```

## Known stale executable fixture call sites

Strict mandatory construction correctly exposes two pre-amendment call sites;
they were not edited because this Gate 4 role is prohibited from changing
tests. Both currently stop with `ArgumentCountError` before their assertions:

- `tests/InstallationProcess/assignment_order_original_maintenance_001_test.php:8`
  (and further two-argument constructions in the same file);
- `tests/InstallationProcess/assignment_order_original_lease_race_001_test.php:11`.

Their fixture adaptation requires the prescribed executable-test Gate 2/Gate 3
path. Therefore this record proves the focused production GREEN but explicitly
does not claim complete Gate 4 or authorize Gate 5.
