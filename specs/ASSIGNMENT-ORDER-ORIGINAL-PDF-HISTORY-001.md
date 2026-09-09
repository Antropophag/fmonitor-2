# ASSIGNMENT-ORDER-ORIGINAL-PDF-HISTORY-001 — all history and exact PDF names

Owner2026-09-09 correction: [PDF-NAVIGATION-UPLOAD-001](PDF-NAVIGATION-UPLOAD-001.md) supersedes the blanket URI/OpenAction prohibition below for ordinary links and explicit page destinations. All other restrictions remain in force.

Версия0.1, 2026-09-06. **DRAFT / INDEPENDENT GATE1 REQUIRED**.

## 1. Scope and authority

Public seam remains `FMonitorPassivePdfInspector::inspect(string): PdfInspection`.
Algorithm ID remains `fmonitor-passive-pdf-v1`; public `ALGORITHM_ID` constant and
`algorithmId()` return that exact value. This correction implements the existing
all-revisions, exact-name and opaque-image/content guarantees. It adds no OCR,
rendering, action execution, malware scanning, signature validation or external I/O.

Normative parent: ORIGINAL-UPLOAD-001 section5. Independent evidence:
`original-parser-all-revisions-contract-audit-2026-09-06.md` and
`original-parser-opaque-stream-and-name-audit-2026-09-06.md`.

Bounds stay unchanged: received20MiB,100000 objects, reference depth100, structural
decoded-byte aggregate67108864 inclusive,64 xref/Prev sections inclusive. Section64
with a further Prev is INVALID_PDF. No bound is widened or replaced by a timeout.

## 2. Security coverage is independent of current reachability

After structural acquisition of selected xref/Prev history, scan every selected
physical object dictionary/value and every trailer dictionary in every revision,
including bodies overwritten or freed later and objects unreachable from the
current Catalog. Each declared structural object stream is decoded and every
member scanned, including members not referenced by the current page graph.

Exact forbidden Name tokens remain:
`JavaScript`, `JS`, `OpenAction`, `AA`, `Launch`, `EmbeddedFiles`, `Filespec`,
`FileAttachment`, `RichMedia`, `Movie`, `Sound`, `URI`, `GoToR`, `SubmitForm`,
`ImportData`. A matching token in a selected object value also fails closed when
it could be referenced indirectly as an action/name; current reachability cannot
remove it from the scan. Literal/hex string contents, comments and opaque stream
payload bytes are not Name tokens.

A valid structure containing any forbidden token returns UNSAFE_PDF. Invalid
structure/tokenization/filter/framing returns INVALID_PDF; encryption remains
UNSAFE_PDF. Tests avoid overlapping malformed+active causes when they require an
exact error category. No callback or viewer executes any marker payload.

Latest-entry precedence remains the separate rule for effective Catalog/Pages
validation. Old dictionaries are scanned for safety but never replace the latest
root, page tree or revision reference graph. Missing/ambiguous current root,
broken physical xref identity, invalid current graph and zero pages stay invalid.

## 3. Exact lexical Name recognition

Byte rules follow Adobe PDF/Cos lexical definitions: whitespace bytes0,9,10,12,13,32;
delimiters `()<>[]{}/%`. Names start with slash, terminate at raw whitespace or a
delimiter, and compare case-sensitively. Decode valid `#` plus two hexadecimal
digits only inside a Name; decoded NUL, truncated/malformed escapes are invalid.
An escaped delimiter remains part of that Name, never a new token boundary.

These lexical facts are grounded in the [Adobe PDF reference, section3.1](https://opensource.adobe.com/dc-acrobat-sdk-docs/pdfstandards/pdfreference1.7old.pdf)
and [Adobe Cos names/strings documentation](https://opensource.adobe.com/dc-acrobat-sdk-docs/library/plugin/Plugins_Cos.html).

The scanner skips `%` comments through CR/LF or EOF, balanced literal strings with
backslash escapes/nested parentheses, and hexadecimal strings (`<...>`, not `<<`).
Hex whitespace and an odd final nibble are legal; nonhex/unclosed hex strings,
unclosed literal strings and dangling escapes are invalid. Parsing is bounded
by existing received/decoded byte sizes and uses no recursion or token allocation
proportional to an unbounded expansion. No new lexical-depth limit is invented.

Exact distinctions: `/JS` and `/J#53` are unsafe; `/JS-Notes`, `/JS.Notes`,
`/JS_Notes`, `/JS0`, `/JS#2DNotes`, `/JS#20Notes`, `/JS#2FNotes` and `/js` are distinct
non-forbidden names. `(text /JS)`, nested/escaped literal strings containing that
text, `<2F4A53>` and comments containing `/JS` are harmless if the surrounding PDF
is valid. Name text inside a string/comment is not decoded or followed as a
reference. No regex word-boundary approximation or global #xx replacement.

Structural dictionary-name recognition also respects these token boundaries;
quoted/commented fake `/Type`, `/Length` or reference text cannot manufacture a
structural entry. This does not permit duplicate actual required keys.

## 4. Structural and opaque streams

Structural streams are xref streams and object streams. A stream used as a
historical type-2 xref container must be a valid object stream; it cannot fall
back to opaque classification. A lexically declared `/Type /ObjStm` is structural
whether or not any latest entry uses it. Their dictionaries retain exact direct
numeric/name requirements and duplicate-key rejection from the existing grammar.
Absent filter or one direct `/FlateDecode` is permitted for structural decoding;
unsupported, array or indirect structural filters remain invalid.

Decode each physical structural payload at most once per inspection, keyed by
physical offset, and reuse that decoded result for both history safety and the
latest graph. Charge every actual structural Flate expansion exactly once to the
existing aggregate ceiling; overwritten object streams count too. Do not use a
latest object-number cache to hide an older body. Historical type-2 entries must
resolve their own revision's container/member identity, not an unrelated latest
container replacement. Malformed/missing containers or member identity mismatch
are invalid.

Other stream payloads, including page content and Image XObjects, remain opaque.
Validate one direct nonnegative Length and exact existing endstream/endobj framing,
then inspect their dictionaries without decompressing, rendering or searching
payload bytes for PDF names. These payloads do not consume the structural decoded
budget, while the total received-file limit still applies.

Opaque Filter may be absent, one direct Name, or a nonempty direct array of Names.
The closed allowed names are ASCIIHexDecode, ASCII85Decode, LZWDecode, FlateDecode,
RunLengthDecode, CCITTFaxDecode, JBIG2Decode, DCTDecode and JPXDecode. Unknown,
indirect, malformed, duplicate Filter entries or non-Name array members are invalid.
A Crypt filter is unsafe (encryption). Filter chains are not decoded by this
validator; image codec validity and OCR are outside the owned structural check.
Existing bounds/framing/dictionary checks are never skipped for opaque bytes.

## 5. Fixed verification matrix

All fixtures are harmless generated byte strings with independent object offsets,
lengths, xref entries and fixed expected statuses. No real document is used.

- Baseline classic, xref stream, raw/Flate object stream and safe incremental
  revision remain PASSIVE_PDF.
- Each forbidden Name in an xref-listed unreachable dictionary is UNSAFE_PDF;
  valid benign metadata in the same position remains passive.
- Older active Catalog/dictionary overwritten by a safe current object remains
  unsafe; safe older/current pair remains passive and current page graph wins.
- Unreferenced object-stream member with an active Name is unsafe, raw and Flate;
  overwritten older object stream and historical type-2 identity mismatch are
  separately covered.
- Forbidden tokens in a trailer, encoded Name and indirect Name value are unsafe;
  string/comment/hex/prefix-name controls from section3 remain passive.
- Invalid Name escape, NUL escape, malformed literal/hex strings fail invalid.
- One-pixel ASCIIHex and actual tiny JPEG/DCT Image XObjects remain passive;
  filter arrays of allowed names and opaque Flate payloads are checked without
  decompression. Marker bytes inside opaque image/content payload remain data.
- Structural unsupported filters, wrong Length/endstream, duplicate required keys,
  object/reference limits and 64/65 Prev controls retain their invalid outcomes.
- Exact aggregate67108864 structural decoded bytes pass; one byte over fails.
  Reusing a decoded structural object for history/current validation does not
  double-charge. Large opaque Flate expansion does not consume this budget.

Fixture content and expected statuses come from this contract/format grammar,
not output of the current parser. Generated compressed fixtures pin decoded sizes
and structural membership; a merely repeated page-content byte string is not a
structural-budget oracle.

## 6. Existing oracle correction and gates

The old parser test explicitly expects an unreachable forbidden dictionary to be
passive, contradicting the parent all-history requirement. Its decoded-budget
fixture uses ordinary content streams, contradicting structural-only accounting.
Preserve those old bytes in history. Prepare a separate exact unapplied patch:
change unreachable outcome to UNSAFE, replace only the aggregate-bound fixture
with actual structural streams, and retain all expected structural boundaries,
not failures→skips. Add positive opaque-stream controls independently.

Fresh Gate1 for this exact grammar/coverage clarification → public-inspector RED
and independent Gate3 → separately reviewed existing-oracle patch → minimal GREEN
→ both parser suites, affected worker/upload regressions, architecture/lint →
independent parser Gate5. No combined command or launch approval is implied.
