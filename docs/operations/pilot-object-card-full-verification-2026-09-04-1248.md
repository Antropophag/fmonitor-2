# Object-card integration full verification — 2026-09-04 12:48 MSK

Candidate: `66438c962127b685760fc2d10e0a12497daca81c`  
Command start: `2026-09-04T12:48:00+03:00`  
Result: **FULL_VERIFICATION_FAILURE / no VERIFY_OK**

```text
git diff --check
make verify

FULL_VERIFICATION_FAILURE count=4 stages=unit-test,db-test,characterization-test,e2e-test
```

The object-card integration itself remained GREEN inside the complete run:

```text
PASS: PILOT-OBJECT-CARD-001 public HTTP card
PASS: PILOT-PREPARE-FORM-001 public HTTP read-only form
PASS: ProductionPdfAssignmentOrderRenderer semantic contract
PASS: ARTIFACT-STORE-001
PASS: PRODUCTION-COMPOSITION-001
```

Reset, migrations v1..v11, architecture 7/7, lint and diff-check passed.
Compared with the earlier prepare verification, the host TCPDF/artifact/
composition failure class is no longer present.

Remaining failures are separately owned predecessor/integration classes:

- intended navigation-removal RED in focused/root/object-list tests;
- UI-shell and E2E assertions still using superseded presentation contracts;
- checklist/session sequential isolation and local-auth/user-access failures;
- rapid auth-hot-path constructor availability and visual-contract projection.

This record proves the full command and failure classification. It does not
claim repository-wide GREEN, hermeticity, CI readiness or `VERIFY_OK`.
