## Why

После переноса основных пользовательских маршрутов production worker и scheduler всё ещё запускаются через `rapid-pilot/jobs-entrypoint.php`. Это сохраняет runtime-зависимость от временного адаптера и мешает завершить №76, хотя прикладной владелец очереди уже находится в `app/Jobs`.

## What Changes

- Перевести production `worker`, `scheduler` и их `health` probe на Yii2 console entrypoint.
- Сохранить действующую конфигурацию, сигналы, exit codes, закрытый JSON, lease/retry/deduplication, heartbeat и durable outcomes.
- Сохранить безопасную загрузку Bitrix-конфигурации и удаление временного token-файла.
- Переключить compose/runtime verification на новый entrypoint и доказать отсутствие production-загрузки `rapid-pilot` для этих процессов.
- Не менять очередь, расписание, workforce semantics, схему БД, imports/migrations, web runtime или deployment stand.

## Capabilities

### New Capabilities

- `runtime/yii2-jobs-console`: Yii2 console владеет production transport для worker, scheduler и jobs health, делегируя существующим application owners.

### Modified Capabilities

Нет.

## Impact

Затрагиваются Yii console configuration/controllers, общий console bootstrap, compose entrypoints/healthchecks, focused CLI/compose tests и verification inventory. `app/Jobs` остаётся владельцем поведения; `rapid-pilot/jobs-entrypoint.php` остаётся только историческим oracle до общего cutover.
