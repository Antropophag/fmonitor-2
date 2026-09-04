# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — Gate 5 structural parser RED

- Recorded: `2026-09-04T06:35:01+03:00`
- Baseline SHA: `bcc4cb4c09d426759fe5eb0702a3af176cc9e6ab`
- Trigger: blocking parser finding in `reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-v1.md`
- Result: **INTENDED RED**

The focused verifier calls the real `FMonitorPassivePdfInspector` through its
public interface. Independent test builders produce valid classic xref with a
non-zero generation, an xref stream, incremental `Prev` revision, Flate object
streams, wrong offsets, a cyclic Pages tree, a reference chain exceeding depth
100, an encrypted trailer and zero/latest-zero-page graphs. Expected outcomes
come directly from approved `fmonitor-passive-pdf-v1`; no production source
inspection or persistence side channel is used.

Actual command and terminal result (exit `255`):

```text
$ php tests/InstallationProcess/assignment_order_original_upload_001_structural_pdf_test.php
Flate object stream hides forbidden JavaScript.
Expected: AssignmentOrderOriginalPdfStatus::UNSAFE_PDF
Actual: AssignmentOrderOriginalPdfStatus::PASSIVE_PDF
```

This is the intended security-sensitive disagreement: the current regex
implementation scans encoded bytes and does not decompress/resolve the object
stream. Later assertions also pin wrong-offset, cycles/bounds and latest-object
graph behavior but are unreachable until the first defect is corrected.

```text
php -l tests/Support/AssignmentOrderOriginalPdfOracle.php
No syntax errors detected
php -l tests/InstallationProcess/assignment_order_original_upload_001_structural_pdf_test.php
No syntax errors detected
git diff --check
PASS (no output)
```

No production file changed.
