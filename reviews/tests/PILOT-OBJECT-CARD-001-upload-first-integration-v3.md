# PILOT-OBJECT-CARD-001 — independent upload-first integration Gate 3 review v3

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/object_card_upload_gate3`
- Reviewed commit: `0c879bb622c05bb300331f5c08c7eda1ffd62ae2`
- Correction base: `b33de6664d301d33188a007702fc6823ddd37879`
- Public behavior seam: raw HTTP `GET|HEAD /pilot/objects/{positive-id}`;
  the new direct table assertion is limited to deterministic fixture setup
- Verdict: **APPROVED**

The reviewer did not author the specifications, tests, production, RED
evidence, or correction. This append-only record is the only review edit.

## Independent assessment

The v3 correction closes the integration-fixture identity ambiguity without
changing the production assertion seam. `LocalRbacFixture::install()` now
receives explicit `fullName` for each of the five local actors used by the card
matrix:

```text
18  Сидоров Сергей Сергеевич
19  No Capability Reader
20  Inactive
21  Inactive role
24  Актор <script>actor-secret</script> &quot;
```

These are independently fixed literals already present in the object-card
legacy/local contract and existing behavior expectations. None is obtained
from current production rendering, an environment value, or the helper's
optional `Fixture {id}` default.

Immediately after installation and before any HTTP server or request, the test
queries its uniquely prefixed test-owned `poc_fm2_pilot_users` table and compares
all five exact `(user_id, full_name)` tuples in numeric ID order. An omitted
`fullName`, fallback use, substituted name, dropped actor, extra actor, wrong
identity association, or ordering change fails at setup before HTTP. This is a
permitted fixture-sensitivity assertion; all product behavior remains observed
through raw HTTP.

The changed diff adds no production/support/specification file and no DB grant.
In particular, the exact SELECT-only application principal still has no
artifact-table or artifact-column privilege. The existing privilege manifest,
forbidden-column/unrelated-table/write probes, and full DB/filesystem snapshots
remain intact.

All v2-approved behavior assertions are byte-unchanged apart from line movement:
complete Examples A/B content, state/current-order/team/event matrices,
upload-first href and interactive cardinality, action order and old-copy
absence, broad-reader/wrong-state/PTO negatives, capability separation,
route/method/body/query handling, GET/HEAD parity, CSP and exact script order,
failure priority/redaction, hostile-value escaping, deterministic reads,
zero-write fingerprints, resource cleanup and corrupt-projection coverage.
The UI-shell test is byte-identical to its v2-reviewed input.

Expected values remain specification literals and the run reaches the same
successful HTTP/CSP/script path before failing on the first missing approved
card fact. The RED is not caused by setup or by the new fixture assertion.

No blocking traceability, seam, sensitivity, expected-value independence,
authorization, privilege, scope, determinism, isolation, or cleanup finding
remains.

## Reproduced evidence

At `2026-09-04T11:57:09+03:00`:

```text
$ php -l tests/InstallationProcess/pilot_object_card_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_card_001_test.php

$ openspec validate remove-pilot-work-navigation-item --strict
Change 'remove-pilot-work-navigation-item' is valid

$ git diff --check \
    b33de6664d301d33188a007702fc6823ddd37879..0c879bb622c05bb300331f5c08c7eda1ffd62ae2
PASS (no output)

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/pilot_object_card_001_test.php
PHP Fatal error: Uncaught TestFailure:
Example A broad reader without capability visible literal/order: 77-000123
Expected: true
Actual: false
... pilot_object_card_001_test.php(556): pocSuccess()
exit 255
```

The exact five-tuple pre-HTTP assertion passed. The observed failure matches the
append-only v3 RED record. No test or production file was edited during review.

## Reviewed SHA-256 inputs

```text
8e348c95eab28ddb6a14fcdf18f512ca797f7dfd63f84df0d42f5678cfa5becc  tests/InstallationProcess/pilot_object_card_001_test.php
a3fd80de9e9d4fda16b04f9ca545a7ede33ca1b85390120a3d977790dafc3d68  tests/InstallationProcess/pilot_ui_shell_001_test.php
4bd7e00cad120124b5c453059fac3a4452664530b55141c12629183217e3d4a7  docs/operations/pilot-object-card-upload-first-integration-red-v3-2026-09-04.md
9de28dd858cf62c761f8d23ce0b1e0f5538b0a93eeb1dec28d2d0945b8c140b5  reviews/tests/PILOT-OBJECT-CARD-001-upload-first-integration-v2.md
ca82fca35d722c9c626eb6eb69e1aa11e7958730bc99e67541aebb610dbf5952  tests/Support/LocalRbacFixture.php
7d78aa830265dff3eb933b7fe3fc790c99be3ccbb2cce4cd2134f7bc803e9039  specs/PILOT-PREPARE-FORM-001.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
ec5d7b438c6696950e09397ae3b129c9890b9182636d27650127532d5d979732  specs/PILOT-OBJECT-CARD-001.md
47727bc12e904980fb751f9547f52b7a2abe67be1639c6c9e9a28b2016cd68ef  specs/PILOT-ROUTE-CSP-001.md
```

The review record path is metadata because a self-hash is circular. Relevant
spec/test/support bytes or scanned-set membership changes require fresh review.

Gate 4 is authorized for the exact test bytes at
`0c879bb622c05bb300331f5c08c7eda1ffd62ae2`. Implementation must preserve the
approved expectations. This record makes no GREEN or Gate 5 claim.
