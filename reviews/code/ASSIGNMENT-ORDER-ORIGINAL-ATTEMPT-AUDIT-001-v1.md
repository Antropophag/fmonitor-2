# Независимый Gate 5 review: ATTEMPT-AUDIT-001 v1

- Дата review: `2026-09-06`
- Reviewer: отдельно назначенный agent `/root/data_transport_gate1`
- Reviewed implementation: `3d4e852866ace2ef3abf776c8ffa027b7eca9d91`
- Implementation baseline: `4ed122cac564ae22c4505d3799a1059b04fb64a9`
- Specification SHA-256: `898bb71819ea5d01faf305ce2c26ad1dae63e33081c01c15e0a61fff11b48bb4`
- Owner approval SHA-256: `b9e6eb2a07a580d4d0daf5a57714af6435f2c309a73fd45619ff35b855e09147`
- Verdict: **APPROVED**

Reviewer не писал specification, tests или implementation. Scope ограничен
ATTEMPT-AUDIT v0.4: application audit behavior/diagnostics, native writer и
reader backing, schema v3/canonical13/physical names/capability-v5 recognition,
production/worker binding и исправление точного quoted-CHECK finding.

## Application и persistence

Denied valid-shape invocation сохраняет precedence: authorization выполняется до
terminal lookup, stream закрывается unread один раз, затем lazily получается один
validated instant и вызывается dedicated writer. Confidential terminal,
fingerprint, lineage, composition и fresh recovery не читаются. COMMITTED
возвращает denial; confirmed rollback и unknown дают точные retryable persistence
results. Existing accepted terminal не меняется и не раскрывается; повторные
denial получают отдельные audit identities.

STREAM/STORAGE failure вызывает audit-only writer после полного cleanup и не
создаёт terminal/root/revision/event/fingerprint. Audit failure не заменяет
выбранный retryable failure. Diagnostic owner выдаёт ровно один exact
`denial|file_failure|terminal|submission` event с единственным phase; guarded
logger Throwable не меняет результат и не повторяет operation. Invalid shape,
authorization unavailable и replay сохраняют заданные clock/audit boundaries.

Public dependency добавлен только trailing optional и default остаётся явно
degraded. Production factory и real worker связывают concrete MariaDB writer;
production observer selector не появился. Safe-log/host/password/connection
ordering и отдельный fresh recovery provider сохранены. Единственный public
state-changing application seam остаётся `submitAssignmentOrderOriginal`.

Native writer полностью валидирует DTO и prefix до SQL. Единственный initial
transaction-state read отличает active borrowed transaction; writer не управляет
caller transaction. Owned READ COMMITTED transaction проверяет native false,
observer/begin/commit/rollback и различает ROLLED_BACK/OUTCOME_UNKNOWN. Denial
request-key collision разрешается внутри adapter только key-presence lock/read,
без чтения result payload; terminal остаётся byte-identical, новая audit row
append-only. Реальные synchronized races дают один terminal и две audit rows без
ранней публикации.

Reader валидирует все audit rows и original matching backing. Только полностью
валидные orphan FAILED/STREAM_FAILURE либо FAILED/STORAGE_FAILURE допускают
NOT_FOUND без terminal; другие orphan/malformed rows fail closed. Дополнительные
valid denial/failure rows не заменяют matching terminal audit и не портят
accepted evidence.

## Schema, naming и canonical ownership

InstallationProcess остаётся единственным DDL owner. Migration v3 сохраняет
v2 rows/IDs и меняет только audit FK/index/status-pair constraints одним atomic
ALTER после semantic preflight. Active caller transaction отклоняется первым
state read; named lock имеет exact database/prefix/family identity и timeout5,
освобождается при success/failure/interruption. Full v2/v3 repeat, partial
recovery, drift conflict и after-ALTER durable retry fail closed без repair.

Единый pure physical-name mapper используется schema, maintenance и evidence
consumers. Только два слишком длинных maintenance suffix сокращаются на нужных
prefix boundaries; prefix сохраняется, existence/config fallback и dual-write
отсутствуют. Восемь FK имеют independently derived bounded hash names и прежнюю
семантику. Duplicate alias конфликтует до DDL.

Shared capability classifier принимает ровно v3/v4/v5, где v5 имеет exact
six-literal set без duplicates/extras и exact constraint name. Migrations3/4
распознают successor read-only без downgrade/grants; canonical runner всегда
выполняет migrations1..13 и migration13 проверяет полную original family.

Подтверждённый во время review fail-open CHECK canonicalizer закрыт. Ранее atom
удалял whitespace/backtick также внутри quoted literal, поэтому изменённый
`author ization_denied` ошибочно признавался exact. Исправленный source удаляет
formatting bytes только вне quotes; public migration tests сохраняют formatting
control и отклоняют quoted case/space/backtick drift без DDL/data mutation.

Ключевые exact source hashes:

```text
191c180ad3da1900381793c30066f41de464176c6941574bc37272d74b123029  app/AssignmentOrderOriginal/MariaDbCheckCanonicalizer.php
f881496670079eb13c0f1ce7df5f8fb92c06e7bcd1590ea830fb7943ed936de5  app/AssignmentOrderOriginal/MariaDbOriginalAttemptAuditWriter.php
8f6dd27cb0a059f5b456fb132c97021d07bdeae4f367e7f00cf2719d5b43a8f4  app/AssignmentOrderOriginal/MariaDbOriginalAttemptAuditRows.php
992544dfe5a30c194b810a8031531267bdae5c1b0b0398be8b76ba8107e31085  app/AssignmentOrderOriginal/AssignmentOrderOriginalAuditFinisher.php
ae179b8a14b09a23e81f2ce1fb00a378f32f4bc12024db6043ffd9471aadcbe7  app/AssignmentOrderOriginal/AssignmentOrderOriginalAttemptDiagnostics.php
c879105370a620e4038cef8f31e82f5d7d74a5b4577405b97214a5aa2b3c3a8e  app/AssignmentOrderOriginal/AssignmentOrderOriginalPhysicalNames.php
bad6183505286f227fa119b6a5804426e3b626f2fa34b49cadf1bce1c93b90db  app/InstallationProcess/OriginalAttemptAuditSchemaEngineSchemaMigration.php
7ff6d3c652d0ff96ba2824d28def5c603199e8f594d88bcea8feb0124d97d812  app/InstallationProcess/MariaDbOriginalAttemptAuditSchemaFingerprint.php
2d746fc3f5eb772d2e9c72a626c7a44b91a5f17ef0929df18cfebceb0d712f31  app/AssignmentOrderOriginal/ProductionAssignmentOrderOriginalFactory.php
58af49fa664e7f95028d94295d63f23e946e1be64ddf34dedce49c14acd919d0  app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php
```

Новый source не создаёт HTTP/CLI/domain mutator, runtime DDL или observer
selector. Новые production files меньше150 строк; architecture baseline не
вырос. Unchanged original core использует предыдущее DATA-INTEGRITY Gate5 source
assessment в её фактических границах.

## Gates и verification evidence

Owner decision «каждая denied invocation» не переоткрывался. Gate1 прошёл
последовательные exact-hash rereviews v0.2–v0.4. Независимые Gate3 records
утвердили command, schema v2, native writer/reader, naming consumers, bindings,
quoted literals и два точных compatibility patches. Ни один reviewer не
утверждал собственный test/code artifact.

Final immutable archive:
`/Users/antropophag/.local/state/fmonitor2-verification/original-attempt-audit-final-green-id4dwftx`.

```text
4be7c8ace5036d5f7ff0f019730becbefa026f3164327bd8a964effd33d736e7  evidence.json
```

Manifest имеет `complete=true`, пустые before/after status и одинаковый exact
HEAD `3d4e852866ace2ef3abf776c8ffa027b7eca9d91`. Все 52 commands завершились exit0:
новые command45/schema21/native41/quoted4 suites, все original regressions,
три supporting DB checks, architecture, unit, lint, оба strict OpenSpec и scoped
app/bin/tests/specs/openspec diff.

Предыдущий completed run на `2f543df` сохранён неизменно: 49 PASS и три FAIL —
два старых shape/recovery expectation mismatch и unit wrapper, остановившийся на
shape. Они не выдавались за GREEN; отдельно утверждённый tail patch исправил
только эти ожидания, после чего final52 прошёл полностью. Ранний quoted-literal
probe/RED также сохранён и закрыт на final SHA.

## Verdict boundary

**APPROVED** закрывает ATTEMPT-AUDIT v0.4 Gate 5 на exact SHA
`3d4e852866ace2ef3abf776c8ffa027b7eca9d91`.

Это не combined Original-command approval и не полный `make verify`/`VERIFY_OK`.
Maintenance completeness за пределами naming compatibility, public declaration
parity, selection, HTTP/opening, CI, deploy/restart и golden path остаются
отдельными незакрытыми gates.
