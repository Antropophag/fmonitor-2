# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — безопасный приём оригинала распоряжения

Статус: **v1 OWNER-APPROVED; v4 lease-conflict amendment ожидает fresh Gate 1 review/owner approval**  
Версия: **v4**  
Дата: **2026-09-02**

## Простыми словами

Сотрудник ФКР или Руководитель ФКР передаёт системе один подписанный PDF-оригинал распоряжения. Система проверяет файл, дату и выбранный ранее состав, сохраняет неизменяемое доказательство и позволяет исправить ошибочный файл или дату только новой версией с причиной. Повтор запроса не создаёт дубль, а сбой не оставляет видимого полурезультата.

Этот slice заканчивается на публичной application-команде и private persistence. Он не создаёт HTTP-форму, экран чтения или скачивание, не применяет новый состав и не открывает работы.

## 1. Идентификатор, actors и публичный seam

Specification ID: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001`.

Actors:

- сотрудник ФКР — active user, которому явно назначено process capability `assignment_order.original.upload` и/или `assignment_order.original.correct`;
- Руководитель ФКР — active user существующего technical role code `manager`, отображаемого как «Руководитель ФКР», которому те же capabilities назначаются явно.

Единственный state-changing public seam:

```text
submitAssignmentOrderOriginal(Command): Result
```

Ни controller, ни CLI, ни import, ни filesystem adapter не записывают original facts напрямую. Runtime seam проверяет active user, active role assignment и exact explicit user capability row. Display role name, legacy rights, `assignment_order.prepare`, `assignment_order.confirm_registration`, `installation.open` и любое другое capability не являются fallback.

## 2. Exact command DTO

```text
Command {
  requestId: UUID lower-case canonical string
  mode: INITIAL | CORRECTION
  installationCaseId: positive integer
  assignmentOrderId: positive integer
  actorUserId: positive integer
  documentDate: YYYY-MM-DD
  compositionConfirmed: boolean
  rootOriginalId: null | opaque lineage ID
  targetRevisionId: null | opaque revision ID
  expectedCurrentRevisionId: null | opaque revision ID
  correctionReason: null | UTF-8 string
  upload: {
    stream: single rewind-independent byte stream
    originalFilename: UTF-8 string
    declaredMediaType: string
  }
}
```

`INITIAL` требует null для трёх lineage/revision fields и reason. `CORRECTION` требует root lineage, target revision, отдельно заявленную current revision и reason после Unicode trim длиной `1..500` code points. `compositionConfirmed=false` является корректной shape и возвращает `REJECTED/COMPOSITION_NOT_CONFIRMED`; только отсутствующее/non-boolean поле даёт `INVALID_COMMAND`. NUL/control characters кроме TAB/LF/CR запрещены в reason и filename. `originalFilename` после trim имеет `1..255` code points и не управляет storage path.

Composition не принимается от caller: seam читает immutable snapshot по exact pair `(installationCaseId, assignmentOrderId)`. Требуется минимум один уникальный installer identity и ровно один control engineer identity. `compositionConfirmed=true` означает только человеческое подтверждение соответствия PDF выбранному составу; OCR и проверка подписей не выполняются.

Любая shape/identity ошибка возвращает `REJECTED/INVALID_COMMAND` до чтения upload stream. Несуществующая или не принадлежащая case order identity возвращает `REJECTED/ORDER_NOT_FOUND` с тем же no-mutation contract.

## 3. Exact execution precedence

Единственный literal порядок:

1. проверить DTO shape и safe scalar bounds;
2. проверить active user и active role assignment;
3. для `INITIAL` потребовать exact `assignment_order.original.upload`;
4. для `CORRECTION` потребовать exact `assignment_order.original.correct`;
5. lookup terminal request result; authorized retry accepted outcome даёт `REPLAYED`, rejection/conflict сохраняет status/reason, stream/order/clock не вызываются;
6. lookup exact order и current composition;
7. получить один clock instant, проверить `compositionConfirmed` и future date;
8. bounded stream acquisition считает bytes/SHA-256 и проверяет declared MIME/magic;
9. real или явно injected PDF inspector проверяет completed bytes;
10. accepted-operation fingerprint lookup;
11. initial/lineage/current/target/no-change checks;
12. private storage finalize;
13. repository commit/CAS;
14. verifier-only delivery observer после commit и до return.

Failure шагов 2–4 возвращает `REJECTED/AUTHORIZATION_DENIED`, поэтому отозванный actor не replay-ит ранее accepted result. Ранее unauthorized terminal request после grant replay-ится как исходный denial на шаге 5; новое намерение требует новый request ID. Order lookup failure возникает на шаге 6 как `ORDER_NOT_FOUND`. Unauthorized caller не узнаёт original/file metadata, target existence или stored result.

## 4. Clock и даты

Production clock предоставляет один instant на attempt. `serverToday` вычисляется из него в `Europe/Moscow`; `uploadedAt` сохраняется как canonical UTC RFC 3339 `YYYY-MM-DDTHH:MM:SSZ` без дробной части.

`documentDate` — дата, напечатанная в оригинале и явно подтверждённая actor. Она может быть раньше `serverToday`, но не позже. Upload time никогда не становится document date.

- после необязательного шаблона UI позже сможет предложить remembered generation date;
- при прямой загрузке UI позже сможет предложить `serverToday`;
- оба пути вызывают этот seam с одним явным `documentDate` и не меняют command behavior.

Future date возвращает `REJECTED/FUTURE_DOCUMENT_DATE` на шаге 7: upload stream, MIME/magic и inspector не вызываются.

## 5. Exact file boundary

Операция содержит ровно один stream. Считаются received bytes до декодирования или трансформации. Допустимый inclusive диапазон: `1..20,971,520` bytes. На byte `20,971,521` чтение немедленно прекращается с `REJECTED/FILE_TOO_LARGE`.

Проверка идёт fail closed:

1. declared media type после ASCII case-fold равен `application/pdf`, иначе `REJECTED/NOT_PDF`;
2. первые пять bytes равны `%PDF-`, иначе `REJECTED/NOT_PDF`;
3. pinned production PDF parser полностью разбирает document и xref/trailer, иначе `REJECTED/INVALID_PDF`;
4. document не encrypted/password-protected и имеет минимум одну page, иначе `REJECTED/UNSAFE_PDF` для encryption и `REJECTED/INVALID_PDF` для zero/page-less structure;
5. catalog/page/object graph не содержит JavaScript, OpenAction/AA actions, Launch, embedded files/file attachments, RichMedia/multimedia или URI/GoToR/external-resource actions; наличие любого даёт `REJECTED/UNSAFE_PDF`.

Production использует owned inspector `FMonitorPassivePdfInspector` с algorithm ID `fmonitor-passive-pdf-v1`; установленный TCPDF `6.11.4` остаётся только renderer и не считается validator. Algorithm v1 лексически разбирает PDF `1.4..1.7`, всю `startxref`/`Prev` chain, classic xref и xref streams, indirect/object streams (structural streams допускают только `FlateDecode`), затем строит latest-object graph. Bounds: не более `100000` objects, reference depth `100`, aggregate structural decompression `67,108,864` bytes. Broken offsets, duplicate/conflicting identities, unsupported structural filters, cycles/limit exhaustion, encryption, отсутствие однозначного Catalog/Pages tree или ноль Page leaves дают fail-closed result. Inspector просматривает dictionaries всех revisions и decompressed object streams и отклоняет keys/actions `JavaScript`, `JS`, `OpenAction`, `AA`, `Launch`, `EmbeddedFiles`, `Filespec`, `FileAttachment`, `RichMedia`, `Movie`, `Sound`, `URI`, `GoToR`, `SubmitForm`, `ImportData`. Image/content streams не декодируются для OCR; их bounds/declared lengths проверяются структурно. Любое изменение algorithm ID/limits/grammar после approval возвращается в Gate 1/2. Malware scan, OCR и проверка подписей/печатей не входят в этот контракт.

Canonical one-page positive fixture — literal base64:

```text
JVBERi0xLjQKMSAwIG9iago8PCAvVHlwZSAvQ2F0YWxvZyAvUGFnZXMgMiAwIFIgPj4KZW5kb2JqCjIgMCBvYmoKPDwgL1R5cGUgL1BhZ2VzIC9LaWRzIFszIDAgUl0gL0NvdW50IDEgPj4KZW5kb2JqCjMgMCBvYmoKPDwgL1R5cGUgL1BhZ2UgL1BhcmVudCAyIDAgUiAvTWVkaWFCb3ggWzAgMCA3MiA3Ml0gPj4KZW5kb2JqCnhyZWYKMCA0CjAwMDAwMDAwMDAgNjU1MzUgZiAKMDAwMDAwMDAwOSAwMDAwMCBuIAowMDAwMDAwMDU4IDAwMDAwIG4gCjAwMDAwMDAxMTUgMDAwMDAgbiAKdHJhaWxlcgo8PCAvU2l6ZSA0IC9Sb290IDEgMCBSID4+CnN0YXJ0eHJlZgoxODQKJSVFT0YK
```

Expected byte size: `327`. Expected SHA-256: `4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784`. Эти literals являются oracle; verifier не вычисляет expected value через production service.

## 6. Exact result DTO и matrix

```text
Result {
  status: ACCEPTED | REPLAYED | REJECTED | CONFLICT | FAILED
  reasonCode: null | stable enum
  retryable: boolean
  requestId: echoed canonical UUID
  rootOriginalId: null | opaque lineage ID
  currentRevisionId: null | opaque revision ID
  revisionNumber: null | positive integer
  documentDate: null | YYYY-MM-DD
  sha256: null | 64 lower-case hex
  byteSize: null | positive integer
  uploadedAt: null | UTC RFC3339 second
}
```

| Status | Exact reasons | `retryable` | Evidence fields |
|---|---|---:|---|
| `ACCEPTED` | null | false | все non-null |
| `REPLAYED` | null | false | точно равны stored accepted result |
| `REJECTED` | `AUTHORIZATION_DENIED`, `INVALID_COMMAND`, `ORDER_NOT_FOUND`, `COMPOSITION_NOT_CONFIRMED`, `INVALID_COMPOSITION`, `FILE_TOO_LARGE`, `NOT_PDF`, `INVALID_PDF`, `UNSAFE_PDF`, `FUTURE_DOCUMENT_DATE`, `NO_CHANGES` | false | все null |
| `CONFLICT` | `SEMANTIC_COLLISION`, `STALE_REVISION`, `TARGET_NOT_FOUND`, `TARGET_NOT_CURRENT`, `INITIAL_ALREADY_EXISTS` | false | все null |
| `FAILED` | `STREAM_FAILURE`, `STORAGE_FAILURE`, `PERSISTENCE_FAILURE`, `PERSISTENCE_OUTCOME_UNKNOWN` | true | все null |

Result не содержит path, filename, composition members, correction reason, parser detail, SQL/exception или filesystem detail. `requestId` владеет retry identity: terminal stored hit возвращается до чтения нового stream и не сравнивает повторный payload. Новое намерение MUST использовать новый request ID.

## 7. Initial acceptance

После authorization, composition/date/file validation `INITIAL`:

- требует отсутствие existing original lineage для `assignmentOrderId`, иначе `CONFLICT/INITIAL_ALREADY_EXISTS`;
- генерирует production-owned opaque `rootOriginalId` и отдельный opaque `currentRevisionId`; caller их не задаёт;
- создаёт `revisionNumber=1`;
- сохраняет case/order identity, immutable composition identity/hash, document date, UTC upload time, actor, SHA-256, byte size, private content identity, request ID и operation fingerprint;
- добавляет один domain event `assignment_order_original_accepted` без bytes/path;
- не изменяет order composition/intervals/status, case state, actual start, tasks или checklist availability.

Accepted result содержит exact persisted values.

## 8. Semantic identity и deterministic precedence

Accepted-operation fingerprint — SHA-256 canonical length-prefixed encoding exact tuple:

```text
mode
installationCaseId
assignmentOrderId
rootOriginalId-or-empty
targetRevisionId-or-empty
expectedCurrentRevisionId-or-empty
documentDate
compositionSnapshotIdentity
compositionSha256
pdfSha256
```

Correction reason, request ID, actor, filename, declared MIME и upload time не входят в fingerprint.

Раздел 3 задаёт полный порядок. На шаге 5 accepted request hit возвращает те же evidence fields со status `REPLAYED`, rejected/conflict hit — исходный terminal status/reason; payload не читается. При miss order/composition/date checks предшествуют stream. После completed bytes шаг 10 ищет accepted fingerprint и возвращает `REPLAYED` независимо от того, стал ли correction target non-current из-за этой operation. Только miss переходит к lineage/CAS.

Новый request correction с тем же PDF/date/composition и только другой reason даёт `REJECTED/NO_CHANGES`. Тот же PDF с новой допустимой date является correction. Correction сравнивает current composition identity/hash с immutable root snapshot: drift даёт `CONFLICT/SEMANTIC_COLLISION` на шаге 6 до stream; caller состав не передаёт. Same-request retry уже accepted operation выигрывает раньше, на шаге 5.

## 9. Append-only correction и CAS

`CORRECTION` указывает lineage `rootOriginalId`, конкретный `targetRevisionId` и отдельно `expectedCurrentRevisionId`. Она может изменить PDF, document date или оба, но reason обязателен.

Accepted correction требует, чтобы actual current revision ID совпадал и с `expectedCurrentRevisionId`, и с `targetRevisionId`, затем создаёт новый opaque revision ID и `revisionNumber=n+1`, сохраняет `previousRevisionId=targetRevisionId`, новую evidence и event `assignment_order_original_corrected`; prior revisions и evidence не обновляются и не удаляются.

Validation outcomes:

- root identity не относится к указанному case/order/composition → `CONFLICT/SEMANTIC_COLLISION`;
- `expectedCurrentRevisionId` не равен actual current revision ID → `CONFLICT/STALE_REVISION`;
- expected current совпадает, но `targetRevisionId` неизвестен → `CONFLICT/TARGET_NOT_FOUND`;
- target существует в другом root → `CONFLICT/SEMANTIC_COLLISION`;
- expected current совпадает с actual current, но `targetRevisionId` указывает другую revision того же root → `CONFLICT/TARGET_NOT_CURRENT`;
- two concurrent different corrections with same current/expected revision: CAS принимает ровно одну `n+1`; loser повторно проверяет fingerprint, затем возвращает `CONFLICT/STALE_REVISION`;
- concurrent identical corrections: winner `ACCEPTED`, loser после fingerprint lookup `REPLAYED` с result winner.

Upload time никогда не разрешает tie.

## 10. Storage/commit/response-loss protocol

Storage private, не web-addressable. Exact phases:

1. storage `beginStage()` создаёт owned private stage и emits `STAGE_BEGIN`;
2. application читает stream chunks с `maximumBytes=65536`, обновляет SHA-256/received count и сразу вызывает stage `write(chunk)`; byte `20,971,521` не записывается;
3. на EOF stage предоставляет exact completed bytes inspector-у; invalid input вызывает `abort` и не вызывает finalize;
4. passive PDF вызывает stage `finalize(sha256,byteSize)` и emits `FINALIZE_BEGIN/DONE`;
5. DB transaction атомарно сохраняет typed accepted commit, terminal result и domain event;
6. stage и stream закрываются exactly once в `finally`; после commit нет дополнительного finalize;
7. delivery observer вызывается после commit и до return.

Storage adapter emits events: `BEGIN` непосредственно перед primitive, `DONE` только после durable success. Stage/stream/validation failure вызывает `abort`; abort failure оставляет только private non-final stage для storage-owned bounded cleanup и safe log. Request replay, order/date/confirmation rejection не создают stage/event.

Матрица:

| Failure point | Persisted original/result | Blob | Returned outcome | Retry |
|---|---|---|---|---|
| stream read до complete bytes | нет | stage очищен/quarantined private | `FAILED/STREAM_FAILURE` | тот же request разрешён |
| stage/private finalize | нет | no public blob; own stage cleanup | `FAILED/STORAGE_FAILURE` | тот же request разрешён |
| accepted commit returns `CONFLICT` | определяется fingerprint/current-lineage rereads | lease held through both rereads, затем release attempted exactly once | selected `REPLAYED`, exact `CONFLICT/*` или `FAILED/PERSISTENCE_FAILURE` | release failure не меняет selected outcome |
| definite DB rollback/failure | нет | private orphan | `FAILED/PERSISTENCE_FAILURE` | verified orphan можно reuse; одна новая commit attempt |
| commit connection loss, fresh lookup proves absent | нет | private orphan | `FAILED/PERSISTENCE_FAILURE` | как definite absence |
| commit connection loss, fresh lookup proves accepted | да | finalized private blob | `ACCEPTED` stored result, если текущий invocation ещё отвечает | дальнейший retry → `REPLAYED` |
| commit connection loss, lookup cannot prove accepted or absent | неизвестно caller-у | private blob | `FAILED/PERSISTENCE_OUTCOME_UNKNOWN` | MUST retry same request ID; новая blind commit запрещена |
| commit success, response lost | да | finalized private blob | caller не получил result | same request → `REPLAYED` без stream/storage/domain effect |
| response serialization after commit fails locally | да | finalized private blob | transport failure, не ложный `FAILED` domain result | same request → `REPLAYED` |

Private orphan не является original fact и не читается никаким public query. Его владелец — отдельный maintenance seam `reconcileAssignmentOrderOriginalPrivateOrphans(Command): Result`, не business command. Command требует system principal с exact capability `assignment_order.original.storage.reconcile` и принимает `{requestId, cutoffUtc, batchLimit, cursor}`, где `batchLimit=1..1000`, `cutoffUtc <= now-3600s`, cursor opaque/null. Result равен `{status, scanned, deleted, retained, failed, nextCursor}`. Для каждого candidate seam берёт digest-scoped storage lock, затем через read-only repository port повторно проверяет отсутствие committed reference. Upload `finalize`/reuse получает typed content lease из того же exclusion domain и удерживает его до terminal DB commit/rollback либо завершения fresh lookup, разрешающего `OUTCOME_UNKNOWN`; maintenance не может приобрести lock и удалить blob, пока lease не released. Referenced/newer/locked blobs retained. Один run обрабатывает не более `batchLimit`, не меняет domain facts и пишет append-only maintenance audit/result. Concurrent runs дают at-most-once delete; absent delete — idempotent success.

## 11. Audit

Accepted initial/correction transaction сохраняет ровно один domain event и terminal request result вместе с revision. `REPLAYED` не создаёт event. Valid-shape `REJECTED`/`CONFLICT`, включая unauthorized attempt, атомарно сохраняют terminal request result и safe attempt audit в одной short transaction; failure этой audit transaction заменяет intended outcome на `FAILED/PERSISTENCE_FAILURE`, stored terminal result отсутствует и retry разрешён. Invalid shape до надёжной request/actor identity не пишет DB audit и даёт только best-effort aggregate metric без payload.

`FAILED/STREAM_FAILURE` и `FAILED/STORAGE_FAILURE` не сохраняются как terminal request result, потому что тот же request должен быть retryable; они пытаются записать safe attempt audit отдельной transaction. Если audit write тоже падает, caller всё равно получает исходный retryable failure, а audit failure логируется best effort: отсутствие audit не может превратить storage failure в иной доказанный domain outcome. `PERSISTENCE_FAILURE` и `PERSISTENCE_OUTCOME_UNKNOWN` также не обещают DB audit и логируются best effort exact once с safe correlation. После committed accepted revision domain event уже существует атомарно; внешний logging failure не отменяет результат. Ни audit, ни log не содержит bytes, filename, path, composition names, correction reason, SQL или exception text.

## 12. Worked examples

Common fixed dependencies:

```text
clock instant = 2026-09-02T09:15:30Z
serverToday Europe/Moscow = 2026-09-02
case = 4512
order = 81
composition identity = composition-81-v1
composition = installers [7001,7002], engineer 901
compositionSha256 = 1111111111111111111111111111111111111111111111111111111111111111
root ID generator first value = original-0001
revision ID generator values = revision-0001, revision-0002
positive PDF = section 5 literal, 327 bytes, sha256 4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784
```

### Example A — initial accept

Input: request `00000000-0000-4000-8000-000000000001`, `INITIAL`, null lineage fields, date `2026-09-01`, `compositionConfirmed=true`, authorized actor 18, positive PDF.

Expected:

```text
ACCEPTED|null|false|00000000-0000-4000-8000-000000000001|original-0001|revision-0001|1|2026-09-01|4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784|327|2026-09-02T09:15:30Z
```

Exactly one revision/event; composition/opening snapshots unchanged.

### Example B — lost response retry

Repeat after commit with the same request ID, even with another supplied unread stream. Expected status `REPLAYED` and stored evidence equal Example A. Stream read count, storage mutation count, revision count and domain-event count do not increase. Same request ID always denotes retry, never a new intention.

### Example C — correction

Root `original-0001`, target and expected current `revision-0001`, request `00000000-0000-4000-8000-000000000002`, same PDF, date `2026-09-02`, reason `Исправлена дата документа`.

Expected `ACCEPTED`, root `original-0001`, current revision `revision-0002`, revision number `2`, date `2026-09-02`, same digest/size, uploadedAt fixed attempt clock. Revision 1 remains byte-identical.

Exact retry Example C returns stored revision 2 as `REPLAYED` before target-current validation. Changing date again while still targeting revision 1 returns `CONFLICT/STALE_REVISION` and no mutation.

## 13. RED verifier contract

Gate 2 verifier MUST:

- cite `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` and call only `submitAssignmentOrderOriginal` through production-composed application factory;
- use deterministic injected clock, ID source, parser outcome adapter only where production parser itself is not under assertion, storage primitives/faults and independent in-memory/MariaDB evidence readers;
- use the literal positive PDF/hash and independently built malformed, encrypted, zero-page, active-action and over-limit streams;
- prove unauthorized denial occurs before stream read;
- snapshot all original, order/composition, case/opening, task, event, audit and unrelated decoy facts before each rejection/conflict/failure;
- distinguish allowed safe attempt-audit/log delta from forbidden domain/storage/composition/opening mutation;
- cover same-request and cross-request replay, idempotency-key reuse, no-change reason, same-bytes/new-date correction, changed composition collision, stale/current targets, identical/different two-runner CAS races;
- inject each phase failure from section 10 and prove response-loss retry behavior;
- use a fresh isolated DB prefix and private temporary storage root, validate every cleanup target, remove only verifier-owned artifacts, and reap every child process in `finally`;
- forbid network, real 1С ДО, production documents, secrets and shared production storage.

Verifier sensitivity MUST demonstrate that it fails if implementation: trusts extension/MIME only; accepts active/encrypted/zero-page PDF; counts transformed rather than received bytes; authorizes by role name/other capability; updates prior revision; checks stale before stored replay; duplicates a concurrent correction; exposes a private orphan; changes composition/opening; or reports committed response loss as no-fact `FAILED`.

Stable successful transcript, one line each in this order:

```text
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_INITIAL_OK
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_PDF_BOUNDARY_OK
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_AUTHORIZATION_OK
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_REPLAY_OK
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_CORRECTION_OK
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_CONCURRENCY_OK
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_FAILURE_MATRIX_OK
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_NO_DOWNSTREAM_MUTATION_OK
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_001_OK
```

Любой skip, warning, setup fallback, missing parser/fault adapter, leaked child/temp resource или unexpected output — failure, не PASS.

## 14. Explicit deferrals

- `expose-assignment-order-original-http`: HTTP method/routes, multipart behavior, session/CSRF, local permissions, metadata read DTO, not-found/forbidden, download and headers. Reserved read permission: `assignment_order.original.read`.
- `apply-assignment-order-original-to-composition`: sequential orders, effective dates, overlaps/ties и применение состава.
- `open-installation-from-assignment-order-original`: замена legacy `registered` opening gate и immutable opening snapshot.

Этот spec не изменяет и не переутверждает старые HTTP/E2E/registration tests. Historical manual-registration facts остаются evidence реализованного predecessor, но не target pilot requirement.

## 15. Exact PHP construction contract

Все типы ниже находятся в namespace `FMonitor2\AssignmentOrderOriginal`. Blocks являются нормативными и MUST проходить `php -l` после добавления общего `<?php` и объединения в порядке объявления.

```php
enum AssignmentOrderOriginalMode: string
{
    case INITIAL = 'initial';
    case CORRECTION = 'correction';
}

enum AssignmentOrderOriginalStatus: string
{
    case ACCEPTED = 'accepted';
    case REPLAYED = 'replayed';
    case REJECTED = 'rejected';
    case CONFLICT = 'conflict';
    case FAILED = 'failed';
}

enum AssignmentOrderOriginalReason: string
{
    case AUTHORIZATION_DENIED = 'authorization_denied';
    case INVALID_COMMAND = 'invalid_command';
    case ORDER_NOT_FOUND = 'order_not_found';
    case COMPOSITION_NOT_CONFIRMED = 'composition_not_confirmed';
    case INVALID_COMPOSITION = 'invalid_composition';
    case FILE_TOO_LARGE = 'file_too_large';
    case NOT_PDF = 'not_pdf';
    case INVALID_PDF = 'invalid_pdf';
    case UNSAFE_PDF = 'unsafe_pdf';
    case FUTURE_DOCUMENT_DATE = 'future_document_date';
    case NO_CHANGES = 'no_changes';
    case SEMANTIC_COLLISION = 'semantic_collision';
    case STALE_REVISION = 'stale_revision';
    case TARGET_NOT_FOUND = 'target_not_found';
    case TARGET_NOT_CURRENT = 'target_not_current';
    case INITIAL_ALREADY_EXISTS = 'initial_already_exists';
    case STREAM_FAILURE = 'stream_failure';
    case STORAGE_FAILURE = 'storage_failure';
    case PERSISTENCE_FAILURE = 'persistence_failure';
    case PERSISTENCE_OUTCOME_UNKNOWN = 'persistence_outcome_unknown';
}

final readonly class AssignmentOrderOriginalUpload
{
    public function __construct(
        public AssignmentOrderOriginalByteStream $stream,
        public string $originalFilename,
        public string $declaredMediaType,
    ) {}
}

final readonly class SubmitAssignmentOrderOriginalCommand
{
    public function __construct(
        public string $requestId,
        public AssignmentOrderOriginalMode $mode,
        public int $installationCaseId,
        public int $assignmentOrderId,
        public int $actorUserId,
        public string $documentDate,
        public bool $compositionConfirmed,
        public ?string $rootOriginalId,
        public ?string $targetRevisionId,
        public ?string $expectedCurrentRevisionId,
        public ?string $correctionReason,
        public AssignmentOrderOriginalUpload $upload,
    ) {}
}

interface AssignmentOrderOriginalResult
{
    public function status(): AssignmentOrderOriginalStatus;
    public function reasonCode(): ?AssignmentOrderOriginalReason;
    public function retryable(): bool;
    public function requestId(): string;
    public function rootOriginalId(): ?string;
    public function currentRevisionId(): ?string;
    public function revisionNumber(): ?int;
    public function documentDate(): ?string;
    public function sha256(): ?string;
    public function byteSize(): ?int;
    public function uploadedAt(): ?string;
}

interface AssignmentOrderOriginalApplication
{
    public function submitAssignmentOrderOriginal(
        SubmitAssignmentOrderOriginalCommand $command,
    ): AssignmentOrderOriginalResult;
}
```

DTO constructor выполняет только PHP type construction. Все shape/business checks возвращаются как Result из application seam; constructor не бросает domain rejection. Result является interface: concrete accepted/replayed result constructors internal к application package и не экспортируются verifier adapters.

### Deterministic input ports

```php
enum AssignmentOrderOriginalAuthorizationStatus: string
{
    case ALLOWED = 'allowed';
    case DENIED = 'denied';
    case UNAVAILABLE = 'unavailable';
}

interface AssignmentOrderOriginalAuthorizer
{
    public function authorize(
        int $actorUserId,
        string $exactCapability,
    ): AssignmentOrderOriginalAuthorizationStatus;
}

enum AssignmentOrderCompositionLookupStatus: string
{
    case FOUND = 'found';
    case NOT_FOUND = 'not_found';
    case UNAVAILABLE = 'unavailable';
}

final readonly class AssignmentOrderCompositionSnapshot
{
    /** @param list<int> $installerIds */
    public function __construct(
        public AssignmentOrderCompositionLookupStatus $status,
        public int $installationCaseId,
        public int $assignmentOrderId,
        public ?string $identity,
        public ?string $sha256,
        public array $installerIds,
        public ?int $controlEngineerUserId,
    ) {}
}

interface AssignmentOrderCompositionReader
{
    public function find(int $caseId, int $orderId): AssignmentOrderCompositionSnapshot;
}

interface AssignmentOrderOriginalClock
{
    public function nowUtc(): string;
}

enum AssignmentOrderOriginalIdStatus: string
{
    case GENERATED = 'generated';
    case COLLISION = 'collision';
    case EXHAUSTED = 'exhausted';
    case UNAVAILABLE = 'unavailable';
}

final readonly class AssignmentOrderOriginalIdResult
{
    public function __construct(
        public AssignmentOrderOriginalIdStatus $status,
        public ?string $id,
    ) {}
}

interface AssignmentOrderOriginalIdSource
{
    public function nextRootId(): AssignmentOrderOriginalIdResult;
    public function nextRevisionId(): AssignmentOrderOriginalIdResult;
}
```

Authorization `UNAVAILABLE`, composition `UNAVAILABLE`, ID `UNAVAILABLE|EXHAUSTED` → `FAILED/PERSISTENCE_FAILURE`; eight consecutive `COLLISION` outcomes → `FAILED/PERSISTENCE_FAILURE`. `nowUtc()` MUST return canonical UTC second; invalid/unavailable clock → `FAILED/PERSISTENCE_FAILURE`. Moscow conversion belongs to application, не clock adapter.

### Exact stream and PDF ports

```php
enum AssignmentOrderOriginalStreamReadStatus: string
{
    case BYTES = 'bytes';
    case EOF = 'eof';
    case FAILED = 'failed';
}

final readonly class AssignmentOrderOriginalStreamRead
{
    public function __construct(
        public AssignmentOrderOriginalStreamReadStatus $status,
        public string $bytes,
    ) {}
}

interface AssignmentOrderOriginalByteStream
{
    public function read(int $maximumBytes): AssignmentOrderOriginalStreamRead;
    public function close(): void;
}

enum AssignmentOrderOriginalPdfStatus: string
{
    case PASSIVE_PDF = 'passive_pdf';
    case INVALID_PDF = 'invalid_pdf';
    case UNSAFE_PDF = 'unsafe_pdf';
    case INSPECTOR_FAILED = 'inspector_failed';
}

final readonly class AssignmentOrderOriginalPdfInspection
{
    private function __construct(public AssignmentOrderOriginalPdfStatus $status) {}
    public static function passive(): self { return new self(AssignmentOrderOriginalPdfStatus::PASSIVE_PDF); }
    public static function invalid(): self { return new self(AssignmentOrderOriginalPdfStatus::INVALID_PDF); }
    public static function unsafe(): self { return new self(AssignmentOrderOriginalPdfStatus::UNSAFE_PDF); }
    public static function failed(): self { return new self(AssignmentOrderOriginalPdfStatus::INSPECTOR_FAILED); }
}

interface AssignmentOrderOriginalPdfInspector
{
    public function inspect(string $completedBytes): AssignmentOrderOriginalPdfInspection;
    public function algorithmId(): string;
}

final class FMonitorPassivePdfInspector implements AssignmentOrderOriginalPdfInspector
{
    public const ALGORITHM_ID = 'fmonitor-passive-pdf-v1';
    public function inspect(string $completedBytes): AssignmentOrderOriginalPdfInspection { /* owned algorithm */ }
    public function algorithmId(): string { return self::ALGORITHM_ID; }
}
```

Application owns `declaredMediaType`, `%PDF-`, size и stream failure mapping. It calls inspector only after complete bounded acquisition. Real-parser cases MUST include positive literal, malformed/truncated, encrypted, zero-page, each forbidden action family, xref table, xref stream and object stream. Injected inspector is permitted only for ordering/storage/CAS/failure cases where parser semantics is not assertion; its public factories cannot create application Result.

Application MUST call `close()` exactly once in `finally` after first stream ownership. Close failure before accepted commit → `FAILED/STREAM_FAILURE`; after commit it is operational safe-log failure and cannot replace accepted stored result.

### Storage, repository and delivery ports

```php
enum AssignmentOrderOriginalStorageEvent: string
{
    case STAGE_BEGIN = 'stage_begin';
    case STAGE_WRITE = 'stage_write';
    case STAGE_DONE = 'stage_done';
    case ABORT_BEGIN = 'abort_begin';
    case ABORT_DONE = 'abort_done';
    case STAGE_CLOSE = 'stage_close';
    case FINALIZE_BEGIN = 'finalize_begin';
    case FINALIZE_DONE = 'finalize_done';
    case DIGEST_LOCK_ACQUIRED = 'digest_lock_acquired';
    case DELETE_BEGIN = 'delete_begin';
    case DELETE_DONE = 'delete_done';
}

enum AssignmentOrderOriginalStorageStatus: string
{
    case OK = 'ok';
    case ALREADY_PRESENT_VERIFIED = 'already_present_verified';
    case LOCKED = 'locked';
    case FAILED = 'failed';
}

enum AssignmentOrderOriginalFaultPoint: string
{
    case STREAM_READ = 'stream_read';
    case STAGE = 'stage';
    case STAGE_WRITE = 'stage_write';
    case STAGE_ABORT = 'stage_abort';
    case STAGE_CLOSE = 'stage_close';
    case PRIVATE_FINALIZE = 'private_finalize';
    case CONTENT_LEASE_RELEASE = 'content_lease_release';
    case DIGEST_LOCK = 'digest_lock';
    case REQUEST_LOOKUP = 'request_lookup';
    case FINGERPRINT_LOOKUP = 'fingerprint_lookup';
    case LINEAGE_LOOKUP = 'lineage_lookup';
    case COMMIT_BEFORE = 'commit_before';
    case COMMIT_AFTER_UNKNOWN = 'commit_after_unknown';
    case ATTEMPT_AUDIT_COMMIT = 'attempt_audit_commit';
    case RESPONSE_DELIVERY = 'response_delivery';
    case ORPHAN_REFERENCE_LOOKUP = 'orphan_reference_lookup';
    case ORPHAN_DELETE = 'orphan_delete';
}

interface AssignmentOrderOriginalPrivateContent
{
    public function opaqueIdentity(): string;
    public function sha256(): string;
    public function byteSize(): int;
}

interface AssignmentOrderOriginalPrivateContentLease
{
    public function status(): AssignmentOrderOriginalStorageStatus;
    public function content(): ?AssignmentOrderOriginalPrivateContent;
    public function release(): AssignmentOrderOriginalStorageStatus;
}

interface AssignmentOrderOriginalPrivateStage
{
    public function write(string $chunk): AssignmentOrderOriginalStorageStatus;
    public function completedBytesForInspection(): string;
    public function finalize(string $sha256, int $byteSize): AssignmentOrderOriginalStorageOutcome;
    public function abort(): AssignmentOrderOriginalStorageStatus;
    public function close(): void;
}

interface AssignmentOrderOriginalStorageOutcome
{
    public function status(): AssignmentOrderOriginalStorageStatus;
    public function lease(): ?AssignmentOrderOriginalPrivateContentLease;
}

final readonly class AssignmentOrderOriginalOrphanCandidate
{
    public function __construct(
        public AssignmentOrderOriginalOrphanKind $kind,
        public string $opaqueIdentity,
        public ?string $sha256,
        public int $byteSize,
        public string $createdOrFinalizedAtUtc,
    ) {}
}

enum AssignmentOrderOriginalOrphanKind: string
{
    case ABANDONED_STAGE = 'abandoned_stage';
    case FINALIZED_CONTENT = 'finalized_content';
}

interface AssignmentOrderOriginalOrphanPage
{
    public function status(): AssignmentOrderOriginalStorageStatus;
    /** @return list<AssignmentOrderOriginalOrphanCandidate> */
    public function candidates(): array;
    public function nextCursor(): ?string;
}

interface AssignmentOrderOriginalDigestLock
{
    public function status(): AssignmentOrderOriginalStorageStatus;
    public function opaqueIdentity(): string;
    public function release(): void;
}

interface AssignmentOrderOriginalPrivateStorage
{
    public function beginStage(): AssignmentOrderOriginalPrivateStage;
    public function listOrphans(string $cutoffUtc, int $limit, ?string $cursor): AssignmentOrderOriginalOrphanPage;
    public function acquireDigestLock(string $opaqueIdentity): AssignmentOrderOriginalDigestLock;
    public function deleteLocked(AssignmentOrderOriginalDigestLock $lock): AssignmentOrderOriginalStorageStatus;
    public function inventoryCanonicalJson(): string;
}

interface AssignmentOrderOriginalStorageObserver
{
    public function observe(
        AssignmentOrderOriginalStorageEvent $event,
        ?string $opaqueIdentity,
    ): void;
}

final class AssignmentOrderOriginalPrivateStorageFactory
{
    public static function create(
        string $absolutePrivateRoot,
        AssignmentOrderOriginalStorageObserver $observer,
        AssignmentOrderOriginalFaultInjector $faults,
    ): AssignmentOrderOriginalPrivateStorage { /* exact real storage adapter */ }
}

enum AssignmentOrderOriginalCommitStatus: string
{
    case COMMITTED = 'committed';
    case CONFLICT = 'conflict';
    case ROLLED_BACK = 'rolled_back';
    case OUTCOME_UNKNOWN = 'outcome_unknown';
}

enum AssignmentOrderOriginalLookupStatus: string
{
    case FOUND = 'found';
    case NOT_FOUND = 'not_found';
    case UNAVAILABLE = 'unavailable';
}

interface AssignmentOrderOriginalResultLookup
{
    public function status(): AssignmentOrderOriginalLookupStatus;
    public function result(): ?AssignmentOrderOriginalResult;
}

interface AssignmentOrderOriginalLineageLookup
{
    public function status(): AssignmentOrderOriginalLookupStatus;
    public function rootOriginalId(): ?string;
    public function currentRevisionId(): ?string;
    public function currentRevisionNumber(): ?int;
    public function compositionIdentity(): ?string;
    public function compositionSha256(): ?string;
    public function containsRevision(string $revisionId): bool;
}

interface AssignmentOrderOriginalReferenceLookup
{
    public function status(): AssignmentOrderOriginalLookupStatus;
    public function referenced(): ?bool;
}

final readonly class AssignmentOrderOriginalAcceptedCommit
{
    public function __construct(
        public string $requestId,
        public string $fingerprint,
        public AssignmentOrderOriginalMode $mode,
        public int $installationCaseId,
        public int $assignmentOrderId,
        public int $actorUserId,
        public string $rootOriginalId,
        public string $newRevisionId,
        public int $newRevisionNumber,
        public ?string $previousRevisionId,
        public ?string $expectedCurrentRevisionId,
        public string $compositionIdentity,
        public string $compositionSha256,
        public string $documentDate,
        public string $uploadedAt,
        public string $pdfSha256,
        public int $byteSize,
        public string $privateContentIdentity,
        public ?string $correctionReason,
        public string $domainEventType,
    ) {}
}

final readonly class AssignmentOrderOriginalAttemptCommit
{
    public function __construct(
        public string $requestId,
        public int $actorUserId,
        public AssignmentOrderOriginalMode $mode,
        public int $installationCaseId,
        public int $assignmentOrderId,
        public AssignmentOrderOriginalStatus $status,
        public AssignmentOrderOriginalReason $reason,
        public bool $retryable,
        public string $attemptedAt,
    ) {}
}

interface AssignmentOrderOriginalRepository
{
    public function findTerminalRequest(string $requestId): AssignmentOrderOriginalResultLookup;
    public function findAcceptedFingerprint(string $fingerprint): AssignmentOrderOriginalResultLookup;
    public function findLineage(string $rootOriginalId): AssignmentOrderOriginalLineageLookup;
    public function commitAccepted(AssignmentOrderOriginalAcceptedCommit $commit): AssignmentOrderOriginalCommitStatus;
    public function commitAttempt(AssignmentOrderOriginalAttemptCommit $commit): AssignmentOrderOriginalCommitStatus;
    public function hasCommittedContent(string $opaqueIdentity): AssignmentOrderOriginalReferenceLookup;
    public function evidenceCanonicalJson(int $caseId, int $orderId): string;
}

enum AssignmentOrderOriginalLifecycleEvent: string
{
    case AFTER_REQUEST_MISS_BEFORE_STREAM = 'after_request_miss_before_stream';
    case AFTER_FINGERPRINT_MISS_BEFORE_CAS = 'after_fingerprint_miss_before_cas';
    case AFTER_PRIVATE_FINALIZE_BEFORE_COMMIT = 'after_private_finalize_before_commit';
    case AFTER_COMMIT_BEFORE_RETURN = 'after_commit_before_return';
}

interface AssignmentOrderOriginalLifecycleObserver
{
    public function observe(AssignmentOrderOriginalLifecycleEvent $event): void;
}

interface AssignmentOrderOriginalFaultInjector
{
    public function before(AssignmentOrderOriginalFaultPoint $point): void;
}

interface AssignmentOrderOriginalSafeLogObserver
{
    /** @param array<string, scalar|null> $safeFields */
    public function record(string $event, array $safeFields): void;
}

interface AssignmentOrderOriginalResultDeliveryObserver
{
    public function afterCommitBeforeReturn(AssignmentOrderOriginalResult $result): void;
}
```

Repository `OUTCOME_UNKNOWN` triggers a fresh `findTerminalRequest(requestId)`: hit resolves stored outcome; reliable miss maps `PERSISTENCE_FAILURE`; lookup exception/unavailable maps `PERSISTENCE_OUTCOME_UNKNOWN`. `CONFLICT` causes fingerprint/current-lineage reread and exact replay/stale mapping. Isolation is InnoDB `READ COMMITTED` plus unique request/fingerprint keys and atomic CAS predicate on current revision ID. Lifecycle observer may block only at named events; deterministic different/identical correction race pauses both workers at `AFTER_FINGERPRINT_MISS_BEFORE_CAS`.

`AssignmentOrderOriginalStorageOutcome::OK|ALREADY_PRESENT_VERIFIED` MUST carry exactly one `AssignmentOrderOriginalPrivateContentLease` with status `OK`; other outcomes carry null. The lease exclusively owns access to its immutable content and the digest-scoped exclusion token shared with maintenance. Upload MUST keep it held while constructing `AssignmentOrderOriginalAcceptedCommit`, through `commitAccepted`, through both mandatory accepted-fingerprint and current-lineage rereads after `CONFLICT`, and through the one fresh terminal-request lookup resolving `OUTCOME_UNKNOWN`.

Release occurs exactly once: after `COMMITTED`; after definite `ROLLED_BACK`; after the `OUTCOME_UNKNOWN` lookup returns `FOUND|NOT_FOUND|UNAVAILABLE`; or, for `CONFLICT`, only after fingerprint then current-lineage rereads have selected provisional `REPLAYED`, exact `CONFLICT/*`, or `FAILED/PERSISTENCE_FAILURE`. If provisional result is a non-replay conflict, its terminal result/audit commit follows normal section 11 rules after lease-release attempt. Maintenance `acquireDigestLock` MUST return `LOCKED` until release succeeds.

Failure to acquire a lease maps `FAILED/STORAGE_FAILURE`; the stage is aborted and closed, no repository commit is attempted. Release is attempt-always. After committed/fresh-lookup-`FOUND`, release failure cannot replace durable accepted/replayed result. After rollback or fresh lookup `NOT_FOUND|UNAVAILABLE`, it preserves the selected retryable persistence result. After `CONFLICT` rereads, typed `FAILED` or Throwable from release MUST preserve the selected provisional replay/conflict/persistence result and MUST NOT skip a required conflict attempt-audit transaction. Every release failure logs exactly once `ASSIGNMENT_ORDER_ORIGINAL_CONTENT_LEASE_RELEASE_FAILED correlation_id=<12hex> phase=<committed|rolled_back|unknown_found|unknown_not_found|unknown_unavailable|commit_conflict>` with no content identity, digest, path, request data or exception; storage retains the exclusion token for bounded recovery. The business command never deletes/exposes/retries that blob. No response-delivery observer runs before lease release has been attempted.

Production storage root comes only from trusted `FMONITOR_ASSIGNMENT_ORDER_ORIGINAL_ROOT`; verifier root is explicit absolute task-owned temp directory. Production bootstrap MUST use no-op lifecycle/delivery observers and cannot select verification composition through env/request/CLI/global/service locator. Verification factory is callable only by tests and still builds the same application implementation.

Every port call is total at the application boundary: adapter Throwable is caught and mapped to its typed `UNAVAILABLE`/`FAILED` outcome without exposing diagnostics. Request/fingerprint/lineage lookup `UNAVAILABLE` maps `FAILED/PERSISTENCE_FAILURE`; order/composition unavailable, clock failure and ID failure map the same. Stream `FAILED` maps `STREAM_FAILURE`; inspector `INSPECTOR_FAILED` and storage outcome `FAILED|LOCKED` map `STORAGE_FAILURE`. Storage observer receives the exact ordered events for operations actually attempted; request-ID replay emits no stream/storage event. Verification fault injector throws only at its named point; production factory binds inert final lifecycle/storage/fault/delivery implementations and exposes no selector.

Private content/outcome implementations are constructed only by storage adapters. Repository lookup/result implementations may rehydrate stored application results but cannot create accepted evidence not already represented by a committed `AssignmentOrderOriginalAcceptedCommit`. Repository MUST validate UUID/ID/hash/date/time/size grammar; exact mode/event pairing (`INITIAL→assignment_order_original_accepted`, `CORRECTION→assignment_order_original_corrected`); revision 1/null previous for initial; revision n+1/previous/expected-current for correction; content digest/size identity; and Result derivation before commit. Invalid adapter DTO is `PERSISTENCE_FAILURE`, never partial persistence. `AssignmentOrderOriginalAttemptCommit` allows only non-retryable `REJECTED|CONFLICT`, exact reason/status mapping and no evidence fields.

### Factory and verification dependencies

```php
final readonly class AssignmentOrderOriginalProductionConfig
{
    public function __construct(
        public string $privateStorageRoot,
        public string $tablePrefix,
    ) {}
}

final readonly class AssignmentOrderOriginalDependencies
{
    public function __construct(
        public AssignmentOrderOriginalAuthorizer $authorizer,
        public AssignmentOrderCompositionReader $compositions,
        public AssignmentOrderOriginalClock $clock,
        public AssignmentOrderOriginalIdSource $ids,
        public AssignmentOrderOriginalPdfInspector $pdfInspector,
        public AssignmentOrderOriginalPrivateStorage $storage,
        public AssignmentOrderOriginalRepository $repository,
        public AssignmentOrderOriginalLifecycleObserver $lifecycle,
        public AssignmentOrderOriginalStorageObserver $storageObserver,
        public AssignmentOrderOriginalFaultInjector $faults,
        public AssignmentOrderOriginalSafeLogObserver $safeLog,
        public AssignmentOrderOriginalResultDeliveryObserver $delivery,
    ) {}
}

final class ProductionAssignmentOrderOriginalFactory
{
    public static function create(
        \mysqli $database,
        AssignmentOrderOriginalProductionConfig $config,
    ): AssignmentOrderOriginalApplication { /* production bindings only */ }
}

final class AssignmentOrderOriginalVerificationFactory
{
    public static function create(
        AssignmentOrderOriginalDependencies $dependencies,
    ): AssignmentOrderOriginalApplication { /* same application owner */ }
}
```

## 16. Maintenance API, evidence and concurrency IPC

```php
enum AssignmentOrderOriginalMaintenanceStatus: string
{
    case COMPLETED = 'completed';
    case REPLAYED = 'replayed';
    case REJECTED = 'rejected';
    case PARTIAL = 'partial';
    case FAILED = 'failed';
}

enum AssignmentOrderOriginalMaintenanceReason: string
{
    case INVALID_COMMAND = 'invalid_command';
    case AUTHORIZATION_DENIED = 'authorization_denied';
    case LOCKED = 'locked';
    case STORAGE_FAILURE = 'storage_failure';
    case PERSISTENCE_FAILURE = 'persistence_failure';
}

final readonly class ReconcileAssignmentOrderOriginalPrivateOrphansCommand
{
    public function __construct(
        public string $requestId,
        public string $systemPrincipalId,
        public string $cutoffUtc,
        public int $batchLimit,
        public ?string $cursor,
    ) {}
}

interface AssignmentOrderOriginalMaintenanceResult
{
    public function status(): AssignmentOrderOriginalMaintenanceStatus;
    public function reason(): ?AssignmentOrderOriginalMaintenanceReason;
    public function retryable(): bool;
    public function scanned(): int;
    public function deleted(): int;
    public function retained(): int;
    public function failed(): int;
    public function nextCursor(): ?string;
}

interface AssignmentOrderOriginalMaintenanceApplication
{
    public function reconcileAssignmentOrderOriginalPrivateOrphans(
        ReconcileAssignmentOrderOriginalPrivateOrphansCommand $command,
    ): AssignmentOrderOriginalMaintenanceResult;
}

interface AssignmentOrderOriginalMaintenanceAuthorizer
{
    public function authorize(
        string $systemPrincipalId,
        string $exactCapability,
    ): AssignmentOrderOriginalAuthorizationStatus;
}

final readonly class AssignmentOrderOriginalMaintenanceCommit
{
    public function __construct(
        public string $requestId,
        public string $systemPrincipalId,
        public AssignmentOrderOriginalMaintenanceStatus $status,
        public ?AssignmentOrderOriginalMaintenanceReason $reason,
        public bool $retryable,
        public int $scanned,
        public int $deleted,
        public int $retained,
        public int $failed,
        public ?string $nextCursor,
        public string $attemptedAtUtc,
    ) {}
}

interface AssignmentOrderOriginalMaintenanceResultLookup
{
    public function status(): AssignmentOrderOriginalLookupStatus;
    public function result(): ?AssignmentOrderOriginalMaintenanceResult;
}

interface AssignmentOrderOriginalMaintenanceRepository
{
    public function findTerminalRequest(string $requestId): AssignmentOrderOriginalMaintenanceResultLookup;
    public function commitResultAndAudit(
        AssignmentOrderOriginalMaintenanceCommit $commit,
    ): AssignmentOrderOriginalCommitStatus;
}

final readonly class AssignmentOrderOriginalMaintenanceDependencies
{
    public function __construct(
        public AssignmentOrderOriginalMaintenanceAuthorizer $authorizer,
        public AssignmentOrderOriginalClock $clock,
        public AssignmentOrderOriginalPrivateStorage $storage,
        public AssignmentOrderOriginalRepository $references,
        public AssignmentOrderOriginalMaintenanceRepository $requests,
        public AssignmentOrderOriginalStorageObserver $storageObserver,
        public AssignmentOrderOriginalFaultInjector $faults,
        public AssignmentOrderOriginalSafeLogObserver $safeLog,
    ) {}
}

final class ProductionAssignmentOrderOriginalMaintenanceFactory
{
    public static function create(
        \mysqli $database,
        AssignmentOrderOriginalProductionConfig $config,
    ): AssignmentOrderOriginalMaintenanceApplication { /* production bindings only */ }
}

final class AssignmentOrderOriginalMaintenanceVerificationFactory
{
    public static function create(
        AssignmentOrderOriginalMaintenanceDependencies $dependencies,
    ): AssignmentOrderOriginalMaintenanceApplication { /* same maintenance owner */ }
}

interface AssignmentOrderOriginalEvidenceReader
{
    public function domainCanonicalJson(int $caseId, int $orderId): string;
    public function requestsCanonicalJson(): string;
    public function fingerprintsCanonicalJson(): string;
    public function eventsCanonicalJson(): string;
    public function safeAuditsCanonicalJson(): string;
    public function unchangedProcessCanonicalJson(int $caseId, int $orderId): string;
    public function privateBlobsCanonicalJson(): string;
    public function safeLogsCanonicalJson(): string;
}

interface AssignmentOrderOriginalByteStreamFactory
{
    public function fromBase64(string $base64): AssignmentOrderOriginalByteStream;
}

final readonly class AssignmentOrderOriginalWorkerConfig
{
    public function __construct(
        public string $databaseDsn,
        public string $databaseUser,
        public string $databasePasswordFile,
        public string $tablePrefix,
        public string $privateStorageRoot,
        public string $clockUtc,
        public string $rootIdSequenceCsv,
        public string $revisionIdSequenceCsv,
        public string $inspectorMode,
        public ?string $faultPoint,
    ) {}
}

final class AssignmentOrderOriginalVerificationWorkerBootstrap
{
    public static function run(
        string $configJsonPath,
        int $commandReadFd,
        int $barrierReadFd,
        int $barrierWriteFd,
        int $resultWriteFd,
    ): int { /* reconstruct adapters and run exactly one command */ }
}
```

Maintenance order: scalar shape → exact string-principal authorization → terminal request lookup → clock/cutoff → candidate page → per-candidate lock/reference/delete → atomic result+audit commit. Invalid UUID/cursor/batch outside `1..1000` or cutoff newer than `now-3600s` → `REJECTED/INVALID_COMMAND`; missing exact `assignment_order.original.storage.reconcile` → `REJECTED/AUTHORIZATION_DENIED`; all candidates handled → `COMPLETED`; authorized request hit → `REPLAYED`; one or more locked/per-item failures → `PARTIAL`; repository/audit unavailable → `FAILED/PERSISTENCE_FAILURE`.

Candidate page includes `ABANDONED_STAGE` and `FINALIZED_CONTENT`, ordered by binary `(createdOrFinalizedAtUtc, opaqueIdentity)` strictly after cursor, at most batchLimit and timestamp `<= cutoffUtc`; cursor is storage-generated base64url without padding encoding the last pair and is rejected if non-canonical/unknown. Under candidate lock, abandoned stage is deleted directly after age/type revalidation; finalized content first requires repository reference lookup. For every page `scanned = deleted + retained + failed`: successful/already-absent delete increments deleted; referenced or locked increments retained; reference/storage error increments failed. `nextCursor` equals page cursor when more may remain, otherwise null. `PARTIAL/LOCKED` applies when all failures were locks; any storage failure uses `PARTIAL/STORAGE_FAILURE`; both retryable true. `COMPLETED|REPLAYED|REJECTED` retryable false; `FAILED` retryable true. Result+audit are one terminal request transaction except `FAILED`; audit failure means `FAILED/PERSISTENCE_FAILURE` and no terminal result.

Evidence JSON uses recursively key-sorted UTF-8 JSON, integer IDs, UTC strings and arrays ordered by root/revision number/event ID/blob identity. Exact top-level shapes, with no additional keys:

```text
domain = {schema:"aoou-evidence-v1",caseId,orderId,roots:[{rootOriginalId,currentRevisionId,compositionIdentity,compositionSha256,revisions:[{revisionId,revisionNumber,previousRevisionId,documentDate,uploadedAt,actorUserId,pdfSha256,byteSize,privateContentIdentity,correctionReason}]}]}
requests = {schema:"aoou-requests-v1",items:[{requestId,status,reasonCode,retryable,rootOriginalId,currentRevisionId,revisionNumber,documentDate,sha256,byteSize,uploadedAt}]}
fingerprints = {schema:"aoou-fingerprints-v1",items:[{fingerprint,requestId,rootOriginalId,currentRevisionId}]}
events = {schema:"aoou-events-v1",items:[{eventId,eventType,caseId,orderId,rootOriginalId,revisionId,occurredAt,actorUserId}]}
safeAudits = {schema:"aoou-audits-v1",items:[{auditId,requestId,actorIdentity,mode,caseId,orderId,status,reasonCode,attemptedAt}]}
unchangedProcess = {schema:"aoou-process-v1",orderCompositionSha256,caseSha256,openingSha256,tasksSha256,decoySha256}
privateBlobs = {schema:"aoou-blobs-v1",stages:[{opaqueIdentity,byteSize,createdAtUtc}],finalized:[{opaqueIdentity,sha256,byteSize,finalizedAtUtc}]}
safeLogs = {schema:"aoou-logs-v1",items:[{sequence,event,correlationId,safeFields}]}
```

Nulls explicit; hashes lower-case. `correctionReason` exists only in protected verifier evidence and never result/log. MariaDB acceptance MUST use production repository plus this read-only evidence adapter on a fresh connection; in-memory Gate 2 may prove initial seam wiring but cannot satisfy persistence/CAS/failure matrix. Evidence inventory is observation only and MUST NOT feed maintenance candidate enumeration or mutation.

Worker config JSON has exact keys matching `AssignmentOrderOriginalWorkerConfig`, no extras, mode `real|injected_passive`, canonical fault enum/null; password file and config are verifier-owned mode 0600 outside repo. Each child opens the shared MariaDB DSN/prefix and private root, constructs real repository/storage through the declared factories, injects only fixed clock/IDs/inspector/fault/barrier, then builds the same application via verification factory. Objects/connections are never serialized.

Command pipe carries exactly one UTF-8 JSON line, maximum `29,000,000` bytes: keys match Command, enum backed strings, PDF bytes base64, nulls explicit. Result pipe carries exactly one canonical JSON line maximum `16384` bytes and no stdout noise. Barrier uses separate FDs: at `AFTER_FINGERPRINT_MISS_BEFORE_CAS` child writes `READY <requestId>\n`, flushes, then waits at most 5 monotonic seconds for exact `RELEASE <requestId>\n`; malformed/EOF/timeout returns exit `70`, no commit and redacted stderr. Parent must receive both READY lines before writing both RELEASE lines. Child exits `0` only after one valid Result line, otherwise nonzero. Parent bounds all reads/waits, closes pipes, terminates then reaps every child in `finally`, restores faults and removes only owned prefix/root/config/password files.
