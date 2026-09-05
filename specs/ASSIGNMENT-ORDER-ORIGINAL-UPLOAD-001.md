# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — безопасный приём оригинала распоряжения

Статус: **v37 GATE 1 REVIEW PENDING — MAINTENANCE CURSOR AMENDMENT**
Версия: **v37**
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

Every returned Result echoes the current invocation `requestId`. A distinct
request whose fingerprint matches winner evidence returns `REPLAYED` with the
loser's current request ID and every other evidence field copied from the
winner. This fingerprint replay creates no terminal request row, safe audit or
domain event for the loser and does not change the winner row. Therefore a
later retry of that distinct loser ID repeats authorized order/stream/fingerprint
proof rather than hitting step 5; it remains effect-idempotent. In an identical
two-worker race, evidence inventory contains only the winner accepted request,
revision and event; loser Result echoes loser ID. A different-race loser still
commits its specified terminal conflict result/audit.

Canonical identical-race worker A request is
`00000000-0000-4000-8000-000000000101`; worker B is
`00000000-0000-4000-8000-000000000102`; both clocks are
`2026-09-02T09:16:00Z`. Both publish READY before any RELEASE. Parent releases A,
requires its complete `ACCEPTED` result and fresh evidence proving commit, then
releases B. Thus A is the deterministic winner without removing B's concurrent
pre-CAS observation. B exact result line is:

```text
{"status":"replayed","reasonCode":null,"retryable":false,"requestId":"00000000-0000-4000-8000-000000000102","rootOriginalId":"original-0001","currentRevisionId":"revision-0002","revisionNumber":2,"documentDate":"2026-09-02","sha256":"4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784","byteSize":327,"uploadedAt":"2026-09-02T09:16:00Z"}
```

Post-race requests evidence is exactly:

```text
{"items":[{"byteSize":327,"currentRevisionId":"revision-0001","documentDate":"2026-09-01","reasonCode":null,"requestId":"00000000-0000-4000-8000-000000000001","retryable":false,"revisionNumber":1,"rootOriginalId":"original-0001","sha256":"4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784","status":"accepted","uploadedAt":"2026-09-02T09:15:30Z"},{"byteSize":327,"currentRevisionId":"revision-0002","documentDate":"2026-09-02","reasonCode":null,"requestId":"00000000-0000-4000-8000-000000000101","retryable":false,"revisionNumber":2,"rootOriginalId":"original-0001","sha256":"4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784","status":"accepted","uploadedAt":"2026-09-02T09:16:00Z"}],"schema":"aoou-requests-v1"}
```

Domain/fingerprint/events inventories contain initial plus exactly one revision-2
winner fact/fingerprint/event keyed to request A; audits contain accepted A and
no request B; no inventory contains request B. A same-B retry returns the exact
line above after repeating stream/fingerprint proof and leaves these inventories
byte-identical.

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
composition = installers [7001,7002], engineer 31
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
    case COMMIT_UNKNOWN_FOUND = 'commit_unknown_found';
    case COMMIT_UNKNOWN_NOT_FOUND = 'commit_unknown_not_found';
    case COMMIT_UNKNOWN_UNAVAILABLE = 'commit_unknown_unavailable';
    case COMMIT_BEFORE_RELEASE_FAILURE = 'commit_before_release_failure';
    case COMMIT_UNKNOWN_FOUND_RELEASE_FAILURE = 'commit_unknown_found_release_failure';
    case COMMIT_UNKNOWN_NOT_FOUND_RELEASE_FAILURE = 'commit_unknown_not_found_release_failure';
    case COMMIT_UNKNOWN_UNAVAILABLE_RELEASE_FAILURE = 'commit_unknown_unavailable_release_failure';
    case RESULT_SERIALIZATION_FAILURE = 'result_serialization_failure';
    case RESULT_OVERSIZE = 'result_oversize';
    case RESULT_WRITE_FALSE = 'result_write_false';
    case RESULT_WRITE_ZERO = 'result_write_zero';
    case RESULT_WRITE_SHORT_7 = 'result_write_short_7';
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

The three `COMMIT_UNKNOWN_*` values are verification-only composite real-
repository scripts, not production fault selectors. `FOUND` durably commits the
accepted transaction, returns `OUTCOME_UNKNOWN`, then allows exactly one fresh
terminal-request read on a new connection to observe FOUND. `NOT_FOUND` rolls
back before returning `OUTCOME_UNKNOWN`, then its one fresh connection proves
NOT_FOUND. `UNAVAILABLE` durably commits, returns `OUTCOME_UNKNOWN`, then makes
exactly the one fresh lookup unavailable; the current invocation returns
`FAILED/PERSISTENCE_OUTCOME_UNKNOWN`, while the next normal same-request worker
finds the durable row and returns `REPLAYED`. Each script is consumed once and
cannot affect earlier request/fingerprint reads or later retries. Production
factory binds no-op faults and cannot select these values by environment,
request, CLI or config; only verification worker config accepts them.

The four `*_RELEASE_FAILURE` values are likewise one-shot verification scripts.
They perform the exact commit/unknown behavior of their base name and then make
the one content-lease release return typed FAILED at phase `rolled_back`,
`unknown_found`, `unknown_not_found` or `unknown_unavailable` respectively.
Release failure preserves the already selected command Result and emits exactly
the phase-specific safe-log line defined by the lease contract; no second
release occurs. Plain `CONTENT_LEASE_RELEASE` covers committed success and, in a
real different-correction CAS loser, natural `commit_conflict`. No arbitrary
multi-fault list/string is accepted, and production binds none of these scripts.

The five `RESULT_*` cases are verification-worker-only result publisher scripts
after a durable command Result exists. Serialization failure and oversize force
their respective pre-write branches and emit zero result bytes. `WRITE_FALSE`
and `WRITE_ZERO` make the sole writer return false/zero and emit zero bytes.
`WRITE_SHORT_7` performs exactly one primitive that writes/returns the first
seven accepted-line bytes `{"statu`, then no second write. All five emit the
fixed stderr line, exit 70, preserve the committed command outcome and allow a
normal same-request worker to return exact `REPLAYED`. They cannot combine with
other faults, affect command/storage/repository behavior, or be selected by
production; production binds the native one-fwrite result publisher.

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

### Canonical schema and deterministic MariaDB setup

Task 3.1 SHALL implement the single public migration seam below. It is the only
Gate 2 entry point that may create or reconcile assignment-order-original
tables; application, evidence-reader, HTTP and worker runtime paths MUST NOT
invoke it.

```php
enum AssignmentOrderOriginalSchemaMigrationStatus: string
{
    case APPLIED = 'applied';
    case UNCHANGED = 'unchanged';
    case CONFLICT = 'conflict';
}

interface AssignmentOrderOriginalSchemaMigrationResult
{
    public function status(): AssignmentOrderOriginalSchemaMigrationStatus;
    public function schemaVersion(): int;
    /** @return list<string> */
    public function affectedTables(): array;
}

final class AssignmentOrderOriginalSchemaMigration
{
    public static function apply(
        \mysqli $database,
        string $tablePrefix = '',
    ): AssignmentOrderOriginalSchemaMigrationResult { /* exact migration */ }
}

final class AssignmentOrderOriginalSchemaMigrationUnavailable extends \RuntimeException {}

enum AssignmentOrderOriginalSchemaMigrationPhase: string
{
    case AFTER_SCHEMA_TABLE_CREATED = 'after_schema_table_created';
    case AFTER_SCHEMA_REVALIDATED_BEFORE_CAPABILITIES =
        'after_schema_revalidated_before_capabilities';
    case AFTER_CAPABILITY_ALTER_BEFORE_REVALIDATION =
        'after_capability_alter_before_revalidation';
}

interface AssignmentOrderOriginalSchemaMigrationObserver
{
    public function observe(
        AssignmentOrderOriginalSchemaMigrationPhase $phase,
        ?string $logicalTable,
    ): void;
}

interface AssignmentOrderOriginalSchemaMigrationApplication
{
    public function apply(
        \mysqli $database,
        string $tablePrefix = '',
    ): AssignmentOrderOriginalSchemaMigrationResult;
}

final class AssignmentOrderOriginalSchemaMigrationVerificationFactory
{
    public static function create(
        AssignmentOrderOriginalSchemaMigrationObserver $observer,
    ): AssignmentOrderOriginalSchemaMigrationApplication;
}

final class AssignmentOrderOriginalVerificationDatabaseFixture
{
    public static function seedExampleA(
        \mysqli $database,
        string $tablePrefix = '',
    ): void { /* verification-only DML */ }

    public static function cleanupExampleA(
        \mysqli $database,
        string $tablePrefix = '',
    ): void { /* bounded verification cleanup */ }
}

final class AssignmentOrderOriginalVerificationFixtureConflict extends \RuntimeException {}
final class AssignmentOrderOriginalVerificationFixtureUnavailable extends \RuntimeException {}
```

`schemaVersion()` is `1`. `apply()` accepts the same canonical prefix grammar as
the evidence config. A clean or compatible partial schema returns `APPLIED` and
the logical table names actually created/reconciled in manifest order; an exact
repeat returns `UNCHANGED` with an empty list; any non-equivalent existing
owned table returns `CONFLICT` with all conflicting logical table names in
binary order and performs no DDL. It never changes historical registration
facts or prerequisite process rows. No consumer may infer readiness from a
version row alone.

Version 1 owns these exact logical tables, in this creation/`affectedTables`
order (the validated prefix is prepended). All use InnoDB, `utf8mb4`, database
default collation; opaque IDs and hashes use `ascii`/`ascii_bin`. `UNSIGNED`,
nullability, column order and keys below are normative:

```text
fm2_assignment_order_original_roots:
  root_original_id varchar(80) PK; installation_case_id bigint unsigned;
  assignment_order_id bigint unsigned UNIQUE; current_revision_id varchar(80) UNIQUE;
  composition_identity varchar(160); composition_sha256 char(64);
  created_at_utc datetime(6); INDEX(installation_case_id,assignment_order_id)
fm2_assignment_order_original_revisions:
  revision_id varchar(80) PK; root_original_id varchar(80); revision_number int unsigned;
  previous_revision_id varchar(80) NULL UNIQUE; document_date date;
  uploaded_at_utc datetime(6); actor_user_id bigint unsigned; pdf_sha256 char(64);
  byte_size int unsigned; private_content_identity varchar(160) UNIQUE;
  correction_reason varchar(500) NULL; request_id char(36) UNIQUE;
  operation_fingerprint char(64) UNIQUE; event_type varchar(80);
  UNIQUE(root_original_id,revision_number); INDEX(root_original_id,revision_number);
  FK(root_original_id)->roots(root_original_id) RESTRICT;
  FK(previous_revision_id)->revisions(revision_id) RESTRICT
fm2_assignment_order_original_requests:
  request_id char(36) PK; mode varchar(20); installation_case_id bigint unsigned;
  assignment_order_id bigint unsigned; actor_identity varchar(160); status varchar(20);
  reason_code varchar(80) NULL; retryable tinyint unsigned; root_original_id varchar(80) NULL;
  current_revision_id varchar(80) NULL; revision_number int unsigned NULL;
  document_date date NULL; sha256 char(64) NULL; byte_size int unsigned NULL;
  uploaded_at_utc datetime(6) NULL; attempted_at_utc datetime(6);
  INDEX(installation_case_id,assignment_order_id,attempted_at_utc)
fm2_assignment_order_original_events:
  event_id bigint unsigned AUTO_INCREMENT PK; event_type varchar(80);
  installation_case_id bigint unsigned; assignment_order_id bigint unsigned;
  root_original_id varchar(80); revision_id varchar(80); occurred_at_utc datetime(6);
  actor_user_id bigint unsigned; UNIQUE(root_original_id,revision_id,event_type);
  INDEX(installation_case_id,assignment_order_id,event_id)
fm2_assignment_order_original_audits:
  audit_id bigint unsigned AUTO_INCREMENT PK; request_id char(36); actor_identity varchar(160);
  mode varchar(20); installation_case_id bigint unsigned; assignment_order_id bigint unsigned;
  status varchar(20); reason_code varchar(80) NULL; attempted_at_utc datetime(6);
  UNIQUE(request_id,status,reason_code); INDEX(installation_case_id,assignment_order_id,audit_id)
fm2_assignment_order_original_maintenance_requests:
  request_id char(36) PK; system_principal_id varchar(160); status varchar(20);
  reason_code varchar(80) NULL; retryable tinyint unsigned; scanned int unsigned;
  deleted int unsigned; retained int unsigned; failed int unsigned;
  next_cursor varchar(500) NULL; attempted_at_utc datetime(6)
fm2_assignment_order_original_maintenance_audits:
  audit_id bigint unsigned AUTO_INCREMENT PK; request_id char(36) UNIQUE;
  system_principal_id varchar(160); status varchar(20); reason_code varchar(80) NULL;
  retryable tinyint unsigned; scanned int unsigned; deleted int unsigned; retained int unsigned;
  failed int unsigned; attempted_at_utc datetime(6)
```

Every non-null hash has `^[0-9a-f]{64}$`; `retryable` is `0|1`; revision and
byte size are positive; accepted/replayed request evidence is all non-null and
rejected/conflict evidence is all null. Roots current revision must name the
same root; this cross-row invariant and CAS are enforced transactionally by the
repository. Structural equivalence compares exact ordered columns, normalized
types/default/nullability, engine/charset/collation, PK, unique/index column
order, FKs/actions and CHECK semantics; extra owned columns/keys/checks or
missing/different members conflict. A compatible partial deployment may contain
only a leading subset of the ordered complete tables; populated exact tables
are preserved byte-for-byte.

The prerequisite `fm2_process_user_capabilities` CHECK has exactly two accepted
semantic states. V4 is the exact set `assignment_order.prepare`,
`assignment_order.confirm_registration`, `installation.open`,
`construction_control_engineer`. V5 is V4 plus exactly
`assignment_order.original.upload` and `assignment_order.original.correct`.
There MUST be exactly one capability-enum CHECK candidate. A candidate is a
normalized top-level `capability IN (...)` expression whose only referenced
column is `capability`; the separate `capability <> ... OR position_snapshot`
engineer-position CHECK is not a candidate. Multiple candidates conflict even
when one is exact. Upload-only, correct-only,
unexpected superset/subset, duplicate candidates, unsafe candidate name or any
other expression is `CONFLICT`. Conflict `affectedTables()` contains every
conflicting original logical table plus `fm2_process_user_capabilities`, binary
sorted, and performs no DDL. `fm2_process_user_capabilities` appears in conflict
output if and only if its candidate classification conflicts. Exact V5 repeats
unchanged and never appears in `affectedTables()`.

Migration order is: validate prefix → inspect all seven original tables and the
capability CHECK → return all conflicts without DDL → create only missing
leading-suffix original tables in manifest order, invoking
`AFTER_SCHEMA_TABLE_CREATED` with that logical table after each durable CREATE
→ re-read and require the full exact seven-table schema →
`AFTER_SCHEMA_REVALIDATED_BEFORE_CAPABILITIES` with null table → if and only if
prior state was exact V4, replace that one safe-named CHECK with exact V5 as the
last DDL → `AFTER_CAPABILITY_ALTER_BEFORE_REVALIDATION` with null table → fresh
re-read exact V5 → return. `APPLIED.affectedTables()` lists created original
tables in manifest order and then `fm2_process_user_capabilities` only when a
fresh reread proves the upgrade durable; `UNCHANGED` is empty.

MariaDB DDL implicitly commits, so failure is fail-closed rather than falsely
atomic. Any query/create/revalidation/ALTER/observer failure throws fixed
`AssignmentOrderOriginalSchemaMigrationUnavailable` (message exactly
`AssignmentOrderOriginalSchemaMigrationUnavailable`, code 0,
previous null). A create failure may leave only an exact leading partial schema
with V4 capability; retry revalidates and resumes. Observer failure occurs after
full schema revalidation and before capability ALTER, so V4 remains. Capability
ALTER or post-ALTER observer failure triggers one fresh candidate reread: exact
V5 returns `APPLIED` with capability in `affectedTables`; exact V4 throws
unavailable and retry attempts the last publication; conflict throws
unavailable without further DDL; reread failure throws unavailable and permits
the externally unknown but safe state full-exact-schema+V4-or-V5, whose retry
classifies V4/V5 before any DDL. No failure may expose V5 with incomplete or
non-exact original schema. The static production `apply()` binds a no-op observer. Only
`AssignmentOrderOriginalSchemaMigrationVerificationFactory` accepts an injected
observer; production bootstrap/runtime cannot select it by env/request/CLI/
global and neither application nor HTTP calls either migration seam.

The exact FK set is: revisions.`root_original_id` → roots.`root_original_id`;
revisions.`previous_revision_id` → revisions.`revision_id`; nullable
requests.`root_original_id` → roots.`root_original_id`; nullable
requests.`current_revision_id` → revisions.`revision_id`; events root/revision
to roots/revisions; audits.`request_id` → requests.`request_id`; and maintenance
audits.`request_id` → maintenance requests.`request_id`. Every action is
`ON UPDATE RESTRICT ON DELETE RESTRICT`; there are no other FKs.

The exact semantic CHECK set is:

```text
roots: composition_sha256 REGEXP '^[0-9a-f]{64}$'
revisions: revision_number>=1; pdf_sha256/fingerprint each lower-hex-64;
  byte_size BETWEEN 1 AND 20971520; event_type IN
  ('assignment_order_original_accepted','assignment_order_original_corrected');
  (revision_number=1 AND previous_revision_id IS NULL AND correction_reason IS NULL
   AND event_type='assignment_order_original_accepted') OR
  (revision_number>1 AND previous_revision_id IS NOT NULL
   AND CHAR_LENGTH(TRIM(correction_reason)) BETWEEN 1 AND 500
   AND event_type='assignment_order_original_corrected')
requests: request_id canonical lower UUID; mode IN ('initial','correction');
  status IN ('accepted','replayed','rejected','conflict'); retryable=0;
  accepted|replayed => reason_code IS NULL and all seven evidence fields
  (root_original_id,current_revision_id,revision_number,document_date,sha256,
  byte_size,uploaded_at_utc) non-null;
  rejected => reason_code IN ('authorization_denied','invalid_command','order_not_found',
  'composition_not_confirmed','invalid_composition','file_too_large','not_pdf',
  'invalid_pdf','unsafe_pdf','future_document_date','no_changes') and all evidence null;
  conflict => reason_code IN ('semantic_collision','stale_revision','target_not_found',
  'target_not_current','initial_already_exists') and all evidence null
events: event_type IN ('assignment_order_original_accepted',
  'assignment_order_original_corrected')
audits: request_id canonical lower UUID; mode IN ('initial','correction');
  status IN ('accepted','rejected','conflict'); reason_code follows the same
  success/rejected/conflict truth sets as requests
maintenance requests: request_id canonical lower UUID;
  status IN ('completed','replayed','rejected','partial');
  completed|replayed => reason_code IS NULL AND retryable=0;
  rejected => reason_code IN ('invalid_command','authorization_denied') AND retryable=0;
  partial => reason_code IN ('locked','storage_failure') AND retryable=1;
  scanned=deleted+retained+failed
maintenance audits: request_id canonical lower UUID;
  status IN ('completed','rejected','partial');
  completed => reason_code IS NULL AND retryable=0;
  rejected => reason_code IN ('invalid_command','authorization_denied') AND retryable=0;
  partial => reason_code IN ('locked','storage_failure') AND retryable=1;
  scanned=deleted+retained+failed
```

Every `request_id` CHECK is exactly
`REGEXP '^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$'`.
On roots, revisions, requests and events, each non-null root/revision ID CHECK
is exactly `CHAR_LENGTH(value) BETWEEN 1 AND 80 AND value NOT REGEXP
'[[:cntrl:]/\\\\]'`; revisions.`private_content_identity` uses the same
expression with upper bound 160. ASCII/`ascii_bin` columns reject non-ASCII.
Roots additionally CHECK that `current_revision_id` is non-empty; the
repository transaction inserts the root with its generated revision identity,
inserts that matching revision before commit, and on correction CAS-updates
current only to a revision inserted for the same root. This invariant has no
FK because MariaDB FKs are not deferrable and a roots↔revisions cycle would make
initial insertion impossible. There are no other CHECKs.
Safe generated constraint names are implementation details; the normalized
expressions, columns and actions above are the complete equivalence oracle.

`seedExampleA()` is a verification-only DML setup seam, callable only after the
approved prerequisite process migrations and this migration are compatible.
It inserts the section-12 Example A prerequisites and no original/request/event/
audit/blob fact: active actor `18` with only the exact upload/correct grants;
case `4512`; order `81`; composition identity `composition-81-v1`, hash
`1111111111111111111111111111111111111111111111111111111111111111`, installers
`7001,7002` and engineer `31`; fixed case/opening/tasks/checklist/decoy process
projections used by section 16. The exact canonical projection literals and
expected SHA-256 values are:

```text
orderCompositionSha256=388c7d94b3cf91235dabddf26398ac05f754d3d12a0b41a7a91ac3d5370faba5
{"caseId":4512,"compositionIdentity":"composition-81-v1","engineerUserId":31,"installers":[7001,7002],"orderId":81}
caseSha256=b28f40fe02e9b4ca3981a5edaf5e165f9f95531e38c87bc70a8458444b2ace84
{"actualStartDate":null,"caseId":4512,"processState":"prepared"}
openingSha256=89c2844c0f723aacd7b7982b36d6297df53a3d2f2b44a268212ab43149687f42
{"actualStartDate":null,"openedAt":null,"openedByUserId":null}
tasksSha256=272d922aa2cdcad49bd98141062fc752eb4f31690a720b46c2fa7a0e1b0fe799
{"items":[{"assigneeRole":"fkr_operator","status":"open","taskId":9001,"taskType":"assignment_order_original_upload"}]}
checklistSha256=ccb8260ee585db0d0cec53f376d71a66869cf1eca3f287e5a5d7a4e91a04d546
{"items":[{"availability":"blocked_until_opening","checklistIdentity":"installation-case-4512"}]}
decoySha256=963ca80eddc50543eb940cf813923bd451d0974585a529e7880107df6982e2ca
{"items":[{"caseId":9999,"marker":"fixture-decoy-v1"}]}
```

The fixture rows are actor `18` active; role `5301/fkr_operator` active and
assigned to actor 18; actor grants exactly upload/correct; engineer `31` active
in active role `5302/control_engineer`; case `4512` links legacy identity
`94512`, state `prepared`, null opening fields; order `81` belongs to case 4512,
version 1/status `prepared`, date `2026-09-01`; installers `7001` then `7002`
have active snapshot status and `assign`; task `9001` is the literal task above;
the checklist and decoy projections are the literals above. An exact repeat is
a no-op; any occupied
identity with different values throws fixed
`AssignmentOrderOriginalVerificationFixtureConflict` before DML. The fixture
performs no DDL, creates no original evidence, accepts no arbitrary SQL/callback
and is never referenced by production composition. Gate 2 owns an isolated
database/prefix. `seedExampleA()` and `cleanupExampleA()` use one `SERIALIZABLE`
transaction and lock all exact identities in binary dependency order. Conflict
rolls back before commit. Cleanup validates every owned row byte-for-byte, then
deletes only those exact fixture rows in reverse dependency order; absent rows
are a no-op and any drift conflicts without deletion. DB/commit/rollback
failure throws `AssignmentOrderOriginalVerificationFixtureUnavailable`.
Both exception classes have fixed message equal to their class basename,
integer code `0` and `previous=null`. The test then drops only its separately
validated task-owned database; no prefix-derived table drop is permitted.
the evidence reader remains read-only and receives no fixture dependency.

All fixture timestamps are `2026-09-02T09:00:00Z`; nullable values not named
above are null. Actor is `Тестовый Оператор ФКР` / `test-fkr@example.invalid`,
engineer is `Тестовый Инженер` / `test-engineer@example.invalid`; both have
session version 1 and no credential. Role names are `Сотрудник ФКР` and
`Инженер строительного контроля`. Installer snapshots are respectively
`Тестовый Монтажник 7001` and `Тестовый Монтажник 7002`, position `Монтажник`,
status `employed`, employed-from `2026-01-01`, no employed-to, workforce source
`TEST-USER`, source-updated-at `2026-09-01T00:00:00Z`, valid-from
`2026-09-01`, no valid-to, action `assign`. Order kind is `initial`, engineer
snapshots match engineer name/position, organization form is `brigade`, previous
order is null, address `Тестовая улица, 1`, entrance `1`, registration number
snapshot `TEST-4512`, planned dates `2026-10-01`/`2026-10-31`, PTO date null,
prepared-at `2026-09-01T09:00:00Z`, prepared-by actor 18. Case created/updated
timestamps are the fixture timestamp and lock version is 1. These values and
only these values define repeat versus conflict.

The production evidence reader derives `checklistSha256` only from the exact
target `fm2_installation_cases` row: checklist identity is
`installation-case-<caseId>`; availability is `available` exactly when
`process_state='working'`, `actual_start_date`, `opened_at` and
`opened_by_user_id` are all non-null, otherwise `blocked_until_opening`.
Original tables, assignment-order status and process tasks are not inputs, so
accepting an original cannot change this projection. The reader derives
`decoySha256` from every other `fm2_installation_cases` row under the configured
prefix, numerically ordered by case ID, as `{caseId,marker}` where `marker` is
that row's exact `process_state`; zero other rows yields `{"items":[]}`.
Example A therefore also seeds case `9999`, legacy identity `99999`,
`process_state='fixture-decoy-v1'`, all opening fields null, fixture timestamps
and lock version 1. These reads use the same fresh read-only connection and no
fixture callback or original table.

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
        AssignmentOrderOriginalMaintenanceAuthorization $authorization,
    ): AssignmentOrderOriginalMaintenanceApplication { /* production bindings only */ }
}

final readonly class AssignmentOrderOriginalMaintenanceAuthorization
{
    public function __construct(
        public string $systemPrincipalId,
        public string $capability,
    ) {}
}

final class AssignmentOrderOriginalMaintenanceVerificationFactory
{
    public static function create(
        AssignmentOrderOriginalMaintenanceDependencies $dependencies,
    ): AssignmentOrderOriginalMaintenanceApplication { /* same maintenance owner */ }
}

enum AssignmentOrderOriginalPrivateOrphanFixtureKind: string
{
    case ABANDONED_STAGE = 'abandoned_stage';
    case FINALIZED_CONTENT = 'finalized_content';
}

final readonly class AssignmentOrderOriginalPrivateOrphanFixtureCommand
{
    public function __construct(
        public AssignmentOrderOriginalPrivateOrphanFixtureKind $kind,
        public string $opaqueIdentity,
        public string $bytes,
        public string $createdOrFinalizedAtUtc,
    ) {}
}

interface AssignmentOrderOriginalPrivateOrphanFixture
{
    public function create(
        AssignmentOrderOriginalPrivateOrphanFixtureCommand $command,
    ): void;
}

final class AssignmentOrderOriginalPrivateOrphanFixtureFactory
{
    public static function create(
        string $privateStorageRoot,
        string $ownershipToken,
        AssignmentOrderOriginalClock $clock,
        AssignmentOrderOriginalProductionConfig $productionConfig,
        AssignmentOrderOriginalFaultInjector $faults,
    ): AssignmentOrderOriginalPrivateOrphanFixture;
}

final class AssignmentOrderOriginalPrivateOrphanFixtureConflict extends \RuntimeException {}
final class AssignmentOrderOriginalPrivateOrphanFixtureUnavailable extends \RuntimeException {}

final class AssignmentOrderOriginalRealMaintenanceVerificationFactory
{
    public static function create(
        \mysqli $database,
        AssignmentOrderOriginalProductionConfig $config,
        AssignmentOrderOriginalMaintenanceAuthorization $authorization,
        AssignmentOrderOriginalClock $clock,
        AssignmentOrderOriginalFaultInjector $faults,
    ): AssignmentOrderOriginalMaintenanceApplication;
}

Production maintenance authorization is trusted operator composition, not a
user/role grant and not stored in `fm2_process_user_capabilities`.
`systemPrincipalId` matches `[A-Za-z0-9._:-]{1,160}` and `capability` must be
exactly `assignment_order.original.storage.reconcile`. Factory rejects any
invalid/other value with `InvalidArgumentException` message exactly
`Invalid maintenance authorization.` before DB/storage access. The bound
authorizer returns ALLOWED only when both command principal and requested exact
capability byte-equal the configured pair; every mismatch is DENIED. Production
bootstrap must construct the DTO from trusted deployment configuration and
cannot select it from request/HTTP/CLI command payload or mutable global.
Canonical TEST-USER verifier principal is `test-maintenance-01` with the exact
reconcile capability. No wildcard/list/role inference or user capability row is
created.

The private-orphan fixture is verification-only and uses the same production
storage path validator, ownership/mode checks, filename codec, atomic write/
rename/fsync primitives, metadata grammar and digest-lock exclusion domain as
upload/maintenance. It accepts only task-owned root, opaque identity grammar and
UTC second timestamp not later than the injected verifier clock. `bytes` size
is `1..20,971,520`; finalized kind computes its own SHA-256 and immutable
metadata, while abandoned kind creates a closed non-final stage. Exact replay
is a no-op; same identity with different kind/bytes/time throws
`AssignmentOrderOriginalPrivateOrphanFixtureConflict` before mutation; invalid
shape/root/marker/time/bytes or primitive failure throws
`AssignmentOrderOriginalPrivateOrphanFixtureUnavailable`. Both exceptions have
message equal to class basename, code 0 and previous null. Precedence is command
scalar/identity/bytes grammar → root authority → clock/future → existing
exact replay/collision → primitive creation. It creates no request, audit,
event, revision or reference row and cannot delete.

Canonical eligible fixtures are abandoned `orphan-stage-0001` with bytes
`stage-orphan-v1` and finalized `orphan-content-0001` with bytes
`finalized-orphan-v1`, both timestamp `2026-09-02T07:00:00Z`. Sizes are exactly
`15` and `19`; finalized SHA-256 is
`edebbe397df1e6932d83cbf742512b480524c89bc6e9b6b679fafec5896db24f`.
Timestamp is stored in owned metadata and is the only candidate-age input;
filesystem mtime/ctime remain primitive metadata and are ignored.

The fixture root is an absolute canonical directory outside repository
realpath, pre-created by verifier with owner/root UID and exact mode `0700`, with
no symlink in root/member graph. Parent pre-creates regular marker
`.aoou-verifier-owner` mode `0600`, same UID, exact bytes
`aoou-private-orphan-fixture-v1\n<ownershipToken>\n`, token 32 lower hex.
Factory realpath/lstat-validates root, every parent/member and marker before any
write and again before verifier removal. Missing/wrong UID/mode/type/token,
repo-contained path, symlink/hardlink (`nlink!=1`) or changed identity is fixed
unavailable; fixture never creates/repairs root/marker and never follows links.
The separately trusted configured production private root must itself pass its
production validation, and task root realpath must be neither equal to nor an
ancestor/descendant of that production root; overlap is fixed unavailable before
fixture-root member reads or writes.
Production bootstrap/application/HTTP cannot construct or select this factory;
verifier cleans only through maintenance plus revalidated task-owned root removal.

`AssignmentOrderOriginalRealMaintenanceVerificationFactory` binds real
production repository/private storage/evidence layout and injects only explicit
clock/fault ports; it has no fake repository/storage and production cannot
select it. Canonical example clock is `2026-09-02T09:00:00Z`. Command request is
`00000000-0000-4000-8000-000000000201`, principal `test-maintenance-01`, cutoff
`2026-09-02T07:30:00Z`, limit `10`, cursor null. Candidates order by
`(07:00:00Z,orphan-content-0001)` then `(07:00:00Z,orphan-stage-0001)`. Expected
Result is `COMPLETED/null/false`, scanned/deleted/retained/failed `2/2/0/0`,
nextCursor null; same request is `REPLAYED` with identical counts and no new
delete/audit. Recursively sorted evidence is exactly:

```text
{"items":[{"attemptedAt":"2026-09-02T09:00:00Z","deleted":2,"failed":0,"nextCursor":null,"reasonCode":null,"requestId":"00000000-0000-4000-8000-000000000201","retained":0,"retryable":false,"scanned":2,"status":"completed","systemPrincipalId":"test-maintenance-01"}],"schema":"aoou-maintenance-requests-v1"}
{"items":[{"attemptedAt":"2026-09-02T09:00:00Z","auditId":1,"deleted":2,"failed":0,"reasonCode":null,"requestId":"00000000-0000-4000-8000-000000000201","retained":0,"retryable":false,"scanned":2,"status":"completed","systemPrincipalId":"test-maintenance-01"}],"schema":"aoou-maintenance-audits-v1"}
```

Candidate timestamp exactly equal cutoff is eligible; the next representable
fixture second `07:30:01Z` is not. Fixture timestamp `09:00:01Z` is future and
rejected before mutation.

Immediately after the two canonical fixture creates, the evidence reader's
exact private blob inventory is:

```text
{"finalized":[{"byteSize":19,"finalizedAtUtc":"2026-09-02T07:00:00Z","opaqueIdentity":"orphan-content-0001","sha256":"edebbe397df1e6932d83cbf742512b480524c89bc6e9b6b679fafec5896db24f"}],"schema":"aoou-blobs-v1","stages":[{"byteSize":15,"createdAtUtc":"2026-09-02T07:00:00Z","opaqueIdentity":"orphan-stage-0001"}]}
```

Exact replay leaves it byte-identical. Every invalid/future/collision rejection
and injected `STAGE|STAGE_WRITE|PRIVATE_FINALIZE` primitive failure throws its
fixed exception and leaves the before inventory byte-identical; fixture fault
scripts are one-shot and production cannot select them. After canonical
maintenance completion/replay the inventory is exactly
`{"finalized":[],"schema":"aoou-blobs-v1","stages":[]}`.

Boundary and newer sensitivity use separate isolated DB/root runs with the same
09:00 clock, principal/cutoff/limit/cursor and audit ID 1. Boundary fixture is
abandoned `boundary-stage-0001`, bytes `boundary-orphan-v1` (18 bytes), timestamp
`07:30:00Z`, request `...0202`: result/replay is COMPLETED/REPLAYED with
`scanned=1,deleted=1,retained=0,failed=0,nextCursor=null`; post inventory empty.
Newer fixture is abandoned `newer-stage-0001`, bytes `newer-orphan-v1` (15
bytes), timestamp `07:30:01Z`, request `...0203`: result/replay has
`scanned=0,deleted=0,retained=0,failed=0,nextCursor=null`; post inventory remains:

```text
{"finalized":[],"schema":"aoou-blobs-v1","stages":[{"byteSize":15,"createdAtUtc":"2026-09-02T07:30:01Z","opaqueIdentity":"newer-stage-0001"}]}
```

Boundary/newer exact request then audit lines are:

```text
{"items":[{"attemptedAt":"2026-09-02T09:00:00Z","deleted":1,"failed":0,"nextCursor":null,"reasonCode":null,"requestId":"00000000-0000-4000-8000-000000000202","retained":0,"retryable":false,"scanned":1,"status":"completed","systemPrincipalId":"test-maintenance-01"}],"schema":"aoou-maintenance-requests-v1"}
{"items":[{"attemptedAt":"2026-09-02T09:00:00Z","auditId":1,"deleted":1,"failed":0,"reasonCode":null,"requestId":"00000000-0000-4000-8000-000000000202","retained":0,"retryable":false,"scanned":1,"status":"completed","systemPrincipalId":"test-maintenance-01"}],"schema":"aoou-maintenance-audits-v1"}
{"items":[{"attemptedAt":"2026-09-02T09:00:00Z","deleted":0,"failed":0,"nextCursor":null,"reasonCode":null,"requestId":"00000000-0000-4000-8000-000000000203","retained":0,"retryable":false,"scanned":0,"status":"completed","systemPrincipalId":"test-maintenance-01"}],"schema":"aoou-maintenance-requests-v1"}
{"items":[{"attemptedAt":"2026-09-02T09:00:00Z","auditId":1,"deleted":0,"failed":0,"reasonCode":null,"requestId":"00000000-0000-4000-8000-000000000203","retained":0,"retryable":false,"scanned":0,"status":"completed","systemPrincipalId":"test-maintenance-01"}],"schema":"aoou-maintenance-audits-v1"}
```

Each run has exactly one request plus audit; replay leaves both byte-identical.
No boundary/newer run shares DB/root state.

final class AssignmentOrderOriginalEvidenceUnavailable extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Assignment-order original evidence unavailable.', 0, null);
    }
}

final readonly class AssignmentOrderOriginalEvidenceReaderConfig
{
    public function __construct(
        public string $databaseHost,
        public int $databasePort,
        public string $databaseName,
        public string $databaseUser,
        public string $databasePasswordFile,
        public string $tablePrefix,
        public string $privateStorageRoot,
        public string $safeLogFile,
    ) {}
}

final class AssignmentOrderOriginalEvidenceReaderFactory
{
    public static function create(
        AssignmentOrderOriginalEvidenceReaderConfig $config,
    ): AssignmentOrderOriginalEvidenceReader { /* fresh read-only production adapters */ }
}

interface AssignmentOrderOriginalEvidenceReader
{
    public function domainCanonicalJson(int $caseId, int $orderId): string;
    public function requestsCanonicalJson(): string;
    public function fingerprintsCanonicalJson(): string;
    public function eventsCanonicalJson(): string;
    public function safeAuditsCanonicalJson(): string;
    public function maintenanceRequestsCanonicalJson(): string;
    public function maintenanceAuditsCanonicalJson(): string;
    public function unchangedProcessCanonicalJson(int $caseId, int $orderId): string;
    public function privateBlobsCanonicalJson(): string;
    public function safeLogsCanonicalJson(): string;
    public function close(): void;
}
```

The evidence factory is a verification composition seam, not a second command
repository. Config is serializable and exact: host matches
`[A-Za-z0-9.:[\]_-]{1,255}`; port is `1..65535`; database matches
`[A-Za-z0-9_]{1,64}`; user matches `[A-Za-z0-9_.-]{1,32}`; prefix matches
`[A-Za-z0-9_]{0,25}`. Password file, private root and safe-log file are absolute
canonical paths outside the repository, contain no NUL/control or `..`
component and are not symlinks. Private root is an existing owner/root-owned
directory with mode `0700|0750`; password and safe-log are existing regular
owner/root-owned files with mode `0600`. Factory never creates/repairs them.
Password file contains `1..1024` bytes, each in exact ASCII range `0x20..0x7E`,
except one optional final LF which is removed before that check; empty result,
TAB, DEL, non-ASCII or any other newline is invalid. Password
bytes never appear in result/log/error output. All scalar/path/metadata checks
complete before password content or database access; invalid config throws only
`AssignmentOrderOriginalEvidenceUnavailable`.

`create()` opens one fresh `utf8mb4` MariaDB connection and binds read-only
production evidence/private-inventory/safe-log adapters. It knows only the exact
canonical original-evidence tables introduced by this change; it MUST NOT query
`information_schema`, infer schema, issue DDL/DML, use command repository
objects, or accept test callbacks/selectors. Every canonical method performs a
fresh read through that connection and returns the closed versioned JSON shape
defined in section 16. `privateBlobsCanonicalJson()` and
`safeLogsCanonicalJson()` use only the two configured owned paths and redact
absolute names. `close()` attempts every live descriptor/connection exactly
once and caches its outcome; later calls perform no I/O. If the first close had
any failure, that call and every later call throw a fresh fixed
`AssignmentOrderOriginalEvidenceUnavailable`; otherwise every call returns
normally. Factory construction and every read likewise either return their
complete declared value or throw only that fixed exception with message/code/
previous exactly as declared, without partial JSON or secret diagnostics.

```php
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
        public string $safeLogFile,
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

Candidate page includes `ABANDONED_STAGE` and `FINALIZED_CONTENT`, ordered by binary `(createdOrFinalizedAtUtc, opaqueIdentity)` strictly after cursor, at most batchLimit and timestamp `<= cutoffUtc`; cursor is storage-generated canonical encoding of the last pair defined below. Under candidate lock, abandoned stage is deleted directly after age/type revalidation; finalized content first requires repository reference lookup. For every page `scanned = deleted + retained + failed`: successful/already-absent delete increments deleted; referenced or locked increments retained; reference/storage error increments failed. `nextCursor` equals page cursor when more may remain, otherwise null. `PARTIAL/LOCKED` applies when all failures were locks; any storage failure uses `PARTIAL/STORAGE_FAILURE`; both retryable true. `COMPLETED|REPLAYED|REJECTED` retryable false; `FAILED` retryable true. Result+audit are one terminal request transaction except `FAILED`; audit failure means `FAILED/PERSISTENCE_FAILURE` and no terminal result.

Cursor payload is exact compact UTF-8 JSON in key order
`{"v":1,"at":"<UTC-second>","id":"<opaqueIdentity>"}` with no whitespace/
extra keys. Its bytes length is `42+idByteLength` (`43..202`), encoded by RFC
4648 URL-safe base64 (`+`→`-`, `/`→`_`) with all trailing `=` removed; cursor
length is `58..270` and alphabet `[A-Za-z0-9_-]`. Decoder restores minimal
padding, strict-decodes, requires UTF-8/exact keys/types, `v===1`, canonical UTC
second, identity grammar, and byte-equal re-encode. Cursor pair need not still
exist because prior page deletion is expected; it is solely an exclusive sort
position. Unsupported version/shape, noncanonical encoding/padding/alphabet or
invalid pair is `REJECTED/INVALID_COMMAND` before clock/candidate access.
Canonical cursor after `(2026-09-02T07:00:00Z,orphan-content-0001)` is:

```text
eyJ2IjoxLCJhdCI6IjIwMjYtMDktMDJUMDc6MDA6MDBaIiwiaWQiOiJvcnBoYW4tY29udGVudC0wMDAxIn0
```

The payload is 62 bytes and cursor 83 bytes. With batchLimit 1 in the canonical
two-orphan example, page 1 deletes content and returns exactly this cursor;
page 2 with a new request ID and this cursor deletes stage and returns null.

Evidence JSON uses recursively key-sorted UTF-8 JSON, integer IDs, UTC strings and arrays ordered by root/revision number/event ID/blob identity. Exact top-level shapes, with no additional keys:

```text
domain = {schema:"aoou-evidence-v1",caseId,orderId,roots:[{rootOriginalId,currentRevisionId,compositionIdentity,compositionSha256,revisions:[{revisionId,revisionNumber,previousRevisionId,documentDate,uploadedAt,actorUserId,pdfSha256,byteSize,privateContentIdentity,correctionReason}]}]}
requests = {schema:"aoou-requests-v1",items:[{requestId,status,reasonCode,retryable,rootOriginalId,currentRevisionId,revisionNumber,documentDate,sha256,byteSize,uploadedAt}]}
fingerprints = {schema:"aoou-fingerprints-v1",items:[{fingerprint,requestId,rootOriginalId,currentRevisionId}]}
events = {schema:"aoou-events-v1",items:[{eventId,eventType,caseId,orderId,rootOriginalId,revisionId,occurredAt,actorUserId}]}
safeAudits = {schema:"aoou-audits-v1",items:[{auditId,requestId,actorIdentity,mode,caseId,orderId,status,reasonCode,attemptedAt}]}
maintenanceRequests = {items:[{attemptedAt,deleted,failed,nextCursor,reasonCode,requestId,retained,retryable,scanned,status,systemPrincipalId}],schema:"aoou-maintenance-requests-v1"}
maintenanceAudits = {items:[{attemptedAt,auditId,deleted,failed,reasonCode,requestId,retained,retryable,scanned,status,systemPrincipalId}],schema:"aoou-maintenance-audits-v1"}
unchangedProcess = {schema:"aoou-process-v1",orderCompositionSha256,caseSha256,openingSha256,tasksSha256,checklistSha256,decoySha256}
privateBlobs = {schema:"aoou-blobs-v1",stages:[{opaqueIdentity,byteSize,createdAtUtc}],finalized:[{opaqueIdentity,sha256,byteSize,finalizedAtUtc}]}
safeLogs = {schema:"aoou-logs-v1",items:[{sequence,event,correlationId,safeFields}]}
```

Nulls explicit; hashes lower-case. `unchangedProcessCanonicalJson(caseId,
orderId)` first requires that both target rows exist and the order belongs to
the case; missing/mismatched target throws the fixed
`AssignmentOrderOriginalEvidenceUnavailable` without partial JSON. Its
`checklistSha256` hashes exactly one case-owned checklist projection described
above; a valid target therefore never has an empty checklist projection.
`decoySha256` includes the explicit empty projection when no other case exists.
`checklistSha256` is independent from `tasksSha256`; neither digest may stand in
for the other.
Maintenance request/audit items use the same recursive binary key sort and are
ordered by binary request ID/audit ID. Exact item key order after canonical sort
is request `{attemptedAt,deleted,failed,nextCursor,reasonCode,requestId,retained,
retryable,scanned,status,systemPrincipalId}` and audit `{attemptedAt,auditId,
deleted,failed,reasonCode,requestId,retained,retryable,scanned,status,
systemPrincipalId}`. A terminal maintenance operation must appear atomically in
both methods or neither; replay changes neither inventory. Reader failures have
the same fixed evidence-unavailable/no-partial contract.
`correctionReason` exists only in protected verifier evidence and never
result/log. MariaDB acceptance MUST use production repository plus this
read-only evidence adapter on a fresh connection; in-memory Gate 2 may prove
initial seam wiring but cannot satisfy persistence/CAS/failure matrix. Evidence
inventory is observation only and MUST NOT feed maintenance candidate
enumeration or mutation.

Worker config JSON has exact keys matching `AssignmentOrderOriginalWorkerConfig`, no extras, mode `real|injected_passive`, canonical fault enum/null; password file, safe-log file and config are verifier-owned mode 0600 outside repo. `safeLogFile` is an absolute canonical existing regular owner/root-owned non-symlink path outside the repository, with no NUL/control or `..` component, validated before password content or database access under the same rules as evidence config. The worker never creates or repairs it. Each child binds its real `AssignmentOrderOriginalSafeLogObserver` exclusively to that exact file; the independent evidence-reader config MUST use the same canonical path identity for the corresponding run. No environment variable, mutable global, default basename, private-root convention or callback may select another log target. Each child opens the shared MariaDB DSN/prefix and private root, constructs real repository/storage through the declared factories, injects only fixed clock/IDs/inspector/fault/barrier, then builds the same application via verification factory. Objects/connections are never serialized.

`databaseDsn` has one exact canonical ASCII form, with segments in this literal
order and no whitespace, percent encoding, duplicates or extras:

```text
host=<host>;port=<port>;database=<database>;charset=utf8mb4
```

`host` is exactly one of two disjoint forms: (1) an ASCII hostname/IPv4 token
matching `[A-Za-z0-9](?:[A-Za-z0-9._-]{0,253}[A-Za-z0-9])?`, containing no
colon/bracket and passed byte-exact; or (2) one balanced `[<ipv6>]` whose inner
value is lower-case canonical IPv6, passes `filter_var(...,
FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)`, and is byte-equal to
`inet_ntop(inet_pton(inner))`; only the brackets are removed for mysqli. Zone
IDs, raw/unbalanced/nested brackets, raw colon, trailing dot and `p:` persistent
prefix are invalid. `port` is canonical decimal `1..65535` without leading
zero, and `database` matches `[A-Za-z0-9_]{1,64}`. `databaseUser` matches
`[A-Za-z0-9_.-]{1,32}` and the password-file grammar is the evidence-config
grammar already defined above. Worker parsing produces exactly the mysqli tuple
`(host,databaseUser,passwordBytes,database,port)` and then calls
`set_charset('utf8mb4')`; unix sockets, persistent prefixes, query parameters,
driver/options or charset alternatives are forbidden. Invalid DSN/user/config
fails worker configuration with exit `70` and fixed redacted stderr before
password-file content, DB access, private storage or safe-log write.

Every worker-controlled exit `70` writes exactly the ASCII bytes
`ASSIGNMENT_ORDER_ORIGINAL_WORKER_FAILED\n` to process stderr, once, with the
single final LF shown and no other stderr bytes. The result FD receives zero
bytes for every failure before the one result-write primitive; only that
primitive's short-write failure may leave the bounded untrusted prefix defined
below. Invalid config/DSN/path/FD validation occurs before reading the command
FD and before writing any barrier bytes, so command input remains unread and
barrier output is empty. A malformed/EOF/timeout barrier failure may occur only
after its exact `READY <requestId>\n` was already written; it writes no further
barrier bytes. No SQL, path, DSN, user, password, request, ID, filename,
exception or diagnostic text appears in any failure channel.

`rootIdSequenceCsv` and `revisionIdSequenceCsv` are non-empty ASCII CSV with
`1..1024` tokens, exact total length `14n-1` for `n` tokens (`13..14335`), no whitespace, empty/trailing token
or duplicate within one sequence. Root tokens match `original-[0-9]{4}`;
revision tokens match `revision-[0-9]{4}`. Validation of both complete sequences
precedes password-file content, command read and all external access; invalid
input follows the exact config exit-70 channels above. The injected ID source
consumes only when the application requests that ID kind, strictly left to
right; unused suffix is allowed, consumption never rewinds, and exhaustion is
the typed ID dependency failure mapped by the command to
`FAILED/PERSISTENCE_FAILURE`, not worker exit 70.

Canonical worker fixtures are: initial setup root `original-0001`, revision
`revision-0001`; two identical correction workers each have unused root
`original-0099` and revision `revision-0002`; different correction workers have
unused roots `original-0098`/`original-0097` and revisions
`revision-0003`/`revision-0004`. Same generated revision in the identical race
is intentional: winner persists it and loser resolves the winner by fingerprint
before attempting a distinct accepted fact.

Command pipe carries exactly one UTF-8 JSON line, maximum `29,000,000` bytes
including its single final LF and no bytes after it. Top-level keys in canonical
order are `requestId,mode,installationCaseId,assignmentOrderId,actorUserId,
documentDate,compositionConfirmed,rootOriginalId,targetRevisionId,
expectedCurrentRevisionId,correctionReason,upload`; `upload` keys in order are
`bytesBase64,originalFilename,declaredMediaType`. No additional/missing keys are
allowed; enum backed strings and explicit nulls map directly to Command.
`bytesBase64` is a JSON string using only RFC 4648 standard alphabet, canonical
`=` padding, no whitespace/URL alphabet; strict decode must succeed and
`base64_encode(decoded)===input`. Empty decoded bytes are transport-valid and
reach application file validation. The worker reads chunks of at most `65536`
bytes into one line buffer that never exceeds `29,000,000` bytes. It stops at
the first LF, which must be the last buffered byte, then requires EOF and no
extra byte within a 5-second monotonic deadline; EOF before LF, any byte after
LF or deadline is framing failure. Only after complete framing does it validate
UTF-8, decode JSON, validate exact keys/types and inspect base64 alphabet/padding
without decoded allocation. `AssignmentOrderOriginalByteStreamFactory::
fromBase64` is the sole strict decoder: it allocates decoded bytes once,
requires re-encode equality and returns the stream. No other decoded copy is
retained by worker bootstrap.

This entire framing/JSON/base64/factory validation occurs after scalar/path
metadata config validation but before password-file content, DB connection,
private storage, safe-log, application or barrier access. Any failure exits
`70` through the exact channels. Decoded size `20,971,521` remains
transport-valid so application proves `FILE_TOO_LARGE`.

Canonical initial worker command fixture (the final LF after `}` is required):

```json
{"requestId":"00000000-0000-4000-8000-000000000001","mode":"initial","installationCaseId":4512,"assignmentOrderId":81,"actorUserId":18,"documentDate":"2026-09-01","compositionConfirmed":true,"rootOriginalId":null,"targetRevisionId":null,"expectedCurrentRevisionId":null,"correctionReason":null,"upload":{"bytesBase64":"JVBERi0xLjQKMSAwIG9iago8PCAvVHlwZSAvQ2F0YWxvZyAvUGFnZXMgMiAwIFIgPj4KZW5kb2JqCjIgMCBvYmoKPDwgL1R5cGUgL1BhZ2VzIC9LaWRzIFszIDAgUl0gL0NvdW50IDEgPj4KZW5kb2JqCjMgMCBvYmoKPDwgL1R5cGUgL1BhZ2UgL1BhcmVudCAyIDAgUiAvTWVkaWFCb3ggWzAgMCA3MiA3Ml0gPj4KZW5kb2JqCnhyZWYKMCA0CjAwMDAwMDAwMDAgNjU1MzUgZiAKMDAwMDAwMDAwOSAwMDAwMCBuIAowMDAwMDAwMDU4IDAwMDAwIG4gCjAwMDAwMDAxMTUgMDAwMDAgbiAKdHJhaWxlcgo8PCAvU2l6ZSA0IC9Sb290IDEgMCBSID4+CnN0YXJ0eHJlZgoxODQKJSVFT0YK","originalFilename":"signed-order.pdf","declaredMediaType":"application/pdf"}}
```

Result pipe carries exactly one canonical JSON line maximum `16384` bytes
including one final LF. Keys are always present in this literal order:
`status,reasonCode,retryable,requestId,rootOriginalId,currentRevisionId,
revisionNumber,documentDate,sha256,byteSize,uploadedAt`. Status/reason use their
lower-case PHP backed strings, nulls are explicit, booleans are JSON booleans,
sizes/revision are unquoted base-10 integers, and strings use
`JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR`, with no
whitespace or optional escaping. The encoded object plus LF must fit before any
result byte is written; serialization/oversize failure therefore writes zero
result bytes. The worker then performs one `fwrite` primitive with the complete
line. `false`, zero or a short byte count is write failure: worker performs no
second result write, closes the FD and follows controlled exit 70. A short write
may leave an untrusted prefix in the private IPC pipe; the parent buffers at
most `16384` bytes, requires exactly one complete canonical JSON line ending LF
followed by EOF, and discards any prefix/extra/malformed input as transport
failure. It never publishes or decodes a partial Result. A committed operation
remains replayable with the same request ID.

Exact lines for Example A accepted, its retry, and a new-request stale conflict:

```text
{"status":"accepted","reasonCode":null,"retryable":false,"requestId":"00000000-0000-4000-8000-000000000001","rootOriginalId":"original-0001","currentRevisionId":"revision-0001","revisionNumber":1,"documentDate":"2026-09-01","sha256":"4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784","byteSize":327,"uploadedAt":"2026-09-02T09:15:30Z"}
{"status":"replayed","reasonCode":null,"retryable":false,"requestId":"00000000-0000-4000-8000-000000000001","rootOriginalId":"original-0001","currentRevisionId":"revision-0001","revisionNumber":1,"documentDate":"2026-09-01","sha256":"4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784","byteSize":327,"uploadedAt":"2026-09-02T09:15:30Z"}
{"status":"conflict","reasonCode":"stale_revision","retryable":false,"requestId":"00000000-0000-4000-8000-000000000002","rootOriginalId":null,"currentRevisionId":null,"revisionNumber":null,"documentDate":null,"sha256":null,"byteSize":null,"uploadedAt":null}
```

Barrier uses separate FDs: at `AFTER_FINGERPRINT_MISS_BEFORE_CAS` child writes `READY <requestId>\n`, flushes, then waits at most 5 monotonic seconds for exact `RELEASE <requestId>\n`; malformed/EOF/timeout returns exit `70`, no commit and redacted stderr. Parent must receive both READY lines before writing both RELEASE lines. Child exits `0` only after one valid Result line, otherwise nonzero. Parent bounds all reads/waits, closes the evidence reader, closes pipes, terminates then reaps every child in `finally`, restores faults, validates every cleanup target again and removes only its owned prefix/root/config/password/safe-log artifacts; safe-log removal occurs only after reader close and child termination/reaping.
