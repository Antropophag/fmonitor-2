# Owner decision — inspection photo upload limit and replay

Date: 2026-09-02  
Decision: **APPROVED**  
GRILL: `GRILL-006`

## Limit

Каждая inspection section допускает не более 10 active photos. Revoked photos
не входят в active count, но их append-only history сохраняется.

## Offline overflow

Если server отклоняет queued upload из-за достигнутого limit, он возвращает
deterministic non-retryable `PHOTO_LIMIT_REACHED`. Локальный элемент остаётся
видимым пользователю и может быть удалён либо заменён; automatic retry
запрещён. Silent drop запрещён.

## Operation replay

Exact replay одного client operation id с тем же canonical payload является
идемпотентным duplicate и не создаёт новый DB/file fact. Тот же operation id с
другим canonical payload возвращает `OPERATION_PAYLOAD_CONFLICT` с zero DB/file
mutation.

## Scope

Решение разблокирует target acceptance clauses и Gate 1
`INSPECTION-PHOTO-UPLOAD-001`. Authorization/current-assignment остаётся в
GRILL-003; revoke/correction/re-upload/retention — в GRILL-007. Все delivery
gates сохраняются.
