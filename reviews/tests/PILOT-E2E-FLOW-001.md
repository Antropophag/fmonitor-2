# Test review: PILOT-E2E-FLOW-001 v0.4

- Gate: 3 — fresh independent review
- Reviewer: separately tasked agent `/root/e2e_test_review_v7`
- Test author: separately tasked Gate 2 author; reviewer authored neither reviewed input
- Specification commit: `d211c92eea2e4980e6ebee5c2765d677cce76f14`
- Test commit / reviewed artifact: `3dec4f565e7af73f4d5894a46bce0aa141b65aa6`
- Specification: `specs/PILOT-E2E-FLOW-001.md`, version `0.4`, `APPROVED`
- Test: `tests/InstallationProcess/pilot_e2e_flow_001_test.php`
- Public seam: configured production raw HTTP under `/pilot`, public process projections, and public `AssignmentOrderArtifactService`
- Date: `2026-08-29`
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **Blocking — the v0.4 fault case is not a separate isolated fixture and does not contain exactly one fault.** Lines 73–75 reuse the database, artifact root, cookie, prepared state, and expected values of the still-running main journey. They then stop that journey's server, inject a private-table rename fault and a root-permission fault in sequence, issue one request to each of two compositions, restart the journey, and continue registration/opening. Section 9 permits one separate isolated fixture with its own unique-prefix database/root, prepared through the public seam, exactly one pre-start reversible fault, and exactly one failure request. Reusing and mutating the main fixture also violates the reiterated uninterrupted main-journey boundary.

2. **Blocking — the private-table rename remains outside the approved fixture boundary.** `RENAME TABLE fm2_order_artifacts ...` mutates an internal process/artifact table. Section 9 limits direct SQL to ordinary pre-request external/case fixture setup and cleanup; v0.4's narrow permission does not turn private `fm2_*` tables into a fault-injection seam. The table is restored in `finally`, but restoration does not authorize the arrangement.

3. **Blocking — the existing integrity mutation is still performed inside the main journey and is not finally-restored.** Line 76 writes `corrupt` directly to a hard-coded content-addressed path after the fault compositions, makes another artifact request, then restores the bytes only on the assertion-success path. Section 9 requires filesystem fault setup/restoration to target the isolated fixture and restoration to run in mandatory `finally`; it expressly keeps filesystem/manual intervention forbidden between main-journey requests. The required integrity rejection must be expressed without weakening those boundaries.

4. **Resolved aspect — the new public restoration oracle is suitable once moved into a compliant isolated case.** Fresh production connections construct the public artifact service before and after the faults, download both artifacts through `AssignmentOrderArtifactService`, and compare complete results. Together with the exact public queue-body comparison, this avoids private rows as expected values and observes process projection plus restored bytes/metadata. Connection and the two newly added fault restorations use `finally`.

5. **Exact request and response sensitivity are individually present but do not cure the fixture violation.** Each of the two dedicated compositions receives one exact artifact GET and asserts redacted `503`, `Service unavailable.\n`, and `Retry-After: 60`. The approved contract, however, permits one fault/composition/request in one separate case, not two one-request fault compositions embedded in the main journey.

6. **Determinism risk.** `chmod($artifactRoot, 0000)` is not a portable unreadability oracle when the test may run with privileges that bypass mode bits. A precise task-owned blob rename is the specification's deterministic example and can serve as the sole isolated fault without depending on execution identity.

## RED verification

Command run in the shared feature workspace with reviewed test bytes matching commit `3dec4f5`:

```text
$ php tests/InstallationProcess/pilot_e2e_flow_001_test.php
PHP Fatal error: Uncaught TestFailure: artifact projection unexpected DB failure redacted 503
Expected: [503, "Service unavailable.\n", "60"]
Actual:   [404, "Not found.\n", null]
at tests/InstallationProcess/pilot_e2e_flow_001_test.php:34
called from tests/InstallationProcess/pilot_e2e_flow_001_test.php:74
exit code: 255
```

The test reaches the first infrastructure-classification assertion and fails for the missing redacted `503` behavior rather than setup/authentication/transport failure. This is a sensitive RED, but Gate 3 cannot approve a test whose fault fixture contradicts the approved observable contract.

## SHA-256 reviewed-input manifest

```text
2c9ae79f73e5a3bf8d93c81fad3f431bd810a5d63c2648fa7dfab16f646839ab  specs/PILOT-E2E-FLOW-001.md
fd51e17eb9ef655904d5a99b22813975a85a4f65eb0a88ca1144cc54c2da4481  tests/InstallationProcess/pilot_e2e_flow_001_test.php
```

Git blob identities:

```text
2b3404a1564df3b1c1259058dd4a704841adb53a  specs/PILOT-E2E-FLOW-001.md
9d633d5ec47da980dc6e940eb39aba5cbc0aa613  tests/InstallationProcess/pilot_e2e_flow_001_test.php
```

Any byte change to either reviewed input invalidates this verdict. The review record is excluded from the self-referential manifest.

## Required changes

- Build one wholly separate unique-prefix MariaDB/artifact-root fixture and drive it to the prepared before-projection through the public process seam.
- Choose exactly one reversible pre-start fault, issue exactly one artifact failure request in its dedicated production composition, and restore the exact target in mandatory `finally`.
- After restoration, compare the public process projection with its before oracle using a fresh production process instance/connection and compare both restored artifacts through public `AssignmentOrderArtifactService`.
- Remove the private `fm2_order_artifacts` rename fault. Move/rework the integrity case so no filesystem mutation occurs inside the uninterrupted main journey and restoration is guaranteed on every failure path.
- Re-run the corrected committed test and request a fresh independent Gate 3 review.

Gate 3 is not approved. No Gate 4 implementation may proceed from test commit `3dec4f5`.
