## Context

См. `proposal.md`. Сейчас `MariaDbNativePremiumInputs` берёт deadline и ПТО из последнего registered assignment-order snapshot; native completion уже имеет root/correction chain. `PremiumCalculation` штрафует начисление до прежних выплат, а `MariaDbSnapshotBuilder` теряет остаток независимыми `intdiv`. Private PDF infrastructure существует у AssignmentOrderOriginal, но её domain-specific state нельзя переиспользовать как owner справки.

## Goals / Non-Goals

**Goals:** один глубокий certificate application owner; воспроизводимый as-of reader; одна чистая versioned money/allocation операция; атомарная snapshot publication; additive schema/recovery; минимальный adapter в `rapid-pilot`.

**Non-Goals:** импорт 659 Excel-значений, Excel `V`, изменение assignment order или completion facts, пересчёт истории, отправка денег, общий document framework, глобальный waiver.

## Decisions

### 1. Один законченный PR, доставляемый вертикальными gates

Справка и формула связаны одним пользовательским результатом: без owning certificate source перенос нельзя доказать, а справка без подключения к новой calculation version не даёт результата. Поэтому PR включает schema/application/card/read/calculator/allocation/publication, но реализация идёт последовательными законченными slices: schema → certificate operation → as-of operands → pure calculation/allocation → publication/UI. Альтернатива разделить на два PR оставила бы либо неиспользуемый документ, либо формулу без утверждённого override.

### 2. Отдельный модуль `DeadlineTransferCertificate`

Публичный application service владеет validation, authorization, idempotency, CAS correction, transaction и file-finalization outcome. Он зависит от узких interfaces clock, PDF acquisition, private storage, transaction/store и authorization. Проверенный passive PDF inspector и низкоуровневые гарантии storage извлекаются/адаптируются без зависимости certificate domain от AssignmentOrderOriginal service/state. `rapid-pilot` только формирует command, переводит result и рендерит read model.

### 3. Три append-only таблицы и отдельный private namespace

Certificate `roots(case_id unique,current_revision_id,lock_version,created_at)`, `revisions(root_id,version_no,previous_revision_id,case/application identities,certificate_date,new_deadline,pdf_sha256,pdf_size,opaque_file_identity,request_id,request_fingerprint,reason,actor,recorded_at)` и terminal `attempts(request_id,fingerprint,status,reason,revision_id,attempted_at)` плюс `fm2_otiz_object_entitlement_events(object_id,snapshot_id,entitlement_identity,event_type,supersedes_event_id,actor,occurred_at,operation_id)` образуют обязательную canonical schema v24 поверх v23. Возможные original-deadline columns/facts не входят в DDL до решения `NEEDS_GRILL`. Ограничения проверяют линейность, UUID/hash/size, mode/reason и уникальности; календарная и cross-row CAS validation принадлежит application. PDF bytes находятся в новом private namespace с mode0600, atomic rename/fsync и orphan recovery inventory.

Две таблицы без attempts хуже восстанавливают потерянный ответ/ambiguous file outcome. Универсальная documents-таблица преждевременно связывает разные lifecycle и не выбрана.

### 4. Current known справка не имеет report-date gate

Reader выбирает current accepted leaf на момент публикации независимо от `certificate_date`: Excel использует `DPn`, а `DOn` в этой формуле не участвует. `new_deadline` — значение документа, а не effective time. Дополнительное правило `new_deadline>=certificate_date` не вводится: реальные справки могут документировать срок, который уже наступил. Новая correction влияет на следующий snapshot, но сохранённый старый snapshot не перечитывается. Альтернатива `certificate_date<=report_date` отклонена как выдуманный cutoff. Void lifecycle отсутствует; если он появится отдельно, reader должен выбирать current non-void revision.

### 5. Native ПТО читается из completion history

Reader использует effective root/correction value и включает root/correction identity, date и canonical hash. Он намеренно не ограничивает PTO report date. Такое же узкое исключение применяется к current certificate document date; cutoff checklist progress/payments и других фактов не ослабляется. Version-1 publication replay читает сохранённый результат и не проходит vNext validation/calculation повторно. Поле `assignment_orders.pto_act_date_snapshot` остаётся историческим реквизитом и больше не является OTIZ operand owner.

Исходный Excel `T` нельзя выбирать как «последний registered order» или существующий `plannedFinishDate`: новый native путь не создаёт `fm2_assignment_orders`, а утверждённый adapter предпочитает `workdateendadjusted`, соответствующий скорректированному `V`. Field evidence подтверждает raw `plan_finish_date` как базовый T, но не определяет owner/timing.

`NEEDS_GRILL`, blocker original-deadline input/schema: (A) читать current raw T из object-card owner и фиксировать date/locator/hash в каждом новом snapshot; (B) захватывать raw T раньше в immutable selection/application; (C) ввести отдельное подтверждение base deadline сотрудником ФКР. A минимальна и ближе к «current T как Excel», B сильнее фиксирует основание процесса, C добавляет ручное действие. До ответа ни один вариант не включается в tasks как готовое решение; missing operand блокирует calculation.

### 6. Новая calculator version и единая целочисленная арифметика

Pure calculator принимает typed evidence, выполняет exact nonnegative integer HALF-UP на каждой денежной basis-point границе и возвращает amounts, Kss и formula trace. Это следует owner decision33; прежние `intdiv` характеристики требуют version cutover, а не молчаливого изменения replay. Paid-before агрегируется из существующих closure/reversal facts до penalty. Allocation является отдельной частью той же versioned policy: stable tab sort, integer quotients, remainder numerator sort DESC, tab ASC tie-break, затем conservation assertion выбранного целого pool. Snapshot hash включает version, ordered operands и ordered allocations.

### 7. Version cutover сохраняет историю

После deployment publication создаёт только active formula version. Старый draft остаётся read-only и получает stale refusal при acceptance. Это лишь formula cutover и само по себе не реализует A02.

Publication owner блокирует installation-case rows всех snapshot objects по object id, создаёт fresh candidate-entitlement identities и сразу append-only supersede-ит previous accepted-unpaid entitlement даже у двух v2 snapshots или разных report periods. Candidate требует отдельной acceptance; это намеренно создаёт промежуток без payable entitlement согласно decision34. Acceptance только активирует тот же fresh candidate после blockers/version validation.

PaymentCompletion — единственный payment writer: повторяет object lock order, подтверждает latest active non-superseded identity, суммирует object-wide signed closures across snapshots и пишет closures/receipt в одной транзакции. Старый rapid-pilot snapshot-local payment SQL удаляется. Paid history не supersede-ится и не мигрирует. Publication supersession + later acceptance + payment guard вместе реализуют A02 и закрывают double-close finding.

Ledger разделяется: `paidBefore` — только net paid/reversal facts; discipline holds уменьшают payable после formula pool и переносятся object-wide. Legacy deadline holds нельзя ни повторно штрафовать, ни возвращать молча, поэтому nonzero balance блокирует v2 до append-only reversal. Отдельный `NEEDS_GRILL` остаётся для cumulative recurrence без нового progress: literal Excel pool9 против interval no-new-amount.

### 8. Waiver отделён от расчёта

`NEEDS_GRILL`: ответ владельца о функциональном аналоге `$EU$2` ещё не получен. Текущий change не содержит toggle, env flag или schema column; calculator всегда применяет penalty. Если waiver будет одобрен, он требует отдельной capability/application history и не меняет задачи этого slice автоматически.

### 9. Architecture and recovery impact

Новые certificate mutations разрешены только в `app/DeadlineTransferCertificate`; SQL-owning adapters находятся там же. Capabilities `deadline_certificate.write|read` получают exact default role mappings из contract. OTIZ acceptance/payment mutations принадлежат `app/Otiz`; `rapid-pilot` не получает SQL/domain decisions. Architecture checks должны не увеличить baseline violations. Schema v24 catalogue/readiness, exact table/index/check manifests, backup inventory и v23→v24 restore forward fixture обновляются вместе; v23 tooling отказывает до mutation на v24.

## Risks / Trade-offs

- [DB commit и file finalize расходятся] → staged file, terminal attempt, explicit unknown outcome, replay recovery и injected-boundary tests.
- [Новая или future-dated correction меняет новый расчёт] → snapshot сохраняет exact revision; current leaf перечитывается только при новой publication operation.
- [PTO после report date выглядит необычно] → literal owner-approved behavior фиксируется отдельным acceptance example и provenance.
- [Новый порядок денег меняет ещё не выплаченные суммы] → новая version, stale old drafts, никаких silent updates accepted/paid history.
- [Schema frontier влияет на recovery] → v23 forward migration, exact vNext restore, old-tool refusal and no-downgrade tests до release.
- [Большой PR] → gates по вертикальным seams и независимые reviewers application/calculation/schema; каждый slice оставляет executable evidence.

## Migration Plan

1. Зафиксировать Gate1/2/3 и RED до source changes.
2. Добавить additive schema v24/capabilities/private storage readiness и доказать v23→v24 forward recovery.
3. Доставить certificate application/read/download и card UI без включения в расчёт до GREEN.
4. Подключить current certificate + effective completion operands, calculator/allocation version и atomic publication.
5. Прогнать focused browser/HTTP/DB/recovery, independent reviews и один full CI exact candidate.
6. Развернуть без backfill; rollback приложения читает старую историю, но tooling, не понимающий новую frontier, обязан fail closed до mutation. Новые certificate rows/PDF сохраняются для повторного forward deployment.

## Open Questions

- `NEEDS_GRILL`, условно и вне текущей реализации: нужен ли владельцу управляемый аналог глобального `$EU$2`; если да, кто имеет право, на какой срок и в каком scope объекта/периода?
- `NEEDS_GRILL`, блокирует original-deadline integration: current object-card capture каждого snapshot, immutable capture в selection/application или отдельное подтверждение ФКР для raw Excel T?
- `NEEDS_GRILL`, блокирует recurring-snapshot money semantics: literal cumulative Excel pool9 без нового progress или interval-driven no-new-amount?
