# Original command pre-clock terminal and audit contract audit

Date: 2026-09-06. Reviewer: separately tasked agent `/root/admission_oracle_gate3`.
Repository HEAD: `ac7c676c9dfde8eafd45f7891b83bda88cea7220`.
Status: bounded contract preparation only; not Gate 1, RED, implementation or Gate 5.

No source, test, specification, database, native resource, primary evidence or
remote system was changed or probed.

## Determination

The active parent already fixes the public result precedence:

1. shape before authorization;
2. authorization before confidential terminal lookup;
3. authorized terminal hit before composition/clock/stream;
4. composition/order lookup before clock;
5. one canonical clock instant for a fresh terminal/audit or accepted fact.

Current production cannot persist every required attempt under that order. It
uses the string `1970-01-01T00:00:00Z` as an internal “clock not acquired”
sentinel and skips `commitAttempt` while the value equals that string. It also
has only one rejection/conflict repository method, which inserts both terminal
request and audit. That method cannot express audit-only retryable failures,
repeat denial, or denial against a request ID already owning an accepted terminal.

One cumulative executable amendment should define an invocation-owned optional
attempt instant and split terminal persistence from audit-only persistence. Most
outcomes are inherited technical consequences. One audit-cardinality choice for
repeat denial remains genuinely unresolved and must be decided explicitly before
Gate 1.

## A. Existing precedence that must not change

### Denied versus request replay

Authorization stays before terminal lookup. A revoked/denied actor using a request
ID with an accepted result receives `REJECTED/AUTHORIZATION_DENIED`; the system
must not disclose the accepted result or evidence. No terminal/fingerprint/
lineage/composition lookup is permitted on that invocation.

Conversely, a denial that was successfully persisted as the terminal result for
a previously unused request ID is returned after the actor later becomes allowed:
the allowed invocation performs terminal lookup and replays the stored rejection
before clock/stream. A new intent requires a new request ID.

This asymmetry is already normative. The amendment must preserve it and must not
solve the collision by moving terminal lookup before authorization.

### Order missing before clock

For an allowed actor with terminal NOT_FOUND, composition/order NOT_FOUND selects
`REJECTED/ORDER_NOT_FOUND` before clock. Because a valid-shape business rejection
requires a terminal request plus safe audit, the application then lazily obtains
one clock instant and persists that selected rejection. It must not repeat the
order lookup or read the stream.

If that lazy clock is unavailable/malformed, the auditable rejection cannot be
acknowledged: return retryable `FAILED/PERSISTENCE_FAILURE`, persist no terminal
request/audit, disclose no additional order data and close the unread stream once.
This follows the existing rule that a business rejection is returned only after
its required terminal/audit transaction is confirmed.

### Valid Unix epoch clock

`1970-01-01T00:00:00Z` is a syntactically valid canonical UTC-second instant.
It cannot represent “clock not called”. Replace the string sentinel with an
explicit optional invocation context, such as `?SelectionInstant`-style state or
`?string $attemptedAt = null` after exact clock validation. A real epoch value
must be persisted and returned/observed exactly like any other valid instant.

Tests must distinguish:

- clock not reached: zero clock calls and null internal context;
- clock reached with exact epoch: one call and exact epoch in terminal/audit;
- clock reached but unavailable: no terminal/audit and persistence failure.

## B. Why the current repository port cannot satisfy the parent

Public `AssignmentOrderOriginalRepository::commitAttempt(AttemptCommit)` is
normatively closed to nonretryable `REJECTED|CONFLICT`, exact status/reason
mapping and no evidence fields. MariaDB implements it as one transaction inserting
both:

- `fm2_assignment_order_original_requests` keyed by `request_id`;
- `fm2_assignment_order_original_audits`, with UNIQUE
  `(request_id,status,reason_code)`.

This represents the first fresh terminal rejection/conflict. It cannot represent:

1. repeat denial for the same request without a duplicate request primary key;
2. denial after that request is already ACCEPTED without overwriting/colliding
   with accepted terminal state;
3. retryable `STREAM_FAILURE` or `STORAGE_FAILURE`, because those must not become
   terminal request rows and are forbidden by the closed AttemptCommit contract;
4. best-effort audit of persistence failures, which likewise cannot claim a
   terminal business result.

Returning `CONFLICT` from `commitAttempt` is not enough: a denied caller cannot
resolve it through confidential terminal lookup, and treating duplicate SQL as
successful audit would be false when no new audit row was appended.

## C. Required public persistence split

Retain `commitAttempt(AssignmentOrderOriginalAttemptCommit)` for the first fresh
nonretryable terminal REJECTED/CONFLICT transaction. Add a separately declared
audit-only port with a passive safe DTO, for example conceptually:

```php
interface AssignmentOrderOriginalAttemptAuditWriter
{
    public function append(
        AssignmentOrderOriginalSafeAttemptAudit $audit,
    ): AssignmentOrderOriginalAuditWriteResult;
}
```

The eventual executable spec must choose exact type names, constructors and
closed outcomes. At minimum the result must distinguish confirmed COMMITTED,
confirmed ROLLED_BACK and OUTCOME_UNKNOWN. It writes only the append-only safe
audit row, never a terminal request, root, revision, event, fingerprint or blob.
It owns its short transaction and never causes a business mutation retry.

Use the split as follows:

- fresh allowed terminal rejection/conflict: `commitAttempt`, returning the
  selected outcome only after COMMITTED;
- first denial on an unused request: one atomic terminal denial + audit through
  `commitAttempt`, preserving later allowed replay;
- denial when the request ID is already terminal: preserve the existing terminal
  row byte-for-byte and use audit-only persistence according to the cardinality
  decision below; return denial without lookup/disclosure;
- retryable STREAM/STORAGE failure: audit-only best effort; audit failure does
  not replace the selected retryable failure;
- PERSISTENCE_FAILURE/OUTCOME_UNKNOWN: no promised DB audit, only the exact
  best-effort diagnostic contract once defined.

The application needs a non-confidential persistence collision outcome that can
distinguish “terminal already exists” from infrastructure rollback without
returning the terminal payload to a denied caller. Options include an exact
`EXISTING_TERMINAL` status from terminal attempt persistence or an audit-writer
operation that appends independently after a request-key collision. The spec
must choose one typed route and forbid raw duplicate-code branching in the
application.

## D. Repeat denial and denial-after-acceptance matrix

The executable package must pin these cases independently:

1. unused request + denied actor: no terminal lookup; clock once; terminal denial
   and one denial audit committed atomically; returned denial;
2. same denied request repeated while still denied: no terminal lookup; no
   accepted evidence disclosure/mutation; exact result and audit behavior per the
   unresolved cardinality choice;
3. first denial, then grant, same request: terminal lookup returns stored denial;
   no new clock/audit/stream read;
4. accepted request, then revoke, same request: no terminal lookup; accepted
   request/root/revision/event remain byte-identical; returned denial; denial audit
   handled without changing accepted terminal;
5. restore grant, same accepted request: ordinary accepted replay and no denial
   data contaminates the stored result;
6. concurrent accepted write versus denied attempt on one request: at most one
   terminal row, accepted evidence is never overwritten, and any denial audit is
   independently attributable without result disclosure.

The unique audit key must match the chosen cardinality. It cannot silently decide
the behavior through duplicate suppression.

## E. Audit-only STREAM/STORAGE failures

The parent explicitly requires retryable `FAILED/STREAM_FAILURE` and
`FAILED/STORAGE_FAILURE` to remain retryable and absent from terminal request
storage, while attempting one safe audit transaction. Add fixed tests for each
failure family:

- selected failure returned unchanged whether audit COMMITTED, ROLLED_BACK,
  OUTCOME_UNKNOWN or writer Throwable;
- stream/stage/lease cleanup remains exact once and precedes the audit attempt;
- no request/root/revision/event/fingerprint fact is created;
- successful audit contains only request ID, actor, mode, case, order, selected
  failure status/reason and the one invocation instant;
- audit failure is logged best effort once and never causes stream/storage retry,
  a second audit, or conversion to generic persistence failure.

`AssignmentOrderOriginalAttemptCommit` must remain closed to nonretryable
REJECTED/CONFLICT. Do not broaden it to accept retryable failures merely to reuse
the existing SQL method; that would incorrectly create terminal request rows.

## F. Safe-log naming is not exact enough yet

The parent says failed safe-audit persistence is logged best effort with safe
correlation, but it does not pin a generic event name and exact sole fields for:

- terminal rejection/conflict audit failure;
- audit-only stream/storage failure audit failure;
- audit-only denial failure/collision;
- persistence/outcome-unknown diagnostic when no DB audit is promised.

Existing exact names cover stage abort, stage close, stream close and content
lease release failures. Reusing those names for repository audit failure would
be false. The next executable specification must pin exact uppercase event names,
allowed `phase` literals and call count/order, while preserving the rule that no
request payload, filename, path, PDF bytes, composition names, correction reason,
SQL or exception text is logged.

This is a technical observability contract, not a new product outcome. Do not
implement guessed logger names before Gate 1.

## G. Unresolved product/audit choice

One question is not determined by the active text and schema together:

**Does every denied invocation append a distinct audit row, or is denial audit
idempotent per `(request_id,status,reason_code)`?**

The prose calls denial an attempt and elsewhere favors append-only audit, which
supports one row per invocation. The current UNIQUE key enforces at most one
identical denial audit per request. Both affect observable audit history and
security reporting, so this cannot be selected solely as an implementation
detail.

Ask the product/security owner to choose:

- `EVERY_DENIED_INVOCATION`: remove/replace the unique tuple with an audit identity
  that permits repeated denial rows while retaining exact ordering and replay
  separation; or
- `ONE_DENIAL_PER_REQUEST_REASON`: define exact idempotent duplicate handling and
  make clear that later denied retries return denial without adding audit.

No other product choice is needed for this package. Denial precedence, accepted
terminal preservation, first-denial replay after grant, auditable business
rejections, retryable failure non-terminality and epoch validity are already fixed.

## H. Gate 2 matrix and public seams

After resolving audit cardinality and exact log names, one executable spec should
cover:

- shape invalid: no auth/lookup/clock/audit;
- authorization unavailable: persistence failure, no confidential lookup and the
  explicitly chosen diagnostic behavior;
- denial cases from section D;
- authorized terminal accepted/rejected/conflict replay: lookup before clock,
  unread stream close once, no new audit;
- order NOT_FOUND then exact epoch clock and ordinary clock;
- order NOT_FOUND plus clock unavailable;
- composition UNAVAILABLE before clock, with no invented terminal;
- confirmation/date/invalid-PDF rejections using one captured instant;
- commitAttempt COMMITTED/ROLLED_BACK/OUTCOME_UNKNOWN/Throwable;
- audit-only STREAM/STORAGE and audit-writer outcome matrix;
- generic safe-log success/Throwable for every newly named event;
- exact before/after terminal, audit, accepted evidence, event, storage and
  delivery snapshots.

Public application tests prove precedence and result behavior. Direct repository
tests are appropriate for the declared atomic terminal and audit-only adapters,
including unique collisions and zero-mutation failure. No private SQL method is
an acceptance seam.

## Exact reviewed hashes

```text
bdd57ea8b79e7414b7724da953b838b1d7c2d76e4e4df8170fa8664cd492e00d  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
014af5a9b72ab93d7e03e9d3dbb1da208b22b28e5bf9c40bead0532ac60e74a8  app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php
ce1e072b08705f6347de23e2867ee53c8e774c528b3abf5f1c5b121d9a21bc3c  app/AssignmentOrderOriginal/MariaDbRuntimeRepository.php
34b4a20be0005e4236ee3cdb04ca6fd41537ca3608ecef41ac5e9d91f151ddf5  app/InstallationProcess/AssignmentOrderOriginalDefinitionSchemaMigration.php
72201ad71b18561cd40c4d697e9eeb110705e66c9467ca707017a88a4ccab7ff  docs/operations/original-command-data-integrity-contract-audit-2026-09-06.md
```

This package remains separate from lifecycle/storage-event completeness,
data-integrity DTO validation, safe-log owner mechanics, PDF history, declaration
parity, selection compatibility and the empty-probe expectation patch.
