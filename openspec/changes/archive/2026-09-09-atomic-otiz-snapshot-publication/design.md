## Context

См. proposal.md и ADR0002. A01 подтверждён чтением кода, RED ещё не выполнен.
Действующая schema имеет draft/accepted и hash pending; enum расширять не требуется.

## Goals / Non-Goals

**Goals:** один владелец build/publication и admission при принятии; атомарность
наблюдаема с другого DB connection и через текущий HTTP.

**Non-Goals:** см. proposal.md. Принятие не становится автоматическим; финансовый
остаток между срезами и новое округление в этот перенос не входят.

## Decisions

Владелец `app/Otiz`. Публичный интерфейс:
`SnapshotPublication::buildAndPublish(actor, reportDate, operationId)` возвращает
snapshot identity либо предметный отказ. `accept(actor, snapshotId)` сохраняет
существующие outcomes плюс `SNAPSHOT_INCOMPLETE`. Чтение результата — через
публичную query-поверхность ОТиЗ. HTTP проверяет CSRF, преобразует input/result;
проверка действующего `otiz.manage` остаётся внутри application operation.

Одна транзакция MariaDB с явно установленным REPEATABLE READ и START TRANSACTION
WITH CONSISTENT SNAPSHOT для чтения native inputs,
прошлого accepted и closures; она принадлежит операции, не caller. В ней строятся
и записываются header, objects, allocations, issues, totals, hash и audit. Внешние
вызовы и DDL в транзакции отсутствуют. Любой Throwable до commit вызывает rollback.
Никакого глобального lock всего набора объектов для публикации.

Additive publication receipt имеет unique snapshot identity и unique actor/operation
identity, fingerprint запроса (дата/версия команды), версию manifest, counts и digest
полного сохранённого расчёта, actor/time. Manifest включает header business fields,
входы/версию правил, objects, allocations и issues в устойчивом порядке; mutable
accepted fields исключены. Он не заменяет существующий content_hash и формулы.
Receipt записывается последним в той же транзакции. При принятии под snapshot lock
проверяются receipt, counts/digest, финальный hash и отсутствие открытых blockers.
Counts/digest повторно вычисляются из реально сохранённых DB rows, не из копии
manifest в receipt. Objects сортируются по object_id, allocations по object_id/tab_id/id,
issues по object_id/id; типы чисел/NULL и сериализация задаются версией manifest и
одинаковы на записи/чтении. Точные golden bytes фиксируются в нормативной spec до RED.
Это предотвращает принятие оборванного старого draft и структурно неполного нового.

Повтор build с тем же actor/operationId и fingerprint возвращает тот же snapshot;
другая дата с тем же ключом — OPERATION_CONFLICT без записи. Unique key сериализует
конкурирующие повторы; проигравшая транзакция откатывается и читает committed receipt.
Новая явно начатая операция может создать новый полный draft на ту же дату — здесь
не вводится уникальность даты и не решается A02. Потеря ответа после commit разрешается
повтором исходного operationId; UI сохраняет ключ до разрешения результата.

Native input readers и чистые формулы переносятся под владельца; legacy verify
callers получают обёртки при необходимости. Dependency direction: HTTP → Otiz;
Otiz → authorization/read/storage adapters; правила не зависят от rapid-pilot.
Перечень удалений и оставшихся adapters — ADR0002. Checker проверяет новые пути
и достижимый из HTTP migration tooling; baseline нельзя расширять ради GREEN.

## Risks / Trade-offs

- Длинная транзакция → измерить обычный build, не держать глобальные write locks;
  фоновый building lifecycle — отдельное изменение при доказанной необходимости.
- Legacy draft без receipt → сохраняется для чтения, принятие отвергается с
  предложением подготовить новый расчёт. Accepted история остаётся без изменения.
- Повтор после потери ответа → durable operation identity; безопасный replay,
  без повторной публикации или события.
- A02/A03 → явно открытые отдельные риски, не обещать финансовую готовность.

## Migration Plan

Каноническая additive миграция создаёт receipt; фактический номер выбирается на
актуальном frontier. Переиспользовать planning/evidence существующих evidence/quarantine changes,
но зарегистрированных канонических миграций для двух decision ledgers сейчас нет:
создать additive migrations для fm2_migrated_evidence_decisions и
fm2_migration_quarantine_decisions и всего необходимого projection schema.
Проследить ensureSchema также из rebuildDecisionState и всех сохраняемых routes;
удалить достижимый runtime DDL после readiness-гарантии. Собственный
RapidPilotOtiz::ensureSchema уже только проверяет readiness и сохраняется.
Legacy accepted не backfill-ить выдуманным подтверждением; drafts тоже не сертифицировать.
Сначала изолированный тестовый контур, затем reviewed кандидат и полный CI.
Переключение рабочего стенда отдельным эксплуатационным шагом с сохранением
данных. Откат к старому accept возвращает A01, поэтому безопасный rollback сохраняет
запрет принятия incomplete; additive таблицы/историю не удалять.
