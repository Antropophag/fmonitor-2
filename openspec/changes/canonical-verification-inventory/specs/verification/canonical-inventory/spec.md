## Purpose

Определяет единый fail-closed реестр executable verification tests и публичные маршруты его валидации, регистрации, планирования и CI composition.

## ADDED Requirements

### Requirement: Единственный canonical inventory
Репозиторий SHALL хранить legacy suite, runtime, path и CI category каждого canonical test ровно в одной строке `tools/verification/suites.tsv`. Другого вручную синхронизируемого roster или category mapping MUST NOT существовать. Допустимы только legacy suites `unit`, `db`, `characterization`, `e2e`, runtimes `php`, `node`, `python3` и CI categories `unit`, `integration`, `e2e`, `governance`.

#### Scenario: Миграция текущего roster
- **WHEN** текущие 427 записей мигрированы в canonical формат
- **THEN** прежние path, legacy suite, runtime и CI category каждой записи сохранены без потерь и дублей

#### Scenario: Невалидная строка
- **WHEN** manifest содержит duplicate path, отсутствующий файл, неизвестный suite, runtime или category либо malformed path/row
- **THEN** validator завершается ненулево до исполнения тестов с точной диагностикой причины

#### Scenario: Детерминированное представление
- **WHEN** два входа задают одинаковый логический inventory в разном порядке
- **THEN** canonical representation и category composition побайтно одинаковы и упорядочены детерминированно

### Requirement: Атомарная регистрация теста
Repository-owned registration command SHALL требовать `FILE`, `CATEGORY`, `RUNTIME` и `SUITE`, валидировать их без эвристического определения suite, изменять только `suites.tsv`, записывать manifest атомарно и после записи выполнять тот же canonical validator. Ошибка MUST сохранять исходные байты manifest.

#### Scenario: Успешная регистрация
- **WHEN** существующий незарегистрированный test передан с допустимыми FILE, CATEGORY, RUNTIME и SUITE
- **THEN** command добавляет его ровно один раз, сохраняет canonical ordering, validator возвращает GREEN, а test появляется в указанной legacy suite и CI category

#### Scenario: Регистрация отклонена
- **WHEN** FILE отсутствует или уже зарегистрирован либо любой enum неизвестен
- **THEN** command завершается ненулево и manifest остаётся побайтно неизменным

### Requirement: Раннее обнаружение незарегистрированных тестов
Общий validator SHALL обнаруживать canonical tests по существующей repository discovery policy. Public change-verification prepare/build seam MUST дополнительно проверять новые `tests/**` из candidate changes и до Gate 3 отклонять новый canonical executable test, отсутствующий в manifest, сообщением `UNREGISTERED_TEST: <path>`. Игнорируемые, generated, fixture/helper и иные non-canonical artifacts по действующей policy MUST NOT давать false positive.

#### Scenario: Новый незарегистрированный test
- **WHEN** candidate добавляет canonical executable `tests/**` и не регистрирует его
- **THEN** prepare/build завершается до Gate 3 с `UNREGISTERED_TEST: <path>`

#### Scenario: Новый non-canonical artifact
- **WHEN** candidate добавляет `tests/**` artifact, который действующая discovery policy классифицирует как ignored, generated, fixture или helper
- **THEN** inventory validation не требует его регистрации

### Requirement: Полная и однократная CI composition
CI category composition SHALL полностью воспроизводиться из canonical manifest: union категорий равен всем manifest paths, каждый path встречается ровно один раз, и missing, duplicate или unknown entry завершают selection до исполнения. Существующие category semantics и fail-closed Quality Graph aggregation MUST сохраняться.

#### Scenario: Полный category union
- **WHEN** consumer запрашивает все четыре CI categories
- **THEN** их union содержит каждую canonical запись ровно один раз с сохранённым runtime и без отсутствующих либо дополнительных paths

#### Scenario: Quality Graph coverage повреждён
- **WHEN** category composition или обязательный Quality Graph result отсутствует, продублирован либо неуспешен
- **THEN** существующий CI selection/aggregation seam отклоняет evidence

### Requirement: Разделение fast validation и governance execution
Fast job SHALL выполнять только дешёвую canonical inventory/schema/discovery validation. `tests/Verification/verification_ci_001_test.py` SHALL оставаться обязательным governance test и MUST NOT непосредственно исполняться fast job. Изменение inventory или verification policy MUST NOT получать FAST bypass; существующий FAST classifier и boundaries MUST оставаться неизменными.

#### Scenario: Full Quality Graph run
- **WHEN** full CI запускает fast и governance jobs
- **THEN** fast валидирует inventory без запуска `verification_ci_001_test.py`, а governance исполняет этот test ровно один раз

#### Scenario: Inventory drift в fast
- **WHEN** manifest имеет missing path, duplicate, invalid enum или незарегистрированный discovered canonical test
- **THEN** fast validation завершается ненулево до category execution

#### Scenario: Изменение verification policy
- **WHEN** candidate изменяет canonical inventory или verification policy
- **THEN** существующий planner сохраняет не-FAST classification согласно текущим boundaries
