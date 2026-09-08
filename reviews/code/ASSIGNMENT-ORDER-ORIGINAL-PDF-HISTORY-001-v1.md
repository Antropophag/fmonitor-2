# Code review: ASSIGNMENT-ORDER-ORIGINAL-PDF-HISTORY-001 v1

- Review date: `2026-09-06`
- Reviewer: separately tasked independent agent `/root/registry_engine_gate1`
- Reviewed implementation: `e9aca37cbb33f11a7cb63d13c1f06ca41bec35c3`
- Approved specification SHA-256: `d7d21869b959dfb839230f084719ff3cc0bc7c6168acfdd5387ebbe710ff34e3`
- Scope: PDF history, lexical names, structural/object streams, opaque streams and preserved parser bounds
- Verdict: **APPROVED**

The reviewer authored neither implementation nor tests/specification. No source,
test or specification was edited during this review.

## Findings

No blocking parser correctness, security, resource-bound or maintainability
finding remains in the scoped implementation.

### Full selected history and current graph

The inspector first discovers physical direct objects while skipping declared
stream payload ranges. It walks at most 64 xref/Prev sections, validates every
type-1 entry against exact physical number/generation at the referenced offset,
and requires every discovered physical object to be selected by the history.

Every selected physical object is then passed through stream validation. The
lexical view has already scanned each non-payload body for exact forbidden Names,
and xref section results carry the same scan for every trailer/xref-stream
dictionary. Every declared object stream is decoded and all members are parsed
and scanned, including members unreachable from the current Catalog.

Historical type-2 entries are validated during oldest-to-newest accumulation.
At each revision, the referenced container must resolve to the type-1 physical
object visible in that cumulative revision, and the indexed member number must
match. An older container cannot be silently replaced by the newest body. The
final latest-entry map remains separate and alone supplies effective Catalog/
Pages validation.

This closes the reproduced unreachable-object, overwritten-history and
historical object-stream gaps without changing current-root precedence.

### Exact lexical and nested-value grammar

`FMonitorPdfLexical` implements the approved byte whitespace/delimiter sets,
skips comments, balanced literal strings and hexadecimal strings, and decodes
`#xx` only inside Names. NUL or malformed escapes fail. Decoded delimiter,
whitespace and hash bytes remain inside one canonical escaped Name and cannot
become structural delimiters or trigger recursive escape interpretation.

Only exact case-sensitive decoded Name values enter the forbidden-name set.
Nesting and escaped literal strings, comments, hex strings and longer/prefix
Names therefore remain inert controls.

`FMonitorPdfValue` validates nested containers iteratively. Dictionary state
requires alternating Name keys and one complete value; arrays accept complete
values; scalar/reference lookahead consumes only exact valid forms. Per-depth
bitsets reject duplicate structural keys without inventing a nesting-depth
limit. Closing delimiters must match their current container. The previously
accepted numeric nested dictionary key now fails invalid, while valid nested
dName/reference/array/empty-container controls pass.

### Structural cache and opaque streams

Structural xref/object-stream payloads accept absent Filter or one direct
FlateDecode only. Decompression is cached by physical offset, so history scan,
historical type-2 resolution and latest graph reuse one byte result and charge
actual expansion exactly once. Object-stream members have bounded count/header/
offset grammar and each contains one complete parsed value.

Ordinary page-content and Image payloads are never decompressed or scanned for
Names. Their dictionaries and direct nonnegative Length/framing remain checked.
Opaque Filter accepts a single allowed Name or a nonempty direct array of allowed
Names; repeated allowed codec values now remain valid, while duplicate Filter
dictionary keys, indirect/non-Name/unknown filters fail. Crypt is unsafe.

The one-byte Length-oracle correction is valid: the prior `+1` fixture consumed
only its trailing LF and left framing valid, while `+2` consumes the first marker
byte and is unambiguously invalid. No production exception was added.

### Bounds and compatibility

The implementation preserves:

- 20 MiB received input;
- 100,000 physical/object identities;
- current graph/page-tree depth 100;
- 67,108,864 aggregate structural decoded bytes;
- 64 xref/Prev sections.

The 64th terminating section is accepted and a continued 65th is rejected.
Exact structural aggregate limit passes, one byte over fails, cached reuse is not
double charged, and large opaque Flate data does not consume structural budget.
The prior independent diagnostic also showed 100,000 classic objects passing
under 256 MiB and 100,001 failing closed.

Public `ALGORITHM_ID` and `algorithmId()` both remain
`fmonitor-passive-pdf-v1`. Earlier parser and incremental grammar suites remain
GREEN, including direct-offset identity, Root/Prev, xref stream, object stream,
page graph, framing and active-content controls. No parser selector, external
I/O, renderer/OCR behavior or new result type was introduced.

## Independent verification

The reviewer independently ran the five parser scripts on the exact commit:

```text
PDF-HISTORY-001                         110 cases PASS
PDF filter-chain additive suite          6 cases PASS
PDF nested-values additive suite         10 cases PASS
existing parser suite                    PASS
existing incremental parser suite        PASS
```

All eight reviewed production parser files lint successfully. The scoped
implementation delta passes:

```text
git diff --check ac7c676..e9aca37
PASS (no output)
```

Final immutable aggregate evidence:

```text
6c70f86c132ecc4f9f80c59b5108b7c607a275f18116695525d637599a9860b6  /Users/antropophag/.local/state/fmonitor2-verification/original-pdf-history-green-16i84l3i/evidence.json
```

The archive records exact before/after SHA
`e9aca37cbb33f11a7cb63d13c1f06ca41bec35c3`, 34 commands, 33 exit-zero results
and one preserved nonzero historical broad diff check. The nonzero command is
`git diff --check 4c23ee1..HEAD`; its log contains only trailing-space/blank-
context diagnostics from two older reviewed audit/patch artifacts predating the
parser implementation base. It is not reported as PASS and is not erased or
reclassified.

The exact parser delta has a separate immutable successful check:

```text
bb7a928bd49d7778874f5499d0107cf34044125d85068e886d6076c1c9cbd2ba  /Users/antropophag/.local/state/fmonitor2-verification/original-pdf-history-green-16i84l3i/scoped-diff-check.json
```

That historical documentation diagnostic prevents a global clean/full-
verification claim, but does not indicate a defect in the scoped parser source,
tests or delta and does not block this parser-only Gate 5 approval.

## Exact reviewed hashes

```text
f9a598632b01a6b47ebdc668b4b4ba98dba764afb61f94796e80241c0d160dbf  app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php
9077ee9edd168475406c185760f8bfcd30f556187e07158f9b91ba49d3810e85  app/AssignmentOrderOriginal/FMonitorPdfDictionary.php
de98a8a1198ccdd7024d73ec24aeec51be35c869ac29c889701c28949b314744  app/AssignmentOrderOriginal/FMonitorPdfGraph.php
ed91d0afe38dcb53381a990e064b818bc8cbb76ef110cd2b1ccaf757c0c7b4c9  app/AssignmentOrderOriginal/FMonitorPdfLexical.php
7a8e794ef17e36b8fc7879b2c709228e27cc04f59f7c17c1d9fd630dd82d2335  app/AssignmentOrderOriginal/FMonitorPdfPhysicalObjects.php
93f6aa69c187bca1f508e9b3a41b9fb597752fd26d19b8e008077f0bebd33558  app/AssignmentOrderOriginal/FMonitorPdfStreams.php
299885b2724502a1663ca9be919db766d2056a3c1c3904c87186308b79873b61  app/AssignmentOrderOriginal/FMonitorPdfValue.php
f92777ce668c00558307f30ed5a685f675ee4d79233944386eda1520a10553a1  app/AssignmentOrderOriginal/FMonitorPdfXref.php
d7d21869b959dfb839230f084719ff3cc0bc7c6168acfdd5387ebbe710ff34e3  specs/ASSIGNMENT-ORDER-ORIGINAL-PDF-HISTORY-001.md
c675de79379a80bc1b19a2b1ece7c673228d2c4bef957f93d65be27539bf503c  tests/InstallationProcess/assignment_order_original_pdf_history_001_test.php
8f7c2d19c65f7f2912268b16b098540982e958e30411c3684efc2ca445650b70  tests/InstallationProcess/assignment_order_original_pdf_filter_chain_001_test.php
ab40ff465c81d065e6505c9c78225519eb7d5561318a7222cb737a30030c5919  tests/InstallationProcess/assignment_order_original_pdf_nested_values_001_test.php
3acfed07b98126d97d9556e359584c2a9b8cdecd092b42cd624c8bf75908c6d8  tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
e85f3c9e56f23856f6603c4a04f42ae53a31fea156e23eff6806874a532e0933  tests/InstallationProcess/assignment_order_original_pdf_parser_incremental_001_test.php
ea740062e623b4fa755b746adc3de04b4b24a81b2adb48be3ea4ea543ca617da  tests/Support/AssignmentOrderOriginalPdfHistoryCorpus.php
2cc8d60ea01257c61ba75be099859a2ca5e37f5904212ee2b25c7039922b09b9  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-PDF-HISTORY-001-v1.md
5f49f3d46ac9947bd1dc3a2e4aec9cf4e2e17d284b2e9347dfe230fe9bd6d563  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-PDF-HISTORY-ORACLE-001-v1.md
1c672616e9b1c2e0cddf4605f08944babe4f1d4c05cccb86dd72f45a5b8a6793  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-PDF-HISTORY-LENGTH-ORACLE-001-v1.md
935639cddefbf13690c04624e579dbecbf64233e53f199d823557390fcbfd34c  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-PDF-FILTER-CHAIN-001-v1.md
f8f530f8da15779b03981f7b00a124b18bc9f12273d2d9b0c043c9378ddcd6ef  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-PDF-NESTED-VALUES-001-v1.md
e6ea0d1f487fa1fcf88dd55d20a2b7d935dab3042e5c289cd4ee85f0b0112bff  docs/operations/original-pdf-history-implementation-gates-2026-09-06.md
```

## Scope boundary

This verdict approves only PDF-HISTORY-001 at exact implementation
`e9aca37cbb33f11a7cb63d13c1f06ca41bec35c3`. It is not combined original-command
Gate 5, global diff cleanliness, full `VERIFY_OK`, deployment or launch approval.

This review omits its own circular hash.
