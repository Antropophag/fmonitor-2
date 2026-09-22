## Why

Владельцем наблюдалось около 550 SQL commands и множество temporary tables на
один `/health/ready`; это исходное измерение требует независимого воспроизведения.
Compose вызывает readiness из PHP и web каждые пять секунд, поэтому полный schema
fingerprint обход создаёт заметную idle-нагрузку домашнего стенда.

## What Changes

Срез `RUNTIME-READINESS-LOAD-001` отделяет обязательную full startup schema check
от дешёвого регулярного probe. Существующий runtime-check и canonical migration
catalogue сохраняются; применимый startup result связывается с exact DB/schema/build.

Не входят общий infrastructure tuning, изменение интервала вместо исправления,
business/UI changes, внешние integrations, рабочий stand или user throughput claims.

## Capabilities

### New Capabilities

- `bounded-runtime-readiness`: fail-closed constant-cost regular readiness after
  an exact successful startup check.

### Modified Capabilities

- `production-http-runtime`: full schema compatibility moves from every HTTP
  readiness request to the deployment startup chain without weakening status.
