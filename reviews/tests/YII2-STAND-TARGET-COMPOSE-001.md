# Test review: YII2-STAND-TARGET-COMPOSE-001

- Reviewer: independent Gate 3 agent `/root/gate3_target_compose` (gpt-5.6-sol / low); did not author the specification or tests
- Test author: root, per the prepared delivery package and repository delivery process
- Reviewed source: candidate source `19ea965c73657b832f4e282f3b89fcf769b988843031b00d109bf2cb66e3a8cb`; executable source `5fc3a961707602d0f22d9c1aabbfe4cdcf1a71b9ca0c97ddd8950a819bd9de88`; base `687f23e798cc426c8ce314e196144dd3674e1c10` + retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T082247Z-94a2d8488e/snapshot/source.patch`, patch SHA-256 `bd6ce2d4ba34fde4e6ffbd4039da0c08be6c29f1efa169d1a8fad1a604f315ab`
- Agreed review scope / prior findings disposition: initial Gate 3 review of exact read-only target manifest validation and canonical Yii2 Compose only; backup, journal, reset, rollback and live deployment are excluded; no prior findings for this bounded change
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T082247Z-94a2d8488e/package.json`; immutable verification plan SHA-256 `6805b001173879237ee9a8888d35521bdc004919c0b3bf7e6225eddb29d3beef`
- Specification: `specs/YII2-STAND-TARGET-COMPOSE-001.md`; OpenSpec change `openspec/changes/yii2-stand-target-compose/`; bound verification input `openspec/changes/yii2-stand-target-compose/verification-input.json`
- Public seam: `python3 tools/delivery/validate-stand-target.py <manifest.json>` and parsed `deploy/runtime/compose.yaml`
- Red command and intended failure: `python3 tests/Deployment/yii2_stand_target_compose_001_test.py`, exit 1 because `tools/delivery/validate-stand-target.py` is absent (`INTENTIONAL_RED: exact target validator exists`); record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789287665886386000-614d9871c02f4fe5b8569358dae40e63.json`. Adjacent prepared evidence is GREEN for `php tests/Runtime/production_runtime_compose_001_test.php` and `python3 tests/Verification/verification_ci_001_test.py`; it does not approve the missing acceptance coverage below.
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **High — the invalid-manifest matrix is not complete or sensitive to most normative rejection families.** The specification requires rejection of missing and extra keys, wrong types, invalid compose paths, both invalid image fields, unresolved expressions anywhere, unknown services/volumes, default or neighboring projects, and missing or mismatched observed IDs (`specs/YII2-STAND-TARGET-COMPOSE-001.md:14-16`). The table at `tests/Deployment/yii2_stand_target_compose_001_test.py:10-15` covers one missing top-level field, no extra keys, no wrong-type case, no compose-path case, only `candidate_image`, one unresolved database value, duplicate but not unknown service/volume names, only literal `default`, and only an empty `observed` object. Partial validators can therefore pass. Add representative cases for every stated family, including nested missing/extra/type cases and each protected path/root boundary.

2. **High — canonical digest and exact wire-output requirements are not independently tested.** At `tests/Deployment/yii2_stand_target_compose_001_test.py:8-17`, output is immediately decoded with `json.loads`, so whitespace, key order, an omitted final newline, or other non-canonical output can pass despite the exact response contract. The valid digest is checked only as 64 lowercase hex characters and equality after reversing top-level insertion order; the expected SHA-256 is never computed independently from normalized JSON. Whitespace variants, nested key ordering, locale, cwd and ambient-variable independence are not exercised. Compare raw stdout bytes for both outcomes, independently calculate the expected digest, and run semantically identical manifests with varied serialization, nested order, cwd, locale and hostile ambient variables.

3. **High — the Compose test does not cover the acceptance seam described by A2.** `tests/Deployment/yii2_stand_target_compose_001_test.py:18-19` checks exact service names, shared image equality, three migration dependencies, two command substrings and global absence of `rapid-pilot`. It does not prove an immutable `name@sha256` image, the exact migration command, the web nginx/PHP-FPM entrypoint, MariaDB-health dependency for every consumer, DML-only versus migration principals, mounted secret files/no literal secret values, or exact database/artifact/session named-volume topology required by `specs/YII2-STAND-TARGET-COMPOSE-001.md:20-22` and explicitly claimed by verification acceptance A2. It also invokes `docker compose config` without a controlled required environment, making the future GREEN dependent on the reviewer's ambient environment. Parse with a complete isolated environment and add structural assertions for every promised service command, dependency, principal, secret and volume invariant.

4. **High — read-only/no-secret/no-external-call behavior is not observable in the test.** The before/after inventory at `tests/Deployment/yii2_stand_target_compose_001_test.py:8-9` watches only the temporary manifest directory. A validator can write in the repository, home, cwd, evidence root contents or another path and still pass; it can invoke Docker/DB/network tools or read arbitrary secret files because no guarded executable/file fixture detects those actions. The check also compares only path names, not bytes or metadata, so mutation of an existing file passes. Add guarded/fake external commands and canary files/directories with byte/metadata snapshots across every allowed location, and prove rejected input/secret values are absent from both output streams.

5. **Medium — two normative rejection concepts are not defined sufficiently to derive independent expectations.** The spec says a read-only manifest validator must reject a “neighbor project” and a “mismatched observed ID” (`specs/YII2-STAND-TARGET-COMPOSE-001.md:16`), but provides neither the neighbor-project rule nor an independent expected source against which a manifest's observed IDs can mismatch. With no Docker/DB calls, the validator cannot infer the truth of self-reported observed IDs. Define the comparison inputs/rules at the public seam (or narrow these claims to structurally valid, nonempty unique IDs and defer freshness/observation comparison to the later control plane), then add deterministic cases derived from that definition.

Confirmed without findings: the spec identifier and public seams are explicit; the test is isolated from the live stand and performs no deployment operation; the captured RED is exact-source and fails for the intended absent validator rather than broken setup; template absence/old topology can make the candidate red; the new test is registered once in both verification inventories; package, source-file and plan hashes match the prepared bindings. CI and deployment are `UNKNOWN` and were not treated as GREEN or approval.

## Required changes

Resolve all five findings, regenerate the bound verification plan/package, capture a new exact-source intended RED that reaches the corrected matrix as far as the absent behavior permits, and return the delta for independent Gate 3 rereview before implementation.

---

## Correction Gate 3 review — 2026-09-13

- Reviewer: independent Gate 3 agent `/root/gate3_target_compose` (gpt-5.6-sol / low); did not author the specification or corrected tests
- Reviewed source: candidate source `589c09e19a1d3b4b253b20658df762758af483cd2074015a2c2844e06017e56f`; executable source `4500309d5ab576f924e86d9188ef07a61d55fce2c90b44564e1effb7b0d33376`; base `687f23e798cc426c8ce314e196144dd3674e1c10` + retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T082856Z-39d1f9e652/snapshot/source.patch`, patch SHA-256 `839c80d27e15fe5638e938236127b8e21bcf20a85eb24156ef51f1301565441c`
- Correction package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T082856Z-39d1f9e652/package.json`; verification plan SHA-256 `5dadeef62ea65193633ed61318cf622dc7b93234e03306ddf72e078f721803d3`
- Exact-source evidence: `python3 tests/Deployment/yii2_stand_target_compose_001_test.py` is `INTENDED_RED`, exit 1 on the absent validator; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789288042987230000-b0a1bf13d6d84267b89a53f254a1a28b.json`. Adjacent Compose regression and verification inventory records are GREEN on the same source. `UNKNOWN` CI/deployment is not approval.
- Verdict: `CHANGES_REQUESTED`

### Prior findings disposition

1. **Partially resolved.** The matrix now includes top-level extra/type cases, compose relative path, both image fields, unknown and duplicate service/volume examples, and empty/duplicate observed collections. Nested missing/extra/wrong-type cases, an absolute non-canonical compose path, missing digest, unresolved values in other fields, and individual empty observed IDs remain insensitive.
2. **Partially resolved.** Raw output and an independently computed expected SHA-256 are now checked. Input whitespace, nested key order, different cwd/locale and hostile ambient-value equivalence are still not exercised; all validator calls use the same JSON serialization, cwd and environment.
3. **Partially resolved.** Controlled Compose variables and several structural assertions were added. Exact commands/entrypoints, secret-file mounts/no literal secrets, exact principal roles, and service-to-volume wiring remain unproved.
4. **Partially resolved.** Fake external executables, hostile ambient value and byte snapshots inside the temporary tree were added. Writes outside that tree and reads of a canary secret/file remain unobservable.
5. **Resolved.** The normative spec now defines observed IDs as structurally nonempty and unique and explicitly defers freshness/Docker comparison; the undefined neighboring-project rejection was removed.

### Current findings

1. **High — the Compose oracle still permits accepts nonconforming security and topology.** At `tests/Deployment/yii2_stand_target_compose_001_test.py:29`, `self.assertIn('secrets', service)` proves only that a Compose `secrets` property exists; it does not prove credentials are mounted files, that environment values contain no literal secret, which secret targets are used, or that web/jobs have DML-only credentials while migration has the exact separate principal. Merely asserting the two usernames differ allows reversed or arbitrary privilege roles. The test checks only declared volume names, not exact per-service mounts/targets, so services can omit persistence or mount the wrong volume while passing. Assert the complete parsed secret definitions, targets/read-only use, credential environment/file variables, exact principals, and exact service-volume source/target mapping.

2. **High — accepted service commands and web runtime remain weak substring checks or absent.** `tests/Deployment/yii2_stand_target_compose_001_test.py:29` accepts any migration/worker/scheduler command containing the named fragment, including extra unsafe shell actions or wrong arguments; it never asserts the web command/entrypoint implements the specified nginx/PHP-FPM runtime. Compare parsed command/entrypoint arrays exactly against the accepted Yii2 schema/jobs/web commands, including `--interactive=0`, and reject build/extra entrypoint behavior rather than relying only on global `rapid-pilot` absence.

3. **Medium — the manifest rejection/determinism correction remains incomplete.** Lines 15-22 do not exercise nested missing/extra/wrong types, missing image digest/value, absolute-but-wrong compose path, unresolved interpolation outside `database`, individual empty observed IDs, whitespace serialization, nested-order canonicalization, or equivalence under different cwd/locale/ambient environments. These are explicit requirements at `specs/YII2-STAND-TARGET-COMPOSE-001.md:14-16`; plausible partial validators still pass. Add representative sensitivity for each remaining family and serialize equivalent inputs independently rather than always through the same `json.dumps(v)` path.

4. **Medium — filesystem read-only and no-secret-read claims remain only locally sampled.** The byte snapshot at `tests/Deployment/yii2_stand_target_compose_001_test.py:13` covers only `self.r`; repository files and arbitrary `/tmp` paths can be mutated undetected. `HOSTILE_SECRET` checks non-reflection of one environment value but not the normative prohibition on reading secret values. Use a bounded sandbox/guard or explicit repository and allowed-root snapshots plus a read-denied/canary secret fixture so forbidden file reads and writes outside the manifest tree become observable. Exact invalid stdout already proves rejected input cannot be reflected there.

Confirmed: exact source/package/plan bindings match; corrected RED remains an intended missing-validator failure; inventory registration is coherent; no live Docker/DB/deployment operation was performed by the RED. The review remains bounded to target validation and canonical Compose and does not reopen backup/journal/reset/rollback/live deployment.

### Required changes

Correct the four current findings, regenerate the exact-source package/evidence, and return the bounded delta for independent Gate 3 rereview before implementation.

---

## Correction Gate 3 review v2 — 2026-09-13

- Reviewer: independent Gate 3 agent `/root/gate3_target_compose` (gpt-5.6-sol / low); did not author the specification or tests
- Reviewed source: candidate source `81d01197b54b1239ca48ae62f93934d35193d96f4b3273af05c3a29103701edd`; executable source `ff7ee866ebc6dfcc6e024f7e25e6eea9f3c5acab54b49bd0be84e0e77f9952d2`; base `687f23e798cc426c8ce314e196144dd3674e1c10` + retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T083532Z-9ae5e5c775/snapshot/source.patch`, patch SHA-256 `950923c3a321f8b9ba758f9010004002c089f9989b7d6e2fc0f37354562535e1`
- Correction package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T083532Z-9ae5e5c775/package.json`; verification plan SHA-256 `a4564daa7e4bb7420f42a78d9326fc024647011312f0a328246a98a6b44e740b`
- Exact-source evidence: `python3 tests/Deployment/yii2_stand_target_compose_001_test.py` is `INTENDED_RED`, exit 1 on the absent validator; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789288411111448000-57d58c3501864b24847bd90ae1565f07.json`. `php tests/Runtime/production_runtime_compose_001_test.php` and `python3 tests/Verification/verification_ci_001_test.py` are GREEN on the same source. CI and deployment remain `UNKNOWN`.
- Agreed corrected scope: preserve accepted `db`, `prepare`, `migrate`, `php`, `web`, `jobs-worker`, `jobs-scheduler` topology; inherited principals, secret/storage handling and volume behavior stay owned by the existing real Compose regression. New behavior is the exact read-only validator, byte-identical canonical template, exact Yii2 migration/jobs commands and successful-migration dependencies. Backup, journal, reset, rollback and live deployment remain excluded.
- Verdict: `APPROVED`

### Prior findings disposition

1. **Resolved for the corrected scope.** The spec no longer invents a replacement topology or redefines inherited principals/secrets/volumes. The parsed test preserves the complete accepted service inventory and shared application image; the existing GREEN Compose lifecycle remains the explicit owner of inherited storage/security behavior.
2. **Resolved.** Migration, worker, scheduler, PHP-FPM and nginx command arrays are exact; jobs include `--interactive=0`; application consumers require successful migration; byte-identical template/render and absence of a production `rapid-pilot` reference are asserted.
3. **Resolved.** The invalid matrix now covers missing/extra/type, relative and absolute-wrong compose paths, default/unresolved project, unresolved database, empty/mutable images, nested wrong shapes/extra/duplicate values and empty/duplicate observed IDs. Exact canonical output and digest are independently computed; top-level and nested key order, isolated cwd, fixed locale and hostile stripped environment are exercised. These representative cases are sensitive to each remaining normative family without dictating implementation internals.
4. **Resolved.** On the supported macOS review host, `sandbox-exec` denies writes outside the isolated tree; byte snapshots detect mutation inside it, and guarded external commands detect prohibited Docker/DB/network calls. Exact output prevents rejected-value disclosure and the hostile ambient secret is absent. The non-macOS fallback retains deterministic byte and executable guards.
5. **Remains resolved.** Observed IDs are structural only and freshness/Docker comparison is explicitly deferred; the undefined neighboring-project rule remains removed.

### Findings

None. The corrected normative scope, OpenSpec delta, verification input, generated plan, tests and exact-source evidence are coherent. Expected commands and canonical digest are independently derived, the public seams remain read-only and isolated, the RED is caused by missing behavior rather than setup failure, and inventory registration is present. Existing GREEN runtime evidence is used only for its explicitly inherited contract and is not treated as proof of the missing validator/template behavior.

### Required changes

None. Gate 3 is approved for source `81d01197b54b1239ca48ae62f93934d35193d96f4b3273af05c3a29103701edd`. This advances only the bounded slice to implementation; it does not imply Gate 5, CI, merge, deployment, reset or rollback approval.

---

## Post-implementation test correction review — 2026-09-13

- Reviewer: independent Gate 3/5 agent `/root/gate5_target_compose` (gpt-5.6-sol / low); authored neither specification, tests nor implementation
- Reviewed source: candidate source `ac8d765ba4bb8d8c5bc628d52b11fb4958f535f97996b9ecae832aaf57e351d5`; executable source `fa1029048b408a11fb33fc94968b5ad3e14d72ee7ec32da29287792ead4220f5`; base `687f23e798cc426c8ce314e196144dd3674e1c10` + retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T085830Z-cab7eaca21/snapshot/source.patch`, patch SHA-256 `58d0f76b9b6d18797db1bfc72f9dba4ed8aa3556760dd210dd27caef97f841f3`
- Correction package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T085830Z-cab7eaca21/package.json`; verification plan SHA-256 `1cf732961974acfaa1297b33a9814754ae00bfdb1ec583465a3c4bfd5ff5ccfd`
- Delta assessed after the prior Gate 3 approval: helper rename `run` to `invoke`, interpreter/profile portability changes, and the current exact assertion matrix. The sandbox now uses the active interpreter, disables bytecode writes, denies network and writes outside the fixture tree on macOS; these are valid improvements.
- Prepared exact-source evidence: target test, inherited runtime Compose test and verification inventory are GREEN. CI and deployment remain `UNKNOWN`.
- Verdict: `CHANGES_REQUESTED`

### Findings

1. **High — the rejection matrix misses a crashing wrong-type family.** `tests/Deployment/yii2_stand_target_compose_001_test.py:15` checks string image failures but no non-string image. A manifest with `current_image: []` reaches `IMAGE_PATTERN.fullmatch()` and produces a Python traceback, nonempty stderr and exit 1 instead of the normative exact `TARGET_INVALID` response with exit 64. This was reproduced against the reviewed source. Add non-string current/candidate image cases (and representative wrong types for other scalar/nested fields) so the test catches this public-contract failure.
2. **High — symlink rejection is only sensitive to a symlink at the final path component.** The test at line 19 covers `evidence_root` that is itself a symlink. It does not cover a normal child below a symlinked parent; the reviewed validator accepts that path as `TARGET_VALID`, although the contract rejects a symlink evidence root. Add a parent-component symlink case and define/assert canonical equality after resolution.
3. **Medium — declared determinism variants are still not executed.** `invoke()` always writes the same `json.dumps(v)` serialization and always uses one cwd, `LC_ALL=C` and one ambient environment. Line 22 varies key order only. The test therefore does not prove the spec's whitespace, locale, cwd and ambient-environment independence, despite the earlier correction record saying it does. Parameterize invocation over raw serialization, cwd, locale and hostile/empty ambient variants and compare exact bytes/digest.
4. **Medium — portable read-only fallback remains materially weaker than the approved claim.** When `/usr/bin/sandbox-exec` is unavailable, the test observes only its temporary tree and fake executables; writes elsewhere are invisible. Either provide an equivalent supported-platform guard or narrow the normative/platform claim and verification record honestly.

### Required changes

Correct the complete bounded matrix above, regenerate the exact-source package/evidence, and obtain correction Gate 3 approval before relying on the updated tests for Gate 5.

---

## Final correction Gate 3 review — 2026-09-13

- Reviewer: independent Gate 3/5 agent `/root/gate5_target_compose` (gpt-5.6-sol / low); authored neither specification, tests nor implementation
- Reviewed commit: `8f0a5eb3d4c5adf879f4af6dbc4e8c5ef9803e49`; candidate source `2c70ada9e94683a76fe70bf36ccf8f4fa419f8d64c6718261f92cab152503f04`; executable source `a5d8fbb8e5885e09df1f76d1808988808f5b0f3f5b355d7fc78a0f190c7f3400`
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T092154Z-079d8e6f31/package.json`; plan SHA-256 `030928a766fba1aacf58ef61621f3d334c0def8e38617f80e7a28666ca2194b3`; clean committed snapshot patch SHA-256 `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855`
- Verdict: `APPROVED`

### Exact-source evidence — 7/7 GREEN

1. Target contract — `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789290967438859000-5fbe634b26744d24acfcd48f5643213a.json`
2. Runtime Compose — `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789290974011421000-11dbc499ea0547d5afbf6fb8c4004e5b.json`
3. Verification inventory — `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789291078146063000-7f2730f6114b42db99c77aa5e58f7b32.json`
4. Pilot jobs Compose — `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789291135279022000-a687c3fe783a4fa49c6858a91c89cdff.json`
5. Change verification — `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789291189926954000-1b06622358804951b2c078a7c5b44080.json`
6. Architecture guard — `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789291222714149000-c84873d67b9b40a0aa0eeb32097dee05.json`
7. Dependency render check — `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789291253109296000-735f86304e904e5dac140ea46006a025.json`

### Disposition

All prior test findings are resolved. Image fields are type-checked before regex use. Canonical path acceptance requires the resolved path to equal the supplied normalized absolute path, and the regression uses a symlink to an otherwise permitted external parent. Canonical output is exercised across serialization, cwd, locale and ambient variants. The readable helper structure remains intact. On the supported review host, sandbox write/network denial is combined with byte snapshots and guarded external executables; the small validator is independently auditable and contains no write/process/network seam. Compose requires an explicit shared image with no mutable default, while the clarified contract permits an explicit temporary tag only for isolated build tests. The renderer seam and its check are executable.

No findings. This approval advances the corrected tests only; it does not approve CI, PR, merge, deployment, backup, reset or rollback.

---

## CI correction Gate 3 review — 2026-09-13

- Reviewer: independent Gate 3/5 agent `/root/gate5_target_compose` (gpt-5.6-sol / low); authored neither the correction nor the implementation
- Reviewed commit: `d9938aed0633c2b2d5ef730e43771931502fba32`; candidate source `d3503af58c85a9846b25a63bb0b99c6ec3c28dd2ff45e52278175e037b18333d`; executable source `80ad0d34865eb1d59d86d0a94434c3eb8d2578ec9aadee1d5745c42eec9cb6ec`
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T093910Z-c410d836c7/package.json`; plan SHA-256 `d57368d66fc71c7c76688cccce30849d17b6c07e18508e2853a2a236a7eaf3cb`; clean committed snapshot patch SHA-256 `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855`
- Prior CI `34749515238`: governance/fast failed only three `development_setup_001_test.py` cases because the isolated fixture omitted the newly generated `deploy/runtime/compose.yaml`; verify failed aggregation; unit was GREEN; integration/e2e were cancelled by owner request. That run remains failed/cancelled evidence, not GREEN.
- Verdict: `APPROVED`

### Correction and sensitivity

The correction is narrowly causal: `tests/Verification/development_setup_001_test.py` copies `deploy/runtime/compose.yaml` into the isolated checkout so every `render-dependencies.py --check` target is represented. The verification input adds the setup consumer to planned paths and A3 exactly once; the follow-up commit removes the accidental duplicate mapping. This preserves the renderer drift check rather than bypassing it, changes no production behavior, and directly catches removal of the fixture target.

### Exact-source evidence — 8/8 GREEN

`1789292020824000000-e1eb57791bc24a78bc304082ec3d167d`, `1789292025887178000-1958c0221a1647e2a4c9a92f0daa4db0`, `1789292115936169000-46265f5f8b214e83ad87513f8ce07177`, `1789292129323374000-a332aa33a952418eaabe21c7425c05f3`, `1789292183459456000-e5b7544d98cd413fa63dbb425fab1630`, `1789292233187160000-855c36259d4a47c7b048626392601299`, `1789292264470083000-9e15b7adc9544b45be42025ae369188a`, and `1789292293665798000-eb5f5038b823427ca44ef2f102528129` under `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/`.

No findings. A new complete exact-commit CI is still required; the prior failed/cancelled run is not superseded by focused evidence alone.

---

## Second CI correction Gate 3 review — 2026-09-13

- Reviewer: independent Gate 3/5 agent `/root/gate5_target_compose` (gpt-5.6-sol / low); authored neither the corrected test nor production implementation
- Reviewed commit: `db65ab5f886ddcb6878f19c60927d8d48a35b159`; candidate source `226ab5949a0de69aee21b3ddf9a4100c879a8e5a948b7630d6506ca8e0e0178e`; executable source `a7d01e62bcb98614a563cc78466e86d40e0e65c3e10a033ebb1427858a291e38`
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T100400Z-34f7f445a4/package.json`; plan SHA-256 `6d27fab0c36fac1a3e1a707d3fd7b82be18f08c966a39cd69ae40ef8070174f2`; clean committed snapshot patch SHA-256 `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855`
- Prior CI `34750011236`: governance, fast, unit, e2e and integration1 were GREEN; integration2 failed only the stale jobs-runtime topology expectation; verify failed aggregation. That run remains failed evidence.
- Verdict: `APPROVED`

The correction is sensitive and narrowly causal. The established jobs root contract now requires default `db,migrate,php,web`, exact `php bin/yii jobs/{worker|scheduler} --interactive=0` argv and `service_completed_successfully` migration dependencies. Existing profile, no-port, shared-image, graceful-stop, secret isolation and no-bootstrap assertions remain. Reverting any CI-observed stale expectation fails an exact assertion. The test is bound once under A2 and added to planned paths without widening production scope.

Exact-source evidence is 10/10 GREEN: records `1789293483932025000-bb8666898c53472fbdbbc8f431d62f5c`, `1789293489374983000-ced7724c5851442d9306c4ecdbab1115`, `1789293491728067000-b6175e16e77c4ed384c33e7848bd9b16`, `1789293596698012000-dd89c98af86b4832bc47dda7db635242`, `1789293612200440000-9a657044611544c4af5bb3060ea7dd61`, `1789293671421762000-ee12dd744c714f8f9f32c590af99f84a`, `1789293733624654000-7cf6465f6159465a8121b0fa0d4bd5c7`, `1789293757553901000-fb60201b4bb246458f74d7543b7a0dc1`, `1789293760239118000-ba74943a3fc04e71b719d2922d66da56`, and `1789293783905538000-61b0154014084976971f4af8f54cca01` under `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/`.

No findings. A new full exact-commit CI is required before merge-ready.
