# OTIZ-SETTLEMENT-001 — bounded migration and replay core review v2

- Date: `2026-09-09`
- Reviewer: separately tasked agent `/root/settlement_review`
- Reviewed exact production candidate: `9ec3f6c1132415536eb0efe3e9285b07ddd49e4d`
- Gate 4 baseline: `42001adc2bb8f2d89471f9d587199288e41fad40`
- Approved authorization RED: `246b6860d6462368fd91f2cc593235062edab95d`
- Scope: canonical v24 settlement schema and replay/authorization core
- Verdict: **APPROVED for this bounded core increment**

The v1 blocking authorization finding is closed. `execute()` sets `admitted`
only after current authority succeeds. A `FORBIDDEN` raised by admission is
rolled back and rethrown without reading or disclosing a prior receipt. A domain
failure after successful admission may still recover a concurrently committed
receipt with the same fingerprint, preserving the approved concurrent exact-
replay behavior; another fingerprint remains `OPERATION_CONFLICT`.

The correction is minimal and does not change budget, reversal, no-op, fact, or
schema behavior. The approved revoked-replay test proves `FORBIDDEN` and exact
closure/event/receipt count preservation. The deterministic concurrent test
proves authorized same-UUID recovery still returns two identical successes and
one reversal.

Independent exact-candidate verification:

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

No blocking finding remains in this bounded core. This approval is not final
Gate 5 for the complete settlement delivery. HTTP admission/parsing, compatibility
adapter and old-writer removal, all final changed entry points, their focused
regressions, and one exact-source full CI remain open.
