# Code review: BITRIX-WORKFORCE-SCHEMA-001 v0.3

- Gate: 5 — fresh independent code review, superseding the earlier `CHANGES_REQUESTED` review
- Reviewed at: `2026-08-28T11:38:26Z` (UTC)
- Reviewer: separately tasked Codex agent `/root/bitrix_schema_final_gate5_fresh`
- Standards axis: separately tasked fresh agent `/root/bitrix_schema_final_gate5_fresh/standards_axis`
- Spec axis: separately tasked fresh agent `/root/bitrix_schema_final_gate5_fresh/spec_axis`
- Repository HEAD: `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Specification: `specs/BITRIX-WORKFORCE-SCHEMA-001.md`, version `0.3`, status `APPROVED`
- Approved current Gate 3: `reviews/tests/BITRIX-WORKFORCE-SCHEMA-001.md`
- Public seam: `BitrixWorkforceHistorySchemaMigration.apply(connection, tablePrefix = '')`
- Verdict: `APPROVED`

The reviewed inputs are working-tree files. HEAD identifies repository ancestry only; the SHA-256 manifest below identifies the exact approved state.

## Standards

**Verdict: `APPROVED` — no hard standards, integration, security, or maintainability findings.**

The prior shared-validator regression is resolved. `MariaDbSchemaInspector::validateTablePrefix()` retains the older identifier-safe alphabet contract without the workforce-specific length ceiling, while `BitrixWorkforceHistorySchemaMigration::apply()` locally enforces the v5 37-byte limit before table construction or any DB access. The approved integration test protects the existing process, catalog and capability migration callers with a 38-byte prefix.

All dynamic SQL identifiers derive from the locally validated ASCII prefix or fixed tokens. The catalog-provided v2 CHECK identifier is backtick-escaped before DDL. Catalog inspection is schema- and table-qualified, and all four targets are classified before mutation.

Non-blocking judgment calls:

- Possible Duplicated Code / Divergent Change: compact exact-schema manifests and DDL builders encode the same structures separately. In this exact migration slice the duplication provides an internal postcondition oracle and is guarded by the independent literal test contract.
- Possible Feature Envy: the migration performs richer catalog inspection beside the shared inspector. Defaults, named/table-qualified CHECKs and update rules are slice-specific; moving them into the shared helper would broaden unrelated integration behavior.

No applicable Mysterious Name, Data Clumps, Primitive Obsession, Repeated Switches, Shotgun Surgery, Speculative Generality, Message Chains, Middle Man or Refused Bequest finding was identified.

## Spec

**Verdict: `APPROVED` — no missing, partial, incorrect or out-of-scope behavior.**

Both prior spec blockers are resolved. Exact database-default table collation and per-character-column collation are compared. CHECK inspection joins on schema, constraint name and table and filters the inspected target table. CHECK normalization preserves literal bytes, escaped/doubled quotes, operators, membership parentheses and internal grouping while removing only formatting, identifier backticks and redundant wrappers around the whole expression.

The review also verified the complete approved contract: v5-local 37-byte validation and invalid-prefix zero-DB behavior; empty-prefix compatibility and raw-prefix-derived CHECK/FK/supporting-index symbols; two populated non-empty namespaces in one database; same-named table-qualified CHECK decoy isolation; exact columns, order, types, defaults, charset/collation, indexes, CHECKs and FKs; exact metadata cardinality; clean creation order; byte-preserving catalog upgrade; populated repeat; compatible partial recovery; legacy prefix-independent-symbol conflict; complete binary-sorted conflict preflight; missing-catalog conflict; and no DDL/DML on conflict. No additional requirement was imposed outside v0.3.

## Verification evidence

```text
Focused syntax and execution:
  php -l migration/inspector/test/support                         PASS
  php tests/InstallationProcess/bitrix_workforce_schema_001_test.php
    BITRIX-WORKFORCE-SCHEMA-001 v0.3 tests passed.

All current tests/InstallationProcess/*_test.php, sequential:     41/41 PASS
All current tests/InstallationProcess/*_test.php, parallel -P8:   41/41 PASS
All PHP in app/InstallationProcess, tests/InstallationProcess,
  and tests/Support:                                              syntax PASS
Scoped git diff --check:                                          PASS
Residual t_bw_schema_001_* / t_* test databases:                  0
Residual PHP/xargs test workers:                                  0
Residual review-created *.orig/*.rej/*.tmp and fmonitor_seq_*:    0
```

The first parallel-suite attempt had one unrelated transient `PILOT-HTTP-AUTH-001` process-cleanup assertion while the workforce test itself passed. A complete clean rerun passed all 41 tests in parallel; subsequent process, database and artifact cleanup checks were empty. This does not identify a BITRIX-WORKFORCE-SCHEMA-001 defect.

## Verdict and invalidation rule

**Gate 5 verdict: `APPROVED`. BITRIX-WORKFORCE-SCHEMA-001 v0.3 is complete for this manifest.**

Any change to the specification, executable test, support oracle, bootstrap, migration or inspector listed below invalidates this approval and requires a fresh independent Gate 5 review. Any test/support/bootstrap change also invalidates the cited Gate 3 approval and requires a fresh independent Gate 3 review before production changes resume. Addition, removal or rename of a relevant support/bootstrap/production file likewise invalidates this manifest.

## SHA-256 manifest

```text
79fcc5f44e8ad4877a55d26db01f17c521c10d03f5c88596e9ccf1ed8a6513a5  specs/BITRIX-WORKFORCE-SCHEMA-001.md
8313a1705ad3aede69adffc0bde6ca2158305a8ee088c234d41fad9826da985c  tests/InstallationProcess/bitrix_workforce_schema_001_test.php
e002de3151e5ef1012da3b201053788da6d35c8cba3321478e58418be5df9f16  tests/Support/BitrixWorkforceSchemaV5Contract.php
652708eea6099b750b805b996da195c6c2b3c6eb8616270323f491591f3935f0  tests/bootstrap.php
f903a36e93028e703558b49467034ff3354e8dbc4b7a5da17d8b9b354e9b2b62  app/InstallationProcess/BitrixWorkforceHistorySchemaMigration.php
923ee3d72fbaba6bb717bf236ce62e00d958908ffc422b15feef82e029481a03  app/InstallationProcess/MariaDbSchemaInspector.php
```
