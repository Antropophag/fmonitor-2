#!/usr/bin/env python3
"""Replay the issue #157 context-byte proxy without estimating tokens."""

import argparse
import hashlib
import json
from pathlib import Path
import sys

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / "tools/delivery"))
import harness_context  # noqa: E402


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--baseline", required=True)
    args = parser.parse_args()
    baseline_path = (ROOT / args.baseline).resolve()
    baseline = json.loads(baseline_path.read_text(encoding="utf-8"))
    cases = []
    for before in baseline["cases"]:
        input_path = ROOT / before["input"]
        verification_input = json.loads(input_path.read_text(encoding="utf-8"))
        plan = {"change": verification_input["change"],
                "paths": {"planned": verification_input["planned_paths"]},
                "acceptances": verification_input["acceptances"]}
        contracts = sorted({item["spec_path"] for item in verification_input["acceptances"]})
        manifest, delivered = harness_context.build_task_context(
            ROOT, plan, role="root", source=baseline["measured_at_source"],
            base=baseline["measured_at_source"], contracts=contracts,
            evidence=[], snapshot="replay")
        mandatory_bytes = sum(len(item["content"].encode("utf-8")) for item in delivered["items"])
        mandatory_characters = sum(len(item["content"]) for item in delivered["items"])
        manifest_bytes = len((json.dumps(manifest, ensure_ascii=False, sort_keys=True,
            indent=2) + "\n").encode("utf-8"))
        artifact_bytes = len((json.dumps(delivered, ensure_ascii=False, sort_keys=True,
            indent=2) + "\n").encode("utf-8"))
        whole_documents = sum(item["content_reference"]["start_byte"] == 0
            and item["content_reference"]["end_byte"] == (ROOT / item["source"]).stat().st_size
            for item in manifest["required_context"])
        cases.append({"id": before["id"], "change": before["change"], "input": before["input"],
            "input_digest": hashlib.sha256(input_path.read_bytes()).hexdigest(),
            "before": {key: before[key] for key in ("mandatory_bytes", "mandatory_characters",
                "whole_documents", "load_on_demand_references", "whole_sources",
                "obviously_historical_or_unrelated")},
            "after": {"mandatory_bytes": mandatory_bytes,
                "mandatory_characters": mandatory_characters,
                "whole_documents": whole_documents,
                "load_on_demand_references": len(manifest["load_on_demand"]),
                "manifest_bytes": manifest_bytes,
                "required_context_artifact_bytes": artifact_bytes,
                "total_delivered_bytes": manifest_bytes + artifact_bytes,
                "required_rule_ids": sorted(item["rule_id"] for item in manifest["required_context"])}})
    print(json.dumps({"schema": "fmonitor-task-context-measurement-v1",
        "token_usage": "UNKNOWN", "cases": cases}, ensure_ascii=False, sort_keys=True))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
