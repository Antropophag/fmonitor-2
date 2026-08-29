# Test review: PILOT-E2E-FLOW-001 v0.4

- Gate: 3 — fresh independent review
- Reviewer: separately tasked agent `/root/e2e_test_review_v11`
- Test author: separately tasked Gate 2 agent `/root/e2e_tests`; reviewer authored neither reviewed input
- Specification commit: `d211c92eea2e4980e6ebee5c2765d677cce76f14`
- Test commit / reviewed artifact: `8614d7c7901cb603438acba6e140fe20fc751f52`
- Missing-behavior baseline: `45918050bebc260a246ee99121451522c51384fc`
- Specification: `specs/PILOT-E2E-FLOW-001.md`, version `0.4`, `APPROVED`
- Test: `tests/InstallationProcess/pilot_e2e_flow_001_test.php`
- Public seam: configured production raw HTTP under `/pilot`, public process projections, and public `AssignmentOrderArtifactService`
- Date: `2026-08-29`
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **Blocking — fault restoration does not cover failure of the EACCES precondition assertion.** The test executes `chmod($digest, 0000)`, performs the direct `fopen()` probe, and asserts that the probe failed *before* entering the `try/finally` whose `finally` restores `$originalMode`. If that assertion fails (including a runtime/filesystem that bypasses mode bits), control jumps directly to the outer cleanup and the exact fault target is never restored to its original mode. Section 9 v0.4 requires restoration in mandatory `finally` even when an assertion fails. Put the direct-read precondition and composition/request inside the restoration-protected `try`, with the permission restoration as the unconditional fault-target cleanup.

2. **The EACCES fixture otherwise exercises the intended read-I/O branch deterministically on this runtime.** It asserts a non-root effective UID, verifies the exact target is a regular non-symlink digest file, changes only that task-owned file to mode `0000`, and independently proves `fopen(..., 'rb')` fails before HTTP composition starts. The reviewed run reached the exact HTTP assertion, so setup, public preparation, filesystem precondition, server startup, and routing all succeeded.

3. **Exactly one failure HTTP request is present.** The dedicated composition receives one `GET` to the canonical artifact URL. `pefStart()` uses a TCP listener readiness connection, not an HTTP probe; there is no HTTP warm-up, retry, or follow-up in the isolated fixture.

4. **Response and public restoration oracles are correctly independent.** The expected `503 Service unavailable.\n`, `Retry-After: 60`, security headers, and redaction come literally from section 4 rather than production output. Before-state is obtained through public `InstallationProcess` and `AssignmentOrderArtifactService`; after restoration, a fresh connection/process/service compares the complete public projection and both artifacts' bytes/metadata. The rollback is used only as the missing-behavior baseline.

5. **The RED is genuine.** Against `4591805`, the exact EACCES request returns the inherited non-enumerating `404`, while the approved test expects the store-I/O `503`. The failure is at the intended classification assertion rather than setup, authentication, transport, or a private implementation oracle.

## RED verification

Command run in the shared feature workspace with specification `d211c92`, test `8614d7c`, and production rollback `4591805`:

```text
$ php tests/InstallationProcess/pilot_e2e_flow_001_test.php
PHP Fatal error: Uncaught TestFailure: isolated artifact-store read-time EACCES redacted 503
Expected: [503, "Service unavailable.\n", "60"]
Actual:   [404, "Not found.\n", null]
at tests/InstallationProcess/pilot_e2e_flow_001_test.php:35
called from tests/InstallationProcess/pilot_e2e_flow_001_test.php:40
exit code: 255
```

## SHA-256 reviewed-input manifest

```text
2c9ae79f73e5a3bf8d93c81fad3f431bd810a5d63c2648fa7dfab16f646839ab  specs/PILOT-E2E-FLOW-001.md
0e878c1fa1dcbd7cea4855d12b0d2aba603fad12d12599c855f507b8c7284aea  tests/InstallationProcess/pilot_e2e_flow_001_test.php
```

Git blob identities:

```text
2b3404a1564df3b1c1259058dd4a704841adb53a  specs/PILOT-E2E-FLOW-001.md
9d02b0c6676288da24a2a05f367b18b9ca1fdfb9  tests/InstallationProcess/pilot_e2e_flow_001_test.php
```

Any byte change to either reviewed input invalidates this verdict. The review record is excluded from the self-referential manifest.

## Required changes

- Move the direct EACCES `fopen` precondition and its assertion inside the `try` protected by the exact digest-mode restoration `finally` (or use an equivalent nesting that unconditionally restores the mode after any post-`chmod` assertion/start/request failure).
- Preserve the current regular-file/non-root checks, exactly-one-request boundary, exact section 4 response oracle, and fresh public projection/artifact comparisons.
- Re-run the committed test against the same intentional rollback and request a fresh independent Gate 3 review.

Gate 3 is not approved. No Gate 4 implementation may proceed from test commit `8614d7c`.
