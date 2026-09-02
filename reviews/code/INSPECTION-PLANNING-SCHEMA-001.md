# Independent code review — INSPECTION-PLANNING-SCHEMA-001

Date: 2026-09-02  
Reviewer: fresh separately tasked agent `/root/completion_test_review`  
Verdict: **CHANGES_REQUESTED**

The reviewer did not author or edit specification, tests, production, evidence
or tasks. The `code-review` process used separate fresh Standards and Spec
agents; their axes are retained separately below. Gate 5 and Done are not
approved.

## Reviewed hashes

```text
464df8d8cdccea4aeb0997d2e397a3d22958f7c8d04a98e556b59d2c055c888c  specs/INSPECTION-PLANNING-SCHEMA-001.md
261797d73a36388ec9c0e5487f72fa015eb7c4a353f2a36b6ddecd1cb95359b9  docs/operations/inspection-planning-schema-owner-approval.md
85723d3e30af78c40d49b399c52863d2fbb656beeadf15cb301ebbde3027c4cb  reviews/tests/INSPECTION-PLANNING-SCHEMA-001-v3.md
128c89a72d29dcb2727ef8d253b98e7b71d33e5657565a95b3eb9f500a2fddbf  docs/operations/inspection-planning-schema-green-verification.md
0722db047306d0429fd092eece4818b209896ae10f4a6574a1f7a752b3b8eec5  docs/operations/installation-completion-v10-integration-fixture-review.md
5082f5764d90fff3dde773ff4e5848c9382d7c5eb6ce3fe09bda4781e9032f72  openspec/changes/canonicalize-inspection-planning-schema/tasks.md

56dbfedd4a883197416c87e10026720684e4ccba1deea705e5a4ca2bb931ac71  app/InstallationProcess/InspectionPlanningSchemaMigration.php
dde376156315bb3c1e27620dd3a9bd54672c83397ab97778bfbf4907d16cdb42  rapid-pilot/InspectionSchedule.php
bd7efb561fc90d3a8a4398a6db2130988e0199321b1a93eacf1459d80ee7e2f6  rapid-pilot/Calendar.php
b3ff4103a265afb61d70cc87772f4fe57598d6b3d8eb29d397ed45a304d9ec79  rapid-pilot/ObjectQueue.php
700393249fd0c0982564ede62e75f090810624b5bb82eeea8330590e3c0dc29f  rapid-pilot/docker-bootstrap.php
df1c58fa0437fc51868db2e91bcba35793d07957f6359aa36407552e8a17319d  bin/fmonitor2-migrate.php
ecb4d052cfff2b4f8c04cbae49d5c081eae452d9ad22d825d80f323498c01b38  tools/architecture/baseline.json

74ada0f93de0fc3f598b1dbfc4ff05243c2c69ead8ea94225baa74d7ce50144a  tests/InstallationProcess/inspection_planning_schema_001_test.php
385673c077d00017c1fe81ee7d362b37712ee9dbb7ec09b301e0c24bfb3d1493  tests/InstallationProcess/inspection_planning_runtime_ddl_001_test.php
730d554b8b7065dd37deaf8bfacb0f3dccc0e015c0becac98dc05bf26101ee90  tests/Support/inspection_planning_runtime_router.php
```

The owner approval records reviewed normative hash `c947d2bd…558dc4`; current
`464df8d8…` is its separately reconfirmed metadata/status version.

## Standards axis

Verdict from fresh Standards reviewer: **CHANGES_REQUESTED**.

### S1 — duplicated schema knowledge and hotspot-threshold evasion

`InspectionPlanningSchemaMigration.php` is exactly 149 lines, immediately below
the repository's 150-line hotspot threshold. It reaches that count through
compressed multi-statement lines (maximum physical line 568 characters) while
combining family orchestration, two DDL definitions, two independent PHP
fingerprint manifests, four information-schema inspections and CHECK parsing.

The schedules/events schema is encoded twice: literal CREATE statements in
`definitions()` and separately hand-written columns/indexes/JSON-check metadata
beside them. A future edit can change what deployment creates without changing
what repeat/readiness accepts, or vice versa. This is actionable Duplicated
Code/knowledge and Divergent Change; normally formatting the current module
would immediately cross the ratchet.

The mechanical architecture check passes, but that does not satisfy the
maintainability purpose of the hotspot policy. Use one readable structured
planning descriptor as the source of columns, types, collation overrides,
indexes and CHECKs; derive both exact DDL and expected fingerprint metadata from
it. Split family orchestration, definition/rendering and MariaDB fingerprint
inspection into coherent modules if needed. If an honest cohesive module
exceeds the ceiling, obtain explicit architecture review/baseline rather than
compressing physical lines.

### Scope isolation and security observation

The shared `baseline.json`/`check.py` diff also contains separately owned
InspectionEvidence/IdentityAccess public-seam and SQL-ownership changes. They
are excluded from this planning verdict, not silently approved here. The
planning-owned baseline delta is correctly subtractive: two planning DDL and
two rapid-mutation fingerprints are removed; one surviving SELECT fingerprint
changes with the readiness call.

`InspectionSchedule` still logs raw exception messages for general command
failures. The new planning-readiness failure itself is a stable non-secret
literal, and this logging predates/extends beyond schema ownership, so it is a
non-blocking security observation in this scoped review. Future route-hardening
should prefer safe category plus opaque correlation rather than DB exception
messages that might contain table/prefix/SQL details.

Positive Standards findings: planning DDL now exists only in the canonical
migration; all runtime paths share one read-only readiness seam; no baseline
exemption, new rapid-pilot domain logic or application-to-HTTP dependency was
introduced.

## Spec axis

Verdict from fresh Spec reviewer: **APPROVED / PASS**, no blocking finding.

- Literal v9 follows v1-v8 in the public runner; public prefix validation keeps
  exact ASCII 0..25 and rejects 26/non-ASCII/invalid input before DB access,
  while direct-family 28-byte arithmetic remains internal only.
- Both exact manifests match approved ordered columns/default/generated/
  character metadata, named indexes/order/type/visibility, InnoDB and explicit
  validated database-default collation. Event payload remains longtext
  `utf8mb4_bin` with exactly one normalized `json_valid(payload_json)` CHECK;
  FK/extra/missing/changed/duplicate constraints conflict.
- Family-wide metadata inspection precedes DDL. Conflicts are binary sorted and
  zero-mutation; creation order is schedules then events; both one-member
  partials, populated rows/Unicode JSON/ids/AUTO_INCREMENT, exact repeat and
  decoy isolation are preserved.
- Scheduling POST, Calendar, ObjectQueue, construction-control and bootstrap
  invoke the same read-only readiness seam. Healthy DML-only behavior remains;
  missing/drift produce exact approved responses before planning DML, partial
  HTML or repair.
- Bootstrap performs planning/completion/legacy readiness before fixture/import
  and other product DML, maps failure to opaque exit 70, and publishes no ready
  manifest or secret/schema diagnostic first.
- Schedule INSERT IGNORE, event payload, duplicate behavior, admission,
  authorization and projections are unchanged. No cadence, reschedule/cancel,
  assignment race or visibility semantics were promoted from GRILL-001 into
  requirements.

## Independent verification

```text
php tests/InstallationProcess/inspection_planning_schema_001_test.php
PASS: INSPECTION-PLANNING-SCHEMA-001 migration matrix

php tests/InstallationProcess/inspection_planning_runtime_ddl_001_test.php
PASS: INSPECTION-PLANNING-SCHEMA-001 real HTTP/Compose DML-only contract

php tests/InstallationProcess/production_migration_runner_001_test.php
PASS: PRODUCTION-MIGRATION-RUNNER-001 CLI contract

php rapid-pilot/verify-calendar-projections.php
PASS calendar bounded projections, deterministic DOM and fail-closed overflow

php tests/Verification/characterize_inspection_schedule_duplicate_001_test.php
CHARACTERIZATION_OK CHARACTERIZE-INSPECTION-SCHEDULE-DUPLICATE-001

make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

make lint
exit 0
```

The v10 integration review independently confirms planning direct/runtime
coverage remains intact after terminal v10 fixture alignment. Recorded full
verification residual is separately owned RBAC/login/combined-PDF debt and does
not hide a planning regression, but it cannot override Standards S1.

## Required next actions

1. Replace duplicated DDL/manifest knowledge with one readable structured
   planning definition and coherent renderer/fingerprint modules.
2. Rerun unchanged approved schema/runtime tests, runner, characterizations,
   architecture/lint and integration verification.
3. Request a fresh independent Gate 5 rereview at exact new production hashes.

No test change is required by this finding; the approved Gate 3 test hashes stay
valid unless expectations/setup are edited. OpenSpec tasks 4.2 and 4.3 must
remain open until the Standards defect is corrected and Gate 5 is approved.

---

# Final independent Gate 5 rereview — structured planning schema

Date: 2026-09-02  
Reviewer: fresh separately tasked agent `/root/completion_test_review`  
Verdict: **APPROVED**

This final additive verdict supersedes the prior `CHANGES_REQUESTED` for the
exact corrected candidate below. The reviewer did not author or edit production,
tests, evidence or tasks.

## Exact reviewed hashes

```text
9a4723ecda2964f13ec49c329ad9057aa9ea8d3f924d459fd28e8d4f85bdf01a  app/InstallationProcess/InspectionPlanningSchemaMigration.php
280f9d49526a5d972c131c30263eea19930c8fd70ea5146d388b982b8604bea1  app/InstallationProcess/InspectionPlanningDefinitionSchemaMigration.php
7970c5d31083f62e8c83b1d2d7b6098e17a3897f36bc6868d42219a7f163803c  app/InstallationProcess/MariaDbInspectionPlanningSchemaFingerprint.php
dde376156315bb3c1e27620dd3a9bd54672c83397ab97778bfbf4907d16cdb42  rapid-pilot/InspectionSchedule.php
bd7efb561fc90d3a8a4398a6db2130988e0199321b1a93eacf1459d80ee7e2f6  rapid-pilot/Calendar.php
b3ff4103a265afb61d70cc87772f4fe57598d6b3d8eb29d397ed45a304d9ec79  rapid-pilot/ObjectQueue.php
700393249fd0c0982564ede62e75f090810624b5bb82eeea8330590e3c0dc29f  rapid-pilot/docker-bootstrap.php
df1c58fa0437fc51868db2e91bcba35793d07957f6359aa36407552e8a17319d  bin/fmonitor2-migrate.php
74ada0f93de0fc3f598b1dbfc4ff05243c2c69ead8ea94225baa74d7ce50144a  tests/InstallationProcess/inspection_planning_schema_001_test.php
385673c077d00017c1fe81ee7d362b37712ee9dbb7ec09b301e0c24bfb3d1493  tests/InstallationProcess/inspection_planning_runtime_ddl_001_test.php
730d554b8b7065dd37deaf8bfacb0f3dccc0e015c0becac98dc05bf26101ee90  tests/Support/inspection_planning_runtime_router.php
73af8890879a382d41da308c71fb8d1cfae8860b4a29d9987e1dd25f7e2bba59  reviews/code/INSPECTION-PLANNING-SCHEMA-001.md (prior verdict before this append)
128c89a72d29dcb2727ef8d253b98e7b71d33e5657565a95b3eb9f500a2fddbf  docs/operations/inspection-planning-schema-green-verification.md
```

The approved Gate 3 tests are unchanged; no return to Gate 2 occurred.

## Standards finding closure

ST1 is genuinely closed. The planning implementation now has three coherent,
normally formatted modules:

```text
InspectionPlanningSchemaMigration.php
  71 lines, maximum physical line 101 characters
InspectionPlanningDefinitionSchemaMigration.php
  80 lines, maximum physical line 127 characters
MariaDbInspectionPlanningSchemaFingerprint.php
  102 lines, maximum physical line 111 characters
```

`InspectionPlanningDefinitionSchemaMigration` contains one structured source
for the two families: ordered columns, types, auto-increment state, payload
collation override, index names/uniqueness/columns and semantic CHECK
expressions. Its renderer derives exact CREATE DDL from that descriptor. The
MariaDB fingerprint adapter derives expected column/index/CHECK metadata from
the same descriptor and separately compares observed `information_schema`
rows. There is no second handwritten schema manifest and no compressed
near-threshold physical line.

Responsibilities and dependencies are sound: the public v9 orchestrator owns
prefix validation, whole-family inspection, dependency order and public result;
the definition module owns schema description/rendering; the MariaDB adapter
owns metadata translation, exact comparison and CHECK normalization. All remain
inside InstallationProcess and depend on neither HTTP nor rapid-pilot.

The prior scope note remains: unrelated IdentityAccess/InspectionEvidence
architecture changes in the shared dirty diff are not approved by this record.
Planning-owned baseline contraction remains correct. The pre-existing raw
exception-log observation is not expanded by the refactor and remains outside
this behavior-neutral schema ownership slice.

## Final Spec/security/history audit

No behavior changed during the refactor:

- literal v9 ordering and public 25/26 prefix boundary remain exact;
- validated database-default utf8mb4 collation is emitted for both InnoDB
  tables; payload remains exact `LONGTEXT utf8mb4_bin`;
- schedules/events ordered metadata and named indexes derive correctly;
  event schema has exactly one normalized `json_valid(payload_json)` CHECK and
  any FK/extra/missing/changed/duplicate constraint remains incompatible;
- whole-family preflight precedes DDL, conflicts are binary sorted and
  zero-mutation, schedules create before events, and both exact one-member
  partials remain restartable without row/counter changes;
- populated repeat, Unicode JSON, decoy isolation and AUTO_INCREMENT
  preservation remain covered by unchanged approved tests;
- scheduling POST, Calendar, queue, construction-control and bootstrap still
  use the same read-only readiness seam, with exact healthy and missing/drift
  outcomes before DML, partial HTML, repair or ready publication;
- bootstrap remains opaque/redacted and performs prerequisite checks before
  fixture/import/product mutations;
- no cadence, reschedule/cancel, assignment race, visibility, authorization or
  new scheduling semantics were added; existing INSERT IGNORE/event/projection
  behavior and append-only event history are unchanged;
- no runtime planning DDL, new rapid-pilot domain ownership, schema/credential
  disclosure or architecture exemption was introduced.

## Independent verification

```text
php tests/InstallationProcess/inspection_planning_schema_001_test.php
PASS: INSPECTION-PLANNING-SCHEMA-001 migration matrix

php tests/InstallationProcess/inspection_planning_runtime_ddl_001_test.php
PASS: INSPECTION-PLANNING-SCHEMA-001 real HTTP/Compose DML-only contract

php tests/InstallationProcess/production_migration_runner_001_test.php
PASS: PRODUCTION-MIGRATION-RUNNER-001 CLI contract

php rapid-pilot/verify-calendar-projections.php
PASS calendar bounded projections, deterministic DOM and fail-closed overflow

php tests/Verification/characterize_inspection_schedule_duplicate_001_test.php
CHARACTERIZATION_OK CHARACTERIZE-INSPECTION-SCHEDULE-DUPLICATE-001

make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

make lint
exit 0
```

## Final verdict

Gate 5 is **APPROVED** for the exact hashes above. Standards and Spec axes have
no remaining planning-owned finding. OpenSpec task 4.2 may be marked complete;
task 4.3 still requires the orchestrator's final task/status audit and is not
automatically completed by this review.
