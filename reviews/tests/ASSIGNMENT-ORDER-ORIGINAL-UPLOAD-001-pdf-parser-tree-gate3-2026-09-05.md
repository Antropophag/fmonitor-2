# Gate 3 test review: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 PDF xref/tree corrective RED

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/pdf_tree_gate3` (fresh independent
  reviewer; did not author the specification, RED fixtures/tests, production
  parser, or prior Gate 5 review)
- Exact reviewed RED commit:
  `7ddfcb923f1721ebef4b7d2eddf158b8f423a7cc`
- Exact production baseline / parent review commit:
  `01722894c1310d3cb202027bc45439ba1433ae4a`
- Source Gate 5 finding: `reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-pdf-parser-v1.md`
- Verdict: **APPROVED**

## Scope and contract fit

The corrective RED is narrowly bound to the two fail-open classes reported by
the independent Gate 5 review. It proves that non-overlapping xref-stream
`/Index [0 3 3 2]` ranges retain the accepted five-row control, while exact
repetition through `/Index [0 5 0 5]` must fail closed. It separately requires
the trailer Root to resolve to a Catalog, Catalog `/Pages` to resolve to a Pages
node, Pages `/Count` to match its reachable Page leaves, and Page discovery to
occur through `/Kids`, not an unrelated `/Other` reference.

These assertions implement the approved `fmonitor-passive-pdf-v1` requirement
that duplicate/conflicting identities and absence of an unambiguous
Catalog/Pages tree return `INVALID_PDF`. The algorithm ID, limits and public
outcome contract are unchanged.

## Independence and sensitivity audit

- The explicit non-overlap fixture carries the canonical rows once: its Index
  is `0 3 3 2`, stream Length is `45`, and total fixture size is `359` bytes.
- The overlap mutant carries the same rows twice: its Index is `0 5 0 5`, stream
  Length is `90`, and total fixture size is `404` bytes. All individual entries
  remain bounded, so the duplicate identities are the material distinction.
- The classic positive control contains the exact Root -> Catalog `/Pages` ->
  Pages `/Kids [3 0 R] /Count 1` -> Page chain and is asserted accepted before
  the aggregate negative assertion.
- Each tree mutant is independently rebuilt by the corpus helper. Root and
  Pages type mutants use equal-length tokens (`Catalog`/`CatXlog` are seven
  bytes; `Pages`/`Panes` are five), preserving classic-xref offsets.
- `catalog_missing_pages` leaves the valid Pages/Page objects present but makes
  them reachable only through Catalog `/Other`; `page_only_via_other` retains a
  valid Page object while the Pages node has `/Kids [] /Count 0 /Other 3 0 R`.
  Thus generic reference traversal cannot serve as the page-tree oracle.
- `pages_count_mismatch` isolates Count (`2`) from one Kids leaf. The remaining
  mutations isolate Root type and Pages-node type respectively.
- Fixtures are deterministic in-memory strings. They create no filesystem,
  database, storage, process, network, or orphan state; cleanup is therefore
  empty and bounded.
- The final aggregate assertion is reached, proving all pre-existing parser
  assertions and both new positive controls passed first.

No production path changed in the reviewed commit. The inspector hash is
byte-identical in the parent and RED commit.

## Independent RED reproduction

```text
$ php tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
exit=255
stdout=1864 bytes
stderr=1868 bytes

Expected: all six entries INVALID_PDF
Actual:
  root_not_catalog => PASSIVE_PDF
  catalog_missing_pages => PASSIVE_PDF
  catalog_pages_not_tree => PASSIVE_PDF
  pages_count_mismatch => PASSIVE_PDF
  page_only_via_other => PASSIVE_PDF
  overlapping_xref_stream_index => PASSIVE_PDF
```

Raw reproduction hashes:

```text
2337343a068644a38a4cb65edb78a284c1105121f7320afaadf958c1011dc020  stdout (1864 bytes)
1a0493a94479691e754793f845d96ab423dfc05ff56caa71a3f85cf964f04d47  stderr (1868 bytes)
```

Static checks:

```text
$ php -l tests/Support/AssignmentOrderOriginalPdfCorpus.php
No syntax errors detected in tests/Support/AssignmentOrderOriginalPdfCorpus.php

$ php -l tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php

$ git diff --check 7ddfcb923f^ 7ddfcb923f
exit=0

$ git diff --name-only 7ddfcb923f^ 7ddfcb923f
docs/operations/assignment-order-original-pdf-parser-tree-red-2026-09-05.md
tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
tests/Support/AssignmentOrderOriginalPdfCorpus.php
```

## Exact reviewed hashes

```text
4b481a294c8e3f40fb450a6921822aa46e4641a09b92e833598981de5f7b2bef  tests/Support/AssignmentOrderOriginalPdfCorpus.php
31e0681a97ef210bb41b74f1c04d3b64b741cee92df36be2702cb2bc57853236  tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
3608b4a7792eda006d1cdc82fe4767b818910a3d3c7111c2d3b924f74aa1059d  app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php
bf008158e451e216f922dfca94f901c936123f50a624685cd249c69ba6f63e5f  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
31e8d1036f99f6e448a0fa23a6027b8a3012946127320a9549d31bfc99649116  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
7a6a56363012959863efd636c119f96406019442f5a55087da636b280462b63c  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-pdf-parser-v1.md
0f2dc8437ea5e8c4a47b278ee7e795bd8a5ec1b95bfc13149100e01e64d98542  docs/operations/assignment-order-original-pdf-parser-tree-red-2026-09-05.md
```

Gate 3 is **APPROVED**. Minimal production correction for these exact parser
gaps is authorized; subsequent production work still requires a fresh,
independent Gate 5 review.
