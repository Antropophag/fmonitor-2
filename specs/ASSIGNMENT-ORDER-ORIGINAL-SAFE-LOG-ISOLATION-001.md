# ASSIGNMENT-ORDER-ORIGINAL-SAFE-LOG-ISOLATION-001 — ошибка журнала не меняет команду

Версия0.1, 2026-09-06. **DRAFT / INDEPENDENT GATE1 REQUIRED**.

## Простыми словами

Если диагностический журнал недоступен, загрузка оригинала должна сохранить
уже выбранный результат и завершить необходимый cleanup/audit. Ошибка записи
журнала не является причиной повторять cleanup или сообщать failure после
успешного commit. Этот срез исправляет нарушение уже утверждённого best-effort
контракта; новых пользовательских решений не вводит.

## 1. Scope и authority

Public seam — существующий
`AssignmentOrderOriginalApplication::submitAssignmentOrderOriginal(Command): Result`
через production и verification factories. Inherited contract — original-upload
spec: diagnostic failure не заменяет selected Result, не повторяет log attempt
и не отменяет cleanup, required attempt audit или delivery после accepted.
Source audit:
`docs/operations/original-safe-log-best-effort-audit-2026-09-06.md`.

Не меняются authorization, PDF/storage/commit/correction/replay semantics,
physical opened-file owner/config, JSON schema safe log или HTTP workflow.
Используется уже существующий diagnostic observer port, не новый construction
observer/fault selector. Никаких filesystem/permission/native/interval fixtures.

## 2. Total diagnostic boundary

Вся command composition SHALL иметь одну best-effort boundary вокруг supplied
`AssignmentOrderOriginalSafeLogObserver`. Она applies при public Dependencies
construction, поэтому production/verification factory и direct Service callers
получают одинаковое поведение. Constructor arguments, names и public readonly
property type сохраняются. `dependencies.safeLog` может быть guarding adapter,
а не object-identical supplied observer; raw observer наружу через новый API
не предоставляется. Guard не открывает файлы, не пишет DB и не владеет bytes.

Если supplied observer implements `AssignmentOrderOriginalRequestSafeLogObserver`,
каждый invocation один раз вызывает его `useRequest(exact requestId)` до
diagnostics. Generic observer без этого интерфейса не получает нового callback.
Throwable из useRequest не выходит из application seam, не меняет command Result
и не пропускает normal stream/storage lifecycle. Он отключает все дальнейшие
record callbacks только для этой invocation, чтобы не использовать stale request
correlation. Это не fallback path/logger/requestId и не persisted state.

Следующая invocation снова делает один useRequest attempt и может восстановить
logging после успеха. Нет sticky suppression между requests или глобального
набора request IDs. Действуют существующие service/worker isolation границы.

При доступной request context каждый требуемый diagnostic вызывает underlying
`record(event,safeFields)` ровно один раз с неизменными arguments. Throwable
подавляется без retry, второго logger или synthetic success log. Независимый
следующий cleanup failure по-прежнему получает свой отдельный single attempt;
не путать это с повтором одной записи. No safe-log failure становится причиной
повторного abort/close/release, нового commit или пропуска audit/delivery.

Прямой opened owner API продолжает бросать свои fixed ошибки по
SAFE-LOG-OWNER-001; suppression принадлежит application diagnostic boundary,
а не filesystem owner. Нельзя заставлять file owner ложно возвращать успех I/O.

## 3. Result, persistence и exact ordering

| Context | Logging failure | Observable command behavior |
| --- | --- | --- |
| selected REJECTED/INVALID_PDF, stage abort FAILED/Throwable | record throws | rejection/retryable=false сохраняется; abort, stage close, stream close — каждый1; terminal attempt commit1 после cleanup |
| selected REJECTED/INVALID_PDF, stage close throws | record throws | та же rejection; abort1/close1/stream close1, attempt commit1 |
| selected REJECTED/INVALID_PDF, stream close throws | record throws | та же rejection; все cleanup attempts1, attempt commit1 |
| selected ACCEPTED после committed acceptance, content lease release FAILED/Throwable | record throws | весь accepted Result неизменён; release1, diagnostic1, delivery1; no extra abort/close/commit |
| selected non-replay CONFLICT после CAS/current-lineage reads, release FAILED | record throws | conflict/reason сохраняется; release1, diagnostic1, required terminal attempt commit1; delivery0 |
| useRequest throws на valid accepted input | logging context unavailable | нет escape; accepted result, stream/stage close1, accepted commit1/delivery1; record callbacks0 |
| useRequest throws, потом выбран invalid_pdf и cleanup failure | logging context unavailable | normal rejected cleanup/audit завершается; record callbacks0, нет stale-correlation output |

Ни одна строка не ослабляет реальные storage/repository failures. Если required
attempt-audit commit сам не подтверждён, прежний PERSISTENCE_FAILURE сохраняется.
Только diagnostic Throwable изолируется; catch-all вокруг всего command и
возврат заранее выбранного success запрещены.

## 4. Independently fixed examples

Наследуется literal Example A из original-upload spec: passive PDF327 bytes,
SHA256 `4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784`,
case4512/order81/actor18, composition `composition-81-v1`, engineer31,
installers7001/7002, documentDate2026-09-01, clock2026-09-02T09:15:30Z.
Root/revision ID source возвращает original-0001/revision-0001.

Invalid inspector example: request `00000000-0000-4000-8000-000000000301`,
typed inspector INVALID_PDF, one selected cleanup primitive fails, underlying
record throws before emitting any bytes. Expected full Result:
status rejected, reason invalid_pdf, retryable false, same requestId, все7
evidence fields null. Accepted commits0, attempt commits1; event/sole field:
stage abort → ASSIGNMENT_ORDER_ORIGINAL_STAGE_ABORT_FAILED / phase=stage_abort;
stage close → ASSIGNMENT_ORDER_ORIGINAL_STAGE_CLOSE_FAILED / phase=stage_close;
stream close → ASSIGNMENT_ORDER_ORIGINAL_STREAM_CLOSE_FAILED / phase=stream_close.
Каждая invocation использует свежие fixture ports/stream.

Accepted release example: request `00000000-0000-4000-8000-000000000001`,
passive inspector, successful commit, release typed FAILED либо Throwable,
record throws without bytes. Expected full Result:
accepted/null/false, same requestId, original-0001, revision-0001, revision1,
documentDate2026-09-01, above PDF hash/327, uploadedAt2026-09-02T09:15:30Z.
One accepted commit, attempts0, abort0, stage/stream close1, release1, delivery1.
Diagnostic event ASSIGNMENT_ORDER_ORIGINAL_CONTENT_LEASE_RELEASE_FAILED,
sole field phase=committed; underlying record calls1, log bytes empty.

CAS-conflict example: same valid initial input, commitAccepted returns CONFLICT;
accepted-fingerprint reread NOT_FOUND, exact assignment lineage FOUND with
pre-existing root and matching composition. Selected outcome is
CONFLICT/INITIAL_ALREADY_EXISTS, false, same requestId, evidence null. Lease
release FAILED and record Throwable leave this result, release1 and attempt
commit1 intact. No new accepted facts; pre-existing repository evidence unchanged.

Request-binding example: observer initially carries a different old request;
useRequest throws before updating it. Valid command must still produce the
accepted tuple above. A separate invalid_pdf+cleanup-failure variant must produce
the rejected tuple with zero record calls/bytes, not logging under old correlation.
A following fresh request on the same application with recovered useRequest
must again allow its required diagnostic once, proving suppression is per attempt.

## 5. Verification/gates

Tests invoke the real public verification factory/application with independently
fixed in-memory ports. Existing initial fixture types may provide unchanged
authorizer/composition/clock/IDs/stream/metadata; fault ports implement existing
interfaces and count application calls. Expected values never come from a
nonthrowing production run or private methods. No file/DB/native operation is
needed to model a throwing diagnostic dependency.

Mandatory RED must show actual wrong public Result/escape/repeated callbacks or
missing audit/delivery on current Service, not a missing fixture/environment.
Gate3 independently reviews both value and call-order sensitivity. Minimal GREEN
adds the diagnostic guard only; original business failure handling is not broadly
rewritten. Focused original-command/owner/worker regressions and architecture
check follow. Independent Gate5 must cover every application construction path,
per-invocation context reset, no duplicate diagnostics, selected Result and
audit/delivery preservation. Combined original-command Gate5 remains separate
until both shared owner and this correction are reviewed.
