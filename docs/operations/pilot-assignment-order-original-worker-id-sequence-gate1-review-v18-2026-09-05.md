# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v18 worker ID sequence — independent Gate 1 review

- Date: `2026-09-05`
- Reviewer: separately tasked fresh agent `/root/assignment_worker_ids_gate1`
- Reviewed commit: `0711f7277dc6701520b8f55c7a9a0a1cd4ecfd7e`
- Triggering gap: `c3ce3db`
- Approved worker-output base: `563d171800f497587db49164be41f2ee8d35d15f`
- Scope: technical worker ID-sequence amendment and coherence of the complete
  current executable specification/OpenSpec package; no tests or production
  implementation reviewed
- Verdict: **CHANGES_REQUESTED**

The reviewer authored none of the reviewed specification, OpenSpec artifacts,
tests or production implementation. This append-only review is the only
authored artifact.

## Blocking finding

### G1-V18-01 — declared total-byte bounds do not follow from the exact token grammar

Both admitted token forms are exactly 13 ASCII bytes:

```text
original- + four decimal digits = 9 + 4 = 13
revision- + four decimal digits = 9 + 4 = 13
```

With `n` tokens and `n-1` one-byte commas, the only possible complete sequence
length is `13n + (n-1) = 14n-1`. Therefore the declared `1..1024` member range
implies an actual total range from 13 bytes to
`13*1024 + 1023 = 14335` bytes. The normative v18 text instead declares total
length `1..82943`.

The lower endpoint `1` and every length except `14n-1` are already impossible
under the exact-token rule; the upper endpoint `82943` is impossible under the
member-count rule. Consequently neither total endpoint can be exercised as a
valid boundary, and no independently derived Gate 2 test can distinguish
enforcement of this stated total bound from enforcement of token/count alone.
This fails the Gate 1 requirement that the public input and its acceptance
boundaries be unambiguous and observable.

Smallest correction: replace `total length 1..82943` with the mathematically
coherent closed bound `total length 13..14335`, or state only the independently
observable exact length formula `14n-1` for `n` members. A fresh independent
Gate 1 review is required after that normative/OpenSpec correction.

## Non-blocking findings

The remainder of the amendment closes the triggering gap coherently:

- each complete input is ASCII, non-empty and comma-delimited without quoting,
  escaping, whitespace, empty members or a trailing comma;
- `unique` applies within each sequence, and `exact original-[0-9]{4}` versus
  `exact revision-[0-9]{4}` gives disjoint, anchored token languages;
- both sequences are fully validated before password content, command input or
  any external access, and invalid input inherits exact exit `70`, one fixed
  stderr line, empty result and empty barrier channels;
- root and revision sources consume independently, strictly left-to-right only
  when that kind is requested, never rewind, and may retain an unused suffix;
- exhaustion is a typed runtime ID dependency failure mapped by the command to
  `FAILED/PERSISTENCE_FAILURE`, not a worker bootstrap exit;
- the initial literal consumes `original-0001` and `revision-0001`; correction
  roots are deliberately unused; identical correction workers receive the
  same `revision-0002`, while different workers receive `revision-0003` and
  `revision-0004`;
- the identical-race collision semantics are explicit: the winner persists the
  shared generated revision ID and the loser resolves that accepted fact by
  fingerprint before attempting a distinct accepted fact.

The amendment introduces no product behavior, role, capability, workflow,
HTTP route, opening/composition change, runtime selector, second mutation seam
or runtime DDL. Task 1.16 does not pass, and task 4.1 must not rely on v18 as an
approved worker sequence contract.

## Verification

```text
$ git rev-parse HEAD
0711f7277dc6701520b8f55c7a9a0a1cd4ecfd7e

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff 0711f727^ 0711f727 --check
PASS (no output)

$ git diff --name-only 0711f727^ 0711f727
openspec/changes/replace-pilot-registration-with-original-upload/design.md
openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
```

The reviewed commit changes specification/OpenSpec artifacts only; it adds no
production worker or runtime selection path.

## Exact reviewed hashes

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
2fc6b60aa12f39b41420f295bf9beb3d7e12d83ca6679f1ac91940271a2e225c  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
60495390c885ad6524ced4e8beb0c23773e01795c5d6e7c921e5da9b2ad15afb  openspec/changes/replace-pilot-registration-with-original-upload/design.md
f7572685872607bdc9c6facec810256ac54abc35d652b4e76304357d4a0754b8  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
5eafa180291aabf57aa1204107e37a91bf165e9efc0c65e5914a7e65b03ea8fd  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
3ddf2ce48ede89d255a3f6ff2f7b5c3c262f7ca94a17dd832f6254f5f159f9b8  docs/operations/assignment-order-original-worker-id-sequence-gate1-gap-2026-09-05.md
41e54cffdedd611ee55dbaa3d7e62670421868bcbc78150737e568c32c3a9946  docs/operations/assignment-order-original-worker-dsn-technical-approval-2026-09-05.md
9097107f04d9b20a970b88fdf07cb8928838f93808eef7dbc89dc0e49835e73d  docs/operations/assignment-order-original-worker-failure-output-technical-approval-2026-09-05.md
```

This review record omits its own circular hash.
