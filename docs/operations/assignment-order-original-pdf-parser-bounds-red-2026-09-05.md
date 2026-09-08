# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — parser bounds corrective RED

Date: `2026-09-05`

Gate 2 RED author: separately tasked agent `/root/pdf_bounds_red` (not eligible
to review these tests or their production correction).

Baseline production commit: `2ebb3416b4831ddb919327d42ec5fdbef4861272`.
The approved algorithm remains exact `fmonitor-passive-pdf-v1`. No production,
OpenSpec, safe-log, or runtime configuration file was changed by this RED.

## Executable coverage

- A reachable ordinary `/Contents` stream accepts its exact direct `/Length`
  control and rejects both a declared end outside the object/file and a declared
  length shorter than the actual stream bytes.
- Two reachable Flate content streams individually decode below the per-stream
  bound. The exact aggregate `67,108,864`-byte control is accepted; the compact
  `67,108,866`-byte fixture is rejected. Both generated PDF inputs are asserted
  below `200,000` received bytes.
- A non-overlapping classic xref control is accepted; an additional subsection
  repeating object identity `1 0` is rejected even though its row is byte-exact
  equivalent to the first row.

All large decoded fixtures are generated deterministically in memory and are
explicitly released after inspection. Tests create no database, filesystem,
storage, or public-orphan fixture, so cleanup is empty and bounded.

## RED transcript

```text
$ php tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
exit=255
Expected:
  length_past_actual_end => INVALID_PDF
  length_before_actual_end => INVALID_PDF
  aggregate_decoded_overflow => INVALID_PDF
  repeated_classic_xref_identity => INVALID_PDF
Actual:
  length_past_actual_end => PASSIVE_PDF
  length_before_actual_end => PASSIVE_PDF
  aggregate_decoded_overflow => PASSIVE_PDF
  repeated_classic_xref_identity => PASSIVE_PDF
```

The command reaches the final aggregate assertion, so the exact-Length,
aggregate-at-limit, compactness, and non-overlapping-xref positive controls all
passed first. Every observed failure is therefore an intended missing
fail-closed parser behavior, not fixture setup failure.

Raw capture hashes:

```text
55f3ae7bb611062781ff05aaf79788d3a18a3daa78ce2b657fe70c97ad153838  stdout (1455 bytes)
fbb5af5db5d0750fcfda858277833e82f4f4a9c4615c51eaad876ead36d582b0  stderr (1459 bytes)
```

Exact pre-commit file hashes:

```text
50306f04d6bd29fe83072fedb37b5f32e78fe97c59185822b0340072b21c5a16  tests/Support/AssignmentOrderOriginalPdfCorpus.php
ca1d8bde91ec46735e528dd38eb0148146f8fd83ceccfdb70bd97fc24cf9f02d  tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
0498c38f3a20082cc04b72a974dfce8d3e84114e5f7a8c439623ad7f59258006  app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php (unchanged production oracle)
```

Classification: **INTENDED RED**. A fresh independent Gate 3 review is required
before production correction.
