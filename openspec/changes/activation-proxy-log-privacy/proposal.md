## Why

Real nginx probe подтвердил, что ссылка активации попадает в access/error logs
при недоступном FastCGI. Это блокер безопасного Yii cutover #76.

## What Changes

- Safe structured access records и отсутствие activation request error context.
- Оба nginx конфига сохраняют обычные upstream diagnostics и FPM handoff.
- Один лёгкий build target даёт тесту настоящий runtime nginx без app builds.

## Capabilities

### New Capabilities
- `activation-proxy-logs`: приватная ссылка и диагностируемые ошибки прокси.

### Modified Capabilities

Нет изменений пользовательских действий и domain schema.

## Impact

Два nginx конфига, Yii Dockerfile stage split, один executable runtime test,
verification inventory. Никакого переключения stand или миграции БД.
