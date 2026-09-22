## Context

`MariaDbYiiObjectQueueProjection` уже сворачивает `item_completed`/`completion_retracted` по `accepted_revision,id` для отображаемого прогресса. `MariaDbYiiObjectQueue::query()`, `stagePredicates()` и `MariaDbYiiOperationalDashboard` вместо этого считают исторические distinct completion rows. Это одна read-side семантическая ошибка; запись и история корректны.

## Goals / Non-Goals

Goals: единая current-state семантика для card/row/filter/count/pagination/dashboard, минимальная SQL-коррекция, сохранение всех действующих stage rules.

Non-goals: writers/offline/history, миграции/materialization, #171, редактор/фото/ОТиЗ/readiness, документы, плановые сроки/справки, редизайн и новые метрики.

## Decisions

1. Переиспользовать одно SQL-выражение активного количества пунктов: выбрать только последнюю меняющую completion операцию на `(case,item)` по `accepted_revision DESC,id DESC`, затем считать строки с типом `item_completed`, исключая item 42. Это тот же смысл и порядок, что у текущей queue projection; второй самостоятельный алгоритм не создаётся.
2. Использовать выражение и в обычных status predicates, и в `stagePredicates()`/dashboard. Фильтрация остаётся внутри SQL до aggregate/pagination.
3. Documentary completion (`pto_act` + `declaration`) сохраняет приоритет завершённого этапа; correction не пишет case state и не переоткрывает дело. `needs_assignment_change` сохраняет отдельный приоритет.
4. Тест проходит через реальные fixture checklist operations и публичные queue/dashboard owners; произвольный stage status не выставляется.

## Risks / Trade-offs

- Correlated current-row selection может быть дороже исторического distinct count. Срез не добавляет схему или индекс: проверяем bounded query count и существующий exact-source CI; отдельная materialization не оправдана.
- Исторические строки с равной revision разрешаются стабильным `id`, как в существующей проекции.

## Verification scope

Применимы queue/card/dashboard reads, pagination и соседние stage rules. Не применимы schema/deployment/backup ceremony (схема не меняется), mutation authorization/replay (writes не меняются), browser redesign и внешние adapters.
