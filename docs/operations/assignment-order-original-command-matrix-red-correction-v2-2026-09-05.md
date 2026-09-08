# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — command matrix RED correction v2

Date: `2026-09-05`

Correction author: `/root/assignment_command_gate3` acting as test author after
issuing independent Gate 3 `CHANGES_REQUESTED` in commit `e801a835` (this author
must not review the corrected batch).

Outcome: **INTENDED RED — executable sensitivity gaps closed**.

The correction converts the former source-string/list assertions into public-
seam behavior and closes every blocking v1 finding:

- exact initial validation plus not-found/invalid/unavailable composition
  mapping before stream access;
- canonical/noncanonical command framing, JSON keys/types, UTF-8, base64 and a
  transport-valid decoded 20 MiB+1 application rejection;
- separately executable FD range/stdio/closed/type/duplicate/alias identity
  matrix, with validation owned by the worker rather than the entry wrapper;
- exact wrong-root, composition-drift and second-initial results;
- both workers READY before byte-identical evidence snapshots, then exact
  winner/loser Result lines and request/audit/fingerprint/event/blob/process/log
  delta assertions for identical and different races;
- exact after-finalize READY blob with zero command facts, LOCKED maintenance
  request/audit, exact accepted evidence and retained byte-identical content;
- maintenance invalid/authorization results, exact terminal request/audit
  evidence for persistable identities, and exact per-fault Result/request/
  audit/blob/replay/cleanup behavior;
- the old `RemainingMatrix` is now only a shared-literal integrity check and no
  longer claims behavior from constant names.

No production or executable specification file changed. OpenSpec task 4.1 is
checked; task 4.2 remains pending fresh independent Gate 3.

## Reproduced evidence

```text
$ php -l <all changed PHP tests/support>
No syntax errors detected (11 files)

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_upload_001_test.php
INTENDED_RED: approved AssignmentOrderOriginalVerificationFactory production seam is absent.
RED_ASSERTION: expected failing behavior observed

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_upload_validation_001_test.php
INTENDED_RED: approved AssignmentOrderOriginalVerificationFactory production seam is absent.
RED_ASSERTION: expected failing behavior observed

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
INTENDED_RED: approved FMonitorPassivePdfInspector production seam is absent.
RED_ASSERTION: expected failing behavior observed

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_evidence_reader_001_test.php
INTENDED_RED: approved AssignmentOrderOriginalEvidenceReaderFactory production seam is absent.
RED_ASSERTION: expected failing behavior observed

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_maintenance_001_test.php
INTENDED_RED: approved AssignmentOrderOriginalPrivateOrphanFixtureFactory seam is absent.
RED_ASSERTION: expected failing behavior observed

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
INTENDED_RED: approved AssignmentOrderOriginalVerificationWorkerBootstrap seam is absent.
RED_ASSERTION: expected failing behavior observed

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_worker_protocol_001_test.php
INTENDED_RED: approved worker protocol seam is absent.
RED_ASSERTION: expected failing behavior observed

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_lease_race_001_test.php
INTENDED_RED: approved lease-race worker seam is absent.
RED_ASSERTION: expected failing behavior observed

$ php tests/InstallationProcess/assignment_order_original_upload_remaining_contract_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_SHARED_ORACLES_OK

$ independent information_schema SCHEMATA / PROCESSLIST query for t_aoou_%
0
0

$ independent /tmp and /private/tmp verifier-root inventory
no matching aoou worker/protocol/lease/reader/maintenance roots

$ git diff --check
PASS (no output)
```

## Exact hashes before this evidence file

```text
9102459b06f42454c5e5c1ef5fcbd9577782cb3a81f30c2d0979fcc22b4dad2b  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
57b1e0e18f53baaf632bcb1fd7893a1370dd0a0c181d649772160cecf53a3bef  tests/InstallationProcess/assignment_order_original_upload_001_test.php
e17299de03bd667fcd263de2af8398800450a30eb314ed9c2cb861e38cd433a1  tests/InstallationProcess/assignment_order_original_upload_validation_001_test.php
40181da226d83a7a0af0b46558810be9997e046d33773fb1b2155b77c1f5deb4  tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
fb687d2682c8cf4ed45420b5372c63ec0b69210866b23d5d975a9ae305876076  tests/InstallationProcess/assignment_order_original_evidence_reader_001_test.php
aa691c26e4c46f545b29e8c435d6cf9607d7bcc6782e75281f554ca2a4617ba5  tests/InstallationProcess/assignment_order_original_maintenance_001_test.php
0d9e3a93f97666da47c3c9e88cbc2146c0e9d977362c06d3abeb61fbb0d8cbd0  tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
cb8e84315339b31389720d4819e98251933728b76283359498d99ab8aa28a547  tests/InstallationProcess/assignment_order_original_worker_protocol_001_test.php
5889edc881ee0585e6d5ca987c02b7a50f9968ef200850c028fe9a72e5329c6b  tests/InstallationProcess/assignment_order_original_lease_race_001_test.php
3f006e89be2967d511cf8c0a00828d38ebc20d83240fc5d428738a3e2c2a2716  tests/InstallationProcess/assignment_order_original_upload_remaining_contract_001_test.php
3fe18be313bf66286ea9f7669bfac2828925754665fe1437637a799f78bf82c3  tests/Support/AssignmentOrderOriginalRemainingMatrix.php
8e1777da035ffa63e9b09d4227cbfbb438bcc5d59b1ff2291f123a6a3094de12  tests/Support/assignment_order_original_worker_entry.php
```

Task 4.1 is complete again. Fresh independent Gate 3 is required before any
command implementation.
