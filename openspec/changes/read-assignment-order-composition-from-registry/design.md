## Context

См. proposal.md. Registry и five-table selection engine имеют scoped Gate5 и
остаются disabled. Existing physical reader и locked persistence повторно
используют MariaDbOriginalSqlComposition; их переключение не входит в этот пакет.

## Goals / Non-Goals

**Goals:** stable existing reader interface, consistent registered-source lookup,
точные empty/error snapshots, сохранность legacy temporal semantics.

**Non-Goals:** global ownership/readiness scans на каждый lookup, runtime binding,
новая схема/artifact owner, original mutation, selection applicability.

## Decisions

- Owner AssignmentOrderOriginal, все SQL adapters MariaDb-prefixed; read-only
  доступ к approved InstallationProcess tables. rapid-pilot adapter не меняется.
- Существующий physical reader остаётся отдельным до cutover. Новый public
  factory выдаёт тот же интерфейс; verification factory добавляет только phase
  observer. Это позволяет проверить source dispatch без раннего включения ветки.
- Registry-first lookup проверяет own case до source reads. Нет fallback:
  отсутствие/повреждение source не даёт права выбрать другой storage.
- Один RR snapshot и targeted member proof; schema/receipt/global chain проверяет
  deployment. Повторный full readiness на каждый find нарушил бы scoped lookup и
  nondisclosure. Raw SQL failures не объявляют absence.
- Legacy malformed composition в новом adapter даёт unavailable согласно v0.8
  section12. Его old reader и current original application semantics не меняются
  до отдельно проверенного wiring. Selection snapshot не становится effective.
- Новых production hotspots>=150lines и architecture baseline growth нет.

## Risks / Trade-offs

- [Registry и source изменяются конкурентно] → owned consistent RR snapshot,
  deterministic phase barrier fixture и проверка следующего find.
- [Legacy-only deployment не имеет новой family] → новый reader unwired до
  exact readiness; query error остаётся unavailable, без lazy migration.
- [Locked original commit всё ещё physical-only] → явный следующий integration
  dependency; этот reader не выдаётся за готовность original-first workflow.

## Migration Plan

Exact contract Gate1 → public RED/Gate3 → unwired GREEN → relevant legacy reader
regression/architecture → independent Gate5. Wiring входит в combined compatibility
gate после остальных prerequisites; rollback сохраняет history и registry frontier.
