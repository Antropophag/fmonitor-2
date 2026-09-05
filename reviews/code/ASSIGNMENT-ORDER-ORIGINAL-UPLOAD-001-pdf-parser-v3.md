# Gate 5 code review: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 PDF parser v3

- Date: 2026-09-05
- Reviewer: separately tasked agent `/root/pdf_xref_gate5_v4`
- Verdict: **CHANGES_REQUESTED**
- Reviewed exact SHA: `45eb644b784e10cffa95068af2f3710e6d0561b5`
- ObjStm corrective Gate 3: `2da88d15c62a85600e0af4b64e4f2257a8353dc2`
- Scope: complete owned `fmonitor-passive-pdf-v1` parser, including all prior
  xref, bounds, page-tree and object-stream corrections. Safe-log and the
  overall command slice are excluded.

## Required findings

### 1. High — xref streams accept unsupported or ambiguous `/Filter` values

`FMonitorPassivePdfInspector::xrefStream()` recognizes only a direct-name
`/Filter /Name`. When `/Filter` is an array or an indirect reference, the regex
does not match and the stream is silently treated as unfiltered. Both of these
mutations of the accepted xref-stream control therefore returned
`PASSIVE_PDF`:

```text
xref_filter_array_asciihex=passive_pdf
xref_filter_indirect=passive_pdf
```

The array mutant declares `/Filter [/ASCIIHexDecode]`; the indirect mutant
declares `/Filter 99 0 R`. Neither is the sole supported `FlateDecode` form.
This violates the approved fail-closed rule for unsupported/ambiguous
structure and permits bytes whose declared decoding semantics were not
applied. The parser must recognize the complete allowed dictionary shape and
reject every other filter value/form, with an executable regression.

### 2. High — xref stream payload framing is not closed

`xrefStream()` extracts exactly `/Length` bytes but never verifies the required
stream terminator and object boundary after those bytes. Replacing the xref
stream's `endstream` token with nine unrelated bytes, or inserting junk before
the token, leaves the document accepted:

```text
missing_xref_endstream=passive_pdf
junk_after_xref_payload=passive_pdf
```

This is malformed/ambiguous structural input and contradicts the exact stream
framing already required for ordinary and object streams. The executable
corpus must pin the accepted CR, LF and CRLF boundary forms and reject missing,
early, late and junk-separated terminators.

### 3. High — duplicate xref dictionary fields are silently accepted

The xref parser takes the first regex match for `/Length`, `/Size`, `/W`,
`/Index`, `/Root`, `/Prev`, `/Encrypt` and `/Filter`; it does not establish an
unambiguous single value. A control mutated to contain two `/Length` entries was
accepted:

```text
duplicate_xref_length=passive_pdf
```

Duplicate security/structure keys are ambiguous and must fail closed. This is
especially important for `/Encrypt`, `/Filter`, `/Length`, `/Root` and `/Prev`,
where selecting one of multiple declarations can change security or graph
interpretation. Add independent duplicate-key mutations and reject them before
using any selected value.

## Verification rerun

The accumulated committed corpus remains green, so the findings are uncovered
axes rather than regressions already detected by it:

```text
$ php tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_PDF_BOUNDARY_OK

$ php tests/InstallationProcess/assignment_order_original_upload_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_INITIAL_OK
$ php tests/InstallationProcess/assignment_order_original_upload_remaining_contract_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_SHARED_ORACLES_OK
$ php tests/InstallationProcess/assignment_order_original_upload_validation_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_AUTHORIZATION_OK
$ php tests/InstallationProcess/assignment_order_original_gate5_domain_red_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_GATE5_DOMAIN_RED_001_OK

$ php -l app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php
No syntax errors detected in app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php

$ make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)
```

The initially requested nonexistent
`tests/InstallationProcess/assignment_order_original_file_validation_001_test.php`
was not counted as evidence; the actual upload validation and related suites
listed above were run instead.

## Exact reviewed artifacts

```text
61ad41280463bf00db819c97c4287e15dc45907606d1f9a4753b2b162cd7542f  app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php
757a4710c1e4605a96e9adcef1bc9f676fc4be0ab9f5c658bac826659fae5aca  tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
c9016cb317f863a9d65d9b0d4d9bd88b26733e59f6db1719736550b73f26d033  tests/Support/AssignmentOrderOriginalPdfCorpus.php
5f8faf2f04ae7ea3f1aec498cc80660b7bc31e558734d8bd7f69ce97e8b38d1f  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-pdf-parser-object-stream-gate3-2026-09-05.md
e6bf37b00dad06129b462f8a2c1ec5e5610f86d189a616d39ac182d616d5d7cf  docs/operations/assignment-order-original-parser-object-stream-green-2026-09-05.md
```

No production code was changed by this review. Gate 5 is not approved; the
findings require reviewed executable RED, minimal correction, and a fresh
independent Gate 5 on the resulting exact SHA.
