# Текущая цель — №150, единая основная навигация Yii2

Поручение владельца 2026-09-15: реализовать [№150](https://github.com/Antropophag/fmonitor-2/issues/150) от актуального `main` `25aee552` до PR-ready. Scope: один permission-aware MAIN navigation renderer для `/pilot/objects`, `/pilot/construction-control`, `/pilot/otiz`, `/pilot/admin/users` и `/pilot/admin/roles`; одинаковый набор разрешённых canonical links, корректный `aria-current`, сохранённая внутренняя OTIZ navigation и прежняя route authorization.

Не входят RBAC/roles/permission semantics, routes, authorization application logic, sidebar/mobile redesign, frontend framework, generic navigation platform, другие Yii2 surfaces и issues №21/№49/№14/№45/№52. `rapid-pilot/` не читать и не изменять; он не является oracle или compatibility target этого среза. Harness/CI policy не менять, кроме штатной регистрации нового test. Merge/deploy не выполнять.

Контракт: [YII2-MAIN-NAVIGATION-001](../../specs/YII2-MAIN-NAVIGATION-001.md). Lifecycle: [unify-yii-main-navigation](../../openspec/changes/unify-yii-main-navigation/). Root пишет scope/spec/tests; отдельный gpt-5.6-sol/low executor реализует; независимые gpt-5.6-sol/low reviewers решают требуемые planner-ом gates. Локально только planner-selected focused checks; full `make test`/`make verify` запрещён.

Предыдущий указатель №15 сохранён в [истории](current-delivery-goal-history-issue-15-2026-09-15.md). Фактические source/PR/CI/lane получать через harness state и активный package.
