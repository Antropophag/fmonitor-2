# PILOT-OBJECT-READ-RBAC-FIXTURES-001 — independent integration Gate 3 v7

- Date: `2026-09-04T17:40:20+03:00`
- Reviewer: separately tasked agent `/root/object_list_integration_gate3`
- Test author: separately tasked agent `/root/object_list_integration_red`; reviewer authored none of the specification, test, fixture, production, RED evidence, or prior review
- Reviewed exact HEAD: `a26710528338c91df56f4588894dbf6521b29d22`
- Correction baseline: `1719e3ee57ad1fdb22160bc4da075323f7b8406d`
- Public seam: raw HTTP `GET|HEAD /pilot/objects`
- Verdict: **APPROVED**

This fresh approval applies only to the exact test/fixture and current
integration composition. It authorizes minimal Gate 4 GREEN and is not Gate 5,
repository-wide GREEN, CI readiness, or release approval. The reviewer changed
no production or test byte.

## Fresh reproduction

```text
$ date --iso-8601=seconds
2026-09-04T17:40:20+03:00

$ php -l tests/InstallationProcess/pilot_object_list_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_list_001_test.php

$ git diff --check \
    1719e3ee57ad1fdb22160bc4da075323f7b8406d..a26710528338c91df56f4588894dbf6521b29d22
# exit 0; no output

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/pilot_object_list_001_test.php
PHP Fatal error: Uncaught TestFailure: legacy descriptive identity unavailable
after local authority status
Expected: 503
Actual: 200
... tests/InstallationProcess/pilot_object_list_001_test.php(256): polError()
exit 255
```

The list-read fault runs first and passes exact GET/HEAD `503` with
`Retry-After: 60`. The next legacy-identity fault is the earliest missing
behavior: current production returns `200` after local authorization even
though inherited descriptive identity tables are unavailable. The run reaches
that public assertion only after direct grant/deny probes and leaves no
task-owned resources through `finally` cleanup.

## Findings

All v6 blockers are closed for the reviewed bytes.

1. The list-fault principal has SELECT on all four canonical local-RBAC tables,
   the exact legacy user/role columns and approved legacy object columns. Direct
   queries prove actor 18 local authority, active descriptive legacy identity
   and one valid object fact. A direct select from `fm2_installation_cases` is
   denied. Therefore its successful public `503 + Retry-After: 60` reaches the
   list read and cannot be an earlier authority or identity failure.
2. The legacy-fault principal has the four local-RBAC tables and all process
   case/legacy object columns needed for the complete valid list. Direct probes
   prove actor 18 exact local authority, three cases and object 4513. Selects
   from both `legacy_users` and `legacy_users_roles` are independently denied.
   Its public GET/HEAD unexpectedly return `200`, establishing a genuine RED
   for the inherited descriptive active-user/role seam.
3. `PILOT-OBJECT-READ-RBAC-FIXTURES-001` makes local ID/role/exact
   `objects.read` the sole admission authority and explicitly says legacy
   rows do not grant authority. It also inherits the existing object-list
   contract unchanged after successful admission. `PILOT-OBJECT-LIST-001`
   retains the active legacy user/role lookup and its failure ordering. The new
   test preserves this distinction: local grant admits, while legacy identity
   supplies a required descriptive/active read and never a fallback grant.
4. Both public faults use `polParity()` inside `polReadOnly()`: GET/HEAD status
   and application headers agree, HEAD is empty, exact redacted body/header is
   checked, and the complete DB/schema/AUTO_INCREMENT plus filesystem snapshot
   remains unchanged.
5. Both principals are randomized, receive only explicit column/table grants,
   have direct positive and negative boundary probes, and are closed/dropped in
   the attempt-all inventory. Final absence covers all four randomized users;
   the task DB/root and foreign-decoy invariants remain intact.
6. The separate local-RBAC schema/read failures retain no Retry header, exact
   correlation and logging. CSS/user/list/integrity/ceiling retry mappings,
   denials, helper inverse sensitivity and every v5 classification/query/
   pagination/semantic-list assertion remain unchanged.
7. The later origin-query RED is still present verbatim after the two new fault
   tracers, alongside non-origin and all origin variants. It is unreachable in
   this run because the newly covered legacy-identity predecessor fails first;
   the record correctly does not misstate it as freshly reproduced.

Expected grants, actor IDs, rows, statuses and headers come from the approved
contracts rather than production output. No weakening, scope expansion, or
test-owned implementation behavior was found.

## Gate decision

**APPROVED.** Minimal Gate 4 may restore the required descriptive identity
failure behavior and then continue to the already-reviewed origin/list-ceiling
correction without changing this test. Any test, fixture, specification or
integration-composition byte change requires another fresh independent Gate 3.

## Exact reviewed-input hashes

```text
3f42ee848b3166a3961c7540d32319f608353e9b3749561cf7f2c30549172aa2  specs/PILOT-OBJECT-LIST-001.md
07d83894e9be75a3d8276d5701661fdc41f671f312d30ed3f5832892cb063b89  specs/PILOT-HTTP-AUTH-001.md
e3858f094c1f5c4411887b7a242122f714ec6d488febca87bd591553f5b05828  specs/PILOT-OBJECT-READ-RBAC-FIXTURES-001.md
a534ca9cf726c01c1dbd0d3faeb3c4560197c23d6f2fc1b654afe49741c6e4cc  openspec/changes/pilot-object-read-rbac-fixtures/proposal.md
bd2cb1b9e48e2b8d8959d88d67b4591297d447f94856179ebaf8a1f18a7e891a  openspec/changes/pilot-object-read-rbac-fixtures/design.md
3128529b18a6226a6f66ebce2159bdf48ffb194f396869132cab179df99aabc2  openspec/changes/pilot-object-read-rbac-fixtures/specs/verification/pilot-object-read-rbac-fixtures/spec.md
71ab6211e1beb90e4af42ddcb6776b5008257d5202aec3e2a8e2bf2d4d0e921d  openspec/changes/pilot-object-read-rbac-fixtures/tasks.md
f459e96cfb359b75dc0c4ad48323f038f60b631aa1b20da171d23a83f533341b  tests/InstallationProcess/pilot_object_list_001_test.php
6c7929a4a599919d25eba2330c097e03450925435b0b50a49026d08cbc1e20f9  tests/Support/PilotObjectReadRbacFixture.php
478fec2cc83964ac692e642126836c1663d340898595fdd7a2b521579ff598a5  docs/operations/pilot-object-list-integration-red-correction-v7-2026-09-04.md
c8dd7a367e258de9aff1287de07e398c0f3058ec32d596ae71d30e4eca00d53c  reviews/tests/PILOT-OBJECT-READ-RBAC-FIXTURES-001-integration-v6.md

METADATA  reviews/tests/PILOT-OBJECT-READ-RBAC-FIXTURES-001-integration-v7.md
```
