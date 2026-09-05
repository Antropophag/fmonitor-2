# Registry matrix Gate3 corrections — v2 RED

Дата2026-09-05. Author `/root`. Base9ba6931.
Review v1 CHANGES_REQUESTED сохранён:
`reviews/tests/ASSIGNMENT-ORDER-IDENTITY-REGISTRY-001-matrix-v1.md`.

Все три findings исправлены в schema test до production implementation:

1. Invalid prefix26 передаётся уже закрытому extra mysqli connection. Только
   exact InvalidArgumentException приемлем; любой DB access приводит к иному
   failure, поэтому absence of mutation больше не подменяет before-access proof.
2. Historical source order IDs и referenced case IDs получают отдельные fixtures
   для0 и9223372036854775808. Для case fixture ссылка order остаётся matching,
   поэтому case overflow не сводится к отсутствующему case. Conflict должен
   произойти до любого target DDL/DML с полным source/catalog preservation.
3. До missing-engine assertion исполняется public fixture cleanup control:
   outer-owned database с похожим именем и similarly named account/grants
   переживают inner fixture.close; owned account/schema исчезают. Только после
   наблюдения сохранности outer owner удаляет свои exact decoys. Нет cleanup
   по wildcard/age/dangling и нет обращения к primary evidence/production data.

Command: `php tests/InstallationProcess/assignment_order_identity_registry_schema_001_test.php`.
Exit1. Exact failure после успешного decoy control и нового DB setup:

```text
RED_ASSERTION: registry schema engine is missing
Expected: true
Actual: false
```

PHP lint и git diff --check PASS. Tracer/recovery/concurrency/worker/helper bytes
не менялись. Остальные underlying branches по-прежнему gated за отсутствующим
public engine; observed negative branch results не выдумываются. Нужен fresh
independent matrix Gate3. Engine code ещё отсутствует; новые продуктовые решения
или ранее отклонённые safe-log mechanisms не затронуты.
