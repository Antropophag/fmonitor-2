## Purpose

Определяет безопасную native-синхронизацию трёх независимых фактов оборудования из 1С ERP, их историю, provenance, freshness и отображение у однозначно сопоставленного объекта монтажа.

## ADDED Requirements

### Requirement: Подтверждённый контракт 1С ERP
Система SHALL читать номер заказа и три независимых значения: готовность из `BI_Sпроф_СрокиХраненияГотовойПродукцииID.ДатаКомплектности`, первую отгрузку как минимальную валидную `BI_DЭтапПроизводства2_2ID.ДатаОтгрузки` и полную отгрузку из `BI_Sпроф_СрокиХраненияГотовойПродукцииID.ДатаПолнойОтгрузки`. Она MUST NOT выводить один факт из другого.

#### Scenario: Полный и частичные source states
- **WHEN** источник возвращает все три даты, только готовность, готовность с первой отгрузкой либо полную отгрузку без первой
- **THEN** public integration seam передаёт ровно полученные независимые значения без синтетического заполнения

#### Scenario: Sentinel этапа отгрузки
- **WHEN** этап содержит legacy sentinel `0001-01-01` либо не содержит даты отгрузки
- **THEN** это значение не участвует в вычислении даты первой отгрузки

### Requirement: Точное и безопасное сопоставление
Система SHALL сопоставлять trim-нормализованный номер заказа 1С только с точным `fm_maintable.zavnumber`. Обновление разрешено только при ровно одном объекте-кандидате; операция доступна только системному integration actor.

#### Scenario: Однозначное сопоставление
- **WHEN** source order number соответствует ровно одному объекту
- **THEN** owner применяет факты к этому объекту

#### Scenario: Отсутствующее или неоднозначное сопоставление
- **WHEN** не найдено ни одного объекта либо найдено больше одного
- **THEN** ни один объект не изменяется, а результат диагностируется безопасным reason code и неприватным идентификатором записи без credentials или source payload

#### Scenario: Неавторизованный вызов
- **WHEN** owner вызывается не системным integration actor
- **THEN** весь вызов отклоняется без изменения projection, history или sync metadata

### Requirement: Атомарное применение authoritative records
Система SHALL валидировать полный полученный batch до записи и применять весь batch одной транзакцией. `NULL` конкретного поля в присутствующей записи SHALL означать explicit clear; отсутствующая из batch запись SHALL оставлять сохранённые значения без изменений. Любая persistence failure MUST откатывать все записи projection, history, diagnostics, run и metadata данного batch.

#### Scenario: Initial import
- **WHEN** успешно полученная запись впервые содержит три даты
- **THEN** current projection содержит все три даты и provenance запуска

#### Scenario: Correction одного факта
- **WHEN** присутствующая запись меняет одну из дат
- **THEN** меняется только этот current fact, а остальные даты сохраняются

#### Scenario: Explicit clear
- **WHEN** присутствующая успешно полученная запись содержит `NULL` для ранее установленной даты
- **THEN** current fact очищается и clear фиксируется в append-only history с provenance

#### Scenario: Отсутствующая запись
- **WHEN** ранее известный заказ отсутствует в успешно полученном batch
- **THEN** его сохранённые факты не изменяются и не очищаются

#### Scenario: Невалидный или неполный batch
- **WHEN** ответ невозможно полностью получить, разобрать или провалидировать либо обязательная форма записи нарушена
- **THEN** batch не применяется и сохранённые даты и last-success metadata остаются прежними

### Requirement: Идемпотентная projection и append-only history
Система SHALL хранить текущие значения отдельно от append-only истории изменений. История SHALL содержать object identity, вид факта, прежнее и новое значение, source system, HMAC source order number, run identity и observed time; raw source order number MUST NOT храниться в projection, history или diagnostics. Повтор того же authoritative state MUST NOT создавать новую историю.

#### Scenario: Повтор идентичного состояния
- **WHEN** один и тот же source state применяется повторно с новым либо прежним run identity
- **THEN** current projection остаётся эквивалентной и новые fact-change rows не создаются

#### Scenario: Correction сохраняет provenance
- **WHEN** источник исправляет существующую дату
- **THEN** прежняя история остаётся неизменной, добавляется одна запись correction и current projection указывает новый успешный run

#### Scenario: Конкурирующие запуски
- **WHEN** два запуска пытаются применить состояние одного заказа одновременно
- **THEN** сериализованный результат эквивалентен некоторому последовательному порядку и история не содержит дублей одного перехода

### Requirement: Run status, freshness и безопасная диагностика
Система SHALL фиксировать каждый запуск через тот же единственный application owner и SHALL продвигать last successful sync только после успешной полной обработки валидного batch. Повтор terminal run с тем же canonical input возвращает исходный receipt; иной input с тем же run identity конфликтует; retry после failed run использует новый run identity. Карточка и read seam SHALL предоставлять source status `never_synced|failed_before_success|fresh|failed_after_success|unavailable`, время последней успешной синхронизации и три текущих даты; ошибки и unmatched diagnostics MUST NOT раскрывать credentials или полный source payload.

#### Scenario: Успешная синхронизация
- **WHEN** валидный batch полностью обработан, включая безопасно пропущенные unmatched records
- **THEN** run завершён успешно и freshness у сопоставленных projections доступна для чтения

#### Scenario: Техническая ошибка после предыдущего успеха
- **WHEN** следующий fetch или apply завершается технической ошибкой
- **THEN** предыдущие даты и last successful sync сохраняются, а новый failed run доступен как source status

#### Scenario: Карточка объекта
- **WHEN** пользователь с существующим правом чтения открывает карточку сопоставленного объекта
- **THEN** карточка минимально показывает три отдельные даты, source system, freshness и текущий sync status без изменения остальной компоновки и процесса

### Requirement: Изоляция от процесса монтажного дела
Синхронизация SHALL изменять только equipment-fact projection, её историю, run metadata и diagnostics. Она MUST NOT изменять opening, progress, completion, checklist или assignment facts.

#### Scenario: Состояние процесса до и после sync
- **WHEN** применяются initial, partial, correction или clear source facts
- **THEN** process state, progress, assignments и checklist evidence до и после операции идентичны

### Requirement: Hourly native invocation
Существующий native scheduler SHALL один раз на каждый часовой slot ставить версионированную sync job в существующую durable queue; существующий worker SHALL вызывать тот же canonical integration/application seam. Cron и новый scheduler framework MUST NOT требоваться.

#### Scenario: Повтор scheduler tick в одном часу
- **WHEN** scheduler получает несколько tick в одном часовом slot
- **THEN** создаётся не более одной equipment-facts sync job

#### Scenario: Пропущенные часы
- **WHEN** scheduler возобновляется после паузы
- **THEN** он ставит job только для текущего slot и диагностирует число пропущенных slots без создания backlog

#### Scenario: Ошибка job
- **WHEN** adapter или owner сообщает техническую ошибку
- **THEN** worker классифицирует job как retryable, а повтор безопасен
