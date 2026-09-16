# Delivery record — issue #123 slice A

- Owner authorization: 2026-09-16, выполнить подтверждённую спецификацию автономно до merge-ready PR; merge/deploy/settings исключены.
- Base: `11f0fcb446d8fbd0cbfa2c88caf2e0888b9819cf`.
- Contract: `specs/CONTAINER-COMPOSER-VISIBILITY-123-A.md`.
- Lifecycle: `openspec/changes/expose-container-composer-dependencies/`.
- Root author: scope, executable specification, verification input и tests — primary Codex session.
- Executor author: separate `gpt-5.6-sol / low` agent `/root/issue123_executor`; production commits `029673a2`, `03d81c98`, `503e12a0`, `f344b397`, `0ef231d8`, `47579912`.
- Independent reviewers: Gate 3 — `/root/issue123_gate3`; Gate 5 — `/root/issue123_gate5`, оба `gpt-5.6-sol / low` и не авторы проверяемых artifacts.
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

### Gate 3 rereview

- Corrected package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T023249Z-4cf39ef9e8/package.json`; exact source `615aeb9ad2b77b52cda63c1daef22a09375935130558fbda1f23e327b8001d61`.
- Evidence: new public-route test `INTENDED_RED` record `1789525887291964000-03bbfc36e3dc47e98eb96caa2532e7c4`; existing profile test `GREEN` record `1789525914660850000-4f5e1bf083ae422da978317104d528a3`.
- Independent reviewer append-only record commit `c056c1cb5563be29db94603b82b13772b3bed890`: `APPROVED`; case F reaches `run-in-profile` and detects hostile host fallback.

### Owner-authorized source-layout delta

- First executor commits retained: `029673a2`, `03d81c98`, `503e12a0`. They proved Yii/dependency visibility and lock/corruption behavior, but nested volume created an empty host `vendor/`; no completion was claimed and the mountpoint was removed.
- Owner decision 2026-09-16 explicitly authorizes bounded verification source-layout change only: reuse existing harness frozen snapshot, materialize exact candidate inside container-owned read-only source, retain separate locked dependency layer and no host application bind/dependency path.
- Root delta authorship: updated OpenSpec/design, executable spec A–O, newline oracle and directly related exact-source test adaptation. Previous Gate 3 approval does not approve this delta; a new delta review is required before implementation resumes.

### Gate 3 source-layout delta return 1

- Review commit `5c3e2671b313fbbb9ef397048d50eca64a9faa1c`: `CHANGES_REQUESTED` D1.
- Finding: default automatic-snapshot N compared container-reported digests only with one another, creating a circular oracle.
- Root correction: compute expected executable digest independently with existing host-side `harness.py source_details()` before launcher invocation, then compare container env, compact evidence and image label to that frozen expectation.

### Gate 3 source-layout delta approval

- Corrected package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T071235Z-d9c6a065e5/package.json`; exact source `81865c8282cc706fb61207e46a3a212b7acdc4d18ded57859d39dd1629ed41f6`.
- Evidence: A–O test `INTENDED_RED` record `1789542636575608000-98d1f019756c4f38a0c00cdad9895b3d`; profile evidence test `INTENDED_RED` record `1789542722353392000-301d097fe2dd48e5a17c278e09a995d9`.
- Independent rereview commit `8871ebb43874eeb4095caea641990076001ff027`: `APPROVED`, D1 resolved, no new findings.

### Gate 4 implementation

- Executor reused `tools/delivery/review-source.py capture/restore`, built only from the restored frozen source, bound the image/tag/labels to `source_details().executable_digest` and Composer lock identity, and removed the host application/dependency mounts. Execution uses a read-only root with disposable `/tmp`; `.local` is the existing explicit artifact seam (host bind only when already present, otherwise tmpfs).
- Final production commits through `47579912592fbe17f059433c296679b757e4be34`; changed production files: `tools/delivery/Dockerfile.focused-checks`, `tools/delivery/Dockerfile.focused-checks.dockerignore`, `tools/delivery/run-in-profile`.
- `python3 tests/Verification/container_composer_visibility_123_a_test.py` → 8/8 GREEN in `321.216s` after the final corrections. A–O reported `setup_failures_before_behavior: 0`; representative command durations were `0.505075–0.550048s`; source/dependency origins, frozen mutation/deletion/mode, lock/corruption failure, read-only source and unchanged host dependency inventories passed.
- `php tests/Verification/quality_graph_ci_setup_001_test.php` → `QUALITY-GRAPH-CI-SETUP-001 PASSED` in approximately `109s`. It was repeated after two relevant corrections: route uv cache to disposable `/tmp`, then expose the existing `.local` artifact seam required by the profile DB probe.
- `python3 tests/Verification/change_verification_001_test.py` → 18/18 GREEN in `22.900s`; `php tests/Runtime/runtime_storage_001_test.php` → GREEN.
- No local full `make test`/`make verify` ran. Gate 5 and exact-source CI remain pending root/reviewer work.

### Gate 5 return и correction

- Первый exact-source review commit `41a731dac5dc18f9b635d6bba61ce6d325a783af`: `CHANGES_REQUESTED`. F1: integration/browser Compose config читался из live host root после freeze.
- Root test delta commit `32c05957` добавил public-route oracle post-freeze Compose mutation. RED record `1789545399360630000-c5d196244f944f11963f7b43a4529e96` наблюдал `later-host-compose` в обоих profiles.
- Gate 3 delta rereview commit `363145139f8fe35c27274d934c4e2cee3ff9dee5`: `APPROVED`.
- Executor correction commit `a88a0f43d1b03002f094983a7d0a6270dfad30de` разрешает Compose config из restored `$materialized` candidate. Narrow D GREEN; полный A–O GREEN, setup failures до behavior `0`.
- Corrected exact source `1daed97dd8dce0a37abcc058258b723c88e8b33deaac5fc31afc5856e36e2699`, executable source `68cbbb96e22d1d17e33d2e8501b638febc5855bef83780981a7e06fb2e0235ae`; package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T081411Z-403c362167/package.json`.
- Source-matched GREEN records: A–O `1789546180836569000-0eef30e87e134e41bdccb3449a5a5ad2`; governance `1789546278873739000-c561bd2fbba54a9e800bae8416bdf04e`; exact-source identity `1789546389098807000-5ebbb335409b4ca3b2a7c135e504ea93`; runtime storage `1789546415603776000-eb5ba0dd3d654b659ee3861bcd5f62b2`.
- Independent Gate 5 rereview commit `79ef839b77df417d4ebe524ed81d689af17bb48f`: `APPROVED`, F1 resolved, no new findings. Review/docs bookkeeping after the approved artifact is enumerated here; executable code/tests are unchanged.
- Exact-source CI remains pending; it is not represented as GREEN before the existing consumer completes.
