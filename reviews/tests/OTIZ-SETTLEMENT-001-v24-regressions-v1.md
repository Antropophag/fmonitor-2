# OTIZ-SETTLEMENT-001 — canonical v24 regression amendment review v1

- Date: `2026-09-10`
- Reviewer: separately tasked agent `/root/settlement_review`
- Test amendment author: root
- Reviewed exact candidate: `a14058f0`
- Verdict: **CHANGES_REQUESTED**

Most changes are correct setup-only consequences of adding canonical successor
v24: current full-catalogue results append 24, repeats terminate at 24, and the
two updated broad production/demo inventories add only
`fm2_otiz_settlement_locks` and `fm2_otiz_settlement_operations`. Historical
direct Jobs-v23 and explicit settlement predecessor-v23 expectations remain
unchanged.

## R1 — BLOCKING: a third full table inventory still expects 69 tables

`tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php:11`
keeps `iicTables()` at 69 entries and omits both settlement tables, while line20
now runs the full v24 catalogue. Its message also remains “exact literal
69-table catalogue.” The suite is internally unable to pass.

Independent focused reproduction:

```text
$ php tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
SETUP_FAILURE: exact literal 69-table catalogue
expected 69 entries; actual includes 71 entries
child exit 255; parent expected0/actual255
```

Add exactly the two settlement tables to `iicTables()` and update the diagnostic
cardinality to 71. Preserve every existing table and downstream expectation.

## R2 — LOW: changed current-catalogue diagnostics still say v23

The values correctly expect v24, but labels remain stale at:

- `identity_access_schema_001_test.php:309,342,351,354`;
- `inspection_planning_schema_001_test.php:132`;
- `object_detail_snapshot_schema_001_test.php:340`.

Update only these current full-catalogue diagnostic labels to v24. Do not change
the genuine historical/direct Jobs23 or settlement predecessor-v23 labels.

No other expectation weakening or incorrect version ordering was found in the
reviewed amendment. The saved focused frontier, OTIZ runtime and Jobs schema
checks are useful but do not cover the failing inventory above. Gate 3 for the
compatibility amendment remains **CHANGES_REQUESTED** pending the narrow
root-authored correction and rereview.
