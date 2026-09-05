# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v48 cleanup evidence — independent Gate 1 rereview

Date: `2026-09-05`

Reviewer: separately tasked agent `/root/assignment_cleanup_log_rereview4`

Reviewed commit: `b83b8e1ef80037aff30b28dd8188878116929a71`

Scope: the v48 executable-spec/OpenSpec amendment resolving G1-47-1, together
with the unchanged cleanup ordering and fault-selector contract covered by
OpenSpec tasks `1.29` and `1.30`; no test or production implementation reviewed
or changed

Verdict: **APPROVED**

## Exact reviewed artifacts

```text
2078ffbf83bd738221a9c03215f34a33e717c363fdfc9b31b13fd7e1aca31633  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
3f026d03d4155682f2c5b3ca9221ff973b31474b30317664ecf21c63cd594210  openspec/changes/replace-pilot-registration-with-original-upload/design.md
fbc39cf38755ac652ab7b51ddf2e5b7f9deffe149198a9bb0fe531eeeae281e9  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
355d74228038f94147b8f5bf7b3bc73ed676892e90da9d7664ef68b1a56cefc4  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
8d2097712714a329071f3ba2695991696b7191890b588a0b9f0ba8bb21a958ff  docs/operations/pilot-assignment-order-original-cleanup-precedence-gate1-rereview-v47-2026-09-05.md
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

## Review result

G1-47-1 is resolved. The canonical isolated abort-failure run now fixes exact,
recursively binary-key-sorted `aoou-requests-v1`, `aoou-audits-v1`,
`aoou-blobs-v1`, and `aoou-logs-v1` JSON. Their values are coherent with the
closed evidence shapes: request `...0301`, actor `18`, case `4512`, order `81`,
initial mode, rejected/`invalid_pdf`, `retryable=false`, all original-evidence
fields null, audit identity `1`, exact attempt/stage time
`2026-09-02T09:15:30Z`, one 327-byte abandoned `stage-0001`, no finalized blob,
and one sequence-1 abort-failure log with correlation `d87e745407ea` and sole
safe field `phase=stage_abort`.

The ordered transcript is independently testable and consistent with the v47
protocol: exact upload authorization and terminal miss precede composition and
clock; lifecycle request-miss precedes stage/read/inspection; an
`INVALID_PDF` inspection selects the rejection; abort is attempted and throws;
the one safe-log attempt precedes successful stage close and stream close; only
then is the rejected request plus audit committed. It explicitly excludes
`abort_done`, finalize, accepted commit, delivery, and lease activity. Because
the transcript is declared exact, it also excludes unlisted fingerprint,
lineage, ID-generation, and further stream/storage/repository calls.

The throwing-safe-log variant calls the observer once, has exact empty logs,
and preserves the same Result, request, audit, blob, and call ordering. The
same-request retry is exactly authorization followed by terminal `FOUND`; it
does not read composition, clock, stream, inspector, storage, cleanup, or log,
and leaves the applicable four inventories byte-identical. This closes both the
missing independent expected values and the missing replay/absence oracle.

The pre-existing v47 conclusions remain coherent: non-accepted cleanup is
abort then stage close then stream close before terminal audit; accepted
candidate stage/stream closes occur before commit and their first failure maps
to retryable storage/stream failure; later cleanup failure cannot replace it;
finalized private content is not published and its lease gets one
`rolled_back` release attempt. Faults and the throwing log observer remain
verification-only, with no production selector or composite worker fault.

The delta and design now mirror the request301 exact-evidence obligation. They
do not broaden product behavior or contradict the executable contract. Tasks
`1.29` and `1.30` remain unchecked in the reviewed artifact, as required for an
append-only reviewer record, but their Gate 1 acceptance criteria are approved
at the reviewed commit. Gate 2 cleanup-fault RED may now be authored against
these exact expectations.

## Verification evidence

```text
$ git rev-parse HEAD
b83b8e1ef80037aff30b28dd8188878116929a71

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff --check 462df92b54a67870643e2b5a0a6c4ce4d08ad0e7..b83b8e1ef80037aff30b28dd8188878116929a71
PASS (no output)

$ printf %s 00000000-0000-4000-8000-000000000301 | shasum -a 256
d87e745407ead6dc30a245ccc3ab8293d5e77dbd823dc47076a32569416415fc  -
```

No findings. The reviewer changed no executable specification, OpenSpec
artifact, task checkbox, test, or production file. Only this append-only review
record was added.
