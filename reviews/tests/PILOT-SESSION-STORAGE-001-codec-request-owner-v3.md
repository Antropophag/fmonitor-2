# Independent Gate 3 test rereview — PILOT-SESSION-STORAGE-001 v10 codec/request owner v3

- Date: 2026-09-03T23:46:50+03:00
- Reviewer: separately tasked agent `/root/session_codec_owner_gate3_v3`
- Test/implementation author: not this reviewer
- Reviewed commit: `8ad9176070417d29d3a553d2875a3d21e6627976`
- Specification: owner-approved `PILOT-SESSION-STORAGE-001` v10, sections 3 and 8
- Prior reviews: `reviews/tests/PILOT-SESSION-STORAGE-001-codec-request-owner-v1.md`, `reviews/tests/PILOT-SESSION-STORAGE-001-codec-request-owner-v2.md`
- Verdict: **APPROVED**

## Closed v2 finding

CR-G3-03 is closed. The v3 test retains every v2 decode, encode and public
factory assertion and adds positive `encode()` assertions at all three exact
contract boundaries: 16 nested array levels, a flat 4096-entry array, and a
nested array with 64 outer entries plus 4032 inner entries (4096 recursively
reachable entries total). Each expected byte string is independently produced
with the specification's canonical PHP `serialize()` primitive. The paired
depth-17, flat-4097 and nested-total-4097 rejection assertions remain present,
so encode implementations with either an off-by-one limit or a root-only entry
count cannot pass.

The complete retained matrix also protects canonical decode, trailing-byte
rejection, float rejection, all allowed encode leaf kinds, exact canonical
encode bytes, and encode rejection of float, object, reference, reference
cycle, depth overflow and both flat and nested entry overflow. The public
composition assertion still constructs one complete graph from dependency set
A, then supplies distinguishable set B and requires the conflicting second
factory call to fail closed with `LogicException`; it does not inspect private
or static state.

## Independent fixture and sensitivity checks

An independent recursive counter confirmed the literal positive fixtures and
their boundaries:

```text
depth=16 flat=4096 nested_total=4096 depth_exact=yes flat_exact=yes nested_exact=yes
```

The current production factory demonstrates that the conflict assertion is
sensitive to the outstanding behavior rather than vacuously passing:

```text
first_graph=yes conflict_failed_closed=no
```

Only the test, v3 RED evidence, and the prior append-only v2 review differ from
the v2 reviewed commit; no production file changed in this RED revision.

## Reproduced RED

```text
$ git rev-parse HEAD
8ad9176070417d29d3a553d2875a3d21e6627976

$ php -l tests/InstallationProcess/pilot_session_codec_request_owner_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_session_codec_request_owner_001_test.php

$ php tests/InstallationProcess/pilot_session_codec_request_owner_001_test.php
PHP Fatal error:  Uncaught TestFailure: float rejected
Expected: NULL
Actual: array (
  'float' => 1.5,
)
exit=255

$ git diff --check 4c1db45a1094d22b8c147ab3d79719d5a0deba57..8ad9176070417d29d3a553d2875a3d21e6627976
exit=0
```

The failure follows successful canonical decode and trailing-byte rejection and
is the same missing codec validation recorded by the v3 evidence. It is not a
fixture, load, syntax, environment, or production-system failure. Later
assertions intentionally remain behind this first honest RED until Gate 4.

## Reviewed hashes

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
f9088b72e6c3cc3c7f42ee20527cb3e464afced7019c85cacc0f66deae18429f  tests/InstallationProcess/pilot_session_codec_request_owner_001_test.php
f230999babdccee2eb2a9292c91427857f9f6bfb8bfeef30feb07050166777f0  docs/operations/pilot-session-codec-request-owner-red-evidence-v3.md
c722c9fc002af1907dc4f9ef343ef01c3ee4dcf0bdc0456e2ed2ab20eddc50d4  reviews/tests/PILOT-SESSION-STORAGE-001-codec-request-owner-v1.md
0afc2f64b6d01985b1afc58bfe437128290de662a77b818b928ede8afeed8c0a  reviews/tests/PILOT-SESSION-STORAGE-001-codec-request-owner-v2.md
```

## Findings

None. The test is traceable to the public codec and production composition
seams, uses independently derived expected values, covers accepted and rejected
boundaries, is deterministic and isolated, and fails for the intended missing
behavior.

## Verdict

Gate 3 is **APPROVED** for the exact test at reviewed commit `8ad9176` and test
hash `f9088b72e6c3cc3c7f42ee20527cb3e464afced7019c85cacc0f66deae18429f`.
Gate 4 may implement only enough production behavior to satisfy this approved
oracle without changing its expectations.
