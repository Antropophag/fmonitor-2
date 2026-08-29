# Test review: PILOT-E2E-FLOW-001 v0.2

- Gate: 3 — fresh independent review after corrected artifact oracle
- Reviewer: separately tasked agent `/root/e2e_test_review_v2`
- Test author: separately tasked Gate 2 author; reviewer authored neither reviewed input
- Independence: expected artifact values were recomputed from the approved literal `DOCUMENT-RENDER-HTML-001 v0.2` templates and the fixed E2E input, never from production renderer output, persisted blobs, or process metadata
- Specification commit: `236a9eb9d51d1ebd88c852125bfd05051e49162e`
- Test commit / reviewed artifact: `6446cb9e430456c8cec5a34505384bcdfe3d7524`
- Specification: `specs/PILOT-E2E-FLOW-001.md`, version `0.2`, `APPROVED`
- Test: `tests/InstallationProcess/pilot_e2e_flow_001_test.php`
- Public seam: configured production raw HTTP under `/pilot`, isolated MariaDB `fm2_*` state, and production artifact store
- Date: `2026-08-29`
- Verdict: `APPROVED`

## Findings

None.

## Review assessment

- **Traceability:** the test cites the exact approved v0.2 specification commit and continues to exercise A–G through the configured production HTTP seam: queue → card → composition → prepare → two downloads → manual 1C registration → opening → durable refreshed card/queue and engineer next step.
- **Seam choice:** commands and downloads cross the real production HTTP entry point. The fixture uses isolated MariaDB production migrations, real legacy identity/object rows, real `fm2_*` persistence, and the production artifact store; no runtime mock or private renderer seam supplies observations.
- **Sensitivity:** exact routing/method, identity/capability, CSRF/body, PRG/error, concurrency, queue/card, artifact GET/HEAD/integrity, immutable registration/opening, audit order, task absence, durable engineer/composition, process state, and legacy no-write assertions remain present. The only executable-test changes from the previously reviewed artifact are the v0.2 traceability comment, two exact artifact size/hash tuples, and the corresponding content-addressed integrity-test path.
- **Expected-value independence:** independently replacing the single approved renderer-example occurrence `Петров Пётр Петрович` with the fixed E2E engineer `Анна Волкова` in each literal UTF-8/LF template produces exact `(1078, 7940150eaea4b749f2f80997f98e159ceac12c3d6ca2fca2fa5f847a689fee06)` and `(1247, 966227fba7d9acc15b39d06850fced300436856d31fbe614cad5f4397a923b01)`. Each predecessor template contains exactly one occurrence, retains its final LF, and shrinks by exactly 15 bytes. These values exactly match both v0.2 spec and test literals.
- **Rejected cases:** coverage is unchanged for malformed media type, Origin/CSRF, missing/invalid composition, stale prepare/registration/opening, invalid registration/open date, wrong methods/noncanonical routes, unavailable/corrupt artifacts, authorization/authentication, and redacted infrastructure failure. The corrected content-addressed order path preserves the integrity-failure probe against the newly specified blob.
- **Determinism and isolation:** fixed clocks/IDs/input, random bounded database/user/artifact root, real fresh connections, and outer `finally` cleanup remain unchanged. The clean detached run left the worktree clean, no task artifact entry, and no pilot server.

## Corrected blocker verification

1. Both artifact oracles now correspond to the fixed E2E engineer `73 / Анна Волкова`, rather than metadata copied from the renderer tracer's different engineer snapshot.
2. The order oracle is exactly `1078` bytes / `7940150eaea4b749f2f80997f98e159ceac12c3d6ca2fca2fa5f847a689fee06`.
3. The appendix oracle is exactly `1247` bytes / `966227fba7d9acc15b39d06850fced300436856d31fbe614cad5f4397a923b01`.
4. The corruption test targets the corrected order content address `sha256/79/40/7940150e...`; no obsolete hash remains in the changed test lines.
5. Outside the specification citation, the two oracle tuples, and the matching content-addressed corruption path, the reviewed test is byte-for-byte unchanged from the previously approved test commit `4a9a8f90c4468ec213e72ff758cdebc6be8f8b0b`.

## RED verification

Command run in a clean detached worktree at exact commit `6446cb9e430456c8cec5a34505384bcdfe3d7524`:

```text
$ php tests/InstallationProcess/pilot_e2e_flow_001_test.php
PHP Fatal error: Uncaught TestFailure: production migration permits exact capability assignment_order.confirm_registration
Expected: true
Actual: false
at tests/InstallationProcess/pilot_e2e_flow_001_test.php:43
exit code: 255
```

The test creates its isolated database and applies the production migrations before failing at the first missing behavior: the production capability constraint does not yet permit `assignment_order.confirm_registration`. This is a clean, intentional RED rather than a setup, dependency, dirty-worktree, or oracle failure.

## SHA-256 reviewed-input manifest

```text
980bc3a522738fab7083d352d662f92625427295e73a44cafc10de1d31c0b0bb  specs/PILOT-E2E-FLOW-001.md
0210e4a5241173f8b67b3996c7db9af80609001682598cf86035bd0af02a1115  tests/InstallationProcess/pilot_e2e_flow_001_test.php
```

Git blob identities:

```text
1efd59fbf6904992c6859afe0e0845545a7d6e0d  specs/PILOT-E2E-FLOW-001.md
aed68ba55b9badf395bcfa84da3a8c346e6c24ed  tests/InstallationProcess/pilot_e2e_flow_001_test.php
```

Any byte change to either reviewed input invalidates this approval. The review record is excluded from the self-referential manifest.

## Required changes

None. Gate 3 is approved for test commit `6446cb9e430456c8cec5a34505384bcdfe3d7524`; Gate 4 may proceed only against these exact reviewed inputs.
