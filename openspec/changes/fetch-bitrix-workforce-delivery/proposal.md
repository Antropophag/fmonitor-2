## Why

Native sync должен получать только полностью проверенный ответ всех страниц до
normalization/publication. Сейчас cron сам fetch-ит и пишет SQL; broad workforce
epic не executable. Отдельный readonly delivery port устраняет этот prerequisite.

## What Changes

- BITRIX-WORKFORCE-DELIVERY-001: production HTTPS user.get client с bounded
  pagination/retries/deadline/body, explicit selected fields и redacted results.
- Native TLS fixture доказывает protocol, peer/hostname validation, no redirects,
  failure without partial batch и cleanup. Реальный портал не вызывается.

## Capabilities

### New Capabilities
- `workforce/bitrix-delivery`: полный структурно проверенный readonly delivery.

### Modified Capabilities

## Impact

New Workforce owning namespace для readonly external-data port; no DB/schema/
publication, no cron wiring, no user/process grants. Normalization, employment
eligibility, freshness threshold, identity reconciliation and machine publication
remain separate gates. This does not claim live Bitrix readiness or launch.
