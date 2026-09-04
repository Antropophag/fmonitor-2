# PILOT-OBJECT-CARD-001 — independent upload-first integration Gate 3 review v1

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/object_card_upload_gate3`
- Reviewed commit: `16523d68061eb2a868de96786f99b3283eb7e0d3`
- Integration base: `2eb022cb2bb514a6ac024394c3739c428ca6f7cb`
- Public seam: raw HTTP `GET|HEAD /pilot/objects/{positive-id}` through
  `public/router.php` and isolated MariaDB; the UI-shell file is reviewed only
  for its bounded object-card expectation correction
- Verdict: **CHANGES_REQUESTED**

The reviewer authored neither the specifications, tests, production changes,
RED evidence nor earlier reviews. This append-only record is the only review
edit.

## Blocking finding

### G3-1 — the new capable-reader oracle does not prove the sole-link contract

Both changed tests count only an anchor which simultaneously has the canonical
href and exact visible text:

```xpath
//a[@href='/pilot/objects/4512/assignment-order/prepare'
   and normalize-space(.)='Загрузить распоряжение']
```

(The UI-shell assertion uses the equivalent `4515` expression.) A response with
that required link plus a second link to the same prepare route under different
visible text therefore still passes. A response with an unrelated additional
action link also passes the capable-card additions. This is not sensitive to
the owner-approved single/sole primary journey link requirement inherited by
`PILOT-UI-SHELL-001` section 6 and described by the Gate 2 evidence itself as
“exactly one ordinary link”.

The existing broad-reader test correctly excludes both upload-first text and
the canonical prepare URL, but it cannot establish capable-reader cardinality.
The capable path does not call the older `pocStructure()` link allowlist because
that helper correctly describes the broad-reader predecessor and would reject
the newly authorized link. Consequently the gap is executable, not merely
wording.

Required correction: at both changed public HTTP assertions, independently
prove that exactly one anchor has the canonical prepare href, that its exact
visible label is `Загрузить распоряжение`, and that no additional process-action
link is present in the reviewed next-step area (or use an equally strict DOM
oracle derived from the approved contract). Preserve the current action order
and old-copy absence assertions. Capture a new intended RED and request a fresh
independent Gate 3 review of the corrected bytes.

Because a required acceptance property is not observable with sufficient
sensitivity, Gate 4 is **not authorized** for these test bytes.

## What remains sound and preserved

- The test changes are traceable only to the owner-approved
  `PILOT-PREPARE-FORM-001 v0.2` upload-first replacement: text changes to
  `Загрузить распоряжение`; the canonical GET route is unchanged.
- Broad-reader actor `19` remains distinct from capable actor `18`; the fixture
  proves that only `18` owns exact `assignment_order.prepare`.
- The capable-path ordered group oracle requires the reason, action, and
  historical/team explanation in the approved order. The superseded
  `Сформировать распоряжение` copy is explicitly absent.
- The complete pre-existing object-card content/state/current-version/team/event
  matrices, route and method grammar, GET/HEAD parity, authorization/failure
  priority and redaction, CSP and exact external scripts, escaping, SELECT-only
  privilege boundary, zero-write snapshots, repeat/concurrency behavior and
  cleanup remain byte-present and were not weakened by the diff.
- The two-line UI-shell change is bounded to the same object-card label and old
  copy absence. Its broader prepare presentation remains an acknowledged
  predecessor RED and is not approved or represented as GREEN by this review.
- Expected upload-first text and route are fixed by specifications, not copied
  from production output. The focused failure reaches HTTP success/CSP/script
  checks and stops at the first missing predecessor card fact, so it is a valid
  behavior RED rather than setup failure.

## Reproduced evidence

At `2026-09-04T11:33:39+03:00`:

```text
$ php -l tests/InstallationProcess/pilot_object_card_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_card_001_test.php

$ php -l tests/InstallationProcess/pilot_ui_shell_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_ui_shell_001_test.php

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/pilot_object_card_001_test.php
PHP Fatal error: Uncaught TestFailure:
Example A broad reader without capability visible literal/order: 77-000123
Expected: true
Actual: false
... pilot_object_card_001_test.php(541): pocSuccess()
exit 255

$ openspec validate remove-pilot-work-navigation-item --strict
Change 'remove-pilot-work-navigation-item' is valid

$ git diff --check \
    2eb022cb2bb514a6ac024394c3739c428ca6f7cb..16523d68061eb2a868de96786f99b3283eb7e0d3
PASS (no output)
```

The reproduced failure matches the append-only Gate 2 record exactly. No test
or production file was modified during review.

## Reviewed SHA-256 inputs

```text
7d78aa830265dff3eb933b7fe3fc790c99be3ccbb2cce4cd2134f7bc803e9039  specs/PILOT-PREPARE-FORM-001.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
ec5d7b438c6696950e09397ae3b129c9890b9182636d27650127532d5d979732  specs/PILOT-OBJECT-CARD-001.md
47727bc12e904980fb751f9547f52b7a2abe67be1639c6c9e9a28b2016cd68ef  specs/PILOT-ROUTE-CSP-001.md
ff5afebbe67b6436b49af8ef4f327ed193f8baa9f2af9e48b401a13c9b15f6ec  tests/InstallationProcess/pilot_object_card_001_test.php
38bd40ed9503900f84d33bc01953f58ea5864481a1eb9e7c394367299765b1fa  tests/InstallationProcess/pilot_ui_shell_001_test.php
17b1087522081c12190030d4606fb0f1d3da68b4a60d9559dfed900e5671d997  docs/operations/pilot-object-card-upload-first-integration-red-2026-09-04.md
014bf3f5726ef7913816ebb536a0b57946b1203e96809c2ecb14f49d4d0e3d19  reviews/tests/PILOT-OBJECT-CARD-001.md
0e43ef4543c83869e0fd0755719cc8a6c837dff8a43e1256f4d988546c3afb91  reviews/tests/PILOT-OBJECT-CARD-001-csp-correction-v4.md
18c9b9e60aef82c297674333c0cf8f4ea069e4e5b6b09467803a6aa94915ae9f  reviews/tests/PILOT-OBJECT-CARD-001-shared-shell-fixture-correction-v1.md
```

The review record path is metadata because a self-hash is circular. Any
specification/test/support byte or relevant manifest membership change requires
fresh review under the governing contracts.
