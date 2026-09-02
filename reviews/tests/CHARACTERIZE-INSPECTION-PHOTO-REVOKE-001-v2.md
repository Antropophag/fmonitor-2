# Test review: CHARACTERIZE-INSPECTION-PHOTO-REVOKE-001 v2

- Gate: 3 — independent test re-review
- Reviewer: separately tasked agent `/root/photo_revoke_test_review_v2`
- Independence: this reviewer did not author the specification, evidence, test, or future verifier
- Reviewed commit: `932662938837b28309fef2bf0fe3cadb2ce86e41` plus the uncommitted artifacts pinned below
- Specification: `specs/CHARACTERIZE-INSPECTION-PHOTO-REVOKE-001.md`, version `0.1`
- Test: `tests/Verification/characterize_inspection_photo_revoke_001_test.php`
- Public seam: `php rapid-pilot/verify-checklist-photo-revoke.php`
- Date: `2026-09-01`
- Verdict: `APPROVED`

## Findings

No blocking findings. The corrected test closes every finding from
`reviews/tests/CHARACTERIZE-INSPECTION-PHOTO-REVOKE-001.md` without weakening
the original fixture, transcript, failure-classification, isolation, or
zero-mutation expectations.

## Prior findings closed

1. The audit now requires five projection calls and records the revision-`1`
   upload projection before revoke. It requires exactly one active photo, fixes
   its section to the test-owned section `3`, and ties its positive integer id
   to the later revoke evidence. The exact ordered scenario object then records
   the revision-`2` post-revoke projection and the projections after replay,
   fresh revoke, and identical re-upload.
2. The surviving SQL photo row is now compared field-for-field against immutable
   test-owned upload metadata: case and section, upload operation id, SHA-256,
   MIME, byte size, original and storage names, actor, device time, receipt time,
   and the exact revoke receipt time. The two operation-history rows are exact
   and ordered by accepted revision. Their literal upload payload and revoke
   payload are asserted, and `photoId` in the revoke payload is derived from the
   positive integer id observed through the revision-`1` public projection.
3. Every generated owned token is tracked independently of verifier success.
   The outer `finally` rediscoveries each exact validated SQL prefix before
   dropping discovered owned tables, so timeout and assertions occurring before
   ordinary post-run checks cannot leak SQL. Storage remains bounded beneath the
   random test-owned artifact root. Discovery and deletion regexes cannot match
   the separately generated foreign token, and the test proves the foreign SQL
   table and foreign storage bytes remain unchanged throughout every verifier
   probe before test-fixture teardown.

## Traceability and sensitivity

- The approved specification hash is pinned before any verifier execution.
- Literal fixture values independently validate PNG decoding, MIME, 68-byte
  size, SHA-256, canonical UUIDv4 identities, and four distinct logical command
  operation ids.
- The mandatory JSON audit prevents an implementation that merely echoes the
  four milestones. Its exact structure proves five public `accept` calls, five
  public projection observations, the full accepted upload/revoke history, and
  before/after fingerprints covering revision, ordered operations and payloads,
  photo facts, and blobs for all three zero-mutation scenarios.
- Result sensitivity distinguishes accepted revisions `1` and `2`, sequential
  duplicate revision `2`, fresh already-revoked rejection, and the observed
  SQLSTATE `23000` / vendor code `1062` exception without depending on a generated
  constraint name or translated message.
- Transcript order and bytes remain literal. Independent recomputation produced
  `60f1a4c65be2a4cedd05f170b243d34283560f480f37a2965fec7aeadd62b784`.
- Two clean random-token runs, occupied SQL and storage probes, controlled
  post-mutation setup/regression failures, missing and invalid tokens, unavailable
  MariaDB, forbidden `/tmp`, ambient decoys, and a foreign-token namespace cover
  determinism, collision refusal, cleanup, classification, and ownership safety.
- Expected values come from the approved specification and fixed evidence rather
  than a planned verifier implementation. The test exercises the confirmed CLI
  oracle seam and requires the verifier to report calls to public
  `ChecklistSync::accept(...)` and `ChecklistSync::projection(...)`.

## Reproduced healthy RED evidence

Commands run from `/home/antropophag/code/fmonitor-2`:

```text
make test-env-up
php -l tests/Verification/characterize_inspection_photo_revoke_001_test.php
php tests/Verification/characterize_inspection_photo_revoke_001_test.php
```

MariaDB became healthy and lint passed. The focused test exited `1`, with empty
stdout and the intended absent-seam failure on stderr:

```text
RED_ASSERTION: missing public photo-revoke verifier must become a successful first run; evidence={"status":1,"stdout":"","stderr":"Could not open input file: rapid-pilot/verify-checklist-photo-revoke.php\n","timed_out":false}
Expected: 0
Actual: 1
```

This is a qualifying Gate 2 RED: fixture and decoy construction reached the
missing public verifier, rather than failing environment setup. A post-run query
found no `photo_revoke_*` or `characterize_photo_revoke_decoy_*` table, and no
`.local/test-artifacts/characterize-photo-revoke-*` root remained.

## Reviewed hashes

```text
143665e8734fd86649622bc71c6da2331d2a4f3a5e2380a31f6eabae2729f154  specs/CHARACTERIZE-INSPECTION-PHOTO-REVOKE-001.md
4d985d094f8889cbd5dc040f0f4bb71bc1b2a36bdfd3a1b141c07e229bfd5288  tests/Verification/characterize_inspection_photo_revoke_001_test.php
900a92700e145b983c8a7b1ab8145342c26b470abe6532c56d69975eda902c14  docs/operations/inspection-photo-revoke-evidence.md
```

`rapid-pilot/verify-checklist-photo-revoke.php` was absent for the reproduced
Gate 2 RED. This Gate 3 approval does not review or pin a future verifier.

## Gate decision

Gate 3 is `APPROVED` for the exact specification and test hashes above. Gate 4
may implement the minimal verifier against this expectation. Any change to the
approved specification or test requires a new independent Gate 3 review.
