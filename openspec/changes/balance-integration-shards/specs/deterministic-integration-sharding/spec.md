## Purpose

Стабильно уменьшать ожидаемую critical-shard длительность полного Quality Graph, сохраняя полный canonical integration coverage и fail-closed результат двух существующих jobs.

## ADDED Requirements

### Requirement: Canonical membership остаётся единственным источником состава
Планировщик MUST получать полный integration set только из `suites.tsv`; historical performance hints MUST влиять лишь на weight. Stale hint для отсутствующего пути MUST игнорироваться, а новый canonical test без hint MUST получить документированный положительный fallback weight и исполниться ровно один раз.

#### Scenario: Новый test без history
- **WHEN** canonical integration inventory содержит новый путь без performance hint
- **THEN** union двух shards содержит путь ровно один раз

#### Scenario: Удалённый test остаётся только в history
- **WHEN** performance hints содержат путь, отсутствующий в canonical inventory
- **THEN** ни один shard не содержит этот путь

### Requirement: Два shards распределяются deterministic LPT
Планировщик MUST отсортировать canonical tests по descending effective weight с path tie-break, затем назначать очередной test shard с меньшим accumulated weight, а при равенстве — shard с меньшим индексом. Одинаковые inventory и hints MUST давать одинаковое распределение независимо от input order.

#### Scenario: Skewed fixture
- **WHEN** fixture имеет веса, на которых прежний sorted round-robin создаёт больший maximum estimated load
- **THEN** LPT allocation имеет строго меньший maximum estimated load

#### Scenario: Перемешанный input
- **WHEN** те же inventory rows и weights поступают в ином порядке
- **THEN** allocation обоих shards не меняется

### Requirement: Invalid hints fail safely without dropping tests
Missing, unreadable, malformed, duplicate, non-finite, zero или negative hint MUST приводить к deterministic documented fallback и диагностике, но MUST NOT исключать canonical test или создавать duplicate. Union MUST равняться integration category, intersection MUST быть пустым.

#### Scenario: Missing или corrupt hints
- **WHEN** hints отсутствуют или содержат invalid values
- **THEN** каждый canonical integration test остаётся запланирован ровно в одном shard с deterministic allocation

### Requirement: Quality Graph compatibility сохраняется
Workflow MUST сохранять ровно две integration jobs с существующими required result names. Failure, cancellation или absence любого shard MUST оставлять aggregate Quality Graph failed/not-approved. Test semantics, category membership, №153 semantic closure и FAST policy MUST оставаться неизменными.

#### Scenario: Один shard завершается ошибкой
- **WHEN** любой из двух обязательных integration shards не сообщает success
- **THEN** overall verification не сообщает GREEN

#### Scenario: Existing workflow topology
- **WHEN** workflow проверяется статически
- **THEN** в нём остаются два shard index и прежнее имя `Integration (<index>/2)`

### Requirement: Performance claims разделяют estimate и measurement
Delivery evidence MUST показывать fresh comparable FULL baseline, old/new estimated loads и predicted critical-shard effect отдельно от exact-source FULL CI job timings. Setup MAY получить не более одной оптимизации только после подтверждения повторной заметной стоимости тремя bounded before и тремя bounded after измерениями одного profile; иначе evidence MUST содержать `NO_SAFE_SETUP_OPTIMIZATION_FOUND`. Token/cost MUST оставаться `UNKNOWN` без supported telemetry.

#### Scenario: Безопасный setup candidate не подтверждён
- **WHEN** профиль не доказывает повтор, заметную стоимость и сохранение contract простым изменением
- **THEN** setup code не оптимизируется и результат явно фиксируется как `NO_SAFE_SETUP_OPTIMIZATION_FOUND`

