# CONTAINER-COMPOSER-VISIBILITY-123-A — Composer dependencies для canonical Yii execution

## Простыми словами

Clean worktree запускает существующие Yii entrypoints через `run-in-profile` без host `vendor/`: application code берётся из проверяемого candidate, а locked third-party code — из соответствующего immutable container dependency layer. Этот slice не меняет классификацию RED, identity guard или environment manager.

## Нормативный контракт

- Идентификатор: `CONTAINER-COMPOSER-VISIBILITY-123-A`.
- Actor: разработчик или существующий CI consumer.
- Source oracle: решение владельца по #123 slice A от 2026-09-16 и T08 gap-check на `main` `11f0fcb446d8fbd0cbfa2c88caf2e0888b9819cf`.
- Public seam: `tools/delivery/run-in-profile <profile> <command> [args...]`.
- Preconditions: Docker daemon доступен; candidate содержит canonical lockfiles и dependency pins; host dependency state не является prerequisite.

### CCV123A-01 — clean-worktree Yii bootstrap

При отсутствии repository-local host `vendor/` representative Yii bootstrap через canonical profile MUST достигать `YII_BOOTSTRAP_OK`. Project classes MUST загружаться из exact candidate source, third-party Composer classes MUST загружаться из container-managed locked dependency location. Candidate source mount MUST NOT скрывать dependency layer.

Два независимых clean worktree MAY переиспользовать одинаковые immutable image layers при одинаковых inputs, но MUST NOT копировать/symlink dependencies между worktrees или разделять mutable dependency/runtime state.

### CCV123A-02 — host vendor не является входом или результатом

Отсутствующий, чужой или stale host `vendor/` MUST NOT определять canonical execution. Cold и warm запуск MUST NOT создавать или изменять repository-local host `vendor/`. Container dependency view MUST быть read-only для выполняемой command.

### CCV123A-03 — locked dependency identity

Dependency identity MUST включать canonical dependency recipe/pins и текущие `composer.json`/`composer.lock`. Изменение locked input в disposable fixture MUST либо построить соответствующий новый layer, либо завершиться setup failure до Yii behavior; старый layer MUST NOT приниматься молча.

`composer update`, изменение lockfile и изменение PHP/Yii versions запрещены.

### CCV123A-04 — fail closed

Missing/corrupt container dependency MUST завершать public command ненулевым setup/command failure до `YII_BOOTSTRAP_OK`. Fallback на host `vendor/` запрещён. Harness `INTENDED_RED` classification этим slice не меняется.

### CCV123A-05 — совместимость существующих profiles

Existing governance profile MUST оставаться GREEN. Representative integration/browser bootstrap, использующий тот же dependency seam, MUST не требовать host `vendor/`; lifecycle внешних services остаётся у существующего владельца. Existing argv, exit-code и compact evidence contracts сохраняются.

### CCV123A-06 — неизменность tracked inputs

Preparation, cold-ish и warm execution MUST оставлять `composer.lock` и tracked source неизменными. Никакой новый environment manager/profile, host setup, product/domain behavior, performance/caching redesign или чтение `rapid-pilot` не допускаются.

## Обязательные примеры A–J

- **A:** fresh clean worktree, host `vendor/` отсутствует → canonical route выводит `YII_BOOTSTRAP_OK`; setup failures before behavior: `0/N`.
- **B:** второй independent clean worktree → тот же Yii behavior, никаких dependency copy/symlink между worktrees.
- **C:** host содержит stale vendor → execution его не использует.
- **D:** candidate-only project marker → class загружен из candidate snapshot.
- **E:** disposable changed lock input → прежняя dependency identity не принята.
- **F:** missing/corrupt container dependency → raw nonzero failure, no host fallback; classification вне scope.
- **G:** existing governance profile GREEN.
- **H:** integration и browser bootstrap не регрессируют на общей dependency seam.
- **I:** tracked source и locks после preparation/execution unchanged.
- **J:** warm repeat не создаёт host `vendor/`.

Для A/B измеряются cold-ish/warm duration из `RUN_IN_PROFILE_RESULT`, host vendor existence, dependency origin и source origin. CI/token improvement без telemetry не заявляется.

## Done

- OpenSpec strict validation и planner obligations разрешены до Gate 2.
- Executable public-route test демонстрирует RED именно на dependency visibility, не на отсутствующем Docker/сети.
- Planner-required independent Gate 3, если выбран, одобрен до implementation.
- Executor реализовал minimal existing-profile composition; focused A–J и relevant governance/architecture checks GREEN.
- Independent Gate 5 APPROVED относится к exact source; один existing-consumer exact-source CI GREEN.
- Candidate PR-ready; merge/deploy/settings не выполнялись.
