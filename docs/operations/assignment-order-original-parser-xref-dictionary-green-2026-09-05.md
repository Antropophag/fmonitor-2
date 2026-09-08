# Assignment-order original xref dictionary Gate 4 GREEN

- Date: `2026-09-05`
- Production author: separately tasked agent `/root/pdf_xref_green`
- Corrective RED: `e0a50fa511b63fb27d09016dd576c140108d7959`
- RED evidence: `315940bc8bbeabd793f5c4ca7c100a01572b6235`
- Independent Gate 3: `c4b93fd444ab972ccef3fcbe973e5db9f8a8563f`
- Scope: production `FMonitorPassivePdfInspector` plus this append-only record;
  tests, specifications, safe-log and runtime configuration were not edited.

## Exact inputs and implementation

```text
21951b0a0cce91d10a5c72ec88e00cb64fa8a8fa383509ef69c23ddb8c12334b  app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php
37254fb8319093d958ae8138faa7d600d7236035392655e1b711b8959347f55a  tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
d31960881cef93778f32a6b19f3602f1087b4dfcac7b1233b0c1d0a6082c3407  tests/Support/AssignmentOrderOriginalPdfCorpus.php
bf008158e451e216f922dfca94f901c936123f50a624685cd249c69ba6f63e5f  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
cf953eb96770afbdfb703a19bb950374c4239126e22ba1da8ebc7a642c292d29  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-pdf-xref-dictionary-gate3-2026-09-05.md
```

The correction rejects identical and conflicting repeated structural keys.
`/Size`, `/W` and `/Root` must each occur once in their direct approved form.
`/Index` retains the previously approved absent-key default and, when present,
must occur once as a direct array. Duplicate or malformed occurrences fail
before row decoding, so first-match parsing cannot select an interpretation.

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
