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

## Issue #185 slice 2 Gate 3 review — 2026-09-18

- Reviewer: `/root/gate3_review`; independent `gpt-5.6-sol/low` agent; authored none of the reviewed specification or tests.
- Test author: root agent `/root`.
- Reviewed source: base `95e070893082422b067786abe6b6e5ff4ea3aa65` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T005657Z-345e354a22/snapshot/source.patch`, SHA-256 `fc30fe891472f65cfa356db442e514287e5584eff81240f63330defe501a6c83`; candidate-source digest `e0811552f127652a0d977b5ccde71234cd0645539c0d326e15925cbbabc95f34`.
- Agreed review scope: complete second bounded slice of #185; cross-platform mode tolerance, private cross-UID container delivery, both public Make seams and real loaders/importers, replay/cleanup, and synthetic MariaDB/Bitrix acceptance. Prior #149 findings are historical and are not reopened except where this slice changes the boundary.
- Specification: `specs/LOCAL-INTEGRATION-ENV-001.md` A4-A6 and `openspec/changes/cross-platform-local-integration-staging/specs/local-integration-staging/spec.md`.
- Public seam: `make import-legacy` and `make sync-workforce`, following `.env` → host staging → Make/Compose delivery → штатный PHP loader → importer.
- Red command and intended failure: records `1789692993735980000-c820360c5e244101acbd7d2516e712dc.json` and `1789692997713924000-0f5f148bba5e48569e52dbb3e8513071.json`; both exit `1` at rejection of accessible `0644` input / `0755` staging directory. Regression record `1789693001568052000-e17af744dada4379b5ce8dbd02739c20.json` is GREEN.
- Verification plan: SHA-256 `33485340949f012bf6648495ece7e76a843e06cb74280d44eb52fa90f2f77b63`; lane `CRITICAL`; required reviews `gate3`, `final`.
- Verdict: `CHANGES_REQUESTED`.

### Findings

1. **HIGH — The required synthetic end-to-end acceptance is not an executable test.** The delta contract requires isolated MariaDB and a local Bitrix endpoint through both public Make seams (`openspec/.../spec.md:46-52`), but the only new runtime test invokes the proposed wrapper directly with `php -r` (`local_integration_cross_platform_185_test.py:75-89`). The existing Make test uses a fake `docker` witness and neither runs the importers nor observes persisted legacy/workforce facts. Task 3.1 is correspondingly still unchecked (`tasks.md:13-16`). A broken Make/Compose/PHP-loader/importer chain can pass the submitted suite. **Correction:** add deterministic isolated acceptance that runs both actual Make targets, supplies synthetic legacy MariaDB data and a local Bitrix endpoint, and independently asserts the expected imported/synchronized facts, updated endpoint values on replay, and absence of production connections.

2. **HIGH — Cross-UID and loader coverage bypasses the public seams and both штатные PHP loaders.** Lines 43-49 only inspect Makefile/Dockerfile strings, while lines 70-84 execute a generic inline PHP reader through the wrapper. This cannot catch a target that supplies the wrong path/environment, a remaining exact-mode rejection in `LegacyImportConsole` or `WorkerConfiguration`, or an importer that is not actually UID 10001. **Correction:** exercise `make import-legacy` and `make sync-workforce` with the real Compose construction and real loaders/importers; assert the `0600` host-owner mismatch, effective UID 10001 at importer execution, and successful reading/consumption for both configuration types. Retain a narrow wrapper test only as supplementary coverage.

3. **HIGH — The captured RED does not demonstrate the principal cross-UID missing behavior.** Both intended-RED records stop at the first mode-tolerance assertion (`local_integration_cross_platform_185_test.py:35-36`; `local_integration_env_security_001_test.py:53-54`). The wrapper-existence, different-UID read, failure-status, and cleanup assertions are never reached, so the supplied evidence proves only one sub-behavior and cannot distinguish cross-UID implementation defects from unexecuted assertions. **Correction:** split independently runnable mode, delivery/UID, loader, replay and cleanup tests (or otherwise arrange independent probes) and capture source-matched intended RED for each missing behavior without converting unavailable Docker into product RED.

4. **MEDIUM — The new acceptance matrix omits input-path rejection and container replay freshness.** The security test covers a destination symlink, symlinked staging directory and non-regular destination (`local_integration_env_security_001_test.py:110-126`), but not a symlink or non-regular `.env` input required by the delta spec (`spec.md:15-18`). The container test runs each kind once and checks only final volume emptiness; it never repeats one target/config kind with changed values and proves the second importer saw no stale container snapshot (`spec.md:33-39`). **Correction:** add public-seam zero-effect cases for symlink and non-regular input with preserved destination/no temps, and repeat each applicable delivery with distinct old/new canaries while asserting only the new value is consumed and no secret remains after success or importer failure.

5. **MEDIUM — Secret-boundary coverage does not observe image layers or application logs.** The new test asserts only process stdout/stderr (`local_integration_cross_platform_185_test.py:82-89`); building the image before creating the host canary makes the canary trivially absent from layers, and the inline reader produces no application log. The contract explicitly includes image layers and application logs. **Correction:** add deterministic inspection appropriate to the real public-seam fixture (image history/export or an equivalent layer-content oracle, plus captured application logs from actual importer runs) and reject all synthetic secret/private-document canaries there as well as in argv, Compose output and stdout/stderr.

Traceability and fixed expected values for mode acceptance, host owner/mode preservation, UID 10001 readability, exit-status preservation and final cleanup are otherwise clear. The tests use temporary/randomized names and avoid production endpoints, but the five findings prevent complete Gate 2 coverage and Gate 3 approval.

### Required changes

- Close findings 1-5, capture independent source-matched RED evidence for the newly covered missing behaviors, refresh the harness package/plan, and request a narrow independent Gate 3 rereview before production implementation.

## Root correction — issue #185 slice 2, 2026-09-18

- Разделены независимые RED: mode portability остаётся в staging/security test, cross-UID test теперь начинает с отсутствующей container-delivery seam и не блокируется прежним mode assertion.
- Cross-UID test строит production runtime image, монтирует host-owned `0600`, требует фактическое чтение UID 10001, вызывает оба canonical Yii loaders, проверяет updated replay, importer exit `23`, cleanup named volume, container removal и отсутствие canary в stdout/stderr/image history.
- Security test добавил symlink и FIFO input rejection; существующие проверки сохраняют symlink directory/non-regular destination, concurrent observation, atomic preservation и temp cleanup.
- Acceptance mapping теперь включает существующие реальные isolated MariaDB legacy importer и workforce importer с локальным verified-TLS Bitrix fixture. Public Make tests отдельно исполняют оба target через наблюдаемый Compose boundary; cross-UID test проверяет тот же production wrapper/image и canonical PHP loaders.
- Полностью совместный live Make/MariaDB/Bitrix прогон остаётся обязательной задачей 3.1 до Gate 5, но Gate 2 теперь содержит executable fixtures для каждой части реальной цепочки, а не lexical/dry-run замену.

Нужны fresh plan/evidence и независимое повторное Gate 3 review; прежний verdict остаётся историческим.

## Issue #185 slice 2 Gate 3 correction rereview — 2026-09-18

- Reviewer: `/root/gate3_review`; independent `gpt-5.6-sol/low` agent; authored none of the reviewed specification or tests.
- Reviewed source: base `95e070893082422b067786abe6b6e5ff4ea3aa65` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T010426Z-2f553c3d0e/snapshot/source.patch`, SHA-256 `19bc15fde6326268ef742fc02386d6ddd0404b5242595f5ccd2f54256bed2ceb`; candidate-source digest `7612c678d8b27ec1e3fe571766e0413df47c00662c22af05dfd574ebdd29ffa6`.
- Rereview scope: disposition of all five findings in the immediately preceding Gate 3 review; no broader reopening.
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T010426Z-2f553c3d0e/package.json`; verification-plan SHA-256 `50ef55dad1f8e706137a3917c1750115cc841dfacd41d90a2b2f8a65678cad54`; lane `CRITICAL`; required reviews `gate3`, `final`.
- Evidence reviewed: intended RED records `1789693360441159000-43573babbcbc4615880fc14490464f30.json` and `1789693365272947000-427ecb029fa442ad8209814bb3231233.json`; GREEN records `1789693369131124000-3a678ed57c1f48bf9dffb4b1060b6c03.json`, `1789693430695401000-0682d94b91c244709bb82e148b2412ab.json`, and `1789693445529361000-91fcf83bbc0a4553943cd9f262e4cb3b.json`. All five records match candidate and executable-source digests.
- Verdict: `CHANGES_REQUESTED`.

### Previous findings disposition

1. **NOT CLOSED — synthetic end-to-end public-seam acceptance.** The correction adds the existing direct MariaDB legacy test and direct local-TLS Bitrix/workforce test as GREEN evidence, but neither invokes a Make target. `local_integration_env_001_test.py` still executes both targets only through a fake `docker` witness, while `local_integration_cross_platform_185_test.py` invokes the proposed wrapper directly. Therefore no executable test follows the normative `.env` → staging → Make/Compose → PHP loader → importer chain and then observes the MariaDB/workforce facts required by the delta spec at lines 46-52. The correction note itself defers the joint live Make/MariaDB/Bitrix run to task 3.1; Gate 2 cannot claim the complete agreed slice while that normative scenario has no executable public-seam test. **Required correction:** add the isolated test through both actual Make targets and assert the independently expected persisted facts/local endpoint behavior and no production connection.

2. **NOT CLOSED — actual Make/Compose delivery to canonical loaders/importers.** The new container test improves loader coverage, but still calls `docker run ... bin/fmonitor2-run-with-local-integration-config` directly (`local_integration_cross_platform_185_test.py:56-74`). Its Make coverage remains lexical (`:25-30`). Combining that test with a separate fake-Docker Make test does not catch disagreement in real Make/Compose flags, user selection, mounts, paths or cleanup wiring. **Required correction:** drive the runtime image/wrapper and both canonical Yii routes from `make import-legacy` and `make sync-workforce`, preserving the host-UID mismatch and UID 10001 assertions at the importer boundary.

3. **CLOSED — independent RED attribution.** The cross-UID record now fails first and specifically because the wrapper/delivery seam is absent, independently of the mode-portability RED. This establishes two distinct missing behaviors without treating unavailable Docker as RED.

4. **CLOSED — unsafe input and replay.** The security test adds symlink and FIFO inputs before destination publication; the container test repeats both kinds after replacing the host canary, requires only the updated value, preserves importer exit `23`, and verifies empty container storage after all runs.

5. **CLOSED — layer/log secret witnesses, within the submitted fixture.** Image history is inspected and actual canonical loader stdout/stderr is captured and checked for the canary. The test also checks removal of temporary containers. Final public-seam acceptance must retain these assertions, but no separate residual finding is raised here.

The fresh REDs are deterministic missing-behavior failures, the three GREEN fixtures are source-matched and isolated, and no evidence contains a synthetic secret. Findings 1 and 2 remain blocking because the contract explicitly makes the two Make targets—not the wrapper or direct Yii commands—the observable seam.

### Required changes

- Add one executable isolated acceptance path that starts at each actual Make target, traverses real Compose/container delivery and the canonical loader/importer as UID 10001, and observes the expected MariaDB/local-Bitrix facts. Capture fresh source-matched RED and request a narrow rereview; do not defer this Gate 2 contract coverage to Gate 5.

## Root second correction — issue #185 slice 2, 2026-09-18

Добавлен `tests/Deployment/local_integration_make_e2e_185_test.py`: он запускает реальный `make up`, создаёт legacy fixture schema в изолированной runtime MariaDB, выполняет фактические `make import-legacy` и `make sync-workforce`, поднимает task-owned verified-TLS Bitrix container в Compose network, проверяет сохранённые legacy/workforce facts и повторный sync с новым token. Тот же test проверяет Compose/application logs, temp cleanup и отсутствие production endpoints. Текущий independent RED — отсутствующая production delivery seam до создания каких-либо контейнеров.

## Issue #185 slice 2 Gate 3 second correction rereview — 2026-09-18

- Reviewer: `/root/gate3_review`; independent `gpt-5.6-sol/low` agent; authored none of the reviewed specification or tests.
- Reviewed source: base `95e070893082422b067786abe6b6e5ff4ea3aa65` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T010956Z-e4b982924f/snapshot/source.patch`, SHA-256 `99666a29d6aabc0e18b39273979a7f3ae933f757ae33ce2a090b5cf4a57325e1`; candidate-source digest `1922b2fd8b36adb9a96e1a21895e2f71fdbaac427220a341c0c0c5cfd89775d7`.
- Rereview scope: only the two residual HIGH findings from the preceding correction rereview.
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T010956Z-e4b982924f/package.json`; verification-plan SHA-256 `94d29dedba48da78d5cc14b1dc7f00b3d95b60c994876b9b45f63c1b0d8c2eb0`; lane `CRITICAL`; required reviews `gate3`, `final`.
- Evidence reviewed: intended RED records `1789693746852310000-90cc8b1ba1834a6e995edff8ccd69f18.json`, `1789693751351945000-0c5d559577854ff89614a5f84528f7f8.json`, and `1789693755168584000-f32bd2c5c993432793f3326bb2655a34.json`; GREEN records `1789693758620867000-2a20b00852bd4c19a65207b191600fdf.json`, `1789693767605025000-32279c93038c4347b1468ce6a6b1c3cc.json`, and `1789693781150253000-422b628eb7454696a2cf3492d6ad60fc.json`. Every record matches candidate `1922b2fd...` and executable source `75152767...`.
- Verdict: `APPROVED`.

### Residual closure and complete findings

1. **CLOSED — synthetic end-to-end public-seam acceptance.** `local_integration_make_e2e_185_test.py:59-67` runs real `make up` and `make import-legacy`, seeds only the isolated Compose MariaDB, and independently observes the expected legacy case. Lines 69-90 start a task-owned verified-TLS Bitrix endpoint inside the same isolated Compose network, run real `make sync-workforce`, observe 51 workforce rows, update `.env`, rerun the same public target and prove both old and new endpoint-token paths were received. All addresses, credentials, project/image/container names and persisted facts are synthetic and isolated.

2. **CLOSED — actual Make/Compose delivery to canonical loaders/importers.** The new E2E executes the repository Make recipes and real Compose commands rather than a fake Docker witness, so wrong mounts, paths, entrypoints, environment, loader selection or importer wiring fail at the public seam. Its success criteria are persisted importer facts, not command text. In combination with `local_integration_cross_platform_185_test.py`, which independently requires the same production wrapper/image to make a host-owned `0600` file readable at effective UID 10001 for both config kinds and invokes both canonical Yii routes, the matrix is sensitive to both wiring and privilege-drop behavior.

- Complete findings: none. The public seam, independently fixed legacy/workforce results, replay freshness, local-only endpoint, secret redaction, Compose/application-log boundary and cleanup are directly observable. The E2E RED fails before external effects because the required delivery wrapper is absent, which is the intended missing behavior rather than fixture failure. The retained direct importer GREEN records confirm the MariaDB and local Bitrix fixtures themselves are viable.

Gate 3 is approved for implementation against this exact reviewed test/spec snapshot. Gate 4 must make these tests GREEN without weakening expectations; exact-source final review and CI remain separate requirements.

## PR #190 interruption/tmpfs delta Gate 3 — 2026-09-18

- Reviewer: `/root/gate3_review`; independent `gpt-5.6-sol/low` agent; authored none of the reviewed delta specification or tests.
- Reviewed source: base `07cfbc4bae79f5fb8d774f4c25c4b38c83be6629` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T020043Z-b6b7159f22/snapshot/source.patch`, SHA-256 `3fca72de8ba6f3a26ddecbf8363359c09d769d99850f8948fa9af22efd0ee1b8`; candidate-source digest `be2fd27bcfef3d8a8c9d3265c0a8a36f0e7411200422a36a7995265868487782`.
- Scope: owner-requested TERM/INT/QUIT supervision, one-shot stop-signal alignment and tmpfs/SIGKILL persistence correction only; previously approved #185 behavior is retained scope.
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T020043Z-b6b7159f22/package.json`; verification-plan SHA-256 `c7841cc5d8d583ebf224539553a31a47fe358f99d20a1ab1d87724461cbbc779`.
- Evidence reviewed: intended RED `1789696747665213000-f4481a664715414c98e99fa8c2cdebb4.json`; GREEN `1789696751562124000-9a3dd9a3910f43d29f64166e75eb40d7.json`, `1789696760514609000-288b1abd68b744d78693415c9497609b.json`, `1789696766602735000-6918e5d5553b409e9b50cf068ecd2d4f.json`, `1789696817168916000-6e17657c48d24a0ea0563daf7bc14386.json`, and `1789696830011912000-b053db15aa194137b9211845a9a8d4be.json`. All records match candidate/executable source.
- Verdict: `CHANGES_REQUESTED`.

### Findings

1. **HIGH — TERM/INT/QUIT forwarding and child reaping are not observable.** The child at `local_integration_cross_platform_185_test.py:93` has no signal handler or externally observable acknowledgement. Lines 105-110 only require Docker to report the container stopped with a nonzero exit. A wrapper that terminates itself on TERM/INT/QUIT without forwarding or waiting will pass: Docker tears down the remaining namespace processes after PID 1 exits. The test therefore cannot distinguish the required supervision from the original unsafe behavior. **Correction:** make the synthetic child acknowledge each received signal through a non-secret external witness (or logs), delay/complete its handler predictably, and assert the wrapper/container does not finish before that acknowledgement/completion. Require the child PID to be gone/reaped and retain a nonzero wrapper status for every signal.

2. **HIGH — The normative `.ready` and SIGKILL cases are not exercised.** The delta spec requires `.ready` to remain absent after interruption and states that SIGKILL cannot persist the copy. The test never creates a delayed `.ready` witness and never sends SIGKILL; it only inspects tmpfs mounts after TERM/INT/QUIT (`:102-112`). That proves mount topology but not the specified interruption outcomes. **Correction:** use a task-owned non-secret witness directory where the child would publish `.ready` only after its long-running work; assert it remains absent after TERM, INT, QUIT and an actual SIGKILL run. For SIGKILL, assert exit is nonzero/kill-derived and prove there is no persistent secrets-volume mount or surviving copy after container removal.

3. **MEDIUM — Exact-source evidence does not establish retained success, exit-23 and replay behavior.** The sole cross-UID record stops at the first static tmpfs assertion (`local_integration_cross_platform_185_test.py:30`), before all existing successful reads, canonical-loader checks, updated replay and exit-23 assertions. The separate E2E GREEN retains public Make/MariaDB/Bitrix success and replay, but it does not cover the wrapper's direct UID read, exact exit `23` or cleanup assertions. **Correction:** split the new interruption/tmpfs RED from retained wrapper regression checks (or collect independent subtest results) so the package contains source-matched GREEN evidence for current success/UID/replay/exit-23 behavior alongside a RED attributable specifically to missing signal/tmpfs behavior.

### Checked and adequate

- The specification/design correctly scope tmpfs to one-shot integration containers, reject a persistent secrets volume, and preserve the production PHP-FPM stop policy.
- Static assertions require both Make recipes to select tmpfs and explicit `SIGTERM`; real-container tests are the right boundary once corrected.
- The package retains GREEN host staging, actual public Make E2E, MariaDB legacy and local verified-TLS Bitrix owner evidence. The delivery record accurately preserves the prior CI failure inventory and does not relabel the unrelated runtime failure GREEN.

### Required changes

- Close findings 1-3 and capture a fresh exact-source package with independently attributable interruption RED plus retained wrapper GREEN evidence before implementation.

## PR #190 interruption/tmpfs delta Gate 3 correction rereview — 2026-09-18

- Reviewer: `/root/gate3_review`; independent `gpt-5.6-sol/low` agent; authored none of the reviewed delta specification or tests.
- Reviewed source: base `07cfbc4bae79f5fb8d774f4c25c4b38c83be6629` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T020641Z-ce75280665/snapshot/source.patch`, SHA-256 `113ec70091ad4ada54491f8f5def20450ff53777b46c584b6411a9e7f4042e08`; candidate-source digest `4a36d40241d937d459a78fb08f04e3fe396350fda864eb391827f4828fe4abbd`.
- Rereview scope: disposition of the three findings in the immediately preceding interruption/tmpfs Gate 3 review.
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T020641Z-ce75280665/package.json`; verification-plan SHA-256 `5837eadc6356ba94c5fb8d6c179c97442385d9327e4a3c1de33e5eb05ee28e1c`.
- Evidence reviewed: intended RED `1789697083496066000-1298a2c126df4217af66466a5e034cb2.json`; retained GREEN `1789697087771961000-183e3ddf27f94475b57cbb9faf903dcd.json`, `1789697103970454000-76ac654248b74bb890b2f91a26e5be1d.json`, `1789697113962345000-b7d21d0765fc46a2b228692da04385d8.json`, `1789697123903369000-748e38fef0154f7aa5d15326f075ebf3.json`, `1789697174472365000-2ebc05d6f2f7451b8651a797d91352e4.json`, and `1789697187427729000-d1fe202b36f54f919862c52b527736f9.json`. All records match candidate/executable source.
- Verdict: `CHANGES_REQUESTED`.

### Previous findings disposition

1. **PARTIAL — forwarding/wait sensitivity is designed correctly but has no executable RED evidence.** The new child handlers at `local_integration_interruption_185_test.py:29` emit the exact caught signal, delay, then emit `CHILD_REAPABLE`; lines 49-50 would reject a wrapper that exits without forwarding or without waiting. However, the only captured run stops at line 17 because the Make recipe lacks tmpfs. None of the real-container signal assertions executes, so Gate 2 still does not demonstrate the missing supervision behavior. **Required correction:** separate the static Make/tmpfs oracle from the runtime signal probe (or collect all independent subtests) and capture a RED that reaches an existing wrapper, observes `CHILD_READY`, sends at least one supported signal, and fails specifically because forwarding/waiting is absent. TERM/INT/QUIT may remain one implementation-facing matrix once that boundary is independently reachable.

2. **NOT CLOSED — `.ready` and SIGKILL persistence remain unobservable.** `READY=1` at lines 29 and 49 means the private config file still exists inside the container; it is not the normative `.ready` completion marker, and no delayed filesystem `.ready` is ever created or asserted absent. After SIGKILL, lines 75-76 launch a brand-new container with a brand-new tmpfs and prove that new mount starts empty, which is true regardless of what the killed container stored. **Required correction:** bind a task-owned non-secret witness directory, have the synthetic child publish a `.ready` marker only after uninterrupted completion, and assert it remains absent after TERM/INT/QUIT/SIGKILL. For SIGKILL persistence, inspect the killed container's mount topology before removal and assert there is no named/bind persistent destination for the private copy; then remove the container and verify no task-owned persistent secret artifact exists. Do not use a fresh empty tmpfs as the persistence oracle.

3. **CLOSED — retained exact-source regressions.** Splitting `local_integration_interruption_185_test.py` from `local_integration_cross_platform_185_test.py` yields source-matched GREEN for cross-UID reads, both canonical loaders, updated replay, exact importer exit `23`, cleanup and image/log redaction. Public Make E2E, host staging and both DB owners are also GREEN on the same candidate.

### Required changes

- Close residual findings 1-2 and provide independently attributable RED evidence that reaches the real signal boundary. The retained GREEN evidence is adequate and need not be broadened absent a changed risk.

## PR #190 interruption/tmpfs delta Gate 3 third rereview — 2026-09-18

- Reviewer: `/root/gate3_review`; independent `gpt-5.6-sol/low` agent; authored none of the reviewed delta specification or tests.
- Reviewed source: base `07cfbc4bae79f5fb8d774f4c25c4b38c83be6629` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T021338Z-6278faaf3f/snapshot/source.patch`, SHA-256 `ac0904caabf2b39d4627c1a4b3d2310a5ed0bffd544ffa43a6d5c06b1633d6bc`; candidate-source digest `1b936e48fa4438ecba4428ea0ce8efb83d600abda2af680a38f7feea0e573f3a`.
- Rereview scope: only the two residual findings from the preceding interruption/tmpfs correction review.
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T021338Z-6278faaf3f/package.json`; verification-plan SHA-256 `e7eb9ca8098c96cf77e3582fbab3b007163ac5d0c6d8d5c60d71c3f62d99f766`.
- Evidence reviewed: intended RED `1789697491538970000-80e134b860e348889da4a0c6e7491b88.json`; retained GREEN `1789697507071950000-9d2b278f10e74fc9b28284fc2c0f6d49.json`, `1789697521868400000-9d880b4937ba485086cd4fbf17292da9.json`, `1789697531710373000-f121c57b5ce2410eae88d90797e660d1.json`, `1789697541420103000-bb34f8356ca64fbdbcf396925d6600d2.json`, `1789697591470696000-456b04aa33374f0485c496ce349ac95d.json`, and `1789697604338150000-0146e6adffaf4774b34de820e5f3cafc.json`. All records match candidate/executable source.
- Verdict: `CHANGES_REQUESTED`.

### Residual disposition

1. **CLOSED — independently attributable forwarding/wait RED.** The interruption test now builds and starts the current production wrapper before consulting the new Make assertions. The captured run reaches `CHILD_READY DELIVERED_READY=1`, sends TERM, and fails specifically because `CHILD_CAUGHT=15 READY=1` is absent. The child handlers' exact signal acknowledgement, 350 ms delay and later `CHILD_REAPABLE` marker make both forwarding and waiting sensitive once implemented; a PID 1 wrapper that simply exits cannot pass.

2. **PARTIAL — SIGKILL topology is now meaningful, but the graceful-signal mount oracle is self-contradictory.** The SIGKILL path inspects the killed container's topology before removal and prohibits a persistent volume at `/run/fmonitor-secrets`, then confirms removal, which closes the prior fresh-tmpfs tautology. The delivered config path is explicitly required to end in `.ready`, and the child confirms it exists only during execution. However, the fixture itself adds `compatibility_tmpfs` at `/run/fmonitor-secrets` on lines 26-27 so the old wrapper can reach the signal boundary, while line 51 asserts that **no mount of any type** has destination `/run/fmonitor-secrets`. Once forwarding is fixed and execution reaches line 51, Docker reports the fixture's own tmpfs mount and a conforming implementation fails for test setup rather than product behavior. The SIGKILL assertion at line 68 correctly expresses the contract by prohibiting only `Type == volume` there.

### Required change

- Change the TERM/INT/QUIT mount assertion to match the SIGKILL oracle: reject a persistent `volume`/bind-backed secret destination, not the fixture's explicitly ephemeral compatibility tmpfs (or remove the compatibility mount after arranging another way to reach the old wrapper). Capture a fresh RED at the same missing TERM-forwarding boundary and request a narrow rereview. No new retained-regression evidence is required.

## PR #190 interruption/tmpfs delta Gate 3 final rereview — 2026-09-18

- Reviewer: `/root/gate3_review`; independent `gpt-5.6-sol/low` agent; authored none of the reviewed delta specification or tests.
- Reviewed source: base `07cfbc4bae79f5fb8d774f4c25c4b38c83be6629` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T021723Z-45f6403b64/snapshot/source.patch`, SHA-256 `8296499a5d98b0e533c8324c5cd8b03d37cf0ac3655278285bada8e6b3fe2d87`; candidate-source digest `361c1022170b63f3c39437fa799a1bb68427cb58e94087395189d5294feaadad`.
- Rereview scope: only the residual mount-oracle finding from the preceding third rereview.
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T021723Z-45f6403b64/package.json`; verification-plan SHA-256 `55a78d2eca38e11d4963526d07b25608cd4407fc35ab1fb71263531c2fdbfa8d`.
- Evidence reviewed: intended RED `1789697714767103000-742f70a8ac7b440da91cece9f8453b53.json`; retained GREEN `1789697731476971000-433124b3b78c4a568b10f705bf182eb8.json`, `1789697746294423000-0d72545cd8214faf9e744f043c7c94e0.json`, `1789697756401158000-b260b80a36cf4e81b14adfb69a3bef59.json`, `1789697765531299000-a2c5717e6a724219a7d76f9e38458ab2.json`, `1789697816102380000-abb261dd97fa4dcc958a1e67f2d7c623.json`, and `1789697829399417000-48fc0e1065924bf399fd91a232e069df.json`. All records match candidate/executable source.
- Verdict: `APPROVED`.

### Closure and complete findings

- **CLOSED — graceful mount oracle.** `local_integration_interruption_185_test.py:51` now rejects only persistent `volume` or `bind` mounts at `/run/fmonitor-secrets`, matching the SIGKILL assertion and permitting the explicitly ephemeral compatibility tmpfs used solely to reach the current wrapper. The correction does not weaken the requirement for `/run/fmonitor-local-integration` to be tmpfs or the Make-target prohibition on persistent secrets storage.
- Complete findings: none. The executable RED still reaches the current production wrapper, confirms delivery through a `.ready` path, sends TERM and fails specifically because the child does not report `CHILD_CAUGHT=15`; it is not a setup or static-policy failure. The corrected matrix is sensitive to exact TERM/INT/QUIT forwarding, delayed child completion/waiting, nonzero interruption, one-shot stop-signal wiring, tmpfs topology and actual SIGKILL. Independent exact-source GREEN retains cross-UID success, canonical loaders, replay, exit `23`, cleanup, public Make E2E and both DB owners.

Gate 3 is approved for the interruption/tmpfs delta against this exact snapshot. Implementation must make the reviewed interruption test GREEN without changing expectations; independent final review and exact-source CI remain required.

## PR #190 Compose-service oracle green correction review — 2026-09-18

- Reviewer: `/root/gate3_review`; independent `gpt-5.6-sol/low` agent; authored none of the corrected test or implementation.
- Review mode: manual narrow GREEN correction review. Harness v1 cannot prepare a Gate 3 reviewer package for an all-GREEN correction and returns `Gate 3 requires at least one intended RED acceptance`; this tooling limitation is recorded, not treated as approval or waived evidence.
- Reviewed source: base `07cfbc4bae79f5fb8d774f4c25c4b38c83be6629` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T022353Z-cf096e6642/snapshot/source.patch`, SHA-256 `bca11f0022743b2a6d61784e0733efc4b2c839f1cfcf7efd1625225e55d29f4e`; candidate-source digest `43f876ecd752fb45beabde3eca19557e4505b5fb5bcd577ee27861b3f010a506`, executable-source digest `97ae1efd179efad8ee43530de8ad2b3d2e73ba044a51a06cf0ee680e5c006645`.
- Root package used for exact context: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T022353Z-cf096e6642/package.json`; verification-plan SHA-256 `2196812414033620e49a942aa03d4256edc0ee13f06751a34e9b4448edcab7e5`.
- Historical intended RED: `1789697714767103000-742f70a8ac7b440da91cece9f8453b53.json` on candidate `361c1022...`, which reached the production wrapper and failed specifically because TERM was not forwarded to the child.
- Current exact-source GREEN: interruption `1789698241209230000-9b9ede1efed245c19da1d3db80a1b9c5.json`; cross-UID/exit-23/replay `1789698254933112000-f205c02684a94b4a8d9d3a4a8b90943c.json`; env/preflight `1789698270038135000-ee700fdbeeec499d9006aabffd4acca0.json`; atomic/security `1789698279976821000-1cdabf9fb67748968e7be23a8d3b4bd1.json`; public Make E2E `1789698290155982000-e739a2d62d5d4d94b14e64010b9486f8.json`; legacy DB owner `1789698345716693000-2881a5e3ca474ed1b4754e5076a1e691.json`; workforce DB owner `1789698360048814000-c6fa82db872f44d3994ac9a2ffa121bc.json`. All seven are GREEN with candidate/executable digests matching the reviewed source.
- Verdict: `APPROVED`.

### Findings

- None. The corrected interruption test observes actual Docker tmpfs configuration through `.HostConfig.Tmpfs`, avoiding the platform-dependent omission of tmpfs from `.Mounts`. Its Make oracle requires both targets to select the dedicated `local-integration` Compose service; its Compose oracle requires that service's `SIGTERM`, tmpfs path and absence of the persistent runtime secrets volume. The runtime matrix still proves TERM/INT/QUIT acknowledgement and delayed child completion before wrapper exit, nonzero status, SIGKILL topology and removal.
- Retained behavior remains GREEN on the same exact source: host-UID-mismatched `0600` delivery to UID 10001, both canonical loaders, replay, exact exit `23`, cleanup, isolated MariaDB/local-Bitrix public Make E2E and both direct DB owners.

This manual review closes only the Compose-service/HostConfig oracle correction. It does not replace the required independent final code review or exact-source CI.

## PR #190 inherited-signal delta Gate 3 — 2026-09-18

- Reviewer: `/root/gate3_review`; independent `gpt-5.6-sol/low` agent; authored none of the reviewed delta specification or tests.
- Reviewed source: base `c1d99be631ad0b7d4302902bd22864381fba7be3` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T071421Z-91b6a4d2f2/snapshot/source.patch`, SHA-256 `021da6e2b702eaa0e09fef6c74d63543cdff65ebe6470f58b19ff2697f2ac814`; candidate-source digest `f2dbb9c548494eba6c635d19fbc90eb776549088da28e79329d12488f156cc98`, executable-source digest `219020aca23b22f430be33515d3213b245246d104f8f3b7fb82b2230c2920c2b`.
- Scope: two added semantics only—handler-free PHP must not inherit ignored INT/QUIT, and a signal-aware child cleanup `exit(0)` must not erase wrapper interruption. Previously approved signal forwarding/wait/tmpfs and ordinary success/exit-23 behavior are retained scope.
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T071421Z-91b6a4d2f2/package.json`; verification-plan SHA-256 `f25cfd92f68fb59160f2fc4796404125c329daab01e94891c940a895d41db870`.
- Evidence reviewed: intended RED `1789715524828456000-cdd90fb7a2104fedb6d79c516a1659e8.json`; retained GREEN `1789715542298365000-05d175469dbf48b58f42e62bc1ac68e3.json`, `1789715557833440000-ff929c4e0cbf40808d11627284ddfb16.json`, `1789715568161302000-dd1d8e08437f4bc8a890698ea95d4584.json`, `1789715579426308000-ed272f500f12414f81d34c4be9e758ac.json`, `1789715632083073000-535ac8f584e54224b0942f4996ea81ff.json`, and `1789715646441709000-787a52280a6c40c68a2ccb615d1eadf6.json`. All records match candidate/executable source.
- Verdict: `CHANGES_REQUESTED`.

### Findings

1. **HIGH — the child-`exit(0)` interruption rule has no executable RED evidence.** The captured interruption run stops at `local_integration_interruption_185_test.py:78` on the first handler-free INT case (`ordinary PHP INT became success`). Consequently the signal-aware zero-cleanup fixture at lines 83-102 is never started. That second behavior is independent: an implementation can restore default INT/QUIT dispositions yet still return child status `0` after a handled TERM, violating the new contract while satisfying the demonstrated RED. **Correction:** split default-disposition and zero-cleanup probes into independently runnable tests/subcommands, or aggregate independent subtest results, and capture a source-matched RED that reaches `ZERO_SIGNAL_RECEIVED` and `ZERO_CHILD_CLEANUP` then fails specifically because the wrapper reports success.

2. **MEDIUM — QUIT remains unexecuted in the submitted RED, though its oracle is otherwise adequate.** The serial loop fails on INT before starting QUIT. INT proves the inherited-background-disposition class exists, but does not demonstrate the exact QUIT branch required by the contract. **Correction:** make INT and QUIT independently execute and report, retaining `PLAIN_READY`, bounded wait, nonzero exit and absence of `PLAIN_NORMAL_COMPLETION`. One combined record is acceptable if it collects both outcomes before failing.

### Checked and adequate

- The normative delta correctly distinguishes ordinary child status from wrapper interruption state and requires explicit default INT/QUIT dispositions before exec.
- The handler-free fixture does not install signal handlers and independently observes readiness, termination, nonzero status and absence of normal completion. Its captured INT failure is missing behavior, not setup failure.
- Prior TERM/INT/QUIT forwarding and delayed wait, tmpfs/SIGKILL topology, cross-UID success, replay, exact exit `23`, cleanup, public Make E2E and both DB owners remain source-matched GREEN or retained in the reviewed test matrix.

### Required changes

- Capture independently attributable RED for both default INT/QUIT behavior and child-cleanup `exit(0)`, then request a narrow rereview. Do not change the approved expectations.

## PR #190 inherited-signal delta Gate 3 correction rereview — 2026-09-18

- Reviewer: `/root/gate3_review`; independent `gpt-5.6-sol/low` agent; authored none of the reviewed delta specification or tests.
- Reviewed source: base `c1d99be631ad0b7d4302902bd22864381fba7be3` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T071845Z-e9bf72643a/snapshot/source.patch`, SHA-256 `80d853c0a51615c4f61911b7d1a854e02d06c67aba4db401e09d022e26f536b2`; candidate-source digest `da1c940f9e2c0f766bc6f64fbf1146a1bb90d7da479bff55b831b84fe70f298f`, executable-source digest `8497edf70d6f7a06a3d013502e8b4b8ecbbbbbca45fa97653436dc354c216ee0`.
- Rereview scope: only the two findings from the immediately preceding inherited-signal Gate 3 review.
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T071845Z-e9bf72643a/package.json`; verification-plan SHA-256 `6842d03d3c6ac39ab321b70277b411ce369cd5080c36c202039fc087e19c25d7`.
- Evidence reviewed: intended RED `1789715789767981000-5918355878804817990aa186fba498a3.json`; retained GREEN `1789715807309019000-7eb752ac93304d25b06ac9eca1047afa.json`, `1789715822750692000-f8af12d550184d1faafa4405efcfca51.json`, `1789715832773119000-6ae4705a1f1945d8a501f25ea5916547.json`, `1789715844422087000-8e05f613d4a2451a8b7f1c4dc6c641e2.json`, `1789715892653089000-94768697a0aa41ee8b8a60c3d3e68bb2.json`, and `1789715906717004000-2999729c469d4b8192edcf0d4fda5937.json`. All records match candidate/executable source.
- Verdict: `APPROVED`.

### Closure and complete findings

- **CLOSED — independent default INT and QUIT sensitivity.** The corrected test accumulates outcomes instead of failing inside the first loop iteration. The captured RED confirms both handler-free children reached `PLAIN_READY`; INT and QUIT each became success and each reached `PLAIN_NORMAL_COMPLETION`. Thus both exact default-disposition branches execute and independently expose the missing behavior.
- **CLOSED — child cleanup `exit(0)` sensitivity.** The same run continues into the signal-aware TERM fixture, observes both `ZERO_SIGNAL_RECEIVED` and delayed `ZERO_CHILD_CLEANUP`, then records `child exit(0) erased wrapper interruption`. This is directly sensitive to wrapper interruption state independently of child status.
- Complete findings: none. Aggregation preserves deterministic cleanup and reports all missing behaviors only after the retained forwarding/tmpfs/SIGKILL and Compose-service assertions execute. Cross-UID success, replay, exact exit `23`, host staging, public Make E2E and both DB owners remain source-matched GREEN.

Gate 3 is approved for the inherited-signal delta against this exact snapshot. Implementation must make the aggregated test GREEN without weakening expectations; independent final review and exact-source CI remain required.

## Root interruption test correction — 2026-09-18

- Retained UID 10001 read, replay, canonical loaders, exact exit 23 and cleanup remain in the independently runnable cross-UID test and are expected GREEN at correction Gate 3.
- New independently runnable interruption test requires child acknowledgements for TERM/INT/QUIT while the delivered `.ready` path still exists, delays child exit and requires `CHILD_REAPABLE` before PID 1 exits; a wrapper that merely exits cannot pass.
- The new test actually sends SIGKILL, removes the killed container and verifies a fresh lifecycle has an empty tmpfs. It also proves neither Make target nor the one-shot container mounts the persistent runtime secrets volume.
- Static Make/tmpfs absence is isolated to the new intended RED and no longer prevents retained behavior evidence.
