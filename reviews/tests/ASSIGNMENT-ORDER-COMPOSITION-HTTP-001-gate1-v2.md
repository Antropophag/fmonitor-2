# Independent Gate 1 amendment — ASSIGNMENT-ORDER-COMPOSITION-HTTP-001

- Verdict: **APPROVED**
- Reviewer: `/root/composition_http_review`, separately tasked agent; not an author of the specification, tests, or production code.
- Date: 2026-09-07
- Reviewed HEAD: `2948146225fbbb9a028396d4ff2edbe1f0d1daa3`
- Exact amended specification SHA-256: `d20728bc3e27c02e5797f75b4b3afe1c06f41ad6b6fd1befc1a5be28a9796597`
- Prior record: `reviews/tests/ASSIGNMENT-ORDER-COMPOSITION-HTTP-001-gate1-v1.md`.

## Amendment

The authorized object, selection, and candidate projection retains one consistent read snapshot. The last template date is subsequently obtained through the existing approved date reader's own transaction and is bound to the exact immutable latest order identity returned by the first snapshot. The contract explicitly makes no shared catalog/template-time snapshot claim.

This is a coherent boundary that reuses the approved date owner without rewriting its transaction ownership. Replacement after the first read does not change the identity to which the displayed date belongs. Read unavailability still fails closed; no read creates domain facts or audit. The previous review's date-scoping requirement remains applicable: a newly selected identity does not inherit its predecessor's generation date.

The amendment is **APPROVED** for Gate 1. All other findings and scope limits of v1 remain in force; this record does not approve Gate 3 or implementation.
