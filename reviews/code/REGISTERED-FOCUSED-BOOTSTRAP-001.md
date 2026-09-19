# REGISTERED-FOCUSED-BOOTSTRAP-001 — Gate 5 final review

## Review binding

- Date: 2026-09-19.
- Verdict: **APPROVED**.
- Reviewer independence: the reviewer authored neither the normative specification/tests nor executor commit `9e46aca311e971ec3a0b2ce24b27156b3fce71a0`.
- Base: `7c85fdbca24f087e235c27039d3e3f0da320341b` (`origin/main` at preparation).
- Reviewed head: `9e46aca311e971ec3a0b2ce24b27156b3fce71a0`.
- Candidate source: `021940c42f69a33f8f674a62470ff9c229e078ffd74da353e8e2c0b9d87a72c5`.
- Executable source: `2a3d01a4fd71495ebe9d56c4da3e35ce9671b44a521b70e4c34b4fa35eb749b3`.
- Exact reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T160420Z-3b54c4f684/package.json`.
- Required-context SHA-256: `cc27073ad5a348b935a49617c0075b4218fbc58f37a0a0975b7bd1a80ed7167e`.
- Verification-plan SHA-256: `99bca916eb2ee295115a687e554d9964f6f7a9bb87d8049141c70c01dfcef2c1`.
- Normative contract: `specs/REGISTERED-FOCUSED-BOOTSTRAP-001.md`.
- Approved Gate 3 source: candidate `6c4e125cb6c93188ac3c0faab16efd9d1e5b9210ad31295f0d12e35b7eb42c63`, recorded in `reviews/tests/REGISTERED-FOCUSED-BOOTSTRAP-001.md`; implementation followed in the separately authored executor commit above.

The prepared planner route is `CRITICAL`, with required reviews `gate3` and `final`. This review evaluates the final exact-source candidate and does not infer a different lane or lifecycle from diff size.

## Evidence reviewed

- Exact-source acceptance GREEN: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789833725896429000-fbfbaacaea584032aa445da73a9eec48.json`, exit `0`, all seven executable methods, 123.225 seconds. The retained run covers the real prepared route from a dependency-free disposable worktree, first/repeat runs, immutable image/source identity, controlled uncommitted navigation mutation RED and restored GREEN, setup stages, 503 classification, interruption cleanup, and foreign Docker resource survival.
- Exact-source governance GREEN: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789833686003553000-ab097fff238b4a26ac2de418875fd58b.json`, exit `0`.
- Bounded reviewer checks: `bash -n tools/delivery/run-in-profile`; Python compilation of `tools/delivery/change-verification.py` and `tests/Verification/registered_yii2_focused_bootstrap_001_test.py`; and `git diff --check 7c85fdbc..9e46aca3`. All passed.
- The canonical full local suite was not run, per owner policy. The package records exact-source CI as `UNKNOWN`; approval here is the required independent code-review decision and is not CI GREEN, publication approval, or merge authorization.

## Conformance assessment

### RFB001-A — first clean run

Conforms. The planner wraps only the registered Yii2 navigation acceptance command with `run-in-profile browser`. The launcher freezes and restores the candidate, builds pinned Composer, Python, Node, browser and `shlz-ui` inputs in the container image, starts an owned MariaDB service, connects the child to its owned network, supplies the container asset path, and preserves the exact selected child argv. The retained real scenario proves the command succeeds without host `vendor`, `node_modules`, sibling `shlz-ui`, or a pre-existing test DB.

### RFB001-B — repeat and cache identity

Conforms. The deterministic image name is derived from the recipe, dependency inputs, lockfiles and executable-source identity. An existing matching image is inspected and reused rather than rebuilt; its Composer-lock and executable-source labels are checked before execution. The exact-source acceptance requires equal immutable image and source digests across unchanged first/repeat runs. No shared cache cleanup was introduced.

### RFB001-C — source isolation

Conforms. `review-source.py` materializes the candidate, the launcher verifies its executable digest, and the image embeds that exact materialized source. Dependencies and `shlz-ui` assets come from pinned container build inputs; the former fixture fallback to the sibling checkout was removed. The controlled uncommitted `MainNavigation.php` mutation is observed by the same command as an assertion-level `REGRESSION_FAILURE`, changes retained source identity, and returns GREEN after restoration.

### RFB001-D — failure semantics and lifecycle

Conforms. Docker, dependency build, DB start, and DB readiness failures occur before child assertions and emit explicit `SETUP_FAILURE: stage=...`; harness evidence retains stdout/stderr paths and classifications. Ordinary child HTTP 503 remains `REGRESSION_FAILURE` with exit `8`; timeout remains `INTERRUPTED`. Each integration/browser invocation generates a high-entropy Compose project, uses it consistently for start/network/readiness/down, and cleans only that project's volumes on EXIT/HUP/INT/TERM. Tests cover success, setup failures, assertion failure, timeout interruption, sequential unique identities, and survival of a real foreign Docker network. No default project reset or `--remove-orphans` was added.

### RFB001-E — prepared route and identity

Conforms. Policy contains one exact focused-command promotion, for `tests/Yii2/yii2_main_navigation_001_test.php`; ordinary integration and the bootstrap diagnostic remain unwrapped. The generated command preserves `acceptance:yii2_main_navigation_001_test`, acceptance purpose, environment metadata and exact PHP argv while adding the registered profile prefix. Invalid profile/missing command inputs reject before candidate or child execution. Existing evidence schema/store and intended-RED behavior are unchanged.

## Security, maintainability, scope, and regression sensitivity

- Container execution remains read-only apart from explicit tmpfs/artifact locations, uses pinned base-image and dependency inputs, validates cached image labels, and does not mount host dependency or sibling-checkout paths.
- Compose ownership is explicit and narrowly scoped. Cleanup cannot select the shared default project or foreign projects by discovery, and the regression test proves a foreign network survives.
- The policy extension is validated as a string-to-known-profile map and each registered target passes the existing trusted test-argv validation. Promotion is acceptance-only, preventing unconditional wrapping of generic diagnostic/category commands.
- The implementation is localized to the existing planner/profile seams plus the fixture's explicit asset contract. It does not change product navigation, error handling, schema, FAST/admission/evidence semantics, workflows, branch settings, or domain state.
- The executable matrix is sensitive to the material risks identified during Gate 3: actual package route, exact child argv, clean-host execution, pinned asset availability, cache/source identity, unique ownership, cleanup on every exit class, foreign-resource isolation, setup versus assertion/interruption outcomes, and singleton heavy promotion.

## Findings

No blocking or non-blocking findings within the agreed scope.

## Final decision

**APPROVED** for Gate 5 at candidate source `021940c42f69a33f8f674a62470ff9c229e078ffd74da353e8e2c0b9d87a72c5` / head `9e46aca311e971ec3a0b2ce24b27156b3fce71a0`.

Remaining delivery condition: the planner-selected full CI obligation is still `UNKNOWN` and must be satisfied by the authorized exact-source CI route before any claim of CI GREEN or publication readiness. Any code, test, specification, verification-input, registration, or source-binding change after this review requires applicable delta review and fresh evidence.
