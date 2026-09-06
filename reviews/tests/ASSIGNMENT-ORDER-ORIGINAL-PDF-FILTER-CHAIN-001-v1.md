# Test review: ASSIGNMENT-ORDER-ORIGINAL-PDF-FILTER-CHAIN-001 v1

- Reviewer: separately tasked agent `/root/selection_v04_readiness`
- Test author: `/root`; reviewer authored neither test nor production
- Reviewed HEAD with uncommitted parser GREEN WIP: `ac7c676c9dfde8eafd45f7891b83bda88cea7220`
- Specification: `specs/ASSIGNMENT-ORDER-ORIGINAL-PDF-HISTORY-001.md`,
  SHA256 `d7d21869b959dfb839230f084719ff3cc0bc7c6168acfdd5387ebbe710ff34e3`
- Test SHA256:
  `8f7c2d19c65f7f2912268b16b098540982e958e30411c3684efc2ca445650b70`
- Verdict: **APPROVED**

## Exact reviewed evidence

```text
ea740062e623b4fa755b746adc3de04b4b24a81b2adb48be3ea4ea543ca617da  tests/Support/AssignmentOrderOriginalPdfHistoryCorpus.php
f953120cbbc41c8af03b9ba7649c864239fd546359cd35b960c351cddc05b977  /Users/antropophag/.local/state/fmonitor2-verification/original-pdf-filter-chain-red-ov_dbick/evidence.json
11f6a2cd2ee3a308bbcbff0cc92e12086d37100cd2f66a3290b598407bbf00ce  /Users/antropophag/.local/state/fmonitor2-verification/original-pdf-filter-chain-red-ov_dbick/red.log
7a461729277075be4a4969b184f7aed663c9e70face98f4ecce54d30a11aa540  /Users/antropophag/.local/state/fmonitor2-verification/original-pdf-filter-chain-red-ov_dbick/initial-setup-failure.json
f04d32953c26be110e7202d7a9405ac153b2c30ad8435eb5a609f3790b69abac  /Users/antropophag/.local/state/fmonitor2-verification/original-pdf-filter-chain-red-ov_dbick/initial-setup-failure.log
```

## Traceability and expected values

HISTORY-001 section 4 permits an opaque Filter to be one direct allowed Name or
a nonempty direct array of allowed Names. It separately rejects duplicate
`/Filter` dictionary entries. “Duplicate Filter entries” therefore refers to
repeated dictionary keys, not repeated codec values inside one array.

Opaque chains are not decoded by this validator; codec execution, image validity
and OCR remain outside the owned structural check. Given a single well-formed
Filter array, repeating an allowed Name does not introduce an unknown filter,
indirect value, malformed array member, duplicate dictionary key or Crypt entry.

The three exact positive expectations are independently determined:

```text
[/FlateDecode /FlateDecode]                         => PASSIVE_PDF
[/FlateDecode /Flate#44ecode]                       => PASSIVE_PDF
[/ASCII85Decode /FlateDecode /FlateDecode]          => PASSIVE_PDF
```

`#44` is the PDF Name escape for uppercase `D`; the second array therefore has
two decoded `FlateDecode` values, both in the closed allowed set. Name decoding
does not turn them into duplicate dictionary entries.

## Control adequacy

The three controls make the test sensitive without broadening the contract:

- `[/ASCII85Decode /FlateDecode]` must remain PASSIVE, proving ordinary distinct
  allowed arrays are accepted;
- `/Filter /FlateDecode /Filter /FlateDecode` must remain INVALID, proving actual
  duplicate dictionary keys are still rejected;
- `[/Unknown /Unknown]` must remain INVALID, proving multiplicity does not make an
  unknown Name acceptable.

All cases use `History::image(..., 'opaque-data')`, which constructs the same
valid one-page Image-XObject framing and varies only the Filter dictionary value.
No expected status is captured from production output. No real image, decoder,
renderer, action or external resource is invoked.

The matrix would catch a uniqueness check over decoded array values, a blanket
rejection of repeated raw tokens, failure to decode Name escapes, acceptance of
duplicate dictionary keys, acceptance of unknown names, reject-all arrays and
accept-all arrays.

## RED evidence

The authoritative run constructs `FMonitorPassivePdfInspector` before resolving
enum constants, correcting the documented initial autoload setup failure. The
initial failure record is retained and excluded from behavioral evidence rather
than overwritten.

The authoritative test exits 255 after reporting exactly:

```text
FAIL repeated-flate: expected passive_pdf, actual invalid_pdf
FAIL repeated-escaped-flate: expected passive_pdf, actual invalid_pdf
FAIL mixed-repeated-codec: expected passive_pdf, actual invalid_pdf
PASS distinct-codecs
PASS duplicate-dictionary-entry
PASS repeated-unknown-name
```

This proves the current WIP parser adds the unsupported uniqueness restriction
while all three controls reach their intended outcomes. Evidence and log hashes
match the private archive, whose manifest pins the complete WIP production and
test/helper source state.

## Scope and isolation

The test is additive, pure and bounded: six generated in-memory PDFs, one public
inspector seam, no filesystem, DB, network, real documents, rendering or action
execution. It changes no existing history test, filter list, decoded-byte limit,
structural-stream behavior, skip or allowed failure.

The test targets only repeated allowed codec Names in one opaque array. It grants
no permission to accept duplicate Filter keys, unknown/indirect/malformed arrays,
non-Name members, Crypt, malformed stream framing or unsupported structural
filters.

## Findings and disposition

No blocking traceability, expected-value independence, control sensitivity, RED
classification, determinism, isolation or scope finding remains.

**APPROVED** for minimal correction of the unsupported opaque-array value
uniqueness restriction after the main HISTORY-001 tests and oracle patches retain
their independent approvals. This is Gate 3 for the additive test only; it does
not establish parser GREEN, Gate 5, combined command approval or broader filter
policy.
