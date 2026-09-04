# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — structural parser Gate 3 v2

- Reviewer: separately tasked agent `/root/original_upload_factory_worker_red/original_upload_structural_parser_gate3`
- Review timestamp: `2026-09-04T06:38:38+03:00`
- Reviewed commit: `393c81b0604dc829f84a05c4ad71685a7b64b385`
- Predecessor review: `reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-structural-parser-v1.md`
- Specification: v4, SHA-256 `97a2527db60750089a53311856756b7db7b4682baf5c426a45503639ebde5479`
- Verdict: **CHANGES_REQUESTED**

## Independence and artifacts

The reviewer changed only this append-only record. Production, tests, fixtures
and evidence were not edited.

```text
aed4884a52f5b1122d638ddcd285a9987284e32fc40122cffd26982a3671aa09  tests/Support/AssignmentOrderOriginalPdfOracle.php
d339d92d2ff45608f7142557a1adb79cefe8f66468ac2b444917d7dabbb70f23  tests/InstallationProcess/assignment_order_original_upload_001_structural_pdf_test.php
8861d9e515ae93a52f0979ef27ff674375693cdecb4b94f797ea8d874db23981  docs/operations/assignment-order-original-upload-gate5-structural-parser-red-correction-v2-2026-09-04.md
```

## Corrected findings

The compressed object is now active and structurally addressed: xref object 6
has a type-2 entry mapping object 5 to object stream 4, and the Catalog directly
references object 5. The added generation mismatch, malformed xref-stream
width, unsupported structural filter and cyclic `/Prev` cases are independently
useful. The expected statuses remain derived from v4 section 5.

## Remaining blocking findings

1. The claimed independent forbidden-family matrix is not independent for
   `JavaScript`: its dictionary still contains both `/JavaScript` and `/JS`.
   An implementation that omits `JavaScript` but detects `JS` passes that case.
   Use `/S /JavaScript` alone; retain the separate `/JS` case.

2. Several purportedly unsafe fixtures contain dangling indirect references:
   `OpenAction`, `AA` and `EmbeddedFiles` point to absent object `8 0`. A bounded
   structural parser may correctly classify the document as `INVALID_PDF`
   before or while resolving the malformed graph, so these fixtures do not
   isolate the forbidden-family behavior. Make every referenced target exist
   with a consistent active xref entry, or express these forbidden constructs
   inline without an unresolved reference.

3. The v1 blocking bound inventory is still incomplete: no case exercises the
   `100000`-object limit or `67,108,864`-byte aggregate structural-decompression
   limit, and no duplicate/conflicting object identity case was added. Those
   fail-closed requirements can still be omitted by an implementation which
   passes the corrected suite.

## Verification

```text
$ php tests/InstallationProcess/assignment_order_original_upload_001_structural_pdf_test.php
PHP Fatal error: Uncaught TestFailure: Flate object stream hides forbidden JavaScript.
Expected: AssignmentOrderOriginalPdfStatus::UNSAFE_PDF
Actual: AssignmentOrderOriginalPdfStatus::PASSIVE_PDF
exit 255

$ php -l tests/Support/AssignmentOrderOriginalPdfOracle.php
No syntax errors detected in tests/Support/AssignmentOrderOriginalPdfOracle.php

$ php -l tests/InstallationProcess/assignment_order_original_upload_001_structural_pdf_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_001_structural_pdf_test.php

$ git diff --check 8c028fe..393c81b
PASS (no output)
```

The first RED is now trustworthy for active Flate object-stream resolution, but
Gate 3 assesses the complete reviewed corrective inventory. Because malformed
fixtures and remaining sensitivity holes can admit materially incomplete parser
implementations, Gate 3 remains **CHANGES_REQUESTED**. Gate 4 must not proceed
from `393c81b0604dc829f84a05c4ad71685a7b64b385`.
