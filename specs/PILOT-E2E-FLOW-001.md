# PILOT-E2E-FLOW-001 — пройти пилотный путь ФКР от очереди до открытия работ

- Статус: `APPROVED`
- Версия: `0.4`
- Дата: `2026-08-29`
- Актор: exact active legacy-пользователь с active legacy-ролью и явно настроенными process capabilities
- Публичный seam: configured production HTTP под `/pilot`
- Наследует: `PILOT-HTTP-AUTH-001 v0.12`, `PILOT-OBJECT-LIST-001 v0.1`, `PILOT-OBJECT-CARD-001 v0.2`, `PILOT-PREPARE-FORM-001 v0.1`, `PILOT-UI-SHELL-001 v0.4`, `ORDER-PREPARE-001..010`, `REGISTRATION-CONFIRM-001 v0.1`, `OPEN-INSTALLATION-001 v0.2`, `ARTIFACT-STORE-001 v0.2`, `PERSISTENCE-PREPARE-001`, `PERSISTENCE-REGISTRATION-001 v0.2`, `PERSISTENCE-OPEN-001 v0.1`

## 1. Цель и граница

Соединить уже утверждённые read-экраны и production `InstallationProcess` в один минимально полный browser journey:

```text
очередь → карточка → выбор состава → подготовленное распоряжение
→ скачивание распоряжения и приложения → ручной номер 1С ДО
→ открытие работ с фактической датой → обновлённые карточка и очередь
→ видимый следующий шаг инженера строительного контроля
```

Срез добавляет только HTTP command/download orchestration, формы и необходимые read-model поля. Он не меняет смысл, порядок проверок, persistence или события существующих process-команд. Любая успешная мутация вызывается через production `InstallationProcess`; download вызывается через production `AssignmentOrderArtifactService`. Прямые SQL `INSERT/UPDATE/DELETE` из HTTP запрещены.

Срез действует только в configured composition с валидными `FMONITOR_SHLZ_CSS_PATH` и `FMONITOR_PILOT_CSS_PATH`. Compatibility composition остаётся read-only и для новых routes возвращает inherited `404/405`; она не получает скрытого mutation fallback.

Подготовка demo-данных, импорт/bootstrap, login/SSO setup и reset сценария не входят в этот срез и следуют отдельным delivery gates. Исполнимый тест использует production MariaDB `fm2_*` rows и legacy identity/object rows, подготовленные fixture до HTTP request; runtime mocks, in-memory process environment и SQL-подмена результата команд запрещены.

## 2. Общие HTTP-инварианты — `PILOT-E2E-FLOW-001-A`

Новые canonical routes:

| Route | Methods | Назначение |
|---|---|---|
| `/pilot/objects/{id}/assignment-order/prepare` | inherited `GET|HEAD`; новый `POST` | выбрать состав и подготовить версию |
| `/pilot/objects/{id}/assignment-orders/{version}/artifacts/{type}` | `GET|HEAD` | скачать `type = order|appendix` |
| `/pilot/objects/{id}/assignment-orders/{version}/registration` | `POST` | вручную подтвердить номер 1С ДО |
| `/pilot/objects/{id}/open` | `POST` | открыть работы |

`id` и `version` — canonical positive decimal без sign/leading zero/overflow. Artifact `type` допускает только literal `order` и `appendix`. Trailing slash, extra/encoded segment и иной path дают inherited `404` до identity/config/DB/body reads. Wrong method даёт `405` с точным `Allow`: prepare route `GET, HEAD, POST`; artifact route `GET, HEAD`; registration/open `POST`. Query не передаёт command values и игнорируется.

Все routes наследуют trusted Host, `REMOTE_USER`, exact user resolution, security headers, redaction, cache `no-store`, configured CSS validation и закрытие ресурсов. Mutation/download требуют authenticated active actor и exact capability до object/process disclosure:

- prepare POST: `assignment_order.prepare`;
- both artifact downloads: `assignment_order.prepare`;
- registration POST: `assignment_order.confirm_registration`;
- open POST: `installation.open`.

Отсутствующее exact capability даёт inherited `403 Access denied.\n`; отсутствие/ошибка capability schema/query даёт redacted `503`, никогда false capability. Capability одной команды не наследует другую. Object не существует или не является exact импортированным pilot case — `404` после capability check. Infrastructure, integrity, renderer/store и unexpected command exceptions — redacted `503 Service unavailable.\n` с `Retry-After: 60`.

### CSRF, body и PRG

После успешной аутентификации command-capable HTML GET (форма prepare или карточка, когда на ней видна registration/open form) сервер выдаёт opaque random CSRF token, связанный server-side с actor ID и pilot session. Read-only queue/card без команды не создают session/cookie. Cookie имеет `Secure; HttpOnly; SameSite=Strict; Path=/pilot`; значение token не равно cookie/session ID. Token имеет не менее 128 bits entropy, single-use для успешной либо достигшей domain command POST попытки и живёт не более 30 минут. Формы содержат hidden `csrfToken`; значение не появляется в URL, log, error, flash или artifact response. Session/CSRF storage не является process/domain history.

POST принимает только `application/x-www-form-urlencoded`, charset UTF-8, body не более 16 KiB и exact allow-list полей соответствующей формы. Missing/duplicate token, missing session, actor mismatch, expired/replayed token либо invalid `Origin`/`Sec-Fetch-Site` дают `403 Invalid request.\n` до process/object reads. При наличии `Origin` он обязан byte-exact соответствовать проверенному request authority с `https`; `Sec-Fetch-Site`, если присутствует, равен `same-origin`. Wrong media type, malformed encoding, duplicate scalar, unknown field или ceiling/body violation дают `400 Bad request.\n` до process command. Installer array допускает не более 500 элементов; duplicate tab ID является validation error, а не silent deduplication.

После любой достигшей domain-команды POST не возвращает success HTML напрямую. Успех и исправимый предметный отказ используют `303 See Other` на canonical same-object URL; flash хранится server-side, показывается ровно один следующий GET и не содержит raw exception/SQL. Refresh GET безопасен и command не повторяет. Infrastructure failure остаётся прямым `503`; ambiguous/unknown persistence result также `503`, без сообщения об успехе и автоматического повтора.

Все successful POST responses имеют empty body, `Content-Length: 0` и literal relative `Location`. `HEAD` никогда не разрешён на mutation routes и не выполняет command.

Read model отдаёт opaque `processRevision` token, связанный с exact case `lock_version` и actor/session; raw DB revision не является авторитетом клиента. Prepare POST требует revision формы. Registration дополнительно требует exact immutable `assignmentOrderVersion`; open требует revision карточки и exact current registered version. Несовпадение token/current revision, сменившаяся current version или concurrent replacement дают `303` на карточку и flash `Данные объекта монтажа изменились. Проверьте актуальное состояние и повторите действие.`; HTTP не повторяет command. Internal command locking/revision остаётся последним authority.

## 3. Подготовка распоряжения — `PILOT-E2E-FLOW-001-B`

Configured GET prepare page сохраняет весь `PILOT-PREPARE-FORM-001`, но форма становится `method="post"` с тем же canonical action и содержит:

- hidden `csrfToken` и `processRevision`;
- существующие `installerTabIds[]`, `controlEngineerUserId`, `controlEngineerConfirmed`;
- primary submit `Сформировать распоряжение` только когда обе eligible группы непусты;
- secondary link `Вернуться к объекту монтажа`.

Никакого input формы организации труда, даты/номера распоряжения или произвольного человека нет. POST нормализует только canonical positive IDs, сохраняет порядок checkbox DOM, требует минимум один уникальный installer, ровно один engineer и literal confirmation `yes`, затем ровно один раз вызывает:

```php
$process->prepareAssignmentOrder($id, $installerTabIds, $controlEngineerUserId, $actorId);
```

HTTP не доверяет GET eligibility и не передаёт object snapshot/дату/форму организации в команду. Успех `accepted=true` требует returned version/status `prepared`, ставит flash `Распоряжение подготовлено.` и отвечает `303 Location: /pilot/objects/{id}`.

Исправимые validation/domain результаты отображаются после PRG на prepare page с сохранённым безопасным выбором только существующих eligible IDs; CSRF/revision выдаются заново. Summary имеет `role=status`; field errors связаны через `aria-describedby`; первый invalid field получает server-rendered error summary link. Exact mapping:

| Причина | Поле / видимый текст |
|---|---|
| missing/empty/duplicate/invalid `installerTabIds[]`, `INSTALLER_REQUIRED` | installers / `Выберите хотя бы одного монтажника.` |
| missing/duplicate/invalid engineer, `CONTROL_ENGINEER_REQUIRED` | engineer / `Выберите одного инженера строительного контроля.` |
| confirmation absent/not `yes` | confirmation / `Подтвердите выбор инженера строительного контроля.` |
| `INSTALLER_NOT_IN_CATALOG`, `INSTALLER_NOT_EMPLOYED` | installers / `Состав монтажников изменился. Проверьте доступных сотрудников.` |
| `CONTROL_ENGINEER_NOT_ELIGIBLE` | engineer / `Выбранный инженер больше недоступен. Выберите другого.` |
| `INSTALLATION_OBJECT_REQUIRED_DATA_MISSING` | summary / `В карточке объекта монтажа не хватает обязательных данных.` |

State/PTO/already-prepared (`ORDER_HAS_PTO_ACT`, `ASSIGNMENT_ORDER_ALREADY_PREPARED`) и `CONCURRENT_MODIFICATION` не возвращают старую форму: `303` на карточку с truthful flash соответственно `Формирование распоряжения недоступно для текущего состояния объекта монтажа.` либо concurrency text раздела 2. `ASSIGNMENT_ORDER_RENDER_FAILED` и `ASSIGNMENT_ORDER_PERSISTENCE_FAILED` дают redacted `503`; HTTP не заявляет, что версия создана. `FORBIDDEN` даёт `403` без field details.

После успеха карточка показывает status `Распоряжение подготовлено`, immutable date/version/team/organization snapshots и два download links. Она показывает inline form `Номер распоряжения в 1С ДО` с hidden CSRF/revision/version и submit `Сохранить номер 1С ДО`; отдельной вкладки, этапа или queue task для номера нет.

## 4. Артефакты — `PILOT-E2E-FLOW-001-C`

Artifact route вызывает только:

```php
$artifactService->download($id, $version, $type, $actorId);
```

Он доступен для prepared и registered immutable version, включая историческую version по exact URL. `accepted=false/FORBIDDEN` даёт `403`. Unknown case/version/type/artifact и hash/size/store integrity failure дают non-enumerating `404 Not found.\n` для structurally valid route; store I/O outage после найденных metadata даёт redacted `503`. HTTP не строит filesystem path из filename/request.

Success `GET` возвращает exact stored bytes и metadata:

```text
200
Content-Type: text/html
Content-Length: exact byte size
Content-Disposition: attachment; filename="{validated ASCII filename}"
X-Content-Type-Options: nosniff
Cache-Control: no-store
```

Filename обязан уже пройти `ARTIFACT-STORE-001`; CR/LF, quote, slash, backslash, non-ASCII или mismatch metadata дают failure без partial bytes. `HEAD` выполняет те же auth/integrity reads, возвращает те же application headers/length и empty body. Response не содержит inline disposition, CSP relaxation, registration number injection или regeneration. Order и appendix links имеют видимый exact text `Скачать распоряжение` и `Скачать приложение` и указывают на exact current version.

## 5. Ручная регистрация 1С ДО — `PILOT-E2E-FLOW-001-D`

POST принимает exact поля `csrfToken`, `processRevision`, `assignmentOrderVersion`, `registrationNumber`. Version — canonical positive decimal. Номер trim-ится ровно как `confirmOrderRegistration`, после trim обязан быть `1..120` UTF-8 characters, без control/NUL/line break. Invalid/empty даёт PRG назад на карточку, inline error `Введите номер распоряжения в 1С ДО.` и не вызывает команду.

При валидном вводе HTTP ровно один раз вызывает:

```php
$process->confirmOrderRegistration($id, $version, $registrationNumber, 'manual', $actorId);
```

Success `accepted=true`, same version `registered` ставит flash `Номер 1С ДО сохранён. Распоряжение зарегистрировано.` и `303` на карточку. Карточка показывает номер, status `Зарегистрировано в 1С ДО`, прежние immutable artifacts/team/date/version и форму открытия. Номер не создаёт process state/task/tab и не пересобирает files.

`REGISTRATION_NUMBER_REQUIRED` отображается тем же field error. Wrong/non-current version, status not `prepared`, already registered, stale revision/concurrent change дают concurrency flash раздела 2 и карточку актуального состояния; HTTP не перезаписывает номер и не вызывает prepare. Unauthorized maps to `403`; persistence/unknown exception maps to redacted `503`.

## 6. Открытие работ — `PILOT-E2E-FLOW-001-E`

Только карточка exact current registered order и actor с `installation.open` показывает inline form:

- `input[type=date] name="actualStartDate"`, required;
- hidden `csrfToken`, `processRevision`, `assignmentOrderVersion`;
- hint `Дата должна быть не раньше даты распоряжения и не позже сегодняшней даты.`;
- submit `Открыть работы`.

POST принимает только эти fields. Date должна быть exact valid `YYYY-MM-DD`. HTTP не подменяет её planned/order/today значением и ровно один раз вызывает:

```php
$process->openInstallation($id, $actualStartDate, $actorId);
```

Success ставит flash `Работы открыты.` и `303 Location: /pilot/objects/{id}`. Missing/invalid date, `ACTUAL_START_BEFORE_ORDER_DATE` и date after Europe/Moscow today возвращаются PRG с field error `Укажите фактическую дату от даты распоряжения до сегодняшнего дня.`. `REGISTERED_ORDER_COMPOSITION_INVALID` даёт card summary `Состав зарегистрированного распоряжения повреждён. Открытие работ недоступно.` без form. Installer no longer employed gives `Состав монтажников изменился. Открытие работ недоступно.` без раскрытия скрытых кадровых значений. No registered current order, already open, wrong version/state и concurrent change дают concurrency flash и актуальную card. Unauthorized maps to `403`; persistence/unknown result maps to redacted `503`.

После успеха карточка показывает `В работе`, exact actual date/opened timestamp/actor, `Чек-лист: Доступен`, зарегистрированное распоряжение и downloads. Prepare/registration/open forms отсутствуют. В visually prominent next-step area видимы:

```text
Следующий шаг
Инженеру строительного контроля: провести первую инспекцию объекта.
Ответственный: Анна Волкова
```

Это derived UI instruction из `working + current registered controlEngineer snapshot`; он не создаёт `fm2_process_tasks`, не обещает срок/notification и не является кнопкой несуществующего inspection route.

## 7. Очередь после переходов — `PILOT-E2E-FLOW-001-F`

Этот срез расширяет configured queue read model утверждёнными process facts, но не меняет membership и canonical order `PILOT-OBJECT-LIST-001`. Для каждого объекта показываются textual status и один truthful next step:

| Projection | Status | Next step |
|---|---|---|
| no order | `Требуется распоряжение` | `Сформировать распоряжение` |
| current `prepared` | `Распоряжение подготовлено` | `Внести номер 1С ДО` |
| current `registered`, not opened | `Готов к открытию` | `Открыть работы` |
| `working` | `В работе` | `Инженеру: провести первую инспекцию` |

Queue action остаётся одной canonical card link; mutation form/button в строке отсутствует. Actor без соответствующей next-command capability видит status, но next step формулируется `Откройте карточку объекта монтажа`, без ложного обещания доступной команды. Working next step виден broad reader, потому что это состояние процесса, а не command authorization.

Process projection query — обязательная dependency configured queue: duplicate/corrupt state или SQL denial даёт redacted `503`, не silent omission. Unconfigured compatibility queue сохраняет predecessor narrow projection. После successful open новый GET queue из MariaDB показывает объект `4512` как `В работе` и `Инженеру: провести первую инспекцию`; SQL/manual refresh не требуется.

## 8. Fixed executable example — `PILOT-E2E-FLOW-001-G`

Production MariaDB fixture до первого request содержит реальные schema rows:

- active actor `18 / Сидоров Сергей Сергеевич / sidorov@shlz.ru` с active role и exact capabilities prepare, confirm registration, open;
- active engineer `73 / Анна Волкова`, active role, `construction_control_engineer`, position `Инженер строительного контроля`;
- imported case/object `4512`, revision `1`, `needs_assignment_order`, no order/opening/PTO/completion; registration `77-000123`, address `Москва, ул. Примерная, д. 10`, entrance `2`, planned dates `2026-10-05..2026-12-20`, prefill engineer `73`;
- current `fm2_workforce_catalog` installer `1042 / Иванов Иван Иванович / Электромеханик по лифтам`, employed `2024-02-01..null`, source `one_c_zup_via_bitrix`, updated `2026-08-27T18:15:00+03:00`;
- production clock returns one exact instant per successful command in chronological order: prepare `2026-08-27T12:30:00+03:00`, registration `2026-08-28T12:15:30+03:00`, opening `2026-08-28T12:45:00+03:00`. The immutable prepare `occurredAt` is therefore `2026-08-27T12:30:00+03:00`, and its `assignmentOrderDate = 2026-08-27` is derived from that same instant in `Europe/Moscow`.

One cookie-aware HTTP client performs, with token/revision read from immediately preceding HTML:

1. `GET /pilot/objects` → one link to `4512`.
2. `GET /pilot/objects/4512` → `Требуется распоряжение`, launch link.
3. `GET` prepare → installer `1042`, engineer `73` prefilled, confirmation unchecked.
4. `POST` prepare with `[1042]`, `73`, `yes` → `303` card.
5. Follow GET → prepared version `1`, two exact artifact links and registration form.
6. GET both artifact URLs → exact persisted HTML bytes и metadata:

   ```text
   order:
     filename = assignment-order-v1.html
     mediaType = text/html
     size = 1078
     sha256 = 7940150eaea4b749f2f80997f98e159ceac12c3d6ca2fca2fa5f847a689fee06

   appendix:
     filename = assignment-order-v1-appendix.html
     mediaType = text/html
     size = 1247
     sha256 = 966227fba7d9acc15b39d06850fced300436856d31fbe614cad5f4397a923b01
   ```

   Exact bytes наследуют два literal UTF-8/LF шаблона `DOCUMENT-RENDER-HTML-001 v0.2` без любых других изменений, но exact engineer fragment в обоих files равен `Анна Волкова — Инженер строительного контроля`.
7. POST registration number ` 12-Р ` and version `1` → `303`; follow GET shows `12-Р`, registered status and open form.
8. POST actual date `2026-08-28`, version `1` → `303`; follow GET shows working/opening facts/checklist and exact engineer next-step block.
9. GET queue → same object status/next step updated from persisted `fm2_*` state.

After step 9 a fresh production composition/new MariaDB connection returns the same opened projection. Exactly one order version, installer assignment, engineer assignment, order artifact and appendix artifact exist; exact events are prepare, registration, opening in order. Files and snapshots created at prepare are byte-/metadata-identical after registration/open. No runtime mock, manual SQL between requests, second version, task for 1C number, duplicated event or direct legacy composition write occurs.

## 9. Gate 2 observable contract

Один RED real-HTTP integration test owns this complete slice. It starts a configured production HTTP entrypoint against unique-prefix production migrations and fixture, uses real cookie/CSRF/PRG requests and observes only raw HTTP, parsed DOM, public process projection and artifact bytes/metadata. SQL is allowed only to establish the pre-request external/case fixture, compare approved no-write external tables and clean the unique prefix; expected command state/order/events/artifacts are not read from private tables or production output.

Test proves happy journey section 8 plus representative rejection for every mapping group: malformed body, CSRF, missing installer, unconfirmed engineer, stale revision/version, invalid registration number, open before order date, capability denial, unknown artifact and artifact integrity failure. It verifies zero domain mutation for pre-command transport/auth/validation failures and append-only unchanged prior facts after domain rejection.

Expected IDs, dates, copy, filenames, headers, URLs and state are literals from this specification and inherited approved specs. Test/reviewer may reuse test support for HTTP transport and unique-prefix migrations, but may not improve harness, add mocks, inspect private renderer methods or derive expected values from implementation.

### Изолированный infrastructure-failure fixture v0.4

Для детерминированного доказательства redacted artifact/infrastructure failure Gate 2 разрешает один отдельный isolated fixture case. Он не является шагом main journey и выполняется по всем следующим правилам:

1. test создаёт свой unique-prefix production MariaDB fixture и свой task-owned artifact root, заранее доводит public process seam до нужной prepared projection и фиксирует её public projection/artifact metadata as the before oracle;
2. до запуска dedicated production HTTP composition и до любого HTTP request тест создаёт ровно один обратимый infrastructure fault в принадлежащей этому fixture границе: например, temporary rename точного content-addressed blob либо временно unreadable task-owned artifact root. Нельзя повреждать shared/user artifact root, `../shlz-ui`, source tree или unrelated database;
3. dedicated composition получает ровно один failure HTTP request к exact artifact URL. До и после него нет probe/warm-up/retry/follow-up request; expected public response берётся из раздела 4, а не из exception/filesystem/SQL details;
4. fault restoration выполняется в mandatory `finally`, даже если assertion/request/composition startup fails. Перед cleanup test новым production process instance/connection сравнивает public process projection с before oracle и читает restored artifact только через public `AssignmentOrderArtifactService`; equality доказывает no domain mutation и byte restoration;
5. private-table rows, filesystem layout, implementation exception и production output не используются как expected oracle. Filesystem operation разрешена только для setup/restoration точного test-owned fault target, а direct SQL — только в обычных pre-request fixture/cleanup boundaries раздела 9.

Это узкое test permission не меняет product runtime behavior и не разрешает application code инъектировать faults, переименовывать artifacts, менять permissions или обходить service. В main journey раздела 8 по-прежнему запрещены SQL, filesystem/manual intervention, fixture mutation и process restart между requests.

### Независимое выведение artifact oracle v0.2

Fixed product example intentionally keeps engineer identity `73 / Анна Волкова`, because the prepare-form prefill, selected team, opened-card responsible person and next engineer step all pin that same person. Previous renderer tracer used a different descriptive snapshot `Петров Пётр Петрович` for the same numeric user ID; its metadata therefore cannot be copied into this E2E example.

Oracle section 8 is derived only from the approved literal templates of `DOCUMENT-RENDER-HTML-001 v0.2` and the fixed input above:

1. retain every byte, UTF-8 encoding, LF and final newline of each approved template;
2. replace the one engineer-name occurrence in each artifact from the renderer tracer's 38-byte UTF-8 name to the fixed example's 23-byte UTF-8 name;
3. retain exact position `Инженер строительного контроля` and every other field unchanged;
4. the independently fixed length of each artifact is consequently 15 bytes below the predecessor tracer (`1093 → 1078`, `1262 → 1247`); SHA-256 over those complete specified bytes is fixed in section 8.

No production renderer, stored blob, process metadata or current implementation output is used as the source of these expected values. A later intentional template change requires a newly approved spec version and new independent oracle; it cannot silently update the E2E expectation.

### Единый time seam v0.3

`ORDER-PREPARE-002` and product decision `DECISION-004` require one source instant for both audit and document business date:

```text
occurredAt = Clock.now()
assignmentOrderDate = occurredAt converted to Europe/Moscow and formatted YYYY-MM-DD
```

An adapter may encapsulate timezone/calendar conversion for testability, but it must receive or otherwise be bound to that exact command instant. It is not an independently configurable second clock and cannot return a date inconsistent with `occurredAt`. In particular, a production environment override such as `businessDate = 2026-08-27` combined with prepare `Clock.now() = 2026-08-28T12:30:00+03:00` is forbidden: it would make the signed-document date and append-only audit describe different command days and would contradict the already approved domain behavior.

The fixed example therefore advances the same production clock between requests and uses the three exact instants in section 8. It does not freeze one `now` for the complete multi-request journey and does not introduce a separate business-date environment input. Registration and opening retain their approved later instants. The artifact oracle remains unchanged because its document date is still the correctly derived `2026-08-27`.

## 10. Не входит в срез

- bootstrap/seed/reset command and short launch instructions;
- SSO/reverse-proxy provisioning;
- PDF/DOCX conversion: approved artifacts remain honest `text/html` downloads;
- 1С ДО integration, separate registration screen/task/tab;
- inspection command/form, task/SLA creation or notification;
- changed assignment order/version 2, replacement, completion or checklist implementation;
- search/filter/pagination, Bitrix history, CI, harness work;
- architectural refactor outside the maintainable Pilot HTTP view/application/read-model seams required here;
- edits/copies inside `../shlz-ui`.

## 11. Gate 1 approval

- Product owner: project user
- Approved by: separately tasked Gate 1 Codex agent `/root/e2e_spec`
- Date: `2026-08-29`
- Decision: `APPROVED`
- Comment: пользователь явно поручил delivery-optimized цельный демонстрационный путь, разрешил объединять близкие acceptance statements и потребовал сохранить SSD/TDD gates. Version `0.4` соединяет только уже утверждённые domain/persistence behaviors через production HTTP, фиксирует public observable PRG/CSRF/capability/concurrency/download/UI outcomes and permits one isolated, restored pre-start infrastructure fault fixture without weakening the uninterrupted main journey or runtime boundaries.

Gate 2 разрешён только для version `0.4`; тест, независимый test review, implementation и независимый code review выполняются fresh bounded-context agents.
