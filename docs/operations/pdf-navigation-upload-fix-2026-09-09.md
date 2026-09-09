# Own-template PDF upload correction — 2026-09-09

Owner reported a manual-work blocker: uploading the system-generated assignment
order template returned unsafe_pdf. The private Downloads source (100971 bytes)
reproduced the rejection. Object 6 contains an HTTP URI link; object 26 contains
OpenAction [7 0 R /FitH null]. Neutralizing either marker alone left unsafe_pdf;
neutralizing both in memory produced passive_pdf. No source bytes were rewritten.

Branch codex/pdf-navigation-upload-fix starts at main 2c46d6c5. The stale original
checkout and stand volumes were not modified. The inspector now admits narrowly
specified link strings and explicit page destinations; scripts, launch, indirect
opening actions, attachments, encryption and historical active objects stay blocked.

Evidence:
- Initial synthetic regression RED: expected PASSIVE_PDF, actual UNSAFE_PDF.
- Original baseline isolated controls: OpenAction-only and URI-only each unsafe_pdf.
- Updated regression: PASS PDF-NAVIGATION-UPLOAD-001.
- Unmodified private source with corrected inspector: passive_pdf.
- Six existing assignment_order_original_pdf* suites PASS, including 110 history cases.
- Generated template upload profile and production renderer semantic tests PASS.
- make architecture-check PASS (7 rules), including HTTP qualification.

This is a local manual-pilot correction. Full CI/production integration and an
update on the user's work machine are separate delivery steps, not claimed by
these focused checks. Private evidence is not committed. Review records are under
reviews/tests and reviews/code with the same specification identifier.

## CI inventory correction

PR69 source107a3472, Actions34340489068: plan, fast, unit, both integration shards
and e2e SUCCESS. Complete failures: governance -> sole REGRESSION_FAILURE
`tests/Verification/verification_inventory_001_test.py`; verify -> aggregate
missing governance success. The new test was registered in suites/categories but
omitted from the inventory test's explicit post-baseline additions.

Locally reproduced unit baseline drift (expected ae1c98c..., actual d3fcb048...).
Add only the new PDF test to `added_by_suite['unit']`: original baseline hashes and
all existing membership checks remain unchanged; the new test must occur once.
No PDF implementation or acceptance expectations change in this CI correction.
