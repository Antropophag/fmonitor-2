# CLASSIFICATION-PROVENANCE-SCHEMA-001 v2 — fresh independent amended Gate 3 review

Date: 2026-09-02  
Reviewer: separately tasked independent agent `/root/classification_test_review`  
Gate: 3, replacement review after GRILL-009 amendment and new exact-hash owner approval  
Verdict: **APPROVED**

The reviewer did not author or edit the reviewed specification, tests, helper,
RED evidence or production WIP. This append-only review record is the reviewer's
only slice edit. Historical Gate 3 records were inspected only as evidence of
the reset; their approvals were not reused.

## Exact reviewed inputs

```text
d6227243dad996c7f67e3b0e8e9fcac0c100567e101ca66220a00946034e4790  specs/CLASSIFICATION-PROVENANCE-SCHEMA-001.md
8de39b681a64ef8a74c497c700e15f1a461930214fe2aa8320940b18490061cc  tests/InstallationProcess/classification_provenance_schema_001_test.php
409a00d9d6c0cb929a6a91800d115cc81245e7349e768ef21f66fb798a6a6c56  tests/Support/classification_provenance_barrier_runner.php
cb83026c22289f56eefb5972da3fdc6c2d4cf4b8f0e528aaf981d18a607cc099  tests/Support/classification_source_sentinel.php
1721f305ea11a2c881df4f666f94a34b16a95924c57fd88778889eac42d92ff0  docs/operations/classification-provenance-schema-red-evidence.md
acc9d92e9a96b7bf066a78a35cee16d43d00c767403755660230fec07963291d  docs/operations/grill-009-owner-decision-2026-09-02.md
24ac06f9a6a2b6eb86ccc5eefdc5742f12cd3e034e887aa481a713d7b0be4f20  docs/operations/grill-009-classification-session-exact-hash-approval-2026-09-02.md
ef196d2a50312cf9fad459e723674ea4c664b2cbb3c53d8265b39ff17c82fb63  docs/operations/classification-provenance-schema-gate1-rereview-v4.md
```

The executable specification hash is exactly the classification hash approved
by the owner, and the verifier independently pins that same literal hash before
executing behavior assertions.

Current production WIP considered when classifying RED and sensitivity:

```text
57a0d5750275b47ec9a7d6fd112a947d911a574d0c914c1d2635c7824971c086  app/InstallationProcess/ClassificationProvenanceDefinitionSchemaMigration.php
249dba52cfa5769d8832633aba95cec20f0769d9d63a011567d05d7380787a98  app/InstallationProcess/MariaDbClassificationProvenanceSchemaFingerprint.php
eb0be1af6396e251c928a3f17a76999a660de038c1cb8eb868eecb6afa8d6785  app/InstallationProcess/ClassificationProvenanceSchemaMigration.php
ee89beb758ce5bdd7a641ffa417fa38a13c3668f0f58b909a67d7809ed32f4a5  bin/fmonitor2-migrate.php
19df0be4d4a6dee9e75c5e18597c855c18d4e78a20248bb4259ee494cdd5294e  rapid-pilot/legacy-migration/LegacyMigrationRouter.php
```

## Traceability, seam and expectation independence

The verifier covers the amended contract's clean/exact/populated migration,
prefix/default boundaries, semantic drift and zero mutation, populated v1-v10
race predecessor and decoy preservation, three DDL-denied runtime provenance
kinds, missing/drift source-before-output boundaries, mandatory native
`PILOT_ONLY_OUTPUT_WITHOUT_PROVENANCE` contrast, and runtime DDL ratchet.

The amended race uses two real PHP subprocesses and the canonical public v11
application seam. Each child can publish its own literal arrival token only from
the injected callback. The parent refuses release until both files exist and
match independently fixed tokens, then publishes one shared release. The
unordered exact expected result is one `0/[11]` winner and one exit-70
`MIGRATION_FAILED` loser, followed by an ordinary production `0/[]` repeat.
The callback observes and pauses production-owned preflight/DDL; it does not
create the table or attest either terminal result.

Expected manifests, rows, transcripts, exit codes and mutations are literals or
test-owned worked fixtures derived from the approved specification. They are
not read from the migration definition, fingerprint implementation, production
runner output, or a test-owned substitute implementation. Presentation-only
secondary index names are normalized while primary-key identity, ordered
columns, visibility, direction, prefix length, constraints, engine, collation,
rows and AUTO_INCREMENT state remain sensitive.

## Amended barrier sensitivity and production isolation

The current WIP's `ClassificationProvenanceSchemaMigration::apply()` accepts
only connection and prefix, and executes plain DDL immediately after its absent
check. The helper's third callback argument therefore cannot be observed by
production WIP; neither child publishes an arrival. The reproduced failure is
the earliest amended missing behavior, which demonstrates that merely landing
v11 schema/runner WIP cannot make the replacement verifier GREEN.

Repository inspection found the callback construction and arrival/release file
protocol only in the test helper. The production migration catalogue and CLI
have no argv, environment or supported configuration switch for it. The
classification production owner/runner contain no `GET_LOCK`, `SLEEP`, durable
or ephemeral ledger, advisory lock, artificial delay or other race
serialization. The helper itself uses only a bounded polling wait after the
injected arrival; it does not serialize the production DDL.

## Determinism, failure classification and cleanup

Both children are bounded to eight seconds, checked for early exit before
release, and terminated/reaped from `finally` on any failure. Arrival/release
files and their private directory are removed; every random `t_cps_*` database,
manifest and source sentinel is owned by outer cleanup. Randomness selects only
private namespaces and does not determine expectations or ordering.

The first local attempt correctly classified `Connection refused` as an
environment setup failure. After `make test-env-up`, the same exact verifier
reached canonical v1-v10/v11 setup and failed at the new barrier assertion.
Post-run inspection found no `t_cps_*` database, no `/tmp/cps-barrier-*`
directory, and no live `classification_provenance_barrier_runner.php` child.

## Reproduced RED

```text
$ make test-env-up
Container fmonitor2-test-test-db-1 Healthy

$ php tests/InstallationProcess/classification_provenance_schema_001_test.php
PHP Fatal error: Uncaught TestFailure: injected verifier barrier is reached
after each absent-v11 preflight before plain CREATE
exit 255
```

Classification: **intended RED**, not setup failure, predecessor blocker or an
already-GREEN result. Both exact post-preflight arrivals are missing because the
approved injected seam is the unimplemented behavior.

Additional checks:

```text
$ php -l tests/InstallationProcess/classification_provenance_schema_001_test.php
No syntax errors detected

$ php -l tests/Support/classification_provenance_barrier_runner.php
No syntax errors detected

$ openspec validate canonicalize-classification-provenance-schema --strict
Change 'canonicalize-classification-provenance-schema' is valid

$ git diff --check
exit 0, empty output
```

## Verdict

No blocking Gate 3 finding remains. The amended Gate 2 test is deterministic,
implementation-sensitive, independently expected, cleanup-safe, and exercises
the approved verifier-only seam without a production activation path or hidden
serialization. Gate 3 is **APPROVED** for exactly the test/helper/evidence hashes
above. Gate 4 may implement only the approved minimal production seam without
changing those artifacts or expectations; any such change resets Gate 2/3.
