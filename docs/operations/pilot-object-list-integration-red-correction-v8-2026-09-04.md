# PILOT-OBJECT-LIST-001 integration RED correction v8

- Date: `2026-09-04`
- Gate: `2` controlling-spec correction discovered during Gate 4
- Test author: separately tasked agent `/root/object_list_integration_red`
- Exact correction baseline: `9f5539ecff3f99f397a99a823be754207d29d72e`
- Production changes: none
- Public seam: raw HTTP `GET|HEAD /pilot/objects`

## Controlling contract

`PILOT-OBJECT-READ-RBAC-FIXTURES-001` sections 0–3 make fictional local user
`18`, its active local role, and exact `objects.read` the sole positive
admission and representation identity. Legacy user/role rows may be decoys and
must not grant authority. The v7 Gate 2 test and its independent v7 review
incorrectly inferred that successful local authorization still required a
second descriptive legacy-user/role lookup. That inference conflicts with the
more specific owner-approved OBJECT-READ contract and is not a product
decision.

The append-only v7 evidence and review remain historical records, but their
legacy-descriptive-fault acceptance claim is invalid and must not authorize
production. The unapproved principal/fault and all assertions that legacy DB
supplies the positive display identity have been removed. Because the test
hash changed, task `2.2` is reopened and the v7 approval cannot be reused.

## Corrected public-seam coverage

The valid list-query fault remains. Its isolated least-privilege principal can
complete exact local actor/role/grant and local identity reads and can read the
approved legacy object facts, but lacks `fm2_installation_cases`. A direct
denied-select probe fixes that boundary. Public GET/HEAD return exact redacted
`503`, `Retry-After: 60`, empty HEAD and no partial/product body while
`polReadOnly` proves snapshot preservation. No legacy identity-table grant or
lookup is required.

Cross-source sensitivity is explicit:

- local user `18` remains `Сотрудник ФКР (тест)` with
  `fkr.object-list@example.invalid` and exact local grant;
- the trusted syntactic `REMOTE_USER` is the differing active legacy decoy
  email `legacy.object-list@example.invalid` whose legacy name is
  `Legacy identity decoy`;
- successful response renders the exact local name and neither legacy decoy
  name nor email;
- removing the trusted local actor key while retaining that legacy email still
  yields the existing exact `401`; legacy identity does not grant admission;
- all missing/inactive/near-match/revoke local actor cases remain unchanged.

The separate local-RBAC unavailable contract still returns correlated `503`
without Retry-After. All v6 header sensitivity and v5 classification,
pagination, query, semantic-list, snapshot, foreign-decoy and cleanup
assertions remain.

## Earliest genuine RED

The corrected list-read `503 + Retry-After: 60` case passes first. Canonical
local identity also passes despite the conflicting legacy decoy. The first
missing behavior returns to the approved query contract:

```text
2026-09-04T17:46:58+03:00
$ php -l tests/InstallationProcess/pilot_object_list_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_list_001_test.php

$ git diff --check -- tests/InstallationProcess/pilot_object_list_001_test.php \
    openspec/changes/pilot-object-read-rbac-fixtures/tasks.md
# exit 0; no output

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/pilot_object_list_001_test.php
PHP Fatal error: Uncaught TestFailure: query is byte-identical and ignored
/pilot/objects?origin=migration
Expected: status 200, Content-Length 3361, canonical three-item body
Actual:   status 200, Content-Length 2439, empty-state body
... tests/InstallationProcess/pilot_object_list_001_test.php(293): assertSameValue()
exit 255
```

Before this intended RED, the run passes helper sensitivity, canonical revoke/
restore, the isolated list-query failure, local-vs-legacy identity decoy,
configured shell/navigation/CSP/script, classification and pagination
negatives, exact facts/order/links, snapshots, `?sort=regnumber`, and
`?origin=demo_fixture`. Attempt-all cleanup leaves no task-owned principal,
schema, server, or artifact.

## Exact input hashes

```text
3f42ee848b3166a3961c7540d32319f608353e9b3749561cf7f2c30549172aa2  specs/PILOT-OBJECT-LIST-001.md
e3858f094c1f5c4411887b7a242122f714ec6d488febca87bd591553f5b05828  specs/PILOT-OBJECT-READ-RBAC-FIXTURES-001.md
07d83894e9be75a3d8276d5701661fdc41f671f312d30ed3f5832892cb063b89  specs/PILOT-HTTP-AUTH-001.md
6c7929a4a599919d25eba2330c097e03450925435b0b50a49026d08cbc1e20f9  tests/Support/PilotObjectReadRbacFixture.php
49cc76cfcc72db0461a19d92326ca98473acec506de8999d4ba23a0c09fda6d1  tests/InstallationProcess/pilot_object_list_001_test.php
71ab6211e1beb90e4af42ddcb6776b5008257d5202aec3e2a8e2bf2d4d0e921d  openspec/changes/pilot-object-read-rbac-fixtures/tasks.md
e63c2d49b43c1305ff3bb0dfff4d6efd9506289b9393c4e60f8efbacfe077058  reviews/tests/PILOT-OBJECT-READ-RBAC-FIXTURES-001-integration-v7.md
478fec2cc83964ac692e642126836c1663d340898595fdd7a2b521579ff598a5  docs/operations/pilot-object-list-integration-red-correction-v7-2026-09-04.md
```
