# Gate 5 code review: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 incremental PDF grammar v6

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/incremental_pdf_gate5` (fresh
  independent reviewer; did not author the specification, executable tests,
  corpus, production parser, RED evidence, Gate 3 review, or implementation)
- Exact reviewed implementation SHA:
  `f3afea2d5a0b60eed0d82bebde03174087d8b255`
- Approved corrective test SHA:
  `25b0f41380b129536bb5e75b3b9059cc5df72d07`
- Approved Gate 3 SHA:
  `6d893a36df56ccc6dfaa45571eda4413a2222e48`
- Originating RED SHA: `9d6ff1920bde084a6a89402f7ca066eb4757aef2`
- Superseded parser Gate 5 SHA:
  `11156ffd86d5f116cc86adac33d01c1056f190e9`
- Public seam: `FMonitorPassivePdfInspector::inspect(string $bytes)`
- Scope: the incremental-PDF/parser correction only; production safe-log and
  overall command readiness remain outside this verdict.
- Verdict: **APPROVED**

## Review result

The implementation closes every blocking parser finding in `11156ffd` without
changing the approved executable expectations. Direct objects are now indexed
by physical offset and discovered with direct numeric stream length framing.
Object-like tokens within an ordinary stream are therefore skipped as payload,
while every physical direct object still has to be selected by an exact type-1
xref entry in the bounded `startxref`/`Prev` chain. Keeping all selected
physical offsets while applying newest-to-oldest object-number precedence
accepts a legitimate later redefinition and continues to reject an unindexed
or offset/identity-mismatched direct object.

`section()` now rejects absent/malformed required `/Size`, duplicate or
non-direct `/Root`, and duplicate or non-direct `/Prev`; an omitted Root remains
valid only in an older revision after the newest available Root has been
selected. Both ordinary reachable streams and direct-object discovery require
exactly one direct numeric `/Length`. The existing ObjStm checks continue to
reject duplicate/conflicting `/N`, `/First`, `/Length`, and `/Filter`. Offset,
object-count, revision-count, reference-depth and aggregate-decompression
bounds remain unchanged, as do encryption and active-content rejection.

The implementation commit changes only
`app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php` and its append-only
Gate 4 evidence. The approved incremental test and corpus are byte-identical to
Gate 3. The executable specification changed between Gate 3 and the reviewed
SHA only for the separately owner-approved production safe-log contract; its
PDF grammar and algorithm ID remain unchanged. No parser test, corpus,
configuration, OpenSpec planning artifact, or product specification was changed
by the implementation commit.

No security, grammar, bounds, integration-boundary, or maintainability blocker
was found in this corrective slice. The approved focused test is sensitive to
all eight prior production mismatches and is now GREEN; the accumulated parser
suite and relevant command regressions are also GREEN.

## Independent verification

```text
$ php -l app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php
No syntax errors detected in app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php

$ for test_file in tests/InstallationProcess/assignment_order_original_pdf*_test.php; do php "$test_file" || exit $?; done
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_PDF_BOUNDARY_OK
ASSIGNMENT_ORDER_ORIGINAL_PDF_INCREMENTAL_RED_OK
exit 0

$ php tests/InstallationProcess/assignment_order_original_upload_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_INITIAL_OK

$ php tests/InstallationProcess/assignment_order_original_upload_validation_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_AUTHORIZATION_OK

$ php tests/InstallationProcess/assignment_order_original_upload_remaining_contract_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_SHARED_ORACLES_OK

$ php tests/InstallationProcess/assignment_order_original_gate5_domain_red_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_GATE5_DOMAIN_RED_001_OK

$ make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

$ git diff --check f3afea2d5a0b60eed0d82bebde03174087d8b255^..f3afea2d5a0b60eed0d82bebde03174087d8b255
exit 0
```

The focused executable retains its historical `RED_OK` label, but strict
expected/actual identity succeeds and the process exits `0` at the reviewed
implementation.

## Exact reviewed hashes

```text
1a59ecc5ec45470ff76a6c043e29c67bdb89b641b298851540a050b46d6fe394  app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php
e85f3c9e56f23856f6603c4a04f42ae53a31fea156e23eff6806874a532e0933  tests/InstallationProcess/assignment_order_original_pdf_parser_incremental_001_test.php
85a72d4c02200ad3ca7f132af37272aceee7cb5371a25db4df168b0bb4637ee2  tests/Support/AssignmentOrderOriginalPdfCorpus.php
4cae80e141ad4caf758e792d0ae5a8383c32f6b9d6a779d6eace6122ec71b255  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
71440c070ec9c3bd1910e74ca24f49a60daab5e040049da4f501a33f2fc25cf1  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-pdf-incremental-grammar-gate3-rereview-v2-2026-09-05.md
d30e1139e373104a817e1ab199709b1c7b439dcead6e111924a0496a4309e7b1  docs/operations/assignment-order-original-pdf-incremental-grammar-green-2026-09-05.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
```

Gate 5 for the incremental-PDF correction is **APPROVED** at exact SHA
`f3afea2d5a0b60eed0d82bebde03174087d8b255`. This verdict does not approve the
separate production safe-log amendment or the combined command slice.
