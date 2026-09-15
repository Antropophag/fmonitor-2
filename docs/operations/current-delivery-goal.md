# Текущая цель — №150, единая основная навигация Yii2

Поручение владельца 2026-09-15: обновить [№150](https://github.com/Antropophag/fmonitor-2/issues/150) от актуального `main` `2ec819e8` после merge №148 и довести тот же candidate до merge-ready. Scope: один permission-aware MAIN navigation renderer для `/pilot/objects`, `/pilot/construction-control`, `/pilot/otiz`, `/pilot/admin/users` и `/pilot/admin/roles`; одинаковый набор разрешённых canonical links, корректный `aria-current`, сохранённая внутренняя OTIZ navigation и прежняя route authorization.

Не входят RBAC/roles/permission semantics, routes, authorization application logic, sidebar/mobile redesign, frontend framework, generic navigation platform, другие Yii2 surfaces и issues №21/№49/№14/№45/№52. `rapid-pilot/` не читать и не изменять; он не является oracle или compatibility target этого среза. Harness/CI policy не менять, кроме штатной регистрации нового test. Merge/deploy не выполнять.

Контракт: [YII2-MAIN-NAVIGATION-001](../../specs/YII2-MAIN-NAVIGATION-001.md). Lifecycle: [unify-yii-main-navigation](../../openspec/changes/unify-yii-main-navigation/). Root пишет scope/spec/tests; отдельный gpt-5.6-sol/low executor реализует; независимые gpt-5.6-sol/low reviewers решают требуемые planner-ом gates. Локально только planner-selected focused checks; full `make test`/`make verify` запрещён.

Предыдущий указатель №52 из нового authoritative main сохранён в [истории](current-delivery-goal-history-issue-52-2026-09-15-issue150-transition.md). Фактические source/PR/CI/lane получать через harness state и активный package.
