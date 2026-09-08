# Object-detail schema first implementation — CLI harness gap

Date: 2026-09-05. Implementer: `/root`.
Approved bounded Gate 3: `26bed9af6c70aefc690e89b5965246ea26628e1c`.
Test: `dba69ad414da65824399123b5499093afcf2a690`, unchanged by implementation.

The first implementation adds the canonical two-table owner and registers v12.
The focused test passes all direct API assertions (exact schemas, metadata
drifts, both partials/conflict directions, preservation, alternate collation,
25/26 boundaries) before failing at the successful CLI case:

```text
Expected exit 0: {"ok":true,"schemaVersion":12,"appliedVersions":[1,2,3,4,5,6,7,8,9,10,11,12]}
Actual exit 64: {"ok":false,"reason":"CONFIGURATION_INVALID"}
```

An independent read-only PHP subprocess probe passing environment
`FMONITOR_PROCESS_TABLE_PREFIX => ''` returned
`{"exists":false,"value":false}`. This host's proc_open drops the empty value;
the runner correctly rejects a missing required field. The harness must
preserve an explicit empty prefix; production validation must not accept a
missing one. This is a new test correction requiring independent Gate 3.

Architecture check: PASSED (7 rules). Both changed PHP files lint successfully;
diff-check exits 0. Exact production candidate SHA-256:

```text
e7518b3d97ad2c5ccf811625832aac4479a54a90d498cc1f1865030c60ea6fb9  app/InstallationProcess/ObjectDetailSnapshotSchemaMigration.php
6d4e6d6e89bf462524a8793e12d3ab69bd1d15125a5956ac52412fa2ad5f36e6  bin/fmonitor2-migrate.php
```

This is incomplete Gate 4, not GREEN or integration. The bounded first tranche
does not yet implement required named-lock/concurrency/observer behavior;
their reviewed RED and implementation remain required. Existing global v11
fixtures and importer runtime-DDL work also remain open. No tests/specs/config
were altered to make the implementation pass.
