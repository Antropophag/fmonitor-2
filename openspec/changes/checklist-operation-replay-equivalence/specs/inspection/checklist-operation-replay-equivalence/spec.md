## Purpose

Гарантирует, что повтор native Yii2 checklist command считается replay только для ранее принятого того же намерения и никогда не подтверждает коллизию другого намерения.

## ADDED Requirements

### Requirement: Exact replay возвращает ранее принятый результат без записи
Для `item_installers_changed`, `completion_retracted`, `photo_uploaded`, `photo_revoked` и `section_completed` система SHALL считать повтор `client_operation_id` успешным replay только когда совпадают installation/object, operation type, actor, device и нормализованное значимое содержимое команды. `baseRevision` и `deviceTime` SHALL оставаться audit/retry metadata и не превращать иначе точный повтор в новое намерение.

Значимое содержимое SHALL быть определено по типу: section и normalized set исполнителей для `item_installers_changed`; section/item, исходная operation и trimmed reason для `completion_retracted`; section, SHA-256, MIME, byte size и original name для `photo_uploaded`; section, photo identity и trimmed reason для `photo_revoked`; section и пустой type-specific payload для `section_completed`. JSON key order SHALL NOT влиять на эквивалентность.

#### Scenario: Healthy exact retry
- **WHEN** разрешённый actor повторяет тот же ID, object, type, device и семантически тот же normalized payload после принятой команды
- **THEN** public seam возвращает `duplicate` и прежнюю revision без новой operation, revision, photo/file или иного fact

#### Scenario: Порядок исполнителей не меняет намерение
- **WHEN** `item_installers_changed` повторён с тем же множеством исполнителей в другом порядке
- **THEN** public seam возвращает exact replay без записи

#### Scenario: Нормализация причины не меняет намерение
- **WHEN** correction/revoke повторён с той же причиной, отличающейся только внешними пробелами
- **THEN** public seam возвращает exact replay без записи

### Requirement: Коллизия ID отвергается без утечки и записи
Система MUST вернуть существующий conflict/rejection envelope, а не `duplicate`, если повторный `client_operation_id` относится к другому object/case, operation type, actor, device или значимому normalized payload. Авторизация команды и read access MUST проверяться до выдачи replay projection; субъект без read access MUST NOT получить revision, projection или иные данные ранее принятой операции.

#### Scenario: Другой object
- **WHEN** ранее принятый ID отправлен для другого installation object
- **THEN** запрос получает безопасный conflict/rejection и не создаёт фактов

#### Scenario: Другой type
- **WHEN** ранее принятый ID отправлен с другим checklist operation type
- **THEN** запрос получает conflict/rejection и не создаёт фактов

#### Scenario: Другой meaningful payload
- **WHEN** ранее принятый ID отправлен с изменёнными исполнителями, исходной operation/причиной, photo identity/metadata либо type-specific section/item
- **THEN** запрос получает conflict/rejection и не изменяет operations, revisions, photos, files или иные facts

#### Scenario: Чужой actor или device
- **WHEN** ID ранее принятой операции повторяет другой actor либо другое device installation
- **THEN** операция не присваивается новому субъекту, replay success не возвращается и защищённая projection не раскрывается без read access

### Requirement: Race recovery использует ту же replay equivalence
После concurrent unique-key/integrity collision система SHALL применить ту же canonical equivalence policy, что и обычный duplicate path. Она MUST сохранить append-only history и не оставлять partial photo/file/revision effects проигравшей команды.

#### Scenario: Concurrent equivalent commands
- **WHEN** два разрешённых эквивалентных запроса одновременно используют один operation ID
- **THEN** ровно один факт принимается, а второй получает корректный replay прежней revision

#### Scenario: Concurrent conflicting commands
- **WHEN** два разрешённых запроса с одним ID, но разным meaningful payload конкурируют
- **THEN** только один принимается, второй получает conflict/rejection и не получает ложный success или partial writes

### Requirement: Существующие native контракты сохраняются
Система SHALL сохранить native canonical replay behavior `item_completed`, существующую #130 read authorization, healthy checklist semantics и текущий public HTTP/domain envelope. Решение MUST использовать уже сохраняемые данные и MUST NOT требовать schema/client/offline/UI changes.

#### Scenario: Item completion regression
- **WHEN** exact и conflicting `item_completed` replays проходят существующий public seam
- **THEN** они сохраняют соответственно `duplicate` и `OPERATION_PAYLOAD_CONFLICT` mapping без новых фактов

#### Scenario: Read authorization regression
- **WHEN** субъект без checklist read access повторяет существующий operation ID
- **THEN** текущая #130 safe denial остаётся неизменной и не раскрывает projection
