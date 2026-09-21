# Предыдущая цель — №21, минимальный операционный дашборд

Этот указатель сохранён при переходе 2026-09-21 к следующему owner-approved срезу `add-operational-dashboard-bar-charts`.

Поручение владельца 2026-09-20: реализовать GitHub issue №21 в отдельном worktree от актуального `origin/main`. Поставить один небольшой read-only Yii дашборд на реальных данных объектов монтажа, строго на публичных компонентах `shlz-ui`, и довести candidate до PR-ready.

Scope: `GET|HEAD /pilot/dashboard`, разрешение `objects.read`, четыре показателя на московскую дату среза, два bounded top-5 списка, переходы в существующий реестр/карточки, честные empty/error states, narrow viewport и короткий сценарий демонстрации. Агрегаты считаются на сервере без materialization полного реестра.

Не входили графики и сторонние chart/UI зависимости, финансовые показатели ОТиЗ, произвольные периоды, персонализация, новый DDL/cache/domain facts/RBAC, изменение landing redirect и `rapid-pilot/`.

Lifecycle: [add-minimal-operational-dashboard](../../openspec/changes/add-minimal-operational-dashboard/). Contract: [MINIMAL-OPERATIONAL-DASHBOARD-001](../../specs/MINIMAL-OPERATIONAL-DASHBOARD-001.md).

Root писал scope/spec/tests; отдельный gpt-5.6-sol/low executor реализовал; независимые gpt-5.6-sol/low reviewers проверили planner-required gates. Последний известный candidate commit: `193fa1ea5a6a8c26fc822f58d26dd150ec4623d4`.
