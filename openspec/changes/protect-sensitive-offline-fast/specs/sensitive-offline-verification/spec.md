## Purpose

Гарантирует, что verification planner детерминированно запрещает FAST для UI assets, владеющих private offline state, cache lifecycle и synchronization/replay.

## ADDED Requirements

### Requirement: Sensitive offline assets use the protected route
Public planner SHALL classify the repository-owned closed set `app/YiiRuntime/Assets/checklist-sw.js`, `app/YiiRuntime/Assets/checklist.js` and `app/YiiRuntime/Assets/control-queue.js` as a named sensitive boundary, SHALL select CRITICAL, and SHALL emit a machine-readable escalation naming that boundary.

#### Scenario: Каждый sensitive asset запрещает FAST
- **WHEN** effective change содержит любой из трёх protected paths
- **THEN** `verification_lane` равен `CRITICAL`, `required_reviews` содержит Gate 3 и final review, а `escalations` содержит конкретную sensitive boundary reason

#### Scenario: Mixed UI не ослабляет route
- **WHEN** sensitive path изменён вместе с bounded presentation-only UI path
- **THEN** итоговый lane остаётся `CRITICAL`

#### Scenario: Direct oracle не возвращает FAST
- **WHEN** acceptance для sensitive path отображён на GREEN direct offline oracle
- **THEN** planner всё равно сохраняет `CRITICAL` и включает зарегистрированный oracle

### Requirement: Existing mechanisms compose monotonically
Sensitive boundary classification SHALL use existing boundary policy and canonical `suites.tsv`, SHALL compose with semantic integration closure, and neither mechanism SHALL lower the stricter selected lane or remove selected verification categories/checks.

#### Scenario: Sensitive и semantic paths вместе
- **WHEN** effective change содержит sensitive offline asset и #153A semantic surface
- **THEN** план сохраняет CRITICAL sensitive escalation и machine-readable semantic integration escalation со всеми требуемыми категориями

#### Scenario: Verification policy changes
- **WHEN** effective change меняет саму verification policy
- **THEN** существующая delivery-policy boundary запрещает FAST

### Requirement: Presentation and metadata remain un-escalated by proximity
Paths outside the explicit protected set MUST NOT receive sensitive escalation solely because they are JavaScript, Yii presentation files, server-rendered Yii views, documentation or lifecycle metadata.

#### Scenario: Presentation-only asset сохраняет FAST
- **WHEN** healthy existing bounded-ui asset изменён отдельно и остальные FAST условия выполнены
- **THEN** planner сохраняет FAST

#### Scenario: Server-rendered presentation рядом с checklist
- **WHEN** изменён presentation-only Yii view без sensitive или semantic path
- **THEN** planner не добавляет sensitive CRITICAL escalation

#### Scenario: Docs metadata
- **WHEN** изменён только docs/lifecycle metadata path
- **THEN** planner не добавляет sensitive escalation

### Requirement: Classification is deterministic and conservative
The protected set SHALL be repository-owned path policy without runtime LLM or function-level/AST judgement. A path inside the protected offline set remains sensitive even when a particular diff appears presentation-only; unknown or ambiguous boundary classification SHALL continue to fail closed.

#### Scenario: Mixed-responsibility checklist file
- **WHEN** изменён `checklist.js`, который совмещает presentation и persisted offline responsibilities
- **THEN** весь файл классифицируется sensitive без анализа отдельных функций или строк

#### Scenario: Unknown boundary
- **WHEN** effective path не имеет одной однозначной policy boundary
- **THEN** planner завершается `SETUP_FAILURE`, а не FAST
