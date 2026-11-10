# Текущая цель — провести production-изменения через Git и CI

Поручение владельца 2026-11-10: собрать все фактически работающие production-изменения в Git, дополнить тесты, пройти независимые ревью и полный exact-source GitHub CI. Предыдущий режим экстренной поставки не освобождает этот интеграционный candidate от проверок.

Рабочая ветка/worktree: `codex/reconcile-production-20261110`, `fmonitor-2-reconcile-production-20261110`. Base: `60ed34ac178d1a85b3f21797981a5d8ba35da39c` (`origin/main`). Production читается только для сверки; изменение кода, миграции, restart/deploy и business-data writes на сервере в это поручение не входят. Локальные WIP и соседние worktrees сохраняются. Root задаёт контракт/тесты, независимые sol/low agents выполняют требуемые роли; импорт сохраняет фактическое прежнее авторство.

Контракт: [PRODUCTION-RECONCILIATION-20261110](../../specs/PRODUCTION-RECONCILIATION-20261110.md). Primary capture/logs находятся вне Git: `~/.local/share/fmonitor-2/reconcile-production-20261110/`. Полный локальный `make test`/`make verify` запрещён. Локально — bounded focused checks, полный matrix — существующий Quality Graph consumer. UNKNOWN не означает GREEN/допуск.

Предыдущая очередь сохранена ниже как история и не определяет scope нового поручения.

---

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
