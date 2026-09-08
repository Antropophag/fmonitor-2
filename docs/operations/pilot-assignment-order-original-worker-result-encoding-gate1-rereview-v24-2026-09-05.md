# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v24 worker result encoding — independent Gate 1 rereview

- Date: `2026-09-05`
- Reviewer: separately tasked fresh agent `/root/assignment_worker_result_rereview2`
- Reviewed commit: `8c0f253453d3e1c58341d2e6e9589c2f29ace071`
- Amendment base: `592da000660cb912c6c99cad2994e50c8055f9f8`
- Prior review: `docs/operations/pilot-assignment-order-original-worker-result-encoding-gate1-rereview-v23-2026-09-05.md`
- Scope: v24 worker failure-channel coherence correction and the complete current worker-result encoding contract; no tests or production implementation reviewed
- Verdict: **APPROVED**

The reviewer authored none of the reviewed specification, OpenSpec artifacts,
tests or production implementation. This append-only review is the only
authored artifact.

## Finding

V24 resolves blocking finding `G1-V23-01`. The executable specification now
requires zero result bytes for every failure before the sole result-write
primitive and permits only that primitive's positive short-write failure to
leave a bounded untrusted prefix. The OpenSpec delta and design carry the same
distinction. This is coherent with the existing result rule: serialization and
the `16384`-byte complete-line bound are checked before the primitive; the
worker makes exactly one complete-line `fwrite`; `false`, zero or a short count
is not retried; and only a short count may expose the prefix that the parent
buffers within the same bound and discards unless it receives one exact
canonical LF-terminated line followed by EOF.

The failure contract remains deterministic. Every controlled exit `70` emits
only `ASSIGNMENT_ORDER_ORIGINAL_WORKER_FAILED\n` on stderr. Config failures do
not consume command or barrier channels, and a barrier failure can retain only
its already emitted exact READY line. No second result write, partial-result
decode or publication is permitted. If the application committed before a
result transport failure, the same request remains replayable through the
public application seam.

The v22/v23 encoding content was rechecked independently. All eleven keys are
mandatory in DTO order; enum strings, explicit nulls, JSON booleans and
unquoted integers have the specified types; fixed JSON flags, compact encoding
and one LF are exact. The accepted and replayed literals retain Example A's
request/root/revision identities, revision `1`, dates, 327-byte size and digest
`4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784`.
The stale literal remains `conflict|stale_revision|false`, uses the declared
second request ID and has all seven evidence values explicitly null. The three
literal lines are canonical JSON and occupy respectively `352`, `352` and
`258` bytes including LF, safely below the declared bound.

No ambiguity affecting Gate 2 remains in this amendment. It changes no product
workflow, roles, capabilities, public application seam, composition/opening
state, runtime DDL or blocked legacy behavior. The worker-result portion of
Gate 2 may proceed from this approved v24 contract.

## Verification

```text
$ git rev-parse HEAD
8c0f253453d3e1c58341d2e6e9589c2f29ace071

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff 592da00..8c0f253 --check
PASS (no output)

$ independent literal decode/canonical comparison
keys = status,reasonCode,retryable,requestId,rootOriginalId,currentRevisionId,revisionNumber,documentDate,sha256,byteSize,uploadedAt
accepted LF bytes = 352
replayed LF bytes = 352
stale LF bytes = 258
accepted/replayed evidence = exact Example A values
stale tuple = conflict|stale_revision|false with seven null evidence values
```

The reviewed commit changes only the executable specification and OpenSpec
design/delta specification. No test, production implementation or task-state
change is part of the reviewed diff.

## Exact reviewed hashes

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
b5138ce1520ef6fc261d94fd6e5b5a58e21586919f57c47715431e3b9d235bff  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
92154897e06427f58c46a1533e117561eeae4b7c5438a32dd51d587d70484242  openspec/changes/replace-pilot-registration-with-original-upload/design.md
01557d974ff357332368d2c4378dc0c3ce18f2cc7365bc753912c4ff300ab219  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
b5fbb4a890bf9456882ac6094f1becf17c1ee51ebfa430e0a236e334b97b7725  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
386dc6d043550efcf295b9f2cf20bcec9df07f0653f7bda24433f2eb73e720bf  docs/operations/pilot-assignment-order-original-worker-result-encoding-gate1-review-v22-2026-09-05.md
ee89bc2813009551a14f0c91178b771faf92b0b4ee932fa8d5701dbc5d887485  docs/operations/pilot-assignment-order-original-worker-result-encoding-gate1-rereview-v23-2026-09-05.md
```

This review record omits its own circular hash.
