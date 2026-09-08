# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v50 worker barrier event — independent Gate 1 review

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/assignment_barrier_event_gate1`
- Reviewed commit: `427d343895f97c64e62dd6fae1548e8e0f50df45`
- Scope: v50 executable-spec/OpenSpec worker barrier-event amendment only; no
  test or production implementation reviewed or changed
- Verdict: **CHANGES_REQUESTED**

The reviewer authored none of the reviewed specification, OpenSpec artifacts,
tests, production implementation or prior evidence. This append-only review is
the only authored artifact.

## Findings

### 1. The canonical IPC contract still permits the barrier only at fingerprint miss

The amendment says that `barrierEvent` selects either
`after_fingerprint_miss_before_cas` or
`after_private_finalize_before_commit`, and that READY/RELEASE blocks only the
selected lifecycle event. However, the later canonical IPC paragraph still
states exclusively:

```text
Barrier uses separate FDs: at `AFTER_FINGERPRINT_MISS_BEFORE_CAS` child writes
`READY <requestId>\n`, flushes, then waits ...
```

It never defines the same wire action at
`AFTER_PRIVATE_FINALIZE_BEFORE_COMMIT`. These are competing normative rules in
one executable specification. A Gate 2 author cannot determine whether an
after-finalize worker must write READY/wait RELEASE or merely observe that
event, and implementations satisfying either reading are possible.

Amend the canonical IPC paragraph so the selected event, not the fingerprint
event alone, triggers the exact READY/RELEASE exchange. Retain the existing rule
that the unselected event is observed without blocking, and state the same
malformed/EOF/timeout/no-commit behavior for either selection.

### 2. The lease-race maintenance commands and age basis are not exact

The new acceptance paragraph identifies maintenance requests only as
`...0401` and `...0402`. It does not publish their full UUIDs or their remaining
command values: exact system principal, cutoff, batch limit and cursor. It also
does not fix the maintenance clock used for these calls. This matters
behaviorally: the finalized candidate is timestamped during upload, while the
maintenance contract may enumerate it only when its timestamp is at or before
the cutoff and the cutoff is at least one hour older than maintenance `now`.
With the worker's canonical upload clock alone, the newly finalized blob is not
eligible. A test could therefore obtain an empty `COMPLETED` page, use an
unpublished later clock, or seed an unrelated candidate while still claiming
the summarized counts.

Publish both complete command fixtures and the exact injected maintenance
clock/cutoff relationship that makes this upload-created candidate eligible.
Fix `batchLimit`, null/exact cursor and `test-maintenance-01` principal, and
require the fresh inventory to identify the one scanned/retained candidate as
the worker's finalized content identity. This makes the two expected results
and their request/audit rows independently derivable:

- paused request: `PARTIAL/LOCKED`, retryable true,
  `scanned/deleted/retained/failed = 1/0/1/0`;
- post-commit new request: `COMPLETED`, no reason, retryable false,
  `1/0/1/0`, with the referenced blob byte-identical and retained.

The existing requirement for real production repository/private storage and a
fresh production evidence reader is sound, as is production isolation: the
selector is confined to verifier worker config, invalid configuration is
pre-secret exit 70, and production binds no-op lifecycle observers with no
selector. The event name and upload lease rules also correctly place the
intended pause after finalize while the exclusion lease is held and before a
commit attempt. Those properties do not resolve the two executable ambiguities
above.

## Verification evidence

```text
$ git rev-parse HEAD
427d343895f97c64e62dd6fae1548e8e0f50df45

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff --check 427d343895f97c64e62dd6fae1548e8e0f50df45^ 427d343895f97c64e62dd6fae1548e8e0f50df45
PASS (no output)
```

## Exact reviewed hashes

```text
bf844f92f8331d594fe9d24bb2b64f7008e1c35ebd2d9c5c216c41ff1d8f7c5e  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
37cca851a6d5983f78f0185c6174e51f6aadb72f14347182ec4272fdfd2382bc  openspec/changes/replace-pilot-registration-with-original-upload/design.md
61a7aa2e1c65a479528a18a86c62893862c05e6b89f1bbefc39fca1b93036b82  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
981f7544207ea80d5441b472e59021d39789a6c079827b2513c762f0d136ed5a  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
b25e3a8506cf3313fba8d23b7fea2da798fe1ff1c548dc1ae987f9c4a6ae7a1a  docs/operations/assignment-order-original-worker-composition-source-gate1-gap-2026-09-05.md
```

This record intentionally omits its own circular hash.
