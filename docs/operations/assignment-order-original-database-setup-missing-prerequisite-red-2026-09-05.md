# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — missing prerequisite setup RED

Date: `2026-09-05`

RED author: separately tasked agent `/root/assignment_original_red2`

Gate 5 v2: `f4bdb56aa446148a69b3044bf565228bbdef0f3d`

Production under test: `edbae87a46ff9d9abf0bda98dd411a6f4ba28aa5`

Outcome: **INTENDED RED — missing capability prerequisite mutates seven tables**

Every normal clean/repeat/leading-partial/populated/conflict axis now installs
exact V4 `fm2_process_user_capabilities` before calling the original migration.
The alternate `near_` and `check_` prefixes do the same, so their expected
manifest/affected ordering remains coherent with V14.

Both setup verifiers add a separately isolated missing-prerequisite axis. It
requires `CONFLICT`, exact affected list `['fm2_process_user_capabilities']`
and a full byte-identical zero-DDL schema snapshot. Current production instead
returns `APPLIED` and creates all seven original tables, which is the intended
behavioral RED.

Task 2.2 is checked. Tasks 2.3 and 3.1 are reopened for fresh Gate 3 and corrected
GREEN; 3.2 remains open. No production or specification artifact changed.

```text
$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
Missing prerequisite capability table conflicts before DDL.
Expected: CONFLICT ['fm2_process_user_capabilities']
Actual: APPLIED [all seven original tables]
RED_ASSERTION: expected failing behavior observed

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
Missing prerequisite capability table conflicts.
Expected: CONFLICT ['fm2_process_user_capabilities']
Actual: APPLIED [all seven original tables]
RED_ASSERTION: expected failing behavior observed

$ independent SCHEMATA/PROCESSLIST query
NO_AOOU_DATABASE_OR_CONNECTION_LEAKS
$ git diff --check
PASS (no output)
```

```text
58f791f67a4d3f207ff4b0aa4f6aa260b18f5a64ddd97bffacf8c190c8f513bf  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
6e8d35d624018a344ef923eba64a7cd0f1927e2205d924a5cac301dd7e3ff4ec  tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
bc7c6a43f558574e73888e2e14ec504001360dbcb5eb4cd98ddb4c4372122856  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
d364f820d32b21c57b832557f53b7d0334e77f641556060b4b7434e8a5669f50  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v2.md
f19bca46b2334e482e079c95fd856754b45f4151fba1636ee82041c0356b9d26  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
```
