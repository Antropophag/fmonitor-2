# PILOT-OBJECT-READ-RBAC-FIXTURES-001 — independent integration Gate 3 v4

- Date: `2026-09-04T17:22:16+03:00`
- Reviewer: separately tasked agent `/root/object_list_integration_gate3`
- Test author: separately tasked agent `/root/object_list_integration_red`; reviewer authored none of the specification, test, fixture, production, RED evidence, or prior review
- Reviewed exact HEAD: `5d1dd4e6f581de1bc6073fa5d4840d9fad08fbd2`
- Correction baseline: `c35a7246231c86a789db3e234a588e1eeb9106ad`
- Public seam: raw HTTP `GET|HEAD /pilot/objects`
- Verdict: **CHANGES_REQUESTED**

No test or production file was edited by this review.

## Fresh verification

```text
$ date --iso-8601=seconds
2026-09-04T17:21:52+03:00

$ php -l tests/InstallationProcess/pilot_object_list_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_list_001_test.php

$ git diff --check \
    c35a7246231c86a789db3e234a588e1eeb9106ad..5d1dd4e6f581de1bc6073fa5d4840d9fad08fbd2
docs/operations/pilot-object-list-integration-red-correction-v4-2026-09-04.md:85: new blank line at EOF.
exit 2

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/pilot_object_list_001_test.php
PHP Fatal error: Uncaught TestFailure: query is byte-identical and ignored
/pilot/objects?origin=migration
Expected: status 200, Content-Length 3361, canonical three-item body
Actual:   status 200, Content-Length 2439, empty-state body
... tests/InstallationProcess/pilot_object_list_001_test.php(268): assertSameValue()
exit 255
```

The HTTP result is the intended origin-selection RED after all four new
classification probes, the canonical RBAC tracer, configured UI-shell checks,
semantic list/facts/order, snapshot, `?sort=regnumber`, and
`?origin=demo_fixture` have passed. Cleanup completed. This is a valid behavior
RED, but the durable evidence's diff-check statement is not factually correct.

## Closed c35 findings

The helper now includes `main#main-content` itself and all descendants, examines
`id`/`class`/`role`, generic data names and values, normalizes Unicode copy, and
stays scoped away from the shared shell and other consumers. The three exact
malicious probes each produce one violation; the exact approved-object and
outside-main provenance control produces zero. These changes close the three
mechanical blind spots identified by v3. All prior query, pagination,
RBAC/revoke/error, semantic list, exact 500/501, snapshot, foreign-decoy and
attempt-all cleanup matrices remain.

## Blocking findings

### 1. The visible-copy matcher rejects valid DB-derived object facts

The classification matcher is applied to all visible text in the collection
main, including DB-derived registration number, address and entrance. Those
approved values are constrained to valid nonblank strings, not to a vocabulary
that excludes English `source` or Russian words beginning with `демо`.

The Russian expression is not word-bounded at all. A direct read-only execution
of the exact `$forbidden` closure returned:

```text
Москва, ул. Демонтажная, д. 7 => VIOLATION
Source, дом 2 => VIOLATION
77-000124 · Москва, ул. Вторая, д. 7 => safe
```

Thus a legitimate imported address such as `ул. Демонтажная` makes the public
object-list test fail even though it contains no origin classification. The
current safe probe proves only the three fixed example values; it does not
prove absence of false positives for legitimate queue data.

Keep structural hook scanning over the whole bounded main, but scope copy
matching to application-owned classification copy rather than arbitrary
DB-derived fields. For example, exclude explicitly marked DB-text subtrees and
use bounded phrases/words with Unicode boundaries, then add safe probes with
collision-bearing legitimate object text (`Демонтажная` and/or `Source`) plus
the outside-main provenance control. Retain independent malicious RU/EN copy
probes so sensitivity is not weakened.

### 2. The append-only RED record incorrectly claims a clean diff

The v4 evidence states `git diff --check` exited zero with no output. Fresh
reproduction on its exact range exits `2` because the record itself has a blank
line at EOF. The append-only record must not be rewritten. Add a separate
hygiene disclosure recording its exact hash and the actual output, and make
the next evidence distinguish the historical evidence-only whitespace from a
clean executable/test diff.

## Gate decision

Gate 3 remains **CHANGES_REQUESTED**. OpenSpec task `2.2` stays unchecked and
Gate 4 is unauthorized. Request fresh review after test-only correction, a new
exact-hash RED, and append-only hygiene disclosure.

## Exact reviewed hashes

```text
3f42ee848b3166a3961c7540d32319f608353e9b3749561cf7f2c30549172aa2  specs/PILOT-OBJECT-LIST-001.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
e3858f094c1f5c4411887b7a242122f714ec6d488febca87bd591553f5b05828  specs/PILOT-OBJECT-READ-RBAC-FIXTURES-001.md
7b4c177ac5cbd748fcaf11f2570860aceb573083ac5dbe725e61efaa29390dcd  tests/InstallationProcess/pilot_object_list_001_test.php
6c7929a4a599919d25eba2330c097e03450925435b0b50a49026d08cbc1e20f9  tests/Support/PilotObjectReadRbacFixture.php
a2a3c4e24ef73799021ff5de5923d267e62df35e9832aeffea2cbce9749704b4  docs/operations/pilot-object-list-integration-red-correction-v4-2026-09-04.md
62ab1b85f4a2053296adf6b549235cbed7fadd2bc90007fd744cf4ba7cbd3301  reviews/tests/PILOT-OBJECT-READ-RBAC-FIXTURES-001-integration-v3.md

METADATA  reviews/tests/PILOT-OBJECT-READ-RBAC-FIXTURES-001-integration-v4.md
```
