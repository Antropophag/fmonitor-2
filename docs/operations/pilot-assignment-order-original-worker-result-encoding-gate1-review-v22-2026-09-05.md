# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v22 worker result encoding — independent Gate 1 review

- Date: `2026-09-05`
- Reviewer: separately tasked fresh agent `/root/assignment_worker_result_gate1`
- Reviewed commit: `2a9e14c781bc88bfc751aecf8ad9b1887ccfaed0`
- Triggering gap: `f6c1f28d7082bbb2474849ed4d1c94ea1b40a7d2` / `docs/operations/assignment-order-original-worker-result-json-gate1-gap-2026-09-05.md`
- Approved command-encoding base: `c697975dccdf294d7a47eb055cff44e16fe98f1a`
- Scope: technical worker Result JSON amendment and coherence of the complete current executable specification/OpenSpec package; no tests or production implementation reviewed
- Verdict: **CHANGES_REQUESTED**

The reviewer authored none of the reviewed specification, OpenSpec artifacts,
tests or production implementation. This append-only review is the only
authored artifact.

## Blocking finding

### G1-V22-01 — result write failure cannot yet guarantee an empty/no-partial channel

V22 correctly requires complete serialization and the `16384`-byte bound to be
validated before the first result byte is written. That makes an empty result
channel independently executable for serialization and oversize failures.
However, the same sentence also requires a **write failure** to publish no
partial result, while the worker still writes to an ordinary dedicated pipe FD
and no result-writer protocol or atomic primitive is specified.

A conventional `fwrite`-style primitive may return a short count or fail after
some bytes have become observable. Buffering the complete JSON line before that
call does not retract those bytes. The `16384` maximum is also not tied to a
normative portable atomic-write bound, and the specification does not define an
atomic framed writer seam whose failed operation is guaranteed to expose zero
bytes. Therefore two materially different Gate 2 tests are presently plausible:

1. inject failure before the primitive write and require an empty channel;
2. inject a short primitive write and observe a prefix before controlled exit
   `70`.

The second behavior violates the literal contract, but the first test would not
prove that the worker prevents it. A test author would have to invent the
missing transport mechanism.

Amend the executable spec and OpenSpec with one implementable, observable rule.
For example, either define an explicit atomic result-writer port/primitive with
an all-or-zero failure contract and bind the worker to it, including its maximum
frame size, or narrow “no partial result” to failures detected before the first
primitive result write and separately define the observable outcome of a short
or failed primitive write. Preserve the already committed request as replayable
and the inherited fixed exit-70 stderr/barrier behavior.

## Non-blocking assessment

Everything else requested by the triggering gap is coherent and independently
testable:

- all eleven keys are mandatory in exact DTO order;
- status and reason use the declared lower-case backed values, null evidence is
  explicit, booleans are JSON booleans and revision/size values are unquoted
  integers;
- `JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR`, compact
  encoding and exactly one final LF define one canonical representation rather
  than recursively sorted evidence JSON;
- the accepted and replayed literals exactly match Example A, including the
  327-byte PDF digest and fixed clock; the stale literal has the DTO-mandated
  `conflict/stale_revision/false` tuple and null evidence;
- the encoded object plus LF must be at most `16384` bytes before output begins;
- serialization or oversize after a durable commit is a transport failure, not
  a false domain failure, and the same request remains replayable;
- the delta spec and design carry the same key/order/type/flags/LF/bound intent,
  and strict OpenSpec validation succeeds;
- no product behavior, workflow, role, capability, public application seam,
  composition/opening state, runtime DDL or blocked legacy behavior changes.

These strengths do not resolve the primitive-write ambiguity. Task 1.18 and the
worker-result portion of Gate 2 must remain open pending a corrected amendment
and fresh independent Gate 1 rereview.

## Verification

```text
$ git rev-parse HEAD
2a9e14c781bc88bfc751aecf8ad9b1887ccfaed0

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff 2a9e14c^ 2a9e14c --check
PASS (no output)

$ independent literal comparison
accepted/replayed fields = exact Example A values
stale tuple = conflict|stale_revision|false with all evidence fields null
result keys = status,reasonCode,retryable,requestId,rootOriginalId,currentRevisionId,revisionNumber,documentDate,sha256,byteSize,uploadedAt
```

The reviewed commit changes only executable-specification and OpenSpec
artifacts. No test or production implementation is part of the reviewed diff.

## Exact reviewed hashes

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
8b60cb2ad662007aa480981228613a2accb93d7f8ba802631848cb914c0988de  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
6419a21f94eec5ac97f20bc91fa35940650114ce01f0ba33a05d8e218267d874  openspec/changes/replace-pilot-registration-with-original-upload/design.md
01557d974ff357332368d2c4378dc0c3ce18f2cc7365bc753912c4ff300ab219  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
dabec26ab4d53a0279b0cf0cdb75d85053626e6a7ae396f5c53af57721cecfd0  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
b81d96e670cdd41d0288dfdbeb990edceb8e648acc50e85d99bb50ec44e6f68b  docs/operations/assignment-order-original-worker-result-json-gate1-gap-2026-09-05.md
0ef56289f5c949cf14e084bd808af556cd02d07635fb6ad03c3c7f5531e6680c  docs/operations/pilot-assignment-order-original-worker-command-encoding-gate1-rereview-v21-2026-09-05.md
```

This review record omits its own circular hash.
