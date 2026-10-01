# Текущая цель — завершить PR #286

Поручение владельца 2026-10-01: сравнить текущий production с PR #286, включить отсутствующие изменения, разобрать все падения CI и довести PR до merge. Это поручение разрешает обновление ветки PR и merge после обязательных проверок и независимого review; production deployment, миграции и изменение данных не запрошены.

Рабочая ветка: `codex/reconcile-production-20260928`. Исходный candidate: `97884878631303d46cb4cf31ab83231517e266e9`. Полная сверка и логи хранятся вне Git в `/Users/antropophag/.local/share/fmonitor-2/pr286-20261001/`. Контракт и продолжение истории: [production reconciliation](production-reconciliation-2026-09-28.md).

Текущий WIP №257 и соседние worktrees сохраняются. Root задаёт scope/spec/tests; отдельный executor реализует новые исправления, независимый reviewer проверяет planner-required gates. Полный локальный `make test`/`make verify` запрещён. Используются bounded focused проверки и exact-source GitHub CI. UNKNOWN не означает approval.

Предыдущая цель ниже приостановлена этим поручением; её история и рабочий checkout сохраняются.

---

# Текущая цель — bounded local Docker growth

Поручение владельца 2026-09-26: после фактического заполнения диска Docker-артефактами реализовать change `bound-local-docker-growth` и довести candidate до PR-ready. Scope: только локальные focused/disposable delivery workflow, storage guard, project-owned image/cache retention, teardown доказанно ephemeral Compose resources, диагностика и проверки безопасности.

Контракт: [LOCAL-DOCKER-STORAGE-BUDGET-001](../../specs/LOCAL-DOCKER-STORAGE-BUDGET-001.md). Lifecycle: [bound-local-docker-growth](../../openspec/changes/bound-local-docker-growth/). Root пишет scope/spec/tests; отдельный gpt-5.6-sol/low executor реализует; независимые gpt-5.6-sol/low reviewers решают planner-required Gates 3/5. Локально только bounded focused checks; full `make test`/`make verify` запрещён. Exact-source GitHub CI выполняется один раз.

Не входят №157 `compact-task-context-manifest`, завершённый hotfix №258/PR №282, параллельный отдельный worktree/PR №276 `reconcile-completed-installation-state`, application/product behavior, production deployment, изменение CI admission, автоматическая правка Docker Desktop settings, глобальный prune, удаление persistent stand/foreign resources и угадывание владельца исторических anonymous volumes. Предыдущие и параллельные цели сохранены в Git history и не включаются в этот candidate.

Текущее состояние, PR и CI читаются только через `python3 tools/delivery/harness.py state`. Merge/deploy/settings не выполнять.
