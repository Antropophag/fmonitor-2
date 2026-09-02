# Classification provenance v11 integration correction evidence

Date: 2026-09-02  
Scope: Gate 5 integration correction requested by
`reviews/code/CLASSIFICATION-PROVENANCE-SCHEMA-001.md`.

## Correction

- Reconciled inherited canonical-runner results and catalogue inventories from
  terminal v10 to the landed terminal v11.
- Added the exact `fm2_migration_classification_provenance` table to independent
  literal catalogue fixtures.
- Reconciled `pilot_case_import_001_test.php` with v11 while retaining the
  canonical test credential contract; no credential is recorded here.
- Removed test-owned runtime creation of the provenance table after the
  canonical runner became its owner. Runtime fixtures now seed complete valid
  provenance rows into the landed v11 table.
- No production file was changed for this correction.

## Focused verification

The following pass against the canonical test database:

- `production_migration_runner_001_test.php`
- `workforce_canonical_runner_001_test.php`
- `pilot_case_import_001_test.php`
- `verify-calendar-projections.php`
- `harness_otiz_canonical_compat_001_test.php`
- inherited v7-v10 schema runner fixtures
- `inspection_item_complete_001_mariadb_test.php`
- both inherited runtime-DDL fixtures

`make architecture-check` passes all 7 rules, strict OpenSpec validation passes,
and `git diff --check` is clean.

## Full verification classification

The post-correction `make verify` run reaches terminal v11 and all
classification/canonical-runner/runtime-DDL checks pass. It remains non-green:

- unit: approved intentional RED for session storage and removal of the
  `Моя работа` navigation item;
- DB/E2E: the approved RBAC/navigation work in progress still fails at actor 18
  admission and dependent pilot HTTP/UI fixtures;
- characterization: PASS;
- diff check: PASS.

No remaining failure in that run is a terminal-v11 catalogue or classification
provenance integration failure. A fresh independent Gate 5 review is required;
this correction record does not approve its own work.
