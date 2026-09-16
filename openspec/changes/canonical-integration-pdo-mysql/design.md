## Context

См. `proposal.md` и `specs/delivery/canonical-integration-runtime/spec.md`. На base `origin/main` `992e32f1` existing `tools/delivery/Dockerfile.focused-checks` строит общий PHP layer для трёх profiles и устанавливает `mysqli pcntl`, но не `pdo_mysql`. Existing launcher уже владеет frozen-source image execution и условным подключением `integration`/`browser` к canonical Compose network; внешний fixture владеет disposable MariaDB lifecycle.

## Goals / Non-Goals

**Goals:**

- минимально расширить общий focused-check PHP runtime поддержкой MySQL PDO;
- доказать driver внутри исполняемого image и DB-backed Yii behavior через public `integration` seam;
- сохранить exact-source/container dependency isolation #123-A и network/service ownership #167;
- оставить host DB port настраиваемым и изолируемым.

**Non-Goals:**

- изменение Yii/application behavior, persistence schema, authorization, audit/history или domain state;
- новый profile, launcher, environment manager либо DB lifecycle owner;
- смена PHP/MariaDB versions, production/deployment images или unrelated Docker optimization;
- исправление конфликта конкретного host port `23306`, продолжение #20 или расширение #123.

## Decisions

### 1. Расширить existing common focused-check layer

Добавить `pdo_mysql` к existing `docker-php-ext-install` в `tools/delivery/Dockerfile.focused-checks`. Все profiles наследуют один `common` runtime, поэтому bootstrap parity сохраняется, а отдельный integration image recipe не появляется.

Альтернатива — устанавливать extension только в target `integration` — отвергнута: она создаёт profile drift и усложняет K/L/M contract без пользы при малом extension delta.

### 2. Проверять capability через public seam, не через image internals отдельно от launcher

Root-authored regression сначала запускает public integration profile и внутри него проверяет `extension_loaded('pdo_mysql')`/PDO drivers, затем обязательный Yii test. Compact evidence и existing origin assertions доказывают, что результат принадлежит frozen source и реально исполненному image.

Альтернатива — статически искать строку в Dockerfile — недостаточна: она не доказывает, что extension собран и загружен. Host `php -m` запрещён как oracle.

### 3. Сохранить внешний disposable MariaDB lifecycle

Test setup использует existing `compose.test.yaml` и его canonical `test-db`; launcher лишь присоединяется к уже существующей network и передаёт container endpoint `test-db:3306`. Host port задаётся fixture/environment и выбирается свободным; значение `23306` не становится requirement или default этого slice.

Альтернатива — запускать MariaDB из `run-in-profile` — отвергнута, поскольку переносит service ownership в launcher и создаёт новый environment manager.

### 4. Verification наследует существующие contracts

Помимо нового intended RED/GREEN запускаются existing #123-A cases K/L/M и применимые #167 profile/bootstrap regressions. Planner определяет lane и required reviews; локально выполняются только bounded focused checks, затем один exact-source CI через existing consumer.

Owning module: delivery verification infrastructure (`tools/delivery`). Allowed dependencies: pinned existing focused-check base image, Docker extension toolchain, existing Compose test service/network. Persistence owner и rapid-pilot adapter: отсутствуют/неприменимы, поскольку slice не изменяет данные или product route. Architecture-check impact ограничен существующими delivery/verification rules; production architecture boundary не меняется.

## Risks / Trade-offs

- [Общий image layer станет немного тяжелее для всех profiles] → принять bounded cost ради единого canonical runtime; не совмещать с optimization.
- [Docker cache может скрыть неправильный source] → existing recipe/source digest и image labels сохраняются, regression проверяет loaded extension в реально запущенном image.
- [Host-port collision даст ложный infrastructure RED] → fixture выбирает настраиваемый свободный port; container всегда использует service DNS/3306.
- [GREEN может быть получен через host vendor/PHP] → запуск только через public profile плюс existing #123-A origin/cleanliness assertions; host execution не учитывается как evidence.
- [DB readiness race] → использовать existing bounded readiness/lifecycle mechanism, не добавляя ownership в launcher.

## Migration Plan

1. Зафиксировать RED нового executable contract на актуальном main: driver отсутствует и DB-backed command не доходит до application behavior.
2. После требуемого Gate 3 отдельный executor вносит минимальное изменение image recipe.
3. Выполнить focused GREEN, existing K/L/M и planner-selected checks; отдельный reviewer решает final Gate 5.
4. Запустить один exact-source CI existing consumer и подготовить отдельный PR-ready candidate. После PR-ready остановиться без merge.

Rollback: удалить `pdo_mysql` из единственной строки extension install и вернуть соответствующий bounded test/delivery slice; application/data migration отсутствует.
