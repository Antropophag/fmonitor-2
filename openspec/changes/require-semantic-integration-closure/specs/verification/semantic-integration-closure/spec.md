## Purpose

Не допускать публикацию неполного verification plan при изменении repository-owned semantic surfaces с широкими downstream effects, сохраняя bounded FAST для локальных изменений.

## ADDED Requirements

### Requirement: Deterministic protected semantic surface classification
Planner SHALL без runtime LLM judgement классифицировать изменённые repository paths по repository-owned closed set, который как минимум различает persistence semantics, schema/migration/frontier, authoritative current-state/domain-contract и recovery/restore representation. Классификация SHALL зависеть от существующих boundaries и минимальной metadata в действующей planner policy, а не от расширения файла само по себе.

#### Scenario: Authoritative current-state owner
- **WHEN** public prepare получает изменение authoritative owner текущего domain state
- **THEN** plan помечает соответствующую semantic surface и требует integration closure

#### Scenario: Persistence semantics
- **WHEN** public prepare получает изменение persistence semantic owner
- **THEN** plan требует integration closure

#### Scenario: Schema migration or frontier
- **WHEN** public prepare получает изменение schema, migration либо canonical frontier owner
- **THEN** plan требует integration closure

#### Scenario: Recovery representation
- **WHEN** public prepare получает изменение recovery/restore representation сохраняемого state
- **THEN** plan требует integration closure

#### Scenario: Presentation-only PHP
- **WHEN** public prepare получает bounded server-rendered presentation-only изменение
- **THEN** planner не добавляет semantic integration escalation только из-за PHP или view path

#### Scenario: Lifecycle documentation
- **WHEN** public prepare получает только docs/OpenSpec lifecycle metadata без protected semantic owner
- **THEN** planner не добавляет semantic integration escalation

#### Scenario: Mixed UI and persistence
- **WHEN** change одновременно содержит UI path и protected persistence semantic path
- **THEN** plan требует integration closure из-за persistence surface

### Requirement: Canonical category-level integration closure
Для любой обнаруженной protected semantic surface planner MUST добавить обязательную integration category и зарегистрированный verifier set из canonical `tools/verification/suites.tsv`. Planner MUST NOT создавать или читать второй manifest/category registry и MUST NOT вычислять transitive consumer graph.

#### Scenario: Direct checks insufficient
- **WHEN** direct/unit acceptance checks существуют и GREEN, но protected semantic change не имеет выполненной required integration closure
- **THEN** plan не является publication-ready

#### Scenario: Closure present
- **WHEN** та же protected semantic change получает обязательную integration category и её canonical registered verifier set
- **THEN** plan проходит проверку полноты Slice A при остальных valid inputs

#### Scenario: No registered verifier
- **WHEN** protected semantic surface требует integration, а canonical inventory не содержит применимого зарегистрированного integration verifier
- **THEN** public prepare завершается ненулевым status с точной deterministic diagnostic до PR

### Requirement: Machine-readable escalation explanation
Planner output SHALL для каждого semantic escalation содержать changed path/surface, стабильную причину обязательности closure, required category и фактически добавленные registered checks.

#### Scenario: Structured reason
- **WHEN** protected semantic path вызывает escalation
- **THEN** JSON plan позволяет машине однозначно связать surface и path с `integration` и добавленными checks без разбора prose

### Requirement: Existing FAST semantics remain bounded
Slice A MUST сохранять lane и selected-check semantics существующего healthy FAST fixture и MUST NOT расширять FAST либо делать integration обязательной для всех STANDARD/CRITICAL changes.

#### Scenario: Healthy FAST fixture
- **WHEN** existing bounded FAST fixture не затрагивает protected semantic surface
- **THEN** его lane, required reviews и selected-check semantics остаются прежними
