# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — xref Index and page-tree corrective RED

Date: `2026-09-05`

Source finding: independent Gate 5 review
`01722894c1310d3cb202027bc45439ba1433ae4a`.

Gate 2 RED author: separately tasked agent `/root/pdf_bounds_red` (not eligible
to review these tests or their production correction).

Production baseline: `369fb6d` (`fix: bound passive pdf stream decoding`), with
exact inspector hash recorded below. No production, OpenSpec, safe-log, runtime
configuration, or prior evidence file was changed by this RED.

## Independent controls and mutations

- Explicit non-overlapping xref-stream `/Index [0 3 3 2]` uses the same five
  canonical rows as the existing valid xref stream and remains accepted.
- `/Index [0 5 0 5]` repeats all five exact rows and identities; it must be
  rejected even though both ranges and every individual row are otherwise in
  bounds.
- The canonical classic fixture proves exact `/Root` → `/Type /Catalog` →
  `/Pages` → `/Type /Pages` → `/Kids` → `/Type /Page`, with matching `/Count`.
- Independent mutations cover a non-Catalog root, missing Catalog `/Pages`, a
  Catalog `/Pages` target with a non-Pages type, mismatched Pages `/Count`, and
  a Page reachable only via unrelated `/Other` while `/Kids` is empty. The root
  `Catalog`→`CatXlog` and tree `Pages`→`Panes` mutations preserve token length,
  preventing offset drift from becoming the oracle.

Fixtures are compact deterministic strings; they create no filesystem,
database, storage, or public-orphan state, so cleanup is empty and bounded.
The command reaches the final aggregate assertion, proving all old parser tests
and both new positive controls passed first.

## RED transcript

```text
$ php tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
exit=255
Expected:
  root_not_catalog => INVALID_PDF
  catalog_missing_pages => INVALID_PDF
  catalog_pages_not_tree => INVALID_PDF
  pages_count_mismatch => INVALID_PDF
  page_only_via_other => INVALID_PDF
  overlapping_xref_stream_index => INVALID_PDF
Actual:
  root_not_catalog => PASSIVE_PDF
  catalog_missing_pages => PASSIVE_PDF
  catalog_pages_not_tree => PASSIVE_PDF
  pages_count_mismatch => PASSIVE_PDF
  page_only_via_other => PASSIVE_PDF
  overlapping_xref_stream_index => PASSIVE_PDF
```

Raw capture hashes:

```text
2337343a068644a38a4cb65edb78a284c1105121f7320afaadf958c1011dc020  stdout (1864 bytes)
1a0493a94479691e754793f845d96ab423dfc05ff56caa71a3f85cf964f04d47  stderr (1868 bytes)
```

Exact pre-commit file hashes:

```text
4b481a294c8e3f40fb450a6921822aa46e4641a09b92e833598981de5f7b2bef  tests/Support/AssignmentOrderOriginalPdfCorpus.php
31e0681a97ef210bb41b74f1c04d3b64b741cee92df36be2702cb2bc57853236  tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
3608b4a7792eda006d1cdc82fe4767b818910a3d3c7111c2d3b924f74aa1059d  app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php (unchanged production baseline)
```

Classification: **INTENDED RED**. A fresh independent Gate 3 review is required
before the next production correction.
