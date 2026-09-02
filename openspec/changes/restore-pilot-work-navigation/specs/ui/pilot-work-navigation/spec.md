## Purpose

Гарантирует, что штатный пользователь может из любого успешного pilot screen
вернуться в свою рабочую очередь через стабильную навигацию «Моя работа».

## ADDED Requirements

### Requirement: «Моя работа» ведёт в рабочую очередь
Каждый успешный штатный pilot HTML screen с общим shell SHALL содержать ровно
один navigation item «Моя работа». Вне route `/pilot/` item SHALL быть обычной
same-origin `<a>` с exact href `/pilot/` и без `aria-current`; permission набора текущего actor
MUST NOT превращать эту уже доступную shell navigation в disabled label.

Governed set ограничен configured composition и включает успешные
`GET|HEAD` representations следующих exact route families: `/pilot/`,
`/pilot/objects`, `/pilot/objects/{positive-id}`,
`/pilot/objects/{positive-id}/assignment-order/prepare`,
`/pilot/objects/{positive-id}/checklist`, `/pilot/construction-control`,
`/pilot/construction-control/objects/{positive-id}/checklist`,
`/pilot/installers`, `/pilot/admin/users` и `/pilot/admin/roles`. Asset и
command responses, compatibility composition без configured shared shell,
redirect/error responses и не использующие `PilotView::document` screens вне scope.

#### Scenario: Пользователь возвращается из списка объектов
- **WHEN** авторизованный actor успешно открывает `GET /pilot/objects`
- **THEN** navigation содержит ровно одну ссылку «Моя работа» с href `/pilot/`
- **AND** эта ссылка не содержит `aria-current`
- **AND** response status, authorization и содержимое списка не изменяются

#### Scenario: Permission-набор не владеет destination
- **WHEN** два авторизованных actor открывают один успешный specialized screen,
  причём один имеет только минимальное route permission, а другой дополнительные
  admin/process permissions
- **THEN** оба получают одну и ту же ссылку «Моя работа» на `/pilot/`
- **AND** ссылка не предоставляет доступ к routes, запрещённым обычной
  authorization policy

### Requirement: Текущий пункт не создаёт дублирование
На exact route `/pilot/` shell SHALL отмечать «Моя работа» как текущий пункт
ровно одним non-link element с exact `aria-current="page"`, без `href` и
SHALL NOT добавлять вторую ссылку или второй navigation item с тем же label.

#### Scenario: Рабочая очередь является текущей страницей
- **WHEN** авторизованный actor успешно открывает exact `GET /pilot/`
- **THEN** navigation содержит ровно один non-link item «Моя работа» с
  exact `aria-current="page"` и без `href`
- **AND** DOM не содержит другой item или link destination `/pilot/` с тем же label

### Requirement: Навигация является read-only presentation
Rendering «Моя работа» SHALL NOT читать или изменять business persistence,
создавать audit/domain facts либо менять authorization result. Повторный render
одинакового успешного representation SHALL давать byte-equivalent navigation.

#### Scenario: Повторный render не создаёт состояние
- **WHEN** один успешный screen отрисован повторно с теми же route и actor facts
- **THEN** navigation byte-equivalent
- **AND** business и audit snapshots остаются неизменными

#### Scenario: Ошибка и method rejection не маскируются shell
- **WHEN** inherited route contract возвращает `401`, `403`, `404`, `405` или `503`
- **THEN** exact inherited status, plain-text body, redaction/security headers и
  `Allow`/`Retry-After`/correlation header where applicable сохраняются
- **AND** change не превращает error response в успешный shell

#### Scenario: Canonical root redirect сохраняется
- **WHEN** client вызывает inherited exact route `/pilot` без trailing slash
- **THEN** exact inherited redirect status, empty body, `Location: /pilot/` и
  method/HEAD header semantics сохраняются
