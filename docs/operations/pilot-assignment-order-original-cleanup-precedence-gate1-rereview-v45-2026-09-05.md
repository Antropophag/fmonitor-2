# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v45 cleanup precedence — independent Gate 1 rereview

Date: `2026-09-05`

Reviewer: separately tasked agent `/root/assignment_cleanup_log_rereview`

Reviewed commit: `dfce2de1366105c627b646f269cde01a78de47b3`

Scope: OpenSpec tasks `1.29` and `1.30`, including closure of v44 findings
`G1-44-1` and `G1-44-2`; no test or production implementation reviewed or
changed

Verdict: **CHANGES_REQUESTED**

## Exact reviewed artifacts

```text
63c2225b8cb8ebacf3732c2945a77062c119cab28225662785599d4596678dc7  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
358637d691ec9401a3e40e84a860f6c9d0fe926357d36c3db8b76d9da82a6aac  openspec/changes/replace-pilot-registration-with-original-upload/design.md
51bd1144038d55a7c03f480b1e03451237f574d89cabe9b09b3b3f8313a5d383  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
092cfe577a526d7fd10d8ee95a83cb789b2f0dc390159ee5f6eb650bfe81c42f  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
cfe96f8477c5e9125eb6db8ecab7727b7da4dbecf3b75f7061dcbf3a6995ddd4  docs/operations/pilot-assignment-order-original-cleanup-safe-log-gate1-review-v44-2026-09-05.md
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

### G1-45-1 — cleanup precedence remains internally contradictory and precommit close is not placed in the protocol

The unchanged normative sentence immediately before the new paragraph still
says, without qualification, that cleanup failures never replace the selected
Result. The new paragraph says that a first pre-durable stage-close or
stream-close failure replaces the provisional result with respectively
`FAILED/STORAGE_FAILURE` or `FAILED/STREAM_FAILURE`. Section 5 independently
says stream close before accepted commit maps to `STREAM_FAILURE`. The new text
therefore adds a precedence rule but does not remove or narrow the direct
contradiction identified by `G1-44-1`.

There is also no reachable placement for the newly described precommit close
branch in the exact protocol. Section 10 orders accepted execution as DB commit
at step 5 and stage/stream close in `finally` at step 6. A normal accepted
candidate therefore cannot encounter either close before commit. Rejected and
conflict paths are said to preserve their terminal result, but the spec does not
say whether their terminal request/audit transaction occurs before or after
close. A RED author cannot independently decide when close is attempted, which
repository operation is forbidden, or what durable/terminal state is expected.

Replace the blanket sentence with one coherent rule or table, and place
abort/stage-close/stream-close relative to accepted commit and rejected/conflict
attempt commit for every applicable path. The table must distinguish: an
already selected retryable failure requiring abort; a pre-durable accepted
candidate; a durably accepted/terminal request; and a terminal outcome requiring
no commit. It must state exact Result, allowed/forbidden repository calls,
durable state, cleanup attempt order and multi-failure precedence.

### G1-45-2 — the three composite selectors are named but not constructible as closed scripts

Adding `STREAM_CLOSE` closes the missing primitive selector. The three
`*_SAFE_LOG_WRITE_FAILURE` enum members, however, are described only as making
their named cleanup primitive fail and then its safe-log write fail. They do not
define the canonical base operation/result that reaches that cleanup primitive.

This is decisive for `STAGE_ABORT_SAFE_LOG_WRITE_FAILURE`: valid Example A does
not call abort, while the one-fault-only rule forbids combining this selector
with `STREAM_READ`, `STAGE_WRITE`, validation failure or another trigger that
would require abort. `STAGE_CLOSE_SAFE_LOG_WRITE_FAILURE` and
`STREAM_CLOSE_SAFE_LOG_WRITE_FAILURE` likewise do not say whether their
canonical run is precommit or post-durable, even though that choice changes the
required Result and persistence inventory under the new precedence rule.
“Preserve the precedence-selected Result” is circular rather than an independent
expected value.

Define each composite as a complete one-shot script: the exact base Example-A
input/outcome or internally scripted prerequisite failure, exact call at which
the cleanup primitive fails, exact one log write failure, exact returned
status/reason, exact requests/domain/events/audits/blob/log inventory, and
whether a normal same-request retry commits or replays. Keep the selector
verification-only, non-combinable and unreachable from production
environment/request/CLI/config/global state.

## Checks that pass

- `STREAM_CLOSE` is now an explicit enum member, so the isolated stream-close
  primitive failure itself is selectable.
- Correlation remains exact: Example A request ID hashes to prefix
  `11e594f48195`.
- Event names, sole phase field, canonical `aoou-logs-v1` key order and sequence
  are exact for the three isolated successful-log examples.
- The prose requires abort → stage close → stream close, attempt-always once,
  and log order matching cleanup order. Once reachability and precedence are
  closed, this is a suitable observable ordering contract.
- Durable accepted/terminal preservation and the existing content-lease
  release semantics remain intact; no cleanup amendment authorizes mutation of
  composition/opening state or exposure/deletion of private content.
- Safe-log payload exclusions cover request/file/content/storage identity,
  path, bytes, SQL, exception and diagnostics. A safe-log write failure is
  specified as zero bytes, no retry and no public diagnostic.
- Production isolation is explicit: production binds none of the cleanup/log
  selectors and exposes no selector through environment, request, CLI, config
  or global state.
- The matching OpenSpec delta/design/tasks trace the amendment and strict
  validation succeeds.

## Verification evidence

```text
$ git rev-parse HEAD
dfce2de1366105c627b646f269cde01a78de47b3

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff --check 611ee93..dfce2de1366105c627b646f269cde01a78de47b3
PASS (no output)
```

Tasks `1.29` and `1.30` are not approved at this commit. Resolve both findings
in the executable specification and matching OpenSpec artifacts, then obtain a
fresh independent Gate 1 rereview before authoring the cleanup-fault RED.

The reviewer changed no executable specification, OpenSpec artifact, test or
production file. Only this append-only review record was added.
