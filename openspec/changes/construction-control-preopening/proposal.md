## Why

Назначенный инженер стройконтроля сейчас видит объект в своей рабочей очереди только после открытия работ, хотя именно он должен выполнить явное открытие. Issue #40 требует показать готовый объект раньше и дать инженеру открыть его над заблокированным до открытия чек-листом.

## What Changes

- Включить в `GET /pilot/construction-control` неоткрытые дела с актуальным применимым оригиналом и назначенным инженером, сохраняя открытые рабочие дела и исключение документального закрытия.
- Показывать назначенному инженеру состояние «Готов к открытию» и ссылку на экран чек-листа до открытия.
- Над чек-листом показывать существующее действие `open_confirmed` с фактической датой начала только назначенному активному инженеру с exact `installation.open`.
- После успешной команды оставить объект в очереди как открытый и разблокировать действующие checklist actions; повторное открытие не предлагать.
- Проверить полный Yii2 путь и отсутствие новых фактов у GET/HEAD и отказов.

## Capabilities

### New Capabilities

- `runtime/construction-control-preopening`: очередь и экран чек-листа назначенного стройконтроля от готовности к открытию до открытых работ.

### Modified Capabilities

Нет.

## Impact

Actor — назначенный активный инженер стройконтроля. Source oracle — issue #40 и существующие контракты `YII2-PREOPENING-JOURNEY-001`, `YII2-INSPECTION-JOURNEY-001`, `YII2-CONSTRUCTION-CONTROL-ACTIVE-QUEUE-001`. Public seams — Yii2 `GET /pilot/construction-control`, `GET /pilot/construction-control/objects/{id}/checklist` и существующий `POST /pilot/objects/{id}/execution`/application owner открытия.

Затрагиваются read model очереди, checklist controller/view и focused Yii2 tests. Новых таблиц, writer'ов, прав, предметных правил открытия или записей из read paths нет. Не меняются очередь ФКР, выбор состава/загрузка оригинала, checklist mutation rules после открытия, ОТиЗ, `rapid-pilot`, deployment и текущий stand.
