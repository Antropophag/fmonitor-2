# Gate 3 test review: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 ObjStm corrective RED

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/pdf_objstm_gate3` (fresh independent
  reviewer; did not author the specification, RED fixtures/tests, production
  parser, or source Gate 5 review)
- Exact reviewed RED commit:
  `8d0c6e46cd23f53c8b833c151fdefb85ca078524`
- Exact production baseline / source Gate 5 review commit:
  `5077899dd4d95eed9c088c2dab12a10ebef8f178`
- Verdict: **APPROVED**

## Scope and contract fit

The corrective RED is narrowly bound to the object-stream framing and filter
fail-open identified by Gate 5. It preserves independent exact-length raw and
`FlateDecode` controls, then requires `INVALID_PDF` for declared `/Length` one
byte shorter, declared `/Length` one byte longer, and an unsupported
`/ASCIIHexDecode` declaration carrying the otherwise parseable raw bytes.

This matches the approved `fmonitor-passive-pdf-v1` contract: structural stream
lengths are exact, structural streams permit only `FlateDecode`, and unsupported
or ambiguous structure fails closed. The public outcome, algorithm identifier,
limits, and accepted grammar are unchanged.

## Independence and sensitivity audit

- Every fixture has a Catalog -> Pages -> Page path, and the Page identity `3 0`
  is not direct: xref type 2 maps it to object stream `4 0`, member index zero.
  The decoded member header is exactly `3 0 ` (`/First 4`), followed by the Page
  dictionary with exact `/Parent 2 0 R`.
- Direct object offsets are rebuilt for every variant. Independent decoding of
  all five xref streams found exact type-1 offsets for objects 1, 2, 4 and 5,
  and the exact type-2 tuple `2/4/0` for object 3. Each `startxref` points to the
  corresponding `5 0 obj` xref stream.
- The raw control's payload is 57 bytes and declares 57. The shorter and longer
  mutations preserve the same 57 payload bytes while declaring 56 and 58.
  Their complete-file SHA-256 hashes differ, so neither is an aliased control.
- The Flate control carries a 58-byte `gzcompress(..., 9)` zlib stream, declares
  58, and independently decompresses to the same 57-byte member payload.
- The ASCIIHex mutant preserves that raw 57-byte payload and exact declared
  length; only the explicit filter token differs. It therefore cannot pass as
  an unfiltered stream without exposing the reported filter bug.
- Both positive controls are asserted before the aggregate negative assertion.
  The reproduced failure occurs only at that final assertion, which proves the
  pre-existing parser suite and the two new controls completed successfully.
- Fixtures are deterministic compact in-memory strings (417..441 bytes). They
  create no filesystem, database, storage, process, network, or orphan state;
  cleanup is empty and bounded.

No production, OpenSpec, runtime configuration, safe-log, or existing evidence
file changed in the reviewed commit. The production inspector is byte-identical
to the Gate 5 baseline.

## Independent RED reproduction

```text
$ php tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
exit=255
stdout=1175 bytes
stderr=1179 bytes

Expected:
  declared_length_shorter => INVALID_PDF
  declared_length_longer => INVALID_PDF
  unsupported_asciihex_raw => INVALID_PDF
Actual:
  declared_length_shorter => PASSIVE_PDF
  declared_length_longer => PASSIVE_PDF
  unsupported_asciihex_raw => PASSIVE_PDF
```

Raw reproduction hashes:

```text
744411fff907ac5667041a78123dbadffbf70cf8fe9d29da6f274ac93b2c40bb  stdout (1175 bytes)
87270a3ad8e3526c44fef69b23203739de6f1f70d38a0e0c3d05cbdcf8112f6b  stderr (1179 bytes)
```

Static checks:

```text
$ php -l tests/Support/AssignmentOrderOriginalPdfCorpus.php
No syntax errors detected in tests/Support/AssignmentOrderOriginalPdfCorpus.php

$ php -l tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php

$ git diff --check 8d0c6e46^..8d0c6e46
exit=0
```

## Exact reviewed hashes

```text
c9016cb317f863a9d65d9b0d4d9bd88b26733e59f6db1719736550b73f26d033  tests/Support/AssignmentOrderOriginalPdfCorpus.php
757a4710c1e4605a96e9adcef1bc9f676fc4be0ab9f5c658bac826659fae5aca  tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
58395291011f21a55188bffb4e67de148bb83d4cb7bb12dc5de1fccdfa49ff11  app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php
bf008158e451e216f922dfca94f901c936123f50a624685cd249c69ba6f63e5f  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
31e8d1036f99f6e448a0fa23a6027b8a3012946127320a9549d31bfc99649116  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
2080ef936512a194e313f2930609f630bdecc607abc44c00a18d2c983c4ee6a4  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-pdf-parser-v2.md
9a8ff5894a688882257909dd9199906b6fa41375b603f649e1890399a88d2670  docs/operations/assignment-order-original-pdf-parser-object-stream-red-2026-09-05.md
```

Gate 3 is **APPROVED**. A minimal production correction for these exact ObjStm
framing/filter gaps is authorized; it still requires a fresh independent Gate 5
review.
