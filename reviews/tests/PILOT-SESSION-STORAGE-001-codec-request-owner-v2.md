# Independent Gate 3 test rereview — PILOT-SESSION-STORAGE-001 v10 codec/request owner v2

- Date: 2026-09-03T23:49:00+03:00
- Reviewer: separately tasked agent `/root/session_codec_owner_gate3_v2`
- Test/implementation author: not this reviewer
- Reviewed commit: `4c1db45a1094d22b8c147ab3d79719d5a0deba57`
- Specification: owner-approved `PILOT-SESSION-STORAGE-001` v10, sections 3 and 8
- Prior review: `reviews/tests/PILOT-SESSION-STORAGE-001-codec-request-owner-v1.md`
- Verdict: **CHANGES_REQUESTED**

## Closed v1 findings and reproduced RED

The revised decode matrix now contains the exact accepted/rejected boundaries
requested by CR-G3-01. Independently counting the fixtures gives 16 array
levels for `depth16`, 4096 root entries for the flat fixture, 4096 total
recursively reachable entries for the 64-by-63 nested fixture (64 outer plus
4032 inner), and 4097 after its one-element mutation. The mixed encode fixture
covers null, bool, int, string, nested arrays and integer/string keys. Encode
also rejects float, object, reference, cycle, depth 17, flat 4097 and nested
total 4097. Canonical expected bytes come from PHP `serialize()`, independently
of the production codec.

CR-G3-02 is materially improved: the test now enters the complete production
graph through the public injected-dependency factory twice, with distinct
filesystem, clock, entropy and observer objects, and requires a fail-closed
`LogicException` on the conflicting second construction. This catches the
current `??=` behavior that silently returns the first owner.

The exact reviewed test is syntactically valid and independently reproduces
the evidence's intended first failure after canonical decode and trailing-byte
control:

```text
$ git rev-parse HEAD
4c1db45a1094d22b8c147ab3d79719d5a0deba57

$ php -l tests/InstallationProcess/pilot_session_codec_request_owner_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_session_codec_request_owner_001_test.php

$ php tests/InstallationProcess/pilot_session_codec_request_owner_001_test.php
float rejected
Expected: NULL
Actual: array (
  'float' => 1.5,
)
exit=255

$ git diff --check 9b070aa..4c1db45
exit=0
```

This is missing codec behavior, not setup failure. The evidence test hash and
observed failure agree with the exact reviewed commit.

## Remaining blocking finding

### CR-G3-03 — checked encode can reject valid exact-boundary states

Section 3 requires the same shape limits before encode as on decode, including
acceptance through depth 16 and 4096 total entries. The corrected test proves
those exact accepted boundaries only through `decode()`. Its `encode()` success
cases are shallow and small; encode is tested only for rejection at depth 17
and 4097 entries. An implementation using `< 16` and/or `< 4096` solely in the
encode path would pass every assertion while rejecting contract-valid state
before `writeCommit` or `regenerate`.

Require exact canonical `serialize()` bytes from `encode($depth16)`, from a
4096-entry flat value, and from the independently totalled nested-4096 value,
while retaining the corresponding one-over-limit rejections. This is a test
change after review and therefore requires a fresh RED capture/evidence hash
and Gate 3 rereview.

## Same-owner assessment

The new factory assertion does not by itself prove object identity for both
consumers in the first returned graph: it does not execute that graph or touch
either distinguishable port set. A split-owner graph using the same supplied
ports could satisfy it. However, that omission is not an additional blocker
for this narrow G5-02 correction when read with the already approved raw-HTTP
LocalAuth/coordinator/UserAccess tests: those tests exercise both factory-created
consumer paths against injected dependencies, while this test newly makes
cross-construction dependency reuse fail closed. Exact same-variable wiring is
also directly reviewable in the small factory Gate 4 diff. Gate 5 must still
verify that one newly constructed owner is passed to both consumers and that no
alternate owner is introduced; this Gate 3 record does not infer that property
from the mere `PilotHttpEntrypoint` return-type assertion.

## Reviewed hashes

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
42253d6c3ca1dd5013795dd152526de803b37d648bb40ef1f5d1ab9b019f41b7  tests/InstallationProcess/pilot_session_codec_request_owner_001_test.php
92236882357a98f578dea94faea0e1a6c996319756e82c829d54fca337605cc5  docs/operations/pilot-session-codec-request-owner-red-evidence-v2.md
c722c9fc002af1907dc4f9ef343ef01c3ee4dcf0bdc0456e2ed2ab20eddc50d4  reviews/tests/PILOT-SESSION-STORAGE-001-codec-request-owner-v1.md
```

## Verdict

The RED is honest and both prior findings are substantially addressed, but the
shared encode grammar still lacks positive exact-boundary protection. Gate 3
remains **CHANGES_REQUESTED** until CR-G3-03 is added without weakening any
existing assertion, RED is recaptured at the new exact hash, and a fresh
independent reviewer approves it.
