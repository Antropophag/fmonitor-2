## Purpose

Определяет least-privilege local-RBAC fixtures для prepare HTTP seam без расширения полномочий, fallback authority или изменения process command.

## ADDED Requirements

### Requirement: Exact prepare GET/HEAD имеет два независимых admission gates
`GET|HEAD /pilot/objects/{positive-id}/assignment-order/prepare` SHALL сначала
требовать active/activated local actor, active assigned role и byte-exact local
permission `assignment_order.prepare`, затем существующую process capability
с тем же literal. Local permission и process capability являются разными facts;
ни один MUST NOT подменять другой. `objects.read`, legacy role, authentication
или похожий permission MUST NOT подразумевать prepare.

#### Scenario: Оба exact gate дают форму
- **WHEN** actor имеет exact local route permission, exact process capability и все предметные preconditions
- **THEN** real prepare route возвращает approved form representation без изменения domain facts

#### Scenario: Read-only actor не получает prepare
- **WHEN** actor имеет только `objects.read`
- **THEN** route возвращает generic 403 и prepare reader/command не выполняется

#### Scenario: Local grant без process capability
- **WHEN** actor имеет exact local permission, но не имеет process capability
- **THEN** route возвращает existing generic 403 до prepare-form read

#### Scenario: Process capability без local grant
- **WHEN** actor имеет process capability, но local permission отсутствует
- **THEN** local boundary возвращает generic 403 до process capability/handler read

### Requirement: Existing GET/HEAD admission precedence сохраняется
Fixtures MUST сохранять для exact GET/HEAD порядок route/method/Host/authentication/
local authorization/process capability/object/state/DB failures, generic
redaction и zero mutation/read rejected cases. POST/media/CSRF command path не
входит в slice. Repeat GET на неизменившемся snapshot SHALL быть детерминирован.

#### Scenario: Revoked prepare read grant
- **WHEN** local grant удалён committed перед новым GET или HEAD
- **THEN** новый invocation отклонён до prepare handler и не использует legacy fallback

#### Scenario: Неверный method не использует GET grant
- **WHEN** actor отправляет fully delivered PUT, PATCH или DELETE с
  bounded payload к exact prepare path
- **THEN** route возвращает existing exact 405 с `Allow: GET, HEAD, POST`
  до local/process authorization и domain/form work
- **AND** response не содержит payload-derived bytes, factory composition
  вызывает `decorate()` ровно один раз, wrapped renderer `render()`/
  compatibility render не вызван,
  а DB/process/artifact/session/files не изменены
- **AND** contract не утверждает, что PHP built-in transport не
  buffer/consume body до application invocation

### Requirement: Canonical renderer invocation наблюдается без подмены
Canonical factory SHALL владеть узким renderer decorator seam. Production
composition MUST использовать identity decorator. Test observation MUST
оборачивать renderer, созданный этим же canonical factory, считать
invocations и делегировать exact input/output bytes без изменения.
Manual reconstructed composition graph и test-owned replacement renderer MUST
NOT быть доказательством canonical wiring.

Успешный GET/HEAD SHALL использовать upload-first read-only representation
`PILOT-PREPARE-FORM-001 v0.2` и SHALL NOT разрешать file/CSRF/multipart/submit
command в этом fixture slice.

Public seam SHALL иметь exact PHP shape:

```php
interface PrepareFormRendererDecorator
{
    public function decorate(PrepareFormRenderer $renderer): PrepareFormRenderer;
}

final class ProductionPilotHttpEntrypointFactory
{
    public static function create(
        EnvironmentSource $environment,
        ?PrepareFormRendererDecorator $prepareFormRendererDecorator = null,
    ): PilotHttpEntrypoint {
        throw new LogicException('Contract signature example; composition is specified below.');
    }
}
```

При `null` factory SHALL использовать `IdentityPrepareFormRendererDecorator`.
При explicit decorator factory SHALL создать real
`ProductionPrepareFormRenderer`, передать его единственному `decorate()` call и
использовать возвращённый renderer во всех canonical positions.

#### Scenario: Allowed GET делегирует real renderer
- **WHEN** actor проходит оба admission gates и form preconditions
- **THEN** spy фиксирует ровно один invocation canonical real renderer
- **AND** response bytes равны bytes, возвращённым real renderer

#### Scenario: Rejected request не достигает renderer
- **WHEN** request отклонён на method, authentication, local permission, process capability, object, state или DB failure boundary
- **THEN** canonical-factory spy фиксирует exactly one composition-time
  `decorate()` call и zero request-time wrapped-renderer `render()`/
  compatibility-render invocations
