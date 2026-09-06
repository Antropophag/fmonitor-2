# PDF-HISTORY opaque long-Length oracle conflict review

Date: 2026-09-06. Reviewer: separately tasked agent `/root/admission_oracle_gate3`.
Repository HEAD: `ac7c676c9dfde8eafd45f7891b83bda88cea7220` with uncommitted parser GREEN work present and left untouched.
Verdict: **the current `opaque-long-length` expectation is an invalid oracle**.

## Reviewed authority

```text
d7d21869b959dfb839230f084719ff3cc0bc7c6168acfdd5387ebbe710ff34e3  specs/ASSIGNMENT-ORDER-ORIGINAL-PDF-HISTORY-001.md
ae47d6300b0f2b049ba52b9057f4de8cd8a66633fbede8c45cee8ff6bd9a0683  docs/operations/original-pdf-history-gate1-review-v01-2026-09-06.md
22b27ffd9413c5ea97e6965693f79d37a5470efdaf67dd8245b550cdf1d4a9e4  tests/InstallationProcess/assignment_order_original_pdf_history_001_test.php
ea740062e623b4fa755b746adc3de04b4b24a81b2adb48be3ea4ea543ca617da  tests/Support/AssignmentOrderOriginalPdfHistoryCorpus.php
85a72d4c02200ad3ca7f132af37272aceee7cb5371a25db4df168b0bb4637ee2  tests/Support/AssignmentOrderOriginalPdfCorpus.php
```

The two existing Gate 3 records were read and remain immutable:

- `reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-PDF-HISTORY-001-v1.md` approves the main new matrix at the exact hashes above;
- `reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-PDF-HISTORY-ORACLE-001-v1.md` covers the separate old-parser oracle patch and is outside this finding.

No test, parser, specification or review record was changed by this audit.

## Concrete byte evidence

`AssignmentOrderOriginalPdfHistoryCorpus::stream()` emits:

```text
stream\n
<payload>\n
endstream
```

For `History::image('/ASCIIHexDecode', '000000>', delta)` the explicit payload is seven bytes. The independently inspected boundary is:

```text
delta=0  declared=7  bytes at boundary: 0a 65 6e 64 73 74 72 65 61 6d
                            boundary -> LF endstream

delta=1  declared=8  bytes at boundary: 65 6e 64 73 74 72 65 61 6d
                            boundary -> endstream

delta=2  declared=9  bytes at boundary: 6e 64 73 74 72 65 61 6d
                            boundary -> ndstream
```

At delta `+1`, the declared stream bytes are exactly `000000>\n`. The physical LF immediately before `endstream` is consumed as the final stream byte, and the next token begins exactly at `endstream`. Nothing crosses into the keyword and the surrounding object framing remains complete.

This is not merely permissive behavior introduced by the GREEN work. The pre-existing approved parser corpus deliberately constructs `contentStreamLength()` as:

```php
$payload = "q\nQ\n";
"stream\n{$payload}endstream"
```

Its exact `/Length` includes the payload's final LF and therefore also places the declared boundary immediately at the `e` of `endstream`. The existing test requires that reachable ordinary content stream to be `PASSIVE_PDF`. PDF-HISTORY v0.1 says opaque streams retain the **exact existing endstream/endobj framing**; it does not replace that framing with a rule requiring an additional uncounted newline.

Consequently, making delta `+1` invalid would require a new framing rule and would reject the already approved ordinary-stream control. Special-casing ASCIIHex, Image, content type or one fixture would contradict the common opaque framing contract.

## Minimal separately reviewable correction

Change only the new test input:

```diff
- History::image('/ASCIIHexDecode', '000000>', 1)
+ History::image('/ASCIIHexDecode', '000000>', 2)
```

Keep the expected status `INVALID_PDF` and the case name `opaque-long-length` (or rename it only if the test owner wants the byte crossing stated explicitly). Delta `+2` declares nine bytes, consuming the LF and the first `e` of `endstream`; parsing then sees `ndstream`, so the exact required keyword/framing cannot begin at the declared boundary. This is unambiguously invalid under both the old and new corpus rules.

The short-length `-1` case remains valid as written: its declared boundary occurs before the last payload byte, so the required framing cannot start there. No parser or content-type condition should change.

The test owner should prepare the one-literal patch, preserve fresh pre-patch mismatch evidence, run the unchanged old approved content-stream control plus the full history test, and obtain a separate independent Gate 3 approval before applying it to the reviewed test. This review identifies the conflict and proposed correction; it does not approve an unauthored future patch or the current parser GREEN.

## Scope

This finding does not assess the other 109 history cases, parser implementation quality, the separate old-parser oracle patch, parser Gate 5 or combined command readiness. It grants no skip, bypass or special-case permission.
