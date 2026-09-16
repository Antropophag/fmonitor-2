## Context

См. `proposal.md`. Existing image build копирует `composer.json` и `composer.lock` в `/opt/fmonitor` и выполняет locked `composer install`, создавая `/opt/fmonitor/vendor`. Existing `run-in-profile` bind-mount'ит exact checkout в `/workspace`; `bin/fmonitor2-yii.php`, `public/yii.php` и Yii tests читают `/workspace/vendor/autoload.php`, поэтому image dependencies остаются невидимыми. Candidate mount не скрывает `/opt/fmonitor`, но repository-relative path не связан с ним.

Owning module — `tools/delivery/run-in-profile` и его существующий focused-check image recipe. Разрешённые dependencies — Docker CLI/daemon, immutable pins из `tools/delivery/dependencies.env`, `composer.json` и `composer.lock`. Persistence owner отсутствует: immutable image layers принадлежат Docker image store, mutable command/runtime state остаётся disposable container state или явно существующему внешнему test-service lifecycle. `rapid-pilot` adapter отсутствует и не читается. Architecture-check impact ограничен shell/container execution contract; domain/persistence ceremony неприменима.

## Goals / Non-Goals

**Goals:**

- сохранить `/workspace` exact candidate source и дать repository-relative Yii bootstrap container-only view на matching `/opt/fmonitor/vendor`;
- проверять lock identity до выполнения candidate command;
- исключить host vendor как источник и результат canonical execution;
- сохранить существующие profiles, network ownership, argv, exit code и evidence.

**Non-Goals:**

- classification slice B, identity guard slice C, новый bootstrap manager/profile;
- host Composer install, host mount/copy/symlink, shared writable vendor;
- dependency/version updates, performance/caching redesign, product behavior.

## Decisions

1. **Container-only mount composition в public launcher.** Перед command launcher предоставляет `/workspace/vendor` отдельным read-only container mount из `/opt/fmonitor/vendor`; bind-mounted candidate source остаётся `/workspace`. Docker mount ordering позволяет более специфичному child mount быть видимым поверх parent source mount, не создавая host path. Альтернатива — менять каждый Yii entrypoint на env resolver — размножает seam и переписывает application/test bootstrap. Альтернатива — symlink/copy на host — нарушает isolation.

2. **Dependency tree переносится в именованный immutable image location, пригодный как mount source.** Runtime container получает dependency contents только из built image. Mount read-only; command не может превратить reuse в shared writable state. Реализация MUST учитывать Docker semantics для bind/volume и не использовать anonymous volume с lifecycle, переживающим task.

3. **Image identity включает lock-bearing recipe inputs.** Existing tag hash только recipe и pins недостаточен при изменении `composer.lock`; hash/tag SHALL включать `composer.json` и `composer.lock`, а build label/check SHALL связывать executing layer с candidate lock digest. Старый image не может быть принят молча. Полный worktree identity guard не добавляется.

4. **Fail closed probe до command.** Container startup проверяет наличие readable autoload, Yii file и lock correspondence из Composer installed metadata до запуска user argv. Проверка не читает host `vendor`. Failure остаётся raw nonzero setup/command result; harness classification не меняется.

5. **Executable regression идёт через настоящий launcher.** Test создаёт disposable Git worktrees/fixtures и вызывает `run-in-profile`; отдельный unit path resolver не считается доказательством. Source origin доказывается candidate-only marker class, dependency origin — included/autoloader path и read-only `/opt/fmonitor/vendor`; tracked/lock state сравнивается до/после.

## Risks / Trade-offs

- [Docker child mount source нельзя напрямую адресовать как image path стандартным bind mount] → выбрать минимальный container-internal startup layout, например запуск через existing image с read-only bind semantics, проверенный executable RED/GREEN; если потребуется новый manager/profile, остановиться `NEEDS_OWNER`.
- [Composer generated autoload maps project PSR-4 paths относительно `/opt/fmonitor/vendor`] → Composer vendor autoload already maps `FMonitor2\\` к path derived from vendor location (`/opt/fmonitor/app` may not exist). Bootstrap SHALL load project classes from candidate `app/autoload.php`; test D обязателен, а Composer project mapping MUST be assessed before implementation.
- [Stale host vendor виден parent bind mount до child composition] → child container-only mount MUST mask it and origin assertion MUST fail if any host path is loaded.
- [Corruption fixture может потребовать test-only image manipulation] → fixture остаётся disposable и проверяет raw failure без production fallback.

## Migration Plan

1. Зафиксировать executable RED A–J на `11f0fcb…` и сохранить timing/origin evidence вне checkout.
2. Независимый Gate 3 рассматривает spec/test согласно planner-selected lane.
3. Executor вносит минимальное изменение existing profile seam.
4. Выполнить focused public-route regression, architecture/governance checks и independent Gate 5.
5. Один exact-source CI run через выбранный existing consumer; merge/deploy/settings не выполнять.

Rollback — удалить launcher/image-layout delta; lockfiles и host filesystem migration отсутствуют.
