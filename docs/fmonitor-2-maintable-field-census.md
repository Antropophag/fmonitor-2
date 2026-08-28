# Census полей legacy `fm_maintable`

Дата исследования: 26 августа 2026 года.

## Вывод

> **Production-проверка от 26.08.2026 заменяет первоначальную статическую оценку полноты.** Через существующий read-only контур проверена БД `c1_fmonitor`: `fm_maintable` содержит **116 физических колонок**, `fm_fields` — **110 metadata-полей**, действуют восемь представлений. Полный production-реестр приведён ниже; ранний статический реестр сохранён как трассировка кода, но его прежняя граница в 34 поля больше не является границей известного.

`fm_maintable` — не предметная модель объекта, а динамически расширяемая широкая запись. Администратор создаёт запись в `fm_fields`, после чего приложение выполняет `ALTER TABLE fm_maintable ADD ...`; тип физической колонки определяется числовым UI-типом поля. Представления, права, сортировка, фильтры и формы также собираются из `fm_views`, `fm_view_fields` и `fm_fields` во время выполнения. Это видно в [`Fields.php:98`](../application/controllers/Fields.php#L98), [`Fields.php:133`](../application/controllers/Fields.php#L133), [`Tables.php:63`](../application/controllers/Tables.php#L63), [`Tables.php:447`](../application/controllers/Tables.php#L447), [`Tables.php:488`](../application/controllers/Tables.php#L488) и [`Tables.php:1027`](../application/controllers/Tables.php#L1027).

Из одного репозитория нельзя было достоверно восстановить все колонки, однако production metadata закрыли этот пробел. Все физические имена, типы и пользовательские названия теперь известны; неизвестными остаются бизнес-смысл и нормативный владелец отдельных полей, а не их существование.

Для FMonitor 2.0 из `fm_maintable` следует канонически принять лишь legacy-ID и устойчивые идентификаторы объекта монтажа/лифта. Состав, ответственные, сроки, статус, прогресс, документы и выплаты должны либо мигрировать в соответствующие процессные модели, либо остаться временной совместимой проекцией. Перенос всей широкой строки в новую карточку воспроизведёт прежнюю ошибку модели.

## Production-проверка и полнота

- Проверка выполнена read-only пользователем через существующий SOCKS-маршрут; бизнес-строки не выгружались, изменения в БД не выполнялись.
- MariaDB production: `10.3.39`; `fm_maintable`: 116 колонок; `fm_fields`: 110 строк; `fm_views`: восемь активных представлений.
- У всех 116 колонок `IS_NULLABLE=NO`; кроме `id` явные defaults отсутствуют. Это подтверждает риск пустых строк, нулей и zero-date как суррогатов отсутствующего значения.
- Только `id` является PK/auto_increment; `zavnumber` имеет неуникальный индекс. `regnumber` не имеет ограничения уникальности.
- Метаданные не описывают технические `id`, `ordernum`, поля аудита и автоматически созданные пары `_address`/`_fio`; формульные metadata-поля, напротив, могут не иметь одноимённой физической колонки.
- Значения бизнес-строк не исследовались. Профиль заполненности и качества требует отдельного согласованного запроса.

## Полный production-реестр по предметным группам

Следующий перечень покрывает все 116 физических колонок ровно один раз. Названия берутся из `fm_fields`; где metadata нет, указано техническое назначение.

| Группа | Физические поля `fm_maintable` | Решение для FMonitor 2.0 |
|---|---|---|
| Техническая идентичность и аудит | `id`, `ordernum`, `ctime`, `cuser_id`, `last_ctime`, `last_cuser_id` | `id` и исходный `ordernum` хранить как provenance; четыре audit-поля — детали миграции, не рабочие реквизиты |
| Идентификация и адрес | `regnumber`, `unom`, `zavnumber`, `area`, `district`, `ordadr`, `ordadr_address`, `entrance` | Канонические реквизиты объекта/локации; округ и район выводить из нормализованного адреса с сохранением исходного снимка |
| Организации и очередность | `subsuplier`, `generalcontractor`, `contractor`, `paired` | Ссылки на организации и отдельное понятие очередности; не свободные списки в строке объекта |
| Характеристики лифта и шахты | `floors`, `weight`, `pittype`, `pitmaterial`, `doorcabin_type`, `typepitdoor`, `speed`, `is_freqconverter`, `is_freqconverter_maindrive` | Версионируемый технический паспорт; `pitmaterial` также расчётный вход с зафиксированной версией |
| Первичный план и готовность оборудования | `workdatestart`, `workdatefinish`, `equiponobject`, `equipmentproduced`, `equipmentdeliverydate`, `measurements` | Плановые вехи и факты готовности с provenance; не смешивать с фактическим открытием работ |
| Открытие и сроки работ | `workstarted`, `factworkstartdate`, `workdatestartadjusted`, `workdateendadjusted`, `plan_finish_date`, `comments`, `stopact` | События открытия/остановки и версии сроков с причиной/документом. Legacy `workdatestart` и `factworkstartdate` требуют правила выбора при миграции |
| Передача объекта и площадки | `transferactsign`, `transferactdate`, `transferactdeliverdate`, `transferactstatus`, `acttransfertoulhdate`, `openingactuploaded`, `openingactverified`, `siteplanuploaded`, `siteplanverified`, `transfer_act_uploaded`, `transfer_act_verified`, `transferactverified`, `uploadtorskr` | Типизированные документы и события их загрузки, проверки, доставки и направления; дублирующие флаги сверить, не переносить как независимые checkbox |
| Контакты стройки | `topmanag`, `respperson`, `contact_phone`, `headofconstructarea`, `contact_phone_headofconstruct`, `workers`, `certnumber`, `contact_phone_itn` | Контактные роли/люди с периодом и источником; телефоны и удостоверение — точечно ограниченные данные |
| Назначения | `responsstroicontrol`, `countofinstallers`, `installator`, `installator_fio`, `assistantinstaller`, `assistantinstaller_fio`, `installerpersonnelnumber`, `installerpersonnelnumber_fio`, `installator2`, `installator2_fio`, `installator3`, `installator3_fio`, `installator4`, `installator4_fio` | Нормализованные назначения без лимита слотов; ФИО — миграционные snapshots, количество — derived |
| Кадровая готовность | `installeremploymentdate`, `assistantemploymentdate`, `is_employed`, `certupload`, `prikazupload` | Ссылки/срезы кадрового справочника и документы допуска; не редактировать в объекте |
| Ход работ и контроль | `control_flag`, `plan_percent_rskr`, `fact_percent`, `test_percent`, `plan_percent`, `work_progress_stages`, `po_comment`, `sm_comment`, `comment` | Прогресс 2.0 вычислять из инспекций; планы версионировать; комментарии превратить в авторские датированные записи, не три безымянных текстовых поля |
| Завершение, ПТО и ввод | `commission_date`, `pto_planned_date`, `ptoactdate`, `pto_status`, `non_conformance_act_date`, `four_side_act_signed_date`, `four_delivered_date`, `four_side_act_transfer_to_ulh_date`, `four_side_act_status`, `commissioning_act_date`, `commissioning_act_status`, `ks2_status` | Вехи процесса и типизированные документы со статусами/версиями |
| Декларации и документарная готовность | `scan_or_orig`, `declarations`, `declarations_upload_control`, `contractor_docs_transfer_date`, `commissioning_act_loading_control`, `acceptance_act_loading_control`, `afdn`, `psd_is_available`, `psd_on_hand`, `verified_ready_for_ulh_submission` | Реестр документов и проверок комплектности; `scan_or_orig` относится к конкретному документу; свободные тексты требуют разбора |
| Расчёт | `ktu` | Расчётный вход только в воспроизводимом срезе; доступ по отдельному финансовому праву |
| Legacy/system | `object_status`, `system_state` | Статусы 2.0 вычисляются из процесса; legacy-значения сохраняются только для сверки |
| Тестовые/непредметные | `testfieldformula` | Не переносить в карточку; сохранить в исходном snapshot до завершения миграционной сверки |
| План ИКЦ | `ikcplandate` | Сохранить как датированную веху; смысл, владелец и влияние на процесс требуют discovery |

Metadata дополнительно содержит семь формульных/виртуальных полей без одноимённой физической колонки: `checklist`, `installation_status`, `date_complete`, `date_push`, `photos`, `testfield`, `date_firstcargo`. Они являются представлениями или интеграционными данными, а не атрибутами сущности объекта.

### Реальные legacy-представления

| ID | Представление | Полей |
|---|---|---:|
| 1 | Главное представление | 36 |
| 2 | Задачи на монтаж | 7 |
| 3 | Презентация | 9 |
| 4 | Адресный перечень | 55 |
| 5 | Акты | 12 |
| 6 | Монтажники | 15 |
| 7 | Декларации | 11 |
| 8 | Объекты монтажа (моб.) | 8 |

Даже самое широкое представление показывает лишь 55 полей, а заметная часть поздних колонок вообще не включена ни в одно представление. Поэтому частота показа в legacy — полезный сигнал, но не доказательство необходимости поля в карточке 2.0.

## Как читать реестр

Уверенность: **высокая** — назначение прямо следует из записи/чтения; **средняя** — имя и использование доказаны, но справочник или точная бизнес-дефиниция недоступны; **низкая** — смысл только предположен по `sysname`.

Судьба:

- **canonical legacy identity input** — устойчивый входной идентификатор legacy-записи;
- **compatibility projection** — временная проекция для старого UI/отчётов, не источник истины;
- **migrate to process model** — перенести в нормализованную предметную модель;
- **derived** — вычислять из источников, не хранить как редактируемый факт карточки;
- **technical** — аудит/техническая идентичность;
- **unknown** — решение невозможно без метаданных или владельца данных.

## Физические типы, которые можно доказать

Для полей, созданных штатным редактором, код задаёт следующий контракт: типы `1,4,7,10,11,15` → `INT(11) NOT NULL`; `2,9` → `VARCHAR(255) NOT NULL`; `3` → `TEXT NOT NULL`; `5` → `DATETIME NOT NULL`; `6` → `VARCHAR(255) NOT NULL` плюс `<sysname>_address TEXT NOT NULL`; `14` → `INT(11) NOT NULL` плюс `<sysname>_fio VARCHAR(255) NOT NULL`; `8` → `FLOAT NOT NULL`: [`Fields.php:137`](../application/controllers/Fields.php#L137). Явный `DEFAULT` отсутствует, поэтому при старом нестрогом MySQL фактически возникают нули, пустые строки и zero-date. Для конкретного поля тип отмечен только там, где известен его `fm_fields.type`.

## Ранний статический реестр и сверка с production

Этот раздел объясняет, где поля используются в коде. Он не является полным перечнем после production-проверки. Типы и metadata, ранее помеченные как недоступные, разрешены полным реестром выше. Особое расхождение: `disciplin_correction` читается расчётным кодом, но физической колонки с таким именем в текущей production `fm_maintable` нет; его нельзя считать legacy-данными объекта без отдельного источника.

| Поле | DB / metadata | Человекочитаемый смысл | Чтение / запись | Качество и риски | Судьба в 2.0 |
|---|---|---|---|---|---|
| `id` | Точный DDL недоступен; целочисленный PK предполагается, уверенность высокая | Внутренний ID legacy-строки | Повсеместный lookup; новый ID берётся после insert: [`Tables.php:1028`](../application/controllers/Tables.php#L1028), [`Tables.php:1135`](../application/controllers/Tables.php#L1135) | Не является номером объекта монтажа, хотя checklist UI называет его «объект монтажа №»: [`show.php:12`](../application/views/checklists/show.php#L12) | **canonical legacy identity input** как `legacy_installation_object_id` |
| `regnumber` | Metadata неизвестны | Регистрационный номер лифта/объекта, высокая | Глобальный поиск и CSV lookup: [`Tables.php:265`](../application/controllers/Tables.php#L265), [`Integration.php:743`](../application/controllers/Integration.php#L743); отчёт ОТиЗ: [`Integration.php:2103`](../application/controllers/Integration.php#L2103) | В коде иногда назван «рег. номер объекта монтажа»; требуется уникальность и очистка дублей | **canonical legacy identity input** |
| `zavnumber` | Metadata неизвестны | Заводской номер лифта, высокая | Поиск, шапка чек-листа и join с ERP: [`Tables.php:265`](../application/controllers/Tables.php#L265), [`Checklists.php:56`](../application/controllers/Checklists.php#L56), [`Tables.php:334`](../application/controllers/Tables.php#L334) | Join по строке без показанной FK; смешение идентификатора с `id` и `regnumber` | **canonical legacy identity input** |
| `orderlink` | Metadata неизвестны, вероятно строка | Ссылка на объект монтажа во внешней системе, высокая | Делает `zavnumber` ссылкой: [`showcell.php:95`](../application/views/tables/helper/showcell.php#L95) | URL хранится без показанной валидации; может устаревать | **compatibility projection**, затем внешняя ссылка/интеграционный атрибут |
| `ordadr` | Для UI-типа 6: `VARCHAR(255) NOT NULL`; metadata name неизвестно | FIAS ID адреса объекта, высокая | Импорт ищет/получает FIAS ID: [`Integration.php:1184`](../application/controllers/Integration.php#L1184); общий address editor пишет пару: [`Tables.php:859`](../application/controllers/Tables.php#L859) | Идентификатор и снимок адреса могут расходиться | **canonical legacy identity input** с последующей нормализацией адреса |
| `ordadr_address` | Для пары типа 6: `TEXT NOT NULL` | Отображаемый адрес объекта, высокая | Поиск и шапки чек-листа: [`Tables.php:265`](../application/controllers/Tables.php#L265), [`Checklists.php:57`](../application/controllers/Checklists.php#L57) | Денормализованный mutable snapshot, возможны дубли/варианты написания | **canonical legacy identity input** как исходный снимок; далее нормализованная сущность Location |
| `entrance` | Metadata неизвестны | Подъезд/секция установки, средняя | Передаётся в checklist: [`Checklists.php:58`](../application/controllers/Checklists.php#L58) | Семантика и формат не проверяются | **canonical legacy identity input** после уточнения термина |
| `pitmaterial` | Metadata неизвестны; фактически код значения | Тип/материал шахты для коэффициента, высокая | Switch расчёта Кшах: [`Integration.php:1612`](../application/controllers/Integration.php#L1612) | Magic IDs, словарь недоступен; финансово значимое поле | **migrate to process model** как версионируемый классификатор/расчётный вход |
| `workdatestart` | По поведению `DATETIME`; metadata неизвестны | Фактическая дата начала монтажа, высокая | Фильтр и периоды премии: [`Integration.php:1488`](../application/controllers/Integration.php#L1488), [`Integration.php:1579`](../application/controllers/Integration.php#L1579) | Mutable итоговое поле без отдельного события открытия | **migrate to process model** (`opened_at`/actual start event); legacy — projection |
| `workdatefinish` | По поведению `DATETIME`; metadata неизвестны | Фактическая дата окончания, высокая | Фильтр, период, просрочка: [`Integration.php:1493`](../application/controllers/Integration.php#L1493), [`Integration.php:1631`](../application/controllers/Integration.php#L1631) | Может быть zero-date; окончание смешано с вычислением премии | **migrate to process model** как событие завершения |
| `plan_finish_date` | По поведению дата; metadata неизвестны | Плановая дата окончания монтажа, высокая | База расчёта срока: [`Integration.php:1625`](../application/controllers/Integration.php#L1625) | Не видно основания/версии плана | **migrate to process model** как датированный план/обязательство |
| `workdateendadjusted` | По поведению дата; metadata неизвестны | Скорректированная дата окончания, высокая | Подменяет плановую дату, если отличается: [`Integration.php:1627`](../application/controllers/Integration.php#L1627) | Нет причины, документа, автора и периода действия; сравнение строк | **migrate to process model** как изменение срока с основанием |
| `object_status` | Вероятно type 4 (`INT NOT NULL`), metadata name неизвестно | Legacy-статус объекта, высокая | Фильтр «завершён» по magic ID `259`: [`Integration.php:1498`](../application/controllers/Integration.php#L1498); имя через `fm_fields_values`: [`Integration.php:1639`](../application/controllers/Integration.php#L1639) | Один редактируемый итоговый статус; magic ID и недоступный словарь | **compatibility projection**, в 2.0 статус **derived** из процесса |
| `responsstroicontrol` | Вероятно type 10 (`INT NOT NULL` → `users.id`), metadata неизвестны | Ответственный инженер строительного контроля, высокая | Имя пользователя в отчёте: [`Integration.php:1635`](../application/controllers/Integration.php#L1635) | Текущий mutable ответственный без интервала/истории назначения | **migrate to process model** как assignment с периодом; legacy — projection |
| `installator` | Field ID 49, type 14 → `INT(11) NOT NULL` | Табельный ID первого монтажника, высокая | Импорт/редактирование/чек-лист: [`Integration.php:892`](../application/controllers/Integration.php#L892), [`Tables.php:894`](../application/controllers/Tables.php#L894), [`Checklists.php:77`](../application/controllers/Checklists.php#L77) | Первый из четырёх слотов; `999999` означает «не закреплён»; замена каскадно переписывает исполнителей | **compatibility projection** из нормализованных назначений |
| `installator_fio` | Pair type 14 → `VARCHAR(255) NOT NULL` | Денормализованное ФИО/табномер первого монтажника | Пишется вместе с ID и читается в премии: [`Integration.php:85`](../application/controllers/Integration.php#L85), [`Integration.php:1591`](../application/controllers/Integration.php#L1591) | Может расходиться с ID; формат чистился отдельным скриптом: [`Tables.php:1158`](../application/controllers/Tables.php#L1158) | **compatibility projection** |
| `installator2` | Field ID 105, type 14 | Второй монтажник | Те же потоки: [`Integration.php:911`](../application/controllers/Integration.php#L911), [`Checklists.php:81`](../application/controllers/Checklists.php#L81) | Поле добавлено позже основного импорта (ID > 104); слот ограничивает состав | **compatibility projection** |
| `installator2_fio` | Pair type 14 | Снимок ФИО второго монтажника | [`Integration.php:94`](../application/controllers/Integration.php#L94), [`Integration.php:1588`](../application/controllers/Integration.php#L1588) | Денормализация | **compatibility projection** |
| `installator3` | Type 14 доказан запросом metadata; ID неизвестен | Третий монтажник | [`Checklists.php:81`](../application/controllers/Checklists.php#L81), [`Tables.php:895`](../application/controllers/Tables.php#L895) | Слот; точная metadata недоступна | **compatibility projection** |
| `installator3_fio` | Pair type 14 | Снимок ФИО третьего монтажника | [`Integration.php:100`](../application/controllers/Integration.php#L100), [`Tables.php:1181`](../application/controllers/Tables.php#L1181) | Денормализация | **compatibility projection** |
| `installator4` | Type 14 доказан запросом metadata; ID неизвестен | Четвёртый монтажник | [`Checklists.php:81`](../application/controllers/Checklists.php#L81), [`Tables.php:895`](../application/controllers/Tables.php#L895) | Слот; некоторые legacy-пути учитывают только первые три, например URL backfill в [`Integration.php:931`](../application/controllers/Integration.php#L931) | **compatibility projection** |
| `installator4_fio` | Pair type 14 | Снимок ФИО четвёртого монтажника | [`Integration.php:106`](../application/controllers/Integration.php#L106), [`Tables.php:1188`](../application/controllers/Tables.php#L1188) | Денормализация | **compatibility projection** |
| `disciplin_correction` | По использованию число; metadata неизвестны | Дисциплинарная денежная поправка, средняя | Вычитается из премии: [`Integration.php:2116`](../application/controllers/Integration.php#L2116) | Правило не найдено в первичных формулах; nullable/пустая строка обрабатываются вручную | **unknown** до нормативного решения; не мигрировать как правило автоматически |
| `ikcplandate` | Field ID 108, type 5 → `DATETIME NOT NULL` | Плановая дата ИКЦ (расшифровка требует владельца), средняя | Заполнение из CSV только если пусто: [`Integration.php:722`](../application/controllers/Integration.php#L722), [`Integration.php:749`](../application/controllers/Integration.php#L749), changelog [`Integration.php:828`](../application/controllers/Integration.php#L828) | Назначение аббревиатуры не раскрыто; zero-date | **unknown**, сохранить в legacy snapshot до discovery |
| `ptoactdate` | Field ID 84, type 5 | Дата акта ПТО, высокая | CSV import/changelog: [`Integration.php:755`](../application/controllers/Integration.php#L755), [`Integration.php:836`](../application/controllers/Integration.php#L836) | Хранится только дата, без сущности/версии документа | **migrate to process model** как документ/событие документа |
| `scan_or_orig` | Field ID 87, type 4 → list ID | Признак «скан или оригинал», высокая | Словарь создаётся на лету при импорте: [`Integration.php:761`](../application/controllers/Integration.php#L761) | Неконтролируемое расширение словаря, значение относится к неявному документу | **migrate to process model** как атрибут конкретного документа |
| `contractor` | Field ID 85, type 4 → list ID | Подрядчик, высокая | Lookup/создание словаря: [`Integration.php:778`](../application/controllers/Integration.php#L778) | Подрядчик смоделирован как свободно расширяемый список, хотя есть таблица `clients` | **migrate to process model** как ссылка на организацию |
| `non_conformance_act_date` | Field ID 86, type 5 | Дата акта несоответствия, высокая | [`Integration.php:795`](../application/controllers/Integration.php#L795), changelog [`Integration.php:860`](../application/controllers/Integration.php#L860) | Документ сведён к одной дате | **migrate to process model** как документ/событие |
| `declarations` | Field ID 88, type 3 → `TEXT NOT NULL` | Декларации/реквизиты деклараций, средняя | Импорт: [`Integration.php:801`](../application/controllers/Integration.php#L801) | В условии ошибочно проверяется `cells[9]`, а пишется `cells[10]`; changelog ошибочно называет поле `contractor_docs_transfer_date`: [`Integration.php:876`](../application/controllers/Integration.php#L876) | **migrate to process model** как коллекция документов; данные требуют аудита |
| `contractor_docs_transfer_date` | Field ID 89, type 5 | Дата передачи документов подрядчику, высокая | [`Integration.php:807`](../application/controllers/Integration.php#L807), [`Integration.php:868`](../application/controllers/Integration.php#L868) | Не видно состава переданного пакета и получателя | **migrate to process model** как событие передачи пакета документов |
| `ctime` | Общий CRUD пишет `DATETIME`; DDL недоступен | Время создания legacy-строки, высокая | [`Tables.php:1135`](../application/controllers/Tables.php#L1135) | Не бизнес-дата объекта | **technical**, сохранить для трассировки |
| `cuser_id` | Общий CRUD пишет user ID; DDL недоступен | Создатель legacy-строки, высокая | [`Tables.php:1135`](../application/controllers/Tables.php#L1135) | FK/nullable неизвестны | **technical**, сохранить для аудита |
| `last_ctime` | Общий CRUD пишет `DATETIME`; DDL недоступен | Время последнего изменения строки, высокая | [`Tables.php:930`](../application/controllers/Tables.php#L930) | Любое поле перетирает единый timestamp; не даёт предметной истории | **technical compatibility projection**; в 2.0 события имеют собственное время |
| `last_cuser_id` | Общий CRUD пишет user ID; DDL недоступен | Последний редактор строки, высокая | [`Tables.php:930`](../application/controllers/Tables.php#L930) | То же ограничение агрегированного аудита | **technical compatibility projection** |

## Не физические поля `fm_maintable`, которые легко принять за них

| Имя | Реальный источник | Судьба |
|---|---|---|
| `checklist_percent` | Коррелированный подсчёт отмеченных пунктов в запросах списка: [`Tables.php:321`](../application/controllers/Tables.php#L321) | **derived** из подтверждённых инспекций/пунктов |
| `kolphotos` | `COUNT(fm_install_checklist_files)`: [`Tables.php:322`](../application/controllers/Tables.php#L322) | **derived** |
| `date_complete`, `date_push`, `date_cargo_push` | ERP/order join, затем специальные формулы отображения: [`Tables.php:332`](../application/controllers/Tables.php#L332), [`showcell.php:68`](../application/views/tables/helper/showcell.php#L68) | Внешняя read model, не переносить в сущность объекта без provenance |

## Динамические обращения и закрытый «слепой сектор»

Стандартный CRUD не перечисляет большинство колонок:

1. Для списка берётся `fm_maintable.*`, а набор видимых полей — из `fm_view_fields JOIN fm_fields`: [`Tables.php:321`](../application/controllers/Tables.php#L321), [`Tables.php:447`](../application/controllers/Tables.php#L447).
2. Фильтр и сортировка подставляют `fm_fields.sysname` в SQL: [`Tables.php:488`](../application/controllers/Tables.php#L488), [`Tables.php:533`](../application/controllers/Tables.php#L533).
3. Inline-edit получает `fieldname` от клиента, разрешает его через `fm_fields`, проверяет `fm_view_fields.rulewrite`, затем динамически обновляет колонку: [`Tables.php:881`](../application/controllers/Tables.php#L881).
4. Создание строки перебирает POST, разрешает каждый ключ через `fm_fields` и строит динамический `INSERT`: [`Tables.php:1051`](../application/controllers/Tables.php#L1051).
5. Адрес создаёт дополнительную колонку `<sysname>_address`, монтажник — `<sysname>_fio`: [`Fields.php:151`](../application/controllers/Fields.php#L151).
6. Формульные поля типа 12 могут читать другие колонки по ID из `formula`; отдельные special cases используют ERP-даты и checklist percent: [`show.php:190`](../application/views/tables/show.php#L190), [`show.php:213`](../application/views/tables/show.php#L213).

До production-проверки CSV-map был единственным доказательством metadata-полей с ID:

`1–21`, `22`, `25–28`, `31–39`, `41`, `43`, `49–56`, `58–61`, `65–78`, `83–104` (82 позиции; точный порядок — [`Integration.php:1051`](../application/controllers/Integration.php#L1051)). Production `fm_fields` теперь разрешает весь диапазон 1–110. Пропуски CSV — особенность старого импорта, а не отсутствие полей.

## Выполненный read-only экспорт

26.08.2026 из production выполнены эквиваленты следующих запросов без значений бизнес-строк:

```sql
SHOW CREATE TABLE fm_maintable;

SELECT id, name, sysname, description, type, formula
FROM fm_fields
ORDER BY id;

SELECT id, name, description, status
FROM fm_views
ORDER BY id;

SELECT vf.id, vf.views_id, vf.fields_id, vf.rang, vf.showname,
       vf.status, vf.ruleadd, vf.ruleread, vf.rulewrite, vf.rulewriteonetime
FROM fm_view_fields vf
ORDER BY vf.views_id, vf.rang, vf.id;

SELECT field_id, COUNT(*) AS value_count
FROM fm_fields_values
GROUP BY field_id
ORDER BY field_id;
```

Для оценки качества по-прежнему нужен отдельный, явно согласованный обезличенный профиль значений: доля пустых/нулевых/zero-date, distinct count, дубли `regnumber`/`zavnumber`, несогласованные пары `ordadr`/`ordadr_address` и `installator*`/`*_fio`. Сам census данных этого не утверждает.

## Миграционная классификация по группам

| Группа | Поля | Решение |
|---|---|---|
| Legacy identity | `id`, `regnumber`, `zavnumber`, `ordadr`, `ordadr_address`, после уточнения `entrance` | Импортировать идемпотентно, хранить provenance и исходный snapshot |
| Внешние ссылки | `orderlink` и ERP-derived даты | Отдельная интеграционная read model |
| Монтажный процесс | `workdatestart`, `workdatefinish`, `plan_finish_date`, `workdateendadjusted`, `object_status` | События, планы и derived state; назад — временная проекция |
| Назначения | `responsstroicontrol`, четыре `installator*` и четыре `*_fio` | Нормализованные назначения с интервалами и снимками; назад — проекция |
| Документы | `ptoactdate`, `scan_or_orig`, `contractor`, `non_conformance_act_date`, `declarations`, `contractor_docs_transfer_date` | Коллекция типизированных документов и событий, а не вкладка из одиночных колонок |
| Расчёт | `pitmaterial`, `disciplin_correction` | Версионируемые расчётные входы; дисциплинарную поправку не принимать без нормативного основания |
| Технический аудит | `ctime`, `cuser_id`, `last_ctime`, `last_cuser_id` | Сохранить для трассировки миграции, не показывать как бизнес-состояние |
| Metadata-only | Неразрешённые ID старого импорта и возможные более новые поля | Не проектировать и не удалять до выгрузки metadata |

## Проверки, которые выявили конкретные риски

- «Незакреплённый монтажник» хранится фиктивным табельным ID `999999`, а не отсутствием назначения: [`Integration.php:74`](../application/controllers/Integration.php#L74).
- Изменение текущего `installator*` запускает каскадную замену исполнителя в checklist, то есть текущий состав способен переписать историческое авторство: [`Tables.php:894`](../application/controllers/Tables.php#L894), [`Tables.php:976`](../application/controllers/Tables.php#L976).
- Импорт справочных значений сам создаёт новые элементы списков подрядчика и формы документа, что делает справочники неуправляемыми: [`Integration.php:765`](../application/controllers/Integration.php#L765), [`Integration.php:782`](../application/controllers/Integration.php#L782).
- `declarations` имеет две явные ошибки импорта/аудита, поэтому его значения нельзя мигрировать без сверки: [`Integration.php:801`](../application/controllers/Integration.php#L801), [`Integration.php:876`](../application/controllers/Integration.php#L876).
- Legacy-премия читает текущие состав и итоговые поля объекта, а не воспроизводимый срез: [`Integration.php:1513`](../application/controllers/Integration.php#L1513), [`Integration.php:1578`](../application/controllers/Integration.php#L1578).

Этот документ является census доступных источников, а не окончательной схемой карточки FMonitor 2.0. Полная карточка может проектироваться только после сопоставления metadata-only полей с предметными группами и подтверждения владельцами данных.
