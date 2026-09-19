## Purpose

Обеспечить автономный exact-source запуск одного зарегистрированного Yii2 integration-теста в чистом worktree через существующий delivery harness и его evidence store.

## ADDED Requirements

### Requirement: Registered command owns focused environment bootstrap
Prepared command для `tests/Yii2/yii2_main_navigation_001_test.php` SHALL получать профиль и зависимости из существующих verification registrations, подготовить pinned container dependencies и test MariaDB и SHALL исполнить только выбранный тест.

Owned lifecycle SHALL включаться явным addressable `--with-services` alias только у этой registered command; generic `integration`/`browser` invocations без alias SHALL сохранять внешний service lifecycle и MUST NOT автоматически поднимать либо останавливать MariaDB.

#### Scenario: Первый запуск в чистом worktree
- **WHEN** оператор из worktree без `vendor`, `node_modules` и соседнего `shlz-ui` запускает prepared command выбранного зарегистрированного теста
- **THEN** команда сама строит container environment по repository lockfiles/pins, поднимает owned MariaDB, дожидается readiness и тест проходит реальные HTTP assertions

#### Scenario: Повторный запуск
- **WHEN** оператор без ручной подготовки повторяет ту же команду при неизменных dependency inputs
- **THEN** неизменённые dependency layers переиспользуются, а выбранный тест снова исполняется без запуска всей категории

### Requirement: Execution is isolated to the candidate snapshot
Application source SHALL загружаться из materialized snapshot текущего candidate, включая незакоммиченные изменения, а dependencies/assets SHALL происходить из pinned container environment. Host `vendor`, host `node_modules`, соседний `shlz-ui`, host PHP/Composer/Node MUST NOT использоваться как fallback.

#### Scenario: Контролируемый дефект candidate
- **WHEN** в candidate вносится незакоммиченный контролируемый дефект поведения, наблюдаемого выбранным тестом
- **THEN** та же prepared command возвращает RED нужного assertion; после удаления дефекта она возвращает GREEN

#### Scenario: Host dependencies отсутствуют или отличаются
- **WHEN** worktree не имеет host dependency directories и не расположен рядом с `shlz-ui`
- **THEN** executed application bytes соответствуют snapshot identity, а dependencies и public CSS asset доступны из container image

### Requirement: Setup failures remain distinct and evidenced
Недоступность Docker, ошибка dependency preparation или истечение bounded DB readiness SHALL завершать команду как `SETUP_FAILURE` до acceptance assertions, называть failing stage и сохранять полный лог через existing evidence store. Произвольный HTTP 503 MUST NOT переклассифицироваться без установленной setup-причины.

#### Scenario: Docker недоступен
- **WHEN** launcher не может обратиться к Docker или построить dependency image
- **THEN** harness записывает `SETUP_FAILURE` с этапом Docker/dependency preparation и ссылкой на retained log, не `INTENDED_RED`

#### Scenario: MariaDB сначала остановлена
- **WHEN** owned test MariaDB до команды не запущена
- **THEN** launcher поднимает её, ограниченно ждёт health и запускает acceptance test после readiness

#### Scenario: MariaDB не готова вовремя
- **WHEN** owned MariaDB не достигает readiness до deadline
- **THEN** команда возвращает `SETUP_FAILURE` с этапом DB readiness, не запускает acceptance assertions и очищает только ресурсы этого запуска

### Requirement: Prepared command preserves delivery identities
Маршрут SHALL сохранять existing command identity, purpose, acceptance mapping, intended-RED interpretation и запись results в existing evidence store; generic diagnostic harness commands SHALL оставаться вне безусловного container wrapping.

#### Scenario: Prepared plan execution
- **WHEN** planner включает зарегистрированный Yii2 test в prepared package
- **THEN** package command использует focused container route с прежними command-id/acceptance-id и оператор указывает test/command-id вместо ручной dependency/DB последовательности

#### Scenario: Один тест выбран по нескольким основаниям
- **WHEN** exact зарегистрированный Yii2 test одновременно выбран acceptance mapping, changed registered test или другим planner obligation
- **THEN** plan SHALL содержать ровно один focused container command, сохранить все selection rationales, acceptance identity и local execution priority и MUST NOT содержать прямой host PHP duplicate

#### Scenario: Reviewer prepare сопоставляет wrapped command точно
- **WHEN** Gate 3 или Gate 5 получает evidence для planner-owned wrapped navigation command
- **THEN** expectation, plan command, coverage и evidence SHALL использовать один exact normalized key; правильный RED принимается только Gate 3, правильный GREEN — Gate 5, а missing либо подменённый command evidence MUST быть отклонён без ослабления source, identity, acceptance и environment checks

#### Scenario: Interruption and test failure
- **WHEN** child прерывается либо acceptance assertion действительно падает после успешного setup
- **THEN** existing interruption/test-failure outcomes сохраняются и не заменяются `SETUP_FAILURE`
