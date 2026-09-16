## Purpose

Определяет container-owned PHP runtime, необходимый public `integration` profile для воспроизводимого выполнения DB-backed Yii проверок без зависимости от host PHP или host Composer dependencies.

## ADDED Requirements

### Requirement: Integration profile предоставляет MySQL PDO runtime

Public seam `tools/delivery/run-in-profile integration <command> [args...]` SHALL исполнять команду в canonical focused-check image, где загружен `pdo_mysql` и доступен MySQL PDO driver. Runtime MUST происходить из исполняемого immutable image, а не из host PHP, host `vendor/`, соседнего checkout или runtime network install.

#### Scenario: Driver виден внутри исполняемого image

- **WHEN** caller через public `integration` profile запрашивает loaded PHP modules и available PDO drivers
- **THEN** container-owned PHP сообщает `pdo_mysql` и MySQL PDO driver, а compact evidence связывает execution с focused-check image и frozen executable source

#### Scenario: Host fallback недоступен

- **WHEN** host PHP или host Composer dependencies отсутствуют либо содержат несовместимую fixture
- **THEN** результат определяется только container-owned runtime/source/dependencies; missing image driver MUST fail nonzero и не может быть скрыт host fallback

### Requirement: DB-backed Yii command доходит до application behavior

При существующей disposable MariaDB в canonical test-service network `integration` profile SHALL выполнить команду `php -d display_errors=0 tests/Yii2/yii2_user_access_001_test.php` через frozen candidate и container-owned Composer/Yii runtime. Успех SHALL означать ожидаемый test application behavior, а не HTTP 503 или иной setup failure из-за отсутствующего PDO driver.

#### Scenario: Обязательный DB-backed regression GREEN

- **WHEN** внешний bounded fixture lifecycle поднимает disposable canonical `test-db`, а обязательная команда запущена через `tools/delivery/run-in-profile integration`
- **THEN** test завершается GREEN и не наблюдает `PDOException: could not find driver` или infrastructure 503

#### Scenario: DB service отсутствует или недоступен

- **WHEN** disposable MariaDB не поднята или объявленная canonical network недоступна
- **THEN** execution завершается явным nonzero setup/integration failure без создания нового DB lifecycle owner и без изменения application facts

#### Scenario: Изолируемый DB endpoint

- **WHEN** fixture использует настраиваемый изолированный host port, отличный от занятого `23306`
- **THEN** container подключается к `test-db:3306` внутри canonical network, и выбор host port не зашит в focused-check image или launcher

### Requirement: Existing profile и bootstrap contracts сохраняются

Change MUST сохранять #123-A immutable source/dependency composition и #167 existing test-service networking. Representative cases K, L и M для `governance`, `integration` и `browser` MUST оставаться GREEN с прежними argv, source identity, service ownership и compact evidence semantics.

#### Scenario: Representative K/L/M regressions

- **WHEN** existing #123-A representative bootstrap checks запускаются для profiles `governance`, `integration` и `browser`
- **THEN** каждый profile загружает frozen candidate и container-owned Composer/Yii dependencies с ожидаемым GREEN без host dependency creation

#### Scenario: Application semantics и state неизменны

- **WHEN** runtime capability добавлена и проверки повторяются или выполняются параллельно на изолированных disposable fixtures
- **THEN** authorization, audit/history, idempotency/concurrency и persisted application facts остаются определёнными существующими Yii contract tests; этот slice не добавляет и не изменяет application state transition
