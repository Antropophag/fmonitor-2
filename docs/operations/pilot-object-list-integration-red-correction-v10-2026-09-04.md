# PILOT-OBJECT-LIST-001 integration RED correction v10

- Date: `2026-09-04`
- Gate: `2` controlling local-only admission correction
- Test author: separately tasked agent `/root/object_list_integration_red`
- Exact correction baseline: `a00ca6b157cd2033646bc22fc49106396ae1fee3`
- Production changes: none
- Public seam: raw HTTP `GET|HEAD /pilot/objects`

## Controlling admission contract

`PILOT-OBJECT-READ-RBAC-FIXTURES-001` lines 9–12 and 38–46 require the
positive object-list fixture to enter through exact local actor ID `18`, active
local role, and exact `objects.read`. Its positive process environment must not
inherit or leak `REMOTE_USER`. The independently approved
`local_rbac_objects_route_admission_001_test.php` also requires a granted local
actor to succeed without `REMOTE_USER`.

V8/v9 evidence and reviews correctly rejected legacy authority, but incorrectly
retained `REMOTE_USER` syntax as a required positive precursor. That claim is
superseded by the controlling local-only contract and must not authorize
production. The append-only records remain history and are not rewritten.

## Test correction

- Canonical positive, list-fault, combined DB/CSS-fault, CSS-fault and all
  other local-actor environments omit `REMOTE_USER` entirely.
- Canonical GET/HEAD explicitly assert the key is absent, return `200`, render
  the exact local name, and exclude legacy identity decoys.
- A second no-REMOTE canonical GET must be byte-equivalent to the first after
  removing only server-controlled Date/Connection headers.
- An isolated server adds differing legacy decoy
  `REMOTE_USER=legacy.object-list@example.invalid` alongside local actor `18`;
  its response must remain byte-equivalent, proving it cannot replace local
  identity or grant.
- The local-key-absent negative case retains that valid active legacy decoy and
  requires exact `401` before list access. Missing/invalid/inactive local actor,
  near-match grants and revoke cases remain.
- The superseded assertion that missing `REMOTE_USER` denies a valid local
  actor is removed. Route/method still precede local authorization; local
  authorization still precedes CSS and list reads.

The changed hash reopens OpenSpec task `2.2`. All v9 list/CSS/combined faults,
retry/correlation behavior, origin query, pagination, classification, exact
facts/order, snapshots, foreign decoys and attempt-all cleanup remain.

## Predecessor and RED execution

```text
2026-09-04T18:01:01+03:00
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 real GET /pilot/objects admission

2026-09-04T18:01:02+03:00
$ php -l tests/InstallationProcess/pilot_object_list_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_list_001_test.php

$ git diff --check -- tests/InstallationProcess/pilot_object_list_001_test.php \
    openspec/changes/pilot-object-read-rbac-fixtures/tasks.md
# exit 0; no output

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/pilot_object_list_001_test.php
PHP Fatal error: Uncaught TestFailure: CSS unavailable after healthy local authorization and before list read status
Expected: 503
Actual: 200
... tests/InstallationProcess/pilot_object_list_001_test.php(297): polError()
exit 255
```

The object-list run passes helper sensitivity, canonical revoke/restore,
isolated list-read `503+60`, combined DB/CSS local-auth precedence, canonical
no-REMOTE GET/HEAD, no-REMOTE repeat, and the differing REMOTE decoy parity
before reaching the same honest CSS-predecessor RED. Thus local-only admission
is executable, not commentary.

The origin-query RED remains later and unreachable until CSS validation is
restored; prior append-only evidence remains valid for its earlier execution.
Attempt-all cleanup left no task-owned schema, principal, server, or artifact.

## Exact input hashes

```text
3f42ee848b3166a3961c7540d32319f608353e9b3749561cf7f2c30549172aa2  specs/PILOT-OBJECT-LIST-001.md
e3858f094c1f5c4411887b7a242122f714ec6d488febca87bd591553f5b05828  specs/PILOT-OBJECT-READ-RBAC-FIXTURES-001.md
07d83894e9be75a3d8276d5701661fdc41f671f312d30ed3f5832892cb063b89  specs/PILOT-HTTP-AUTH-001.md
6c7929a4a599919d25eba2330c097e03450925435b0b50a49026d08cbc1e20f9  tests/Support/PilotObjectReadRbacFixture.php
19fcba6f14c73e36b69375737a730f937b1cd7c150e2c7d9fddd567c4ece0b36  tests/InstallationProcess/pilot_object_list_001_test.php
71ab6211e1beb90e4af42ddcb6776b5008257d5202aec3e2a8e2bf2d4d0e921d  openspec/changes/pilot-object-read-rbac-fixtures/tasks.md
f551df2b26eaef6b46482dd77d008c9fb6c90ab08553743640bf654aefef426a  reviews/tests/PILOT-OBJECT-READ-RBAC-FIXTURES-001-integration-v8.md
c3394b2952418c3028b61c68de7812cfd36d03e8d4d043b94c78da65fbd6c477  reviews/tests/PILOT-OBJECT-READ-RBAC-FIXTURES-001-integration-v9.md
ceb60883f6ad4cdb8344012e04b5aa9c057210d54582417e27cb82474d8da82b  docs/operations/pilot-object-list-integration-red-correction-v8-2026-09-04.md
e4034cb56e9080f7ce23126cef27f02e2c27c113a5c44d7d23b6445eb35e4720  docs/operations/pilot-object-list-integration-red-correction-v9-2026-09-04.md
```
