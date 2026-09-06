# Test review: ASSIGNMENT-ORDER-ORIGINAL-PDF-HISTORY-ORACLE-001 v1

- Reviewer: separately tasked agent `/root/selection_v04_readiness`
- Patch/corpus author: `/root`; reviewer authored neither tests nor production
- Reviewed commit containing unapplied patch: `ac7c676c9dfde8eafd45f7891b83bda88cea7220`
- Specification: `specs/ASSIGNMENT-ORDER-ORIGINAL-PDF-HISTORY-001.md`,
  SHA256 `d7d21869b959dfb839230f084719ff3cc0bc7c6168acfdd5387ebbe710ff34e3`
- Independent Gate 1 record SHA256:
  `ae47d6300b0f2b049ba52b9057f4de8cd8a66633fbede8c45cee8ff6bd9a0683`
- Candidate patch SHA256:
  `adc5e3ed15f3ef5d58e88dad1e61d2cdbd9a07c8ab4539531d2d9d67901b7b2b`
- Verdict: **APPROVED**

## Exact reviewed evidence

```text
37254fb8319093d958ae8138faa7d600d7236035392655e1b711b8959347f55a  tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php before patch
3acfed07b98126d97d9556e359584c2a9b8cdecd092b42cd624c8bf75908c6d8  candidate-snapshot/tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
ea740062e623b4fa755b746adc3de04b4b24a81b2adb48be3ea4ea543ca617da  tests/Support/AssignmentOrderOriginalPdfHistoryCorpus.php
57c2634453de89b0e5330df587967cb5ee06a2abe9ef3363d11048b8c279ac15  candidate-red.log
f17f8cedb89a2b0b0706a9db03348d8b232e69174712a442cc79ccbf83c4eeb1  patch-evidence.json
```

Private evidence root:
`/Users/antropophag/.local/state/fmonitor2-verification/original-pdf-history-red-w81_smc8`.
The worktree parser test remains at its pre-patch hash.

## Scope and traceability

The patch changes the existing parser test only. It adds one import/alias for the
already committed pure history corpus, changes unreachable forbidden-history
from PASSIVE to UNSAFE, and replaces two ordinary content-stream aggregate
fixtures with exact structural object-stream aggregates. It changes no parser,
limit, skip, failure allowance, production input or unrelated assertion.

These expectations follow two approved HISTORY-001 behaviors:

1. every physical dictionary/object-stream member in every accepted revision is
   scanned for forbidden active names even when no longer reachable from the
   latest root;
2. the 67,108,864-byte aggregate decoded limit applies to structural xref/object
   streams, while ordinary content/image stream payloads remain opaque.

## Unreachable forbidden-history oracle — PASS

The existing pair uses byte-identical forbidden dictionary content. The reachable
fixture links it through Catalog `/Names`; the unreachable fixture leaves it as a
physical historical object. Existing structural assertions prove the Catalog has
no direct forbidden key and that only the allowed reference changes reachability.

HISTORY-001 makes latest-graph reachability irrelevant to the all-history active
scan, so both outcomes are independently `UNSAFE_PDF`. The patch changes only the
stale unreachable expectation. Current production returns PASSIVE and the private
candidate fails at that first exact mismatch, proving missing behavior rather
than setup failure.

## Structural aggregate fixture review — PASS

`AssignmentOrderOriginalPdfHistoryCorpus::structuralAggregate(total)` constructs
two direct `/Type /ObjStm` containers and two compressed-object xref entries:

- decoded member numbers are 7 and 8;
- each object-stream header is exact `"7 0 "` or `"8 0 "`, hence `/First 4`;
- each has `/N 1`, scalar `/Filter /FlateDecode`, and exact compressed `/Length`;
- xref stream object 6 has valid `/Type /XRef`, `/Size`, `/Root` and `/W [1 4 4]`;
- compressed xref entries point to containers 4/5 at index 0;
- decoded members are syntactically valid dictionaries containing only inert
  `/Padding` literal strings;
- the two decoded payload lengths are `floor(total/2)` and the remainder, so the
  aggregate is exactly the requested integer independent of production output.

For `67_108_864`, the two decoded lengths sum exactly to the approved limit and
the expected parser status remains PASSIVE. For `67_108_865`, they exceed it by
one byte and expected status remains INVALID. Both received PDFs are asserted
below 200,000 bytes, preserving a compact deterministic fixture while requiring
real structural decompression.

The helper computes its payload length from literal header/tail lengths and
asserts exact constructed size before compression. `gzcompress` is fixture
construction only; it does not derive the expected limit or expected status from
the production inspector.

## Old-oracle removal — PASS

The prior `aggregateFlateContentStreams` fixtures used ordinary reachable page
content streams. Counting their decoded payload toward the structural budget
contradicts the approved opaque-stream distinction. Replacing those two inputs
does not relax framing or declared-length coverage: the adjacent
`contentStreamLength` positive and under/over declared-Length negative assertions
remain unchanged.

The patch also leaves the existing decompression-bomb, unsupported structural
filter, xref/object graph and all other parser tests intact. It neither broadens
accepted filters nor changes the numeric ceiling.

## Source-copy and RED evidence — PASS

The evidence manifest pins base HEAD
`2de38cb0c5c1dea21914bdc67d32ea3c83ffcf1b`, the exact patch, pre-patch and
candidate test hashes, corpus/helper hashes and full copied application manifest.
The candidate copy hash matches the private snapshot. The candidate run exits
255 at the intended first mismatch:

```text
unreachable forbidden dictionary: expected unsafe_pdf, actual passive_pdf
```

The log SHA matches the archive. Early first-mismatch termination is sufficient
for this small existing-test patch because the structural boundary expectations
retain their old PASS/INVALID values and their new fixture constructibility is
independently inspectable from the helper source. The separately authored main
110-case history test supplies the broader behavioral RED and has its own Gate 3.

## Sensitivity and preservation

The patched history assertion fails if an implementation scans only the current
reachable graph. The structural pair fails for an off-by-one decoded budget,
failure to decode actual ObjStm containers, counting received/compressed bytes,
or continuing to charge opaque content streams through the old oracle.

All unrelated parser statuses, limits, inputs and controls remain unchanged.
There is no conversion to skip/allowed failure and no protected E2E change.

## Findings and disposition

No blocking scope, traceability, expected-value independence, structural-fixture,
source-copy, RED-classification, sensitivity or determinism finding remains.

**APPROVED** for applying the exact patch as part of minimal HISTORY-001 GREEN
after the main history test receives its separate independent Gate 3. This
approval covers only the two stale old-oracle areas. It does not establish parser
GREEN, implementation Gate 5, broader format policy or combined original-command
readiness.
