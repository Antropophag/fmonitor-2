## Why

Owned preview стал503: healthcheck создаёт анонимные сессии каждые5s;10001 files
превысили approved storage bound10000. Вход восстановлен сохранением анонимных
return-to sessions в incident archive без удаления или изменения domain history.

## What Changes

Один slice PILOT-HEALTHCHECK-SESSION-001: Docker сохраняет health cookie и проверяет
оба прежних HTTP paths. Actor Docker; oracle реальный LocalAuth/native storage.
Не менять GC bounds, auth, migration, domain или protected E2E. Product ambiguity нет.

## Capabilities

### New Capabilities
- `pilot-healthcheck-session`: bounded повторная проверка без роста сессий.

### Modified Capabilities

## Impact

Compose, небольшой operational CLI, focused native HTTP regression. Launch readiness
не следует из health status. Independent Gates1/3/5 обязательны.
