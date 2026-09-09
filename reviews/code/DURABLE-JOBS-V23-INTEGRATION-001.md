# DURABLE-JOBS-V23-INTEGRATION-001 — bounded Gate 5 review

- Reviewer: `/root/runtime_review`
- Fixed point: `839001b427ccff851dc0332cfe2d731e207f2bee`
- Verdict: **APPROVED**

The verification inventory now requires and scans `tests/Jobs`, registers the
twelve current PHP tests as DB integration checks, and assigns their quality-graph
categories. Synthetic inventory/CI/quality fixtures create the new directory.
Historical protected digests and explicit subtraction semantics are unchanged.

Reviewed inventory hashes:

```text
5a33bd2db7788e8f00b3a8b7d522fe65938a5728f71ca856320ca2dcecc22077  tools/verification/run.sh
78202fdde564e4dc74408373802d5c87dacea2cf11031185a1ebfd4f6d6f4a16  tools/verification/suites.tsv
b54523a56f01a3afb7d18f8eeb27da51da8cb7e182fa70e1def36cd3f4f3d042  tools/verification/categories.json
c3fd94e899c8619539773a0800ac29d3900ac90e335e6e314d7e281ee0baad1b  tests/Verification/verification_inventory_001_test.py
```

The reviewed frontier fixture changes only current terminal results from 22 to 23,
extends applied-version lists with 23, and adds the six exact Jobs tables to the
three literal catalogues. Demo ready metadata likewise advances to 23. Assertions
that directly characterize older migrations retain their own historical version.
Initially stale current-terminal labels were corrected to 23 without changing
expectations or behavior.

Evidence reviewed:

```text
inventory contract: 15/15 PASS
verification CI contract: 15/15 PASS
quality graph setup: PASS
selected v23 compatibility executables: 21/21 PASS
git diff --check: PASS
```

The complete initial failure inventory is retained at
`/tmp/fmonitor-jobs-v23-frontier/results.json`; the final scope summary is at
`/tmp/fmonitor-jobs-v23-frontier/summary.md`. A shared stale test database was not
reset; the affected prepared-contour case passed on a fresh task-owned canonical
v23 database.

This approval covers verification registration and migration-frontier compatibility
only. Pending Jobs behavior tests may remain RED until their separately reviewed
production implementations land.

## PR64 first-CI demo/calendar correction addendum

GitHub Actions run `34315020151` at head
`49b7540bd39b9fb46b0c15d85fa392b085c402af` had exactly two failures in the complete
inventory `/tmp/pr64-49b7540b-failure-inventory.log`: the calendar verifier still
required terminal v22, and demo status became incomplete because its read-only
catalogue remained at 63 names while provisioning/marker already used v23.

Pre-correction hashes:

```text
12e0c3c313c260f76841a69709ee4e7753aec0876a3ad89dcbebfe2bbeb43e89  app/demo/PilotDemoDatabase.php
6b99acf4fb90aa4a07e0123a5a8f2776d6cc576bb57bb08d35084685e602903a  rapid-pilot/verify-calendar-projections.php
fcfc98cde1cb6629bbefbea8a77a9fac20560884ef656e4af547b5cfaee1a9e0  tests/InstallationProcess/pilot_demo_bootstrap_001_test.php
```

The independently reviewed demo test first changed only its literal expected
catalogue/marker to v23 and reproduced the causal RED: expected ready generation1,
observed incomplete/null. The production correction adds exactly the six reviewed
Jobs names to the read-only demo catalogue and changes its comment to v23. Calendar
changes only the terminal version/list expectation.

Reviewed final hashes:

```text
c7f7ed3917cf5323cb4f706b12fcdd6cc48fca74c314687b94eae4e9dc045a97  app/demo/PilotDemoDatabase.php
f52a390e79d21d01dd8df8237c1e4b44ca9e64f6215e5e9341295d2b3d59b970  rapid-pilot/verify-calendar-projections.php
fdfff010053c5b3832bfe8f325a9f1035c4ef12be3739e580cfb79fc651c01ae  tests/InstallationProcess/pilot_demo_bootstrap_001_test.php
```

Calendar verification and the complete demo public launch/walkthrough/persistence/
reset/cleanup test are GREEN; PHP syntax and `git diff --check` pass. **Bounded Gate3
and Gate5 verdict: APPROVED.** Authoritative PR CI rerun remains required.
