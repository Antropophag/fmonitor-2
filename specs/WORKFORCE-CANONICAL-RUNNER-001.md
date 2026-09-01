# WORKFORCE-CANONICAL-RUNNER-001 — зарегистрировать workforce schema v5 в canonical runner

- Статус: `APPROVED — GATE 1 PASSED`
- Версия: `0.1`
- Дата: `2026-09-01`
- Актор: оператор deployment FMonitor 2.0
- Публичный seam: `php bin/fmonitor2-migrate.php`
- Наследуемый contract: `BITRIX-WORKFORCE-SCHEMA-001 v0.3` (`APPROVED`)
- OpenSpec change: `register-workforce-history-canonical-v5`

## 1. Единичный срез

Canonical production/test migration command последовательно применяет migrations v1–v5. Пятый шаг вызывает уже утверждённый `BitrixWorkforceHistorySchemaMigration.apply(connection, tablePrefix)` и не переопределяет его exact schema, preflight, preservation, conflict или partial-recovery semantics.

После среза runtime bootstrap, importer, HTTP/UI и workers остаются только consumers schema. В production runtime вне canonical migration classes и runner запрещены вызовы workforce migration и workforce `CREATE`, `ALTER` или `DROP`. Отсутствующая либо несовместимая v5 schema является deployment/setup failure вызывающего процесса, а не поводом для self-healing DDL.

Срез не меняет workforce schema v5, workforce facts, импорт/синхронизацию Bitrix, assignment eligibility, authorization или storage design.

## 2. Preconditions и configuration

Runner сохраняет существующий configuration contract:

- обязательны `FMONITOR_DB_HOST`, `FMONITOR_DB_PORT`, `FMONITOR_DB_NAME`, `FMONITOR_DB_USER`, `FMONITOR_DB_PASSWORD`, `FMONITOR_PROCESS_TABLE_PREFIX`;
- invalid/missing configuration возвращает exit `64`, stdout `{"ok":false,"reason":"CONFIGURATION_INVALID"}\n`, пустой stderr и не обращается к DB;
- недоступная DB возвращает exit `69`, stdout `{"ok":false,"reason":"DATABASE_UNAVAILABLE"}\n`, пустой stderr;
- unexpected migration/DB exception возвращает exit `70`, stdout `{"ok":false,"reason":"MIGRATION_FAILED"}\n`, пустой stderr.

Canonical runner superseding draft использует composed process-prefix contract
`/^[A-Za-z0-9_]{0,25}$/D`: 25 ASCII bytes допускаются, 26 и invalid input
отклоняются до DB connection/access. Это будущее сужение необходимо для
release-supporting 39-byte classification-provenance basename и не переписывает
исторический approved 32-byte runner contract. Оно также не изменяет автономно
утверждённый workforce family-local contract: direct v5 migration по-прежнему
принимает 37 bytes и отклоняет 38 до DB access.

## 3. Ordered canonical composition

Exact migration catalogue и порядок:

1. `ProductionProcessSchemaMigration` — v1;
2. `WorkforceCatalogSchemaMigration` — v2;
3. `ProcessUserCapabilitiesSchemaMigration` — v3;
4. `ProcessCommandCapabilitiesSchemaMigration` — v4;
5. `BitrixWorkforceHistorySchemaMigration` — v5.

Runner вызывает следующий шаг только после успешного результата предыдущего. `appliedVersions` содержит в ascending catalogue order только версии, чей вызов вернул exact boolean `applied=true`. После полного успеха `schemaVersion` всегда равен `5`.

## 4. Exact observable outcomes

### 4.1 Clean database

На чистой совместимой database с допустимым prefix:

```json
{"ok":true,"schemaVersion":5,"appliedVersions":[1,2,3,4,5]}
```

Exit `0`, одна JSON line в stdout, stderr пуст. Полученная workforce schema обязана независимо соответствовать exact manifest `BITRIX-WORKFORCE-SCHEMA-001 v0.3`.

### 4.2 Completed repeat

Повтор на exact completed v5 schema:

```json
{"ok":true,"schemaVersion":5,"appliedVersions":[]}
```

Exit `0`, stderr пуст. Полный schema fingerprint и все sentinel rows v1–v5 остаются byte-for-byte неизменными.

### 4.3 Compatible partial recovery

Если v1–v4 exact, а v5 находится в одном из совместимых partial states из `BITRIX-WORKFORCE-SCHEMA-001 v0.3`, runner завершает recovery через v5 seam. Success result содержит `appliedVersions:[5]`; уже завершённые v1–v4 и существующие compatible v5 structures/data не изменяются сверх exact recovery contract v5.

### 4.4 Workforce conflict

Если v1–v4 exact, но хотя бы одна workforce target table несовместима с v5:

```json
{"ok":false,"reason":"SCHEMA_MIGRATION_CONFLICT","schemaVersion":5}
```

Exit `2`, stderr пуст. Runner не выполняет runtime repair и не продолжает после конфликтующего v5 step. Exact v5 preflight гарантирует zero DDL/DML для всех workforce targets при конфликте.

Conflict на более ранней migration сохраняет соответствующую версию `1`–`4` в `schemaVersion`; v5 не вызывается.

### 4.5 Unexpected v5 failure

Unexpected exception из v5 seam классифицируется только как:

```json
{"ok":false,"reason":"MIGRATION_FAILED"}
```

с exit `70` и пустым stderr. Частично committed exact MariaDB DDL может быть восстановлен только последующим canonical runner invocation по inherited v5 recovery contract.

## 5. Runtime DDL ownership

Machine-checkable architecture verification обязана запрещать вне canonical migration ownership:

- `BitrixWorkforceHistorySchemaMigration::apply(...)` в production runtime/bootstrap/importer/HTTP/UI/worker code;
- workforce-targeted `CREATE TABLE`, `ALTER TABLE` и `DROP TABLE` в тех же слоях;
- увеличение существующего runtime-DDL debt baseline.

Test/demo fixtures могут создавать изолированные schemas только в test/support ownership. Deployment сначала запускает canonical runner, затем runtime consumers.

Текущий repository audit не обнаружил прямого workforce v5 invocation или workforce DDL в `app/`; Gate 4 добавляет/уточняет ратчет и не обязан удалять несуществующий production debt. Demo/test fixture DDL не является production schema owner.

## 6. Independent executable examples

Gate 2 test получает expectations только из этого документа и `BITRIX-WORKFORCE-SCHEMA-001 v0.3`, а не из runner catalogue или migration implementation constants. Он обязан через public CLI доказать как минимум:

1. clean result `[1,2,3,4,5]` и independent exact v5 catalog;
2. populated repeat `[]` с неизменными v1–v5 fingerprints/rows;
3. compatible v5 partial recovery `[5]`;
4. incompatible workforce table → exact v5 conflict, zero workforce mutation;
5. earlier-version conflict short-circuits до v5;
6. unexpected v5 failure → `MIGRATION_FAILED`, не conflict;
7. exact 25-byte ASCII process prefix `aaaaaaaaaaaaaaaaaaaaaaaaa` на clean
   compatible database проходит полный v1–v5 CLI runner и возвращает clean
   success из раздела 4.1; все derived catalogue identifiers отдельно
   проверяются на MariaDB limit 64;
8. otherwise-valid exact 26-byte process prefix
   `aaaaaaaaaaaaaaaaaaaaaaaaaa` через тот же public CLI возвращает exit `64`,
   stdout `{"ok":false,"reason":"CONFIGURATION_INVALID"}\n` и пустой stderr;
   connection observer доказывает zero DB connection/access, а ledger/schema/
   rows/ambient objects остаются неизменными;
9. direct `BitrixWorkforceHistorySchemaMigration` verifier независимо сохраняет
   approved family-local 37 success / 38 pre-DB rejection и не используется как
   доказательство composed production configuration support;
10. architecture rule запрещает runtime workforce DDL/direct apply.

Environment/setup failure не является RED evidence. До implementation ожидаемый RED — clean/partial CLI сообщает `schemaVersion=4`, v5 отсутствует из `appliedVersions`, а v5 tables не созданы.

## 7. Done definition

Срез завершён только если:

- этот Gate 1 artifact явно утверждён владельцем;
- reviewed RED получен через public CLI и отдельно approved test reviewer;
- v5 зарегистрирован ровно один раз после v4;
- clean/repeat/partial/conflict/failure cases green без ослабления assertions;
- runtime workforce DDL/direct-apply architecture check green;
- relevant DB, architecture и deployment regression green;
- отдельный code reviewer записал `APPROVED`;
- OpenSpec tasks синхронизированы с фактически пройденными gates.

## 8. Owner decision

Нужно одно решение: утвердить или отклонить этот executable contract. До явного `APPROVED` Gate 2/implementation не начинаются и OpenSpec task `1.1` остаётся unchecked.

- Владелец продукта: пользователь проекта
- Дата решения: `2026-09-02`
- Решение: `APPROVED`
- Комментарий: утверждён exact composed runner contract v0.1; Gate 2 выполняет
  свежий RED-автор и передаёт результат отдельному свежему test reviewer до
  любых production changes.
