```delivery-metadata
{"schemaVersion":1,"kind":"test-review","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","reviewer":"agent:/root/qg_runner_security_gate3","verdict":"APPROVED","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","tests":[{"path":"tests/Verification/quality_graph_runner_security_001_test.php","status":"A","sha256":"a99ca7f53c811bb9be8a5abf761805569eec150d3a96e3f376f2c5f5b2261af9"}],"redCommit":"7e81612afb437cdaca2720d1fa4bf600b92076c4","recordedAt":"2026-09-04T06:14:56+03:00"}
```

# Test review: QUALITY-GRAPH-GOVERNANCE-001 v0.6 — untrusted runner security RED v35

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/qg_runner_security_gate3`
- Independence: reviewer did not author the specification, test, RED evidence, or implementation
- Reviewed RED commit: `7e81612afb437cdaca2720d1fa4bf600b92076c4`
- Reviewed evidence: `docs/operations/quality-graph-governance-red-evidence-v36.md`
- Verdict: `APPROVED`

## Findings

No blocking findings for this runner-security tracer slice.

The test exercises the repository-owned public validation seam
`check-quality-graph.php --repo <fixture>` for both deployed workflows that
execute repository code. For each workflow, four independent mutations cover
the approved fail-closed runner boundary: one pinned third-party `uses:` ref is
changed from a 40-lowercase-hex commit to a floating branch, checkout credential
persistence is enabled, top-level `contents` is changed from read to write, and
a job-level write permission is added. Every mutation first has to change the
fixture bytes and then must produce exactly one stable `runner_security`
diagnostic, nonzero exit, and no success marker.

The oracle is generic. It neither names a setup action nor requires a particular
action commit, installer, package-bootstrap command, or textual occurrence
count. A conforming implementation may parse or otherwise inspect the runners;
the observable contract is only full-SHA third-party action references,
non-persisted checkout credentials, read-only untrusted permissions and the
stable security classification before generated parity. This avoids the
unapproved implementation constraints rejected in reviews v22 and v23.

The final independent mutation appends generated-runner drift and retains its
existing exact `generated_drift` category. It prevents a GREEN implementation
from relabelling all generated parity failures as security failures. Each case
starts from canonical repository files in a fresh repository-local random
fixture, invokes no production or network service, and is removed after each
case and in `finally`.

## Reproduced RED

At exact commit `7e81612afb437cdaca2720d1fa4bf600b92076c4`:

```text
php -l tests/Verification/quality_graph_runner_security_001_test.php
No syntax errors detected in tests/Verification/quality_graph_runner_security_001_test.php
exit=0

php tests/Verification/quality_graph_runner_security_001_test.php
QUALITY_GRAPH_VALIDATION_FAILURE category=generated_drift detail=stale generated file: .github/workflows/quality-graph.yml
TestFailure: RED_ASSERTION: .github/workflows/quality-graph.yml floating third-party action must be classified before generated parity
Expected: 1
Actual: 0
exit=255
```

The exact test commit was also cherry-picked without conflict onto detached
historical commit `c2b653efdcb3cbd78491a9dc8d4dd1be33f7d627` in a home-directory
worktree. `uv sync --frozen` installed the lockfile-defined Quality Graph 0.1.7
toolchain, and the focused test reproduced the same intended
`generated_drift` versus `runner_security` assertion with exit `255`. This
confirms the RED is not a missing-toolchain setup failure and is present both
before and after the later runner pin work.

## Traceability, hashes, and inventory

```text
189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859  specs/QUALITY-GRAPH-GOVERNANCE-001.md
a99ca7f53c811bb9be8a5abf761805569eec150d3a96e3f376f2c5f5b2261af9  tests/Verification/quality_graph_runner_security_001_test.php
410df9ea406d936585a9dbc0dc5ae1a81b31e48fa72499299d0eba6000d3ff3d  docs/operations/quality-graph-governance-red-evidence-v36.md
```

`git diff --no-renames --name-status
533ebca7225e5691bb049ca3ff39037f64a93a76..7e81612afb437cdaca2720d1fa4bf600b92076c4
-- tests/` contains exactly the single `A` entry recorded in evidence and this
review. The approved specification hash, exact test hash, base commit, observed
failure and Git ancestry agree with the append-only RED record.

## Authorized minimal GREEN

This approval authorizes only early runner validation for the two exercised
deployed workflows: reject a non-full-SHA third-party action reference,
persisted checkout credentials, top-level write permission, or job-level write
permission with exactly one `runner_security` failure, while preserving the
existing `generated_drift` parity classification. It does not authorize any
specific setup action SHA, pip/uv command, runner generation change, publisher
permission change, package pin change, product behavior, or broader workflow
policy.

Gate 3 is approved only for the exact specification and test blob recorded
above. Any expectation, fixture, test inventory, or specification change
restarts Gate 2.
