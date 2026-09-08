# Gate 3 test review: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 xref dictionary corrective RED

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/pdf_xref_dict_gate3` (fresh
  independent reviewer; did not author the specification, RED fixtures/tests,
  production parser, evidence, or source Gate 5 review)
- Exact reviewed RED commit:
  `e0a50fa511b63fb27d09016dd576c140108d7959`
- Exact RED evidence commit:
  `315940bc8bbeabd793f5c4ca7c100a01572b6235`
- Exact production baseline / source Gate 5 review:
  `558698a2716d63f15b00ff947b82acbbd04936f4` /
  `afbcf22d777bde20d0c4167a9217adeabf15d68f`
- Verdict: **APPROVED**

## Traceability and public seam

The corrective RED is narrowly traceable to the unresolved Gate 5 finding that
xref-stream `/Size`, `/W`, `/Index`, and `/Root` remain ambiguous when repeated.
It exercises only the owned public
`FMonitorPassivePdfInspector::inspect()` seam and observes the approved
`PASSIVE_PDF` or fail-closed `INVALID_PDF` result. This is existing malformed /
ambiguous-structure behavior under `fmonitor-passive-pdf-v1`, not a new product
contract.

The rebuilt positive control contains one exact instance of every selected
structural key and is accepted before the negative aggregate assertion. The
eight negative cases pair an identical duplicate and a conflicting duplicate
for each of the four keys. Expected values come from the approved fail-closed
contract and the exact Gate 5 finding, not from a planned parser correction.

## Fixture isolation, sensitivity, and bounds

- Every fixture rebuilds objects 1--3, their byte offsets, object 4, all five
  decoded xref rows, the payload `/Length`, and `startxref`. Independent checks
  confirm `startxref=184`, payload length `45`, and exact declared length for
  the control and all eight variants.
- The selected key is the only key whose multiplicity changes from one to two;
  the other three remain single. The positive control therefore guards fixture
  validity while each negative case isolates one ambiguity axis.
- The identical-duplicate cases make the multiplicity oracle sensitive without
  depending on alternative-value semantics. Their paired conflicting cases
  additionally cover the concrete ambiguity examples requested by Gate 5.
- Fixture sizes are deterministic and bounded from `355` through `372` bytes.
  They use compact in-memory strings and create no files, rows, processes,
  network traffic, private objects, or cleanup obligations.
- The reproduced failure occurs only at the final aggregate assertion: all
  earlier accumulated parser checks and the new positive control have passed.
  All eight negatives are currently `PASSIVE_PDF`, exactly exposing first-match
  parsing of these repeated keys.

The RED commit changes only the parser test and its test corpus. Production,
specification, OpenSpec, runtime configuration, and prior evidence/review files
do not drift; the inspector is byte-identical to the stated baseline.

## Independent RED reproduction

```text
$ php tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
exit=255

Expected: duplicate/conflict Size, W, Index, Root => INVALID_PDF
Actual:   duplicate/conflict Size, W, Index, Root => PASSIVE_PDF
```

Static and fixture checks:

```text
$ php -l tests/Support/AssignmentOrderOriginalPdfCorpus.php
No syntax errors detected in tests/Support/AssignmentOrderOriginalPdfCorpus.php

$ php -l tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php

$ git diff --check e0a50fa^..e0a50fa
exit=0
```

## Exact reviewed hashes

```text
d31960881cef93778f32a6b19f3602f1087b4dfcac7b1233b0c1d0a6082c3407  tests/Support/AssignmentOrderOriginalPdfCorpus.php
37254fb8319093d958ae8138faa7d600d7236035392655e1b711b8959347f55a  tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
4e96c42422313fc4d76f345438529e36212125701b2f13e47eceb44267ae2184  app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php
bf008158e451e216f922dfca94f901c936123f50a624685cd249c69ba6f63e5f  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
f2b84af6f8141307e38a8cef58ae4ceeb091aad52ae90ec9625f30809681b4ee  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-pdf-parser-v4.md
052df80e98696b94994d30567c7406566e2762e58e6e6418e8143f4ba5a1aa9a  docs/operations/assignment-order-original-pdf-xref-dictionary-red-evidence-2026-09-05.md
```

Gate 3 is **APPROVED**. A minimal production correction for these exact four
xref-stream dictionary-key ambiguity gaps is authorized. It still requires
focused and relevant GREEN evidence and a fresh independent Gate 5 review.
