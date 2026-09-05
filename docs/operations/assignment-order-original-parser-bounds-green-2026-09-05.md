# Assignment-order original parser bounds Gate 4 GREEN

- Date: `2026-09-05`
- Production author: separately tasked agent `/root/pdf_xref_green`
- Baseline bounds RED: `f13bbe3992e5361c1c052e5b3bf0d272b2349a98`
- Independent Gate 3 review: `0419b88ed330863b03132ad582e2971fc973ebd1`
- Scope: production `FMonitorPassivePdfInspector` only; executable spec,
  tests, safe-log and runtime configuration were not edited.

## Exact inputs and implementation

```text
3608b4a7792eda006d1cdc82fe4767b818910a3d3c7111c2d3b924f74aa1059d  app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php
ca1d8bde91ec46735e528dd38eb0148146f8fd83ceccfdb70bd97fc24cf9f02d  tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
50306f04d6bd29fe83072fedb37b5f32e78fe97c59185822b0340072b21c5a16  tests/Support/AssignmentOrderOriginalPdfCorpus.php
bf008158e451e216f922dfca94f901c936123f50a624685cd249c69ba6f63e5f  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
f5538af2cad6c76a520bbcfcad6fa227cfd3f0573e785721f6a4e69104b8f8f6  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-pdf-parser-bounds-gate3-2026-09-05.md
```

The bounded correction validates a reachable ordinary stream's direct
`/Length` against its exact stream boundary, accepting only the permitted EOL
separator before `endstream`. One shared counter now accounts for bytes decoded
from xref streams, object streams and reachable ordinary Flate streams, accepts
exactly `67,108,864`, and fails closed before exceeding that aggregate.
Classic xref subsection insertion rejects a repeated identity, which also
rejects overlapping subsection ranges rather than silently replacing a row.

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

This is Gate 4 evidence only. It does not approve its own implementation and
does not claim Gate 5 or full `make verify` readiness.
