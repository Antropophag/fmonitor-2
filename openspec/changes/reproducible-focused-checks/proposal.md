## Why

Focused checks должны одинаково и воспроизводимо исполняться локально и в CI,
без скрытых host dependencies и без доступа к чужим ресурсам.

## What Changes

Issue #110 поставляет только I2 container execution contract из
`specs/DELIVERY-EXECUTION-107-I2.md`. I1 — отдельный predecessor PR; I3/I4 не входят.

## Capabilities

### New Capabilities

- `delivery/reproducible-focused-checks`: общий isolated Docker route для registered checks.

## Impact

Затронуты harness execution profiles, dependency manifests, Make/CI routing и
bounded verification inventory. Product/domain behavior, deployment и merge не меняются.
