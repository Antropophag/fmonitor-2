# Test review: DELIVERY-PROFILE-NETWORK-110

- Reviewer: independent Gate 3 agent `/root/gate3_profile_network`; authored none of the reviewed spec, OpenSpec, test, or intended-RED evidence.
- Test author: root delivery agent (per package/task authorship declaration).
- Reviewed source: base `11b8587372040ab045d1a69faeeb1432ff85e400` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T170237Z-c887bf0f80/snapshot/source.patch`, SHA-256 `ee32a7bdad73b44e8825263550638bf251945084ed54af428376e86891f09f6b`; package candidate source `a7ad6c7c2a08d4011e8b4b06a408b8d5a537abb82d7df6d12758875b9271f5b8`, executable source `aba1cc1b9c0e128573b4a91bc49c055d6205ebc67cabf8572192c43900a22a70`.
- Agreed review scope / prior findings disposition: first review; issue #110 prerequisite `DELIVERY-PROFILE-NETWORK-110` only. PR B, Quality Graph/planner/selection/inventory changes, Compose/Make topology changes, product/domain behavior, and implementation are forbidden scope.
- Specification: `specs/DELIVERY-PROFILE-NETWORK-110.md`; OpenSpec `connect-focused-profiles-to-test-services`.
- Public seam: `tools/delivery/run-in-profile <profile> <command> [args...]`.
- Red command and intended failure: `php tests/Verification/quality_graph_ci_setup_001_test.php`; retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789318799183414000-977c5a2922f241958bc9fca02eec9483.json`, exit 255 after the `integration` probe returned 20 because `test-db` was unresolved. The failure is sensitive to the missing route rather than setup: `make test-env-up`, Compose config parsing, network inspection, and running service inspection had already succeeded. The `finally` teardown ran successfully as evidenced by the retained end fixture `missing:.../ephemeral-test-db`.
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **BLOCKER — DPN110-01 is not sensitive to the required discovery rule.** `tests/Verification/quality_graph_ci_setup_001_test.php:163-190` reads the canonical declared name in the fixture, but the only launcher-facing assertion is successful DNS/`SELECT 1`. An implementation that hard-codes `fmonitor2-test_default` or manually constructs the current `<project>_default` name would pass, although both the normative spec and delta spec explicitly forbid that implementation and require consuming `docker compose ... config --format json`. This also leaves the claimed Docker Engine/Desktop portability unprotected. Add an observable test boundary that makes a declared network name differ from a guessed/project-derived name, or otherwise deterministically proves the launcher consumes the Compose declaration rather than a reconstructed constant.

2. **BLOCKER — DPN110-03 rejection and governance cases are absent.** The acceptance binding maps all `DPN110-01-04` to this single test, but `tests/Verification/quality_graph_ci_setup_001_test.php:159-197` exercises only the prepared-service success path. It does not invoke an `integration`/`browser` DB command with the canonical network or service absent and prove that the launcher neither creates nor starts resources and returns the child's availability failure. The earlier generic governance probe (`:108-154`) also does not assert that `FMONITOR_TEST_DB_HOST`/`FMONITOR_TEST_DB_PORT` are absent/unmodified or that no Compose-network dependency is introduced. Therefore a launcher that owns lifecycle on the missing path or injects the DB route into `governance` could pass. Add deterministic, cleanup-safe cases for missing network/service and governance non-dependence/non-injection.

3. **NON-BLOCKING — test-only credential is copied into retained argv evidence.** The inline probe at `tests/Verification/quality_graph_ci_setup_001_test.php:174-180` embeds the repository-local MariaDB root password in the command string, and `run-in-profile` records full argv. This is a documented disposable local credential rather than a production secret, so it does not independently block this gate, but using the least-privileged test account or an environment-based fixture would reduce credential propagation in retained logs.

Traceability is otherwise coherent: issue #110 is bound once through `verification-input.json`; plan hashes match the reviewed spec/test/input bytes; the plan includes governance, integration, and authoritative full-CI obligations; strict OpenSpec validation passes. The public seam and real `mysqli SELECT 1` are appropriate, expected DNS/port/query values come from the contract, argv/exit-code checks from PR A remain intact, and no forbidden production/domain or Compose/Make change is present in the package. The fixture owns service setup and uses `finally` teardown for probe/assertion failure after successful setup.

Harness state reports `action_authorized: false`; Gate 3 review is read-only, but implementation remains prohibited until the root obtains/records current owner authorization. This is governance state, not approval inferred by this review.

## Required changes

1. Make the behavioral test fail for hard-coded or project-derived network naming and pass only when the declared Compose network name is consumed, including the cross-platform case.
2. Cover absent network/service without launcher lifecycle ownership, and prove `governance` neither depends on the canonical network nor receives the test DB route.
3. Capture a new intended RED and prepare a new exact-source review package; do not implement before a subsequent independent Gate 3 `APPROVED` and recorded action authorization.

## Correction rereview — package `20260913T171420Z-f000841fd8`

- Reviewer: independent Gate 3 agent `/root/gate3_profile_network`; still authored none of the reviewed artifacts.
- Reviewed source: base `11b8587372040ab045d1a69faeeb1432ff85e400` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T171420Z-f000841fd8/snapshot/source.patch`, verified SHA-256 `70027a528782141b6f9d7df6410c633c112243561f83cabd975d67d82fa3b5c4`; candidate source `90b308912b37198706e474eeee670f03ddc372f2f4d01ad6af9880c6d8ed0a0e`, executable source `aa5ccd5c749fe20dbe9973784d1351bb73312cdbc8201f9073e35fccb2c15c1f`.
- Review boundary: only the correction delta from previous package `20260913T170237Z-c887bf0f80` and disposition of findings 1–3 above.
- Fresh RED: `php tests/Verification/quality_graph_ci_setup_001_test.php`, retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789319428123177000-5527efd20a244c30847783cd54c80c54.json`, exit 255 at the prepared-network integration probe because `test-db` remained unresolved (exit 20). Earlier missing-network, governance, and stopped-service assertions completed before that intended failure. The randomized Compose resources and probe file were cleaned in `finally`; retained end source matches the package source and the Docker fixture is absent after the run.
- Verdict: `CHANGES_REQUESTED`

### Findings disposition

1. **BLOCKER REMAINS — randomized project name defeats a fixed canonical hardcode, but not manual project-name construction.** The new test sets `COMPOSE_PROJECT_NAME=qcsnet<random>` and asserts only that the resolved name does not contain `fmonitor2-test`. Docker Compose resolves this fixture to exactly `qcsnet<random>_default`; this was independently confirmed with `docker compose -f compose.test.yaml config --format json`. Consequently an implementation that reads `COMPOSE_PROJECT_NAME` and manually appends `_default` passes every new assertion, while DPN110-01 explicitly says the launcher MUST read the name from Compose JSON and MUST NOT construct `<project>_default`. The correction therefore does not yet provide the requested sensitivity to manual naming or the portability risk that motivated the rule. Use a fixture whose declared network name is deliberately different from both the repository default and `<project>_default` (for example a controlled Compose override supplied through an observable test boundary), or another deterministic interception that proves the launcher consumes the JSON field.

2. **RESOLVED — missing lifecycle and governance behavior.** The correction invokes `governance` while the randomized canonical network is absent and requires both successful execution and absent DB-route variables. It invokes `integration` with the network absent, verifies no network was created, then externally creates a stopped service/network, invokes the profile probe, verifies the command reports unavailability, and proves the container remains stopped. This would catch launcher creation/start ownership and governance route injection/dependence.

3. **RESOLVED — retained-argv credential propagation.** The DB probe is now a temporary repository-relative file and uses the least-privileged disposable test account. The compact launcher evidence records only the probe path, not the credential-bearing PHP source; the file is removed in `finally`.

Cleanup structure is adequate for the corrected lifecycle: ownership is set immediately after successful external `compose create`, and all later failure paths execute `make test-env-down` with the same randomized environment before removing the temporary probe. The new RED remains an intended missing-route failure rather than setup failure. No implementation or forbidden-scope file was added to the correction delta.

### Required correction

Make DPN110-01 observably distinguish Compose-declared name discovery from both a fixed `fmonitor2-test_default` hardcode and construction of `${COMPOSE_PROJECT_NAME}_default`; capture a fresh intended RED and prepare a new bounded rereview package. Implementation remains prohibited because Gate 3 is not approved and harness still reports `action_authorized: false`.

## Third Gate 3 review — package `20260913T171955Z-2dfed3c5c5`

- Reviewer: independent Gate 3 agent `/root/gate3_profile_network`; authored none of the reviewed artifacts.
- Reviewed source: base `11b8587372040ab045d1a69faeeb1432ff85e400` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T171955Z-2dfed3c5c5/snapshot/source.patch`, verified SHA-256 `6e0a6dbffa86a145daf692f527cb6c1c6724230213c12ad9f4fc732b8dcc6e46`; candidate source `7bc1921a9be9f5754be322cde3ea01a58b0e98c5a81ee1104519dc4cb48cf5ba`, executable source `dc74d170a9d9f37f4b189bade614678024dae7ad6cea72e858f76c76ea733341`.
- Review boundary: complete prior findings disposition plus the test-only delta from package `20260913T171420Z-f000841fd8`; no implementation is present.
- Fresh RED: retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789319815733190000-f9226be41dd94a55998928ba71d02d83.json`, exit 255 at the new DPN110-01 declared-network probe. The old launcher returned 20 because it did not consume the spy's declared Compose JSON network. Candidate/executable sources match the package, and no randomized container, network, or probe file remained after the observed run.
- Verdict: `CHANGES_REQUESTED`

### Findings disposition

1. **RESOLVED — discovery is now behaviorally sensitive.** The fixture creates an atypically named network independent of both `fmonitor2-test_default` and `${COMPOSE_PROJECT_NAME}_default`, attaches the running service to it with only the `test-db` alias, and disconnects the derived default route. A PATH-scoped Docker spy changes only Compose `config` JSON to declare that atypical name. Thus fixed hardcode and project-name derivation cannot reach the service, while a launcher that consumes `networks.default.name` can. The fresh RED occurs at exactly this boundary. The spy delegates build, inspect, run, and lifecycle commands to the real Docker binary, so it does not replace the public launcher seam or fake the DB result.

2. **RESOLVED — lifecycle non-ownership and governance coverage remain intact.** The previous correction's absent-network, stopped-service non-start, governance no-env/no-network, real `mysqli SELECT 1`, argv, and exit semantics cases are unchanged.

3. **RESOLVED — sensitivity of retained evidence remains adequate.** The least-privileged credential stays inside the temporary probe file rather than compact argv evidence, and the new retained failure identifies missing declared-route behavior rather than setup failure.

4. **BLOCKER — the new Docker resource cleanup is best-effort and its result is not verified.** In `tests/Verification/quality_graph_ci_setup_001_test.php:260-265`, the `finally` block invokes `docker network disconnect --force` and `docker network rm` for the atypical sensitivity network but discards both exit codes. If disconnect or removal fails, the test can still pass after implementation while leaking the randomized network; `make test-env-down` only owns the randomized Compose project and does not prove removal of this separately created network. This contradicts the requested cleanup guarantee, the design's isolation requirement, and Gate 2 determinism. The observed RED run happened to leave no resource, but one successful cleanup observation does not make future cleanup failures visible.

The retained snapshot, plan hashes, exact test hash, strict OpenSpec validation, and forbidden-scope boundary are otherwise coherent. The plan continues to require focused governance/integration checks and one authoritative full CI run. No product state, domain lifecycle, Compose/Make topology, inventory, Quality Graph, planner, PR B, or implementation change is included.

### Required correction

In `finally`, capture and assert successful cleanup of the separately created atypical network (and any required disconnect), while still ensuring Compose teardown and probe-file removal execute even if an earlier cleanup step fails. Capture a fresh intended RED and prepare the bounded package for final rereview. Harness still reports `action_authorized: false`; implementation remains prohibited independently of this Gate 3 verdict.

## Final cleanup correction rereview — package `20260913T172616Z-36d9f44ce5`

- Reviewer: independent Gate 3 agent `/root/gate3_profile_network`; authored none of the reviewed artifacts.
- Reviewed source: base `11b8587372040ab045d1a69faeeb1432ff85e400` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T172616Z-36d9f44ce5/snapshot/source.patch`, verified SHA-256 `345f1e7ddde7752541dbd2938322a851a08e37ba7ebeb251b83750bd1f9bee0b`; candidate source `1691417404bfed091ef908288ad14d2f67a52d37f87706dae88db961d9caa8c6`, executable source `906aaeecdcd33ed06b2d9c620e2063bf9f6aaa8972e375da90bf3ef671831e4d`.
- Review boundary: cleanup-only delta from package `20260913T171955Z-2dfed3c5c5`, plus confirmation that all prior findings remain resolved.
- Fresh RED: `php tests/Verification/quality_graph_ci_setup_001_test.php`; retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789320131761398000-62c0cc4be0e5434f8b47d730b69c4a44.json`, exit 255 at DPN110-01 because the unimplemented launcher did not consume the atypical network declared by Compose JSON. Candidate/executable sources match the package. No randomized network, container, or `.local/qcs-db-probe-*` file remained after the run.
- Verdict: `APPROVED`

### Findings disposition

1. **RESOLVED — cleanup failures are now observable without skipping later cleanup.** The `finally` block independently attempts declared-network disconnect, declared-network removal, and external Compose teardown; it accumulates every non-zero exit instead of asserting inline. It then unconditionally attempts probe-file removal and only afterward asserts that the accumulated Docker cleanup failures are empty. Thus a disconnect failure does not skip network removal/down/unlink, a network-removal failure does not skip down/unlink, and a Compose teardown failure cannot be silently accepted. The fresh intended-RED run exercised this `finally` path and left every owned resource absent.

2. **RESOLVED — all earlier Gate 3 findings remain closed.** The atypical declared-network spy distinguishes JSON consumption from fixed or project-derived names; missing-network and stopped-service paths preserve lifecycle non-ownership; governance has neither DB-route injection nor network dependency; real integration/browser probes cover DNS, TCP, and `mysqli SELECT 1`; argv/exit semantics remain covered; the credential-bearing probe body is not retained in launcher argv evidence.

No blocking findings remain for `DELIVERY-PROFILE-NETWORK-110`. Traceability, seam choice, sensitivity, expected values, rejection behavior, determinism, isolation, cross-platform naming risk, lifecycle non-ownership, governance independence, and forbidden scope are adequately covered. Strict OpenSpec validation passes, package hashes match the reviewed bytes, and the verification plan retains focused governance/integration obligations plus authoritative full CI.

Gate 3 approval permits progression under the delivery process but does not itself authorize implementation: harness state still reports `action_authorized: false`, so root must record current owner action authorization before dispatching Gate 4.

## Git metadata dependency correction review — package `20260913T174548Z-d7c7ff106a`

- Reviewer: independent Gate 3 agent `/root/gate3_profile_network`; authored none of the reviewed spec, OpenSpec, tests, networking implementation, or RED evidence.
- Reviewed source: base `11b8587372040ab045d1a69faeeb1432ff85e400` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T174548Z-d7c7ff106a/snapshot/source.patch`, verified SHA-256 `b543117b8684e4a7b5258b906cb55f6c5cc26ba0820e5f127ebbb78faf09ebea`; candidate source `2e532325eee21a9abe9b1e4b33aa9db796ea3fc6841204752f4819584e0bbe5b`, executable source `08a234bc753bf13d380f01d37fbf9fa6def43cd484c6539bdb8b51d8b3d9aab4`.
- Review boundary: the newly discovered existing-harness Git metadata dependency at the same `run-in-profile` seam. Launcher remains the only production path, its current total delta is 23 added lines (<100), and no harness, category runner, Compose/Make topology, inventory, planner, Quality Graph, product/domain, or PR B change is included.
- Fresh RED: `php tests/Verification/quality_graph_ci_setup_001_test.php`; retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789321437532297000-f3edf79c125746c1a7c8de5127194e95.json`, exit 255. The integration `git ls-files --error-unmatch tools/delivery/run-in-profile` probe returned 128 with `fatal: not a git repository: /Users/antropophag/code/fmonitor-2/.git/worktrees/fmonitor-2-issue110-pr-b`. This is the intended inaccessible linked-worktree gitdir failure, not a setup or network failure. Exact source and test/spec hashes match the package.
- Verdict: `CHANGES_REQUESTED`

### Findings

1. **BLOCKER — the test is not sensitive to the normative read-only security boundary.** The additions at `tests/Verification/quality_graph_ci_setup_001_test.php:157-165` only run `git ls-files`. A launcher that bind-mounts the Git common dir read-write, or exposes a broader writable host directory containing it, passes both integration and browser probes. Yet the normative spec, delta spec, and design all require read-only access and prohibit metadata modification/copying. Add a deterministic profile-side assertion that a harmless write/create attempt in the mounted Git metadata fails while `git ls-files` still succeeds; ensure the assertion cannot modify the actual checkout metadata if the implementation is wrong (for example, exercise a controlled disposable Git worktree/common-dir fixture through the public launcher seam). This is a security boundary, not an implementation detail.

2. **BLOCKER — main-checkout/relative common-dir portability is unspecified by the executable acceptance.** In this linked worktree, `git rev-parse --git-common-dir` returns the absolute path `/Users/antropophag/code/fmonitor-2/.git`, so the RED validates the external-pointer case. In an ordinary GitHub Actions checkout, the same command normally returns relative `.git`; passing that directly as Docker `--mount` source is invalid/non-portable, while the design explicitly promises an absolute actual path and equal Engine/Desktop behavior. The test does not distinguish canonicalization from using the raw relative result, so an implementation can pass locally and fail in the target CI topology. Bind the contract explicitly to an absolute path obtained/resolved through Git and add deterministic coverage for both linked-worktree external metadata and a normal checkout/relative Git result (a controlled Git/path spy or disposable fixture is sufficient).

### Confirmed properties

- The new readable-metadata RED is valid for the current linked worktree and both `integration` and `browser` are mapped to the same public seam.
- Mounting the actual common dir at the same absolute container path is a sound approach for the current macOS Docker Desktop linked-worktree pointer and for Linux absolute host paths, provided the source is canonicalized and mounted read-only.
- The requested metadata access is adjacent to the real category consumers and does not transfer fixture lifecycle ownership or justify harness changes. Read-only exposure limits mutation risk, although the common dir may expose repository config/refs to the test container; this is acceptable only at the narrow declared path and must not become a broad host mount.
- All previously approved networking, lifecycle, cleanup, governance, credential, argv, and exit-code cases remain unchanged. Strict OpenSpec validation and `git diff --check` pass; the verification plan still requires focused governance/integration checks and authoritative full CI.

### Required changes

1. Make the behavioral test fail for a writable or broader-than-declared Git metadata mount without risking the real repository, while retaining real `git ls-files` probes for both profiles.
2. Make the spec/test sensitive to absolute-path canonicalization for both linked worktrees and ordinary main checkouts.
3. Capture a fresh intended RED and prepare a bounded correction package for independent Gate 3 rereview. Do not change the harness or implement the Git mount before approval; harness currently also reports `action_authorized: false`.

## Git metadata portability/read-only rereview — package `20260913T175043Z-a9ba327bc6`

- Reviewer: independent Gate 3 agent `/root/gate3_profile_network`; authored none of the reviewed artifacts.
- Reviewed source: base `11b8587372040ab045d1a69faeeb1432ff85e400` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T175043Z-a9ba327bc6/snapshot/source.patch`, verified SHA-256 `8ea9366874ec98e612d84459a68df72f15ae77593cd3448abb91d29aba5895ba`; candidate source `9f97baa32bb99a54518f3a171e03078b671a6e80990f9a22f3aa3cca99f0f7bf`, executable source `bdd0f87127dc887250968ba43c8e84ac0c319ec2e64eae0b9d6c53f30dc5fb96`.
- Review boundary: spec/design and behavioral-test corrections for ordinary-checkout absolute canonicalization and read-only sensitivity; the linked-worktree probes and all previously approved networking cases are unchanged. No Git-mount implementation or harness change is present in this delta.
- Fresh RED: `php tests/Verification/quality_graph_ci_setup_001_test.php`; retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789321776675485000-619900c6556b4bc8b1e91cc03081f7e1.json`, exit 255 after the disposable ordinary-checkout command returned 1. Its absolute-path assertion succeeded, but `touch /workspace/.git/qcs-write-probe` also succeeded through the existing writable checkout mount, making the required negation fail. This is the intended missing nested read-only mount behavior. The disposable clone, including the harmless write probe, was removed by the test's outer `finally`.
- Verdict: `APPROVED`

### Findings disposition

1. **RESOLVED — read-only behavior is now sensitive without risking repository metadata.** The test clones the repository with `--no-hardlinks` into its already disposable `$tmp`, copies the exact candidate launcher into that standalone checkout, and invokes the public launcher seam. The container must still read Git metadata, while a write probe under its resolved common dir must fail. A wrong writable mount only mutates the disposable clone; `qcsRemove($tmp)` removes it on success or failure. This distinguishes the required nested read-only mount from the launcher's ordinary writable `/workspace` bind.

2. **RESOLVED — ordinary and linked checkout destinations are now explicit and covered.** The normative spec and delta require a relative ordinary-checkout `.git` to be canonicalized as an absolute bind source mounted at `/workspace/.git`, while a linked worktree's external common dir remains mounted at the absolute path referenced by its `.git` pointer. The standalone clone exercises ordinary `.git` and asserts `git rev-parse --path-format=absolute --git-common-dir` equals `/workspace/.git`; the existing integration and browser probes exercise this repository's external linked-worktree pointer. Together they protect the Linux CI/main-checkout and Docker Desktop/linked-worktree shapes without hard-coding the host path.

No blocking findings remain in this correction scope. The test retains real `git ls-files` behavior, uses the public launcher, preserves argv/exit semantics, and neither copies Git metadata into the image nor changes the existing harness/category runner. Read-only mounting only the Git common/worktree metadata is the authorized narrow security boundary; broader host exposure remains forbidden by the normative path mapping and is reviewable at Gate 5. Total current launcher implementation delta remains 23 added lines, below the 100-LOC guard, with no forbidden harness/Compose/Quality Graph/product change.

Package hashes match the reviewed bytes, strict OpenSpec validation and `git diff --check` pass, and all earlier Gate 3 findings remain resolved. This Gate 3 approval covers the corrected Git metadata acceptance matrix. Harness still reports `action_authorized: false`; approval is not implementation authorization.
