# Independent code review — INSTALLATION-COMPLETION-SCHEMA-001

Date: 2026-09-02  
Reviewer: fresh separately tasked agent `/root/completion_test_review`  
Verdict: **CHANGES_REQUESTED**

The reviewer did not author or edit the specification, tests, production,
evidence or tasks. This review record is the only edit. Gate 5 and Done are not
approved.

The `code-review` skill split Standards and Spec analysis. A fresh separate
agent `/root/completion_test_review/completion_gate5_standards` completed the
Standards axis. The second parallel slot was unavailable (`agent thread limit
reached`), so the already-independent parent reviewer performed the Spec axis.

## Reviewed contract and hashes

```text
c6f3cf995a81d214559d4078696f82d6d2cfaa1123120cb91775fc5c6b5c5448  specs/INSTALLATION-COMPLETION-SCHEMA-001.md
4c47e0577ba63a51489ba0c3cecb47ff20c08a6bf1e238a399b00f29e73f4f46  docs/operations/installation-completion-schema-owner-approval.md
3f139844be5fbf9898474f3075612ff9e8403e8819b29f43236c4f6cd3a82ebe  reviews/tests/INSTALLATION-COMPLETION-SCHEMA-001.md
5c34a12971e09a0307959dbf19b5316d455195653c473b0a3296e2622894b650  docs/operations/installation-completion-schema-green-verification.md
0722db047306d0429fd092eece4818b209896ae10f4a6574a1f7a752b3b8eec5  docs/operations/installation-completion-v10-integration-fixture-review.md
2bd07107c2f39b692331ed71f74a2bfc8072ee14d6a9c563b259a348762948ce  openspec/changes/canonicalize-installation-completion-schema/tasks.md

ebd59ff1ca8c9968a6726da4f9d45f133477951f81f11a529e61fd61079ac1f7  app/InstallationProcess/InstallationCompletionSchemaMigration.php
c70223a44d716e967ce317d948871614d36c25ee902ba36c0296a55c516d153a  app/InstallationProcess/InstallationCompletionDefinitionSchemaMigration.php
846f05e1aedd215ab824512e159f54c110553c2d929639f4fdaf92293ca5cd54  app/InstallationProcess/MariaDbPilotLegacyObjectSchemaReadiness.php
df1c58fa0437fc51868db2e91bcba35793d07957f6359aa36407552e8a17319d  bin/fmonitor2-migrate.php
2f162a78f05d2d6efd70e512211ad3fc06b25900e024adfca67c258b286e1dc8  rapid-pilot/CompletionFlow.php
1244e6d550594453d32e21c49d351410b127d82b5c7812e512cc240f573125fc  rapid-pilot/Otiz.php
700393249fd0c0982564ede62e75f090810624b5bb82eeea8330590e3c0dc29f  rapid-pilot/docker-bootstrap.php
d23b3563c1921c9b54abe0d60134eaabb0a4aad367a2141e3203934757ebb10a  rapid-pilot/docker-entrypoint.sh
ecb4d052cfff2b4f8c04cbae49d5c081eae452d9ad22d825d80f323498c01b38  tools/architecture/baseline.json
1c5d0563a0399a9457ec88a535a5d0e2afc7ecba4286419a62b7131606c2dafe  tools/architecture/check.py

b70ef939e67152afd4059f419464d3d5c3cc5644448e415ca76348b1759e118d  tests/InstallationProcess/installation_completion_schema_001_test.php
8427a39674cf8d4c0e710f164bca14487b88a6fb54815fca699fc5119064618d  tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php
1331bc60607cd8a6c7f7e87a0bcb1d837a727c90e00465185dfd789c2ebbb682  tests/Support/installation_completion_runtime_router.php
986146019ef542e8fb3e62267d5399c03f932b657343f01a22475238f8b24fc2  tests/Support/inspection_planning_bootstrap_wrapper.php
```

The owner-approved normative hash remains the recorded `c63ed10e…`; the
current spec hash is its reviewed administrative status transition.

## Spec axis

Verdict: **CHANGES_REQUESTED**.

### S1 — reserved supporting index is incorrectly ignored by the exact fingerprint

`InstallationCompletionSchemaMigration::matches()` removes every observed
index named `fk_completion_correction_previous` before comparing the correction
manifest:

```php
$actualIndexes = array_values(array_filter(
    $actualIndexes,
    static fn(array $index): bool =>
        $index['name'] !== 'fk_completion_correction_previous',
));
```

The approved final manifest contains no such index and §3 requires extra named
indexes to conflict. Consequently an otherwise exact correction table with an
extra index of that exact name is reported `exact`; public repeat returns a
no-op and runtime `isCompleteCompatible()` returns true. This is also the real
interrupted state possible between the auto-committed correction CREATE and the
separate ALTER that drops MariaDB's generated supporting index. A restart then
accepts the non-final schema without removing the index or reporting conflict.

This violates exact fingerprinting, allowed partial states and restartable
normalization. Remove the blanket filtering or explicitly model the generated
intermediate as a non-final recoverable form that deterministically completes
normalization and is never accepted by runtime readiness.

The approved Gate 2 mutation matrix checks generic extra/subpart/type/visibility
indexes but not this reserved-name exception. Therefore current tests can
falsely pass this defect. Per `docs/development-process.md`, adding the required
reserved-name/crash-window RED returns the slice to Gate 2 and requires fresh
independent Gate 3 approval before production correction.

### Positive Spec observations

Apart from S1, the implementation conforms closely:

- v10 is literal after v1-v9 and runner prefix/database-default failure mappings
  are exact and redacted;
- definitions cover ordered columns/defaults/generated/character metadata,
  indexes, named composite/root FKs, CHECK semantics, engine and collation;
- family-wide inspection precedes creation, root-only predecessor is lossless,
  reverse partial and occupied conflicts fail before sibling mutation;
- correction storage is append-only and enforces ordinal, predecessor,
  same-root, no-branch and bounded nonblank-reason invariants without adding a
  correction command or payload/details rewrite;
- CompletionFlow queue/card/checklist/POST and bootstrap call read-only readiness
  before completion reads/writes, produce approved missing/drift outcomes and
  retain exact successful behavior under a DML-only principal;
- bootstrap no longer owns legacy, sentinel, planning, completion or OTIZ DDL,
  maps readiness failure to exact exit 70 and publishes no ready manifest first;
- no completion facts, projections, premium facts or authorization behavior are
  invented by migration; no diagnostic SQL/schema/credential detail is exposed.

## Standards axis

Verdict from fresh standards reviewer: **CHANGES_REQUESTED**.

### ST1 — hotspot ratchet is passed literally but defeated structurally

`InstallationCompletionSchemaMigration.php` is exactly 149 lines, one line
below the architecture hotspot threshold, while owning DDL generation, duplicate
expected manifests, family-state inspection, four information-schema
comparisons, MariaDB index normalization and CHECK parsing. Lines 74–118 encode
the same schema twice as SQL and PHP arrays; lines 122–147 compress substantial
branching into one-line statements.

This is actionable Duplicated Code/knowledge and Divergent Change, with future
schema edits requiring shotgun changes. The current file passes the mechanical
ratchet but not its maintainability purpose. Format normally and separate a
coherent manifest/inspection responsibility, then accept the honest line count
through the required explicit architecture review/baseline ceiling if it is a
justified migration owner. Do not preserve 149 lines through compression.

### ST2 — the index workaround changes caller session state

`InstallationCompletionDefinitionSchemaMigration::removeRedundantSupportingIndex()`
always executes `SET FOREIGN_KEY_CHECKS=1` in `finally`, regardless of the
incoming session value. A caller that intentionally entered with checks disabled
leaves with different connection semantics, affecting subsequent migrations.
Read and restore the prior session value. The class/method name should also
describe the actual MariaDB supporting-index normalization rather than a vague
“definition schema migration”.

### Positive Standards observations

Runtime completion DDL and its architecture fingerprints were removed rather
than grandfathered. The baseline contracts rather than expands completion debt;
the public migration seam remains in InstallationProcess; rapid-pilot contains
readiness/wiring and existing behavior, not new correction domain logic. No
additional Feature Envy, Data Clump, Primitive Obsession, repeated-switch,
Message Chain, Middle Man or Refused Bequest finding was identified.

## Verification evidence

Previously reproduced at the exact approved test hashes and recorded in Gate 4
evidence:

```text
PASS: INSTALLATION-COMPLETION-SCHEMA-001 migration and chain matrix
PASS: INSTALLATION-COMPLETION-SCHEMA-001 DML-only runtime matrix
```

The independent v10 integration review additionally reproduced production
runner, v5/v6/v7/v8/v9 direct suites, inspection-item MariaDB, case import,
calendar, completion, OTIZ and canonical-compat harness GREEN.

Current review commands:

```text
make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

make lint
exit 0

openspec validate canonicalize-installation-completion-schema --strict
Change 'canonicalize-installation-completion-schema' is valid

git diff --check -- <completion-owned production files>
exit 0, empty output
```

A fresh rerun of the two DB-backed completion executables during this code
review encountered `Connection refused` before test behavior because the prior
fresh-verification teardown had stopped the local MariaDB contour. This is an
environment/setup observation, not a replacement for the exact-hash GREEN runs
above and not evidence of completion. No database was restarted or test result
fabricated by this review-only task.

The recorded full `make verify` remains non-GREEN in DB/E2E stages for separately
owned local-RBAC/login/combined-PDF integration debt. That classification is
honest but cannot override the blocking S1/ST1/ST2 findings in completion-owned
production.

## Required next actions

1. Add a deterministic RED for an extra supporting index named exactly
   `fk_completion_correction_previous` and the interrupted CREATE-before-DROP
   state; capture evidence and obtain fresh Gate 3 approval.
2. Correct fingerprint/restart behavior without weakening the exact manifest or
   runtime fail-closed contract.
3. Preserve and restore the incoming `FOREIGN_KEY_CHECKS` value in the MariaDB
   normalization helper.
4. Reformat/decompose the 149-line migration owner honestly and obtain any
   required architecture baseline review rather than threshold gaming.
5. Rerun focused/integration/architecture/lint/full verification and request a
   fresh independent Gate 5 review at new production and test hashes.

Until these are complete, OpenSpec task 4.3 and Done task 5.1 must remain open.

---

# Independent Gate 5 rereview — correction candidate

Date: 2026-09-02  
Reviewer: fresh separately tasked agent `/root/completion_test_review`  
Verdict: **CHANGES_REQUESTED**

This additive rereview supersedes the first verdict only where findings are
explicitly closed below. The reviewer did not edit production, tests, evidence
or tasks.

## Exact candidate hashes

```text
34e8e941fc71fe9bf2db1b2690378a4cd3ca1fed9c50cb8c012d1c015f1ef071  app/InstallationProcess/InstallationCompletionSchemaMigration.php
a275a7f060612045a75dffa460cfe7836c84fdb976a1acea3b7487bf1692e690  app/InstallationProcess/InstallationCompletionDefinitionSchemaMigration.php
df1c58fa0437fc51868db2e91bcba35793d07957f6359aa36407552e8a17319d  bin/fmonitor2-migrate.php
2f162a78f05d2d6efd70e512211ad3fc06b25900e024adfca67c258b286e1dc8  rapid-pilot/CompletionFlow.php
700393249fd0c0982564ede62e75f090810624b5bb82eeea8330590e3c0dc29f  rapid-pilot/docker-bootstrap.php
98f9dceb0c35c15a90e0c6349854a6299dfe8727dd8249df5133f2b654579e1a  tests/InstallationProcess/installation_completion_schema_001_test.php
8427a39674cf8d4c0e710f164bca14487b88a6fb54815fca699fc5119064618d  tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php
f2edf274cad89b70ebac406004b3e235fa5214ff1994be1d0bce6b0f9105e310  reviews/tests/INSTALLATION-COMPLETION-SCHEMA-001.md
e4f3f0b542e98365371c4342d43de2c1b27eeebbb105bb9c298452d47b9900cc  reviews/code/INSTALLATION-COMPLETION-SCHEMA-001.md (first verdict before this append)
bc1b8edf79d58724d2852de5018f1871aa6640f65f11edc427d9530775480505  docs/operations/installation-completion-schema-green-verification.md
```

## Closure audit

### S1 — closed

The exact fingerprint no longer filters index
`fk_completion_correction_previous`. The approved correction test proves both
the raw CREATE-before-DROP state and a hostile post-normalization reserved index
produce the deterministic sole correction-table conflict with complete zero
mutation. Runtime readiness uses the same unfiltered matcher and therefore
fails closed on both states.

### ST2 — closed

The normalization helper now reads `@@SESSION.FOREIGN_KEY_CHECKS` before the
temporary change and restores that exact value in `finally`. Approved tests pass
for successful `0→0`, successful `1→1`, and denied-ALTER failure `0→0`.

### ST1 — not closed: line-count split still duplicates and compresses schema knowledge

The claimed “honest 32/42-line deep split without duplicated manifests” is
contradicted by the candidate bytes:

```text
32 lines, maximum line length 350:
  InstallationCompletionSchemaMigration.php
42 lines, maximum line length 4037:
  InstallationCompletionDefinitionSchemaMigration.php
```

Definition line 29 contains nearly the entire root and correction DDL plus a
second PHP manifest of the same columns, indexes, FKs and CHECKs in one
4,037-character physical line. Lines 34–41 similarly compress four metadata
queries, transformations and CHECK parsing into a handful of physical lines.
The original duplicated schema knowledge remains; it was moved to another file
and compressed more aggressively to stay below the hotspot threshold.

This is still the first review's Duplicated Code/knowledge and Divergent Change
finding, and it continues to defeat the documented hotspot-ratchet purpose.
Future schema maintenance must update SQL and a separately hand-written manifest
in lockstep, while the formatting makes review and blame needlessly difficult.
The updated green evidence statement “42 lines without duplicated manifests” is
factually incorrect.

The split boundary itself is reasonable: the public orchestrator owns family
state/order/result, while a definition/fingerprint module may own exact schema
mechanics. To close ST1, format both modules normally and remove avoidable
schema duplication—for example, construct DDL from one canonical structured
definition, or otherwise establish one source of truth with readable focused
helpers. If the honest module exceeds the hotspot ceiling, record the justified
new migration-owner baseline through explicit architecture review rather than
encoding thousands of characters per line.

## Reconfirmed Spec/security/history conformance

No new Spec regression was found after S1/ST2 correction. Literal v10 ordering,
prefix/preflight/collation mapping, exact normalized manifests, root-only
preservation, reverse/conflict behavior, correction append-only constraints,
runtime consumer readiness, bootstrap DML-only/fail-closed mapping and redaction
remain GREEN. No correction command, mutable history, premium fact,
authorization expansion, diagnostic disclosure or rapid-pilot domain owner was
introduced. The architecture baseline still contracts obsolete completion DDL
debt and does not whitelist new runtime DDL.

## Independent verification

```text
php tests/InstallationProcess/installation_completion_schema_001_test.php
PASS: INSTALLATION-COMPLETION-SCHEMA-001 migration and chain matrix

php tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php
PASS: INSTALLATION-COMPLETION-SCHEMA-001 DML-only runtime matrix

php tests/InstallationProcess/production_migration_runner_001_test.php
PASS: PRODUCTION-MIGRATION-RUNNER-001 CLI contract

make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

make lint
exit 0

openspec validate canonicalize-installation-completion-schema --strict
Change 'canonicalize-installation-completion-schema' is valid

git diff --check -- <corrected completion artifacts>
exit 0, empty output
```

The mechanical checks cannot detect physical-line threshold gaming; their GREEN
does not close ST1. Gate 5 remains **CHANGES_REQUESTED**. Tests remain approved
at `98f9dceb…`; no Gate 2 return is required unless another test edit is made.
OpenSpec 4.3 and 5.1 must stay open pending an honest readable/de-duplicated
implementation and a fresh independent Gate 5 rereview.

---

# Final independent Gate 5 rereview — structured schema correction

Date: 2026-09-02  
Reviewer: fresh separately tasked agent `/root/completion_test_review`  
Verdict: **APPROVED**

This final additive verdict supersedes the prior `CHANGES_REQUESTED` records for
the exact corrected candidate below. The reviewer did not author or edit
production, tests, evidence or tasks.

## Exact reviewed hashes

```text
09055ed8b3d2521d925ce55001a83b34a3f5f49edbb063b4f11a4b80450e8583  app/InstallationProcess/InstallationCompletionSchemaMigration.php
167c5668cd7db12fc564fd8a73f883d66926300fbd81cd0cd4482ffe9d752674  app/InstallationProcess/InstallationCompletionDefinitionSchemaMigration.php
abf1fa34b8f04f20138daee5671a4133ed160d37209d175ffb666d0f6e7ddd7a  app/InstallationProcess/MariaDbInstallationCompletionSchemaFingerprint.php
df1c58fa0437fc51868db2e91bcba35793d07957f6359aa36407552e8a17319d  bin/fmonitor2-migrate.php
2f162a78f05d2d6efd70e512211ad3fc06b25900e024adfca67c258b286e1dc8  rapid-pilot/CompletionFlow.php
700393249fd0c0982564ede62e75f090810624b5bb82eeea8330590e3c0dc29f  rapid-pilot/docker-bootstrap.php
98f9dceb0c35c15a90e0c6349854a6299dfe8727dd8249df5133f2b654579e1a  tests/InstallationProcess/installation_completion_schema_001_test.php
8427a39674cf8d4c0e710f164bca14487b88a6fb54815fca699fc5119064618d  tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php
f2edf274cad89b70ebac406004b3e235fa5214ff1994be1d0bce6b0f9105e310  reviews/tests/INSTALLATION-COMPLETION-SCHEMA-001.md
6fe97a3763859e17d559538b866f1dafe61d3bd9de38822dca756715f356e76e  reviews/code/INSTALLATION-COMPLETION-SCHEMA-001.md (prior verdict before this append)
ab2464ae02d507f4ef09e3cbc09cfb8bae4270ec184f71641d3ef0f8d0a72893  docs/operations/installation-completion-schema-green-verification.md
```

## Closure of all prior findings

### S1 remains closed

The fingerprint compares the full observed index inventory directly to the
expected inventory. It contains no reserved-name filter. Raw
CREATE-before-DROP and hostile post-normalization
`fk_completion_correction_previous` indexes are therefore incompatible and the
approved tests prove deterministic sole conflict with zero mutation.

### ST2 remains closed

Supporting-index normalization reads the caller's session
`FOREIGN_KEY_CHECKS`, temporarily disables it, and restores the exact incoming
value in `finally`. Approved success and denied-ALTER tests prove `0→0`, `1→1`
and failure `0→0`.

### ST1 is genuinely closed

The implementation now has three readable, coherent modules:

```text
InstallationCompletionSchemaMigration.php
  71 lines, maximum physical line 105 characters
InstallationCompletionDefinitionSchemaMigration.php
  137 lines, maximum physical line 119 characters
MariaDbInstallationCompletionSchemaFingerprint.php
  116 lines, maximum physical line 118 characters
```

`InstallationCompletionDefinitionSchemaMigration::schemas()` is the single
structured source of root/correction facts: columns, types, nullability,
defaults, extra state, ordered indexes, FK identities/columns/targets, and CHECK
expressions. `renderTable()` consumes that descriptor to produce exact DDL.
`manifest()` delegates the same descriptor to the MariaDB fingerprint module,
which derives expected column/index/FK/CHECK metadata. There is no second
hand-written list of schema facts and no 4,000-character compressed line.

Responsibilities are deep and sensible: the public orchestrator owns family
state, dependency order and results; the definition module owns schema
description and DDL rendering; the MariaDB fingerprint module owns metadata
translation/inspection and semantic CHECK normalization. Normal formatting no
longer games the hotspot threshold, and each file is below the ceiling through
cohesion rather than compression.

## Final Spec/security/history audit

No regression was introduced by the refactor:

- the canonical runner retains literal v10 after v1-v9, exact 25/26 prefix
  behavior, database-default utf8mb4 preflight and stable public result mapping;
- whole-family read-only preflight, binary conflicts, root-first creation,
  reverse-partial rejection, exact repeat and populated-root preservation remain;
- rendered tables retain the approved ordered metadata, explicit collation,
  named root/composite FKs, three CHECKs and no final redundant supporting index;
- correction constraints preserve append-only roots/history and reject branch,
  cross-root, gap, malformed version shape and blank reason; no correction
  command or mutable payload semantics were added;
- queue, card, checklist, completion POST and bootstrap still use read-only
  readiness and exact missing/drift fail-closed outcomes before DML/partial HTML;
- exact runtime remains DML-only, bootstrap publishes only after prerequisites,
  and no legacy/sentinel/OTIZ/completion runtime DDL or repair returned;
- rapid-pilot remains wiring/behavioral adapter code, not the new persistence or
  correction-domain owner;
- generic external errors remain redacted; no SQL, table, prefix, credentials,
  exception details or diagnostic instrumentation are exposed;
- architecture baseline continues to ratchet obsolete completion DDL/mutation
  entries downward without a new exemption.

## Independent verification

```text
php tests/InstallationProcess/installation_completion_schema_001_test.php
PASS: INSTALLATION-COMPLETION-SCHEMA-001 migration and chain matrix

php tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php
PASS: INSTALLATION-COMPLETION-SCHEMA-001 DML-only runtime matrix

php tests/InstallationProcess/production_migration_runner_001_test.php
PASS: PRODUCTION-MIGRATION-RUNNER-001 CLI contract

make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

make lint
exit 0

openspec validate canonicalize-installation-completion-schema --strict
Change 'canonicalize-installation-completion-schema' is valid
```

The approved correction test was not changed after its final Gate 3 review.
Representative v10 integration/regression evidence remains valid for the
unchanged runtime/runner/bootstrap hashes. The full verification residual
recorded in green evidence remains separately owned RBAC/login/combined-PDF
integration debt and is not concealed by this approval.

## Final verdict

Gate 5 is **APPROVED** for the exact hashes above. S1, ST1 and ST2 are closed;
no remaining Standards or Spec finding belongs to this slice. OpenSpec task 4.3
may be marked complete. Done task 5.1 still requires the orchestrator's final
status/backlog and completion audit; this review does not mark it automatically.
