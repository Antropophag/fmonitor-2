# YII2-DOCUMENTARY-CLOSURE-001 v0.1

## Простыми словами

Карточка Yii2 позволяет ФКР зафиксировать акт ПТО после 85% монтажа, добавить декларацию и исправить ошибочные сведения с причиной. Видны исходные документы и вся цепочка исправлений. Это перенос действующего процесса №76, без новых денежных правил, файлов документов или переключения стенда.

## Основания и seam

PRODUCT.md (решения 2026-09-07), действующий `MariaDbInstallationCompletion` и `installation_completion_manual_pilot_test.php`; HTTP oracle — текущий `CompletionFlow.php`, а не черновые CHARACTERIZE документы сентября 1. Общие Yii session/CSRF/CSP contracts наследуются от YII2-PREOPENING/INSPECTION. Пользователь подтвердил продолжение №76 после merge PR89. Root — спецификация и тесты; отдельный sol/low — реализация; независимый sol/low — reviews.

Публичный seam: GET/HEAD `/pilot/objects/{id}` (секция `#completion`) и POST `/pilot/objects/{id}/completion` через `public/yii.php`, реальный login/session, Yii `_csrf`. Имена action/полей сохраняются: `record_pto/ptoActDate`, `record_declaration/declarationDate/declarationDetails`, `correct_pto|correct_declaration/factId/.../reason`. Actor приходит только из identity. POST успех — 303 Location `/pilot/objects/{id}#completion`, пустое тело, no-store. Возврат из checklist к секции и из карточки в `/pilot/objects` сохраняется.

## A1. Admission и транспорт

GET/HEAD карточки требует `objects.read`; POST требует active/activated identity, active role и exact capability `installation.completion.{pto|declaration}.{record|correct}`. Стандартные сотрудник/Руководитель ФКР получают действия своими существующими grants. Инженер, ОТиЗ, администратор и reader не получают их автоматически. Не добавлять нового role-code ограничения поверх существующего application capability contract. Потеря grant/status/activation после login блокирует запись; actor из payload игнорируется. CSRF проверяется Yii, invalid/missing — 400/403 без фактов. Guest/disabled session не исполняет команду (303 login либо 401/403).

Completion URL принимает только POST; остальные методы дают 405 с Allow: POST. Нулевые, ведущие нули, отрицательные, переполненные и смешанные id — 404. Неподдерживаемый action — 422 «Неизвестное действие.». Нестроковые поля (включая массивы), неоднозначные duplicate form members, malformed encoding и invalid factId — 400/422 без фактов; form body ограничен 16384 bytes, превышение — 413; неприемлемый content type — 400/415. Никакого process DDL, импорта или bootstrap в запросе. HEAD карточки имеет пустое тело.

## A2. ПТО и декларация

Рабочее открытое дело, независимый checklist progress 85, без документов: действительный record_pto сохраняет ровно root `pto_act` с датой, пустыми details, actor и текущим Moscow DATE_ATOM recorded_at. Другие таблицы неизменны. Дата — реальная YYYY-MM-DD, не позже сегодня Europe/Moscow. HTTP invalid/future PTO — 422 «Укажите дату акта ПТО не позже сегодняшней.».

PTO не добавляет 15%; карточка остаётся «Документарное закрытие», 85%, следующее действие декларация. record_declaration требует PTO и checklist >=85, trim details 1–500 Unicode characters; сохраняет root с точными внутренними bytes, отдельными датой/actor/recorded_at. Неверная/будущая дата или пустые/501 details — 422 «Укажите дату и реквизиты декларации.». Декларация раньше ПТО допускается действующим контрактом и молча не запрещается миграцией. При двух фактах карточка/очередь показывают «Работы завершены» и 100%; underlying process_state остаётся working (projection, не новая state mutation).

Пример: literal item ids 1..41 с accepted pilot весами дают 85, исключение item32 вес1 даёт84. ПТО 2026-09-05, декларация 2026-09-06, details `  Д-001  ` → сохранено `Д-001`. Clock берётся live; audit ограничивается before/after Moscow instants.

## A3. Отклонения и повторы

Для корректной формы application причины: CASE_NOT_FOUND →404 «Объект не найден.»; CASE_NOT_WORKING →409 «Работы по объекту не открыты.»; CHECKLIST_INCOMPLETE →409 «Сначала завершите монтажные работы до 85%.»; PTO_REQUIRED →409 «Сначала зафиксируйте дату акта ПТО.»; FACT_ALREADY_RECORDED →409 «Документ уже зафиксирован.»; ACTOR_NOT_AUTHORIZED →403 «Действие недоступно для вашей роли.».

Payload validation до вызова owner сохраняет текущий HTTP порядок. В owner authorization/working/threshold предшествуют duplicate/PTO проверке. Exact и changed valid record replay возвращают 409 без дублей; invalid payload replay остаётся 422. Latest checklist retraction уменьшает progress и запрещает новую запись; duplicate operation не увеличивает progress. Все отказы сохраняют byte-equivalence completion/case/order/checklist/assignment/evidence facts. Тела domain ошибок text/plain UTF-8 с LF, no-store.

## A4. Исправления и история

Коррекция допускается только exact correct grant в working case, root должен принадлежать объекту и типу. Обязательна trim reason 1–1000 characters; исправленная дата действительна и <=today. Коррекция не требует текущие85 (сохраняется существующий owner contract). Неверные данные/причина —422; чужой/несуществующий root или неправильный тип —409 «Исправляемая запись не найдена.».

Каждая успешная коррекция добавляет ровно одну revision с root_fact_id, version_no 1,2,..., previous_correction_id/version, датой/details/reason, серверными actor/time. Корень и предыдущие corrections immutable. Пустые declarationDetails означают NULL/inherit последнего непустого corrected details, не очистку реквизитов. Trim выполняется на входе. Пример: root 2026-09-06/Д-001 → correction1 2026-09-04/Д-002 → correction2 2026-09-03/empty: effective дата03 и Д-002; root06/Д-001 неизменен. Повтор correction создаёт следующую revision по текущему контракту (operation id отсутствует); не выдавать за идемпотентность.

Карточка показывает effective данные и раскрываемую историю: root и каждую correction по порядку, причину, автора (имя с устойчивым user id), время, дату и effective реквизиты. Публичные строки истории идентифицируются `data-completion-history="pto_act|declaration"` и `data-completion-version="0|1|2|..."` (0 — root), позволяя проверить порядок и attribution каждой строки. HTML escaping для реквизитов/причин/имён. Формы записи/исправления отображаются только при соответствующем grant и предусловиях; reader видит историю без mutation форм.

## A5. Concurrency и сбои

Два реально одновременных record одного типа из разных server processes/session/DB connections: {303,409}, ровно root победителя. Две correct одного root: {303,303}, последовательные 1/2, chain без потерянной причины/автора. ПТО/declaration serial order хранится owner case lock; первый declaration без PTO даёт409, после принятого PTO повтор разрешён. Тест concurrency доказывает оба запроса находятся в полёте под внешним case lock до его освобождения.

Synthetic DB failure перед INSERT root/correction возвращает sanitized503/Retry-After, не сохраняет частичный факт и не переписывает историю. Отсутствие обязательной completion schema —503 без repair. Restart server сохраняет session/documents/history. Ошибка не утверждает отсутствие commit при неопределённом результате; клиент читает карточку перед повтором. Новую receipt/deduplication модель не вводить.

## A6. UI и соседние проекции

На карточке один уникальный completion anchor. До85 объяснение и ссылка к checklist; после85 форма ПТО, после ПТО форма декларации, после двух документов summary100. При всех стадиях доступны возврат к карточке/очереди и актуальная история. Реальный browser выполняет обе записи и correction с проверкой persisted facts, refresh и возвратом. Проверить desktop и mobile (390px): поля/submit доступны, нет горизонтального overflow, labels связаны с inputs, required/max/maxlength, отсутствие console/page errors. Использовать существующие shlz-ui и стили; без нового JS, если native формы достаточны.

Проверить согласованность status в `/pilot/objects`, карточке, checklist completion cap; raw checklist часть остаётся85, item42 по-прежнему409. Correction не меняет начисление15 и не трогает фото/offline operations. Стандартные login/logout, original/preopening и inspection regression продолжают проходить; grant-only reader видит документы без правообхода через POST.

## A7. Владение и интеграция

Единственный владелец completion DML/transactions/authorization остаётся InstallationProcess публичный record/correct. Yii controller адаптирует transport/error/UI, query читает effective facts/history. Runtime closure новых URL не загружает rapid-pilot/PilotHttp; old handler не копируется как новый доменный владелец. Используются явно configured DB/prefix, completion v10+details v17 на текущем canonical v24. Ни schema frontier, ни backup inventory, ни file storage не меняются. Tests используют уникальную приватную canonical DB, DML-only HTTP account, private session/runtime paths; teardown только своих ресурсов. Все новые tests включены в действующий verification inventory, обязательный plan не имеет unresolved coverage. Gate3/5 independent и один exact-source full CI обязательны перед объявлением поставки.
