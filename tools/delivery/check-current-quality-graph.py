#!/usr/bin/env python3
"""Validate current-CI Quality Graph published boundaries using stdlib only."""
from __future__ import annotations
import argparse
import hashlib
import json
import re
from pathlib import Path

FILES = ["quality-graph.yml", ".quality-graph/current-ci-manifest.json",
         ".github/workflows/quality-graph.yml", ".github/workflows/quality-graph-publish.yml",
         "tools/delivery/quality-graph-preflight.py", "tools/delivery/quality-graph-report.py"]
PIN = "alchemmist/quality-graph@caf5366a04ca01b230f1df5585d0fbd9693d7bef"
NODES = ["plan", "fast", "unit", "integration", "e2e", "governance", "verify"]

def failure(detail: str) -> int:
    print("QUALITY_GRAPH_VALIDATION_FAILURE detail=" + " ".join(detail.split())[:400])
    return 1

def canonical(value: object) -> str:
    return json.dumps(value, indent=2, sort_keys=True) + "\n"

def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--root", type=Path)
    args = parser.parse_args()
    root = (args.root or Path(__file__).resolve().parents[2]).resolve()
    try:
        for name in FILES:
            path = root / name
            if not path.is_file() or path.is_symlink(): raise ValueError(f"missing or unsafe {name}")
        manifest_path = root / FILES[1]
        manifest = json.loads(manifest_path.read_text())
        if set(manifest) != {"schemaVersion", "graphDigest", "compiledManifest", "fileSha256", "selfDigest"}: raise ValueError("manifest shape")
        if manifest["schemaVersion"] != 1 or re.fullmatch(r"[0-9a-f]{64}", manifest["graphDigest"] or "") is None: raise ValueError("manifest identity")
        without_self = dict(manifest); observed_self = without_self.pop("selfDigest")
        if hashlib.sha256(canonical(without_self).encode()).hexdigest() != observed_self: raise ValueError("manifest self digest")
        hashes = manifest["fileSha256"]
        expected_hash_paths = set(FILES) - {FILES[1]}
        if set(hashes) != expected_hash_paths: raise ValueError("manifest hash paths")
        for name in expected_hash_paths:
            if hashlib.sha256((root / name).read_bytes()).hexdigest() != hashes[name]: raise ValueError(f"hash drift {name}")
        compiled = manifest["compiledManifest"]
        if compiled.get("graphDigest") != manifest["graphDigest"]: raise ValueError("compiled digest drift")
        nodes = compiled.get("nodes")
        if not isinstance(nodes, list) or [node.get("id") for node in nodes] != NODES: raise ValueError("compiled nodes")
        if any(node.get("result", {}).get("adapter") != "native" for node in nodes): raise ValueError("compiled adapters")
        workflow = (root / FILES[2]).read_text(); publisher = (root / FILES[3]).read_text()
        if workflow.count("adapter: native") != 7 or workflow.count("uses: " + PIN) != 7: raise ValueError("runner collection")
        if manifest["graphDigest"] not in workflow: raise ValueError("runner graph digest")
        for node in NODES:
            if f"node-id: {node}" not in workflow: raise ValueError(f"runner node {node}")
        if "actions/checkout" in publisher or publisher.count("uses: " + PIN) != 1: raise ValueError("publisher execution boundary")
        for required in ["actions: read", "contents: read", "checks: write", "issues: write", "pull-requests: write"]:
            if required not in publisher: raise ValueError("publisher permissions")
        for forbidden in ["issue_comment:", "operation: command", "operation: approve", "contents: write"]:
            if forbidden in publisher: raise ValueError("publisher forbidden surface")
        source = (root / FILES[4]).read_text()
        embedded = "\n".join("          " + line if line else "" for line in source.splitlines())
        if embedded not in publisher: raise ValueError("preflight inline drift")
    except (OSError, ValueError, TypeError, json.JSONDecodeError) as error:
        return failure(str(error))
    print("QUALITY_GRAPH_VALIDATION_OK digest=" + manifest["graphDigest"]); return 0
if __name__ == "__main__": raise SystemExit(main())
