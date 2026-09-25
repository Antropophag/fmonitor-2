## Purpose

Гарантировать, что показанное активному пользователю действие планирования инспекции выполняется в пределах его canonical object scope и не расходится с server-side authorization.

## ADDED Requirements

### Requirement: Exact permission двух ролей
Система SHALL включать exact permission `inspection.schedule` в канонический каталог ролей `manager` («Руководитель ФКР») и `construction_control_engineer` («Инженер строительного контроля»). Точечное provisioning существующего production MUST добавить отсутствующий grant этим двум ролям идемпотентно и MUST NOT выдать его другим ролям, удалить custom grants, изменить role assignments или schema frontier.

#### Scenario: Существующие роли получают permission
- **WHEN** авторизованная provisioning operation применяется к production-compatible каталогу без `inspection.schedule` у `manager` и `construction_control_engineer`
- **THEN** обе роли получают ровно этот дополнительный grant, а прочие роли и назначения не меняются

#### Scenario: Повтор provisioning идемпотентен
- **WHEN** provisioning operation повторяется после успешного применения
- **THEN** набор role permissions остаётся byte-for-byte эквивалентным и дубликаты не создаются

### Requirement: Видимое планирование доступно двум ролям в scope
Система SHALL разрешать активному аутентифицированному actor с effective permission `inspection.schedule` создать, перенести или отменить план инспекции только для объекта, входящего в его canonical construction-control scope. Global scope SHALL оставаться доступен `manager` с `objects.read`, а инженерный scope — только актуально закреплённому `construction_control_engineer`.

#### Scenario: Руководитель ФКР создаёт план
- **WHEN** активный `manager` с выданным `inspection.schedule` активирует показанную кнопку для объекта в global scope и отправляет валидную дату
- **THEN** сервер создаёт план, добавляет append-only event с фактическим actor и возвращает canonical success redirect

#### Scenario: Закреплённый инженер создаёт план
- **WHEN** активный `construction_control_engineer` с выданным `inspection.schedule` планирует инспекцию для объекта своего актуального закрепления
- **THEN** команда принимается и сохраняет engineer actor в audit event

#### Scenario: Чужой объект скрыт
- **WHEN** инженер отправляет команду для объекта вне актуального закрепления
- **THEN** система возвращает canonical not-found outcome и не создаёт план или audit event

### Requirement: Mutation-инварианты сохраняются
Универсальное выполнение видимой кнопки SHALL сохранять CSRF validation, active identity, object eligibility, непрошедшую дату, expected version, request identity/fingerprint, idempotent replay, conflict semantics, atomic transaction и append-only event history. Гость или inactive actor MUST NOT планировать инспекцию. Ошибка инфраструктуры MUST оставаться UNKNOWN/retryable без ложного success и частичной записи.

#### Scenario: Гость и inactive actor отклоняются
- **WHEN** гость либо inactive actor отправляет валидную команду планирования
- **THEN** система применяет canonical auth denial и не изменяет schedule/event tables

#### Scenario: Replay остаётся идемпотентным
- **WHEN** scoped actor повторяет тот же request identity с тем же намерением
- **THEN** сервер возвращает тот же результат без второго schedule root или event

#### Scenario: Optimistic conflict не перезаписывает историю
- **WHEN** scoped actor отправляет устаревшую expected version после конкурентного изменения плана
- **THEN** сервер возвращает canonical conflict, сохраняя текущий план и прежние события без изменения

### Requirement: UI и command policy согласованы
Construction-control UI SHALL показывать create/reschedule/cancel controls только когда текущий активный actor имеет effective `inspection.schedule` и объект удовлетворяет той же canonical scope policy, которую применяет command seam. UI MUST NOT показывать действие actor без permission, заведомо завершающееся `Access denied`.

#### Scenario: Показанная кнопка выполняется
- **WHEN** активный scoped actor видит кнопку «Запланировать инспекцию» и отправляет валидную форму без конкурентного изменения
- **THEN** ответ не равен `403 Access denied`, план появляется в очереди и календаре

#### Scenario: Out-of-scope действие отсутствует
- **WHEN** объект не входит в scope текущего actor
- **THEN** UI не публикует controls планирования этого объекта, а прямой command остаётся закрытым

#### Scenario: Роль без permission не видит кнопку
- **WHEN** активный пользователь другой роли открывает доступную read-only поверхность стройконтроля без `inspection.schedule`
- **THEN** UI не показывает controls планирования, а прямой POST возвращает canonical denial без записей
