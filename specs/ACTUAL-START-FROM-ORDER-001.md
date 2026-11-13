# ACTUAL-START-FROM-ORDER-001

## Простыми словами

После загрузки подписанного распоряжения работы открываются сразу. Фактическое начало — подтверждённая пользователем «Дата начала работ» из приложения к этому распоряжению, даже если она находится в будущем. Дата самого документа, текущий план и время действия стройконтроля её не заменяют. Очистка старой базы выполняется отдельно; этот change данные не удаляет.

## Решения и границы

Владелец подтвердил все три правила в беседе 13 ноября 2026 и выбрал чистую последующую загрузку объектов без backfill. Production-first режим откладывает обычную последовательность gates/CI, но сохраняет focused проверки и независимый final review. Root — автор контракта/тестов; отдельный executor — код. Никакого OCR, планировщика будущего открытия, нового расчёта премии или массовой правки существующих дат.

Публичный application seam: `ProductionOriginalStartFactory::create(db,prefix,originalApplication,clock)->submit(originalCommand, objectId, appendixStartDate)`. Yii upload adapter вызывает только этот owner; SQL и последовательность принятия/открытия не живут в controller. Низкоуровневый original owner сохраняет прежний протокол bytes/DB/recovery и используется внутри composite owner.

## Acceptance

### A1 — дата документа и дата начала независимы

Upload требует отдельную валидную календарную ISO-date `appendixStartDate`, явно подтверждаемую по подписанному приложению. Она сохраняется immutable и однозначно связывается с принятой original revision. Значение может быть в прошлом, сегодня или будущем, в том числе раньше documentDate; documentDate продолжает проходить прежние проверки. Нет fallback на documentDate, plannedStartDate, upload time или время открытия. Изменение плана после загрузки не меняет факт.

Пример: сегодня05.09.2026, documentDate04.09.2026, plan01.09.2026, appendixStartDate20.09.2026. После upload дело уже working, actual_start_date=20.09.2026; opened_at — системное05.09.2026. Чек-лист доступен сразу. Второй пример: appendixStartDate01.09.2026 при документе04.09.2026 сохраняет01.09 без подмены.

### A2 — немедленное открытие

Первый принятый оригинал нового дела в том же пользовательском сценарии применяет актуальный состав и открывает работы. Сохраняются case actual_start_date, uploader как actor открытия, реальное opened_at, один opening event и один template association. Не нужны отдельное действие инженера, поле даты на чек-листе или scheduler. Существующие проверки состава, оригинала, инженера, employment, completed/PTO, current revision и прав продолжают действовать. Финансовые выплаты/принятые расчёты upload не создаёт и не пересчитывает.

### A3 — авторизация и отказы

До чтения/сохранения PDF нужны обе exact capabilities: original.upload/original.correct и installation.open. Перед записью opening полномочия проверяются повторно. Невалидная дата, запрещённый actor, stale object/order/revision и неприемлемый PDF не открывают работы; сохраняется существующий safe audit, не появляется принятый PDF от заведомо запрещённого вызова. Старый HTTP execution open/open_confirmed не позволяет задавать другую actualStartDate и обходить новый owner.

### A4 — durable replay и конкуренция

До низкоуровневого original submit фиксируется immutable submission intent: requestId, exact object/case/order/actor/mode, document metadata, appendixStartDate, PDF digest/size и полный semantic fingerprint. Незавершённый intent не является принятым распоряжением. Изменённая дата/PDF/actor/target с тем же requestId даёт conflict, не повторное принятие старого результата. Exact retry возобновляет операцию после intent или принятого original и не дублирует composition application, opening, checklist association или facts. Low-level cross-request replay проверяется по каноническому request исходной returned revision: одинаковый PDF с другой appendix date не вправе заимствовать старую revision и новую дату. Concurrent submissions оставляют одного победителя; чужая revision/selection не открывается.

### A5 — честный частичный результат

Принятый PDF не удаляется, если следующий opening commit не состоялся. Composite возвращает `pending` с retryable=true, оригинальным receipt и явной причиной; UI не сообщает, что работы открыты, и сохраняет файл/данные/requestId для повтора. После устранения причины exact retry завершает opening один раз. Если результат commit неизвестен, не объявлять rollback/успех без durable readback. File persistence и opening не объявляются одной ACID-транзакцией.

### A6 — correction и следующие распоряжения

После первого открытия actual_start_date/opened_at/opened_by и первоначальный opening event неизменяемы. Correction того же распоряжения с той же appendix date принимает новый PDF и актуализирует application без второго opening. Попытка изменить дату уже принятого распоряжения после открытия отклоняется до принятия нового PDF; отдельное историческое исправление даты не входит в этот change. Для последующего нового распоряжения допустима другая appendix date: состав меняется по прежним правилам применения, initial actual start не меняется. Для дела, немедленно открытого с будущей подтверждённой датой, новый документ до этой даты не блокируется сравнением с будущим actual_start_date; проверка documentDate не раньше предыдущего распоряжения сохраняется. Correction последующего распоряжения сравнивает дату с предыдущей revision именно этого распоряжения, а не с началом всего дела.

### A7 — интерфейс и HTTP

Форма original различает «Дата распоряжения» и «Дата начала работ по распоряжению». Для initial можно предложить текущую плановую дату с явным пояснением проверки по подписанному документу; пользователь может её исправить. Correction показывает сохранённую дату предыдущей revision. JSON metadata включает appendixStartDate и сохраняет строгую проверку shape/CSRF/PDF limits. Success redirect только при полном accepted/replayed; partial имеет отдельное сообщение. На карточке/чек-листе нет ручного ввода actualStartDate; при незавершённом upload-opening есть понятный возврат к оригиналу/повтору. History оригиналов показывает подтверждённую дату. Старые PDF bytes и даты документа сохраняются.

### A8 — migration/readiness/recovery

Additive schema39 хранит отдельные immutable intents без переписывания original schema38, existing rows или dates. Миграция идемпотентна, проверяет prefix/catalog shape до изменений и отвергает несовместимую одноимённую таблицу. Нет DDL из HTTP/worker. Current canonical frontier, readiness и backup/restore учитывают новую таблицу; старые V38 definitions неизменяемы. Исходный production не очищается. До поставки — private backup, exact changed-file preflight и bounded migration39 без повторения известного конфликтующего legacy full migration.

### A9 — потребители и завершение

Card/queue читают новый actual_start_date без fallback к плану/document date, в том числе будущий. Checklist association действует в момент открытия. Existing premium eligibility по actual_start_date остаётся прежней: будущая дата не превращается в прошедшую, accepted financial history не переписывается. Focused acceptance включает real domain + HTTP/browser, malformed/denied/replay/partial/correction, migration/recovery и adjacent opening checks. Только после независимого final review допускается scoped production delivery и read-only проверка; deferred formal gates/CI не означают GREEN.
