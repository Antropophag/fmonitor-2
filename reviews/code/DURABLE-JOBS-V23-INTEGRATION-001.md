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
