# SAFE-RUNTIME-ERROR-CORRELATION-001 — безопасная корреляция выбранных runtime 5xx

## Простыми словами

При выбранной серверной ошибке клиент получает безопасный непрозрачный ID. По нему оператор находит ровно одну короткую запись с местом, категорией и временем, но без exception text, запроса, документа или секретов. Срез не меняет предметные операции, ответы успеха/4xx и не создаёт новую observability-платформу.

## 1. Акторы и публичный seam

- Клиент canonical HTTP runtime наблюдает фактически отправленные status, headers и body.
- Оператор читает фактически записанный штатный runtime log и ищет exact error ID.
- Выбранные boundaries: canonical bootstrap, global Yii error handler, `ExecutionController`, `OriginalController`, `ChecklistController`.

## 2. Preconditions and action

Изолированный запрос проходит через один выбранный boundary. Fault injection вызывает подтверждённую configuration/database/dependency/storage ошибку или неизвестное исключение. Клиент может прислать одноимённый header любой длины и с любым содержимым; он недоверенный.

## 3. Observable result

Для выбранного 5xx сервер генерирует новый opaque ASCII error ID и добавляет `X-FMonitor-Error-ID`. Существующие status, body/reason, security headers, `Retry-After`, redirects/cookie sanitization и смысл «результат операции неизвестен» сохраняются.

По exact ID находится ровно одна JSON-line запись со строго следующими смысловыми полями: `event=http_failure`, `errorId`, `occurredAtUtc`, `component`, `status`, `category`, `buildVersion`. Допустимые component соответствуют пяти boundaries. Допустимые category: `configuration`, `database`, `dependency`, `storage`, `unexpected`. Build version берётся только из существующего trusted server-side source, иначе `unknown`.

## 4. Privacy and trust

ID всегда server-owned и не равен принятому client value. Component/category задаются closed values. Category определяется `instanceof`, стабильным кодом либо явным местом failure; message search запрещён. Unknown остаётся `unexpected`.

HTTP и новая запись не содержат raw exception message/trace, SQL или параметры, DSN/credentials/token/cookie, query string, request body, PII, filename/document metadata и bytes/content документов. Debug mode не включается.

## 5. Failure semantics

Logger failure не меняет исходный безопасный HTTP result, не повторяет command/external effect, не отменяет уже принятый result, не пишет domain facts и не вызывает recursive logging. Bootstrap path не зависит от исправной DB или Yii. Один failure не получает двойную запись в controller и handler.

Успехи, ожидаемые 4xx, authorization/RBAC/CSRF decisions, domain facts и append-only history не меняются и не создают новую failure-запись.

## 6. Acceptance examples

1. Bootstrap configuration failure → 503, прежний JSON reason/security headers/Retry-After, server ID; одна запись `component=bootstrap`, `category=configuration`.
2. Bootstrap database readiness failure → тот же безопасный HTTP contract; одна запись `category=database` или `dependency` по известному type/code.
3. Known storage failure в selected controller → прежний 503 envelope и одна запись `category=storage`.
4. `RuntimeException("CANARY_DSN_PASSWORD_COOKIE_QUERY_BODY_DOCUMENT")` без known type/code → `category=unexpected`; canary отсутствует в wire response и полном записанном line.
5. Client header со строкой `client\r\nInjected: secret` не появляется ни в response ID, ни в record ID.
6. Sink бросает на write → тот же 503/status/body/security headers/error ID; command вызывается не более одного раза.
7. Global handler удаляет unsafe response headers → `Location`/`Set-Cookie` отсутствуют, `X-FMonitor-Error-ID` присутствует.
8. Success и representative 400/403/404/405/409/413/415 сохраняют exact prior behavior и не создают runtime failure record.

## 7. Non-applicable lifecycle groups

Schema, backup/restore, replay/concurrency domain semantics, permissions, deployment dependencies и UI не меняются: capability добавляет operational record после failure и не владеет state mutation. Проверка всё равно доказывает отсутствие новых domain writes/retries и сохранение adjacent HTTP flows.
