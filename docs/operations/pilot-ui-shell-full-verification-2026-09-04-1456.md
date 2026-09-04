# UI-shell integration full verification — 2026-09-04 14:56 MSK

Candidate: `83bb495cfe92a9d34dbdf103ec82d01e71d64e81`  
Started: `2026-09-04T14:56:51+03:00`  
Result: **FULL_VERIFICATION_FAILURE / no VERIFY_OK**

```text
git diff --check
make verify

FULL_VERIFICATION_FAILURE count=4 stages=unit-test,db-test,characterization-test,e2e-test
```

Reset, migrations v1..v11, architecture 7/7, lint and diff-check passed. The
target and already integrated predecessors remained GREEN in the full run:

```text
PASS: PILOT-UI-SHELL-001 public UI shell
PASS: PILOT-PREPARE-FORM-001 public HTTP read-only form
PASS: ProductionPdfAssignmentOrderRenderer semantic contract
PASS: ARTIFACT-STORE-001
PASS: PRODUCTION-COMPOSITION-001
```

Remaining failures are classified outside the UI-shell production diff:

- intended navigation-removal transition in navigation/root/object-list/card;
- stale E2E/object-link and old HTTP-auth CSP expectations;
- checklist/session sequential isolation and user-access failures;
- rapid auth-hot-path constructor availability and rapid visual adapter drift.

This record does not claim repository-wide GREEN, hermeticity, CI readiness or
literal `VERIFY_OK`. Each remaining class returns to its own approved gate.
