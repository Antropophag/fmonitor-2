# Assignment-order original setup — MariaDB regex normalization gap

Date: `2026-09-05`

Status: **GATE 2 RESTART REQUIRED**.

After Gate 3 v5 approval, a second minimal GREEN attempt passed the corrected
table-scoped CHECK query but proved the remaining normalizer oracle impossible.
MariaDB returns the approved `NOT REGEXP '[[:cntrl:]/\\]'` semantic expression
with its SQL literal backslash representation doubled in `CHECK_CLAUSE`. The
test normalizes `!()` to `NOT REGEXP` but does not normalize this representation.

An independent disposable database created the roots table using the test's
own `AssignmentOrderOriginalDatabaseSetupV1::rootsDdl()` and returned:

```text
char_length(root_original_id) ...
  !(root_original_id regexp '[[:cntrl:]/\\\\]')
```

while `Contract::checks()` expects the equivalent pattern ending in `.../\\]`.
Thus even the test's canonical DDL cannot satisfy its oracle after round-trip.

The unreviewed production attempt was removed completely and the disposable
probe database was dropped. Task 2.2 is reopened. RED author must narrowly
canonicalize MariaDB's doubled CHECK-clause string escaping while preserving
operand/pattern sensitivity, then obtain fresh independent Gate 3.
