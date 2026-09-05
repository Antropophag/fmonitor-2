# ASSIGNMENT-ORDER-ORIGINAL-COMMAND-SHAPE-001 — exact command metadata boundary

Версия0.1, 2026-09-06. **DRAFT / INDEPENDENT GATE1 REQUIRED**.

## 1. Authority and scope

Public seam — existing `submitAssignmentOrderOriginal(Command): Result`.
Normative parent — ORIGINAL-UPLOAD-001 sections2–4 and exact step1 precedence.
Independent gap inventory:
`docs/operations/original-command-shape-contract-audit-2026-09-06.md`.

Это техническая конкретизация scalar validation/normalization и opaque-ID storage
boundary, не новая capability, actor policy, upload limit или product workflow.
Existing owner upload/correction/read approvals не переоткрываются. No HTTP,
selection, opening, database migration или filesystem behavior is introduced.

## 2. Passive command and first-step validation

Все существующие constructor arguments/names/types сохраняются. DTO остаётся
passive: invalid-but-type-correct scalar values конструируются, public application
возвращает полный `REJECTED/INVALID_COMMAND`, retryable=false, current requestId,
все7 evidence fields null. Constructor не возвращает domain Result и не бросает
новый validation exception.

Validation step1 до authorizer, terminal-request/fingerprint/lineage repository,
composition, clock, inspector, storage, IDs, lifecycle и delivery. Invalid shape
не создаёт attempt audit, request, revision, root или event. Supplied stream
принадлежит application для closure: close attempted once, read never called.
Это уточнение lifecycle existing cleanup, не разрешение прочитать payload.
Safe-log request binding может предшествовать shape по approved isolation
contract; это единственная diagnostic operation до shape. Stream-close failure
сохраняет invalid-command result и получает существующий best-effort diagnostic.
Ни malformed raw value, ни exception text не попадает в safe log.

Canonical lowercase UUID и positive PHP integer case/order/actor identities
остаются ровно parent contract. INITIAL требует null для root/target/expected/reason.
CORRECTION требует три valid opaque IDs и valid normalized reason. Bool false
compositionConfirmed остаётся shape-valid и сохраняет прежний последующий
COMPOSITION_NOT_CONFIRMED outcome. Никакой filename/reason/fingerprint equality
не добавляется к terminal replay после successful shape validation.

## 3. Calendar date

`documentDate` exactly10 ASCII bytes `YYYY-MM-DD`, year0001..9999, month01..12,
day valid Gregorian date including leap-year rules. No whitespace, fraction,
time, offset, PHP normalization или zero component. This is calendar shape,
not future-date policy. Future-date comparison remains step7 using the single
already validated UTC clock instant converted to Europe/Moscow.

Fixed invalids: `2026-02-29`, `2026-02-31`, `2026-04-31`, `0000-00-00`,
`0000-01-01`, `2026-13-01`, `2026-00-01`, `2026-01-00`, `2026-9-01`,
`2026-09-01 ` → INVALID_COMMAND before any business port.
`2024-02-29` is calendar-valid and reaches normal authorization/command path.

## 4. UTF-8, controls, trim and length

Filename and correction reason MUST be valid UTF-8 before normalization.
Forbidden raw code points are Unicode Cc except U+0009 TAB, U+000A LF and
U+000D CR. Exact prohibited ranges:
U+0000..0008, U+000B..000C, U+000E..001F, U+007F..009F.
Check raw input before trim so forbidden leading/trailing controls are not hidden.
No replacement characters are manufactured for invalid encoding.

Unicode trim removes only a maximal leading/trailing sequence from this exact
White_Space set: U+0009..000D, U+0020, U+0085, U+00A0, U+1680,
U+2000..200A, U+2028, U+2029, U+202F, U+205F, U+3000. The earlier raw-control
check still rejects forbidden members of that set. Interior allowed whitespace
is preserved byte-for-byte. No Unicode NFC/NFKC/case folding is applied.

Normalized filename length1..255 Unicode code points; normalized correction
reason length1..500 Unicode code points. Count code points, not bytes or grapheme
clusters. Valid multi-byte code points and combining marks count independently.
Filename remains metadata only; it never chooses path/storage identity or enters
fingerprint. Application may retain raw command filename because it is not
persisted by this seam; validation uses its normalized value.

The accepted CORRECTION commit MUST carry the normalized reason, not raw input.
The original immutable command object is not mutated. Reason remains excluded
from fingerprint, so trim/case/content differences in a shape-valid reason do
not create a different accepted-operation identity. Prior evidence is unchanged.

Fixed examples:

- filename `я` and255 copies of `я` valid;256 invalid; empty, ASCII-space-only,
  NBSP-only, `signed`+NUL+`.pdf`, U+0001, U+0085, invalid bytes C3 28 invalid;
- interior TAB/LF/CR in a nonempty filename or reason valid; leading/trailing
  allowed whitespace trimmed; U+00A0 around `оригинал.pdf` valid;
- reason1/500 copies of `Я` valid;501 invalid; NBSP-only/invalid UTF8/NUL/Cc invalid;
- raw ` U+00A0Исправлена датаU+3000 ` persists exactly `Исправлена дата`;
- interior `Исправлена\tдата\nскана\r` (literal TAB/LF/CR) preserves interior
  bytes and trims only trailing CR; accepted reason ends in `скана`.

## 5. Opaque identity grammar

Caller root/target/expected revision IDs and GENERATED ID-source values share
one storage-safe grammar:1..80 bytes, every byte printable ASCII U+0021..007E.
No spaces, control, NUL, non-ASCII or implicit trim/case normalization. These are
opaque identities; no required `original-`/`root-`/`revision-` prefix is imposed.
Quotes, backslash and punctuation within that printable range are ordinary data,
never SQL/path syntax. Existing adapters must continue parameter binding.
Worker sequence tokens retain their separate narrower transport grammar.

Malformed caller IDs are INVALID_COMMAND at step1. Malformed GENERATED id is
FAILED/PERSISTENCE_FAILURE retryable=true at the existing ID boundary, before
finalize/commit, with normal once-only stage/stream cleanup. Non-GENERATED status
rules remain the approved dynamic-port correction; malformed generated data is
not retried as COLLISION. No change to8 consecutive collision limit.

Boundaries: one printable byte and80 printable bytes are shape-valid; empty,
81 bytes, space, TAB, NUL, DEL, NBSP and invalidUTF8 are invalid. A shape-valid
unknown ID reaches trusted lineage lookup and its existing conflict outcome;
it need not be an existing root to pass scalar validation.

## 6. Fixed public verification examples

Use ExampleA unchanged literal327-byte passive PDF, SHA256 `4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784`,
case4512/order81/actor18/composition-81-v1, clock2026-09-02T09:15:30Z.
Fresh initial accepted IDs original-0001/revision-0001. Correction baseline has
that immutable revision1/date2026-09-01 and allocates only revision-0002; command
date2026-09-02; accepted revision2 retains root and previous revision1.

Every invalid scalar example expects full invalid-command tuple with current
request `00000000-0000-4000-8000-000000000001`, seven null evidence fields,
stream read0/close1, all business-port and audit/delivery counts0. A repository
fixture deliberately capable of returning stored terminal success must still
receive zero calls for malformed filename/reason/date/IDs.

Valid boundary controls MUST reach an independently fixed later outcome, never
use reject-all acceptance. Initial valid filename1/255, Unicode trim and allowed
interior controls yield exact ExampleA ACCEPTED. Correction valid reason1/500 and
Unicode-trim examples yield exact revision2 ACCEPTED and normalized commit reason.
Valid leap-day documentDate persists exactly2024-02-29. Valid but changed metadata
with same terminal request yields existing replay unchanged and no stream read.
For opaque ID boundaries, a counting denied authorizer proves valid shape reached
step2 without inventing matching root evidence for arbitrary IDs.

Generated malformed-ID cases cover both initial root and correction revision
allocation; full persistence-failure tuple, source calls1 at the failing source,
no next source after root failure, abort/close/stream-close each1, no finalize,
commit, audit, delivery or modification of pre-existing evidence.

## 7. Gates and exclusions

Independent Gate1 must approve exact Unicode/ID/calendar semantics and compatibility
with existing public sources before these new tests. Then real public-seam RED,
independent Gate3, minimal validation/normalization GREEN, relevant regressions,
architecture and independent Gate5. No primary evidence, DB, file/native/permission
fixtures or user product decision is required.

Lifecycle/storage callback completeness, response-loss clarification and other
public declaration parity are separate corrective contracts. This slice does
not silently decide their outcomes or claim combined original-command approval.
