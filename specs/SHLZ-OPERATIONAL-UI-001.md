# SHLZ-OPERATIONAL-UI-001 — карточка объекта на `shlz-ui`

## Простыми словами

Карточка должна сразу показывать, что это за объект, в каком он статусе, кто инженер и что делать дальше. На телефоне, планшете и при увеличении контент не должен ломаться. Данные, права и команды не меняются.

## Acceptance

### A1 — иерархия
Видимы регномер, адрес, статус, инженер и ровно одно разрешённое primary next-action. Состав/документы, сроки, техданные и история разделены на semantic regions.

### A2 — edge states
Long content, unknown dates, missing/corrupt technical data и permission-limited actor сохраняют читаемость и не показывают запрещённые actions.

### A3 — responsive и input
На 320/768/1024/1440 CSS px и 200% zoom нет page overflow. Keyboard order обратим и focus видим. В coarse-pointer context targets не менее 44×44 px.

### A4 — progressive enhancement
При disabled JavaScript authenticated SSR content и native workflow link остаются видимыми и рабочими.

### A5 — domain invariants
Exact routes, methods, CSRF/fields, permissions, GET/HEAD read-only, replay/concurrency, outcomes и append-only facts сохраняются.

## Non-goals

ОТиЗ и остальные active screens доставляются follow-up changes. Новые domain rules, schema и SPA/GSAP dependency не входят.
