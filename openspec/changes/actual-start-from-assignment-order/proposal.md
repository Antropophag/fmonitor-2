## Why

Дата начала сейчас вводится отдельно при открытии стройконтролем и может отличаться от подписанного распоряжения. Владелец назначил документ источником факта и потребовал открывать работы сразу после загрузки, включая будущую дату.

## What Changes

- **BREAKING**: production upload требует подтверждённую дату начала из приложения и автоматически применяет состав/открывает работы.
- Сохраняется отдельная immutable дата с durable replay и честным partial outcome.
- Ручное открытие с датой убирается; миграция39 и recovery сохраняют новые факты.

## Capabilities

### New Capabilities

- `installation/order-start-date`: дата начала и автоматическое открытие из подписанного распоряжения.

### Modified Capabilities

Нет; новый контракт явно заменяет прежнее отдельное открытие в production upload flow.

## Impact

AssignmentOrderComposition owner, additive schema39, RuntimeRestore, Yii upload/history/card/checklist и регрессии. Нормативный контракт: `specs/ACTUAL-START-FROM-ORDER-001.md`. Не входят очистка базы, backfill, OCR, новое финансовое правило, scheduled opening. Root пишет tests/spec; отдельный executor код; независимый final review.
