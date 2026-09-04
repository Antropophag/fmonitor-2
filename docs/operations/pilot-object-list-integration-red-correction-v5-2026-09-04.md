# PILOT-OBJECT-LIST-001 integration RED correction v5

- Date: `2026-09-04`
- Gate: `2` correction after independent Gate 3 v4
- Test author: separately tasked agent `/root/object_list_integration_red`
- Exact correction baseline: `0e8cd19e93a7fa72d185478f3769b74cbb1b395b`
- Returned review: `reviews/tests/PILOT-OBJECT-READ-RBAC-FIXTURES-001-integration-v4.md`
- Production changes: none
- Public seam: raw HTTP `GET|HEAD /pilot/objects`

## Bounded application-copy oracle

Structural checks remain strict across the object-list main itself and all
descendants: classification-bearing `id`, `class`, `role`, and generic
`data-*` names or values are forbidden.

Visible-copy matching is now separate and narrower. It examines individual
application-owned visible text nodes while excluding script/style, hidden
subtrees, `.fm2-db-text`, explicit `data-db-text`, and canonical object-ID link
subtrees. Unicode-normalized exact/bounded labels include `Источник данных`,
`Происхождение данных`, Russian classification/migration/demo-data labels, and
English `data origin`, `origin classification`, `data provenance`, `migration
data`, and `demo data`. It no longer applies broad `демо` or `source` stems to
DB-derived object facts.

Independent probes require:

```text
application-owned `Источник данных: импорт` => one violation
DB text `Москва, ул. Демонтажная, д. 7`      => zero violations
DB text `Source, дом 2`                      => zero violations
shared-shell provenance outside main        => zero violations
```

The main-class and generic-data-value mutation probes remain independently
sensitive. Query, pagination, exact list facts/order, RBAC/revoke/error matrix,
snapshots, foreign decoys and cleanup remain unchanged.

The v4 record's blank EOF is disclosed separately without rewriting it in
`pilot-object-list-integration-red-v4-hygiene-note-2026-09-04.md`. Task `2.2`
remains open pending fresh independent Gate 3 approval of this hash.

## Genuine RED

```text
2026-09-04T17:24:31+03:00
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
... tests/InstallationProcess/pilot_object_list_001_test.php(270): assertSameValue()
exit 255
```

All classification mutation/collision controls pass first. The public normal
queue then passes the corrected structural/application-copy oracle, pagination
absence, exact list representation, configured shell, RBAC and snapshots.
`?sort=regnumber` and `?origin=demo_fixture` remain byte-equivalent. Current
production still changes selection for `?origin=migration`, producing the same
genuine public-seam RED. Attempt-all cleanup left no task-owned residue.

## Exact input hashes

```text
3f42ee848b3166a3961c7540d32319f608353e9b3749561cf7f2c30549172aa2  specs/PILOT-OBJECT-LIST-001.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
e3858f094c1f5c4411887b7a242122f714ec6d488febca87bd591553f5b05828  specs/PILOT-OBJECT-READ-RBAC-FIXTURES-001.md
6c7929a4a599919d25eba2330c097e03450925435b0b50a49026d08cbc1e20f9  tests/Support/PilotObjectReadRbacFixture.php
d611117fdaa8cfb83e18570b0704a470371e4c8423a872ebab4505c2aa7536a9  tests/InstallationProcess/pilot_object_list_001_test.php
9de75ed3831925201e77b9bf972ec1db131d229140bc1a297718f7410b8774c9  reviews/tests/PILOT-OBJECT-READ-RBAC-FIXTURES-001-integration-v4.md
```
