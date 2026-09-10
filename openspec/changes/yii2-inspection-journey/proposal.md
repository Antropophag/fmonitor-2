## Why

После PR87 пользователь открывает объект в Yii2, но следующий шаг инспекции
ещё зависит от прежнего HTTP контура. Нужен целый рабочий путь инженера с
сохранением общей offline очереди, соседних действий и возврата.

## What Changes

- Перенести checklist GET, sync-context, операции/фото и возврат через очередь
  стройконтроля в Yii2; сохранить текущие URL, JSON, историю и корпоративный UI.
- Сосредоточить оставшиеся checklist mutations в InspectionEvidence; существующий
  completeItem остаётся единственным владельцем отметки пункта.
- Заменить перенесённые обработчики тонкими адаптерами, без второй реализации правил.

## Capabilities

### New Capabilities
- `runtime/yii2-inspection-journey`: маршрут инженера по нормативному
  `specs/YII2-INSPECTION-JOURNEY-001.md`.

### Modified Capabilities
Нет новых денежных, кадровых или документальных правил.

## Impact

YiiRuntime/controllers/views/assets/config, InspectionEvidence, прежний PilotHttp
adapter и focused HTTP/browser/architecture проверки. Oracle: ChecklistSync,
PilotE2ECoordinator::checklist/syncContext, checklist.js/sw, действующие inspection
specs. Не входят completion ПТО/декларация, новый календарь, console и cutover.
Переключение стенда отдельным согласованным шагом; #76 остаётся открытым.
