## Purpose

Обеспечивает воспроизводимые local-RBAC fixtures для object-read HTTP routes, сохраняя least-privilege admission и все fail-closed отрицательные контракты.

## ADDED Requirements

### Requirement: GET object list использует canonical local authority
Положительный `GET /pilot/objects` verifier SHALL передавать trusted positive
local actor ID и SHALL предоставлять этому actor active/activated user, active
assigned role и byte-exact `objects.read`. Legacy identity, email или role MUST
NOT быть достаточными сами по себе.

#### Scenario: Exact grant допускает список объектов
- **WHEN** canonical actor с exact `objects.read` выполняет успешный `GET /pilot/objects`
- **THEN** route возвращает свой утверждённый 2xx representation и verifier наблюдает реальные handler/read results

#### Scenario: Legacy-only actor отклонён
- **WHEN** actor присутствует только в legacy users/roles либо передан только через `REMOTE_USER`
- **THEN** route возвращает generic 401/403 и защищённый read handler не выполняется

### Requirement: Negative RBAC cases остаются чувствительными
Fixtures MUST сохранять inactive user/activation/role, missing/near-match grant,
committed revoke и unavailable cases, точные generic outcomes и отсутствие
domain/audit mutation. Повтор неизменившегося snapshot SHALL быть детерминирован.

#### Scenario: Permission revoke закрывает следующий invocation
- **WHEN** exact grant удалён committed после успешного invocation
- **THEN** следующий invocation отклонён до object read и не использует cached или legacy authority

### Requirement: List representation проверяется после admission
После успешной authorization fixture SHALL проверять current approved
object-list status/body/security/DOM contract, не production-derived expected
HTML. Object card, prepare и generic UI shell не входят в этот slice.

#### Scenario: Authorized exact list representation
- **WHEN** canonical actor выполняет `GET /pilot/objects`
- **THEN** response содержит approved list landmarks/local assets и не содержит raw authorization/schema details

#### Scenario: Unknown list suffix не использует grant
- **WHEN** тот же actor выполняет `GET /pilot/objects/unknown`
- **THEN** existing route result сохраняется, а `objects.read` fixture не превращает suffix в list handler
