## Why

Локальный loopback Compose pilot не объявляет trusted request scheme. Native
owner-backed Users GET штатно fail-closed503, хотя login/roles/objects работают.

## What Changes

- Явный trusted `http` в local Compose environment; public config→native Users GET
  для администратора с exact access.administer. Source oracle: existing
  PILOT-SESSION-STORAGE-001 trusted scheme и фактический loopback HTTP transport.
- Executable contract PILOT-LOCAL-TRUSTED-SCHEME-001, native RED/GREEN и ревью.

## Capabilities

### New Capabilities
- `pilot/local-trusted-scheme`: явная схема доверенного локального HTTP профиля.

### Modified Capabilities

## Impact

compose.yaml, native regression, operations evidence. Нет нового HTTP/domain
behavior, default/fallback в application, trust request headers, grants или DDL.
Preview recreation отдельно после evidence с сохранением exactimage и volumes;
это не новый deploy source и не fullVERIFY/launch approval. Remote не меняется.
