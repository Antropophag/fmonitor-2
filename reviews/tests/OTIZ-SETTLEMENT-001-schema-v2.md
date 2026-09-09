# OTIZ-SETTLEMENT-001 — canonical settlement schema Gate 3 v2

- Date: `2026-09-09`
- Reviewer: separately tasked agent `/root/settlement_review`
- Test author: executor `/root/settlement`; reviewer authored neither test nor production
- Reviewed exact candidate: `0b802126fcd420bad74bf3c9bca4d6f48037940f`
- Correction baseline: `2b0ca215cb685bb1c0e1162f3bc5e8b343bd1b60`
- Public seam: `CanonicalMigrationApplication::run()` with the production catalogue
- Verdict: **APPROVED**

The v1 predecessor finding is closed. The corrected test creates an isolated
canonical v23 database, inserts a representative legacy payment closure,
captures its complete row, and runs the full catalogue. It requires terminal
v24 with `appliedVersions` exactly `[24]` and byte-equivalent decoded closure
history afterward. This is separate from the retained empty clean run,
populated compatible repeat, and incompatible-schema conflict cases.

The v24 expectation is independent of implementation: the production catalogue
is contiguous through v23 and the approved additive settlement migration is its
next successor. The test uses the canonical deployment application seam and
disposable databases; cleanup drops both clean and predecessor databases.

Author evidence records exit `255` at the intended missing catalogue entry:
expected `OtizSettlementSchemaMigration::class`, actual `NULL`. This is a valid
first missing-behavior RED; downstream lifecycle assertions are present and
will qualify the minimal implementation in GREEN. The plan check was
independently rerun on the frozen candidate and returned literal
`CHANGE_VERIFICATION_OK`. `git diff --check` passed.

Reviewed identities:

```text
9132621a9ebf94efc676f78f321e37590ba09a7446e9432644b4b49fee382305  tests/InstallationProcess/otiz_settlement_schema_001_test.php
d324f88ccb5d29340ab43d3b632c247db89b15f690b1a40c9a3f79d474dfef1f  docs/operations/otiz-settlement-red-evidence-2026-09-09.md
```

Gate 3 for the canonical schema lifecycle increment is approved. Gate 4 may
implement the minimal compatible v24 migration and catalogue registration
without changing these expectations. Focused GREEN/regression evidence,
independent Gate 5, and one exact-source full CI remain required.
