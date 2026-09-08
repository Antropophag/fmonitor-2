## Why

Защищённый `pilot_e2e_flow_001_test.php` проверяет устаревший экран широкого списка, ручную регистрацию номера 1С ДО и отдельные apply/open переходы, поэтому не может служить доказательством уже одобренного текущего pilot flow. Во время стабилизации до `VERIFY_OK` его нужно согласовать с актуальным source oracle без ослабления transport, RBAC, append-only и persistence проверок.

## What Changes

- Перестроить защищённый E2E-сценарий вокруг текущего маршрута: очередь → выбор состава → необязательный inline PDF-шаблон → загрузка/подтверждение и исправление оригинала → карточка → атомарный `open_confirmed` → checklist 85% → акт ПТО и декларация → 100%.
- Сохранить отрицательные transport/RBAC/date/concurrency проверки, точные download bytes, HEAD/GET, CSRF/Origin, no-partial-write, append-only history и fresh-read persistence.
- Удалить только superseded assertions универсального semantic list, ручного номера 1С ДО, legacy registration endpoints и отдельного пользовательского apply UI.
- Подключить обновлённый защищённый сценарий к bootstrap/E2E verification без превращения failure в skip.
- Не менять runtime behavior, protected hash без отдельного review, реальные данные, stand или CI publication в рамках planning change.

## Capabilities

### New Capabilities

- `verification/protected-pilot-e2e-current-flow`: исполняемый контракт защищённого E2E для актуального manual-pilot маршрута и его security/history/persistence инвариантов.

### Modified Capabilities

Нет.

## Impact

Затрагиваются только защищённый E2E verifier, его изолированные fixtures/bootstrap wiring и review/evidence. Source oracle: `PRODUCT.md`, `CONTEXT.md`, pilot spec/data model, решение владельца о прямом opening после original confirmation и уже принятые public application seams. Целевой публичный изменяющий seam открытия — атомарный `open_confirmed`; presentation и HTTP не владеют application/opening facts.
