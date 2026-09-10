## ADDED Requirements
### Requirement: Current pre-opening journey through Yii
Система SHALL выполнять контракт specs/YII2-PREOPENING-JOURNEY-001.md через
Yii HTTP/session/CSRF и уже существующие domain owners с сохранением истории.
#### Scenario: Signed original followed by separate opening
- **WHEN** авторизованный пользователь выбирает состав, загружает оригинал и отдельно открывает работы
- **THEN** Yii вызывает соответствующих владельцев, сохраняет атомарность и возвращает актуальную карточку
#### Scenario: Rejection and recovery
- **WHEN** допуск, версия, файл, дата либо dependency не проходит contract
- **THEN** endpoint возвращает предусмотренный отказ без ложного успеха/partial facts и сохраняет native replay/recovery
