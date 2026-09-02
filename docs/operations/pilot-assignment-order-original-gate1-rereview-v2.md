# Fresh independent Gate 1 rereview v2 — assignment-order original upload

Date: 2026-09-02  
Reviewer: separately tasked agent `/root/assignment_original_v2_review`  
Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v2  
Scope: constructibility amendment only; no tests or production implementation reviewed  
Verdict: **CHANGES_REQUIRED**

## Exact reviewed artifacts

```text
0a51507b4a6b43e8afb996a5e462d6f08b092e9dfe206160733cdcef8d2d5380  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
d6a5261cbbd7f12c2c8fd5b21f9d23d93040576d0060a0730900d2617901c566  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
6d60daef300eeaec93d91742ae44eb1ba9d3e6f9a2a03ef97e54e1fac38731c4  openspec/changes/replace-pilot-registration-with-original-upload/design.md
a5299bfc7891e29f6aecaee72437e067b31c3aa2acd8930a88d665b30d15cb9d  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
8090ad392894f9851e0f25ca1999bff228c2fa49e5c6c39fc3061cc5bd9ff5fd  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
08a3f37cc6d03e1057f5ceb0347ff53c337a5369bef455b0e23961229c78cbf7  docs/operations/assignment-order-original-upload-gate2-constructibility-gap-2026-09-02.md
```

## Outcome

The amendment resolves the command DTO, result, authorization replay precedence,
clock/ID/PDF ports, parser ownership, production-versus-verification factories,
safe result redaction and the main upload repository outcome vocabulary. The
normative PHP blocks concatenate and pass `php -l`; the OpenSpec change passes
strict validation.

Gate 1 cannot pass yet because the required maintenance and deterministic
two-process acceptance paths remain impossible to construct solely from the
published API. A Gate 2 author would still have to invent architecture and wire
formats outside the approved contract.

## Blocking findings

### 1. Orphan maintenance has no constructible application composition

Section 16 declares `AssignmentOrderOriginalMaintenanceApplication`, command and
result types, but neither factory returns that application nor does an exhaustive
maintenance dependency bundle exist. The only production factory returns
`AssignmentOrderOriginalApplication`. The verification dependency bundle has
only the upload authorizer whose principal is `int actorUserId`; it cannot
authorize the maintenance command's `string systemPrincipalId` and exact
`assignment_order.original.storage.reconcile` capability without an invented
adapter or coercion.

Add exact production and verification construction seams for maintenance,
including its system-principal authorizer, clock, terminal request/audit
repository and result-construction ownership. Production selection must retain
the same no-env/request/CLI/global verifier-injection prohibition as upload.

### 2. The declared storage/repository ports cannot implement the normative reconciliation algorithm

The maintenance behavior requires bounded candidate enumeration by
`cutoffUtc/batchLimit/cursor`, candidate age, digest-scoped lock acquisition,
reference recheck while holding that lock, deletion, cursor advancement and
at-most-once concurrent deletion. `AssignmentOrderOriginalPrivateStorage`
exposes only `stageAndFinalize`, unscoped `deleteUnreferenced` and a JSON
inventory. It exposes no typed candidate page, age, cursor, lock handle/scope or
lock release. The repository has no maintenance request lookup/atomic result and
audit commit contract. `DIGEST_LOCK_ACQUIRED` is only an observer event and
cannot establish exclusion.

Define typed candidate-page/lock/delete outcomes and ownership/release rules,
the repository's atomic maintenance replay/result/audit operation, and exact
count/cursor derivation. `inventoryCanonicalJson()` is evidence and must not
become the mutation input or an implicit private schema.

### 3. Deterministic two-worker CAS IPC remains underspecified and not constructible

`AssignmentOrderOriginalVerificationWorker::runJsonLine()` accepts an already
constructed application object, while the contract says the parent starts two
production-application workers with verifier dependencies. PHP application
objects containing `mysqli`, streams and observers cannot be serialized through
the stated single JSON command line. No worker bootstrap/factory input contract
defines how each child reconstructs the shared DB/storage application.

The lifecycle observer has only `observe(event): void`; no exact barrier IPC
port, ready signal, release signal, timeout/failure mapping or output framing is
declared. The statement that inherited pipes carry one command/result JSON line
also conflicts with the additional synchronization required to prove both
workers reached `AFTER_FINGERPRINT_MISS_BEFORE_CAS` before release.

Define the callable child bootstrap and its serializable configuration, plus an
exact separate barrier protocol (or exact additional file descriptors/messages),
bounded waits, malformed/EOF outcomes and cleanup/reaping behavior. Preserve the
rule that this barrier is verifier-only and production-inaccessible.

### 4. Repository canonical JSON is not an exact executable contract

The repository mutation seam accepts opaque `string $transactionJson`, but no
canonical key/schema/version is specified for either accepted transactions or
attempt-audit transactions. Consequently a RED author must invent how a real
MariaDB adapter receives revision, request result, fingerprint, event, CAS
expected leaf and audit values, and cannot independently assert that one atomic
transaction owns all of them. The prose requirement to validate rehydrated
identity/digest/status combinations also has no typed persisted-result/evidence
schema against which to test fail-closed behavior.

Replace the opaque unspecified payload with typed immutable commit requests, or
normatively define the complete versioned canonical JSON shapes and validation
rules. Do the same for maintenance terminal results. Exact DB table design may
remain implementation-owned; application-to-repository facts and CAS inputs may
not.

### 5. Stream staging/cleanup and storage-observation claims do not match the exposed API

The behavioral contract says candidate bytes are streamed into bounded staging
and that failed/incomplete reads leave an owned stage cleaned or quarantined.
The only storage operation accepts the entire completed PDF as a `string`, so no
storage stage exists during acquisition and the application has no cleanup
handle. The observer lists stage/finalize events but is not passed into storage;
the contract does not state which owner emits them or how an adapter failure
between begin/done maps cleanup and privacy evidence. The fault enum has
`DIGEST_LOCK` and `ORPHAN_DELETE`, but there is no corresponding typed lock seam
and no stage cleanup/quarantine fault/outcome.

Choose and specify one coherent model: either application-bounded in-memory
acquisition followed by storage-owned stage/finalize (and remove claims about
stream-time storage staging), or a typed staged-writer lifecycle with abort and
ownership. In either case, assign observer emission and cleanup responsibility
at every failure point so ordering/privacy tests do not guess implementation.

## Non-blocking observations

- Authorization is correctly re-evaluated before terminal request replay, so a
  revoked actor cannot recover accepted evidence; an earlier authorized denial
  remains replayable only after current authorization passes. Result DTOs do not
  expose filename, path, composition, correction reason or parser/storage/SQL
  diagnostics.
- `FMonitorPassivePdfInspector` is correctly fixed as the production parser and
  verification injection cannot construct application acceptance results.
- The production upload factory has an explicit trusted configuration and the
  spec forbids runtime selection of verifier observers/faults. Equivalent
  guarantees must be added to the missing maintenance/worker construction.
- `AssignmentOrderOriginalEvidenceReader` describes ordering in prose, but exact
  top-level/member JSON shapes would make independent MariaDB assertions safer.
  This becomes blocking only if the opaque repository transaction payload is not
  replaced by typed DTOs.

## Verification evidence

```text
$ php -l <concatenated normative PHP blocks>
No syntax errors detected

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff --check
PASS (no output)
```

The working tree was already heavily dirty. This reviewer changed no reviewed
specification, OpenSpec artifact, test or production file; only this append-only
review record was added. Task 1.6 must remain open, owner exact-hash approval v2
must not be requested, and Gate 2 remains blocked until a fresh rereview approves
the amended exact hashes.
