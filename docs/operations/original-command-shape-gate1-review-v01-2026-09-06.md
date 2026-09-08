# ASSIGNMENT-ORDER-ORIGINAL-COMMAND-SHAPE-001 v0.1 — independent Gate 1 review

Дата: 2026-09-06.  
Reviewer task: `/root/selection_v04_readiness`.  
Reviewed commit: `96c22d2305e01daa86fe6ace26a2862f8f6f85ab`.  
Verdict: **CHANGES_REQUESTED**.

Reviewer не автор specification, parent или OpenSpec artifacts. Это review только
scalar/calendar/Unicode/opaque-ID amendment. Public API parity, lifecycle/storage
observers, response loss and combined original-command review остаются отдельными
corrective scopes.

## Exact reviewed hashes

```text
f11ee3fdf4c326efb7ea3a285a92a580882f502cad1d24b1fb4f11d7ce24a494  specs/ASSIGNMENT-ORDER-ORIGINAL-COMMAND-SHAPE-001.md
2ded2156b983886ee9276adefd4e3df53937f715c877a6131fd7b32da19dc1d0  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
653fd90c53d8fdf89533ce0f88c2f18798d0e273cac04aaf596584ce4ba0521a  docs/operations/original-command-shape-contract-audit-2026-09-06.md
7388b5d8b371f9de05940af7d88e6de19604faa49ab7acf89b202aa0422e8809  openspec/changes/replace-pilot-registration-with-original-upload/design.md
233510a2eb8aefe347f2e0f6ac50c539865a8b6b79af25f3b9724c0b4e15f1bb  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
6b0ca8f1b33469c9c8eb51ba4fc0c2c16394df355b8e80ffe62e5c540e264515  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
c59ef5e8784c68c3f260bdf1baafbd17e4314c09e5aee5021fc3127409e06de9  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
```

## Blocking finding

### P0 — opaque-ID grammar contradicts the active persisted schema

COMMAND-SHAPE-001 section 5 defines caller and GENERATED root/revision IDs as
every printable ASCII byte U+0021..U+007E and explicitly says quotes, backslash
and punctuation are ordinary allowed data. This admits both `/` and `\`.

The active parent v62 section 18, lines 1422–1425, requires every non-null
root/revision ID in roots, revisions, requests and events to satisfy exactly:

```text
CHAR_LENGTH(value) BETWEEN 1 AND 80
AND value NOT REGEXP '[[:cntrl:]/\\]'
```

The implemented schema uses the same exclusion. Therefore these literal values
are valid under the amendment but cannot be persisted under the normative parent:

```text
root/0001
revision\0001
/
\
```

For caller IDs, the amendment says a shape-valid unknown identity reaches lineage
and its existing conflict outcome. For GENERATED IDs, it says a grammar-valid ID
may proceed to finalize/commit. The parent database instead rejects these values,
so one input has two incompatible normative outcomes and the successful path is
not constructible across the public seam and persistence boundary.

Required correction: define the shared caller/GENERATED grammar as 1..80 ASCII
bytes U+0021..U+007E **excluding slash and backslash**, matching the exact parent
CHECK. Add `/` and `\` to fixed invalid caller and malformed-GENERATED examples.
Keep quotes and other printable punctuation allowed because parameter binding and
ASCII-bin persistence support them. Reconcile parent linkage and all four
OpenSpec artifacts at exact hashes, then request a fresh independent Gate 1.

This is a technical storage-boundary correction and requires no product decision.

## Non-blocking review results

### Passive DTO and precedence — PASS

The DTO retains exact constructor arguments, types and passive construction.
Application step 1 owns invalid-but-type-correct values and returns the full
nonretryable `REJECTED/INVALID_COMMAND` tuple. Validation precedes authorization,
terminal/fingerprint/lineage repositories, composition, clock, inspector,
storage, IDs, lifecycle and delivery. Invalid input creates no request, audit,
root, revision or event; stream read is zero and close is attempted once.

The permitted safe-log request binding before shape is correctly inherited from
the approved isolation contract. Stream-close diagnostic failure cannot replace
the selected invalid-command result or disclose the malformed raw value.

### Gregorian calendar — PASS

The amendment pins exact 10 ASCII bytes, year 0001..9999, real Gregorian month/day
and leap rules, with no normalization, whitespace, time or offset. Invalid and
valid leap-day examples distinguish calendar shape from future-date policy.
Future comparison remains after the single validated UTC clock and Moscow
conversion.

### UTF-8, raw controls and Unicode trim — PASS

Valid UTF-8 is checked before normalization. Raw Cc ranges are exact and preserve
TAB/LF/CR while rejecting all other Cc characters, including U+0085 even though
it is in the trim set. Raw checking before trim prevents hidden prohibited
leading/trailing controls.

The White_Space trim set is enumerated rather than delegated to locale/runtime
defaults. Interior allowed whitespace is byte-preserved; normalization performs
no NFC/NFKC/case folding. Code-point limits are exact and explicitly differ from
bytes and grapheme clusters. Boundary examples cover empty/trim-empty, 1/max/max+1,
multibyte values, combining marks, invalid UTF-8, NUL/Cc, NBSP and allowed
TAB/LF/CR.

Accepted correction persists the normalized reason while leaving the immutable
command unchanged. Filename validation uses its normalized value but may retain
raw command metadata because this seam does not persist it. Neither field enters
the fingerprint, preserving existing replay identity.

### Replay preservation and controls — PASS

Malformed shape is rejected before a repository fixture capable of returning a
stored terminal result, closing the previously confirmed replay leak. Once shape
is valid, changed filename/reason does not add fingerprint equality and the
approved terminal replay remains unchanged.

Positive controls cover filename 1/255, reason 1/500, Unicode trim, interior
allowed whitespace, valid leap day, arbitrary grammar-valid caller identity
reaching a denied authorizer and exact accepted initial/correction outcomes. This
prevents reject-all validation.

### Generated-ID scope — PASS except grammar finding

Using the same storage-safe grammar for caller and GENERATED IDs is coherent.
Malformed generated identity maps to the existing retryable
`FAILED/PERSISTENCE_FAILURE` at the ID boundary, is not retried as COLLISION,
stops before finalize/commit, and performs one normal abort/stage-close/
stream-close sequence. Initial-root and correction-revision cases, call counts
and no-next-source behavior are observable.

Worker sequence tokens correctly retain their narrower transport-only grammar.
The amendment does not change the eight-collision limit or non-GENERATED status
mapping. Once slash/backslash exclusion is corrected, this portion is
constructible.

### Scope and product behavior — PASS

The amendment specifies technical scalar validation and normalization already
required by the parent. It changes no actor, capability, upload limit, workflow,
HTTP route, storage owner, selection/opening behavior or user-visible policy.
No new product approval is needed.

Parent v62 and the four OpenSpec artifacts consistently link the amendment,
preserve separate observer/API/response-loss scopes and leave shape tests and
implementation pending. Their opaque-ASCII shorthand must be corrected together
with the normative grammar to avoid retaining the same ambiguity.

## Gate disposition

Gate 1 is **CHANGES_REQUESTED** solely for the slash/backslash storage
contradiction. Do not write scalar-shape RED against v0.1. After the grammar,
examples, parent and OpenSpec links are corrected, obtain a fresh independent
Gate 1 at exact hashes. Existing dynamic-port implementation/review remains
separate and is not reopened by this verdict.
