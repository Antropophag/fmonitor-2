# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v46 cleanup precedence — independent Gate 1 rereview

Date: `2026-09-05`

Reviewer: separately tasked agent `/root/assignment_cleanup_log_rereview2`

Reviewed commit: `e5abfac4bd0cffdae32cf78a99da21544d0c3d3b`

Scope: OpenSpec tasks `1.29` and `1.30`, including closure of v44/v45
cleanup-order, close-before-commit, selector and safe-log-observer findings; no
test or production implementation reviewed or changed

Verdict: **CHANGES_REQUESTED**

## Exact reviewed artifacts

```text
023137c7dbaa36b3a2e7416665e6bbea41e49f18655de5024423df17f869294f  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
6e03adaff4887b9642e8204788a934e95b44566de2425df28578659505ee9858  openspec/changes/replace-pilot-registration-with-original-upload/design.md
fbc39cf38755ac652ab7b51ddf2e5b7f9deffe149198a9bb0fe531eeeae281e9  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
7d7e26e41f29fda0257fefce763ece6b65b69eea7532de73fe6718b3dec3b7fa  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
cfe96f8477c5e9125eb6db8ecab7727b7da4dbecf3b75f7061dcbf3a6995ddd4  docs/operations/pilot-assignment-order-original-cleanup-safe-log-gate1-review-v44-2026-09-05.md
86ab1d39c4c6e2c3bdae07ad02b1aa4b883db0f6285610639e16573a9cc081c7  docs/operations/pilot-assignment-order-original-cleanup-precedence-gate1-rereview-v45-2026-09-05.md
```

Canonical context/process hashes consulted:

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
```

## Findings

### G1-46-1 — the closed PHP contract still permits post-commit stream close

Section 10 and the new cleanup paragraph now place accepted-candidate stage
close and stream close before the DB commit. That placement correctly makes a
stage-close failure `FAILED/STORAGE_FAILURE`, a stream-close failure
`FAILED/STREAM_FAILURE`, forbids commit, retains the finalized private orphan
and releases the lease with `rolled_back` phase.

However, the normative stream contract at line 616 remains unchanged:
application must call `close()` in `finally`, and a close failure *after commit*
is an operational safe-log failure that preserves the accepted result. This is
not merely stale explanation: it is part of the exact PHP construction contract
and explicitly defines an observable post-commit branch that the v46 protocol
forbids. An implementation or RED author can conform to either order and obtain
different persistence, Result and lease-phase evidence.

Replace that sentence with the same close-before-commit rule as section 10 and
the cleanup paragraph. It must not retain any reachable accepted post-commit
stage/stream-close branch. Keep the one exact close attempt and state how
non-accepted early exits use the invalid-path cleanup order.

### G1-46-2 — the published stage-abort evidence is still unreachable from its stated canonical run

The v46 correction appropriately removes worker composite fault selectors and
makes safe-log-write failure constructible by injecting a throwing
`AssignmentOrderOriginalSafeLogObserver` through the public verification
factory. `STREAM_CLOSE`, `STAGE_CLOSE` and `STAGE_ABORT` remain plain,
non-production selectors, so no worker composite is needed.

Yet the exact evidence paragraph still says that **each** isolated one-fault
run is an Example-A run. Example A is the valid passive PDF accepted candidate;
it never calls `abort`. Selecting only `STAGE_ABORT` therefore cannot reach the
abort primitive, while combining it with a stream/validation/storage fault is
forbidden. The spec consequently publishes an exact abort log line without a
constructible canonical base operation and leaves Gate 2 to invent which
rejection/failure triggers abort and its exact Result/audit/blob inventory.

Name one exact invalid base command for the plain `STAGE_ABORT` run (for
example, one already specified malformed-PDF literal), its selected status and
reason, exact applicable abort→stage-close→stream-close sequence, audit/blob/log
inventory and same-request retry expectation. Use that same base run when the
throwing safe-log observer is injected. The stage/stream-close accepted runs
must likewise retain the already stated no-commit/private-orphan/rolled-back
lease evidence. No composite worker selector or production selector is needed.

## Checks that pass

- The accepted path is now explicitly stage close → stream close → commit, and
  its first close failure mapping is otherwise coherent: no commit, private
  orphan retained, lease released once as `rolled_back` and later cleanup does
  not replace the first technical Result.
- The invalid path is explicitly abort → stage close → stream close, with all
  applicable primitives attempted once before terminal rejection/conflict
  audit; close failures preserve its selected non-accepted Result.
- The three plain cleanup selectors exist. The former three composite
  safe-log-write enum members are removed, and the verification factory's
  injected observer makes a throwing, zero-log-byte, no-retry observation
  possible without worker or production composite faults.
- Correlation is independently correct: the Example-A request ID hashes to
  prefix `11e594f48195`.
- Event names, sole `phase` safe field, canonical `aoou-logs-v1` key ordering
  and sequence are exact. Payload, request/file/content/storage identity, path,
  bytes, SQL, exception and diagnostics remain forbidden.
- The OpenSpec delta/design/tasks track the intended v46 amendment and strict
  OpenSpec validation succeeds.

## Verification evidence

```text
$ git rev-parse HEAD
e5abfac4bd0cffdae32cf78a99da21544d0c3d3b

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff --check 9fddba2..e5abfac4bd0cffdae32cf78a99da21544d0c3d3b
PASS (no output)

$ printf %s 00000000-0000-4000-8000-000000000001 | shasum -a 256
11e594f481958c10e3015d0bf0447a22f068a8a647f475df15ce2c7ab4b8f3f1  -
```

Tasks `1.29` and `1.30` are not approved at this commit. Resolve both findings
in the executable specification and matching OpenSpec artifacts, then obtain a
fresh independent Gate 1 rereview before authoring cleanup-fault RED.

The reviewer changed no executable specification, OpenSpec artifact, test or
production file. Only this append-only review record was added.
