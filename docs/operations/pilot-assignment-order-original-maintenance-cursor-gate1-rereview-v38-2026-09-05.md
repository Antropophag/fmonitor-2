# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v38 maintenance cursor — independent Gate 1 rereview

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/assignment_cursor_rereview`
- Reviewed commit: `0ba077987c6253d7f831d5275b1d4bf8056b33dd`
- Changes-requested predecessor review: `3492df4f9bf62e97a13077a6c324f1587cedd2ca`
- Scope: maintenance cursor executable-spec correction and retained v37 cursor
  semantics; no tests, fixtures, or production implementation reviewed
- Verdict: **APPROVED**

The reviewer authored none of the reviewed executable specification, OpenSpec
artifacts, tests, fixtures, or production implementation. This append-only
rereview is the only authored artifact.

## Findings

### 1. Exact opaque-identity grammar is closed

The amended contract accepts exactly `1..160` ASCII bytes in `0x20..0x7E`,
excluding `/` and backslash. Thus control bytes, DEL, non-ASCII, slash and
backslash are rejected; space and the remaining printable punctuation are
unambiguous. Double quote is deliberately accepted and its exact JSON form
`\"` is stated. Together with strict UTF-8/exact JSON keys and types, canonical
UTC second, version 1, strict base64 decoding and byte-equal re-encoding, this
is sufficient for an implementation-independent decoder acceptance domain.

### 2. Payload and base64url extrema are correct

The compact empty-identity frame is 43 bytes. For identity byte length `n` and
double-quote count `q`, JSON encoding contributes one additional escape byte
per quote, so payload size is exactly `43+n+q`.

- Minimum: one non-quote byte gives `43+1+0 = 44` payload bytes and
  `ceil(44*8/6) = 59` unpadded base64url bytes.
- A one-byte quote gives 45 payload bytes and 60 cursor bytes, confirming that
  quote escaping is active rather than being counted as a raw byte.
- A 160-byte identity without quotes gives 203 payload bytes and 271 cursor
  bytes, an accepted interior point rather than the maximum.
- Maximum: 160 double quotes give `43+160+160 = 363` payload bytes and
  `ceil(363*8/6) = 484` unpadded base64url bytes.

Therefore the normative bounds `44..363` and `59..484` are exact. Generated
cursors at both extrema use only `[A-Za-z0-9_-]` and reproduce byte-for-byte
after strict decode and canonical re-encode.

### 3. Canonical literal and retained pagination semantics are coherent

The canonical `orphan-content-0001` identity remains 19 bytes; its compact
payload is exactly 62 bytes and its unpadded base64url cursor exactly 83 bytes.
Independent encoding reproduces the published literal byte-for-byte.

The other v37 findings remain satisfied: binary pair ordering makes content
the first candidate at the shared timestamp; page 1 may delete it and return
its pair cursor; page 2 seeks strictly after the pair without requiring that
the deleted member still exist. Unsupported version/shape, noncanonical
alphabet or padding, failed strict decode, invalid timestamp/identity pair, or
non-byte-equal re-encoding maps to `REJECTED/INVALID_COMMAND` during scalar
shape validation, before clock and candidate access. OpenSpec retains the same
83-byte literal, deleted-position semantics and invalid-cursor behavior and is
coherent with this more exact executable contract.

No ambiguity affecting observable cursor behavior remains.

## Gate decision

Gate 1 for the v38 maintenance cursor amendment is **APPROVED** at exact commit
`0ba077987c6253d7f831d5275b1d4bf8056b33dd`. The two blocking v37 findings are
closed without changing the already coherent ordering, pagination, validation
precedence, or canonical worked example. Gate 2 may proceed from this exact
approved executable specification.

## Verification

```text
$ git rev-parse HEAD
0ba077987c6253d7f831d5275b1d4bf8056b33dd

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff 3492df4f9bf62e97a13077a6c324f1587cedd2ca..0ba077987c6253d7f831d5275b1d4bf8056b33dd --check
PASS (no output)

$ php cursor-independent-recalculation
min id=1 quotes=0 payload=44 cursor=59 alphabet=YES canonical=YES
min_quote id=1 quotes=1 payload=45 cursor=60 alphabet=YES canonical=YES
max_no_quote id=160 quotes=0 payload=203 cursor=271 alphabet=YES canonical=YES
max_quotes id=160 quotes=160 payload=363 cursor=484 alphabet=YES canonical=YES
canonical id=19 quotes=0 payload=62 cursor=83 alphabet=YES canonical=YES
canonical literal=EXACT
```

## Exact reviewed hashes

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
914e9f06fc62b4fc1137f491315cb0d48b71fdc3c3c70260afa5445bd34054e0  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
ed0aa9b7f6fe91ceab4eba0f6243fa824e5c5b54ee5d8ac55ee0b7701af9a011  openspec/changes/replace-pilot-registration-with-original-upload/design.md
326b5f6e3f4306479f96de23cf35e3ff8896b68e6681412471db56929dab3de1  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
f6f5936219cb6dae5a276b6ff7b95050e6c89aec0655d9cab33e43305a6a3329  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
d617c9166e975c31b3550109f09107bd27eb16f7f86b5073dbda1bcd3c34ea6a  docs/operations/pilot-assignment-order-original-maintenance-cursor-gate1-review-v37-2026-09-05.md
```

This review record omits its own circular hash.
