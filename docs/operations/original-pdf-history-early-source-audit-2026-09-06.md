# PDF-HISTORY-001 — early mutable-source audit

- Date: `2026-09-06`
- Reviewer: separately tasked agent `/root/registry_engine_gate1`
- Observed repository HEAD: `ac7c676c9dfde8eafd45f7891b83bda88cea7220`
- Scope: mutable GREEN parser source; early findings only, not Gate 5
- Verdict: **TWO CONCRETE GAPS; TWO EARLIER CONCERNS WITHDRAWN; RESOURCE BOUNDARY PASSES DIAGNOSTIC**

No production source, reviewed test or specification was edited. Harmless
synthetic PDFs were passed only to the public inspector. They contained marker
tokens as inert bytes and executed no PDF action or script. No real document,
database, OS/native/permission probe or external system was used.

## Confirmed finding 1 — repeated allowed opaque filters are over-rejected

PDF-HISTORY-001 section 4 permits an opaque stream Filter to be a nonempty direct
array of Names from the closed allowlist. It separately rejects a duplicate
`Filter` **entry**, meaning more than one `/Filter` key in the stream dictionary.
It does not require all codec Names inside one valid filter chain to be unique.

`FMonitorPdfStreams::validate`, lines 75–80, keeps `$seen[$name]` and rejects the
same allowed codec appearing twice in the array. Thus an opaque stream with
`/Filter [/FlateDecode /FlateDecode]` is rejected even though both direct Names
are allowed and the filter-chain payload remains opaque under the candidate.

Required correction: remove only the array-member uniqueness restriction. Keep
duplicate dictionary `/Filter` keys invalid through the dictionary parser, keep
every array member a direct Name, keep the allowlist closed, and keep `/Crypt`
unsafe. Add an exact positive repeated-codec array test plus duplicate-key,
non-Name, indirect, unknown and Crypt controls through the already required
separate Gate 3 before modifying source.

## Confirmed finding 2 — nested dictionary grammar is not validated

PDF-HISTORY-001 sections 2–4 require invalid structure/tokenization to return
INVALID_PDF and state that structural dictionary recognition uses exact lexical
boundaries. Adobe Cos dictionary grammar requires dictionary entries to be
key/value pairs whose keys are Names.

`FMonitorPdfDictionary::entries` validates key/value pairing only for the outer
dictionary passed to it. `valueEnd`, lines 31–55, balances a nested `<< >>` value
but does not parse its entries: any sequence of otherwise legal scalar tokens is
accepted until the delimiters balance.

Harmless public-inspector reproduction added an xref-listed unreachable object:

```text
4 0 obj
<< /Metadata << 123 456 >> >>
endobj
```

The nested dictionary uses numeric `123` as a key and is invalid PDF dictionary
syntax. Observed result:

```text
status = passive_pdf
byteSize = 392
sha256 = 0d1100ef1904a4da931f85fc965995bd022054a5c6a16cf4ea0d83dc5ae9cd7e
```

Required result is INVALID_PDF. The dictionary parser must validate key/value
alternation recursively or with an iterative stack for every nested dictionary,
while retaining bounded nonrecursive operation over the 20 MiB input. Tests
should include numeric/string/array keys, missing values, duplicate actual keys,
balanced valid nested dictionaries, nested arrays containing dictionaries, and
forbidden Name tokens inside valid nested values. Strings/comments/hex data must
remain lexically opaque.

## Withdrawn concern — rootless older xref stream

The early note that every older xref stream should be allowed to omit `/Root` is
not supported by this candidate's approved compatibility boundary. PDF-HISTORY
section 4 says structural stream dictionaries retain the exact direct-name/
numeric requirements of the existing grammar. The prior approved xref-stream
tests require one Root for that stream shape. Classic older trailers can omit
Root under their inherited grammar; that does not automatically widen xref-
stream grammar.

`FMonitorPdfXref::section` requiring Root for an xref stream is therefore not a
finding in this audit. Any future grammar widening would require a new explicit
specification decision and cannot be inferred from classic trailer behavior.

## Withdrawn concern — blanket generic reference-cycle rejection

The parent mentions cycles as a fail-closed structural condition, but ordinary
valid PDF page trees contain child `/Parent` backlinks. Rejecting every repeated
reference discovered from Catalog would reject the canonical
Catalog→Pages→Page→Parent Pages shape.

Current page-tree validation separately rejects repeated page-tree nodes, and
the xref walk rejects repeated `/Prev` offsets. No exact approved text was found
requiring all non-page metadata graph backlinks to be acyclic. The generic
queue's visited-set behavior is therefore not a proven defect. A broader cycle
policy must first identify the precise structure whose cycles are forbidden;
this audit does not request a blanket graph change.

## Bounded 100,000/100,001 object diagnostic

A read-only synthetic classic-xref child ran with exact PHP
`memory_limit=256M` and `max_execution_time=30`. It generated the three canonical
Catalog/Pages/Page objects and filled remaining objects with the direct value
`null`. Offsets and xref rows were generated independently before invoking the
public inspector.

Observed results:

```text
{"objects":100000,"bytes":4389116,"status":"passive_pdf","seconds":0.527661959,"peak":96157696}
{"objects":100001,"bytes":4389161,"status":"invalid_pdf","seconds":0.302399833,"peak":55771136}
```

The approved maximum succeeds well below 256 MiB and the first over-limit case
fails closed. The lower peak for 100,001 reflects early rejection of object
identity `100001`; it is not evidence of a skipped limit. No timeout or memory
bound was widened. A reviewed Gate 2 test should still run boundary cases in a
bounded child so fatal resource regressions remain observable rather than taking
down the parent verifier.

## Confirmed positive source properties

- Exact Name tokenization skips literal strings, comments and hexadecimal
  strings; `#xx` decoding occurs only inside Names and decoded NUL is rejected.
- Physical object scanning advances over declared opaque payload bytes, so
  object-like payload tokens are not rediscovered as physical objects.
- Selected physical direct objects are matched to exact xref number/generation;
  all selected stream dictionaries are history-scanned.
- Historical type-2 entries are checked during oldest-to-newest accumulation
  against the container/member visible in their own revision state.
- Structural decoding is cached by physical offset and actual Flate expansion is
  charged once to the aggregate 64 MiB budget.
- The xref loop permits at most 64 terminating sections and rejects a continued
  `/Prev` at the 64th section without widening the bound.

## Exact observed mutable-source hashes

```text
97483d702d6658398242783359a520b488ec15d4ba2ca6e504994584de23cabe  app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php
a956fd552993bb3636aeb42d6a6be731d8f00d9d6c55735e0a9f1f3b1b2be7d3  app/AssignmentOrderOriginal/FMonitorPdfDictionary.php
de98a8a1198ccdd7024d73ec24aeec51be35c869ac29c889701c28949b314744  app/AssignmentOrderOriginal/FMonitorPdfGraph.php
ea3f17f23ead036b5b0ff23286399b2fc237a532461b0c36d4eea31b0443b92d  app/AssignmentOrderOriginal/FMonitorPdfLexical.php
323f075e70e1ef230a61305f6c39511481ac176021a5ddd69f1bb6a475bdefd0  app/AssignmentOrderOriginal/FMonitorPdfPhysicalObjects.php
36166797813e8ce95460ce1db7841f092b9db269531f3ad8926437fb787480e5  app/AssignmentOrderOriginal/FMonitorPdfStreams.php
f92777ce668c00558307f30ed5a685f675ee4d79233944386eda1520a10553a1  app/AssignmentOrderOriginal/FMonitorPdfXref.php
d7d21869b959dfb839230f084719ff3cc0bc7c6168acfdd5387ebbe710ff34e3  specs/ASSIGNMENT-ORDER-ORIGINAL-PDF-HISTORY-001.md
```

These are mutable-worktree observation anchors, not approved implementation
identities. This audit does not issue Gate 5 or resolve the separate long-Length
oracle review.
