# PILOT-OBJECT-READ-RBAC-FIXTURES-001 — independent integration Gate 3 v2

- Date: `2026-09-04T17:10:43+03:00`
- Reviewer: separately tasked agent `/root/object_list_integration_gate3`
- Test author: separately tasked agent `/root/object_list_integration_red`; this reviewer authored none of the specification, test, fixture, production, prior review, or RED evidence
- Reviewed exact HEAD: `1350a8fe6268b8df1ac5b4910aa2603af42ff619`
- Replacement Gate 2 baseline: `dc91b50badba0959df1c6ab7fc5c6fcac5484625`
- Public seam: raw HTTP `GET|HEAD /pilot/objects`
- Verdict: **CHANGES_REQUESTED**

This is a fresh review of the changed integration-composition test bytes. It
does not reuse the earlier fixture Gate 3 approval across the later
origin-classification/pagination drift. Production and tests were not edited by
this review.

## Reproduced RED

On the exact reviewed HEAD:

```text
$ date --iso-8601=seconds
2026-09-04T17:09:53+03:00

$ php -l tests/InstallationProcess/pilot_object_list_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_list_001_test.php

$ php -l tests/Support/PilotObjectReadRbacFixture.php
No syntax errors detected in tests/Support/PilotObjectReadRbacFixture.php

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/pilot_object_list_001_test.php
PHP Fatal error: Uncaught TestFailure: query is byte-identical and ignored
/pilot/objects?origin=migration
Expected: status 200, Content-Length 3361, canonical three-item body
Actual:   status 200, Content-Length 2439, empty-state body
... tests/InstallationProcess/pilot_object_list_001_test.php(238): assertSameValue()
exit 255
```

The run reaches the real configured HTTP collection after the canonical local
RBAC fixture/revoke tracer, authentication, GET/HEAD parity, shared shell,
navigation-removal predecessor, scripted CSP, negative origin attributes and
copy, semantic list, exact object facts/order/links, decoy exclusion and
read-only snapshots. Canonical GET and `?origin=demo_fixture` are equivalent;
the first intended failure is the current production selection change for
`?origin=migration`. This is a genuine behavior RED, not setup or predecessor
failure. Final cleanup left the worktree clean and the next fresh run reused no
task-owned schema, principal, or artifact.

## Blocking findings

### 1. The arbitrary-query contract lost its independent sensitivity case

`PILOT-OBJECT-LIST-001` sections 2, 3, 10 and 11 require that **any** query is
ignored and that query does not participate in routing, selection, ordering or
rendering. Before this correction the test had a non-origin example
`?sort=regnumber`. The replacement loop contains only the `origin` key:
`origin=demo_fixture`, `origin=migration`, and `origin=arbitrary`.

The value named `arbitrary` is not an arbitrary query shape: it still exercises
the same `origin` parser. An implementation can remove the known origin filter
while starting to honor `sort`, `page`, an unknown key, or a multi-value query,
and this oracle will remain GREEN. Restore at least one independently chosen
non-origin query (the prior `?sort=regnumber` is sufficient) and require the
same stable byte-equivalent response. Keep the origin variants because they
are the executable regression for the integration drift.

### 2. Absence of pagination DOM is not asserted

The approved contract explicitly has no pagination/query parameters or
pagination control. The corrected boundary checks correctly require `501` to
return exact redacted `503` with `Retry-After: 60`, no object links, and `500`
to return all 500 object-card links. It does not reject a pagination control,
`.shlz-pagination`, `page=` link, pagination-labelled navigation, or page copy
on the successful three-row or 500-row representations.

Consequently a production response can contain all 500 rows plus a still
rendered pagination UI/URL state and pass. Add public-DOM assertions that the
successful queue has no pagination navigation/control, pagination class, or
`page=` destination. This must be checked on the ceiling representation as
well as the normal representation, because pagination may be threshold-bound.

### 3. The classification-DOM prohibition is structurally incomplete

The negative XPath rejects origin-labelled navigation, `origin=` links/forms,
an input named `origin`, and a useful set of data attributes. The forbidden
literal list catches the current Russian classification copy. It does not
reject ordinary classification markup such as an element whose `class` or
`id` names origin/migration/classification/filter, or alternative visible
classification copy. Such a non-interactive badge would violate the approved
queue while passing the current oracle.

Add a bounded structural negative for origin/filter/classification markup in
the collection DOM (class/id/role or equivalent application hook), while
retaining the exact known-copy and data-attribute checks. Do not turn this into
a repository-wide ban on provenance: the prohibition is scoped to this
object-list representation, and no provenance assertion in prepare/upload or
other approved consumers may be weakened.

## Passing assessment

- Expected object values are independently fixed by the approved executable
  example; the semantic list binds each exact link and fact set to one item in
  canonical date/ID order. The non-imported decoy remains excluded.
- The 500/501 fixture derives only input rows mechanically; the expected
  ceiling and statuses come from the independently fixed specification. It
  catches truncation and the current 50-row pagination implementation.
- The current UI-shell composition is correctly inherited: exact scripted CSP,
  one source-only navigation script and no inline handler/JavaScript URL are
  asserted without restoring the obsolete no-script predecessor.
- Local-RBAC grant/revoke, malformed/absent actors, inactive/missing/near-match
  grants, restricted denial reader, authorization failure priority and safe
  logging remain unchanged and sensitive.
- GET/HEAD parity, invalid route/method, empty/integrity/unavailable outcomes,
  complete schema/row/AUTO_INCREMENT snapshots, filesystem guards, foreign
  decoys and attempt-all cleanup remain intact.
- `polStableResponse()` avoids mutating the canonical response while excluding
  only server-controlled `Date` and `Connection`; all application-controlled
  status, headers and body remain byte-compared.

## Required changes

1. Restore a byte-equivalent non-`origin` arbitrary-query case without removing
   any of the three origin regression cases.
2. Reject pagination DOM/URLs on both normal success and exact-500 success.
3. Close the bounded classification-markup hole without weakening provenance
   contracts elsewhere.
4. Record a fresh RED for the new exact test hash and request another fresh
   independent Gate 3 review. Gate 4 remains unauthorized.

OpenSpec task `2.2` remains unchecked because this verdict is not `APPROVED`.

## Exact reviewed-input hashes

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
3f42ee848b3166a3961c7540d32319f608353e9b3749561cf7f2c30549172aa2  specs/PILOT-OBJECT-LIST-001.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
e3858f094c1f5c4411887b7a242122f714ec6d488febca87bd591553f5b05828  specs/PILOT-OBJECT-READ-RBAC-FIXTURES-001.md
551493ed4bcff887a36f53897bb5f41499913009be29a5995d57d2ca88755443  tests/InstallationProcess/pilot_object_list_001_test.php
6c7929a4a599919d25eba2330c097e03450925435b0b50a49026d08cbc1e20f9  tests/Support/PilotObjectReadRbacFixture.php
79db1cfed40b2e60b4d53acce963527eaeb6e9cc3eec31e1a30562c88a504c40  docs/operations/pilot-object-list-integration-red-correction-v1-2026-09-04.md
dd442cb073be2b3b91f648ea39098245e2bc64589dc33d72f6f6d2a356f21cb7  reviews/code/PILOT-OBJECT-CARD-001-upload-first-integration-v2.md
47fbb292797b24e1772d3a8deb7a26a27b78818a7137c1f7cecaff9fdfd7a109  reviews/code/PILOT-UI-SHELL-001-upload-first-integration-v4.md
17059bf35aef7eca757548eed9d38742c25d3b1091587f8ce9ce7eb7f0aba18b  reviews/code/PILOT-WORK-NAVIGATION-ITEM-REMOVAL-001-v1.md

METADATA  reviews/tests/PILOT-OBJECT-READ-RBAC-FIXTURES-001-integration-v2.md
```
