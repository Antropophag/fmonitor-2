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
