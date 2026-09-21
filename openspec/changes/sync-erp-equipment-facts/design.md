## Context

См. `proposal.md` и `specs/erp-equipment-facts/spec.md`. Legacy evidence ограничено двумя прямыми MS SQL BI-запросами и чтением staging projection по точному номеру заказа; legacy truncate/reload не переносится. В FMonitor 2 уже существуют durable queue, hourly scheduler process, worker dispatch, schema migrations и декоратор карточки.

## Goals / Non-Goals

**Goals:**

- Один owner в `InstallationProcess` принимает полностью нормализованный batch и владеет projection/history/run metadata/diagnostics.
- Тонкий ERP adapter выполняет только подтверждённые read-only запросы и нормализацию.
- Существующие scheduler и worker вызывают canonical sync composition раз в час.
- Disposable-DB regressions проверяют поведение через public integration/application seam.

**Non-Goals:**

- Универсальный integration framework, scheduler, outbox или event sourcing; issue #135 и его verification inventory WIP.
- Изменения процесса, карточки сверх компактного блока фактов, issues #13/#16/#30/#11, email или `rapid-pilot`.
- Реальный production fetch, deployment scheduling, merge или deployment в рамках проверок.

## Decisions

1. **Source snapshot формируется двумя bounded queries.** Order query возвращает `sale.Номер`, `MAX(ДатаКомплектности)` и `MAX(ДатаПолнойОтгрузки)`; stage query возвращает номер заказа и `ДатаОтгрузки` только для неудалённых этапов лифтового оборудования. Adapter агрегирует минимальную валидную shipment date и объединяет записи по exact order number. Альтернатива — копировать legacy staging — отклонена как лишняя архитектура и небезопасный truncate.

2. **Присутствие order record задаёт authoritative scope.** В присутствующей записи SQL `NULL` является explicit clear. Отсутствующий order number не является clear. Любая ошибка query, невалидная форма, конфликтующие дубли или невозможность завершить оба query делают весь fetch неприменимым. Это минимально отличает source correction от технической неполноты без дополнительного протокола источника.

3. **Один transactional application owner.** Единственный `execute` принимает discriminated `complete|failed` command. Owner сначала валидирует весь command и authorization, затем в одной MariaDB transaction блокирует run/object rows; complete разрешает exact unique mapping, сравнивает три факта, добавляет только реальные переходы и обновляет projection/metadata, а failed пишет только terminal run status. Любая persistence failure откатывает весь batch. Unmatched/ambiguous records получают allowlisted diagnostic; raw order number и source payload не сохраняются.

4. **Минимальная persistence-модель.** Одна current projection row на объект, fact-change history, sync runs, singleton last-success metadata и bounded diagnostics. Новая migration подключается к существующему production schema catalogue; generic tables не создаются.

5. **Используется существующий jobs runtime.** Добавляется equipment-facts hourly scheduler sibling существующих scheduler-ов и версионированный handler branch. Scheduler process вызывает его каждый tick; canonical CLI/job composition создаёт ERP adapter и owner. Нового cron нет. Пропущенные slots не догоняются.

6. **Карточка только читает projection.** Существующий native card projection добавляет компактный `equipmentFacts` block с тремя nullable dates, `source`, `lastSuccessfulSyncAt` и status. Он не участвует в process-state вычислениях. `rapid-pilot` adapter отсутствует и не требуется. Architecture check должен подтвердить единственного writer-owner и отсутствие новых запрещённых зависимостей.

## Risks / Trade-offs

- [Order отсутствует из-за логической фильтрации источника и потому не очищается] → безопасный stale факт предпочтительнее потери данных; freshness/status делает состояние видимым.
- [Два source query видят разные моменты] → batch применяется только после успеха обоих; согласованный DB snapshot зависит от возможностей read-only ERP connection и остаётся operational UNKNOWN до live qualification.
- [Дубликаты `zavnumber`] → никакого произвольного выбора; diagnostic и zero write.
- [Неверный sentinel/date] → strict date parsing и единственный подтверждённый sentinel `0001-01-01`; прочее делает batch invalid.

## Migration Plan

1. Применить additive schema migration.
2. Развернуть adapter/config и worker handler с выключенным реальным fetch до наличия production credentials.
3. Запустить focused disposable-DB verification и exact-source CI.
4. При deployment включить существующие scheduler/worker processes; первый hourly run заполнит projection.
5. Rollback кода прекращает новые записи; additive tables и история сохраняются. Удаление данных не является частью rollback.

## Open Questions

- Live connectivity, read-only credentials и поддержка согласованного snapshot в production остаются operational qualification и не меняют контракт или task breakdown.
