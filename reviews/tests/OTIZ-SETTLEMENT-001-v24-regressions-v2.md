# OTIZ-SETTLEMENT-001 — canonical v24 regression amendment review v2

- Date: `2026-09-10`
- Reviewer: separately tasked agent `/root/settlement_review`
- Test amendment author: root
- Reviewed correction: `2a4d3e58`
- Prior review: `reviews/tests/OTIZ-SETTLEMENT-001-v24-regressions-v1.md`
- Verdict: **APPROVED**

Both v1 findings are closed. The inspection-item full inventory now adds exactly
`fm2_otiz_settlement_locks` and `fm2_otiz_settlement_operations`, preserving all
69 prior entries and updating the diagnostic to 71. The focused test passes both
existing- and missing-revision concurrency modes, demonstrating that the setup
amendment no longer blocks its original behavior.

The six identified current full-catalogue diagnostics now say v24. Searches find
no remaining changed current-v24 assertion labelled as v23. The explicit
settlement predecessor-v23 assertions remain intact; historical direct migration
versions were not mechanically advanced.

Evidence records the corrected focused command as exit0. Exact plan validation
returned `CHANGE_VERIFICATION_OK`, and `git diff --check
a38716ff..2a4d3e58` passed.

Reviewed corrected identities:

```text
e9bd32c92918c65e51a5ea20e09c988dc144c10834f910e587f3cc71d0a496b0  tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
40fe1752caf68df85b0e0d8efcb549c0d924abab379f5a8b48440204afaed0bf  tests/InstallationProcess/identity_access_schema_001_test.php
dfa9bef2a16fa143d9a3904185e8c718fad3085b32a4e150d45806f7723fd60e  tests/InstallationProcess/inspection_planning_schema_001_test.php
411fd57145d6f34acf7a9498c4128b1baac3bdf216abdd3c953dea1830bed011  tests/InstallationProcess/object_detail_snapshot_schema_001_test.php
4654fe2e0f405e1b4012ff7561908858cd0469d10bdc27030d6909e0bacf506b  docs/operations/otiz-v24-regression-amendment-2026-09-10.md
```

The current-frontier compatibility amendment is **APPROVED** as setup-only test
maintenance. Remaining recovery integration, production/browser GREEN, removal,
final Gate 5 and exact-source CI are not approved by this record.
