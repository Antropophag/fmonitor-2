# CHECKLIST-TEMPLATE-SCHEMA-001 — GREEN verification

- Date: `2026-09-01`
- Specification: `CHECKLIST-TEMPLATE-SCHEMA-001 v0.1`, Gate 1 approved
- Approved test: `tests/InstallationProcess/checklist_template_schema_001_test.php`
- Result: `GREEN / READY_FOR_GATE_5_REVIEW`

## Focused evidence

```text
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/checklist_template_schema_001_test.php
CHECKLIST-TEMPLATE-SCHEMA-001 migration runner test passed
```

The same approved test hash has separately retained qualifying RED evidence
against landed predecessor `c57663d`: the canonical runner completed v1–v6 and
failed on the missing literal v7 expectation.

Relevant regressions:

```text
PASS: native checklist commands require and snapshot the cutover template
PASS active baseline operational case dry-run/apply/repeat provenance
ARCHITECTURE CHECK PASSED (7 rules)
```

## Full verification

`make verify` reached the established repository baseline:

```text
VERIFY_STAGE migrate PASS          schemaVersion=7, appliedVersions=[1..7]
VERIFY_STAGE architecture-check PASS
VERIFY_STAGE lint PASS
VERIFY_STAGE unit-test PASS
REGRESSION_FAILURE: 8 verifier(s) failed
VERIFY_STAGE db-test FAIL
VERIFY_STAGE characterization-test PASS
REGRESSION_FAILURE: 1 verifier(s) failed
VERIFY_STAGE e2e-test FAIL
VERIFY_STAGE diff-check PASS
FULL_VERIFICATION_FAILURE count=2 stages=db-test,e2e-test
```

The eight DB failures are the pre-existing pilot authorization/CSP failures.
The E2E failure is the pre-existing missing assignment-order artifact failure.
The earlier stale v6 catalogue failures were updated to the landed v1–v7
catalogue and now pass. No checklist-template v7 regression remains.

## Gate boundary

This evidence completes Gate 4 verification but does not self-approve Gate 5.
A fresh independent reviewer must inspect the specification, approved test,
production diff and this verification record.
