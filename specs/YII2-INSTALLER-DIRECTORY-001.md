# YII2-INSTALLER-DIRECTORY-001 — справочник монтажников в Yii2

Status: `ACCEPTED_FOR_GATE_2`
Actor: активный пользователь FMonitor с exact capability `installers.read`
Public seam: `GET|HEAD /pilot/installers` через целевой Yii2 web runtime

## Простыми словами

Существующий справочник монтажников переносится с временного rapid-pilot HTTP
пути в Yii2. Пользователь по-прежнему ищет людей, фильтрует кадровый статус и
закрепления, видит текущие объекты и перелистывает каталог. Срез ничего не пишет,
не меняет правила назначения и не добавляет карточку монтажника, sync/import или
новую политику конфликтов и устаревания.

## A1. Маршрут, доступ и transport

`GET /pilot/installers` требует действительную Yii session и exact
`installers.read`. Anonymous browser получает `303` на `/pilot/login` с безопасным
return URL; активный пользователь без capability получает `403`. `HEAD` повторяет
status и существенные headers соответствующего GET с пустым body. Другие методы
дают `405`, `Allow: GET, HEAD`. Ответы защищены действующими Yii security/cache
headers; GET/HEAD не требуют CSRF и никогда не создают facts.

Query shape закрыт: допустимы только scalar `q`, `status`, `availability`, `page`.
`q` после trim имеет максимум 120 Unicode characters; status — empty, `employed`
или `dismissed`; availability — empty, `assigned` или `free`; page — canonical
positive decimal integer. Duplicate keys, arrays, malformed encoding, unknown
keys, noncanonical/zero/negative/overflow page и остальные значения дают
sanitized `400` без DB result или facts.

## A2. Каталог и summary

Показываются только `fm2_workforce_catalog.reconciliation_state=delivered`.
Каждая строка содержит устойчивый positive tabId, trimmed nonempty ФИО/должность,
status `employed|dismissed`, parseable source updatedAt и optional valid
dismissalEffectiveAt. Summary считается по всему delivered каталогу до фильтров:
total, working, dismissed=total-working, assigned на текущую дату Europe/Moscow и
максимальное source updatedAt. Пустой каталог даёт 200 и отдельный текст
«Каталог монтажников пока не загружен».

Результаты сортируются `fio`, затем numeric tabId; страница содержит максимум 50
строк. pages = max(1, ceil(filtered total/50)); page выше pages является sanitized
`400`, а не пустым успешным листом. Pagination отображается только при pages>1,
имеет ровно один `aria-current=page` и сохраняет q/status/availability.

Принятый пример: 125 delivered записей `Монтажник 001..125`, каждая третья
dismissed. Первая страница содержит 50, третья 25, pages=3; при
`status=dismissed&availability=free` и действующих закреплениях 1..5 total=40.

## A3. Поиск и фильтры

`q` ищет Unicode-подстроку ФИО и, если содержит цифры, подстроку numeric tabId
после удаления недесятичных символов и ведущих нулей. Status фильтрует exact
employment status. Availability вычисляется на сегодняшнюю Moscow-дату:
закрепление активно, только если последняя по version_no версия распоряжения дела
имеет status registered, строка не `release`, valid_from<=today и
valid_to absent либо >=today. `assigned` требует такое закрепление, `free` — его
отсутствие. Все фильтры комбинируются AND.

## A4. Проекция закреплений

Для каждой строки показываются все активные закрепления только из последней
registered версии каждого дела: registration number и address из legacy object,
ссылка `/pilot/objects/{positive-id}`. Они устойчиво упорядочены по tabId,
valid_from и object id. Prepared/superseded orders, release rows, будущие и
завершённые интервалы не показываются. Независимо от размера страницы один read
выполняет не более четырёх summary/count/catalog/assignment SQL queries; per-row queries
запрещены.

Кадровые и объектные строки HTML-escaped. Неверные/пустые обязательные строки,
nonpositive identity и invalid dates/status/timestamps в возвращённой проекции не
превращаются в частичный каталог и обрабатываются как A5. Duplicate identities и
broken foreign-key joins исключены canonical primary/foreign-key schema и не
переопределяются read model; их schema drift обрабатывается как missing/invalid schema.

## A5. Сбои и privacy

Missing/unavailable configured DB, invalid prefix, обязательная schema/table/column,
query/read failure и некорректная обязательная строка дают sanitized `503` с
`Retry-After: 60`. Ответ не раскрывает SQL, schema/table/prefix/path/config,
principal, filter/person/object values, counts, exception или stack. HTTP не
выполняет DDL, bootstrap, import или repair; до и после любого успеха/отказа
workforce/process/legacy facts byte-equivalent.

## A6. UI и browser

Страница использует существующие Yii shell/assets, отмечает «Монтажники» текущим
разделом, показывает identity/logout и только разрешённые navigation items.
Форма содержит связанный search maxlength=120, status и availability controls,
submit и условный reset. Таблица/узкое представление показывает ФИО, padded tabId,
должность, кадровый статус, неизвестную дату увольнения, текущие закрепления и
source update. Нулевая фильтрованная выдача отличима от пустого каталога.

В desktop и viewport 390px поля, строки, ссылки и pagination keyboard-accessible,
нет horizontal overflow, console/page errors; поиск, комбинированный фильтр,
page navigation, object link, refresh и logout работают через реальные URL.

## A7. Владение, runtime closure и verification

Yii controller владеет только transport/authorization. Workforce read query
использует одну явно configured Yii DB connection и process/legacy prefixes;
никаких DML/transactions и нового кадрового owner нет. Yii route/controller/query/
view и их production transitive load SHALL NOT include/require/runtime-read
`rapid-pilot`. Старый handler/renderer остаётся behavioral oracle и адаптером
рабочего stand до общего cutover, но не вызывается новым маршрутом.

Gate 2 покрывает A1–A7 через real Yii HTTP и browser, включая canonical example,
denials, closed query shape, HEAD/405, empty/filtered/out-of-range, escaping,
schema/row failure, no-writes, bounded query behavior, mobile и load closure.
Verification inventory включает новые tests ровно один раз. Обязательны focused
GREEN, architecture check, independent Gates 3/5 и один full exact-source CI.
Срез не закрывает общий #76, не переключает stand и не заменяет общий
upgrade/rollback/deployment gate.
