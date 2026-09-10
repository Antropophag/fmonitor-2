## Why

После переноса инспекций №76 карточка Yii2 содержит заглушку документарного закрытия. Сотруднику и Руководителю ФКР нужен целый путь от 85% монтажа до акта ПТО, декларации и исправлений с историей.

## What Changes

- Подключить POST `/pilot/objects/{id}/completion` к Yii2 и существующему `MariaDbInstallationCompletion.record/correct`.
- Показать в карточке текущие документы, формы по точным полномочиям, исходные факты и последовательность исправлений с причиной/автором/временем.
- Сохранить действующие 85/15, duplicate-as-conflict, возврат в карточку/очередь, проверки дат и append-only историю.
- Проверить реальные HTTP, browser, конкурентные команды, отказ БД и соседние Yii flows. Источник текущего поведения: `rapid-pilot/CompletionFlow.php`, `MariaDbInstallationCompletion`, manual-pilot test и PRODUCT.md.

## Capabilities

### New Capabilities
- `runtime/yii2-documentary-closure`: документарное закрытие через Yii2, нормативный контракт `YII2-DOCUMENTARY-CLOSURE-001`.

### Modified Capabilities
Нет.

## Impact

Yii routes/controller/card view и read adapter InstallationProcess; существующий mutation owner, canonical schema v24 без новой миграции. Старый completion handler остаётся только у временного native router до общего cutover. Новый путь не загружает rapid-pilot/PilotHttp. Не меняются формулы, документы распоряжения, даты отношений, политика сессий, offline receipts, jobs и стенд. Новые бизнес-правила не вводятся; отдельного NEEDS_GRILL решения для этого переноса нет.
