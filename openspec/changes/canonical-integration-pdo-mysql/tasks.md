## 1. Gate 1 и verification plan

- [x] 1.1 Root создаёт stable executable spec для `CANONICAL-INTEGRATION-RUNTIME-001`, сопоставляет все scenarios из delta spec с public `run-in-profile integration` seam и проверяет отсутствие product/application semantics.
- [x] 1.2 Root создаёт `verification-input.json`, запускает `python3 tools/delivery/harness.py prepare` для актуального exact source и проверяет, что все planner obligations разрешены; lane и `required_reviews` принимаются только из сгенерированного plan.

## 2. Gate 2 RED и обязательное test review

- [x] 2.1 Root пишет bounded regression, который внутри реально исполняемого `integration` image проверяет `pdo_mysql`/PDO MySQL, source/dependency origin и отсутствие host fallback; RED на unchanged main обязан указывать на отсутствующий driver.
- [x] 2.2 Root связывает disposable MariaDB с настраиваемым изолированным host port и фиксирует RED обязательной команды `tools/delivery/run-in-profile integration php -d display_errors=0 tests/Yii2/yii2_user_access_001_test.php`, доходящей до текущего driver blocker без изменения application expectation.
- [x] 2.3 Если planner требует Gate 3, независимый reviewer проверяет полный spec/test/RED candidate и записывает `APPROVED` в `reviews/tests/CANONICAL-INTEGRATION-RUNTIME-001.md`; любой `CHANGES_REQUESTED` исправляется root до реализации.

## 3. Gate 4 minimal implementation и focused GREEN

- [x] 3.1 Отдельный executor меняет только existing focused-check image recipe, добавляя `pdo_mysql` без смены PHP/MariaDB versions, profile topology или application code; diff подтверждает bounded scope.
- [x] 3.2 Через public integration profile подтвердить loaded `pdo_mysql`, container-owned PHP/vendor/source origins и GREEN обязательной DB-backed Yii команды с disposable MariaDB и изолируемым host port.
- [x] 3.3 Запустить existing #123-A cases K/L/M и применимые #167 profile/bootstrap regressions, OpenSpec strict validation, planner-selected bounded checks и `git diff --check`; полный локальный `make test`/`make verify` не запускать.

## 4. Gate 5, exact-source CI и PR-ready

- [x] 4.1 Подготовить exact-source role package и передать отдельному независимому reviewer полный candidate, focused evidence и предыдущие required reviews; записать final `APPROVED` в `reviews/code/CANONICAL-INTEGRATION-RUNTIME-001.md` либо исправить все findings с повторным review изменённого delta.
- [ ] 4.2 На exact committed source один раз запустить planner-selected existing GitHub CI consumer, собрать полный failed-job/`REGRESSION_FAILURE` inventory при сбое и не считать `UNKNOWN` или same-source retry GREEN.
- [ ] 4.3 Подготовить отдельный branch/PR с delivery record, exact source, reviews и CI GREEN; проверить, что #20/application code/production runtime/versions/unrelated Docker files не вошли, затем остановиться на PR-ready без merge.
