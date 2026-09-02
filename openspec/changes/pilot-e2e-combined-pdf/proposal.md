## Why

Owner-approved artifact contract и production renderer уже формируют один
versioned combined PDF, но golden E2E всё ещё скачивает отдельные `order` и
`appendix` HTML artifacts. Этот stale oracle дважды ломает full verify и
маскирует дальнейшую TEST-USER integration.

## What Changes

- Зафиксировать один immutable combined PDF на versioned assignment order,
  содержащий распоряжение и приложение.
- Подготовить executable amendment `PILOT-E2E-FLOW-001 v0.5`, который полностью
  заменяет v0.4 route/card/two-HTML artifact/happy-journey/oracle/non-goal blocks
  одним `order` PDF contract; owner approval v0.4 не переносится автоматически.
- Синхронизировать E2E download/projection/fault fixtures с approved
  `ARTIFACT-STORE-001`, не возвращая параллельные legacy artifacts.
- Сохранить authorization-first, content-addressed integrity, EACCES/fault
  redaction, fresh reload, byte/hash/size/filename и append-only process facts.
- **Actor:** FKR/test user с exact artifact-read authority; **source oracle:**
  production PDF renderer, artifact store и failing golden E2E; **target public
  seam:** versioned artifact download HTTP/application service.
- **Release value:** воспроизводимый golden journey на фактическом public PDF
  contract. **Non-goals:** PDF visual redesign, order semantics, RBAC fixture
  migration, document signing/1С integration и два HTML artifact.

## Capabilities

### New Capabilities

- `verification/pilot-e2e-combined-pdf`: Определяет combined-PDF E2E contract, integrity/fault assertions и удаление stale two-artifact fixture expectation.

### Modified Capabilities

Нет.

## Impact

Затронуты `specs/PILOT-E2E-FLOW-001.md` v0.5, executable E2E test fixtures,
download expectations и operations
evidence. Production renderer/store меняются только если RED найдёт реальное
несоответствие approved combined contract.
