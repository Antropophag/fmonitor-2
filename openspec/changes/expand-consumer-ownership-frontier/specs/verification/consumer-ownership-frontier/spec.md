## Purpose

Расширять verification plan от изменённого protected capability или invariant до всех зарегистрированных прямых и транзитивных consumers с проверяемой причинной цепочкой.

## ADDED Requirements

### Requirement: Repository-owned consumer frontier
Planner SHALL использовать минимальную repository-owned ownership metadata рядом с существующей verification policy и canonical verifier inventory. Каждый capability, владеющий changed protected path, SHALL иметь non-empty owner patterns; consumer-only capability MAY не владеть path и иметь пустой patterns list. Каждый protected semantic surface SHALL иметь ровно один однозначный capability owner, а каждый consumer edge SHALL ссылаться на другой declared capability либо canonical registered verifier identity. Runtime LLM judgement, issue names и конкретные исторические incidents MUST NOT быть policy inputs.

#### Scenario: Canonical migration frontier
- **WHEN** canonical migration/schema frontier capability изменён
- **THEN** planner выбирает его direct migration verifier, recovery/current-schema consumer и indirect runtime/inventory consumer

#### Scenario: Synthetic current assignment
- **WHEN** protected authoritative current-assignment capability изменён
- **THEN** planner выбирает все его зарегистрированные direct и transitive consumers без full-suite selection

#### Scenario: Local presentation-only change
- **WHEN** изменение принадлежит только bounded presentation surface и не меняет protected semantic capability
- **THEN** planner не добавляет consumer ownership frontier

### Requirement: Complete transitive expansion
Planner SHALL обходить весь достижимый consumer frontier детерминированно, независимо от порядка declarations. Наличие direct consumer MUST NOT прекращать поиск indirect consumers. Повторные пути и cycles MUST завершаться bounded traversal с canonical-equivalent result.

#### Scenario: Direct and indirect consumers coexist
- **WHEN** capability имеет direct verifier и edge к owned downstream capability с собственным verifier
- **THEN** оба verifier и все дальнейшие reachable registered verifiers входят в plan ровно по одному разу

#### Scenario: Declaration order changes
- **WHEN** те же valid ownership declarations переставлены без изменения graph semantics
- **THEN** selected verifier set и causal chains остаются canonical-equivalent

### Requirement: Machine-readable causal reasons
Для каждого verifier, добавленного consumer expansion, plan SHALL содержать structured reason, связывающую changed path и changed capability с полной consumer chain и конечным registered verifier. Причина MUST быть проверяема без разбора human prose.

#### Scenario: Expanded verifier explanation
- **WHEN** verifier выбран через два consumer edges
- **THEN** plan содержит changed capability, ordered consumer chain и verifier identity для этого selection

#### Scenario: Multiple paths to one verifier
- **WHEN** один verifier достижим более чем одной цепочкой
- **THEN** plan выполняет verifier ровно один раз и сохраняет отдельное machine-readable evidence для каждой unique simple causal chain в canonical sort order

### Requirement: Protected ownership fails closed
Planner MUST завершаться ненулевым status до plan emission, если protected semantic surface не имеет однозначного current ownership, если consumer target отсутствует, либо если terminal verifier удалён, stale или не зарегистрирован в canonical inventory. Незарегистрированный path MUST NOT считаться coverage.

#### Scenario: Missing protected owner
- **WHEN** changed protected semantic path matches surface, но не имеет capability owner
- **THEN** prepare fail closed с deterministic diagnostic

#### Scenario: Stale consumer capability
- **WHEN** ownership edge ссылается на undeclared capability
- **THEN** policy validation fail closed

#### Scenario: Removed or unregistered verifier
- **WHEN** terminal verifier отсутствует в canonical inventory либо файл удалён
- **THEN** policy validation или prepare fail closed и verifier не засчитывается как coverage

#### Scenario: Ambiguous ownership
- **WHEN** protected path принадлежит более чем одному capability owner
- **THEN** prepare fail closed вместо выбора произвольного frontier

### Requirement: Slice A conservative closure remains
Consumer frontier SHALL быть additive к Slice A semantic integration closure. Planner MUST сохранять integration category closure и её machine-readable escalation для protected surface, даже когда более точный direct/transitive frontier успешно вычислен.

#### Scenario: Protected change with complete ownership
- **WHEN** protected change имеет полный consumer frontier
- **THEN** plan содержит как Slice A integration closure, так и consumer-expanded verifier reasons

#### Scenario: Direct verifier already selected elsewhere
- **WHEN** direct verifier уже выбран boundary, acceptance mapping или Slice A closure
- **THEN** execution дедуплицирован, но indirect consumer остаётся выбранным и causal evidence сохраняется
