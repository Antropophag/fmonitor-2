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

### Requirement: Production safe log fail-closed construction
`AssignmentOrderOriginalProductionConfig` SHALL require `safeLogFile` in addition to `privateStorageRoot` and `tablePrefix`. Before any database access or private-storage validation/access, `ProductionAssignmentOrderOriginalFactory` MUST resolve and validate `safeLogFile` as an already existing canonical regular file, MUST reject a symlink at the configured path, MUST require ownership by the current effective user and exact permission mode `0600`, and MUST bind the real append-only cleanup/release safe-log observer to that canonical file. The retained append descriptor MUST have exact device/inode identity with the final non-following pathname observation and MUST itself be revalidated by `fstat` as regular, current-effective-user-owned and exact `0600` before resource access or write. Any open, `fstat`, identity or attribute mismatch MUST close an opened descriptor and surface only the existing fixed redacted production-configuration error. The factory MUST NOT create, chmod, chown, replace, truncate, append to or otherwise repair/change the file on construction failure. This descriptor-integrity clarification is pending fresh independent technical Gate 1 review and preserves the earlier owner-approved policy/history.

Verification SHALL использовать public opaque opened-file owner и pure attribute
policy из `ASSIGNMENT-ORDER-ORIGINAL-SAFE-LOG-OWNER-001`. Stable-file behavioral
GREEN MUST дополняться exact-SHA independent structural Gate5 proof native fstat,
retained ownership/close и factory ordering. Pending construction observer и
interval permission transition заменены; эти отвергнутые механизмы MUST NOT
повторяться. Public raw-handle/adoption/opener/metadata-provider escape и
production selector запрещены.

#### Scenario: Valid production safe-log binding
- **WHEN** `safeLogFile` names an existing non-symlink regular file owned by the current effective user with exact mode `0600`
- **THEN** factory construction may proceed to database/private-storage dependencies and cleanup/release diagnostics append exactly one canonical JSON line per emitted event without truncating prior bytes

#### Scenario: Invalid production safe-log fails before resources
- **WHEN** the configured path is missing, non-canonical, a symlink, not a regular file, owned by another user or has mode other than exact `0600`
- **THEN** factory construction throws one fixed fail-closed construction error before database access and before private-storage validation/access, does not create or repair any file, and exposes no configured path, secret or underlying exception detail

#### Scenario: Stable direct owner and policy verification
- **WHEN** public owner получает обычные task-owned изначально valid0600 и invalid0640 files, а pure policy получает independently fixed mode/type/UID/device/inode cases
- **THEN** valid owner сохраняет prior bytes и exact JSON append/close behavior; invalid input отклоняется fixed-redacted без create/repair; tests не заявляют, что stable-file observation сама доказывает использование fstat

#### Scenario: Structural retained-descriptor proof
- **WHEN** independent Gate5 reviewer проверяет exact implementation SHA после approved behavioral GREEN
- **THEN** actual fstat retained handle подаёт все approved policy fields, private owner один выполняет append/close без reopen/adoption escape, все failed acquisitions закрывают handle, production factory ordering/fixed exception сохраняются
- **AND** отсутствие этой proof запрещает APPROVED даже при passing tests; rejected native/observer mechanisms не реализуются

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
- **THEN** version-2 manifest exact tables/columns/keys/FKs/checks/equivalence и literal fixture projections/digests определяют independent expected values, `cleanupExampleA` удаляет только byte-validated fixture rows, а test удаляет только separately validated task-owned database
- **AND** version 2 replaces only v1 UNIQUE(private_content_identity) with non-unique INDEX, preserves populated rows, permits same-content revisions, reports revisions affected, repeats exact, and conflicts on every other drift before DDL
- **AND** classifier finds sole safe-name v1 unique or exact `idx_aoou_revision_content` v2 index; manifest walk upgrades v1 by one atomic drop+add ALTER at revisions position with post-ALTER phase/re-read/retry v1-or-v2 recovery, then creates suffix and appends capability last; clean/roots+v1-partial/full-populated-v1/mixed-drift/v2-repeat affected orders are exact

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

#### Scenario: Barrier event selectable only by verifier
- **WHEN** worker config выбирает fingerprint-miss или after-private-finalize lifecycle event
- **THEN** READY/RELEASE блокирует только exact selected event; CAS races use first; isolated full INITIAL request0400 fixture (case4512/order81/actor18/date09-01/confirmed/null lineage/canonical PDF/lease-race.pdf/application-pdf) clock07:00/root0040/revision0040 uses second and exact content-sha256 identity; maintenance clock09/principal/cutoff07:30/limit10/null cursor request0401 while paused proves PARTIAL/LOCKED 1/0/1/0, after exact accepted upload request0402 proves referenced COMPLETED 1/0/1/0, both request/audits + one upload fact and blob retention via fresh reader; invalid config pre-secret, production selector absent

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

### Requirement: Shared safe-log owner не маскирует unavailable dependency или close failure

Exact file mode SHALL проверяться mask07777, включая запрет special bits.
Owner close SHALL кешировать success/failure после одной native попытки;
closed-state MUST NOT означать ложное подтверждение kernel close при failure.
Existing direct Runtime imports SHALL предоставлять owner/policy без autoloader.

#### Scenario: Direct imports перед policy negatives
- **WHEN** verifier использует existing Runtime/FileStorage direct imports
- **THEN** оба новых класса уже loaded и valid-owner control проходит до invalid-input assertions; class-not-found не считается policy denial

#### Scenario: Повтор после native close failure
- **WHEN** первая close попытка возвращает false/warning/Throwable
- **THEN** owner permanently unusable, fixed failure кешируется; repeated close не делает I/O и повторяет fixed error, destructor не выпускает исключение

### Requirement: Diagnostic failure isolation

Application composition SHALL соблюдать SAFE-LOG-ISOLATION-001: diagnostic
Throwable не меняет selected Result, cleanup, required audit или delivery.

#### Scenario: Logger throws while reporting cleanup failure
- **WHEN** cleanup/release failure требует diagnostic и record throws
- **THEN** underlying record attempted exactly once; selected result и remaining cleanup/audit/delivery сохраняются

#### Scenario: Request binding throws before context update
- **WHEN** request-aware logger useRequest throws
- **THEN** command lifecycle продолжается, record callbacks этой invocation отсутствуют; следующая invocation снова пытается bind exact request

### Requirement: Exact scalar boundary precedes replay

Application SHALL соблюдать COMMAND-SHAPE-001 до business ports: exact Gregorian
date, UTF-8/raw controls, Unicode trim/code-point limits и opaque ASCII1..80 IDs.
Accepted correction SHALL сохранять normalized reason; filename не выбирает path.

#### Scenario: Malformed metadata with stored terminal request
- **WHEN** request ID существует, но filename/reason/date/lineage нарушает scalar shape
- **THEN** INVALID_COMMAND, evidence null, business lookup/read/audit calls0 и stream close1; stored result не раскрывается

#### Scenario: Unicode boundary and generated opaque identity
- **WHEN** normalized reason содержит500 valid code points либо source возвращает malformed GENERATED ID
- **THEN** valid reason достигает normal correction и сохраняется normalized; malformed generated ID даёт retryable PERSISTENCE_FAILURE до finalize/commit

## Scalar boundary v0.2 schema alignment — 2026-09-06

Opaque IDs retain1..80 printable ASCII bytes but exclude slash/backslash under
the existing schema CHECK. Caller and GENERATED negatives cover both separators;
no identity rewrite/escaping is substituted. v0.1 Gate1 rejection is preserved;
fresh v0.2 independent Gate1 precedes any shape RED.

### Requirement: Once-only command resource lifecycle

Application SHALL соблюдать COMMAND-LIFECYCLE-001: primitive-specific failures,
actual ordered observer phases, malformed read/finalize rejection, once-only
cleanup/release and post-commit response loss without false no-fact failure.

#### Scenario: Replay and acquisition failure
- **WHEN** stored request replay либо acquired-stage failure/replay выбирает outcome
- **THEN** supplied stream closes once unread for terminal replay; staged outcomes attempt abort/close/stream-close without skips/repetition; selected nonaccepted result сохраняется

#### Scenario: Committed response observer fails
- **WHEN** post-commit lifecycle/delivery callback throws after lease release attempt
- **THEN** fixed ResponseDeliveryLost without Result, durable facts unchanged, no resource/commit retry; next authorized same-request call replays

### Requirement: Full PDF history and exact lexical names

Inspector SHALL соблюдать PDF-HISTORY-001: all selected revisions/object-stream
members are scanned, exact decoded Name tokens are distinguished from literal
bytes, and opaque image/content payloads are not structurally decompressed.

#### Scenario: Older or unreachable active dictionary
- **WHEN** selected historical/unreachable dictionary contains an exact forbidden Name
- **THEN** UNSAFE_PDF even when current page graph is passive; benign history remains allowed

#### Scenario: Name-looking data and opaque images
- **WHEN** valid metadata contains marker text in strings/comments or distinct prefix Names, or a correctly framed supported opaque image filter
- **THEN** no false active-name match or structural-only filter rejection; actual active tokens/invalid framing still fail closed

### Requirement: Total persistence values and genuine fresh recovery

The command and MariaDB adapters SHALL obey DATA-INTEGRITY-001's closed lookup,
complete lineage, immutable historical evidence and pre-SQL validation contracts.
Fresh recovery SHALL use its owned new connection and never reuse the writer.

#### Scenario: Corrupt stored evidence or contradictory port value
- **WHEN** a FOUND result has invalid identity/evidence/backing or its status and payload contradict
- **THEN** fail typed persistence unavailable without repair, new mutation or false replay

#### Scenario: Historical request after correction
- **WHEN** an authorized earlier accepted request is retried after a newer correction
- **THEN** replay the earlier request's own validated revision, independently of the current root pointer

#### Scenario: Unknown commit with unusable writer
- **WHEN** commit outcome cannot be confirmed and the borrowed write connection is unusable
- **THEN** perform one owned fresh read; validated found/miss/unavailable selects the exact stored/failure/unknown outcome and closes once

DATA-INTEGRITY v0.2 treats stored INVALID_COMMAND as unavailable, requires only
original denial-audit presence without selecting repeated-denial cardinality,
and pins authoritative composition locking/NO_CHANGES, separate storage clocks
and actual worker safe-log acquisition before secret/DB access. No product audit
policy is decided. The previous v0.1 Gate1 rejection remains immutable.

DATA-INTEGRITY v0.3 reserves generic commit CONFLICT for actual root/current/
unique-winner states resolvable by fingerprint/lineage. Authoritative composition
change/disappearance is confirmed ROLLED_BACK, with no invented business reason.
Initial CONFLICT plus fingerprint/lineage misses is PERSISTENCE_FAILURE, never
false INITIAL_ALREADY_EXISTS; correction NO_CHANGES reread remains exact.
