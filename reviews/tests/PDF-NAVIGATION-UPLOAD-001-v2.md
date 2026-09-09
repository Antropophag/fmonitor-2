# Test rereview: PDF-NAVIGATION-UPLOAD-001 v2

- Reviewer: separately tasked agent `/root/pdf_review`
- Reviewed baseline: `2c46d6c56dda6320da05ac99055c5715366f9fda`
- Specification SHA256: `80a5a111305b55a7a2509caf193ee3a67ea18ad587054a87c57842152d34f85d`
- Test SHA256: `5ee07f774dc253e169b4bae56fa1112bec6ea4d716d1a0399b3aceea8e223310`
- Public seam: `FMonitorPassivePdfInspector::inspect(bytes)`
- Verdict: `APPROVED`

## Rereview result

The revised normative contract fixes the exact direct-destination grammar, operand
counts and types, URI string encodings, one-pass decoding, case handling, scheme
delimiter and whitespace/control-byte boundary. It retains the established lexical
all-history policy and explicitly leaves destination-page semantic validation to the
existing graph validation.

The revised test resolves the blocking sensitivity findings from the first review.
An OpenAction-only fixture and a URI-only fixture prove the two reported corrections
independently, while the combined fixtures preserve the reported generated-document
shape. All eight admitted destination modes are exercised with valid arities. Rejected
controls cover action dictionaries, indirect OpenAction, unknown/nested/malformed
destinations, invalid FitR null operands, extra Fit operands, indirect URI values,
leading whitespace, prefix lookalikes, percent-encoded script text and escaped/hex
forbidden schemes. The retained `/AA` plus JavaScript case proves that a navigation
exception does not waive another active-content marker.

Expected statuses are literal contract values. Fixtures are deterministic synthetic
PDFs built by the existing independent corpus helper and invoke the real public
inspector; they do not depend on production implementation output, private methods,
external viewers, files, databases or services.

## RED evidence reviewed

Against temporary copies of the unchanged baseline parser classes at
`2c46d6c56dda6320da05ac99055c5715366f9fda`, the author ran the revised public-seam
fixtures through `AssignmentOrderOriginalPdfCorpus::classic`. Both independently
missing behaviors were observed:

```text
OpenAction only: expected passive_pdf, actual unsafe_pdf
URI only:        expected passive_pdf, actual unsafe_pdf
```

The temporary harness was removed. This is the intended RED: setup constructed a
valid PDF and the baseline inspector returned its existing active-content status for
each independently missing exception.

Independent rereview on the implemented worktree also confirmed the focused test is
green:

```text
php tests/InstallationProcess/pdf_navigation_upload_001_test.php
PASS PDF-NAVIGATION-UPLOAD-001
```

## Required changes

None. Minimal implementation may proceed against these exact expectations. Any
expectation change restarts Gate 2 and requires a new independent review.

