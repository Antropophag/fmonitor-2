## Context

См. proposal.md. CLI уже реализует отзыв и выпуск, но обходит прикладные полномочия и пишет created_by=NULL. Каталог скрывает invited под blocked. Исходный токен восстановить из SHA-256 нельзя.

## Goals / Non-Goals

**Goals:** одна транзакционная операция в IdentityAccess; HTTP и CLI только адаптируют ввод/вывод.

**Non-Goals:** миграция первичного создания/активации/ролей, изменение схемы и реальных данных стенда.

## Decisions

- MariaDbReissueUserInvitation реализует public interface ReissueUserInvitation и владеет транзакцией mysqli и таблицей приглашений, проверяет локальное полномочие active/administer и блокирует пользователя перед отзывом/выпуском. Зависимости — PHP/mysqli; нет обратной зависимости на PilotHttp.
- HTTP POST /pilot/admin/users/{id}/invitation использует существующие CSRF и flash. Read projection добавляет состояние приглашения без раскрытия token_hash.
- CLI сохраняет email-аргумент, требует вторым аргументом administrator ID; read-only MariaDbInvitationRecipientLookup передаёт ID в application. Это убирает безымянного writer.
- Существующая схема поддерживает историю: старые строки сохраняются с revoked_at, новая имеет created_by и created_at. TTL остаётся 24 часа.
- Architecture check должен подтвердить отсутствие нового pilot writer и зависимости IdentityAccess → PilotHttp; baseline не расширяется ради нового нарушения.

## Risks / Trade-offs

- Одноразовый flash может быть потерян при сбое сессии → перевыпуск доступен повторно.
- Активация использует собственную транзакцию → блокировки не допускают выпуск для уже активированного пользователя; возможный deadlock завершается rollback без частичной записи.
- Полная миграция identity остаётся вне среза. Gates, production integration и деплой не считаются выполненными по focused smoke.

## Migration Plan

Без DDL. Поставка кода обратима; созданные приглашения совместимы с прежней активацией. Стенд владельца не изменяется без отдельного конкретного deployment шага.
