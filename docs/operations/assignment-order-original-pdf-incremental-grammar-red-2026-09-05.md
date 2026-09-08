# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — incremental PDF grammar RED

- Date: `2026-09-05`
- Gate: `2` corrective executable RED after Gate 5 review `11156ffd`
- Exact production baseline: `7844c1a783a5da5c09bb336ccce0d31f19a074d6`
- Scope: tests, compact corpus and this append-only evidence only; production,
  specification and configuration are unchanged.

## Independent oracle construction

`assignment_order_original_pdf_parser_incremental_001_test.php` calls only the
public `FMonitorPassivePdfInspector::inspect()` seam. Every generated fixture is
below 1024 bytes. The classic builder recalculates every object offset and
`startxref`; incremental builders append a current xref subsection and retain
the exact older xref offset in `/Prev`.

Positive controls cover classic xref, an unchanged incremental revision, a
newer exact `1 0` Catalog redefinition, and harmless `obj/endobj`-like bytes
inside an ordinary stream bounded by its direct `/Length`. Isolated invalid
mutations cover duplicate/conflicting classic `/Root` and `/Size`, duplicate or
malformed incremental `/Prev`, duplicate ordinary-stream `/Length`, and
duplicate/conflicting object-stream `/N`, `/First`, `/Length`, `/Filter`.

## Demonstrated RED

```text
$ php tests/InstallationProcess/assignment_order_original_pdf_parser_incremental_001_test.php
Expected: incremental_redefinition=passive_pdf
Actual:   incremental_redefinition=invalid_pdf
Expected: stream_object_tokens=passive_pdf
Actual:   stream_object_tokens=invalid_pdf
Expected: classic_duplicate_root=invalid_pdf
Actual:   classic_duplicate_root=passive_pdf
Expected: classic_conflicting_root=invalid_pdf
Actual:   classic_conflicting_root=passive_pdf
Expected: classic_duplicate_size=invalid_pdf
Actual:   classic_duplicate_size=passive_pdf
Expected: classic_conflicting_size=invalid_pdf
Actual:   classic_conflicting_size=passive_pdf
Expected: incremental_duplicate_prev=invalid_pdf
Actual:   incremental_duplicate_prev=passive_pdf
Expected: ordinary_duplicate_length=invalid_pdf
Actual:   ordinary_duplicate_length=passive_pdf
Fatal error: Uncaught TestFailure: Newest-revision graph, stream framing and every structural dictionary key obey the approved fail-closed grammar.
exit 255
```

The unchanged classic/incremental controls pass. Malformed `/Prev` and all eight
ObjStm structural-key mutations already return `invalid_pdf`, which preserves
the complete requested regression matrix while the eight mismatches above prove
the corrective RED is caused by production parser behavior rather than fixture
setup.

## Regression control

```text
$ php tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_PDF_BOUNDARY_OK

$ php -l tests/Support/AssignmentOrderOriginalPdfCorpus.php
No syntax errors detected in tests/Support/AssignmentOrderOriginalPdfCorpus.php

$ php -l tests/InstallationProcess/assignment_order_original_pdf_parser_incremental_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_pdf_parser_incremental_001_test.php

$ git diff --check
exit 0
```

## Exact hashes before commit

```text
85a72d4c02200ad3ca7f132af37272aceee7cb5371a25db4df168b0bb4637ee2  tests/Support/AssignmentOrderOriginalPdfCorpus.php
b83023b9a9a968851c3a98fce2e589756ec5d2f3c5297f681c48890d35b232b1  tests/InstallationProcess/assignment_order_original_pdf_parser_incremental_001_test.php
21951b0a0cce91d10a5c72ec88e00cb64fa8a8fa383509ef69c23ddb8c12334b  app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php
bf008158e451e216f922dfca94f901c936123f50a624685cd249c69ba6f63e5f  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
```

Classification: **INTENDED_RED**. A fresh independent Gate 3 review is required
before any production correction.
