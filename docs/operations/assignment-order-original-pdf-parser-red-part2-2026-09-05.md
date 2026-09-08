# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — owned parser RED part 2

Date: `2026-09-05`

RED author: `/root/assignment_original_red2`

Added independent fail-closed fixtures for cyclic Prev chain, duplicate object
identity, 101-level page-tree reference depth and 67,108,865-byte aggregate
structural decompression. Existing classic/xref-stream/object-stream and active-
key corpus remains.

```text
$ php -l tests/Support/AssignmentOrderOriginalPdfCorpus.php
No syntax errors detected
$ php -l tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
No syntax errors detected
$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
INTENDED_RED: approved FMonitorPassivePdfInspector production seam is absent.
RED_ASSERTION: expected failing behavior observed
```

Task 4.1 remains open pending worker races and remaining fault/lease coverage.

```text
e483729360fb99db68fc8efb64259a39a049ad6c1b2823911c80269faf5bbad6  tests/Support/AssignmentOrderOriginalPdfCorpus.php
40181da226d83a7a0af0b46558810be9997e046d33773fb1b2155b77c1f5deb4  tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
```
