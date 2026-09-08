# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v44 cleanup safe logs — independent Gate 1 review

Date: `2026-09-05`

Reviewer: separately tasked agent `/root/assignment_cleanup_log_gate1`

Reviewed commit: `b32debcb6c08f38d854686eca64e5882c42395ea`

Scope: cleanup safe-log amendment and its coherence with the full executable
specification/OpenSpec package and the recorded Gate 1 gap; no tests or
production implementation reviewed or changed

Verdict: **CHANGES_REQUESTED**

## Exact reviewed artifacts

```text
e6876e3a8e247d40f86cec35a01d3b848ec14eab748cb24440d92765e974405c  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
22c35b77efeb68d367a502632f4bde8a76b60f6195d8a82be8f834256c2e765b  openspec/changes/replace-pilot-registration-with-original-upload/design.md
5db933badf800da6c77e946594c5f4ef109d7f11f11e661fed294f213201e925  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
99e8fab2396a8936934c4f744b8cce4f5efe10096fb6eb3313e99833854b8ebe  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
c2cc5e04e288a18ad0c1dc0154176cbd61a50a581a60e365aebf446a6e1b8b84  docs/operations/assignment-order-original-storage-close-safe-log-gate1-gap-2026-09-05.md
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

### G1-44-1 — stream-close Result semantics contradict the existing normative rule

The amendment says without qualification that cleanup failures never replace
the selected Result. Section 5 still says that a stream `close()` failure
before accepted commit produces `FAILED/STREAM_FAILURE`, while only a failure
after commit preserves the accepted stored Result. Those statements prescribe
different observable outcomes whenever a pre-commit branch has already selected
a rejection/conflict/failure and stream close then fails. A RED author must
choose which statement wins and would therefore be inventing behavior.

State an exact precedence table for stream close before and after durable
acceptance, and separately for stage abort and stage close. For each reachable
base outcome say whether cleanup preserves it or changes it to one exact
status/reason. Keep the exact cleanup call order and corresponding log order in
that table, including the outcome when more than one cleanup primitive fails if
such a combination is reachable.

### G1-44-2 — the required worker fault evidence is not constructible

The closed verification fault enum exposes `STREAM_READ`, `STAGE_ABORT` and
`STAGE_CLOSE`, but no stream-close fault. It also exposes no safe-log-write
failure. Worker config accepts only one canonical member of that exact enum and
forbids arbitrary fault lists/callbacks; the worker must bind the real safe-log
observer. Consequently Gate 2 cannot construct the amendment's isolated
Example-A stream-close line or its asserted no-retry/no-Result-change behavior
for a log-write failure through the declared real-worker seam. Injecting an
unpublished selector would violate the specification.

Add exact verification-only fault selections (or another equally explicit
public verification construction) for stream close and safe-log write. Specify
their one-shot location, whether they return typed failure or throw, the base
Result used by each canonical run, exact result/evidence expectations, and that
production binds neither selector. Preserve the one-fault-only rule unless a
combined cleanup-order case is explicitly required by the resolution of
G1-44-1.

## Checks that pass

- Example A request ID `00000000-0000-4000-8000-000000000001` hashes to
  `11e594f481958c10...`; its published first 12 lower hex correlation
  `11e594f48195` is independently correct.
- The three isolated `aoou-logs-v1` literals are recursively binary-key-sorted:
  top-level `items,schema`, item `correlationId,event,safeFields,sequence`, and
  sole safe field `phase`.
- Event names and phase values distinguish abort, stage close and stream close;
  the prose requires one record per failing primitive in cleanup order.
- Request/file/content/storage identity, path, bytes, SQL, exception and
  diagnostics are excluded. Safe-log write failure is best-effort, has no
  retry, cannot alter Result and exposes no public diagnostic once a
  constructible fault route exists.
- Lease-release logging coherently inherits the same request correlation while
  retaining its already approved event and exact phase vocabulary; lease
  lifetime/release ordering is not weakened by this amendment.
- OpenSpec delta/design/tasks trace the intended amendment and strict validation
  succeeds.

## Verification evidence

```text
$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ printf %s 00000000-0000-4000-8000-000000000001 | shasum -a 256
11e594f481958c10e3015d0bf0447a22f068a8a647f475df15ce2c7ab4b8f3f1  -

$ git diff --check
PASS (before this append-only review; no output)
```

Gate 1 v44 is not approved. Resolve both findings in the executable
specification and matching OpenSpec artifacts, then obtain a fresh independent
Gate 1 review on the new exact commit before extending RED evidence.

The reviewer changed no executable specification, OpenSpec artifact, test or
production file. Only this append-only review record was added.
