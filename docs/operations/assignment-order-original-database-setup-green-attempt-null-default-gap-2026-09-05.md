# Assignment-order original setup — nullable default normalization gap

Date: `2026-09-05`

Status: **GATE 2 RESTART REQUIRED**.

The Gate-3-v6-approved verifier passed CHECK round-trip and reached the next
real MariaDB structural observation. MariaDB reports `COLUMN_DEFAULT` as the
string `NULL` for nullable columns without a non-null default, while the
approved manifest expects PHP `null`. The first mismatch was
`previous_revision_id`; all nullable columns share it.

A disposable database independently tested an implicit nullable column,
explicit `NULL`, `DEFAULT NULL`, and `NULL DEFAULT NULL`; every row returned
the string `NULL`. Therefore no conforming DDL can satisfy the current raw
comparison on this supported MariaDB.

The unreviewed production attempt was removed and the probe database dropped.
Task 2.2 is reopened. The RED observer must normalize only
`IS_NULLABLE='YES' && COLUMN_DEFAULT==='NULL'` to canonical null, retain
non-null/wrong-default sensitivity, reproduce intended RED, and obtain a fresh
Gate 3.
