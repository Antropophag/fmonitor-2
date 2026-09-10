# YII2-OBJECT-QUEUE-001

## Простыми словами

Очередь объектов и назначение даты осмотра работают через Yii2. Пользователь
сохраняет фильтры, понятный следующий шаг и возврат после назначения. Чтение
ничего не подготавливает в базе, а назначение и его аудит сохраняются вместе.
Это перенос работающего пилота по #76; карточка, календарь, отмена/перенос
осмотров, новая периодичность и переключение рабочего стенда сюда не входят.

## Основания и public seams

PRODUCT.md, CONTEXT.md, pilot spec/data model и архитектурные инварианты обязательны.
Oracle: rapid-pilot/ObjectQueue.php, InspectionSchedule.php, CompletionFlow.php,
app/PilotHttp/MariaDbObjectQueue.php, InstallationStatusLabels.php,
app/InspectionEvidence/MariaDbChecklistProgress.php. Characterization
CHARACTERIZE-INSPECTION-SCHEDULE-DUPLICATE-001 фиксирует текущий пилот, не новую
предметную политику. Этот срез сохраняет наблюдаемую eligibility назначения;
registered не возвращается как gate загрузки оригинала или открытия работ.

Public HTTP: GET/HEAD /pilot/objects; POST
/pilot/objects/{positive-decimal-id}/inspection-schedule. Application seams:
InstallationProcess\\YiiObjectQueue::read(actorId, query, status, page) возвращает
objects и filters; InstallationProcess\\YiiInspectionPlanning::scheduleInspection(
actorId, objectId, inspectionDate) возвращает status и scheduleId при успехе.
Внутренняя SQL-проекция скрыта от контроллеров. Новая запись имеет одного владельца.

## Матрица очереди

1. Гость получает303 /pilot/login с безопасным return URL. Активный пользователь
   требует exact objects.read через активную роль; имя роли и соседние полномочия
   доступа не дают. Отказ403 без процессных записей. Owner также проверяет право.
2. q обрезается по краям, максимум120 Unicode символов; status из пустого значения,
   needs_assignment_order, ready_to_open, installation, document_closeout,
   completed, needs_assignment_change. page целое>=1, по умолчанию1, размер50.
   Неверные фильтры и страница за пределом возвращают503 как текущий пилот;
   пустая выдача имеет ровно одну страницу. Массив вместо строки не превращается
   в текст Array и не раскрывает исключение.
3. Поиск — буквальная подстрока в ID объекта, регномере, адресе, подъезде;
   %, _ и обратный слеш не являются wildcard. Данные/фильтры HTML-экранируются.
4. Допускаются native_candidate provenance либо imported detail без provenance
   при совпадении object ID и SHA256 исходного payload. Повреждённый hash и
   исключённая provenance не попадают в выдачу. Сортировка: неизвестный NULL
   planned start последним, затем первые10 знаков planned start, затем ID.
5. Сохраняется приоритет latest selection/revision оригинала, latest application
   и latest order: актуальный оригинал latest selection делает неоткрытое дело
   готовым; старый оригинал другого состава этого не делает. Без native selection
   сохраняется историческая готовность по application/registered order.
   prepared без оригинала не означает открытие. Opening tuple (actual date,
   opened at, opened by) может быть весь NULL или весь задан; частичная тройка,
   неподдерживаемое состояние или пустые обязательные реквизиты дают503.
6. Статусы/фильтры сохраняют текущую SQL-семантику oracle, включая исторический
   COUNT DISTINCT completed item кроме42 для фильтра installation/closeout.
   Отображаемый прогресс учитывает последнюю completion/retraction по revision/id
   и существующие веса, максимум85; исправление истории не отменяется переносом.
   Несовпадение старого фильтра после retract отмечено как отдельный долг, без
   скрытого изменения фильтра в этом рефакторинге.
7. Для working: незавершённый монтаж «Монтажные работы» / «Продолжить монтажные
   работы»;85 без обоих документов «Документарное закрытие» с действием
   «Зафиксировать дату акта ПТО» либо «Добавить декларацию»; оба документа
   «Работы завершены» / «Монтаж закрыт актом ПТО и декларацией».
   needs_assignment_change сохраняет «Требуется изменение».
   «Требуется распоряжение» предлагает оригинал при prepare OR
   confirm_registration; «Готов к открытию» предлагает открытие при installation.open.
   Иначе ссылка предлагает открыть карточку. Эти подсказки не выдают полномочия.
8. Страница сохраняет корпоративный shell, заголовок, общее число, таблицу с
   карточками /pilot/objects/{id}, даты («Не указано» для неизвестных), фильтры,
   сброс/empty state и пагинацию с q/status. Новый поиск не несёт прежнюю page.
   Ровно один общий диалог; кнопка назначения только при inspection.schedule и
   отображаемом «Монтажные работы». Дата по умолчанию сегодня Moscow, min сегодня.
   Только строгая реальная дата inspectionScheduled показывает notice.
9. GET/HEAD не пишут process/schema facts и не вызывают migrations/bootstrap/repair.
   На DML-only credentials готовая схема работает; missing/drifted schema даёт503
   без repair. HEAD имеет заголовки GET и пустое тело. Ошибки generic/no-store,
   нет SQL, реквизитов подключения или stack trace в ответе.

## Матрица назначения

1. Гость303 login; GET/HEAD существующего mutation route405. Yii native _csrf
   защищает POST (невалидный/отсутствующий токен400, допускаемое платформенное
   отличие от прежнего403). Поддельные FMONITOR_AUTH_* не являются identity.
2. Owner проверяет активный account+activation_state active, активную роль и
   exact inspection.schedule до проверки даты и дела в одной транзакции с записью.
   blocked/invited, неактивная роль, похожее имя capability и одна objects.read
   дают access_denied/HTTP403 даже с невалидной датой; факты неизменны.
3. Строгая существующая Y-m-d, не раньше сегодняшнего дня Europe/Moscow;
   неверная или прошедшая дата invalid_date/HTTP422. Сегодня допустимо.
   Production clock из единого серверного источника; тест может инъецировать clock
   на application seam, публичные request параметры время не задают.
4. Дело working либо needs_assignment_change, latest order по version_no
   registered и engineer ID>0: eligible. Отсутствующее дело, иной state, последний
   prepared при старом registered и нулевой engineer дают ineligible/HTTP409.
   Один ответ не раскрывает отличий отсутствующего и недоступного дела.
5. В transaction актуальные authorization/eligibility проверены перед вставкой.
   Одна schedule: case ID, object ID, engineer ID, date, actor, Moscow DATE_ATOM.
   Один append-only event inspection_scheduled: schedule ID, case ID, тот же actor
   и timestamp; JSON ровно scheduleId, inspectionDate, controlEngineerUserId.
   Успех status=scheduled и303 /pilot/objects?inspectionScheduled=Y-m-d, no-store.
6. Тождественный повтор и конкурентные запросы по уникальной тройке
   (case, engineer, date) успешны с тем же scheduleId, без новых schedule/event.
   Другой день — новый факт; новый latest engineer для той же даты — новая тройка,
   прежняя schedule не переписывается. Никакой автоматической отмены/переноса.
7. Ошибка записи event откатывает schedule; инфраструктура503. Все отказы оставляют
   прежние schedules/events, case/order/checklist/completion/identity rows точными.
   DML-only principal достаточен; версия24 и schema fingerprints неизменны.

## Примеры и тестовая поверхность

Независимые fixtures задают object451201/case6101, order v1 engineer7299 и v2
engineer7301, actor8101 с exact permission, clock2026-09-10T09:30:00+03:00.
Дата2026-09-12 создаёт одну schedule engineer7301 и один event с тем же timestamp;
повтор возвращает её ID. 2026-02-30 и2026-09-09 дают422, today2026-09-10 допустима.
Для пагинации51 admitted объектов: первая страница50, вторая1, третья503.
Строки с буквальными %/_/\\ отличаются от строки без этих знаков.

Обязательны независимые DB-audits owner/HTTP: вся матрица выше, real Yii
login→queue→dialog→POST→303→notice, browser desktop/mobile/keyboard, assets200,
no page overflow (table-local scroll допустим), no rapid/PilotHttp includes на
перенесённых маршрутах. Native auth/logout, users и OTIZ остаются рабочими.
Полный Quality Graph CI и независимые Gates3/5 завершают этот срез; не весь #76.

## Полномочие на compatibility migration

Прямое решение владельца в issue #76 («рефакторинг с сохранением действующих
пользовательских сценариев», этап4: filters/sorting/pagination и owner планирования)
и текущее поручение2026-09-10 автономно продолжать #76 разрешают этот перенос.
Это superseding amendment для нового Yii маршрута к противоречащим положениям
PILOT-OBJECT-LIST-001 (непагинированный ceiling500, игнорирование query, запрет
controls), PILOT-UI-SHELL-001 и PILOT-SERVICEDESK-REDESIGN-001 (нет filters/pager).
Исторические тесты старых owners сохраняют собственный scope, не ослабляются.
Scheduling здесь остаётся compatibility policy текущего PILOT_ONLY сценария;
новая cadence/cancel/reassignment policy не утверждается. Защита конкурентного
повтора следует существующей unique tuple и общему атомарному invariant.
Для инфраструктурных ошибок используется существующий Yii safe503 contract
с no-store; старый буквальный body/correlation presentation не переносится.

Readiness в пункте queue9 означает exact existing planning/completion/evidence
families (публичные canonical manifests), без копирования их literal schemas.
Для selection/application/original глобальная readiness остаётся prerequisite
deployment; SQL/malformed projection при запросе всё равно закрывают маршрут.
Новый Yii metadata reader должен совпадать со старым manifest comparator для
valid/missing/column/index/CHECK/FK drift; HTTP никогда не исполняет definition DDL.

Действующая readiness оригинала дополнительно закреплена owner feedback2026-09-07
в tests/AssignmentOrderComposition/original_ready_queue_manual_test.php: original
без application уже ready; новая pending selection не наследует старый original
или application. Этот regression входит в соседнюю проверку среза.

Уточнение public read rejection: YiiObjectQueue::read при отсутствии exact
authority выбрасывает DomainException('ACCESS_DENIED'); transport отображает403.
Это сохраняет принятую read-owner convention YiiUserAccess::directory и позволяет
проверять допуск независимо от HTTP. Ошибки данных/фильтров/readiness остаются
инфраструктурными исключениями, отображаемыми Yii safe503.
