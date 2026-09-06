# Независимый Gate 1 review: ATTEMPT-AUDIT-001 v0.1

- Дата review: `2026-09-06`
- Reviewer: отдельно назначенный agent `/root/data_transport_gate1`
- Executable specification SHA-256: `961df2ea6835aa67578b1d2de9773728584d01a33879269a5ba76c3d9f0ae9ae`
- Parent ORIGINAL-UPLOAD v73 SHA-256: `c19c9e99244ae75a4756c03042d2cb8dc8762127c063c35dd9cd3df9a1872624`
- OpenSpec proposal SHA-256: `6e99133ab41ca1e92c10c45128ba0a4a10ae671ef64680f85cb976ac70f776ab`
- OpenSpec design SHA-256: `564fa49012340014bb6e935923696b9952bcb76303d0f97eeb1ddc011b1421f6`
- OpenSpec tasks SHA-256: `af4a6422db3f0db1453960299622fb3a26687bd96d7aab5a5cba30a45935609d`
- OpenSpec delta SHA-256: `9ebbc32f0c3c35177f95b831b0f154a0f69f5f1a44d4dad73c6fa973468d8c51`
- Owner approval SHA-256: `b9e6eb2a07a580d4d0daf5a57714af6435f2c309a73fd45619ff35b855e09147`
- Verdict: **CHANGES_REQUESTED**

Reviewer не писал спецификацию, OpenSpec artifacts, tests или production code.
Owner policy «каждая denied invocation» считается утверждённой и не
переоткрывалась. `openspec validate record-original-submission-attempt-audits
--strict` проходит.

## Блокирующая неоднозначность

Требуется определить exact public outcome для двух уже заявленных active
caller-transaction preconditions.

1. Section 2 говорит, что real `AssignmentOrderOriginalMariaDbAttemptAuditWriter`
   использует borrowed mysqli и не commit/rollback-ит caller-owned transaction.
   Sections 3–4 требуют closed `COMMITTED|ROLLED_BACK|OUTCOME_UNKNOWN`, но не
   определяют, что именно возвращают `recordDenied` и `appendFailure`, когда
   connection уже находится в caller transaction, вызывается ли observer и
   допускается ли SQL. Унаследованный DATA-INTEGRITY contract фиксирует
   `ROLLED_BACK` и zero writes для старых AcceptedCommit/AttemptCommit, однако
   новый writer — отдельный port, а v0.1 явно не наследует это правило для его
   двух methods. Выбор `ROLLED_BACK` против `OUTCOME_UNKNOWN` меняет denied
   public result (`PERSISTENCE_FAILURE` против `PERSISTENCE_OUTCOME_UNKNOWN`),
   diagnostic classification и обязательный RED.

2. Section 6 запрещает начинать DDL при active caller transaction, но public
   `OriginalAttemptAuditSchemaMigration::apply` допускает только success/conflict
   array либо `DatabaseUnavailable`. Не задано, должен active-transaction case
   вернуть `SCHEMA_MIGRATION_CONFLICT` или бросить fixed
   `DatabaseUnavailable`, происходит ли metadata/lock access до отказа. Это
   observable поведение public migration seam и необходимая часть transaction
   ownership test.

Минимальное исправление спецификации: для каждого случая назвать один точный
result/exception и зафиксировать zero SQL/observer/lock/DDL либо точную разрешённую
последовательность. Нового API, status enum или тестовой матрицы для этого не
требуется.

## Остальная оценка

Других блокирующих неоднозначностей не найдено. Public DTO/port constructible:
denial и STREAM/STORAGE failure закрыты по status/reason, scalar grammar и
outcomes. Denial precedence не выполняет terminal/fingerprint/lineage/
composition lookup и сохраняет accepted terminal byte-exact. Request-PK
collision обрабатывается внутри одной adapter transaction без чтения или
раскрытия terminal payload; подтверждённый commit добавляет отдельную audit row
каждой invocation.

Schema v3 совместима с утверждённой cardinality: она снимает audit FK и UNIQUE,
добавляет только две failed reason pairs, сохраняет прежние rows/IDs и требует
semantic preflight до одного atomic ALTER. Actual canonical runner заканчивается
на 12; selection contract явно не резервирует 13, поэтому текущая reservation
original-v3=13 непротиворечива. Whole-family v2/v3 recognition, named-lock
ownership, post-DDL revalidation и no runtime DDL заданы достаточно точно.

Reader backing остаётся fail-closed: orphan разрешён только для полностью
валидного FAILED/STREAM_FAILURE или FAILED/STORAGE_FAILURE audit; все остальные
orphan/malformed rows дают UNAVAILABLE. Дополнительные denial/failure rows не
заменяют обязательный matching audit terminal result и сами полностью
валидируются. Existing canonical evidence сохраняет все строки в audit_id order.

Примеры section 8 дают достаточный минимальный маршрут Gate 2 через public
application, writer и migration seams: repeated/revoked denial, конкурентный
request key, failure audit outcomes, populated-v2 preservation/repeat/conflict,
direct invalid DTO zero-SQL и safe diagnostic. Расширять его speculative matrix
не требуется.

После точного исправления двух active-transaction outcomes нужен новый exact-hash
Gate 1 review. Этот verdict не затрагивает уже APPROVED DATA-INTEGRITY v2 и не
является test/code/combined-command/launch approval.
