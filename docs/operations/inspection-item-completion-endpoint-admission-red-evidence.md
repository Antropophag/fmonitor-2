# INSPECTION-ITEM-COMPLETE-001 raw HTTP admission RED evidence

Date: 2026-09-01

The focused executable test starts the production PHP router against an
isolated canonical v1-v8 MariaDB database. Its coherent working-case fixture
has exact legacy/order planned dates, a registered installer, active assigned
engineer `7302`, and a distinct active engineer `7301` whose sole relevant
permission is `inspection.item.complete`; `7301` has neither `checklist.edit`
nor current assignment.

Through the public endpoint it requests
`GET /pilot/construction-control/objects/4512/sync-context`. The approved result
is HTTP 200 with a CSRF token and revision zero. Current production returns the
legacy-policy HTTP 403. The exact 403 after healthy canonical migration and
coherent object-card projection is the qualifying behavior RED, not setup
failure.

```text
$ tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
TestFailure: Unassigned active exact-capability engineer obtains offline sync context.
Expected: 200
Actual: 403
RED_ASSERTION: expected failing behavior observed
```

```text
2e2717a1635650966f17a472c0d6d2a6ca0f03dc6112f8f7273d1a6f6a65b64b  tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
```

The private database, HTTP process, artifact root, Compose container, network,
and volumes are removed after the run. Production was not edited.
