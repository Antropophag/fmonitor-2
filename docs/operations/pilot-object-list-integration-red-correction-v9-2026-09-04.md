# PILOT-OBJECT-LIST-001 integration RED correction v9

- Date: `2026-09-04`
- Gate: `2` successor-precedence correction discovered during Gate 4
- Test author: separately tasked agent `/root/object_list_integration_red`
- Exact correction baseline: `b507e05225402a9f9a095bdf17cf632671c9e74a`
- Prior Gate 3 approval: `reviews/tests/PILOT-OBJECT-READ-RBAC-FIXTURES-001-integration-v8.md`
- Production changes: none
- Public seam: raw HTTP `GET|HEAD /pilot/objects`

## Corrected precedence

After the owner-approved local-RBAC successor, matched object-list requests are
ordered as route/method, trusted transport identity, local authorization,
configured CSS, then list read. The prior combined broken-CSS/broken-DB oracle
incorrectly expected the inherited CSS `503 + Retry-After: 60`. With the DB
unavailable, local authorization cannot complete and controls the result first:

```text
503 Service unavailable.
no Retry-After
X-Correlation-ID: exact 12 lowercase hex
one AUTHORIZATION_READ_FAILED log with the same correlation ID
```

The combined fault now runs through `polUnavailable`, including the exact
header manifest, safe category/log checks, full snapshot, and absence of
handler/product output. Two isolated cases remain distinct:

- healthy local RBAC plus missing configured CSS returns exact redacted
  GET/HEAD `503` with `Retry-After: 60` before list read;
- wrong DB with otherwise valid configuration returns the same correlated
  local-authorization unavailable result without Retry-After.

The combined missing `REMOTE_USER` case remains exact `401` before both broken
DB and CSS. Route and method still precede it. Comments and assertion messages
now describe this successor order rather than the obsolete legacy-auth order.

The test hash changed, so OpenSpec task `2.2` is reopened and the prior approval
cannot be reused. All v8 local identity/legacy decoy, list-read fault, query,
classification, pagination, semantic-list, RBAC, snapshot, foreign-decoy and
attempt-all cleanup assertions remain.

## Genuine RED

```text
2026-09-04T17:54:07+03:00
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
... tests/InstallationProcess/pilot_object_list_001_test.php(255): polError()
exit 255
```

The expected origin-query RED did not remain earliest under actual execution.
The run first passes existing helper sensitivity, canonical revoke/restore,
the isolated list-read fault, and the new combined DB/CSS precedence assertion:
broken DB wins as `AUTHORIZATION_READ_FAILED`, with correlation and no Retry.
It then exposes that the healthy-local-RBAC object-list branch does not validate
the configured CSS descriptor at all and returns product `200` for a missing
file. This is a genuine inherited CSS-predecessor RED, not setup failure.

The unchanged origin-query assertion remains later and is now unreachable. Its
prior v8 RED is retained as append-only evidence but is not claimed as
reproduced in this run. Gate 4 must first restore CSS validation after local
authorization, then continue to the already known origin-driven selection RED
without changing these expectations.

Attempt-all cleanup left no task-owned object-list schema, principal, server,
or artifact.

## Exact input hashes

```text
3f42ee848b3166a3961c7540d32319f608353e9b3749561cf7f2c30549172aa2  specs/PILOT-OBJECT-LIST-001.md
e3858f094c1f5c4411887b7a242122f714ec6d488febca87bd591553f5b05828  specs/PILOT-OBJECT-READ-RBAC-FIXTURES-001.md
07d83894e9be75a3d8276d5701661fdc41f671f312d30ed3f5832892cb063b89  specs/PILOT-HTTP-AUTH-001.md
6c7929a4a599919d25eba2330c097e03450925435b0b50a49026d08cbc1e20f9  tests/Support/PilotObjectReadRbacFixture.php
1335fb641a6e2e1ec23d8158405c8a6eee9bb9eb015f95a6ce923e3c30dd4760  tests/InstallationProcess/pilot_object_list_001_test.php
71ab6211e1beb90e4af42ddcb6776b5008257d5202aec3e2a8e2bf2d4d0e921d  openspec/changes/pilot-object-read-rbac-fixtures/tasks.md
f551df2b26eaef6b46482dd77d008c9fb6c90ab08553743640bf654aefef426a  reviews/tests/PILOT-OBJECT-READ-RBAC-FIXTURES-001-integration-v8.md
```
