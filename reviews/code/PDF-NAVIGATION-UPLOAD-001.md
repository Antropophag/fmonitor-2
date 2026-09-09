# Code review: PDF-NAVIGATION-UPLOAD-001

- Reviewer: separately tasked agent `/root/pdf_review`
- Reviewed baseline: `2c46d6c56dda6320da05ac99055c5715366f9fda`
- Production files reviewed: `app/AssignmentOrderOriginal/FMonitorPdfLexical.php`, `app/AssignmentOrderOriginal/FMonitorPdfNavigation.php`
- Specification SHA256: `ba81190390ba59a4d954cc142ec9687498597a4aabc7b2060433f75784776873`
- Reviewed navigation implementation SHA256: `a7c1766d3c05c2c9cb7bbcb8d61e1afbee9d11231b36ed39dec928e7619957c9`
- Reviewed lexical implementation SHA256: `f9a598632b01a6b47ebdc668b4b4ba98dba764afb61f94796e80241c0d160dbf`
- Test SHA256 at code review: `6cb33a92aa63d30417e23188a83d1ecd2c2abacffd4cb42b6f22f00fe9679a8f`
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **Blocking security boundary — URI exceptions are decided from token adjacency,
   without proving a complete URI action dictionary.** `FMonitorPdfLexical::view()`
   waives every active `/URI` name when the immediately preceding token value is `S`,
   and waives every `/URI` followed by an allowed-looking string, regardless of the
   containing object, dictionary key/value position or presence of the paired
   `/S /URI` entry. Thus fragments such as `<< /Foo /S /URI >>` and
   `<< /URI (https://example.org) >>` are admitted even though neither is the complete
   direct URI action described by the specification and reported fixture. This widens
   the former all-history active-name prohibition across every parsed dictionary and
   object stream. Parse and validate the containing action dictionary as a unit, and
   waive both active names only when the same direct dictionary has `/S /URI` plus a
   direct permitted `/URI` string and no structural ambiguity. Add negative controls
   for separated, missing, duplicate and out-of-context pairs.

2. **Blocking process state — Gate 3 is not approved.** The independent test review in
   `reviews/tests/PDF-NAVIGATION-UPLOAD-001.md` is `CHANGES_REQUESTED`. The revised test
   adds hex/escaped URI examples and all destination keywords, but its positive
   fixtures still couple URI and OpenAction, so the independent-behavior and RED
   sensitivity finding remains. The normative destination grammar and URI scheme/
   encoding rules also remain unstated. Under `docs/development-process.md`, Gate 5
   cannot approve production code against an unapproved expectation; revised Gate 1/2
   artifacts require independent rereview first.

3. **Maintainability — navigation policy is embedded as a callback from the generic
   lexer using hidden cursor context.** The lexer now requires the navigation policy,
   while `FMonitorPdfNavigation` calls back into the lexer and reconstructs grammar by
   scanning ahead from an integer cursor. This circular dependency makes lexical
   scanning responsible for semantic allowlisting and leaves the caller without an
   object/dictionary boundary. Move the exception to a parsed-value/object validation
   layer where the complete direct action or destination is available. That provides a
   single explicit place to enforce context, duplicate keys and directness, and keeps
   the byte lexer policy-neutral.

## Verification evidence

The following focused commands passed on the reviewed worktree:

```text
php tests/InstallationProcess/pdf_navigation_upload_001_test.php
PASS PDF-NAVIGATION-UPLOAD-001

php tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_PDF_BOUNDARY_OK

php tests/InstallationProcess/assignment_order_original_pdf_history_001_test.php
PASS PDF-HISTORY-001 (110 cases)

php -l app/AssignmentOrderOriginal/FMonitorPdfNavigation.php
No syntax errors detected

php -l app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php
No syntax errors detected

git diff --check
(no output)
```

These results show that the reported combined shape is now accepted and the existing
parser/history corpus remains green. They do not detect the context-free URI waiver,
and a green revised test does not retroactively satisfy the required independent Gate 3
approval.

## Required changes

- Validate a complete direct URI action in its containing dictionary; do not waive
  active names using only the preceding/following token.
- Add negative regressions for incomplete, separated, duplicate and out-of-context URI
  tokens, while preserving all-history and object-stream enforcement.
- Resolve the Gate 1/2 findings and obtain a new independent test approval before code
  rereview.
- After revision, rerun the focused navigation, parser and PDF-history suites and record
  their exact results.

No approval is given for integration or release under this record.
