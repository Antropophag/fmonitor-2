# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — Gate 2 constructibility audit

Date: 2026-09-02  
Role: separately tasked RED author `/root/assignment_original_red`  
Scope: approved command-only slice; no HTTP, composition application, or opening  
Outcome: **GATE 1 AMENDMENT REQUIRED BEFORE RED**

## Outcome

The approved behavioral contract is detailed enough to describe the domain
outcomes, but not enough to write an implementation-independent executable RED
through the required production-composed PHP seam. The RED author therefore did
not invent class names, factory overloads, dependency ports, or a test-only
dispatcher. No production or test file was changed and OpenSpec task 2.1 remains
open.

The blocker is not a broken test environment. The specification requires the
verifier to call `submitAssignmentOrderOriginal` through a production-composed
application factory, but provides only language-neutral `Command` and `Result`
records. It gives no callable PHP factory or DTO signature. Consequently even
the first worked example cannot be constructed without making a new architecture
decision in Gate 2.

## Exact constructibility gaps to close in one amendment

The amendment should contain syntactically valid, fully typed PHP declarations
for all items below. Names listed here describe responsibilities, not proposed
API names.

1. **Application and factory seam**
   - namespace and exact application interface/class;
   - exact `submitAssignmentOrderOriginal` parameter and return types;
   - production factory class and `create(...)` signature;
   - verifier composition method, if injection is allowed, with an exhaustive
     dependency list;
   - a prohibition on production bootstrap selecting verifier composition by
     environment, request, CLI flag, service locator, or mutable global.

2. **Command, upload, and result DTOs**
   - constructors/factories, property/accessor names and PHP scalar/value types
     for every field in sections 2 and 6;
   - exact representation of `INITIAL|CORRECTION`, status and reason enums;
   - exact upload-stream interface, including read result, EOF and failure
     representation, and how a one-byte-over-limit read is observed;
   - ownership and close/cleanup rules for the stream;
   - whether shape validation occurs in a DTO constructor or in the application
     command (this changes whether `INVALID_COMMAND` is observable as a Result);
   - immutable result construction ownership so verifier adapters cannot
     self-attest `ACCEPTED` or `REPLAYED`.

3. **Deterministic domain input ports**
   - exact authorization port result for active user, active role assignment and
     explicit user capability row, including unavailable/error behavior;
   - exact order/composition lookup DTO, not-found/error behavior, immutable
     composition identity, installer identities, engineer identity and supplied
     composition hash trust boundary;
   - clock instant API and Moscow-date conversion ownership;
   - opaque root/revision ID source API, collision/exhaustion/failure behavior.

4. **PDF boundary**
   - production `FMonitorPassivePdfInspector` callable signature and immutable
     outcome type;
   - exact verifier-injectable inspector interface and outcome factories for
     cases where the real parser is deliberately not under assertion;
   - which matrix cases must use the real owned parser rather than the outcome
     adapter;
   - parser diagnostic redaction and exception-to-result mapping;
   - whether bounded acquisition owns MIME/magic checks or the inspector does.

5. **Private storage boundary**
   - staging/finalization/reuse/cleanup interfaces and typed outcomes;
   - immutable candidate/content identity types and who may construct them;
   - verifier-observable operation events or counters needed to prove ordering,
     no read, no mutation, orphan privacy and retry reuse;
   - exact fault points for stream, stage, finalize, digest lock and delete;
   - private temporary-root configuration and production binding.

6. **Repository, transaction, and ambiguity boundary**
   - exact request lookup, accepted-fingerprint lookup, current-lineage read and
     CAS write contracts;
   - one transaction API for revision, terminal result, event and safe audit;
   - typed outcomes for definite rollback, commit accepted, commit lost with
     fresh lookup accepted/absent/unknown, and lookup failure;
   - an explicit verifier injection mechanism for response loss and response
     serialization failure that cannot be selected in production;
   - transaction/read isolation needed for the two-runner identical and
     different correction races.

7. **Independent evidence observation**
   - exact read-only verifier seam(s) for revisions, terminal request results,
     fingerprints, events, safe audits, private blob inventory and the required
     unchanged order/composition/case/opening/task/decoy snapshots;
   - which of these are in-memory versus MariaDB acceptance obligations;
   - canonical snapshot shapes so tests do not query private tables or infer a
     future schema;
   - safe-log observer API if log exact-once/redaction is an acceptance claim.

8. **Maintenance seam**
   - exact callable `reconcileAssignmentOrderOriginalPrivateOrphans` owner,
     command and result PHP signatures;
   - status/reason enums and invalid/unauthorized/failure mappings currently
     absent from the language-neutral maintenance Result;
   - authorization principal representation, cursor type/validation, lock
     outcomes, per-candidate failure accounting and replay semantics;
   - read-only evidence needed to prove at-most-once deletion and no domain
     mutation.

9. **Concurrency verifier composition**
   - production-composed worker/bootstrap callable used by two processes;
   - verifier-only observation/barrier seam and exact safe pause point(s), if a
     deterministic CAS race is mandatory;
   - serialization format for command/result across IPC;
   - child exit/output contract and cleanup/reaping protocol.

10. **Failure and audit precedence**
    - exact exception/outcome mapping for dependency failures before and after a
      reliable request/actor identity exists;
    - callable distinction between terminal rejected/conflict audit transaction
      failure and best-effort audit failure after retryable stream/storage errors;
    - exact stored replay shape for terminal rejected/conflict results;
    - metric/log interfaces sufficient to test forbidden payload leakage without
      depending on implementation logs.

## Contract inconsistencies to resolve while amending

- The exact-hash owner decision approves the current file, while its header still
  says `Статус: DRAFT / Gate 1`. The normative status should unambiguously record
  owner approval without changing historical decision records.
- Section 4 says a future date is rejected “without reading PDF parser after
  bounded stream acquisition”; authorization precedence says order/composition
  is read before upload. The exact order of composition lookup, full stream
  acquisition, future-date decision, MIME/magic and parser invocation must be a
  single literal sequence so call-count assertions are stable.
- Section 2 says any shape/identity error occurs before stream read, while
  `ORDER_NOT_FOUND` is separately described as an identity outcome. Clarify
  whether order lookup failure is included in “identity error” and its exact
  position relative to terminal request replay.
- Section 8 places stored-request lookup after shape and authorization, whereas
  section 11 says rejected/conflict request results are terminal. Clarify whether
  an authorization change on retry is checked before returning a stored result
  and whether a previously unauthorized request can ever replay without stream
  read; encode the intended security/no-disclosure rule explicitly.
- `CORRECTION` collision with a changed composition cannot be produced by the
  caller because composition is loaded by exact order identity. Define the
  historical snapshot/current snapshot comparison that makes this outcome
  observable without a caller-supplied composition.
- `TARGET_NOT_CURRENT` requires expected-current to equal actual current while
  target names another revision of the root. State whether the target revision
  must exist and the outcome for an opaque unknown target.
- Maintenance results currently have no reason/retry contract, yet invalid
  `batchLimit`, young cutoff, unauthorized principal, lock/storage failure and
  persistence failure are all required to be distinguishable for a complete
  matrix.

## Gate consequence

Until an amended exact-hash specification and coherent OpenSpec artifacts receive
fresh independent Gate 1 review and owner approval, an executable test would
either stop at a guessed missing class or encode an unapproved architecture.
Neither is an honest demonstrated RED for Example A. Tasks 2.1 and 2.2 therefore
remain unchecked; Gate 3 review must not be requested yet.

