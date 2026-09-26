# Текущая цель — #276 runtime-коррекции completed и диагностики ОТиЗ

Параллельное поручение владельца 2026-09-26: в отдельном worktree/PR реализовать follow-up GitHub #276 после `persist-completed-installation-state`. Активная Docker-доставка в основном worktree не входит в этот candidate и не изменяется.

Решение владельца 2026-09-26: production будет пересоздан с нуля без historical data. Scope сужен до runtime-коррекций новых дел: единый label/metrics persisted `completed` в shared status, operational dashboard и weekly FKR; исправление `INSTALLER_ATTRIBUTION_ABSENT` для zero/positive progress. Связь паспорта с `fm_maintable` сохраняется.

Lifecycle: `openspec/changes/reconcile-completed-installation-state/`. Root пишет normative specs и RED tests; отдельный `gpt-5.6-sol/low` executor реализует; независимые `gpt-5.6-sol/low` reviewers решают planner-required Gate 3/final. Base — `fd75b584` (`origin/main` при создании worktree). Полный локальный `make test`/`make verify` запрещён; только bounded focused checks и один exact-source CI.

Не входят historical reconciliation/backfill, deployment CLI, migration, backup/restore ceremony, production data mutation, изменение premium formula/settlement, state writers, runtime DDL, новая логика в `rapid-pilot` и отвязка паспорта от `fm_maintable`. Предыдущее Gate 3 `CHANGES_REQUESTED` сохранено как история отменённого широкого scope и не является approval нового candidate.

Текущее состояние, PR и CI читаются через `python3 tools/delivery/harness.py state`. Merge/deploy/data mutation без отдельного поручения не выполнять.
