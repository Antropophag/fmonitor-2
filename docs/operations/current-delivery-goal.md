# Текущая цель — №20, перенос закреплений стройконтроля

Поручение владельца 2026-09-16: реализовать [№20](https://github.com/Antropophag/fmonitor-2/issues/20) от актуального `origin/main` и довести candidate до PR-ready. Scope: предварительно создать/пригласить local пользователей стройконтроля существующей Yii2 админкой, явно связать их с exact `legacy users.id`, сформировать read-only preview по уже импортированным объектам и перенести `fm_maintable.responsstroicontrol` в standalone append-only assignment owner с reconciliation.

Источником объектов служат уже импортированные `fm2_installation_cases`; legacy-only объекты этим срезом не импортируются. ФИО/email/роль являются только подсказками, не authority. Legacy credentials/roles/rights, распоряжения, applications, originals, opening/checklist facts и legacy rows не изменяются. Существующее отличающееся native-закрепление не заменяется автоматически.

Контракт: [LEGACY-CONTROL-ENGINEER-MIGRATION-001](../../specs/LEGACY-CONTROL-ENGINEER-MIGRATION-001.md). Lifecycle: [migrate-legacy-control-engineer-assignments](../../openspec/changes/migrate-legacy-control-engineer-assignments/). Root пишет scope/spec/tests; отдельный gpt-5.6-sol/low executor реализует; независимые gpt-5.6-sol/low reviewers решают planner-required Gates 3/5. Авторизация автономного implementation не дана: каждый role действует только по prepared package.

Локально только bounded focused checks; full `make test`/`make verify` запрещён. Exact-source GitHub CI выполняется один раз. Merge/deploy/settings и фактический production apply не выполнять. Предыдущий указатель №123 и dirty/conflicted worktree №157 сохранены в Git history/отдельных worktrees и не изменяются.
