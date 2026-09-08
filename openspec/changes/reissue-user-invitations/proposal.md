## Why

#46 блокирует подключение тестировщиков: одноразово показанная ссылка теряется после ухода с экрана. Администратору нужен перевыпуск для той же учётной записи.

## What Changes

- Срез INVITATION-REISSUE-001: администратор перевыпускает неиспользованное приглашение, видит состояние и получает новую ссылку.
- Oracle: существующий rapid-pilot/issue-invitation.php; целевой public seam — IdentityAccess\ReissueUserInvitation::reissue(actorId, userId).
- Админка и CLI вызывают один владеющий persistence интерфейс; CLI больше не пишет приглашения самостоятельно.
- Не входят: сброс пароля активного пользователя, почтовая доставка, перенос остальных identity-команд, #40/#39 и ОТиЗ.

## Capabilities

### New Capabilities

- `identity/invitation-reissue`: безопасное восстановление возможности пригласить уже созданного пользователя.

### Modified Capabilities

Нет.

## Impact

IdentityAccess, HTTP user admin/session/routes, каталог пользователей, pilot CLI, focused HTTP/application/browser проверки. Схема БД сохраняется. Основание — issue46 и решение владельца о срезе отвязки; аудиты PR48 остаются отдельными проектными материалами.
