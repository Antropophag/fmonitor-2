# Test review: PDF-NAVIGATION-UPLOAD-001

- Reviewer: separately tasked agent `/root/pdf_review`
- Reviewed baseline: `2c46d6c56dda6320da05ac99055c5715366f9fda`
- Specification: `specs/PDF-NAVIGATION-UPLOAD-001.md`, SHA256 `ba81190390ba59a4d954cc142ec9687498597a4aabc7b2060433f75784776873`
- Test: `tests/InstallationProcess/pdf_navigation_upload_001_test.php`, SHA256 `82e5c3b8b27d326da270ad398530cc2864d764c84c877a01f7a77837dd332a4c`
- Public seam: `FMonitorPassivePdfInspector::inspect(bytes)`
- Verdict: `CHANGES_REQUESTED`

## RED evidence

Command:

```text
php tests/InstallationProcess/pdf_navigation_upload_001_test.php
```

Observed result on the reviewed baseline: exit `255`. The first positive fixture
expected `PASSIVE_PDF` and received `UNSAFE_PDF`:

```text
own template navigation accepted (https://example.org)
Expected: AssignmentOrderOriginalPdfStatus::PASSIVE_PDF
Actual:   AssignmentOrderOriginalPdfStatus::UNSAFE_PDF
```

The fixture is constructed locally through `AssignmentOrderOriginalPdfCorpus::classic`
and reaches the real public inspector. No production system, private parser method or
implementation-derived expected value is involved. The failure is therefore a genuine
behavioral RED rather than broken setup. It reproduces the reported combination of a
direct URI action and `/OpenAction [3 0 R /FitH null]`, but does not identify which of
the two existing prohibitions caused the result.

## Findings

1. **Blocking — the two intended exceptions are coupled in every positive case.**
   Each accepted URI fixture also contains the newly accepted `FitH` OpenAction, and
   every accepted OpenAction fixture also contains a newly accepted URI. An
   implementation that fixes only URI remains RED because of OpenAction; one that
   fixes only OpenAction remains RED because of URI. Consequently the test does not
   independently prove the owner's two reported blockers, does not localize the RED,
   and cannot show that each narrow exception works on its own. Add a benign document
   with only the direct OpenAction destination and a separate benign document with only
   the URI annotation. Retain one combined fixture matching the reported PDF structure.

2. **Blocking — seven expressly accepted destination forms are untested.** The
   specification admits `Fit`, `FitH`, `FitV`, `FitB`, `FitBH`, `FitBV`, `XYZ`, and
   `FitR`, while the test exercises only `FitH null`. A minimal implementation could
   special-case `FitH` and pass without implementing the normative contract. Exercise
   every admitted destination keyword with a valid direct array and appropriate
   operands, plus a control for an unlisted destination/action name.

3. **Blocking Gate 1 ambiguity — the accepted destination grammar is not fixed.**
   The phrase “using Fit ... or FitR” does not state the required array shapes and
   operand types. It is unclear whether malformed forms such as `[3 0 R /FitR]`, extra
   operands, a non-page first operand, or a nested/indirect array are accepted,
   `INVALID_PDF`, or `UNSAFE_PDF`. Because this is an allowlist carved out of an
   active-content prohibition, implementation and independent expected values require
   exact accepted shapes and observable rejected statuses before Gate 3 approval.

4. **Blocking Gate 1/test coverage ambiguity — “direct URI strings” is underspecified.**
   PDF strings can be literal or hexadecimal, while the current examples cover only
   literal strings. Scheme matching also lacks rules for case, leading whitespace,
   escaping and delimiter recognition (for example `HTTPS:`, `javascript%3A...`, or
   `httpsx:`). State the accepted direct-string encodings and exact scheme parsing rule,
   then cover the permitted boundary and lookalike/obfuscated rejected controls. The
   existing `javascript:`, `file:` and indirect-value cases are useful but insufficient
   to pin this security boundary.

The rejected action controls for direct JavaScript, direct Launch and an indirect
OpenAction, and the rejected URI controls for `javascript`, `file` and an indirect URI
value, are traceable to the stated restrictions. The synthetic corpus is deterministic
and isolated. Those strengths do not compensate for the coupled positive oracle or the
uncovered and ambiguous allowlist surface.

## Required changes

- Separate the OpenAction and URI positive fixtures so each correction is proven
  independently, and retain a combined reported-shape regression.
- Specify and test all eight accepted destination array forms, including their exact
  operand grammar and rejected malformed/unlisted controls.
- Specify direct URI string encoding and scheme-normalization/delimiter semantics, then
  add boundary controls that would catch prefix, case or encoding mistakes.
- Re-run the focused test against the unchanged implementation and retain RED evidence
  showing failures for the independently missing behaviors.

Implementation must not proceed under this Gate 3 record. A revised specification and
test require a new independent review; changing expectations after approval would
restart Gate 2.
