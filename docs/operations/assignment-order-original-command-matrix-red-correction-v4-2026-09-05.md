# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — command matrix RED correction v4

Date: `2026-09-05`

Correction author: `/root/assignment_command_gate3`; this author must not review
the corrected batch. Authority: Gate 3 v3 `47bfdfe` CHANGES_REQUESTED.

Outcome: **INTENDED RED — v3 race-oracle findings corrected**.

The two already isolated race fixtures now compare complete byte-exact
canonical inventories rather than selected fields/counts:

- exact initial domain revision, request result, fingerprint, event, audit,
  process, blob and empty-log JSON is independently enumerated for both fresh
  databases and is the literal READY oracle;
- different-race revision-2 prerequisite likewise enumerates every field of
  both revisions/results/fingerprints/events/audits and every other inventory;
- identical race uses exact v52 clocks `2026-09-02T09:16:00Z`, unused root
  sequence `original-0099` for both workers and revision `revision-0002`;
- exact winner-before-loser and final inventories enumerate every revision
  field (including actor, previous, dates, content, reason), every request
  result field, fingerprint/link, event and audit field, blobs, six process
  digests and logs;
- different final oracle includes exact accepted winner, stale loser terminal
  request/audit and the sole `commit_conflict` release-failure log;
- identical final oracle requires empty logs and same-loser retry preserves all
  eight canonical inventories byte-for-byte.

No production, specification or OpenSpec artifact changed. Task 4.1 remains
complete; a different fresh reviewer is required for Gate 3.

```text
$ php -l tests/Support/assignment_order_original_isolated_races.php
No syntax errors detected

$ tools/verification/run.sh red <all eight focused suites>
eight intended missing-production-seam RED_ASSERTION results; every wrapper exit 0

$ php tests/InstallationProcess/assignment_order_original_upload_remaining_contract_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_SHARED_ORACLES_OK

$ independent information_schema SCHEMATA / PROCESSLIST t_aoou_%
0
0

$ independent verifier temp-root inventory
no matching roots

$ git diff --check
PASS (no output)
```

```text
8a88d3f93da76186296610c8d54567d1b00d3a81ccb311f25b16ebd584318fdd  tests/Support/assignment_order_original_isolated_races.php
```
