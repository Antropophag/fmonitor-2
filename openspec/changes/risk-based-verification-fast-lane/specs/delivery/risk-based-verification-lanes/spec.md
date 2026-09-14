## Purpose

Дать доказанно bounded UI changes короткий fail-closed delivery route, используя только существующие machine-readable ownership и verification seams.

## ADDED Requirements

### Requirement: Deterministic FAST v1 classification
Существующий verification planner SHALL выдавать ровно `FAST`, `STANDARD` или `CRITICAL`, machine-readable reasons/escalations, required reviews и `selected_checks` с конкретными registered checks/canonical argv. FAST SHALL требовать однозначного owner/consumer closure и bounded UI/view/JS/CSS seam. Classification SHALL зависеть только от verification policy, registered ownership/consumer facts и effective source; LLM judgement не является входом. FAST SHALL не скрывать целую category, не расширять category с последующей фильтрацией и не выбирать unrelated integration/e2e checks.

#### Scenario: F01 bounded UI
- **WHEN** effective change содержит локальный UI/view/JS/CSS seam и связанный registered oracle при доказанном closure без critical owners
- **THEN** planner возвращает FAST, один final review и exact selected-check argv без unrelated category expansion

#### Scenario: F03 persistence
- **WHEN** к candidate добавлен persistence owner path
- **THEN** planner не возвращает FAST и сообщает machine-readable escalation

#### Scenario: F04 auth boundary
- **WHEN** candidate затрагивает auth, permission, RBAC или session seam
- **THEN** planner возвращает CRITICAL

#### Scenario: F06 harness boundary
- **WHEN** candidate меняет delivery harness, verification policy/inventory/router или CI workflow
- **THEN** planner возвращает CRITICAL, включая реализацию issue #118

#### Scenario: F07 unknown closure
- **WHEN** effective path не имеет одного доказанного owner/consumer closure
- **THEN** planner fail closed повышает lane минимум до STANDARD и никогда не возвращает FAST

### Requirement: One-review FAST v1 lifecycle contract
FAST candidate SHALL пройти prepare/classify, intended RED, implementation, selected affected checks, один independent final review и exact-source FAST admission. Final review package SHALL одновременно содержать acceptance/test sensitivity, implementation diff, lane classification, отсутствие critical boundaries, RED→GREEN trace и exact selected checks. Отдельный Gate 3 для FAST consumer SHALL отсутствовать. Само изменение #118 SHALL использовать CRITICAL и обычные Gates 1–5.

#### Scenario: FAST final review package
- **WHEN** bounded FAST candidate завершил intended RED, implementation и selected checks
- **THEN** prepared package требует ровно один independent final review со всеми перечисленными evidence dimensions

### Requirement: Sensitive FAST oracle
Positive F01 fixture SHALL иметь defective variant, нарушающий заявленное публичное UI behavior; тот же selected registered oracle SHALL вернуть intended RED по behavior assertion, не setup failure.

#### Scenario: F10 defective fixture
- **WHEN** FAST fixture содержит намеренный behavior defect
- **THEN** конкретный selected oracle возвращает intended RED и candidate не получает admission

### Requirement: Compatible execution frontier
Selected check SHALL использовать существующий canonical route. `run-in-profile` и его Docker/tooling capabilities SHALL не изменяться. Check, совместимость которого с доступным route не доказана, SHALL повышать applicability/lane fail closed.

#### Scenario: Unknown execution compatibility
- **WHEN** selected oracle требует недоказанный runtime или Docker contour
- **THEN** planner не расширяет profile и не публикует FAST GREEN

### Requirement: Conventional exact-source reconstruction
Каждый FAST CI job SHALL независимо реконструировать plan из current HEAD, ровно одного applicable committed `openspec/changes/<change>/verification-input.json` и canonical policy/ownership/inventory. Ноль или более одного applicable input, malformed/mismatched input, stale HEAD или различающиеся reconstruction SHALL fail closed. Jobs SHALL не передавать serialized plan, command blobs, scripts или arbitrary check lists. Две reconstruction одного HEAD SHALL быть canonical-equivalent по lane, selected checks, required reviews, reasons/escalations и admission expectations.

#### Scenario: One deterministic input
- **WHEN** current candidate содержит ровно один applicable conventional input
- **THEN** две независимые reconstruction возвращают одинаковый FAST contract и concrete registered checks

#### Scenario: Missing or ambiguous input
- **WHEN** applicable inputs ноль или больше одного
- **THEN** FAST mode не выбирается и никакой файл не выбирается по порядку, mtime или имени
