# PILOT-OBJECT-CARD-001 upload-first integration RED v3 — 2026-09-04

## Integration fixture correction

- Gate: Gate 2 tests/evidence only.
- Starting head: `b33de6664d301d33188a007702fc6823ddd37879`.
- Prior independent Gate 3 record:
  `reviews/tests/PILOT-OBJECT-CARD-001-upload-first-integration-v2.md`.
- Production/support/specification changes: none.

Object-list integration made `LocalRbacFixture` the configured HTTP identity
source. Its optional fallback name `Fixture {id}` therefore cannot stand in for
the independently fixed object-card actor names. The object-card fixture now
passes explicit `fullName` values matching its fixed legacy/local identities:

```text
18  Сидоров Сергей Сергеевич
19  No Capability Reader
20  Inactive
21  Inactive role
24  Актор <script>actor-secret</script> &quot;
```

Before any HTTP request, a direct assertion against the test-owned
`poc_fm2_pilot_users` fixture table compares all five exact `(user_id,
full_name)` tuples. This is fixture sensitivity only: it demonstrates that the
test arrangement contains the approved literal names and does not infer or
exercise a production identity adapter. Later public HTTP assertions remain
the behavior seam.

No artifact-table privilege was added. The POC contract does not require
artifact reads, and the future minimal Gate 4 correction remains responsible
for restoring the approved read composition instead of widening the test
principal around the current redesigned reader.

All Examples A/B, card facts and structure, upload-first action cardinality,
broad-reader/wrong-state/PTO negatives, capability separation, routes,
GET/HEAD, CSP/scripts, exact failures, escaping, SELECT-only checks, zero-write
fingerprints, determinism and cleanup assertions are otherwise unchanged.

## Qualifying RED

At `2026-09-04T11:55:40+03:00`:

```text
$ php -l tests/InstallationProcess/pilot_object_card_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_card_001_test.php
$ git diff --check
PASS (no output)
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/pilot_object_card_001_test.php
TestFailure: Example A broad reader without capability visible literal/order: 77-000123
Expected: true
Actual: false
... pilot_object_card_001_test.php(556): pocSuccess()
exit 255
```

The new exact fixture tuple assertion passed before HTTP. The canonical run
then passed setup, migration, RBAC, least-privilege checks, HTTP success, CSP
and external-script assertions and retained the same intended first card
presentation RED at missing registration number `77-000123`. This is not a
setup or identity-fixture failure.

## Exact candidate bytes

```text
8e348c95eab28ddb6a14fcdf18f512ca797f7dfd63f84df0d42f5678cfa5becc  tests/InstallationProcess/pilot_object_card_001_test.php
a3fd80de9e9d4fda16b04f9ca545a7ede33ca1b85390120a3d977790dafc3d68  tests/InstallationProcess/pilot_ui_shell_001_test.php
9de28dd858cf62c761f8d23ce0b1e0f5538b0a93eeb1dec28d2d0945b8c140b5  reviews/tests/PILOT-OBJECT-CARD-001-upload-first-integration-v2.md
7d78aa830265dff3eb933b7fe3fc790c99be3ccbb2cce4cd2134f7bc803e9039  specs/PILOT-PREPARE-FORM-001.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
ec5d7b438c6696950e09397ae3b129c9890b9182636d27650127532d5d979732  specs/PILOT-OBJECT-CARD-001.md
47727bc12e904980fb751f9547f52b7a2abe67be1639c6c9e9a28b2016cd68ef  specs/PILOT-ROUTE-CSP-001.md
```

Because the object-card test bytes changed after the v2 approval, Gate 4 is no
longer authorized by that record. A fresh independent Gate 3 review is
required.
