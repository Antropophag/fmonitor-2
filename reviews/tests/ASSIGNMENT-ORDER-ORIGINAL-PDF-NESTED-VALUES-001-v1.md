# Test review: ASSIGNMENT-ORDER-ORIGINAL-PDF-NESTED-VALUES-001 v1

- Reviewer: separately tasked agent `/root/selection_v04_readiness`
- Test author: `/root`; reviewer authored neither test nor production
- Reviewed HEAD with uncommitted parser GREEN WIP: `ac7c676c9dfde8eafd45f7891b83bda88cea7220`
- Specification: `specs/ASSIGNMENT-ORDER-ORIGINAL-PDF-HISTORY-001.md`,
  SHA256 `d7d21869b959dfb839230f084719ff3cc0bc7c6168acfdd5387ebbe710ff34e3`
- Test SHA256:
  `ab40ff465c81d065e6505c9c78225519eb7d5561318a7222cb737a30030c5919`
- Verdict: **APPROVED**

## Exact reviewed evidence

```text
ea740062e623b4fa755b746adc3de04b4b24a81b2adb48be3ea4ea543ca617da  tests/Support/AssignmentOrderOriginalPdfHistoryCorpus.php
5f7e0e804887a6d601407aa67babf8926fbce8d292f83849b77aca08cecfb2e6  docs/operations/original-pdf-history-early-source-audit-2026-09-06.md
480a6a6433236fa7aa80130ca39b4334c5d427f62a27ff6636ca33c2f34387c4  /Users/antropophag/.local/state/fmonitor2-verification/original-pdf-nested-values-red-yxz8g0ai/evidence.json
f2d3ac17658d9035fb3c39c60f328305a515c0537fa610f539f96e13bf471ea4  /Users/antropophag/.local/state/fmonitor2-verification/original-pdf-nested-values-red-yxz8g0ai/red.log
```

## Gate 1 authority

HISTORY-001 section 2 states that invalid structure or tokenization returns
`INVALID_PDF`. Section 3 adopts the Adobe PDF/Cos lexical definitions, requires
structural dictionary recognition to use those token boundaries and forbids
duplicate actual required keys. A PDF dictionary is a sequence of Name-key/value
pairs; nested dictionaries and dictionaries inside arrays are values governed by
the same grammar.

This is sufficient existing Gate 1 authority for the test. Requiring a Name key,
exactly one valid value per key and a complete indirect-reference token does not
introduce a new nesting depth or broaden the accepted value grammar. The existing
received/decoded input bounds and nonrecursive implementation requirement remain.

## Invalid-case oracle

Each of the six expected `INVALID_PDF` cases violates one exact dictionary/value
rule:

- `<< 7 /Value >>` uses an integer where a Name key is required;
- `<< (key) /Value >>` uses a literal string as a key;
- `<< /Key >>` ends a dictionary without the key's value;
- `[ << /Key >> ]` proves the same missing value remains invalid when the
  dictionary is nested in an array;
- `<< /Key true false >>` supplies two values, so `false` occupies the next-key
  position but is not a Name;
- `<< /Key R >>` uses bare `R`, which is only valid as part of a complete
  `<integer> <integer> R` indirect-reference value.

The cases contain no forbidden active Name, encryption or malformed outer xref,
so their expected category is unambiguously INVALID rather than UNSAFE.

## Positive controls

The four controls prevent a shallow reject-all parser:

- `/Key /Value` proves a valid nested Name key and Name value;
- `/Key 3 0 R` proves a complete indirect reference remains valid;
- a nested array containing `true`, `false`, `null`, integer, real, Name, empty
  literal string and empty hex string proves standard values and mixed arrays are
  accepted;
- nested empty dictionary and empty array prove empty containers are lawful
  values.

All controls are embedded through `History::direct()` as an additional
xref-listed object in the same valid one-page classic-xref document. Only the
nested body varies; no production output determines the expected status.

The main HISTORY matrix separately covers strings/comments/hex lexical opacity,
forbidden Names, actual duplicate Filter keys and history scanning. This additive
test need not repeat those matrices to prove the newly identified nested
key/value validation gap.

## RED evidence

The authoritative public-inspector run reports exactly six intended failures:

```text
nested-integer-key             expected invalid_pdf, actual passive_pdf
nested-string-key              expected invalid_pdf, actual passive_pdf
nested-missing-value           expected invalid_pdf, actual passive_pdf
array-dictionary-missing-value expected invalid_pdf, actual passive_pdf
nested-extra-value             expected invalid_pdf, actual passive_pdf
nested-bare-reference-marker   expected invalid_pdf, actual passive_pdf
```

All four controls pass as `PASSIVE_PDF`. The script then fails its aggregate
empty-failure assertion and exits 255. Evidence/log hashes match the private
archive, whose manifest pins the full uncommitted WIP parser and helper sources.
This is behavior RED at the public inspector, not setup failure.

The result matches the independent early source audit: the current nested-value
scanner balances `<< >>` but does not recursively/iteratively enforce dictionary
key/value alternation.

## Sensitivity and scope

The test catches accepting non-Name keys, missing values, surplus scalar values,
bare reference markers, validation only at the outermost dictionary and failure
to validate dictionaries nested in arrays. The controls catch blanket rejection
of nested dictionaries, references, arrays, standard scalar values or empty
containers.

It uses ten harmless generated in-memory PDFs through one public inspector seam.
There is no filesystem, DB, network, renderer, action execution, real document,
new depth limit, skip or allowed failure. A minimal implementation may validate
the already approved grammar with a bounded iterative stack; no grammar widening
is authorized.

## Findings and disposition

No blocking Gate 1 authority, traceability, expected-value independence,
sensitivity, RED-classification, determinism, isolation or scope finding remains.

**APPROVED** for minimal correction of nested dictionary/value syntax under
HISTORY-001. This is Gate 3 for the additive test only. It does not establish
parser GREEN, Gate 5, a new nesting limit or combined original-command readiness.
