#!/usr/bin/env python3
"""Render/check the repository-owned current-CI Quality Graph envelope."""
from __future__ import annotations
import argparse
import hashlib
import json
from pathlib import Path

import yaml
from qg_github.compiler import compile_graph
from quality_graph_core.graph import Graph

ROOT = Path(__file__).resolve().parents[2]
BOUNDARIES = ["quality-graph.yml", ".github/workflows/quality-graph.yml",
              ".github/workflows/quality-graph-publish.yml",
              "tools/delivery/quality-graph-preflight.py", "tools/delivery/quality-graph-report.py"]

def publisher(source: str) -> str:
    embedded = "\n".join("          " + line if line else "" for line in source.splitlines())
    return f"""# Repository-owned trusted wrapper around pinned stock Quality Graph 0.1.7.
name: Quality Graph Publisher
'on':
  workflow_run:
    workflows: [Quality Graph]
    types: [completed]
permissions: {{}}
concurrency:
  group: quality-graph-publish-${{{{ github.event.workflow_run.id }}}}
  cancel-in-progress: false
jobs:
  publish:
    if: github.event.workflow_run.event == 'pull_request'
    runs-on: ubuntu-latest
    permissions:
      actions: read
      contents: read
      checks: write
      issues: write
      pull-requests: write
    steps:
    - name: Admit exact current result artifacts
      if: github.event.action == 'completed'
      shell: bash
      env:
        GITHUB_TOKEN: ${{{{ github.token }}}}
      run: |
        sed 's/^  //' <<'PY' | python3
{embedded}
        PY
    - name: Publish trusted Quality Graph state
      uses: alchemmist/quality-graph@caf5366a04ca01b230f1df5585d0fbd9693d7bef
      with:
        operation: publish
"""

def canonical(value: object) -> str:
    return json.dumps(value, indent=2, sort_keys=True) + "\n"


def quality_results(digest: str) -> str:
    nodes = [("plan", "plan"), ("fast", "fast"), ("unit", "unit"),
             ("integration", "Integration"), ("e2e", "e2e"),
             ("governance", "governance"), ("verify", "verify")]
    value = """  quality-results:
    if: always() && github.event_name == 'pull_request'
    needs: [plan, fast, unit, integration, e2e, governance, verify]
    runs-on: ubuntu-latest
    timeout-minutes: 10
    permissions:
      contents: read
    steps:
    - uses: actions/checkout@fbc6f3992d24b796d5a048ff273f7fcc4a7b6c09
      with:
        ref: ${{ github.event.pull_request.head.sha || github.sha }}
        persist-credentials: 'false'
        fetch-depth: '0'
    - name: Render native Quality Graph reports
      id: reports
      env:
        FULL: ${{ needs.plan.outputs.full || 'unknown' }}
        RESULTS: '{"plan":"${{ needs.plan.result }}","fast":"${{ needs.fast.result }}","unit":"${{ needs.unit.result }}","integration":"${{ needs.integration.result }}","e2e":"${{ needs.e2e.result }}","governance":"${{ needs.governance.result }}","verify":"${{ needs.verify.result }}"}'
      run: python3 tools/delivery/quality-graph-report.py --full "$FULL" --results-json "$RESULTS" --graph-digest DIGEST --output-dir .quality-graph/reports
""".replace("DIGEST", digest)
    for node, title in nodes:
        value += f"""    - name: Collect {title}
      id: collect-{node}
      if: always()
      uses: alchemmist/quality-graph@caf5366a04ca01b230f1df5585d0fbd9693d7bef
      with:
        operation: collect
        node-id: {node}
        title: {title}
        adapter: native
        report-path: .quality-graph/reports/{node}.json
        command-outcome: ${{{{ steps.reports.outcome }}}}
        graph-digest: {digest}
        approval-findings: 'false'
        approval-files: 'false'
        approval-node: 'false'
    - name: Upload {title}
      if: always()
      uses: actions/upload-artifact@b7c566a772e6b6bfb58ed0dc250532a479d7789f
      with:
        name: quality-result-{node}-${{{{ github.run_attempt }}}}
        path: ${{{{ steps.collect-{node}.outputs.result-path }}}}
        if-no-files-found: error
        retention-days: 7
"""
    return value

def expected() -> dict[str, str]:
    graph_text = (ROOT / "quality-graph.yml").read_text()
    graph = Graph.from_yaml(graph_text)
    project = compile_graph(graph)
    compiled_manifest = next(f.content for f in project.files if str(f.path) == ".quality-graph/manifest.json")
    workflow_path = ROOT / ".github/workflows/quality-graph.yml"
    current_workflow = workflow_path.read_text()
    prefix = current_workflow.split("\n  quality-results:\n", 1)[0] + "\n"
    workflow = prefix + quality_results(project.graph_digest)
    jobs = yaml.safe_load(workflow)['jobs']
    fast_commands = [line.strip() for step in jobs['fast']['steps']
                     for line in step.get('run', '').splitlines() if line.strip()]
    declared_fast = next(node.step.run for node in graph.nodes if node.id == 'fast')
    if declared_fast != ' && '.join(fast_commands):
        raise ValueError('graph fast command differs from the actual CI job')
    values = {path: (ROOT / path).read_text() for path in BOUNDARIES
              if path not in {".github/workflows/quality-graph.yml",
                              ".github/workflows/quality-graph-publish.yml"}}
    values[".github/workflows/quality-graph.yml"] = workflow
    values[".github/workflows/quality-graph-publish.yml"] = publisher(values["tools/delivery/quality-graph-preflight.py"])
    for relative in ("quality-graph.yml", ".github/workflows/quality-graph.yml",
                     ".github/workflows/quality-graph-publish.yml"):
        loaded = yaml.safe_load(values[relative])
        if not isinstance(loaded, dict):
            raise ValueError(f"generated YAML root is invalid: {relative}")
    manifest = {"schemaVersion": 1, "graphDigest": project.graph_digest,
                "compiledManifest": json.loads(compiled_manifest),
                "fileSha256": {path: hashlib.sha256(text.encode()).hexdigest()
                               for path, text in sorted(values.items())}}
    body = canonical(manifest)
    manifest["selfDigest"] = hashlib.sha256(body.encode()).hexdigest()
    values[".quality-graph/current-ci-manifest.json"] = canonical(manifest)
    return values

def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--check", action="store_true")
    args = parser.parse_args()
    values = expected()
    drift = []
    for relative, content in values.items():
        path = ROOT / relative
        if args.check:
            if not path.is_file() or path.read_text() != content:
                drift.append(relative)
        else:
            path.parent.mkdir(parents=True, exist_ok=True)
            path.write_text(content)
    if drift:
        print("QUALITY_GRAPH_RENDER_FAILURE files=" + ",".join(drift))
        return 1
    digest = json.loads(values[".quality-graph/current-ci-manifest.json"])["graphDigest"]
    print(f"QUALITY_GRAPH_RENDER_OK digest={digest}"); return 0
if __name__ == "__main__":
    raise SystemExit(main())
