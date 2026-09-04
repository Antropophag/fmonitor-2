# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — structural parser Gate 3 v3

- Reviewer: separately tasked agent `/root/original_upload_factory_worker_red/original_upload_structural_parser_gate3`
- Review timestamp: `2026-09-04T06:40:33+03:00`
- Reviewed commit: `8516b8bf60f326fcb0465855fd07185826d83aba`
- Predecessor review: `reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-structural-parser-v2.md`
- Specification: v4, SHA-256 `97a2527db60750089a53311856756b7db7b4682baf5c426a45503639ebde5479`
- Verdict: **CHANGES_REQUESTED**

## Independence and artifacts

The reviewer changed only this append-only record.

```text
d1fc19e15c44a6dbb1e5b1e965d363a4063a3743640c977bbf22f0057788b7e  tests/Support/AssignmentOrderOriginalPdfOracle.php
375f878bc7cbe178583be0f0ef9a745412d4c0b8e461134ef2a8e0e974409678  tests/InstallationProcess/assignment_order_original_upload_001_structural_pdf_test.php
aaa067c7b8ec704789ce309283a522988fcd928f3310b8fcf4bcb05cc458147e  docs/operations/assignment-order-original-upload-gate5-structural-parser-red-correction-v3-2026-09-04.md
```

## Corrected findings

The forbidden families are now isolated without dangling references.
`JavaScript` and `JS` have separate dictionaries. The object-namespace and
aggregate Flate-expansion cases exercise the two approved resource bounds.

## Remaining blocking finding

`conflictingXrefIdentity()` is not independently sensitive to duplicate/conflict
handling. It repeats object 1 with offset `58`, while object 1 is at offset `9`
and byte 58 is not an object header (object 2 starts at `59`). A parser which
does not detect duplicate xref identities but correctly validates offsets will
return `INVALID_PDF`, satisfying the assertion for the wrong reason. This exact
wrong-offset behavior is already covered by `wrongObjectOffset()`.

Construct a duplicate/conflict whose individual entry targets are otherwise
structurally valid, or add a focused mutation/sensitivity demonstration showing
that disabling only duplicate/conflict rejection makes this oracle fail. The
current fixture cannot distinguish the Gate 5-required identity validation from
ordinary offset validation.

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

$ git diff --check eb3c058..8516b8b
PASS (no output)
```

Gate 3 remains **CHANGES_REQUESTED**. Gate 4 must not proceed from
`8516b8bf60f326fcb0465855fd07185826d83aba`.
