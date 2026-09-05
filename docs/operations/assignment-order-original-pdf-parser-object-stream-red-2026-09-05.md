# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — object-stream corrective RED

Date: `2026-09-05`

Source finding: independent Gate 5 review
`5077899dd4d95eed9c088c2dab12a10ebef8f178`.

Gate 2 RED author: separately tasked agent `/root/pdf_bounds_red` (not eligible
to review these tests or their production correction).

Production baseline: `70aabd1` (`fix: validate passive pdf page trees`), with
the exact inspector hash below. No production, OpenSpec, safe-log, runtime
configuration, or prior evidence file was changed by this RED.

## Independent controls and mutations

The fixture always builds the same structurally active PDF 1.5 graph: Catalog
points to Pages, Pages `/Kids` points to object identity `3 0`, xref type 2 maps
that identity to index zero of object stream `4 0`, and the decoded object is the
required Page with exact Parent. All direct offsets and xref rows are rebuilt
after each dictionary/payload change.

- The exact-length unfiltered object stream preserves the already approved
  valid control.
- The exact-length `/FlateDecode` object stream proves the only approved
  compressed filter is decoded and accepted.
- Declared `/Length` one byte shorter and one byte longer than the same raw
  payload must each fail closed.
- `/Filter /ASCIIHexDecode` with the same raw payload must fail closed; it must
  not be treated as an unfiltered object stream merely because the bytes happen
  to contain a parseable object.

Fixtures are compact deterministic in-memory strings and create no filesystem,
database, storage, or public-orphan state. Cleanup is therefore empty and
bounded. Reaching the final aggregate assertion proves all prior parser tests
and both new controls passed first.

## RED transcript

```text
$ php tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
exit=255
Expected:
  declared_length_shorter => INVALID_PDF
  declared_length_longer => INVALID_PDF
  unsupported_asciihex_raw => INVALID_PDF
Actual:
  declared_length_shorter => PASSIVE_PDF
  declared_length_longer => PASSIVE_PDF
  unsupported_asciihex_raw => PASSIVE_PDF
```

Raw capture hashes:

```text
744411fff907ac5667041a78123dbadffbf70cf8fe9d29da6f274ac93b2c40bb  stdout (1175 bytes)
87270a3ad8e3526c44fef69b23203739de6f1f70d38a0e0c3d05cbdcf8112f6b  stderr (1179 bytes)
```

Exact pre-commit file hashes:

```text
c9016cb317f863a9d65d9b0d4d9bd88b26733e59f6db1719736550b73f26d033  tests/Support/AssignmentOrderOriginalPdfCorpus.php
757a4710c1e4605a96e9adcef1bc9f676fc4be0ab9f5c658bac826659fae5aca  tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
58395291011f21a55188bffb4e67de148bb83d4cb7bb12dc5de1fccdfa49ff11  app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php (unchanged production baseline)
```

Classification: **INTENDED RED**. A fresh independent Gate 3 review is required
before the next production correction.
