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
