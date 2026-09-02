# Test review: CHARACTERIZE-INSPECTION-PHOTO-REJECTIONS-001 (v2)

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/photo_rejections_test_review_v2`
- Independence: this reviewer did not author the specification, test, or prior review
- Test author: separately tasked test author (not this reviewer)
- Reviewed commit: `932662938837b28309fef2bf0fe3cadb2ce86e41` plus the uncommitted artifacts identified by the hashes below
- Specification: `specs/CHARACTERIZE-INSPECTION-PHOTO-REJECTIONS-001.md`, version `0.1`
- Test: `tests/Verification/characterize_inspection_photo_rejections_001_test.php`
- Public seam: `php rapid-pilot/verify-checklist-photo-rejections.php`
- Date: `2026-09-01`
- Verdict: `APPROVED`

## Findings

### Prior blocking finding is resolved

The corrected nominal-run check now proves both conditions independently for
each exact owned storage root: `ciprArtifactState(...)` is empty and
`file_exists(...)` is false. Because an existing empty directory makes the latter
assertion fail, the test now explicitly distinguishes missing storage from empty
storage and catches the lifecycle regression identified in the prior review.

### Traceability, seam, and sensitivity

The test cites the exact approved specification and invokes only its stable public
harness entry point. It does not call `ChecklistSync`, `storePhoto`, a private
method, or production DDL. The two successful runs must return exit `0`, empty
stderr, the exact four milestones in specification order, and the exact terminal
line. Consequently a missing verifier, any missing rejection scenario, mutation
of revision/active-photo/blob observations exposed by the verifier, transcript
drift, nondeterministic output, or failure to clean either exact namespace is
observable. Exact pilot-only rejection wording is correctly not asserted.

The expected transcript is literal specification data rather than a value
obtained from production validation code. Independently decoding the fixture
produced 68 bytes; `finfo(FILEINFO_MIME_TYPE)` and
`getimagesizefromstring()` both reported `image/png`, the latter with dimensions
1 by 1. Its independently recomputed SHA-256 is:

```text
431ced6916a2a21a156e38701afe55bbd7f88969fbbfc56d7fe099d47f265460
```

The independently recomputed SHA-256 of the four LF-terminated milestone lines
in specification order is:

```text
d81a8b99ece0cfff99f32e0f5f535369349c6cce48fc6898ba7e7f193dc055b9
```

Thus the MIME/content mismatch, 67-versus-68 size mismatch, fixed all-zero
declared hash, and U+000A filename rejection examples are independently fixed
and traceable.

### Isolation, cleanup, determinism, and classification

Two nominal runs receive distinct random valid 12-hex tokens and must emit
byte-identical stdout. Before and after each run the test enumerates only the
escaped exact-token SQL prefix and inspects only the exact-token storage child.
Ambient and foreign-token SQL/storage decoys are fingerprinted and required to
remain byte-for-byte unchanged. Generated cleanup is bounded to recorded tables
matching the owned-name grammar, explicit test decoys, exact generated token
children, and the private generated artifact root.

The test separately exercises occupied SQL and occupied storage namespaces,
requiring exit `2`, empty stdout, one `SETUP_FAILURE` stderr line, preservation of
the occupied namespace, and absence of mutation in the other namespace. It also
checks `/tmp`, missing token, and unavailable database as setup failures. Nominal
assertion/transcript drift remains exit `1` through the test harness. Fixture and
database setup failures are distinguishable from the focused RED.

## Reproduced RED evidence

Disposable MariaDB was available and fixture/decoy setup completed. Exact
command:

```text
php tests/Verification/characterize_inspection_photo_rejections_001_test.php
```

Result: exit `1`, stdout empty, stderr:

```text
RED_ASSERTION: missing public photo-rejection characterization verifier must become a successful first run; evidence={"first":{"status":1,"stdout":"","stderr":"Could not open input file: rapid-pilot/verify-checklist-photo-rejections.php\n"},"second":{"status":1,"stdout":"","stderr":"Could not open input file: rapid-pilot/verify-checklist-photo-rejections.php\n"}}
Expected: 0
Actual: 1
```

This is the qualifying intended RED: both executions reached the missing public
harness, rather than failing in database connection, fixture setup, permissions,
or an unrelated suite. A post-run query found no `photo_reject_*` or
`characterize_photo_rejections_decoy_*` tables; no generated
`characterize-photo-rejections-*` artifact root remained.

## Reviewed hashes

```text
ef590e32f055f9dfddee2c12664fba253f9b45741ea853ebea127994807280c5  specs/CHARACTERIZE-INSPECTION-PHOTO-REJECTIONS-001.md
c36bf874f0fd7353fff16dbe02a7ec8af7bd9187f582640c96b027ef1a73d5a2  tests/Verification/characterize_inspection_photo_rejections_001_test.php
```

## Required changes

None.
