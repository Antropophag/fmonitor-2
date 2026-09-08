# PILOT-WORK-NAVIGATION-ITEM-REMOVAL-001 — remove «Моя работа» from shared navigation

Статус: **OWNER_APPROVED / Gate 1 re-review pending**
Версия: **v2 — deep renderer seam + HTTP sentinels**
Дата: **2026-09-03**

## Простыми словами

Пункт «Моя работа» полностью исчезает из общего меню pilot, потому что владелец
решил его убрать. Сама рабочая очередь `/pilot/` продолжает работать по прямой
ссылке: этот slice не меняет её данные, права, фильтры, redirects или ошибки и
не добавляет переименованную, скрытую либо icon-only замену удалённого пункта.

## 1. Actor, public seam и scope

Actor — любой anonymous или authenticated visitor после inherited route
admission. Public seam — status, application-controlled headers и navigation
DOM успешного configured pilot HTTP response. Target owner — единая shared
navigation composition в `app/PilotHttp`; application/domain/persistence facts
не входят в seam.

Governed configured route families — successful representations exact:

```text
/pilot/
/pilot/objects
/pilot/objects/{positive-id}
/pilot/objects/{positive-id}/assignment-order/prepare
/pilot/objects/{positive-id}/checklist
/pilot/construction-control
/pilot/construction-control/objects/{positive-id}/checklist
/pilot/installers
/pilot/admin/users
/pilot/admin/roles
```

Для parameterized family verifier SHALL использовать существующий positive ID и
actor, допущенного к exact successful representation. Проверяются поддерживаемые
`GET` и `HEAD`. Static assets, command responses, redirects/errors,
compatibility composition без configured shared navigation и screens, которые
не вызывают shared composition, не получают нового DOM contract.

## 2. Exact absence contract

В `<nav aria-label="Основная навигация">` каждого successful governed `GET`
SHALL одновременно отсутствовать:

- descendant с normalized visible text exact `Моя работа`;
- descendant с accessible name exact `Моя работа`, включая visually hidden и
  `aria-label` варианты;
- link с normalized destination exact `/pilot/`;
- current/disabled navigation item, представляющий `/pilot/` через
  `href`, `aria-current`, `aria-disabled`, data attribute или доступный текст;
- hidden, renamed или icon-only replacement удалённого work item.

`hidden` включает HTML `hidden`, `aria-hidden="true"`, `display:none`,
`visibility:hidden`, visually-hidden class и off-screen duplicate внутри
navigation landmark. Renamed/icon-only replacement определяется как новый item
в удалённой первой work-slot позиции или item с `/pilot/` destination/current
semantics без approved sibling label. Другие non-navigation links на `/pilot/`
в content, breadcrumb или user-provided text этим slice не запрещаются.

### Acceptance example: Root queue

Given authorized actor and successful `GET /pilot/`, response SHALL remain the
work queue while primary navigation has no visible/accessibility label `Моя
работа`, `/pilot/` link, current marker or substitute item.

### Rejected implementation examples

Hiding the current span with CSS, changing its label, keeping its icon as a
focusable/clickable item, or converting it to an unlabeled `/pilot/` link SHALL
fail Gate 2.

## 3. Governed route coverage, GET и HEAD

Gate 2 SHALL exercise all ten enumerated current-screen states through the one
production shared renderer seam, with exact section 2 absence and section 4
sibling/accessibility/icon preservation for applicable minimal and broad
actors. Canonical real HTTP entrypoint sentinels SHALL cover `/pilot/` and
`/pilot/objects`; existing route-specific HTTP tests SHALL continue proving
that card, prepare, checklist, construction-control, installer and
administration callers reach their production views and preserve their own
admission/content contracts. Gate 2 SHALL NOT reconstruct route output or add
eight duplicate DB/server fixtures whose sole new observation is the already
exhaustive shared navigation composition.

Sentinel and existing route-specific successful `HEAD` SHALL preserve the corresponding inherited status and
application-controlled headers, including the GET representation's exact
`Content-Length` semantics where already specified, and SHALL return empty
body. HEAD does not manufacture a DOM body for inspection; its paired GET proves
the shared-navigation absence. Removal MUST NOT change method admission.

## 4. Sibling navigation preservation

For identical actor and current-screen inputs, removing the one work item SHALL
leave the ordered sequence, label, destination, visibility predicate,
`aria-current`, disabled state, accessible name and icon bytes of all remaining
groups/items byte-equivalent to the predecessor composition:

1. group `Работа`;
2. permitted link `Стройконтроль` → `/pilot/construction-control`;
3. permitted link `Объекты монтажа` → `/pilot/objects`;
4. disabled `Распоряжения`;
5. when permitted, group `Справочники` then `Монтажники` →
   `/pilot/installers`;
6. group `Управление`, disabled `Расчёты ОТиЗ`, disabled `Контроль`;
7. when permitted, group `Администрирование`, `Пользователи` →
   `/pilot/admin/users`, `Роли` → `/pilot/admin/roles`.

Permission-based omission of conditional siblings remains inherited. The
applicable existing page alone retains `aria-current="page"`; removal neither
adds a root-current substitute nor changes another item's current semantics.
Logo, skip link, breadcrumb, user menu and non-navigation content remain outside
the removed item and MUST preserve predecessor bytes for the same inputs.

## 5. `/pilot/`, redirect, authorization и errors are preserved

Exact `/pilot/` SHALL remain the existing successful work-queue route. Removal
MUST NOT change queue rows, filtering/order, authorization, session behavior,
content, business facts or audit history. Exact `/pilot` without trailing slash
SHALL preserve inherited redirect status, empty body, `Location: /pilot/` and
GET/HEAD application headers.

Inherited unauthorized/forbidden/not-found/method/storage-unavailable outcomes
SHALL remain exact for both applicable GET and HEAD:

```text
401 / 403 / 404 / 405 / 503
```

Their status, body (empty for HEAD), `Content-Type`, `Content-Length`, security/
redaction headers and conditionally applicable `Allow`, `Retry-After` and safe
correlation headers SHALL be byte-equivalent to predecessor contracts. Removal
MUST NOT render a success shell around an error, broaden route admission, grant
or revoke a permission, or make authorization depend on navigation visibility.

## 6. Zero-write, repeat и audit

Navigation composition is read-only. For one accepted route/actor snapshot,
first and repeated GET render SHALL return byte-equivalent navigation after
excluding transport-generated headers. Database tables/rows/counters, session
storage, artifact bytes and process/audit/domain facts SHALL remain unchanged by
the removal. No event records the presentation-only absence.

Concurrent renders SHALL have the same result and SHALL NOT introduce locks,
deduplication state or a public state-changing seam. This change has no schema,
backfill, cleanup or rollback-data operation.

## 7. Gates and Done

The owner approved the v2 deep-seam strategy; fresh independent review of this
exact hash remains required. `restore-pilot-work-navigation` and its reviews are superseded
historical evidence and confer no approval on this opposite behavior.

Done requires, in order: fresh independent Gate 1 review and explicit owner
approval of exact reviewed hashes; exhaustive shared-renderer RED plus root/
object-list HTTP sentinels sensitive to visible, accessible, hidden, renamed
and icon-only variants, with existing route-specific wiring evidence; fresh independent
test approval; minimal shared-composition GREEN; downstream object-list RBAC
predecessor update without weakened authorization assertions; focused GET/HEAD,
route/error/sibling/zero-write regressions; `git diff --check`, architecture
check and full `make verify` with literal `VERIFY_OK`; fresh independent code
approval and append-only status update. This DRAFT does not authorize Gate 2.
