# DATA-INTEGRITY v0.4 — initial precheck and fresh-close diagnostic clarification

Date: 2026-09-06.

Reviewer task: `/root/selection_v04_readiness`.

Reviewed HEAD: `110b05489091b4fb062f6786c77c078e9b0ec42e`.

Verdict: **TWO MATERIAL TECHNICAL CLARIFICATIONS REQUIRED BEFORE RELATED RED**.

This bounded follow-up does not edit DATA-INTEGRITY v0.4, its Gate 1 record or
the prior target-order clarification. No code, test or specification changed.

## Exact reviewed hashes

```text
e232bbdfb01c1671ff57e6f353c5b5b1b77c2b60680627b9be37299a2ff27e29  specs/ASSIGNMENT-ORDER-ORIGINAL-DATA-INTEGRITY-001.md
5274f339b6c73890438297135b739319d30bf787c781c89db7135079df616969  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
4ac2e79f8e21c1235f37a5578cda21cfd192e47d02d6627a3a832e8cf2b4f332  specs/ASSIGNMENT-ORDER-ORIGINAL-COMMAND-LIFECYCLE-001.md
41c79f58093850be318027a46d4615e698fe228ca701d063bf938540b95e7410  docs/operations/original-data-integrity-target-order-clarification-review-2026-09-06.md
```

## Finding 1 — INITIAL requires a normal pre-finalize lineage absence check

The parent literal step 11 is “initial/lineage/current/target/no-change checks”
and precedes step 12 private finalize. Parent initial acceptance also requires
absence of an existing original lineage for the assignment order, otherwise
`CONFLICT/INITIAL_ALREADY_EXISTS`.

Relying only on the repository's initial uniqueness collision after finalize does
not satisfy that order. It creates finalized private content and a lease before
checking a condition the public application can read after fingerprint miss.

### Required normal INITIAL order

After stream acquisition, PDF inspection and accepted-fingerprint NOT_FOUND:

1. Call the explicit assignment-order lineage query once for exact case/order.
2. Validate the closed lookup and complete lineage under DATA-INTEGRITY rules.
3. Complete valid FOUND → select `CONFLICT/INITIAL_ALREADY_EXISTS`.
4. Valid NOT_FOUND → proceed to root/revision IDs, finalize and commit.
5. UNAVAILABLE, base-only FOUND, wrong echoed query pair or malformed complete
   metadata → retryable `FAILED/PERSISTENCE_FAILURE`.

FOUND/conflict and unavailable/protocol failure are post-stream but pre-finalize.
They require stage abort, stage close and stream close once, finalize zero, lease
zero and accepted commit zero. The nonretryable INITIAL_ALREADY_EXISTS result
then performs its normal terminal attempt commit; retryable persistence failure
does not create a terminal fact.

If the repository lacks the optional assignment-lineage interface on this
required path, the application must fail persistence rather than treating it as
NOT_FOUND.

### Separate post-CAS race check

The precheck does not remove the post-CAS assignment-lineage reread. A concurrent
initial winner can appear after the valid NOT_FOUND precheck and before commit.
On repository CONFLICT:

- accepted fingerprint FOUND may select replay for an identical winner;
- fingerprint miss followed by complete exact assignment lineage FOUND selects
  INITIAL_ALREADY_EXISTS;
- lineage NOT_FOUND, UNAVAILABLE or malformed remains persistence failure.

The two reads have different evidence times. A deterministic race fixture should
return NOT_FOUND during normal step 11, expose the winner only after the commit
conflict, then return complete FOUND during post-CAS reclassification. This is not
a mutable contradiction: a new append-only root may legitimately appear between
the two snapshots.

### Compatibility fixture disposition

Ordinary initial repository fixtures that model no existing root must implement
the optional assignment-lineage query and return valid NOT_FOUND with null/empty
metadata. Existing public Result and resource expectations remain; their traces
gain the one pre-finalize lineage read.

Fixtures that simulate a CAS winner must distinguish the normal NOT_FOUND read
from the later FOUND read after their commit-conflict transition. Returning FOUND
before commit would correctly stop at step 11 and would not test the race path.

### Required tests

The v0.5 matrix should include:

- normal exact FOUND → INITIAL_ALREADY_EXISTS, cleanup and no IDs/finalize;
- normal NOT_FOUND → accepted path continues;
- normal unavailable/base-only/wrong-echo/malformed → persistence failure and no
  IDs/finalize;
- missing optional interface → persistence failure;
- race: precheck NOT_FOUND, commit CONFLICT, fingerprint winner FOUND → replay;
- race: precheck NOT_FOUND, commit CONFLICT, fingerprint miss, assignment lineage
  FOUND → INITIAL_ALREADY_EXISTS;
- race final lineage miss/unavailable/malformed → persistence failure.

Every case must assert exact query counts and ordering, not only final Result.

## Finding 2 — fresh-reader close diagnostic conflicts with the safe-log envelope

DATA-INTEGRITY v0.4 section 12 currently specifies event
`ORIGINAL_FRESH_READER_CLOSE_FAILED` with sole safe fields
`{requestCorrelation, phase}`.

The existing approved safe-log owner already constructs the canonical envelope:

```text
{
  correlationId: first12hex(sha256(exact requestId)),
  event: eventName,
  safeFields: supplied fields,
  sequence: n
}
```

Existing command diagnostics use the prefix
`ASSIGNMENT_ORDER_ORIGINAL_...` and supply only `phase` in safeFields. Adding a
second request correlation field would duplicate the same identity under a new
name and make the purported sole-field schema incompatible with the current
logger contract.

### Required exact correction

Pin the new diagnostic as:

```text
event = ASSIGNMENT_ORDER_ORIGINAL_FRESH_READER_CLOSE_FAILED
safeFields = {phase: accepted_commit_recovery|authorized_attempt_recovery}
```

The existing request-aware logger supplies `correlationId` in the outer envelope.
No `requestCorrelation`, requestId or other correlation field belongs inside
safeFields. Generic observers receive exactly the same event and phase arguments
without an invented envelope API.

Logging remains one best-effort attempt after a failed reader close. Logger
Throwable cannot replace the already validated recovery result, retry close or
alter lease cleanup. This is a naming/payload completion of existing diagnostic
policy, not a new logging mechanism.

Tests should assert exact underlying observer arguments and, for the real opened
owner, the canonical envelope with one `correlationId`, event, phase-only
safeFields and sequence. No path, DSN, SQL, exception or reader identity is
allowed.

## Relation to target-order clarification

Normal INITIAL assignment-lineage absence and normal CORRECTION target/current
checks both belong to parent step 11 before finalize. Their post-CAS behavior is
different:

- a new initial root can legitimately appear after the normal absence snapshot,
  so post-CAS assignment-lineage FOUND is reachable;
- a correction target already proved to be the append-only current member cannot
  legitimately become globally absent/foreign while current remains unchanged,
  so post-CAS foreign/absent target-owner resolution is not reachable.

The v0.5 amendment should preserve this distinction rather than applying one
generic lineage helper to all paths.

## Disposition

Both findings are technical consequences of already approved parent order and
safe-log schema. No owner decision is required. Pin normal INITIAL pre-finalize
lineage absence plus its distinct post-CAS race reread, and correct the fresh-close
event/payload in DATA-INTEGRITY v0.5. Obtain fresh independent Gate 1 before
capturing those RED cases. Unchanged result/composition/scalar test work remains
unaffected.
