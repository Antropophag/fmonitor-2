# ASSIGNMENT-ORDER-ORIGINAL-ATTEMPT-AUDIT-001

Версия: 0.1. Статус: DRAFT / требуется независимый технический Gate1.

## Простыми словами

Каждая попытка загрузки или исправления оригинала без нужных прав остаётся в
журнале, даже если пользователь повторяет тот же запрос. Ошибки чтения файла и
хранилища тоже пытаются оставить безопасную запись. Ранее принятый оригинал и
результат запроса никогда не переписываются. Экран журнала этим slice не меняется.

## 1. Полномочия, scope и precedence

Owner policy: `docs/operations/original-denied-attempt-owner-approval-2026-09-06.md`.
Наследуются ORIGINAL-UPLOAD v72, DATA-INTEGRITY v0.7, COMMAND-LIFECYCLE и
SAFE-LOG-ISOLATION; этот contract уточняет только original audit persistence,
соответствующую схему и diagnostics. До GREEN прежний runtime — predecessor.
Единственный application mutator остаётся `submitAssignmentOrderOriginal`.

Shape → exact authorization → authorized terminal lookup → composition → clock →
file/remaining original command сохраняется. Invalid shape: stream close1,
auth/clock/lookup/audit0. Authorization UNAVAILABLE/Throwable: retryable
PERSISTENCE_FAILURE, lookup/audit/clock0. Denied: terminal/fingerprint/lineage/
composition lookup0, file read0, stream close1. Writer не раскрывает terminal data.

Allowed terminal hit: прежний immutable replay, clock/audit0. Allowed fresh
business rejection/conflict: прежний `commitAttempt` atomic terminal+audit,
не новая audit-only запись. Accepted/replayed и stored denial после grant
сохраняют прежние результаты. Время — один lazy validated UTC instant invocation,
включая допустимый epoch; actor — caller actorUserId, а не владелец старого result.

## 2. Public audit port

Namespace `FMonitor2\AssignmentOrderOriginal`. Exact declarations:

```php
enum AssignmentOrderOriginalAuditWriteStatus: string {
    case COMMITTED = 'committed';
    case ROLLED_BACK = 'rolled_back';
    case OUTCOME_UNKNOWN = 'outcome_unknown';
}
final readonly class AssignmentOrderOriginalSafeAttemptAudit {
    public function __construct(
        public string $requestId,
        public int $actorUserId,
        public AssignmentOrderOriginalMode $mode,
        public int $installationCaseId,
        public int $assignmentOrderId,
        public AssignmentOrderOriginalStatus $status,
        public AssignmentOrderOriginalReason $reason,
        public string $attemptedAtUtc,
    ) {}
}
interface AssignmentOrderOriginalAttemptAuditWriter {
    public function recordDenied(AssignmentOrderOriginalSafeAttemptAudit $audit): AssignmentOrderOriginalAuditWriteStatus;
    public function appendFailure(AssignmentOrderOriginalSafeAttemptAudit $audit): AssignmentOrderOriginalAuditWriteStatus;
}
```

DTO passive; writer validates before observer/escaping/SQL. UUID/positive IDs/
UTC exact как DATA-INTEGRITY. `recordDenied` принимает только
REJECTED/AUTHORIZATION_DENIED. `appendFailure` только FAILED/STREAM_FAILURE либо
FAILED/STORAGE_FAILURE. Остальное возвращает ROLLED_BACK без SQL. Никаких PDF,
имён, путей, correctionReason и произвольного JSON в DTO нет.

Dependencies получает после existing optional freshTerminalReaders optional
`?AssignmentOrderOriginalAttemptAuditWriter $attemptAudits = null`; одноимённое
public readonly property всегда содержит writer. Default unavailable writer
возвращает ROLLED_BACK и не пишет. Это явно degraded compatibility, не launch
composition. Production и real verification factories связывают real writer с
той же borrowed mysqli/prefix; caller-owned transaction он не commit/rollback-ит.

`AssignmentOrderOriginalMariaDbAttemptAuditWriter` имеет public constructor
`(\mysqli $connection, string $tablePrefix = '',
?AssignmentOrderOriginalPersistenceObserver $observer = null)` и реализует port.
Production не принимает observer/request-selected writer.

## 3. Каждая denied invocation

После unread-stream cleanup получить clock один раз, если ещё не получен.
Clock unavailable: FAILED/PERSISTENCE_FAILURE, audit writer0. Иначе один вызов
recordDenied. COMMITTED → REJECTED/AUTHORIZATION_DENIED, все evidence null.
ROLLED_BACK → FAILED/PERSISTENCE_FAILURE. OUTCOME_UNKNOWN/Throwable →
FAILED/PERSISTENCE_OUTCOME_UNKNOWN. Failure results retryable=true; никакого
confidential terminal/fresh-result lookup или повторного audit write нет.

RecordDenied владеет одной короткой READ COMMITTED transaction. Он пытается
вставить обычный terminal denial result. Если ровно request primary key уже
занят, сохраняет эту запись byte-identical и продолжает; это не бизнес replay.
Любая другая SQL ошибка откатывает transaction. Затем вставляет ровно одну новую
safe audit row и commit. Existing terminal lookup ради result запрещён;
request-key collision обрабатывается внутри adapter, не в application.
Конкурирующий request-key INSERT упорядочивается самой БД. При подтверждённом
commit каждая invocation добавила1 audit; новый request также получил1 terminal.
При rollback ни terminal, ни audit этой invocation не сохраняются.

Native commit false/Throwable или postcommit observer failure = OUTCOME_UNKNOWN;
rollback failure = OUTCOME_UNKNOWN. Before-commit failure + confirmed rollback =
ROLLED_BACK. Используются existing BEFORE_WRITE_BEGIN/BEFORE_NATIVE_COMMIT/
AFTER_NATIVE_COMMIT/BEFORE_WRITE_ROLLBACK observer events, только verification.
Повтор при неизвестном исходе — новая попытка пользователя и новая audit row;
writer не делает автоматического повтора. Это не обещание audit при недоступной БД.

## 4. Retryable file/storage failures

После завершения once-only cleanup вызвать appendFailure ровно один раз с
выбранным FAILED/STREAM_FAILURE или FAILED/STORAGE_FAILURE и captured clock.
Если clock ещё не получен — попытаться получить один instant; при его ошибке
writer0. Любой исход аудита сохраняет выбранный file/storage failure и
retryable=true; terminal/root/revision/event/fingerprint не создаются. Успешный
appendFailure вставляет только audit в собственной short transaction.

PERSISTENCE_FAILURE/PERSISTENCE_OUTCOME_UNKNOWN не обещают DB audit; writer0.
Audit-only failures не расширяют закрытый `AssignmentOrderOriginalAttemptCommit`.

## 5. Безопасная диагностика

Все новые события используют existing guarded safeLog с first12 SHA256(requestId)
correlation. `safeFields` содержит ровно один ключ `phase`.

| Событие | Когда | phase |
|---|---|---|
| ASSIGNMENT_ORDER_ORIGINAL_ATTEMPT_AUDIT_FAILED | denied writer не COMMITTED | denial |
| ASSIGNMENT_ORDER_ORIGINAL_ATTEMPT_AUDIT_FAILED | file/storage audit не COMMITTED либо clock недоступен | file_failure |
| ASSIGNMENT_ORDER_ORIGINAL_ATTEMPT_AUDIT_FAILED | ordinary terminal attempt persistence/recovery не подтвердило terminal | terminal |
| ASSIGNMENT_ORDER_ORIGINAL_PERSISTENCE_FAILED | прочий PERSISTENCE_FAILURE | submission |
| ASSIGNMENT_ORDER_ORIGINAL_PERSISTENCE_UNKNOWN | прочий PERSISTENCE_OUTCOME_UNKNOWN | submission |

Ровно один новый diagnostic на invocation, если соответствующее условие достигнуто;
audit diagnostic заменяет generic persistence diagnostic для того же исхода.
Existing primitive cleanup diagnostics отдельны и сохраняют прежние counts.
Logger Throwable не меняет result и не повторяет logger/cleanup/audit/commit.
Denied clock failure использует generic PERSISTENCE_FAILED. Invalid shape не
получает этих diagnostics. Никаких raw exception/SQL/filename/path/bytes/имён.

## 6. Forward schema v3 и canonical deployment

Владение DDL — `FMonitor2\InstallationProcess\OriginalAttemptAuditSchemaMigration`.
Public `apply(\mysqli $connection, string $tablePrefix = ''): array` возвращает
`['applied'=>bool,'reason'=>null|'SCHEMA_MIGRATION_CONFLICT']`; unavailable бросает
existing InstallationProcess DatabaseUnavailable без native details.
Canonical frontier подтверждён 2026-09-06: bin runner1..12; selection spec явно
не резервирует13, remote integration75a6424 неизменён. Этот slice резервирует
family original v3 как version13; при смене frontier требуется новый Gate1.
Migration13 не меняет ранее зарегистрированные migrations1..12.

Original schema v2 — exact семья из parent v72 / существующего public
AssignmentOrderOriginalSchemaMigration. V3 отличается только audit table:

- все columns/types/defaults/auto_increment остаются byte-equivalent;
- UNIQUE `(request_id,status,reason_code)` заменяется неуникальным index с теми же
  ordered columns и exact name `idx_aoou_attempt_request`;
- единственный FK audit.request_id→original_requests.request_id удаляется;
- status CHECK расширяется accepted/rejected/conflict/failed;
- status/reason CHECK сохраняет все прежние пары, добавляя только
  `status='failed' AND reason_code IN ('stream_failure','storage_failure')`;
- другие два audit CHECK (UUID/mode), primary audit_id и case/order/audit index
  неизменны. Все остальные таблицы, строки, ключи и grants прежние.

Снятие constraints и замена двух CHECK выполняются одним atomic ALTER; имена
старых constraints читаются из metadata только после exact semantic matching и
проверяются safe identifier grammar. No data UPDATE/DELETE/backfill. Любой иной
schema drift = conflict до DDL. Все prefix0..25 сохраняются.

Migration владеет named lock SHA256(database + byte00 + prefix + byte00 + ASCII original-audit-v3),
не дольше5 секунд ожидания; failed lock/release — unavailable, lock released в
finally. Не начинать DDL внутри active caller transaction. Пустая семья и exact
leading partial v2 проходят прежний public v2 migration после его whole-family
preflight, затем v3 upgrade. Полная v3 распознаётся до v2 call и repeat ничего
не изменяет. Full v2 upgrade сохраняет каждый прежний audit_id и строку. После
ALTER обязательно fresh metadata revalidation; failure не публикует success,
повтор принимает только целую v2 либо целую v3. Downgrade после новых audit rows
запрещён; rollback приложения только forward-compatible.

Verification-only `OriginalAttemptAuditSchemaMigrationVerification::apply`
принимает те же connection/prefix и третьим обязательным argument
`OriginalAttemptAuditSchemaObserver $observer`. Интерфейс observe получает
`OriginalAttemptAuditSchemaPhase` с BEFORE_AUDIT_ALTER/AFTER_AUDIT_ALTER.
Production apply всегда связывает no-op. AFTER_AUDIT_ALTER callback failure
возвращается как unavailable; следующий запуск распознаёт durable v3 без DDL.

## 7. Read compatibility

Stored terminal reads сохраняют DATA-INTEGRITY проверки original backing.
Отсутствие request при наличии только valid FAILED STREAM/STORAGE audit больше
не является corruption; эти audit rows проверяются по полному DTO и request echo,
затем result NOT_FOUND. Любой другой orphan audit остаётся UNAVAILABLE.

Accepted/rejected/conflict terminal требует прежний original matching audit;
дополнительные valid denial и failure rows не заменяют это доказательство и не
портят его. Для terminal denial original matching row определяется по request
metadata/actor/mode/case/order/time; требуется минимум1, а не единственность.
Повторные denial могут иметь другой actor/time и сохраняются как отдельные строки.
Все дополнительные rows валидируются по exact grammar и request echo.

Existing evidence reader `safeAuditsCanonicalJson` сохраняет schema/keys/order и
показывает все строки в audit_id order; допускает две новые failed reason pairs.
Новых read grants или HTTP route не вводится.

## 8. Независимые примеры и минимальный RED

Общие literals: request `00000000-0000-4000-8000-000000000701`, case4512/order81,
actor18, modeINITIAL. Времена T1=2026-09-06T09:00:00Z,
T2=2026-09-06T09:01:00Z. При fresh migrated schema audit IDs начинаются1.

1. Первый denied T1 → denial terminal1/audit1. Повтор denied T2 → тот же terminal
   byte-identical, audit2 с T2, result denial, reads/stream0. Grant затем replay
   stored denial, clock/audit0. Пример повторить для CORRECTION exact shape.
2. Public accepted initial другого request создаёт прежние evidence. Revoke,
   denial тем же request → accepted terminal/evidence unchanged, новая denial row.
   Restore → прежний accepted replay. Никаких privileged lookups при denial.
3. Параллельные denied одного request → один terminal, две audit rows. Denial vs
   accepted → at most1 terminal; уже committed terminal не переписывается,
   denied invocation добавляет1 audit и не получает accepted evidence.
4. Каждый denied writer outcome и Throwable/clock failure даёт exact result из
   §3 и diagnostic из §5, без повторных reads/writes/cleanup.
5. STREAM/STORAGE failure × audit COMMITTED/ROLLED_BACK/UNKNOWN/Throwable →
   выбранный failure неизменен; audit success1 либо unknown/absent, terminal0;
   retry может дойти до ordinary accepted command. Existing cleanup oracles reuse.
6. Migration clean/repeat/populated-v2 upgrade/leading partial/other drift и
   before/after ALTER interruption сохраняют историю и не дают ложного success.
   Canonical runner13 clean/repeat, v12→13 и prefix25; untouched v2 fixture APIs
   сохраняют прежние результаты на своих v2 databases. V3 не выдаётся за v2.
7. Direct writer malformed DTO → no SQL/observer; real recordDenied existing
   accepted request и appendFailure без terminal доказывают actual schema/port.
   Read controls покрывают valid extra rows и malformed/orphan nonfailure row.
8. Generic persistence diagnostics и throwing logger проверяются на public seam
   с reused original failures; one safe event без payload и повторных effects.

Gate1 exact spec→минимальный публичный RED→independent Gate3→minimal GREEN→
affected checks + architecture→independent Gate5. Existing test expectations
меняются только точным отдельно рассмотренным patch после mismatch evidence.
Полный VERIFY_OK, combined command, HTTP и launch остаются отдельными gates.

Exact verification declarations в InstallationProcess: enum OriginalAttemptAuditSchemaPhase:string с BEFORE_AUDIT_ALTER=before_audit_alter и AFTER_AUDIT_ALTER=after_audit_alter; interface OriginalAttemptAuditSchemaObserver::observe(OriginalAttemptAuditSchemaPhase $phase):void; final OriginalAttemptAuditSchemaMigrationVerification::apply(\mysqli $connection,string $tablePrefix,OriginalAttemptAuditSchemaObserver $observer):array. Fixed unavailable message: `Original attempt audit schema unavailable.`. Production/verification methods static.
