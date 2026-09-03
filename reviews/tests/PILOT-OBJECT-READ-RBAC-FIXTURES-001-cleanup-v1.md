# PILOT-OBJECT-READ-RBAC-FIXTURES-001 — independent cleanup-oracle Gate 3 v1

- Date: 2026-09-04
- Reviewer: separately tasked agent `/root/object_list_cleanup_gate3`
- Test/oracle author: not this reviewer
- Reviewed commit: `36c4bdfe68b20d74bb249330ef2f0011a54cafb2`
- Specification: `PILOT-OBJECT-READ-RBAC-FIXTURES-001` v1, owner-approved SHA-256 `e3858f094c1f5c4411887b7a242122f714ec6d488febca87bd591553f5b05828`
- Public seam: production HTTP `GET /pilot/objects`
- Prior Gate 3: rereview v2 in `reviews/tests/PILOT-OBJECT-READ-RBAC-FIXTURES-001.md`
- Gate 5 finding returned this test-only oracle correction to Gates 2–3: `reviews/code/PILOT-OBJECT-READ-RBAC-FIXTURES-001-v1.md`
- Verdict: **APPROVED**

## Reviewed hashes

```text
e3858f094c1f5c4411887b7a242122f714ec6d488febca87bd591553f5b05828  specs/PILOT-OBJECT-READ-RBAC-FIXTURES-001.md
a534ca9cf726c01c1dbd0d3faeb3c4560197c23d6f2fc1b654afe49741c6e4cc  openspec/changes/pilot-object-read-rbac-fixtures/proposal.md
bd2cb1b9e48e2b8d8959d88d67b4591297d447f94856179ebaf8a1f18a7e891a  openspec/changes/pilot-object-read-rbac-fixtures/design.md
3128529b18a6226a6f66ebce2159bdf48ffb194f396869132cab179df99aabc2  openspec/changes/pilot-object-read-rbac-fixtures/specs/verification/pilot-object-read-rbac-fixtures/spec.md
71ab6211e1beb90e4af42ddcb6776b5008257d5202aec3e2a8e2bf2d4d0e921d  openspec/changes/pilot-object-read-rbac-fixtures/tasks.md
6c7929a4a599919d25eba2330c097e03450925435b0b50a49026d08cbc1e20f9  tests/Support/PilotObjectReadRbacFixture.php
62c3ad3cf0ed8ebe18fc07009b41e799e61ecc3c17921e27adaaf746898aa2cf  tests/InstallationProcess/pilot_object_list_001_test.php
755185d35822925b2931c6eadeffa1dbb165a0633c7bb4d4e91aed36a48e5002  docs/operations/pilot-object-read-rbac-fixtures-cleanup-red-v3.md
b9698848754dd57f149643a710eeebf47ab2d9d031c3bdc8b12fa065fc072249  reviews/code/PILOT-OBJECT-READ-RBAC-FIXTURES-001-v1.md
```

## Review result

The correction is one test-only setup operation: after creating the exact
task-owned foreign directory and its `keep` sentinel, the test performs one
owned `scandir()` before capturing the baseline `lstat()` records. This primes
the directory access time before the protected snapshot. A later read-only
guard traversal therefore cannot manufacture its own preservation failure
under Linux `relatime` semantics.

The correction does not remove, normalize, mask or ignore metadata. The
baseline and final oracle still compare complete raw `lstat()` arrays for both
the directory and sentinel file and the exact sentinel SHA-256. The foreign
path remains in the protected-path set. A production mutation of bytes, mode,
ownership, inode/link identity, size, access/change/modification time or other
captured stat data remains observable. Production and fixture implementation
are unchanged.

The focused public-seam execution advances through the canonical fixture
revoke tracer: deletion of exact role `5101`'s `objects.read` grant yields its
expected public `403`, after which the grant is restored. Its first and only
failure is the separately approved but not yet landed navigation-removal
predecessor at line 208 (`Expected: 0`, `Actual: 2`). Unwinding no longer emits
the former foreign-directory-atime assertion or an attempt-all cleanup error,
so the test retains the primary navigation RED without cleanup noise.

This is a deterministic correction of test observation, not a relaxation of
the acceptance contract or a substitute GREEN. Gate 4 may proceed only after
the navigation predecessor lands and this exact focused test can complete.

## Fresh independent verification

At exact reviewed HEAD:

```text
$ git rev-parse HEAD
36c4bdfe68b20d74bb249330ef2f0011a54cafb2

$ php -l tests/InstallationProcess/pilot_object_list_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_list_001_test.php

$ php -l tests/Support/PilotObjectReadRbacFixture.php
No syntax errors detected in tests/Support/PilotObjectReadRbacFixture.php

$ openspec validate pilot-object-read-rbac-fixtures --strict
Change 'pilot-object-read-rbac-fixtures' is valid

$ git diff --check 36c4bdf^..36c4bdf
exit 0

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/pilot_object_list_001_test.php
PHP Fatal error: Uncaught TestFailure: approved removal predecessor: no work item or root navigation destination
Expected: 0
Actual: 2
exit 255
```

The stderr contains no subsequent `foreign file bytes and metadata preserved`
or `attempt-all cleanup failure`. Post-run inventory found no `.test-artifacts`
entry named `pol-*` or `foreign-*`, no schema named `t_pol_*` or
`foreign_pol_*`, no principal named `pol_*` or `pold_*`, and no matching PHP
loopback server process.

## Gate 3 assessment

- **Traceability and seam:** unchanged from the approved rereview v2; the
  correction only stabilizes the required foreign-decoy preservation oracle.
- **Sensitivity:** strict byte hash and complete raw file/directory metadata
  equality remain. No protected field or path was removed.
- **Expected-value independence:** expected metadata is still captured before
  the production request; the priming read occurs before that capture.
- **Determinism and isolation:** priming targets only the verifier-owned foreign
  directory, before baseline, and fresh execution leaves no inspected residue.
- **RED classification:** the own fixture tracer is GREEN and the remaining
  exit is only the independently owned navigation RED; setup and cleanup are
  not the cause.

No blocking finding remains for this test-only correction. Gate 3 is
**APPROVED** for the exact reviewed hashes. This does not approve production
code, declare the complete focused test GREEN, or waive the fresh Gate 5 review
required after the navigation predecessor and relevant regressions pass.
