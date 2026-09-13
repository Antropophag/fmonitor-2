# Test review: YII2-PRODUCTION-IMAGE-001

- Reviewer: Codex `/root/gate3_image_cleanup` (independent Gate 3 reviewer; did not author specification or test)
- Test author: root
- Reviewed source: candidate source `60eef2b3c36f8ba9933faabe62e47baa35c144471b58a09e988509981094e758`; executable source `db86eeaa3d2282e6caaa81da97a79e32c1a3c63b38a507e807e7112d053f8dbd`; base `1ce54b04f37d234a5b951cb53eb40a0b30e9b4a8` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T065311Z-b18e27c978/snapshot/source.patch` (SHA-256 `d49f9c539cb62af180a27cd959d4e03e62d0ae1f01d416f1443341acf89fe5c6`)
- Agreed review scope / prior findings disposition (for rereview): initial Gate 3 review of A1-A3; no prior findings
- Specification: `specs/YII2-PRODUCTION-IMAGE-001.md`
- Public seam: canonical production Dockerfile template/render, fresh-built image filesystem, packaged Yii console closed failure, and packaged Yii live endpoint/include frontier
- Red command and intended failure: `python3 tests/Deployment/yii2_production_image_cleanup_001_test.py` exited 1 because `tools/delivery/Dockerfile.runtime.in` still contains `rapid-pilot`; harness evidence record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789282383313391000-6bf395a6244eaf7ad8a533d92ae22bf.json` bound that run to the reviewed candidate/executable source. Reviewer rerun on the unchanged candidate reproduced `AssertionError: INTENDED_RED: production recipe retains rapid-pilot: tools/delivery/Dockerfile.runtime.in` with exit 1.
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **Blocking — A1/A3 secret-like-file exclusion is not sensitive to the specified regression.** `specs/YII2-PRODUCTION-IMAGE-001.md` requires secret-like repository files not to enter the image, and the OpenSpec allowlist scenario repeats that requirement. `tests/Deployment/yii2_production_image_cleanup_001_test.py` only rejects the directory roots `tests`, `reviews`, `specs`, `docs`, `tools`, `.git`, and `.local`. A recipe regression such as `COPY .env ./.env`, `COPY auth.json ./auth.json`, or another named secret-like file would pass that inspection while violating the normative contract. Add deterministic negative checks for the agreed secret-like path/name inventory (or narrow the normative requirement to an explicit, fully tested set). Location: `tests/Deployment/yii2_production_image_cleanup_001_test.py:31-35`; contract locations: `specs/YII2-PRODUCTION-IMAGE-001.md:15-18,36-38`.

The remaining Gate 3 dimensions pass: A1-A3 trace to one executable test; the selected recipe/image/CLI/live seams are public and implementation-independent; exact CLI/live values come from previously accepted contracts rather than the proposed recipe edit; missing `rapid-pilot`, missing required Yii assets, unsafe CLI/live drift, wrong uid, repository-root leakage, build failure, and generated-recipe drift are rejected; the test is isolated from production systems and performs no DDL/DML; and the captured/reproduced RED fails for the intended pre-implementation recipe reference before Docker availability can obscure the reason. The random image tag prevents collision and does not vary asserted outcomes.

## Required changes

- Cover the normative secret-like-file exclusion with an explicit deterministic inventory at the built-image seam, or revise Gate 1 so the acceptance statement exactly matches the deliberately bounded exclusion tested.
- Rerun intended RED, retain exact-source evidence, and request independent Gate 3 rereview before implementation.

## Correction rereview — 2026-09-13

- Reviewer: Codex `/root/gate3_image_cleanup` (independent; authored neither specification nor test correction)
- Reviewed source: candidate source `ab9936cb2341135ae4144253f81c8a905f473789d84a3e23cc3c2358debfc758`; executable source `994d12548b8ced6fb703507b06ab99a058057efac9fecf72ed3a9f3a74dfde7f`; base `1ce54b04f37d234a5b951cb53eb40a0b30e9b4a8` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T065638Z-f0757e051a/snapshot/source.patch` (SHA-256 `56141c22780f6d747764de8190715db22fc05a9f8c8724b07460ce51728c00a0`)
- Correction delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T065638Z-f0757e051a/delta.patch`
- Agreed review scope / prior findings disposition: rereview of the explicit secret-like inventory finding; no scope expansion or production implementation
- Red evidence: `python3 tests/Deployment/yii2_production_image_cleanup_001_test.py`, harness record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789282572169597000-70eaf8e7fa4f48de9a646c3ead6c46b7.json`, exit 1 at source `ab9936cb…` / executable source `994d1254…`; reviewer rerun reproduced `INTENDED_RED: production recipe retains rapid-pilot: tools/delivery/Dockerfile.runtime.in`
- Verdict: `APPROVED`

### Findings disposition

The blocking finding is resolved. Gate 1 now defines an explicit secret-like filename inventory, consistently in the normative specification and OpenSpec delta. The built-image inspection searches the application closure, excludes Composer vendor from repository-secret classification, and rejects every listed pattern. The earlier examples `.env*` and `auth.json`, as well as dump, backup, key, certificate and message patterns, are now deterministic failures at the public image seam.

Traceability, seam selection, regression sensitivity, independent expected values, rejected cases, determinism, isolation, and RED reason remain satisfactory. The correction did not alter production recipes or dilute the original assertions. No further findings.

### Required changes

None.

## Post-implementation test correction rereview — 2026-09-13

- Reviewer: Codex `/root/gate3_image_cleanup` (independent; authored neither the test changes nor implementation)
- Reviewed source: candidate source `7da4f52a33de8b825878e5038ec9cb851e103e2b9a9469a5d3ca355106082aa0`; executable source `154c3fc09ac4a9462ec63f7287ca7a11ecd84fa5024f12c0926ae28c027ad922`; base `1ce54b04f37d234a5b951cb53eb40a0b30e9b4a8` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T071713Z-b0701a69da/snapshot/source.patch` (SHA-256 `2d18192367b19d282b2f7ae3c817724880e790d157a376afad91b4691d9d8bfb`)
- Agreed review scope: test delta after the preceding approval — explicit secret inventory retention, fixture-only Yii cookie-validation key, replacement of CLI pseudo-request with a real packaged HTTP request, and corresponding specification/seam wording
- Historical evidence: intended RED record `1789282572169597000-70eaf8e7fa4f48de9a646c3ead6c46b7` exited 1 for the retained production recipe; post-implementation fixture record `1789282794988370000-0f0533f3da0945878e76f21f2eee9120` reached the live assertion and exited 1 before the required Yii fixture key was supplied
- Corrected evidence: record `1789283536421869000-bb4959c4c7fe4835afefb5c103c8ea56` is GREEN for the corrected image test at the exact candidate/executable source above
- Verdict: `APPROVED`

### Findings disposition

The explicit secret-like inventory is unchanged and remains fully asserted at the built-image application closure. The fixed literal `FMONITOR_YII_COOKIE_VALIDATION_KEY` is a fixture-only value passed to one ephemeral container; it is required to construct the packaged Yii web request and is neither read from nor written to a production system or image layer. It does not weaken the independent expected response.

The live check now starts the packaged PHP HTTP server and performs an actual `GET /health/live` with `Host: localhost`. Its bounded readiness loop, final `curl --fail-with-body`, exact stdout assertion, process trap, container removal and image cleanup keep the check deterministic and isolated. Removing the PHP include-list trace is consistent with the clarified contract: the filesystem assertion proves `rapid-pilot` is absent, while the real HTTP request proves executable Yii live behavior. Test registration as `e2e` and its verification-inventory assertion are appropriate.

No blocking or non-blocking findings remain for traceability, public seam, sensitivity, expected-value independence, rejected cases, determinism, RED lineage, or coverage mapping.

### Required changes

None.
