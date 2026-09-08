# PILOT-OBJECT-CARD-001 Spec-axis integration GREEN v2 — 2026-09-04

## Fresh Gate 4 composition

- Reviewed Gate 2 test head:
  `2d334efcdb81ff9facc311b6fb876aea4e2cd61b`.
- Independent Gate 3 record head:
  `f31860c2ebb6d291a28451a92ee95adf5222fe4f`.
- Gate 3 verdict: `APPROVED` in
  `reviews/tests/PILOT-OBJECT-CARD-001-spec-axis-integration-v6.md`.
- Production candidate:
  `e5c99ed96fee0a999560ffb67f474d66b111686c`.
- Frozen tests, specifications and reviews changed during Gate 4: none.

This record is append-only and supersedes the earlier Gate 4 GREEN only for the
changed integration composition. No prior Gate 3 or GREEN verdict is reused.

The configured card now always uses the shared UI shell, independently of
`canPrepare`. The compatibility renderer remains selected only by the existing
configuration flag. A resolved active user with at least one active role may
read the card without either a local permission or a process capability. The
identity directory validates role activity through its existing public read
composition and introduces no new SQL ownership exception. A permissionless
reader still receives the current Objects navigation item because it is the
admitted current screen.

All earlier bounded production behavior remains present: exact five semantic
groups, newest three durable events, no artifact-table read, exact
`needs_assignment_change` label, sole eligible upload-first action, no action
for broad/wrong-state/PTO cases, and ordered source-only `navigation.js` then
`object-details.js` scripts.

## Verification

At `2026-09-04T12:30:48+03:00`, on exact production SHA
`e5c99ed96fee0a999560ffb67f474d66b111686c`:

```text
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/pilot_object_card_001_test.php
PASS: PILOT-OBJECT-CARD-001 public HTTP card

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/pilot_prepare_form_001_test.php
PASS: PILOT-PREPARE-FORM-001 public HTTP read-only form

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/local_rbac_auth_contract_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 public seam contract

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 real GET /pilot/objects admission

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/pilot_route_csp_001_test.php
pilot_route_csp_001_test: PASS

$ make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

$ make lint
exit 0

$ openspec validate remove-pilot-work-navigation-item --strict
Change 'remove-pilot-work-navigation-item' is valid

$ git diff --check
PASS (no output)
```

Exact bytes:

```text
e684d1daf0ad3ee69678632d32b06d60eb6aed57b4924454b75781c9a9620e5d  tests/InstallationProcess/pilot_object_card_001_test.php
a3fd80de9e9d4fda16b04f9ca545a7ede33ca1b85390120a3d977790dafc3d68  tests/InstallationProcess/pilot_ui_shell_001_test.php
ec5d7b438c6696950e09397ae3b129c9890b9182636d27650127532d5d979732  specs/PILOT-OBJECT-CARD-001.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
f18b804204c838965daf70c3aa81b3e2b609db67c5df58e658302aaf321c88d8  reviews/tests/PILOT-OBJECT-CARD-001-spec-axis-integration-v6.md
065d4bddd974cf8acec1becff5e85fda23adef728ef44b3b6f6f9a45a7ae647d  app/PilotHttp/ObjectCardView.php
39a27d0d686b0c548592d33acf9ce539d1b0a5909b38d059c9a93faba7ba816f  app/PilotHttp/PilotHttp.php
5f225ece450786d653badcf2f900fed23d42438740901aece904ef4aca471d25  app/PilotHttp/PilotView.php
```

Navigation removal and the broader UI-shell predecessor remain separately
owned work. No repository-wide `VERIFY_OK`, navigation GREEN, integration
completion or Gate 5 approval is claimed here.
