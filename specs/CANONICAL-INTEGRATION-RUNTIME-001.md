# CANONICAL-INTEGRATION-RUNTIME-001 — MySQL PDO в canonical integration profile

## Простыми словами

Существующий public `integration` profile должен уметь выполнить настоящий Yii route, читающий MariaDB через PDO. Проверка обязана доказать, что `pdo_mysql`, PHP, Yii и Composer dependencies находятся внутри исполняемого focused-check image; host PHP/vendor не могут дать ложный GREEN. Этот инфраструктурный срез не меняет application behavior, версии runtime и ownership disposable database.

## Actor and public seam

- Identifier: `CANONICAL-INTEGRATION-RUNTIME-001`.
- Actor: разработчик или existing CI consumer.
- Source oracle: owner request от 2026-09-16 и blocker, воспроизведённый при #20 на `origin/main` `992e32f1`.
- Public seam: `tools/delivery/run-in-profile integration <command> [args...]`.
- Required behavior command: `tools/delivery/run-in-profile integration php -d display_errors=0 tests/Yii2/yii2_user_access_001_test.php`.

## Preconditions and input

- Docker daemon доступен; candidate materialize'ится existing frozen-source mechanism #123-A.
- Existing canonical `compose.test.yaml` lifecycle снаружи launcher поднял disposable `test-db` и дождался readiness.
- Test DB host port задаётся caller/fixture и выбирается изолированно; container endpoint остаётся `test-db:3306` в canonical network. Конкретный конфликтный host port `23306` не является input/default этого contract.
- Host PHP и host dependency directories не являются prerequisites.

## Acceptance contract

### CIR001-01 — MySQL PDO принадлежит исполняемому image

Через public `integration` profile container-owned PHP MUST сообщать `extension_loaded('pdo_mysql') === true`, наличие `mysql` в `PDO::getAvailableDrivers()` и module `pdo_mysql`. Compact evidence MUST связывать command с exact frozen executable source и фактически запущенным focused-check image.

Static Dockerfile text, host `php -m` или host PHP result не являются acceptance evidence.

### CIR001-02 — DB-backed Yii execution доходит до application behavior

При ready disposable MariaDB обязательная команда MUST завершиться `0` и наблюдать ожидаемый existing `yii2_user_access_001_test.php` application behavior. Execution MUST NOT завершаться infrastructure 503 либо `PDOException: could not find driver`.

Expected assertions принадлежат существующему Yii test и не изменяются этим slice; product/application code и database schema не меняются.

### CIR001-03 — host fallback запрещён

PHP, project source, Composer autoloader и Yii MUST загружаться из read-only container-owned `/workspace` composition #123-A. Отсутствующие либо несовместимые host PHP/vendor MUST игнорироваться. Если extension или container dependency отсутствует, command MUST завершиться nonzero до ложного behavior GREEN без fallback на host, соседний checkout или runtime network install.

### CIR001-04 — service ownership и port isolation сохраняются

Launcher MUST только присоединить `integration` command к уже существующей canonical Compose network и передать `FMONITOR_TEST_DB_HOST=test-db`, `FMONITOR_TEST_DB_PORT=3306`. Он MUST NOT создавать, запускать, останавливать или удалять MariaDB, резервировать host port либо вводить новый environment manager.

Caller MAY выбрать любой свободный настраиваемый host port для disposable fixture. Выбор host port MUST NOT изменять container endpoint или image identity.

### CIR001-05 — #123-A/#167 regressions неизменны

Representative #123-A cases K/L/M для `governance`, `integration` и `browser` MUST оставаться GREEN с прежними argv, frozen source/dependency origins, read-only project composition и compact evidence semantics. Existing #167 network/service regression MUST подтверждать conditional connection только к уже существующей network.

## Rejected cases and reasons

- В image отсутствует `pdo_mysql` или PDO driver `mysql` → `INTEGRATION_PDO_MYSQL_MISSING`, nonzero; DB-backed behavior не считается проверенным.
- Disposable MariaDB/network не ready → explicit setup/integration failure, nonzero; launcher не принимает ownership lifecycle.
- PHP/Yii/vendor загружены с host path либо host dependency directory создан/изменён → `HOST_RUNTIME_FALLBACK`, nonzero.
- Required Yii command возвращает 503/driver exception → infrastructure RED, не application GREEN.
- Port `23306` занят → fixture выбирает иной свободный configured port; изменение hard-coded port вне scope.

## Authorization, audit/history, replay and concurrency

Запуск локального disposable fixture авторизует только bounded verification resources выбранного Compose project. Contract не выдаёт product permissions и не меняет domain/application facts, audit или append-only history. Повторный или параллельный запуск с distinct Compose project/host-port identity MUST сохранять candidate/image isolation; каждый launcher использует только соответствующую существующую network.

## Independently determined examples

1. В host отсутствуют `vendor/` и подходящий PHP extension; public profile печатает JSON с `extension=true`, `drivers` содержащим `mysql`, source `/workspace`, затем `CIR001_DRIVER_OK` и exit `0`.
2. Тот же image с удалённым/неустановленным `pdo_mysql` возвращает nonzero `INTEGRATION_PDO_MYSQL_MISSING`; наличие host driver не меняет результат.
3. Disposable Compose project публикует DB на свободный host port `24317`, но integration container соединяется с `test-db:3306`; обязательный Yii test заканчивается своим existing success marker/exit `0`.
4. Profiles K/L/M повторяют Yii bootstrap из `/workspace/vendor/yiisoft/yii2/Yii.php`, не создавая host `vendor/`; network lifecycle остаётся внешним.

## Done

- Quality Graph plan свеж для exact scope, obligations разрешены, planner-selected reviews выполнены.
- Intended RED на unchanged main доказывает отсутствующий image driver, не broken setup.
- Separate executor внёс minimal image recipe change; required DB-backed command, driver proof, K/L/M и #167 focused regressions GREEN.
- Independent final review `APPROVED`; один exact-source existing-consumer CI GREEN.
- Отдельный PR-ready candidate подготовлен; merge/deploy/settings не выполнялись.
