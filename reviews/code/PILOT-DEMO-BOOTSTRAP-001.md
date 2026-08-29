# Code review: PILOT-DEMO-BOOTSTRAP-001 v0.1

- Gate: 5 — fresh independent code review after corrective Gate 4
- Reviewer: separately tasked agent `/root/bootstrap_code_review_final`
- Independence: reviewer authored neither specification, approved test, nor implementation
- Specification commit: `71e5e50`
- Approved test commit: `a1fca72`
- Approved test review: `cdb5b3a`
- Exact reviewed HEAD: `aca0bbc9b66ff8a6a77209fd67f48ef56749438f`
- Review date: 2026-08-29
- Verdict: `APPROVED`

## Verdict

`APPROVED`. The exact reviewed implementation conforms to the approved bootstrap specification and closes the previously identified safety gaps. Reset activates a generation only after its public HTTP smoke succeeds; occupied-bind failure preserves the byte-identical active manifest, old generation tree, and usable prior process state. Initial and final generations survive restart without reseeding, while interrupted inactive generations are skipped without being mistaken for ready state.

Destructive database cleanup requires an independent random nonce to agree between the filesystem owner record and markers on both the process and legacy anchor tables. Forged same-fingerprint owner files and same-prefix tables therefore do not authorize a drop. Cleanup remains confined to marked generations, leaves foreign paths/rows intact, and reports irreversible material removal. No residual demo process or test-state artifact remained after verification.

The plain-HTTP exception is limited to the loopback PHP development server and a bootstrap-injected random nonce. The same production router composition without that marker remains HTTPS-origin-only and rejects the command with `403`; inbound identity headers cannot replace the configured actor. The launch smoke validates the exact CSS bytes, queue/card/form projection, non-import boundary, enabled action, and read-only repeat before printing the banner or switching `active.json`.

## Standards

Pass — zero blocking findings. The implementation stays within demo/deployment orchestration, uses production migrations, importer, HTTP router, process commands, and artifact service, and does not introduce mocks or direct SQL process transitions. The single-file CLI is dense, but splitting it would be architecture refactoring beyond this bounded slice; its lifecycle helpers and ownership checks keep responsibilities explicit enough for the approved pilot scope.

## Spec

Pass — zero findings. Provisioning, deterministic fixture, loopback launch, browser walkthrough, persistence, reset recovery, status/cleanup behavior, redaction, ownership containment, and documented short run instructions match `PILOT-DEMO-BOOTSTRAP-001 v0.1`. No scope creep was found.

## Verification

```text
$ php tests/InstallationProcess/pilot_demo_bootstrap_001_test.php
PASS PILOT-DEMO-BOOTSTRAP-001 public launch, walkthrough, persistence, reset and cleanup

$ for test_file in tests/InstallationProcess/*_test.php; do php -d display_errors=1 -d error_reporting=E_ALL "$test_file"; done
48/48 test files PASS

$ find app public bin tests/InstallationProcess tests/Support -type f -name '*.php' -print0 | xargs -0 -n1 php -l
PHP lint PASS

$ git diff --check 71e5e50..HEAD
PASS

$ git status --short
clean before review record
```

The first full-suite attempt encountered a transient failure in the pre-existing `pilot_http_auth_001_test.php` resource-close assertion after its global-call companion. The failing test passed immediately in isolation, and a fresh complete sequential run then passed all 48 files. No bootstrap process or state residue was present after either run.

## Reviewed-input hashes

```text
e6b082c9b2ed2bd0c8aca370fa785dd2aa25a38901c12d620f8b6e1e1d048263  specs/PILOT-DEMO-BOOTSTRAP-001.md
67d5a8122a08a465ae4e35a2e5bb66051a860eaf9f3c272f76a6f55e1897537c  tests/InstallationProcess/pilot_demo_bootstrap_001_test.php
ac61ec61c500bc3301cc74256f62295425f7f6c47865a4e807b002a764087e4c  reviews/tests/PILOT-DEMO-BOOTSTRAP-001.md
826f3a79a666a0a1c8435cff996a1ae82a308e011da369db8ef22487c52982e3  bin/fmonitor2-pilot-demo.php
fceab9b40c2d3fe766217e2cda2d4d54966cef141bc6cd8fe7a6e90836d2cd38  app/PilotHttp/PilotE2ECoordinator.php
c84db90cbbe0dfc759ea5ea8a319176ac273fb2a2cccd105883c8a097e38e5eb  public/router.php
854f64fde52a2d85d63ffe022adb040c3998ea786e1dee757d18ba6239e3d03e  README.md
```

## Required changes

None. Gate 5 is approved for exact reviewed commit `aca0bbc9b66ff8a6a77209fd67f48ef56749438f`.
