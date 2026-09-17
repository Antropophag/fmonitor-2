# Gate 2 / Gate 3 test review — LOCAL-INTEGRATION-ENV-001

- Test author: root agent `/root`.
- Authorship date: 2026-09-17.
- Base: `19ae9d3ec02a801075add5e6db3f504585271e26` (`origin/main` at preparation).
- Root package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T120224Z-39224dc7eb/package.json`.
- Exact source: harness digest `f39d68b308c968a6bc5743cf64846c737b7cdd607e9209f3f5728381867dd7ab`.
- Verification plan SHA-256: `d6c715f72c1f8b561c83e354f52a5d0920e42fc66b7266c29afbdcf1119775f8`.
- Planner lane: `CRITICAL`; required reviews: `gate3`, `final`.

## Acceptance mapping

| Contract | Public seam and observable | Tests |
|---|---|---|
| A1 | `.env.example`; independent `make import-legacy`, `make sync-workforce`; ordered/fail-fast `make up-with-data` | `local_integration_env_001_test.py` |
| A2 | Legacy `.env` parsing and complete preflight before downstream witness | `local_integration_env_001_test.py`; retained private-config test |
| A3 | Bitrix `.env` parsing and complete preflight before downstream witness | `local_integration_env_001_test.py`; retained workforce owner tests |
| A4 | First/changed/invalid/concurrent staging; exact modes; symlink rejection; no reset | `local_integration_env_security_001_test.py` |
| A5 | Canary absence from output/config sources and private file-only transport | both new tests; existing Git/build exclusions |
| A6 | Existing Yii owner commands, configuration-only bootstrap and one documented operator path | `local_integration_env_001_test.py`; retained legacy/workforce DB tests |

Expected values are fixed by the normative examples rather than current implementation. Temporary directories isolate files; no production system, network or DB is used by the two new RED tests. Existing DB tests remain regression evidence for unchanged owners and are not required to be RED.

## Intended RED evidence

`python3 tests/Deployment/local_integration_env_001_test.py` exits `1` at `INTENDED_RED: .env.example missing exact FMONITOR_SOURCE_HOST`. This is the absent single-input template behavior, not fixture setup failure.

`python3 tests/Deployment/local_integration_env_security_001_test.py` exits `1` after invoking the existing executable bootstrap: its new `stage legacy INPUT_ENV DESTINATION` seam returns `64` with safe `LOCAL_INTEGRATION_CONFIG_INVALID`. This is the absent staging behavior, not fixture setup failure.

The captured excerpts contain no canary value. Full local `make test`/`make verify` was not run. CI and deployment are `UNKNOWN`.

## Independent Gate 3 review

- Reviewer: `/root/issue149_gate3` — independent gpt-5.6-sol/low agent; authored none of the specification, verification input or tests.
- Review date: 2026-09-17.
- Verdict: `CHANGES_REQUESTED`.
- Reviewed exact source: harness digest `c5a77d3b6519aea7b1540dd07afccc13bdcae444f1dabc7cfb30380f170c75e9`, executable-source digest `1517e6990bbe56b371febe9ab96b5bcfb81f92c0cfd795a6b17c498e48d0e784`, base `19ae9d3ec02a801075add5e6db3f504585271e26`.
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T120634Z-9310b24609/package.json`; required-context SHA-256 `344fd732ae062c572af68a77a020f96d99b87f550330b2acc001311b060c4109`; verification-plan SHA-256 `4c8bd4c39a9d6280230c65195106b801efdc54396c22b2778dc6bae73ea45a4a` (`CRITICAL`; reviews `gate3`, `final`).
- Evidence reviewed: all five package records — intended RED `1789646770712676000-408d5e5616724ed88256b12e95dd4299.json` and `1789646770712124000-6e743d9604834822b16d407e19a15e60.json`; regression GREEN `1789646770713955000-5ee783ee83ca4bf2ad71de996232f4e5.json`, `1789646770712116000-032b05aaf4014731ae2e5587eca88142.json`, and `1789646770715847000-feefb0d338794b2d9ceb7d32c17b2ac7.json`. Both RED failures are missing behavior rather than setup failures and contain no canary, but do not close the findings below.

### Complete findings

1. **HIGH — Public Make seams are not executed.** `tests/Deployment/local_integration_env_001_test.py:97-110` only checks Makefile strings. It can pass while Compose runs before validation, staging status/path is ignored, a wrong owner runs, or `up-with-data` prints ready after failure. **Correction:** execute `make import-legacy`, `make sync-workforce`, and `make up-with-data` in an isolated fixture with deterministic fake runtime/Compose/owner witnesses; assert exact order/owner/read-only path, zero witness on invalid input, standalone-set independence, fail-fast behavior, and no ready marker after failure.

2. **HIGH — A5 is not observed across the changed Make/bootstrap/Compose path.** `tests/Deployment/local_integration_env_security_001_test.py:76-85` checks ignore patterns and repository literals, not actual public-target argv/output, resolved Compose configuration, or the private read-only handoff. The retained validator test covers the old seam and cannot catch a leak introduced by new staging/Make wiring. **Correction:** drive canaries through a public target; capture bootstrap/Compose/consumer argv, environment and output; inspect rendered Compose and build-context/image-input witnesses; assert canary/full-webhook/private-document absence and only the absolute read-only file contract.

3. **HIGH — Required rejection/publication cases are absent.** `local_integration_env_001_test.py:70-88` omits missing `.env`/keys, empty database/user/password, malformed assignment/unclosed quote, forbidden expansion/substitution, and Bitrix zero/negative IDs, empty token and malformed authority. `local_integration_env_security_001_test.py:68-74` covers only a destination symlink, not unsafe/symlinked `.local`, non-regular destination, unfixable permissions, or deterministic write/publication failure. These are explicit at spec lines 21-24, 47-55, 77-83. **Correction:** add table-driven cases at bootstrap/public seams with zero-effect witness, safe reason/redaction, unchanged prior destination where applicable, and temp cleanup.

4. **MEDIUM — Concurrency checks only the final file, not concurrent observation.** `local_integration_env_security_001_test.py:60-66` reads after both writers finish, so it cannot detect partial content visible during publication. **Correction:** overlap coordinated writers with a reader/consumer witness; assert every observation is exactly complete snapshot A or B and parseable, using barriers/bounded retries for determinism; retain mode and temp-cleanup assertions.

Traceability and independently fixed expected documents are otherwise clear. The RED attribution and retained GREEN owner evidence are sound; the four findings block approval.

## Root correction — 2026-09-17

Root retained the contract and corrected only the Gate 2 tests:

- the primary test now executes all three public Make targets in an isolated checkout with fake Docker/curl witnesses, asserts exact owner/order/fail-fast behavior, zero downstream calls on invalid input, no premature ready marker and read-only private-file mounts;
- the same witness records argv and integration-prefixed environment and rejects both canaries from argv/environment/output while retaining build-context exclusion checks;
- parsing matrices now cover missing/empty/duplicate/malformed/unterminated/expansion inputs, all required scalar and port/cutoff boundaries, malformed/empty-token/authority webhook cases, and zero/negative/boolean/string/duplicate department IDs;
- security cases now cover symlinked private directory, non-regular destination, deterministic publication failure with preserved destination/temp cleanup, and coordinated concurrent writers with a live reader requiring every observation to equal one complete snapshot.

Both corrected tests remain intended RED at the same missing implementation boundaries. A fresh exact-source package and evidence follow for independent rereview; the prior `CHANGES_REQUESTED` decision remains historical.

## Gate 3 correction rereview — 2026-09-17

- Reviewer: `/root/issue149_gate3`; independent of specification and test authorship.
- Verdict: `CHANGES_REQUESTED`.
- Reviewed exact source: harness digest `158c11ca3ef736a415f9d1d4a5417742532018f9870f86640adea14cb3b2d504`; executable-source digest `e527a8ee14b2a4d43ad52e764f473721bc15d4d9bd4d04f4bb91d59bbbfdddc3`; base `19ae9d3ec02a801075add5e6db3f504585271e26`.
- Correction package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T121350Z-72226eab84/package.json`; required-context SHA-256 `344fd732ae062c572af68a77a020f96d99b87f550330b2acc001311b060c4109`; verification-plan SHA-256 `e8017be6bde50552cda377806b572fb8dc820e407932451bd98be87668f36183`.
- Evidence reviewed: intended RED records `1789647206822248000-3ea46d07cc4b457d8bf5d84a165164eb.json` and `1789647206821664000-f11f30bc4b9a48339313ad3e6da40bac.json`; regression GREEN records `1789647206821656000-7f1e0d40674743bcafe486d7d20908fe.json`, `1789647206821660000-47230bafb3804be585c4e9c28bb0e23b.json`, and `1789647206835752000-905b5ac4544c4bec98cc7560806b1d5d.json`. Both REDs remain attributable to the missing template/stage behavior and expose no canary.

### Closure of previous findings

1. **CLOSED — public Make seams.** `local_integration_env_001_test.py:111-202` now executes all three targets against isolated Docker/curl witnesses and checks order, owners, read-only mounts, invalid-input zero effects, fail-fast behavior and absence of the ready marker.
2. **PARTIAL — secret boundary.** Public-target argv, integration-prefixed environment, output, read-only mounts and repository/build-context exclusions are now observed. The exact Compose-config requirement remains untested; see finding 1 below.
3. **PARTIAL — rejected/publication cases.** The expanded parsing and unsafe-publication matrices close the scalar, grammar, URL/department, symlink, non-regular destination and write-failure gaps. The exact missing-`.env` case remains absent; see finding 2 below.
4. **CLOSED — concurrent observation.** `local_integration_env_security_001_test.py:62-95` overlaps two writers with a reader and constrains every observation and the final destination to one complete snapshot, while retaining permission and cleanup checks.

### Complete correction-rereview findings

1. **HIGH — `docker compose config` leakage is still not tested.** The fake Docker at `local_integration_env_001_test.py:145-155` prints a constant only if some command already contains `config --quiet`, but none of the tested Make recipes invokes `docker compose config`, and no assertion executes or validates resolved Compose output. Consequently an implementation that exposes the full webhook/password through Compose interpolation can pass lines 196-202. **Correction:** invoke the repository's actual Compose command construction with the test `.env` using the fake/controlled Compose boundary in a real `config` operation (or an equivalent deterministic renderer witness), capture the resolved configuration, and assert that both canaries, full webhook and private-document contents are absent. Do not satisfy this with a constant fake response independent of passed files/configuration.

2. **MEDIUM — the specified missing `.env` rejection remains untested.** `invalid_legacy` begins with an empty string at `local_integration_env_001_test.py:70-72`, but `stage()` always creates `env_file` at lines 41-42. This tests an empty existing file, not the spec line 79 case where `.env` does not exist; the public Make invalid-input check at lines 187-194 also keeps `.env` present. **Correction:** unlink the input `.env` and exercise both bootstrap and at least one public Make seam, asserting the stable safe reason, nonzero status, zero Docker/owner witness, no stack trace/secret output, and preservation/non-use of any old destination.

No additional findings were found in the correction delta. Approval remains blocked by these two residual gaps.

## Root second correction — 2026-09-17

- Added an absent-file bootstrap case and an absent `.env` public `make import-legacy` case with stable reason, zero Docker/owner witness, no stack trace/canary and byte-preserved old destination.
- Added execution of the repository's real `docker compose ... config` construction through `local-runtime-env` against the isolated test `.env`; the test inspects actual rendered output for both integration canaries/full webhook absence and the expected private Bitrix file reference. The fake Docker witness is not used for this assertion.

No specification or previously approved expectation changed. Fresh exact-source evidence and a narrow rereview are required.

## Gate 3 second correction rereview — 2026-09-17

- Reviewer: `/root/issue149_gate3`; independent of specification and test authorship.
- Verdict: `CHANGES_REQUESTED`.
- Reviewed exact source: harness digest `5acd68e1c1b8da41f508a29ab241dcc6b27a6720ae7516977f7d5a9e1cc0eaed`; executable-source digest `a6ca4d70a8e3de060e0ab94383aabbe08eade2423d9f1e9f821343ed846b1905`; base `19ae9d3ec02a801075add5e6db3f504585271e26`.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T121733Z-22961b82a5/package.json`; required-context SHA-256 `344fd732ae062c572af68a77a020f96d99b87f550330b2acc001311b060c4109`; verification-plan SHA-256 `2d2341c60345b543e462c4bb3a1a6b4ad1da5865c750f93ddeb6b85947b75b27`.
- Evidence reviewed: intended RED `1789647429597345000-c20d839c7f164269803c2e0c23678e30.json`, `1789647429597511000-2e60f29a7da24440b2ef75a5ffef813d.json`; regression GREEN `1789647429597720000-91546e1d31884f81bb8268e4f4ee25d8.json`, `1789647429600738000-faeb8d2aa5754d78b6b9dfefbd29be8f.json`, `1789647429601900000-765ce0770a1448459a23716c87127933.json`. RED remains at the intended missing template/stage boundaries with no canary disclosure.

### Residual closure

1. **NOT CLOSED — actual Compose rendering.** The correction now invokes the real Compose renderer, but introduces an incorrect/incomplete oracle; see the finding below.
2. **CLOSED — missing `.env`.** `local_integration_env_001_test.py:48-53` removes the direct bootstrap input and verifies the safe reason; lines 199-211 remove the public seam input and verify nonzero safe failure, zero Docker/owner witness, no traceback/canary, and preservation of the old private destination.

### Complete second-correction findings

1. **HIGH — the Compose-config oracle requires behavior outside the contract while still missing the full private-document prohibition.** `local_integration_env_001_test.py:219-229` correctly executes real `docker compose ... config`, but line 228 requires `.local/bitrix-workforce.json` in the rendered static Compose model. The current public seam supplies that host path only through the `docker compose run --volume ...:ro` CLI override (`Makefile:57`); it is not part of `deploy/runtime/compose.yaml`, and A5 requires absence of secrets/private-document contents from Compose config, not presence of this runtime-only mount. The assertion would force an unnecessary Compose-model change or fail after a conforming implementation. Conversely, lines 226-227 check only the two secret canaries; A5 also prohibits **contents of private documents**, so a rendered `FMONITOR_SOURCE_HOST`, source user/database, or departments JSON could leak and the test would pass. This is material because the current Compose model already has integration-related interpolation at `deploy/runtime/compose.yaml:113-118`. **Correction:** remove the expectation that the CLI-only `.local/bitrix-workforce.json` host path appears in static Compose output. Seed distinct canaries for every generated private-document value (including non-secret legacy fields and departments), render real Compose config, and assert all those values/full document fragments are absent; keep read-only mount/path verification on the already exercised `compose run` argv witness.

No other residual or newly introduced findings were found. Approval remains blocked by this single HIGH finding.

## Root third correction — 2026-09-17

Removed the out-of-contract expectation that a CLI-only run mount appear in the static Compose model. The isolated `.env` now gives every generated private-document field a distinct value; actual Compose rendering is rejected if it contains the legacy host/database/user/password/cutoff, full webhook/token or departments JSON. Read-only private paths remain asserted only on the executed `compose run` argv witnesses.

## Gate 3 third correction rereview — 2026-09-17

- Reviewer: `/root/issue149_gate3`; independent of specification and test authorship.
- Verdict: `CHANGES_REQUESTED`.
- Reviewed exact source: harness digest `b8d33a6c494b9dbb10fd41de12ea9b1111b685484cdd93f87be3898141d810c2`; executable-source digest `b69d1ddf2be338dda090fd22eb823012ba34be29ff1905d04f70cc6a7b6eab69`; base `19ae9d3ec02a801075add5e6db3f504585271e26`.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T122005Z-1b51452a71/package.json`; required-context SHA-256 `344fd732ae062c572af68a77a020f96d99b87f550330b2acc001311b060c4109`; verification-plan SHA-256 `71d99c437185f4d2bd8525414cfd9a408366fe0e3027106eadd186e619f6a997`.
- Evidence reviewed: intended RED `1789647580808858000-e6d866831a1b4ec6af2abf177b1fdae0.json`, `1789647580808138000-21edfa5448da4c87b2548cf14d237b56.json`; regression GREEN `1789647580812276000-af23f6b064cb4c43aa5779adf2109951.json`, `1789647580824062000-e35a0498cceb4542a9d68551cbb540a1.json`, `1789647580813077000-247da7a0137c4d62b23bf3a911ab5cbc.json`. RED remains attributable to the missing template/stage behavior and contains no canary.

### Residual closure and complete findings

The out-of-contract static-mount assertion is removed, and actual Compose rendering now rejects the distinct host/database/user/password/cutoff, full webhook/token, and departments values.

1. **MEDIUM — source-port leakage remains deliberately unobservable.** `local_integration_env_001_test.py:227-236` includes `3306` in `private_values` and then explicitly skips it because it is shared with unrelated DB configuration. This contradicts the correction note's claim that every generated private-document field is distinct and leaves an A5 regression that injects `FMONITOR_SOURCE_PORT` into Compose config undetected. **Correction:** use a valid distinctive source port (for example `65431`) throughout the independent input/expected document and invalid-case replacements, then assert that value is absent from rendered Compose output like every other private-document value; do not special-case it out of the oracle.

No other residual or newly introduced findings were found. Approval remains blocked only by this finding.
## Root fourth correction — 2026-09-17

Changed the source-port fixture from shared `3306` to distinct valid canary `43149` and now rejects that value in actual Compose rendering together with every other private-document value. No acceptance behavior changed.

## Gate 5 return — root Gate 2 correction, 2026-09-17

Final review of exact source `48915af46b38c6e2a9c549d90c011bfc1ced89316768da1d791032aaf4afd3d4` returned `CHANGES_REQUESTED` in `reviews/code/LOCAL-INTEGRATION-ENV-001.md`. Root amended the contract and test matrix before production correction:

- ambiguous legacy passwords that cannot be represented losslessly by the unchanged consumer grammar are rejected before publication;
- Bitrix user-info and tokens outside `[A-Za-z0-9_-]{1,256}` are rejected before Docker;
- `$HOME`, positional `$1` and backtick expressions join the existing `$(`/`${` rejection matrix;
- operator documentation must display the same HTTP port as `.env.example`.

Fresh RED: `python3 tests/Deployment/local_integration_env_001_test.py` reaches the existing stage executable and fails because legacy invalid case 16 (`$HOME`) returns success. The failure is the new prohibited-input assertion, not fixture setup. The secret value is not printed. Security staging and retained owner regressions remain expected GREEN. A fresh independent Gate 3 review is required before correction implementation.

### Gate 3 return correction

The first Gate 3 review of the Gate 5 return requested two sensitivity additions. Root added positive lossless legacy-password cases for an embedded single quote and an embedded double quote, plus an owner-compatible Bitrix token boundary accepting exactly 256 `[A-Za-z0-9_-]` characters and rejecting 257. The existing ambiguous-both-quotes, user-info/dotted-token and shell-expression rejections remain unchanged.

### Correction Gate 5 return — uppercase scheme

Correction final review reproduced one remaining owner-parity gap: uppercase `HTTPS://portal.example/rest/7/token/` passed staging but the unchanged `WorkerConfiguration` rejected it after the Docker boundary. Root added this exact rejection to the public preflight matrix and clarified the owner-compatible lowercase scheme. Fresh Gate 3 approval is required before the production correction.

### CI oracle correction

Exact-source CI run `35224850825` exposed one complete unit inventory failure: `tests/Architecture/yii2_local_quickstart_boundary_001_test.py` retained the pre-#149 assertion that `.env.example` must not contain `FMONITOR_BITRIX_WEBHOOK_URL`. The new contract intentionally adds Bitrix and legacy placeholders while preserving plain `make up` as runtime-only. Root changes the stale oracle to require both integration groups in the template and to prove the `up` recipe itself does not invoke `local-integration-config`. The failed unit record is the historical RED; current corrected oracle must be GREEN and is explicitly mapped before correction review.

## CI oracle correction Gate 3 — 2026-09-17

- Reviewer: `/root/issue149_gate3`; independent of specification, test and implementation authorship.
- Verdict: `APPROVED`.
- Review mode: green correction; harness v1 cannot prepare a Gate 3 package without remaining intended RED. Historical RED is exact-source CI run `35224850825`, unit `REGRESSION_FAILURE`, recorded above; the corrected oracle and all mapped current checks are GREEN.
- Reviewed exact source: harness digest `f28b84654b3e49995b3cd9a9f45162a3271479bb2a605da0cc60f41edbaea463`; executable-source digest `52871ab219063a3c81ca316d5280e96e107e839910bce254f34891610ca003ad`; base `19ae9d3ec02a801075add5e6db3f504585271e26`.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T131501Z-1bd6946a74/package.json`; required-context SHA-256 `344fd732ae062c572af68a77a020f96d99b87f550330b2acc001311b060c4109`; verification-plan SHA-256 `d1b7c9aeedd92fe75a051910d3d0d562d04b0278d25821277811b5d09dcde85d`.
- Source-matched GREEN evidence: architecture oracle `1789650810600314000-321fdd2183424123a96ede7a45a42e95.json`; deployment contracts `1789650811710933000-7554c42078e64127971b893de2d3e7e6.json`, `1789650818157355000-366de9d4dcee41579a1316a45a6b6102.json`, `1789650820990391000-c39ba54bdf6d418b918df9a00840c4bc.json`; retained owners `1789650823217397000-5ae86a41fd754c5bbaa5d1c767099c9d.json`, `1789650834874619000-c0d09be4498a48ccb8295265aa4a3c3d.json`.

### Complete findings

- None. `tests/Architecture/yii2_local_quickstart_boundary_001_test.py` replaces only the stale negative Bitrix-template assertion with positive witnesses for both Bitrix and legacy placeholder groups and a negative witness that the plain `up` recipe does not call `local-integration-config`.
- Traceability is explicit through the A1-A3-A6 mapping. Sensitivity is complementary: the architecture oracle fails if either integration group disappears or staging is inserted directly into `up`; the mapped executed `up-with-data` test requires exactly one `up`, then exactly one legacy and workforce owner, so an indirect integration invocation from plain `up` also breaks the observable order/count. The complete template and independent integration seams remain covered by the existing deployment contract.
- The historical failure is an obsolete pre-#149 expectation rather than a product regression; the replacement preserves the old quickstart invariant that plain `make up` is integration-free while aligning the template oracle with the accepted single-`.env` behavior. No production/spec expectation is weakened.

## Gate 3 fourth correction rereview — 2026-09-17

- Reviewer: `/root/issue149_gate3`; independent of specification and test authorship.
- Verdict: `APPROVED`.
- Reviewed exact source: harness digest `531f586d42b3667d55f33d23530e4544ee2530eb06ee4614398b8cc662a22614`; executable-source digest `5a33448b27c3b11938802a01b28327a7aac4851ea2849f0dafaccc5888a225df`; base `19ae9d3ec02a801075add5e6db3f504585271e26`.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T122201Z-16b5f4f63c/package.json`; required-context SHA-256 `344fd732ae062c572af68a77a020f96d99b87f550330b2acc001311b060c4109`; verification-plan SHA-256 `0f35488c40ad163532ffecfdb8efa528a40ef1f2a3e1f9cfa07a56cdb463ae51`.
- Evidence reviewed: intended RED `1789647698591238000-4db0354a68d14950922e82cfb6d40594.json`, `1789647698591198000-da12c20bb18048e8954d4ed5d7ab6e81.json`; regression GREEN `1789647698592476000-52799daed11d488db75cc705ecc6877d.json`, `1789647698596172000-0d883eea134e45759febda19999754e5.json`, `1789647698600911000-5859ae56664e48dda8df019909e3203d.json`. RED is still attributable to missing template/stage behavior, not setup, and captured output contains no canary.

### Residual closure and complete findings

- **CLOSED — source-port Compose leakage.** `local_integration_env_001_test.py` now uses valid distinctive port `43149` consistently in the input, independently fixed private document, invalid matrices and public-seam invalid case. The real Compose rendering assertion includes `43149` without a special-case exclusion, so a source-port leak is regression-sensitive.
- Complete findings: none. All four original findings and subsequent residuals are closed; traceability, public seams, sensitivity, independent expectations, rejected/security/atomic/concurrency cases, determinism, and intended RED evidence are adequate for Gate 3.

## Gate 5 return Gate 3 — 2026-09-17

- Reviewer: `/root/issue149_gate3`; independent of specification, test and implementation authorship.
- Verdict: `CHANGES_REQUESTED`.
- Reviewed exact source: harness digest `02b97ce4a96b546b7c5d24110c48ed7247f506fa940bc939691f340c056df250`; executable-source digest `98781814504044e3e61052eba4410f7b362a72564d529fa24f8507b6ea7ce2ee`; base `19ae9d3ec02a801075add5e6db3f504585271e26`.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T124428Z-cb9ad9570e/package.json`; required-context SHA-256 `344fd732ae062c572af68a77a020f96d99b87f550330b2acc001311b060c4109`; verification-plan SHA-256 `225f4d34e63d9ec4f0577a5a9c5f8248db742aa9fabcdd227fd730077fbc24f3`.
- Evidence reviewed: intended RED `1789649036996166000-ba74cda06d7f452abbd6868e6f47084e.json`; GREEN `1789649038927762000-73a4ce02d75c40ea807b846b55780694.json`, `1789649041866308000-281f0fcd696141b4a4fde8910ec9edda.json`, `1789649044222166000-f697d9fffde64063adec1650ac2519ff.json`, `1789649055017439000-33895466788f4087af1c4139ac0e761e.json`. The RED reaches zero-based legacy invalid case 16 (`$HOME`), which the current stage executable accepts; this is the newly specified missing behavior, not setup failure, and no secret is printed. Security staging and all three mapped retained checks are source-matched GREEN.

### Complete findings

1. **HIGH — Bitrix owner-compatible token length remains untested.** The amended contract requires the complete owner grammar `[A-Za-z0-9_-]{1,256}`, but `tests/Deployment/local_integration_env_001_test.py:95-113` tests user-info, a token containing `.`, and an empty token only. An implementation that enforces characters but accepts 257+ characters will satisfy the new test and then be rejected by unchanged `WorkerConfiguration` after Docker, recreating Gate 5 finding 2. **Correction:** add a 256-character accepted token case and a 257-character rejected case (with zero downstream/public-seam witness and redacted output), deriving the boundary independently from the normative regex.

2. **MEDIUM — quote compatibility is sensitive to rejection but not to required lossless acceptance.** The new ambiguous case at `local_integration_env_001_test.py:96` correctly requires rejection when the post-unquote password contains both quote kinds. No positive case proves that representable passwords containing only a single quote or only a double quote remain accepted and reach the unchanged legacy consumer byte-for-byte. An overbroad implementation that rejects every password containing any quote would pass, despite the amended contract rejecting only values that cannot be represented losslessly and the general quoted-value grammar. **Correction:** add independently expected accepted cases for each representable quote shape and observe the unchanged consumer/private-file interpretation equals the intended password bytes; retain the both-quote rejection.

The full `$`/backtick rejection forms and template-derived documentation port assertion are otherwise correctly specified and regression-sensitive. No additional findings were found.

## Gate 5 return Gate 3 correction rereview — 2026-09-17

- Reviewer: `/root/issue149_gate3`; independent of specification, test and implementation authorship.
- Verdict: `APPROVED`.
- Reviewed exact source: harness digest `6fa00710064b640cfc1f4a074a89a1054f89921f54bfe9217095f688335535bf`; executable-source digest `0dd6743ac8872a6209251ee4c138e365307d06c5eac36241d228ab93c3987eb9`; base `19ae9d3ec02a801075add5e6db3f504585271e26`.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T124701Z-cb2161779d/package.json`; required-context SHA-256 `344fd732ae062c572af68a77a020f96d99b87f550330b2acc001311b060c4109`; verification-plan SHA-256 `1b32993dbbf620219330bfedcd48c925add5b01d00262dbfa2eb9ed7d65c6bf0`.
- Evidence reviewed: intended RED `1789649188065543000-4d03fef3c9c14ad6bc6eba0a26f2b92b.json`; GREEN `1789649190143543000-d41ce0f8fd1949f594093a901a18d4db.json`, `1789649193059390000-36e55bfbffdf43af9247cc683204b2dc.json`, `1789649195437073000-408ab3a2c9664f0bb0b3a443e5c21157.json`, `1789649208374628000-f3cdfb2c6db64eb2a0597b365c29b754.json`. RED remains the intended zero-based legacy invalid case 16 (`$HOME`) with no secret disclosure; security and retained owner checks remain source-matched GREEN.

### Closure and complete findings

- **CLOSED — Bitrix token boundary.** A 256-character `[A-Za-z0-9_-]` token is accepted and its exact normalized owner-compatible `baseUrl` is asserted; the adjacent 257-character token is in the rejection matrix. User-info, dotted-token and empty-token rejections remain present.
- **CLOSED — lossless quote acceptance.** Separate positive cases stage passwords containing only an embedded single quote and only an embedded double quote with independently expected matching wrappers, which the unchanged consumer removes without byte loss. The ambiguous password containing both quote kinds remains rejected.
- Complete findings: none. The amended shell-expression matrix, owner-compatible Bitrix grammar, quote-loss behavior and template-derived documentation port are sufficiently traceable, deterministic and regression-sensitive for Gate 3.

## Uppercase HTTPS owner-parity Gate 3 — 2026-09-17

- Reviewer: `/root/issue149_gate3`; independent of specification, test and implementation authorship.
- Verdict: `APPROVED`.
- Reviewed exact source: harness digest `2c915327dd2a33544454e627c579fda03070d2b70fade0846c1e845a64d3de48`; executable-source digest `c6f676a1b41c93b94962464678b196084bcdcacebedfad2821a75c5487456160`; base `19ae9d3ec02a801075add5e6db3f504585271e26`.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T125637Z-ab5559604b/package.json`; required-context SHA-256 `344fd732ae062c572af68a77a020f96d99b87f550330b2acc001311b060c4109`; verification-plan SHA-256 `1640e3a7f1a410578c56f7fffd20e917e6679b6860dde2e0248140336f1a718f`.
- Evidence reviewed: intended RED `1789649764758960000-d10160a1fda84712b5d99671052371ed.json`; GREEN `1789649767149268000-4216dc9783c44c3ebf6bce4c9caa68b8.json`, `1789649769974677000-1989bbe580114a1385a787257b6a6615.json`, `1789649772503094000-fc44431fdcaa46f8a28cccc503ee0c1e.json`, `1789649784192956000-830b6912d0ed45f5b89e7287c2d27dc5.json`.

### Complete findings

- None. The normative contract now requires exact lowercase `https`, matching unchanged `WorkerConfiguration`; the focused test independently changes only the scheme spelling and places uppercase `HTTPS` in the existing Bitrix rejection matrix while retaining valid lowercase, user-info, path, empty/dotted/256/257 token and departments cases.
- The captured RED reaches zero-based Bitrix invalid case 2 because the current stage accepts uppercase `HTTPS`; this is the intended owner-parity gap rather than setup failure, returns no secret output, and is positioned before any Docker/owner effect. Security staging and all three retained mapped checks remain source-matched GREEN.
