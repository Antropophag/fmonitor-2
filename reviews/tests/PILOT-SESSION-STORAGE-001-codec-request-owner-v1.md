# Independent Gate 3 test review — PILOT-SESSION-STORAGE-001 v10 codec/request owner v1

- Date: 2026-09-03T23:39:04+03:00
- Reviewer: separately tasked agent `/root/session_codec_owner_gate3`
- Test/implementation author: not this reviewer
- Reviewed commit: `2d3230369c724fb0e37307cee1ec548529e1d20c`
- Specification: owner-approved `PILOT-SESSION-STORAGE-001` v10, sections 3 and 8
- Prior authority: `reviews/code/PILOT-SESSION-STORAGE-001-consumers-v1.md`
  (`CHANGES_REQUESTED`, findings SESSION-CONSUMERS-G5-01 and G5-02)
- Verdict: **CHANGES_REQUESTED**

## Sound coverage and reproduced RED

The test uses the named public codec and request-owner types rather than private
methods. Its canonical accepted payload is independently produced by PHP's
specified whole-array `serialize()` primitive. The trailing-byte case is a
useful canonicality mutation. The first owner assertion uses strict object
identity, and the second distinct owner assertion is sensitive to the current
`??=` behavior rather than merely checking a return type.

The checked encode assertions point in the right direction and do not reuse a
production encoder. The isolated PHP process also makes the static binding
sequence deterministic. Hashes in the evidence match the exact reviewed spec
and test. Independent execution reached the recorded missing-behavior failure:

```text
$ git rev-parse HEAD
2d3230369c724fb0e37307cee1ec548529e1d20c

$ sha256sum specs/PILOT-SESSION-STORAGE-001.md \
    tests/InstallationProcess/pilot_session_codec_request_owner_001_test.php
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
157f6750d4e42ce1dd5cec0eaa55d1a66ebfc1d83709c7b32a3fdb17134e73c8  tests/InstallationProcess/pilot_session_codec_request_owner_001_test.php

$ php tests/InstallationProcess/pilot_session_codec_request_owner_001_test.php
float rejected
Expected: NULL
Actual: ['float' => 1.5]
exit=255

$ git diff --check 9baede7..2d32303
exit=0
```

This is an intended behavioral RED after successful canonical decode and
trailing-byte rejection, not a setup or loading failure.

## Blocking findings

### CR-G3-01 — exact codec bounds and the shared encode grammar are under-specified by the oracle

The test rejects depth 17 and a flat 4097-entry root, but has no accepted depth
16 or accepted 4096-entry boundary. Consequently implementations using `< 16`
or `< 4096` would pass. The flat entry case also permits an implementation that
checks only the root array size; it does not prove the required **total** entry
count across nested arrays. Add literal boundary pairs that accept exactly 16
levels and exactly 4096 total recursively reachable entries and reject one more,
including an over-limit fixture whose root itself is small.

The scalar matrix only rejects a float. Existing approved malformed-payload
tests exercise object/reference rejection on decode, but this new public
`encode()` seam is not tested against objects, references/cycles, or all allowed
leaf kinds (`null`, `bool`, `int`, `string`, nested array). Section 3 requires
the same complete shape validation before write/regenerate. A codec could apply
the full decoder grammar but let a forbidden encode value through and still
pass this test. Add an independently literal accepted mixed-scalar/nested value
and encode rejections for every serializable forbidden-shape family, especially
object and reference/cycle, while retaining exact canonical bytes. The test may
cite the already approved decode cases rather than duplicating them, but the
new encode contract itself must be protected.

### CR-G3-02 — direct static binding does not prove the public factory dependency seam

G5-02 concerns `ProductionPilotHttpEntrypointFactory` silently preferring an
owner retained from an earlier factory construction over the filesystem,
clock, entropy and observer explicitly supplied to
`createWithSessionStorageDependencies()`. The test calls
`PilotSessionRequestOwner::bind()` directly and never constructs either public
factory method. A production regression that bypasses `bind()`, binds only one
factory path, or supplies a different owner separately to LocalAuth and the
coordinator would pass unchanged.

Add a public-composition assertion that invokes the production factory seams in
one process with two distinguishable dependency sets. It must prove that the
first constructed graph preserves exact owner identity for both consumers and
that a later conflicting construction fails closed before it can use or return
the earlier owner. The test double may observe public primitive calls or an
external trace, but must not inspect private/static fields. This also establishes
that setup succeeds through the actual construction boundary rather than only
through a helper class loaded with direct `require` statements.

## Verdict

Gate 2 is genuinely RED, but it does not yet fully protect either correction
requested by the consumer Gate 5 record. Gate 3 remains
**CHANGES_REQUESTED**. Amend the oracle and evidence append-only, reproduce the
new first intended RED at the exact test hash, and request a fresh independent
Gate 3 review before production changes.
