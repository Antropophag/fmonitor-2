# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v23 worker result encoding — independent Gate 1 rereview

- Date: `2026-09-05`
- Reviewer: separately tasked fresh agent `/root/assignment_worker_result_rereview`
- Reviewed commit: `68804196a8160937ca9b06594cd975ec185b95a9`
- Amendment base: `6ba5ebc7184fa61f31b1c9f10fc6ff93b0b1b801`
- Prior review: `docs/operations/pilot-assignment-order-original-worker-result-encoding-gate1-review-v22-2026-09-05.md`
- Scope: portable worker Result write/parent framing amendment and coherence of the complete current executable specification/OpenSpec package; no tests or production implementation reviewed
- Verdict: **CHANGES_REQUESTED**

The reviewer authored none of the reviewed specification, OpenSpec artifacts,
tests or production implementation. This append-only review is the only
authored artifact.

## Blocking finding

### G1-V23-01 — short-write prefix contradicts the unconditional exit-70 empty-result contract

The v23 amendment resolves the primitive portability gap identified in v22 in
isolation: serialization and size are checked before output, the worker makes
one complete-line `fwrite`, does not retry `false`, zero or a short byte count,
and the parent accepts only one canonical LF-terminated line followed by EOF.
It correctly acknowledges that a short write can leave an untrusted prefix and
requires the parent to discard that prefix without decoding or publishing it.

However, the same executable specification still says unconditionally at
lines 1488–1491 that **every** worker-controlled exit `70` leaves the result FD
with zero bytes. The short-write branch at lines 1560–1562 is also a controlled
exit `70`, while it may have already emitted a non-empty prefix. Both outcomes
cannot hold for the same observable execution. The OpenSpec delta repeats the
contradiction: `Worker failure channels exact` requires an empty result FD for
every controlled exit `70`, whereas `Worker result JSON exact` permits the
short prefix which the parent discards. The design likewise says both “an empty
result channel” for every controlled exit and “parent-side rejection of any
bounded short prefix.”

Gate 2 therefore cannot independently determine whether an injected short
`fwrite` must demonstrate zero bytes or a discarded prefix. Narrow the earlier
failure-channel rule consistently across executable spec, delta spec and design
so zero result bytes apply to failures before the primitive write (and to a
zero/false write that emitted none), while a positive short write may expose
only the bounded untrusted prefix handled by the new parent rule. Preserve the
fixed stderr, no second result write, close/exit behavior and committed-request
replay contract.

Task 1.18 and the worker-result portion of Gate 2 remain open pending that
coherence correction and another fresh independent Gate 1 rereview.

## Rechecked v22 content

No additional blocker was found in the v22 encoding, types or literals:

- the eleven keys remain mandatory and in the declared DTO order;
- backed status/reason strings, explicit nulls, JSON booleans, unquoted integer
  revision/size values, compact fixed JSON flags and one final LF remain exact;
- accepted and replayed lines retain Example A's request, root/revision,
  revision `1`, document/upload dates, 327-byte size and
  `4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784`;
- the stale line remains `conflict|stale_revision|false` with its declared new
  request ID and all seven evidence values null;
- the complete line including LF is bounded by `16384` before the primitive;
- serialization/oversize produces zero result bytes, a positive short write is
  not retried, parent acceptance requires exact LF plus EOF, no partial result
  is decoded/published, and a durable commit remains replayable.

Those rules are implementable once the older unconditional empty-channel text
is narrowed. The amendment changes no product workflow, role, capability,
public application seam, composition/opening state, runtime DDL or blocked
legacy behavior.

## Verification

```text
$ git rev-parse HEAD
68804196a8160937ca9b06594cd975ec185b95a9

$ git rev-parse 68804196a8160937ca9b06594cd975ec185b95a9^
6ba5ebc7184fa61f31b1c9f10fc6ff93b0b1b801

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff 6ba5ebc..68804196a8160937ca9b06594cd975ec185b95a9 --check
PASS (no output)

$ independent coherence comparison
earlier executable rule = every controlled exit 70 => zero result bytes
new short-write rule = positive short fwrite => possible untrusted prefix, then controlled exit 70
delta/design contain the same incompatible pair
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
d56a19ac66142bc46ae9049ba5eaccfd9226923edef3b33fe77c96f12fe1e9db  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
041bfc92cea5397931c7ed387ea0d88ded3746aa68313fae709efc798f9cf3e2  openspec/changes/replace-pilot-registration-with-original-upload/design.md
01557d974ff357332368d2c4378dc0c3ce18f2cc7365bc753912c4ff300ab219  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
6f6eedc238d8a44ee3871b75a3499367753e92105d0c97ef7b3706b97c2323fc  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
386dc6d043550efcf295b9f2cf20bcec9df07f0653f7bda24433f2eb73e720bf  docs/operations/pilot-assignment-order-original-worker-result-encoding-gate1-review-v22-2026-09-05.md
```

This review record omits its own circular hash.
