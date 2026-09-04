# PILOT-SESSION-STORAGE-001 — anonymous repeated commit Gate 3 rereview v2

Date: 2026-09-04

Reviewer: `/root/session_anonymous_repeat_gate3_v2`

Reviewed commit: `e2026fe5305d6006f6970ed4c8acc25f15b842d6`

Correction base: `74913e3e3118bf89cc2151e207cf21fdba3489b1`

Verdict: **APPROVED**

Independence: the reviewer did not author or edit the reviewed test, its v2 RED
evidence, the production owner, or the prior review. This rereview adds only
this append-only review record.

## Traceability and scope

The correction-base diff contains only the focused public-owner test correction
and its append-only v2 RED evidence. No production file, shared fixture,
checklist integration test, approved input, or prior review was changed.

Exact reviewed hashes:

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
a2e376531a4db9364cc16636388d9bc8285bd54b06d16ddd8b68edd6f0818496  reviews/tests/PILOT-SESSION-STORAGE-001-local-auth-lifecycle-v1.md
1abbf879022d43d2e85bc4bfcd1ae8845fe46c09c8c7768fb9e8c4f0013c354e  reviews/tests/PILOT-SESSION-STORAGE-001-architecture-ratchet-v2.md
efa7dca793cfa864b91eadd887af3e7bd687eb7ca630b2d9b7a6bcf2e20d91d2  tests/InstallationProcess/pilot_session_storage_anonymous_repeat_001_test.php
b1626824e6f9d571a75e86c6024c5e2bc236e79c7ada147bc0739ac40adf711b  docs/operations/pilot-session-storage-anonymous-repeat-red-evidence-v2-2026-09-04.md
```

## Prior rejection resolved

The external-collision scenario now follows the specified lifecycle boundary.
It first obtains deterministic ID1 from `start(null)`, creates the external
mode-0600 committed ID1 file afterward, and detects the collision only when
`writeCommit(ID1, payload)` attempts no-clobber publication. The owner then
returns deterministic ID2. The test independently proves that the external
ID1 digest is unchanged, the submitted bytes exist under owner ID2, and the
instance contains exactly external ID1 plus owner ID2.

Those assertions execute before the deliberately deferred primary assertion.
Every replay reached the primary assertion at line 29, so the corrected
collision timing, retry identity, preservation, payload, and exact-file-count
oracles all passed. This closes the sole blocking finding in the v1 review.

## Primary intended RED and sensitivity

The primary scenario remains a direct call through the real public owner
factory. Deterministic entropy establishes ID1 and ID2. It starts ID1, commits
`payload-one` under ID1, and performs an ordinary second
`writeCommit(ID1, payload-two-latest)`. Current production incorrectly returns
`OK/ID2`; the contract requires `OK/ID1`, one addressable committed file, and
the latest bytes under ID1.

The result tuple and material snapshot are captured immediately after the
second write. Deferring their assertions until after the independent collision
scenario ensures both oracle families execute in the same focused run without
weakening the primary RED. An implementation that merely fixes the returned ID
must still satisfy the exact one-file/path/latest-bytes assertions.

## Independent reproduction

Syntax passed. Three fresh focused executions were byte-for-byte stable in the
material failure: each exited `255` only at the intended primary ID assertion
with expected `OK/1111...1111` and actual `OK/2222...2222`. `git diff --check`
for `74913e3..e2026fe` passed with no output.

```text
$ php -l tests/InstallationProcess/pilot_session_storage_anonymous_repeat_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_session_storage_anonymous_repeat_001_test.php

$ php tests/InstallationProcess/pilot_session_storage_anonymous_repeat_001_test.php
Repeated anonymous commit retains the caller current ID1.
Expected: [OK, 1111111111111111111111111111111111111111111111111111111111111111]
Actual:   [OK, 2222222222222222222222222222222222222222222222222222222222222222]
exit 255
```

The random root token isolates concurrent runs while all IDs and stage entropy
that affect asserted behavior are queued literals. The failure text contains
only those fictional deterministic IDs and no ambient path or credential.

## Cleanup and standards

The attempt-all `finally` recursively removes only the exact random test-owned
root and asserts its absence. After each of the three RED executions no matching
`.test-artifacts/session-anonymous-repeat-*` child remained, and the worktree
had no generated or modified file. The external collision is deliberately
inside that owned root; no foreign/default/Compose path is touched.

No documented-standard breach or applicable code-smell finding exists in the
two-file correction. The compact style is consistent with adjacent focused PHP
tests, and the correction adds no production abstraction or scope.

Gate 3 is approved for the exact reviewed commit and hashes above. Gate 4 may
implement the smallest production correction that preserves ID1 for an
ordinary repeated anonymous commit and makes the approved focused oracle GREEN
without weakening its collision, material-state, determinism, or cleanup
assertions.
