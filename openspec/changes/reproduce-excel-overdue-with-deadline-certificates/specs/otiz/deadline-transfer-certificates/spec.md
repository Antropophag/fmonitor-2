## Purpose

Задаёт проверяемую append-only историю обязательных PDF-справок, которыми ФКР подтверждает перенос планового срока объекта для последующих расчётов ОТиЗ.

## ADDED Requirements

### Requirement: ФКР публикует справку одной операцией
Система SHALL предоставлять одну application operation для первичной публикации справки. Активный пользователь с exact `deadline_certificate.write` MUST передать installation case, request identity, дату справки, новый срок и один passive PDF размером от 1 байта до 20 MiB. Default grant SHALL принадлежать только ролям `fkr_operator` и `manager`. Операция SHALL атомарно сохранить immutable revision, actor, recorded-at, PDF byte size/hash и opaque private reference; до принятия всех частей новый срок MUST NOT стать действующим.

#### Scenario: Принятая справка
- **WHEN** уполномоченный ФКР передаёт валидные даты и passive PDF
- **THEN** система возвращает принятую первую версию, а карточка и ОТиЗ видят дату справки, новый срок и проверяемую PDF-ссылку

#### Scenario: Неполное документное основание
- **WHEN** отсутствует PDF, дата справки или новый срок либо PDF небезопасен, пуст или превышает 20 MiB
- **THEN** операция отклоняется, новый срок не действует, revision и бесхозный постоянный файл не появляются

### Requirement: Исправление дополняет историю
Система SHALL исправлять ошибочную справку только новой immutable revision с обязательными новым PDF, обеими датами, причиной и expected current version. Предыдущие revisions и использовавшие их snapshots MUST остаться неизменными.

#### Scenario: Успешное исправление
- **WHEN** ФКР передаёт актуальную expected version и полный исправленный документ
- **THEN** создаётся следующая связанная revision, текущей становится она, а предыдущая остаётся читаемой и скачиваемой

#### Scenario: Конкурентное исправление
- **WHEN** два исправления используют одну expected version
- **THEN** принимается не более одного, второе получает stale-version conflict и не изменяет историю или файл текущей версии

### Requirement: Авторизация и идемпотентность проверяются сервером
Write operation SHALL допускать только активного пользователя с exact `deadline_certificate.write`. History/download SHALL требовать exact `deadline_certificate.read`, default для `fkr_operator|manager|otiz_specialist`. `otiz.manage` сам по себе MUST NOT разрешать запись, а роли `construction_control_engineer|system_admin` не получают certificate access неявно. Одинаковые request identity и fingerprint SHALL вернуть прежний результат; повтор identity с другим содержимым SHALL дать conflict. Authorization MUST предшествовать обработке файла и раскрытию case history.

#### Scenario: Повтор после потери ответа
- **WHEN** клиент повторяет byte-identical принятую команду с тем же request identity
- **THEN** система возвращает ту же revision и не создаёт второй файл, факт или audit outcome

#### Scenario: ОТиЗ пытается изменить справку
- **WHEN** пользователь имеет только право расчёта ОТиЗ
- **THEN** операция отказывает до сохранения или чтения переданных PDF bytes

### Requirement: Расчёт использует текущую известную справку
При публикации расчёта система SHALL выбирать текущую принятую non-void revision независимо от того, раньше или позже report date указана её документная дата. Если справки нет, calculation MUST использовать отдельно утверждённый original deadline source; пока его owner/timing имеет статус `NEEDS_GRILL`, отсутствие доказанного operand SHALL блокировать объект. `workdateendadjusted` MUST NOT молча подменять raw `plan_finish_date`. Валидные календарные даты обязательны; система MUST NOT выводить дату справки или новый срок из времени загрузки. Отношение между датой справки и новым сроком не ограничивается дополнительно. Void-команда в этот slice не входит.

#### Scenario: Дата справки позже отчётной даты
- **WHEN** текущая известная справка датирована после report date
- **THEN** новый расчёт всё равно использует её новый срок, как Excel использует заполненное `DPn` без gate по `DOn`

#### Scenario: Исправление после публикации
- **WHEN** новая revision стала текущей перед следующим запуском расчёта
- **THEN** новый snapshot ссылается на новую revision, а опубликованный прежний snapshot продолжает воспроизводиться из сохранённого operand

### Requirement: История и PDF доступны без изменения фактов
Карточка объекта SHALL показывать текущую справку, все revisions, actor/time/reason и download каждой сохранённой PDF-версии. Download MUST проверять read capability, возвращать только bytes выбранной revision и не раскрывать private filesystem path.

#### Scenario: Чтение истории
- **WHEN** уполномоченный пользователь открывает историю после исправления
- **THEN** он видит обе версии и может скачать каждый byte-identical PDF без изменения current revision
