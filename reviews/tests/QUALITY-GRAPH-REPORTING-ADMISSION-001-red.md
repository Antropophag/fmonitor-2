# QUALITY-GRAPH-REPORTING-ADMISSION-001 — supplemental intended RED

- Date: `2026-09-09T14:49:47+03:00`
- Test author: separately tasked agent `/root/qg_report_tests`
- Public seam: `python3 tools/delivery/quality-graph-preflight.py`
- Baseline HEAD: `9185c5f0da2d7f4b72f66f974b863eb033496bee`
- Gate state: supplemental RED captured; independent review remains required

## Scope and sensitivity

The existing localhost HTTP fixture now serves the GitHub Actions jobs endpoint
as well as run and artifact endpoints. Unless a case overrides it, the fixture
contains exactly one current-attempt `quality-results` job with
`status=completed` and `conclusion=success`; this preserves every earlier valid
and invalid case's intended meaning.

The supplemental matrix rejects a failed, cancelled, in-progress, missing,
old-attempt or duplicated reporting job. A successful completed reporting job
remains valid when the source workflow conclusion is failure or cancellation,
so category failures are not confused with report-delivery failure. A separate
positive case puts 100 unrelated jobs on page one and the sole valid reporting
job on page two, requiring complete jobs pagination.

Every positive case also requires the HTTP trace to contain an authenticated
GET of the jobs endpoint with `filter=latest`. This makes the test sensitive
even when all seven valid artifacts already exist after a partial upload.

## RED reproduction

```text
$ python3 -m py_compile tests/Verification/quality_graph_preflight_001_test.py
# exit 0, no output

$ python3 tests/Verification/quality_graph_preflight_001_test.py
Traceback (most recent call last):
  File "tests/Verification/quality_graph_preflight_001_test.py", line 215, in <module>
    main()
  File "tests/Verification/quality_graph_preflight_001_test.py", line 150, in main
    execute("current full seven", Fixture(run, {1: {"total_count": 7, "artifacts": current()}}), should_pass=True)
  File "tests/Verification/quality_graph_preflight_001_test.py", line 142, in execute
    raise AssertionError(
AssertionError: RED_ASSERTION: current full seven must read current jobs with filter=latest: [{'method': 'GET', 'path': '/repos/Antropophag/fmonitor-2/actions/runs/424242', 'authorization': 'Bearer fixture-token'}, {'method': 'GET', 'path': '/repos/Antropophag/fmonitor-2/actions/runs/424242/artifacts?per_page=100&page=1', 'authorization': 'Bearer fixture-token'}]
# exit 1
```

The current command has already accepted the run identity and the complete
seven-artifact page. It fails only because it does not query current reporting
job state. The fixture received authenticated read-only requests and returned
valid JSON; no production or external service was involved.

Supporting checks:

```text
$ openspec validate integrate-current-quality-graph --strict
Change 'integrate-current-quality-graph' is valid

$ git diff --check
# exit 0, no output
```

## Frozen input identities

```text
ca0fed7085bdd4a66d3c66bc168af08fe9fb706133beef047868885730b0e639  specs/QUALITY-GRAPH-CURRENT-CI-001.md
24822d2a7e2354f2c80190e2e795462d9636d501114b55dd766af4d63510d6ee  tests/Verification/quality_graph_preflight_001_test.py
5b09ea7c6dd9499a8c0cc1bfeba717b562028958bb7542e44841072c4cb9d6e4  tools/delivery/quality-graph-preflight.py
```

This is RED evidence authored with the test. It is not an independent review
and contains no approval verdict.

## Final pagination sensitivity — 2026-09-09

The test author added a valid reporting job on page one and a duplicate on page two
before its session was interrupted. Root reran the public test against committed
implementation1e7ac621; it still fails specifically on missing GET jobs, exit1.
This negative prevents stopping pagination after the first apparent success.
Frozen test SHA256: `7a302ea79ff148ca5ec03ba86d52e967988e7033cfd852bac0b7812abf201104`.
Raw evidence: /tmp/fmonitor-qg-reporting-admission-red.log.
Independent supplemental approval is pending because reviewer service returned usage limit.
