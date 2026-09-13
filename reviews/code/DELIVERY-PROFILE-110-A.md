# Code review: DELIVERY-PROFILE-110-A

- Reviewer: Codex independent Gate 5 reviewer (`/root/gate5_profile_a`)
- Implementation author: separate executor (`/root/executor_profile_a`); reviewer authored neither specification/tests nor implementation
- Reviewed commit: `f5297097d59a07c19bb3cf40c0043a7a36986cef`
- Reviewed candidate source: `3b22788403b79e06f91b17288883a6fd3480c6e587588f241e7a80aaad3bad78`
- Reviewed executable source: `4d958630fe3d8da3f586a3906d3c12fb73a88b993f5c499e7c3c84337c205923`
- Review package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T143944Z-b57fd1cf2e/package.json`
- Retained snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T143944Z-b57fd1cf2e/snapshot` (base commit `f5297097d59a07c19bb3cf40c0043a7a36986cef`, empty patch SHA-256 `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855`)
- Agreed review scope: PR A only; three pinned profiles and the transparent launcher. Quality Graph adoption, `setup-runtime` retirement, orchestration, provenance models and PR B are excluded.
- Specification: `specs/DELIVERY-PROFILE-110-A.md`; OpenSpec change `openspec/changes/pinned-focused-check-profiles/`
- Approved test review: `reviews/tests/DELIVERY-PROFILE-110-A.md`, final bounded Gate 3 verdict `APPROVED`
- Supplied GREEN evidence: `php tests/Verification/quality_graph_ci_setup_001_test.php`, record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789310344456939000-4831567a77b5429b8ad1d4480d62e0e5.json`, exit `0`, duration `30.179734665999998`, source/executable source as above
- Reviewer verification: `python3 tests/Verification/change_verification_001_test.py` GREEN (16 tests); `php tests/Runtime/runtime_storage_001_test.php` GREEN; `python3 tools/delivery/render-dependencies.py --check` GREEN; `python3 tests/Verification/verification_ci_001_test.py` GREEN (16 tests); `openspec validate pinned-focused-check-profiles --strict` GREEN; `git diff --check origin/main...HEAD` GREEN
- Scope/LOC check: forbidden Quality Graph, harness, planner, inventory and `tools/verification/ci.py` files are unchanged; implementation/infrastructure delta is 128 changed lines (53 Dockerfile, 5 canonical pin additions, 66 launcher, 4 task-state edits), below the 500 LOC STOP threshold
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **Blocking — the built environment is not reproducible from the pinned inputs (DP110A-03).** `tools/delivery/Dockerfile.focused-checks:16-20` and `:29-34` execute `apt-get update` against the mutable Debian repositories, then install unversioned OS packages and compile PHP extensions against whatever package set is current at build time. The launcher builds the images independently on every local/CI machine (`tools/delivery/run-in-profile:29-44`) and reports the resulting local Docker image ID. Thus two clean builds from the same commit can resolve different Debian packages and produce different environments/image IDs despite identical pinned base-image digests and lockfiles. The supplied GREEN test only verifies one build and selected top-level runtime versions; it does not compare independent clean local/CI builds or protect the unpinned OS/build dependency frontier. This contradicts the core acceptance that local and CI use the same pinned reproducible container environment.

2. **Blocking — the exact-source Gate 4 evidence package is incomplete.** The prepared verification plan requires five focused commands before the full CI matrix, but the supplied package retains only the contract test record. In particular it contains no exact-source retained records for `change_verification_001_test.py`, `runtime_storage_001_test.php`, `render-dependencies.py --check`, or `verification_ci_001_test.py`. Reviewer reruns of those commands are GREEN, but they are post-package executions and do not replace the implementation author's retained Gate 4 evidence. CI is still `UNKNOWN`, correctly not treated as GREEN. Gate 5 cannot approve publication from a package that does not substantiate its own checked task 2.4 and complete focused plan.

No forbidden scope growth, command registry, selection/aggregation change, persistent evidence store, security privilege escalation, or LOC hard-stop violation was found. Argv is passed as an array, child exit `23` is preserved, invalid profiles are rejected before execution, and the compact result contains only the permitted fields.

## Required changes

- Make image construction reproducible without adding a framework: remove mutable package resolution from per-machine builds, or pin the complete OS/build input to an immutable artifact/snapshot so clean local and CI builds of a profile resolve the same environment. Add a bounded test/evidence comparison sensitive to two independent clean builds; do not introduce forbidden identity/provenance machinery.
- Re-run and retain the complete focused verification plan against the corrected exact source, then prepare a fresh Gate 5 package. Full Quality Graph remains the later exact-source CI step and must not be represented as GREEN until it actually runs.

## Revised-contract rereview — 2026-09-13

- Reviewed commit: `0f7f3a3e5ce9f50c29d4caf1acc5b9c3bd4da47a`
- Reviewed candidate source: `e1ce1dc069a3f619dc6ff869a76186ab0a09529b00a90cce92aa9ad8cbf2e2cc`
- Reviewed executable source: `acea153fd731da3ec66ce62a94d7b65e53e168cbbe483d441f88d944dac0a2e4`
- Review package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T160709Z-5159e903b9/package.json`
- Retained snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T160709Z-5159e903b9/snapshot` (base commit `0f7f3a3e5ce9f50c29d4caf1acc5b9c3bd4da47a`, empty patch SHA-256 `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855`)
- Revised specification/test approval: `reviews/tests/DELIVERY-PROFILE-110-A.md`, revised reproducibility-contract Gate 3 verdict `APPROVED`
- Exact-source GREEN evidence:
  - `php tests/Verification/quality_graph_ci_setup_001_test.php`: record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789315422407176000-37fac7fa38914e6c95bd1985888d61ae.json`, exit `0`, duration `79.36998662500001`
  - `python3 tests/Verification/change_verification_001_test.py`: record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789315510338641000-a72698de453a4e4ba8b0f7bfe63df21e.json`, exit `0`, duration `32.955278375`
  - `php tests/Runtime/runtime_storage_001_test.php`: record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789315540541305000-4a80c6171a8241f18518db22abe5c2d7.json`, exit `0`, duration `4.359219792`
  - `python3 tools/delivery/render-dependencies.py --check`: record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789315547534534000-9d2ef5241baf42ddbda9592e0155014b.json`, exit `0`, duration `1.064805125`
  - `python3 tests/Verification/verification_ci_001_test.py`: record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789315551289150000-f7e012d93d244b9a9ada8638136078fe.json`, exit `0`, duration `56.001391915999996`
- All five records identify candidate source `e1ce1dc069a3f619dc6ff869a76186ab0a09529b00a90cce92aa9ad8cbf2e2cc` and executable source `acea153fd731da3ec66ce62a94d7b65e53e168cbbe483d441f88d944dac0a2e4`.
- Scope/LOC check: forbidden Quality Graph workflow/manifests, `harness.py`, planner, selection, aggregation, inventories, `tools/verification/ci.py`, category semantics and `setup-runtime` are unchanged. Production LOC is zero; implementation/infrastructure is 135 changed lines (64 Dockerfile, 5 additions to the existing canonical pin file, 66 launcher), below the 500 LOC STOP threshold.
- Revised verdict: `APPROVED`

### Findings disposition

1. **Resolved by explicit owner criterion and strengthened observed contract.** The owner explicitly permits independent builds to have different Docker image IDs/config/layer digests and excludes registry publishing/normalization. Under that normative boundary, mutable layer bytes are not a rejection. Every base image is consumed with a canonical registry `sha256` digest. The focused public-seam test observes all three profile names, PHP plus the canonical required-extension set, Python, Node/npm, Composer and uv versions, Composer packages against `composer.lock`, an offline/frozen Python environment against `uv.lock`, and browser-profile Playwright plus its installed locked Chromium revision/executable. No parallel dependency manifest was introduced.
2. **Resolved.** The fresh package contains all five focused commands from the prepared plan, GREEN against one exact candidate/executable source. Full CI remains honestly `UNKNOWN` and is a post-review publication check, not evidence used for this approval.

The launcher remains a 66-line transparent adapter: it validates exactly three profile names, passes the submitted argv directly to `docker run`, propagates the child exit code, and emits only git SHA, argv, profile/local executing image digest, exit code and duration. Invalid profile/empty command handling, executing-container identity, successful/failing evidence and exit `23` are sensitively tested. No command registry, service orchestration, selection/aggregation ownership, persistent provenance/evidence subsystem or forbidden scope growth was found. No blocking security or maintainability findings remain for PR A.

## Required changes after rereview

None.
