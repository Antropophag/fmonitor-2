## Context

См. [proposal.md](proposal.md). Сейчас документарное закрытие сохраняет ПТО и декларацию, а Yii-проекции вычисляют «Работы завершены» и 100%, не меняя `fm2_installation_cases.process_state`. Checklist owner допускает mutation только для `working`; OTIZ input reader также выбирает только `working`. Поле состояния — строковое и уже является process-owned фактом, а `fm2_process_events` служит append-only аудитом.

Рабочее дерево содержит параллельные пользовательские изменения; реализация обязана ограничить свой diff перечисленными владельцами и не переписывать посторонний WIP. `rapid-pilot` остаётся только oracle/adapter: новая доменная запись туда не добавляется.

## Goals / Non-Goals

**Goals:**

- один application owner завершает дело вместе с первой декларацией;
- состояние, документ и аудит имеют одну транзакционную судьбу;
- все активные Yii2-проекции и OTIZ одинаково понимают `completed`;
- существующие доказанно завершённые дела приводятся детерминированно и повторяемо;
- исправления документов сохраняют append-only историю после завершения.

**Non-Goals:**

- менять формулу прогресса или премии;
- исправлять семантику нулевой installer attribution;
- переносить паспорт объекта из `fm_maintable`;
- вводить обратный переход `completed → working`;
- добавлять доменную запись в `rapid-pilot`.

## Decisions

### 1. Владельцем перехода остаётся InstallationProcess completion owner

Первая успешная `record_declaration` выполняет под существующим case lock три операции в одной транзакции: вставляет корневой факт декларации, условно обновляет exact `working` case до `completed` и добавляет append-only `installation_completed` event. HTTP-контроллеры и проекции состояние не меняют.

Альтернатива — вычислять завершение при каждом чтении — отвергнута, потому что сохраняет исходную проблему и не создаёт единого запрета для writers. Отдельная кнопка «Завершить» отвергнута: при утверждённых правилах ПТО + декларация уже являются достаточным основанием.

### 2. `completed` — новое допустимое значение существующего строкового состояния

Текущая process schema хранит `process_state` как `VARCHAR`, поэтому отдельная колонка или enum не требуются. Перед реализацией schema-frontier test должен подтвердить отсутствие ограничивающего CHECK и инвентаризировать consumers с exact comparisons.

Альтернатива `completed_at` без смены состояния отвергнута: writers продолжили бы считать дело `working`. Время/actor перехода берутся из append-only event и документного факта.

### 3. Исправления отделяются от первичной записи

Первичная декларация разрешена только для `working` и завершает его. `correct_pto` и `correct_declaration` принимают `working|completed` при прежних capability и validation; для `completed` они добавляют только correction revision. Checklist writers продолжают требовать exact `working`, поэтому автоматически fail closed после перехода.

Альтернатива временно переоткрывать дело для correction отвергнута как ложная история и источник повторных checklist-команд.

### 4. OTIZ выбирает `working|completed`, но сохраняет все остальные фильтры

Native input query расширяет только process-state predicate. Cutoff операций и документных фактов остаётся владельцем исторической воспроизводимости, поэтому текущее `completed` не добавляет финальные 15% в срез за более раннюю дату. Распределение, уже выплаченное и `no_new_amount/completed` calculation states не меняются.

Альтернатива исключать полностью выплаченные дела на входе отвергнута: это ломает повторяемость срезов и историю уже учтённых сумм.

## Risks / Trade-offs

- [Частичный deploy исключит `completed` из старого OTIZ reader] → поставлять owner и OTIZ reader одним exact-source candidate.
- [Неучтённый consumer сравнивает только `working`] → verification plan и architecture search инвентаризируют все process-state predicates, очереди, checklist, completion, backup/restore и fixtures.
- [Correction ошибочно требует `working`] → отдельные публичные тесты исправления ПТО и декларации после `completed`.
- [Параллельная декларация создаёт два перехода] → существующий case lock, conditional state transition и один transaction owner.

## Migration Plan

1. Подтвердить schema frontier и добавить/обновить нормативный executable contract и RED-тесты публичных seams.
2. Реализовать owner transition, событие, read-model compatibility и OTIZ selection без runtime DDL.
3. Выполнить planner-selected focused checks, независимые reviews и один exact-source CI run.
4. Развернуть owner и OTIZ reader согласованно; проверить новое завершение и следующий OTIZ draft.

Откат: не удалять события и не возвращать `completed` в `working`; восстановить совместимый application reader либо остановить расчёт до исправления.

## Verification inventory

- State owner: `MariaDbInstallationCompletion`; public Yii completion HTTP and held-lock concurrency tests.
- Read consumers: completion query, object card/queue projection and construction-control checklist read; existing capability owners and their registered verifiers remain mandatory.
- Write consumers: checklist mutation/admission already require exact `working`; regression proves `completed` fail closed without changing those owners.
- Money consumer: native premium inputs and snapshot publication; Excel input/publication tests cover current and historical cutoff behavior.
- Persistence frontier: `fm2_installation_cases` is `VARCHAR(80)` without a state CHECK; `fm2_process_events` already stores JSON append-only audit, so no schema DDL is planned.
- Historical reconciliation, deployment CLI и recovery старых завершений вынесены в follow-up; file storage и external adapters N/A.
- Verification policy: completion owner/query and object queue projection are added to existing capability ownership entries; no classifier exception or reduced check set is introduced.
