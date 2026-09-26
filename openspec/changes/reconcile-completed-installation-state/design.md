## Context

Production будет пересоздан с нуля без historical import. Live completion owner уже корректно сохраняет `completed`; дефекты находятся только в downstream reads и OTIZ diagnostic.

## Goals / Non-Goals

**Goals:** исправить shared status/dashboard/weekly projections и zero/positive attribution outcomes минимальным read-only delta.

**Non-Goals:** reconciliation/backfill/CLI, schema и migrations, backup/restore, writers, формула/settlement, passport detachment.

## Decisions

1. `InstallationCaseCurrentStatus` возвращает для `completed` отдельный label «Работы завершены»; weekly source наследует его без новой ветки.
2. Dashboard predicate использует persisted state как источник завершения; document facts остаются источником document stage для незавершённых `working` cases.
3. OTIZ проверяет общий progress до создания missing-attribution issues. При zero возвращает empty team; при positive агрегирует deterministic issues с safe case/tab/name, не меняя contributions.
4. Existing exact-working checklist admission и `fm_maintable` join подтверждаются regressions, production code там не меняется.

## Risks / Trade-offs

- [Dashboard metrics drift] → один real fixture сравнивает completed/active/overdue/stage.
- [Zero-progress fix скрывает positive gap] → отдельные zero, one-missing и multiple-missing `forDate()` cases.
- [Diagnostic теряет участника из-за code-only dedup] → stable aggregate issue содержит всех affected members.
