# Original PDF parser — all-revisions active-content contract audit

- Date: `2026-09-06`
- Reviewer: separately tasked independent agent `/root/registry_engine_gate1`
- Reviewed implementation: `d7ed54d03041605200887c607ce6b3ce81f579be`
- Parser SHA-256: `1a59ecc5ec45470ff76a6c043e29c67bdb89b641b298851540a050b46d6fe394`
- Parent specification SHA-256: `de9622d1d7691330fe905b0cfefc49b8ad4f7985489b6b4fab51d9e750a2cd52`
- Verdict: **GAP CONFIRMED — ALL-REVISIONS ACTIVE SCAN IS NOT IMPLEMENTED**

No production source, test or specification was edited. The only executions
were harmless synthetic byte strings passed to the public
`FMonitorPassivePdfInspector::inspect` seam. No PDF action or JavaScript was
executed; marker tokens were data only. No real document, OS/native/permission
probe or external system was used.

## Contract

The active `fmonitor-passive-pdf-v1` contract requires the parser to inspect
dictionaries of **all revisions** and decompressed object streams and reject any
listed active key/action, including `JavaScript`, `JS` and `OpenAction`. This is
stronger than validating only the latest reachable Catalog/Pages graph. Older
overwritten dictionaries and xref-listed unreferenced dictionaries remain part
of the PDF byte history covered by the approved scan.

## Source cause

The parser correctly walks the bounded `startxref`/`Prev` chain and validates
every type-1 xref entry against its exact physical object identity. It retains
all selected physical offsets in `$selectedDirect`, requiring every discovered
direct object to be represented by some selected revision entry.

It then builds `$entries` with newest-to-oldest object-number precedence and
constructs `$objects` only from those latest entries. Compressed objects from
latest object-stream entries are added to that same latest-object map. The
ACTIVE-key loop occurs only in the queue traversal starting at the current root:

```text
queue = current root
for each reachable latest object:
  decode reachable dictionary
  scan ACTIVE names
  enqueue references
```

Consequences:

1. a current xref-listed direct or compressed object that is not reachable from
   the current root is structurally validated but never ACTIVE-scanned;
2. an older revision's object body that is superseded by a newer entry with the
   same object number is excluded from `$objects` and never ACTIVE-scanned;
3. decompressed object-stream members not reached from the current root likewise
   escape the ACTIVE loop.

The current-root page-tree validation remains appropriate for determining the
effective document. It cannot substitute for the separate all-revisions
security scan required by the parent specification.

## Reproduction A — unreferenced current-revision active dictionary

Using the existing test corpus classic-xref builder, the synthetic document has
the normal three-object Catalog/Pages/Page tree plus object 4:

```text
4 0 obj
<< /S /JavaScript /JS (harmless-marker-only) >>
endobj
```

Object 4 is included by an exact in-use xref entry but is deliberately not
referenced by the page graph. Public result:

```text
status = passive_pdf
byteSize = 410
sha256 = 8339dc20f17582f3273d9d927b3d8a64ec79c7a0d9bd948005ce749b8cbf47dd
```

Required result is `unsafe_pdf` because `/JavaScript` and `/JS` occur in a
dictionary selected by the current revision. No action payload was executed.

## Reproduction B — overwritten older-revision active dictionary

The synthetic base revision has a valid one-page document whose catalog object
contains marker-only:

```text
/OpenAction << /S /JavaScript /JS (old-harmless-marker) >>
```

A second incremental revision redefines exact object `1 0` as a safe Catalog and
points `/Prev` to the base xref. Both physical object identities and xref offsets
are valid; the latest page graph is valid. Public result:

```text
status = passive_pdf
byteSize = 528
sha256 = 9fac820662029091b46c928c6cf0a0041111d5d21dff85a8ba3343ea97392afb
```

Required result is `unsafe_pdf`: the parent explicitly requires scanning
dictionaries of all revisions, so overwriting an active dictionary cannot erase
the earlier unsafe token from validation scope.

## Prior incremental Gate 5 disposition

The prior incremental-parser review correctly approved its bounded findings:
physical direct-object discovery, exact xref identity/offset checks, newest
object precedence for effective graph construction, `/Root`/`/Prev` grammar,
stream framing, object-stream dictionary checks and existing bounds. Its source
assessment did not demonstrate an all-revisions ACTIVE scan and did not include
either reproduction above. This is an uncovered contract axis, not a duplicate
or contradiction of the approved xref/latest-graph behavior.

The correction must preserve newest-to-oldest precedence for effective
Catalog/Pages evaluation while separately scanning every selected historical
dictionary and every decoded object-stream member for the forbidden names.

## Prev-chain bound

The source loop processes at most 64 xref sections (`revision` 0 through 63).
It rejects an invalid/out-of-range/repeated offset immediately, and if section
64 still contains `/Prev`, the explicit `revision === 63` check returns invalid.
Thus a chain of at most 64 sections can terminate successfully; a longer chain
fails closed. This audit does not request widening or changing that existing
bound.

## Required bounded correction

Before parser changes, add public-inspector RED cases for:

- one xref-listed unreachable direct dictionary containing each representative
  forbidden-name family;
- one older-revision active dictionary overwritten by a safe latest object;
- one unreferenced decompressed object-stream member containing an active key;
- safe unreferenced and safe overwritten controls, proving the correction does
  not reject history merely for being unreachable/old;
- exact 64-section terminating control and 65-section `/Prev` rejection,
  preserving the current bound.

Expected values must be constructed from literal synthetic bytes, not production
parser output. After demonstrated RED and independent Gate 3, minimal GREEN
should separate all-selected/all-revision dictionary scanning from latest
reachable page-graph validation, retain name-escape decoding and decompression/
object/reference limits, then receive independent parser Gate 5 and a fresh
cumulative command review.

## Exact reviewed hashes

```text
1a59ecc5ec45470ff76a6c043e29c67bdb89b641b298851540a050b46d6fe394  app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php
de9622d1d7691330fe905b0cfefc49b8ad4f7985489b6b4fab51d9e750a2cd52  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
e460454efd741e303275a5e54e7c1e3609f1963c919eed9232864507d6aaf6da  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-pdf-incremental-grammar-v6.md
85a72d4c02200ad3ca7f132af37272aceee7cb5371a25db4df168b0bb4637ee2  tests/Support/AssignmentOrderOriginalPdfCorpus.php
e85f3c9e56f23856f6603c4a04f42ae53a31fea156e23eff6806874a532e0933  tests/InstallationProcess/assignment_order_original_pdf_parser_incremental_001_test.php
```

This audit is not Gate 1, Gate 3 or Gate 5. It does not weaken the active-content
contract or approve the combined original command.
