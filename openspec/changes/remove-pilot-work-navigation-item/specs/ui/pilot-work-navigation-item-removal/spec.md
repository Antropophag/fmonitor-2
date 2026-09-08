## Purpose

Фиксирует полное отсутствие navigation item «Моя работа» в общей pilot
navigation без удаления рабочей очереди `/pilot/` и без изменения route,
authorization, transport или business contracts.

## ADDED Requirements

### Requirement: Shared navigation не содержит «Моя работа»
Каждый успешный configured pilot HTML representation, использующий shared
navigation, SHALL не содержать navigation item «Моя работа». Внутри navigation
landmark SHALL отсутствовать descendant с normalized visible text или
accessible name exact `Моя работа`, а также item, который представляет exact
destination `/pilot/` как link или current-page marker. Переименование label,
скрытый duplicate и icon-only замена того же item SHALL NOT считаться удалением.

Governed configured set включает успешные `GET|HEAD` representations exact
route families: `/pilot/`, `/pilot/objects`,
`/pilot/objects/{positive-id}`,
`/pilot/objects/{positive-id}/assignment-order/prepare`,
`/pilot/objects/{positive-id}/checklist`, `/pilot/construction-control`,
`/pilot/construction-control/objects/{positive-id}/checklist`,
`/pilot/installers`, `/pilot/admin/users` и `/pilot/admin/roles`. Assets,
command responses, compatibility composition без configured shared navigation,
redirect/error responses и screens, не использующие shared navigation, остаются
вне DOM-removal scope.

Verification SHALL prove the shared composition once across all ten enumerated
current states with exact sibling/accessibility/icon bytes, use real root and
object-list HTTP responses as canonical sentinels, and reuse existing
route-specific HTTP tests to prove the other callers still reach the same
shared renderer. It SHALL NOT duplicate eight database/server fixture stacks
whose only new assertion would inspect the same navigation output.

#### Scenario: Item отсутствует на рабочей очереди
- **WHEN** авторизованный actor успешно открывает exact `GET /pilot/`
- **THEN** shared navigation не содержит text/accessibility label `Моя работа`
- **AND** navigation не содержит link или current-page item destination `/pilot/`
- **AND** рабочая очередь продолжает возвращаться как успешное содержимое route

#### Scenario: Item отсутствует на специализированном экране
- **WHEN** авторизованный actor успешно открывает `GET /pilot/objects`
- **THEN** shared navigation не содержит item `Моя работа` или destination
  `/pilot/`
- **AND** status, authorization и содержимое списка объектов не изменяются этим
  removal

#### Scenario: GET и HEAD используют одну configured composition
- **WHEN** один governed route family успешно отвечает на поддерживаемые `GET`
  и `HEAD`
- **THEN** HTML representation для `GET` не содержит удалённый item
- **AND** `HEAD` сохраняет inherited status/header semantics и empty body

#### Scenario: Остальные route callers сохраняют wiring
- **WHEN** existing route-specific HTTP tests успешно достигают card, prepare,
  checklist, construction-control, installer и administration views
- **THEN** exhaustive shared-renderer oracle применяется к их exact current navigation states
- **AND** route-specific tests сохраняют собственные content/admission checks без нового дублирующего server fixture

### Requirement: Удаление не зависит от полномочий actor
Отсутствие item SHALL быть одинаковым для любого actor, уже допущенного к
конкретному successful governed representation. Removal SHALL NOT выдавать или
отзывать route permissions и SHALL NOT заменять route authorization.

#### Scenario: Minimal и broad actor видят одинаковое отсутствие
- **WHEN** actor с минимальным route permission и actor с дополнительными
  admin/process permissions успешно открывают один governed screen
- **THEN** shared navigation обоих не содержит item `Моя работа` или destination
  `/pilot/`
- **AND** доступ каждого actor к остальным routes по-прежнему определяется
  inherited authorization policy

### Requirement: Route `/pilot/` и transport contracts сохраняются
Removal SHALL быть presentation-only. Exact `/pilot/` SHALL оставаться рабочей
очередью без удаления, redirect или изменения queue filtering/business facts.
Остальные navigation items SHALL сохранять exact order, label, destination и
current/disabled semantics. Rendering SHALL NOT читать или изменять business
persistence либо создавать audit/domain facts.

#### Scenario: Повторный render не создаёт состояние
- **WHEN** один successful governed representation отрисован повторно с теми же
  route и actor facts
- **THEN** shared navigation byte-equivalent между renders
- **AND** business и audit snapshots остаются неизменными

#### Scenario: Canonical root redirect сохраняется
- **WHEN** client вызывает inherited exact route `/pilot` без trailing slash
- **THEN** exact inherited redirect status, empty body, `Location: /pilot/` и
  method/HEAD header semantics сохраняются

#### Scenario: Error и method rejection не маскируются
- **WHEN** inherited route contract возвращает `401`, `403`, `404`, `405` или
  `503`
- **THEN** exact inherited status, plain-text body, redaction/security headers и
  `Allow`/`Retry-After`/correlation header where applicable сохраняются
- **AND** removal не превращает response в successful HTML shell
