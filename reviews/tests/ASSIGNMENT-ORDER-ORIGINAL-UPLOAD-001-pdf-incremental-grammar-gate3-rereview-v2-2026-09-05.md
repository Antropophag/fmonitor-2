# Gate 3 rereview: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 incremental PDF grammar

- Date: `2026-09-05`
- Reviewer: separately tasked agent
  `/root/pdf_incremental_red/pdf_incremental_gate3_v2` (fresh independent
  reviewer; did not author the specification, test, corpus, production parser,
  RED evidence, correction, or earlier review)
- Exact reviewed test commit:
  `25b0f41380b129536bb5e75b3b9059cc5df72d07`
- Exact production baseline:
  `7844c1a783a5da5c09bb336ccce0d31f19a074d6`
- Superseded Gate 3 review:
  `064b002559b8e1cbafec103b8de1f14803a8b9a5`
- Originating Gate 5 review: `11156ffd`
- Public seam: `FMonitorPassivePdfInspector::inspect(string $bytes)`
- Verdict: **APPROVED**

## Finding closure

The sole prior Gate 3 finding is closed. Commit `25b0f413` replaces the chained
right-to-left assignment with two separate expected-value assignments in the
same order as the corresponding cases: `incremental_duplicate_prev`, then
`incremental_malformed_prev`. The focused failure dump now shows identical key
order for the complete expected and actual arrays. Therefore strict array
identity can become GREEN when the eight remaining production mismatches are
corrected; no harness-only order mismatch remains.

The correction changes only the executable test line and adds append-only RED
evidence. Corpus, approved specification and production parser are unchanged.

## Traceability, controls, and sensitivity

The test is traceable to section 5 of the approved v54 executable specification
and to all three findings in Gate 5 review `11156ffd`. It exercises only the
public inspector seam. Positive controls cover a rebuilt classic xref, a valid
Prev chain, newest-revision replacement of an existing object identity, and
harmless object-like tokens inside a direct-Length content stream.

Negative cases isolate identical and conflicting classic `/Root` and `/Size`,
duplicate and malformed incremental `/Prev`, duplicate ordinary-stream
`/Length`, and both identical and conflicting occurrences of every selected
ObjStm structural key: `/N`, `/First`, `/Length`, and `/Filter` (eight ObjStm
cases total). Each mutation has an explicit multiplicity control. This matrix
is sensitive both to over-rejection of valid incremental/stream content and to
acceptance of any ambiguous selected dictionary key.

Corpus inspection independently confirmed that every classic live xref row in
the positive and mutated classic/incremental fixtures points to the exact
rebuilt object header; every `startxref` points to its current xref token; and
the incremental `/Prev` value equals the accepted base revision's exact
`startxref`. All fixtures are asserted below 1024 bytes. The object-stream
builders recalculate their binary xref entries, stream length, object offsets,
and `startxref`; existing complete parser regression remains GREEN. Tests are
deterministic, local, bounded, and use no production system or external input.

The focused run is RED for exactly eight missing parser behaviors:
`incremental_redefinition`, `stream_object_tokens`, both `/Root` variants, both
`/Size` variants, `incremental_duplicate_prev`, and
`ordinary_duplicate_length`. The malformed `/Prev` and all eight ObjStm cases
already produce their specified fail-closed result. This is intended RED, not
fixture/setup failure.

## Independent verification

```text
$ php -l tests/Support/AssignmentOrderOriginalPdfCorpus.php
No syntax errors detected in tests/Support/AssignmentOrderOriginalPdfCorpus.php

$ php -l tests/InstallationProcess/assignment_order_original_pdf_parser_incremental_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_pdf_parser_incremental_001_test.php

$ php tests/InstallationProcess/assignment_order_original_pdf_parser_incremental_001_test.php
Expected/actual arrays have the same 19 keys in the same order.
Eight status mismatches remain: incremental_redefinition, stream_object_tokens,
classic_duplicate_root, classic_conflicting_root, classic_duplicate_size,
classic_conflicting_size, incremental_duplicate_prev,
ordinary_duplicate_length.
Fatal error: Newest-revision graph, stream framing and every structural
dictionary key obey the approved fail-closed grammar.
exit 255

$ php tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_PDF_BOUNDARY_OK

$ php tests/InstallationProcess/assignment_order_original_upload_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_INITIAL_OK

$ php tests/InstallationProcess/assignment_order_original_upload_validation_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_AUTHORIZATION_OK

$ php tests/InstallationProcess/assignment_order_original_upload_remaining_contract_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_SHARED_ORACLES_OK

$ make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

$ git diff --check 7844c1a783a5da5c09bb336ccce0d31f19a074d6..25b0f41380b129536bb5e75b3b9059cc5df72d07
exit 0
```

## Exact reviewed hashes

```text
e85f3c9e56f23856f6603c4a04f42ae53a31fea156e23eff6806874a532e0933  tests/InstallationProcess/assignment_order_original_pdf_parser_incremental_001_test.php
85a72d4c02200ad3ca7f132af37272aceee7cb5371a25db4df168b0bb4637ee2  tests/Support/AssignmentOrderOriginalPdfCorpus.php
21951b0a0cce91d10a5c72ec88e00cb64fa8a8fa383509ef69c23ddb8c12334b  app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php
bf008158e451e216f922dfca94f901c936123f50a624685cd249c69ba6f63e5f  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
d8d48ac94d519c9b3b8707dcaf4c27046dc777ab890baf979c82fafd39d0a283  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-pdf-parser-v5.md
2470c75fb63a997ef620e1386a9f124703ca8405ef7b8a252babb3ff2d26003f  docs/operations/assignment-order-original-pdf-incremental-grammar-red-correction-2026-09-05.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
```

Gate 3 is **APPROVED**. Minimal production correction may begin from exact test
commit `25b0f41380b129536bb5e75b3b9059cc5df72d07` without changing the approved
expectations.
