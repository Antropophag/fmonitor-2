# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v54 — command Gate 5 domain RED evidence

- Date: `2026-09-05`
- RED author: `Codex agent /root/command_gate5_red_domain`
- Gate 5 finding record: `fa97cfd5f5ca900424bcf9863eff66ed6b701a2e`
- Production under test: `6c4fb5b70065cabb19adab23f6004714c1f0699a`
- Scope: findings 3, 4, 5 and 6 only

## Executable identities

```text
f788e80143c25cda53fb02d79a4089248ce6079fcf1586b6aeb65b53d5ba6486  tests/InstallationProcess/assignment_order_original_gate5_domain_red_001_test.php
40545c57c70239975062e0e677d3f4f82e89e5b0ef7944ffa270949468a8c916  tests/InstallationProcess/assignment_order_original_gate5_mariadb_red_001_test.php
```

The domain suite uses only the public application/dependency contracts. Its
repository, stream, storage and lifecycle adapters are test-owned and expose
call ordering independently of the production implementation. It covers:

- two different assignment orders accepting independent INITIAL roots;
- authorization, terminal-request and fingerprint `UNAVAILABLE` exact
  `FAILED/PERSISTENCE_FAILURE/retryable=true` outcomes before stream access;
- non-`COMMITTED` and throwing attempt/audit persistence replacing the
  otherwise selected unaudited result with that exact technical failure;
- correction composition/lineage drift before stream access;
- the actual `AFTER_PRIVATE_FINALIZE_BEFORE_COMMIT` callback observing a lease
  created by `stage.finalize()` and still held at the callback.

The MariaDB suite creates one random, regex-bounded database and drops exactly
that database in `finally`. Its source tables intentionally have no constraints
that could hide malformed legacy facts. It checks all rows before exclusion:
duplicate release identities, missing release end, reversed release dates and a
release after the order date. A missing-table commit then distinguishes a real
MariaDB technical failure (`ROLLED_BACK`) from CAS `CONFLICT`.

## Demonstrated intended RED

Commands:

```text
php tests/InstallationProcess/assignment_order_original_gate5_domain_red_001_test.php
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/assignment_order_original_gate5_mariadb_red_001_test.php
```

First exact mismatches against the reviewed production are:

```text
Authorization UNAVAILABLE has the only contract-valid technical tuple.
Expected: FAILED / PERSISTENCE_FAILURE / true
Actual:   REJECTED / PERSISTENCE_FAILURE / true

Malformed all-row composition case 0 is invalid, not filtered into a valid snapshot.
Expected identity: NULL
Actual identity:   composition-81-v1
```

Both commands exit nonzero. PHP lint passes for both files and `git diff
--check` exits zero. The failures are intended contract mismatches, not fixture,
database-connectivity or cleanup failures. Later assertions remain executable
oracles for the remaining reviewed branches; Gate 3 must review the exact test
bytes before production correction.

This record grants no Gate 3 or Gate 5 approval. The RED author is ineligible to
review these tests or the later production correction.
