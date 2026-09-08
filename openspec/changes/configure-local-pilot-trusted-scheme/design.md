## Context

См. proposal. Preview image1eba93cf, GETusers503 и trustedScheme=false;
directory read и render отдельно успешны. Primary evidence внешнее.

## Goals / Non-Goals

**Goals:** explicit profile binding и native HTTP доказательство.
**Non-Goals:** backend defaults, source deploy, TLS/remote configuration, grants.

## Decisions

Compose environment literal http соответствует loopback transport. SessionStorage
остаётся persistence owner; PilotHttp читает trusted config; rapid adapter только
использует прежние approved seams. Тест парсит effective Docker Compose JSON и
передаёт actual scheme в real-router native fixture. При missing значение остаётся
пустым (failclosed), не заменяется test default. GET DB snapshots сравниваются.
Architecture boundaries и baseline не меняются.

## Risks / Trade-offs

Нельзя трактовать local http как внешний TLS profile → scope только существующий
loopback published port. Preview recreate может запускать entrypoint/bootstrap →
reuse прежний configuration-only recovery workflow, snapshot evidence before/after,
не изменять source image/volumes и не запускать новые migrations/production imports.
Штатный old bootstrap допускает только прежнее operational manifest/sentinel
nonce refresh; exact diff allowlist задан spec, все остальные DB facts/DDL сохраняются.

## Operational implementation decision

Дальнейшее чтение IdentityBootstrap показало изменения auth/role timestamps при
обычном restart. Для соблюдения spec выбирается external readonly runtime-start
recipe: прежние socat listeners + exec прежнего start.php, без bootstrap/migrations.
Image/state/healthcheck сохраняются, DB и manifest остаются полностью exact.
Это техническое сужение side effects, не расширение разрешённого diff.
