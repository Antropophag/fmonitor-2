# Test review: CHARACTERIZE-OBJECT-DETAIL-IMPORT-001 v0.2

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/object_detail_import_gate3_v02`
- Reviewed worktree: dirty authoritative worktree at HEAD
  `0304541e6dc9a9a441d1734c3af22df8247fe720`
- Executable specification: `CHARACTERIZE-OBJECT-DETAIL-IMPORT-001 v0.2`
- Owner Gate 1 evidence:
  `docs/operations/object-detail-import-v02-owner-approval-2026-09-05.md`
- Gate 2 evidence:
  `docs/operations/object-detail-import-red-evidence-2026-09-05.md`
- Verdict: `CHANGES_REQUESTED`

The reviewer did not author or edit the specification, test, importer, schema
migration, or RED evidence. This review used only the private fictional Docker
fixture. It did not access a VPN, legacy/production source, production
credentials, shared database, or protected end-to-end environment.

## Reviewed identities

```text
a2e9f65a20bd6e33c740c774094508b32a33faa6e0bdf4ace498e31f024e24c9  specs/CHARACTERIZE-OBJECT-DETAIL-IMPORT-001.md
22b2fc642c247d5f1d748534524aceed82eac7c727f153cb2527cbf1e82d82a2  tests/Verification/characterize_object_detail_import_001_test.php
668b51891c956202cc4d6fe0d37287fdf615489e56bdfa66eea087d0d899b449  exact six-line expected normalized transcript, LF after every line
8613298f71bc35f2b3c915fae3ad841548c278d4b0a10a3748c4dbb7a64d07f6  docs/operations/object-detail-import-red-evidence-2026-09-05.md
442fd28a8fe5ea4628068084b0ddfaf3b8d166b58cd26339763b7dbec38791d1  docs/operations/object-detail-import-v02-owner-approval-2026-09-05.md
069f8d75334380b1b0348ab0ed60b508c6152e0cf4f8daa118827c5f79696950  rapid-pilot/import-production-object-details.php
2bc47395cdaf61974c493907a84d8f8f25dc034e10c140a8fd851ca4845ace8a  app/InstallationProcess/ObjectDetailSnapshotSchemaMigration.php
```

The owner record explicitly approves the exact v0.2 specification hash above.
The stale draft-status prose inside the immutable specification does not
override that later durable approval.

## Blocking findings

### 1. HIGH — the meta-test deletes the common artifact root forbidden by the contract

Specification lines 167–168 and 180–184 require cleanup to delete only the
enumerated per-token files/child directory and explicitly say the common parent
is preserved and the common root is not deleted. The test instead creates
`.test-artifacts/object-detail-import` and unconditionally calls
`rmdir($artifactRoot)` in its outer `finally` (lines 285–310). The RED evidence
also describes removal of that repository-owned root. This is the exact
contract discrepancy raised for review, and the implementation follows the
evidence claim rather than the normative requirement.

Required correction: arrange an exact repository-owned common root as setup,
delete the meta-test-owned decoy only after its identity/bytes proof, and prove
the common root survives both successful and failing completion. Cleanup must
remain limited to each token child and its manifest plus the exact decoy.

### 2. HIGH — the least-privilege assertion permits broad grants

Lines 198–202 obtain `SHOW GRANTS`, but assert only that each textual row lacks
the words `CREATE`, `ALTER`, `DROP`, `UPDATE`, and `DELETE`. A principal granted
`ALL PRIVILEGES`, a role, or a database/global grant can pass that expression;
for example, `GRANT ALL PRIVILEGES ON *.* ...` contains none of those five
literal privilege names. The specification requires only the enumerated exact
table grants, forbids roles/global/schema grants except `USAGE`, and makes the
normalized `ddl_privileges=0` milestone depend on that proof.

Required correction: compare normalized `SHOW GRANTS` facts to an independently
literal allowlist for each principal, including scope and privilege set, and
explicitly reject `ALL PRIVILEGES`, roles, global grants, and database-wide
grants. Add a focused test-side sensitivity probe or equivalent retained
assertion demonstrating that a broad grant cannot satisfy the verifier.

### 3. HIGH — exact state is not independently established around every CLI call

The contract requires schema fingerprints and full-row snapshots of every
target fixture before each CLI call. The eight schema-refusal calls use
`odciFamilyState()`, which observes only six column attributes and rows for the
two family members. It omits indexes, constraints, engine/table options and all
cases/sentinel/SQL-decoy structures and rows. Consequently a schema-refusal
implementation can mutate an omitted target fact and still pass. Clean result
checking is also incomplete: it does not assert the stored detail
`object_id`/`schema_version`, and for quarantine it omits
`object_id`, `schema_version`, `reason_code`, and exact payload bytes. Counts and
two hashes do not satisfy the worked fixture's complete-row requirement.

Required correction: use independently literal, complete schema and row state
for every target fixture around every importer invocation, including all eight
schema-refusal calls. Assert every clean detail/quarantine column and exact raw
payload byte required by the worked fixture. Keep expectations independent of
importer output and migration-owned constants.

### 4. HIGH — timeout/output-overflow paths do not implement bounded reap and cleanup proof

`odciProcess()` throws immediately after `proc_terminate()` on output overflow,
and on timeout throws immediately after a possible `SIGKILL` (lines 35–42).
Neither path drains/closes pipes, calls `proc_close()`, proves child exit within
the required three-second reap deadline, or preserves a cleanup failure
classification. This contradicts the mandatory deadline and cleanup contract
and can leave the importer or Docker CLI alive while outer cleanup begins.

Required correction: retain the process handle through a bounded TERM/KILL/reap
sequence on every exit path, close pipes, verify termination, and report cleanup
failure with the specified classification precedence. Add deterministic
test-side probes for timeout and output overflow so these future assertions are
sensitive rather than dead helper branches.

## RED quality and retained behavior

The focused RED itself is valid. Independent execution produced exactly:

```text
$ php -l tests/Verification/characterize_object_detail_import_001_test.php
No syntax errors detected in tests/Verification/characterize_object_detail_import_001_test.php

$ php tests/Verification/characterize_object_detail_import_001_test.php
REGRESSION_FAILURE: real importer attempted runtime CREATE on the exact precreated v12 family under the verified DDL-denied principal
[exit 1; stdout 0 bytes]
```

This is not setup failure: the private container was created from the pinned
image, the public v12 migration precreated the exact family, the distinct child
principals connected successfully, and MariaDB denied the real importer's
runtime `CREATE`. Reviewer cleanup observation found no container with label
`fmonitor2.object-detail-token` and no `object-detail-*` child or
`ambient-decoy.txt` under `.test-artifacts`.

The test otherwise has the correct public CLI seam and exact apply/dry-run argv
construction; a private manifest with sentinel guard; two explicit random
per-run tokens; a manifest-only child inventory; an ambient artifact decoy;
separate source/target credentials; fixed six-field fixture and fixed hashes;
clean, replay, conflict, metadata and dictionary scenarios; and the complete
four schema defects in both argv modes with a positive listener control and a
post-child zero-source-connection observation. Independent database reads after
the clean call and pre/post snapshots for replay, dry-run, conflict and source
rejections prevent an output-only fake from satisfying the main serial path.
Those strengths do not close the blocking contract and sensitivity gaps above.

## Verdict

`CHANGES_REQUESTED`

Gate 4 must remain paused. Correct the artifact-root lifecycle, exact grant
proof, complete per-call database observations, and bounded process reap paths;
capture a new qualifying RED at the corrected test hash; then request a fresh
independent Gate 3 review.
