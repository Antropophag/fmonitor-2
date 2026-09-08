# Gate 5 code review: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 production safe-log v55

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/safe_log_gate5`
- Exact reviewed combined SHA: `af59aae41f6beafa7e88a513c75772a967bf52d1`
- Production implementation: `813d224ae4ba99a8d685fcfce48d161b27e7a3e4`
- Approved production-boundary RED: `848e082db54154574e6b4cabbf1597e4324eb147`
- Independent production-boundary Gate 3: `bbb6298cce223ec451d8030f62965fa6bc9d4ed5`
- Approved fixture correction: `9d1c0570feb4004cfd2420214482df7303f11315`
- Independent fixture Gate 3: `c4890db0dbe1e92f36e700c12ea18830c2a703cd`
- Gate 4 completion evidence: `af59aae41f6beafa7e88a513c75772a967bf52d1`
- Verdict: **CHANGES_REQUESTED**

## Independence and scope

I did not author the specification, planning amendment, tests, RED evidence,
production implementation, fixture correction, Gate 1/Gate 3 reviews, or Gate
4 evidence. This append-only record is my only repository change.

I reviewed the approved v55 production-config contract, approved executable
tests, all listed corrections/evidence, the production diff, current call sites,
safe-log construction/write behavior, application binding, and relevant
regressions. This verdict is limited to the production `safeLogFile` amendment;
it does not review or approve the separate parser correction or the complete
original-upload command.

## Exact reviewed artifact identities

```text
4cae80e141ad4caf758e792d0ae5a8383c32f6b9d6a779d6eace6122ec71b255  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
09fbfb8d9a5405989ad40150d011b557d8fea1ba030530fbf68ce06c3a710abb  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
482fb11555e9f10968a2485c2f22e863543c67fa78c41cd8148ce3f1b0e4ce09  openspec/changes/replace-pilot-registration-with-original-upload/design.md
42a03479da33ecc99f2b6e6f76600480a723e7a8c8e25296d33285584d8d3c88  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
dd4c5f05ddfadfcff57a8f4303165a806a43a4b339227407ceac6a37b8d7d2b2  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
c6ca5d3da46f861cc245f0b1b4684bdfe880e5bbe97c54e7e1c22fe835db18c3  app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php
7f90d23d59ba193ed3fa5f917625e0da0d58176e2ae76c3e6edca73b4efc926f  app/AssignmentOrderOriginal/AssignmentOrderOriginalFileStorage.php
09897fefe895c7aec19d2991b2237ab0996c00a1a5595051218334e05eb87907  tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
954e5efc1e97ea61eff4a70f972a335b6c4be30bfa19521521d4702a0cf959a5  tests/InstallationProcess/assignment_order_original_maintenance_001_test.php
cdec5a0acfc63f74f82b2f4ebea7ec8990ed184be1ae51592f5d09d15bf6cf43  tests/InstallationProcess/assignment_order_original_lease_race_001_test.php
4f438061c5b61556ab5bcac3c4bc134cf3711d5a247e19b17190036fe52f5fa2  docs/operations/assignment-order-original-production-safe-log-gate4-complete-2026-09-05.md
```

## Blocking finding

### G5-SAFELOG-1 — configured paths through a symlinked parent are accepted as canonical

The executable specification requires the configured `safeLogFile` path itself
to be absolute and canonical before any DB operation or private-root access.
`AssignmentOrderOriginalFileSafeLog::validate()` rejects lexical `.`/`..` and a
symlink at the final configured entry, but it never requires the configured
string to equal `realpath($file)`. `canonical()` therefore silently resolves a
path traversing a symlinked parent and accepts the resolved target. That is not
rejection of a non-canonical deployment input; it changes the configured path
identity before binding the logger.

Independent read-only reproduction on reviewed SHA:

```text
configured=/.../aoou-g5-.../alias/safe.log
resolved=/private/.../aoou-g5-.../real/safe.log
result=ACCEPTED
```

Here `alias` is a symlink to `real`; `safe.log` itself is an existing regular
current-EUID-owned exact-`0600` file. No DB or private-root resource is needed
to reproduce the acceptance. The approved test's `non-canonical` case covers
only a literal `/./` component and its `symlink` case covers only a symlink at
the final entry, so the current test remains GREEN under this plausible
regression/omission.

This is blocking because canonical-path rejection is an explicit owner-approved
security/configuration invariant. Correct it through the mandatory executable
test RED and fresh independent Gate 3 path, then make the production validator
reject this input with the fixed
`AssignmentOrderOriginalProductionConfigurationUnavailable` shape before DB or
private-storage access. A fresh Gate 5 must review the corrected exact SHA.

### G5-SAFELOG-2 — the opened inode's security attributes are not revalidated

Construction calls `validate($file)`, then performs a new `lstat($file)`, opens
with `fopen($file, 'ab')`, and compares only the latter `lstat` device/inode to
the opened descriptor's `fstat`. The device/inode comparison correctly catches
a replacement between that second `lstat` and `fopen`. It does not close the
earlier interval between `validate` and the second `lstat`: a replacement in
that interval becomes both `$before` and `$opened`, so the identity comparison
passes even though ownership, exact mode and regular-file type were checked
only on the displaced inode. The descriptor's `fstat` ownership/mode/type are
never checked.

This leaves a direct TOCTOU gap in the owner-approved exact regular/current-EUID/
`0600` condition. The correction must validate the security attributes on the
opened descriptor itself, as well as bind it to the intended configured entry,
and must close/fail with the fixed redacted exception on any mismatch. A
deterministic race-capable executable test and fresh independent Gate 3 are
required because the current approved test has no pathname-replacement race.

## Conforming behavior observed

- `ProductionConfig` has the mandatory third `safeLogFile` field and all current
  PHP call sites pass it explicitly.
- Missing, relative, lexical `/./`, final-entry symlink, non-regular, wrong-mode,
  device and controlled wrong-owner inputs fail with the fixed redacted
  construction exception. The test also proves no create/repair/replace for its
  covered cases.
- Safe-log construction is attempted before private-root validation and before
  construction/use of MariaDB adapters; the invalid-log sentinel observes zero
  DB calls and no private-root creation.
- For the interval from the immediate pre-open `lstat` through `fopen`,
  `lstat`/`fstat` device+inode equality pins writes to that identity. The
  persistent `ab` descriptor, exclusive lock, complete write check and flush
  provide real append behavior without truncation; pathname replacement after
  open cannot redirect that already-open handle. This positive property does
  not close `G5-SAFELOG-2`'s earlier validation-to-`lstat` race.
- The production factory binds the real logger into the public application
  dependencies. `useRequest()` is invoked at the public command seam, and the
  production cleanup probe observes the exact correlation/event/phase line
  without filename or injected exception detail.
- Production changes remain confined to the assignment-order application and
  adapter boundary; no domain logic was added to `rapid-pilot/` and no runtime
  DDL was introduced.

## Independent verification on exact reviewed SHA

All commands below exited `0`:

```text
php tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_PRODUCTION_BOUNDARY_OK
php tests/InstallationProcess/assignment_order_original_maintenance_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_MAINTENANCE_OK
php tests/InstallationProcess/assignment_order_original_lease_race_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_LEASE_RACE_OK
php tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_PDF_BOUNDARY_OK
php tests/InstallationProcess/assignment_order_original_pdf_parser_incremental_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_PDF_INCREMENTAL_RED_OK
php tests/InstallationProcess/assignment_order_original_upload_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_INITIAL_OK
php tests/InstallationProcess/assignment_order_original_upload_validation_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_AUTHORIZATION_OK
php tests/InstallationProcess/assignment_order_original_upload_remaining_contract_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_SHARED_ORACLES_OK
make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)
git diff --check
(no output)
```

GREEN regression output does not override `G5-SAFELOG-1` or `G5-SAFELOG-2`:
the approved suite exercises neither symlink traversal in an intermediate path
component nor a replacement between attribute validation and the pre-open
identity snapshot.

## Decision

Gate 5 is **CHANGES_REQUESTED** for exact reviewed SHA
`af59aae41f6beafa7e88a513c75772a967bf52d1`. The production safe-log slice is
not complete and cannot contribute an approval to the combined command Gate 5
until `G5-SAFELOG-1` and `G5-SAFELOG-2` are closed through the required
RED/review/GREEN/review sequence.
