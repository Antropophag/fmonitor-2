## Context

См. proposal.md. Base main5822cde3 после PR87. Сохранённая карта использована только
как навигация; актуальный handler — PilotE2ECoordinator, не ChecklistHttpHandler.
Нормативный контракт: specs/YII2-INSPECTION-JOURNEY-001.md, без второй матрицы.

## Goals / Non-Goals

Целый экран требует общего endpoint cohort: item-only перенос ломает correction,
photo и automatic section dispatch. Переносим cohort в одном срезе. Нового
архитектурного аудита, governance layer, инструментов учёта или redesign нет.

## Decisions

InspectionEvidence владеет shared commands/projection; Yii controller переводит
HTTP и получает actor/CSRF от Yii. Старый ChecklistSync становится adapter к
одному owner, сохраняющим native oracle. completeItem используется целиком без
смешанной транзакции. Для переносимых остальных SQL boundaries — Yii DAO.
Views/assets сохраняют DOM/offline protocol; shell/карточка обеспечивают возврат.

## Risks / Trade-offs

- Общая revision связывает photo/correction/item: focused tests проверяют связи,
  rollback и реальные отдельные concurrent requests до final CI.
- Characterization содержит DRAFT product contrasts: сохраняем pilot semantics,
  не объявляем их целевой политикой и не исправляем денежные/предметные правила.
- UI/SW связаны с asset paths и cached CSRF: browser проверяет retry/reconnect,
  user isolation и mobile; new session payload bridge не вводится.
- Schema/frontier не меняется: migration/table fixtures inventory остаётся прежним;
  canonical DML-only fixture и readiness/failure проверяют это, без новых миграций.
- Runtime dependencies: только locked Yii/существующие owners; production image
  и architecture inventory проверяются focused. Backup DB/PDF/photo layout
  сохраняется; restore существующих данных не требует нового формата.

## Migration Plan

Gates1–3 полного кандидата → отдельный executor → focused GREEN → independent
Gate5 → один full exact-source CI. Рабочий стенд не переключать; upgrade/rollback,
sessions/offline/jobs и deployment проверяются и согласуются отдельно по #76.
