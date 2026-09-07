## Why

Full verify выявил consumers, которые вызывают актуальный canonical runner, но
по-прежнему требуют terminal12. Уже approved migrations13/14/15 делают эти
ожидания неверными; release engineer теряет проверку последующих assertions.

## What Changes

- VERIFICATION-CANONICAL-FRONTIER-015: согласовать exact current-runner expectations
  и каталог с approved terminal15, сохраняя проверки каждого predecessor.
- Не менять isolated historical-engine expectations, production migrations,
  protected E2E или user-visible behavior.

## Capabilities

### New Capabilities
- `delivery/canonical-frontier-verification`: актуальные strict consumer oracles.

### Modified Capabilities

## Impact

Только13 выявленных non-protected verifier files и test-only catalog support.
Oracle: approved original audit13, registry14/selection15 registration и их
существующие literal contracts. Public seam — bin/fmonitor2-migrate.php и
существующие реальные consumer workflows. Нет новых versions, bypasses или skips.
E2E launch-label mismatch сохраняется отдельно; этот пакет не разрешает его менять.
