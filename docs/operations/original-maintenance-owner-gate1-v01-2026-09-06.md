# Независимый Gate 1 review: ORIGINAL-MAINTENANCE-001 v0.1

- Дата review: `2026-09-06`
- Reviewer: отдельно назначенный agent `/root/data_transport_gate1`
- Specification SHA-256: `b6c172418590ac6ce28da581dd71260c692b0ebd22e3e800e13ad23914e3d433`
- Parent ORIGINAL-UPLOAD v74 SHA-256: `c98405ee3ef5506e42b25e220ecd87976ca0bdd552f742f59e9ddc44a1e1ed21`
- OpenSpec proposal SHA-256: `dc9d0107fa957bdc56bc4494f8abca3b76a89678863f5e9091987bfea6f190ae`
- OpenSpec design SHA-256: `273608023da81ba19a1030f6c2bb3168d964094d98aef1572d41580d32b75cc6`
- OpenSpec tasks SHA-256: `40e5122dfcc7fbdefd65ff4df2b7f5e00f2af058ef05ede3090319132cd7f515`
- OpenSpec delta SHA-256: `19cdc655b8f2a6bc0bd9737be206aafec496527171f8c9d77c36589025a57396`
- Verdict: **APPROVED**

Reviewer не писал specification, tests или production code. Review ограничен
maintenance/storage slice; существующие owner approvals не переоткрывались.

## Coherence и public behavior

Контракт сохраняет один public maintenance application seam и ранее объявленные
DTO/interfaces/factories. Service получает только MaintenanceDependencies; SQL
принадлежит MariaDB adapter, candidate/lock/delete — FileStorage. HTTP, screen,
import и cron не получают domain writes. Hardcoded test principal прямо запрещён
в production composition.

Admission precedence однозначна. Invalid shape не вызывает authorization,
lookup, clock, storage или DB audit. Valid denial не читает terminal/storage и не
раскрывает старый result; один lazy instant передаётся существующему atomic
maintenance terminal port. Это отдельный system-principal contract и не меняет
утверждённую cardinality user original-denial audit.

Authorized lookup предшествует clock. Stored COMPLETED/REJECTED/PARTIAL
валидируется и возвращается REPLAYED с исходными reason, retryable, counters и
cursor; PARTIAL сохраняет retryable=true. Miss вызывает clock один раз. Future
cutoff получает terminal REJECTED; malformed/unavailable clock или page дают
FAILED без ложного audit факта. Эти правила устраняют parent-противоречия про
invalid cursor и replay retryability без новой product policy.

Page protocol закрыт по status/payload, uniqueness, binary order, cutoff/cursor,
size/kind/hash/identity grammar и immutable getter snapshot до первого lock.
Unavailable/malformed page даёт counters0 и audit0, поэтому недоступный inventory
не выдаётся за обработанную порцию.

Каждый item получает один digest lock. Полученный object освобождается один раз в
finally даже при malformed status/getter/identity; release failure только пишет
safe diagnostic и не меняет уже выбранный deleted/retained/failed. Finalized
delete допускается лишь после exact FOUND/false reference; NOT_FOUND здесь не
маскирует unavailable backing. Stage не выполняет reference lookup.

Итоговая формула `scanned=deleted+retained+failed` и приоритет
STORAGE_FAILURE перед LOCKED точны. Page cursor берётся из validated snapshot.
Result+audit выполняются после всех release attempts. Никаких повторных
lock/reference/delete/commit при ошибке нет.

## Storage и transaction ownership

FileStorage extension конструктивна на существующей модели: beginStage должен
захватить stage lock до публикации metadata и удерживать до close/abort;
maintenance использует тот же identity-derived lock. Content lock совпадает с
upload digest lease. Delete принимает только активный lock своего storage
instance и candidate из последнего successful page snapshot, повторно проверяет
kind/time/identity до path access. Чужой/released lock и metadata drift дают
FAILED без filesystem mutation. Last-page binding не требует нового public
handle или path input.

Observer/fault ordering отделяет успешный native lock, фактический delete begin и
подтверждённый delete/absence. DELETE_DONE callback loss не повторяет уже
выполненное удаление. Обычные upload lifecycle events и append-only original
facts остаются неизменными.

Repository имеет полный read-only consistent lookup с matching audit и
caller-transaction isolation. Commit DTO закрыт по status/reason/retry/counts/
cursor/scalars до SQL; active caller transaction даёт ROLLED_BACK без управления
ею. Terminal и audit вставляются одной short transaction с проверкой native
acknowledgements. Collision/rollback/unknown различены без confidential denial
lookup.

При любом non-COMMITTED application возвращает FAILED/PERSISTENCE_FAILURE с уже
наблюдёнными counts/cursor и не утверждает отсутствие файловых эффектов или
возможной terminal row. Retry того же request ID может replay-ить подтверждённый
result. Это физически исполнимо и устраняет прежнее ложное обещание `no row` при
commit acknowledgement uncertainty.

Safe diagnostics имеют fixed event/phase-only payload и один соответствующий
failure на invocation; primitive release diagnostic отдельный. Factory ordering
фиксирует authorization config до resource I/O, затем guarded safe-log и
root/prefix/real ports. Production не получает caller-selected clock/fault/
observer.

## Constructibility и минимальный RED

Actual `MariaDbMaintenanceService` уже подтверждает наличие public result/
maintenance tables seam, но содержит hardcoded principal, direct SQL, неполную
shape/lookup validation и не реализует declared dependencies. Actual
`AssignmentOrderOriginalFileStorage` сохраняет upload state/identity/digest-lock
основу, но public list/lock/delete сейчас отсутствуют. Поэтому новый contract
описывает достижимый vertical correction, а не требует несовместимого API или
второго storage owner.

Заявленный минимальный набор достаточен: public control-flow/closed values,
pagination/delete after vanished cursor, referenced/locked/failure counts,
throwing release/logger/observer, held stage/content exclusion, real atomic
repository/replay/rollback, invalid DTO zero SQL и production/verification
composition. Existing maintenance/lease fixtures переиспользуются; изменение
старых expectations требует отдельного exact patch и Gate3. Расширять matrix не
требуется.

Блокирующих product или technical ambiguities не найдено. OpenSpec notes/tasks
согласованы с executable scope: canonical13/schema v3/grants неизменны, а
maintenance, declaration parity и remaining fixture gaps не объявляются
закрытыми заранее.

**APPROVED** разрешает переход exact v0.1 к RED и независимому Gate 3. Решение не
утверждает tests, implementation, Gate5, full Original command, VERIFY_OK, CI,
deployment или launch readiness.
