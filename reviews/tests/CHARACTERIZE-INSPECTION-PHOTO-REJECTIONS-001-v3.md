# Test review: CHARACTERIZE-INSPECTION-PHOTO-REJECTIONS-001 (v3)

- Gate: 3 — independent test review after Gate 5 sensitivity finding
- Reviewer: separately tasked agent `/root/photo_rejections_test_review_v3`
- Independence: this reviewer did not author the specification, test, verifier, or prior reviews
- Reviewed commit: `932662938837b28309fef2bf0fe3cadb2ce86e41` plus the uncommitted artifacts identified by the hashes below
- Specification: `specs/CHARACTERIZE-INSPECTION-PHOTO-REJECTIONS-001.md`, version `0.1`
- Test: `tests/Verification/characterize_inspection_photo_rejections_001_test.php`
- Public seam: `php rapid-pilot/verify-checklist-photo-rejections.php`
- Date: `2026-09-01`
- Verdict: `APPROVED`

## Findings

### Gate 5 sensitivity finding is covered

The new probe creates a random, test-owned MariaDB account whose name is derived
from the dedicated 12-hex `ddl-denied` token and is accepted only after the exact
`cipr_ddl_[a-f0-9]{12}` validation. The password is 32 lowercase hexadecimal
characters. Consequently the interpolated account identifier and password cannot
terminate their quoted SQL contexts. The configured database identifier is
backtick-escaped before use in `GRANT`.

The account receives only `SELECT` on the configured disposable verification
database; it receives no `CREATE` or other DDL grant. Its successful connection,
owned-prefix occupancy query, and creation of the exact private storage child
establish that the database, credentials, database selection, artifact root and
pre-mutation namespace are otherwise valid. The failure is therefore induced at
the verifier's first verification-schema `CREATE TABLE`, rather than at connection
or unrelated fixture setup.

The test requires that this condition produce exit `2`, empty stdout, and exactly
one `SETUP_FAILURE` stderr line. It also requires no owned SQL tables, removal of
the newly created owned storage directory (not merely emptiness), and byte-for-byte
preservation of ambient and foreign-token SQL/storage decoys. The account is
dropped in `finally` only when this invocation successfully created that exact
validated account, so an account-name collision cannot cause deletion of an
ambient account. Cleanup remains bounded to the task-owned identity and exact
generated namespaces.

This directly catches the prior Gate 5 finding: the current verifier routes the
denied `CREATE` through its generic regression handler and returns exit `1` with
`REGRESSION_FAILURE`.

### Earlier Gate 3 coverage remains intact

The v3 test retains the literal four-line transcript, independently fixed
transcript hash, two distinct-token nominal runs, exact stdout/stderr and status
checks, SQL and storage collision probes, unsafe `/tmp`, missing-token and
unavailable-database classification probes, and ambient/foreign decoy
fingerprints. In particular, successful cleanup still asserts both empty state
and `file_exists(...) === false`, preserving the v1 finding's missing-versus-empty
distinction.

The new probe does not derive an expected product result from the verifier and
does not inspect verifier internals. It exercises the same public process seam
with a controlled environmental fault and independently asserts the specification's
setup-failure classification and zero-residue contract.

## Reproduced focused RED evidence

Command run from `/home/antropophag/code/fmonitor-2`:

```text
php tests/Verification/characterize_inspection_photo_rejections_001_test.php
```

Result: exit `1`, stdout empty, stderr (the random task-owned suffix is expected
to vary):

```text
Verification fixture DDL permission denial must be classified as setup failure; evidence={"status":1,"stdout":"","stderr":"REGRESSION_FAILURE: CREATE command denied to user 'cipr_ddl_bf1bbd97ffb4'@'172.29.0.1' for table `fmonitor2_test`.`photo_reject_bf1bbd97ffb4_fm2_installation_cases`\n"}
Expected: 2
Actual: 1
```

This is a qualifying RED for the newly exposed classification mismatch. Reaching
the first `CREATE TABLE` proves connection, database selection, namespace check
and artifact setup succeeded; the evidence contains no earlier setup failure.
All preceding nominal transcript/isolation checks and safety probes completed.

A read-only post-run inspection found no `photo_reject_*` or
`characterize_photo_rejections_decoy_*` tables and no `cipr_ddl_*` MariaDB
accounts. No generated `characterize-photo-rejections-*` artifact root remained.
The test file also passed `php -l`.

## Reviewed hashes

```text
ef590e32f055f9dfddee2c12664fba253f9b45741ea853ebea127994807280c5  specs/CHARACTERIZE-INSPECTION-PHOTO-REJECTIONS-001.md
01a9f42d78d440c6546626ad997fc21c6208eee3a7d19f2331fcdfd3c31ff9cd  tests/Verification/characterize_inspection_photo_rejections_001_test.php
37632b3ae487c8d5b3c3d935d83ed75f6ca98d57a92d142b95a72f81ab58bf1d  reviews/tests/CHARACTERIZE-INSPECTION-PHOTO-REJECTIONS-001.md
ba00b596798063d877f9e5fce5c4f37478b0eac5944da09608d2144ab290b610  reviews/tests/CHARACTERIZE-INSPECTION-PHOTO-REJECTIONS-001-v2.md
e3fbb8e4797d366c4957e0c62637b9086f9baf1b7d4f415fdcdd52c6438644ac  reviews/code/CHARACTERIZE-INSPECTION-PHOTO-REJECTIONS-001.md
cd914680eb70c31f1d6f722adc60097c3fff1f22514d7e11c798305059368373  rapid-pilot/verify-checklist-photo-rejections.php
```

## Required changes

None. Gate 3 is approved for test hash
`01a9f42d78d440c6546626ad997fc21c6208eee3a7d19f2331fcdfd3c31ff9cd`.
