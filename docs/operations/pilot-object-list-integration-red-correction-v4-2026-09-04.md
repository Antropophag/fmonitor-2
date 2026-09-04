# PILOT-OBJECT-LIST-001 integration RED correction v4

- Date: `2026-09-04`
- Gate: `2` correction after independent Gate 3 v3
- Test author: separately tasked agent `/root/object_list_integration_red`
- Exact correction baseline: `c35a7246231c86a789db3e234a588e1eeb9106ad`
- Returned review: `reviews/tests/PILOT-OBJECT-READ-RBAC-FIXTURES-001-integration-v3.md`
- Production changes: none
- Public seam: raw HTTP `GET|HEAD /pilot/objects`

## Classification-oracle correction

The bounded object-list classification oracle now inspects
`main#main-content` itself and every descendant. It normalizes attribute values
and visible text with Unicode lowercase and collapsed whitespace. It checks:

- every `id`, `class`, and `role` value;
- both the name and normalized value of every generic `data-*` attribute;
- visible main text, excluding script/style and hidden/`aria-hidden` subtrees;
- bounded English words `origin`, `provenance`, `classification`, `migration`,
  `demo`, `source` and Russian stems `источник`, `происхожд`, `классификац`,
  `миграц`, `демо`.

The Latin checks use word boundaries so unrelated substrings are not rejected.
The scan remains rooted at the object-list main and therefore does not ban
provenance in the shared shell, prepare, upload, card, or other consumers.

Independent synthetic mutations now require exactly one violation each for:

```html
<main id="main-content" class="fm2-origin-view">...</main>
<span data-kind="migration">...</span>
<span>Источник данных: импорт</span>
```

A safe control contains approved object facts in the main and a deliberately
provenance-labelled shared-shell element outside the main; it must yield zero
violations. All v3 query, pagination, list semantics, local-RBAC, failure,
snapshot, foreign-decoy and cleanup assertions remain.

OpenSpec task `2.2` remains open because this exact test hash has not received
fresh independent Gate 3 approval.

## Genuine RED

```text
2026-09-04T17:20:12+03:00
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
... tests/InstallationProcess/pilot_object_list_001_test.php(268): assertSameValue()
exit 255
```

All four independent classification probes pass before the HTTP fixture. The
real normal queue then passes the expanded main-and-descendants structural and
visible-copy oracle, pagination absence, exact facts/order/links, current
UI-shell/navigation/CSP/script composition, RBAC revoke/restore and read-only
snapshots. `?sort=regnumber` and `?origin=demo_fixture` remain byte-equivalent
to canonical GET. The first behavioral failure is unchanged: production uses
`?origin=migration` to replace the canonical list with an empty state.

Attempt-all cleanup completed; no task-owned object-list database, principal,
server or artifact remained.

## Exact input hashes

```text
3f42ee848b3166a3961c7540d32319f608353e9b3749561cf7f2c30549172aa2  specs/PILOT-OBJECT-LIST-001.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
e3858f094c1f5c4411887b7a242122f714ec6d488febca87bd591553f5b05828  specs/PILOT-OBJECT-READ-RBAC-FIXTURES-001.md
6c7929a4a599919d25eba2330c097e03450925435b0b50a49026d08cbc1e20f9  tests/Support/PilotObjectReadRbacFixture.php
7b4c177ac5cbd748fcaf11f2570860aceb573083ac5dbe725e61efaa29390dcd  tests/InstallationProcess/pilot_object_list_001_test.php
62ab1b85f4a2053296adf6b549235cbed7fadd2bc90007fd744cf4ba7cbd3301  reviews/tests/PILOT-OBJECT-READ-RBAC-FIXTURES-001-integration-v3.md
```

