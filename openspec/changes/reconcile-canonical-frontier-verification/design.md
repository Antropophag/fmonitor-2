## Context

См. proposal и diagnostic fullverify7cb79d0. Actual canonical registration14/15
уже independently approved; old terminal12 consumer expectations не обновлены.

## Goals / Non-Goals

**Goals:** исправить только current-runner oracles и доказать сохранение checks.
**Non-Goals:** production/schema/runtime changes, protected E2E updates, skips,
допуск исключений, удаление metadata/table/history assertions.

## Decisions

Test-only owning layer; migration/persistence остаются у existing owners.
Literal13/14/15 approvals используются как authority. Composed catalog reuse
называется явно, не дублирует новую production introspection как oracle.
Вместо слепой замены12→15 каждый CLI call классифицируется: полный current runner
или scoped historical engine. Изолированный pre-registration checkout13 даёт
реальный missing-successor RED даже при already-landed GREEN implementation.
Rapid-pilot меняется только в verifier; architecture baseline неизменен.

## Risks / Trade-offs

Непреднамеренное ослабление legacy assertions → independent diff review и
сохранение exact negative outcomes, metadata, bytes/counters/no-extras checks.
Новые причины failure после преодоления stale precondition → отдельный scope,
не автоматическое расширение этого test-only пакета.

## Migration Plan

Gate1→13consumers/support edits→pre-registration RED→Gate3→current native GREEN
→Gate5/commit→full VERIFY. Не менять source/tests во время текущего fullrun a8e6e92.

Inherited v13 CHECK transition проверяется точной test-only заменой old clause
на approved v5 clause при сохранении whole-table DDL и всех rows. Это не
исключение таблицы из preservation; остальные metadata bytes остаются exact.
