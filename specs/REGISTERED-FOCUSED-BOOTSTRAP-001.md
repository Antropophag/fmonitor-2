# REGISTERED-FOCUSED-BOOTSTRAP-001 — автономный запуск адресного Yii2-теста

## Простыми словами

Одна штатная prepared-команда должна запускать настоящий зарегистрированный Yii2-тест из чистого worktree. Она сама готовит закреплённые зависимости и свою MariaDB в Docker, проверяет именно текущий candidate и не пользуется чужими checkout/dependencies. Это не меняет продуктовую навигацию и не превращает все integration-тесты в browser-профиль.

## 1. Actor и public seam

Actor: delivery operator или агент. Public seam: prepared plan/package command, переданная в existing `tools/delivery/harness.py run`, которая для зарегистрированного `tests/Yii2/yii2_main_navigation_001_test.php` исполняется existing `tools/delivery/run-in-profile browser --with-services` route. Малый `--with-services` alias включает owned service lifecycle только для planner-зарегистрированной команды; обычные `integration`/`browser` вызовы сохраняют прежний внешний lifecycle.

Preconditions: Git candidate доступен snapshot-механизму; на host доступны Git, Docker/Compose и launcher prerequisites. Host PHP/Composer/Node, `vendor`, `node_modules`, соседний `shlz-ui` и заранее поднятая MariaDB не являются preconditions.

## 2. Normative acceptance

### RFB001-A — первый чистый запуск

Из disposable worktree без `vendor`, `node_modules` и sibling `shlz-ui` одна prepared-команда MUST:

1. выбрать dependency/profile из existing registrations;
2. materialize exact candidate snapshot с tracked и uncommitted bytes;
3. построить pinned dependencies по repository lockfiles/pins внутри container build;
4. предоставить pinned `shlz-ui` public assets для Yii HTTP;
5. создать owned Compose project, поднять test MariaDB и bounded ждать health/readiness;
6. запустить только `php tests/Yii2/yii2_main_navigation_001_test.php`;
7. завершиться GREEN после настоящих HTTP assertions baseline.

### RFB001-B — повторный запуск

При неизменных dependency inputs повтор той же команды MUST переиспользовать Docker dependency layers/image. Ручная подготовка и очистка общего Docker cache запрещены. Если измеряется время, отдельно фиксируются setup/build и test execution; экономия токенов не выводится.

### RFB001-C — source isolation

Application classes MUST загружаться из materialized candidate snapshot. Dependencies и public `shlz-ui` assets MUST загружаться из его pinned container environment. Host dependency directories и соседние checkouts MUST NOT монтироваться или использоваться fallback-ом.

Незакоммиченный контролируемый дефект наблюдаемого navigation behavior MUST сделать эту же команду RED на соответствующем assertion; удаление дефекта MUST вернуть GREEN. Это witness, не production feature и не mutation framework.

### RFB001-D — setup failure semantics и lifecycle

Отсутствующая test DB MUST подниматься автоматически. Недоступный Docker, dependency build failure и DB readiness timeout MUST возвращать `SETUP_FAILURE` до assertions, указывать stage и retained-log path existing evidence store. Они MUST NOT считаться `INTENDED_RED`.

HTTP 503 является setup failure только при установленной launcher/DB причине. Обычный test failure и interruption сохраняют действующую классификацию.

Каждый запуск MUST владеть уникальным Compose project. Cleanup MUST затрагивать только его контейнеры/network/volumes и MUST выполняться при success, failure и interruption. Общая БД и чужие контейнеры не сбрасываются и не останавливаются.

### RFB001-E — prepared route и identity

Planner/package MUST реально выдавать новый route для зарегистрированного теста. Existing command-id, purpose, acceptance mapping, intended RED и evidence schema/store сохраняются. Оператор выбирает test/command-id, а не воспроизводит Composer/npm/DB bootstrap вручную. Generic diagnostic harness commands не получают безусловное Docker wrapping.

## 3. Rejections и неизменные области

- Unknown profile, missing command и незарегистрированная попытка heavy promotion MUST быть отклонены до child execution.
- Все integration tests автоматически в browser profile не переводятся; адресная registration выбранного Yii-теста может использовать подходящий existing profile с нужными assets.
- Generic `run-in-profile integration|browser` без `--with-services` MUST NOT поднимать или останавливать service resources и сохраняет существующий contract подключения к уже доступной declared network.
- Не меняются product navigation, production error handler, database schema, FAST classifier, review/CI admission и evidence schema.
- State-changing domain facts отсутствуют; authorization, audit/history, replay и concurrency product semantics неприменимы. Infrastructure ownership/isolation проверяются unique project identity и cleanup.
