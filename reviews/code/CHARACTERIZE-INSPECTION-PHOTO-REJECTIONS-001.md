# Code review: CHARACTERIZE-INSPECTION-PHOTO-REJECTIONS-001

- Reviewer: `Codex agent /root/photo_rejections_code_review_v1` (separately tasked independent Gate 5 reviewer; did not author the specification, test, test reviews, or implementation)
- Implementation author: `Codex agent /root/photo_rejections_green_v1`
- Reviewed commit: working tree at HEAD `932662938837b28309fef2bf0fe3cadb2ce86e41`, limited to the exact manifest below
- Specification: `specs/CHARACTERIZE-INSPECTION-PHOTO-REJECTIONS-001.md`, version `0.1`
- Approved test review: `reviews/tests/CHARACTERIZE-INSPECTION-PHOTO-REJECTIONS-001-v2.md`, verdict `APPROVED`
- Verdict: `CHANGES_REQUIRED`

## Exact reviewed manifest and hashes

```text
ef590e32f055f9dfddee2c12664fba253f9b45741ea853ebea127994807280c5  specs/CHARACTERIZE-INSPECTION-PHOTO-REJECTIONS-001.md
c36bf874f0fd7353fff16dbe02a7ec8af7bd9187f582640c96b027ef1a73d5a2  tests/Verification/characterize_inspection_photo_rejections_001_test.php
ba00b596798063d877f9e5fce5c4f37478b0eac5944da09608d2144ab290b610  reviews/tests/CHARACTERIZE-INSPECTION-PHOTO-REJECTIONS-001-v2.md
37632b3ae487c8d5b3c3d935d83ed75f6ca98d57a92d142b95a72f81ab58bf1d  reviews/tests/CHARACTERIZE-INSPECTION-PHOTO-REJECTIONS-001.md
cd914680eb70c31f1d6f722adc60097c3fff1f22514d7e11c798305059368373  rapid-pilot/verify-checklist-photo-rejections.php
532743b05b2bc1fa376e19b1fe37a5cc7d2c1f3f3d86e7b2a12c668cd4efa718  tools/verification/run.sh
```

Only the new `tests/Verification/characterize_inspection_photo_rejections_001_test.php` entry at `tools/verification/run.sh:80` was reviewed in that runner. No `ChecklistSync` or other production change is attributed to this characterization slice.

## Standards

`APPROVED` on this axis.

The verifier uses only the public `ChecklistSync::accept(...)` and `projection(...)` seams. It neither invokes `ensureSchema` nor calls `storePhoto` or another private method. Its DDL is verification-only. The exact 12-hex token and canonical non-`/tmp` artifact root are validated before mutation; namespace occupation is rejected before ownership is claimed; teardown is ownership-gated and limited to the fixed table suffix list and exact token storage child.

The four scenarios use distinct cases and operation UUIDs. After each rejection, the verifier checks the public projection and its own tables/storage for zero accepted operations, photo rows, revision increments, and blobs. The approved meta-test checks exact namespace removal and fingerprints ambient and foreign-token SQL/storage decoys. The runner adds exactly the focused meta-test to the existing characterization aggregation. No documented-standard breach or blocking maintainability smell was found.

## Spec

`CHANGES_REQUIRED` on this axis.

### Blocking: schema and fixture setup failures are classified as regressions

The fixture/isolation contract requires every database or fixture setup failure to emit `SETUP_FAILURE` and exit `2`. The implementation translates only connection/charset errors at `rapid-pilot/verify-checklist-photo-rejections.php:72-84` into that classification. Verification schema creation and fixture insertion at lines `98-120` execute outside that setup-classification boundary. A missing `CREATE` privilege, DDL incompatibility, or failed fixture insert therefore reaches the generic `Throwable` handler at lines `162-164`, emits `REGRESSION_FAILURE`, and exits `1`.

That behavior contradicts the exact classification contract and can falsely present a broken disposable environment as product drift. The approved test probes unavailable DB, invalid inputs, and occupied namespaces, but does not induce a DDL/fixture setup failure, so it would not catch this plausible regression. Per `docs/development-process.md`, correcting this test-sensitivity gap restarts Gate 2 and requires fresh independent Gate 3 approval before Gate 4 and Gate 5 repeat.

All other checked Spec requirements conform: the approved test hash is unchanged; the verifier implements exactly the MIME/content, size, hash, and control-character-name scenarios in the required order; stdout and transcript hash are exact; exact pilot rejection copy is not asserted; rejected calls leave no accepted fact or revision; and upload predecessor/harness wiring remain green.

## Independent verification evidence

Commands run from `/home/antropophag/code/fmonitor-2`:

```text
php tests/Verification/characterize_inspection_photo_rejections_001_test.php
php tests/Verification/characterize_inspection_photo_upload_001_test.php
make characterization-test
php -l rapid-pilot/verify-checklist-photo-rejections.php
php -l tests/Verification/characterize_inspection_photo_rejections_001_test.php
make architecture-check
git diff --check
```

Results:

- focused photo-rejection characterization passed;
- predecessor photo-upload characterization passed;
- the complete `characterization-test` aggregation passed, including checklist current crew and both photo characterizations;
- both PHP files passed syntax checks;
- architecture check passed all 6 rules;
- `git diff --check` exited `0` with no output;
- a read-only post-run database inspection found no `photo_reject_*` or `characterize_photo_rejections_decoy_*` tables;
- a post-run filesystem inspection found no `characterize-photo-rejections-*` or `photo-reject-*` artifact namespace.

## Required changes

1. Add an approved sensitive test that induces failure during verification schema or fixture setup and requires one `SETUP_FAILURE` line, empty normalized stdout, exit `2`, and bounded cleanup/preservation.
2. Reproduce qualifying RED for the corrected test and obtain fresh independent Gate 3 approval for its new hash.
3. Minimally classify verification DDL/fixture setup failures as setup failures while retaining regression classification for assertion drift, unexpected mutation, and cleanup/isolation failure; then rerun Gate 5.

Gate 5 is not approved. No status file was changed by this review.
