# Independent final Gate 5 review — LOCAL-INTEGRATION-ENV-001

- Reviewer: `/root/issue149_gate5`; authored none of the specification, tests, implementation, or Gate 3 decisions.
- Review date: 2026-09-17.
- Verdict: `CHANGES_REQUESTED`.
- Base: `19ae9d3ec02a801075add5e6db3f504585271e26` (`origin/main` at preparation).
- Exact candidate source: `48915af46b38c6e2a9c549d90c011bfc1ced89316768da1d791032aaf4afd3d4`.
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T123656Z-1d209329a1/package.json`, SHA-256 `352e71b9ea8b2f4642b1fd6130932f4719be966226de6745a6ca8b394a8d2577`.
- Immutable snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T123656Z-1d209329a1/snapshot`; `source.patch` SHA-256 `bad9bdcef48974ce59ad0ec9e7327adbf6dceb9ea79e141b0db7ec33d4530860`, `manifest.json` SHA-256 `032c673c38547f89a4ac5034115eeffef3b0e3293ef0e2cfa41fe663331384d8`.
- Required context SHA-256: `344fd732ae062c572af68a77a020f96d99b87f550330b2acc001311b060c4109`; task-context manifest SHA-256: `2cddd83aec3c0d624a31490876213d5a56c907ee352e1ec07f27835480053443`.
- Verification plan SHA-256: `3a751cdfdf0d3768a9eed4dd30a26d93fee6f08018188aefb284b45075e0b554` (`CRITICAL`; Gate 3 and final review required).
- Normative contract SHA-256: `6f7df1476dc44ce9ebdeaa57ec03cd164d80596d2c84b88d14dafb1b8bd197f6` (`specs/LOCAL-INTEGRATION-ENV-001.md`).

## Evidence and review scope

The package binds five source-matched GREEN records: both new deployment tests, the retained local-integration configuration contract, and both unchanged Yii2 owner DB contracts. I read the complete append-only Gate 3 history, including its four `CHANGES_REQUESTED` iterations and final `APPROVED` decision for source `531f586d42b3667d55f33d23530e4544ee2530eb06ee4614398b8cc662a22614`. I reviewed A1-A6, every public Make entry point including `import-production`, fail-closed ordering, parsing and quote behavior, private-file publication, symlink/mode/concurrency behavior, unchanged consumers, Compose/argv boundaries, documentation, and regression sensitivity.

As bounded supplemental checks on the same working source, `change_verification_001_test.py`, `runtime_storage_001_test.php`, and `architecture_guard_001_test.py` passed. The canonical local full suite was not run, per owner policy. Exact-source GitHub CI remains `UNKNOWN` and is not claimed as GREEN.

## Complete findings

1. **HIGH — staged legacy passwords are not value-preserving for the allowed quoted grammar.** `tools/delivery/local-integration-config:135-142` adds a wrapper only when at least one quote kind is absent. A valid double-quoted `.env` value whose intended password is `'a"b'` contains both quote kinds and is therefore emitted raw as `FMONITOR_SOURCE_PASSWORD='a"b'`. The unchanged owner strips matching outer single quotes at `app/YiiRuntime/LegacyImportConsole.php:42-47` and connects with `a"b`, a different credential. This violates A2's applicable exact private document, A4 changed-value replay, and compatibility with the existing owner. The GREEN tests use simple alphanumeric passwords and cannot detect this. Serialize every accepted password losslessly under the existing file contract, or reject values that cannot be represented before publication and align the normative grammar/tests.

2. **HIGH — Bitrix preflight accepts values that the existing `WorkerConfiguration` rejects only after Docker is invoked.** `tools/delivery/local-integration-config:145-158` accepts authority user-info and a token containing `.` because its URL regex is broader than `app/Workforce/WorkerConfiguration.php:15-22`, which rejects user-info and restricts the token to `[A-Za-z0-9_-]{1,256}`. A bounded probe with `https://user@portal.example/rest/7/token.with.dot/` returned success from `stage bitrix`, then `WorkerConfiguration::fromFile()` rejected the staged document. Through `make sync-workforce`, that failure occurs in the downstream Compose owner, after the preflight and Docker boundary, contrary to A3's requirement that the generated JSON be accepted by the existing owner and that invalid input fail before Docker/network/DB effects. Reuse the owner-compatible URL constraints in staging and add mismatch cases to the public-seam regression matrix.

3. **MEDIUM — the parser only partially enforces the prohibited shell-expression grammar.** `tools/delivery/local-integration-config:104-115` rejects `$(` and `${`, but accepts `$HOME`, positional expansions such as `$1`, and backtick command substitution literally. The contract precondition prohibits shell expansion and command substitution; the test matrix covers only `$(` and `${`. No evaluation occurs in this implementation, but accepting prohibited syntax weakens the declared input boundary and can produce private documents outside the approved grammar. Reject all prohibited expansion/substitution forms and add regression cases.

4. **LOW — the documented endpoint disagrees with the single canonical template.** `.env.example:5` sets `FMONITOR_HTTP_PORT=8093` (and its trusted host matches), while `docs/bitrix-startup.md:32` directs the operator to `http://127.0.0.1:8092/`. This breaks A6's one current operator path. Refer to the configured port or update the literal to match the template.

## Conforming areas

A1's template and public target ownership/order, the early dual preflight in `up-with-data`, atomic same-directory rename, exact `0700`/`0600` modes, static symlink/non-regular rejection, concurrent complete-snapshot visibility, old-file non-use after invalid input, read-only absolute file handoff, secret absence from Compose rendering/argv/environment, no reset, retained Yii2 owners, and the main replay documentation are otherwise consistent with the contract and inspected tests. The duplicated legacy/Bitrix validation paths in `local-integration-config` are a maintainability risk that directly enabled finding 2; consolidating validation would reduce future drift.

## Verdict

`CHANGES_REQUESTED`. Correct findings 1-4 without weakening the approved expectations. Because fixes require behavioral test additions for the quote, owner-compatibility, and parser grammar gaps, return those changed expectations through a fresh independent Gate 3 review, capture source-matched focused evidence, and prepare a new immutable Gate 5 package. CI, publication, merge, deploy, and settings remain outside this approval.

## Correction final Gate 5 rereview — 2026-09-17

- Reviewer: `/root/issue149_gate5`; independence is unchanged.
- Verdict: `CHANGES_REQUESTED`.
- Exact candidate: `d205b3aa12293b00cc690108eec51f07f853d4374cdfec18cfbecaa929a189bf`; executable source `6e4c7b25080b0752ae05a9f5a20f87bd09e3e3da6fba3354a108ebf312e3cb44`; base `19ae9d3ec02a801075add5e6db3f504585271e26`.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T125204Z-ad9769f346/package.json`, SHA-256 `92a3ef9423cfa2052b86915c80f0ddeb5446b24c50064e80da8710614911f7f9`.
- Previous snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T123656Z-1d209329a1/snapshot`.
- Correction snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T125204Z-ad9769f346/snapshot`; patch SHA-256 `89587a396dfa16ff8f91c354f3f8ac90d05f4c07c3eb524772fba80fd90f0d34`; manifest SHA-256 `7710daa1b1027aa7c60bfe2b22009b9747d18c6cb6ecb3b7a9254ff7219faba5`; delta SHA-256 `885ca026ab1c00c0d5335a62626bd5810c88c4454d8b3bd35b254393a72c72ec`.
- Required context SHA-256 `344fd732ae062c572af68a77a020f96d99b87f550330b2acc001311b060c4109`; context manifest SHA-256 `2d8d691958cfcdc2cca2d5e758ccc4106dd26d79df38a80366e33fd2bd00bcf7`; plan SHA-256 `a548b7cc7c883b0dab2d47ecd4b3763156387c2285089cb2eb78b1c9db4ba647` (`CRITICAL`).
- Fresh Gate 3: `CHANGES_REQUESTED` at `02b97ce4…`, then `APPROVED` at `6fa00710…` after quote-positive and 256/257-token sensitivity additions.
- All five package commands are source-matched `GREEN`; independent bounded reruns of both deployment tests passed. Full local suite was not run. Exact-source CI remains `UNKNOWN`.

### Prior findings disposition

1. **CLOSED — legacy quote losslessness.** `tools/delivery/local-integration-config:136-142` rejects both-quote ambiguity and wraps representable values with the absent quote kind; positive single-/double-quote tests and rejection coverage are present.
2. **PARTIAL — Bitrix owner compatibility.** User-info, dotted/empty tokens and the 256/257 boundary align, but the residual mismatch below remains.
3. **CLOSED — shell expressions.** Any `$` or backtick is rejected, with `$(`, `${`, `$HOME`, `$1`, and backtick cases covered.
4. **CLOSED — docs port.** Documentation uses `FMONITOR_HTTP_PORT` and shows the template's `8093`; the test derives the expected port from `.env.example`.

### Complete current finding

1. **HIGH — uppercase HTTPS passes preflight but the unchanged owner rejects it after the Docker boundary.** `tools/delivery/local-integration-config:150-160` compares `urlsplit(url).scheme` with `https`; Python normalizes that property to lowercase, while line 169 publishes the original spelling. Thus `HTTPS://portal.example/rest/7/token/` stages successfully with an uppercase `baseUrl`, but `app/Workforce/WorkerConfiguration.php:15-22` requires literal lowercase `https` and rejects it. A bounded exact-source probe returned staging `0` and owner `65` (`CONFIGURATION_INVALID`). This violates A3's pre-Docker owner-compatibility requirement, and the matrix lacks an uppercase-scheme case. Require exact lowercase input or canonicalize the published URL, then add an independently reviewed zero-downstream regression.

No other new regression was found. A1, A2, A4-A6 and remaining A3 cases conform. Python/PHP validation duplication remains a non-blocking maintainability risk and enabled this residual drift.

### Correction verdict

`CHANGES_REQUESTED`. Correct the uppercase-scheme gap, obtain fresh independent Gate 3 for the regression, capture source-matched evidence, and prepare another immutable Gate 5 package. CI, publication, merge, deployment, and settings remain unapproved.

## Final narrow correction rereview — 2026-09-17

- Reviewer: `/root/issue149_gate5`; independence is unchanged.
- Verdict: `APPROVED`.
- Exact candidate: `c110e59ed801fce673b55c8188181ff79e1f962025c970249e9fcedf176ffb72`; executable source `be39ddedc97e213ec906952a8c5f6c41988a929577ccb9f2e85c0673d766d051`; base `19ae9d3ec02a801075add5e6db3f504585271e26`.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T130045Z-c7b6bb162f/package.json`, SHA-256 `ae2bd8d33453e8f80375c1f8de7eaa92ded125d81f4ac1e825c6ce20125349f1`.
- Previous snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T125204Z-ad9769f346/snapshot`.
- Final snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T130045Z-c7b6bb162f/snapshot`; patch SHA-256 `af43128467eee25377c76f4dc2d51940dc1921485e66e5ff4287c38f0211ac4d`; manifest SHA-256 `ef343e27c63824eb8c5da3aaecd977389d7672124250cbbd8c58b389c619b6e4`; delta SHA-256 `e196ab13260c5b74925c1a04a2f233bed959998426207e5dbaf23d6a9c0a85cc`.
- Required context SHA-256 `344fd732ae062c572af68a77a020f96d99b87f550330b2acc001311b060c4109`; context manifest SHA-256 `b46f16deff80abd878b57a0f8240144dd1c1047854898334fb3803f02efe5f95`; verification plan SHA-256 `b420e6d7549491bcaddf888d7bcf771d4fe766e4be7c675834b1fd42f1e5b4de` (`CRITICAL`).
- Fresh Gate 3: `APPROVED` at source `2c915327dd2a33544454e627c579fda03070d2b70fade0846c1e845a64d3de48` after source-matched intended RED for uppercase `HTTPS`.
- All five package commands are source-matched `GREEN`. Independent bounded acceptance rerun passed; explicit probes returned `64` for `HTTPS` and mixed `hTtPs`, and `0` for exact lowercase `https`. Full local suite was not run. Exact-source CI remains `UNKNOWN` and is not treated as GREEN.

### Residual closure and complete findings

- **CLOSED — exact lowercase owner parity.** `tools/delivery/local-integration-config:155` now requires the original value to start with exact `https://` in addition to the parsed URL checks. This rejects uppercase and every mixed-case spelling before publication while preserving valid lowercase output unchanged for `WorkerConfiguration`. The approved regression places uppercase `HTTPS` in the existing zero-downstream invalid matrix; the predicate mechanically covers all mixed-case variants.
- The complete narrow delta changes only the normative/delta wording, approved regression, append-only review history, and this one production predicate. It does not weaken URL authority, user-info, query/fragment, path, token length/charset, department validation, secret handling, atomic staging, permissions, concurrency, Make ordering, existing owners, replay, or documentation.
- Complete findings: none. All original and correction findings are closed; the full exact source conforms to A1-A6. Python/PHP validation duplication remains a non-blocking future-maintainability observation, not a current conformance defect.

### Final verdict

`APPROVED` for exact source `c110e59ed801fce673b55c8188181ff79e1f962025c970249e9fcedf176ffb72`. This Gate 5 approval does not claim CI, publication, merge, deployment, or settings; exact-source CI remains `UNKNOWN` and must follow the owner-selected workflow.

## CI oracle correction final rereview — 2026-09-17

- Reviewer: `/root/issue149_gate5`; independence is unchanged.
- Verdict: `APPROVED`.
- Exact candidate: `af58ae69e9659b1d698829c5bd469480232cad6a3b17492dbca990cb9a4fa791`; executable source `52871ab219063a3c81ca316d5280e96e107e839910bce254f34891610ca003ad`; base `19ae9d3ec02a801075add5e6db3f504585271e26`.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T131707Z-8691313613/package.json`, SHA-256 `563d900bf05c73f69ebdaae9c76487c7b10f189dd9742a3440e28ae30d56220f`.
- Previous Gate 3 snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T131501Z-1bd6946a74/snapshot`.
- Final snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T131707Z-8691313613/snapshot`; patch SHA-256 `3bb02411bed5f70f6ee62b4affc5bde4c3176386c34eb97687139796b013ac47`; manifest SHA-256 `98016cc7e6c867e1681da9e85f3d0e9474ff4cca197ac182c6bc6a2417f85e75`; final delta SHA-256 `cc0f846afc1313754b35e9329a8991b6d25ccdebc92c116f0774aa84b12c7653`.
- Required context SHA-256 `344fd732ae062c572af68a77a020f96d99b87f550330b2acc001311b060c4109`; context manifest SHA-256 `c8b1059998e76bd42ea93406a198f964931432cc0ca103063576cf31b2f46a70`; plan SHA-256 `5c2dfbe286a5f8663c7fd7a6c5ceb32c5e6b3485b1308d5122aeaa61109f89a9` (`CRITICAL`).
- Fresh Gate 3: `APPROVED` at source `f28b84654b3e49995b3cd9a9f45162a3271479bb2a605da0cc60f41edbaea463` for the corrected quickstart oracle and mapping.
- All six package commands are source-matched `GREEN`. Independent bounded reruns of `yii2_local_quickstart_boundary_001_test.py` and `local_integration_env_001_test.py` passed. Full local suite was not run.

### Delta review and findings

- The stale pre-#149 assertion that `.env.example` must omit Bitrix configuration is correctly replaced by positive witnesses for the Bitrix and legacy groups required by the accepted single-`.env` contract.
- The quickstart invariant is not weakened: the oracle now explicitly rejects `local-integration-config` in the plain `up` recipe. The mapped executed `up-with-data` contract complements this direct check by requiring exactly one `up`, then exactly one legacy owner and one workforce owner in order, so indirect integration execution from `up` would also fail.
- The corrected architecture test is explicitly planned and mapped to A1-A3-A6. The complete eight-key template, independent seams, A4-A5 security behavior, and retained owners remain covered by the other five source-matched GREEN checks.
- The final package delta after the Gate 3 snapshot contains only the append-only Gate 3 record. No production or normative specification behavior changed, no expectation was weakened, and no unrelated impact was introduced.
- Complete findings: none.

### Final correction verdict

`APPROVED` for exact source `af58ae69e9659b1d698829c5bd469480232cad6a3b17492dbca990cb9a4fa791`. The historical exact-source CI failure identified the obsolete oracle; this approval verifies its correction but does not represent the broader CI state as GREEN. CI triage/publication, merge, deployment, and settings remain separate workflow steps.

## Issue #185 slice 2 independent final Gate 5 review — 2026-09-18

- Reviewer: `/root/gate5_review`; independent reviewer, authored none of the specification, tests, implementation, or Gate 3 decisions.
- Verdict: `APPROVED`.
- Base: `95e070893082422b067786abe6b6e5ff4ea3aa65` (`main` after merge #189).
- Exact candidate source: `d3661ac140c8514a125a471b8a6c962a343568489e36b8eb0b628d6c98644c9b`; executable-source digest `e846c5d7ac64907ba43374c998c895c5e2d2036ffc7e951d698b1a3beda3ca60`.
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T013530Z-4106ad0fa3/package.json`; verification-plan SHA-256 `3bb9ed8e7dd9840ec7e46f356cd475ced925e2ec77c948859fb3c39d1cd1c376` (`CRITICAL`; reviews `gate3`, `final`).
- Immutable snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T013530Z-4106ad0fa3/snapshot`; patch SHA-256 `b4c81c0a8053235eeefc38ef2609b031e0c8c355b26a3fe50015b26bc147ef2b`.
- Required-context SHA-256 `64066bf33612e5e676ef972040f01e268236f2368af9d57e0aa667bf25781e5d`; task-context-manifest SHA-256 `a4208f4215c68ec13957430d521fd0bd4e40273f2d8b6b51a671ef1f0f2b8a16`.

### Evidence and complete review

All eleven package records are source-matched GREEN. They cover portable `0644` input and `0755` staging, unsafe input/destination rejection, atomic publication and replay, a host-owned `0600` file read by effective UID/GID 10001, preserved importer exit `23`, empty container secret storage after success/failure, both real Make/Compose targets against isolated MariaDB and a task-owned verified-TLS Bitrix endpoint, both retained importer DB contracts, loader compatibility, generated Dockerfile parity, architecture/governance checks, and the CI-consumer oracle. Full local `make test`/`make verify` was not run, in accordance with the owner prohibition. WSL was unavailable and remains `UNKNOWN`; the recorded Docker Desktop/Linux-container exercise does not claim a full OS matrix. Exact-source GitHub CI remains `UNKNOWN` and is not represented as GREEN by this review.

I reviewed the complete candidate against the owner scope, normative A4-A6 contract, OpenSpec delta, approved Gate 3 history, implementation and test sensitivity. Host staging no longer gates valid accessible regular files or directories on exact POSIX mode bits, while retaining format validation, non-regular/symlink rejection, same-directory temporary publication and atomic replacement. Neither the source `.env` ownership nor its mode is changed. Both Make targets mount only the staged host snapshot into a root-only delivery step; the wrapper copies to a unique private file in container-owned storage, assigns `10001:10001` and `0600`, then invokes the unchanged canonical importer as UID/GID 10001. The legacy and workforce loaders accept absolute readable regular non-symlink files without repeating the removed exact-mode predicate.

Cleanup uses unique names for concurrent invocations, removes both temporary and published container files, reports cleanup failure when the importer succeeded, and preserves a nonzero importer status when cleanup also fails. Secret material is file-delivered rather than placed in argv or Compose environment values; the acceptance checks cover stdout/stderr, resolved Compose output, image history, application logs and final volume contents. The optional local Bitrix CA follows the same copy/drop/cleanup boundary and does not alter production secret/session policy. The change does not modify import filtering, domain owners, harness/classifier/skip rules, architecture baselines, provisioning, schema, or other excluded #185 slices.

### Complete findings

None. The tests would fail for plausible regressions in exact-mode removal, Make wiring, direct host-file delivery, privilege drop, loader readability, stale replay, cleanup, exit-status masking, importer ownership, persisted synthetic facts, or secret disclosure. The implementation is bounded and maintainable for the stated local integration seam.

### Verdict

`APPROVED` for exact source `d3661ac140c8514a125a471b8a6c962a343568489e36b8eb0b628d6c98644c9b`. This approval covers Gate 5 only. It does not claim exact-source CI, publication, merge, deployment, production imports, external sends, or completion of the remaining parent #185 scope.

## PR #190 interruption/tmpfs delta Gate 5 rereview — 2026-09-18

- Reviewer: `/root/gate5_review`; independence unchanged; authored none of the delta specification, tests, implementation, or Gate 3 decisions.
- Verdict: `APPROVED`.
- Reviewed base: committed candidate `07cfbc4bae79f5fb8d774f4c25c4b38c83be6629` plus the prepared correction snapshot.
- Exact candidate source: `22df10142169dc32ba6a6ac7b3d68a7786546dcbf81f1536656de37ce19347a2`; executable-source digest `508a0224c4db58a14e78e1a2a04d22635f34fad58355a54d2f717ce9ca4b4417`.
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T023230Z-83f168e520/package.json`; verification-plan SHA-256 `3ae6b3a8d5b860793aab21e71d71c46e97c7634d90e3acf99fded42ab43230fb`.
- Immutable snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T023230Z-83f168e520/snapshot`; patch SHA-256 `f1b9850a5ffe65061fedc068572251211f4d09798dfda3118d70233e35a19dee`.
- Required-context SHA-256 `64066bf33612e5e676ef972040f01e268236f2368af9d57e0aa667bf25781e5d`; task-context-manifest SHA-256 `2504a4c681e32b8cc447a4e3133b172fe7a39ec0256b91566cb7f964ddb67653`.

### CI failure inventory reviewed

Exact-head CI run `35296146049` for `07cfbc4bae79f5fb8d774f4c25c4b38c83be6629` is retained as failed, not GREEN. The complete recorded inventory is: one `unit` `REGRESSION_FAILURE` from the stale exact-mode assertion in `tests/Deployment/yii2_local_data_bootstrap_001_test.py`; one unrelated `e2e` `REGRESSION_FAILURE` in `tests/Runtime/production_runtime_compose_001_test.php` where DB-restart readiness expected 200 and observed 503; aggregate `verify` failed because those categories failed. Plan, fast, both integration shards and governance were GREEN. The first failure is corrected in this candidate; the unrelated runtime failure remains unresolved evidence and is not silently converted into approval. No CI retry or broader CI GREEN is claimed here.

### Delta and retained-invariant review

The dedicated `local-integration` Compose service is correctly separated from `x-app`: it uses the runtime image and environment needed by the importers, runs only its fixed wrapper as root, stores delivered configuration in `/run/fmonitor-local-integration` tmpfs with `noexec,nosuid,nodev`, and does not mount the persistent `secrets` volume. Both Make targets select that service through the real Compose seam. `stop_signal: SIGTERM` is scoped to the one-shot service; the long-running `php` service remains unchanged on `SIGQUIT`.

The wrapper starts the UID/GID 10001 importer as a child, records its PID, forwards TERM, INT and QUIT, and loops around an interrupted `wait` until the child is no longer alive. It therefore collects the child's actual nonzero signal-handler result instead of reporting success or exiting before the delayed child completion. Normal success and exact importer failure `23` retain their statuses; cleanup failure can replace only a successful importer result. EXIT cleanup remains active for ordinary and graceful-signal exits. SIGKILL cannot execute cleanup, but the delivered config and optional CA exist only in the container tmpfs, so container teardown removes them without leaving a named-volume or bind-backed private copy.

All thirteen package records are source-matched GREEN. The new real-container interruption test covers TERM/INT/QUIT acknowledgement while the delivered `.ready` file exists, delayed `CHILD_REAPABLE` completion before wrapper exit, nonzero container status, actual SIGKILL, tmpfs topology, container removal, Make/service selection and absence of a persistent secrets mount. Retained exact-source evidence covers host-UID-mismatched `0600` delivery and UID 10001 reads, both canonical loaders, replay freshness, success and exact exit `23`, atomic host staging, real Make/MariaDB/local-Bitrix E2E, both DB owners, generated Compose parity, architecture/governance checks and CI-consumer registration. The tests remain sensitive to a wrapper that exits without forwarding, forwards the wrong signal, does not wait/reap, masks interruption, reuses persistent secret storage, changes the PHP service stop policy, or bypasses the dedicated service.

### Complete findings

None. The interruption/tmpfs correction conforms to the amended A4-A5 contract and does not weaken the previously approved mode portability, cross-UID delivery, cleanup/status, replay, loader, Make E2E, secret-redaction or scope boundaries.

### Verdict

`APPROVED` for exact source `22df10142169dc32ba6a6ac7b3d68a7786546dcbf81f1536656de37ce19347a2`. This is the independent delta Gate 5 decision only. Exact-source CI for the corrected committed candidate remains required; the earlier failed run and its unrelated e2e regression remain historical unresolved evidence until the delivery workflow records their disposition. Publication, merge, deploy, production imports and external sends are not approved by this review.

## PR #190 final CI-topology oracle Gate 5 rereview — 2026-09-18

- Reviewer: `/root/gate5_review`; independence unchanged; authored none of the correction.
- Verdict: `APPROVED`.
- Reviewed base: `41cd3014573c4a9fda5e756439164e1baa6b99be` plus the prepared topology-oracle snapshot.
- Exact candidate source: `31bbba2ca5d37c8a86dd54148799600ebcdb75feff60810d15ee08098bcf6e40`; executable-source digest `cb45f71180c0981c19a348e786688e1bf8a77f50f37aa3eb7feb2299a9fce423`.
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T025502Z-3787fa21d5/package.json`; verification-plan SHA-256 `48ae7ca7ce4ed1eb1038dfc2dcb17e2e5ccf172cb61bd1f84c0f72f583a7b9e6`.
- Immutable snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T025502Z-3787fa21d5/snapshot`; patch SHA-256 `6d7df353b6eee2784ab6bb61adedd32a8e13137a8c30366d0b898eb4e328ec77`.
- Required-context SHA-256 `64066bf33612e5e676ef972040f01e268236f2368af9d57e0aa667bf25781e5d`; task-context-manifest SHA-256 `cb075929ee9a7bf880a576cf5f2d999f174167f6be308a2239d595e8d9bf1f4b`.

### CI inventory and delta review

Corrected-head CI run `35299888379` for `41cd3014573c4a9fda5e756439164e1baa6b99be` had exactly one `REGRESSION_FAILURE`: the existing exact parsed-Compose topology test retained the pre-change service set and omitted the approved bounded `local-integration` service. Aggregate `verify` failed only because the e2e category contained that stale expectation. The previously observed runtime reconnect failure was GREEN in this run; unit, both integration shards, fast and governance were also GREEN. This review does not reinterpret that failed run as GREEN.

The correction is necessary because the repository's topology contract intentionally enumerates the complete generated Compose service set; accepting the new service without updating that oracle would leave CI deterministically red. It is also behaviorally sensitive rather than a permissive allowlist edit: in addition to adding exactly `local-integration` to the expected set, the test requires its fixed wrapper entrypoint, dedicated `SIGTERM`, `/run/fmonitor-local-integration` tmpfs and absence of any `/run/fmonitor-secrets` volume target. It continues to require byte parity between the generated Compose file and its template and retains all prior topology, dependency and rapid-pilot exclusions.

No production code, Compose topology, runtime behavior, normative specification, harness algorithm, classifier, skip rule or architecture baseline changes in this delta. The verification input merely registers the already-existing topology test in the bounded change paths. The delivery record appends the complete corrected-head failure disposition. All eight package evidence records are source-matched GREEN, including the corrected topology oracle, interruption supervision/tmpfs, cross-UID delivery, staging/security, public Make E2E and both canonical DB owners.

### Complete findings

None. The correction closes the sole current-head CI failure without weakening the topology boundary or expanding #185 scope.

### Verdict

`APPROVED` for exact source `31bbba2ca5d37c8a86dd54148799600ebcdb75feff60810d15ee08098bcf6e40`. This decision approves only the final topology-oracle delta. A new exact-source CI result for the committed correction is still required before PR-ready admission; publication, merge, deploy, production imports and external sends remain outside this review.

## PR #190 inherited-signal delta Gate 5 — 2026-09-18

- Reviewer: `/root/gate5_review`; independent of specification, tests and implementation.
- Verdict: `APPROVED`.
- Reviewed base: `c1d99be631ad0b7d4302902bd22864381fba7be3` plus the prepared inherited-signal snapshot.
- Exact candidate source: `b7c747dfbc87ab8212908edd5a8bebb8221b88cfd402cec4ce869fa64510b0d1`; executable-source digest `904625f0dc0d1408c2258cac5035bcced5e2662be2d0050a8057b38b283cc5bc`.
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T072550Z-bdc6df9d58/package.json`; verification-plan SHA-256 `63fbe650e040415018bffc64551ea0807ff556a2849a2a68106c72eb892dfffa`.
- Immutable snapshot patch SHA-256 `b4a4da9d8800981d32021dfe911a2e21d9cacfcfd9c5a44adfd77191bb9350dd`; required-context SHA-256 `64066bf33612e5e676ef972040f01e268236f2368af9d57e0aa667bf25781e5d`; task-context-manifest SHA-256 `9ec90e44a6a20ed9a8e6b16ab792be75b48a8e960251db0b99e1d6cebbab180b`.

The wrapper uses GNU `env --default-signal=INT,QUIT` immediately before `setpriv`; the real production image test reaches handler-free PHP readiness and proves both INT and QUIT terminate it without `PLAIN_NORMAL_COMPLETION`, which also establishes option availability in that image rather than only host-side syntax. Wrapper traps record only the first accepted interruption as `143`, `130`, or `131`, continue forwarding later signals without overwriting it, wait until the child is no longer alive, and apply that recorded nonzero result after reap so signal-aware child cleanup `exit(0)` cannot become success. In executions with no accepted wrapper signal, the child status remains exact; retained real-image evidence covers ordinary success and exact exit `23`. Existing cleanup precedence still preserves a nonzero importer/interruption status, and wait/reap plus tmpfs cleanup behavior remains intact.

All nine package records are source-matched GREEN. The interruption record covers default INT/QUIT behavior, handled TERM cleanup-to-zero, prior TERM/INT/QUIT forwarding and delayed reap, SIGKILL/tmpfs confinement and Compose-service invariants. Cross-UID delivery, replay, exact ordinary failure, staging/security, generated-runtime parity, public Make E2E and both canonical DB owners remain GREEN. The delta changes no importer, Make/Compose topology, tmpfs policy, loader, domain behavior, harness/classifier or architecture baseline.

Complete findings: none.

`APPROVED` for exact source `b7c747dfbc87ab8212908edd5a8bebb8221b88cfd402cec4ce869fa64510b0d1`. Exact-source CI, publication, merge and deployment remain separate workflow decisions.
