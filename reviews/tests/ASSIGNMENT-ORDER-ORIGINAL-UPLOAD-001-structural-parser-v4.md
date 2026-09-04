# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — structural parser Gate 3 v4

- Reviewer: separately tasked agent `/root/original_upload_factory_worker_red/original_upload_structural_parser_gate3_fresh`
- Review timestamp: `2026-09-04T06:43:50+03:00`
- Reviewed commit: `c4643bb277d02313ebad7b046bcf0b9e7c9c6282`
- Predecessor review/correction: `reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-structural-parser-v3.md` and its append-only metadata correction
- Specification: `specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md` v4, owner-approved SHA-256 `97a2527db60750089a53311856756b7db7b4682baf5c426a45503639ebde5479`
- Verdict: **APPROVED**

## Independence and exact artifacts

This fresh reviewer did not author the specification, test, oracle, RED
evidence or production implementation. The reviewer changed only this new
append-only review record.

```text
65a4e2c48669b528aa67aeb65593a3728e830759e9f0d630d9abfb5508a619ca  tests/Support/AssignmentOrderOriginalPdfOracle.php
375f878bc7cbe178583be0f0ef9a745412d4c0b8e461134ef2a8e0e974409678  tests/InstallationProcess/assignment_order_original_upload_001_structural_pdf_test.php
0a10c77ddcc35bdb89a8bb584605777f182555ba16b75e37231cf85ec6833a95  docs/operations/assignment-order-original-upload-gate5-structural-parser-red-correction-v4-2026-09-04.md
```

## Review findings

The v3 blocking defect is closed. `conflictingXrefIdentity()` contains two real
`1 0 obj` headers at byte offsets `9` and `184`; its two active object-1 xref
entries contain those exact offsets, both generation `00000`. The first three
objects also retain exact offsets `9`, `58`, and `115`, and `startxref=251`
points to the actual classic xref at byte `251`. Consequently neither conflict
target is malformed in isolation: duplicate/conflicting identity is the only
reason this fixture must be `INVALID_PDF`, independently of the separately
covered wrong-offset case.

The complete reviewed matrix matches v4 section 5. It covers valid classic xref
with a non-zero generation, generation mismatch, binary xref stream, malformed
xref widths, active type-2 object `5` in Flate ObjStm `4`, exact Catalog reachability,
every forbidden family independently, unsupported structural filters, wrong
offsets, duplicate identities, `/Prev` traversal and cycle, cyclic Pages,
reference depth above 100, object namespace above 100000, aggregate structural
inflation above 67,108,864 bytes, encryption, direct zero pages, and latest-root
zero-page resolution over an obsolete positive revision. Expected results are
literal v4 outcomes and the test calls the public real inspector without source
inspection or a persistence side channel.

Independent binary inspection confirmed the active compressed entry decodes to
`5 0 << /S /JavaScript >>`, its xref entry is type `2` with object-stream number
`4` and index `0`, `/N=1`, `/First=4`, and the Catalog references `5 0 R`.
Equivalent inspection confirmed all fifteen forbidden-family dictionaries are
present in their independently generated decoded object streams.

## Verification and trustworthy RED

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

$ git diff --check e0b3bc41131106c720aa80097c2c39c31fcbc320..c4643bb277d02313ebad7b046bcf0b9e7c9c6282
PASS (no output)
```

The first disagreement is a trustworthy intended RED: the current production
inspector accepts reachable active JavaScript hidden in a structurally valid
Flate object stream. Gate 3 is **APPROVED** for exact RED commit
`c4643bb277d02313ebad7b046bcf0b9e7c9c6282`. Gate 4 may proceed from this exact
reviewed test/evidence envelope; this approval does not claim GREEN or Gate 5.
