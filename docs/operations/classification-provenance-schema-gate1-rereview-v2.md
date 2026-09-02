# CLASSIFICATION-PROVENANCE-SCHEMA-001 — independent Gate 1 rereview v2

Date: 2026-09-02  
Reviewer: fresh separately tasked agent `/root/completion_test_review`  
Verdict: **READY_FOR_OWNER_APPROVAL**

The reviewer did not author or edit the executable specification, OpenSpec
artifacts, evidence, production or tasks. This verdict supersedes the prior two
`CHANGES_REQUIRED` records for the exact coherent package below. It permits an
explicit owner decision; it does not itself authorize Gate 2.

## Reviewed hashes

```text
a044645fac8c347e98ae876f1dfdb98c12944a1c4fde85a098f99b6a84be71ed  specs/CLASSIFICATION-PROVENANCE-SCHEMA-001.md
d7fb395224fa48abf1e837df2091ffc076d945b4faeb31852f9072f09e7bb657  openspec/changes/canonicalize-classification-provenance-schema/proposal.md
73c6af7898e70a87752d1738600403e030fb403b64df25fd09847ab9dc1e54a0  openspec/changes/canonicalize-classification-provenance-schema/design.md
f58663b7e2640b277ab64a12bc4bff7893bb73c65d7571c811d9b09efaceefb6  openspec/changes/canonicalize-classification-provenance-schema/specs/persistence/classification-provenance-schema/spec.md
0ed2d3951e2fa089fedcc335843c72210e34daf25368b524ce3daeee34e92bde  openspec/changes/canonicalize-classification-provenance-schema/tasks.md
aa69137e8cc5a59fe2462df9d751a01b2d487499867258a26ceff00ae2c772e7  docs/operations/classification-provenance-schema-gate1-review.md
37cc0b5941cad03ed567ea4a33ecd560634ac29197b4e57807c5baf735d0c774  docs/operations/classification-provenance-schema-gate1-rereview.md
7eb5f2c8936fc4a75aa8708130eab7ffccb74fa6a4895802e64bd36d2cf2bfd5  docs/operations/active-baseline-provenance-schema-evidence.md
df1c58fa0437fc51868db2e91bcba35793d07957f6359aa36407552e8a17319d  bin/fmonitor2-migrate.php
```

## Closure of all prior findings

### No durable migration ledger

Executable spec, proposal, design, delta and tasks now consistently state that
the canonical runner exposes per-invocation `schemaVersion` and
`appliedVersions` only. No durable ledger row, general lock or cross-runner
serialization contract is claimed. Clean reports v11 when that invocation
creates the target; ordinary exact repeat reports no applied version and writes
no product/audit fact.

### Bounded race is exact and feasible

The race begins from one exact populated v1-v10 database/prefix with only the
v11 target absent. Two real runner processes/connections contend on that table:
one exact winner returns exit 0, terminal v11 and `[11]`; the loser returns exact
redacted exit 70 `MIGRATION_FAILED` with empty stderr after losing CREATE.
Final target is one exact empty table; predecessor data and decoys are unchanged;
the next ordinary repeat returns exit 0 and empty applied versions.

This is implementable with the current runner's generic migration-failure
mapping and does not pretend that concurrent clean v1-v11 catalogues or other
migrations are supported. Non-table-exists errors remain failures; the final and
repeat assertions prevent an incompatible/partial race outcome from being
accepted.

### Three runtime failures are executable

Executable and delta specs agree on exact public outcomes before source
connection/fetch:

- native apply: exit 2, exact `NATIVE_BATCH_UNAVAILABLE` JSON, empty stderr;
- historical apply: exit 2, exact `HISTORY_BATCH_UNAVAILABLE` JSON, empty
  stderr;
- active apply: exit 2, exact `ACTIVE_BATCH_UNAVAILABLE` JSON, empty stderr.

Each requires an independent connection/query sentinel proving zero source
access plus complete target/ambient/decoy snapshots proving zero output,
provenance, schema, counter or ready-publication mutation. Redaction excludes
credentials, SQL, prefix/table/classification literals and source payload.

The active exact DDL-denied storage proof remains bounded to literal provenance
reconcile. Its missing/drift batch precondition may fail at the public batch
boundary but must neither create/check the two optional cutover tables nor imply
their canonical ownership or product approval.

### Mandatory non-atomic contrast is native-only

All artifacts now require one release-supporting native operational contrast.
The verifier proves the literal eligible object had no case, creates exactly one
case in that invocation, and encounters a prepared mismatched proof with the
same output identity before expected provenance append. The public result is
the exact native unavailable outcome; the new case remains, the injected proof
is byte-exact, and no matching expected provenance row exists.

`PILOT_ONLY_OUTPUT_WITHOUT_PROVENANCE` is only the transcript classification.
Historical and active contrasts are explicitly not required, and the package
does not normalize the observed non-atomic window into target semantics or a
new public domain error.

## Reconfirmed contract

- Literal v11 follows the landed exact v1-v10 catalogue and precedes import/
  bootstrap output creation.
- The 39-byte basename preserves the catalogue-wide 25-byte ASCII success /
  26-byte, invalid and non-ASCII pre-access rejection boundary.
- Database-default policy is exact utf8mb4 with safe applicable explicit
  collation; incompatible defaults map to redacted exit 70 with zero mutation.
- The exact table retains ten ordered NOT NULL columns with SQL NULL metadata
  defaults, unsigned identities, sole auto-increment `id`, plain TEXT reasons,
  exact character metadata, InnoDB and no FK/CHECK.
- Index presentation names remain non-normative, while the exact multiset is
  primary `id`, unique ordered `(output_kind,output_id)` and nonunique
  `(legacy_object_id)` with full ascending visible BTREE columns. Extra,
  duplicate, missing, order/subpart/type/visibility drift conflicts.
- Absent/exact/conflict preflight occurs before target mutation; populated
  rows, Unicode/plain JSON bytes, hashes, timestamps, ids, next AUTO_INCREMENT
  and decoys are preserved without backfill/UPDATE/DELETE/taxonomy normalization.
- `operational_case`, `historical_snapshot` and `active_baseline` remain bounded
  PILOT_ONLY storage compatibility literals with append/replay/conflict behavior,
  not a DB enum or approved taxonomy.
- Optional `fm2_legacy_active_baselines` and `fm2_active_case_provenance` remain
  excluded and ambient; no legacy-active cutover choice is made.
- Diagnostics, append-only provenance history, rapid-pilot adapter boundary and
  mandatory Gates 1–5 remain intact. No task incorrectly marks owner approval,
  RED, GREEN or Done complete.

## Verification

```text
openspec validate canonicalize-classification-provenance-schema --strict
Change 'canonicalize-classification-provenance-schema' is valid

git diff --check -- <reviewed planning artifacts>
exit 0, empty output
```

## Owner approval boundary

The owner may approve exact executable-spec SHA-256:

```text
a044645fac8c347e98ae876f1dfdb98c12944a1c4fde85a098f99b6a84be71ed  specs/CLASSIFICATION-PROVENANCE-SCHEMA-001.md
```

Any normative executable-spec change after this review requires a new hash and
fresh independent Gate 1 review. After owner approval, record that exact decision
before Gate 2. Verdict: **READY_FOR_OWNER_APPROVAL**.
