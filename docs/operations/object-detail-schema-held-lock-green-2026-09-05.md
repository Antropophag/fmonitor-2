# Object-detail schema held-lock GREEN and architecture correction

Date: 2026-09-05. Implementer: `/root`.
RED: `30657cd34b40041e78bcd0ce42622e9aff52a144`.
Independent Gate 3: `b60216bfe4a8ccbc827977c5ea00ac6e6fde630b`.
Initial lock implementation: `5d47fde9112dcde059f592d4ce72c5965843cfa7`.

Migration now obtains the exact specified database/prefix lock before metadata
inspection/DDL and holds it through final verification. Acquisition failure
returns DatabaseUnavailable; successful acquisition has one release attempt in
finally, with release failure mapped to DatabaseUnavailable.

Initial architecture check rejected the 169-line migration hotspot. The
baseline was not increased. Exact metadata classification was extracted to
`MariaDbObjectDetailSnapshotSchemaFingerprint`, retaining one migration/DDL
owner and the same metadata assertions. No test or spec was changed.

After this extraction, the following all passed against these exact working
tree production bytes (preserved in the containing commit):

```text
object_detail_snapshot_schema_lock_001_test.php: PASS held-lock rejection/retry
object_detail_snapshot_schema_001_test.php: PASS canonical migration contract
production_migration_runner_001_test.php: PASS CLI contract
make architecture-check: ARCHITECTURE CHECK PASSED (7 rules)
PHP lint: both production files passed
git diff --check: exit 0
```

This closes Gate 4 for the admitted held-lock tranche only. Fresh independent
code review remains required; observer interruption, two-creator concurrency,
remaining composed consumers, importer no-DDL and full verify still prevent
full migration/integration completion.
