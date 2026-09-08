# Gate 5 code review: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 PDF parser v1

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/pdf_xref_gate5_v2` (fresh independent reviewer; did not author the specification, executable tests, production implementation, or earlier reviews)
- Exact reviewed implementation SHA: `369fb6df12433ce715f092aaa5e3fffc3ee37453`
- Corrective production commits: `2ebb3416b4831ddb919327d42ec5fdbef4861272`, `369fb6df12433ce715f092aaa5e3fffc3ee37453`
- Authorized Gate 3 reviews: parser portion of `55991af`, and bounds review `0419b88ed330863b03132ad582e2971fc973ebd1`
- Scope: owned `fmonitor-passive-pdf-v1` implementation only. The production safe-log owner blocker and overall command slice are expressly excluded.
- Verdict: **CHANGES_REQUESTED**

## Exact reviewed identities

```text
3608b4a7792eda006d1cdc82fe4767b818910a3d3c7111c2d3b924f74aa1059d  app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php
ca1d8bde91ec46735e528dd38eb0148146f8fd83ceccfdb70bd97fc24cf9f02d  tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
50306f04d6bd29fe83072fedb37b5f32e78fe97c59185822b0340072b21c5a16  tests/Support/AssignmentOrderOriginalPdfCorpus.php
bf008158e451e216f922dfca94f901c936123f50a624685cd249c69ba6f63e5f  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
31e8d1036f99f6e448a0fa23a6027b8a3012946127320a9549d31bfc99649116  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
7a9b5ffa9e2bfb42949184249e81bd3d6f68afb7be409eb4fd2d326785cff80f  docs/operations/assignment-order-original-parser-bounds-green-2026-09-05.md
f5538af2cad6c76a520bbcfcad6fa227cfd3f0573e785721f6a4e69104b8f8f6  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-pdf-parser-bounds-gate3-2026-09-05.md
```

## Closed prior findings

The implementation now reconciles classic and stream xref rows with exact
direct object identity, generation and offset. Unsupported stream-entry types
fail closed. Reachable ordinary stream `/Length` is checked against its exact
body boundary. One shared counter bounds xref, object-stream and reachable
ordinary Flate output at `67,108,864` bytes, including the exact-boundary
positive control. Repeated identities within classic xref subsections are
rejected. All approved parser RED assertions are GREEN.

## Blocking finding 1: repeated xref-stream identity is silently overwritten

`xrefStream()` accepts overlapping `/Index` ranges and assigns each decoded row
with `$entries[$first+$i]=...`. A later row for the same object identity silently
replaces the earlier row. This violates the approved fail-closed requirement for
duplicate/conflicting identities and is inconsistent with the new classic-xref
duplicate check.

Independent reproduction starts from the accepted corpus xref-stream, duplicates
its exact five-row payload, changes `/Length 45` to `/Length 90`, and adds
`/Index [0 5 0 5]`. The document remains bounded and has no unrelated corrupt
offset or object-body mutation:

```text
xref_stream_duplicate_identity=passive_pdf
```

Expected: `invalid_pdf`.

## Blocking finding 2: Catalog/Pages topology is not validated

The approved grammar requires an unambiguous Catalog/Pages tree and zero Page
leaves to fail closed. The implementation instead begins at the trailer `/Root`,
follows every syntactic indirect reference in every reachable body, and accepts
when any visited body contains `/Type /Page`. It does not require Root to be a
Catalog, the `/Pages` target to be a Pages node, or Page leaves to occur through
the Pages `/Kids` topology.

Independent bounded mutations/rebuilds produce:

```text
root_not_catalog=passive_pdf
pages_not_pages=passive_pdf
page_outside_kids=passive_pdf
```

The first two replace equal-length PDF names, preserving all original xref
offsets. The third is rebuilt through the independent classic corpus helper with
`/Kids [] /Count 0` and an unrelated `/Other 3 0 R` reference to a Page. All
three must be `invalid_pdf` under the approved Catalog/Pages-tree requirement.

This is material fail-open behavior at the public inspector seam. It requires a
new executable RED and independent Gate 3 before production correction; this
review does not authorize implementation from prose alone.

## Independent verification

```text
$ php -l app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php
No syntax errors detected in app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php

$ /usr/bin/time -l php tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_PDF_BOUNDARY_OK
0.66 real; maximum resident set size 164282368

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

$ git diff --check 0419b88ed330863b03132ad582e2971fc973ebd1..369fb6df12433ce715f092aaa5e3fffc3ee37453
exit 0
```

The approved suites prove the intended corrections and upload mappings remain
GREEN, but they contain no sensitivity for either reproduced fail-open class.
Gate 5 for the parser is therefore **CHANGES_REQUESTED**. Safe-log and the full
command slice remain outside this verdict and are not approved.
