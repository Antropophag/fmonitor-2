# Gate 3 test review: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 incremental PDF grammar correction

- Date: `2026-09-05`
- Reviewer: separately tasked agent
  `/root/pdf_incremental_red/pdf_incremental_gate3` (fresh independent reviewer;
  did not author the specification, production parser, corpus, executable test,
  RED evidence, or Gate 5 review)
- Exact reviewed test commit:
  `9d6ff1920bde084a6a89402f7ca066eb4757aef2`
- Exact production baseline:
  `7844c1a783a5da5c09bb336ccce0d31f19a074d6`
- Originating Gate 5 review commit: `11156ffd`
- Verdict: **CHANGES_REQUIRED**

## Traceability and controls

The corrective corpus is otherwise traceable to section 5 of the approved v54
specification and to all three blocking findings in the v5 Gate 5 review. It
uses only the public `FMonitorPassivePdfInspector::inspect()` seam. The positive
fixtures cover classic xref, an accepted Prev chain, a newest incremental
redefinition of the same object identity, and harmless object-like tokens
inside a direct-Length stream. The invalid matrix covers identical and
conflicting classic `/Root` and `/Size`, duplicate and malformed `/Prev`,
ordinary duplicate `/Length`, and identical/conflicting `/N`, `/First`,
`/Length`, and `/Filter` in an object stream dictionary.

The builders recalculate classic object offsets and `startxref`, and the
incremental builders calculate the appended object/xref offsets while retaining
the older exact `startxref` as `/Prev`. Every fixture is asserted below 1024
bytes. Existing corpus regression remains green. These properties make the
current production mismatches an intended parser RED rather than broken PDF
setup.

## Blocking test defect

The final oracle compares `$expected` and `$actual` with strict array identity
through `assertSameValue()`. However this statement:

```php
$expected['incremental_duplicate_prev']=$expected['incremental_malformed_prev']=AssignmentOrderOriginalPdfStatus::INVALID_PDF;
```

inserts `incremental_malformed_prev` before `incremental_duplicate_prev` in the
expected array, while the cases array inserted duplicate before malformed.
PHP preserves insertion order, and `assertSameValue()` uses `!==`. The captured
failure confirms the order difference. Consequently the test will still fail
after production returns every expected status, so it cannot prove GREEN and
does not yet satisfy determinism/sensitivity for Gate 3.

Gate 2 must separate or reorder those expected assignments so the expected key
order exactly follows the case key order, then demonstrate the intended RED
again and obtain a fresh independent Gate 3 review. No executable test,
fixture, specification, production, configuration, or existing evidence was
changed by this review.

## Independent verification

```text
$ php -l tests/Support/AssignmentOrderOriginalPdfCorpus.php
No syntax errors detected in tests/Support/AssignmentOrderOriginalPdfCorpus.php

$ php -l tests/InstallationProcess/assignment_order_original_pdf_parser_incremental_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_pdf_parser_incremental_001_test.php

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
Fatal error: Newest-revision graph, stream framing and every structural
dictionary key obey the approved fail-closed grammar.
exit 255

$ php tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_PDF_BOUNDARY_OK

$ make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

$ git diff --check 7844c1a783a5da5c09bb336ccce0d31f19a074d6..9d6ff1920bde084a6a89402f7ca066eb4757aef2
exit 0
```

## Exact reviewed hashes

```text
b83023b9a9a968851c3a98fce2e589756ec5d2f3c5297f681c48890d35b232b1  tests/InstallationProcess/assignment_order_original_pdf_parser_incremental_001_test.php
85a72d4c02200ad3ca7f132af37272aceee7cb5371a25db4df168b0bb4637ee2  tests/Support/AssignmentOrderOriginalPdfCorpus.php
bf008158e451e216f922dfca94f901c936123f50a624685cd249c69ba6f63e5f  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
d8d48ac94d519c9b3b8707dcaf4c27046dc777ab890baf979c82fafd39d0a283  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-pdf-parser-v5.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
```

Gate 3 is **CHANGES_REQUIRED**. Production correction must not begin from this
test revision.
