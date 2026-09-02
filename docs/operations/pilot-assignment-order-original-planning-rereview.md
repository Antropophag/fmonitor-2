# Independent planning rereview — pilot assignment-order original

Date: 2026-09-02  
Reviewer task: `/root/assignment_order_planning_rereview`  
Reviewed change: `replace-pilot-registration-with-original-upload`  
Verdict: **CHANGES_REQUIRED**

## Reviewed immutable inputs

```text
8e4410e2dfed49534eea6a49649f6adfb2668ba0c7e5be03c0046dce81220098  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
d8f5bff01f9398408a36c1cbe95559ef55a1d44fa43212004693cba64040270a  openspec/changes/replace-pilot-registration-with-original-upload/design.md
74df85cfc3b17a075b15b0de8c06cd022ed98157de1b2760a72f4edb3ff82abb  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
5f242e2849b44b8fefd84aa9929a1f37526c6a395395870e099c6a6940e4986f  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
```

Owner evidence: `docs/operations/pilot-assignment-order-original-owner-decision-2026-09-02.md`. Process and product inputs: `AGENTS.md`, `PRODUCT.md`, `CONTEXT.md`, `docs/development-process.md`, `docs/fmonitor-2-pilot-spec.md`, `docs/fmonitor-2-pilot-data-model.md`, and `docs/installation-process-interface.md`.

## Resolved former findings

- Active manual-number/registration truth is now an explicit task 1.1 prerequisite to executable-spec approval and RED. Historical evidence remains immutable, and the still-legacy opening implementation is named as a predecessor rather than claimed GREEN.
- The former five `NEEDS_GRILL` items are resolved as engineering/security decisions: exact role codes/capabilities, inclusive 20 MiB received-byte limit, deterministic fail-closed PDF classes, semantic identity rules, and explicit deferral of sequential applicability.
- Initial/correction authorization is exact and fail closed; `manager` remains the technical role code while its display label becomes «Руководитель ФКР».
- The upload evidence slice no longer attempts to mutate composition or opening. Those responsibilities are assigned to named future changes.
- Command shape, date/clock boundary, immutable correction lineage, CAS loser outcome, basic PDF policy, private staging, digest verification, download headers and zero-public-orphan intent are materially sharper.

## Findings

### 1. BLOCKING — read projection remains neither authorized nor closed

The change promises `read/download`, but only download has an exact capability (`assignment_order_artifact.read`). The spec says merely that the read projection “shows lineage metadata”; it does not say which capability authorizes that query, whether inactive users and role/display-name fallbacks fail closed, or enumerate its result DTO. Consequently a RED author could expose `originalId`, document dates, actor identity, correction reasons, hashes, filenames or audit metadata to any authenticated user and still claim conformance.

Define the public read query, its exact authorization rule, its exact projection fields (including whether actor, reason, original filename and revision history are visible), not-found/forbidden behavior, and guarantee that bytes/private paths are absent. If read deliberately shares `assignment_order_artifact.read`, state that literally for both local and process authorization. Keep download separately explicit.

### 2. BLOCKING — accepted correction retry has ambiguous precedence

The semantic replay rule requires a new request ID fingerprint to match an “already accepted current revision”, while a correction fingerprint contains the target identity and the correction command necessarily targets the prior leaf. Immediately after successful correction, that target is no longer current. The same command retried with the same `requestId`, or semantic repeat with a new `requestId`, can therefore satisfy both replay intent and `TARGET_NOT_CURRENT`/`STALE_REVISION` rejection depending on validation order.

Specify deterministic precedence before RED: stored idempotency-key replay lookup first; then define whether cross-request semantic replay of an accepted correction matches the accepted result despite its now-superseded target, or is intentionally rejected. State the exact no-mutation result for each case. The current wording does not provide an independent oracle for retry-after-commit/response-loss, which is a core storage/persistence failure boundary.

### 3. MAJOR — result/rejection mapping is not yet exact enough for the promised executable boundary

The result DTO lists stable reason codes but does not normatively map all of them to `REJECTED` versus `CONFLICT`; only some scenarios do. Storage and persistence failures are called `REJECTED`, although these are retryable technical failures rather than rejected business commands, and no distinction is made between failure before commit and failure after commit but before finalize/response. The latter may already own an immutable fact and must be discoverable/replayable rather than reported as an unqualified no-fact rejection.

Close the status/reason matrix and the commit/finalize/response-loss outcomes in task 1.2. In particular, define what the caller receives when DB commit succeeded but publication/final response failed, how a retry discovers that fact, and when `REPLAYED` may be returned while download remains temporarily unavailable.

### 4. MAJOR — HTTP/read/download contract is deferred too far inside the same Gate cycle

Task 2.1 is a good smallest public-command RED, but task 2.2 expands the same approval cycle over PDF adversaries, authorization, replay, correction, concurrency and storage faults, while task 3.4 adds HTTP upload/query/download with no exact HTTP methods/routes/status/body/error/header contract in the delta. The development process requires the executable acceptance oracle before production work; “real HTTP tests” is not itself an oracle.

Task 1.2 must either close the HTTP/read/download contract before Gate 2, or the task plan must give HTTP/read/download a separately approved executable slice and RED/review/GREEN cycle. Do not let task 3.4 invent externally observable behavior after test review.

## Scope and safety assessment

The revised change is now credibly bounded as an **original-evidence upload slice**: it does not claim that upload applies composition or permits opening. Early supersession, engineering/security defaults, immutable history, private storage and named future composition/opening slices are sound. It is not yet executable because the read surface and accepted-correction retry behavior remain materially ambiguous.

No reviewed OpenSpec artifact, executable spec, test or production file was edited by this reviewer. This append-only review record is the only review output.

## Verification

```text
$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff --check
PASS (exit 0 at review time before this review record was added)
```

