## Context

См. [proposal.md](proposal.md). Текущий `legacy-import/run` читает `responsstroicontrol`, но snapshot не переносит referenced строки `users`, а import owner создаёт объекты/дела без канонического пользователя и связи инженера. В актуальном main уже есть append-only `fm2_legacy_identity_links` и административный flow invitation reissue, но существующий link command рассчитан на вручную созданного активного локального инженера и не является bulk import seam.

Изменение пересекает `InstallationProcess` и `IdentityAccess`, затрагивает schema frontier, security state и replay, поэтому требует отдельного дизайна. Legacy остаётся read-only, `rapid-pilot` — только oracle, production code его не загружает.

## Goals / Non-Goals

**Goals:**

- Один atomic application owner принимает object snapshot, referenced engineer identities и связи.
- Imported identity остаётся неаутентифицируемой до явного действия администратора.
- Stable keys, provenance, replay и conflict semantics исключают сопоставление по ФИО и silent overwrite.
- Очередь и карточка читают только canonical target state.

**Non-Goals:**

- Перенос legacy password/session material или автоматическая отправка email.
- Импорт пользователей, на которых не ссылается ни один eligible object.
- Превращение source assignment в выпущенное распоряжение или historical composition fact.
- UI редактирования назначения инженера в этом срезе.

## Decisions

### 1. Snapshot замыкает referenced identities одним read-only transaction

`MariaDbLegacySourceSnapshot` выбирает eligible objects и множество положительных `responsstroicontrol`, затем в том же consistent snapshot читает exact `users.id/name/email/status/role_id` и роль. Возвращается нормализованный graph `objects + engineers + references`; каждый positive reference обязан разрешиться ровно один раз.

Альтернатива — отдельные запросы target importer по мере обработки — отвергнута: она нарушает единый cutoff/snapshot и допускает source drift между объектом и пользователем.

### 2. IdentityAccess владеет пользователем, InstallationProcess — связью дела

Новый глубокий import owner оркестрирует два внутренних порта в одной target DB transaction. `IdentityAccess` создаёт/находит pending-invitation user, role/capability и append-only legacy identity provenance. `InstallationProcess` создаёт/подтверждает case-engineer assignment. HTTP, console и source adapter не пишут таблицы напрямую.

Allowed dependency направлена от import application orchestration к публичным портам обоих модулей; object read model зависит от canonical projection, но не от legacy connection. Architecture checks должны запретить новые SQL writes из Yii controller/console и любое production include из `rapid-pilot`.

### 3. Отдельное состояние «ожидает приглашения» не выдаёт secret

Если существующее `activation_state='invited'` семантически требует уже созданного invitation secret, migration вводит/закрепляет отдельное состояние `pending_invitation`; UI подписывает его «Ожидает приглашения». Явное admin command атомарно создаёт invitation и переводит пользователя в существующее invited состояние. Если текущая схема уже допускает invited без secret и существующий command корректно создаёт первый secret, отдельный enum не нужен, но observable contract остаётся тем же.

Альтернатива — импорт сразу создаёт invitation token — отвергнута: владелец решил генерировать и отправлять приглашения позднее, а неиспользованные secrets увеличивают риск утечки и не имеют подтверждённого получателя.

### 4. Stable identity — legacy user ID, email только атрибут и conflict witness

Канонический unique link связывает `(source_system, legacy_user_id)` с local user ID. Нормализованный email проверяется на collision, но не используется для автоматического merge. Любая коллизия с другим local identity даёт conflict. Имя/роль/email сохраняются snapshot-полями для аудита.

Альтернатива — upsert по email или ФИО — отвергнута из-за переименований, общих адресов и риска связать объект не с тем человеком.

### 5. Связь объекта хранится отдельно от распоряжения

Импортированное source assignment не считается новым assignment order. Additive canonical relation связывает installation case, local engineer identity и source revision/provenance; текущая read projection использует её, пока более сильный native assignment/application fact не определит инженера по существующим правилам. Приоритет источников фиксируется один раз в read model.

Изменившийся source engineer всегда возвращает conflict и не обновляет current row: автоматического source revision в этом срезе нет. Исправление выполняется только отдельным явным command с причиной; исторические snapshots распоряжений неизменяемы.

### 6. All-or-nothing import и aggregate receipt

Target transaction охватывает identity facts, object/case facts, links, template/details и terminal receipt. Positive unresolved reference отклоняет invocation целиком; нулевая ссылка является допустимым unassigned object. Operation fingerprint включает cutoff и canonical hashes object/engineer/reference graph. Replay возвращает сохранённый aggregate receipt.

Это сохраняет существующее обещание clean-stand bootstrap без частично наполненного состояния. Счётчики не содержат PII.

## Risks / Trade-offs

- [Один плохой positive reference блокирует весь импорт] → До записи выполнять полный preflight graph и возвращать bounded conflict inventory по legacy IDs без персональных данных.
- [Email уже занят вручную созданным пользователем] → Не merge автоматически; показать conflict администратору и использовать существующую явную identity-link correction с причиной.
- [Роль legacy не совпадает с ожидаемой] → Зафиксировать allowlist по устойчивому role ID/code из подтверждённого source evidence; UNKNOWN остаётся rejection.
- [Pending user может случайно пройти auth] → Auth policy требует active state и credential; добавить отрицательные HTTP/session tests до migration GREEN.
- [Source assignment конкурирует с native correction] → Import возвращает conflict и никогда не переписывает native/manual current fact.

## Migration Plan

1. Снять read-only inventory referenced legacy IDs, ролей, email collisions и текущего target schema frontier; персональные данные не коммитить.
2. Добавить forward-only migration identity state/provenance и case-engineer source relation; существующих пользователей и assignment snapshots не менять.
3. Выпустить reader, понимающий старое отсутствие связи и новые строки, до включения writer.
4. Включить расширенный importer и выполнить clean-stand import; сверить aggregate coverage `eligible = linked + unassigned` при нулевых unresolved conflicts.
5. Проверить authenticated object queue/card и admin directory, затем restart/replay на том же snapshot.
6. Rollback приложения выполняется возвратом к reader-compatible image; additive facts сохраняются. Destructive down migration не применяется.
