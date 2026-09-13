# DELIVERY-PROFILE-110-A — pinned focused-check execution profiles

## Простыми словами

Разработчик и CI запускают уже существующую команду проверки через один и тот
же небольшой launcher и получают одинаковое закреплённое container environment.
Этот срез не выбирает тесты, не меняет Quality Graph и не создаёт provenance
систему.

## Нормативный контракт

- Идентификатор: `DELIVERY-PROFILE-110-A`.
- Actor: разработчик или существующий CI job.
- Public seam: `tools/delivery/run-in-profile <profile> <command> [args...]`.
- Source oracle: issue #110, суженный прямым решением владельца 2026-09-13 и
  OpenSpec change `pinned-focused-check-profiles`.
- Preconditions: Docker daemon доступен; checkout содержит canonical dependency
  pins и lockfiles.

### DP110A-01 — ровно три profile

Launcher MUST принимать `governance`, `integration`, `browser`. Иное имя MUST
завершаться ненулевым exit code без запуска команды.

### DP110A-02 — прозрачный argv и exit code

После profile launcher MUST воспринимать остаток аргументов как одну команду,
передавать argv без test selection/aggregation interpretation и возвращать exit
code этой команды. Пустая command MUST быть отклонена.

Пример: команда `sh -c 'exit 23'` завершается `23`; аргументы с пробелами остаются
отдельными значениями argv.

### DP110A-03 — pinned reproducible environment

При одинаковом Git source и одинаковых immutable build inputs каждый profile
MUST иметь одинаковые наблюдаемые runtime/dependency contracts. Все base
container images MUST быть закреплены registry digest, не только tag. Runtime и
package versions MUST происходить из существующих canonical sources
`tools/delivery/dependencies.env`, `composer.lock`, `uv.lock` и применимых npm
lockfiles; новый параллельный manifest запрещён, а `latest`/`current` не могут
определять нормативную версию.

Наблюдения MUST включать profile name, immutable base-image inputs, PHP version
и required extensions, Python version, Node/npm versions, Composer version,
installed application dependencies относительно lockfiles, а для `browser` —
Playwright version и установленную browser asset revision.

Два независимых local cold build MAY иметь разные Docker image IDs, config или
layer digests, если immutable inputs и все перечисленные наблюдения совпадают.
Registry publishing и normalization machinery не входят в контракт.

### DP110A-04 — минимальное evidence

Каждый запуск MUST выдать компактный результат с git SHA, argv, profile/local
image digest, exit code и duration. Этот local image digest идентифицирует
фактически выполненный container, но не обязан совпадать у независимых builds.
Persistent evidence store, lifecycle identity, fixture identity и дополнительные
digest-модели запрещены.

### DP110A-05 — неизменность владельцев тестов

Launcher MUST выполнять произвольную существующую command и MUST NOT владеть
test inventory, selection, sharding, aggregation, setup-runtime или service
lifecycle. PR A MUST NOT изменять Quality Graph workflow/manifests, `harness.py`,
planner, inventories или `tools/verification/ci.py`.

## Done

- Gate 2 test демонстрирует intended RED на отсутствии launcher/profiles.
- Независимый Gate 3 APPROVED предшествует implementation.
- Focused contract test GREEN локально и тем же существующим corpus в CI.
- Production/infrastructure implementation меньше 500 новых/изменённых LOC.
- Независимый Gate 5 APPROVED относится к exact reviewed source.
