## Why
Очередь объектов остаётся на rapid-pilot и смешивает чтение с подготовкой схемы.
#76 требует переноса полного пользовательского пути на Yii2 без потери истории.

## What Changes
- Очередь, фильтры и назначение осмотра через Yii session/CSRF и явные owners.
- Yii DAO, готовность без HTTP DDL, атомарные schedule/event и replay.
- Сохраняются shell/shlz и наблюдаемая семантика пилота по YII2-OBJECT-QUEUE-001.

## Capabilities
### New Capabilities
- `runtime/yii2-object-queue`: очередь и назначение осмотра в Yii2.
### Modified Capabilities
Нет.

## Impact
InstallationProcess, YiiRuntime controllers/views/assets/config, tests/verification.
Oracle: rapid-pilot/ObjectQueue.php и InspectionSchedule.php. Карточка, календарь,
новые правила инспекций и переключение стенда не входят. Root автор spec/tests,
отдельный sol/low исполнитель и независимый sol/low reviewer по процессу.
