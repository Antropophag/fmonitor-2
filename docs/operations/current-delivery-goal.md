# Текущая цель — общий короткий процесс обычных изменений

Поручение владельца 2026-09-20 заменяет узкое исключение для одного SQL
read-model: расширить существующие compact lifecycle и FAST selection на
повторяющиеся ordinary presentation, read и application-test/refactor changes и
довести один отдельный candidate от `origin/main` до PR-ready.

Base: `ae0a9596ee3b18952832949015d3d345459dfc05`. Branch:
`codex/ordinary-change-process`. Lifecycle:
[extend-ordinary-change-process](../../openspec/changes/extend-ordinary-change-process/).
Contract: [ORDINARY-CHANGE-PROCESS-001](../../specs/ORDINARY-CHANGE-PROCESS-001.md).

Переиспользовать existing planner, lifecycle, ownership, inventory и CI consumer.
Не создавать PR/path whitelist, второй registry, LLM classifier или общий AST
analyzer. #187/#194/#209 — только replay evidence. Не менять product runtime,
stand, merge authority, settings, deploy, #107/#153, supervisor, общий cache или
metrics platform.

Это изменение admission policy чувствительно и не применяет будущий shortcut к
себе: root владеет scope/spec/tests, отдельный executor реализует, независимые
reviewers решают Gate 3 и final. Локально только bounded checks; полный
`make test`/`make verify` запрещён. Ровно один выбранный exact-source CI.
