## Purpose

Определяет fail-closed lifecycle routing и минимальный durable evidence set для planner-selected FAST maintenance без ослабления executable verification, независимого final review или exact-source CI.

## ADDED Requirements

### Requirement: Planner-owned fail-closed lifecycle route
Repository prepare seam SHALL выдавать ровно один machine-readable route `FAST_MAINTENANCE` или `OPENSPEC_REQUIRED` и стабильный reason. Route `FAST_MAINTENANCE` SHALL быть допустим только когда актуальный planner plan уже выбрал `FAST`, canonical requirement существует и непротиворечив, change восстанавливает существующее behavior, а вход явно подтверждает `semantic_change=false`. Harness MUST NOT самостоятельно расширять или переопределять FAST classifier.

#### Scenario: A — existing requirement и planner FAST
- **WHEN** bounded presentation bugfix имеет planner-selected FAST plan, одну или несколько существующих canonical requirement references с актуальными digest и `semantic_change=false`
- **THEN** prepare выбирает `FAST_MAINTENANCE` и не требует нового proposal/design/tasks/delta-spec

#### Scenario: E — новое product behavior
- **WHEN** small UI change добавляет behavior или меняет acceptance semantics
- **THEN** route равен `OPENSPEC_REQUIRED` с reason semantic change

#### Scenario: F — permission semantics
- **WHEN** small UI change затрагивает auth, authorization или permission semantics
- **THEN** route равен `OPENSPEC_REQUIRED` и sensitive lifecycle сохраняется

#### Scenario: G — schema или persistence
- **WHEN** change затрагивает schema или persistence contract
- **THEN** route равен `OPENSPEC_REQUIRED`

#### Scenario: H — offline или service worker state
- **WHEN** change затрагивает offline, service worker, cache, synchronization или state semantics
- **THEN** route равен `OPENSPEC_REQUIRED`

#### Scenario: I — verification policy
- **WHEN** change меняет verification или admission policy
- **THEN** route равен `OPENSPEC_REQUIRED`

#### Scenario: J — planner STANDARD
- **WHEN** planner выбирает `STANDARD` или `CRITICAL`
- **THEN** route равен `OPENSPEC_REQUIRED` независимо от размера diff

#### Scenario: K — canonical requirement отсутствует
- **WHEN** вход не содержит допустимой canonical requirement reference
- **THEN** prepare fail-closed выбирает `OPENSPEC_REQUIRED`

#### Scenario: L — canonical references противоречат друг другу
- **WHEN** referenced requirements конфликтуют или semantic conclusion требует owner decision
- **THEN** prepare не выбирает shortcut и сообщает `NEEDS_OWNER` в reason/disposition существующего normal discovery route

### Requirement: Minimal durable FAST maintenance record
Для `FAST_MAINTENANCE` harness SHALL расширять существующий delivery/change record и package, а не создавать второй registry. Machine-readable record MUST связывать issue/task, exact source и base, FAST class/reason, canonical requirement paths и digests, `semantic_change=false`, executable regression/spec reference, selected verification plan, final independent review reference, exact-source CI reference и final disposition. Он MUST ссылаться на canonical acceptance, а не копировать её полный текст.

#### Scenario: Compact record при prepare
- **WHEN** eligible FAST maintenance подготавливается через public harness seam
- **THEN** package содержит обязательные поля, references разрешаются внутри checkout или external evidence store, а отсутствующие future review/CI/disposition значения явно `PENDING`/`UNKNOWN`, не GREEN

#### Scenario: Повторный prepare
- **WHEN** одинаковые source, base, input и requirement bytes подготавливаются повторно
- **THEN** lifecycle route и requirement digests детерминированы, без создания дублирующего нормативного artifact

### Requirement: Canonical requirement freshness
Каждая canonical requirement reference SHALL быть связана с digest её current bytes. Prepare/state/apply SHALL считать FAST maintenance package stale, если referenced content изменилось или reference больше не разрешается; stale package MUST быть пересобран и повторно оценён planner/routing до продолжения.

#### Scenario: D — requirement digest изменился после prepare
- **WHEN** referenced requirement bytes изменились после package prepare
- **THEN** state/apply сообщает stale/rebuild и не продолжает по прежней semantics

### Requirement: Verification и review guarantees сохраняются
`FAST_MAINTENANCE` SHALL требовать executable regression, доказанный intended RED до executor-role preparation, planner-selected focused verification, один independent final review и exact-source CI. Shortcut MUST NOT выдавать approval/GREEN из отсутствующего или `UNKNOWN` evidence и MUST NOT добавлять Gate 3, когда planner его не требует.

#### Scenario: B — executable RED обязателен
- **WHEN** eligible FAST maintenance не имеет применимого intended RED record для regression reference
- **THEN** implementation admission отклоняется

#### Scenario: C — final independent review обязателен
- **WHEN** implementation и focused checks GREEN, но final independent review отсутствует или не APPROVED для exact source
- **THEN** PR-ready disposition запрещён

#### Scenario: Exact-source CI обязателен
- **WHEN** final review APPROVED, но CI source отличается, не GREEN или UNKNOWN
- **THEN** final disposition не является PR-ready

### Requirement: Existing OpenSpec lifecycle сохраняется
`OPENSPEC_REQUIRED` SHALL направлять change в существующий OpenSpec/normal lifecycle без автоматической генерации OpenSpec внутри T06. Explicit OpenSpec proposal tooling MUST продолжать работать, а STANDARD/CRITICAL artifact/review policy MUST остаться неизменной.

#### Scenario: N — explicit OpenSpec tooling
- **WHEN** пользователь запускает существующий `openspec-propose` для change, которому OpenSpec требуется
- **THEN** proposal/spec/design/tasks workflow остаётся доступным и не заменяется FAST maintenance record

### Requirement: Historical lifecycle measurement
Repository SHALL иметь deterministic historical replay, который отдельно сообщает mandatory created artifact count, суммарные bytes/chars этих artifacts, mandatory independent review dispatches и explicit lifecycle phase stops до/после. Отчёт MUST отдельно подтвердить сохранение regression, final review, exact-source CI и canonical traceability и MUST NOT называть proxy фактической экономией tokens без telemetry.

#### Scenario: M — presentation maintenance replay
- **WHEN** сохранённый historical FAST presentation candidate проигрывается через старый normal lifecycle baseline и новый eligible route
- **THEN** конечные verification/review guarantees совпадают, mandatory artifact count/bytes уменьшаются, review dispatches уменьшаются согласно planner FAST, proposal boundary отсутствует, а token usage остаётся `UNKNOWN`, если telemetry отсутствует
