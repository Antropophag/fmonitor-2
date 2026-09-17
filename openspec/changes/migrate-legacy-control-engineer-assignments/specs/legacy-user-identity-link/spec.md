## Purpose

Позволяет администратору доказуемо связать локальную учётную запись FMonitor 2.0 с конкретным пользователем legacy FMonitor для безопасной миграции предметных фактов.

## ADDED Requirements

### Requirement: Явное однозначное связывание identity
Система SHALL позволять активному администратору с exact `access.administer` записать для одной local identity один положительный canonical `legacy users.id`. Один legacy ID SHALL быть связан не более чем с одной local identity. ФИО, email, роль и должность SHALL отображаться только как подсказки и MUST NOT самостоятельно создавать связь.

#### Scenario: Администратор подтверждает связь
- **WHEN** администратор выбирает local identity и существующего legacy пользователя по exact ID
- **THEN** система сохраняет связь, actor, server time и source identity snapshot и показывает её в каталоге пользователей

#### Scenario: Неоднозначная или повторная связь отклонена
- **WHEN** legacy ID уже связан с другой identity, local identity уже имеет иной legacy ID либо legacy row отсутствует
- **THEN** система отклоняет команду со стабильной причиной без частичной записи

### Requirement: Связь не переносит legacy-доступ
Система SHALL создавать пользователей, роли, приглашения, активацию и первый вход только существующим local IdentityAccess flow. Legacy password/hash, session, роль и права MUST NOT переноситься или давать local access.

#### Scenario: Предварительно созданный инженер активируется
- **WHEN** администратор приглашает инженера, назначает local роль/permissions, связывает exact legacy ID, а инженер использует invitation
- **THEN** первый вход выполняется с новым local password и не зависит от legacy credentials

### Requirement: Авторизация, история и идемпотентность
Команда связывания SHALL повторно проверять active actor, active role и exact permission внутри owning transaction, вести append-only audit и быть идемпотентной для exact повторения. Изменение существующей связи SHALL требовать отдельной явной correction-команды с причиной; молчаливое обновление запрещено.

#### Scenario: Повтор и запрещённый actor
- **WHEN** exact command повторена тем же request ID либо actor не имеет exact permission
- **THEN** повтор возвращает прежний результат без новой связи, а запрещённая команда не раскрывает legacy-кандидатов и не создаёт facts

