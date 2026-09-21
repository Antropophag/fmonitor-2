# shipment-indicator Delta Specification

## ADDED Requirements

### Requirement: приоритетный подтверждённый shipment status

Очередь SHALL показывать full shipment при подтверждённой полной дате, иначе first shipment при подтверждённой первой дате, иначе MUST NOT показывать положительный статус.

#### Scenario: полная дата имеет приоритет
- **GIVEN** обе даты присутствуют
- **WHEN** пользователь открывает очередь
- **THEN** виден только статус полной отгрузки с полной датой

#### Scenario: readiness не является shipment
- **GIVEN** присутствует только readiness date
- **WHEN** пользователь открывает очередь
- **THEN** положительный shipment indicator отсутствует

### Requirement: доступное компактное пояснение

Состояния SHALL различаться не только цветом; пояснение SHALL открываться клавиатурой и касанием и показывать название с датой.

#### Scenario: keyboard disclosure
- **GIVEN** строка с подтверждённым shipment status
- **WHEN** пользователь фокусирует и активирует индикатор
- **THEN** раскрывается понятное название и соответствующая дата
