## Context

См. `proposal.md` и delta-spec. Текущий workflow связывает opening с manual `prepared → registered`; owner truth уже перешла на подписанный PDF. Этот change намеренно заканчивается на secure original evidence seam. Применение состава и opening будут отдельными changes, чтобы каждый Gate 2 доказывал одну acceptance statement.

## Goals / Non-Goals

**Goals:** один application owner для initial/correction, exact process authorization, deterministic PDF security boundary, immutable lineage, semantic idempotency и zero-public-orphan failures.

**Non-Goals:** HTTP/upload route, metadata-read/download, менять composition/opening, моделировать sequential-order ties, OCR/signature/malware inspection, интегрировать 1С ДО, удалять historical registration facts или добавлять domain logic в `rapid-pilot/`.

## Decisions

### 1. Contract supersession — prerequisite Gate 1, не implementation tail

Canonical `CONTEXT.md`, pilot spec и pilot data model уже синхронизированы с owner-approved original-PDF truth. До executable spec оставшиеся активные interface/inventory/dependent changes/tests получают explicit disposition: target requirements amended; tests текущей реализации сохраняются как legacy characterization либо блокируются predecessor-ом и не считаются target acceptance; historical review/evidence records не редактируются. Найденные families: `docs/installation-process-interface.md`, `docs/operations/pilot-behavior-inventory.md`, active `pilot-e2e-rbac-fixtures`, `pilot-e2e-combined-pdf`, registration/opening application specs/tests и downstream characterization, использующая historical registered crew. Opening implementation остаётся predecessor для `open-installation-from-assignment-order-original`; этот slice не может объявить end-to-end pilot journey GREEN.

### 2. Один command с двумя modes и optimistic revision

Assignment Orders владеет `submitAssignmentOrderOriginal(Command): Result`. `INITIAL` создаёт root identity и отдельную revision identity; `CORRECTION` передаёт root, target revision и expected current revision. DTO/result/reason codes нормативно закрыты executable spec. Composition берётся production query по order identity, не доверяется HTTP payload.
Production composition identity derives from order/version and its canonical
case/engineer/numeric-sorted installer JSON; Example A SHA-256 is `388c7d94...`,
with no caller, legacy-slot or fixed-hash fallback.
Reader selects exact order `id,installation_case_id,version_no,
control_engineer_user_id,order_date` plus every member's order/id/action/
valid-from/to in one snapshot. Physical `order_date` is compatibility
`template_date`, not documentDate. All rows are validated before exclusion:
positive IDs unique across every action and valid ordered dates; assign/retain
must cover order date and are included, release must have non-null end no later
than order date and is excluded. Any invalid row or empty included set fails the
composition.

Отдельные update-file/update-date endpoints отвергнуты: они допускают partial/mutable history. Sequential composition intentionally отвергается `SEMANTIC_COLLISION` до отдельного slice.

### 3. Exact capability grants

Новые exact strings `assignment_order.original.upload` и `.correct` добавляются в process capabilities. Bootstrap/administration явно выдаёт обе пользователям builtin technical role codes `fkr_operator` и `manager`; `manager` сохраняет code и получает display «Руководитель ФКР». Runtime seam проверяет explicit user capability row плюс active user/role. Ни `assignment_order.prepare`, ни legacy role/name не являются fallback. Future HTTP slice вводит отдельный exact local/read permission `assignment_order.original.read`; он не выводится из upload/correct.

### 4. Deterministic PDF policy

Caller adapter передаёт stream; application/storage boundary считает received bytes и прекращает чтение после `20,971,520 + 1`. MIME/magic — быстрый prefilter. Production использует owned `FMonitorPassivePdfInspector` algorithm `fmonitor-passive-pdf-v1` с literal grammar/bounds/active-key set executable spec; TCPDF 6.11.4 остаётся renderer и не валидирует input. Algorithm version меняется только новым Gate 1/2.

### 5. Storage/persistence publication protocol

Document storage finalizes private content и возвращает typed lease из общего с maintenance digest exclusion domain. Lease held через commit/rollback, fresh unknown-outcome lookup и, для CAS `CONFLICT`, через обязательные fingerprint/current-lineage rereads. После выбранного conflict outcome release вызывается exactly once; failure не заменяет outcome, safe-log-ится и оставляет token storage recovery. Non-replay conflict attempt-audit выполняется даже при release failure. Private blob без DB row не public/applicable; maintenance/retry работают только через тот же exclusion domain.
Invalid-path abort/close failures preserve its selected Result and emit exact
one-event/one-phase safe logs with first12-SHA256 request correlation. Accepted
path closes stage then stream before commit; failure selects STORAGE/STREAM,
forbids commit, retains private orphan and releases lease rolled_back. Invalid
cleanup order is abort→stage-close→stream-close; a throwing injected safe-log
observer proves best-effort no-retry behavior without worker composite faults.
Canonical request301 invalid-inspector run fixes exact request/audit/blob/log
JSON, full abort→close→audit transcript, empty-log throwing variant and
authorization+terminal-lookup-only replay inventories.
Verification worker has three one-shot real-repository unknown-outcome scripts:
durable+FOUND, rollback+NOT_FOUND and durable+UNAVAILABLE followed by normal
same-request replay; production binds none and exposes no selector.
Four exact combined scripts add one release failure after rollback or each
unknown branch; plain release fault covers committed and natural CAS-conflict,
so arbitrary multi-fault configuration remains forbidden.
Five exact verification publisher scripts cover zero-byte serialization,
oversize, false and zero outcomes plus one seven-byte short prefix; all preserve
the committed result for normal same-request replay and production binds none.
Production maintenance factory binds one trusted exact system-principal/
reconcile-capability DTO from deployment composition, separate from user roles
and impossible to select from request payload or mutable global.
A verification-only orphan fixture uses the same production private-storage
validation/primitives/locks to create exact timestamped abandoned/finalized
candidates without DB facts, sleep, private metadata edits or production selector.
Task root has an exact marker/token and must be disjoint from configured
production root; evidence fixes create/replay/failure inventories and separate
boundary/newer maintenance request/audit/result runs.
Maintenance cursor is unpadded canonical base64url of exact versioned JSON
timestamp/identity pair; it remains a valid exclusive position after deletion.

### 6. Idempotency и correction ties

После shape/authorization request-ID hit возвращает stored result до stream read и владеет retry identity. При miss stream hash позволяет accepted-operation fingerprint lookup; затем идут expected-current, target-current и no-change checks. Раздельные root/current/target revision IDs делают `STALE_REVISION` и `TARGET_NOT_CURRENT` наблюдаемыми. CAS даёт одного winner; upload time не разрешает tie.
Distinct fingerprint replay echoes the current loser request ID, copies winner
evidence and persists no loser request/audit/event; identical-race inventory
therefore contains only the winner fact, while different-race conflict remains
terminal and audited.
Fingerprint bytes are exact concatenated 4-byte big-endian lengths plus raw
member bytes; initial/correction worked digests are fixed independently.

### 7. Persistence owner и schema direction

Additive canonical migration вводит immutable root/revision identities, terminal request results, semantic fingerprint, one-current-leaf CAS, composition hash, dates, digest/size/storage identity, actor и reason. Rejected/conflict result и safe audit сохраняются атомарно; retryable failures не становятся terminal request hits. Existing registration facts не переписываются. Literal migration version назначается по актуальному frontier.
Schema v2 forward-upgrades only revisions private-content key from unique to
non-unique index, preserving v1 rows so same-content immutable revisions share
one content-addressed identity and reference lookup remains existential.
The manifest walk classifies a sole safe-name v1 unique versus exact named v2
index, performs one atomic drop+add ALTER at revisions position, observes and
revalidates it, then creates any suffix and publishes capability last. Retry
accepts only atomic v1/v2; all other mixed drift conflicts before DDL.

### 8. HTTP/read/download boundary отложен

Отдельный future change `expose-assignment-order-original-http` обязан определить exact methods/routes/status/body, multipart limits, CSRF/session, local permissions, metadata DTO, actor/reason/filename visibility, not-found/forbidden indistinguishability, download authorization/digest/headers. Текущий command slice не создаёт query или HTTP surface.

### 9. Architecture impact

Разрешённые зависимости текущего slice: verifier/caller → public application seam; application → authorization/composition/repository/storage/clock ports; MariaDB/filesystem/parser adapters → ports. Future HTTP/rapid-pilot adapter сможет зависеть только от того же seam. Запрещены direct HTTP writes, runtime DDL, public web storage и opening/composition mutation. `make architecture-check` должен закрепить границы.

### 10. Gate 2 constructibility API

Four worker channels are distinct non-stdio AF_UNIX SOCK_STREAM endpoints from
separate socketpairs, validated by integer range and fstat identity before
secret/command access; directions remain logical over full-duplex sockets.
Final worker config selects exactly fingerprint-miss or after-private-finalize
barrier event. The latter causally proves upload-held lease against real
maintenance LOCKED, then referenced retention after accepted commit.

Executable spec v5 фиксирует namespace `FMonitor2\AssignmentOrderOriginal`, typed application/DTO/result/stream/auth/composition/clock/ID/PDF/staged-storage/repository/observer/evidence/maintenance contracts. Upload и maintenance имеют отдельные production/verification factories и exhaustive dependency bundles; maintenance authorizer принимает string system principal. Production связывает real inspector/private storage/no-op lifecycle/storage/delivery observers и real file safe-log observer, не выбирает verifier composition по environment/request/CLI/global.

Production command construction получает обязательный `safeLogFile` через `AssignmentOrderOriginalProductionConfig`. Самым первым resource-sensitive шагом factory канонизирует путь и доказывает, что configured entry уже существует, сама не symlink, является regular file, принадлежит effective current user и имеет exact mode `0600`. Real safe-log observer связывает retained append descriptor с final non-following pathname observation по exact device/inode и до DB/private-storage/write повторно проверяет descriptor `fstat`: regular, effective-current-user owned, exact `0600`. Любое open/stat/identity/attribute несоответствие закрывает открытый descriptor и даёт один fixed redacted construction exception; factory не создаёт, не заменяет, не chmod/chown, не очищает и не append-ит файл на construction failure. После успешной проверки observer сохраняет cleanup/release diagnostics, не включая path, secret или exception detail. Descriptor-integrity clarification pending fresh independent technical Gate 1 review; прежние owner approvals/history сохраняются. Worker/evidence-reader `safeLogFile` остаётся отдельным ранее утверждённым serializable config contract и не подменяет production-config field.

Gate 2 descriptor-integrity proof остаётся на real production factory. Private verification-only native interposer в bounded task-owned child меняет pathname metadata только exact synthetic fixture: сохраняет real device/inode, но сообщает regular/current-EUID/`0600`, тогда как retained descriptor real `fstat` имеет другой mode. Unchanged control run и отдельные setup assertions доказывают harness; expected failure фиксирует exact factory error, zero DB calls/private-root touch, unchanged safe-log bytes/metadata и отсутствие retained descriptor. Interposer недоступен production config/environment/request/CLI/global/service locator; probabilistic loops запрещены, cleanup ограничен revalidated task-owned child/library/fixture artifacts.

Request replay после authorization предшествует order/clock/stream. New-request semantic replay требует completed staged bytes/hash. Current composition drift относительно root snapshot делает collision наблюдаемым без caller composition. Repository принимает typed accepted/attempt commit DTOs, использует `READ COMMITTED`, unique request/fingerprint и CAS current revision. Worker bootstrap получает serializable config path с exact `safeLogFile` и пять dedicated FDs; real safe-log observer пишет только в этот pre-created owned `0600` file, а evidence reader того же run читает ту же canonical path identity; parent закрывает reader и terminate/reap children до revalidated task-owned safe-log cleanup. Worker DSN has exact ordered `host;port;database;charset=utf8mb4` key-value grammar mapped only to mysqli plus separately bounded user/password file and fails before secret/resource access. Every controlled exit 70 writes one exact fixed stderr line, zero result bytes before the sole write primitive, a discarded bounded prefix only on short write, and empty-or-already-READY-only barrier output. Root/revision verifier IDs use bounded unique exact-token CSV, independent left-to-right consumption and typed runtime exhaustion. Command transport has one literal fixture, bounded chunks through final-LF+bounded-EOF before UTF8/JSON/key/base64 validation, and `fromBase64` as sole decoder; all parsing precedes password/DB/application/barrier. Result IPC uses an exact 11-key JSON/LF encoding with literal accepted/replayed/conflict lines and pre-write size/serialization validation, one complete-line fwrite, and parent-side rejection of any bounded short prefix. Barrier имеет READY/RELEASE protocol после fingerprint miss до CAS. Private orphan maintenance получает typed candidate pages/cursor/digest locks/reference recheck/delete и atomic terminal result+audit; upload получает typed finalized-content lease и не releases его до terminal DB/unknown resolution.

### 11. Independent production evidence construction

Gate 2 MariaDB/CAS/fault evidence строится только через public
`AssignmentOrderOriginalEvidenceReaderFactory` и serializable config с exact DB
connection fields, password-file, prefix, private root и safe-log file. Factory
владеет fresh read-only connection/descriptors и explicit idempotent close;
reader знает canonical original tables напрямую, не использует
`information_schema`, private SQL из теста, command repository или test
callbacks. Это делает requests/fingerprints/domain/events/audits/process/blob/log
snapshots независимыми и одновременно не создаёт второй mutation seam.
Separate maintenance request/audit canonical methods expose atomic terminal
persistence and replay stability without direct verifier SQL.
Closed `aoou-process-v1` shape содержит отдельный `checklistSha256`
для canonical checklist identities/availability exact case/order; он не
выводится из `tasksSha256`, чтобы no-mutation matrix была
sensitivity-testable без private SQL в verifier.
Config grammar, pre-access path ownership/mode checks, no-create safe-log policy
и optional-final-LF + post-strip ASCII `0x20..0x7E` password parsing exact; construction/read/close totality
выражена одним fixed `AssignmentOrderOriginalEvidenceUnavailable`, а close
attempts all resources once и кэширует success/failure для idempotent repeats.

### 12. Gate 2 database setup ownership

Real MariaDB RED вызывает named public
`AssignmentOrderOriginalSchemaMigration::apply` version 1; runtime paths её не
вызывают. Verification-only deterministic `seedExampleA` владеет
только idempotent prerequisite DML и не создаёт original facts; evidence
reader остаётся fresh/read-only и не получает fixture dependency.
Version-1 manifest нормативно фиксирует seven owned tables,
structural equivalence и clean/repeat/leading-partial/populated/conflict
outcomes. Fixture имеет literal rows/projection digests, serializable
all-or-nothing seed и reverse byte-validated cleanup; после этого test
удаляет только validated task-owned database, не prefix tables.
MariaDB non-deferrable FK cycle не создаётся: revision ссылается
на root, а root current-leaf same-root invariant владеется atomic repository
transaction/CAS. Per-table UUID/opaque/status/reason/retry/evidence/count CHECK
truth sets закрыты без символических conditional FK.
Checklist evidence выводится только из target case opening fields,
а decoy evidence — из прочих case rows изолированного prefix;
original acceptance не входит ни в один digest input.
Capability CHECK classifier accepts only exact V4 or exact V5 and distinguishes
the engineer-position constraint. Migration publishes V5 last, after full
seven-table revalidation. Implicit-commit failures leave only recoverable exact
leading partial/full-schema+V4 states; a verification-only phase factory proves
the boundary without a production runtime selector. Observer phases include
every durable table CREATE and both sides of final capability ALTER. A
post-ALTER fresh classifier resolves durable V5 before return; unavailable
reread leaves only full-exact-schema+V4-or-V5 safe retry.

## Risks / Trade-offs

- [DB/filesystem не имеют общей транзакции] → private finalize до DB commit; private orphan не observable, bounded reconciliation/reuse; stored accepted result разрешает response-loss retry.
- [Production cleanup/release diagnostics могут потеряться при неверной конфигурации или pathname race] → обязательный pre-created owned regular `0600` safe log и реально retained descriptor проверяются fail-closed до DB/private storage/write, identity связывается по device/inode и используется только append-only; auto-create/repair запрещены.
- [Parser bugs] → pinned production parser, adversarial fixtures и fail-closed unsupported/active/encrypted behavior.
- [Active truth amended раньше code] → documents явно маркируют original upload как planned and opening replacement as future, чтобы не выдавать незавершённый journey за GREEN.
- [Role `manager` получает mutation] → только два exact new capabilities, no wildcard/inheritance, independent RBAC RED.
- [Correction document date позже/раньше иных orders] → этот slice хранит evidence lineage, но не применяет её к composition/opening; applicability решает следующий change.

## Migration Plan

1. Coherent supersede active manual-number/registration truth, сохранить historical records; strict validate и fresh independent planning rereview.
2. После constructibility amendment получить fresh independent Gate 1 review и новый owner exact-hash approval; прежний v1 approval сохраняется исторически и не разрешает Gate 2 по v3.
3. Продемонстрировать migration/fixture RED, получить fresh independent Gate 3, реализовать только setup GREEN и получить его fresh Gate 5.
4. На Gate-5-approved setup продемонстрировать full command/MariaDB/worker/fault RED и получить fresh independent Gate 3.
5. Добавить storage/parser/repository adapters и один command минимальным GREEN.
6. Выполнить focused tests, architecture-check, `make verify`, fresh independent code review.
7. Только после Done создать через отдельные propose workflows `expose-assignment-order-original-http`, `apply-assignment-order-original-to-composition`, затем `open-installation-from-assignment-order-original` по их зависимостям.

Rollback до production facts отключает route/composition. После появления facts rollback только forward-compatible: bytes/revisions/audit не удаляются.

## Open Questions

Нет. Applicability к составу и opening не являются вопросами этого change, а явно отложены в отдельные lifecycle slices.
