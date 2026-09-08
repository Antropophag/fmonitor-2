# PILOT-OBJECT-LIST-001 integration RED correction v6

- Date: `2026-09-04`
- Gate: `2` correction discovered during Gate 4 preflight
- Test author: separately tasked agent `/root/object_list_integration_red`
- Exact correction baseline: `14ffbb7f06e740a183111e8a6b88d3f9b05059d1`
- Prior Gate 3 approval: `reviews/tests/PILOT-OBJECT-READ-RBAC-FIXTURES-001-integration-v5.md`
- Production changes: none
- Public seam: raw HTTP `GET|HEAD /pilot/objects`

## Retry-After contract correction

The common `polError` helper previously required every error to omit
`Retry-After`. That is correct for route/method/identity/authorization denials
and for the separate local-RBAC unavailable contract, but contradicts
`PILOT-OBJECT-LIST-001` section 8 and inherited `PILOT-HTTP-AUTH-001` for CSS,
configuration, legacy-user lookup, list-read/integrity, and ceiling failures.
Those object-list infrastructure outcomes require exact `Retry-After: 60`.

`polError` now receives an explicit expected Retry value, defaulting to absent.
Every existing error call was audited against its controlling contract:

| Outcome exercised | Expected Retry-After |
|---|---|
| invalid route, method, missing identity | absent |
| inactive/missing/near-match local actor or revoke denial | absent |
| local-RBAC schema/read unavailable with correlation | absent |
| configured CSS unavailable | `60` |
| dangling case / invalid approved legacy value | `60` |
| 501-case ceiling overflow | `60` |

Two positive helper controls accept respectively an absent header and exact
`60`. Two inverse negative controls prove sensitivity: unexpected `60` in a
no-retry outcome and missing `60` in a required-retry outcome each must throw a
`TestFailure` specifically at the Retry assertion. This prevents a permissive
helper while leaving the independently specified local-RBAC correlation/header
manifest unchanged.

The changed test hash invalidates the prior Gate 3 approval, so OpenSpec task
`2.2` is reopened. All v5 classification, query, pagination, semantic list,
RBAC, snapshot, foreign-decoy, error-redaction and cleanup assertions remain.

## Genuine RED

```text
2026-09-04T17:31:43+03:00
$ php -l tests/InstallationProcess/pilot_object_list_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_list_001_test.php

$ git diff --check -- tests/InstallationProcess/pilot_object_list_001_test.php
# exit 0; no output

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/pilot_object_list_001_test.php
PHP Fatal error: Uncaught TestFailure: query is byte-identical and ignored
/pilot/objects?origin=migration
Expected: status 200, Content-Length 3361, canonical three-item body
Actual:   status 200, Content-Length 2439, empty-state body
... tests/InstallationProcess/pilot_object_list_001_test.php(277): assertSameValue()
exit 255
```

Both Retry-After positive controls and both mismatch-sensitivity controls pass
before the real fixture. The unchanged production then reaches the same
intended origin-query RED after canonical RBAC, UI-shell, exact list,
classification/pagination negatives, snapshots, `?sort=regnumber`, and
`?origin=demo_fixture`. Later corrected CSS/integrity/ceiling assertions are
unreachable until the origin behavior receives minimal GREEN; their expected
headers are nevertheless independently exercised by the helper controls.
Attempt-all cleanup left no task-owned residue.

## Exact input hashes

```text
3f42ee848b3166a3961c7540d32319f608353e9b3749561cf7f2c30549172aa2  specs/PILOT-OBJECT-LIST-001.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
e3858f094c1f5c4411887b7a242122f714ec6d488febca87bd591553f5b05828  specs/PILOT-OBJECT-READ-RBAC-FIXTURES-001.md
6c7929a4a599919d25eba2330c097e03450925435b0b50a49026d08cbc1e20f9  tests/Support/PilotObjectReadRbacFixture.php
781ef51e6c6626d3dc94fbcbce938c74c2118b71e0ccfbd022736493c544b546  tests/InstallationProcess/pilot_object_list_001_test.php
71ab6211e1beb90e4af42ddcb6776b5008257d5202aec3e2a8e2bf2d4d0e921d  openspec/changes/pilot-object-read-rbac-fixtures/tasks.md
d5233f4a7f43e71cfd5eff0ce16fd3b49eff1d27471034f892021e8efcb023ac  reviews/tests/PILOT-OBJECT-READ-RBAC-FIXTURES-001-integration-v5.md
```
