# QUALITY-GRAPH-CURRENT-CI-001 — intended RED evidence

- Date: `2026-09-09T14:31:52+03:00`
- Test author: separately tasked agent `/root/qg_report_tests`
- Public seam: `python3 tools/delivery/quality-graph-report.py`
- Baseline HEAD: `7d43c2e7a0d2843b5b817c840cdb70f66d1b36fb`
- Gate state: RED captured; independent Gate 3 review remains required

## Scope

The new stdlib `unittest` exercises the repository-owned report command through
its public CLI. Its isolated fixture creates a temporary GitHub workspace, a
readable `pull_request` event, and the complete required GitHub environment. The
event deliberately distinguishes the PR head from `GITHUB_SHA`, so a synthetic
merge SHA cannot satisfy the expected provenance.

Expected Result v0 objects are independent literals in the test. They require
the exact seven node identities and titles, exact statuses, failure kinds,
provenance, key sets and Python JSON value types, plus empty native arrays. The
matrix covers full success, a permitted full-mode dependency skip with failed
verify, docs-only skips, command failure, cancellation and a failed plan with
dependency skips. Docs-only negative cases independently reject a non-skipped
successful category and a mixed skipped/failed category. Other negative cases cover malformed, ambiguous,
incomplete, extra and wrongly typed outcomes; invalid state combinations;
missing, malformed and contradictory GitHub provenance; absolute and parent path
escape; an output symlink; byte-identical replay; conflicting replay; and an
incomplete prior output set. Rejected inputs must not create or overwrite a
successful report set.

## RED reproduction

```text
$ python3 -m py_compile tests/Verification/quality_graph_current_report_001_test.py
# exit 0, no output

$ python3 -m unittest tests.Verification.quality_graph_current_report_001_test
E
======================================================================
ERROR: setUpClass (tests.Verification.quality_graph_current_report_001_test.QualityGraphCurrentReport)
----------------------------------------------------------------------
Traceback (most recent call last):
  File "tests/Verification/quality_graph_current_report_001_test.py", line 69, in setUpClass
    raise AssertionError(
AssertionError: INTENDED_RED: missing public command tools/delivery/quality-graph-report.py

----------------------------------------------------------------------
Ran 0 tests in 0.001s

FAILED (errors=1)
# exit 1
```

The fixture is parsed and checked before the command-presence assertion. The
failure is therefore the intended missing public seam, not malformed event data,
an absent workspace or another setup fault. No production command was created.

The discovery form reaches the same single intended failure:

```text
$ python3 -m unittest discover -s tests/Verification -p 'quality_graph_current_report_001_test.py'
AssertionError: INTENDED_RED: missing public command tools/delivery/quality-graph-report.py
Ran 0 tests in 0.001s
FAILED (errors=1)
```

Supporting checks:

```text
$ openspec validate integrate-current-quality-graph --strict
Change 'integrate-current-quality-graph' is valid

$ git diff --check
# exit 0, no output
```

## Exact reviewed-input identities

```text
e140f33bfd844891d099b59f84ec70c58dd0b68bd90a835d8420464951d56b92  specs/QUALITY-GRAPH-CURRENT-CI-001.md
c2965dfa50573065d48f4545569eadd42bce70bd208749cba237cc60fa0b91ef  openspec/changes/integrate-current-quality-graph/proposal.md
7e5d8b60231809e80ca2f34d72c284eee68758b566681a4d95a01032dc73ec42  openspec/changes/integrate-current-quality-graph/design.md
3cf35738dcf020b26f320eb42e8f0ba7552c9320ded8d3798a6844f4c5323283  openspec/changes/integrate-current-quality-graph/tasks.md
3e215d751a95d4f2f03a418c7ea6d1f1ed843f9fe3dd9e5020d30a5b51924f0b  openspec/changes/integrate-current-quality-graph/specs/delivery/current-ci-quality-graph/spec.md
b5fec5960c45833f3c474a98ac78e94d2d320f84be46b71e719624b71f2f1edd  tests/Verification/quality_graph_current_report_001_test.py
```

This is an author RED record, not an independent test-review verdict. Gate 3 is
pending.
