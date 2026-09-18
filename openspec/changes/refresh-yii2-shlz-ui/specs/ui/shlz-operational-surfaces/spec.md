## Purpose

Задаёт наблюдаемый `shlz-ui` контракт карточки объекта и минимальных shared compositions без изменения domain behavior.

## ADDED Requirements

### Requirement: Операционная карточка объекта
Карточка SHALL показывать идентичность, адрес, статус, инженера и одно разрешённое primary-действие; остальные факты SHALL быть сгруппированы по смыслу.

#### Scenario: Обычная и edge-state карточка
- **WHEN** актор с текущими permissions открывает обычную, long-content, missing/corrupt-data или unknown-date карточку
- **THEN** факты и разрешённая next action MUST оставаться читаемыми, а недоступные actions MUST NOT появляться

### Requirement: Адаптивность и progressive enhancement
Карточка SHALL работать на 320/768/1024/1440 CSS px, при 200% zoom, keyboard-only и coarse pointer без page overflow; coarse targets MUST быть не менее 44×44 px. JS-off SHALL сохранять SSR content и native navigation.

#### Scenario: Адаптивный и JS-off просмотр
- **WHEN** карточка открыта в указанном viewport/input mode или с JavaScript disabled
- **THEN** content, focus и native link MUST остаться рабочими без перекрытия и horizontal page overflow

### Requirement: Доменная неизменность
Routes, methods, CSRF, fields, permissions, GET/HEAD read-only, replay/concurrency и append-only facts MUST остаться неизменными.

#### Scenario: Просмотр и команды
- **WHEN** актор читает карточку или выполняет существующую команду
- **THEN** текущие fact-count, outcomes, replay и conflict behavior MUST не измениться
