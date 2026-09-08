# Test review: ASSIGNMENT-ORDER-ORIGINAL-PDF-HISTORY-001 v1

- Reviewer: separately tasked agent `/root/admission_oracle_gate3`
- Test author: `/root`; reviewed commit authored by Timofey Grishin
- Reviewed commit: `ac7c676c9dfde8eafd45f7891b83bda88cea7220`
- Specification: `specs/ASSIGNMENT-ORDER-ORIGINAL-PDF-HISTORY-001.md` v0.1, SHA256 `d7d21869b959dfb839230f084719ff3cc0bc7c6168acfdd5387ebbe710ff34e3`
- Parent: `specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md` v65, SHA256 `bdd57ea8b79e7414b7724da953b838b1d7c2d76e4e4df8170fa8664cd492e00d`
- Public seam: `FMonitorPassivePdfInspector::inspect(string): AssignmentOrderOriginalPdfInspection`
- Red command and intended failure: `php -d memory_limit=256M tests/InstallationProcess/assignment_order_original_pdf_history_001_test.php`; 110 cases, 62 intended failures and 48 controls, aggregate exit `255`
- Verdict: `APPROVED`

## Exact reviewed inputs

```text
d7d21869b959dfb839230f084719ff3cc0bc7c6168acfdd5387ebbe710ff34e3  specs/ASSIGNMENT-ORDER-ORIGINAL-PDF-HISTORY-001.md
ae47d6300b0f2b049ba52b9057f4de8cd8a66633fbede8c45cee8ff6bd9a0683  docs/operations/original-pdf-history-gate1-review-v01-2026-09-06.md
bdd57ea8b79e7414b7724da953b838b1d7c2d76e4e4df8170fa8664cd492e00d  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
22b27ffd9413c5ea97e6965693f79d37a5470efdaf67dd8245b550cdf1d4a9e4  tests/InstallationProcess/assignment_order_original_pdf_history_001_test.php
ea740062e623b4fa755b746adc3de04b4b24a81b2adb48be3ea4ea543ca617da  tests/Support/AssignmentOrderOriginalPdfHistoryCorpus.php
85a72d4c02200ad3ca7f132af37272aceee7cb5371a25db4df168b0bb4637ee2  tests/Support/AssignmentOrderOriginalPdfCorpus.php
cf00844d934c5ce31b3e6c9fee498e926eb6c84458c02fd48f8c7a8b2c5a21c3  docs/operations/original-pdf-history-red-v1-2026-09-06.md
5526e8ecfdb41d66946cad32234cc24e5560e333954c316951b9698d04a810c1  /Users/antropophag/.local/state/fmonitor2-verification/original-pdf-history-red-w81_smc8/evidence.json
bbbd289f827d28f7736dff8140f1072d55306fca083443426fb0746a6df8ec19  /Users/antropophag/.local/state/fmonitor2-verification/original-pdf-history-red-w81_smc8/red.log
1a59ecc5ec45470ff76a6c043e29c67bdb89b641b298851540a050b46d6fe394  app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php
```

The exact v0.1 specification and parent linkage have independent Gate 1 approval. Parser production bytes are unchanged. The separately assigned old-parser expectation patch remains unapplied and is outside this verdict.

## Findings

Traceability and seam choice are complete. The test invokes one real public inspector instance and compares only the returned typed status with independently fixed literal outcomes. It also pins the public algorithm constant visibility/value and equality with `algorithmId()`. No private parser method, external viewer, codec, file, database, production document or callback supplies an expected value.

Baseline controls retain classic xref, xref stream, raw/Flate object stream and safe incremental Prev acceptance. These prevent an implementation from satisfying the history corrections by rejecting incremental, compressed or object-stream PDFs wholesale.

All-history security sensitivity is complete. Every one of the 15 exact forbidden Names appears in an xref-listed unreachable dictionary and must be unsafe, while an equivalent benign dictionary stays passive. An older active Catalog overwritten by a safe current object remains unsafe, but a benign old/current pair remains passive and preserves newest-entry graph precedence. Trailer, indirect Name value, raw/Flate unreferenced object-stream member, overwritten old object-stream body and historical type-2 container/index identity are separately exercised.

The historical type-2 fixtures preserve physical-container identity. The newer revision overwrites the container object number with a different member and adds a current direct object, so resolving the old compressed entry through the newest same-number container cannot pass. The benign own-container control and bad historical member index distinguish correct physical-offset history from reject-all behavior.

Lexical tests fix PDF Name tokenization rather than regex matching. Exact `/JS` and encoded `/J#53` are unsafe. Literal/nested/escaped strings, comments, even/odd hex strings, lower case, longer hyphen/dot/underscore/digit Names and encoded hyphen/space/slash/hash forms remain passive. Malformed/truncated/NUL name escapes, unclosed/dangling literal strings and malformed/unclosed hex strings are invalid. The same lexer is tested against quoted/commented fake `/Length` and `/Type`, structural-name prefixes, escaped structural `/Type /ObjStm`, and an escaped duplicate Filter key.

Opaque stream coverage preserves dictionary/framing validation without decoding payloads. ASCIIHex image data and a pinned 692-byte GD-generated JPEG are passive; the JPEG SHA256 and 1×1 RGB SOF marker are asserted directly, so runtime GD/image codec availability is irrelevant. Forbidden-looking `/JS` bytes inside an opaque image remain data. Opaque Flate and every allowed filter name are admitted: `ASCIIHexDecode`, `ASCII85Decode`, `LZWDecode`, `FlateDecode`, `RunLengthDecode`, `CCITTFaxDecode`, `JBIG2Decode`, `DCTDecode`, and `JPXDecode`. Single/array/multiple/escaped Names are distinguished from unknown, empty/non-Name array, indirect, duplicate and Crypt filters. These are filter-name admissions only and make no codec-validity claim.

Structural stream controls retain the narrower filter contract: LZW, array and indirect filters are invalid. Short/long opaque Length cases retain exact framing enforcement. Marker bytes and large opaque Flate expansions do not participate in structural scanning or decoded-byte accounting.

Bounds are constructible and independently fixed. A terminating 64-section Prev chain is passive and a continued 65th section is invalid. Structural aggregate fixtures build valid compressed object streams whose decoded member payloads total exactly `67,108,864` and `67,108,865` bytes before compression; both received files remain under 200,000 bytes. The inclusive boundary and one-byte excess are asserted, while a referenced cached structural container at the exact limit proves one physical payload is charged once rather than once per history/current consumer. A 33,554,433-byte opaque expansion remains passive and outside that budget.

Corpus helpers calculate their own object offsets, xref entries, direct Length values, structural membership and compressed bytes. Expected statuses remain literals in the test. Generated fixture metadata is not read from the production parser and no malformed-plus-active overlap is used where the exact INVALID versus UNSAFE category matters.

Independent reproduction under the required memory limit produced:

```text
passes=48 failures=62 cases=110
exit=255
```

This matches the archived evidence and raw-log hash. Both PHP files lint and `git diff --check` passes. The 48 controls demonstrate functional setup and prevent failure-only acceptance; the 62 REDs correspond to missing all-history/name/opaque-stream behavior rather than environment failure.

No blocking traceability, expected-value independence, fixture construction, status classification, sensitivity, determinism, resource-bound or public-seam finding remains. Minimal parser GREEN may proceed against these exact expectations.

## Required changes

None.

This approval does not cover or apply the separate old-parser oracle patch and does not establish parser Gate 5, combined command approval or release readiness.
