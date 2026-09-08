# Registry engine — Gate4 GREEN

Дата2026-09-05. Implementation author `/root`.
Exact implementation SHA: `b6f619f41e924c6ae2663d66e22de85cad30d6de`.
Working tree/HEAD clean и неизменны во время focused verification.

Gate1: `assignment-order-identity-registry-gate1-review-v01-2026-09-05.md`.
Spec SHA256: `31ffe9a297af927f030947e00cbbf9629fe2ebf98a312d222cdbf7bff3f1896f`.
Gate3: tracer v1 и full matrix v2 в `reviews/tests/`.
Все approved test bytes сохранены; production появилась только после Gate3.

## Реализованная граница

Public facade и verification composition вызывают один migration engine.
Registry/receipt metadata имеют отдельные definition, read-only preflight,
source capture, historical-subset verifier и transactional backfill delegates.
Typed snapshots/observer соответствуют approved declarations. Named lock
сериализует миграторы; registry frontier устанавливается до atomic rows+receipt
transaction, historical source rows/counters не меняются.

Все DDL/DML остаются у migration owner. Production facade фиксирует inert
observer; нет runtime env/HTTP selector. Canonical runner не изменён, migration
number не добавлен, existing writers не переключены. Это **engine-only**.

## Focused results на exact SHA

Каждая строка выполнена отдельным `php tests/InstallationProcess/<file>`:

| Test | Exit | Seconds |
| --- | ---: | ---: |
| assignment_order_identity_registry_001_test.php | 0 | 0.613 |
| assignment_order_identity_registry_concurrency_001_test.php | 0 | 5.866 |
| assignment_order_identity_registry_recovery_001_test.php | 0 | 2.240 |
| assignment_order_identity_registry_schema_001_test.php | 0 | 3.137 |
| migration_process_001_test.php | 0 | 0.783 |
| production_migration_runner_001_test.php | 0 | 5.938 |

Четыре registry scripts вывели свои exact `_OK` markers. Predecessor migrations
вывели PASS. Native process termination/transaction rollback, same-prefix5s lock
timeout, independent-prefix progress и exact decoy cleanup действительно
исполнены за formerly missing-seam assertions.

`make architecture-check`: PASS,7 rules, без baseline changes.
PHP lint всех19 новых production files: PASS; максимальный файл78 строк.
`git diff --check`: PASS.

## Найденное и исправленное в Gate4

Первый schema GREEN attempt обнаружил неполную visibility FK metadata для
SELECT-only principal: KEY_COLUMN_USAGE показывает2 references, а joined
REFERENTIAL_CONSTRAINTS скрывает rules. Engine ошибочно интерпретировал это как
schema conflict. Read adapter теперь проверяет полноту referential rows и
возвращает fixed DatabaseUnavailable при недостаточной visibility; отсутствие
authority не считается отсутствием FK. Approved expectations не менялись.

Первые direct predecessor invocations использовали старые demo defaults и
завершились Access denied до проверок. Это **setup failure**, не regression.
Оба raw logs сохранены. Повтор с exact canonical local test environment
(`fmonitor2_test`, соответствующие test user/admin credentials из run.sh)
прошёл без изменения code/tests. Reset или connection к production не выполнялись.

## Private primary evidence

Archive:
`/Users/antropophag/.local/state/fmonitor2-verification/registry-green-40lh7ud8`.
`evidence.json` содержит exact source/test hashes, stdout/stderr/exits/time,
clean before/after SHA; он сохраняет и первые predecessor setup failures.
`predecessor-canonical-env.json` содержит corrected environment outcomes.

```text
d99bd027224d5ce3877a93a04ce22052a74cbbcd1d3b162c7aea707d7103d531  evidence.json
feed109bcfecd8ec1dd17f99157998105d904fd6430f81349f309823729de01b  predecessor-canonical-env.json
```

Independent engine Gate5 ещё требуется. Full verify на этом SHA не запускался:
integration/registration не объявляются завершёнными. Последний full run на
060e880 сохраняет downstream protected E2E/card failure и отсутствие VERIFY_OK.
Ни writer cutover, ни parent selection Gate1/Done, CI/deploy/launch не утверждены.
