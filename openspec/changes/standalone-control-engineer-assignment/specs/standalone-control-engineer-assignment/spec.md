## Purpose

Определяет самостоятельное append-only закрепление инженера строительного контроля за объектом и его безопасное использование как текущего операционного факта в карточке, подготовке распоряжения и зависимых native-проекциях.

## ADDED Requirements

### Requirement: Самостоятельное текущее закрепление
Система SHALL считать последнее валидное standalone-закрепление authoritative current control engineer assignment для одного объекта и его монтажного дела. Факт SHALL содержать object/case, нового инженера, actor, server timestamp и lineage к предыдущему факту; прежние факты SHALL оставаться неизменными.

#### Scenario: Первое назначение
- **WHEN** уполномоченный пользователь назначает активного допустимого инженера объекту без standalone-истории
- **THEN** система добавляет первый immutable assignment fact, показывает этого инженера текущим и сохраняет достаточную lineage с отсутствующим previous assignment

#### Scenario: Последовательные замены
- **WHEN** уполномоченный пользователь последовательно заменяет инженера A на B и B на C
- **THEN** current engineer равен C, а история позволяет восстановить A, B, C, actor и timestamp каждого перехода без UPDATE или DELETE прежних фактов

#### Scenario: Конкурентное изменение
- **WHEN** две разные команды исходят из одной ожидаемой current revision
- **THEN** не более одной команды создаёт следующий факт, а вторая получает conflict и не создаёт assignment/process facts

#### Scenario: Идемпотентный повтор
- **WHEN** авторизованный пользователь повторяет принятую команду с тем же request identity и теми же значениями
- **THEN** система возвращает подтверждение прежнего результата без второго assignment fact; повтор с тем же request identity и иными значениями получает conflict без записи

### Requirement: Авторизованное изменение из карточки
Карточка объекта SHALL предоставлять явное назначение или замену только активному пользователю с exact native permission этой команды. Сервер SHALL повторно проверить actor, permission, объект, ожидаемую revision и допустимость выбранного active construction-control engineer до записи.

#### Scenario: Разрешённая замена
- **WHEN** пользователь с exact permission отправляет валидную замену из карточки
- **THEN** сервер добавляет один assignment fact и возвращает пользователя в карточку, где показан новый current engineer

#### Scenario: Запрещённая замена
- **WHEN** активный пользователь без exact permission отправляет ту же замену
- **THEN** сервер отказывает с 403 и не создаёт assignment, audit или иной domain fact

#### Scenario: Недопустимый инженер
- **WHEN** выбранный пользователь отсутствует, неактивен или не является active construction-control engineer
- **THEN** команда отклоняется понятным безопасным результатом и не изменяет факты

#### Scenario: Чтение карточки
- **WHEN** пользователь открывает карточку GET или HEAD
- **THEN** текущий инженер и provenance отображаются без создания или изменения assignment/process facts

### Requirement: Bounded bootstrap из native application
Если standalone assignment fact отсутствует, система SHALL разрешить initial current assignment только из единственного последнего подтверждённого native application данного дела и SHALL пометить результат provenance `native_application_bootstrap`. После появления standalone fact application SHALL перестать участвовать в определении current assignment. Legacy и произвольный пользователь SHALL никогда не использоваться как fallback.

#### Scenario: Совместимость существующего объекта
- **WHEN** standalone history отсутствует и существует однозначный последний подтверждённый native application с инженером A
- **THEN** current assignment read возвращает A с явным bootstrap provenance и не создаёт standalone fact

#### Scenario: Standalone перекрывает application
- **WHEN** application хранит snapshot A, а standalone history завершается инженером B
- **THEN** current assignment read возвращает только B, при этом application и snapshot A остаются побайтно неизменными

#### Scenario: Текущий инженер не определён
- **WHEN** нет standalone fact и однозначного допустимого native application bootstrap
- **THEN** current assignment имеет состояние missing или ambiguous и не подставляет инженера из legacy, последнего распоряжения вне подтверждённого application или каталога пользователей

### Requirement: Подготовка распоряжения потребляет current assignment
Форма подготовки распоряжения SHALL показывать current engineer справочно и SHALL NOT содержать selector, radio или отдельное confirmation для инженера. GET/HEAD SHALL быть read-only. Команда сохранения состава SHALL получать инженера из server-side authoritative current assignment в момент решения, а не доверять переданному клиентом идентификатору.

#### Scenario: Подготовка с текущим инженером
- **WHEN** объект имеет current engineer B и пользователь открывает preparation
- **THEN** форма показывает B справочно, не содержит engineer selector/radio/confirmation и не создаёт assignment mutation

#### Scenario: Сохранение состава
- **WHEN** пользователь сохраняет допустимый состав монтажников при current engineer B
- **THEN** новый selected composition и последующее новое распоряжение фиксируют immutable snapshot B согласно существующему native contract

#### Scenario: Нет однозначного инженера
- **WHEN** current assignment missing или ambiguous
- **THEN** preparation показывает понятное блокирующее состояние, не разрешает сохранить состав и не выполняет fallback или mutation

#### Scenario: Подмена клиентского поля
- **WHEN** клиент добавляет устаревшее или поддельное поле engineer ID/confirmation
- **THEN** сервер не использует его как authority и не позволяет им заменить current assignment

### Requirement: Исторические документы и process facts неизменяемы
Назначение или замена current engineer SHALL NOT изменять существующие assignment orders, application rows, original revisions, selected snapshots или исторические inspection/checklist facts. Каждый новый документ SHALL хранить snapshot инженера, актуального для своей операции.

#### Scenario: Замена после существующего распоряжения
- **WHEN** существующий order/application хранит engineer A и current assignment заменяется на B
- **THEN** прежний order/application продолжает возвращать snapshot A, а карточка и следующая preparation возвращают current B

#### Scenario: Следующее распоряжение
- **WHEN** после замены на B формируется и применяется новое распоряжение
- **THEN** новое распоряжение/application хранит snapshot B, а прежние документы и facts остаются неизменными

### Requirement: Согласованные current-assignment consumers
Native read seams #40 и #38, которые действительно показывают или фильтруют current control engineer, SHALL использовать standalone current-assignment read с тем же bootstrap rule. Проекции исторического состава монтажников SHALL сохранять authoritative application semantics и SHALL NOT быть переопределены новым фактом инженера.

#### Scenario: Construction-control current engineer
- **WHEN** standalone assignment заменяет application engineer A на B
- **THEN** current-engineer значение в существующей construction-control/opening проекции равно B без изменения opening workflow и historical application A

#### Scenario: Installer directory regression
- **WHEN** current engineer меняется standalone-командой
- **THEN** authoritative installer assignments из последнего application и их история остаются прежними, а существующий #38 flow продолжает работать
