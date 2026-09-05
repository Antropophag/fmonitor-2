# Gate 5 rereview: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 PDF parser v2

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/pdf_xref_gate5_v3` (fresh independent
  reviewer; did not author the specification, executable tests, corpus,
  production implementation, corrections, or prior Gate 5 review)
- Exact reviewed implementation SHA:
  `70aabd19e1e6565177aaf0805610af94fbc5b23b`
- Production history in scope:
  `2ebb3416b4831ddb919327d42ec5fdbef4861272`,
  `369fb6df12433ce715f092aaa5e3fffc3ee37453`,
  `70aabd19e1e6565177aaf0805610af94fbc5b23b`
- Authorized tree correction: Gate 3 review commit
  `d1d9354` for RED `7ddfcb923f1721ebef4b7d2eddf158b8f423a7cc`
- Scope: owned `fmonitor-passive-pdf-v1` implementation only. The production
  safe-log owner blocker and overall command slice are expressly excluded.
- Verdict: **CHANGES_REQUESTED**

## Exact reviewed identities

```text
58395291011f21a55188bffb4e67de148bb83d4cb7bb12dc5de1fccdfa49ff11  app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php
31e0681a97ef210bb41b74f1c04d3b64b741cee92df36be2702cb2bc57853236  tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
4b481a294c8e3f40fb450a6921822aa46e4641a09b92e833598981de5f7b2bef  tests/Support/AssignmentOrderOriginalPdfCorpus.php
bf008158e451e216f922dfca94f901c936123f50a624685cd249c69ba6f63e5f  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
31e8d1036f99f6e448a0fa23a6027b8a3012946127320a9549d31bfc99649116  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
dd0b6eefba19367e88c89eb165b7ec635fd52a97457198545339e1cd554a3240  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-pdf-parser-tree-gate3-2026-09-05.md
f5538af2cad6c76a520bbcfcad6fa227cfd3f0573e785721f6a4e69104b8f8f6  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-pdf-parser-bounds-gate3-2026-09-05.md
```

## Closed prior Gate 5 findings

The exact correction rejects repeated identities inside overlapping xref-stream
`/Index` ranges. It also requires one exact Catalog Root, one Catalog `/Pages`
reference, an exact Pages/Page topology through `/Kids`, exact child `/Parent`
links, cycle-free traversal, and `/Count` equal to the number of reachable Page
leaves. The approved non-overlapping xref stream, classic document, compressed
Page object, and incremental `/Prev` controls remain accepted. The earlier
classic/stream xref reconciliation, ordinary-stream length checks, shared
decompression budget, and duplicate classic-xref checks also remain GREEN.

## Blocking finding: object-stream framing and filter are not validated

The approved algorithm says structural streams permit only `FlateDecode`, their
declared lengths are checked structurally, and unsupported/ambiguous structure
fails closed. `objectStream()` captures `/Length` but never compares it with the
stream payload boundary. It also treats every object stream whose body does not
contain literal `/FlateDecode` as an unfiltered stream, even when `/Filter`
declares another algorithm.

Independent bounded reproductions rebuild the whole classic-xref fixture after
the mutation, so all offsets and direct identities remain exact:

```text
object_stream_declared_length_1_for_7_received_bytes=passive_pdf
object_stream_filter_ASCIIHexDecode_with_raw_payload=passive_pdf
```

Both documents have a valid one-page Catalog/Pages tree. Their fourth,
unreferenced structural object is respectively:

```text
<< /Type /ObjStm /N 1 /First 4 /Length 1 >>
stream
5 0 <<>>
endstream

<< /Type /ObjStm /N 1 /First 4 /Filter /ASCIIHexDecode /Length 7 >>
stream
5 0 <<>>
endstream
```

Expected for both: `invalid_pdf`. Actual for both: `passive_pdf`.

This is a material fail-open at the public inspector seam. It is not merely an
unreachable-content issue: production intentionally enumerates every indexed
`/Type /ObjStm` before building the reachable latest-object graph, and the
normative contract explicitly applies the structural grammar and filter rule to
object streams. The existing executable suite has no mutation for either axis.
Because this coverage gap was discovered at Gate 5, it must restart at Gate 2,
receive a fresh independent Gate 3, then receive a minimal production correction
and another fresh independent Gate 5 review.

## Independent verification

```text
$ php -l app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php
No syntax errors detected in app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php

$ /usr/bin/time -l php tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_PDF_BOUNDARY_OK
0.65 real; maximum resident set size 164265984

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

$ git diff --check d1d9354..70aabd19
exit 0
```

The focused and relevant upload regressions are GREEN, and the prior tree/xref
findings are closed. Gate 5 nevertheless remains **CHANGES_REQUESTED** because
the independently reproduced structural-stream fail-open violates the complete
approved parser contract. Safe-log and the overall command remain outside this
verdict.
