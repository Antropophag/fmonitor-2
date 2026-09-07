## Purpose

Определяет fail-closed delivery governance, который связывает существующие SSD/TDD evidence и repository-owned проверки с точным PR/commit, не подменяя независимые review gates.

## ADDED Requirements

### Requirement: Repository-owned проверки остаются исполнимым источником истины
Delivery graph SHALL вызывать публичные repository-owned команды, а не воспроизводить внутри graph declaration тестовую, архитектурную или domain-specific логику. Результат graph SHALL быть отрицательным, если обязательная команда отсутствует, завершилась ненулевым кодом или не опубликовала ожидаемый результат.

#### Scenario: Существующая полная проверка успешна
- **WHEN** graph выполняется для commit, на котором `make verify` и все обязательные governance checks успешны
- **THEN** соответствующие nodes фиксируются успешными с provenance этого commit

#### Scenario: Repository-owned команда неуспешна
- **WHEN** любая обязательная repository-owned команда завершается ненулевым кодом
- **THEN** graph отклоняет commit и не заменяет ошибку синтетическим успехом

### Requirement: SSD/TDD lineage машинно проверяется
Repository SHALL хранить для каждого поставляемого slice immutable machine-readable lineage receipt chain, который однозначно связывает base commit, change, executable specification и её SHA-256, полный Git-derived `tests/**` name-status/hash diff, RED evidence, независимое test review, GREEN evidence, полный remaining implementation name-status/hash diff, независимое code review и exact reviewed implementation commit. Checker SHALL выводить commits всех evidence/review records только из Git history и fail closed при отсутствии, неоднозначности, hash drift, неполном diff, несовпадении commit или нестрогой Git chronology gates.

#### Scenario: Полная непротиворечивая цепочка
- **WHEN** receipt ссылается на существующие immutable evidence, exact test/implementation sets совпадают, Git ancestry доказывает Gate 2 до Gate 3 и GREEN после test approval, а Gate 5 одобряет exact implementation commit
- **THEN** lineage checker принимает slice без копирования содержимого evidence в receipt

#### Scenario: Спецификация изменилась после RED или approval
- **WHEN** SHA-256 текущей executable specification отличается от SHA-256 в downstream evidence или receipt
- **THEN** lineage checker отклоняет slice как stale lineage

#### Scenario: Review не является независимым
- **WHEN** test/code review не содержит machine-readable reviewer identity либо reviewer совпадает с автором проверяемого artifact
- **THEN** lineage checker отклоняет slice

#### Scenario: Code review относится к другому commit
- **WHEN** APPROVED code review не называет exact implementation commit либо после него изменены source/test/spec/graph файлы
- **THEN** lineage checker отклоняет slice; допускается только последующий evidence-envelope commit с review/receipt/status/parity evidence

### Requirement: Graph provenance привязан к одному запуску и topology
Каждый опубликованный результат SHALL содержать точные node identity, PR, head SHA, workflow run, run attempt и graph digest. Publisher SHALL отклонять результаты с отсутствующим или несовместимым provenance и SHALL читать доверенную topology из base branch.

#### Scenario: Результат принадлежит другому attempt
- **WHEN** result ссылается на иной run attempt, head SHA или graph digest
- **THEN** publisher не учитывает его при решении о готовности текущего PR

#### Scenario: Generated graph расходится с declaration
- **WHEN** generated graph не соответствует canonical declaration
- **THEN** graph validation завершается неуспешно до публикации положительного governance result

### Requirement: Независимые approvals не делегируются Quality Graph
Graph SHALL считать Gate 3 и Gate 5 выполненными только по repository-owned APPROVED review records, прошедшим lineage checker. Автоматические graph approvals SHALL быть отключены и не SHALL создавать или интерпретировать независимое одобрение вместо отдельно назначенного reviewer.

#### Scenario: Checks зелёные, но review отсутствует
- **WHEN** все тестовые команды успешны, но обязательный независимый APPROVED review record отсутствует
- **THEN** governance node и итоговый graph остаются неуспешными

#### Scenario: Compiler генерирует неиспользуемый command handler
- **WHEN** pinned compiler v0.1.7 создаёт publisher с `issue_comment` trigger, command/approval job или write permissions, не нужными publish/watch
- **THEN** repository SHALL не развёртывать этот generated publisher и SHALL использовать проверяемый repository-owned publisher без указанной поверхности

### Requirement: Миграция требует доказанной dual-run parity
Существующий CI/harness SHALL оставаться работающим и обязательным до сохранения parity evidence на representative PR. Parity SHALL сравнивать одинаковый head commit, набор repository-owned команд, коды завершения, обязательные failures и provenance; расхождение SHALL блокировать переключение или удаление старого механизма.

#### Scenario: Representative PR обнаруживает пропущенный failure
- **WHEN** старый harness отклоняет representative PR, а новый graph принимает тот же head commit
- **THEN** parity считается недоказанной и старый механизм остаётся обязательным

#### Scenario: Runner parity доказана, publisher bootstrap ещё не доказан
- **WHEN** незамерженный bootstrap PR доказывает локальную/CI runner parity, но новая topology отсутствует в base branch
- **THEN** migration state явно остаётся незавершённым и required checks не переключаются

#### Scenario: Полная parity доказана
- **WHEN** runner/governance parity сохранена на representative PR и отдельный trusted publisher run подтвердил base-branch topology и end-to-end provenance
- **THEN** repository может отдельно предложить переключение required checks, но текущий slice сам его не выполняет

### Requirement: Проверка безопасна при повторе и конкуренции
Повторный запуск для одинаковых PR, head SHA, run attempt и graph digest SHALL давать тот же governance verdict. Результаты параллельного или прежнего head SHALL быть изолированы и не SHALL удовлетворять gates нового head.

#### Scenario: Старый успешный run существует рядом с новым head
- **WHEN** PR получает новый head commit после успешного graph run
- **THEN** старый результат не удовлетворяет governance нового head и требуется новый полный run
