# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 parser bounds — Gate 3

Date: `2026-09-05`

Reviewer: separately tasked agent `/root/pdf_bounds_gate3`.

Verdict: **APPROVED**.

The reviewer did not author the executable tests, fixtures, specification or
production correction and did not edit production. Review scope is corrective
RED commit `f13bbe3992e5361c1c052e5b3bf0d272b2349a98` against unchanged production
baseline `2ebb3416b4831ddb919327d42ec5fdbef4861272`.

## Exact reviewed inputs

```text
bf008158e451e216f922dfca94f901c936123f50a624685cd249c69ba6f63e5f  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
31e8d1036f99f6e448a0fa23a6027b8a3012946127320a9549d31bfc99649116  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
50306f04d6bd29fe83072fedb37b5f32e78fe97c59185822b0340072b21c5a16  tests/Support/AssignmentOrderOriginalPdfCorpus.php
ca1d8bde91ec46735e528dd38eb0148146f8fd83ceccfdb70bd97fc24cf9f02d  tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
0498c38f3a20082cc04b72a974dfce8d3e84114e5f7a8c439623ad7f59258006  app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php
fd6d2abbf4b718098b692021de555c3ea9584fcebcf762ee7a83e27bfe59b353  docs/operations/assignment-order-original-pdf-parser-bounds-red-2026-09-05.md
```

The diff from the production baseline contains only the two executable-test
files and append-only RED evidence. No OpenSpec, production, configuration,
safe-log or runtime file changed.

## Sensitivity and controls

- The ordinary reachable `/Contents 4 0 R` stream independently pins declared
  versus actual bytes: the accepted control is `4/4`; the two RED mutations are
  `1,000,000/4` and `1/4`. Thus both out-of-object/file end and short mismatch
  are exposed without changing reachability or another parser axis.
- The two Flate fixtures are each only `65,687` received bytes. Their decoded
  per-stream sizes are respectively `33,554,432` and `33,554,433`; the exact
  aggregates are `67,108,864` and `67,108,866`. The at-limit fixture is required
  to remain `PASSIVE_PDF` before the overflow assertion, preventing an
  off-by-one or blanket multi-stream rejection from satisfying the RED.
- The existing `passiveClassic()` control remains accepted. The negative adds
  only a second classic subsection for identity `1 0`, with the same exact
  object offset `0000000009` and generation. This isolates repeated identity
  from bad offset, generation, overlap payload or object-body corruption.
- Assertions before the final aggregate assertion passed, proving the exact
  `/Length`, aggregate-at-limit, received-size compactness and classic-xref
  non-overlap controls all reached their intended outcomes.

Expected values are derived from the approved `fmonitor-passive-pdf-v1`
contract, not parser internals. The test remains bounded: independent timed
execution completed in `0.66s` with maximum resident set size `164,216,832`
bytes and created no database, filesystem or storage fixture requiring cleanup.

## Independent reproduction

```text
$ php tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
exit=255

Expected: length_past_actual_end, length_before_actual_end,
aggregate_decoded_overflow, repeated_classic_xref_identity => INVALID_PDF
Actual:   length_past_actual_end, length_before_actual_end,
aggregate_decoded_overflow, repeated_classic_xref_identity => PASSIVE_PDF
```

Raw capture hashes exactly reproduce the RED evidence:

```text
55f3ae7bb611062781ff05aaf79788d3a18a3daa78ce2b657fe70c97ad153838  stdout (1455 bytes)
fbb5af5db5d0750fcfda858277833e82f4f4a9c4615c51eaad876ead36d582b0  stderr (1459 bytes)
```

Both changed PHP files pass `php -l`; `git diff --check` for
`2ebb3416..f13bbe3` exits `0`. The failure is the intended missing fail-closed
production behavior, not setup, resource exhaustion or fixture construction.
Gate 4 may proceed for these exact reviewed hashes.
