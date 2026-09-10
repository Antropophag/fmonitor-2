## Purpose

Сохранить целый рабочий путь инженера при переносе чек-листа открытого объекта
в единый HTTP runtime, включая накопленные действия и возвращение пользователя.

## ADDED Requirements

### Requirement: Inspection journey preserves the accepted contract

Система SHALL выполнять нормативный контракт `specs/YII2-INSPECTION-JOURNEY-001.md`
(A1–A9); этот lifecycle документ не дублирует его acceptance matrix.

#### Scenario: Engineer records work and returns
- **WHEN** допущенный инженер отправляет действие из чек-листа открытого объекта
- **THEN** результат, история, повторы, отказы и возврат соответствуют указанному контракту.
