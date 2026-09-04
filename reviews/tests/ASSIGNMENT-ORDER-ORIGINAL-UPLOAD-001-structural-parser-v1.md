# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — structural parser Gate 3 v1

- Reviewer: separately tasked agent `/root/original_upload_factory_worker_red/original_upload_structural_parser_gate3`
- Review timestamp: `2026-09-04T06:36:13+03:00`
- Reviewed commit: `0215c00bf87226daedc16ebab16c3998b9c5a2a9`
- Trigger: parser finding in `reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-v1.md`
- Specification: `specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md` v4, owner-approved SHA-256 `97a2527db60750089a53311856756b7db7b4682baf5c426a45503639ebde5479`
- Verdict: **CHANGES_REQUESTED**

## Independence and exact artifacts

The reviewer authored only this append-only review record. The specification,
test, support oracle, RED evidence and production implementation were not
changed.

```text
5a64f609a583208f302f2c3063c767b739b5408fd78f85963d65eadbc79a25dd  tests/Support/AssignmentOrderOriginalPdfOracle.php
f3635b0a3ba0f63dd96c2a2d0c308f9062b90a0f9b7d0918ace3d274506e3311  tests/InstallationProcess/assignment_order_original_upload_001_structural_pdf_test.php
2a30c103be1c8271c929b03635b21cc40211d9c2e1842dc9a487f83cecd988a4  docs/operations/assignment-order-original-upload-gate5-structural-parser-red-evidence-2026-09-04.md
```

## Blocking findings

1. **The Flate object-stream fixtures are not structurally valid compressed-object fixtures.**
   `flateObjectStream()` appends object stream `4 0` through a classic xref
   subsection but never creates a type-2 xref entry for contained object `5 0`.
   Classic xref entries cannot identify compressed objects. The fixture can
   therefore be rejected as invalid or ignore unindexed `5 0` without proving
   whether the implementation resolves an active compressed object. Replace it
   with an independently built xref stream whose type-2 entry maps the hidden
   object to the Flate-decoded object stream, with internally consistent
   `/Size`, `/Index`, `/W`, offsets, generations and `/Prev` where applicable.

2. **The forbidden-family matrix is incomplete.** Section 5 requires real-parser
   cases for every forbidden family. The current loop does not independently
   cover `JS`, `Filespec`, `RichMedia`, `Movie`, `Sound`, `SubmitForm`, or
   `ImportData`. `JavaScript` happens to share one dictionary with `JS`, but
   either check alone could make that case pass, so it is not sensitive to a
   parser that omits one of them. Add one valid compressed-object case per exact
   forbidden key/action.

3. **Structural traversal/bound sensitivity is incomplete.** The suite has one
   wrong classic offset, one cyclic Pages tree and one chain beyond reference
   depth 100, but does not pin a `/Prev` cycle, conflicting/duplicate object
   identities, malformed xref-stream offsets/entries, the `100000` object bound,
   aggregate decompression bound, or unsupported structural filters. A parser
   that follows the happy-path xref stream and latest Root yet omits those
   mandatory fail-closed checks could pass this suite.

4. **Generation coverage has only a positive case.** The non-zero catalog
   fixture usefully proves exact generation support, but there is no mismatched
   trailer reference or conflicting-generation negative case. An implementation
   that accepts the object number while ignoring generation can pass.

## Adequate coverage retained

The independently constructed expected statuses match v4 section 5. The suite
does cover a valid classic xref with a non-zero catalog generation, a positive
xref stream, a wrong classic offset, a Pages cycle, depth exhaustion,
encryption, direct zero pages and an incremental `/Prev` update whose latest
Root has zero pages. It calls the public production inspector and does not use
source inspection or persistence side channels. These are useful assertions,
but they do not close the blocking holes above.

## Verification and RED

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

$ git diff --check 0215c00^ 0215c00
PASS (no output)
```

The observed disagreement is implementation-related, but because its fixture
does not establish an active compressed object, it is not yet a trustworthy RED
for the specified object-stream resolution defect.

Gate 3 is **CHANGES_REQUESTED**. Gate 4 must not proceed from
`0215c00bf87226daedc16ebab16c3998b9c5a2a9`. Correct the oracle/tests and RED
evidence, then request a fresh independent Gate 3 review.
