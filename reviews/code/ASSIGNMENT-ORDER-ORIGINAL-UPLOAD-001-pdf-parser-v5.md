# Gate 5 code review: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 PDF parser v5

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/pdf_xref_gate5_v6` (fresh independent
  reviewer; did not author the specification, executable tests, corpus,
  production parser, corrections, or earlier reviews)
- Exact reviewed implementation SHA:
  `7844c1a783a5da5c09bb336ccce0d31f19a074d6`
- Latest corrective Gate 3 review:
  `c4b93fd444ab972ccef3fcbe973e5db9f8a8563f`
- Scope: complete owned `fmonitor-passive-pdf-v1` parser and all accumulated
  Gate 3 corrections. Safe-log and the overall command slice are excluded.
- Verdict: **CHANGES_REQUESTED**

## Closed findings

The latest correction closes the eight executable xref-stream dictionary
cases. `/Size`, `/W` and `/Root` must each occur exactly once in their direct
approved form; `/Index` is either absent or occurs exactly once as a direct
array. Identical and conflicting duplicates fail closed. The complete approved
corpus remains GREEN, including exact xref identities and offsets, overlapping
Index ranges, page-tree topology, object streams, stream framing, aggregate
decompression, active names, encryption and bounded Prev traversal.

## Blocking finding 1: trailer and Prev dictionaries still use first-match parsing

The correction is limited to four keys in `xrefStream()`. `classic()` passes
the trailer dictionary to `section()`, where `/Root` and `/Prev` are still read
with one unqualified first-match regex; `/Size` is not validated. The same
`section()` path leaves `/Prev` ambiguous in xref-stream dictionaries. Bounded
mutations built from accepted corpus controls therefore produced:

```text
classic_duplicate_root=passive_pdf
classic_conflicting_root=passive_pdf
classic_duplicate_size=passive_pdf
incremental_duplicate_prev=passive_pdf
classic_malformed_prev=passive_pdf
```

The classic fixtures were rebuilt by the corpus helper, so every direct-object
offset and `startxref` value is current. The Prev mutation starts with the
accepted two-revision `validIncrementalPrev()` control and duplicates its exact
Prev value; it does not change any preceding offset. Choosing the first Root or
silently treating a present malformed Prev as absent contradicts the approved
fail-closed ambiguous-structure rule and can select a different document graph
or omit an older revision from inspection.

## Blocking finding 2: valid incremental object replacement is rejected

The approved algorithm requires the latest-object graph across the complete
Prev chain. `direct()` instead rejects a repeated direct object number and
generation anywhere in the whole byte string before the xref chain is parsed.
A bounded valid incremental fixture appended a new `1 0 obj` Catalog, a fresh
`xref 1 1` row pointing to its exact offset, and a trailer with the accepted
base xref in `/Prev`. It produced:

```text
incremental_redefinition=invalid_pdf old_offset=9 new_offset=327
```

Redefining an existing identity in a later incremental revision is precisely
what the newest-to-oldest active xref view must resolve. Rejecting it means the
implementation does not construct the required latest-object graph for a
normal incremental update. Duplicate/conflicting active identities inside one
xref section must still fail closed; that is distinct from a later revision
superseding an earlier object through Prev.

## Blocking finding 3: direct-object discovery is not stream-length aware

`direct()` discovers objects with a non-lexical `.*? endobj` regex before
stream boundaries have been established. A rebuilt classic fixture containing
an ordinary reachable content stream with exact direct Length and harmless
literal bytes resembling `99 0 obj ... endobj` was rejected:

```text
content_bytes_contain_obj_endobj=invalid_pdf bytes=453
```

The same corpus builder recalculated all xref offsets and startxref. Stream
payload bytes are delimited by the approved direct Length and must not create
direct-object identities or terminate the containing object. This also makes
object identity and reachability depend on unparsed payload text rather than
the PDF grammar.

The related dictionary implementation remains inconsistent: an ordinary
reachable stream rebuilt with two identical direct `/Length 4` entries is
accepted as `passive_pdf`, because `reachableBody()` selects the first match.
Object-stream `/N`, `/First`, `/Length` and `/Filter` handling similarly counts
only matching approved forms rather than every structural key occurrence.
These ambiguous direct dictionaries need focused independent executable
coverage rather than another regex-only production adjustment.

Each finding is observable at the public inspector seam and concerns the
already approved grammar, bounds and fail-closed behavior. Correction requires
focused Gate 2 RED, fresh independent Gate 3, minimal GREEN and another fresh
Gate 5. No production or executable-test file was changed by this review.

## Independent verification

```text
$ php -l app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php
No syntax errors detected in app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php

$ /usr/bin/time -l php tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_PDF_BOUNDARY_OK
0.67 real; maximum resident set size 164282368

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

$ git diff --check c4b93fd444ab972ccef3fcbe973e5db9f8a8563f..7844c1a783a5da5c09bb336ccce0d31f19a074d6
exit 0
```

## Exact reviewed hashes

```text
21951b0a0cce91d10a5c72ec88e00cb64fa8a8fa383509ef69c23ddb8c12334b  app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php
37254fb8319093d958ae8138faa7d600d7236035392655e1b711b8959347f55a  tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
d31960881cef93778f32a6b19f3602f1087b4dfcac7b1233b0c1d0a6082c3407  tests/Support/AssignmentOrderOriginalPdfCorpus.php
bf008158e451e216f922dfca94f901c936123f50a624685cd249c69ba6f63e5f  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
cf953eb96770afbdfb703a19bb950374c4239126e22ba1da8ebc7a642c292d29  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-pdf-xref-dictionary-gate3-2026-09-05.md
```

Gate 5 remains **CHANGES_REQUESTED**. Safe-log and command-level readiness are
outside this verdict.
