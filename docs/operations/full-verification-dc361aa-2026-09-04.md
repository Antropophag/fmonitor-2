# Full verification at `dc361aa`

Date: `2026-09-04`

Command:

```text
PATH=/opt/homebrew/bin:/Applications/Docker.app/Contents/Resources/bin:$PATH \
FMONITOR_TEST_DB_ADMIN_PASSWORD=<REDACTED> make verify
```

Terminal result:

```text
FULL_VERIFICATION_FAILURE count=4 stages=unit-test,db-test,characterization-test,e2e-test
```

Passing stages were test DB reset, migrations (`1..11`), architecture (`7/7`),
lint and diff-check.

The current classified failures are:

- unit: the two approved assignment-order-original upload RED verifiers stop on
  the absent `AssignmentOrderOriginalVerificationFactory` production seam;
- DB: the owner-blocked legacy `PILOT-E2E-FLOW-001` fails directly and through
  the demo bootstrap wrapper; it was not edited; the HTTP auth verifier passes
  its corrected uppercase and legacy-query-fault assertions, then reaches the
  known macOS `LD_PRELOAD` ready-marker limitation; the object-card verifier
  reaches the known macOS `/proc` worker-observation limitation;
- characterization: the known macOS `setsid` absence remains, and five
  checklist characterization paths expose one newly classified integration
  RED: `ChecklistSync.php` constructs
  `MariaDbChecklistProjectionStateReader` without making that production
  dependency loadable when `ChecklistSync.php` is the public verifier entry;
- E2E: only the owner-blocked legacy `PILOT-E2E-FLOW-001` remains.

Exact RED file hashes:

```text
361732cf785361aaecc525ec2b0e9d574890e46a177afdfb53ed6b14428e7eb8  rapid-pilot/verify-checklist-current-crew.php
9a96af122b9971a2c0e34b28ae7538b97f296b40250ec39bf2a656852edd5125  rapid-pilot/verify-checklist-photo-upload.php
cff96c450a7ceb4a01fd2de4094ba0cd0b4ad80f2e24437f3964777db943f64c  rapid-pilot/verify-checklist-photo-rejections.php
d88c7fd2c785e76ddbad7bae9d9fdfb554b2aec808c092a18ce42f8c79163a5b  rapid-pilot/verify-checklist-photo-revoke.php
db717907d77c5c5c6a303fff2d7e3138a5170b8dc18fc72ad511fdeeb0ef3f91  rapid-pilot/verify-checklist-photo-limit-concurrency.php
22ea2d7f0455f1778522f807ef9e0ead1303c5205762b2ba1eee1e124f87dfeb  app/PilotHttp/ChecklistSync.php
ff41533ddd438697cc3e2bb609be6e2ba97fb1784da9751cd074615ce46d28f4  app/PilotHttp/MariaDbChecklistProjectionStateReader.php
```

The checklist integration RED is under fresh independent Gate 3 review. No
production correction is admitted by this receipt.
