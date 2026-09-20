# Текущая цель — №21, минимальный операционный дашборд

Поручение владельца 2026-09-20: реализовать GitHub issue №21 в отдельном worktree от актуального `origin/main`. Поставить один небольшой read-only Yii дашборд на реальных данных объектов монтажа, строго на публичных компонентах `shlz-ui`, и довести candidate до PR-ready.

Scope: `GET|HEAD /pilot/dashboard`, разрешение `objects.read`, четыре показателя на московскую дату среза, два bounded top-5 списка, переходы в существующий реестр/карточки, честные empty/error states, narrow viewport и короткий сценарий демонстрации. Агрегаты считаются на сервере без materialization полного реестра.

Не входят графики и сторонние chart/UI зависимости, финансовые показатели ОТиЗ, произвольные периоды, персонализация, новый DDL/cache/domain facts/RBAC, изменение landing redirect и `rapid-pilot/`.

Lifecycle: [add-minimal-operational-dashboard](../../openspec/changes/add-minimal-operational-dashboard/). Contract: [MINIMAL-OPERATIONAL-DASHBOARD-001](../../specs/MINIMAL-OPERATIONAL-DASHBOARD-001.md).

Root пишет scope/spec/tests; отдельный gpt-5.6-sol/low executor реализует; независимые gpt-5.6-sol/low reviewers решают planner-required Gates 3/5. Локально только bounded focused checks; полный `make test`/`make verify` запрещён. Один exact-source CI consumer обязателен. Merge/deploy/settings не выполнять.

Авторизация: сообщения владельца «В новом ворктри от мейн гита делай» и «Реализуй» разрешают эту поставку в `/Users/antropophag/code/fmonitor-2-issue21`, ветка `codex/issue-21-minimal-dashboards`, base `f145e3e00f25644f5c4e32f7c2f3e8bba4f624a3`. Авторы: root — OpenSpec, normative spec, verification input и RED tests; executor/reviewers записываются после фактического назначения.
