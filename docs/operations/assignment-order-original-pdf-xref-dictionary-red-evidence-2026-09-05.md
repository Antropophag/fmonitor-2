# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 xref dictionary corrective RED evidence

- Date: `2026-09-05`
- RED author: separately tasked agent `/root/pdf_xref_dict_red`
- Exact RED test commit: `e0a50fa511b63fb27d09016dd576c140108d7959`
- Exact production baseline: `558698a2716d63f15b00ff947b82acbbd04936f4`
- Source Gate 5 review: `afbcf22d777bde20d0c4167a9217adeabf15d68f`
- Classification: **INTENDED RED** at the public
  `FMonitorPassivePdfInspector::inspect()` seam.

## Scope and fixture controls

The test-only corpus rebuilds objects 1--3, their exact byte offsets, object 4,
all five decoded xref rows and `startxref` for every fixture. The positive
control contains exactly one direct `/Size 5`, `/W [1 4 4]`, `/Index [0 5]`
and `/Root 1 0 R` and is accepted as `PASSIVE_PDF`.

Eight negative fixtures independently add only a second selected structural
key. Each key has an exact duplicate and a conflicting value; assertions also
prove the selected key occurs twice while each other named key remains single.
Payload bytes, declared `/Length`, object offsets and `startxref` remain
derived from the same fresh builder. The expected `INVALID_PDF` follows from
the approved fail-closed ambiguous-structure contract and the exact Gate 5
finding; it is not derived from production internals.

## RED transcript

Command at exact RED commit:

```text
$ php tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
exit=255

Expected:
  duplicate_size => INVALID_PDF
  conflict_size => INVALID_PDF
  duplicate_w => INVALID_PDF
  conflict_w => INVALID_PDF
  duplicate_index => INVALID_PDF
  conflict_index => INVALID_PDF
  duplicate_root => INVALID_PDF
  conflict_root => INVALID_PDF
Actual:
  duplicate_size => PASSIVE_PDF
  conflict_size => PASSIVE_PDF
  duplicate_w => PASSIVE_PDF
  conflict_w => PASSIVE_PDF
  duplicate_index => PASSIVE_PDF
  conflict_index => PASSIVE_PDF
  duplicate_root => PASSIVE_PDF
  conflict_root => PASSIVE_PDF
```

The aggregate assertion is after the existing parser suite and the new
single-key positive control. Reaching it proves those controls passed before
the intended failure.

Static checks:

```text
$ php -l tests/Support/AssignmentOrderOriginalPdfCorpus.php
No syntax errors detected in tests/Support/AssignmentOrderOriginalPdfCorpus.php

$ php -l tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php

$ git diff --check 558698a2716d63f15b00ff947b82acbbd04936f4..e0a50fa511b63fb27d09016dd576c140108d7959
exit=0
```

## Exact hashes at RED

```text
d31960881cef93778f32a6b19f3602f1087b4dfcac7b1233b0c1d0a6082c3407  tests/Support/AssignmentOrderOriginalPdfCorpus.php
37254fb8319093d958ae8138faa7d600d7236035392655e1b711b8959347f55a  tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
4e96c42422313fc4d76f345438529e36212125701b2f13e47eceb44267ae2184  app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php
bf008158e451e216f922dfca94f901c936123f50a624685cd249c69ba6f63e5f  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
f2b84af6f8141307e38a8cef58ae4ceeb091aad52ae90ec9625f30809681b4ee  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-pdf-parser-v4.md
```

No production, specification, OpenSpec artifact, runtime configuration or
prior evidence/review record was changed by the RED commit.
