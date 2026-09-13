## Why

После production web cutover PR108 runtime-образ всё ещё копирует весь каталог
`rapid-pilot` и исполняет его visual verifier во время сборки. Это оставляет
устаревший runtime внутри поставляемого артефакта, хотя production HTTP и console
уже обслуживаются Yii2, и мешает доказуемо завершить №76.

## What Changes

- Удалить `rapid-pilot` из production runtime image и его build-time команд.
- Сохранить Yii2 web/console entrypoints, assets, locked Composer dependencies,
  non-root user и действующие безопасные failure contracts.
- Проверять build context и готовый image через отдельный executable contract.
- Не удалять repository demo/oracle, не менять pilot demo image, БД, поведение
  маршрутов, deployment stand или upgrade/rollback procedure.

## Capabilities

### New Capabilities

- `runtime/yii2-production-image`: поставляемый production image содержит только
  Yii2 runtime closure и не содержит временный `rapid-pilot` runtime.

### Modified Capabilities

Нет.

## Impact

Затрагиваются template и generated production Dockerfile, verification inventory,
focused image contract и delivery metadata. Публичные HTTP/CLI контракты и данные
не меняются.
