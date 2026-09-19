# Текущая цель — №203, восстановить Yii Calendar

Поручение владельца 2026-09-19: реализовать bounded issue №203 от актуального `origin/main` и довести до PR-ready без merge/deploy/settings. Base: `fa930ed7bd308ede3ab1b083f7e366ba451ecb98`; branch `codex/issue-203-yii-calendar`; worktree `/Users/antropophag/code/fmonitor-2-issue203`.

Scope: восстановить authenticated read-only `GET /pilot/calendar[/]` в Yii; проецировать существующие inspection schedule facts детерминированно; требовать `objects.read`; вернуть `Календарь` в группу `Монтаж` и current state; сохранить no-write GET и scheduling commands; добавить focused HTTP/browser coverage.

Не входят schema/migrations, mutation redesign, новая rapid-pilot domain logic, дополнительный calendar event catalogue и несвязанная полировка sidebar. Запрошенный владельцем FAST передаётся verification planner; authoritative lane и reviews выбирает только planner.

Lifecycle: [restore-yii-calendar](../../openspec/changes/restore-yii-calendar/). Stable contract: [YII2-CALENDAR-003](../../specs/YII2-CALENDAR-003.md). Root авторит scope/spec/tests; отдельный gpt-5.6-sol/low executor реализует; независимый gpt-5.6-sol/low reviewer принимает planner-required review. Чужой WIP №157 и №198 не менять.

Локально только bounded focused checks и применимый architecture check; полный `make test`/`make verify` запрещён. Один exact-source GitHub CI через выбранный existing consumer. UNKNOWN не является GREEN/approval.
