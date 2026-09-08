# Gate 5 code review: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 PDF parser v4

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/pdf_xref_gate5_v5` (fresh independent
  reviewer; did not author the specification, executable tests, corpus,
  production parser, corrections, or prior reviews)
- Exact reviewed implementation SHA:
  `558698a2716d63f15b00ff947b82acbbd04936f4`
- Latest corrective Gate 3 review:
  `ca54d4454ef56543ad70e978dab566dd193e7dc2`
- Scope: complete owned `fmonitor-passive-pdf-v1` parser, including all prior
  xref, bounds, page-tree, object-stream, Filter and stream-framing corrections.
  Safe-log and the overall command slice are excluded.
- Verdict: **CHANGES_REQUESTED**

## Closed findings

The latest correction closes the executable xref-stream grammar cases. It
rejects array and indirect Filter forms, permits only a single direct
`/FlateDecode`, requires exactly one direct integer `/Length`, and requires LF,
CR or CRLF followed immediately by `endstream` and the containing `endobj`.
The accumulated classic/xref-stream reconciliation, structural decompression
bounds, duplicate identities, Catalog/Pages topology, object-stream framing,
and active-content cases remain GREEN.

## Blocking finding: other xref dictionary keys remain ambiguous

The preceding Gate 5 finding did not limit dictionary unambiguity to `/Length`:
it explicitly identified `/Size`, `/W`, `/Index`, `/Root`, `/Prev`, `/Encrypt`
and `/Filter`, and required independent duplicate-key mutations. The corrective
RED covered only duplicate `/Length` alongside Filter form and stream framing.
The production correction likewise counts only `/Length` and `/Filter`.

`xrefStream()` still selects the first regex match for `/Size` and `/W`, and the
first `/Index`; `section()` still selects the first `/Root` and `/Prev`. Bounded
mutations of the accepted xref-stream fixture therefore produce:

```text
duplicate_size=passive_pdf
conflicting_size=passive_pdf
duplicate_w=passive_pdf
duplicate_index=passive_pdf
duplicate_root=passive_pdf
conflicting_root=passive_pdf
```

Every mutation adds only a second structural field inside the xref-stream
dictionary. The object start, `startxref`, object 1--3 offsets, decoded xref
rows and declared stream payload length remain unchanged. In particular,
`conflicting_root` contains both `/Root 1 0 R` and `/Root 2 0 R`, yet the parser
chooses the first and returns `PASSIVE_PDF` rather than failing closed on the
ambiguous document graph.

This is the unresolved high-severity class from the prior Gate 5, not a new
product behavior. It is material at the public inspector seam and contradicts
the approved fail-closed rule for unsupported or ambiguous PDF structure.
Because the approved executable test does not cover these keys, correction
requires a focused Gate 2 RED and a fresh independent Gate 3 before production
changes, followed by another independent Gate 5.

## Independent verification

```text
$ php -l app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php
No syntax errors detected in app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php

$ /usr/bin/time -l php tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_PDF_BOUNDARY_OK
0.66 real; maximum resident set size 164265984

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

$ git diff --check ca54d4454ef56543ad70e978dab566dd193e7dc2..558698a2716d63f15b00ff947b82acbbd04936f4
exit 0
```

## Exact reviewed hashes

```text
4e96c42422313fc4d76f345438529e36212125701b2f13e47eceb44267ae2184  app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php
6c0edebdc1e2c99ec8c342cdece4c09bc0f3c1c9cd2254044e9353df0725dcdf  tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
f86be607dec0a8ade356107204c207f4dc25bbd2620c6e4b2cc49ad77264d7cd  tests/Support/AssignmentOrderOriginalPdfCorpus.php
bf008158e451e216f922dfca94f901c936123f50a624685cd249c69ba6f63e5f  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
38d6600cfdfd706417343fe92fd0ca24e2e2f338847ba7b5e2afd7902a24fbc1  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-pdf-parser-v3.md
a2f9b352d73363b26fa68a990db41ccfaba3c5f5c93a69d7725f786119f800ba  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-pdf-xref-grammar-gate3-2026-09-05.md
```

No production or test code was changed by this review. Gate 5 remains
**CHANGES_REQUESTED**. Safe-log and the overall command slice remain outside
this verdict.
