# Bitrix workforce integration: фактический контракт и границы истории

Дата исследования: 2026-08-28.

## Вопрос

Какие кадровые факты о монтажниках реально доступны FMonitor напрямую через действующую интеграцию Bitrix24, и позволяет ли этот контракт достоверно восстановить дату увольнения?

## Короткий вывод

Действующий контракт отдаёт **текущее состояние учётной записи**, но не доказанную кадровую дату увольнения:

- `ACTIVE` (`true`/`false`), где Bitrix называет `false` уволенным сотрудником;
- Bitrix user ID;
- ФИО и должность;
- подразделения;
- табельный номер, закодированный организационной договорённостью в email вида `tab<номер>@…`, с fallback на стандартное поле `UF_XING`.

В фактическом ответе портала нет заполненного поля даты увольнения. Поэтому FMonitor 2 может надёжно хранить:

- когда Bitrix впервые был замечен в состоянии `dismissed` (`observed_at`);
- какой снимок и от какого доставочного источника дал это состояние;
- последовательность последующих наблюдений.

Но FMonitor **не может** называть `observed_at` фактической датой увольнения. Поле `dismissal_effective_at` должно оставаться `null`, пока Bitrix не начнёт передавать отдельную дату, происходящую из 1С ЗУП.

Принятое продуктовое разграничение источников:

- `authority_system = 1c_zup` — кадровая система учёта;
- `delivery_system = bitrix24` — канал, из которого FMonitor читает данные напрямую;
- legacy FMonitor не является ни кадровым источником, ни промежуточным production adapter новой системы.

## Первичные свидетельства

### Существующая интеграция legacy FMonitor

Legacy-контроллер вызывает Bitrix24 REST через входящий webhook и метод `user.get`; результат забирается POST-запросом. URL webhook содержит credential прямо в исходнике, а TLS peer verification отключена: [`../fmonitor/application/controllers/Integration.php`, строки 1362–1380](../../fmonitor/application/controllers/Integration.php#L1362-L1380). Эти решения нельзя переносить в FMonitor 2.

Каталог ограничивается двумя подразделениями и намеренно не фильтрует только активных сотрудников: [`Integration.php`, строки 1395–1400](../../fmonitor/application/controllers/Integration.php#L1395-L1400). Статус вычисляется исключительно из `ACTIVE`; табельный номер извлекается из email, затем используется `UF_XING`; ФИО и локальное время запуска записываются в `fm_installators`: [`Integration.php`, строки 1402–1417](../../fmonitor/application/controllers/Integration.php#L1402-L1417).

Для первой страницы legacy-код при первом наблюдении `ACTIVE = false` добавляет запись локального status log без полученной от Bitrix даты увольнения: [`Integration.php`, строки 1419–1429](../../fmonitor/application/controllers/Integration.php#L1419-L1429). На последующих страницах он добавляет запись для каждого неактивного сотрудника без проверки существования и без явного `status`: [`Integration.php`, строки 1432–1470](../../fmonitor/application/controllers/Integration.php#L1432-L1470). Затем премиальный отчёт трактует локальный `date_at` этой записи как временную границу: [`Integration.php`, строки 2035–2049](../../fmonitor/application/controllers/Integration.php#L2035-L2049) и [`Integration.php`, строки 2150–2159](../../fmonitor/application/controllers/Integration.php#L2150-L2159). Это дата локального наблюдения/вставки, а не доказанная effective date из ЗУП.

Текущая пагинация опирается на `total` и шаг `50`, но выполняет лишнюю страницу при `ceil(total / 50)` и не обеспечивает единый атомарный reconciliation: [`Integration.php`, строки 1432–1471](../../fmonitor/application/controllers/Integration.php#L1432-L1471).

### Официальный контракт Bitrix24

Официальная документация `user.get` подтверждает:

- `ACTIVE=true` исключает уволенных из выборки;
- одна страница всегда содержит максимум 50 записей, `start` задаётся как `(N-1) * 50`;
- результат по умолчанию сортируется по Bitrix user ID;
- можно явно запросить только необходимые поля через `select`;
- стандартные доступные даты включают `UF_EMPLOYMENT_DATE`, но документация не перечисляет поле даты увольнения.

Источник: [Bitrix24 REST — `user.get`](https://apidocs.bitrix24.ru/api-reference/user/user-get.html).

Bitrix документирует `ACTIVE=N` как «сотрудник уволен», то есть это текущее состояние Bitrix-пользователя, а не отдельная дата кадрового события: [Bitrix24 REST — `user.update`](https://apidocs.bitrix24.ru/api-reference/user/user-update.html).

Набор полей зависит от scope. `user_basic` включает нужные для действующей договорённости `ID`, `ACTIVE`, ФИО, email, должность, `UF_DEPARTMENT`, `UF_XING` и `UF_EMPLOYMENT_DATE`; для произвольных пользовательских полей требуется отдельный `user.userfield` scope: [Bitrix24 REST — версии User Scope](https://apidocs.bitrix24.ru/api-reference/user/user-scope.html).

Официальный каталог REST-событий содержит `ONUSERADD`, но не содержит `ONUSERUPDATE` или `ONUSERDELETE`. Следовательно, по документированному API нельзя строить историю увольнений на user-update webhook; требуется периодический полный polling: [Bitrix24 REST — `events`](https://apidocs.bitrix24.ru/api-reference/events/events.html).

Входящий webhook — секрет в URL, исполняющий запросы в рамках прав создавшего его сотрудника и выбранных scopes. Bitrix прямо требует не публиковать URL/код и хранить секрет вне клиентского кода: [Bitrix24 REST — входящие и исходящие webhook](https://apidocs.bitrix24.ru/local-integrations/local-webhooks.html).

### Безопасный read-only probe действующего портала

28 августа 2026 года выполнены только read-only вызовы через уже настроенный legacy webhook. Credential, hostname, внутренние ID подразделений и персональные значения в эту записку не включены. TLS-проверка для probe была включена.

Получены агрегированные результаты полного `user.get` с точной пагинацией:

| Наблюдение | Результат |
|---|---:|
| `total` и реально собранные записи | 1260 / 1260 |
| Все записи действительно принадлежат хотя бы одному целевому подразделению | 1260 |
| `ACTIVE=true` / `ACTIVE=false` | 931 / 329 |
| Непустые Bitrix user ID и полные ФИО | 1260 / 1260 |
| Непустая должность | 1260 |
| Табельный номер из email / fallback `UF_XING` / отсутствует | 1259 / 1 / 0 |
| Дубли полученного табельного номера | 0 |
| Непустой `UF_EMPLOYMENT_DATE` | 0 |
| Непустой `TIMESTAMP_X`, в том числе у неактивных | 0 |

`user.fields` показал только стандартные кадрово-релевантные поля `ACTIVE`, `UF_EMPLOYMENT_DATE`, `TIMESTAMP_X`, ФИО, должность, email и `UF_XING`. На портале есть ещё два локальных пользовательских поля типа `string`, однако у всех 1260 отобранных работников оба пусты. Отдельного поля даты увольнения schema probe не обнаружил.

Это снимок текущего production-контракта, а не гарантия будущего API. Перед реализацией adapter тест должен фиксировать allow-list полей и fail closed при несовместимом изменении формы ответа.

## Что доказано, а что является выводом

### Доказано

- FMonitor может напрямую получить через Bitrix текущий `ACTIVE`, Bitrix ID, ФИО, должность, подразделения и действующий табельный номер.
- `ACTIVE=false` является текущим признаком увольнения в терминологии Bitrix.
- Фактическая дата увольнения и пригодная source-modified timestamp текущим ответом не передаются.
- Legacy status log создаёт собственную дату наблюдения и не получает effective date из Bitrix.
- Документированного события обновления/увольнения пользователя в REST event catalog нет.

### Выводы и ограничения

- По продуктовому решению кадровая истина возникает в 1С ЗУП, но исследованный Bitrix REST-ответ не содержит provenance отдельных полей. Поэтому `authority_system=1c_zup` является утверждённым контрактом организации, а не фактом, который можно проверить в каждом API payload.
- Табельный номер сейчас практически уникален и полон, но его извлечение из email — организационная договорённость, не гарантия Bitrix API. Новый adapter должен принимать его как отдельное нормализованное поле контракта доставки и сверять с Bitrix ID, а не считать email вечным идентификатором.
- `ACTIVE` может отражать момент деактивации Bitrix-аккаунта с задержкой относительно кадрового приказа. Без отдельного ZUP-origin effective date нельзя принимать `ACTIVE` или `TIMESTAMP_X` за юридическую дату увольнения.
- Исчезновение сотрудника из полного ответа нельзя автоматически трактовать как увольнение: оно может означать смену подразделения, прав webhook, фильтра или временно неполную выгрузку.

## Минимальный правдивый контракт `BITRIX-WORKFORCE-HISTORY-001`

### Входной снимок одного сотрудника

```text
delivery_person_id       non-empty Bitrix user ID
employee_number          non-empty normalized tab number
full_name                normalized LAST_NAME + NAME + SECOND_NAME
position                 non-empty WORK_POSITION
employment_status        employed | dismissed
dismissal_effective_at   nullable date; сейчас всегда null
authority_system         literal 1c_zup
delivery_system          literal bitrix24
source_modified_at       nullable datetime; сейчас null
observed_at              FMonitor server datetime после успешного чтения страницы
sync_run_id              identifier полного запуска
```

`employee_number` остаётся доменной идентичностью монтажника для совместимости с распоряжениями; `delivery_person_id` хранится отдельно для обнаружения переиспользования/изменения табельного номера и reconciliation. Пара `(delivery_system, delivery_person_id)` и актуальный `employee_number` должны быть уникальны в одной проекции. Конфликт — fail-closed ошибка всего запуска, а не выбор одной записи.

### Append-only история и текущая проекция

Минимально нужны:

1. append-only observation на первом появлении и при каждом материальном изменении `employee_number`, ФИО, должности, статуса или effective date;
2. отдельная current projection, обновляемая только после успешной загрузки, валидации и дедупликации **всех** страниц;
3. `first_observed_dismissed_at` как производное от первого сохранённого observation со статусом `dismissed`;
4. `dismissal_effective_at = null`, пока источник явно не передал дату из ЗУП;
5. различимые качества времени: `effective_from_source` и `observed_only`; значение `observed_only` нельзя использовать как доказанную effective date;
6. полный audit запуска: started/completed/failed, число страниц и записей, checksum нормализованного набора, но без ФИО и credential в событиях процесса.

Повтор того же нормализованного снимка не создаёт новый change-event, но завершённый sync run всё равно фиксируется для оценки свежести. Возврат `dismissed → employed` не переписывает историю: создаётся новое observation и требует видимого кадрового исключения/проверки, поскольку API не объясняет причину.

### Правило допустимости в пилоте

- Для нового распоряжения монтажник допустим только если текущая проекция `employed` и последний **полностью успешный** sync не старше утверждённого freshness threshold.
- Для открытия уже подготовленного распоряжения повторно проверяется текущая проекция, но historical installer snapshot распоряжения не изменяется.
- При `dismissed` с неизвестной effective date запрещаются новые назначения с момента наблюдения; система не должна задним числом объявлять прежний период недопустимым.
- Для запроса «когда уволился?» UI отвечает либо точной source effective date, либо честным текстом «увольнение впервые обнаружено <observed_at>; фактическая дата источником не передана».

### Polling и reconciliation

- Периодический full polling `user.get`, потому что документированного user-update event нет.
- Явный `select` allow-list; страницы по 50 до полного `total`/исчерпания результата; стабильная сортировка по `ID`; дедупликация по Bitrix ID и табельному номеру.
- Ни `TRUNCATE`, ни публикация частично загруженной проекции не допускаются.
- Ошибка transport/API/JSON/schema/страницы/duplicate сохраняет прежнюю current projection и помечает запуск failed.
- Отсутствующий в полном успешном снимке работник получает отдельное состояние reconciliation `missing_from_delivery`, а не `dismissed`; кадровая допустимость для новых назначений блокируется до разрешения.

### Авторизация и эксплуатация

- Новый read-only webhook/OAuth credential с минимальным scope (`user_basic`; `user.userfield` добавлять только если появится утверждённое отдельное ZUP-поле).
- Credential и base URL только в environment/secret store; никакого секрета в repo, URL, логах или HTML.
- TLS peer/hostname verification обязательна; timeout, bounded retries с jitter и redacted errors.
- Существующий webhook credential, обнаруженный в tracked legacy source, следует отозвать/ротировать отдельно от разработки FMonitor 2.

## Что изменить в контракте, если Bitrix начнёт передавать дату из ЗУП

Нужны не эвристика и не `TIMESTAMP_X`, а утверждённое поле с документацией владельца интеграции:

```text
field meaning: кадровая дата прекращения трудовых отношений
origin: 1c_zup
type/format/timezone: date (не transport timestamp)
null semantics: трудовые отношения не прекращены / дата неизвестна — различить явно
update cadence and delay SLO
correction semantics: дата может быть исправлена задним числом
stable identity paired with employee_number
```

После этого новое observation может заполнить `dismissal_effective_at` и пометить качество `effective_from_source`. Исправление даты создаёт ещё одно событие; прошлое не обновляется на месте.
