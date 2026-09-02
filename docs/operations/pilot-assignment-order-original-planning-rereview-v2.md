# Independent planning rereview v2 — pilot assignment-order original

Date: 2026-09-02  
Reviewer task: `/root/assignment_order_planning_rereview_v2`  
Reviewed change: `replace-pilot-registration-with-original-upload`  
Verdict: **CHANGES_REQUIRED**

## Reviewed immutable inputs

```text
133993c4d080f159364f3f980ca67b649414bfecafcfb46ba09689248d87642b  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
e2e2a234c03fbd2be6c26568ae8e3127de7fbbd35215f17e5aeae2debe4975ab  openspec/changes/replace-pilot-registration-with-original-upload/design.md
a26cdcbe765de560c9999009ccfa53331d038db2ef868c6f837a049c8eb75a57  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
01791cf2038213049d74d07d7458175d3fc3674f206bc26f984e3750a223a292  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
```

Owner evidence reviewed: `docs/operations/pilot-assignment-order-original-owner-decision-2026-09-02.md`. Process and product inputs reviewed: `AGENTS.md`, `PRODUCT.md`, `CONTEXT.md`, `docs/development-process.md`, `docs/fmonitor-2-pilot-spec.md`, `docs/fmonitor-2-pilot-data-model.md`, and the two preceding independent planning reviews.

## Resolved findings

- The change is now one command-only vertical slice. HTTP upload, metadata read and download are absent from its implementation tasks and assigned to a separately approved future change, `expose-assignment-order-original-http`.
- The future read capability is reserved exactly as `assignment_order.original.read`; it is explicitly independent of upload/correct capabilities and display roles. Exact routes, projection, local authorization, disclosure and response policy are correctly deferred with that HTTP slice.
- Accepted-correction retry precedence is deterministic: shape and authorization, stored `requestId`, accepted-operation fingerprint even when the former target is no longer the current leaf, then current-target/revision/no-change validation. Changed evidence cannot borrow the replay result.
- The result matrix now distinguishes `ACCEPTED`, `REPLAYED`, `REJECTED`, `CONFLICT` and retryable `FAILED`. Business/file reasons, concurrency conflicts, storage failure, definite persistence failure and unknown commit outcome have stable mappings.
- The storage protocol is coherent: bounded staging and validation, private content-addressed finalize before DB commit, atomic revision/result/audit persistence, no post-commit finalize, private orphan reconciliation/reuse and stored-result recovery after response loss.
- Composition application, sequential-order applicability and opening remain outside this slice under three named future lifecycle changes. Upload acceptance does not mutate composition, case/opening facts or checklist availability.
- The engineering decisions remain traceable to the owner's approved one-PDF, 20 MiB, actor, date, explicit composition-confirmation, idempotency and append-only-correction direction. No new owner-visible policy is invented.

## Findings

### 1. BLOCKING — active manual-registration truth has not yet been superseded

The revised plan correctly moves coherent supersession into task 1.1 before executable-spec approval and RED, but the required work is still unchecked and the active sources remain contradictory at review time:

- `docs/fmonitor-2-pilot-spec.md` still makes manual 1C DO number entry and `registered` status the pilot opening gate;
- `docs/fmonitor-2-pilot-data-model.md` still exposes `confirmOrderRegistration` and registration-owned assignment/opening semantics;
- `CONTEXT.md` still defines opening and current assignment facts using registered orders.

The first review explicitly required amended exact hashes, not only a plan to amend them. Gate 1 cannot have one unambiguous source of truth while these active contracts remain normative. Complete task 1.1, keep historical review/evidence records append-only, include the promised inventory showing every active dependent change/spec/test disposition, and then request a fresh planning rereview. The legacy production opening implementation may remain as a named predecessor, but active documentation must not present it as target pilot behavior.

### 2. BLOCKING — command-only authorization scenario requires an undefined local capability

The normative requirement correctly says this slice checks exact **process** capabilities and that HTTP/local RBAC is out of scope. Its two positive scenarios nevertheless say the actor has the exact “local and process capability” `assignment_order.original.upload` / `.correct`.

No local capability with either string is defined, and the future local/read capability is separately reserved as `assignment_order.original.read`. A RED author could therefore require an accidental local grant, or could ignore the word `local`, and either behavior could claim conformance. Remove `local and` from the two command scenarios (or otherwise state literally that only the exact process capability is checked at this seam). Keep local session/route admission entirely for `expose-assignment-order-original-http`.

## Scope and safety assessment

Apart from the two blockers above, the current change closes the prior retry, status/failure and DB/filesystem publication ambiguities. Its command-only vertical boundary is appropriately narrow and its future HTTP/read capability reservation is sufficient without prematurely specifying that separate surface.

No reviewed OpenSpec artifact, executable test or production file was edited by this reviewer. This append-only review record is the only review output.

## Verification

```text
$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff --check
PASS (exit 0 at review time before this review record was added)
```

Structural validation is GREEN; planning verdict remains **CHANGES_REQUIRED**.
