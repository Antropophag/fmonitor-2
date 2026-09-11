## Purpose

Устанавливает полный наблюдаемый контракт delivery harness и его связей с verification tooling, чтобы ложный GREEN и рассинхронизация метаданных выявлялись локально до публикации кандидата.

## ADDED Requirements

### Requirement: Публичный runner согласует outcome и процессный exit
Публичная команда delivery runner SHALL классифицировать завершение child-процесса по полным stdout/stderr, завершению процесса и явно заданному ожидаемому RED-маркеру. Она MUST возвращать нулевой CLI exit только для `GREEN`; `REGRESSION_FAILURE`, `INTENDED_RED`, `SETUP_FAILURE`, `UNKNOWN` и `INTERRUPTED` MUST возвращать ненулевой exit с сохранением фактического и raw child return code. Управляющие `SETUP_FAILURE` и `UNKNOWN` SHALL распознаваться только как отдельный marker в начале строки, а не как substring domain-данных.

#### Scenario: Обычный GREEN
- **WHEN** child завершается с кодом `0` и не выводит управляющий marker
- **THEN** JSON summary и retained record имеют outcome `GREEN`, shell exit равен `0`, а child codes сохранены как успешные

#### Scenario: Обычная регрессия
- **WHEN** child завершается с ненулевым кодом без согласованного RED-маркера
- **THEN** outcome равен `REGRESSION_FAILURE`, shell exit ненулевой и retained record сохраняет фактический child code и оба потока

#### Scenario: Ожидаемый RED
- **WHEN** child завершается с ненулевым кодом и полный вывод содержит явно переданный ожидаемый RED-маркер
- **THEN** outcome равен `INTENDED_RED`, shell exit остаётся ненулевым и record однозначно связывает marker с этим запуском

#### Scenario: Управляющий marker при успешном child exit
- **WHEN** child завершается с кодом `0`, но отдельная строка stdout или stderr начинается с `SETUP_FAILURE:` либо `UNKNOWN:`
- **THEN** outcome соответствует marker, shell exit ненулевой, а raw child code остаётся `0`

#### Scenario: Прерывание сигналом или timeout
- **WHEN** child завершается сигналом либо превышает bounded timeout
- **THEN** outcome равен `INTERRUPTED`, shell exit ненулевой и retained record различает raw signal return и публичный нормализованный exit

#### Scenario: Domain identifier содержит UNKNOWN
- **WHEN** успешно завершившийся child выводит domain identifier, содержащий `UNKNOWN` не в позиции отдельного управляющего marker
- **THEN** outcome остаётся `GREEN` и shell exit равен `0`

#### Scenario: Изоляция retained effects
- **WHEN** любой table-driven case завершён
- **THEN** harness создаёт только собственный append-only record и связанные логи во внешнем evidence home, не меняет source checkout и не повреждает записи других запусков

### Requirement: Публичные delivery seams совместимы end-to-end
Harness SHALL проверять реальными публичными командами и возвращёнными артефактами цепочки `prepare -> plan -> check -> refresh -> run`, `hook -> binding -> context -> plan`, `runner -> verification wrapper -> CI aggregate` и `Gate 3 evidence -> reviewer package`. Произвольный внешний plan, несопоставимое evidence или неполный package MUST отклоняться fail-closed.

#### Scenario: Plan проходит полный lifecycle
- **WHEN** root готовит package из допустимого verification input и использует возвращённый plan в check, refresh и run
- **THEN** каждая следующая публичная команда принимает именно возвращённый артефакт, сохраняет mapping acceptances и выдаёт согласованный результат

#### Scenario: Недопустимый внешний plan
- **WHEN** caller подменяет возвращённый plan произвольным путём вне разрешённого evidence state
- **THEN** downstream команда отклоняет plan до запуска verification commands и сохраняет отсутствие побочных эффектов

#### Scenario: Hook контекст использует active binding
- **WHEN** repository hook получает допустимое событие после prepare
- **THEN** контекст ссылается на worktree-specific binding, актуальный downstream plan, contracts и source identity без превращения `UNKNOWN` observations в approval

#### Scenario: Gate 3 package отвергает несопоставимое evidence
- **WHEN** package содержит same-source, но unrelated evidence, не mapped к acceptance
- **THEN** публичная проверка package возвращает ненулевой результат и не считает Gate 3 готовым

### Requirement: Active binding изолирован по worktree
Active binding, live state и generated plan SHALL иметь namespace конкретного realpath worktree при общем Git common directory. Prepare или resume одного worktree MUST NOT заменять либо читать binding другого.

#### Scenario: Два worktree готовятся независимо
- **WHEN** worktree A и worktree B последовательно выполняют prepare с разными допустимыми inputs
- **THEN** state/resume A возвращает binding A, state/resume B возвращает binding B, а повторное чтение любого worktree не меняет другой

#### Scenario: Повторный prepare идемпотентен в одном worktree
- **WHEN** один worktree повторяет prepare с тем же input, base и неизменным source
- **THEN** binding остаётся логически тем же и не создаёт конфликт с соседними worktrees

### Requirement: Verification registries согласуются до full CI
Добавление или удаление suite SHALL иметь один канонический roster либо deterministic local consistency contract, покрывающий inventory, categories, suites, plan и ожидаемую CI composition. Рассинхронизация MUST падать в bounded fast/governance verification до публикации, не ослабляя uniqueness, coverage или full CI assertions.

#### Scenario: Regression PR #91 воспроизводится локально
- **WHEN** новый E2E suite зарегистрирован поддерживаемым способом, но dependent expected composition оставлена устаревшей
- **THEN** bounded local verification завершается ненулевым кодом до full CI и указывает рассинхронизированный contract

#### Scenario: Синхронизированный roster GREEN
- **WHEN** новый suite и все derived либо проверяемые dependent registries согласованы
- **THEN** inventory, fast, governance, plan и aggregate composition проходят, а suite присутствует ровно один раз

#### Scenario: Metadata correction переиспользует допустимое evidence
- **WHEN** меняется только verification metadata, а source, fixtures и environment ранее выполненного продуктового E2E не изменились и evidence protocol разрешает reuse
- **THEN** исправление доказывает metadata contract без обязательного повторного продуктового E2E, сохраняя exact mapping и полный CI для публикуемого source

### Requirement: Критические orchestration faults наблюдаемы
Ограниченный deterministic fault-injection contract SHALL доказывать, что тестовый набор становится RED при мутациях public exit semantics, outcome guard, plan confinement, worktree namespace, control-marker parsing, acceptance-evidence mapping и suite-registry synchronization.

#### Scenario: Каждая заявленная мутация убивается
- **WHEN** по одной активируется каждая утверждённая fault injection
- **THEN** соответствующий bounded test завершается RED по ожидаемому observable mismatch, а немутированный baseline остаётся GREEN

### Requirement: Gate 3 contract перечисляет применимые observable dimensions
Verification input для изменения публичного CLI или infrastructure seam SHALL явно фиксировать применимость stdout, stderr, exit status, retained evidence, filesystem effects, idempotence, failure semantics, caller interoperability, worktree/concurrency isolation и synchronization зависимых registries. Непокрытая применимая dimension MUST блокировать готовность существующего Gate 3 без создания нового Gate.

#### Scenario: Неполный infrastructure contract отклоняется
- **WHEN** verification input объявляет изменение публичного CLI, но пропускает применимую observable dimension без явного неприменимого обоснования
- **THEN** локальная проверка input/package завершается ненулевым кодом до независимого Gate 3 review

#### Scenario: Полный contract использует существующие Gates
- **WHEN** все применимые dimensions покрыты executable evidence либо явно обоснованы как неприменимые
- **THEN** package готов для обычного независимого Gate 3 review без дополнительной стадии или reviewer
