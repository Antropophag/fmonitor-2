# Original command — cumulative shape/precedence contract audit

Дата: 2026-09-06.  
Reviewer task: `/root/selection_v04_readiness`.  
Reviewed HEAD: `621e7e83efdb0073086aba5f87ab955b2b1aaba7`.  
Verdict: **NEW SHAPE GAPS CONFIRMED; dynamic-port G5-CMD3 remains separate**.

Это независимый read-only source/spec/test audit. Код, тесты и спецификации не
изменялись. Никакие DB/filesystem/native/OS probes не запускались. Все
counterexamples ниже синтетические public DTO values, выведенные из exact source
branches; это не RED evidence и не Gate 3.

## Exact reviewed artifacts

```text
d65470e2e1da510aa1ebe6d9cce6549b8fffcc6bf66035716623eb0cad61f890  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
8ea2b82300c157f2ca6c69b9b4eef5ab773d8e612d22f6725d4d88aa705c013f  app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php
e17299de03bd667fcd263de2af8398800450a30eb314ed9c2cb861e38cd433a1  tests/InstallationProcess/assignment_order_original_upload_validation_001_test.php
57b1e0e18f53baaf632bcb1fd7893a1370dd0a0c181d649772160cecf53a3bef  tests/InstallationProcess/assignment_order_original_upload_001_test.php
3f006e89be2967d511cf8c0a00828d38ebc20d83240fc5d428738a3e2c2a2716  tests/InstallationProcess/assignment_order_original_upload_remaining_contract_001_test.php
```

Existing cumulative review and dynamic-port evidence were also inspected:
`reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-command-v3.md`,
`reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-dynamic-ports-v1.md` and v2.

## Normative shape and precedence

Active v61 section 2 requires:

- canonical lowercase UUID request ID;
- positive PHP integer case/order/actor IDs;
- real `YYYY-MM-DD` document date;
- INITIAL null lineage/reason and CORRECTION non-null lineage IDs plus reason;
- correction reason after Unicode trim of 1..500 code points;
- filename after trim of 1..255 code points;
- valid UTF-8 and no NUL/control characters in reason/filename except TAB/LF/CR;
- invalid shape returns `REJECTED/INVALID_COMMAND` before upload stream read.

Section 3 makes shape/safe scalar validation step 1, before authorization,
terminal request lookup, composition, clock and stream. DTO constructors are
passive PHP type carriers; application owns these validations.

Runtime `shape()` at `AssignmentOrderOriginalRuntime.php:94` currently checks
only:

- request UUID regex;
- IDs greater than zero;
- document-date digit shape `dddd-dd-dd`;
- mode-specific null/non-null fields;
- correction `trim(reason) !== ''` using PHP's ASCII trim.

It does not inspect `originalFilename` at all and does not return a normalized
correction reason.

## Confirmed source-contract gaps

### SHAPE-01 — calendar-invalid document dates pass step 1

The regex accepts impossible dates because it performs no `checkdate` or exact
round-trip validation. Literal counterexamples:

```text
2026-02-29  (2026 is not leap year)
2026-02-31
2026-04-31
0000-00-00
2026-13-01
```

For a past-lexical value such as `2026-02-31`, current code proceeds through
authorization, terminal request and composition, calls the clock, compares raw
strings for future date, and may consume/store/finalize before the repository or
DB rejects it. The required result is `REJECTED/INVALID_COMMAND` before all those
ports and before stream read. A parseable-normalized invalid date must not become
another date, `FUTURE_DOCUMENT_DATE` or `PERSISTENCE_FAILURE`.

Existing validation matrix covers one valid past date and one valid future date,
but no invalid calendar day/month/year.

### SHAPE-02 — filename contract is entirely absent from application shape

`AssignmentOrderOriginalUpload::originalFilename` is never read by `shape()`.
All of the following currently pass step 1:

```text
''                              empty
'   '                           trim-empty
str_repeat('я', 256)            256 Unicode code points
"signed\0.pdf"                  forbidden NUL
"signed\x01.pdf"                forbidden control
"bad-\xC3\x28.pdf"              invalid UTF-8
```

They can reach authorization and a matching terminal request can replay before
the stream is inspected. On a miss they reach composition, clock and stream.
The contract instead requires `INVALID_COMMAND` at step 1. Filename remains
metadata only and must not become a storage path; validating it does not broaden
storage behavior.

Existing validation tests use ordinary filenames but contain no empty,
trim-empty, Unicode-boundary, invalid-UTF-8, NUL or control cases.

### SHAPE-03 — correction reason uses ASCII nonempty check only

Current `trim()` neither performs Unicode whitespace trim nor checks UTF-8,
code-point count or allowed controls. Confirmed counterexample families:

```text
"\u{00A0}"                      Unicode NBSP only; PHP trim leaves nonempty
str_repeat('Я', 501)            exceeds 500 code points
"исправление\0скана"            forbidden NUL
"исправление\x01скана"          forbidden control
"bad-\xC3\x28"                  invalid UTF-8
"  Исправлена дата  "           passes and is persisted unnormalized
```

The first five must be `INVALID_COMMAND` at step 1. The active phrase “reason
после Unicode trim” also requires one exact normalized value to feed accepted
persistence; current code passes the original bytes into
`AssignmentOrderOriginalAcceptedCommit` at line 87. Without normalization,
semantically trim-equivalent reasons produce different stored evidence even
though reason is deliberately excluded from fingerprint.

Existing tests exercise a normal Russian reason and no-changes behavior, but no
Unicode trim, 500/501 boundary, invalid UTF-8, NUL/control or persisted normalized
reason.

### SHAPE-04 — malformed filename/reason can incorrectly reach terminal replay

The parent contract intentionally permits terminal replay without comparing a
new valid payload, but shape validation precedes that lookup. Because SHAPE-02
and SHAPE-03 are missing, a previously accepted request ID combined with a
malformed filename, or a previously terminal correction request combined with a
malformed reason, can return stored accepted/rejected/conflict outcome instead of
`INVALID_COMMAND`.

This is not a request-fingerprint comparison requirement. The smallest oracle is:
the same request ID with a **shape-invalid** filename/reason fails at step 1 and
performs zero authorization/repository/composition/clock/stream calls. A different
but shape-valid filename/reason may retain the approved terminal replay behavior.

No existing replay test places malformed command metadata before the stored hit.

## Specification gap requiring technical clarification

### SHAPE-SPEC-01 — caller lineage/revision ID grammar is not executable

Section 2 calls `rootOriginalId`, `targetRevisionId` and
`expectedCurrentRevisionId` opaque IDs and requires non-null values for
CORRECTION, but it does not pin their command-input length, encoding or forbidden
characters. The schema stores root/revision IDs in ASCII-bin `VARCHAR(80)`, while
generated worker fixtures use narrower `original-NNNN`/`revision-NNNN` forms.
Those worker forms are transport fixtures, not necessarily the public command
grammar.

Runtime accepts empty strings, whitespace, invalid UTF-8, controls and arbitrarily
long strings as long as they are non-null. Examples `''`, `"\0"` and 81 ASCII
characters therefore reach lineage lookup and produce a conflict/dependency
outcome rather than `INVALID_COMMAND`.

Before a shape RED includes these fields, the executable contract should state
whether caller-supplied opaque IDs use the storage-safe 1..80 ASCII/no-control
grammar or another exact public grammar. This is a technical identity-boundary
decision, not a product-policy question. Do not infer the worker token regex as
the public rule.

## Already-open G5-CMD3 findings — not duplicated as new shape gaps

The following current defects are already covered by the approved dynamic-port
RED/Gate 3 cycle and remain separate:

1. Real post-stream fingerprint lookup `UNAVAILABLE` falls through toward IDs,
   finalize and commit (`Runtime.php:74`).
2. Public ID enum/result and eight-collision protocol do not match the active
   four-status contract; runtime ignores status/null and calls each source once
   (`:15`, `:31`, `:75`).
3. Clock output is neither canonical-UTC validated nor round-tripped; raw
   noncanonical/invalid values can become `uploadedAt` (`:70`).

The dynamic-port approved test covers post-stream lookup, root/revision paths,
correction allocation, generated/null, unavailable/exhausted/collisions/Throwable,
noncanonical clock values, valid Moscow boundary and future date. These issues
should not be merged into a new shape test or counted twice in cumulative review.

## Other dynamic boundary observations

The current composition guard at `Runtime.php:69` checks only a truthy identity,
hash grammar, nonempty unique positive installers and positive engineer. It does
not itself verify snapshot case/order echo, exact identity format, numeric ordering
or recomputed hash. Current production reader derives these values, but the public
reader is an injected port. Likewise terminal/fingerprint lookup results are
interfaces whose malformed status/payload combinations require fail-closed
handling.

These are total-port integrity questions adjacent to G5-CMD3, not scalar command
shape. They should be reconciled in the cumulative dynamic-port contract/review
inventory before combined command approval so another serial partial review is
not needed. This audit does not assign a new user-visible outcome beyond the
existing `INVALID_COMPOSITION`/`PERSISTENCE_FAILURE` contract.

## Smallest complete public-seam regression package

After the lineage grammar clarification, one focused pure test should exercise
the real public application seam with counting in-memory ports and fixed
synthetic streams:

- document date: valid leap day, invalid non-leap day, invalid day/month/year;
- filename: 1/255 valid code points, empty/trim-empty, 256, Unicode whitespace,
  invalid UTF-8, NUL and disallowed control; TAB/LF/CR positive controls;
- correction reason: 1/500 valid code points after Unicode trim, trim-empty,
  501, invalid UTF-8, NUL/disallowed control; TAB/LF/CR positive controls; assert
  exact normalized reason in accepted commit;
- INITIAL rejects any non-null reason/lineage field as already required;
- CORRECTION applies the newly pinned exact lineage boundaries;
- same-request malformed filename/reason proves shape rejection before terminal
  lookup, while a shape-valid changed metadata control still replays normally.

Every invalid case expects full `REJECTED/INVALID_COMMAND`, retryable false, same
request ID and seven null evidence fields; stream close remains exactly once but
read count is zero. Authorization, repository lookup, composition, clock, storage,
ID, audit and delivery counts are zero. Each positive boundary must reach its
independently intended later outcome so a reject-all implementation cannot pass.

This package must demonstrate RED on frozen production bytes and receive a fresh
independent Gate 3 before minimal GREEN. It needs no DB, file, native, OS,
permission, protected-E2E or production-data mechanism.

## Disposition

The scalar command shape is not conformant with active v61. SHAPE-01 through
SHAPE-04 are concrete implementation/test gaps. SHAPE-SPEC-01 must be made exact
before testing caller lineage values. The existing G5-CMD3 cycle remains valid
and should finish independently; combined original-command Gate 5 must review
both corrections and the remaining public dynamic-port integrity surface at one
frozen SHA.

## Follow-up: adjacent declared public API parity

После основной shape проверки отдельно прочитан current dynamic-fix HEAD
`738b9adf00e6e808e0a89f5899ce5db412daa7e6`; Runtime SHA256
`d8ca5ba2f38d463b8f84b0e91b045412f5c625a0694f0fe51e037ed98453d00a`.
Scoped G5-CMD3 implementation не меняет findings SHAPE-01..04.

### API-01 — PDF inspection factory surface не совпадает с exact declaration

Active spec lines 609–615 задают private constructor и четыре public factories:
`passive()`, `invalid()`, `unsafe()`, `failed()`. Runtime line 33 имеет public
constructor и только первые три factories. Поэтому approved caller не может
сконструировать inspector-failed result через declared `failed()` API, а
альтернативный public constructor остаётся доступен вопреки exact declaration.

Это declaration parity gap, отдельный от PDF parser behavior и command shape.
Минимальный public API ratchet должен проверять private constructor и четыре
factory names/status results; existing inspector-failure command outcome затем
остаётся без изменений.

### API-02 — StorageEvent enum неполон

Active spec lines 645–657 объявляет 11 exact cases. Runtime line 17 содержит
только восемь stage/finalize cases и не содержит:

```text
DIGEST_LOCK_ACQUIRED = digest_lock_acquired
DELETE_BEGIN = delete_begin
DELETE_DONE = delete_done
```

Это concrete public enum parity gap: callers и verification adapters не могут
сослаться на три нормативных cases. Audit не делает вывод, что command upload
или maintenance обязан немедленно emit эти события в новых местах; exact
maintenance/storage observer wiring должно проверяться против его собственного
контракта отдельно. Сначала требуется восстановить declared API и составить
полный use-site inventory, затем добавить только уже нормативные behavioral
assertions.

API-01/API-02 следует включить в единый exact public-declaration inventory до
следующего combined command Gate 5, не смешивая их со scalar-shape RED и не
сохраняя runtime alternatives молча.
