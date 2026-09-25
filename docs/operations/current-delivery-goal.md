# Текущая цель — compact mobile-first список стройконтроля

Поручение владельца 2026-09-25: реализовать согласованный макет списка стройконтроля от свежего `origin/main@403a57bded679ec79819a2833f6ed50832f01d86`, создать PR и после обязательных reviews/exact-source GREEN CI вмерджить в `main`.

Scope: responsive Yii2 `/pilot/construction-control`, effective address/entrance/registration/factory identity, white search/filter toolbar, existing ownership/completed/server search, Bitrix document action, inspection create/reschedule/cancel entry point, shipment/local-sync states и full-height checklist rail. На mobile heading/description/count скрываются; desktop сохраняет контекст.

Не менять DB schema, persistence facts, RBAC, Bitrix refresh, inspection-planning application seam/history, calendar/checklist semantics, `rapid-pilot`, shared `shlz-ui` или production deployment. Owner decision supersedes прежний queue-document presentation запрет только для этой bounded UI задачи. Дополнительное поручение владельца: до PR опубликовать candidate на существующем локальном стенде с сохранением его данных и ждать ручной UI validation.

Root пишет scope/spec/tests; отдельный gpt-5.6-sol/low executor реализует; независимые planner-required reviewers решают Gates 3/5. Контракт: [YII2-CONSTRUCTION-CONTROL-MOBILE-LIST-001](../../specs/YII2-CONSTRUCTION-CONTROL-MOBILE-LIST-001.md). Lifecycle: [redesign-construction-control-mobile-list](../../openspec/changes/redesign-construction-control-mobile-list/). Delivery record: [construction-control-mobile-list-delivery-2026-09-25](construction-control-mobile-list-delivery-2026-09-25.md). Точка продолжения для новой сессии: [construction-control-mobile-list-handoff-2026-09-25](construction-control-mobile-list-handoff-2026-09-25.md).

Локально только bounded focused checks; полный `make test`/`make verify` запрещён. PR и один exact-source GitHub CI run выполняются только после ручного подтверждения локального стенда и независимого final review.
