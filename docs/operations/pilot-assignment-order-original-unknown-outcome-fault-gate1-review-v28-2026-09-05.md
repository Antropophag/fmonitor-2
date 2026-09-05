# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v28 unknown-outcome fault — independent Gate 1 review

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/assignment_unknown_fault_gate1`
- Reviewed commit: `41b3bfab1424017950545893b95e54d700883450`
- Triggering gap: `a50034a`
- Scope: unknown-outcome real-repository fault amendment and coherence of the
  current executable specification/OpenSpec package; no tests or production
  implementation reviewed
- Verdict: **CHANGES_REQUESTED**

The reviewer authored none of the reviewed specification, OpenSpec artifacts,
tests or production implementation. This append-only review is the only
authored artifact.

## Blocking finding — the old and composite unknown selectors coexist ambiguously

V28 makes the three required effects independently expressible: the new
`COMMIT_UNKNOWN_FOUND`, `COMMIT_UNKNOWN_NOT_FOUND` and
`COMMIT_UNKNOWN_UNAVAILABLE` values prescribe durable commit plus fresh
`FOUND`, rollback plus fresh `NOT_FOUND`, and durable commit plus one fresh
`UNAVAILABLE`, respectively. It also fixes the finite scope: the script is
consumed once, cannot affect earlier request/fingerprint reads or later retries,
and the unavailable case is followed by a normal same-request worker that must
return `REPLAYED` from the durable row. Those rules are coherent with the
existing lease-release phases and result mappings.

However, the same canonical worker fault enum still contains the pre-existing
`COMMIT_AFTER_UNKNOWN = 'commit_after_unknown'`. The specification never says
whether that worker-selectable value remains valid, aliases
`COMMIT_UNKNOWN_FOUND`, has a different durable/rollback effect, or is no
longer accepted by worker config. The generic rule still says worker config
accepts the canonical fault enum, so both the old value and all three composite
values are valid inputs. Meanwhile the generic injector rule says it throws at
its named point, which does not independently determine the old value's real
repository commit status, fresh-lookup result, command result, or evidence.

This changes observable Gate 2 behavior. A test author cannot derive one exact
oracle for `faultPoint="commit_after_unknown"`, and a future worker can either
retain it as a fourth script, treat it as the FOUND script, or reject it while
claiming conformance to a different sentence.

## Required amendment

Close the worker language explicitly. Either remove/deprecate
`COMMIT_AFTER_UNKNOWN` from worker-accepted values and state that it is rejected
as invalid config, or define its exact distinct/alias semantics, including its
durability, fresh lookup, result and evidence. If it remains an internal
primitive point rather than a composite worker script, separate the worker
scenario enum from `AssignmentOrderOriginalFaultPoint` so the exact worker
config grammar cannot select it accidentally.

After amendment, a fresh independent Gate 1 review is required. Task 1.20 must
remain unchecked; this verdict does not authorize the corresponding fault RED.

## Coherence and scope checks

- The three new scripts otherwise close the `a50034a` constructibility gap over
  the real repository and fresh connection rather than an in-memory substitute.
- `FOUND` preserves the accepted/replayed outcome; `NOT_FOUND` maps to retryable
  `PERSISTENCE_FAILURE`; `UNAVAILABLE` maps to retryable
  `PERSISTENCE_OUTCOME_UNKNOWN`, and the next normal invocation replays. Lease
  release remains exactly once after each fresh lookup outcome.
- Production binds no-op faults and has no environment, request, CLI or config
  selector. Only the verification worker may select scripts.
- The amendment changes no role, capability, workflow, HTTP contract, original
  lineage, composition/opening behavior, schema, runtime DDL, or blocked legacy
  E2E behavior.

## Verification

```text
$ git rev-parse HEAD
41b3bfab1424017950545893b95e54d700883450

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff 41b3bfa^ 41b3bfa --check
PASS (no output)
```

## Exact reviewed hashes

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
86d1953ad04937a8a77332169ab80fe44a767dc648c13d9f99d13e541b013f27  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
3b26a248dbc545bde5544303b7e83419c1148d4ecbbe0305aadee8633fea5c32  openspec/changes/replace-pilot-registration-with-original-upload/design.md
2625105fb790c72f929a6ae813e67aa0e030c3ea74bf16babc044ba23a15e4b9  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
72aa32a0163e3e1299a86eed4e9334aaf2a2e77612f5eb3f515e3410073286ea  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
037e962e732f26b17c3f2dbdbe57ef29f34d419698e4db9c99a86319284c1add  docs/operations/assignment-order-original-worker-unknown-outcome-fault-gate1-gap-2026-09-05.md
```

This review record omits its own circular hash.
