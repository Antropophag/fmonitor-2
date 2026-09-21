# Текущая цель — #222, редактирование реквизитов объекта

Поручение владельца 2026-09-21: продолжить существующую ветку `codex/issue-222-object-details` до PR-ready по контракту [OBJECT-DETAILS-EDITING-001](../../specs/OBJECT-DETAILS-EDITING-001.md) и lifecycle [edit-object-details-with-history](../../openspec/changes/edit-object-details-with-history/). Детали scope, разовое разрешение раннего production implementation и authorship зафиксированы в [delivery record](issue-222-object-details-editing-delivery.md). Эта цель заменяет расположенный ниже исторический указатель dashboard; dashboard WIP не смешивать.

Root пишет scope/spec/tests; отдельный `gpt-5.6-sol / low` executor реализует. Два прежних Gate 3 отказа сохраняются. Один независимый reviewer совместно проверяет готовые tests+implementation+evidence и disposition всех findings. До review Gate 3 не `APPROVED`. Локально только bounded checks; полный `make test`/`make verify` запрещён. Затем один exact-source CI, PR-ready без merge/deploy/import/external sends/settings.

---

## Исторический указатель — быстрый refinement операционного дашборда

Поручение владельца 2026-09-21: реализовать согласованный на локальном стенде refinement дашборда. Candidate начат от merged PR #217 и после предупреждения владельца rebased на актуальный `origin/main` `80130fbb` (merged PR #218 ERP operational); конфликты разрешаются с повторным exact-source plan/checks.

Визуальный primary source — throwaway commit `6c46bab2`; он не является production approval. Контракт: [YII2-OPERATIONAL-DASHBOARD-REFINEMENT-001](../../specs/YII2-OPERATIONAL-DASHBOARD-REFINEMENT-001.md). Lifecycle: [refine-operational-dashboard-visuals](../../openspec/changes/refine-operational-dashboard-visuals/). Delivery record: [operational-dashboard-refinement-delivery](operational-dashboard-refinement-delivery.md).

Scope: CSP-safe proportional bars, start-risk read model и точный drill-down, semantic palette, компактная responsive chart/KPI geometry, exact public `shlz-ui` navigation icons/order и единый content-derived asset versioning. Не входят schema/writers/permissions/jobs/rapid-pilot, merge или deploy. ERP equipment sync из PR #218 сохраняется без изменения.

Root пишет scope/spec/tests. Отдельный `gpt-5.6-sol / low` executor реализует production code; независимые reviewers решают planner-required Gates 3/5. Локально только bounded focused checks; полный `make test` / `make verify` запрещён. Один exact-source CI run — после final review.
