## Context

См. `proposal.md`. Текущий `ChecklistSync` одновременно разбирает HTTP/offline protocol, создаёт schema и владеет SQL/rules. Existing verifiers являются behavioral oracle, но не target architecture.

## Goals / Non-Goals

**Goals:**

- Создать глубокий модуль Inspection Evidence с одним command seam для item completion.
- Сохранить offline idempotency, optimistic concurrency, immutable template binding и historical crew attribution.
- Использовать landed canonical inspection-evidence schema v8 и перенести
  business SQL в MariaDB adapter без новой migration version.
- Оставить rapid-pilot тонким HTTP/protocol adapter.

**Non-Goals:**

- Не переносить photos, section completion, scheduling, completion percentage или premium behavior.
- Не переделывать существующее хранилище целиком и не извлекать PB-01..03 из compatibility facade.

## Decisions

1. Owning module — `InspectionEvidence`; public command seam —
   `InspectionRecording::completeItem`, public read seam —
   `InspectionEvidenceView::getItemCompletion`. Application contracts зависят
   только от ports/values/clock, не от PilotHttp, rapid-pilot или concrete
   MariaDB. Read seam делает audit/persistence outcomes наблюдаемыми без SQL
   side channel и никогда не backfill-ит history. Альтернатива добавить методы в
   гигантский `InstallationProcess` отклонена из-за низкой cohesion.
2. Persistence adapter атомарно блокирует case revision, проверяет/replays operation id и добавляет operation, installer snapshots, revision и audit. SQL разрешён только adapter/migration слоям. Альтернатива сохранить SQL в HTTP противоречит ownership guard.
3. Canonical runner v8 уже владеет exact inspection-evidence family. Этот
   behavior slice не получает additive version; production adapter проверяет
   deployment precondition и никогда не вызывает migration. Existing rapid
   tables остаются data-shape oracle, storage redesign отложен.
4. Rapid adapter переводит authenticated request в command DTO и отображает typed rejection в существующий offline response contract. Он не пересчитывает expected values и не делает fallback writes.
5. Architecture baseline не расширяется. Любой рост `ChecklistSync.php` требует явного justification; ожидаемый результат slice — уменьшение его mutation ownership.
6. Authorization проверяет active user и exact capability
   `inspection.item.complete` при server receipt. Любой инженер с этой
   capability может завершать пункты на любом объекте; current engineer
   assignment не является object-scope guard и сохраняется только как
   routing/audit context. `deviceTime` — аудируемое клиентское время, но не
   authority time: offline command после блокировки пользователя или отзыва
   capability отклоняется детерминированно. Альтернатива capability плюс current
   assignment отклонена владельцем, потому что инженерам требуется отмечать
   объекты друг за друга.
7. Replay precedence: current authorization, command syntax и v8 deployment
   precondition проверяются на каждом receipt; затем exact replay/conflict
   разрешается до изменяемых case/template/current-crew preconditions. Это
   сохраняет offline idempotency после закрытия дела или смены состава, но не
   позволяет replay обойти отзыв capability.
8. Production composition имеет один внешний seam:
   `ProductionInspectionEvidenceFactory::create(mysqli,
   ProductionInspectionEvidenceConfig, ?InspectionEvidenceClock)` возвращает
   `InspectionEvidenceApplication`, объединяющий command и query interfaces.
   Caller владеет lifecycle соединения, application — транзакциями на нём; один
   instance не разделяется между concurrent workers. Factory валидирует
   canonical 0..25-byte ASCII process prefix до DB access, устанавливает
   `utf8mb4`, допускает injected clock и не выполняет DDL/repair/mutation.
   MariaDB tests и HTTP wiring используют factory, а не concrete repository.
   `InspectionEvidenceClock::now(): DateTimeImmutable` — exact clock contract;
   application форматирует его как RFC3339 seconds `Y-m-d\TH:i:sP` с numeric
   offset. Default clock использует `Europe/Moscow`, injected clock вызывается
   один раз на first-time receipt и не вызывается для replay.

## Risks / Trade-offs

- [Существующий offline protocol шире одного command] → characterization фиксирует только item-completion branch, остальные ветви остаются за ratchet.
- [Dual path может разойтись во время миграции] → adapter переключается атомарно для выбранного operation type; один command не имеет двух writers.
- [Широкий object scope позволяет инженерам работать за коллег] → доступ всё
  равно ограничен active-user status и exact capability, фактический actor и
  назначенный инженер сохраняются раздельно в аудите; assignment не участвует в
  admission.
- [Offline command предъявляет старое device time] → authority повторно
  проверяется при server receipt; client time не восстанавливает отозванное
  право и не используется как основание допуска.

## Migration Plan

1. Зафиксировать characterization и approved executable spec.
2. Подтвердить landed v8 schema precondition; не создавать новую migration и не
   импортировать/переписывать факты.
3. Реализовать application seam и adapter под reviewed RED.
4. Переключить только `item_completed` branch rapid-pilot HTTP на seam.
5. Проверить offline/current-crew/E2E/architecture; rollback возвращает wiring при сохранении additive schema и фактов.
