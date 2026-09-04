# PILOT-OBJECT-READ-RBAC-FIXTURES-001 — independent integration Gate 3 v3

- Date: `2026-09-04T17:17:44+03:00`
- Reviewer: separately tasked agent `/root/object_list_integration_gate3`
- Test author: separately tasked agent `/root/object_list_integration_red`; this reviewer did not author the specifications, tests, fixtures, production, RED evidence, or prior review
- Reviewed exact HEAD: `8a8a4e2ed158dabeb0c16a052f82b5e7df36ff78`
- Correction baseline: `04ec425ab62a6a5c0a8dc5bf28ec248624631530`
- Public seam: raw HTTP `GET|HEAD /pilot/objects`
- Verdict: **CHANGES_REQUESTED**

Production and tests were not edited. This record reviews only the new exact
test/evidence bytes and does not reuse an approval from an earlier integration
composition.

## Fresh RED reproduction

```text
$ date --iso-8601=seconds
2026-09-04T17:17:44+03:00

$ php -l tests/InstallationProcess/pilot_object_list_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_list_001_test.php

$ git diff --check \
    04ec425ab62a6a5c0a8dc5bf28ec248624631530..8a8a4e2ed158dabeb0c16a052f82b5e7df36ff78
# exit 0, no output

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/pilot_object_list_001_test.php
PHP Fatal error: Uncaught TestFailure: query is byte-identical and ignored
/pilot/objects?origin=migration
Expected: status 200, Content-Length 3361, canonical three-item body
Actual:   status 200, Content-Length 2439, empty-state body
... tests/InstallationProcess/pilot_object_list_001_test.php(264): assertSameValue()
exit 255
```

Both synthetic probes pass before database setup. The public run then passes
the canonical RBAC revoke/restore tracer, authentication, configured shell,
navigation/script/CSP, normal-success structural negatives, exact semantic
list facts/order/links, snapshots, and the newly restored
`?sort=regnumber` byte-equivalence case. `?origin=demo_fixture` also matches.
The first failure remains the intended current-production origin selection,
not setup or a predecessor. Attempt-all cleanup ran after the failure.

## Closed v2 findings

1. **Independent arbitrary query — closed.** `?sort=regnumber` is restored in
   addition to all three origin variants, and stable status, every
   application-controlled header, and the body are byte-compared.
2. **Pagination structure — closed.** Normal and exact-500 successes reject
   nested navigation/controls, pagination/pager classes, IDs, roles and labels,
   `data-page*` hooks, `aria-current=page`, `page=` destinations and the fixed
   pagination copy. The 501 case still requires exact redacted `503`,
   `Retry-After: 60`, and zero partial object links. The synthetic probe proves
   sensitivity to nav/class/query/data/copy mutations.
3. **Scope — closed in principle.** All new structural scans are rooted at the
   collection `main#main-content`; no prepare, upload, card, shared-sidebar or
   other provenance consumer is scanned. No existing provenance assertion was
   removed or weakened.

All earlier local-RBAC actor/grant/revoke/error matrices, restricted denial
reader, GET/HEAD/route/method/integrity checks, exact object values, snapshots,
foreign decoys and attempt-all cleanup remain present and unchanged.

## Blocking finding: classification mutation oracle still has three holes

`polObjectListClassificationHooks()` scans only descendants of
`main#main-content`, not the main element itself. It inspects the **names** of
generic `data-*` attributes but not their values. It does not inspect visible
copy at all. The fixed forbidden-string loop catches current known Russian copy
and two full machine values, but cannot supply structural mutation sensitivity
for these paths.

Therefore each of the following forbidden queue classifications passes the new
oracle:

```html
<main id="main-content" class="fm2-origin-view">...</main>
<span data-kind="migration">...</span>
<span>Источник данных: импорт</span>
```

A read-only reproduction of the helper algorithm over those combined
mutations printed `descendant_candidates=1` and no `violation`: the main class
is outside the descendant XPath, `data-kind` contains no token in its name and
its `migration` value is ignored, and visible copy is never evaluated. This
also shows why the current synthetic fixture's visible `Источник` text does
not prove copy sensitivity: its asserted count of three comes solely from the
class, ID, and data-name attributes.

The approved object-list excludes origin/process classification, and the task
explicitly requires classification attributes/classes/IDs/data/copy/machine
values with mutation sensitivity. Expand the bounded helper/probe to:

- include `main#main-content` itself as well as descendants;
- inspect both names and values of `data-*` attributes for the bounded tokens;
- inspect normalized collection-main copy for bounded English and Russian
  origin/provenance/classification/migration/demo/source labels;
- demonstrate each channel independently in the synthetic probe.

Keep the scan scoped to the object-list main. It must not ban provenance in the
shared shell or in prepare/upload/card consumers.

## Gate decision

Gate 3 remains **CHANGES_REQUESTED**. OpenSpec task `2.2` stays unchecked and
Gate 4 remains unauthorized. After test-only correction and fresh exact-hash
RED evidence, request another independent Gate 3 review.

## Exact reviewed hashes

```text
3f42ee848b3166a3961c7540d32319f608353e9b3749561cf7f2c30549172aa2  specs/PILOT-OBJECT-LIST-001.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
e3858f094c1f5c4411887b7a242122f714ec6d488febca87bd591553f5b05828  specs/PILOT-OBJECT-READ-RBAC-FIXTURES-001.md
b25a110fd7610cb347df4e0bd7fcfeec9d133c8213cd039d013c95ec89fea00e  tests/InstallationProcess/pilot_object_list_001_test.php
6c7929a4a599919d25eba2330c097e03450925435b0b50a49026d08cbc1e20f9  tests/Support/PilotObjectReadRbacFixture.php
cfdb0a3e26c61638c0d808bfffab03b78b9d8293d2ed6aa620deb6bd004b9f3a  docs/operations/pilot-object-list-integration-red-correction-v3-2026-09-04.md
4b234cb97fe0e62b3048021a1a310b95d6b0ad9ccbf97bafb41dd3c2ed42eb8f  reviews/tests/PILOT-OBJECT-READ-RBAC-FIXTURES-001-integration-v2.md

METADATA  reviews/tests/PILOT-OBJECT-READ-RBAC-FIXTURES-001-integration-v3.md
```
