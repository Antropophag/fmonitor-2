# CHANGE-VERIFICATION-001 delta

## ADDED Requirements

### Requirement: Bound deterministic pre-Gate 2 plan
Repository SHALL вычислять canonical executable JSON plan из declared paths,
acceptance ID/spec path/public-seam mappings и полного git delta от resolved base, включая
staged, unstaged, untracked и deleted paths. Unknown/ambiguous boundaries,
malformed/duplicate mappings, omission и empty plan SHALL fail closed.
Существующий mapped acceptance test SHALL добавлять свою exact inventory category;
будущий незарегистрированный test SHALL оставаться допустимым.
Каждый changed/planned registered test SHALL добавлять свою exact category и
собственный runtime argv с deduplication.

#### Scenario: Будущий RED test
- **WHEN** acceptance отображён на ещё не созданный repository test path
- **THEN** plan содержит deterministic runtime argv и не требует synthetic pass

### Requirement: Stale and tamper rejection
Plan SHALL bind graph, inventory, policy, planner spec, все acceptance specs, input, planner source,
resolved base, HEAD и actual path/status snapshot. `check` и `run` SHALL отклонить
любое расхождение до child process.

#### Scenario: Working tree изменён после plan
- **WHEN** файл добавлен, удалён, переименован, staged или изменён после plan
- **THEN** validation fails и тестовая команда не запускается

### Requirement: Safe focused execution and retained full CI
Команды SHALL храниться как argv с phase/rationale и исполняться без shell.
Focused SHALL не выполнять DB reset или дублирующий full suite. Code/test/policy/
unknown impact SHALL сохранять `make test` в integration phase.
Category command и boundary test SHALL согласовываться со своей точной inventory
category; объединённые категории других paths не маскируют ошибку. Governance
test-only change SHALL сохранять focused plan без unrelated DB command.

#### Scenario: Gate 2 RED
- **WHEN** focused child возвращает nonzero
- **THEN** run возвращает тот же failure, не печатая synthetic success
