## Context

См. `proposal.md`. Этап 2 #258 уже отделяет живую текущую projection от immutable daily observations, но dashboard визуализирует только observations внутри шестинедельного окна. Прогноз должен быть live read model: запись будущих «снимков» сделала бы изменяемые планы ложной историей.

## Goals / Non-Goals

**Goals:**

- один владелец интервалов и недельной классификации в `app/Workforce`;
- bounded прогноз на шесть недель и авторизованный drill-down;
- fail-safe UNKNOWN и независимость от observation scheduler;
- сохранение существующей исторической диаграммы без пересчёта её точек.

**Non-Goals:**

- автоназначение, оптимизация бригад, отпуска/квалификации, финансовая производительность;
- запись forecast rows, backfill observations или изменение assignment/PTO writers;
- изменение calendar, checklist, inspection и общего shell;
- исправление отдельно обнаруженного transport/bootstrap дефекта capture-job в этом change.

## Decisions

### Отдельная forecast projection поверх общего assignment owner

Новый read owner использует те же latest application, compatibility fallback, effective object details и PTO facts, что installer directory, но формирует нормализованные интервалы один раз и классифицирует их по шести неделям. Контроллер и view получают DTO и не повторяют SQL/JSON/domain rules. Альтернатива — строить прогноз из observation history — отвергнута: история не содержит будущих интервалов и не должна изменяться вслед за планом.

### Недельная семантика — пересечение интервалов

Неделя считается занятой, если закрытый или открытый справа authoritative интервал пересекает хотя бы один её день. Это ловит старты/освобождения в середине недели; снимок только на понедельник скрыл бы такую нагрузку. `releasing` и `conflict` остаются overlay-показателями, поэтому базовый denominator проверяется формулой `busy + free + unknown = employed`.

### Консервативная неизвестная граница

Неизвестное окончание текущей работы означает занятость до конца горизонта, а не UNKNOWN/free. Неизвестное начало или неоднозначный authoritative состав не позволяет построить интервал и даёт UNKNOWN либо глобальную unavailable при системной неполноте. Это предотвращает опасную ложную доступность.

### Эффективные даты без новой persistence

Плановые даты читаются через существующие effective-detail/deadline owners; factual start и PTO имеют приоритет. Forecast ничего не сохраняет. Drill-down пересчитывается на чтении и поэтому явно является текущим прогнозом, в отличие от saved historical detail.

### Публичные seams и UI

Dashboard остаётся `/pilot/dashboard`; detail получает закрытый маршрут `/pilot/dashboard/installers/forecast/{weekStart}/{bucket}`. Тот же authorization guard применяется до чтения counts/PII. Используется server-rendered `shlz-ui` grouped chart с локальным CSS; JavaScript chart dependency не вводится.

### Boundaries

Owning module — `app/Workforce`; разрешённые зависимости — application read owners effective object details/queue dates и Yii DB adapters. Persistence owner отсутствует, потому что forecast read-only. `rapid-pilot` не загружается и не расширяется. Architecture/runtime closure checks должны закрепить эти границы.

## Risks / Trade-offs

- [Плановая дата окончания не гарантирует фактическое освобождение] → явно подписать прогноз как плановый и показывать основания/UNKNOWN.
- [Тяжёлый JSON/assignment query на 937+ людях] → bounded bulk queries, один проход по интервалам в памяти, query-count test для 50/125/1000 identities; никаких per-row SQL.
- [Одновременное изменение фактов между dashboard и detail] → каждый запрос консистентен сам по себе и показывает live forecast; immutable identity для forecast намеренно не обещается.
- [Перегруженный график на mobile] → компактные группы, текстовая легенда и локальная горизонтальная прокрутка без page overflow.

## Migration Plan

1. Выпустить read-only код и маршруты без schema migration.
2. Проверить focused public-seam, authorization, performance/runtime closure и browser tests.
3. Развернуть после exact-source CI и independent review; rollback — возврат предыдущего image, данные не мигрируются и не теряются.
