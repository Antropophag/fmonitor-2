# SELECTION-CANONICAL-REGISTRATION-001

Версия0.1, 2026-09-06. Candidate Gate1.

## Простыми словами

Штатная команда установки базы создаёт уже готовые registry и таблицы выбора
состава. Тестам и будущему deployment больше не потребуется вызывать эти engines
отдельно. Это регистрация схемы, а не включение пользовательских экранов.

## 1. Scope и authority

Actor — оператор controlled deployment/test setup. Public seam:
`php bin/fmonitor2-migrate.php` с прежними FMONITOR_DB_* и
FMONITOR_PROCESS_TABLE_PREFIX. Existing runner v1–v13, registry engine и selection
engine, native selection и selected-original binding approvals переиспользуются.

Authority fresh scope: `docs/operations/fresh-launch-owner-scope-2026-09-06.md`.
Исторического переноса/переделки старых writers/mixed rollout в launch scope нет.
Вызов готового registry engine не переопределяет его уже approved preservation
semantics; новые historical matrices не создаются. Новый portal должен запускать
только новые writers после их отдельного routing/bootstrap gate.

## 2. Точный catalogue на текущем frontier

Проверен source d1bcddc: canonical catalogue contiguous1..13, v13 —
OriginalAttemptAuditSchemaMigration, который подготавливает original schema.
В текущем registration slice добавляются:

- v14 — AssignmentOrderIdentityRegistryMigration::apply;
- v15 — AssignmentOrderSelectionSchemaMigration::apply.

Это фактический следующий шаг integration, не резерв для будущего slice.
Перед GREEN повторно проверить frontier; если он изменился, этот exact contract
и review обновляются до изменения runner. Ни одна версия не пропускается.
Production diff — импорты/ordered registration готовых engines. Schema definitions,
engine validators, canonical error mapping и application commands не меняются.

## 3. Public outcomes

Успех: exit0, одна JSON line, stderr пуст. Fresh compatible empty database:
`{"ok":true,"schemaVersion":15,"appliedVersions":[1,2,3,4,5,6,7,8,9,10,11,12,13,14,15]}`.
На already canonical v13 без registry/selection: appliedVersions=[14,15].
На полном повторе: schemaVersion15, appliedVersions=[].
На complete registry при отсутствующей selection: appliedVersions=[15].
Existing reportFromVersion/preflight semantics v1–v13 сохраняются.

Оба engines вызываются в порядке14→15; selection никогда не вызывается после
registry conflict. Conflict v14/v15 → прежний exit2 и
`{"ok":false,"reason":"SCHEMA_MIGRATION_CONFLICT","schemaVersion":14}`
(соответственно15). Никакого repair несовместимой family или успешного report15.
DatabaseUnavailable сохраняет exit69/DATABASE_UNAVAILABLE; unexpected failure —
exit70/MIGRATION_FAILED. Invalid config/prefix>25 — exit64/CONFIGURATION_INVALID
до DB access. Source secrets/SQL/errors не попадают в stdout/stderr.

После success public registry completion и selection readiness истинны; exact
schema fingerprint соответствует approved engines. Fresh family не создаёт
selection/member/original/domain events. Existing immutable receipt и next-ID
frontier не понижаются. Repeat byte-identical для domain rows/receipt.

## 4. Verification

Только task-owned synthetic databases. Actions — реальный CLI через scoped env;
fixture setup/observation не являются application writer. Cleanup attempt-all.

Минимальный RED/Gate3: complete-v13 fixture вызывает actual runner и ожидает15,
обе approved readiness проверки и отсутствие process facts; затем repeat.
Контроли: clean database, registry-only recovery, registry conflict stops before
selection, selection conflict reports15, prefix25 и прежние config failures.
Engine-level prefix/partial/recovery matrices не переписываются. Existing canonical
consumer expectations обновляются только там, где меняется advertised final
version/appliedVersions; unrelated protected E2E не изменяется.

## 5. Done и release boundary

Gate1→RED→independent Gate3→minimal registration→focused runner regression,
architecture-check/diff→independent Gate5 с exact-SHA evidence.
Canonical registration не включает HTTP/app startup, не обновляет preview и не
объявляет full portal readiness. Перед controlled launch нужны новые routes,
исключение старых writers, полный make verify/VERIFY_OK и deployment/restart/golden
path. Никаких remote mutations этим slice.
