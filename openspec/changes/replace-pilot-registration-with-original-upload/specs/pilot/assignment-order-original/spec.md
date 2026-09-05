## Purpose

Определяет безопасный application-command контракт приёма и append-only исправления одного подписанного PDF-оригинала распоряжения без ручного номера и регистрации.

## ADDED Requirements

### Requirement: Один public command и точные capabilities
Система SHALL предоставлять один public application command `submitAssignmentOrderOriginal`, различающий mode `INITIAL` и `CORRECTION`. `INITIAL` MUST требовать exact process capability `assignment_order.original.upload`, `CORRECTION` MUST требовать `assignment_order.original.correct`. Bootstrap/administration policy SHALL выдавать обе process capabilities пользователям active builtin role codes `fkr_operator` и `manager`; display name `manager` меняется на «Руководитель ФКР», но technical code сохраняется. Runtime command MUST проверять active user/role и explicit user capability row, а не выводить доступ из role/display name, legacy rights или другого capability. HTTP/local RBAC не входит в этот slice.

#### Scenario: Разрешённый initial upload
- **WHEN** active actor имеет exact process capability `assignment_order.original.upload`, явно выданный по approved bootstrap/administration mapping для active `fkr_operator` или `manager`
- **THEN** command допускается к последующим проверкам

#### Scenario: Разрешённое исправление
- **WHEN** active actor имеет exact process capability `assignment_order.original.correct`, явно выданный по approved bootstrap/administration mapping для active `fkr_operator` или `manager`
- **THEN** correction допускается к последующим проверкам

#### Scenario: Fail-closed authorization
- **WHEN** user/role inactive, exact capability отсутствует либо доступ основан только на display/legacy/другом permission
- **THEN** система возвращает `REJECTED/AUTHORIZATION_DENIED` без чтения upload stream и без domain/storage mutation

#### Scenario: Retry после отзыва authorization
- **WHEN** request ранее accepted, но actor больше не authorized
- **THEN** authorization check предшествует request lookup и возвращает `AUTHORIZATION_DENIED` без раскрытия stored result

### Requirement: Закрытый command DTO
`submitAssignmentOrderOriginal` SHALL принимать immutable DTO: `requestId`, `mode`, `installationCaseId`, `assignmentOrderId`, `actorUserId`, `documentDate`, boolean `compositionConfirmed`, nullable `rootOriginalId`, `targetRevisionId`, `expectedCurrentRevisionId`, nullable `correctionReason`, и ровно один upload descriptor `{stream, originalFilename, declaredMediaType}`. `INITIAL` MUST иметь null lineage/revision/reason fields; `CORRECTION` MUST указывать все три lineage/revision identities и непустую trimmed reason. Composition выбирается ранее и читается command-ом по `assignmentOrderId`; caller SHALL NOT передавать произвольный composition snapshot.

Execution order MUST быть: shape → authorization → terminal request lookup → order/current-composition lookup → clock/confirmation/future-date → bounded stream/MIME/magic → PDF inspector → accepted fingerprint → lineage/current/target/no-change → private finalize → commit/CAS → verifier delivery observer. Future date и composition drift не читают stream; request replay не вызывает order/clock/stream.

#### Scenario: Невалидная shape mode
- **WHEN** initial содержит correction fields либо correction не содержит target/reason/exact expected revision
- **THEN** система возвращает `REJECTED/INVALID_COMMAND` без чтения stream и mutation

#### Scenario: Подтверждение состава
- **WHEN** valid boolean `compositionConfirmed=false` либо order не содержит минимум одного монтажника и ровно одного инженера
- **THEN** система возвращает `REJECTED/COMPOSITION_NOT_CONFIRMED` или `REJECTED/INVALID_COMPOSITION` без accepted original

### Requirement: Exact PDF и byte policy
Система SHALL считать limit по received bytes до любой трансформации; допустимы размеры `1..20,971,520` bytes включительно. После MIME/magic prefilter owned inspector `FMonitorPassivePdfInspector` algorithm `fmonitor-passive-pdf-v1` MUST разобрать PDF 1.4..1.7, xref/Prev/object graph и object streams в exact bounds executable spec; TCPDF 6.11.4 является только renderer. Inspector MUST отклонять encrypted/password-protected, malformed/truncated, zero-page/page-less, unsupported/ambiguous structure и active content PDF. Проверка подписи, печати, OCR и malware scan не входят в этот slice.

#### Scenario: Валидный многостраничный PDF на границе
- **WHEN** один parseable passive PDF имеет от одной page и ровно `20,971,520` received bytes
- **THEN** файл проходит PDF/size boundary

#### Scenario: Превышение на один byte
- **WHEN** stream содержит `20,971,521` received bytes
- **THEN** чтение прекращается bounded образом и результат равен `REJECTED/FILE_TOO_LARGE` без public orphan

#### Scenario: Опасный или невалидный PDF
- **WHEN** MIME/magic не соответствуют PDF, parser не разбирает документ, документ encrypted/password-protected, не имеет pages или содержит запрещённый active content
- **THEN** система возвращает соответственно `REJECTED/NOT_PDF`, `REJECTED/INVALID_PDF` или `REJECTED/UNSAFE_PDF` без accepted fact

### Requirement: Дата документа и upload time различны
`documentDate` SHALL быть подтверждённой actor календарной датой из оригинала в timezone `Europe/Moscow`; production clock задаёт `serverToday` и immutable UTC `uploadedAt`. Date позже `serverToday` MUST быть отклонена. Template path предлагает remembered generation date, direct path предлагает `serverToday`, но public command всегда получает явное подтверждённое значение и SHALL NOT выводить его из `uploadedAt`.

#### Scenario: Прошлая дата загруженного сегодня оригинала
- **WHEN** допустимый PDF имеет `documentDate < serverToday`
- **THEN** accepted fact хранит переданную date и отдельный UTC `uploadedAt`

#### Scenario: Будущая дата
- **WHEN** `documentDate > serverToday` в `Europe/Moscow`
- **THEN** система возвращает `REJECTED/FUTURE_DOCUMENT_DATE` без accepted fact

### Requirement: Result DTO и stable outcomes
Command SHALL возвращать DTO `{status, reasonCode, retryable, requestId, rootOriginalId, currentRevisionId, revisionNumber, documentDate, sha256, byteSize, uploadedAt}`. `status` MUST быть одним из `ACCEPTED`, `REPLAYED`, `REJECTED`, `CONFLICT`, `FAILED`. Для `ACCEPTED`/`REPLAYED`: `reasonCode=null`, `retryable=false`, все evidence fields обязательны. Для `REJECTED`: `retryable=false`, evidence fields null, reason — `AUTHORIZATION_DENIED`, `INVALID_COMMAND`, `ORDER_NOT_FOUND`, `COMPOSITION_NOT_CONFIRMED`, `INVALID_COMPOSITION`, `FILE_TOO_LARGE`, `NOT_PDF`, `INVALID_PDF`, `UNSAFE_PDF`, `FUTURE_DOCUMENT_DATE` или `NO_CHANGES`. Для `CONFLICT`: `retryable=false`, evidence fields null, reason — `SEMANTIC_COLLISION`, `STALE_REVISION`, `TARGET_NOT_FOUND`, `TARGET_NOT_CURRENT` или `INITIAL_ALREADY_EXISTS`. Для `FAILED`: evidence fields null, `retryable=true`, reason — `STREAM_FAILURE`, `STORAGE_FAILURE`, `PERSISTENCE_FAILURE` или `PERSISTENCE_OUTCOME_UNKNOWN`.

#### Scenario: Accepted result
- **WHEN** initial или correction полностью принята
- **THEN** result возвращает immutable accepted evidence и `reasonCode=null`

#### Scenario: Rejected attempt audit
- **WHEN** authenticated/authorized command проходит admission, но отклоняется business/file validation
- **THEN** immutable security audit записывает request identity, actor, case/order, status/reason и time без filename/file bytes/document content; этот audit не является original domain fact

#### Scenario: Technical failure result
- **WHEN** private storage или persistence не могут завершить operation и accepted outcome не доказан
- **THEN** command возвращает exact `FAILED` mapping с `retryable=true` и не изображает business rejection

### Requirement: Semantic replay и collision
После shape/authorization checks система MUST сначала lookup terminal `requestId` до чтения stream; accepted hit возвращает те же evidence fields со status `REPLAYED`, rejected/conflict hit возвращает исходный terminal status/reason, без payload comparison. При miss система читает/валидирует stream, вычисляет fingerprint из mode, case/order, root/target/expected-current identities, document date, composition identity/hash и PDF SHA-256, затем lookup accepted fingerprint; match возвращает `REPLAYED` даже после смены leaf. Только miss проходит current/stale/no-change validation. Новый intent MUST использовать новый request ID.

Production composition identity SHALL be `composition-<orderId>-v<versionNo>`
and hash SHALL equal SHA-256 exact compact JSON
`{caseId,compositionIdentity,engineerUserId,installers,orderId}` from canonical
order/installers rows with numeric-sorted unique installer IDs. Example A hash
is `388c7d94b3cf91235dabddf26398ac05f754d3d12a0b41a7a91ac3d5370faba5`;
caller/legacy/fixed fallback hashes are forbidden.
Source columns are exact order `id,installation_case_id,version_no,
control_engineer_user_id,order_date` and all exact-order member
`assignment_order_id,installer_tab_id,change_action,valid_from,valid_to` rows in
one read snapshot. Before exclusion, every action row requires matching order,
positive ID unique across all rows, exact known action and valid dates with
`valid_from<=valid_to` when non-null. `assign|retain` is included only when
`valid_from<=order_date` and (`valid_to` null or `>=order_date`); `release`
requires non-null `valid_to<=order_date` and is excluded. Unknown/invalid/
duplicate or empty included set is INVALID_COMPOSITION. Physical `order_date`
is current compatibility source for semantic `template_date`, never documentDate.
Fingerprint tuple encodes each member as unsigned 4-byte big-endian byte length
plus raw bytes, nullable empty as zero length, integers unpadded decimal and no
separators. Example initial/correction preimages are 208/250 bytes with exact
digests `dd356db041181636ce1ecfc619f9055a625d81250e59ad3543c9f5cd5b582a7d`
and `719d1773101e3211fb0857ad8fcb375fac10a5c7e08180491181a93a4e30f91e`.

#### Scenario: Полный semantic replay
- **WHEN** новый request имеет полный fingerprint ранее принятой operation, включая root, target и expected-current revision identities
- **THEN** система возвращает исходный `REPLAYED` result без нового эффекта

#### Scenario: Distinct-request replay эхо и persistence
- **WHEN** loser/new request ID находит accepted fingerprint winner
- **THEN** Result эхо-ирует loser request ID и копирует прочую winner evidence, но не создаёт loser request/audit/event row; повтор loser снова доказывает fingerprint без domain effect

#### Scenario: Identical race literal oracle
- **WHEN** A `...0101` и B `...0102` оба READY, parent releases/observes accepted A до release B
- **THEN** B возвращает exact LF replay line с request B/winner revision-0002 evidence; exact requests inventory содержит только initial+A, а domain/fingerprint/event/audit не содержат B; retry B byte-identical

#### Scenario: Retry принятой correction после смены leaf
- **WHEN** retry имеет тот же request/fingerprint принятой correction, а её target теперь non-current из-за результата самой этой correction
- **THEN** lookup возвращает сохранённый `REPLAYED` result до stale/current checks и не создаёт новую revision

#### Scenario: Изменённый retry старой correction
- **WHEN** новый request/fingerprint не совпадает с принятой operation и expected-current или target revision уже stale/non-current
- **THEN** система возвращает `CONFLICT/STALE_REVISION` либо `CONFLICT/TARGET_NOT_CURRENT` без mutation

#### Scenario: Та же evidence с другой correction reason
- **WHEN** correction меняет только reason, но PDF/date/composition/target evidence совпадают с current revision
- **THEN** система возвращает `REJECTED/NO_CHANGES` без новой revision или audit success event

#### Scenario: Same bytes с новой датой
- **WHEN** mode `CORRECTION` имеет тот же PDF SHA-256, новую допустимую document date, current target/revision и непустую reason
- **THEN** система принимает новую append-only revision

#### Scenario: Same bytes с другим составом
- **WHEN** correction order lookup показывает current composition identity/hash, отличные от immutable root snapshot
- **THEN** система до stream read возвращает `CONFLICT/SEMANTIC_COLLISION`; caller не передаёт состав, а смена состава принадлежит future slice

### Requirement: Append-only correction lineage
Initial upload SHALL создать отдельные `rootOriginalId`, `currentRevisionId` и `revisionNumber=1`. Correction SHALL указать root, target revision и expected current revision; accepted correction требует совпадения target/expected с actual current и создаёт новый revision ID/number `n+1`. Expected-current mismatch даёт `STALE_REVISION`; при совпавшем expected current, но другом target того же root достигается `TARGET_NOT_CURRENT`. Старые metadata, bytes и audit неизменяемы; correction SHALL NOT менять case/order/composition.

#### Scenario: Успешное исправление
- **WHEN** authorized correction указывает current leaf/exact revision, непустую reason и меняет PDF или date
- **THEN** система создаёт одну revision `n+1`, связывает её с prior revision и сохраняет prior evidence byte-identical

#### Scenario: Stale или не-current target
- **WHEN** `expectedCurrentRevisionId` отличается от actual current либо при совпавшем expected current `targetRevisionId` указывает другую revision того же root
- **THEN** система возвращает `CONFLICT/STALE_REVISION` или `CONFLICT/TARGET_NOT_CURRENT` без candidate publication

#### Scenario: Unknown target
- **WHEN** expected-current совпадает, но opaque target revision не существует
- **THEN** система возвращает `CONFLICT/TARGET_NOT_FOUND`; target другого root даёт `SEMANTIC_COLLISION`

#### Scenario: Две конкурентные corrections
- **WHEN** две разные corrections используют один root, target и expected-current revision IDs
- **THEN** ровно одна создаёт `n+1`, loser получает `CONFLICT/STALE_REVISION`, смешанного состояния нет

#### Scenario: Deterministic two-worker barrier
- **WHEN** два verifier workers через shared MariaDB/storage достигают named barrier после fingerprint miss и до CAS
- **THEN** каждый пишет отдельный READY, parent отпускает обоих exact RELEASE только после двух READY, waits bounded 5 seconds, а malformed/EOF/timeout не допускает commit

#### Scenario: Attempt-audit failure atomicity
- **WHEN** valid-shape rejected/conflict outcome не может атомарно сохранить terminal request result и safe attempt audit
- **THEN** система возвращает `FAILED/PERSISTENCE_FAILURE`, не сохраняет terminal/domain result и разрешает retry; retryable storage/stream failures не становятся terminal request hits

### Requirement: Storage, commit и response-loss safety
Storage SHALL начать private stage до чтения stream; application читает chunks максимум `65536`, считает received bytes/hash и пишет каждый chunk в owned stage. EOF даёт bytes real/injected inspector-у; rejection/failure вызывает typed abort/close, passive PDF — private content-addressed finalize до typed DB commit. Storage adapter emits exact stage/write/abort/finalize/lock/delete events. Finalized blob не public/applicable без committed row. DB transaction принимает immutable typed commit DTO, атомарно записывает revision, terminal result, fingerprint, event и audit; opaque transaction JSON запрещён.

#### Scenario: Persistence failure после staging
- **WHEN** repository commit завершается ошибкой после staging candidate
- **THEN** command возвращает `FAILED/PERSISTENCE_FAILURE`, original fact отсутствует, а finalized blob остаётся private orphan для bounded reconciliation/reuse

#### Scenario: Stream, invalid input и storage различаются
- **WHEN** stream unreadable/incomplete, либо completed bytes не проходят PDF policy, либо staging/private finalize падает
- **THEN** outcomes соответственно `FAILED/STREAM_FAILURE`, `REJECTED/NOT_PDF|INVALID_PDF|UNSAFE_PDF`, `FAILED/STORAGE_FAILURE`; accepted fact отсутствует

#### Scenario: Explicit orphan reconciliation owner
- **WHEN** system principal с exact `assignment_order.original.storage.reconcile` вызывает bounded maintenance seam с cutoff не моложе часа и batch `1..1000`
- **THEN** seam под digest lock удаляет только повторно доказанные unreferenced private blobs, сохраняет append-only maintenance result и не меняет domain facts

#### Scenario: Upload lease исключает maintenance delete
- **WHEN** initial/correction finalize либо reuse возвращает verified private content и upload ещё не разрешил DB commit/rollback/unknown outcome
- **THEN** typed content lease остаётся held в том же digest exclusion domain, maintenance видит `LOCKED` и не может удалить blob; release выполняется ровно один раз после terminal resolution

#### Scenario: Lease release failure безопасен
- **WHEN** release после accepted commit возвращает `FAILED` или бросает исключение
- **THEN** durable accepted result не заменяется failure, safe cleanup failure записывается без identity/path и storage-owned recovery сохраняет blob; до commit lease-acquisition failure даёт `FAILED/STORAGE_FAILURE` без repository commit

#### Scenario: CAS loser release после reconciliation reads
- **WHEN** `commitAccepted` возвращает `CONFLICT`
- **THEN** lease остаётся held через обязательные fingerprint и current-lineage rereads, затем release вызывается exactly once до return/attempt-audit; release failure не заменяет выбранный `REPLAYED`, exact `CONFLICT/*` или `FAILED/PERSISTENCE_FAILURE`, логируется safe exact once и оставляет exclusion token storage recovery

#### Scenario: Maintenance outcome matrix
- **WHEN** maintenance command invalid/unauthorized, полностью успешен, replayed, имеет locked/per-item storage failures или repository unavailable
- **THEN** exact outcomes равны `REJECTED/INVALID_COMMAND|AUTHORIZATION_DENIED`, `COMPLETED`, `REPLAYED`, `PARTIAL/LOCKED|STORAGE_FAILURE`, `FAILED/PERSISTENCE_FAILURE` с exact counts/cursor/retryable executable contract

#### Scenario: Constructible maintenance composition
- **WHEN** production или verification собирает maintenance application
- **THEN** dedicated factory получает string-principal authorizer, clock, candidate-page/digest-lock/delete storage, reference repository, atomic maintenance request/result/audit repository, observers/faults/log; production получает trusted exact principal+reconcile-capability DTO, не user grant, и не выбирает verifier dependencies/authorization по request runtime input

#### Scenario: Production maintenance authorization exact
- **WHEN** configured `test-maintenance-01` requests exact reconcile capability
- **THEN** byte-equal pair ALLOWED; any principal/capability mismatch DENIED, invalid config throws fixed pre-resource error, не создаёт user capability row и не выводится из role/request/global

#### Scenario: Eligible orphan fixture deterministic
- **WHEN** verifier создаёт canonical abandoned/finalized orphan с timestamp `2026-09-02T07:00:00Z`
- **THEN** verification-only fixture использует same production storage validation/primitives/locks, exact owned-root marker/token+injected clock и disjointness с configured production root, fixed conflict/unavailable precedence, exact 15/19 bytes/digest и pre/post blob inventories, metadata time не mtime; real-adapter maintenance factory с clock 09:00, cutoff 07:30 даёт ordered two-candidate COMPLETED 2/2/0/0, exact request/audit JSON и stable replay; separate exact boundary 07:30:00/newer 07:30:01/future/primitive-failure runs наблюдаемы без sleep/private edits/production selector

#### Scenario: Maintenance cursor exact
- **WHEN** batch 1 завершается на `(07:00:00Z,orphan-content-0001)`
- **THEN** `nextCursor` равен exact 83-byte base64url literal versioned JSON pair; next request ищет strictly after pair без requirement её existence; invalid version/shape/alphabet/padding/re-encode/pair даёт INVALID_COMMAND before clock/candidates

#### Scenario: Commit success и обычный ответ
- **WHEN** private blob finalized, DB commit accepted revision/result/audit и process может вернуть response
- **THEN** command возвращает `ACCEPTED`; blob и immutable fact согласованы по digest/size

#### Scenario: Commit outcome неоднозначен
- **WHEN** connection теряется во время commit и fresh lookup по `requestId` не может доказать accepted или absent outcome
- **THEN** command возвращает `FAILED/PERSISTENCE_OUTCOME_UNKNOWN`; retryable caller MUST повторить тот же request identity, а система не создаёт второй effect

#### Scenario: Commit success с потерей ответа
- **WHEN** DB commit успешен, но response теряется до caller
- **THEN** повтор того же request проходит precedence lookup и возвращает `REPLAYED` с сохранённым accepted result без повторного stream/storage/domain effect

#### Scenario: Commit точно отсутствует после ambiguous failure
- **WHEN** retry lookup тем же request доказывает отсутствие accepted result
- **THEN** command может заново проверить/reuse verified private blob и выполнить одну новую commit attempt; итогом остаётся не более одного accepted fact

#### Scenario: Real unknown-outcome scripts constructible
- **WHEN** verification worker выбирает one `commit_unknown_found|not_found|unavailable`
- **THEN** real repository выполняет ровно durable-commit+fresh-FOUND, rollback+fresh-NOT_FOUND или durable-commit+fresh-UNAVAILABLE; script one-shot, production не может его выбрать, unavailable retry normally replays durable row

#### Scenario: Release failure сочетается с commit outcome
- **WHEN** worker выбирает exact `commit_before|commit_unknown_{found,not_found,unavailable}_release_failure`
- **THEN** base outcome выполняется один раз, затем one release FAILED с exact rolled_back/unknown_* safe log без замены Result; plain release fault покрывает committed и natural CAS-conflict, arbitrary fault lists forbidden

#### Scenario: Cleanup safe logs exact
- **WHEN** stage abort, stage close или stream close fails
- **THEN** non-accepted selected Result preserved; accepted candidate close uses precommit failure mapping; one exact event + only phase field + first12-SHA256(requestId) correlation logged; isolated canonical JSON lines заданы, payload/path/exception forbidden, injected observer log-write failure no retry/no Result change

#### Scenario: Cleanup failure precedence constructible
- **WHEN** invalid path cleanup or valid accepted-candidate close fails
- **THEN** invalid path keeps selected result and audits only after abort→stage-close→stream-close; accepted path closes stage→stream before commit, failure selects STORAGE/STREAM failure, forbids commit, retains private orphan and releases lease rolled_back; injected throwing safe-log observer writes nothing/no retry
- **AND** request301 invalid-inspector abort-failure run has exact request/audit/blob/log JSON, ordered call transcript, empty-log throwing variant and authorization+terminal-lookup-only byte-identical retry

### Requirement: Independent evidence reader constructible through public factory
Verification SHALL строить fresh-connection production evidence reader только
через `AssignmentOrderOriginalEvidenceReaderFactory::create` и exact serializable
config с DB host/port/name/user/password-file, canonical prefix, private root и
safe-log file. Reader SHALL выполнять только read-only canonical evidence reads,
не SHALL использовать `information_schema`, private SQL/test callbacks или
command repository и SHALL закрывать connection/descriptors exactly once.
Config SHALL иметь exact bounded ASCII scalar grammar, canonical owned
`0700|0750` private root, existing owned `0600` password/safe-log files, SHALL
не создавать/repair paths и SHALL принимать password только как `1..1024`
bytes exact ASCII `0x20..0x7E` с одним optional final LF, удаляемым до проверки.
TAB, DEL, non-ASCII и другие newline MUST отклоняться. Construction/read/close failure
MUST бросать только fixed `AssignmentOrderOriginalEvidenceUnavailable` без
partial JSON/diagnostics; repeated close MUST не повторять I/O и сохранять
первый cached outcome.

#### Scenario: Isolated MariaDB setup конструируем
- **WHEN** Gate 2 готовит task-owned database/prefix для real repository/reader/worker evidence
- **THEN** public `AssignmentOrderOriginalSchemaMigration::apply` version 1 создаёт/проверяет только owned original schema с exact `APPLIED|UNCHANGED|CONFLICT`, а verification-only `AssignmentOrderOriginalVerificationDatabaseFixture::seedExampleA` idempotently добавляет fixed prerequisites без original facts; runtime consumers не вызывают migration/fixture

#### Scenario: Fixture conflict fail closed
- **WHEN** Example-A prerequisite identity уже занята другими values
- **THEN** fixture до DML бросает fixed `AssignmentOrderOriginalVerificationFixtureConflict`, не принимает SQL/callback и не создаёт original evidence

#### Scenario: Exact schema и bounded cleanup наблюдаемы
- **WHEN** Gate 2 проверяет clean/repeat/partial/populated/conflict migration и завершает Example-A run
- **THEN** version-1 manifest exact tables/columns/keys/FKs/checks/equivalence и literal fixture projections/digests определяют independent expected values, `cleanupExampleA` удаляет только byte-validated fixture rows, а test удаляет только separately validated task-owned database

#### Scenario: Capability publication fail closed
- **WHEN** original schema incomplete/fails/revalidation differs, observer fails after exact schema, capability CHECK is ambiguous/non-exact, or final ALTER fails
- **THEN** V5 upload/correct grants are never published before full exact schema; per-created-table and pre/post-capability phases make implicit-commit failures deterministic; conflicts return binary-complete `affectedTables`, technical failures throw exact fixed migration-unavailable, and post-ALTER fresh reread resolves durable V5 or leaves only safe full-schema+V4-or-V5 unknown recovery

#### Scenario: Shared MariaDB evidence independently observable
- **WHEN** Gate 2 выполняет upload/replay/CAS/fault/maintenance через real production adapters
- **THEN** новый reader на fresh connection возвращает closed canonical requests/fingerprints/domain/events/audits/maintenance-requests/maintenance-audits/process/blob/log snapshots, где process shape содержит отдельные `tasksSha256` и `checklistSha256`, maintenance terminal result+audit появляются atomic-or-neither и replay их не меняет, а после `close()` reader не оставляет ресурсов

#### Scenario: Checklist availability наблюдается отдельно
- **WHEN** verifier сравнивает process snapshot до и после command attempt
- **THEN** exact `aoou-process-v1` shape содержит `checklistSha256`, выведенный только из target case opening state (`available` только при `working` + all opening fields, иначе `blocked_until_opening`); valid target всегда даёт ровно одну case-owned checklist identity, missing/mismatched case/order даёт fixed evidence-unavailable без partial JSON; original/order/task facts не являются inputs и `tasksSha256` не заменяет этот digest

#### Scenario: Unrelated decoy facts наблюдаемы
- **WHEN** reader строит `decoySha256` для exact target case
- **THEN** digest покрывает every other prefixed installation-case `{caseId,processState-as-marker}` в numeric case order, включая empty projection, без original tables и fixture callback

#### Scenario: Evidence config invalid or read fails
- **WHEN** config/path/password-file/prefix invalid либо evidence read/close падает
- **THEN** factory/reader бросает exact fixed evidence-unavailable exception без partial JSON, DDL/DML, schema inference или secret/path diagnostics; missing safe-log не создаётся

#### Scenario: Worker и evidence reader имеют одну safe-log identity
- **WHEN** verification запускает five-FD worker для real commit/release fault evidence
- **THEN** exact serializable worker config содержит `safeLogFile`, worker валидирует его как заранее созданный owned `0600` canonical regular file по тем же path rules, пишет в него через real safe-log observer, а reader config использует ту же canonical path identity; worker не создаёт/repair file и не выбирает path через env/global/default/private-root convention/callback, а parent закрывает reader и terminate/reap children до repeat-validation и удаления только task-owned safe-log artifact

#### Scenario: Worker DSN однозначно строит mysqli
- **WHEN** five-FD worker читает exact `host=...;port=...;database=...;charset=utf8mb4`
- **THEN** bounded grammar однозначно строит `(host,user,password,database,port)` и `set_charset('utf8mb4')`; host есть либо colon/bracket-free hostname/IPv4 token, либо balanced canonical lower-case IPv6 с filter+inet round-trip; `p:`, raw colon, bad brackets/zone/trailing-dot, invalid/extra/reordered/socket/alternate-charset input даёт exit 70 + fixed stderr до secret/DB/storage/log access

#### Scenario: Worker failure channels exact
- **WHEN** worker завершается controlled exit 70
- **THEN** stderr ровно `ASSIGNMENT_ORDER_ORIGINAL_WORKER_FAILED\n`; result FD пуст для every pre-write failure, а one result-fwrite short failure может оставить только bounded discarded prefix; invalid config не читает command и не пишет barrier, а barrier failure после READY сохраняет только уже записанный exact READY без новых bytes

#### Scenario: Worker FD ownership exact
- **WHEN** bootstrap получает command/barrier-in/barrier-out/result FDs
- **THEN** each 3..65535 из separate AF_UNIX SOCK_STREAM pair, integers и `(dev,ino)` pairwise distinct, opened once r+ and blocking; stdio/FIFO/file/device/closed/dup/alias/range invalid pre-secret/pre-command с close-once+exit70, logical opposite directions unused

#### Scenario: Worker ID sequences deterministic
- **WHEN** config передаёт root/revision CSV
- **THEN** each имеет `1..1024` unique exact `original-NNNN`/`revision-NNNN` tokens without whitespace/empty/trailing values, валидируется pre-secret, потребляется left-to-right only on requested kind; exhaustion даёт command `FAILED/PERSISTENCE_FAILURE`, а canonical identical/different race sequences фиксированы executable spec

#### Scenario: Worker command JSON/base64 exact
- **WHEN** command FD получает one bounded UTF-8 JSON line
- **THEN** exact literal fixture и ordered top-level/upload keys с `upload.bytesBase64` и strict canonical RFC4648 round-trip строят Command/stream; read идёт chunks <=65536 в buffer <=29000000, final LF требует EOF <=5s, затем UTF8/JSON/keys/base64 и sole decode factory, всё до password/DB; malformed/extra/second-line/overlong input даёт exit70 fixed channels до application/storage/log/barrier, empty bytes доходят до file validation, а decoded 20MiB+1 — до `FILE_TOO_LARGE`

#### Scenario: Worker result JSON exact
- **WHEN** worker публикует command Result
- **THEN** all 11 keys идут exact order, lower backed enums/explicit nulls/JSON booleans/unquoted integers и fixed JSON flags с one LF; accepted/replayed/stale literals заданы, <=16384 checked before write; serialization/oversize даёт zero bytes, one complete-line fwrite short/failure не retry-ит write, parent отбрасывает bounded prefix unless exact LF+EOF line complete, committed request остаётся replayable

#### Scenario: Result publisher faults deterministic
- **WHEN** verification worker выбирает serialization/oversize/write-false/write-zero/write-short-7
- **THEN** first four пишут zero result bytes, short пишет ровно `{"statu` one attempt, all stderr+exit70 и normal same-request retry REPLAYED; production и arbitrary combinations forbidden

### Requirement: Scope boundary следующего lifecycle
Принятый original SHALL NOT в этом slice менять current assignment composition, case state, actual start или checklist availability. Sequential-order applicability/ties принадлежат будущему change `apply-assignment-order-original-to-composition`; замена opening gate и immutable opening snapshot принадлежат `open-installation-from-assignment-order-original`; HTTP upload, metadata-read и download принадлежат `expose-assignment-order-original-http`, где exact local read capability SHALL быть `assignment_order.original.read` и не SHALL наследоваться из upload/correct/display role.

#### Scenario: Upload не открывает и не применяет состав
- **WHEN** initial или correction принята
- **THEN** изменяется только private original evidence persistence, а composition и opening facts остаются byte-identical; query/HTTP surface не создаётся
