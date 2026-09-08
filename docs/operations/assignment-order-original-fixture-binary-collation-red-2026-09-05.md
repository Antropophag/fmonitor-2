# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — fixture binary collation RED

Date: `2026-09-05`

RED author: separately tasked agent `/root/assignment_original_red2`

Gate 5 v3: `12fca973ab0a9eb997607a63259df783aaf62c4f`

Outcome: **INTENDED RED — collation-equivalent drift accepted by seed**

The fixture verifier now stores and compares `HEX(CAST(column AS BINARY))` for
every generated non-identity field mutation before either public fixture call
and after literal restoration. This makes the all-field matrix an octet oracle,
not a database-collation oracle.

Additional utf8mb4 cases cover trailing U+0020 in user/order/installer text,
case-equivalent role/task values, composed U+00E9 and decomposed U+0065 U+0301
email substitutions. Every value is byte-distinct, yet potentially equal under
`utf8mb4_unicode_ci`; both seed and cleanup must throw fixed conflict and leave
the full database byte-identical.

Current production accepts the first trailing-space user drift, reproducing the
review finding. Task 2.2 is checked; 2.3 and 3.1 are reopened. No production or
specification artifact changed.

```text
$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
Fatal error: Uncaught TestFailure: INTENDED_RED: trailing-space user was collation-accepted by seed.
RED_ASSERTION: expected failing behavior observed
$ independent SCHEMATA/PROCESSLIST query
NO_AOOU_DATABASE_OR_CONNECTION_LEAKS
$ php -l tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
No syntax errors detected
$ git diff --check
PASS (no output)
```

```text
c439aabf1c1f9ec57b945290661d50900c567b738e34f10cdfd80852ce8c36d2  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
bc7c6a43f558574e73888e2e14ec504001360dbcb5eb4cd98ddb4c4372122856  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
79798a1a79aae370daa4154582e1ca5c034840558376766b5b71ac01cde26ce5  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v3.md
f19bca46b2334e482e079c95fd856754b45f4151fba1636ee82041c0356b9d26  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
```
