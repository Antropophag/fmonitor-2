# Independent test review — migration 18 collation regression

Verdict: **APPROVED** for the bounded migration-18 collation regression.

Reviewer: `/root/bootstrap_review` (independent from the
`/root/migration18_collation` author). Review date: 2026-09-08 Europe/Moscow.
Source HEAD at review: `8c0cc63f0454b0a620dafa399d5d2462d8447feb`.

Reviewed exact inputs:

- `tests/InstallationProcess/assignment_order_unknown_employment_schema_collation_001_test.php` — SHA256 `41b360c7d02423006722f21c23c2bd3d3e855e9f4a75093820668e8fbeaa8ec9`.
- `reviews/tests/MIGRATION-18-COLLATION-REGRESSION-2026-09-07.md` — SHA256 `db73eaa5ba48906895674f22bd97c3a20067e4b764d9851fd196c9ec1cf6edd5`.

The regression uses the public migration catalogue for the canonical v17
predecessor, then calls the public migration-18 seam. It covers both
`utf8mb4_general_ci` and the reproducing `utf8mb4_bin` database default and checks
the meaningful lifecycle: predecessor not ready, first apply succeeds, readiness
recognizes the result, and repeat apply is a no-op. Each case owns a random isolated
database and cleanup removes only those exact databases.

Independent execution:

```text
$ php tests/InstallationProcess/assignment_order_unknown_employment_schema_collation_001_test.php
ASSIGNMENT_ORDER_UNKNOWN_EMPLOYMENT_SCHEMA_COLLATION_001_OK

$ php tests/AssignmentOrderComposition/selection_unknown_employment_manual_pilot_test.php
selection_unknown_employment_manual_pilot_test: PASS
```

The author evidence now explicitly distinguishes the injected private diagnostic
result from the unmodified provisioning path and makes no whole-bootstrap claim.
