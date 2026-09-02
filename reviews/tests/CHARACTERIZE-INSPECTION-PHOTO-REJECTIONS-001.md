# Test review: CHARACTERIZE-INSPECTION-PHOTO-REJECTIONS-001

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/photo_rejections_test_review_v1`
- Independence: this reviewer did not author the specification or test
- Test author: separately tasked test author (not this reviewer)
- Reviewed commit: `932662938837b28309fef2bf0fe3cadb2ce86e41` plus the uncommitted artifacts identified by the hashes below
- Specification: `specs/CHARACTERIZE-INSPECTION-PHOTO-REJECTIONS-001.md`, version `0.1`
- Test: `tests/Verification/characterize_inspection_photo_rejections_001_test.php`
- Public seam: `php rapid-pilot/verify-checklist-photo-rejections.php`
- Date: `2026-09-01`
- Verdict: `CHANGES_REQUESTED`

## Findings

### Blocking: the nominal-run storage cleanup assertion does not prove removal

The specification requires the meta-test to prove both owned storage namespaces
are removed. After each nominal run the test compares this expression with an
empty array:

```php
ciprArtifactState($artifactRoot . '/photo-reject-' . $tokens[$name])
```

`ciprArtifactState()` returns `[]` both when the path does not exist and when it is
an existing empty directory. Consequently a verifier that leaves each owned
storage directory behind after deleting its contents passes the asserted cleanup
contract. This is a plausible lifecycle regression and violates the explicit
"namespaces are removed" acceptance statement. Add an independent non-existence
assertion for each successful owned storage root (while retaining the content/leak
check).

### Traceability and expected-value independence

The test cites the exact specification and runs the specified public harness. Its
four milestone lines, order, terminal line, exit/status checks, and empty-stderr
checks are literal expectations copied from the specification rather than derived
from production validation code. It does not call `ChecklistSync`, `storePhoto`,
or verifier internals itself.

The PNG literal was independently decoded during review. It is 68 bytes,
`finfo(FILEINFO_MIME_TYPE)` reports `image/png`,
`getimagesizefromstring()` reports 1 by 1 pixels and `image/png`, and its SHA-256
is:

```text
431ced6916a2a21a156e38701afe55bbd7f88969fbbfc56d7fe099d47f265460
```

The independently recomputed SHA-256 of the four LF-terminated milestone lines is:

```text
d81a8b99ece0cfff99f32e0f5f535369349c6cce48fc6898ba7e7f193dc055b9
```

The MIME/content, size/content, hash/content, and control-character filename
rejections are therefore traceable to independently fixed spec examples. Exact
pilot rejection copy is intentionally not asserted.

### Determinism, setup classification, and isolation

The test supplies separate random valid 12-hex tokens to two nominal runs and
requires exact byte-identical stdout. SQL and storage collision probes fingerprint
their occupied namespaces and require exit `2`, empty stdout, and a single
`SETUP_FAILURE` stderr line. Missing-token, `/tmp`, and unavailable-database probes
also check the specified setup classification. Ambient and foreign-token SQL and
storage decoys are fingerprinted across the nominal and safety probes. Final
cleanup is bounded to generated exact paths, explicit decoys, and table names
matching a test-owned token grammar.

The successful-run SQL namespace check distinguishes absence because it enumerates
tables. The storage helper ambiguity described above is the only blocking
isolation/sensitivity defect found.

## Reproduced RED evidence

Disposable MariaDB was available and fixture/decoy setup completed. Exact command:

```text
php tests/Verification/characterize_inspection_photo_rejections_001_test.php
```

Result: exit `1`, stdout empty, stderr:

```text
RED_ASSERTION: missing public photo-rejection characterization verifier must become a successful first run; evidence={"first":{"status":1,"stdout":"","stderr":"Could not open input file: rapid-pilot/verify-checklist-photo-rejections.php\n"},"second":{"status":1,"stdout":"","stderr":"Could not open input file: rapid-pilot/verify-checklist-photo-rejections.php\n"}}
Expected: 0
Actual: 1
```

This is the intended qualifying RED: both invocations reach the missing public
harness, and the failure is neither database nor fixture setup. A post-run query
found no `photo_reject_*` or `characterize_photo_rejections_decoy_*` tables, and
no generated `characterize-photo-rejections-*` artifact root remained.

## Reviewed hashes

```text
ef590e32f055f9dfddee2c12664fba253f9b45741ea853ebea127994807280c5  specs/CHARACTERIZE-INSPECTION-PHOTO-REJECTIONS-001.md
9418c0e701bf683262d2699a78a9b4a1aa62295e43b256673ca5dfb1498f45a9  tests/Verification/characterize_inspection_photo_rejections_001_test.php
```

## Required changes

1. Make the nominal-run storage cleanup assertion distinguish an absent owned
   directory from an existing empty owned directory and require absence after
   each run.
2. Reproduce and retain a qualifying RED with the corrected test, then obtain a
   fresh independent Gate 3 review of the new test hash.
