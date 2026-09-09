#!/usr/bin/env python3
"""Aggregate Codex JSONL usage without exposing log content or identifiers."""

from __future__ import annotations

import argparse
import json
import os
from pathlib import Path
import stat
import sys
from typing import Any


TOKEN_FIELDS = (
    "input_tokens",
    "cached_input_tokens",
    "cache_write_input_tokens",
    "output_tokens",
    "reasoning_output_tokens",
    "total_tokens",
)
OUTPUT_TOKEN_FIELDS = {
    "input_tokens": "input",
    "cached_input_tokens": "cached_input",
    "cache_write_input_tokens": "cache_write_input",
    "output_tokens": "output",
    "reasoning_output_tokens": "reasoning_output",
    "total_tokens": "total",
}
MAIN_SOURCES = {"user", "chatgpt_handoff"}


def empty_tokens() -> dict[str, int]:
    return {output: 0 for output in OUTPUT_TOKEN_FIELDS.values()}


def empty_role() -> dict[str, Any]:
    return {
        "sessions": 0,
        "model_calls": 0,
        "model_calls_basis": "unavailable",
        "tokens": empty_tokens(),
        "tool_calls": 0,
        "turns": {"completed": 0, "duration_ms": 0, "average_duration_ms": None},
        "context": {
            "samples": 0,
            "maximum_input_tokens": None,
            "average_input_tokens": None,
            "maximum_window_tokens": None,
            "peak_utilization": None,
        },
        "legacy_counter_resets": 0,
        "incomplete": False,
    }


def valid_usage(value: Any) -> dict[str, int] | None:
    if not isinstance(value, dict):
        return None
    if "input_tokens" not in value or "output_tokens" not in value:
        return None
    result: dict[str, int] = {}
    for field in TOKEN_FIELDS:
        if field == "total_tokens" and field not in value:
            item = value["input_tokens"] + value["output_tokens"]
        else:
            item = value.get(field, 0)
        if isinstance(item, bool) or not isinstance(item, int) or item < 0:
            return None
        result[field] = item
    return result


def output_tokens(usage: dict[str, int]) -> dict[str, int]:
    return {OUTPUT_TOKEN_FIELDS[field]: usage[field] for field in TOKEN_FIELDS}


def add_tokens(target: dict[str, int], source: dict[str, int]) -> None:
    for field in target:
        target[field] += source[field]


def role_for(sources: set[str]) -> str:
    if sources == {"subagent"}:
        return "subagents"
    if sources and sources.issubset(MAIN_SOURCES):
        return "main"
    return "unknown"


def parse_file(path: Path) -> tuple[str, dict[str, Any], int, int]:
    result = empty_role()
    result["sessions"] = 1
    skipped_lines = 0
    skipped_usage = 0
    sources: set[str] = set()
    response_usage: dict[str, tuple[dict[str, int], int | None]] = {}
    legacy: list[dict[str, int]] = []
    legacy_last: list[tuple[dict[str, int], int | None] | None] = []
    windows: list[int] = []
    current_window: int | None = None
    tool_calls: set[str] = set()
    completed_turns: dict[str, int] = {}

    with path.open("r", encoding="utf-8", errors="replace") as stream:
        for line in stream:
            try:
                row = json.loads(line)
            except (json.JSONDecodeError, ValueError):
                skipped_lines += 1
                continue
            if not isinstance(row, dict):
                skipped_lines += 1
                continue
            payload = row.get("payload")
            if not isinstance(payload, dict):
                continue
            row_type = row.get("type")
            if row_type == "session_meta" and isinstance(payload.get("thread_source"), str):
                sources.add(payload["thread_source"])
            elif row_type == "token_usage_record":
                parsed = valid_usage(payload.get("usage"))
                response_id = payload.get("response_id")
                if parsed is None or not isinstance(response_id, str) or not response_id:
                    skipped_usage += 1
                elif response_id not in response_usage:
                    response_usage[response_id] = (parsed, current_window)
            elif row_type == "event_msg" and payload.get("type") == "token_count":
                info = payload.get("info")
                if not isinstance(info, dict):
                    skipped_usage += 1
                    continue
                parsed = valid_usage(info.get("total_token_usage"))
                if parsed is None:
                    skipped_usage += 1
                else:
                    legacy.append(parsed)
                    last = valid_usage(info.get("last_token_usage"))
                    window = info.get("model_context_window")
                    valid_window = window if isinstance(window, int) and not isinstance(window, bool) and window > 0 else current_window
                    legacy_last.append((last, valid_window) if last is not None else None)
                window = info.get("model_context_window")
                if isinstance(window, int) and not isinstance(window, bool) and window > 0:
                    windows.append(window)
                    current_window = window
            elif row_type == "event_msg" and payload.get("type") == "task_started":
                window = payload.get("model_context_window")
                if isinstance(window, int) and not isinstance(window, bool) and window > 0:
                    windows.append(window)
                    current_window = window
            elif row_type == "event_msg" and payload.get("type") == "task_complete":
                turn_id, duration = payload.get("turn_id"), payload.get("duration_ms")
                if isinstance(turn_id, str) and turn_id and isinstance(duration, int) and not isinstance(duration, bool) and duration >= 0:
                    completed_turns.setdefault(turn_id, duration)
            elif row_type == "response_item" and payload.get("type") in {"function_call", "custom_tool_call"}:
                for candidate in (payload.get("call_id"), payload.get("id")):
                    if isinstance(candidate, str) and candidate:
                        tool_calls.add(candidate)
                        break

    context_inputs: list[int] = []
    context_ratios: list[float] = []
    if response_usage:
        result["model_calls_basis"] = "response_ids"
        result["model_calls"] = len(response_usage)
        for usage, window in response_usage.values():
            add_tokens(result["tokens"], output_tokens(usage))
            context_inputs.append(usage["input_tokens"])
            if window is not None:
                context_ratios.append(usage["input_tokens"] / window)
    elif legacy:
        result["model_calls_basis"] = "legacy_distinct_snapshots"
        distinct: list[dict[str, int]] = []
        distinct_last: list[tuple[dict[str, int], int | None] | None] = []
        for snapshot, last in zip(legacy, legacy_last):
            if distinct and snapshot == distinct[-1]:
                continue
            distinct.append(snapshot)
            distinct_last.append(last)
        result["model_calls"] = len(distinct)
        segment_last: dict[str, int] | None = None
        for snapshot in distinct:
            if segment_last is not None and any(snapshot[field] < segment_last[field] for field in TOKEN_FIELDS):
                add_tokens(result["tokens"], output_tokens(segment_last))
                result["legacy_counter_resets"] += 1
                result["incomplete"] = True
            segment_last = snapshot
        if segment_last is not None:
            add_tokens(result["tokens"], output_tokens(segment_last))
        for item in distinct_last:
            if item is not None:
                last, window = item
                context_inputs.append(last["input_tokens"])
                if window is not None:
                    context_ratios.append(last["input_tokens"] / window)

    result["tool_calls"] = len(tool_calls)
    result["turns"]["completed"] = len(completed_turns)
    result["turns"]["duration_ms"] = sum(completed_turns.values())
    if completed_turns:
        result["turns"]["average_duration_ms"] = round(sum(completed_turns.values()) / len(completed_turns), 3)
    if context_inputs:
        result["context"]["samples"] = len(context_inputs)
        result["context"]["maximum_input_tokens"] = max(context_inputs)
        result["context"]["average_input_tokens"] = round(sum(context_inputs) / len(context_inputs), 3)
    if windows:
        result["context"]["maximum_window_tokens"] = max(windows)
    if context_ratios:
        result["context"]["peak_utilization"] = round(max(context_ratios), 6)
    if skipped_usage:
        result["incomplete"] = True
    return role_for(sources), result, skipped_lines, skipped_usage


def merge_roles(target: dict[str, Any], source: dict[str, Any]) -> None:
    target["sessions"] += source["sessions"]
    target["model_calls"] += source["model_calls"]
    bases = {target["model_calls_basis"], source["model_calls_basis"]} - {"unavailable"}
    target["model_calls_basis"] = next(iter(bases)) if len(bases) == 1 else ("mixed" if bases else "unavailable")
    add_tokens(target["tokens"], source["tokens"])
    target["tool_calls"] += source["tool_calls"]
    target["turns"]["completed"] += source["turns"]["completed"]
    target["turns"]["duration_ms"] += source["turns"]["duration_ms"]
    target["legacy_counter_resets"] += source["legacy_counter_resets"]
    target["incomplete"] = target["incomplete"] or source["incomplete"]
    source_samples = source["context"]["samples"]
    if source_samples:
        previous_samples = target["context"]["samples"]
        previous_sum = (target["context"]["average_input_tokens"] or 0) * previous_samples
        source_sum = source["context"]["average_input_tokens"] * source_samples
        total_samples = previous_samples + source_samples
        target["context"]["samples"] = total_samples
        target["context"]["average_input_tokens"] = round((previous_sum + source_sum) / total_samples, 3)
        maximum = source["context"]["maximum_input_tokens"]
        current = target["context"]["maximum_input_tokens"]
        target["context"]["maximum_input_tokens"] = maximum if current is None else max(current, maximum)
    window = source["context"]["maximum_window_tokens"]
    if window is not None:
        current = target["context"]["maximum_window_tokens"]
        target["context"]["maximum_window_tokens"] = window if current is None else max(current, window)
    peak = source["context"]["peak_utilization"]
    if peak is not None:
        current = target["context"]["peak_utilization"]
        target["context"]["peak_utilization"] = peak if current is None else max(current, peak)


def finalize(role: dict[str, Any]) -> None:
    completed = role["turns"]["completed"]
    role["turns"]["average_duration_ms"] = round(role["turns"]["duration_ms"] / completed, 3) if completed else None


def discover_jsonl(source: Path) -> list[Path]:
    mode = source.stat().st_mode
    if stat.S_ISREG(mode):
        with source.open("rb"):
            pass
        return [source]
    if not stat.S_ISDIR(mode):
        raise OSError("unsupported input type")

    paths: list[Path] = []

    def traversal_error(error: OSError) -> None:
        raise error

    with os.scandir(source):
        pass
    for directory, _, filenames in os.walk(source, onerror=traversal_error, followlinks=False):
        for filename in filenames:
            if filename.endswith(".jsonl"):
                candidate = Path(directory) / filename
                if candidate.is_file():
                    paths.append(candidate)
    return sorted(paths)


def aggregate(source: Path) -> dict[str, Any]:
    paths = discover_jsonl(source)
    roles = {name: empty_role() for name in ("main", "subagents", "unknown")}
    skipped_lines = 0
    skipped_usage = 0
    for path in paths:
        role, session, bad_lines, bad_usage = parse_file(path)
        merge_roles(roles[role], session)
        skipped_lines += bad_lines
        skipped_usage += bad_usage
    totals = empty_role()
    for role in roles.values():
        finalize(role)
        merge_roles(totals, role)
    finalize(totals)
    return {
        "format_version": "fmonitor-usage-aggregate-v1",
        "files": {"accepted": len(paths), "skipped_lines": skipped_lines, "skipped_usage_records": skipped_usage},
        "billing": {"available": False, "reason": "local logs contain no verified tariff or billing breakdown"},
        "limitations": [
            "response_ids count unique logged responses, not independently verified provider requests",
            "durations may overlap and include waiting time",
            "skipped usage records or legacy counter resets make the affected aggregate incomplete",
        ],
        "roles": roles,
        "totals": totals,
    }
def main() -> int:
    parser = argparse.ArgumentParser(description="Emit privacy-safe aggregate Codex usage metrics")
    parser.add_argument("--input", required=True)
    parser.add_argument("--pretty", action="store_true")
    arguments = parser.parse_args()
    source = Path(arguments.input)
    try:
        report = aggregate(source)
    except (OSError, UnicodeError):
        print("usage aggregation failed: input is not a readable file or directory", file=sys.stderr)
        return 2
    print(json.dumps(report, indent=2 if arguments.pretty else None, sort_keys=True))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
