# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — owned PDF RED evidence

Date: 2026-09-04

RED author: `/root/original_upload_red`

Verdict: **INTENDED RED; task 2.2 remains open**

## Covered subset

The verifier directly exercises the required production-owned
`FMonitorPassivePdfInspector` and independently requires algorithm ID
`fmonitor-passive-pdf-v1`. Fixtures cover the approved 327-byte classic-xref
literal, malformed and truncated files, encrypted and zero-page files, passive
xref-stream and object-stream files, and every forbidden dictionary/action
family enumerated by the v4 contract: `JavaScript`, `JS`, `OpenAction`, `AA`,
`Launch`, `EmbeddedFiles`, `Filespec`, `FileAttachment`, `RichMedia`, `Movie`,
`Sound`, `URI`, `GoToR`, `SubmitForm`, and `ImportData`.

The fixture builder belongs to tests and derives byte offsets and xref entries
without calling production parser/storage code. Expected statuses are literal
test values. No production file changed.

Chunk/stage/abort ordering and the remaining Gate 2.2 persistence, concurrency,
maintenance and fault matrices are not covered by this increment. The task
checkbox therefore remains open and Gate 3 has not started.

## Transcript

```text
$ php -l tests/Support/AssignmentOrderOriginalPdfOracle.php
No syntax errors detected in tests/Support/AssignmentOrderOriginalPdfOracle.php

$ php -l tests/InstallationProcess/assignment_order_original_upload_001_owned_pdf_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_001_owned_pdf_test.php

$ php tests/InstallationProcess/assignment_order_original_upload_001_owned_pdf_test.php
PHP Fatal error: Uncaught TestFailure: INTENDED_RED: owned production PDF seam is missing: FMonitor2\AssignmentOrderOriginal\FMonitorPassivePdfInspector
exit 255

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff --check
PASS (no output)
```

The failure is classified intended RED rather than setup failure: both test
files parse, the OpenSpec change validates, and execution stops at the explicit
owned production parser seam before any environment dependency.
