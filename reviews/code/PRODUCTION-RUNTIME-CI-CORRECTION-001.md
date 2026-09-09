# Independent code review — PRODUCTION-RUNTIME-CI-CORRECTION-001

- Date: `2026-09-09`
- Reviewer: separately tasked agent `/root/runtime_review`
- Candidate base: `3cedf5cb`
- Verdict: **APPROVED**

## Complete failed-job inventory

The first PR #62 run was inspected before correction:

- fast: `development_setup_001_test.py` — 3 failures;
- governance: the same development setup failures plus
  `verification_inventory_001_test.py` — 16 and
  `verification_ci_001_test.py` — 7;
- integration 1/2: 10 files with stale canonical-frontier expectations;
- integration 2/2: 8 files: six stale-frontier suites, the real pilot-demo v21
  marker defect, and a synthetic verification fixture missing `tests/Runtime`;
- unit, e2e and plan passed; aggregate verify failed from the child failures.

Complete logs: `/tmp/pr62-fast.log`, `/tmp/pr62-governance.log`,
`/tmp/pr62-integration1.log`, `/tmp/pr62-integration2.log`.

## Reviewed identities

```text
8cf0cec947b8996b2a76ac22eba4207995259e88c0338b280c5c7aa1ed13f83f  app/demo/PilotDemoDatabase.php
c36a32d2b56b7be6b501fed7eae4f73969aa50637ee83c552e0de0df34e23209  bin/fmonitor2-pilot-demo.php
6b99acf4fb90aa4a07e0123a5a8f2776d6cc576bb57bb08d35084685e602903a  rapid-pilot/verify-calendar-projections.php
563b395b7e1f8fd7452e89b6a25cab7012c591f9a58527e1821438dd03f3a2db  tests/InstallationProcess/assignment_order_original_attempt_audit_schema_001_test.php
506cdb7d6d11a1f861933b0f2704a2ea3c4559893b22f9f9e7f8f0ac15593858  tests/InstallationProcess/checklist_template_schema_001_test.php
26afa680f9b451b7bed8fc549eb25f86c44b407b44304ccceaf49098b788745c  tests/InstallationProcess/classification_provenance_schema_001_test.php
abd1807724e7344b582108b6f3b06a120edc94aac95e75d43646c2d64716cdbd  tests/InstallationProcess/identity_access_schema_001_test.php
1be066044972eb6a781b37dd8e6adf0b6fcdd6c38fc3d6de65656595dd7281c7  tests/InstallationProcess/inspection_evidence_schema_001_test.php
b20784507e8832032e2484cd84ca819694ebdd0690204768bb55fea1b0bfedde  tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
d999c1f3fd3570723471b6a8acd99c34e9f4556cf424f8423971a9361e93bf37  tests/InstallationProcess/inspection_photo_content_index_schema_001_test.php
42affecb7754988b28891d148d5d09ca5056970396c8f860393d049ccae9a8b7  tests/InstallationProcess/inspection_planning_schema_001_test.php
e23799202355ff627b88c3a1b71f760e1fc9385e41425842bd25e20e8d0fa2d6  tests/InstallationProcess/installation_completion_schema_001_test.php
388cdb06ca14182f574cc36ac655e601f296ecf3dd7c12a9faa82d5a115cf4cf  tests/InstallationProcess/object_detail_snapshot_schema_001_test.php
cde44df4ccaf65d7d445ac37784845a211a297ade83a2f1c81a422f22cb6a765  tests/InstallationProcess/pilot_case_import_001_test.php
fcfc98cde1cb6629bbefbea8a77a9fac20560884ef656e4af547b5cfaee1a9e0  tests/InstallationProcess/pilot_demo_bootstrap_001_test.php
ee9aa3d0b0846ffafdf99ad762db6f5d1c2dd2870ebfc0c3ae2d5a8b9c31e1db  tests/InstallationProcess/pilot_http_auth_001_test.php
bcd151abb67dd145bdadd450e95de7f61d30a743357a83f410bb8ef1df291b80  tests/InstallationProcess/selection_canonical_registration_001_test.php
7f98fdb7ebe8223d345cc32d67b6f5abcd587fab47f12951a92094c40a454a0e  tests/InstallationProcess/workforce_canonical_runner_001_test.php
917c543b06040471a32a2ba6c075579f280048eeab41f6fedd61002c9f79505b  tests/Verification/harness_otiz_canonical_compat_001_test.php
02e7dbf42d6cba3303bd726ea6d5a44fe10f62a0dc1470536e245c7333985448  tests/Verification/quality_graph_ci_setup_001_test.php
ca481529b34da77ca283e0775c041f3a2bc1b3c49735661c5662db9c0f62d198  tests/Verification/development_setup_001_test.py
cba33fe594e615fb0f56483241a5818114ddd4a16f15a4919f7bbcfc0571e649  tests/Verification/verification_ci_001_test.py
d5d4379df4ade889b05b45037a740001848f83de704d52863a331a321f96eacd  tests/Verification/verification_inventory_001_test.py
ecd033b003afad26651eb39fde03cf6d0725ad2699ee476300550a2e57104618  tests/Verification/verification_native_suites_001_test.py
```

## Review and verification

Canonical runner assertions now advance only the aggregate terminal frontier to
v22; each originating migration's own version/conflict assertions remain unchanged.
The empty-prefix import fixture reuses the canonical v22 `fm_maintable` and inserts
only its original nine source columns.

The fictional demo deliberately retains a populated ten-column predecessor so v22
must add the exact seven columns. Canonical membership includes `fm_maintable`,
ready/status markers require 22, and only `users`/`users_roles` remain extras.
Marker, ownership, incomplete-schema, restart/reset and cleanup barriers remain.

Governance fixtures now include the generated runtime Dockerfile and Runtime test
directory, and exact inventory/E2E expectations account for every new verifier
without weakening protected historical digests.

```text
Focused exact CI-failure inventory: 18/18 PASS
development setup: 8/8 PASS
verification inventory: 15/15 PASS
verification CI: 15/15 PASS
pilot demo bootstrap: PASS
git diff --check: PASS
```

Focused aggregate log: `/tmp/pr62-focused-correction.log`.

## Verdict

**APPROVED.** The correction addresses every observed CI failure and the underlying
demo compatibility defect without changing domain behavior or weakening migration
ownership tests. It is ready to commit and push for one authoritative rerun.
