# Code rereview: PDF-NAVIGATION-UPLOAD-001 v2

- Reviewer: separately tasked agent `/root/pdf_review`
- Reviewed baseline: `2c46d6c56dda6320da05ac99055c5715366f9fda`
- Specification SHA256: `80a5a111305b55a7a2509caf193ee3a67ea18ad587054a87c57842152d34f85d`
- Approved test SHA256: `5ee07f774dc253e169b4bae56fa1112bec6ea4d716d1a0399b3aceea8e223310`
- Navigation implementation SHA256: `dc6d8119b606edb574f752fc552572d8263d2cf90f31184ffc8866de07bac04a`
- Lexical implementation SHA256: `6991feb8e50dc75dd51a7cb856e00ae043a45b0a0714489aee1e07936e371417`
- Verdict: `APPROVED`

## Rereview result

The implementation makes the narrow compatibility exception at the existing lexical
active-name policy point. `/OpenAction` is waived only when the following direct array
matches the specified page reference, exact destination name, operand grammar and
arity. Action dictionaries and indirect values remain unsafe. FitR correctly requires
four numeric coordinates; the other modes admit null only where the contract permits.

URI string handling accepts literal and hexadecimal PDF strings, decodes PDF escapes,
octal and line continuations once, then applies an anchored case-insensitive allowlist
for `http://`, `https://` and `mailto:` with a nonempty target containing no ASCII
control or whitespace byte. It does not percent-decode, so encoded script text cannot
be transformed into an admitted scheme. Indirect URI values and disallowed schemes
remain unsafe. `/S /URI` and a direct allowed `/URI` string are lexical exceptions as
specified; neither permits JavaScript, Launch, AA, attachments or the other retained
active names.

The helper is small and isolated from upload/history state. It changes no original
bytes, authorization, persistence, audit or composition behavior. The existing parser
continues to scan current and historical revisions and object streams, so the
exception applies consistently without bypassing the retained active-name checks.

## Verification evidence

Independently reproduced on the reviewed worktree:

```text
php tests/InstallationProcess/pdf_navigation_upload_001_test.php
PASS PDF-NAVIGATION-UPLOAD-001

php tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_PDF_BOUNDARY_OK

php tests/InstallationProcess/assignment_order_original_pdf_history_001_test.php
PASS PDF-HISTORY-001 (110 cases)

git diff --check
(no output)
```

The delivery agent additionally reported all six existing PDF parser suites green,
the generated-template inspector/renderer semantic test green, and
`make architecture-check` green with seven architecture rules plus HTTP
qualification. The independent focused runs above substantiate the changed boundary
and its largest all-history regression corpus; the broader reported checks are
supplemental evidence.

## Findings

No blocking specification-conformance, retained-active-content, integration,
history, authorization or maintainability finding remains for this narrow manual-pilot
correction.

## Required changes

None. This approval covers the reviewed hashes. It does not by itself claim full-suite,
CI, deployment or release readiness.
