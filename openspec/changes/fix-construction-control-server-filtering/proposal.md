## Why

Очередь уже использует SQL-пагинацию и canonical completion, но поиск, «Мои / Все» и переключатель завершённых применяются JavaScript только к текущей странице.

## What Changes

- Применять нормализованные URL-фильтры до COUNT/LIMIT/OFFSET.
- Строить «Мои» по текущему native-закреплению, поиск по адресу/регномеру, завершённость по существующему `pto_act AND declaration`.
- Сохранять фильтры в URL и pagination; смена фильтра возвращает page 1.
- Не менять offline protocol, UI, карточку, общий список и ОТиЗ.

## Capabilities

### New Capabilities
- `construction-control/server-filtering`: единая серверная отфильтрованная выборка очереди.

### Modified Capabilities

Нет.

## Impact

Yii2 queue controller/read/view, узкая часть `control-queue.js`, focused HTTP/browser/DB tests. Без миграций и writers.
