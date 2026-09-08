# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v20 worker command encoding — independent Gate 1 review

- Date: `2026-09-05`
- Reviewer: separately tasked fresh agent `/root/assignment_worker_command_gate1`
- Reviewed commit: `5c15553cce6a38a844175b84e3675a49ccb75de1`
- Triggering gap: `5cf7aa7` / `docs/operations/assignment-order-original-worker-command-base64-gate1-gap-2026-09-05.md`
- Approved worker-ID base: `b95f0449da25e3ebb139dce61732c4d1359ff990`
- Scope: technical worker command/framing/base64 amendment and coherence of the complete current executable specification/OpenSpec package; no tests or production implementation reviewed
- Verdict: **CHANGES_REQUESTED**

The reviewer authored none of the reviewed specification, OpenSpec artifacts,
tests or production implementation. This append-only review is the only
authored artifact.

## Blocking findings

### G1-V20-01 — the required literal canonical command fixture is still absent

The triggering gap explicitly requires one literal canonical command JSON
example so a Gate 2 author can derive the valid wire bytes independently. V20
enumerates the top-level and nested key order, but publishes no complete JSON
line (including literal values, explicit nulls and the final LF). Consequently
the reviewer still cannot point to one normative valid `INITIAL` or
`CORRECTION` byte sequence. This is especially material because the transport
requires object-member order, explicit nulls and exactly one final LF rather
than accepting semantically equivalent JSON.

Add at least one complete literal canonical command line whose values satisfy
the existing DTO grammar, and state its exact byte length or digest. It must
show all eleven ordered top-level keys, all three ordered `upload` keys,
explicit null lineage/reason fields for `INITIAL`, one canonical padded
standard-alphabet `bytesBase64` value and the single terminal LF.

### G1-V20-02 — malformed-command resource precedence does not close the gap

The gap asks whether malformed/noncanonical base64 is rejected before password
content and database access. V20 instead says only that rejection happens
“after config validation but before application/storage/safe-log/barrier
calls.” Elsewhere the same specification says a valid child opens the shared
MariaDB connection and reconstructs real adapters, while the earlier invalid-
configuration rules are the only ones explicitly guaranteed to fail before
password content and DB access. Both implementations therefore remain
conforming to the text:

1. read/validate the command before password-file content and MariaDB access;
2. validate config, read the password and connect MariaDB, then reject the
   malformed command before invoking the application.

Those alternatives have observably different resource-access evidence and the
Gate 2 pre-secret controls cannot choose between them. State one exact order
covering scalar/path/config validation, command framing/key/type/base64
validation, password-file content, MariaDB connection, private-storage access,
safe-log access, application invocation and barrier output. If command
validation is intended to be pre-secret/pre-DB as requested by the gap, say so
normatively in both executable spec and OpenSpec scenario.

### G1-V20-03 — the allocation rule is not executable as written

“The complete line bound is checked during read before JSON/base64 allocation”
does not identify what allocation is forbidden. Reading a line into any bounded
buffer itself allocates bytes, while validating JSON keys/types requires JSON
parsing. A future worker could buffer 29,000,001 bytes, allocate a decoded
buffer before detecting trailing data, or stream into bounded chunks and each
could claim compliance with a different reading of this sentence.

Define the externally testable bounded-read rule: the maximum number of command
bytes the worker may consume/buffer before rejecting an overlong or second-line
input, whether it must read through EOF to prove “no bytes after LF,” and that
no JSON parse or decoded-byte-buffer/byte-stream construction occurs until the
complete framing/29,000,000-byte condition is established. The rule should use
observable factory/resource calls or a named bounded reader seam rather than an
undefined “JSON allocation.”

## Non-blocking assessment

The remaining amendment is coherent and independently testable:

- the declared canonical key names and nesting resolve `upload.bytesBase64`
  versus the application DTO's `upload.stream` and exclude extra/missing keys;
- strict standard RFC 4648 alphabet, canonical padding, no whitespace/URL
  alphabet, strict decode and byte-for-byte re-encoding reject noncanonical
  forms; empty decoded bytes remain transport-valid;
- `20,971,521` decoded bytes encode to `27,962,028` base64 bytes and therefore
  can fit below the 29,000,000-byte transport ceiling with the bounded command
  metadata, reaching application `FILE_TOO_LARGE` rather than transport exit
  `70`;
- malformed framing/UTF-8/JSON/key/type/base64 has the inherited exact exit-70
  channels: fixed one-line stderr, empty result FD and empty barrier output;
- the decoded byte array is the sole input to the byte-stream factory, so the
  application byte counter observes decoded PDF bytes, not base64 text;
- second line/data after the final LF, EOF before LF and line overflow are
  explicitly invalid; the OpenSpec summary and design accurately reflect those
  outcomes;
- no product role, capability, workflow, HTTP contract, composition/opening
  behavior, state-changing seam, runtime DDL or blocked legacy behavior changes.

These strengths do not close the three ambiguities above. Gate 2 command-matrix
work must not rely on an invented literal or resource/allocation order. A
corrected v21 requires a fresh independent Gate 1 rereview.

## Verification

```text
$ git rev-parse HEAD
5c15553cce6a38a844175b84e3675a49ccb75de1

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff 5c15553^ 5c15553 --check
PASS (no output)

$ php base64-length calculation for 20,971,521 decoded bytes
27962028
```

The reviewed commit changes only the executable specification and OpenSpec
artifacts. No test or production implementation is part of the reviewed diff.

## Exact reviewed hashes

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
82c4b1aea6f01124debba0dee5b413527514ba582dcd3d3da69d9f4480a68b1e  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
4d6ac0be3df022451827e4d81399fd48f2c43b9dd357ad09d23693f5ee55bce7  openspec/changes/replace-pilot-registration-with-original-upload/design.md
587976abf1422e515f48f4f7ae25b643d03e4bf60cef4cf571f453173350dfbe  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
9b9940df59f049946940e543cda246757c4fd68ab4566e3a7bd40357ad542012  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
86046faa28f44665d50ce2f0549d9c6b64254819c98b89ce34d3e3ff862b02f7  docs/operations/assignment-order-original-worker-command-base64-gate1-gap-2026-09-05.md
607359e3226713a1408be7390dcb7513bc1f921694967becbd852b11be8bd4b8  docs/operations/pilot-assignment-order-original-worker-id-sequence-gate1-rereview-v19-2026-09-05.md
8c675a1385c62654b902664842de1c729532fec678e80a53912c7a691b037f1a  docs/operations/pilot-assignment-order-original-worker-failure-output-gate1-review-v17-2026-09-05.md
4b579dabc65ce5f701d684f2f77a1f72d823eaf8fdd03be94d748a83832efc7c  docs/operations/pilot-assignment-order-original-worker-dsn-gate1-rereview-v16-2026-09-05.md
```

This review record omits its own circular hash.
