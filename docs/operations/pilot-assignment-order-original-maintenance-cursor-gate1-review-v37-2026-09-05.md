# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v37 maintenance cursor — independent Gate 1 review

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/assignment_cursor_gate1`
- Reviewed commit: `758c49dfc037f3c658f5ef355576d14fc2d39640`
- Predecessor gap commit: `e977749`
- Scope: maintenance cursor amendment and its OpenSpec coherence; no tests,
  fixtures, or production implementation reviewed
- Verdict: **CHANGES_REQUESTED**

The reviewer authored none of the reviewed executable specification, OpenSpec
artifacts, tests, fixtures, or production implementation. This append-only
review is the only authored artifact.

## Findings

### 1. Payload and cursor bounds are off by one — blocking

The exact compact payload has a 43-byte fixed part, not 42 bytes:

```text
{"v":1,"at":"2026-09-02T07:00:00Z","id":""}
```

Consequently, for the cited one-to-160-byte identity domain, payload length is
`43+idByteLength`, hence `44..203`, not `42+idByteLength` / `43..202`.
Unpadded RFC 4648 base64url length is `ceil(payloadBytes*8/6)`, giving
`59..271`, not `58..270`. The lower and upper bound assertions as written
would reject valid canonical cursors and admit no independent correct boundary
expectation. Amend all four bounds and the formula before Gate 2.

The published worked example itself is correct: its identity is 19 bytes, its
payload is 62 bytes, and its unpadded cursor is 83 bytes. Strict decoding of
the literal yields byte-exactly:

```text
{"v":1,"at":"2026-09-02T07:00:00Z","id":"orphan-content-0001"}
```

Re-encoding those bytes reproduces the literal exactly.

### 2. Cursor identity grammar is referenced but not closed — blocking

The cursor decoder is required to validate “identity grammar”, and its length
math assumes `idByteLength=1..160`, but the executable cursor/orphan contract
does not state the exact accepted byte grammar. The schema section only states
that persisted `private_content_identity` is ASCII, length `1..160`, and has no
control, slash, or backslash; the fixture section refers circularly to an
“opaque identity grammar”. A future codec could therefore disagree on spaces,
quotes, punctuation, DEL, or non-ASCII input while satisfying the cursor prose.

State one exact normative identity grammar (or explicitly cite the complete
existing schema predicate as the cursor grammar, including ASCII semantics)
and make the JSON escaping/byte-length consequence explicit. This is necessary
to derive valid minimum/maximum cursors and invalid-pair tests independently.

### 3. Canonical pagination/deleted-position example is otherwise coherent

Binary ordering puts `orphan-content-0001` before `orphan-stage-0001` at the
same timestamp. With `batchLimit=1`, page 1 therefore deletes content and
returns the published content-pair cursor. Page 2 uses a new request ID, seeks
strictly after that decoded pair even though the content candidate was deleted,
deletes the stage, and returns null. This correctly prevents existence lookup
of the cursor member from becoming a pagination prerequisite.

The validation precedence is also coherent: invalid cursor is part of scalar
shape validation and is rejected before authorization, terminal-request,
clock, and candidate access; the amendment's weaker “before clock/candidates”
claim is satisfied. Version, exact key order/shape/types, strict alphabet and
no padding, canonical UTC second, strict decode, and byte-equal re-encode give
sufficient malformed/noncanonical sensitivity once the identity grammar and
bounds are corrected.

## Gate decision

Gate 1 for v37 is **CHANGES_REQUESTED**. Correct the normative length formula
and four bounds, and close the exact `opaqueIdentity` grammar. The canonical
literal, its 62/83 lengths, deleted-position semantics, invalid-command
precedence, and two-page example need no product decision or behavioral change.

## Verification

```text
$ git rev-parse HEAD
758c49dfc037f3c658f5ef355576d14fc2d39640

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff 15155c303cddbe01cb7ffddd34c2d50e0cb5a0e1..758c49dfc037f3c658f5ef355576d14fc2d39640 --check
PASS (no output)

$ php cursor-independent-recalculation
identity=19 payload=62 cursor=83 literal=EXACT
identity=1 payload=44 cursor=59
identity=160 payload=203 cursor=271
```

## Exact reviewed hashes

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
d31b3fb2a4745650f066184341f6bb49a2e2c4c94f1b540de654ecd61bb745c2  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
ed0aa9b7f6fe91ceab4eba0f6243fa824e5c5b54ee5d8ac55ee0b7701af9a011  openspec/changes/replace-pilot-registration-with-original-upload/design.md
326b5f6e3f4306479f96de23cf35e3ff8896b68e6681412471db56929dab3de1  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
f6f5936219cb6dae5a276b6ff7b95050e6c89aec0655d9cab33e43305a6a3329  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
d431ef7b35d7e6605e81b2928ce9d2e4176093aad20c8ace3114bc612c5d4b74  docs/operations/assignment-order-original-maintenance-cursor-gate1-gap-2026-09-05.md
```

This review record omits its own circular hash.
