# Текущая цель — №180, Docker dependency cache focused checks

Поручение владельца 2026-09-17: реализовать только первую задачу
[№180](https://github.com/Antropophag/fmonitor-2/issues/180) из навигационной
сводки №179 от актуального `main` и довести candidate до PR-ready. Scope:
сохранить Docker cache dependency layers focused checks при source-only
изменении, не ослабляя exact source/lock identity; показать реальный bounded
A→B before/after и внешний wall time.

Не входят №181–№183, application code, CI composition, FAST/Gates, dependency
upgrades, новый image/runner/telemetry framework, `RUN_IN_PROFILE_RESULT` schema
и общий рефакторинг harness. Merge/deploy/settings не выполнять.

Контракт: [FOCUSED-CHECK-CACHE-180](../../specs/FOCUSED-CHECK-CACHE-180.md).
Lifecycle:
[focused-checks-dependency-cache](../../openspec/changes/focused-checks-dependency-cache/).
Root пишет scope/spec/tests; отдельный gpt-5.6-sol/low executor реализует;
независимые gpt-5.6-sol/low reviewers решают planner-required Gates 3/5.
Локально только bounded focused checks; full `make test`/`make verify` запрещён.
Exact-source GitHub CI выполняется один раз.

Предыдущий указатель №157 сохранён в Git history и его WIP остаётся в отдельном
worktree. Нельзя очищать shared Docker caches или удалять чужие
worktrees/volumes. Устойчивый процент экономии по одной машине не заявляется.
