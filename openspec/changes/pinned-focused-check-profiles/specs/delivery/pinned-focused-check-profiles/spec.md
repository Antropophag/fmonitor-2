## Purpose

Обеспечить одинаковое воспроизводимое окружение для существующих focused checks
при локальном запуске и в CI без изменения смысла выбираемых тестов.

## ADDED Requirements

### Requirement: Три pinned execution profile
Система SHALL предоставлять ровно профили `governance`, `integration` и
`browser`; каждый профиль SHALL разрешаться в container image, закреплённый
неизменяемым digest, и SHALL иметь одинаковые runtime/dependency versions при
локальном запуске и в CI.

#### Scenario: Совпадение окружения локально и в CI
- **WHEN** одна команда запускается локально и в CI через один и тот же profile
- **THEN** наблюдаемые profile, image digest и runtime/dependency versions совпадают

#### Scenario: Неизвестный profile
- **WHEN** launcher получает имя вне `governance`, `integration`, `browser`
- **THEN** он завершается ненулевым exit code до запуска команды

### Requirement: Простой произвольный запуск
Система SHALL принимать `run-in-profile <profile> <command>` или эквивалентный
интерфейс и SHALL передавать command и её аргументы контейнеру без собственной
интерпретации test category, inventory или selection policy.

#### Scenario: Сохранение семантики команды
- **WHEN** существующая test command передана launcher
- **THEN** launcher выполняет именно эту команду, возвращает её exit code и не выбирает дополнительные тесты

#### Scenario: Минимальное evidence
- **WHEN** команда завершилась
- **THEN** доступное evidence ограничено git SHA, командой, profile/image digest, exit code и duration

### Requirement: Поэтапное принятие Quality Graph
PR A SHALL добавить container execution независимо от перевода category jobs.
После merge PR A PR B SHALL обернуть существующие команды jobs `unit`,
`integration`, `e2e`, `governance`, не меняя planner/selection/aggregation.

#### Scenario: PR A до adoption
- **WHEN** PR A проверяется до merge
- **THEN** существующие Quality Graph category commands и `setup-runtime` продолжают работать как прежде

#### Scenario: PR B после подтверждённого GREEN
- **WHEN** все четыре category jobs GREEN через execution profiles
- **THEN** прежний `setup-runtime` может быть удалён отдельным подтверждённым изменением
