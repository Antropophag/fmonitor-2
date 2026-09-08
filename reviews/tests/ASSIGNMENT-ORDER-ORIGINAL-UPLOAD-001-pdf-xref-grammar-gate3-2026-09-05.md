# Gate 3 test review: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 xref grammar corrective RED

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/pdf_grammar_gate3` (fresh independent
  reviewer; did not author the specification, RED fixtures/tests, production
  parser, or source Gate 5 review)
- Exact reviewed RED commit:
  `b67fff55ab02cefe1605822ebe0624828fdaac2c`
- Exact production baseline / source Gate 5 review commit:
  `45eb644b784e10cffa95068af2f3710e6d0561b5` /
  `7a2675a96046df435b8b90b3ef34759ab24f758b`
- Verdict: **APPROVED**

## Traceability and public seam

The corrective RED is narrowly traceable to the three unresolved xref-stream
findings in Gate 5: unsupported or ambiguous `/Filter`, non-closed payload
framing, and duplicate dictionary fields. It exercises the owned public
`FMonitorPassivePdfInspector::inspect()` seam and observes only the approved
`PASSIVE_PDF` or `INVALID_PDF` result.

The four positive controls pin the accepted grammar independently: unfiltered
xref streams with LF, CR, and CRLF immediately before `endstream`, plus the
only approved direct `/Filter /FlateDecode` form. The five negative fixtures
each vary one relevant axis: array Filter, indirect Filter, absent marker,
extra bytes between the exact payload and marker, or duplicate `/Length`.
Their required `INVALID_PDF` result follows from the approved fail-closed
malformed/unsupported/ambiguous structure contract and the concrete Gate 5
findings; it is not derived from planned implementation details.

## Fixture independence and sensitivity

- `xrefStreamGrammar()` rebuilds objects 1--3 and their byte offsets for every
  variant, records the current offset of object 4, and emits fresh xref rows.
  `startxref` is that same object-4 offset after all preceding bytes are built.
- `/Length` is calculated from the actual raw or compressed payload. The direct
  Flate control uses `gzcompress(..., 9)` and retains the decoded canonical five
  xref rows; the unfiltered controls retain those rows byte-for-byte.
- Filter-array and Filter-indirect cases change only the Filter value form.
  The missing-marker case replaces the post-payload marker with nine ordinary
  bytes. The extra-byte case inserts four bytes immediately after the declared
  payload. The duplicate case adds a second identical `/Length`, so no length
  mismatch can mask the intended ambiguity.
- Positive controls execute before the aggregate negative assertion. The
  reproduced failure occurs at that final assertion, proving fixture setup,
  offsets, supported delimiters, and direct Flate decoding completed first.
- Fixtures are deterministic compact in-memory strings. They create no files,
  database rows, processes, network traffic, storage objects, or cleanup work.

No production, OpenSpec, runtime configuration, or existing evidence file was
changed by the reviewed commit. The production parser is byte-identical to the
Gate 5 baseline.

## Independent RED reproduction

```text
$ php tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
exit=255

Expected:
  filter_array => INVALID_PDF
  filter_indirect => INVALID_PDF
  missing_endstream => INVALID_PDF
  extra_bytes_before_endstream => INVALID_PDF
  duplicate_length => INVALID_PDF
Actual:
  filter_array => PASSIVE_PDF
  filter_indirect => PASSIVE_PDF
  missing_endstream => PASSIVE_PDF
  extra_bytes_before_endstream => PASSIVE_PDF
  duplicate_length => PASSIVE_PDF
```

Static checks:

```text
$ php -l tests/Support/AssignmentOrderOriginalPdfCorpus.php
No syntax errors detected in tests/Support/AssignmentOrderOriginalPdfCorpus.php

$ php -l tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php

$ git diff --check b67fff55^..b67fff55
exit=0
```

## Exact reviewed hashes

```text
f86be607dec0a8ade356107204c207f4dc25bbd2620c6e4b2cc49ad77264d7cd  tests/Support/AssignmentOrderOriginalPdfCorpus.php
6c0edebdc1e2c99ec8c342cdece4c09bc0f3c1c9cd2254044e9353df0725dcdf  tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
61ad41280463bf00db819c97c4287e15dc45907606d1f9a4753b2b162cd7542f  app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php
38d6600cfdfd706417343fe92fd0ca24e2e2f338847ba7b5e2afd7902a24fbc1  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-pdf-parser-v3.md
```

Gate 3 is **APPROVED**. A minimal production correction for these exact
xref-stream grammar gaps is authorized. It still requires focused and relevant
regression GREEN evidence and a fresh independent Gate 5 review.
