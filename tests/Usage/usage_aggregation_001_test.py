#!/usr/bin/env python3
"""Executable specification for USAGE-AGGREGATION-001."""

import json
from pathlib import Path
import subprocess
import sys
import tempfile


ROOT = Path(__file__).resolve().parents[2]
COMMAND = ROOT / "tools" / "usage" / "aggregate.py"
CANARY = "PRIVATE-CONTENT-MUST-NEVER-ESCAPE"


def write_jsonl(path: Path, rows: list[dict], malformed: bool = False) -> None:
    with path.open("w", encoding="utf-8") as stream:
        for row in rows:
            stream.write(json.dumps(row) + "\n")
        if malformed:
            stream.write("{private malformed " + CANARY + "\n")


def usage(input_tokens: int, cached: int, output: int, reasoning: int) -> dict:
    return {
        "input_tokens": input_tokens,
        "cached_input_tokens": cached,
        "cache_write_input_tokens": 0,
        "output_tokens": output,
        "reasoning_output_tokens": reasoning,
        "total_tokens": input_tokens + output,
    }


with tempfile.TemporaryDirectory(prefix="fm2-usage-spec-") as temporary:
    source = Path(temporary)
    main_rows = [
        {"type": "session_meta", "payload": {"thread_source": "user", "session_id": CANARY}},
        {"type": "event_msg", "payload": {"type": "task_started", "turn_id": "turn-a", "model_context_window": 1000}},
        {"type": "token_usage_record", "payload": {"response_id": "response-a", "usage": usage(100, 80, 10, 4), "turn_token_usage": usage(100, 80, 10, 4), "thread_token_usage": usage(100, 80, 10, 4)}},
        {"type": "token_usage_record", "payload": {"response_id": "response-a", "usage": usage(100, 80, 10, 4), "thread_token_usage": usage(999999, 0, 0, 0)}},
        {"type": "event_msg", "payload": {"type": "task_started", "turn_id": "turn-b", "model_context_window": 200}},
        {"type": "token_usage_record", "payload": {"response_id": "response-b", "usage": usage(140, 120, 20, 8), "thread_token_usage": usage(240, 200, 30, 12)}},
        {"type": "token_usage_record", "payload": {"response_id": "response-c", "usage": {"input_tokens": 10, "output_tokens": 2}}},
        {"type": "event_msg", "payload": {"type": "token_count", "info": {"model_context_window": 1000, "total_token_usage": usage(999999, 0, 0, 0)}}},
        {"type": "response_item", "payload": {"type": "function_call", "call_id": "tool-a", "arguments": CANARY}},
        {"type": "response_item", "payload": {"type": "function_call", "call_id": "tool-a", "arguments": CANARY}},
        {"type": "response_item", "payload": {"type": "custom_tool_call", "call_id": "tool-b", "input": CANARY}},
        {"type": "event_msg", "payload": {"type": "task_complete", "turn_id": "turn-a", "duration_ms": 2500, "summary": CANARY}},
        {"type": "event_msg", "payload": {"type": "task_complete", "turn_id": "turn-a", "duration_ms": 2500}},
        {"type": "response_item", "payload": {"type": "message", "content": CANARY}},
    ]
    legacy_rows = [
        {"type": "session_meta", "payload": {"thread_source": "subagent", "agent_path": CANARY}},
        {"type": "event_msg", "payload": {"type": "token_count", "info": {"total_token_usage": usage(100, 60, 10, 3), "last_token_usage": usage(100, 60, 10, 3)}}},
        {"type": "event_msg", "payload": {"type": "token_count", "info": {"model_context_window": 400, "total_token_usage": usage(180, 130, 25, 9), "last_token_usage": usage(80, 70, 15, 6)}}},
        {"type": "event_msg", "payload": {"type": "token_count", "info": {"model_context_window": 400, "total_token_usage": usage(180, 130, 25, 9), "last_token_usage": usage(80, 70, 15, 6)}}},
        {"type": "event_msg", "payload": {"type": "token_count", "info": {"model_context_window": 400, "total_token_usage": usage(20, 10, 3, 1)}}},
        {"type": "event_msg", "payload": {"type": "turn_aborted", "turn_id": "turn-z", "duration_ms": 500}},
    ]
    unknown_rows = [
        {"type": "session_meta", "payload": {"session_id": CANARY}},
        {"type": "token_usage_record", "payload": {"response_id": CANARY, "usage": {"input_tokens": -1, "cached_input_tokens": CANARY, "output_tokens": 2}}},
        {"type": "token_usage_record", "payload": {"response_id": "empty", "usage": {}}},
    ]
    write_jsonl(source / "main.jsonl", main_rows, malformed=True)
    write_jsonl(source / "subagent.jsonl", legacy_rows)
    write_jsonl(source / "unknown.jsonl", unknown_rows)
    before = {path.name: path.read_bytes() for path in source.iterdir()}
    completed = subprocess.run(
        [sys.executable, str(COMMAND), "--input", str(source)],
        text=True,
        capture_output=True,
        check=False,
    )
    assert completed.returncode == 0, completed.stderr
    assert completed.stderr == ""
    assert CANARY not in completed.stdout + completed.stderr
    report = json.loads(completed.stdout)
    assert report["format_version"] == "fmonitor-usage-aggregate-v1"
    assert report["files"] == {"accepted": 3, "skipped_lines": 1, "skipped_usage_records": 2}
    assert report["billing"]["available"] is False
    assert "price" not in json.dumps(report).lower()
    main = report["roles"]["main"]
    assert main["sessions"] == 1
    assert main["model_calls"] == 3
    assert main["model_calls_basis"] == "response_ids"
    assert main["tokens"] == {
        "input": 250, "cached_input": 200, "cache_write_input": 0,
        "output": 32, "reasoning_output": 12, "total": 282,
    }
    assert main["tool_calls"] == 2
    assert main["turns"] == {"completed": 1, "duration_ms": 2500, "average_duration_ms": 2500.0}
    assert main["context"] == {
        "samples": 3, "maximum_input_tokens": 140, "average_input_tokens": 83.333,
        "maximum_window_tokens": 1000, "peak_utilization": 0.7,
    }
    subagents = report["roles"]["subagents"]
    assert subagents["sessions"] == 1
    assert subagents["model_calls"] == 3
    assert subagents["model_calls_basis"] == "legacy_distinct_snapshots"
    assert subagents["tokens"]["input"] == 200
    assert subagents["tokens"]["total"] == 228
    assert subagents["legacy_counter_resets"] == 1
    assert subagents["incomplete"] is True
    assert subagents["context"]["samples"] == 2
    assert subagents["context"]["maximum_input_tokens"] == 100
    assert subagents["context"]["peak_utilization"] == 0.2
    assert subagents["turns"]["completed"] == 0
    unknown = report["roles"]["unknown"]
    assert unknown["sessions"] == 1
    assert unknown["model_calls"] == 0
    assert report["totals"]["model_calls"] == 6
    assert report["totals"]["model_calls_basis"] == "mixed"
    assert report["totals"]["tokens"]["input"] == 450
    assert report["totals"]["incomplete"] is True
    after = {path.name: path.read_bytes() for path in source.iterdir()}
    assert before == after

missing = subprocess.run(
    [sys.executable, str(COMMAND), "--input", "/private/" + CANARY],
    text=True,
    capture_output=True,
    check=False,
)
assert missing.returncode == 2
assert missing.stdout == ""
assert missing.stderr == "usage aggregation failed: input is not a readable file or directory\n"
assert CANARY not in missing.stderr

print("USAGE_AGGREGATION_001_OK")
