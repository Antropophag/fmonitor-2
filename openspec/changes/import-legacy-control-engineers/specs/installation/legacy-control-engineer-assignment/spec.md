## Purpose

Определяет каноническую связь импортированного монтажного дела с инженером строительного контроля из legacy и её наблюдаемое использование в очереди и карточке объекта.

## ADDED Requirements

### Requirement: Imported object is linked to its resolved engineer
Legacy import SHALL преобразовать положительный `fm_maintable.responsstroicontrol` импортируемого объекта в каноническую связь installation case → local engineer identity в той же atomic import boundary. Связь SHALL хранить legacy object ID, legacy engineer ID, local user ID и source snapshot provenance.

#### Scenario: Объект и инженер пригодны
- **WHEN** объект проходит eligibility импорта и его legacy engineer identity успешно разрешена
- **THEN** монтажное дело создаётся или подтверждается со связью на соответствующего локального инженера

#### Scenario: Инженер ещё не активирован
- **WHEN** связанный пользователь ожидает приглашения
- **THEN** связь объекта сохраняется и читается, но пользователь не получает права входа до отдельной активации

#### Scenario: Нулевая legacy-ссылка
- **WHEN** пригодный объект имеет пустой или нулевой `responsstroicontrol`
- **THEN** импорт сохраняет объект без назначенного инженера и явно учитывает unassigned result, не создавая фиктивного пользователя

#### Scenario: Неразрешимая положительная ссылка
- **WHEN** `responsstroicontrol` положителен, но referenced identity отсутствует, неактивна, недопустима или конфликтна
- **THEN** atomic import завершается fail-safe без частичной записи объекта, пользователя или связи

### Requirement: Assignment import is replay-safe and history-preserving
Повтор того же source fact SHALL возвращать ту же каноническую связь без дубликатов. Изменившийся legacy engineer SHALL NOT задним числом переписывать принятую ручную коррекцию или исторические assignment snapshots; конфликт MUST быть наблюдаемым и требовать явного application command с причиной.

#### Scenario: Идентичный повтор
- **WHEN** объект с тем же legacy engineer ID импортируется повторно
- **THEN** существующая связь подтверждается без новой версии или дубликата

#### Scenario: Source engineer changed after accepted import
- **WHEN** новый snapshot содержит другой положительный `responsstroicontrol` для уже связанного дела
- **THEN** система либо добавляет разрешённую append-only source revision по явному контракту, либо возвращает conflict; она MUST NOT выполнять silent overwrite

#### Scenario: Параллельный повтор
- **WHEN** два импорта одновременно принимают один object/engineer source fact
- **THEN** итог содержит одну каноническую identity и одну текущую связь, а проигравший вызов получает replay/conflict без partial facts

### Requirement: Object reads expose the canonical engineer
Авторизованные объектная очередь и карточка SHALL показывать ФИО и статус приглашения связанного инженера из канонической identity; они MUST NOT повторно разрешать `responsstroicontrol` напрямую из legacy при HTTP-чтении.

#### Scenario: Связанный инженер отображается
- **WHEN** авторизованный пользователь открывает очередь или карточку импортированного объекта
- **THEN** ответ показывает связанного инженера и состояние «ожидает приглашения» либо актуальное состояние активации

#### Scenario: Объект не назначен
- **WHEN** у дела нет канонической связи инженера
- **THEN** ответ явно показывает отсутствие назначения без выдуманного имени или fallback по legacy

#### Scenario: Нет права чтения объекта
- **WHEN** actor не имеет разрешения на объектный экран
- **THEN** связь и персональные данные инженера не раскрываются

### Requirement: Import result reports engineer coverage
Public import result SHALL отдельно сообщать количества referenced, created, already-present, linked, unassigned и conflicted engineers/assignments, не включая персональные данные или секреты.

#### Scenario: Успешный смешанный импорт
- **WHEN** snapshot содержит новые, ранее импортированные и неназначенные объекты
- **THEN** terminal success содержит согласованные aggregate counts, позволяющие доказать покрытие всех eligible объектов
