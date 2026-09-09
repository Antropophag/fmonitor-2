# OTIZ-SETTLEMENT-001 — canonical v24 test setup amendment

- Date: `2026-09-09`
- Reviewer: separately tasked agent `/root/settlement_review`
- Reviewed exact candidate: `42001adc2bb8f2d89471f9d587199288e41fad40`
- Baseline: `7252600d`
- Verdict: **APPROVED**

The test delta only replaces canonical-v23 setup followed by direct
`OtizSettlementSchemaMigration::apply()` with the production canonical-v24
catalogue setup. It removes the obsolete migration-class RED assertion from the
owner suite. No authorization, budget, replay, reversal, concurrency, rollback,
fact-count, or expected monetary value is changed.

This makes the owner and concurrency fixtures consume the approved deployment
seam exercised by the schema test. Setup remains isolated in disposable
databases and fails explicitly unless the catalogue reaches v24.

Independent verification on the exact candidate:

```text
python3 tools/delivery/change-verification.py check --plan .local/verification/otiz-settlement-owner-plan.json
CHANGE_VERIFICATION_OK

php tests/InstallationProcess/otiz_settlement_schema_001_test.php
PASS: OTIZ-SETTLEMENT-001 canonical v24 clean repeat and conflict

php tests/Otiz/settlement_owner_001_test.php
PASS: OTIZ-SETTLEMENT-001 A02 settlement, replay, denial and reversal

php tests/Otiz/settlement_concurrency_001_test.php
PASS: OTIZ-SETTLEMENT-001 concurrent financial basis, reversal and rollback
```

`git diff --check 7252600d..42001adc` passed. The setup-only test amendment is
approved and does not restart Gate 2 for the unchanged business expectations.
