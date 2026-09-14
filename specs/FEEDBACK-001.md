# FEEDBACK-001 — Обратная связь тестового стенда

## Простыми словами

Активный пользователь открывает «Обратная связь», пишет о проблеме или пожелании и получает подтверждение сохранения. Администратор видит обращения и дописывает результат разбора. Это локальный журнал тестового стенда: без внешних отправок, вложений, телеметрии и изменений монтажного процесса.

## Public seam и полномочия

Единственный application owner — `FMonitor2\YiiRuntime\FeedbackApplication`:
`submit(int actorId, string requestId, string description, string pagePath): array`,
`listing(int actorId, ?int beforeId = null): array`,
`recordResult(int actorId, int feedbackId, string requestId, string result): array`.
Конфигурация owner: существующий Yii DB connection, tablePrefix, server-owned appVersion.
Каждый вызов проверяет текущее `status=1`, `activation_state=active` пользователя; submit доступен всем таким пользователям. listing/recordResult требуют существующее активное полномочие `access.administer`. Название роли не даёт доступ. Инварианты PRODUCT.md о неизменяемой истории наследуются; новых процессных полномочий нет.

HTTP: GET/HEAD `/pilot/feedback?from=...` — форма; POST `/pilot/feedback` — отправка; GET/HEAD `/pilot/admin/feedback` — список; POST `/pilot/admin/feedback/<id>/result` — результат. Yii сессия, trusted host и CSRF остаются обязательными. Гость направляется на login (POST без CSRF отклоняется), обычный пользователь получает 403 при чтении/разборе; неизвестный/заблокированный actor не получает данные и не пишет факты через application seam.

## Acceptance matrix

### A1 — Сохранение и подтверждение

Для активного actor 9401, UUID `11111111-1111-4111-8111-111111111111`, описания `Не открывается карточка` и `/pilot/objects/1450` submit возвращает `status=saved`, положительный `id`. Неизменяемая запись хранит этот текст после trim, actor ID, request ID, безопасный page path, object ID 1450, server-owned app version, системное UTC время. Имена, email, телефон, IP, headers и содержимое иных полей не собираются. HTTP показывает «Обращение сохранено», номер и безопасную ссылку возврата к исходной странице. Автор не получает доступ к чужим обращениям через номер подтверждения.

### A2 — Минимальный безопасный контекст

Контекст нормализует owner, а не только HTTP. Он отбрасывает query и fragment, допускает только известные read/form paths: `/pilot/objects`, `/pilot/installers`, `/pilot/construction-control`, `/pilot/admin/users`, `/pilot/admin/roles`, `/pilot/feedback`, `/pilot/admin/feedback`; `/pilot/objects/<positive-int>` и суффиксы `/checklist`, `/assignment-order/selection`, `/execution`; `/pilot/construction-control/objects/<positive-int>/checklist`; `/pilot/objects/<positive-int>/assignment-orders/<positive-int>/originals/submit` и `/originals/history`. Прочие пути, абсолютные/сетевые URL, backslash, управляющие символы, percent-encoded сегменты, oversized ID дают fallback `/pilot/objects`, object ID null. Object ID выводится только из допущенного path, не из POST objectId. Например `/pilot/objects/1450?token=SECRET&email=private#fragment` сохраняет только `/pilot/objects/1450` и 1450. `/pilot/activate?token=SECRET`, `https://example.test/`, `/pilot/objects/SECRET` дают fallback. Возврат использует тот же allowlist; open redirect исключён.

Версия — явное Yii application version `2.0` (или настроенный server-owned release identifier длиной 1–80 ASCII `[A-Za-z0-9._-]`); не Git SHA по догадке и не пользовательское поле. Некорректная конфигурация версии вызывает безопасный отказ без записи. Вводимое пользователем описание сохраняется как текст, UI предупреждает не указывать пароли и содержимое документов; автоматическое содержимое страницы не собирается.

### A3 — Валидация и безопасность

Описание после trim: 1–4000 Unicode символов валидного UTF-8, без управляющих символов кроме CR/LF/TAB. Результат разбора: 1–2000 с теми же правилами. Некорректный текст или UUID дают `status=invalid`, без новых фактов; HTTP 422 с объяснением и сохранёнными корректными полями. Нескалярный POST даёт 400. Неавторизованные команды возвращают `status=access_denied`; listing бросает DomainException `ACCESS_DENIED`. Несуществующее обращение при авторизованном разборе — `status=not_found`/404. Текст и контекст экранируются во всех HTML представлениях; `<script>` не исполняется. GET/HEAD не выполняют команды, неправильный метод на известных feedback routes даёт 405, отсутствие/неверный CSRF не пишет факты.

### A4 — Повтор и сбой

Request ID — UUID v4, идентичность команды ограничена actor и видом команды. Точный повтор после потери ответа возвращает `saved` и исходный id без второй записи; одинаковый ID с другим нормализованным описанием/контекстом (или result/feedbackId для разбора) возвращает `conflict`/409 без изменения истории. Новый ID позволяет осознанно создать новое обращение. Версия при повторе уже сохранённого запроса не переписывается после обновления приложения. Параллельные одинаковые запросы дают ровно одну запись и одинаковый id, несовпадающие — один saved и один conflict.

Транзакционная ошибка не даёт частичных фактов. HTTP 503 сообщает: «Не удалось подтвердить сохранение. Повторите отправку — дубликат не появится.» Сохраняются description/result, requestId и безопасный контекст в форме для повтора; SQL, credentials и детали исключения не показываются. При потере ответа браузер может повторить исходный POST/вернуться к исходной форме с тем же ID. Не требуется offline queue или фоновый retry.

### A5 — Разбор и история

listing возвращает `items` (до 50 обращений по убыванию числового id), `nextBeforeId` (или null). Переход по cursor делает доступными более старые обращения. У каждого item поля `id`, `description`, `pagePath`, `objectId`, `appVersion`, `actorId`, `createdAt`, `results` (по возрастанию event id); каждый result содержит `id`, `result`, `actorId`, `createdAt`. Прочие персональные реквизиты не возвращаются.

recordResult добавляет неизменяемую запись с обязательным текстом, actor/time и request identity. Например `Проверено: исправлено` и затем `Повторно проверено на стенде` остаются двумя видимыми записями; оригинал обращения неизменен. Независимые параллельные результаты с разными ID оба сохраняются — это журнал заметок, без статусов, назначения исполнителя и last-write-wins. Повторы и коллизии защищены A4. Пустой список сообщает «Обращений пока нет».

### A6 — Интерфейс и соседние маршруты

Ненавязчивая ссылка в существующей навигации Yii2: очередь, карточка, подготовка/оригинал/открытие, стройконтроль, справочник монтажников, пользователи/роли. Форма — обычная страница shlz-ui, одно поле описания, объяснение сохраняемого контекста, отправка и возврат; без обязательного JavaScript. Разбор доступен из этой страницы только уполномоченному сотруднику. На 360px и desktop нет горизонтального overflow, наложения на основные действия; label связан с textarea, кнопки доступны клавиатурой. Login/activation не собирают контекст. OTIZ и checklist/process owners не изменяются; существующий общий shell обеспечивает ссылку у checklist view.

### A7 — Persistence и поставка

Additive canonical migration v25 сохраняет старые таблицы и историю, создаёт две feedback tables, повтор не меняет данные; несовместимый schema frontier отклоняется без попытки исправить существующую таблицу. Runtime не выполняет DDL. Новый current-image backup inventory включает обе таблицы и auto-increment frontier; прежние version profiles остаются неизменны. Backup/restore существующим seam сохраняет обращения, результаты и replay identity. Соседние auth, объектная очередь и canonical migration проходят focused checks. Полный make test выполняется только существующим CI consumer для точного финального commit.
