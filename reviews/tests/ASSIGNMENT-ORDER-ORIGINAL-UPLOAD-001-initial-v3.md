# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — independent Gate 3 rereview, initial RED v3

- Date: `2026-09-05`
- Reviewer: separately tasked fresh agent `/root/assignment_initial_composition_gate3`
- Test author: separately tasked agent `/root/assignment_original_red2`
- Owner-approved Gate 1 composition amendment: `b15bd04c6a9d4766b684119e4b5db163b097b5d1`
- Reviewed RED correction commit: `1f1ec19b971bf9f51a12e9b11f92627d6844200c`
- Prior initial Gate 3: v2 approved the earlier downstream-sensitivity correction, before the v42 composition amendment
- Scope: OpenSpec task 2.4 and the corrected task-2.1 Example-A initial RED only
- Public seam: `AssignmentOrderOriginalVerificationFactory::create(...)->submitAssignmentOrderOriginal(...)`
- Verdict: **APPROVED**

The reviewer authored none of the specification, OpenSpec artifacts, owner
approval, test, support fixtures, production code, prior reviews or RED
evidence. This append-only review record is the only artifact added.

## Findings

### Exact v42 composition oracle is independent and sensitive

The corrected test constructs the canonical Example-A value map independently
of the application and fixture adapters, serializes it with fixed insertion/key
order and exact JSON flags, and first requires the exact literal preimage:

```text
{"caseId":4512,"compositionIdentity":"composition-81-v1","engineerUserId":31,"installers":[7001,7002],"orderId":81}
```

Independent PHP and `shasum -a 256` calculations confirm that the preimage is
exactly 115 bytes and has SHA-256
`388c7d94b3cf91235dabddf26398ac05f754d3d12a0b41a7a91ac3d5370faba5`.
The expected hash is therefore not obtained from a production service or from
the fixture's returned snapshot.

Before RED qualification, isolated mutations of case ID, composition identity,
engineer ID, installer membership, installer order and order ID must each
change the digest. Separate empty, duplicate and nonpositive installer sets
must each fail the independently stated positive/unique/nonempty validity
predicate. These assertions execute before the missing-factory guard. The
canonical map also preserves JSON integer types and installer ordering, so a
case, identity, engineer, member, order, type or ordering regression cannot
silently retain the approved preimage.

The fixture adapter and accepted-evidence literal now use engineer `31`,
identity `composition-81-v1` and the same derived digest. A byte search across
the active initial test and both active support files finds neither the obsolete
64-character `111...` hash nor engineer `901`. Historical RED and Gate 3
records remain unchanged and explicitly retain the older facts as append-only
history.

### Existing initial behavior coverage remains intact

No v2-approved assertion was removed. The test still calls the single public
application seam through the approved verification factory and cannot create
an application Result in support code. It retains the exact request/case/order/
actor, upload capability, literal 327-byte PDF and independent PDF digest,
document date versus upload time, application-owned root/revision identities,
revision number, immutable evidence JSON, one accepted event/commit and zero
rejection attempts.

The shared process state remains reachable through both the composition reader
and accepted-commit repository. Its separate evidence reader still proves that
order composition, case, opening, tasks, checklist availability and unrelated
decoy families are byte-identical after the command. Six isolated perturbations
continue to establish sensitivity before the RED guard. Stream/stage/lease and
delivery lifecycle assertions also remain unchanged.

The verifier is deterministic and uses no database, network, production file,
secret, shared storage, temporary path or child process. There is therefore no
external cleanup or environmental setup capable of causing the qualifying RED.
The injected fixture is test support for this one initial-command acceptance;
it does not add a second state-changing seam or couple the test to a planned
application implementation.

## Reproduced intended RED

```text
$ php -l tests/Support/AssignmentOrderOriginalInitialProcessState.php
No syntax errors detected in tests/Support/AssignmentOrderOriginalInitialProcessState.php

$ php -l tests/Support/AssignmentOrderOriginalInitialFixture.php
No syntax errors detected in tests/Support/AssignmentOrderOriginalInitialFixture.php

$ php -l tests/InstallationProcess/assignment_order_original_upload_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_001_test.php

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_upload_001_test.php
Fatal error: Uncaught TestFailure: INTENDED_RED: approved AssignmentOrderOriginalVerificationFactory production seam is absent.
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/assignment_order_original_upload_001_test.php

$ independent preimage calculation
115 388c7d94b3cf91235dabddf26398ac05f754d3d12a0b41a7a91ac3d5370faba5

$ rg obsolete-hash-or-engineer active-initial-test-and-support
(no output)

$ git diff --check
(no output)
```

The failure is reached at the explicit missing
`AssignmentOrderOriginalVerificationFactory` guard only after all independent
composition and downstream-family sensitivity checks pass. It is therefore the
intended missing public application behavior, not broken setup or an oracle
failure.

## Verdict and boundary

Gate 3 is **APPROVED** for the exact corrected initial RED bytes at
`1f1ec19b971bf9f51a12e9b11f92627d6844200c`. Task 2.4 may be closed by the
integrator and minimal Gate 4 implementation of this reviewed initial behavior
may proceed without changing the approved expectation. This approval does not
approve production code, the separate database-setup lineage, the remaining
matrix, HTTP/composition application/opening slices, or the full OpenSpec
change. Any change to the reviewed test/support bytes requires a fresh
independent Gate 3 review.

## Exact reviewed SHA-256 inputs

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
e6141aa2df6d9e457a9f3ebd593a9defdbd68b2a233d8091a238afc404b317aa  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
a81b54645f0ab8e320663d9c01bbdfa4f1f0520fc93a3f4ce321afdd95399ace  openspec/changes/replace-pilot-registration-with-original-upload/design.md
b0de98b2b91a0f61c20ca2a22ca489d91f50eaa76fc3f3824958bd72635ce187  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
a80d44ea94c9a9e503b116e7100dfd260d0290d0bed07d6403f2a56b09472fb9  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
57b1e0e18f53baaf632bcb1fd7893a1370dd0a0c181d649772160cecf53a3bef  tests/InstallationProcess/assignment_order_original_upload_001_test.php
e862136c5d21b9a3291f38aee47f344cdb1ed2a512d47ae859bd30fa5f29ed10  tests/Support/AssignmentOrderOriginalInitialFixture.php
ffec151ff4d11b66184ffb88f8860fac9bac7a2d5166faa1a1f6a736d96f76fa  tests/Support/AssignmentOrderOriginalInitialProcessState.php
db24ffaffe46c2b8c47e09879de64b286331fdf07351c93f585294426dcf8ff2  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-initial-v1.md
d8fa4511ab63b95d9032d39fe1895e878fead250e68c97befa81038f2261e609  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-initial-v2.md
75c316b5f994e59c391c5338c8263300acd0b331b88f357a158a119e5e0a62d7  docs/operations/assignment-order-original-upload-initial-red-v2-2026-09-04.md
8f1e7b2a2c0e034be5f2e47f3c5259ee04a65c5a2c5d3ea10cd45a656c6d5578  docs/operations/assignment-order-original-upload-initial-red-composition-v42-2026-09-05.md
4fca33aff538dc1626a297573e7e1593cefa229a8e1ae66cd089a77eebe6a5cb  docs/operations/pilot-assignment-order-original-production-composition-gate1-rereview-v42-2026-09-05.md
```

The review path is metadata because a self-hash is circular.
