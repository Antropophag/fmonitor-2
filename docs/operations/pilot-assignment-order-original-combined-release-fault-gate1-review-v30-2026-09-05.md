# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v30 combined release fault — independent Gate 1 review

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/assignment_release_fault_gate1`
- Reviewed commit: `7a9e5b100db07f3b4cd60b572b01b2383bbd1bbf`
- Triggering gap: `1a8bb8d`
- Scope: combined real-repository commit/release-fault amendment and coherence
  of the current executable specification/OpenSpec package; no tests or
  production implementation reviewed
- Verdict: **APPROVED**

The reviewer authored none of the reviewed specification, OpenSpec artifacts,
tests or production implementation. This append-only review is the only
authored artifact.

## Review result

V30 closes the constructibility gap without opening an arbitrary fault language.
The canonical enum adds exactly four bounded, one-shot composite selectors:

- `COMMIT_BEFORE_RELEASE_FAILURE` performs the definite rollback behavior of
  `COMMIT_BEFORE`, selects retryable `FAILED/PERSISTENCE_FAILURE`, then makes
  the sole lease release return typed `FAILED` and emits exactly one safe-log
  event with `phase=rolled_back`;
- `COMMIT_UNKNOWN_FOUND_RELEASE_FAILURE` durably commits, reports unknown,
  permits the one fresh terminal-request read to prove `FOUND`, selects the
  stored accepted result, then fails the sole release and logs
  `phase=unknown_found` without replacing that result;
- `COMMIT_UNKNOWN_NOT_FOUND_RELEASE_FAILURE` rolls back, reports unknown,
  permits the one fresh read to prove `NOT_FOUND`, selects retryable
  `FAILED/PERSISTENCE_FAILURE`, then fails the sole release and logs
  `phase=unknown_not_found` without replacing that result;
- `COMMIT_UNKNOWN_UNAVAILABLE_RELEASE_FAILURE` durably commits, reports
  unknown, makes exactly the one fresh lookup unavailable, selects retryable
  `FAILED/PERSISTENCE_OUTCOME_UNKNOWN`, then fails the sole release and logs
  `phase=unknown_unavailable` without replacing that result. The later normal
  same-request invocation still observes the durable terminal row and returns
  `REPLAYED` without a second domain effect.

Those effects are independently determined by the explicitly inherited base
scripts plus the existing storage/commit matrix. In every case the base script
is consumed once, release is attempted exactly once after the prescribed
terminal lookup/rollback phase, release failure preserves the already selected
command Result, and no response-delivery observer precedes the release attempt.
The safe-log contract is exact: one
`ASSIGNMENT_ORDER_ORIGINAL_CONTENT_LEASE_RELEASE_FAILED` line with a 12-hex
correlation ID and the named phase, and no content identity, digest, path,
request data or exception text.

Coverage remains closed for the two non-composite outcomes. Plain
`CONTENT_LEASE_RELEASE` exercises release failure after ordinary committed
success (`phase=committed`) and, with the already specified real
different-correction CAS loser, after natural conflict resolution
(`phase=commit_conflict`). This does not synthesize or bypass the real CAS
path. No list, delimiter grammar, repeated selector, environment-controlled
composition or other arbitrary multi-fault configuration is accepted.

Production remains bound to inert faults and cannot select any verification
script through environment, request, CLI, config, global or service locator.
The amendment changes no actor, capability, workflow, HTTP contract, schema,
original lineage, composition/opening behavior, runtime DDL or blocked legacy
E2E behavior.

Gate 1 for the v30 combined release-fault amendment is therefore
**APPROVED**. This review makes task 1.21 eligible for integrator bookkeeping
and authorizes preparation of the corresponding Gate 2 RED; it does not approve
any test or production artifact.

## Verification

```text
$ git rev-parse 7a9e5b100db07f3b4cd60b572b01b2383bbd1bbf
7a9e5b100db07f3b4cd60b572b01b2383bbd1bbf

$ git diff 7a9e5b1^ 7a9e5b1 --check
PASS (no output)

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid
```

## Exact reviewed hashes

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
3d60ecd7f62220e89c256f6115eccc486c148ca2dee9843b314b53a360f5e9d1  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
0f57d5a958ae5cc6cd1484325810d733a1ab1a162d2f257b3adcc87769990a3a  openspec/changes/replace-pilot-registration-with-original-upload/.openspec.yaml
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
68b6087ee77eccd8404b63be4f58826df78d29f674da56157e750e65f76ca2ca  openspec/changes/replace-pilot-registration-with-original-upload/design.md
6e7ba149f85b93ffabe9b4ebebe3c45832e22e8f3c884bb2f21583270385f15e  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
6a29b2d6a14a2458e7fefdf5704d8c3b5a43124b9f36216a87b008e3b8f174fe  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
269770f9e8403c8b755e00f32ff9332dbbe4287af23a49722d5b338ed535b7f2  docs/operations/assignment-order-original-worker-combined-release-fault-gate1-gap-2026-09-05.md
8c10fbf712202ad91ae99fbd6ded029a0126d9212e2829e7e8f2128f6eef356a  docs/operations/pilot-assignment-order-original-unknown-outcome-fault-gate1-rereview-v29-2026-09-05.md
```

This review record omits its own circular hash.
