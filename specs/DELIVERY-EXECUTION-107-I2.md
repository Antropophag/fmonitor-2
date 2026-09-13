# DELIVERY-EXECUTION-107-I2

## Простыми словами

Один container route готовит окружение чистого checkout и выполняет настоящие зарегистрированные проверки. PHP/Node/Python project packages на хосте не нужны. Нормативная матрица и границы — DELIVERY-EXECUTION-107, AC02/03/07/08/09; здесь уточнён public execution contract для I2, без копирования матрицы.

## Public seam

Actor — delivery agent, локально или в GitHub CI. Host launcher использует Python stdlib >=3.9, Git и Docker с Compose; GitHub read/publish требует gh/auth, не выдаваемых candidate container. Только named verification profiles управляются существующим harness.

`python3 tools/delivery/harness.py environment --profile <governance|integration|browser> --prepare` строит/находит image из shared pins/lockfiles и выводит JSON с outcome, profile, image_id, platform, lockfiles, runtimes, dependency_installs. Без Docker либо при недоступном обязательном пакете outcome=SETUP_FAILURE, ненулевой exit, точная причина; host fallback запрещён. Неизвестный профиль отклоняется до side effects. Warm prepare неизменённого профиля имеет тот же image_id и dependency_installs=0.

`harness.py run --profile <profile> --task 107 --run-id <id> -- <registered argv>` использует тот же profile локально и в CI, автоматически готовит image, frozen snapshot и отдельные mutable fixture/artifact roots. Output — существующий компактный summary + внешний retained record. Record содержит observed execution.image_id/platform/profile/lockfiles/runtimes, исходный source, snapshot identity, service/fixture identity. Python внутри — 3.12.11, Node 22.22.0, PHP 8.5.x из shared pins. Отсутствующий профиль у поддерживаемой project command выбирается из plan/category; не означает host fallback. Собственный лёгкий orchestrator и его isolated unit fixtures могут исполняться stdlib host launcher до I2 bootstrap, но не project checks.

## Dependencies and isolation

Для явно переданного run-owned `--fixture <directory>` launcher монтирует отдельный mutable fixture и передаёт внутри контейнера `FMONITOR_EXECUTION_FIXTURE_ROOT`; исходники остаются read-only. Это поддерживает детерминированные readiness/barrier witnesses без произвольных задержек. Identity исходного fixture сохраняется отдельно от его последующих изменений.

Внешний record сохраняет execution.resource_ids с containers/networks/volumes для наблюдаемого cleanup. SIGTERM одному run даёт INTERRUPTED и удаляет только его ресурсы; параллельный run другого worktree продолжает настоящий HTTP launcher. Идентификаторы не используются для broad prune и не выводятся из общего project name.

Locked third-party dependencies хранятся в immutable image layer отдельно от source mount. Composer project autoload загружает app classes из snapshot. Source mount не скрывает dependencies и не читает соседний worktree. Image/profile key зависит от target/platform/runtime/extensions/lockfiles. Новый lockfile не использует старую dependency identity; здоровый warm run не выполняет install.

Недоверенный source vendor/autoload или попытка использовать внешний autoload origin выявляются до project test как SETUP_FAILURE, не удаляются из пользовательского checkout. Положительный сценарий не содержит host vendor/node_modules/.venv. Snapshot additions/deletions/modes проверяются; runtime не может менять frozen исходники. Только task/run-owned DB/network/artifacts доступны для cleanup; interruption сохраняет evidence и не трогает другие worktrees/stand.

Контейнеры с browser используют pinned Playwright/Chromium и shlz public assets из image. Browser вызывается через PHP launcher с fixtures/config, не напрямую .mjs. Runtime tests при необходимости получают Docker service согласно profile; обычный governance не требует лишнего доступа к daemon.

## Real acceptance execution

В новом disposable checkout от проверяемого кандидата выполняются:

- integration: `php tests/Yii2/yii2_user_access_001_test.php`;
- governance: `python3 tests/Verification/change_verification_001_test.py`;
- browser: `php tests/Yii2/yii2_user_access_browser_001_test.php`.

Все три должны пройти cold и warm с сохранёнными observed records. Mutant/negative controls запускаются тем же public route: missing service/package, foreign autoload, generated drift, stale registry, DB helper ошибочно отнесённый к unit, unknown Python/Node import. Healthy local Python module и настоящий Node builtin принимаются. Произвольный node:specifier не становится builtin. Политика probes/registry берётся из поставляемого source; stub exit0 не считается observation.

## Compatibility and verification

Preflight разрешает зависимости транзитивных helpers от зарегистрированного
launcher, включая вложенный require_once; helper не становится самостоятельным
тестом и не освобождает unit launcher от DB-profile проверки. Node specifier
проверяется целиком (включая scoped package и subpath): node:fs/promises доступен,
node:fs/nonexistent_delivery107 не builtin. Объявленный отсутствующий Node/PHP
package даёт DEPENDENCY_UNAVAILABLE, а не fabricated available evidence.

Make aliases и CI-category команды используют один execution route без повторной
контейнеризации уже запущенного check. Host project runtimes не нужны также для
lint/architecture и legacy suite aliases. Daemon доступ определяется настоящими
transitive service prerequisites, включая helpers и category dispatch; слово
Docker в комментарии не предоставляет socket. Аргументы launcher не теряют его
dependency identity. Явный несовместимый профиль даёт SETUP_FAILURE с
CATEGORY_ENVIRONMENT_MISMATCH до project execution.
В текущем canonical local/CI профиле канал daemon — /var/run/docker.sock;
профиль без такого prerequisite не получает socket либо удалённый DOCKER_HOST.

Сохраняется HARNESS-CANONICAL-MIGRATION-STAGE-001: полный Make route имеет
девять стадий reset→migrate→architecture→lint→unit→db→characterization→e2e→diff
и прежнюю агрегацию setup-blocked failures. Это проверяется существующим
bounded stage-overlay regression и структурным чтением Makefile, не полным suite.
Reset и migrate полного run используют одну и ту же подготовленную БД.
Standalone `make migrate` использует уже существующую БД из явно переданных
FMONITOR_TEST_DB_* и не создаёт, не подменяет, не сбрасывает и не удаляет её.
Повтор сохраняет marker и выполняет canonical idempotence. `test-db-reset`
сбрасывает именно указанный fixture database; соседние/borrowed service resources
не становятся собственностью дочернего runner для cleanup. Новый обычный check
без явного заимствования сохраняет run/worktree isolation. Governance без DB
prerequisite не подсовывает потребителю фиктивный DB host/port.

Внутренние prepare/preflight/checks контейнера также сохраняют host-readable
packages/plans/records во внешнем run-owned home. Временный /root home внутри
удаляемого container и недоступные снаружи record paths не считаются retention.

Reviewer preparation принимает legacy evidence с его прежней точной keyed
host-environment binding либо container evidence с наблюдаемым execution envelope.
Для container не требуется подставлять host hash: image_id/platform/runtimes/locks
и supported profile должны соответствовать текущему target и обязательным
languages/services плана. Missing/declared-only/mismatched envelope и STALE/UNKNOWN
applicability отклоняются. Source, argv, outcome, имеющиеся command id/purpose и
trusted evidence path проверки не отменяются. Отсутствующий command_environment
не считается совпадением сам по себе: соответствие доказывается observed envelope.
Изменение только PATH reviewer shell не инвалидирует неизменённый container check.
Prepare остаётся NOT_REVIEWED, а не независимым approval.

Governance image содержит наблюдаемые uv и locked Quality Graph dependencies;
uv.lock не просто хешируется, а используется для immutable installation.
Поставляемый fast CI command list выполняется в этом профиле без source .venv
и без install на warm run. Требуемые PHP extensions проверяются в image.
FMONITOR_SHLZ_UI_ROOT указывает на существующие pinned assets image, а не на
неиспользуемую declared директорию. Nested acceptance records/stdout/stderr
переживают cleanup временного checkout; расположение corpus сохраняется снаружи.

Global gitignore не может скрыть релевантный untracked test: он входит в source identity либо обнаруживается точный GLOBAL_IGNORE_CONFLICT. Наличие global config само по себе не блокирует здоровый checkout. Generated untracked Python bytecode не меняет identity даже без global/project ignore; tracked source остаётся проверяемым. Unknown base и изменение shared execution manifests выбирают full CI. Diagnostic state сохраняет те же identity semantics по canonical path aliases.

Версии и autoload origins подтверждаются независимым probe внутри каждого профиля, а image identity/platform сопоставляются с Docker inspect. Record сообщает snapshot.container_path и execution.dependency_root: project Reflection origin лежит в первом, Yii origin — во втором. Неподтверждённые declared поля не заменяют это наблюдение.

Make/focused/full-CI routing использует общий container entrypoint. Shared profile changes требуют full CI и не допускают harness-only escape. Локальный full suite запрещён. Existing #90/#99 и product corrections сохраняются как regressions; aliases/fixtures корректируются только где меняется execution contract. Gate3 тестов и Gate5 кода остаются независимыми.

Тяжёлые I2 self-test corpora выбираются только при изменении delivery harness,
container profiles, dependency manifests или verification/CI routing. Обычный
product PR не включает их в focused selection; он выполняет только затронутые
product checks и затем canonical GitHub CI matrix.

Выбранные I2 corpora выполняются отдельными параллельными CI jobs с достаточным
per-corpus timeout, а не последовательно внутри product e2e. Итоговый verify MUST
требовать успешный I2 matrix при выбранном trigger и SKIPPED при обычном product PR.
Delivery-infrastructure PR использует отдельный `mode=i2`: fast и I2 matrix
выполняются, product unit/integration/e2e/governance и harness job SKIPPED.

## Correction: source boundary and observed readiness

Набор исходников snapshot совпадает с проверяемым Git candidate: tracked файлы
и релевантные неигнорируемые untracked файлы. Произвольный project-ignored input
не попадает в исполняемый source; mutable inputs передаются только отдельным
fixture contract. Исключение generated untracked bytecode не скрывает tracked
source даже в каталоге с именем __pycache__. Snapshot identity подтверждается
на реально исполняемой копии, а не повторным чтением рабочего ROOT.

Разрешение daemon не следует из имени произвольного метода cli/run_profile,
невызываемой функции, PHP-комментария или инертной строки. Реальный PHP launcher
с Docker-вызовом через require/helper сохраняет доступ; helper отдельно в inventory
не регистрируется. Отрицательные и положительные случаи проходят тот же public run.

Environment prepare проверяет требуемые runtime versions, target platform,
PHP extensions, locked governance packages и browser/assets до GREEN. Испорченная
собранная recipe с отсутствующей обязательной частью не сертифицируется по одним
декларациям; возвращается SETUP_FAILURE с причиной требуемого компонента/target.
DOCKER_DEFAULT_PLATFORM задаёт явный target; без него native разрешается в
фактическую платформу выбранного Docker engine, а не остаётся текстовой заглушкой.
Тестовые дефектные images имеют уникальные run-owned labels/tags и не подменяют
общие verification tags соседних worktrees.
