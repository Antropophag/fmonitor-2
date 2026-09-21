# Текущая цель — production/pilot ERP-синхронизация оборудования

Поручение владельца 2026-09-21: продолжить issue [#12](https://github.com/Antropophag/fmonitor-2/issues/12) после merged PR #216 и довести ERP equipment facts до реально работающего автоматического hourly-контура на локальном пилотном стенде `127.0.0.1:8093`. Candidate перед публикацией rebased на свежий `origin/main` `6e6ccbdbd4fa4d676fe94e9244e44aaaf411a36d`; исходный stand baseline был `45fa7dee`, migration v31 уже применена, существующие данные/volumes/sessions/artifacts сохранены.

Scope: versioned job claim/worker, реальный fully-qualified legacy ERP SQL, bounded запрос только по локальным exact `zavnumber`, direct `.env` configuration, canonical worker/scheduler Compose wiring, полный штатный startup, jobs-aware readiness, safe retry/failed runs, ручной и hourly запуск, object-card readback и state-preserving qualification объектов 1226, 1427, 2238, 2239. Объект 1318 с `zavnumber=0` не сопоставляется. Legacy `../fmonitor` — только read-only oracle; secrets не попадают в Git, выводы или evidence.

Контракт: [ERP-EQUIPMENT-FACTS-001](../../specs/ERP-EQUIPMENT-FACTS-001.md). Lifecycle: [operationalize-erp-equipment-sync](../../openspec/changes/operationalize-erp-equipment-sync/). Delivery record: [issue-12 ERP operational delivery](issue-12-erp-operational-delivery.md).

Root владеет scope/spec/tests и orchestration. Отдельный `gpt-5.6-sol / low` executor реализует production code; независимые `gpt-5.6-sol / low` reviewers решают planner-required Gates 3/5. Локально только bounded checks; полный `make test`/`make verify` запрещён. Затем push/PR и один exact-source GitHub CI с полным failure inventory.

Разрешено state-preserving обновление только локального стенда 8093. Merge и любой внешний deployment запрещены без нового явного подтверждения владельца. UNKNOWN не является GREEN. Предыдущая цель про sidebar сохраняется в [delivery record](issue-sidebar-state-icons-delivery.md) и Git history; её WIP не смешивается с этим candidate.
