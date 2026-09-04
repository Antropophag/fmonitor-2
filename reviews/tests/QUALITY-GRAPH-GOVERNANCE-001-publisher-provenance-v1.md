```delivery-metadata
{"schemaVersion":1,"kind":"test-review","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","reviewer":"agent:/root/qg_publisher_provenance_gate3","verdict":"CHANGES_REQUESTED","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","tests":[{"path":"tests/Verification/quality_graph_publisher_provenance_001_test.py","status":"A","sha256":"2c2bec078d430f5ef22a79c020c67899c3ad35eb9840375fe2ddcb684031ae93"}],"redCommit":"3f417cc0b59d97853965ae42a0ec686fc4a19d4c","recordedAt":"2026-09-04T06:02:54+03:00"}
```

# Test review: QUALITY-GRAPH-GOVERNANCE-001 — publisher provenance RED v1

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/qg_publisher_provenance_gate3`
- Independence: reviewer did not author the specification, test, RED evidence, or implementation
- Reviewed RED commit: `3f417cc0b59d97853965ae42a0ec686fc4a19d4c`
- Reviewed evidence: `docs/operations/quality-graph-governance-red-evidence-v34.md`
- Specification: `specs/QUALITY-GRAPH-GOVERNANCE-001.md` v0.6
- Gate 5 source finding: `reviews/code/QUALITY-GRAPH-GOVERNANCE-001-v2.md`, blocking finding 4
- Verdict: **CHANGES_REQUESTED**

## Blocking findings

1. **The trusted workflow run attempt is not an input to the proposed public seam, so the test cannot prove exact-attempt binding.** `validate(...)` calls `validate_result_artifacts(...)` without a `run_attempt`. Its “wrong run attempt” case changes only Result JSON from `3` to `4`, while the artifact name remains `quality-result-<node>-3`. An implementation can satisfy that case by comparing Result provenance to the untrusted artifact-name attempt, exactly as upstream `download_results(...)` does, yet accept a coherently replayed artifact whose name and Result both say attempt `4` when the trusted expected attempt is `3`. This violates required behavior 5 and the acceptance example requiring rejection of another run attempt.

   Return to Gate 2 by adding the independently fixed expected run attempt to the seam call and descriptor construction. Add a coherent replay contrast in which both the artifact name and Result provenance carry the same wrong attempt; it must still raise `ArtifactError`. Keep the existing payload-only mismatch contrast.

2. **The new executable test is absent from every repository verification entrypoint.** `tools/verification/run.sh` runs the three earlier Quality Graph tests but never `quality_graph_publisher_provenance_001_test.py`; neither `make verify` nor a focused Make target therefore executes this contract. The entire new test and the production seam may remain missing while repository verification reports the same result as before. That is not executable representative-PR publisher coverage under the approved spec.

   Return to Gate 2 with repository-owned invocation from the applicable verification stage (or an explicit Make target that the graph/verification stage invokes), and test that invocation rather than relying only on an ad hoc direct command.

3. **The fixture depends on an ignored, interpreter-minor-specific local directory rather than the declared Python environment.** It prepends the literal `.venv/lib/python3.14/site-packages`; `.venv` is ignored, `pyproject.toml` supports every Python `>=3.12`, and the RED evidence command uses ambient `python3`. A clean checkout, a Python 3.12/3.13 environment, or an activated non-`.venv` environment can fail imports before observing the repository seam. The current RED is reproducible only because this worktree happens to contain the ignored Python 3.14 environment.

   Make dependency loading use the invoked interpreter/environment and ensure the repository-owned command installs or checks the exact pinned `quality-graph-github==0.1.7` dependency. A missing dependency must be classified as setup failure, not as the intended missing-seam RED.

## Reviewed strengths

Subject to those blockers, the fixture is compact and constructible against the pinned v0.1.7 APIs. `MemoryGitHubPort` gives exact queued GET/download behavior; fixed ZIP entry name, timestamp and content make archive bytes deterministic; descriptor SHA-256 is independently computed. The valid pair and isolated payload-only repository, PR, head, workflow-run, attempt and graph-digest mutations are well formed. Omitted, unexpected, duplicate same-attempt, expired and digest-drift cases each use a fresh in-memory port and require `ArtifactError`. A thin repository wrapper around upstream `ArtifactExpectation`/`download_results` can add exact trusted-attempt, completeness and duplicate rejection without network access.

The duplicate expectation is intentionally stricter than upstream v0.1.7: upstream replaces an already selected node on `descriptor.attempt >= current_attempt`, so the repository wrapper must explicitly reject a second descriptor for the same node/attempt. The omitted-node expectation likewise requires an explicit exact-key-set check after upstream download.

## Reproduced RED and traceability

At exact reviewed head:

```text
$ sha256sum specs/QUALITY-GRAPH-GOVERNANCE-001.md tests/Verification/quality_graph_publisher_provenance_001_test.py docs/operations/quality-graph-governance-red-evidence-v34.md
189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859  specs/QUALITY-GRAPH-GOVERNANCE-001.md
2c2bec078d430f5ef22a79c020c67899c3ad35eb9840375fe2ddcb684031ae93  tests/Verification/quality_graph_publisher_provenance_001_test.py
77ad518a60bcfc8631fc0092d186efe99b28988205b85a30e3e0c9d9c4faae33  docs/operations/quality-graph-governance-red-evidence-v34.md

$ git diff --no-renames --name-status e97218eac87e079a16dc7e3c090a83080e31bb98..3f417cc0b59d97853965ae42a0ec686fc4a19d4c -- tests/
A tests/Verification/quality_graph_publisher_provenance_001_test.py

$ python3 tests/Verification/quality_graph_publisher_provenance_001_test.py
Traceback (most recent call last):
  ...
AssertionError: RED_ASSERTION: repository-owned offline publisher validation seam is absent
exit=1
```

The specification, test and base hashes in evidence v34 agree with Git, and the only test delta is the declared added file. The observed failure is the intended missing-seam RED in this populated worktree, not a GREEN result. Because the clean-environment and exact-attempt oracle gaps remain, Gate 4 is not authorized.
