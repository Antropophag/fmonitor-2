## ADDED Requirements
### Requirement: Yii queue and inspection planning
Система SHALL выполнять полный контракт specs/YII2-OBJECT-QUEUE-001.md через
Yii HTTP и application owners без HTTP DDL и без записи из контроллера.
#### Scenario: Назначение из очереди
- **WHEN** авторизованный пользователь назначает допустимую дату из очереди
- **THEN** schedule и event сохраняются атомарно, повтор не дублирует историю,
  пользователь возвращается в очередь с подтверждением
#### Scenario: Отказ без новых фактов
- **WHEN** не выполнены authorization, date, eligibility или readiness
- **THEN** система возвращает предусмотренный контрактом отказ и сохраняет историю
