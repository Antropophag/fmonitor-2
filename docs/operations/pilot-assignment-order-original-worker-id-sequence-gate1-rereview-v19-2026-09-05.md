# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v19 worker ID sequence — independent Gate 1 rereview

- Date: `2026-09-05`
- Reviewer: separately tasked fresh agent `/root/assignment_worker_ids_rereview`
- Reviewed commit: `f9af106aeed445bb82d4421656da48121a9177aa`
- Predecessor review: `d2e5c79` recording v18 `CHANGES_REQUESTED`
- Approved worker-output base: `563d171800f497587db49164be41f2ee8d35d15f`
- Scope: corrected technical worker ID-sequence amendment and coherence of the
  complete current executable specification/OpenSpec package; no tests or
  production implementation reviewed
- Verdict: **APPROVED**

The reviewer authored none of the reviewed specification, OpenSpec artifacts,
tests or production implementation. This append-only review is the only
authored artifact.

## Blocking finding disposition

### G1-V18-01 — resolved

The v19 normative input bound now follows exactly from the admitted token
languages. Both token forms contain exactly 13 ASCII bytes:

```text
original- + four decimal digits = 9 + 4 = 13
revision- + four decimal digits = 9 + 4 = 13
```

For `n` tokens, the `n-1` literal one-byte commas make the complete byte length
`13n + (n-1) = 14n-1`. The declared member interval `n = 1..1024` therefore has
exact endpoints `13` and `14335`. The normative contract states both the exact
formula and those derived endpoints, so Gate 2 can exercise the lower and upper
valid boundaries and adjacent invalid forms without inventing a value.

## Gate 1 findings

The complete v19 executable specification and unchanged OpenSpec package remain
coherent with all previously reviewed semantics:

- each input is a non-empty ASCII comma-delimited sequence of `1..1024` exact
  13-byte tokens, with no whitespace, empty/trailing member or duplicate within
  that sequence;
- root tokens are anchored `original-[0-9]{4}` and revision tokens are anchored
  `revision-[0-9]{4}`, which keeps the two token languages disjoint;
- both complete sequences are validated before password content, command input
  or external access; invalid input inherits exit `70`, exact fixed stderr, an
  empty result FD and an empty barrier channel;
- each source consumes independently, strictly left-to-right and only when its
  kind is requested; an unused suffix is allowed and consumption never rewinds;
- exhaustion is the typed runtime ID dependency failure mapped by the command to
  `FAILED/PERSISTENCE_FAILURE`, rather than a worker configuration exit;
- the canonical initial, identical-correction and different-correction values
  remain independently specified, including the deliberate shared
  `revision-0002` identical-race collision and fingerprint resolution;
- the amendment adds no product behavior, role, capability, workflow, HTTP
  route, opening/composition change, runtime selector, mutation seam or runtime
  DDL.

No ambiguity affecting observable behavior remains in this amendment. This
approval satisfies the fresh independent Gate 1 rereview required after v18 and
authorizes construction/review of the corresponding Gate 2 matrix. It does not
approve any test or production implementation.

## Verification

```text
$ git rev-parse HEAD
f9af106aeed445bb82d4421656da48121a9177aa

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff f9af106^ f9af106 --check
PASS (no output)

$ git diff --name-only f9af106^ f9af106
specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md

$ printf 'original-0000' | wc -c
13

$ printf 'revision-0000' | wc -c
13

$ awk formula check for every n=1..1024
FORMULA_OK n=1..1024 endpoints=13,14335
```

The reviewed commit changes only the executable specification metadata and the
previously rejected total-length sentence. OpenSpec hashes are unchanged from
the v18 review; no test or production path changed.

## Exact reviewed hashes

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
e9bc402f092de6e03d67573f40dcd6bb94f25005a44a0cab65fc869da1b06ef7  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
60495390c885ad6524ced4e8beb0c23773e01795c5d6e7c921e5da9b2ad15afb  openspec/changes/replace-pilot-registration-with-original-upload/design.md
f7572685872607bdc9c6facec810256ac54abc35d652b4e76304357d4a0754b8  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
5eafa180291aabf57aa1204107e37a91bf165e9efc0c65e5914a7e65b03ea8fd  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
3ddf2ce48ede89d255a3f6ff2f7b5c3c262f7ca94a17dd832f6254f5f159f9b8  docs/operations/assignment-order-original-worker-id-sequence-gate1-gap-2026-09-05.md
6ea7512b2f84cf5ad35dcf577deb58db2183f2cdb4e1608a9af06e3eba184ca2  docs/operations/pilot-assignment-order-original-worker-id-sequence-gate1-review-v18-2026-09-05.md
```

This review record omits its own circular hash.
