# INSPECTION-PLANNING-SCHEMA-001 Gate 2 superseding RED evidence

Date: 2026-09-02  
Author role: fresh separately tasked Gate 2 correction author  
Verdict: **QUALIFYING RED OBSERVED — READY FOR FRESH INDEPENDENT GATE 3 REVIEW**

This evidence supersedes the earlier non-qualifying artifact and incorporates
every finding from the prior `CHANGES_REQUESTED` test review. No production file
was changed.

## Approved artifact and verifier identity

The owner reconfirmed the current artifact after the independent metadata-only
hash reconciliation recorded in
`inspection-planning-schema-owner-approval.md`:

```text
464df8d8cdccea4aeb0997d2e397a3d22958f7c8d04a98e556b59d2c055c888c  specs/INSPECTION-PLANNING-SCHEMA-001.md
354f761918a276d1a9f6c7c2da9ed628d539a038818695207e063d3181498602  tests/InstallationProcess/inspection_planning_schema_001_test.php
abf3f4cf4f518e9dd87b2f4e1d38f744a20f636d384e1e6587165700c82a7839  tests/InstallationProcess/inspection_planning_runtime_ddl_001_test.php
0bd08ec0a3cff88b8e652195fa9404425b8a511fa2a3b704f6493b52267e7b81  tests/Support/inspection_planning_runtime_router.php
f9ef81237e1000b5d2efa3a67f0d6184ae273834a36d516f6dcc8dd7367273ea  tests/Support/inspection_planning_bootstrap_wrapper.php
aaaa45f3b59d2d753002b3407474bdd44dbd180c3d8bd948091458569d874f36  rapid-pilot/docker-bootstrap.php
```

Both verifiers and the isolated HTTP router pass `php -l`.

The final Gate 2 correction adds no production code. It closes the remaining
creation-result findings by asserting both clean tables contain zero rows before
sentinel insertion and by exercising clean plus both populated partial
orientations through the direct migration seam. Each direct call compares the
whole returned array to a test-owned literal, so an extra result key fails; the
clean `tablesCreated` list is binary ascending and each partial list contains
only its missing family member.

## Healthy environment and qualifying RED

`make test-env-up` passed and the disposable MariaDB container reported healthy.
After the final correction, `php -l
tests/InstallationProcess/inspection_planning_schema_001_test.php` also passed,
and all three focused commands below reproduced the same intended RED outcomes.

The schema command:

```sh
php tests/InstallationProcess/inspection_planning_schema_001_test.php
```

reached the real public runner after creating an isolated database and failed
with exit 255 because the landed runner reports exact v8 instead of required v9:

```text
clean v1-v9 exact runner result
Expected: schemaVersion 9, appliedVersions [1,2,3,4,5,6,7,8,9]
Actual:   schemaVersion 8, appliedVersions [1,2,3,4,5,6,7,8]
```

The runtime command:

```sh
php tests/InstallationProcess/inspection_planning_runtime_ddl_001_test.php
```

created its isolated database and DML-only runtime principal, started a real PHP
HTTP subprocess, exchanged a real scheduling POST, and failed with exit 255:

```text
DML-only healthy scheduling
Expected: 303
Actual: 503
```

The healthy exact family already exists. The failure therefore demonstrates the
missing behavior: current scheduling still attempts runtime DDL and cannot work
under the required DML-only principal. These are behavior assertions after
healthy setup, not environment failures.

The corrected, independently selectable bootstrap branch was run with:

```sh
FMONITOR_IPR_BOOTSTRAP_ONLY=1 php tests/InstallationProcess/inspection_planning_runtime_ddl_001_test.php
```

It connected successfully to the disposable database with the DML-only
principal, then failed with exit 255 for the intended bootstrap defects:

```text
INSPECTION-PLANNING-SCHEMA-001 intended RED:
- execution reaches hostile planning readiness
- stderr redacts configured prefix
```

All other accumulated bootstrap assertions passed in that same execution:
non-zero exit, no ready manifest, no fixture/import/product DML, no row-byte,
schema or allocator mutation, and redaction of the supplied secret, password,
planning table identifier and DDL tokens. The branch selector skips only the
unrelated HTTP matrix for this focused evidence run; the default command still
runs both the complete prior HTTP matrix and the bootstrap branch.

## Coverage map resolving the prior review

| Review finding / normative requirement | Executable coverage |
|---|---|
| Independent exact table fingerprints | Literal ordered column manifests include type, nullability, SQL default, `EXTRA`, `IS_GENERATED`, generation expression, per-column charset/collation; literal ordered index manifests include name, uniqueness, sequence, column, sub-part, direction, type and `IGNORED`; exact engine/table collation plus zero FK/extra constraints and exact CHECK count are asserted. |
| CHECK normalization | `ipsNormalizeCheck()` removes only ASCII whitespace/backticks and repeatedly removes a balanced outer pair only when it encloses the complete expression. It preserves the normative function parentheses and requires exact `json_valid(payload_json)`. Absent, extra, changed and duplicate-equivalent cases are separate fixtures. |
| Public runner matrix | Fresh clean v1–v9, populated repeat, populated schedules-only partial and populated events-only partial use only `bin/fmonitor2-migrate.php`; the clean family is asserted to contain zero rows in both members before sentinel insertion, and partial rows, Unicode JSON bytes and explicit next allocators are snapshotted. Conflict cases additionally require exact runner exit/stdout/stderr. |
| Direct migration result and no backfill | A separate clean direct-family call requires the exact no-extra-key result `applied=true`, `schemaVersion=9`, and both created names in binary-ascending order, then proves both tables contain zero rows before any sentinel insertion. Both populated partial orientations separately require that same exact result shape with only the missing member in `tablesCreated`, preserve the populated member byte/allocator state, and prove the newly created sibling is empty. |
| Preservation and hostile conflicts | Changed type, default, generated column, column collation, extra index, extra UNIQUE, descending index, sub-part, FULLTEXT type, ignored visibility, FK and table collation are independent cases. A two-table hostile conflict requires the complete binary-ascending list and zero family mutation. |
| Prefix and decoy isolation | 25/26, invalid-character and non-ASCII public-runner boundaries use deliberately unreachable DB settings. Populated incompatible unprefixed and other-prefix decoys, including Unicode JSON, are byte-equivalent. Family-local 28/29 remains evidence only. |
| Database defaults | Exact `utf8mb4_unicode_ci`, applicable MariaDB UCA alias, and non-utf8mb4 rejection with zero target mutation are executable. Both created tables must explicitly inherit the observed validated database default except exact `payload_json` `utf8mb4_bin`. |
| Real runtime processes | The runtime verifier creates separate healthy/missing/incompatible prefixes, a DML-only principal, and a real isolated PHP HTTP server. It exchanges scheduling POST, Calendar GET, object-queue GET and construction-control GET through real rapid-pilot handlers. |
| Exact fail-closed outcomes | Exact 503 statuses, UTF-8 bodies/newlines, absence of redirects, queue content type, strict 12-hex regex and freshness across two exchanges are asserted. Exact bodies exclude partial HTML. Before/after observations now capture every prefixed table's full `SHOW CREATE`, table metadata and allocator, ordered column/index/constraint metadata, row count, and independently base64-encoded/sorted bytes for every column of every row plus a SHA-256 digest. This detects UPDATE and count-neutral mutations as well as inserts/deletes, schema repair and partial creation. |
| Healthy DML-only outcomes | Existing migrated family requires scheduling 303 and Calendar/queue/control 200 under the same DML-only principal, so missing/incompatible success cannot be fabricated by denying all database access. |
| Real bootstrap contract | A test-owned configuration adapter reads the real `rapid-pilot/docker-bootstrap.php`, requires exactly one match for each of its three hard-coded settings, and changes only DB connection plus process/legacy prefixes in a disposable copy. It then launches that complete configured script as a separate process against the isolated database, genuinely DML-only credentials, hostile incompatible planning family, `test-fixtures` mode and isolated `HOME`. A direct connection probe distinguishes setup from RED. Accumulated assertions require hostile-readiness reach, non-zero exit, no `active.json`, no fixture/import/product byte/schema/counter mutation, and absence of supplied secret/password/prefix/SQL/table identifiers from stderr. The current script fails before hostile readiness and leaks its configured prefix: both are intended missing behavior. |
| Cleanup and scope | Random database, user, prefixes, server process, private HOME and disposable configured-bootstrap tree are bounded; `finally` paths stop/reap the process, drop user/database and recursively remove both private trees even on assertion failure. No concurrent-runner/ledger promise is asserted. |

## Gate state

OpenSpec tasks 2.1 and 2.2 are complete as test-author work. Production remains
unchanged and both focused verifiers are RED for the approved missing behavior.
A different fresh agent must review these exact hashes and record Gate 3 verdict
in `reviews/tests/INSPECTION-PLANNING-SCHEMA-001.md` before any GREEN work.
