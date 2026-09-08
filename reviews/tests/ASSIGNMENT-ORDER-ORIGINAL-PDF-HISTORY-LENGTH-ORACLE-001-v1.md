# Test review: ASSIGNMENT-ORDER-ORIGINAL-PDF-HISTORY-LENGTH-ORACLE-001 v1

- Reviewer: separately tasked agent `/root/selection_v04_readiness`
- Patch author: `/root`; reviewer authored neither reviewed test nor production
- Reviewed commit containing unapplied patch: `ac7c676c9dfde8eafd45f7891b83bda88cea7220`
- Specification: `specs/ASSIGNMENT-ORDER-ORIGINAL-PDF-HISTORY-001.md`,
  SHA256 `d7d21869b959dfb839230f084719ff3cc0bc7c6168acfdd5387ebbe710ff34e3`
- Candidate patch SHA256:
  `580c6d016b517171a70124e91672375bb95843a7495e568581e67b831c62c505`
- Independent conflict review SHA256:
  `95ee183480ae22cb869007ba7d6b0f755f7efcaec10e047320e970f2ad258a92`
- Verdict: **APPROVED**

## Exact reviewed identities

```text
22b27ffd9413c5ea97e6965693f79d37a5470efdaf67dd8245b550cdf1d4a9e4  tests/InstallationProcess/assignment_order_original_pdf_history_001_test.php before patch
c675de79379a80bc1b19a2b1ece7c673228d2c4bef957f93d65be27539bf503c  tests/InstallationProcess/assignment_order_original_pdf_history_001_test.php after patch
ea740062e623b4fa755b746adc3de04b4b24a81b2adb48be3ea4ea543ca617da  tests/Support/AssignmentOrderOriginalPdfHistoryCorpus.php
```

The patch remains unapplied to the worktree test.

## Scope and traceability

The patch changes one numeric literal in one new HISTORY-001 test case:

```diff
- History::image('/ASCIIHexDecode', '000000>', 1)
+ History::image('/ASCIIHexDecode', '000000>', 2)
```

The case name `opaque-long-length` and expected `INVALID_PDF` remain unchanged.
No parser, helper, limit, status, skip, allowance or unrelated assertion changes.
The immutable main 110-case RED archive remains separate.

HISTORY-001 requires a direct nonnegative Length and the exact existing
`endstream/endobj` framing for opaque streams. The correction makes the fixture
actually cross that framing boundary.

## Independent byte oracle

`AssignmentOrderOriginalPdfHistoryCorpus::stream()` emits exactly:

```text
stream\n
<payload>\n
endstream
```

The explicit ASCIIHex payload `000000>` is seven bytes. Therefore:

| Delta | Declared bytes | First bytes at declared boundary | Resulting framing |
| ---: | --- | --- | --- |
| 0 | 7 | `0a 65 6e 64...` | optional separator LF, then `endstream` |
| 1 | 8 | `65 6e 64...` | exact `endstream` begins at boundary |
| 2 | 9 | `6e 64...` | first `e` consumed; only `ndstream` remains |

At `+1`, the stream legitimately consists of `000000>\n`; PDF Length counts
stream bytes, and the marker can begin immediately at `endstream`. Requiring an
additional uncounted newline would invent a new framing rule.

This is independently corroborated by the pre-existing approved
`contentStreamLength()` control. Its payload already ends in LF, its declared
Length includes that LF, and the existing test requires PASSIVE when `endstream`
begins immediately at the boundary. Special-casing ASCIIHex, Image or this
fixture would contradict the common framing contract.

At `+2`, declared data consumes the first byte of the required marker. Parsing at
the declared boundary sees `ndstream`; exact framing is impossible. Expected
`INVALID_PDF` is therefore fixed by grammar, not by current production output.

The short-length `-1` case remains unchanged and valid: its declared boundary
occurs before the final payload byte, so the framing marker cannot start there.

## Oracle independence and sensitivity

The expectation remains INVALID; the patch does not weaken a rejection to PASS,
skip the case or admit an unsupported filter. It changes only the malformed input
so that it unambiguously violates the already approved grammar.

The corrected case is intentionally a framing control, not missing-production
RED: both old and work-in-progress parsers may reject `+2`. Its sensitivity is to
future acceptance of an overlong declared stream that consumes marker bytes.
The main HISTORY-001 matrix supplies separate behavioral RED for the new parser
requirements.

The conflict review derives the boundary from literal helper bytes and preserves
the original test/helper hashes. No expected value is captured from a production
run. The reported work-in-progress result—109/110 cases passing with only the
invalid `+1` oracle failing—supports conflict detection but is not used to derive
the corrected expected status.

## Findings and disposition

No blocking grammar, traceability, scope, expected-value independence,
sensitivity or preservation finding remains.

**APPROVED** for applying the exact one-byte patch before treating the main
HISTORY-001 test as the reviewed oracle for GREEN. This approval does not approve
the parser implementation, modify the immutable RED archive, establish Gate 5,
or broaden accepted PDF formats or limits.
