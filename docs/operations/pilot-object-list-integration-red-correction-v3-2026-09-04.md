# PILOT-OBJECT-LIST-001 integration RED correction v3

- Date: `2026-09-04`
- Gate: `2` correction after independent Gate 3 v2
- Test author: separately tasked agent `/root/object_list_integration_red`
- Exact correction baseline: `04ec425ab62a6a5c0a8dc5bf28ec248624631530`
- Review returned to Gate 2: `reviews/tests/PILOT-OBJECT-READ-RBAC-FIXTURES-001-integration-v2.md`
- Production changes: none
- Public seam: raw HTTP `GET|HEAD /pilot/objects`

## Gate 3 findings closed

The corrected verifier retains all v1 assertions and adds:

1. Independent non-origin query sensitivity. Canonical GET must be byte-equivalent
   to `?sort=regnumber` as well as the three origin regression variants; only
   server `Date` and `Connection` headers are excluded.
2. Bounded pagination absence on the object-list `<main>` for both the normal
   three-item success and exact 500-item success. The oracle rejects pagination
   or pager classes/IDs/roles/labels, `data-page*`/pagination data hooks,
   `aria-current=page`, pagination copy, and any `href` containing `page=`.
3. Bounded classification-hook absence on the object-list `<main>`. IDs and
   classes and roles, plus `data-*` attribute names, are rejected when they
   contain `origin`, `provenance`, `classification`, `migration`, or `demo`.
   The existing explicit origin controls/URLs/data attributes and fixed visible
   classification-label prohibitions remain.
4. Synthetic sensitivity probes demonstrate that the new structural helpers
   catch class, ID, data-name, navigation-label, query and visible-copy
   mutations before the real public HTTP fixture runs.

The scope is deliberately the collection `<main>`; shared shell and other
approved provenance consumers are not scanned or weakened. Exact list
facts/order, navigation removal, local-RBAC revoke/negative matrix,
origin-independent reader behavior, HEAD parity, snapshots, cleanup, foreign
decoys and infrastructure/error cases remain unchanged.

OpenSpec task `2.2` remains `[ ]`: changed test bytes require another fresh
independent Gate 3 approval.

## Genuine RED

```text
2026-09-04T17:16:34+03:00
$ php -l tests/InstallationProcess/pilot_object_list_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_list_001_test.php

$ git diff --check
# exit 0; no output

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/pilot_object_list_001_test.php
PHP Fatal error: Uncaught TestFailure: query is byte-identical and ignored
/pilot/objects?origin=migration
Expected: status 200, Content-Length 3361, canonical three-item body
Actual:   status 200, Content-Length 2439, empty-state body
... tests/InstallationProcess/pilot_object_list_001_test.php(264): assertSameValue()
exit 255
```

Before the intended failure, both structural mutation probes pass. The real
configured collection then passes canonical RBAC revoke/restore, authenticated
GET/HEAD, shared shell/navigation/script/CSP, bounded classification and
pagination absence, exact three-item facts/order/links, non-imported decoy and
read-only snapshots. The independent `?sort=regnumber` request and
`?origin=demo_fixture` are byte-equivalent to canonical GET. The next request,
`?origin=migration`, changes only the query and current production returns an
empty representation, proving the same intended public-seam RED.

Attempt-all cleanup completed after the failure; no task-owned schema,
principal, server, `pol-*` or `foreign-*` artifact remained.

## Exact reviewed-input candidates

```text
3f42ee848b3166a3961c7540d32319f608353e9b3749561cf7f2c30549172aa2  specs/PILOT-OBJECT-LIST-001.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
e3858f094c1f5c4411887b7a242122f714ec6d488febca87bd591553f5b05828  specs/PILOT-OBJECT-READ-RBAC-FIXTURES-001.md
6c7929a4a599919d25eba2330c097e03450925435b0b50a49026d08cbc1e20f9  tests/Support/PilotObjectReadRbacFixture.php
b25a110fd7610cb347df4e0bd7fcfeec9d133c8213cd039d013c95ec89fea00e  tests/InstallationProcess/pilot_object_list_001_test.php
4b234cb97fe0e62b3048021a1a310b95d6b0ad9ccbf97bafb41dd3c2ed42eb8f  reviews/tests/PILOT-OBJECT-READ-RBAC-FIXTURES-001-integration-v2.md
```
