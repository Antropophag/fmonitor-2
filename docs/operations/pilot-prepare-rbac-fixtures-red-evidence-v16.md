# PILOT-PREPARE-RBAC-FIXTURES-001 — Gate 2 sensitivity evidence v16

Дата: 2026-09-04  
Автор: independently tasked agent `/root/prepare_v15_red`  
Current head: `3e1ea2e5a5cf84345177d8dc87828fea3464223d`  
RED baseline: `02e16bbcbe0c667e62634801a73f5fed88171dce` (parent of production correction `2935710`)  
Verdict: **QUALIFYING RED / CURRENT GREEN / fresh Gate 3 required**

## Added sensitivity

- Public MariaDB fixture places 503 valid ineligible rows before eligible tail
  `999998`; GET/HEAD must return all exact eligible IDs `1042,2088,999998`.
  A later structurally invalid `1000000` row must produce redacted 503 even
  though it is ineligible, proving no LIMIT/truncation hides integrity facts.
- Client harness executes exact mixed-provenance association: exact row IDs,
  attributes, cardinality, order and text; dynamic result inserts associated
  provenance after name/details; redundant no-JS list hides only after valid
  atomic initialization. Mismatched association retains fallback/list, hidden
  opener and zero hidden IDs.
- Six-field inert template grammar and homogeneous path remain unchanged.

Task 2.2 reopened. Production was not edited.

## Exact correction RED

Detached `~/code/fmonitor-2-prepare-v16-red` used exact `02e16bb` plus only
current test/support/task diff:

```text
2026-09-04T10:57:04+03:00
node tests/InstallationProcess/support/pilot_prepare_picker_client.js app/PilotHttp/picker.js
Error: validated mixed provenance fallback list hidden after initialization:
expected true, actual false
# exit 1

php tests/InstallationProcess/pilot_prepare_form_001_test.php
Error: validated mixed provenance fallback list hidden after initialization:
expected true, actual false
Expected: 0
Actual: 1
# exit 255; completed 2026-09-04T10:57:07+03:00
```

Это intended missing client association behavior, not setup failure. Diff был
reverse-applied, clean status verified, worktree removed/pruned, patch deleted.

## Current-head outcome

```text
2026-09-04T10:56:30+03:00
prepare picker client contract: PASS
PASS: PILOT-PREPARE-FORM-001 public HTTP read-only form
# both exit 0; completed 2026-09-04T10:56:40+03:00
```

Syntax, strict OpenSpec and diff checks passed.

```text
59552423291008f1fa9b42a33a5523a988522c8c8b1841c05d2496a410be7611  tests/InstallationProcess/pilot_prepare_form_001_test.php
aa0afd4453d208699919019c6133086b2bf1fae561b47280d36df1471db236a2  tests/InstallationProcess/support/pilot_prepare_picker_client.js
00e7265ea0d1d16dd50b4590cccf1358d8c99c5ce4b9d0448f108ba0c8ad5546  openspec/changes/pilot-prepare-rbac-fixtures/tasks.md
```

Fresh Gate 3 выполняет отдельно назначенный reviewer; это не self-review.
