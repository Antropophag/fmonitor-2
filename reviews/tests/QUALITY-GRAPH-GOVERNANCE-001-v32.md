```delivery-metadata
{"schemaVersion":1,"kind":"test-review","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","reviewer":"agent:/root/qg_allowlist_gate3","verdict":"CHANGES_REQUESTED","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"5b1ca1d8f6c95e3483894f32d9b917314052e89b465e374c786ce7d2bc05420b"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"391fe3e2aa3d162b978b21643c43eb92cb701319f1d69cca803d6f1ca6271600"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863"}],"redCommit":"7126418dbeef81a1928ac58fb21b7cf55650e9b3","recordedAt":"2026-09-04T00:57:25+03:00"}
```

# Test review: QUALITY-GRAPH-GOVERNANCE-001 v0.6 — exact post-review allowlist RED v32

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/qg_allowlist_gate3`
- Independence: reviewer did not author the specification, test, RED evidence, or implementation
- Reviewed RED commit: `7126418dbeef81a1928ac58fb21b7cf55650e9b3`
- Reviewed evidence: `docs/operations/quality-graph-governance-red-evidence-v31.md`
- Gate 5 source finding: `reviews/code/QUALITY-GRAPH-GOVERNANCE-001-v2.md`, blocking finding 1
- Verdict: **CHANGES_REQUESTED**

## Blocking finding

The new scenario is traceable to the Gate 5 finding and correctly demonstrates
that the current directory-wide `docs/operations/**` exception accepts an
unrelated post-review file. It is not sufficient to authorize the requested
exact allowlist, because it exercises only one forbidden path.

The executable specification also requires post-review parity and
final-verification evidence to remain allowed. A production change that removes
the `docs/operations/**` exception entirely would make this new test GREEN while
rejecting every permitted parity/final-verification record. Therefore the test
does not distinguish a conforming exact allowlist from a plausible
over-restrictive regression.

Add at least one post-review positive fixture for every exact permitted
parity/final-verification evidence path or path class chosen by the technical
contract, while retaining the current negative unrelated-path scenario. The
positive expectations must be fixed independently of checker output. If the
exact paths/classes are not already unambiguous in an approved executable
contract, return to Gate 1 before revising the RED.

## Seam, determinism, and expected independence

The scenario otherwise uses the correct public test-only seam,
`tools/delivery/check-evidence.php --repo <isolated-git-fixture>`, against real
committed Git history. It asserts nonzero status, one exact `commit_mismatch`,
one total failure marker, and no success marker. The category and path are
derived from the specification rather than copied from actual checker output.
The fixture resets to the supersession head afterward and remains isolated from
the production checkout.

## Reproduced RED

At exact commit `7126418dbeef81a1928ac58fb21b7cf55650e9b3`, two consecutive runs
failed at the intended new assertion. Representative output:

```text
php -l tests/Verification/quality_graph_governance_001_test.php
No syntax errors detected in tests/Verification/quality_graph_governance_001_test.php

php tests/Verification/quality_graph_governance_001_test.php
PHP Fatal error: Uncaught TestFailure: Unallowlisted post-review operations evidence must fail;
evidence={"status":0,"stdout":"DELIVERY_EVIDENCE_OK receipts=1 head=<fixture-sha>\n","stderr":""}
Expected: true
Actual: false
exit=255
```

The varying fixture commit SHA is expected Git fixture data; status, terminal
success shape, assertion and failure reason were stable. Setup reached the
intended scenario and the current checker's production allowlist was exercised.

## Reviewed hashes and inventory

```text
189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859  specs/QUALITY-GRAPH-GOVERNANCE-001.md
5b1ca1d8f6c95e3483894f32d9b917314052e89b465e374c786ce7d2bc05420b  tests/Verification/quality_graph_governance_001_test.php
391fe3e2aa3d162b978b21643c43eb92cb701319f1d69cca803d6f1ca6271600  tests/Verification/quality_graph_publisher_001_test.php
ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863  tests/Verification/quality_graph_toolchain_001_test.php
```

The three test entries are the complete bytewise-sorted
`9c87164393b2048428fc0987c357e65e0e9fc146..RED` inventory under `tests/`, all
with status `A`. RED evidence v31 matches the specification hash, test hashes,
base commit and observed failure.

Gate 4 is not authorized. A revised RED requires a fresh independent Gate 3
review and a new append-only record.
