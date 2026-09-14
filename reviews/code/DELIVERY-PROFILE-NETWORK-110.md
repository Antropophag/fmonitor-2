# Code review: DELIVERY-PROFILE-NETWORK-110

- Reviewer: independent Gate 5 agent `/root/gate5_profile_network`; authored none
  of the reviewed specification, OpenSpec, tests, implementation, or evidence.
- Review package:
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T183935Z-7df6386a92/package.json`.
- Reviewed source: base `11b8587372040ab045d1a69faeeb1432ff85e400`
  plus retained snapshot `snapshot/source.patch`, verified SHA-256
  `e5e7262460453836ecab21fa403b83a209fbc56b67f4db8a5f708d973c5febde`;
  package candidate source
  `be6e3f1a92fba088c9fa459bc5b9b362e0e2faf7f194b4dcaf051c003c012a46`
  and executable source
  `2f796724f9c8c3d654551bdcfcd15e7e266fca1f171352e6606b6acd9a9a0b1f`.
- Normative contract: `specs/DELIVERY-PROFILE-NETWORK-110.md`; OpenSpec
  `connect-focused-profiles-to-test-services`; public seam
  `tools/delivery/run-in-profile <profile> <command> [args...]`.
- Verdict: `CHANGES_REQUESTED`.

## Blocking finding

1. **BLOCKER — Gate 4 has no recorded owner action authorization.** The exact
   package/harness state reports `action_authorized: false`. The final Gate 3
   history explicitly states that its approval does not authorize implementation
   and that root must record current owner authorization before dispatching Gate
   4. Nevertheless, the package contains the implemented launcher delta and Gate
   4 GREEN records. Repository policy requires preserving authorization, and an
   absent authorization record cannot be inferred from successful execution or
   from this review. Root must bind/record the owner's authorization for this
   narrowed prerequisite in the delivery harness, then prepare an exact-source
   Gate 5 package. No implementation correction is requested by this finding.

## Spec axis

No implementation-conformance finding was found. The 23-line launcher delta is
below the 100 infrastructure LOC guard and is limited to the declared public
seam. For `integration` and `browser` it reads `networks.default.name` from
canonical `docker compose -f compose.test.yaml config --format json`, checks that
the network already exists, and conditionally adds only that network plus
`FMONITOR_TEST_DB_HOST=test-db` and `FMONITOR_TEST_DB_PORT=3306`. It does not
construct the Compose network name, create/start/reset/migrate/tear down any
resource, or change `governance` behavior. Existing argv and child exit-code
handling remain unchanged.

No Git metadata mount, Docker socket, Docker CLI/tooling inside a profile,
Compose/Make topology change, harness change, planner/selection/aggregation/
inventory/Quality Graph change, PR B, or blanket category adoption is present.
The current behavioral test hash
`5fc44dc9d9db75055acf7f0f9f5bb012fa9e255d03b8296112637f0e378cf0bc`
matches the previously approved networking Gate 3 package
`20260913T172616Z-36d9f44ce5`. Later Git-metadata review entries are preserved
history of a superseded excursion; the final owner-narrowed contract and current
candidate explicitly exclude that scope.

## Standards axis

No documented-standard violation was found. `git diff --check` and
`bash -n tools/delivery/run-in-profile` pass. The launcher addition is cohesive and does
not exhibit a material Fowler smell. A non-blocking **Divergent Change / fixture
complexity** observation applies to
`tests/Verification/quality_graph_ci_setup_001_test.php:157`: the registered
contract test now owns several Docker scenarios and cleanup paths. They are all
required by this acceptance slice, so extraction is not requested here; further
profile-network behavior should use a named helper or dedicated registered test
rather than grow this procedural block.

## Verification evidence

All three required focused checks are GREEN on the same executable source
`2f796724f9c8c3d654551bdcfcd15e7e266fca1f171352e6606b6acd9a9a0b1f`:

- `php tests/Verification/quality_graph_ci_setup_001_test.php` — record
  `1789324594583345000-c728f67e1adb424e8f90b1ea80b30a30`;
- `python3 tests/Verification/change_verification_001_test.py` — record
  `1789324706036178000-d7210be4821a443ba142b1d4bdc43654`;
- `php tests/Runtime/runtime_storage_001_test.php` — record
  `1789324731421147000-97d08c04d765416b88d3f272531b865a`.

The behavioral check covers an atypical Compose-declared network name, absent
network, stopped service, governance non-injection, real `mysqli SELECT 1`
through both profiles, retained argv/exit semantics, and verified cleanup.

Records `1789322336496696000-37f17af0baa340c588333734d6462101`
and `1789323956397204000-32bb360992584c5fb87b658eb425dd24`
are `REGRESSION_FAILURE` results from rejected full-category consumers. They are
explicit negative evidence supporting rejection of blanket category adoption;
they are not acceptance GREEN and are not counted as such.

Authoritative exact-source full Quality Graph CI, PR, publication, and deployment
remain `UNKNOWN`. This review does not authorize any of them.

## Authorization correction rereview — package `20260913T184620Z-7516567b90`

- Reviewer: independent Gate 5 agent `/root/gate5_profile_network`; authored none
  of the reviewed artifacts or evidence.
- Reviewed correction: retained snapshot
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T184620Z-7516567b90/snapshot/source.patch`,
  verified SHA-256
  `a43a82fc51e0e9f3eb9cb2b5befa52cb33d53173c3de3b665068e4a574f82865`;
  candidate source
  `cb375935ce1e26d02a4aa2d94e7b2fea790037a840d9e8ca65a3566b2afbf12a`
  and executable source
  `fbbf4625d2cd9defb161caf5935d73ae25d2dbd011bd0e1e37c8cdcb2b66168a`.
- Verdict: `APPROVED`.

### Prior finding disposition

1. **RESOLVED — owner action authorization is now retained and bound.**
   `openspec/changes/connect-focused-profiles-to-test-services/owner-authorization.md`
   records the owner's pre-implementation authorization for the Gate 4 executor,
   subsequent autonomous delivery through a separate prerequisite PR, and the
   final narrowed networking scope after consumer verification. The authorization
   artifact is now an explicit planned path in `verification-input.json` and is
   hashed by the correction package. It permits only the reviewed launcher seam,
   caps infrastructure code at 100 LOC, excludes Git metadata, Docker socket/CLI
   expansion, lifecycle ownership, PR B, Quality Graph/planner/selection/
   aggregation/inventory/harness changes, and forbids autonomous merge.

   Harness still reports live `action_authorized: false` together with
   `live_github_unavailable`; the retained authorization explicitly distinguishes
   that unavailable live publication-admission field from owner authorization.
   It does not claim the field as GREEN or use it to authorize merge. This closes
   the sole process blocker without weakening fail-closed publication state.

### Correction boundary and evidence

The package delta from the previous review contains only the new authorization
artifact, its verification-input binding, and the preserved initial Gate 5 review
record. The launcher and behavioral test are byte-for-byte unchanged; therefore
the prior Spec and Standards axes remain approved, including the non-blocking
fixture-complexity observation.

All required focused checks were rerun GREEN on exact executable source
`fbbf4625d2cd9defb161caf5935d73ae25d2dbd011bd0e1e37c8cdcb2b66168a`:

- `php tests/Verification/quality_graph_ci_setup_001_test.php` — record
  `1789325035981464000-f2d9a02014fd47b9b6713a974c130671`;
- `python3 tests/Verification/change_verification_001_test.py` — record
  `1789325144284857000-2a3819d8d9704f3790388204a394c66c`;
- `php tests/Runtime/runtime_storage_001_test.php` — record
  `1789325168691817000-003c242cf92948a291a57ec32d8d1380`.

No blocking findings remain. The rejected full-category regression records remain
negative scope evidence only. PR state, authoritative exact-source Quality Graph
CI, merge readiness, publication, and deployment remain `UNKNOWN`; this approval
does not promote any of them to GREEN and does not authorize merge.

## Post-rebase exact-source rereview — package `20260913T185024Z-d0985778d9`

- Reviewer: independent Gate 5 agent `/root/gate5_profile_network`; authored none
  of the reviewed artifacts, implementation, rebase, or evidence.
- Reviewed committed source: base
  `e5a420e0b52162bde19c7d527c3fb1c57eb9f40a` (merged PR #124) and candidate
  `c57bfaf15f382dafae3e59c8747ad961229a4759`; package candidate source
  `7c7f8c520b68964da3b8743024457d518849b50e75d4c370298d81e8ce5373b8`
  and executable source
  `542916bad74fcbf2842e9af49aa0e019b5b49b6c6ede8bb0368577c3feb13d13`.
  The retained snapshot patch is correctly empty for the clean committed
  candidate (SHA-256
  `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855`).
- Verdict: `APPROVED`.

### Rebase interaction review

The candidate remains one commit ahead of new `origin/main` and its production
delta is still exactly 23 added lines in `tools/delivery/run-in-profile`; the
behavioral test remains exactly 127 added lines. Their content hashes are
unchanged from the prior approved technical candidate. The only expected
repository-owned post-approval artifact changes are the completed Gate 5 task
checkbox and the appended review history.

Upstream PR #124 adds the Yii2 stand-restore control and registers its checks in
`tests/Verification/verification_ci_001_test.py`,
`tools/verification/categories.json`, and `tools/verification/suites.tsv`. It
does not change `compose.test.yaml`, `tools/delivery/run-in-profile`, or
`tests/Verification/quality_graph_ci_setup_001_test.php`. The regenerated plan
continues to require the applicable `governance` and `integration` categories,
and the change-verification check is GREEN against the expanded upstream
inventory. No semantic, lifecycle, network, profile, selection, or aggregation
interaction was found.

All three required focused checks were rerun GREEN on exact executable source
`542916bad74fcbf2842e9af49aa0e019b5b49b6c6ede8bb0368577c3feb13d13`:

- `php tests/Verification/quality_graph_ci_setup_001_test.php` — record
  `1789325301764498000-287dd861f30043a1ba23dffcd57ac584`;
- `python3 tests/Verification/change_verification_001_test.py` — record
  `1789325389687265000-16e286cc27a6466399541ad894b162ca`;
- `php tests/Runtime/runtime_storage_001_test.php` — record
  `1789325412452200000-d8ecc05874dc4c2090458b22cc911f16`.

No blocker was introduced by the rebase. The prior technical and authorization
approvals remain valid. Live PR/CI/publication/deployment state remains
`UNKNOWN`; this exact-source Gate 5 approval neither declares authoritative
Quality Graph GREEN nor authorizes merge.

## Post-PR125 fixture correction review — package `20260913T191146Z-e9f056010d`

- Reviewer: independent Gate 5 agent `/root/gate5_profile_network`; authored none
  of the reviewed implementation, test correction, Gate 3 decision, or evidence.
- Reviewed source: committed PR #125 head
  `885d30dea885658d0256feb2b35d0a8828ab4392` plus retained correction snapshot
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T191146Z-e9f056010d/snapshot/source.patch`,
  verified SHA-256
  `9dc05a3591f5d3c72dd36189e177daea8949062e3588801c4b8add6872e17db9`;
  package candidate source
  `8f7837e027e7fb54fa9ea8b7ad01d494f7f6c1b0b2f0d071d4159d256952d5b8`
  and executable source
  `a729a5d3c30e45beb42f52e446dba4d5ef003dd698d3f84fbab422fb72073170`.
- Verdict: `APPROVED`.

### Correction review

The executable delta is test-only: 11 additions and one deletion in
`tests/Verification/quality_graph_ci_setup_001_test.php`. The fixture now asks
the OS for an ephemeral loopback port, validates the returned numeric endpoint,
closes the reservation, and passes that host port as `FMONITOR_TEST_DB_PORT` in
the same environment already used consistently by Compose config/create/up/
inspect/down. Container-side acceptance remains exactly `test-db:3306`.

This correction prevents the randomized nested Compose project from predictably
colliding with the outer canonical DB on host port `23306`, without changing the
launcher, normative specification, Compose/Make topology, lifecycle ownership,
profile route, or forbidden-scope boundaries. The small close-before-Compose-bind
race can cause a visible setup failure but cannot produce a false GREEN; the
previously approved `finally` cleanup remains in force.

The independent Gate 3 correction review appended after package preparation is
`APPROVED` and names the same package, snapshot digest, candidate/executable
sources, and behavioral record. Its later documentary bytes are expected review
history rather than an unreviewed executable change. No test expectation was
weakened.

The Quality Graph run `34775953385` on exact prior head
`885d30dea885658d0256feb2b35d0a8828ab4392` remains `FAILURE`: its Integration
(1/2) failure was the nested fixture's occupied host port, not a networking
contract failure. It is retained as historical failure and is not acceptance
GREEN.

All required focused checks are GREEN on exact executable source
`a729a5d3c30e45beb42f52e446dba4d5ef003dd698d3f84fbab422fb72073170`:

- `php tests/Verification/quality_graph_ci_setup_001_test.php` — record
  `1789326462814596000-edc3ae02abba4b8ab7b6a42a2016f4cf`;
- `python3 tests/Verification/change_verification_001_test.py` — record
  `1789326659293133000-aa26b094f21f4b4ba876290808aad4a9`;
- `php tests/Runtime/runtime_storage_001_test.php` — record
  `1789326691256565000-d817f387e9084a37954e1cead3281e73`.

No blocking findings remain in the correction. PR #125 still requires a new
authoritative exact-source Quality Graph run. Current CI is `FAILURE`,
`merge_ready` is false, and no historical run is promoted to GREEN; this review
does not authorize merge or deployment.
