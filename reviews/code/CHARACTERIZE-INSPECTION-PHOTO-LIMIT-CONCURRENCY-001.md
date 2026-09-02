# Code review: CHARACTERIZE-INSPECTION-PHOTO-LIMIT-CONCURRENCY-001

- Gate: 5 — fresh independent code review
- Reviewer: separately tasked agent `/root/photo_limit_concurrency_code_review_v1`
- Independence: this reviewer did not author the specification, test, verifier, or Gate 3 review
- Reviewed commit: dirty working tree at HEAD `932662938837b28309fef2bf0fe3cadb2ce86e41`, limited to the exact manifest below
- Specification: `specs/CHARACTERIZE-INSPECTION-PHOTO-LIMIT-CONCURRENCY-001.md`, version `0.1`
- Approved test review: `reviews/tests/CHARACTERIZE-INSPECTION-PHOTO-LIMIT-CONCURRENCY-001-v4.md`, verdict `APPROVED`
- Date: `2026-09-01`
- Verdict: `CHANGES_REQUIRED`

## Exact reviewed manifest and hashes

```text
e9481cf5239c407c52383a91289c4d17779ef32b6dd3da82d1aff9a1c6dfd820  specs/CHARACTERIZE-INSPECTION-PHOTO-LIMIT-CONCURRENCY-001.md
76390192a1b9d8afb7481d31a400284262fb7f718dbcdf05b69c9f94f4cb37cc  tests/Verification/characterize_inspection_photo_limit_concurrency_001_test.php
fc50f5f81d9dfd1923686679da2dcc2dc7635b97276ad02540c4fab3e50d19ae  reviews/tests/CHARACTERIZE-INSPECTION-PHOTO-LIMIT-CONCURRENCY-001-v4.md
138114b386689c5c97777a7a0a25b0b20d545a2c0dc2f86270f0c1c9ae7a0de0  rapid-pilot/verify-checklist-photo-limit-concurrency.php
55ecabc9a79f14ac45b2eda6c4d916f0d65b457603e689044b700d5d6c48a587  tools/verification/run.sh
```

Only the new photo-limit concurrency verifier and its single characterization-runner entry are attributed to this Gate 4 implementation. No production behavior file is part of the reviewed implementation.

## Standards

No blocking standards finding.

The verifier stays within rapid-pilot's permitted characterization boundary and invokes the public `ChecklistSync::accept(...)` and `ChecklistSync::projection(...)` seams. Each contender is a separate child process with a separately opened MariaDB connection; both publish readiness before the parent creates one release barrier. The reviewed test independently checks distinct PIDs and connection IDs.

Fixture DDL and audit SQL are limited to the strictly validated `photo_limit_<12 hex>_` namespace. Storage is limited to the exact canonical, non-`/tmp`, non-symlink artifact root and its `photo-limit-<token>` child. SQL and storage collisions are checked before verifier-owned mutation. Cleanup is ownership-gated and table/path targets are closed over validated literals; no wildcard or sibling deletion is used. Controlled child crash, regression, and timeout paths are bounded and the timeout path records then reaps the isolated process group.

The audit is not derived from the success transcript alone: it contains child readiness/connection evidence, raw public-seam results normalized only after validation, a fresh public projection, owned-table counts, projected contender identity, and hashes of actual owned blobs. The meta-test requires this audit in addition to exact stdout and independently verifies cleanup and ambient decoys.

The runner wiring adds the approved focused test once, before the predecessor photo rejection/upload characterizations. No broad refactor, production logic change, or unrelated behavior is introduced by the slice.

## Spec

One blocking conformance finding.

### 1. The verifier asserts pilot presentation copy that the specification explicitly excludes

At `rapid-pilot/verify-checklist-photo-limit-concurrency.php:350`, the verifier requires the entire loser result to equal:

```php
['status' => 'rejected', 'message' => 'В разделе уже 10 фотографий.']
```

The approved specification says the race “intentionally does not assert the exact rejection message”, excludes “exact Russian messages”, and requires only exactly one terminal `status=rejected`. Consequently, a presentation-copy-only change would incorrectly fail this characterization as `REGRESSION_FAILURE`, promoting excluded pilot copy into a stable behavioral contract.

Required correction: validate the loser's rejection status without comparing the message text (and without otherwise emitting or pinning that message). The existing aggregate, winner/loser identity, zero-mutation, projection, SQL and blob assertions must remain unchanged. This is a Gate 4 verifier correction; the approved specification and test expectation do not need to change.

All other reviewed acceptance behavior conforms: winner neutrality is preserved; exactly one accepted revision-10 operation/photo/blob is proven; the loser leaves no operation, photo, revision or blob mutation; the tenth projected photo belongs to the actual winner; same accepted content with a new operation id is observed as duplicate without mutation; stdout is stable and contains no volatile identity; collision, cleanup, decoy, child failure and timeout classifications are exercised.

## Independent verification evidence

Commands run from `/home/antropophag/code/fmonitor-2`:

```text
make test-env-up
php tests/Verification/characterize_inspection_photo_limit_concurrency_001_test.php
php tests/Verification/characterize_inspection_photo_upload_001_test.php
php tests/Verification/characterize_inspection_photo_rejections_001_test.php
make architecture-check
tools/verification/run.sh lint
git diff --check -- rapid-pilot/verify-checklist-photo-limit-concurrency.php tools/verification/run.sh specs/CHARACTERIZE-INSPECTION-PHOTO-LIMIT-CONCURRENCY-001.md tests/Verification/characterize_inspection_photo_limit_concurrency_001_test.php
```

Results:

- focused concurrency characterization passed, including two nominal runs, exact audit/transcript, both collision probes, controlled crash/regression/timeout paths, process reaping, cleanup and decoy preservation;
- predecessor upload and rejection characterizations passed;
- architecture check passed all 6 rules;
- repository PHP lint passed;
- diff check exited `0` with no output;
- no owned photo-limit artifact directory remained after the run.

Green tests do not waive the blocking finding because the reviewed test correctly avoids pinning the excluded message while the verifier contains the extra assertion internally.

## Required changes

1. Remove the exact Russian loser-message comparison and assert only the approved rejection status while retaining every mutation/aggregate proof.
2. Re-run the focused and predecessor characterizations, lint, architecture check, and diff check.
3. Obtain a fresh independent Gate 5 review for the corrected verifier hash.

Gate 5 is not approved for the manifest above. No implementation, test, specification, runner, or status artifact was modified by this reviewer.
