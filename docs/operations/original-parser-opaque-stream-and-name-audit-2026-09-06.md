# Original PDF parser — opaque stream and exact-name audit

Дата: 2026-09-06.  
Reviewer task: `/root/selection_v04_readiness`.  
Reviewed HEAD: `a1ce83dfb5c4796b05a488b8f6edad46d01e0d12`.  
Verdict: **TWO EXISTING-CONTRACT GAPS CONFIRMED**.

Это bounded read-only source audit с harmless synthetic public-inspector probes.
Никакие реальные PDF, renderer, action execution, filesystem, DB или external
data не использовались. Код, tests и specifications не изменялись. Findings не
разрешают новый filter/grammar algorithm без exact Gate 1.

## Exact reviewed hashes

```text
1a59ecc5ec45470ff76a6c043e29c67bdb89b641b298851540a050b46d6fe394  app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php
de9622d1d7691330fe905b0cfefc49b8ad4f7985489b6b4fab51d9e750a2cd52  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
37254fb8319093d958ae8138faa7d600d7236035392655e1b711b8959347f55a  tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
e85f3c9e56f23856f6603c4a04f42ae53a31fea156e23eff6806874a532e0933  tests/InstallationProcess/assignment_order_original_pdf_parser_incremental_001_test.php
```

## Existing normative boundary

Parent section 5 states that structural streams support only the pinned
structural FlateDecode path. Image and content streams are not decoded for OCR;
their bounds and declared lengths are checked structurally. The inspector scans
dictionary names/actions across revisions and decoded object streams for the
exact active names listed by the contract.

This already distinguishes structural data needed to interpret the PDF graph
from opaque page/image payload. Accepting a structurally well-framed opaque image
stream does not require adding OCR, rendering, external resource loading or a new
active-content policy.

## Finding 1 — reachableBody applies structural filter policy to opaque streams

`FMonitorPassivePdfInspector.php:55-57` handles every reachable object containing
a stream. It parses the declared length, then:

- rejects any scalar `/Filter` other than `/FlateDecode`;
- attempts `gzuncompress` for every `/FlateDecode` stream;
- returns only the dictionary and discards the resulting payload.

The method does not first classify whether the stream is an xref stream, object
stream or ordinary image/content stream. Xref and object streams already have
their own structural decoders at lines 37–46 and 52–54. Applying the same
Flate-only/decompression rule again to reachable image/content objects therefore
rejects opaque data whose bytes are not needed to build the object/page graph.

### Public synthetic evidence

A minimal one-page classic-xref PDF was inspected through
`FMonitorPassivePdfInspector::inspect`. Its Page references one one-pixel Image
XObject. Declared length and xref offsets are exact; payload is inert synthetic
data.

```text
plain one-page, no image             => passive_pdf
same page + /Filter /ASCIIHexDecode  => invalid_pdf
same page + /Filter /DCTDecode       => invalid_pdf
```

The ASCIIHex payload was `00>` and the DCT payload was the harmless literal
`notjpeg`; the test did not ask the parser to decode or render either. The result
is driven by the filter name before payload use. A real JPEG image XObject uses
DCTDecode and reaches the same rejection.

This is a gap against the current opaque-stream guarantee. It is not evidence
that every PDF filter, filter array, predictor or malformed stream must be
accepted. Exact Gate 1 must define classification and the structural checks
retained for nonstructural streams before implementation.

## Finding 2 — active-name regex scans strings/comments and prefix names

At lines 25–26 the parser takes the entire reachable object body/dictionary as
raw text, decodes every `#xx` sequence globally, then searches each active marker
using `/NAME\b/`. It does not lex PDF comments, literal strings, hex strings and
Name objects separately.

`` is a regular-expression word boundary, not a PDF Name delimiter. Hyphen,
period and many permitted name bytes are non-word regex characters but remain
part of one PDF name. Thus `/JS-Notes` is a distinct harmless name, yet it matches
the `/JS\b/` detector. Global scanning also treats `/JS` inside a literal string
or comment as an executable name.

### Public synthetic evidence

Each case changed only harmless catalog metadata in the valid minimal one-page
fixture:

```text
(harmless /JS text)   literal string => unsafe_pdf
% harmless /JS        comment        => unsafe_pdf
/JS-Notes             distinct Name  => unsafe_pdf
/JavaScriptNotes      distinct Name  => passive_pdf
```

The last control proves the implementation is not rejecting every prefix; the
false positive depends on regex word-boundary characters. Encoded forms such as
`/JS#2DNotes` are also exposed because global `#xx` decoding produces
`/JS-Notes` before the same regex.

The contract requires rejection of exact active keys/actions, not matching their
bytes inside strings/comments or longer Name objects. Correct lexical separation
is therefore an existing-contract obligation rather than a new decision to
permit active content.

## Required Gate 1 boundaries

Any parser correction must first specify exact behavior; this audit does not
select an implementation. At minimum the executable amendment must pin:

1. How a reachable stream is classified as structural xref/object stream versus
   opaque content/image/other stream using parsed dictionary values.
2. Which dictionary/length/framing/filter shapes are required for opaque streams,
   without decoding their bytes or silently approving malformed filter arrays.
3. That structural xref/object streams retain current Flate-only decoding,
   decoded-byte limits and graph requirements.
4. A PDF lexical scanner for dictionary Name tokens that skips comments, literal
   strings with escapes/nesting and hex strings, and decodes `#xx` only within a
   Name token.
5. Exact PDF Name termination rules rather than regex `\b`, including longer
   names containing hyphen, period, underscore, digits and encoded bytes.
6. Exact active-name matching after Name-token decoding, preserving rejection of
   `/JS`, `/JavaScript`, `/OpenAction`, `/AA` and every other approved marker in
   direct and object-stream dictionaries.
7. Bounds for lexical scanning, nesting/escape handling and malformed tokens,
   using existing document/object/depth/decompression ceilings unless a separately
   reviewed change is necessary.

Positive controls should include opaque Flate content, ASCIIHex/DCT image
XObjects and benign exact-prefix names/strings/comments. Negative controls must
place every active Name in real dictionary/action positions, including `#xx`
encoded names and decoded object streams. Malformed syntax/filter shapes must
remain invalid, and encrypted/external/action documents remain unsafe.

## Coverage assessment

Current parser suites cover active markers, classic/xref/object streams,
incremental history and several structural failures, but they do not contain a
positive non-Flate image XObject or distinguish Name tokens from marker text in
strings/comments/prefix names. Their existing green results therefore do not
contradict these findings.

The harmless probes above are diagnostic evidence only. A normative parser
amendment must receive independent Gate 1 before committed RED tests, followed by
independent Gate 3, minimal GREEN and Gate 5. Parser algorithm ID/version
disposition must be explicit at that Gate; this audit does not silently retain or
change it.

## Disposition

Both gaps are reachable through the public inspector and can reject ordinary
passive documents. They should be included in the pending parser lexical/history
correction so one complete exact grammar is reviewed rather than serial local
patches. No new format support beyond the already stated opaque-stream and exact
active-name guarantees is approved by this record.
