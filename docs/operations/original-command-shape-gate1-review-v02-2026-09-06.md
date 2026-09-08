# ASSIGNMENT-ORDER-ORIGINAL-COMMAND-SHAPE-001 v0.2 — independent Gate 1 rereview

Дата: 2026-09-06.  
Reviewer task: `/root/selection_v04_readiness`.  
Reviewed commit: `f8d4df60303f6c930e379576b88af37ff9b4a580`.  
Verdict: **APPROVED**.

Reviewer не автор specification, parent или OpenSpec artifacts. Scope ограничен
scalar/calendar/Unicode/opaque-ID amendment. Public API parity,
lifecycle/storage observers, response loss and combined original-command review
остаются отдельными corrective scopes.

## Exact reviewed hashes

```text
e98e37f832a64995c68e329e106b1c56ec0ded28d309ee43539b9970e7cb5d0d  specs/ASSIGNMENT-ORDER-ORIGINAL-COMMAND-SHAPE-001.md
d7113dcdf79915751f266f8026c5415fab8ec3a9878cd8c51ce43ddb1c7ab6a8  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
ee5bdf2b475a27899e7c86e2c96a7cd85c4ee385b6f130883cc12a7009f1a2fc  docs/operations/original-command-shape-gate1-review-v01-2026-09-06.md
84e88e033473f234ac63a62ce933d714034cf2ed6e693ce5c5364c6c6023711f  openspec/changes/replace-pilot-registration-with-original-upload/design.md
3512c841965f83c9255d8f297c286bc515a0efb52895bf8b5d0c9209a5364bcd  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
321b7f0f1ef0e4ac1d8b9f5db1b38fa5d6174be3f3aee8f7b8fd37f2681126d5  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
c6682bdf947d34624efe6144965e2859c734a3d63c2d30a95e6a6b2e6e2f4e43  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
```

## v0.1 finding disposition

### Slash/backslash schema contradiction — RESOLVED

The shared caller/GENERATED identity grammar is now exactly 1..80 ASCII bytes,
each U+0021..U+007E except U+002F slash and U+005C backslash. This matches parent
v63's normative ASCII-bin columns and exact
`NOT REGEXP '[[:cntrl:]/\\]'` checks.

The amendment no longer calls backslash ordinary punctuation. It explicitly
classifies these caller and GENERATED values as invalid:

```text
root/0001
revision\0001
/
\
```

Neither escaping nor trimming converts them to a different identity. Quotes and
other permitted printable punctuation remain valid and safe through required
parameter binding. Worker ID sequences retain their narrower transport-only
grammar.

Malformed caller values produce first-step `REJECTED/INVALID_COMMAND`; malformed
GENERATED values produce the existing retryable `FAILED/PERSISTENCE_FAILURE`
before finalize/commit and are not retried as collisions. Both outcomes are now
constructible through the existing persistence schema.

## Preserved v0.1 findings

The v0.1 review's non-blocking PASS conclusions remain unchanged:

- passive DTO construction and exact validation-before-business-port precedence;
- invalid result tuple, no audit/facts, stream read zero and close attempt once;
- safe-log request binding as the sole permitted diagnostic operation before
  shape and best-effort close diagnostic preservation;
- real Gregorian dates for years 0001..9999 without PHP normalization;
- valid UTF-8, raw Cc rejection except TAB/LF/CR, exact enumerated Unicode
  White_Space trim and code-point length boundaries;
- normalized correction reason persisted without mutating command or fingerprint;
- filename validation without path/storage ownership;
- malformed metadata rejected before terminal replay while valid changed metadata
  preserves approved replay behavior;
- positive boundary controls preventing reject-all behavior;
- generated root/revision validation, exact cleanup, no finalize/commit and
  unchanged eight-collision/non-GENERATED behavior;
- no new actor, capability, workflow, upload, HTTP, filesystem or product policy.

## Parent and OpenSpec coherence

Parent v63 cites the exact amendment and now states printable ASCII 1..80 IDs
excluding slash/backslash. Proposal, design and delta repeat the same technical
boundary. Tasks preserve the rejected v0.1 history, keep scalar RED/Gate 3 and
GREEN/Gate 5 open, and do not infer combined original-command approval.

No behavioral narrowing or new product decision was introduced by v0.2.

## Gate disposition

`ASSIGNMENT-ORDER-ORIGINAL-COMMAND-SHAPE-001` v0.2 satisfies Gate 1 at exact
SHA256 `e98e37f832a64995c68e329e106b1c56ec0ded28d309ee43539b9970e7cb5d0d`
and is **APPROVED** to proceed to a demonstrated pure public-seam RED. The RED
must use the fixed invalid and positive controls from the specification and then
receive independent Gate 3 before minimal GREEN. This approval does not approve
tests, implementation or any separate corrective scope by implication.
