# PILOT-OBJECT-CARD-001 — shared-shell fixture RED correction

Date: 2026-09-04

RED author: `/root/original_upload_red`

Base: `c48cbe8`

Verdict: **SETUP CORRECTED; existing presentation RED reached; fresh Gate 3 required**

## Scope and controlling evidence

```text
ec5d7b438c6696950e09397ae3b129c9890b9182636d27650127532d5d979732  specs/PILOT-OBJECT-CARD-001.md
014bf3f5726ef7913816ebb536a0b57946b1203e96809c2ecb14f49d4d0e3d19  reviews/tests/PILOT-OBJECT-CARD-001.md
a2ef54ff39f550be9b0ccd63d473147b08a894a4fa0573de0ad1b6f72cbf715a  reviews/tests/PILOT-UI-SHELL-001.md
```

The stale fixture composed only selected schema families and omitted the
configured shared-shell process prefix and pilot CSS. It therefore returned a
generic infrastructure 503 before any object-card behavior assertion.

The correction runs the canonical migration entrypoint on the owned database,
uses a non-empty exact `poc_` process prefix, installs local RBAC through the
existing fixture at that prefix, configures both SHLZ and pilot CSS from the
task-owned artifact root, and grants the SELECT-only HTTP principal only the
additional current read columns/tables used by shared identity, provenance and
card projection. No legacy authorization fallback is introduced.

User 19 has exact local `objects.read` admission and no process capability.
User 18 is a separate actor with exact process capability
`assignment_order.prepare`. An explicit complete-row assertion fixes this
separation before HTTP starts. The LocalRbacFixture prefix parameter is
backward-compatible and defaults to its previous empty-prefix behavior.

## Demonstration

Before correction, with the disposable MariaDB credential:

```text
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/pilot_object_card_001_test.php
Expected: 200
Actual: 503
exit 255
```

After correction:

```text
$ php -l tests/InstallationProcess/pilot_object_card_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_card_001_test.php
$ php -l tests/Support/LocalRbacFixture.php
No syntax errors detected in tests/Support/LocalRbacFixture.php
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/pilot_object_card_001_test.php
Example A broad reader without capability content-security-policy
Expected: default-src 'none'; style-src 'self'; img-src 'self'; font-src 'self'; ...
Actual: default-src 'none'; style-src 'self'; script-src 'self'; img-src 'self'; font-src 'self'; ...
exit 255
$ openspec validate remove-pilot-work-navigation-item --strict
Change 'remove-pilot-work-navigation-item' is valid
$ git diff --check
PASS (no output)
```

The test now reaches its own pre-existing presentation/CSP expectation instead
of infrastructure setup. That mismatch is not reclassified or changed here;
no new product assertion was added. All existing card content, authorization,
error, adversarial and zero-mutation assertions remain unchanged. Production
code was not modified, and this correction requires fresh independent Gate 3.
