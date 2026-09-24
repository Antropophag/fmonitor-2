## Why

ФКР вынужден обходить карточки объектов, чтобы найти внесённый ПТО без декларации и проверить уже зарегистрированные сведения. Issue #268 выделяет рабочий межобъектный реестр из документарной части #19.

## What Changes

- Добавляется read-only раздел `/pilot/completion-register` с очередью, поиском, фильтрами, счётчиками и серверной пагинацией.
- Добавляется batch read adapter существующих completion facts/corrections и эффективных реквизитов объектов.
- Каждая строка ведёт к существующей форме `/pilot/objects/<id>#completion`; writers и правила completion не меняются.
- Добавляются точечные route/navigation registrations и focused tests.

## Capabilities

### New Capabilities
- `completion-document-register`: доступный по `objects.read` реестр ПТО и деклараций.

### Modified Capabilities
- Нет.

## Impact

Новые application read adapter, Yii controller/view и tests. Нет миграций, новых источников истины, изменяющих endpoints или изменений CompletionController.
