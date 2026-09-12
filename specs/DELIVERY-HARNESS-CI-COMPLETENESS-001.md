# DELIVERY-HARNESS-CI-COMPLETENESS-001

## Простыми словами

До push harness обязан находить те же предсказуемые ошибки связности, зависимостей
и CI-среды, которые ранее обнаруживались лишь первым GitHub CI. Проверка остаётся
ограниченной и не заменяет полный exact-source CI.

## Actor, oracle, authorization and public seam

Actor — root, готовящий candidate к публикации по issue #99. Oracle — сохранённые
failure inventories PR #98 runs 34606125088/34609680703 и PR #100 run 34686467885,
а не текущая реализация. Публичный seam — generated verification plan, role package,
runner evidence и pre-publication admission команд `tools/delivery/harness.py` и
`tools/delivery/change-verification.py`. Root авторизован писать spec/tests;
отдельный executor реализует только Gate 3 APPROVED контракт; независимые reviewers
решают Gates 3/5. Merge и deployment не авторизованы.

## R1 — Transitive repository obligations

Planner MUST связывать generated artifact с template/pins, generator `--check` и
известными setup consumers. Изменение inventory, entrypoint, shared bootstrap/error
envelope, Dockerfile/Compose или verification catalog MUST включать все известные
consumers. Неизвестная или неоднозначная связь даёт `UNKNOWN` и блокирует publication.

Пример: изменение только `deploy/runtime/Dockerfile` при прежнем
`tools/delivery/Dockerfile.runtime.in` планирует
`python3 tools/delivery/render-dependencies.py --check`; drift падает до push.
Новый E2E member планирует composition consumer, поэтому stale inventory RED.

## R2 — CI category environment and dependencies

Каждая новая/изменённая test command MUST объявлять runtime prerequisites: language
imports/packages, DB, Docker, browser, external fixture и dependency workspace.
Suite/category MUST предоставлять не меньше prerequisites. Preflight MUST выполняться
в category-equivalent profile и не может использовать лишние developer services.

Undeclared Python/PHP/Node dependency и test, обращающийся к MariaDB до assertion,
но зарегистрированный как unit, MUST завершать admission ненулево с точным test,
dependency/service и category. Evidence MUST фиксировать фактически доступные
services/dependencies; GREEN более богатой среды не подтверждает узкий CI profile.

Python module, расположенный в том же repository test directory и доступный
стандартному запуску test script, MUST считаться source dependency, а не внешним
undeclared package. Preflight MUST разрешать такой sibling import без добавления
его имени в список third-party dependencies и продолжать отклонять отсутствующий
либо внешний незаявленный import.

Node.js import с обязательным префиксом `node:` MUST считаться встроенным runtime
module, а не внешним package. Preflight MUST разрешать такие imports без записи в
third-party allowlist и продолжать отклонять незаявленные bare package imports.

## R3 — Typed plan-owned evidence

Каждая command obligation имеет стабильный id и purpose `acceptance`, `boundary`
либо `category`. Reviewer package MUST принимать все plan-owned records с совпавшими
argv, source, environment и expected outcome, даже если boundary/category record не
сопоставлен отдельной acceptance. Unrelated record MUST отклоняться fail-closed.

## R4 — Post-implementation Gate 3 test delta

Для test correction после реализации reviewer package MUST принимать current GREEN,
точный base/current test delta и linked historical `INTENDED_RED` той же acceptance.
Новый фиктивный RED на current production bytes запрещён. Несопоставимый RED,
изменённая acceptance либо отсутствующий delta MUST блокировать package.

## R5 — Candidate and executable source identity

Каждый record/package MUST хранить полный candidate source и executable digest.
Executable digest включает production, tests, verification code/config/catalogs и
исключает только узко перечисленные review verdicts и task-checkbox metadata.
Добавление append-only verdict/checkbox MAY сохранить executable digest, но полный
candidate source меняется и остаётся в audit. Любое изменение executable bytes
инвалидирует прежний GREEN. Classification неизвестного path fail-closed executable.

## R6 — Dependency workspace

Ignored/generated dependency tree MUST использовать manifest с разрешённым realpath,
lock/source digest и consumers. Его файлы не входят в deliverable/source, но identity
входит в environment evidence. Missing/mutable identity, symlink escape или consumer
вне manifest MUST блокировать prepare/preflight. Harness не меняет и не публикует
dependency workspace.

## R7 — Bounded pre-publication admission

Перед push/PR harness MUST выполнить на одном executable digest bounded CI-parity
preflight generator, inventory composition, dependency policy и category profiles.
Failure, missing evidence или `UNKNOWN` блокирует publication-ready package. GREEN
не превращает фактически отсутствующие PR/CI в одобрение и не заменяет GitHub matrix.

Synthetic PR #98 fixture MUST быть заблокирован на drift, stale consumers и
undeclared import до публикации. Synthetic PR #100 fixture MUST быть заблокирован
как DB-in-unit mismatch. Исправленный fixture становится publication-ready без
локального `make test`/`make verify`, product DB/PDF/runtime/E2E и без source effects.

## Audit, rejection, idempotence and concurrency

Все планы, manifests, records и packages append-only либо атомарно заменяют только
worktree-scoped active pointer во внешнем private evidence home. Повторный preflight
одного source/environment логически идемпотентен; два worktree не читают и не
перезаписывают state друг друга. Rejection сохраняет полную причину снаружи checkout,
не изменяет candidate, dependencies, GitHub, стенд или domain history.
