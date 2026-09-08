# Full verification at `d372a9c`

Date: `2026-09-04`

Command used the explicit installed PHP/Docker paths and the disposable test DB
root credential (redacted in this record), then ran `make verify`.

Terminal result:

```text
FULL_VERIFICATION_FAILURE count=3 stages=unit-test,db-test,e2e-test
```

Passing stages:

```text
VERIFY_STAGE test-db-reset PASS
VERIFY_STAGE migrate PASS
VERIFY_STAGE architecture-check PASS
VERIFY_STAGE lint PASS
VERIFY_STAGE characterization-test PASS
VERIFY_STAGE diff-check PASS
```

The previous characterization failure stage is now completely GREEN. HTTP auth
and object-card also pass natively on macOS after their independently approved
test-only portability corrections.

All remaining failures are already gated work:

- unit has exactly the two intended assignment-order-original upload RED
  verifiers, both stopping on the absent production verification factory;
- DB has the owner-blocked legacy `PILOT-E2E-FLOW-001` directly and through its
  demo-bootstrap wrapper;
- E2E has only that same blocked legacy test.

The blocked legacy target was not edited. Original-upload task 2.2 is awaiting
the newly identified checklist evidence-field Gate 1 amendment before its RED
matrix can be completed.
