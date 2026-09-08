# Independent corrective Gate 3 — HTTP UUID v4 admission

- Verdict: **APPROVED**
- Reviewer: `/root/composition_http_review`, separately tasked agent; not the test or implementation author.
- Test author: `/root`.
- Date: 2026-09-07
- Reviewed production commit: `333e6633f825b469d932a69b4fb1d9c51a043081`; production unchanged during RED.
- Specification: `ASSIGNMENT-ORDER-COMPOSITION-HTTP-001`, section 3; SHA-256 `d20728bc3e27c02e5797f75b4b3afe1c06f41ad6b6fd1befc1a5be28a9796597`.
- Corrected finding: `reviews/code/ASSIGNMENT-ORDER-COMPOSITION-HTTP-001-v1.md`.
- Test: `tests/AssignmentOrderComposition/selection_http_admission_001_test.php`, SHA-256 `feed9bd0c6ee0e800e86bba9237aedd93084d02090691c1a4d38951f497e06ae`.

## Review

The sole test change sends the otherwise valid native HTTP selection body with canonical UUID v1 `11111111-1111-1111-8111-000000000301`. Expected 400 comes directly from the approved HTTP v4-only grammar. Existing per-case assertions also require unchanged domain rows, so a rejection response after mutation would fail. This uses the approved actual router/native-session/native-DB fixture; no private validator or domain policy is substituted.

The author ran the admission entrypoint with `/opt/homebrew/bin/php tests/AssignmentOrderComposition/selection_http_admission_001_test.php`. Supplied RED output records only `non-v4 request` failing: expected 400, actual 303; all remaining cases pass and the run exits 1. The successful 303 demonstrates the precise permissive transport bug rather than broken setup.

Evidence: `/Users/antropophag/.local/state/fmonitor2-verification/composition-http-20260906-2200/http-v4-red.log`, SHA-256 `f2b69eea2eebabdac498376e90f49735527cc4631aeb28d3e91338cf4c6fca36`.

## Decision

Corrective Gate 3 is **APPROVED** for this exact test. Proceed with minimal v4 validation in the HTTP adapter, retain the existing approved domain UUID grammar, rerun admission GREEN, and obtain Gate 5 rereview. Other test approvals remain unchanged.
