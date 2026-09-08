# Assignment-order original xref-stream grammar Gate 4 GREEN

- Date: `2026-09-05`
- Production author: separately tasked agent `/root/pdf_xref_green`
- Corrective RED: `b67fff55ab02cefe1605822ebe0624828fdaac2c`
- Independent Gate 3: `ca54d4454ef56543ad70e978dab566dd193e7dc2`
- Scope: production `FMonitorPassivePdfInspector` plus this append-only record;
  tests, specifications, safe-log and runtime configuration were not edited.

## Exact inputs and implementation

```text
4e96c42422313fc4d76f345438529e36212125701b2f13e47eceb44267ae2184  app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php
6c0edebdc1e2c99ec8c342cdece4c09bc0f3c1c9cd2254044e9353df0725dcdf  tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
f86be607dec0a8ade356107204c207f4dc25bbd2620c6e4b2cc49ad77264d7cd  tests/Support/AssignmentOrderOriginalPdfCorpus.php
bf008158e451e216f922dfca94f901c936123f50a624685cd249c69ba6f63e5f  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
a2f9b352d73363b26fa68a990db41ccfaba3c5f5c93a69d7725f786119f800ba  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-pdf-xref-grammar-gate3-2026-09-05.md
```

The bounded correction requires exactly one direct integer `/Length` in an
xref-stream dictionary. `/Filter` may be absent or must be exactly one direct
`/FlateDecode`; array, indirect, duplicate and unsupported forms fail closed.
The declared payload must be followed immediately by an approved LF, CR or
CRLF delimiter and `endstream`, then the containing `endobj`; missing markers
and intervening bytes are rejected.

## GREEN transcript

```text
$ php -l app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php
No syntax errors detected in app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php

$ php tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_PDF_BOUNDARY_OK

$ php tests/InstallationProcess/assignment_order_original_upload_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_INITIAL_OK

$ php tests/InstallationProcess/assignment_order_original_upload_validation_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_AUTHORIZATION_OK

$ php tests/InstallationProcess/assignment_order_original_upload_remaining_contract_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_SHARED_ORACLES_OK

$ php tests/InstallationProcess/assignment_order_original_gate5_domain_red_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_GATE5_DOMAIN_RED_001_OK

$ make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

$ git diff --check
EXIT=0
```

This is Gate 4 evidence only and requires a fresh independent Gate 5 review.
