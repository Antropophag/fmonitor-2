## Why

Prepare-form verifier считает legacy identity/process capability достаточной и поэтому
получает 403 после local-RBAC cutover. Без отдельного gated fixture slice нельзя
доказать, что положительный prepare route имеет ровно необходимые local grants,
а denied cases остаются fail closed.

## What Changes

- Мигрировать ровно `GET|HEAD /pilot/objects/{positive-id}/assignment-order/prepare`
  на local route permission `assignment_order.prepare` и создать canonical
  actor/role fixtures для этого read route.
- Явно сохранить второй downstream gate: существующая process capability
  `assignment_order.prepare`; local permission и process capability не
  подменяют друг друга.
- Не выводить prepare из `objects.read` и не давать wildcard/legacy fallback.
- Сохранить exact method/path, unknown object, wrong state, local denial,
  process-capability denial, DB failure, no-handler-read и redaction assertions.
- Согласовать GET/HEAD fixture с owner-approved upload-first representation
  `PILOT-PREPARE-FORM-001 v0.2`; state-changing upload остаётся вне slice.
- Уточнить public HTTP boundary для fully delivered unsupported-method
  payload: application admission не достигает authorization/domain/form work и
  не отражает payload в 405 response; PHP built-in transport может
  buffer/consume body до application invocation, поэтом app contract не
  утверждает transport-level no-read и не добавляет hidden seam.
- Добавить узкий factory-owned renderer decorator/observation seam:
  production composition использует identity decorator, а test spy оборачивает
  и делегирует real renderer без ручной сборки альтернативного graph. Public
  API фиксируется как `PrepareFormRendererDecorator::decorate(PrepareFormRenderer):
  PrepareFormRenderer` и optional second argument canonical factory `create`.
  Factory composition вызывает `decorate()` ровно один раз на
  request; rejected request доказывает zero request-time `render()`/
  compatibility-render invocations; composition-time `decorate()` count remains one.
- **Actor:** test/integration operator. **Source oracle:**
  `pilot_prepare_form_001_test.php`. **Target public seam:** exact GET|HEAD prepare
  form composition поверх local route admission и existing process-capability
  read. POST command/CSRF вне scope.
- **Release value:** least-privilege prepare verification. **Non-goals:** other
  route mappings, POST prepare command/CSRF, изменение process command, user
  administration, session protocol и PDF contract.

## Capabilities

### New Capabilities

- `verification/pilot-prepare-rbac-fixtures`: Определяет exact local-RBAC prepare fixtures и сохранение всех admission/rejection contracts.

### Modified Capabilities

Нет.

## Impact

Затронуты exact GET route composition в `app/PilotHttp`, local authorization
mapping и prepare test/support fixtures. Change зависит от stable
`LOCAL-RBAC-AUTH-CONTRACT-001` и существующего process capability
`assignment_order.prepare`; POST command/domain semantics не меняются.
