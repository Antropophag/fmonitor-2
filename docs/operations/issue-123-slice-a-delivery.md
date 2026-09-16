# Delivery record — issue #123 slice A

- Owner authorization: 2026-09-16, выполнить подтверждённую спецификацию автономно до merge-ready PR; merge/deploy/settings исключены.
- Base: `11f0fcb446d8fbd0cbfa2c88caf2e0888b9819cf`.
- Contract: `specs/CONTAINER-COMPOSER-VISIBILITY-123-A.md`.
- Lifecycle: `openspec/changes/expose-container-composer-dependencies/`.
- Root author: scope, executable specification, verification input и tests — primary Codex session.
- Executor author: pending separate `gpt-5.6-sol / low` agent.
- Independent reviewers: pending separate `gpt-5.6-sol / low` agents according to planner-required Gates 3/5.
- Exact-source CI/PR: pending.

## Bounded ownership check

- Candidate snapshot: `run-in-profile` bind-mount'ит repository root в `/workspace`; command выполняется с `--workdir /workspace`.
- Dependencies: `Dockerfile.focused-checks` copies canonical manifests в `/opt/fmonitor` и выполняет locked `composer install`, создавая `/opt/fmonitor/vendor` в image.
- Gap: Yii entrypoints `bin/fmonitor2-yii.php`, `public/yii.php` и existing Yii probes требуют repository-relative `/workspace/vendor/autoload.php`; parent `/workspace` mount не содержит host vendor и не проецирует `/opt/fmonitor/vendor`.
- Minimal boundary: existing profile launcher/image layout предоставляет container-only read-only child dependency view для repository-relative path; `/workspace` остаётся exact candidate source. Lock-bearing inputs участвуют в image identity; missing/corrupt layer fails closed.
- Rejected as out of scope: host install/vendor, cross-worktree copy/symlink, shared writable vendor, second manager/profile, entrypoint-by-entrypoint rewrite, Docker performance/caching audit.

## Evidence

### Planning / Gate 2

- `openspec validate expose-container-composer-dependencies --strict` → valid.
- Planner lane: `CRITICAL`; required reviews: `gate3`, `final`; required categories: `governance`, `integration`; integration command: CI-only `make test`.
- Root package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T021816Z-8af8a6989a/package.json`; plan SHA-256 `9b1bec285f1fa46d54931bcf6e467a9c7ea6d88be4fd0ccafa662f054b741ea0` (superseded after test/catalog addition).
- RED command: `python3 tests/Verification/container_composer_visibility_123_a_test.py ContainerComposerVisibilityTest.test_a_b_d_i_j_two_clean_worktrees_candidate_origin_and_warm_repeat`.
- RED result: exit 1; public command exit 255 before `YII_BOOTSTRAP_OK`; exact failure `require(vendor/autoload.php): No such file or directory`; compact runtime duration `1.1627s`; host vendor absent. This reproduces the already established T08 baseline `3/3` setup failures before behavior without re-running all three historical attempts.
- Full RED log retained outside checkout in the active session evidence; no local full suite executed.

Gate 3 / implementation / Gate 5 / CI evidence follows append-only.

### Gate 3 return 1

- Review `reviews/tests/CONTAINER-COMPOSER-VISIBILITY-123-A.md`, reviewer commit `aa9a401b9689c0b0e099b885574175f926182bdc`: `CHANGES_REQUESTED`.
- Blocking finding: case F fixture searched only the proposed `/workspace` install path, while RED source still installed at `/opt/fmonitor`; fixture failed before invoking public seam.
- Root correction: fixture now recognizes either bounded install location and corrupts the dependency layer at the actual current location, so both pre-implementation RED and post-implementation regression traverse `run-in-profile`.
