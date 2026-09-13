## Why

Три Gate 3 review показали, что общий cutover нельзя чувствительно проверить одним срезом. Сначала нужен неизменяемый exact target contract и canonical Yii2 topology, на которые сможет безопасно опереться отдельный destructive control plane.

## What Changes

- Добавить read-only exact target manifest validator с deterministic digest и safe failures.
- Добавить canonical template/render production Compose с обязательным explicit shared image reference; immutable digest обязателен для deployment manifest, а isolated build может использовать explicit temporary tag.
- Отложить backup, journal, reset, rollback и live deployment в отдельные changes.

## Capabilities

### New Capabilities

- `operations/yii2-stand-target-compose`: exact stand target и canonical Yii2 service topology.

### Modified Capabilities

Нет.

## Impact

Actor — deployment operator; public seams — read-only validator и parsed Compose. Source oracle — issue #76 и принятые Yii2 runtime/jobs/image contracts. Затрагиваются `tools/delivery/`, `deploy/runtime/compose.yaml`, tests и verification inventory. Stand, данные, история и deployment не меняются.
