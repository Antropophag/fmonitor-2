# YII2-CONSTRUCTION-CONTROL-MOBILE-LIST-001 — компактный список стройконтроля

## Scope

Только read-only Yii2 queue `/pilot/construction-control` и existing inspection dialog actions. Actor — пользователь с `construction_control.read` и существующей planning scope. Source oracle — согласованный owner mockup 2026-09-25 и shipped `shlz-ui`. Public seams — real Yii GET/HEAD queue, existing POST inspection-plan and checklist/document links. Не входят DB schema, writers, permissions, calendar/checklist semantics, Bitrix refresh, `rapid-pilot`, deployment.

## A — Identity and search

Каждая запись SHALL показывать full effective address, entrance, registration number и factory/order number. Missing/legacy-zero number SHALL отображаться как `Заводской номер не указан`. Server query SHALL искать case-insensitively и literal-safe по effective address, registration и factory/order number, сохраняя ownership/completed filters, count, pages and stable ordering.

## B — Header and filter surface

Desktop SHALL показывать `Стройконтроль`, описание, count и table headings. На viewport ≤680 CSS px heading/description/count SHALL скрываться, а первой content surface SHALL быть белая toolbar с search, `Мои / Все` и `Показывать завершённые`. Existing clear, debounce submit, page reset, filter validation and pagination semantics MUST сохраняться.

## C — Record actions

Row MUST NOT быть целиком checklist link и MUST NOT содержать nested interactive controls. Available technical document SHALL быть круглой primary icon-link на exact verified URL с `target="_blank" rel="noopener noreferrer"`; иной status SHALL показывать disabled icon-button без URL. No-plan inspection SHALL использовать primary calendar button. Planned inspection SHALL использовать outlined brand calendar button. Checklist SHALL открываться единственной right-side full-height white rail со штатным `chevron-right-duo`, vertical divider, keyboard focus and accessible name.

Это supersedes только queue presentation из `BITRIX-ORDER-DOCUMENT-LINKS-001 A4`: отдельная document action теперь обязательна; exact mapping, ambiguity fail-soft and safe URL rules сохраняются.

## D — Inspection states

Calendar controls SHALL отображаться только actor с exact active `inspection.schedule` и SHALL использовать existing create/reschedule/cancel POST seam, CSRF, request identity, expected version, retained filters, rejection/unknown recovery and append-only event history. Читатель без capability MUST NOT получать action, который гарантированно завершится `Access denied`. Planned row SHALL иметь один calendar trigger; opened dialog SHALL разрешать reschedule и explicit cancel. Moscow-today SHALL показывать только `Инспекция сегодня`; future plan SHALL показывать `Инспекция запланирована на DD.MM.YYYY`.

## E — Operational signals

Row SHALL сохранять colored `.fm2-local-sync` states/accessible labels, shipment `delivery-box` full/partial label, `Готов к открытию`, last activity and empty activity semantics. Unknown shipment MUST NOT изображаться как подтверждённая.

Строка `Готов к открытию` с однозначным current native control-engineer assignment и capability `inspection.schedule` SHALL показывать действие планирования: выезд для открытия является инспекцией. Мобильный dialog SHALL удерживать primary submit в visual viewport Safari; дублирующий footer-close при наличии круглого header-close MUST NOT вытеснять submit. Внутренний input публичного `shlz-ui` date field MUST NOT рисовать второй прямоугольный border/focus поверх общего control outline.

## F — Responsive and accessibility

UI SHALL использовать shipped `shlz-ui` fields, segments, choices, buttons, colors and icons. Inspection dialog SHALL использовать public `shlz-ui` DatePicker с локализованным видимым значением и hidden ISO submission; native date остаётся только disabled fallback после enhancement. Action target SHALL быть ≥40x40 CSS px, строго квадратным/circular, keyboard operable and visibly focused. Long Cyrillic addresses MUST wrap without truncation or document-level horizontal overflow at 320/390 CSS px. Mobile record SHALL сохранять двухъярусную компактную композицию, а desktop checklist rail MUST оставаться узкой ≤56 CSS px областью. Optional press/dialog motion SHALL respect `prefers-reduced-motion`.

На viewport 681–1180 CSS px primary navigation SHALL по умолчанию оставаться узким боковым rail. Раскрытие SHALL показывать overlay drawer поверх неизменной ширины workspace и MUST NOT сжимать или перекомпоновывать список. Drawer SHALL закрываться по `Escape`, клику вне него и после выбора navigation link; focus SHALL возвращаться trigger. На viewport ≤680 bottom navigation SHALL сохранять production composition: один ряд, fixed 48×48 CSS px actions, все permission-allowed links в DOM и доступ к ним через horizontal swipe со скрытым scrollbar; document MUST NOT получать horizontal overflow. На ≥1181 сохраняется постоянный collapsible sidebar.

## Verification

- `tests/Yii2/yii2_construction_control_mobile_list_001_test.php`: projection, HTML states, safe links, retained facts and focused public HTTP behavior.
- `tests/Yii2/yii2_construction_control_mobile_list_browser_001_test.php` plus browser driver: 320/390/desktop layout, long addresses, filters, actions, focus, dialog states and overflow.
- Retained focused consumers: active queue, server filtering, shipment indicator, inspection planning UI/browser, visual contract and verification inventory.
