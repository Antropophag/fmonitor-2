# PILOT-BASELINE-CI-001 independent code review

- Reviewer: `agent:/root/linux_code_review`
- Implementation author: `agent:/root`
- Reviewed implementation commit: `633da7ae1d0929010281ce5f227b84d811c9e680`
- Approved transfer review: `819f6ee` / `agent:/root/linux_test_review`
- Transfer source: `9f530017ab769de4e6e1647cadb990281e34c0e9`
- Specification: `specs/PILOT-BASELINE-CI-001.md`
- Verdict: **APPROVED**

## Standards

No blocking finding. The change reuses the repository's existing Make and shell
verification seams. The workflow delegates setup, tooling validation and the full
harness to repository-owned commands instead of duplicating their implementation.
Its single job is bounded to 60 minutes, uses only `contents: read`, disables
persisted checkout credentials and retains full Git history for exact-source checks.

The implementation delta after approved Gate 3 contains exactly these five paths;
the GREEN record itself is evidence rather than implementation:

```text
65e4d3be33b0cab0cafd673191493add181323d4653c7fce37bc7c025589f3d1  .github/workflows/repository-verification.yml
6d2894b8b533643674bcfb46aafe53d3885552f369905e8e0ebedccc9d5c7cdb  Makefile
b37f7ce00617d55e318c8f53232ffe8002aaf79742de4bb14b30e8e12dfcc4ed  tools/delivery/ci-setup.sh
62d3e360873e22a188337a11d304b0e537c1ee13eb248f3371b42be79b453ea5  tools/verification/Dockerfile.test
7ba4a23e4c494cb41ccbed4bc32b44407bcfeb0fe04e3f241bf3b6522806f89c  tools/verification/run.sh
```

## Specification, security and integration

No blocking finding. The workflow has only `pull_request` against `main` and
manual `workflow_dispatch` triggers. Its action references remain exact commit
pins. PHP 8.5 extensions, Node `22.22.0`, uv `0.8.15` and Python `3.12.11` are
declared explicitly; the selected Python is checked before repository setup.
`make ci-setup` verifies the runner preconditions, installs and checks ripgrep,
builds the repository-owned test image, verifies exact TCPDF and shlz-ui revisions,
and builds the pilot image from the reviewed checkout.

`tools/verification/run.sh` now rejects missing `rg` before test discovery or list
output. `make test-tools` accepts an isolated image name and labels the image with
the exact Git HEAD. The image supplies PHP 8.5, `mysqli`, `pcntl` and
`util-linux`/`setpriv`, matching the approved Linux probes. The existing
`make fresh-test-verify` behavior remains the terminal workflow command, including
all nine verification stages, aggregate failure reporting and test-environment
teardown.

No Quality Graph package, manifest, checker, publisher, runtime action, approval
surface or write permission is imported. The historical filename
`quality_graph_ci_setup_001_test.php` is the only textual Quality Graph reference;
its reviewed contents exercise only baseline setup and the public test-image seam.
Files under `app/`, `bin/`, `public/`, `rapid-pilot/` and the pilot `Dockerfile` are
unchanged from PR41 base `12c96f674b0960617727dae72993615b61674929`.

## Approved test provenance and verification

The eight-test set approved at Gate 3 is byte-identical to source `9f53001`:

```text
752aa4765f35ea2a39b60afa779fa8a72b37898ce0380e10ca9a9d8c33bf055e  tests/InstallationProcess/pilot_object_list_001_test.php
571f958174a970007884ddec35098502a8455f52e3eb17d7ac268242df120f6b  tests/InstallationProcess/pilot_shlz_assets_001_test.php
e6fcbed10b086064228a151fa9ff70b0fecc744bd71f770b43653073f3d90943  tests/Support/PilotSafeAuthorizationLog.php
920f6a3ae580e52159cec5fad11eb90b7453d543a115695563cb7493044ffee7  tests/Support/SelectedOriginalFixture.php
abe0ad400bc535a7632277800aa88c65cf3ceda458d8836aa892e2b637f8c501  tests/Support/ShlzManifestCaptureProbe.php
bc1d5313cf710eab977b6b3ea507adcfd08fe5572edb4140262b96104f4a1f61  tests/Support/construction_control_completed_filter_browser.cjs
a87af3e38204c0f38dc1ed2f6ad2842c2a59456c62def8bd4ce7bc1abb09c357  tests/Support/shlz_manifest_capture_witness.php
3f61386a0fe582f142d3ff6e366f1081a4d339d2eaae559bad31cc90f8e3b4ab  tests/Verification/quality_graph_ci_setup_001_test.php
```

Focused GREEN evidence records exit 0 for the setup test, shell syntax,
architecture checks, YAML parsing and exact trigger/permission/pin inspection.
It also confirms byte-exact transfer of `ci-setup.sh` and `Dockerfile.test`, the
unchanged pilot runtime boundary, and a clean implementation diff. Private
`setup-green.log` SHA-256:
`566fb63dbd0c3a689b76d85d78dd325a0e0b24e3892b3e04c01ad26a567336b8`.

The actual PR41 GitHub `make fresh-test-verify` run remains pending and must supply
its own head, run, attempt and nine-stage result. This approval does not substitute
PR37 evidence, claim full CI readiness, authorize merge, change branch protection,
or authorize PR37/PR10 actions.

Blocking changes: None.
