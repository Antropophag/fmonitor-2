# ASSIGNMENT-ORDER-ORIGINAL-DATA-INTEGRITY-001 v0.1 — independent Gate 1 review

Дата: 2026-09-06.  
Reviewer task: `/root/selection_v04_readiness`.  
Reviewed commit: `86a605239243b2ebf497c8173cd002703c070623`.  
Verdict: **CHANGES_REQUESTED**.

Reviewer не автор specification, parent или OpenSpec artifacts. Review covers
the cumulative data-integrity public/application/MariaDB/fresh-recovery package.
Lifecycle, PDF history, selection compatibility, maintenance behavior and other
separately assigned corrective scopes are not approved here.

## Exact reviewed hashes

```text
0f116462166cf7db03c13a7f126357464c3a5eb05da12c65224243ea6bd14af7  specs/ASSIGNMENT-ORDER-ORIGINAL-DATA-INTEGRITY-001.md
7066cca2becf4ae428b90fbd2885cb19760ff87658ac96108762bd2c2f74fd48  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
11a9bfee6cb3b98e3ac73c37df096ac3e1a14cf996735c804ce65ee5c3a07981  openspec/changes/replace-pilot-registration-with-original-upload/design.md
4700412695c5f2185022389e88355157b1a69a8a022e08a4cf50f851ef88d6bd  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
0d2bfc1cb91312c2192dc63a76b418e51d14b69965ef7143b7a67c054a7a9749  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
c1a03ff14ca134d39a256c0d9fa62699505632b72ad4097f9f89191362444bb6  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
72201ad71b18561cd40c4d697e9eeb110705e66c9467ca707017a88a4ccab7ff  docs/operations/original-command-data-integrity-contract-audit-2026-09-06.md
51fd437bd8808987516f27a0a3b51b2e7333d180170121249a28d9c3c0459d14  docs/operations/original-fresh-recovery-factory-feasibility-review-2026-09-06.md
6a9b3f4ab2b4d233b34d2c2450938a9a729fbd44528a27ef6dc652944eb07cc1  docs/operations/original-complete-lineage-feasibility-review-2026-09-06.md
145ea4f9902b14c63c2381206c95ca0d391e294bb85cd01363d052df318e1d8f  docs/operations/original-authorized-attempt-clock-prereview-2026-09-06.md
26e75869a988a11905dafd6ac61e3ac4454b975fb4ef1062212637f7b3494705  docs/operations/original-command-denial-audit-owner-deferred-2026-09-06.md
```

## Blocking findings

### P0 — persisted INVALID_COMMAND is incorrectly admitted as valid terminal history

Section 3 lists `INVALID_COMMAND` among valid stored REJECTED reasons and permits
an authorized terminal lookup/fresh recovery to return that stored rejection when
one matching audit exists.

The parent contract and COMMAND-SHAPE-001 require invalid shape to stop before
authorization/business ports and create **no terminal request and no attempt
audit**. There is no legitimate command path that persists an original-command
`REJECTED/INVALID_COMMAND` row. The fact that the historical table CHECK admits
the literal syntactically does not make it valid domain evidence; the same section
correctly rejects stored REPLAYED despite its physical CHECK.

Consequences of the current v0.1 rule:

- an independently inserted/corrupt request+audit pair with reason
  `invalid_command` can be rehydrated as a valid terminal result;
- authorized replay or fresh recovery can expose it rather than returning
  persistence-integrity failure;
- the reader validates a state that no approved public writer may create.

Required correction: remove `INVALID_COMMAND` from admissible stored terminal
reasons. Any stored original request/audit with that reason is malformed backing
and yields lookup UNAVAILABLE. Preserve `INVALID_COMMAND` as an application-only
result for invalid current input, with zero persistence. Add direct MariaDB and
foreign-port negative cases plus an ordinary invalid-shape zero-port control.

This is an inherited invariant correction, not a new product decision.

### P0 — exact-one denial audit silently resolves the deferred cardinality policy

Section 1 lines 22–25 and section 11 lines 407–409 explicitly defer denied-
invocation audit cardinality and state that this package does not choose denial
audit policy. Section 7 lines 216–219 nevertheless requires every stored
REJECTED/CONFLICT result, including `AUTHORIZATION_DENIED`, to have **exactly one**
matching safe audit; more than one makes backing unavailable.

Authorization precedes confidential terminal lookup. Under one possible deferred
policy, each denied retry of the same request appends an independent denial audit
while the terminal denial remains one row. Under another, the first denial only
is stored. Requiring exactly one chooses the latter representation and would mark
the former as corrupt. That is the pending owner decision the scope promises not
to make.

Required correction: do not apply an exact-one audit cardinality rule to stored
`AUTHORIZATION_DENIED` until the owner policy is resolved. The spec must either:

1. exclude denial terminal backing from this slice and keep it unavailable/
   unclaimed in the cumulative matrix; or
2. after the owner decision, pin the selected denial terminal/audit model and
   its exact request replay/cardinality rules in parent, data-integrity spec and
   OpenSpec before Gate 1 rereview.

Other rejected/conflict reasons may retain the exact-one request/audit rule when
their approved writer creates one atomic terminal attempt.

## Non-blocking review results

### Closed lookup/result snapshots — PASS

The six status/payload combinations are explicit. Status and payload are read
once even for negative states; getter Throwable and contradictory payload map to
unavailable rather than absence. Stored result status/reason/retry/evidence
relationships, request identity rules and all 11 getter snapshots are exact.
Fingerprint FOUND correctly requires accepted backing while permitting the
winner's stored request ID to differ from the current invocation.

After the INVALID_COMMAND/denial corrections above, this boundary is testable
with foreign public implementations without private methods.

### Complete lineage extension — PASS

The optional extension preserves base-interface source compatibility for
NOT_FOUND/UNAVAILABLE while making complete metadata mandatory for FOUND. It
adds only case/order ownership and an ordered revision-ID list; current date/hash
remain in the existing current-evidence extension.

Root/current/member grammar, unique contiguous chain, current-last relationship,
case/order ownership, composition binding and `containsRevision` agreement are
validated before business conflict precedence. Base-only FOUND, missing current
evidence or corrupt metadata is persistence failure. The MariaDB adapter still
owns full previous-chain validation without exposing unused historical DTOs.

Historical accepted request evidence correctly remains tied to the revision
accepted by that request and is not forced equal to the root's later current
revision. Exact request→revision→root→event/audit membership is sufficient for
historical replay and fingerprint lookup.

### Composition application and MariaDB reader — PASS

The public snapshot distinguishes protocol/ownership failure from found-invalid
business composition. Application recomputes exact canonical identity/hash,
requires exact case/order echo and a strictly increasing integer installer list,
and cannot trust a plausible custom hash.

The MariaDB reader uses one owned REPEATABLE READ read-only snapshot across order
and members, refuses an active borrowed transaction, validates lossless numeric
and real date representations, and releases before returning. Another-case order
is NOT_FOUND without member probing; malformed/query/transaction/release states
are UNAVAILABLE; found invalid business rows remain FOUND-invalid. The
two-connection observer race is public and deterministic.

### Stored backing and commit validation — PASS subject to P0 findings

Accepted request/revision/root/event/audit/fingerprint relationships are complete,
including historical request replay after a later correction, canonical UTC
storage and private-content digest identity. Rejected/conflict backing prevents a
same-request accepted revision/event claim.

AcceptedCommit and AttemptCommit scalar/mode/status/relationship checks occur
before transaction, SQL escaping/query and observer. Active caller transactions
are not committed or rolled back. Correction locking/CAS, confirmed rollback,
unique semantic conflict and lost-acknowledgement distinctions are explicit.
Native false and Throwable outcomes are both testable through public mysqli
methods plus actual synthetic MariaDB; no private/native interception is needed.

The accepted/attempt rules become coherent after stored INVALID_COMMAND and
denial cardinality are corrected.

### Fresh-reader dependency and production compatibility — PASS

Open/close results are closed typed values. Config is passive and lazy; open owns
a genuinely new utf8mb4 connection from explicit trusted fields and contains all
failures. The one-shot reader snapshots and validates its result before close,
never retains mutable reader data, and exposes no general query/write/credential
surface.

Dependencies adds one optional trailing promoted parameter after all 12 existing
names/order; absence installs an explicit unavailable factory and never reuses
the writer. Production `create(db, config, ?factory=null)` preserves two-argument
compatibility as degraded recovery, while `createRecoveryReady` requires the
provider. Later launch readiness must reject degraded construction and perform an
actual probe; a non-null object alone is not readiness proof.

Safe-log acquisition remains before DB/private-root validation/composition and
provider open remains lazy until unknown recovery. The worker can construct the
provider from already validated DSN/user/password-file inputs without extracting
credentials from mysqli or changing safe-log-before-secret ordering.

Unknown accepted/authorized-attempt recovery maps validated FOUND, reliable
NOT_FOUND and unavailable distinctly, opens once, closes once before lease
release, preserves validated result on read-only close failure with one fixed
diagnostic, and never retries commit/allocation/clock. Normal commit/replay/
fingerprint/semantic conflict does not open it.

### Authorized lazy attempt time — PASS

The contract replaces the epoch sentinel with explicit absence. Authorized
ORDER_NOT_FOUND and FOUND-invalid composition close the unread stream, lazily
acquire one canonical instant, and commit one terminal attempt. Exact epoch is
valid data. Clock failure leaves no attempt and does not repeat closure.

Attempt `CONFLICT`, `OUTCOME_UNKNOWN` and unconfirmed Throwable use the explicitly
defined fresh recovery path rather than collapsing uncertainty to rollback.
Authorization denial remains outside this behavior, consistent with the stated
scope, apart from the cardinality contradiction above.

### Verification ownership and matrix — PASS

The observer enum and exact phases expose consistent-read races, pre-SQL proof,
native commit/rollback acknowledgement and fresh-reader close without private SQL
timing or sleeps. Observer Throwable semantics preserve native cleanup and make
unconfirmed actions unavailable/unknown.

The cumulative matrix covers foreign lookup/result/lineage combinations,
composition integrity, MariaDB snapshots and malformed backing, commit DTOs,
reference closure, fresh real connection, native false/unknown outcomes,
historical replay and authorized lazy time. It uses only synthetic task-owned
data and public adapters. No real documents, primary data, secret disclosure,
OS permission mutation or rejected interception is required.

### Exact-name note

Section 12 uses the shorthand `MariaDbCompositionReader` and `MariaDbRepository`
when describing optional observer parameters. The executable declarations and
tests should use the repository's actual public class names
`AssignmentOrderOriginalMariaDbCompositionReader` and
`AssignmentOrderOriginalMariaDbRepository` to avoid creating alternate classes.
This is editorially resolvable while addressing the blocking findings; preserve
the exact existing constructor parameter names/order before adding the trailing
observer.

## Gate disposition

Gate 1 is **CHANGES_REQUESTED** for the two stored-rejection contradictions.
Do not write the cumulative data-integrity RED against v0.1. Remove persisted
INVALID_COMMAND from valid backing and avoid deciding denial audit cardinality
without owner authority, then reconcile parent/OpenSpec and request a fresh
independent Gate 1 at exact hashes.

No other technical constructibility blocker was found. This verdict does not
alter the separately approved lifecycle, shape, PDF or fresh-provider planning
work and makes no combined command or launch-readiness claim.
