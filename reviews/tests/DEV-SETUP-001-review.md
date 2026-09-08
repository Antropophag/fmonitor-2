# DEV-SETUP-001 — independent test review

Reviewer: root agent (implementation author, not test author). Test author:
`/root/setup_contracts`, gpt-5.6-sol / low. Date: 2026-09-08.

Verdict: APPROVED for the five setup acceptance examples. Reviewed test SHA-256:
`602bd7a81056afb7f8237d307eee522ee471eadbb66ef3bd51fde617be58d7b0`.

The tests call the public shell/Make seams inside a disposable filesystem with
trace-only external commands. Expected runtime/source pins come from the accepted
contract. Repeated setup fingerprints an existing user file/tree; the failed fresh
build asserts that execution actually reached the failing npm build and that the
final dependency directory remains absent. Incompatible Node proves preflight
runs before dependency mutations. Mismatched Git HEAD proves fail-without-repair.

Initial review requested fixes to fixture helper copying, TCPDF identity, Python
forwarding, npm mutation tracing and failure sensitivity. The author fixed those
without changing required production outcomes. Original intended missing-script
RED is preserved in DEV-SETUP-001-red.md; the fixture-complete run now passes 5/5.

Limits: these are isolated acceptance examples, not proof of actual downloads,
Docker or browser compatibility. Clean-checkout and full-suite evidence is required
separately. Implementation/configuration work overlapped fixture review; this is
not evidence of a strictly sequential pre-implementation Gate 3. Independent final
code review remains required; production deployment is outside this change.
