# YII2-SHLZ-VISUAL-CONTRACT-001 — единый визуальный договор Yii2

## Простыми словами

Все рабочие экраны текущего Yii2-приложения должны выглядеть и вести себя как
одна система: одинаковые таблицы, поля, окна, состояния и оболочка. Работа не
меняет бизнес-правила, данные, права или маршруты; она устраняет расхождения
композиции и проверяет интерфейс на реальных ширинах, с клавиатурой и touch.

## Актор и публичный seam

- Актор: любой активный пользователь Yii2-приложения в пределах уже выданных
  permissions; для admin/ОТиЗ/checklist сценариев используется соответствующая
  существующая роль.
- Публичный seam: существующие `GET|HEAD|POST` Yii2 HTTP routes под `/pilot`, их
  серверная HTML-разметка и существующие пользовательские действия в browser.
- Source oracle для поведения: действующие executable specs, `PRODUCT.md`,
  `CONTEXT.md` и сохранённые characterization tests.
- Visual primitive oracle: только публичные exports закреплённого `shlz-ui`
  `9aaedf50eabf5f92e4af1cbc9c0f2a26a171b35b`.
- Изменение не создаёт нового state-changing seam и не владеет persistence.

## Preconditions

1. Candidate основан на exact `origin/main` не старее audit SHA `bced877a`.
2. Yii test runtime использует изолированные fixtures; пользовательские или
   production records не изменяются.
3. Маршрут и роль актора уже поддерживаются приложением до этого change.
4. Для comparisons before/after используются одинаковые source fixtures,
   viewport, browser и shell/sidebar state.
5. Composer autoload и application classes MUST разрешаться из одного exact
   worktree; dependency directory другого checkout не является допустимым
   baseline и MUST fail before browser evidence is accepted.

## Нормативный контракт

### 0. Authenticated baseline

1. Canonical isolated fixtures MUST пройти login и достигнуть installers, users,
   preopening и ОТиЗ routes до первого предметного assertion без раннего 503.
2. Source-coherence guard MUST подтвердить, что `MainNavigation`, `ViewSupport`
   и их Composer dependencies загружены из exact worktree.
3. Permission-denied responses MUST сохранять действующий 403/303 contract;
   baseline setup не вправе ослаблять authorization.

### A. Shell и landmarks

1. Каждый активный route MUST иметь одну общую оболочку, один `main`, рабочий
   skip-link, видимые focus states и достижимый последний control.
2. Страница MUST NOT неявно владеть `body`, sidebar или workspace через селектор,
   зависящий от наличия предметного потомка. Отличия ширины/фона MUST быть явным
   общим variant.
3. На touch viewport bottom navigation/sidebar MUST NOT перекрывать content;
   safe area MUST учитываться.

### B. Data lists

1. Табличный реестр MUST использовать общий wrapper/head/row/cell contract,
   явные numeric/status/action variants, empty state и pagination.
2. Плотная таблица, которая не помещается, MUST прокручиваться внутри
   подписанного контейнера. Документ MUST NOT получать горизонтальный overflow.
3. Деньги, действия и существенные значения MUST оставаться полностью доступны;
   ellipsis без доступного полного значения запрещён.
4. Card variant допустим только для простого каталога и MUST сохранять labels,
   values, status, actions, row identity и порядок.
5. Empty source и empty search MUST различаться по тексту и допустимому next
   action.

### C. Fields

1. Text/search/date/select/textarea/file control MUST входить ровно в один полный
   Field с одной видимой label, accessible name, help и error slots.
2. Full Field MUST NOT быть вложен в другой полный Field.
3. Ошибка MUST быть связана с control, не удалять введённое значение и не
   кодироваться только цветом.
4. Select/popover MUST NOT обрезаться overflow-родителем.

### D. Modal и drawer

1. Общий overlay MUST иметь header/body/footer, достижимый close target, локальный
   scroll и одного владельца focus lifecycle.
2. После открытия focus MUST войти в overlay; Tab/Shift+Tab MUST оставаться в
   modal context; background MUST быть неинтерактивным.
3. Escape и cancel MUST закрывать без записи; focus MUST вернуться к initiator.
4. Payment confirmation MUST сохранить существующий payload, repeat protection,
   границу «система фиксирует внешнюю выплату» и no-JS fallback.
5. Selection modal MUST применять responsive geometry к фактическому dialog при
   320/390 px, малой высоте, длинных ФИО и большом составе.

### E. Feedback и operational states

1. Empty, loading, success, warning, denied, field error, retryable/final error,
   offline pending, conflict и sent MUST иметь различимый текст и semantics.
2. Offline states MUST NOT сводиться к одному зелёному notification.
3. Разрешённое действие MUST быть доступно без hover; запрещённое действие MUST
   оставаться запрещённым сервером и не появляться как рабочий control.

### F. Сохранность поведения

1. MUST сохраняться routes, query/page/filter state, link/button semantics,
   roles, permissions, payloads, CSRF, idempotency, concurrency behavior,
   formulas, dates, statuses, snapshots и append-only histories.
2. MUST сохраняться offline queue/storage/protocol и server/no-JS fallbacks.
3. Object card MUST сохранить двухзонную композицию, маленькую кнопку-карандаш,
   одно окно с двумя группами, редактируемые реквизиты и запрет ручного Кшах.
4. Повтор после timeout/double click MUST NOT создавать дополнительный факт сверх
   действующего idempotency contract.

### G. Responsive и accessibility acceptance

1. Representative browser cases MUST покрывать 320, 390, 768, 1024, 1280, 1440
   и 1920 CSS px, expanded/collapsed sidebar и границы фактических breakpoints,
   включая 760–769 px.
2. Acceptance MUST включать short height, открытые overlays, long Russian data,
   touch и настоящий 200% browser zoom/reflow. DPR simulation не считается zoom.
3. Close target object card сохраняет visual 40×40 px и MUST иметь touch target
   не менее 44×44 CSS px.
4. Before/after screenshots MUST быть просмотрены человеком на одной среде и
   данных; автоматическое принятие текущего дефекта запрещено.

## Матрица поверхностей

| Группа | Views | Обязательные композиции |
|---|---|---|
| Реестры | `objects`, `construction-control`, `installers`, `users`, `roles` | shell, toolbar, data list, fields, states, pagination |
| Карточка и исполнение | `object-card`, `completion`, `checklist`, `selection`, `execution` | page, fields, overlays, offline/feedback, focus |
| Документы | `original`, `original-history`, `deadline-certificates` | file/date fields, history data list, errors/retry |
| ОТиЗ | `otiz`, `otiz-snapshot`, `_otiz-snapshot-list`, `_otiz-nav` | shell variant, financial tables, tabs, drawer/modal, ledger |
| Обзор | `calendar`, `dashboard` | shell, toolbar, surfaces, readable labels, local overflow |
| Feedback | `feedback`, `feedback-confirmation`, `feedback-admin`, `preopening-error` | fields, success/error/retry, long content, pagination |
| Auth | `login`, `activate` | auth layout, fields, errors, success/expired states |

Каждая строка final inventory MUST назвать применённые общие композиции,
сохранённые исключения с причиной и focused либо representative evidence.

## Rejected cases и точная причина

- Второй локальный common primitive REJECTED: создаёт второго владельца общего
  поведения.
- Page-wide horizontal scroll REJECTED: скрывает navigation/actions и нарушает
  bounded workspace.
- Обрезанная сумма без полного доступного значения REJECTED: теряется финансовый
  смысл.
- Вложенный полный Field REJECTED: создаёт две labels/controls одного факта.
- ARIA-only `div` без focus lifecycle REJECTED: `aria-modal` не реализует modal.
- Автообновление snapshots ради GREEN REJECTED: принимает дефект за baseline.
- Изменение domain facts, permissions, formulas, routes или offline protocol
  REJECTED: находится вне visual seam.

## Authorization, audit и history

- Visual read paths используют существующую route authorization; change не
  ослабляет middleware/controller checks.
- POST actions сохраняют существующие CSRF, permission, audit, idempotency и
  append-only history contracts.
- Cancel/Escape и visual-only navigation не создают audit/history facts.
- Отсутствующий live adapter или непроверенный browser outcome имеет статус
  `UNKNOWN`, а не approval/GREEN.

## Независимые примеры ожидаемого результата

### Example 1 — узкая финансовая таблица

При workspace 390 CSS px и сумме `12 345 678,90 ₽` ожидается локальный scroll
table wrapper. `document.scrollWidth <= document.clientWidth`; полная строка
`12 345 678,90 ₽` и action доступны после прокрутки контейнера.

### Example 2 — выбор монтажников

При viewport 320×568, 20 выбранных людях и ФИО длиной 80 символов modal header и
footer остаются видимыми/достижимыми, body прокручивается, Escape закрывает без
изменения состава, а focus возвращается к кнопке открытия.

### Example 3 — ошибка поля

При отклонённой дате control сохраняет введённую дату, имеет одну label,
`aria-describedby` указывает на текст причины, и причина различима без цвета.

### Example 4 — повтор подтверждения

Два быстрых submit одной существующей команды передают тот же установленный
idempotency key/contract и дают не более одного нового domain fact; visual
refactor не добавляет собственную запись.

## Done

Контракт выполнен только когда V01–V09 проверены, все 25 views имеют заполненный
inventory, заменённые CSS/JS layers удалены, focused checks GREEN, before/after
просмотрены, planner-required reviews APPROVED и один exact-source CI GREEN.
Merge и deployment не входят в Done этого candidate.
