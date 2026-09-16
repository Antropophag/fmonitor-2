## Purpose

Определяет единый восстанавливаемый machine-readable context, через который live delivery harness передаёт существующему admission consumer точные plan, obligations, bindings и фактические Gate reviews.

## ADDED Requirements

### Requirement: Exact plan переживает fresh invocation
После `prepare` harness SHALL durable сохранять exact verification plan и selected obligations для task/candidate и SHALL восстанавливать их без transcript предыдущей session.

#### Scenario: Prepare затем fresh state
- **WHEN** `prepare` создал plan и завершился, а `state` запущен новым процессом
- **THEN** canonical live admission context содержит byte-identical plan identity и те же selected obligations

#### Scenario: Restart без conversation history
- **WHEN** новый Codex session/process знает только repository/worktree и external harness state
- **THEN** он восстанавливает task, plan, obligations, source и policy bindings без conversation transcript

### Requirement: Structured review result durable связан с review subject
Harness SHALL принимать и durable сохранять structured Gate result только с task/change, reviewer role/identity, фактическим verdict, exact candidate/source и applicable plan/policy binding. Candidate-specific result SHALL храниться вне candidate source tree.

#### Scenario: Current Gate approval
- **WHEN** независимый reviewer сохраняет `APPROVED` для текущих task, required Gate, source, plan и policy
- **THEN** fresh `state` возвращает structured current `APPROVED` review с этими bindings

#### Scenario: Foreign review
- **WHEN** record относится к другой task, candidate, source, plan или policy
- **THEN** context не принимает его как current approval

#### Scenario: Нет self-reference
- **WHEN** candidate-specific review result сохраняется
- **THEN** он не изменяет candidate bytes или candidate/source hash, к которому привязан

### Requirement: Freshness fail-closed
Canonical context SHALL вычислять review freshness относительно текущих exact source/candidate, task, required Gate, plan и policy. Missing либо stale result SHALL оставаться явно missing либо stale и MUST NOT преобразовываться в `APPROVED`.

#### Scenario: Source-invalidating change
- **WHEN** exact source/candidate изменяется после approval
- **THEN** ранее сохранённый review отражается как stale и не является current approval

#### Scenario: Missing review
- **WHEN** для требуемого Gate нет связанного structured result
- **THEN** context отражает missing без fabricated reviewer или `APPROVED`

### Requirement: Planner-selected review contract
Canonical context SHALL использовать `required_reviews` exact verification plan как единственный список требуемых reviews и MUST NOT синтезировать Gate, отсутствующий в plan.

#### Scenario: FAST route
- **WHEN** planner выбрал FAST plan с одним `final` review
- **THEN** context содержит только реально требуемый final review и не создаёт synthetic STANDARD Gate 3

#### Scenario: STANDARD или CRITICAL route
- **WHEN** planner выбрал STANDARD либо CRITICAL plan
- **THEN** context содержит ровно требуемые policy reviews, включая Gate 3/final только когда они присутствуют в `required_reviews`

### Requirement: Один context для live consumers
`state`, `wait` и `prepare-merge` SHALL получать одну и ту же canonical plan/review projection для одной неизменившейся task/candidate. Context SHALL передаваться существующему admission consumer без изменения semantics его решения.

#### Scenario: Три live команды
- **WHEN** `state`, `wait --once` и `prepare-merge` запускаются для одного неизменившегося external harness state
- **THEN** их canonical plan/review context идентичен

#### Scenario: Consumer compatibility
- **WHEN** live harness строит admission observation
- **THEN** он получает structured current reviews из canonical context, а решение продолжает принимать существующий admission evaluator

