# Quality Graph 0.1.7 stock publisher — bounded artifact probe

Date: 2026-09-09. This is pre-spec diagnostic evidence, not an implementation,
publisher patch, parity approval, or permission change.

The executable probe calls the pinned stock `qg_github.publication.publish_workflow_run`
with its real `MemoryGitHubPort`, compiler, artifact downloader and Result v0 parser:

```text
tools/delivery/probes/stock_publisher_0_1_7.py
```

Pinned environment: the existing Quality Graph worktree `.venv`, packages
`quality-graph-core==0.1.7` and `quality-graph-github==0.1.7`. Command:

```text
/Users/antropophag/code/fmonitor-2-quality-current-20260908/.venv/bin/python \
  tools/delivery/probes/stock_publisher_0_1_7.py \
  --output /Users/antropophag/.local/state/fmonitor2/quality-graph-stock-probe-20260909/results.json
```

Exit was `1`, the intended RED result. Evidence SHA-256:

```text
ae0c64a3002810a1cc08b5f88af3a965e3852fb0a8bea8acf9b820edba740378  results.json
```

## Findings

Two required admission properties fail in unmodified stock 0.1.7:

1. A result artifact named and internally bound to attempt 2 is accepted and published
   `passed` for a completed workflow-run event whose current `run_attempt` is 3.
2. Two artifacts for the same node and the same attempt are accepted and published
   `passed`; the later descriptor silently replaces the earlier one.
3. An artifact from future attempt 4 is accepted for the current attempt-3 event.
4. When attempt-2 failure and attempt-4 pass artifacts coexist, stock selects attempt 4
   and publishes `passed` for the attempt-3 event.

The cause is visible in stock `qg_github/artifacts.py`: `ArtifactExpectation` contains
no expected run attempt, and `download_results()` selects a node whenever
`descriptor.attempt >= current_attempt`. Provenance validation compares the Result
attempt only with its artifact name, not with the triggering workflow event attempt.

The same direct publisher probe confirms current PASS and current command failure are
published accurately. Missing artifact, malformed Result JSON, and mismatched workflow
run ID, head SHA, or graph digest all produce a published failed dashboard.

## Planning consequence

Stock `publish_workflow_run` cannot be the sole acceptance boundary for result artifacts.
The repository integration needs an independently specified exact-attempt and unique-node
guard before calling the stock publisher, or an upstream pinned release that proves both
properties. The guard must reject any descriptor/result attempt unequal to the current
workflow event attempt and reject cardinality other than exactly one artifact per expected
node. Choosing a newest or last duplicate is not acceptable provenance.

The owner-authorized upstream report is
[quality-graph issue #69](https://github.com/alchemmist/quality-graph/issues/69).
A separate reproduction against latest `0.1.10`, using the actual latest-run-attempt
API response and a GET check stub, observes the same four admission failures. Its
outside-repository result is `/tmp/repro_quality_graph_results.json`, SHA-256
`e0f4ab7326f79e296937f96240da323ae2d9916b88a40802897f409b18d5bb4a`.
This evidence does not change the pinned integration version or patch upstream code.
