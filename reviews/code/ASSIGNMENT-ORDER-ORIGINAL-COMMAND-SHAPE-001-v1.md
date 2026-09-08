# Code review: ASSIGNMENT-ORDER-ORIGINAL-COMMAND-SHAPE-001 v1

- Reviewer: separately tasked agent `/root/selection_v04_readiness`
- Reviewer authored neither reviewed tests nor production implementation
- Reviewed commit: `bca89b4853a7106fac3194d3724f27cba38b3b2f`
- Base/test commit: `9b8d6ec03846b8b8e96444e9f451da72d106e266`
- Specification v0.2 SHA256: `e98e37f832a64995c68e329e106b1c56ec0ded28d309ee43539b9970e7cb5d0d`
- Parent specification v63 SHA256: `d7113dcdf79915751f266f8026c5415fab8ec3a9878cd8c51ce43ddb1c7ab6a8`
- Independent Gate 3 SHA256: `289baeb2778ea4a7ee54f7b689b6da54b97555f02b91eac699c5786fd9040e0e`
- Verdict: **APPROVED**

## Exact reviewed hashes

```text
34a5725044c015a36fc729f57355940fb056fbbc77f265ae6ce789ca554903e7  app/AssignmentOrderOriginal/AssignmentOrderOriginalCommandShape.php
a7767a8bb6ae53baa87bdcc04f598f8b399a0411b0faff80e5ddee27c0665647  app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php
57369a798740de338b797b5d59b25a0c94eb23d612f95dd154d47ca90d5fbb70  app/AssignmentOrderOriginal/AssignmentOrderOriginalPortValues.php
32858b2c602f65f19b5efeb1773db354d3fd6b44fb3535c88995d2021bf553d2  tests/InstallationProcess/assignment_order_original_command_shape_001_test.php
f458a7f705ff7f5e217f50e860a117c5ff8042832b9d0035481df958da4eda95  docs/operations/original-command-shape-green-v1-2026-09-06.md
67650e2456b6d9720576d0756497a7343969806b86e16a3bb6bc401c585b11e0  /Users/antropophag/.local/state/fmonitor2-verification/original-shape-green-aecqm3bs/evidence.json
```

Evidence records identical before/after reviewed HEAD.

## Production diff and ownership

The production change adds one readable 57-line pure shape helper, delegates the
existing Runtime shape method to it, passes its normalized correction reason to
the accepted commit, and makes the existing ID-port helper reuse the same opaque
identity grammar. No command constructor, result, authorizer, repository,
storage, schema, HTTP or capability surface changes.

Runtime eagerly loads the shape helper before PortValues. Established production,
worker, verification and direct Runtime imports therefore see the helper without
an environment selector or alternate validation owner. Shape remains the first
business step; approved safe-log request binding is the sole earlier diagnostic.

## Specification conformance

### Calendar — PASS

The helper requires exact four-two-two ASCII digits and then calls `checkdate`.
It rejects year zero, invalid/zero month or day, non-leap February 29, impossible
month days, missing padding, whitespace and appended data. Valid leap day remains
exact instead of being normalized by PHP parsing.

### UTF-8, controls, trim and lengths — PASS

UTF-8 validation precedes normalization. The raw-control expression rejects
exactly U+0000..0008, U+000B..000C, U+000E..001F and U+007F..009F while allowing
TAB/LF/CR. Forbidden edge controls therefore cannot disappear during trim.

The trim constant enumerates the approved White_Space set. Its anchored
expression removes only edge sequences and preserves interior bytes. U+0085 is
still rejected by the earlier raw-control rule. `preg_match_all('/./us', ...)`
counts Unicode code points, including allowed newlines, rather than bytes or
grapheme clusters. Filename bounds are 1..255 and reason bounds 1..500. No
replacement, Unicode composition, case-folding or locale behavior is added.

### Opaque identities — PASS

The shared helper requires non-null 1..80 bytes of printable ASCII U+0021..U+007E
and excludes slash and backslash. ASCII byte length matches the parent ASCII-bin
schema. Empty, 81-byte, space/control/DEL, NBSP, invalid UTF-8 and separator
values fail. Quotes and allowed punctuation remain unchanged under existing
parameter binding.

Caller root/target/expected IDs use the helper at shape step. GENERATED root and
revision IDs reuse it through PortValues and fail before finalize/commit. They
are not retried as collision. Worker token grammar and the eight-collision
protocol remain unchanged.

### Precedence, replay and cleanup — PASS

Invalid metadata returns full nonretryable `INVALID_COMMAND` before authorizer,
repository, composition, clock, inspector, storage, IDs, lifecycle, audit and
delivery. The stream remains unread and close is attempted once. The terminal-hit
fixture proves malformed metadata cannot disclose or replay stored evidence.
Shape-valid changed filename/reason preserves approved replay without adding a
fingerprint comparison.

### Passive DTO and normalized persistence — PASS

The helper performs no I/O and does not mutate the command. Runtime normalizes
reason only after successful shape validation when building an accepted commit.
The correction persists the exact trimmed reason; reason remains excluded from
fingerprint. Filename is validated but remains non-persisted metadata and never
becomes a path or storage identity.

INITIAL still requires null lineage/reason. CORRECTION requires three valid IDs
and nonempty normalized reason. `compositionConfirmed=false` remains shape-valid
and reaches its inherited rejection.

## Test adequacy

The unchanged Gate-3-approved test has 194 public-seam cases: 152 former
negatives and 42 positive controls. It covers ordinary and terminal-hit invalids,
calendar boundaries, every control family, invalid UTF-8, Unicode trim,
code-point max/max+1, caller ID axes, INITIAL null policy, normalized correction,
valid replay and initial/correction generated-ID boundaries.

Every invalid asserts exact result, zero business calls, stream read zero and
close one. Positive boundaries reach fixed accepted, replayed or denied outcomes,
preventing reject-all logic. Generated-ID cases assert source counts, no next
allocation after root failure, exact cleanup, no finalize/commit/audit/delivery
and unchanged prior evidence.

The dynamic-port suite remains green across 33 cases, proving no regression in
four-status/eight-collision IDs, canonical clock or post-stream fingerprint
failure handling.

## Verification

Independent reviewer ran lint for helper, PortValues, Runtime and test: all PASS;
the shape test passed all 194 cases and dynamic-port test passed all 33 cases.

Parent sequential evidence records all 19 commands exit 0 at frozen SHA: 14
affected tests plus `make architecture-check` (7 rules), `make unit-test`,
`make lint`, strict OpenSpec validation and cumulative `git diff --check`. No
hotspot or architecture-baseline growth was introduced.

## Findings and disposition

No blocking correctness, validation, precedence, normalization, security,
architecture, import, maintainability or regression finding remains.

**APPROVED** for scoped Gate 5 of
`ASSIGNMENT-ORDER-ORIGINAL-COMMAND-SHAPE-001` v1 at commit
`bca89b4853a7106fac3194d3724f27cba38b3b2f`.

This closes only command shape and generated-ID grammar. Known observer/public
declaration, maintenance and response-loss issues remain open. It does not
establish combined original-command, protected E2E, deployment, full
verification or launch readiness.
