# Текущая цель — №149, единая настройка интеграций через `.env`

Поручение владельца 2026-09-17: переключить активную очередь на [№149](https://github.com/Antropophag/fmonitor-2/issues/149), изолировать незавершённый worktree №157 и реализовать candidate от актуального `origin/main`. Scope: один операторский `.env` для legacy import и Bitrix workforce sync, полный безопасный шаблон, fail-closed preflight до внешних эффектов, атомарная подготовка приватных runtime-файлов и повторное применение изменённых настроек без reset данных.

Существующие Yii2 import/sync owners, read-only доступ к legacy DB, идемпотентность и append-only история сохраняются. Секреты не попадают в argv, вывод, Compose config, image layers или Git. Не входят изменения бизнес-правил интеграций, redesign их владельцев, автоматический reset базы/volumes, merge, deploy и settings.

Lifecycle: [unify-local-integration-env](../../openspec/changes/unify-local-integration-env/). Root пишет scope/spec/tests; отдельный gpt-5.6-sol/low executor реализует; независимые gpt-5.6-sol/low reviewers решают planner-required Gates 3/5. Авторизация автономного implementation не дана: каждый role действует только по prepared package.

Локально только bounded focused checks; full `make test`/`make verify` запрещён. Exact-source GitHub CI выполняется один раз. Предыдущие цели и dirty/conflicted worktree №157 сохранены в Git history/отдельных worktrees и не изменяются.
