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

Каждый profile MUST разрешаться в immutable image digest. Runtime и package
versions MUST происходить из существующих canonical sources
`tools/delivery/dependencies.env`, `composer.lock`, `uv.lock` и применимых npm
lockfiles; новый параллельный manifest запрещён. Локальный и CI вызовы одного
profile на одной платформе MUST наблюдать один image digest и версии.

### DP110A-04 — минимальное evidence

Каждый запуск MUST выдать компактный результат с git SHA, argv, profile/image
digest, exit code и duration. Persistent evidence store, lifecycle identity,
fixture identity и дополнительные digest-модели запрещены.

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
