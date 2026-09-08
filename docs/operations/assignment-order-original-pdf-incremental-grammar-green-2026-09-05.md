# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — incremental PDF grammar GREEN

- Date: `2026-09-05`
- Gate: `4 — minimal implementation`
- Exact starting baseline: `5c9a530286e8d0bad4d245b14052da89ae246ea6`
- Approved Gate 3 commit: `6d893a36df56ccc6dfaa45571eda4413a2222e48`
- Approved corrective test commit: `25b0f41380b129536bb5e75b3b9059cc5df72d07`
- Original RED commit: `9d6ff1920bde084a6a89402f7ca066eb4757aef2`
- Public seam: `FMonitorPassivePdfInspector::inspect(string $bytes)`

## Minimal production correction

Only `FMonitorPassivePdfInspector` changed. Direct objects are now scanned with
declared stream framing, so object-like tokens inside a content stream are not
mistaken for document objects. Every physical direct object still has to be
selected by an xref entry in the bounded revision chain, while the latest xref
entry wins for an identity. This accepts a valid incremental redefinition but
continues to reject an unindexed duplicate identity.

Classic trailer `/Size`, optional `/Root`, and optional `/Prev` are accepted
only when each selected key is syntactically exact and unambiguous. Ordinary
direct streams likewise require exactly one direct numeric `/Length` before
their payload is skipped. No test, corpus, specification, OpenSpec artifact,
configuration, or review record changed.

## GREEN verification

```text
$ php -l app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php
No syntax errors detected in app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php

$ for test_file in tests/InstallationProcess/assignment_order_original_pdf*_test.php; do php "$test_file" || exit $?; done
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_PDF_BOUNDARY_OK
ASSIGNMENT_ORDER_ORIGINAL_PDF_INCREMENTAL_RED_OK
exit 0

$ php tests/InstallationProcess/assignment_order_original_upload_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_INITIAL_OK

$ php tests/InstallationProcess/assignment_order_original_upload_validation_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_AUTHORIZATION_OK

$ php tests/InstallationProcess/assignment_order_original_upload_remaining_contract_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_SHARED_ORACLES_OK

$ make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

$ git diff --check
exit 0
```

The historical focused executable prints a label ending in `RED_OK`; its exit
status is `0` here because the strict expected/actual matrix is now GREEN.

## Exact artifact hashes before this evidence commit

```text
1a59ecc5ec45470ff76a6c043e29c67bdb89b641b298851540a050b46d6fe394  app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php
e85f3c9e56f23856f6603c4a04f42ae53a31fea156e23eff6806874a532e0933  tests/InstallationProcess/assignment_order_original_pdf_parser_incremental_001_test.php
85a72d4c02200ad3ca7f132af37272aceee7cb5371a25db4df168b0bb4637ee2  tests/Support/AssignmentOrderOriginalPdfCorpus.php
bf008158e451e216f922dfca94f901c936123f50a624685cd249c69ba6f63e5f  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
71440c070ec9c3bd1910e74ca24f49a60daab5e040049da4f501a33f2fc25cf1  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-pdf-incremental-grammar-gate3-rereview-v2-2026-09-05.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
```

Fresh independent Gate 5 review is still required. This record does not approve
its own implementation.
