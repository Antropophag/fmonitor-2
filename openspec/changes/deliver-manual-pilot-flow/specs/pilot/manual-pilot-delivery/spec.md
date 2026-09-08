## Purpose

Определить наблюдаемую границу передачи чистого ручного стенда владельцу без
подмены focused pilot evidence утверждением полной production readiness.

## ADDED Requirements

### Requirement: Clean authorized stand
Manual-pilot deployment SHALL использовать новый отдельный generation с единым
process/legacy prefix, пустым object contour и ровно одной начальной owner-admin
учётной записью. FMonitor users и прежние preview volumes MUST NOT импортироваться;
владелец SHALL приглашать остальных пользователей через штатный authorization flow.

#### Scenario: First clean start
- **WHEN** оператор запускает новый стенд с настроенной owner-admin учётной записью
- **THEN** login доступен, очередь объектов пуста, прежние volumes отключены и сохранены как резерв.

#### Scenario: Restart after invitation
- **WHEN** владелец пригласил пользователя и стенд перезапущен
- **THEN** manifest nonce, owner, приглашённый пользователь, роли и ранее записанные facts сохраняются без повторного bootstrap reset.

### Requirement: Complete manual route through application owners
Стенд SHALL направлять изменяющие действия через существующие public application
owners и SHALL сохранять append-only original, composition application, checklist,
photo и completion correction history. Сервер SHALL проверять exact capabilities;
opening MUST оставаться отдельным действием после применённого original.

#### Scenario: Golden path
- **WHEN** авторизованные участники выбирают состав, принимают и применяют original,
  открывают работы, завершают checklist, фиксируют ПТО и декларацию
- **THEN** объект достигает100%, а данные и история доступны после повторного входа и restart.

#### Scenario: Unauthorized mutation
- **WHEN** пользователь без exact capability вызывает изменяющее действие
- **THEN** сервер отклоняет его без частичного business fact или скрытой HTTP/rapid-pilot записи.

### Requirement: Private read-only workforce delivery
Разрешённый Bitrix `user.get` batch SHALL храниться вне репозитория, публиковаться
native workforce owner и не создавать FMonitor users. Unknown employedFrom MUST
оставаться null и допускается только с coherent full current snapshot proof;
возраст снимка отображается как warning и MUST NOT сам по себе блокировать pilot.

#### Scenario: Authorized workforce publication
- **WHEN** private complete batch прошёл normalization/publication и содержит
  подтверждённого employed сотрудника с неизвестной датой приёма
- **THEN** каталог сохраняет null и full proof, а selection отклоняет ту же форму без proof.

### Requirement: Honest delivery status
Deployment, hourly schedule и live login/restart/golden-path smoke SHALL считаться
выполненными только после фактического evidence. Full `make verify`, Gate3/5,
production integration и CI SHALL оставаться pending до отдельных подтверждений.

#### Scenario: Built but not deployed
- **WHEN** source и focused native checks готовы, но новый контейнерный стенд ещё не проверен
- **THEN** change сообщает deployment и live smoke как pending и не заявляет production readiness.
