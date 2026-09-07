## Purpose

Workforce ingestion получает полный структурно проверенный ответ Bitrix через
readonly production transport, не публикуя частичные или непроверенные наборы.

## ADDED Requirements

### Requirement: Complete bounded delivery
Client SHALL соблюдать BITRIX-WORKFORCE-DELIVERY-001, сохраняя TLS verification,
configured scope, redaction и отсутствие DB/domain writes.

#### Scenario: Later page fails
- **WHEN** первая страница проверена, а следующая завершилась ошибкой
- **THEN** результат failed с batchnull и числом ранее проверенных страниц

#### Scenario: Full response succeeds
- **WHEN** все страницы согласованы по total, порядку и scope
- **THEN** результат содержит immutable selected records без normalization или grants
