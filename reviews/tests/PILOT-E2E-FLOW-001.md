# Test review: PILOT-E2E-FLOW-001 v0.4

- Gate: 3 — fresh independent review
- Reviewer: separately tasked agent `/root/e2e_test_review_v12`
- Test author: separately tasked Gate 2 agent `/root/e2e_tests`; reviewer authored neither reviewed input
- Specification commit: `d211c92eea2e4980e6ebee5c2765d677cce76f14`
- Test commit / reviewed artifact: `bba5c287ff3255cdd4a35af5fcb0ad786dca8ef2`
- Missing-behavior baseline: `45918050bebc260a246ee99121451522c51384fc`
- Specification: `specs/PILOT-E2E-FLOW-001.md`, version `0.4`, `APPROVED`
- Test: `tests/InstallationProcess/pilot_e2e_flow_001_test.php`
- Public seam: configured production raw HTTP under `/pilot`, public process projections, and public `AssignmentOrderArtifactService`
- Date: `2026-08-29`
- Verdict: `APPROVED`

## Findings

1. **The sole restoration defect from the prior Gate 3 review is fixed.** After the regular-file/non-symlink and non-root preconditions establish a safe task-owned target, the successful `chmod($digest, 0000)`, direct EACCES probe and assertion, composition startup, sole HTTP request, and response assertion all execute inside one `try`. Its `finally` always stops any started server and restores the exact digest file to `$originalMode`. Thus assertion, startup, request, and response failures after fault creation cannot bypass mode restoration, satisfying section 9 v0.4.

2. **The change is narrowly scoped and preserves the approved isolated-fixture boundary.** Relative to rejected test commit `8614d7c`, only the restoration guard changed. The dedicated fixture still owns a unique MariaDB database and artifact root, prepares state through public `InstallationProcess`, verifies the exact target is a regular non-symlink file, proves read-time EACCES independently before HTTP startup, and changes no shared/user artifact, source, or unrelated database.

3. **Exactly one failure HTTP request remains.** The dedicated production composition receives one `GET` to `/pilot/objects/4512/assignment-orders/1/artifacts/order`. Listener readiness is not an HTTP request, and there is no probe, retry, or follow-up request.

4. **Traceability and independent oracles are unaffected.** The isolated response expectation remains the literal section 4 tuple `503 Service unavailable.\n` plus `Retry-After: 60`, including inherited security/redaction assertions. Before-state comes from public `InstallationProcess` and `AssignmentOrderArtifactService`; after restoration, a fresh connection/process/service compares the complete public projection and both artifacts' bytes and metadata. No private row, filesystem detail, exception, or production output supplies an expected value. The complete main journey and representative rejection groups remain unchanged from the previously reviewed test.

5. **Sensitivity is genuine on the named rollback.** With specification `d211c92`, reviewed test `bba5c28`, and production at rollback `4591805`, setup, public preparation, direct EACCES proof, server startup, routing, authentication, and download dispatch succeed. The sole artifact request returns the predecessor non-enumerating `404`; the test requires the specified store-I/O `503` and fails at that exact classification assertion.

6. **Determinism, isolation, and cleanup satisfy Gate 2/3.** The task-owned file and database are unique per run; exact fixture inputs and expected values are specification literals; the test runs on the required non-root environment; nested `finally` blocks cover server stop, mode restoration, connections, database/user removal, and task-owned artifact cleanup. The failure is not caused by broken setup.

## RED verification

Command run in the shared feature workspace at reviewed test commit `bba5c28`, whose production file is byte-identical to missing-behavior baseline `4591805`:

```text
$ php tests/InstallationProcess/pilot_e2e_flow_001_test.php
PHP Fatal error: Uncaught TestFailure: isolated artifact-store read-time EACCES redacted 503
Expected: [503, "Service unavailable.\n", "60"]
Actual:   [404, "Not found.\n", null]
at tests/InstallationProcess/pilot_e2e_flow_001_test.php:35
called from tests/InstallationProcess/pilot_e2e_flow_001_test.php:40
exit code: 255
```

`app/PilotHttp/PilotE2ECoordinator.php` has SHA-256 `e0a4f0b82a5a87bbef39f27576ae4804e5c32302851aac4241614eb40ca6a0d2` both at `4591805` and in the reviewed workspace.

## SHA-256 reviewed-input manifest

```text
2c9ae79f73e5a3bf8d93c81fad3f431bd810a5d63c2648fa7dfab16f646839ab  specs/PILOT-E2E-FLOW-001.md
905eefecf098df2fbfd79d628008670a35fa00e6570b147a44557ef593411834  tests/InstallationProcess/pilot_e2e_flow_001_test.php
```

Git blob identities:

```text
2b3404a1564df3b1c1259058dd4a704841adb53a  specs/PILOT-E2E-FLOW-001.md
6b6b623cddb1d1b3be22b5a774d803dc6a200122  tests/InstallationProcess/pilot_e2e_flow_001_test.php
```

Any byte change to either reviewed input invalidates this verdict. The review record is excluded from the self-referential manifest.

## Required changes

None.

Gate 3 is approved for test commit `bba5c28`. Gate 4 may proceed only against the reviewed input manifest above.
