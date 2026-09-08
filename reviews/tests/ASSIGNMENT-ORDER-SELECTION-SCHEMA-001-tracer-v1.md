# ASSIGNMENT-ORDER-SELECTION-SCHEMA-001 — initial RED tranche

Reviewer: `/root/selection_initial_gate3`, новый `gpt-5.6-sol`, low, fork none.
Author: root. Verdict: **APPROVED — narrow initial RED tranche only**.
Reviewed clean HEAD: `b6eaf223bc64ed46585bbfe93056d5efb44a09c5`.

## Scope and findings

Public Selection/SelectionView facade совпадает с approved v0.2. Runtime expected
values — fixed spec literals (family/table hashes, names, counters, empty row hash,
registry receipt), без production constants. Тест чувствителен к missing facade,
неправильным result/readiness, five-table family/order/shape, domain rows,
counters, repeat, registry/source/catalog preservation и transaction leak.

Setup доказан до target action. Clean exact-SHA RED завершился exit1 только на
missing public migration. Cleanup пытается освободить owned fixture, проверяет
удаление exact schema и сохранение отдельно принадлежащего outer owner decoy.
Reviewer не менял test/support/code и не повторял выполненный запуск.

Approval не утверждает, что downstream assertions были выполнены в RED.
Оставшаяся mandatory matrix и полный Gate3 открыты; production implementation
этим tranche не разрешена.

## Evidence

Command: `/opt/homebrew/bin/php tests/InstallationProcess/assignment_order_selection_schema_001_test.php`.
Synthetic FMONITOR_TEST_DB_HOST=127.0.0.1, PORT=23306, ADMIN_USER=root,
ADMIN_PASSWORD — existing local synthetic fixture value. Timeout60s,
actual0.452s, clean before/after, terminal exit1.

```text
SELECTION_SCHEMA_REGISTRY_SETUP_OK
SELECTION_SCHEMA_OWNED_CLEANUP_AND_EXTERNAL_DECOY_OK
RED_ASSERTION: public assignment-order selection schema migration is missing
Expected: true
Actual: false
```

External archive:
`/Users/antropophag/.local/state/fmonitor2-verification/selection-schema-initial-red-3gzlqtpd`.

```text
3ad61df6c0c2de67432aa4e4961320919b0e8bd6898b34bd3e2d2378bf12bfa1  tests/InstallationProcess/assignment_order_selection_schema_001_test.php
b409eddac3fa05caf1283c7c2dbf96f36f7611f4bd1a86cb8fd4bbf06b89d02c  tests/Support/IdentityRegistryTestDatabase.php
63759ba4e889c0a3940ea15f60e4f747f48ce26c39165fe9dc67984682fe5bb1  specs/ASSIGNMENT-ORDER-SELECTION-SCHEMA-001.md
4b541fb55e065b99ffa015a3fcdc93f6237f3281132510464e70cdc49a9cadcb  evidence.json
a48d9edb65b23edc291b7823f55b66155a3e3390f02d9580b54cd65738a357bd  red.log
```
