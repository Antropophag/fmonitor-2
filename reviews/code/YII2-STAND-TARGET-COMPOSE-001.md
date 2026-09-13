# Code review: YII2-STAND-TARGET-COMPOSE-001

- Reviewer: independent agent `/root/gate5_target_compose` (gpt-5.6-sol / low); authored neither specification, tests nor implementation
- Reviewed source: candidate source `ac8d765ba4bb8d8c5bc628d52b11fb4958f535f97996b9ecae832aaf57e351d5`; executable source `fa1029048b408a11fb33fc94968b5ad3e14d72ee7ec32da29287792ead4220f5`; base `687f23e798cc426c8ce314e196144dd3674e1c10` + retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T085830Z-cab7eaca21/snapshot/source.patch`, patch SHA-256 `58d0f76b9b6d18797db1bfc72f9dba4ed8aa3556760dd210dd27caef97f841f3`
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T085830Z-cab7eaca21/package.json`; verification plan SHA-256 `1cf732961974acfaa1297b33a9814754ae00bfdb1ec583465a3c4bfd5ff5ccfd`
- Scope: exact read-only target validator and canonical Yii2 Compose only. Backup, journal, reset, rollback and live deployment are excluded.
- Evidence: GREEN records for `python3 tests/Deployment/yii2_stand_target_compose_001_test.py`, `php tests/Runtime/production_runtime_compose_001_test.php`, and `python3 tests/Verification/verification_ci_001_test.py`, all on the exact package source/executable source. The generated focused plan additionally requires `pilot_jobs_compose_001_test.py`, `change_verification_001_test.py`, and `architecture_guard_001_test.py`; no records for them are present in this package. CI and deployment are `UNKNOWN`.
- Verdict: `CHANGES_REQUESTED`

## Spec axis

1. **High — wrong-type image input violates the fail-closed wire contract.** At `tools/delivery/validate-stand-target.py:127-130`, truthy non-string image values are passed to the regex and raise `TypeError`. Reproduction with `current_image: []` returned exit 1, traceback on stderr and no canonical invalid JSON, instead of spec section 2's exit 64, empty stderr and exact `TARGET_INVALID` response. Validate the type before regex matching and add the missing test family.
2. **High — a symlinked parent bypasses the evidence-root rejection.** `canonical_absolute_path()` at lines 81-90 tests only `path.is_symlink()` on the final component and then returns the resolved path without requiring the supplied path to equal it. A regular directory reached through a symlinked parent was reproduced as `TARGET_VALID`. Reject any path whose lexical absolute normalized form differs from its resolved form, with a regression test.
3. **High — the exact-source package is broader than the agreed candidate.** Its source/snapshot includes the future `yii2-stand-cutover-rehearsal` specification, OpenSpec, test and review artifacts even though this review and normative section 4 explicitly exclude backup/reset/rollback/live stand. Preserve that WIP, but prepare a reconstructible package/source containing only this bounded slice before approval/publication.
4. **Medium — focused Gate 5 evidence is incomplete.** The immutable plan contains six focused commands, while the package contains records for only three. Run and retain the missing e2e/governance/unit obligations on the corrected exact source; `UNKNOWN` or absent evidence is not GREEN.

## Standards axis

1. **Medium, judgement call — duplicated canonical/rendered Compose has no regeneration seam.** `tools/delivery/compose.runtime.yaml.in` and `deploy/runtime/compose.yaml` are complete byte-identical copies, but repository search finds no renderer or command that owns regeneration; only a test compares them. This creates Duplicated Code/Divergent Change risk and conflicts with the design's statement that the output is generated. Add a deterministic documented generation command or make a single file the operational source.
2. **Medium, judgement call — the new test is unnecessarily opaque.** `tests/Deployment/yii2_stand_target_compose_001_test.py` compresses the fixture, sandbox, rejection matrix, environment and topology checks into 30 mostly single-line statements with names such as `T`, `r`, `e`, `v`, `q`, and `s`. This is a Mysterious Name/Data Clumps maintainability issue and makes failures/corrections hard to diagnose. Format it normally and extract descriptive helpers/cases without changing expectations.

No domain-history, authorization, external-call or live-deployment violation was found in the bounded implementation. The canonical template and rendered Compose match byte-for-byte, exact Yii2 migration/jobs commands and dependencies are present, and production Compose contains no `rapid-pilot` reference. These successes do not override the fail-closed defects, changed-test Gate 3 return, package contamination or incomplete evidence.

## Required changes

Fix both validator defects and corresponding tests; complete correction Gate 3; provide an isolated exact-source package with all generated focused evidence; add a supported Compose regeneration seam and make the test maintainable; then return the full bounded candidate for Gate 5 rereview.

---

## Final correction Gate 5 review — 2026-09-13

- Reviewer: independent agent `/root/gate5_target_compose` (gpt-5.6-sol / low); authored neither specification, tests nor implementation
- Reviewed commit: `8f0a5eb3d4c5adf879f4af6dbc4e8c5ef9803e49`; candidate source `2c70ada9e94683a76fe70bf36ccf8f4fa419f8d64c6718261f92cab152503f04`; executable source `a5d8fbb8e5885e09df1f76d1808988808f5b0f3f5b355d7fc78a0f190c7f3400`
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T092154Z-079d8e6f31/package.json`; plan SHA-256 `030928a766fba1aacf58ef61621f3d334c0def8e38617f80e7a28666ca2194b3`; clean committed snapshot patch SHA-256 `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855`
- Scope: exact read-only target validator and canonical Yii2 Compose only; backup, journal, reset, rollback and live deployment remain excluded
- Verdict: `APPROVED`

### Exact-source evidence — 7/7 GREEN

1. `python3 tests/Deployment/yii2_stand_target_compose_001_test.py` — `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789290967438859000-5fbe634b26744d24acfcd48f5643213a.json`
2. `php tests/Runtime/production_runtime_compose_001_test.php` — `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789290974011421000-11dbc499ea0547d5afbf6fb8c4004e5b.json`
3. `python3 tests/Verification/verification_ci_001_test.py` — `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789291078146063000-7f2730f6114b42db99c77aa5e58f7b32.json`
4. `python3 tests/Deployment/pilot_jobs_compose_001_test.py` — `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789291135279022000-a687c3fe783a4fa49c6858a91c89cdff.json`
5. `python3 tests/Verification/change_verification_001_test.py` — `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789291189926954000-1b06622358804951b2c078a7c5b44080.json`
6. `python3 tests/Verification/architecture_guard_001_test.py` — `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789291222714149000-c84873d67b9b40a0aa0eeb32097dee05.json`
7. `python3 tools/delivery/render-dependencies.py --check` — `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789291253109296000-735f86304e904e5dac140ea46006a025.json`

### Disposition

All prior Gate 5 findings are resolved: wrong-type images fail with the exact safe response; symlinked parent components fail canonical-path comparison; the package is clean and bounded; all generated focused obligations are present and GREEN; deterministic serialization/environment behavior is covered; Compose has an explicit required shared image and no mutable default; isolated builds may use only an explicit temporary tag under the clarified contract; `render-dependencies.py` owns template regeneration/check; and the test remains readable and diagnosable. Standards and spec axes are both approved. No new scope creep, security, history, authorization, integration-boundary or maintainability finding was found.

CI, PR, merge and deployment remain `UNKNOWN` and are not approved by this Gate 5 verdict.

---

## CI correction Gate 5 review — 2026-09-13

- Reviewer: independent agent `/root/gate5_target_compose` (gpt-5.6-sol / low); authored neither tests, correction nor production implementation
- Reviewed commit: `d9938aed0633c2b2d5ef730e43771931502fba32`; candidate source `d3503af58c85a9846b25a63bb0b99c6ec3c28dd2ff45e52278175e037b18333d`; executable source `80ad0d34865eb1d59d86d0a94434c3eb8d2578ec9aadee1d5745c42eec9cb6ec`
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T093910Z-c410d836c7/package.json`; plan SHA-256 `d57368d66fc71c7c76688cccce30849d17b6c07e18508e2853a2a236a7eaf3cb`; clean committed snapshot patch SHA-256 `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855`
- Verdict: `APPROVED`

### Failure inventory and disposition

CI `34749515238` was inspected as failed/cancelled evidence: governance and fast contained only the three development-setup fixture failures caused by the omitted generated runtime Compose target; verify failed their aggregation; unit was GREEN; integration/e2e cancellation was owner-requested. The correction adds that target to the established isolated fixture, binds the existing setup test under A3, and removes an accidental duplicate verification-input entry. It neither changes production behavior nor weakens/skips the renderer check.

### Exact-source evidence — 8/8 GREEN

1. Target contract: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789292020824000000-e1eb57791bc24a78bc304082ec3d167d.json`
2. Runtime Compose: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789292025887178000-1958c0221a1647e2a4c9a92f0daa4db0.json`
3. Development setup: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789292115936169000-46265f5f8b214e83ad87513f8ce07177.json`
4. Verification inventory: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789292129323374000-a332aa33a952418eaabe21c7425c05f3.json`
5. Pilot jobs Compose: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789292183459456000-e5b7544d98cd413fa63dbb425fab1630.json`
6. Change verification: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789292233187160000-855c36259d4a47c7b048626392601299.json`
7. Architecture guard: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789292264470083000-9e15b7adc9544b45be42025ae369188a.json`
8. Dependency render check: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789292293665798000-eb5f5038b823427ca44ef2f102528129.json`

Standards and spec axes are approved. No new scope, behavior, security, architecture or maintainability finding was found. A new full exact-commit CI must complete successfully before merge-ready; focused GREEN does not convert CI `34749515238` into GREEN, and deployment remains `UNKNOWN`.

---

## Second CI correction Gate 5 review — 2026-09-13

- Reviewer: independent agent `/root/gate5_target_compose` (gpt-5.6-sol / low); authored neither tests, correction nor production implementation
- Reviewed commit: `db65ab5f886ddcb6878f19c60927d8d48a35b159`; candidate source `226ab5949a0de69aee21b3ddf9a4100c879a8e5a948b7630d6506ca8e0e0178e`; executable source `a7d01e62bcb98614a563cc78466e86d40e0e65c3e10a033ebb1427858a291e38`
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T100400Z-34f7f445a4/package.json`; plan SHA-256 `6d27fab0c36fac1a3e1a707d3fd7b82be18f08c966a39cd69ae40ef8070174f2`; clean committed snapshot patch SHA-256 `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855`
- Verdict: `APPROVED`

CI `34750011236` is retained as failed evidence: all named axes except integration2 and verify were GREEN; integration2 contained only the stale jobs-runtime topology expectation and verify aggregated that failure. The correction aligns that adjacent root test with the already approved canonical Compose: required default migration, exact Yii2 worker/scheduler commands and successful-migration dependencies. It preserves all existing security, profile, shared-image and lifecycle assertions and binds the touched test once under A2. Production behavior is unchanged.

Exact-source evidence is 10/10 GREEN, in package order:

1. `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789293483932025000-bb8666898c53472fbdbbc8f431d62f5c.json`
2. `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789293489374983000-ced7724c5851442d9306c4ecdbab1115.json`
3. `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789293491728067000-b6175e16e77c4ed384c33e7848bd9b16.json`
4. `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789293596698012000-dd89c98af86b4832bc47dda7db635242.json`
5. `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789293612200440000-9a657044611544c4af5bb3060ea7dd61.json`
6. `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789293671421762000-ee12dd744c714f8f9f32c590af99f84a.json`
7. `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789293733624654000-7cf6465f6159465a8121b0fa0d4bd5c7.json`
8. `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789293757553901000-fb60201b4bb246458f74d7543b7a0dc1.json`
9. `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789293760239118000-ba74943a3fc04e71b719d2922d66da56.json`
10. `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789293783905538000-61b0154014084976971f4af8f54cca01.json`

Standards and spec axes are approved. No finding remains. A new complete exact-commit CI GREEN is still required before merge-ready; the prior failed run and current deployment `UNKNOWN` are not approvals.
