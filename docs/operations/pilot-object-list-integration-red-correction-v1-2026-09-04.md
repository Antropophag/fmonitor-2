# PILOT-OBJECT-LIST-001 integration RED correction v1

- Date: `2026-09-04`
- Gate: `2` replacement after integration-composition drift
- Test author: separately tasked agent `/root/object_list_integration_red`
- Exact baseline HEAD: `dc91b50badba0959df1c6ab7fc5c6fcac5484625`
- Production changes: none
- Public seam: raw HTTP `GET|HEAD /pilot/objects`

## Correction

The object-list verifier is restored to the owner-approved
`PILOT-OBJECT-LIST-001 v0.1` contract. Later classification work had inserted
positive assertions for an origin filter and `data-origin` rows, while later
pagination work had replaced the approved 500/501 fail-closed boundary with a
50-row pagination contract. Neither behavior belongs to the approved list
slice: the specification explicitly says that query does not participate in
routing, selection, ordering or rendering, and that filtering, URL state and
pagination are out of scope.

The corrected test now requires:

- no origin-filter navigation/form/control, no `origin=` URL, no origin or
  process-classification data attribute, and no migration/demo classification
  claim in the configured queue;
- byte-equivalent status, stable headers and body for canonical GET and each of
  `?origin=demo_fixture`, `?origin=migration`, and `?origin=arbitrary`, excluding
  only HTTP `Date` and `Connection`;
- exactly 500 imported cases as one complete successful representation, and
  501 imported cases as redacted `503` with `Retry-After: 60`, never a partial
  page.

The exact object facts/order, semantic item-to-card links, non-imported decoy,
navigation removal, local-RBAC grant/revoke and negative matrix, authorization
failure priority, HEAD parity, snapshots, foreign decoys, attempt-all cleanup
and infrastructure failures remain asserted. The current approved UI-shell
composition is also retained: the configured queue has the sole source-only
`/pilot/assets/navigation.js` script and exact scripted CSP. An initial run
reached the obsolete predecessor assertion forbidding every script; that
assertion was aligned before the RED below and is not claimed as product RED.

OpenSpec task `2.2` remains reopened (`[ ]`) because the test hash changed. No
prior Gate 3 approval applies to these bytes.

## Genuine RED

At the exact baseline, canonical GET and `?origin=demo_fixture` are equivalent.
The next query proves that current production still uses the forbidden query as
a selection input:

```text
2026-09-04T17:07:22+03:00
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/pilot_object_list_001_test.php
PHP Fatal error: Uncaught TestFailure: query is byte-identical and ignored /pilot/objects?origin=migration
Expected: status 200, Content-Length 3361, canonical three-item body
Actual:   status 200, Content-Length 2439, empty-state body
... tests/InstallationProcess/pilot_object_list_001_test.php(238): assertSameValue()
exit 255
```

Before that intended failure the request passed real-server startup, canonical
local RBAC revoke/restore sensitivity, authenticated `200`, GET/HEAD parity,
configured CSS/shared shell, navigation-removal predecessor, current scripted
UI-shell manifest/CSP, negative origin/classification DOM assertions, exact
three-item facts/order/links, non-imported decoy exclusion and read-only
snapshots. Cleanup removed the randomized schemas, DB principals and task-owned
files; no matching residue was found under `.test-artifacts`.

This is a public-seam behavior RED, not setup failure: changing only the query
changes the selected representation. Gate 4 is forbidden until a fresh,
separately tasked Gate 3 reviewer approves the exact corrected test hash.

## Exact input hashes

```text
3f42ee848b3166a3961c7540d32319f608353e9b3749561cf7f2c30549172aa2  specs/PILOT-OBJECT-LIST-001.md
e3858f094c1f5c4411887b7a242122f714ec6d488febca87bd591553f5b05828  specs/PILOT-OBJECT-READ-RBAC-FIXTURES-001.md
a534ca9cf726c01c1dbd0d3faeb3c4560197c23d6f2fc1b654afe49741c6e4cc  openspec/changes/pilot-object-read-rbac-fixtures/proposal.md
bd2cb1b9e48e2b8d8959d88d67b4591297d447f94856179ebaf8a1f18a7e891a  openspec/changes/pilot-object-read-rbac-fixtures/design.md
3128529b18a6226a6f66ebce2159bdf48ffb194f396869132cab179df99aabc2  openspec/changes/pilot-object-read-rbac-fixtures/specs/verification/pilot-object-read-rbac-fixtures/spec.md
71ab6211e1beb90e4af42ddcb6776b5008257d5202aec3e2a8e2bf2d4d0e921d  openspec/changes/pilot-object-read-rbac-fixtures/tasks.md
6c7929a4a599919d25eba2330c097e03450925435b0b50a49026d08cbc1e20f9  tests/Support/PilotObjectReadRbacFixture.php
551493ed4bcff887a36f53897bb5f41499913009be29a5995d57d2ca88755443  tests/InstallationProcess/pilot_object_list_001_test.php
```

