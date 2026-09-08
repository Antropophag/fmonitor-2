# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v17 worker failure output — independent Gate 1 review

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/assignment_worker_output_gate1`
- Reviewed commit: `0dafaa62f491a67fdf2efd3831a58e5786eaa264`
- Triggering gap: `1a3f7693d13c81924947764d478ac5ccaf4dd48e`
- Approved DSN base: `4fd4fa5e0d9b471b1588a86bbc8e1f5e1ba01ac4`
- Scope: technical worker failure-output amendment and coherence of the complete
  current executable specification/OpenSpec package; no tests or production
  implementation reviewed
- Verdict: **APPROVED**

The reviewer authored none of the reviewed specification, OpenSpec artifacts,
tests or production implementation. This append-only review is the only
authored artifact.

## Gap disposition and Gate 1 findings

The gap at `1a3f769` is closed. Every worker-controlled exit `70`, including
invalid config/DSN/path/FD validation and malformed/EOF/timeout barrier input,
now has one independently derivable process outcome:

- stderr is exactly the ASCII bytes
  `ASSIGNMENT_ORDER_ORIGINAL_WORKER_FAILED\n`, written once, with exactly the
  displayed single terminal LF and no other stderr bytes;
- the dedicated result FD receives zero bytes;
- invalid config is resolved before command-FD read or barrier output, so the
  command remains unread and the barrier channel is empty;
- barrier failure is reachable only after exact
  `READY <requestId>\n` was already written and adds no bytes, so the complete
  barrier output is that READY line only.

The universal controlled-exit rule and the explicit channel cases cover all
exit-70 paths without allowing a parser, adapter or fixture to select a
different diagnostic. The existing command/result/barrier contract remains a
five-dedicated-FD protocol, and its successful result-line rule is not weakened
by the new empty-result failure rule.

The leakage exclusion is closed over every failure channel: SQL, path, DSN,
database user/password, request or other IDs, filename, exception and arbitrary
diagnostic text cannot appear. Invalid configuration still precedes password
file content, database access, private storage and safe-log access, so neither
secret-bearing nor external-resource diagnostics can be produced on that path.

The amendment adds no role, capability, workflow, HTTP route/result, original
lineage, composition/opening behavior, schema ownership, runtime DDL or blocked
legacy E2E change. It adds no environment/request/CLI/global selector, second
state-changing seam or alternate production composition. The design and
OpenSpec delta accurately summarize the normative executable contract.

This approval satisfies the fresh independent Gate 1 review requested by task
1.15. It authorizes construction of the corresponding Gate 2 worker/failure
matrix, but does not approve any future test or production implementation.

## Verification

```text
$ git rev-parse HEAD
0dafaa62f491a67fdf2efd3831a58e5786eaa264

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff 0dafaa6^ 0dafaa6 --check
PASS (no output)

$ git diff --name-only 0dafaa6^ 0dafaa6
openspec/changes/replace-pilot-registration-with-original-upload/design.md
openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
```

The reviewed commit changes specification/OpenSpec artifacts only; repository
search found no new runtime worker implementation or selector.

## Exact reviewed hashes

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
0b875d1a9aab30d8daaa1584f5961747a7f2fecf4e00ce77f6bdba2c5c0dd317  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
020263ca56236fbd510f9cedd940d15e3818402b7c12875e98dd1385a83c9c3f  openspec/changes/replace-pilot-registration-with-original-upload/design.md
2bdaa82121d6d6fe4615276dff872304cc65569ea986d241e4cd5fdebf1b21d5  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
94cdd5be9d9ee298400b5c3944d1b3e5a33582f002a21d9358864a3c396d575e  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
6ca100afde14c46384e2cf2ede6da9c389b028cd3270b473a4bc677f7f6767b9  docs/operations/assignment-order-original-worker-stderr-gate1-gap-2026-09-05.md
5b96741c6d8f493e311f7730f4c2def15bdfa3fb1316ccd4743774baf9dd5ed2  docs/operations/pilot-assignment-order-original-worker-dsn-gate1-review-v15-2026-09-05.md
4b579dabc65ce5f701d684f2f77a1f72d823eaf8fdd03be94d748a83832efc7c  docs/operations/pilot-assignment-order-original-worker-dsn-gate1-rereview-v16-2026-09-05.md
```

This review record omits its own circular hash.
