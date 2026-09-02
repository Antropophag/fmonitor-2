## Purpose

Зафиксировать воспроизводимый `PILOT_ONLY` oracle первой записи даты Акта ПТО через реальный rapid-pilot HTTP seam, не превращая наблюдаемые shortcuts в целевые продуктовые требования.

## ADDED Requirements

### Requirement: Успешная первая запись Акта ПТО характеризуется через HTTP
Characterization SHALL отправлять аутентифицированный `POST /pilot/objects/{id}/completion` с действительным form CSRF, `action=record_pto` и валидной датой не позже текущей даты `Europe/Moscow` для однозначного монтажного дела, в котором сумма весов различных принятых `item_completed` достигла 85. Она SHALL наблюдать `303`, redirect на `/pilot/objects/{id}#completion`, `Cache-Control: no-store` и ровно один сохранённый факт `pto_act` с переданной датой, пустыми реквизитами, actor id и живым Moscow timestamp.

#### Scenario: Первый PTO fact сохраняется
- **WHEN** активный pilot-пользователь с действительным CSRF отправляет `record_pto` для уникального дела с progress 85 и сегодняшней датой Moscow
- **THEN** ответ имеет status `303`, указанные `Location` и `Cache-Control`, а БД содержит ровно один `pto_act` с submitted date, пустыми `details`, этим actor id и parseable `recorded_at` внутри снятых до/после запроса Moscow clock bounds

### Requirement: Порог и дата характеризуются без утверждения target semantics
Characterization SHALL фиксировать pilot-отказы `409` при progress ниже 85 и `422` при несуществующей, неверно отформатированной или будущей дате. Эти сценарии MUST быть помечены `PILOT_ONLY` и MUST NOT утверждать 85/15 либо требования к документам как целевой контракт.

#### Scenario: Progress ниже pilot-порога
- **WHEN** действительный `record_pto` отправлен для уникального дела, у которого сумма различных завершённых пунктов равна 84
- **THEN** ответ имеет status `409` и текст `Сначала завершите монтажные работы до 85%.`, а completion fact не появляется

#### Scenario: Будущая дата
- **WHEN** действительный `record_pto` при progress 85 содержит календарную дату, следующую за текущей датой `Europe/Moscow`
- **THEN** ответ имеет status `422` и текст `Укажите дату акта ПТО не позже сегодняшней.`, а completion fact не появляется

#### Scenario: Невалидная календарная дата
- **WHEN** действительный `record_pto` при progress 85 содержит `2026-02-30`
- **THEN** ответ имеет status `422` с тем же текстом, а completion fact не появляется

### Requirement: Фактическая pilot-авторизация и admission характеризуются явно
Characterization SHALL доказывать, что отсутствие/невалидность form CSRF отклоняется с `403`, а ранее аутентифицированная, затем деактивированная session перенаправляется local-auth на login до completion handler. Она также SHALL доказывать текущий риск: любой активный pilot-пользователь, включая не закреплённого за объектом, может записать PTO в дело с достаточным progress независимо от его `process_state`. Эти наблюдения MUST быть помечены `PILOT_ONLY`, а не приняты как target authorization policy.

#### Scenario: Невалидный CSRF
- **WHEN** аутентифицированный активный пользователь отправляет `record_pto` с неверным form CSRF
- **THEN** ответ имеет status `403` и completion fact не появляется

#### Scenario: Деактивированная session останавливается в local-auth
- **WHEN** ранее аутентифицированный пользователь после смены status на 0 отправляет `record_pto` со своей session cookie
- **THEN** router отвечает `303`, `Location: /pilot/login`, completion table при её исходном отсутствии не создаётся и fact не появляется

#### Scenario: Активный пользователь вне scope
- **WHEN** активный пользователь без назначения на объект отправляет действительный `record_pto` для дела с progress 85
- **THEN** pilot отвечает `303` и сохраняет факт с id этого пользователя

#### Scenario: Дело не находится в working
- **WHEN** активный пользователь отправляет действительный `record_pto` для дела с progress 85 и `process_state`, отличным от `working`
- **THEN** pilot отвечает `303` и сохраняет факт

### Requirement: Отсутствующий объект и порядок проверок характеризуются
Characterization SHALL наблюдать `404` для legacy object id без единственного соответствующего дела. Для запроса с progress ниже 85 неизвестный action SHALL оставаться скрыт ранним threshold rejection; duplicate PTO SHALL оставаться скрывающим последующую проверку изменённой даты.

#### Scenario: Объект не найден
- **WHEN** действительный `record_pto` отправлен для legacy object id без монтажного дела
- **THEN** ответ имеет status `404` и completion fact не появляется

#### Scenario: Порог проверяется до action
- **WHEN** запрос с неизвестным action отправлен для дела с progress 84
- **THEN** pilot отвечает `409` причиной достижения 85%, а не `422` причиной неизвестного action

#### Scenario: Duplicate проверяется до изменённой даты
- **WHEN** дело уже имеет `pto_act`, а повторный запрос содержит другую будущую дату
- **THEN** pilot отвечает `409` и текст `Дата акта ПТО уже зафиксирована.`, сохраняя исходный факт без изменений

### Requirement: Replay и конкуренция сохраняют единственный исходный факт
Characterization SHALL фиксировать, что точный и изменённый последовательные replay после успешной записи возвращают `409`, а не idempotent success. Два одновременных допустимых запроса одного дела SHALL завершаться одним `303` и одним `409`, после чего SHALL существовать ровно один `pto_act` без UPDATE или DELETE исходной истории.

#### Scenario: Точный replay
- **WHEN** успешный `record_pto` повторён с теми же actor и датой
- **THEN** повторный ответ имеет status `409`, а единственный сохранённый факт не изменён

#### Scenario: Изменённый replay
- **WHEN** после успешного `record_pto` тот же actor отправляет другую допустимую дату
- **THEN** повторный ответ имеет status `409`, а исходные date, actor и recorded timestamp не изменены

#### Scenario: Два конкурентных запроса
- **WHEN** два допустимых `record_pto` одного дела стартуют конкурентно при отсутствии PTO fact
- **THEN** один ответ имеет status `303`, другой `409`, и после обоих ответов существует ровно один `pto_act`

### Requirement: Request-triggered DDL характеризуется как архитектурный риск
В изолированной схеме без completion table characterization SHALL доказать, что запрос активной session с прошедшим CSRF создаёт `fm2_pilot_completion_facts` до разрешения отсутствующего object id. Результат SHALL регистрироваться как нарушение целевого DDL ownership, а не как разрешение добавлять runtime DDL. Внутренний порядок DDL-before-actor MUST NOT выдаваться за достижимый router-сценарий.

#### Scenario: Отсутствующий объект всё равно инициирует schema creation
- **WHEN** completion table отсутствует и активный пользователь отправляет запрос с действительным CSRF для object id без монтажного дела
- **THEN** ответ имеет status `404`, но completion table существует после ответа и не содержит фактов

### Requirement: Проверка изолирована и устойчива к живым часам
Characterization MUST использовать уникальный DB prefix, реальный pilot HTTP route и очистку собственных таблиц. Для concurrency она MUST запускать штатный server с минимум двумя подтверждёнными worker processes, разделяющими prefixed DB, и использовать две независимые sessions. Она MUST вычислять сегодня/завтра по наблюдаемым часам `Europe/Moscow`, проверять успешный whole-second `recorded_at` на parseability и попадание в округлённые наружу до/после запроса bounds и MUST исключать конкретный timestamp из golden-сравнений. Environment/setup failure MUST отличаться от RED assertion и regression failure.

#### Scenario: Запуск около смены календарной даты
- **WHEN** characterization запускается в любое время суток, включая границу Moscow date
- **THEN** fixture dates и timestamp assertions выводятся из снятых clock bounds, а тест не зависит от `FMONITOR_NOW` и hard-coded today
