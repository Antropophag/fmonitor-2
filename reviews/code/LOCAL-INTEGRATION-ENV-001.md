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
