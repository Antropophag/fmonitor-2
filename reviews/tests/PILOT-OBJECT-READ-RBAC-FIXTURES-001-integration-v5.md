# PILOT-OBJECT-READ-RBAC-FIXTURES-001 — independent integration Gate 3 v5

- Date: `2026-09-04T17:26:17+03:00`
- Reviewer: separately tasked agent `/root/object_list_integration_gate3`
- Test author: separately tasked agent `/root/object_list_integration_red`; reviewer authored none of the specification, test, fixture, production, RED evidence, or prior review
- Reviewed exact HEAD: `b66ac85e21e221c342f7bce5ac39994fd32b273e`
- Correction baseline: `0e8cd19e93a7fa72d185478f3769b74cbb1b395b`
- Public seam: raw HTTP `GET|HEAD /pilot/objects`
- Verdict: **APPROVED**

This approval applies only to the exact reviewed test/fixture and integration
composition. It authorizes minimal Gate 4 GREEN; it is not production approval,
Gate 5, repository-wide GREEN, CI readiness, or release evidence. This reviewer
changed no test or production byte.

## Fresh reproduction

```text
$ date --iso-8601=seconds
2026-09-04T17:26:17+03:00

$ php -l tests/InstallationProcess/pilot_object_list_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_list_001_test.php

$ git diff --check \
    0e8cd19e93a7fa72d185478f3769b74cbb1b395b..b66ac85e21e221c342f7bce5ac39994fd32b273e
# exit 0; no output

$ tail -c 2 docs/operations/pilot-object-list-integration-red-correction-v5-2026-09-04.md | od -An -t x1
60 0a

$ tail -c 2 docs/operations/pilot-object-list-integration-red-v4-hygiene-note-2026-09-04.md | od -An -t x1
2e 0a

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/pilot_object_list_001_test.php
PHP Fatal error: Uncaught TestFailure: query is byte-identical and ignored
/pilot/objects?origin=migration
Expected: status 200, Content-Length 3361, canonical three-item body
Actual:   status 200, Content-Length 2439, empty-state body
... tests/InstallationProcess/pilot_object_list_001_test.php(270): assertSameValue()
exit 255
```

All classification probes and the real canonical queue pass before the intended
origin-query failure. The run also passes the canonical RBAC revoke/restore
tracer, configured shell/navigation/CSP/script assertions, exact semantic list
facts/order/links, non-imported decoy, snapshots, pagination absence,
`?sort=regnumber`, and `?origin=demo_fixture`. Cleanup completes after the
failure. The RED is therefore public behavior sensitivity, not setup or a
predecessor.

## Findings

All v2–v4 blockers are closed for the exact bytes:

1. Canonical response is stably byte-compared with an independent non-origin
   query and all three origin regression values, excluding only server `Date`
   and `Connection`.
2. Normal and exact-500 success reject pagination navigation, controls,
   classes/IDs/roles/labels, data hooks, copy, `aria-current=page`, and `page=`
   destinations. Exactly 500 complete object links succeed; 501 returns exact
   redacted `503`, `Retry-After: 60`, and no partial list.
3. Structural classification detection retains the whole bounded collection
   main plus descendants and checks `id`, `class`, `role`, and generic `data-*`
   names and values.
4. Visible-copy detection is separated from structural hooks and restricted to
   application-owned text nodes. It excludes hidden/script/style, marked
   DB-text and canonical object-ID link subtrees, and uses normalized bounded
   Russian/English classification phrases rather than the prior broad `source`
   and `демо` match.
5. Independent probes are sensitive one channel at a time: main class,
   generic data value, and application copy each yield exactly one violation.
   Collision-bearing legitimate DB text (`Демонтажная`, `Source`), canonical
   object/date text, and provenance outside collection main yield zero. The
   real approved queue has no false positive.
6. Exact semantic item/link association, object values/order, exclusion,
   local-RBAC grant/revoke and actor/error matrices, denial reader, HEAD/route/
   method/integrity behavior, schema/row/AUTO_INCREMENT and filesystem
   snapshots, foreign decoys, and attempt-all cleanup remain intact.
7. The v4 evidence source was not rewritten. Its hygiene note identifies the
   exact immutable source/hash/range and reproduces the evidence-only blank EOF.
   The v5 range is diff-clean and both new records have exactly one final LF.

Expected values remain independent of production output. The negative
classification scan is confined to this queue and does not weaken or prohibit
approved provenance in prepare, original upload, card, shared shell, or other
consumers. No further Gate 3 finding remains.

## Gate decision

**APPROVED.** Minimal Gate 4 may remove origin-driven selection/rendering and
restore the exact unpaginated 500/501 contract without changing the reviewed
test. Any test, fixture, approved specification, or integration-composition
change requires a fresh independent Gate 3 review.

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
d611117fdaa8cfb83e18570b0704a470371e4c8423a872ebab4505c2aa7536a9  tests/InstallationProcess/pilot_object_list_001_test.php
6c7929a4a599919d25eba2330c097e03450925435b0b50a49026d08cbc1e20f9  tests/Support/PilotObjectReadRbacFixture.php
9cc0c4dc6cfa09fa42b090497943a24e55390866d30d45a5cd74bcf89d5d0f36  docs/operations/pilot-object-list-integration-red-correction-v5-2026-09-04.md
4b1826b92dcb1a1a81504151636876ea7623fc753be215a3fe358fcc5d9fc776  docs/operations/pilot-object-list-integration-red-v4-hygiene-note-2026-09-04.md
a2a3c4e24ef73799021ff5de5923d267e62df35e9832aeffea2cbce9749704b4  docs/operations/pilot-object-list-integration-red-correction-v4-2026-09-04.md
9de75ed3831925201e77b9bf972ec1db131d229140bc1a297718f7410b8774c9  reviews/tests/PILOT-OBJECT-READ-RBAC-FIXTURES-001-integration-v4.md

METADATA  reviews/tests/PILOT-OBJECT-READ-RBAC-FIXTURES-001-integration-v5.md
```
