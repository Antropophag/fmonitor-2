## Purpose

Переносит актуальные legacy-закрепления стройконтроля для уже импортированных объектов через проверяемый preview и canonical append-only owner без изменения legacy-данных.

## ADDED Requirements

### Requirement: Детерминированный read-only preview
Offline migration seam SHALL принимать явный набор canonical legacy object IDs либо режим `all-imported`, читать только соответствующие `fm2_installation_cases`, legacy `fm_maintable.responsstroicontrol`, legacy users и подтверждённые local identity links и выдавать отсортированный deterministic preview. Preview SHALL включать counts и для каждого объекта один статус `ready`, `already_applied`, `skipped` или `conflict` со стабильными reason codes.

Canonical JSON SHALL использовать key order и digest/source fingerprint algorithm из `LEGACY-CONTROL-ENGINEER-MIGRATION-001`, чтобы независимый consumer мог пересчитать оба SHA-256 без production code.

#### Scenario: Готовое закрепление
- **WHEN** объект уже импортирован, `responsstroicontrol` содержит существующий legacy user ID, этот ID однозначно связан с active local engineer и native assignment отсутствует
- **THEN** preview содержит `ready` с object ID, legacy user ID, local user ID и fingerprint входных фактов без изменения process или legacy rows

#### Scenario: Неполные и конфликтующие данные
- **WHEN** объект не импортирован, инженер отсутствует/неактивен/не связан/связан неоднозначно либо native assignment отличается
- **THEN** preview не считает строку ready, сообщает точную стабильную причину и не использует ФИО как fallback

### Requirement: Подтверждённое применение exact preview
Apply SHALL требовать identity и digest ранее сформированного preview, повторно читать все входные facts и fail closed при drift. Каждое ready-закрепление SHALL применяться через public application owner standalone assignment с migration actor, operation ID, source `legacy_fmonitor`, legacy object/user IDs и timestamp. Batch SHALL быть all-or-nothing до подтверждённого commit.

#### Scenario: Успешное применение
- **WHEN** оператор применяет неизменившийся preview и все ready rows проходят повторную проверку
- **THEN** для каждой строки появляется canonical append-only assignment history, а signed documents, applications, originals, opening и legacy rows остаются byte-identical

#### Scenario: Drift между preview и apply
- **WHEN** изменились legacy responsibility, identity link, local eligibility, imported case или native current assignment
- **THEN** apply отклоняет весь batch как stale/conflict и не создаёт ни одного assignment fact

### Requirement: Идемпотентность, конфликты и итоговая сверка
Exact повтор одной operation SHALL возвращать тот же terminal result без дублей. Уже существующее эквивалентное migrated assignment SHALL классифицироваться `already_applied`; иное native assignment MUST NOT заменяться автоматически. После apply система SHALL выдать deterministic reconciliation report по exact operation и всем выбранным объектам.

#### Scenario: Повтор после успеха
- **WHEN** оператор повторяет exact operation после подтверждённого успеха
- **THEN** система не создаёт новые users или assignments и возвращает совпадающий reconciliation result

#### Scenario: Существующее ручное закрепление
- **WHEN** у объекта уже есть current native assignment с иным инженером
- **THEN** preview/apply отмечает conflict, сохраняет текущее закрепление и требует отдельного штатного решения владельца

### Requirement: Ограниченный доступ и безопасный вывод
Preview/apply SHALL быть offline operator seam с least-privilege DB principal и SHALL NOT быть доступен web principal. Output MUST NOT содержать passwords, hashes, tokens, DB credentials, SQL или произвольные персональные данные; допускаются canonical IDs, bounded display labels, counts, digests и stable reason codes.

#### Scenario: Неверная конфигурация или schema
- **WHEN** отсутствует required schema/column, prefix/config malformed либо source недоступен
- **THEN** команда завершает работу до mutation со стабильным technical outcome и без раскрытия секретов
