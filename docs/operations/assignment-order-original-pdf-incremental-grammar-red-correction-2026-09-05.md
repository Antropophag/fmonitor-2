# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — incremental PDF RED correction

- Date: `2026-09-05`
- Superseded test commit: `9d6ff1920bde084a6a89402f7ca066eb4757aef2`
- Gate 3 finding: `064b002559b8e1cbafec103b8de1f14803a8b9a5`
- Scope: test oracle ordering only; corpus, production, specification and
  configuration are unchanged.

The previous test inserted the two expected `/Prev` keys through a right-to-left
chained assignment, reversing their order relative to the cases array. Since
`assertSameValue()` deliberately performs strict array comparison, that harness
defect could preserve RED after a correct production implementation.

The correction assigns `incremental_duplicate_prev` and
`incremental_malformed_prev` separately in the same order as their cases. The
focused test remains intended RED with the same eight production mismatches;
expected and actual arrays now have identical key order. The unchanged complete
parser suite remains GREEN.

```text
$ php -l tests/InstallationProcess/assignment_order_original_pdf_parser_incremental_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_pdf_parser_incremental_001_test.php

$ php tests/InstallationProcess/assignment_order_original_pdf_parser_incremental_001_test.php
Fatal error: Uncaught TestFailure: Newest-revision graph, stream framing and every structural dictionary key obey the approved fail-closed grammar.
exit 255

$ php tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_PDF_BOUNDARY_OK

$ git diff --check
exit 0
```

Exact corrected test hash:

```text
e85f3c9e56f23856f6603c4a04f42ae53a31fea156e23eff6806874a532e0933  tests/InstallationProcess/assignment_order_original_pdf_parser_incremental_001_test.php
85a72d4c02200ad3ca7f132af37272aceee7cb5371a25db4df168b0bb4637ee2  tests/Support/AssignmentOrderOriginalPdfCorpus.php
21951b0a0cce91d10a5c72ec88e00cb64fa8a8fa383509ef69c23ddb8c12334b  app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php
```

Classification remains **INTENDED_RED**. Fresh independent Gate 3 rereview is
required before production correction.
