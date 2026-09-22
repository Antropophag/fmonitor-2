# Текущая цель — bounded regular runtime readiness

Поручение владельца 2026-09-22: от актуального `main` с merged PR #226 доставить
небольшой PR-ready фикс избыточной idle-нагрузки `/health/ready`. Контракт:
[`RUNTIME-READINESS-LOAD-001`](../../specs/RUNTIME-READINESS-LOAD-001.md), lifecycle:
[`bound-regular-runtime-readiness`](../../openspec/changes/bound-regular-runtime-readiness/).

Scope: existing full schema runtime-check становится обязательным deployment
startup gate; steady readiness ограничивается current DB connect/cheap query,
local resources и exact DB/schema/build-bound startup result. Сохранить liveness,
routes, response/status, canonical migrations and fingerprints. Не входят
business/UI, #171, ОТиЗ, integrations, общий infrastructure tuning или stand.

Работа идёт в отдельном worktree `fmonitor-2-readiness-load` от `3c242f34` (merge
PR #226), с отдельными Compose resources. Root пишет scope/spec/tests; separate
gpt-5.6-sol/low executor implements; independent reviewers решают Gates 3/5.
Локальный full `make test`/`make verify` запрещён; exact-source CI обязателен.

Предыдущая цель #222 завершена merge PR #226; её запись сохранена ниже как история.

---

## Исторический указатель — #222, редактирование реквизитов объекта

Поручение владельца 2026-09-21: продолжить существующую ветку `codex/issue-222-object-details` до PR-ready по контракту [OBJECT-DETAILS-EDITING-001](../../specs/OBJECT-DETAILS-EDITING-001.md) и lifecycle [edit-object-details-with-history](../../openspec/changes/edit-object-details-with-history/). Детали scope, разовое разрешение раннего production implementation и authorship зафиксированы в [delivery record](issue-222-object-details-editing-delivery.md). Эта цель заменяет расположенный ниже исторический указатель dashboard; dashboard WIP не смешивать.

Root пишет scope/spec/tests; отдельный `gpt-5.6-sol / low` executor реализует. Два прежних Gate 3 отказа сохраняются. Один независимый reviewer совместно проверяет готовые tests+implementation+evidence и disposition всех findings. До review Gate 3 не `APPROVED`. Локально только bounded checks; полный `make test`/`make verify` запрещён. Затем один exact-source CI, PR-ready без merge/deploy/import/external sends/settings.

---

## Исторический указатель — быстрый refinement операционного дашборда

Поручение владельца 2026-09-21: реализовать согласованный на локальном стенде refinement дашборда. Candidate начат от merged PR #217 и после предупреждения владельца rebased на актуальный `origin/main` `80130fbb` (merged PR #218 ERP operational); конфликты разрешаются с повторным exact-source plan/checks.

Визуальный primary source — throwaway commit `6c46bab2`; он не является production approval. Контракт: [YII2-OPERATIONAL-DASHBOARD-REFINEMENT-001](../../specs/YII2-OPERATIONAL-DASHBOARD-REFINEMENT-001.md). Lifecycle: [refine-operational-dashboard-visuals](../../openspec/changes/refine-operational-dashboard-visuals/). Delivery record: [operational-dashboard-refinement-delivery](operational-dashboard-refinement-delivery.md).

Scope: CSP-safe proportional bars, start-risk read model и точный drill-down, semantic palette, компактная responsive chart/KPI geometry, exact public `shlz-ui` navigation icons/order и единый content-derived asset versioning. Не входят schema/writers/permissions/jobs/rapid-pilot, merge или deploy. ERP equipment sync из PR #218 сохраняется без изменения.

Root пишет scope/spec/tests. Отдельный `gpt-5.6-sol / low` executor реализует production code; независимые reviewers решают planner-required Gates 3/5. Локально только bounded focused checks; полный `make test` / `make verify` запрещён. Один exact-source CI run — после final review.
