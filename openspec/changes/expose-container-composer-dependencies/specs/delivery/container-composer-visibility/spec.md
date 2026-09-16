## Purpose

Обеспечивает Yii entrypoints exact candidate checkout locked Composer dependencies из immutable container layer через canonical execution profile без host `vendor/` и cross-worktree mutable state.

## ADDED Requirements

### Requirement: Candidate source и locked dependencies компонуются в canonical profile
`tools/delivery/run-in-profile <profile> <command> [args...]` SHALL выполнять project code из materialized existing harness frozen candidate snapshot и SHALL предоставлять его repository-relative Composer paths из отдельного container-managed dependency layer, построенного по `composer.lock` этого candidate. Snapshot SHALL сохранять применимые additions, deletions и executable modes; его existing executable identity MUST совпадать с image label и compact execution evidence. Bootstrap MUST NOT требовать host `vendor/`, `node_modules`, `.venv`, dependency mountpoints, копировать или связывать dependencies между worktrees либо использовать shared writable dependency tree.

#### Scenario: Fresh clean worktree достигает Yii bootstrap
- **WHEN** checkout не содержит host `vendor/` и actor запускает representative Yii bootstrap через canonical profile
- **THEN** команда выводит `YII_BOOTSTRAP_OK`, а host `vendor/` остаётся отсутствующим

#### Scenario: Второй независимый clean worktree
- **WHEN** тот же locked source запускается из второго clean worktree
- **THEN** Yii bootstrap также достигается без dependency copy или symlink между worktrees и без разделяемого mutable runtime state

#### Scenario: Изменённый candidate source имеет приоритет
- **WHEN** project class в candidate checkout отличается от image build context или соседнего checkout
- **THEN** canonical profile загружает project class из текущего candidate checkout, сохраняя third-party dependency origin в container layer

#### Scenario: Host изменён после freeze
- **WHEN** existing harness snapshot frozen, а исходный host checkout затем изменён
- **THEN** canonical profile исполняет frozen bytes и identity, не более позднее host состояние

#### Scenario: Candidate deletion и mode
- **WHEN** frozen candidate удаляет tracked project file или меняет применимый executable mode
- **THEN** materialized execution source сохраняет отсутствие файла и mode candidate

#### Scenario: Candidate source read-only
- **WHEN** command пытается изменить project source внутри execution container
- **THEN** изменение отклоняется, а явно разрешённый temp/artifact/runtime location остаётся writable

### Requirement: Host dependency state не является входом canonical profile
Canonical profile MUST игнорировать отсутствующие, stale или чужие host dependency trees и MUST NOT создавать repository-local `vendor/`, `node_modules`, `.venv` или dependency mountpoints при cold или warm запуске.

#### Scenario: На host присутствует stale vendor
- **WHEN** checkout содержит чужой или stale `vendor/`, а actor запускает Yii bootstrap через canonical profile
- **THEN** third-party classes загружаются из container-managed locked dependency location, а host tree не используется и не изменяется

#### Scenario: Warm repeat не материализует host dependencies
- **WHEN** actor повторяет успешный canonical запуск с уже построенным соответствующим image
- **THEN** Yii bootstrap снова успешен, свежий frozen candidate identity соблюдён и host dependency directories не появляются

### Requirement: Dependency identity соответствует текущему locked input
Dependency layer SHALL однозначно соответствовать canonical dependency recipe, immutable image inputs и текущему `composer.lock`. Изменение locked input MUST штатно инвалидировать прежнюю identity; профиль MUST NOT молча выполнить candidate с dependencies от другого lock input.

#### Scenario: Изменённый lock в disposable fixture
- **WHEN** disposable candidate имеет другой `composer.lock`
- **THEN** canonical route либо строит и использует соответствующий новый dependency layer, либо завершается setup failure до Yii behavior; прежняя dependency identity не принимается

#### Scenario: Неизменный lock допускает immutable layer reuse
- **WHEN** два worktrees имеют одинаковые canonical dependency inputs
- **THEN** они MAY использовать одинаковые immutable image layers, оставаясь изолированными по mutable runtime state

#### Scenario: Missing или corrupt dependency не вызывает network install при execution
- **WHEN** executing image не имеет валидного locked dependency layer
- **THEN** command fail closed до behavior без host/network fallback; dependency resolution выполняется только image preparation stage

### Requirement: Missing или corrupt dependency layer завершается fail closed
Если container-managed Composer dependency location отсутствует, повреждена или не соответствует lock input, canonical route MUST завершиться ненулевым setup/command failure до Yii behavior и MUST NOT fallback на host `vendor/`.

#### Scenario: Dependency autoload отсутствует в executing container
- **WHEN** representative Yii command запускается с отсутствующим или повреждённым container dependency bootstrap
- **THEN** команда завершается ненулевым raw environment failure без `YII_BOOTSTRAP_OK` и без чтения host dependencies

### Requirement: Existing profiles и source remain compatible
Изменение SHALL сохранять argv/exit/evidence contract всех трёх existing profiles и SHALL не изменять tracked source или lockfiles во время preparation/execution. Governance SHALL оставаться работоспособным; integration и browser SHALL использовать ту же dependency seam без bootstrap regression, когда их внешние prerequisites доступны.

#### Scenario: Governance regression
- **WHEN** существующий governance profile contract выполняется через public route
- **THEN** он остаётся GREEN с прежним argv, exit и compact evidence contract

#### Scenario: Integration и browser bootstrap
- **WHEN** representative integration и browser commands загружают Composer/Yii bootstrap через canonical route
- **THEN** они используют container-managed dependency seam и не требуют host `vendor/`

#### Scenario: Preparation сохраняет tracked inputs
- **WHEN** image preparation и representative cold/warm runs завершаются
- **THEN** `composer.lock` и tracked candidate source byte-for-byte неизменны

#### Scenario: Два разных candidate изолированы
- **WHEN** два worktrees с разными project markers исполняются последовательно или параллельно
- **THEN** каждый container видит только свой frozen source и matching dependency identity

### Requirement: Slice A не меняет classification или domain semantics
Этот slice MUST NOT классифицировать setup failure как `INTENDED_RED`, добавлять worktree identity guard, создавать новый environment manager/profile или менять product/domain behavior, authorization, audit/history либо concurrency semantics.

#### Scenario: Raw setup failure остаётся вне classification scope
- **WHEN** container dependency bootstrap повреждён
- **THEN** public command сообщает ненулевой raw failure; итоговая harness classification остаётся неизменённой этим slice
