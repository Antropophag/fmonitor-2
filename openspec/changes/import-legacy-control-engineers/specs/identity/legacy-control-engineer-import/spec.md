## Purpose

Определяет безопасный и повторяемый перенос referenced legacy-инженеров строительного контроля в локальный справочник пользователей без автоматической активации или выдачи приглашения.

## ADDED Requirements

### Requirement: Referenced engineer identity is imported by stable legacy ID
Штатный legacy import SHALL для каждого положительного `responsstroicontrol` импортируемого объекта прочитать ровно одну соответствующую legacy user identity по числовому `users.id`. Система MUST NOT сопоставлять инженеров по ФИО, email либо позиции.

#### Scenario: Единственная активная legacy identity
- **WHEN** импортируемый объект ссылается на существующего активного legacy-пользователя с допустимой ролью инженера
- **THEN** import result содержит локальную identity и устойчивую связь с exact legacy user ID

#### Scenario: Отсутствующая или неоднозначная identity
- **WHEN** ссылка не разрешается ровно в одного допустимого legacy-пользователя
- **THEN** импорт завершается fail-safe без создания пользователя, связи объекта либо частичного success для этого atomic import

### Requirement: Imported engineer awaits an explicit invitation
Новый локальный пользователь SHALL отображаться администратору как ожидающий приглашения, иметь роль и capability инженера строительного контроля, но MUST NOT иметь пароль, активную сессию, invitation secret или возможность входа. Генерация приглашения SHALL происходить только отдельным явным административным действием авторизованного администратора.

#### Scenario: Новый инженер импортирован
- **WHEN** legacy import впервые принимает пригодную identity инженера
- **THEN** пользователь виден в административном справочнике как ожидающий приглашения и попытка входа отклоняется

#### Scenario: Владелец позднее создаёт приглашение
- **WHEN** авторизованный администратор запускает существующее действие генерации/перевыпуска приглашения для импортированного инженера
- **THEN** система создаёт новый invitation secret и сохраняет существующую legacy identity пользователя

#### Scenario: Неавторизованная генерация приглашения
- **WHEN** пользователь без `access.administer` пытается создать приглашение
- **THEN** действие отклоняется без новых credential, invitation или audit success фактов

### Requirement: Identity import is idempotent and preserves corrections
Повторный импорт одной legacy identity SHALL переиспользовать того же локального пользователя и MUST NOT создавать дубликаты ролей, capabilities или active identity links. Ранее выполненная явная административная коррекция identity MUST NOT молча отменяться последующим импортом.

#### Scenario: Повтор того же snapshot
- **WHEN** тот же legacy snapshot импортируется повторно
- **THEN** локальный user ID, identity link и назначенные права остаются теми же, а result сообщает replay/already-present counts

#### Scenario: Конфликт с существующей identity
- **WHEN** legacy ID или нормализованный email уже связан с другим локальным пользователем либо текущая связь была явно скорректирована
- **THEN** импорт сообщает детерминированный conflict и не перепривязывает пользователей автоматически

### Requirement: Imported identity has auditable provenance
Система SHALL сохранять source legacy user ID, снимок имени/email/status/role, cutoff импорта и import operation identity. Accepted import и последующая административная коррекция SHALL оставлять append-only audit facts.

#### Scenario: Аудит принятого импорта
- **WHEN** инженер создан или подтверждён импортом
- **THEN** администратор и диагностика могут установить legacy identity, source snapshot, operation identity и время принятия без доступа к секретам
