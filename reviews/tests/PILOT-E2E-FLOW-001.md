# Test review: PILOT-E2E-FLOW-001 v0.4

- Gate: 3 — fresh independent review
- Reviewer: separately tasked agent `/root/e2e_test_review_v13`
- Test author: separately tasked Gate 2 agent `/root/e2e_tests`; reviewer authored neither reviewed input
- Specification commit: `d211c92eea2e4980e6ebee5c2765d677cce76f14`
- Test commit / reviewed artifact: `7daac263c9ef90b36b80682c1389fd03a50358a2`
- Missing-behavior baseline: `5558fc3` (digest EACCES fixed; shard-traversal EACCES still misclassified)
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

## Fresh review of test commit `7daac26`

### Findings

1. **Two genuinely separate fixtures cover the two fault positions.** `pefIsolatedArtifactFault(...)` is invoked once with `shard` and once with `digest`. Every invocation creates a new random database name, database user, task-owned artifact root, production process composition and prepared order. No fixture state or fault target is shared between the invocations.

2. **Each fixture creates exactly one fault and sends exactly one request.** The digest branch changes only the exact regular content-addressed blob to mode `0000`; the shard branch changes only its exact real parent directory to mode `0000`. Each branch independently establishes existence/type/non-symlink and non-root execution, proves loss of access after the successful `chmod`, then starts one dedicated composition and issues one `GET` to the exact artifact URL. Listener readiness is not HTTP traffic; there is no probe, retry, warm-up or follow-up request.

3. **Restoration and public no-mutation oracles are symmetric.** In both branches the fault creation, access precondition, startup, sole request and response assertion are inside the same `try`, whose `finally` stops the server and restores the exact original mode. Only after restoration does a fresh connection instantiate a new public production process and public `AssignmentOrderArtifactService`; it compares the complete before projection and both artifacts' exact public results. Outer cleanup remains limited to each unique database/user and task-owned artifact root.

4. **Shard traversal is deterministic and cannot collapse into a missing-file fixture during setup.** Before mutation, `lstat` requires the exact shard target to be a real directory and not a symlink. A successful `chmod(..., 0000)` followed by cache-cleared `stat` of the already-established digest must lose traversal access under the asserted non-root runtime. The digest branch analogously opens the already-established exact regular file. These checks happen before HTTP composition starts and are setup facts, not expected response oracles.

5. **The RED is genuine and narrowly classifies `404 → 503`.** At reviewed commit `7daac26`, the new shard-first test reaches its exact response assertion and receives predecessor behavior `[404, "Not found.\n", null]` where section 4 requires `[503, "Service unavailable.\n", "60"]`. Re-running a temporary review-only invocation order with `digest` first passed the complete digest fixture—including restoration and fresh public projection/artifact comparisons—then failed at the same shard assertion. Thus the existing digest EACCES behavior is preserved and the new RED is specifically the unresolved shard-traversal outage classification, not broken setup.

6. **Traceability, sensitivity and oracle independence remain sound.** Both fault responses use the literal v0.4 public tuple and inherited redaction/security headers. Expected process state and artifact results come only from the public before seam, never private tables, filesystem contents, exceptions or current HTTP output. The rest of the approved main journey is byte-unchanged from the prior reviewed test.

### RED verification

Canonical reviewed command at `7daac26`:

```text
$ php tests/InstallationProcess/pilot_e2e_flow_001_test.php
PHP Fatal error: Uncaught TestFailure: isolated artifact-store shard traversal EACCES redacted 503
Expected: [503, "Service unavailable.\n", "60"]
Actual:   [404, "Not found.\n", null]
at tests/InstallationProcess/pilot_e2e_flow_001_test.php:35
called from tests/InstallationProcess/pilot_e2e_flow_001_test.php:40
exit code: 255
```

Independent sensitivity check in a detached task worktree changed only fixture invocation order to `digest` then `shard`. It passed the complete digest fixture and reached the same shard-only RED shown above. The temporary edit was restored and the worktree removed and pruned.

### SHA-256 reviewed-input manifest

```text
2c9ae79f73e5a3bf8d93c81fad3f431bd810a5d63c2648fa7dfab16f646839ab  specs/PILOT-E2E-FLOW-001.md
2929830a6808fac914557b75b9689f13f2b9ae95beb69dfc3ed8d71887ad3a14  tests/InstallationProcess/pilot_e2e_flow_001_test.php
```

Git blob identities:

```text
2b3404a1564df3b1c1259058dd4a704841adb53a  specs/PILOT-E2E-FLOW-001.md
38b42d3a49fe9e5249f7db03454ddf88dfe417ab  tests/InstallationProcess/pilot_e2e_flow_001_test.php
```

Any byte change to either reviewed input invalidates this verdict. The review record is excluded from the self-referential manifest.

### Required changes

None.

Gate 3 is `APPROVED` for test commit `7daac26`. Gate 4 may proceed only against the reviewed input manifest above.
