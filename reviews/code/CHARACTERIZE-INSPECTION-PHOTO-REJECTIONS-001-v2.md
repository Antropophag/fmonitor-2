# Code review: CHARACTERIZE-INSPECTION-PHOTO-REJECTIONS-001 (v2)

- Gate: 5 — independent code review after the prior classification finding
- Reviewer: separately tasked agent `/root/photo_rejections_code_review_v2`
- Independence: this reviewer did not author the specification, test, verifier, or prior reviews
- Reviewed commit: working tree at HEAD `932662938837b28309fef2bf0fe3cadb2ce86e41`, limited to the exact manifest below
- Specification: `specs/CHARACTERIZE-INSPECTION-PHOTO-REJECTIONS-001.md`, version `0.1`
- Current approved test review: `reviews/tests/CHARACTERIZE-INSPECTION-PHOTO-REJECTIONS-001-v3.md`, verdict `APPROVED`
- Date: `2026-09-01`
- Verdict: `APPROVED`

## Exact reviewed manifest and hashes

```text
ef590e32f055f9dfddee2c12664fba253f9b45741ea853ebea127994807280c5  specs/CHARACTERIZE-INSPECTION-PHOTO-REJECTIONS-001.md
01a9f42d78d440c6546626ad997fc21c6208eee3a7d19f2331fcdfd3c31ff9cd  tests/Verification/characterize_inspection_photo_rejections_001_test.php
37632b3ae487c8d5b3c3d935d83ed75f6ca98d57a92d142b95a72f81ab58bf1d  reviews/tests/CHARACTERIZE-INSPECTION-PHOTO-REJECTIONS-001.md
ba00b596798063d877f9e5fce5c4f37478b0eac5944da09608d2144ab290b610  reviews/tests/CHARACTERIZE-INSPECTION-PHOTO-REJECTIONS-001-v2.md
04e15a495df3141d67f6b9d7bc6dbca7546dc001e4f5fee57ebfea1850204a8a  reviews/tests/CHARACTERIZE-INSPECTION-PHOTO-REJECTIONS-001-v3.md
e3fbb8e4797d366c4957e0c62637b9086f9baf1b7d4f415fdcdd52c6438644ac  reviews/code/CHARACTERIZE-INSPECTION-PHOTO-REJECTIONS-001.md
5dd9e6ce0232241ff6ea1753219124c2126192727847680a537e00c6573178c8  rapid-pilot/verify-checklist-photo-rejections.php
532743b05b2bc1fa376e19b1fe37a5cc7d2c1f3f3d86e7b2a12c668cd4efa718  tools/verification/run.sh
```

Only the photo-rejection verifier, its approved meta-test, and its runner entry at `tools/verification/run.sh:80` are attributed to this narrow characterization slice. No `ChecklistSync` production behavior change is attributed to it.

## Standards

`APPROVED` on this axis.

The verifier exercises only the public `ChecklistSync::accept(...)` and `projection(...)` seams. It does not invoke production schema creation or private photo storage methods. The exact token, canonical non-`/tmp` artifact root, occupied-namespace rejection and ownership-gated teardown keep SQL and filesystem mutation bounded to the verifier's explicit namespace.

The v3 test safely creates a random, strictly validated `cipr_ddl_<12 hex>` account, grants only `SELECT` on the configured disposable database, and drops only the account it successfully created. Its random password is constrained to lowercase hexadecimal and is not printed by the verifier or test. The expected MariaDB setup error may identify the task-owned synthetic username, which is useful bounded diagnostic context and is not a secret. No credential, password, volatile path, table name, translated pilot message or production datum leaks into the successful normalized transcript.

The implementation remains narrow and maintainable: verification DDL and seed statements share one local setup boundary, while public-seam execution and behavioral assertions remain outside it. Cleanup is attempted table by table after partial DDL and removes the exact private storage child. The aggregation runner contains the focused test once and retains the predecessor upload characterization.

## Spec

`APPROVED` on this axis.

The prior Gate 5 blocker is resolved. Verification schema and seed creation are wrapped in a catch that translates any thrown setup error to one `SETUP_FAILURE` diagnostic with code/exit `2`. The approved v3 sensitivity probe reaches the first `CREATE TABLE` with an otherwise valid database, artifact root and unoccupied namespace, and proves this classification together with empty stdout, removal after partial setup, and preservation of ambient and foreign-token decoys.

Behavioral assertions are deliberately outside that setup catch. Assertion drift, an unexpected accepted mutation, transcript mismatch, or cleanup/isolation test failure therefore continues through the generic `REGRESSION_FAILURE` path with exit `1`; the implementation does not blur product drift into setup failure.

The four required cases remain exact and ordered: MIME/content mismatch, declared-size mismatch, declared-hash mismatch, and control-character filename. They use distinct cases and operation identities, the fixed actor/times/revision/template context, and the approved 68-byte PNG fixture. Each observes `rejected`, revision `0`, no projected active photo, no accepted operation/photo row, no revision increment and no blob. The terminal transcript and independently fixed SHA-256 are exact. Pilot-only copy, 10-photo/revoke/replay policy and unresolved authorization/assignment rules are neither asserted nor promoted.

Two nominal runs remain byte-identical after normalization. SQL/storage collision probes, missing-token, unsafe `/tmp`, unavailable database and DDL-denial probes establish determinism, bounded cleanup, zero residue and preservation of decoys. The predecessor upload characterization and the complete characterization aggregation are green.

## Independent verification evidence

Commands run from `/home/antropophag/code/fmonitor-2`:

```text
php tests/Verification/characterize_inspection_photo_rejections_001_test.php
php tests/Verification/characterize_inspection_photo_upload_001_test.php
php -l rapid-pilot/verify-checklist-photo-rejections.php
php -l tests/Verification/characterize_inspection_photo_rejections_001_test.php
make characterization-test
make lint
make architecture-check
git diff --check
docker compose -f compose.test.yaml exec -T test-db mariadb ...
find .local/test-artifacts -maxdepth 3 ...
```

Results:

- the focused photo-rejection test passed, including exact transcript/isolation checks and the DDL-denial setup-classification probe;
- the predecessor photo-upload test passed;
- both PHP files passed syntax checks;
- `make characterization-test` passed its complete aggregation;
- `make lint` exited `0` with no output;
- `make architecture-check` passed all 6 rules;
- `git diff --check` exited `0` with no output;
- read-only MariaDB inspection returned no `photo_reject_*` or `characterize_photo_rejections_decoy_*` tables and no `cipr_ddl_*` users;
- filesystem inspection returned no `characterize-photo-rejections-*` roots or `photo-reject-*` children.

## Required changes

None. Gate 5 is approved for the exact manifest and hashes above. No specification, test, status file, or implementation was changed by this review.
