# Test review: CHARACTERIZE-INSPECTION-PHOTO-REVOKE-001

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/photo_revoke_test_review_v1`
- Independence: this reviewer did not author the specification, evidence, test, or future verifier
- Reviewed commit: `932662938837b28309fef2bf0fe3cadb2ce86e41` plus the uncommitted artifacts identified by the hashes below
- Specification: `specs/CHARACTERIZE-INSPECTION-PHOTO-REVOKE-001.md`, version `0.1`
- Test: `tests/Verification/characterize_inspection_photo_revoke_001_test.php`
- Public seam: `php rapid-pilot/verify-checklist-photo-revoke.php`
- Date: `2026-09-01`
- Verdict: `CHANGES_REQUESTED`

## Findings

### High — the upload acceptance statement is not observed through projection

The first specified scenario requires the accepted upload to expose exactly one
active section-3 photo before the revoke is submitted. The test instead requires
only four projection calls in total and its `upload_then_revoke` audit records
only the post-revoke projection (`revision=2`, no active photos). A verifier can
therefore omit or return an incorrect revision-1 upload projection and still
satisfy the reviewed expectation.

The expectation must record the public projection after upload, including
revision `1`, the single active photo and section `3`, as well as the existing
post-revoke projection. Reconcile the projection-call count with that required
observation; the present exact count of four is incompatible with observing both
states while also observing each of the other three scenarios.

### High — required SQL/history evidence is reduced to counts

The specification requires the surviving photo row's original upload metadata to
remain unchanged and the ordered revoke operation payload to contain the projected
integer photo id. The audit expectation proves only row/revocation/operation
counts, `revoked_at`, operation kinds, revisions and operation UUIDs. It neither
defines the original photo metadata fields nor the two operation payloads. A
verifier that overwrites upload metadata or records an empty/wrong revoke payload
could pass.

Add exact test-owned expectations for the relevant surviving photo fields and
ordered operation payloads, including the relationship between the projected
photo id and the revoke payload. Volatile row ids need not enter stdout, but the
audit can assert their within-run relationship.

### Medium — early assertion and timeout paths can leak verifier-owned SQL

Owned verifier tables are added to the test's `$discovered` cleanup set only when
`ciprvOwnedTables(...)` runs after a verifier invocation. For nominal runs, an
unexpected status, stderr, timeout, or missing audit throws before that discovery.
For controlled failure runs, an unexpected status/classification similarly
throws before line 380 rediscovers the namespace. The outer `finally` therefore
cannot remove tables left by the failed/killed verifier. Killing a timed-out
process makes this especially important: timeout is detected, but residue is not
bounded or classified by a post-run namespace check.

Track every generated token independently of verifier success and rediscover its
exact validated SQL prefix in `finally` (or otherwise guarantee bounded cleanup).
Also assert timeout/residue classification explicitly enough that a hung verifier
cannot contaminate later autonomous runs. Storage is ultimately bounded by
recursive removal of the random test-owned artifact root; SQL currently is not.

## Coverage that is acceptable

- The specification hash is pinned and matches the reviewed file.
- The fixture owns literal UUIDv4 values and four distinct operation UUIDs; the
  PNG is independently decoded, MIME-detected, sized and SHA-256 checked.
- The audit file is mandatory, test-owned and consumed before stdout acceptance,
  which resists a stdout-only implementation.
- The expected audit fixes four scenario names, five `accept` results, the three
  zero-mutation fingerprint pairs and SQLSTATE `23000` / vendor code `1062`
  without pinning translated exception copy or an index name.
- Transcript order and bytes are literal. Independent recomputation produced
  `60f1a4c65be2a4cedd05f170b243d34283560f480f37a2965fec7aeadd62b784`.
- Two random tokens, occupied SQL/storage probes, uppercase-invalid token,
  forbidden `/tmp`, ambient SQL/storage and foreign-token decoys provide useful
  isolation and collision sensitivity.
- Controlled setup/regression-after-mutation probes require classified single-line
  stderr, no success stdout and cleanup when the verifier honors the protocol.

The missing-token and unavailable-database classifications stated by the general
failure contract are not independently probed. They are lower priority than the
findings above, but adding the analogous safe probes would improve classification
coverage and align this test with the approved photo characterization harnesses.

## Reproduced focused RED evidence

Commands run from `/home/antropophag/code/fmonitor-2`:

```text
make test-env-up
php -l tests/Verification/characterize_inspection_photo_revoke_001_test.php
php tests/Verification/characterize_inspection_photo_revoke_001_test.php
```

The lint command passed. The focused test exited `1` with empty stdout and this
intended failure on stderr:

```text
RED_ASSERTION: missing public photo-revoke verifier must become a successful first run; evidence={"status":1,"stdout":"","stderr":"Could not open input file: rapid-pilot/verify-checklist-photo-revoke.php\n","timed_out":false}
Expected: 0
Actual: 1
```

This is a qualifying healthy RED: MariaDB was healthy, fixture/decoy construction
completed, and the failure is the absent public verifier rather than setup. A
post-run database inspection found no `photo_revoke_*` or
`characterize_photo_revoke_decoy_*` table, and no generated
`.local/test-artifacts/characterize-photo-revoke-*` root remained.

## Reviewed hashes

```text
143665e8734fd86649622bc71c6da2331d2a4f3a5e2380a31f6eabae2729f154  specs/CHARACTERIZE-INSPECTION-PHOTO-REVOKE-001.md
f8c606e936f8daa761b19e44e50a91ebd660d8a274276b1712118c71e5ccc574  tests/Verification/characterize_inspection_photo_revoke_001_test.php
900a92700e145b983c8a7b1ab8145342c26b470abe6532c56d69975eda902c14  docs/operations/inspection-photo-revoke-evidence.md
```

`rapid-pilot/verify-checklist-photo-revoke.php` was absent, as required for the
reviewed Gate 2 RED; Gate 3 does not approve or pin a future implementation.

## Required changes

1. Observe and assert the revision-1 single-active-photo projection before revoke,
   and reconcile the exact projection-call count.
2. Expand the audit expectation to prove unchanged original photo metadata and
   exact ordered operation payloads, including the projected-photo-id relationship.
3. Make test cleanup rediscover and remove exact owned SQL namespaces even when a
   verifier times out or an earlier status/classification/audit assertion fails.

After those changes, obtain a fresh independent Gate 3 review with new exact
specification/test hashes. Gate 4 must not begin on the reviewed test hash above.
