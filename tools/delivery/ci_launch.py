#!/usr/bin/env python3
"""Fail-closed selection of one exact Quality Graph run."""

import time


_REQUIRED_BINDING = ("repository", "workflow", "head", "base", "mode")


def _unknown(reason, run=None):
    return {"decision": "UNKNOWN", "status": "UNKNOWN", "reason": reason,
            "run": run or {"id": None}}


def _applicable(run, binding):
    if not isinstance(run, dict) or not isinstance(run.get("id"), int):
        return None
    if any(run.get(key) is None for key in _REQUIRED_BINDING):
        return None
    return (all(run.get(key) == binding.get(key) for key in _REQUIRED_BINDING)
            and run.get("event") == "pull_request")


def _decide_existing(run, admission):
    status = run.get("status")
    if status in {"queued", "in_progress"}:
        return {"decision": "REUSE", "run": run}
    if status != "completed":
        return _unknown("incomplete run status", run)
    if run.get("conclusion") != "success":
        return {"decision": "OBSERVE_FAILURE", "run": run}
    jobs = run.get("jobs")
    if (not isinstance(jobs, list) or not jobs
            or any(not isinstance(job, dict) or job.get("run_id") != run["id"]
                   for job in jobs)):
        return _unknown("run jobs are incomplete or cross-run", run)
    try:
        result = admission.evaluate(run)
    except Exception:
        return _unknown("admission failed", run)
    if (not isinstance(result, dict) or result.get("status") != "SUCCESS"
            or result.get("required_results_complete") is not True):
        return _unknown("required results are not admitted", run)
    return {"decision": "REUSE", "run": run, "admission": result}


def select_or_dispatch(github, admission, binding, *, polls=3, poll_interval=1):
    """Discover a PR run, or dispatch once after a bounded confirmed absence."""
    if (not isinstance(binding, dict) or polls < 1
            or any(not binding.get(key) for key in _REQUIRED_BINDING)):
        return _unknown("invalid binding")
    try:
        for attempt in range(polls):
            runs = github.list_runs()
            if not isinstance(runs, list):
                return _unknown("incomplete discovery response")
            if runs:
                applicability = [_applicable(run, binding) for run in runs]
                if any(value is None for value in applicability):
                    return _unknown("unknown run applicability")
                applicable = [run for run, matches in zip(runs, applicability) if matches]
                if len(applicable) != len(runs) or len(applicable) != 1:
                    return {"decision": "BLOCKED_MISMATCH", "run": runs[0]}
                selected = applicable[0]
                if selected.get("status") == "completed" and not selected.get("jobs"):
                    selected = github.observe(selected["id"])
                    if (not isinstance(selected, dict)
                            or selected.get("id") != applicable[0]["id"]
                            or _applicable(selected, binding) is not True):
                        return _unknown("completed run observation mismatch")
                return _decide_existing(selected, admission)
            if attempt + 1 < polls and poll_interval:
                time.sleep(poll_interval)
        created = github.dispatch()
        if not isinstance(created, dict) or not isinstance(created.get("id"), int):
            return _unknown("dispatch identity unavailable")
        observed = created
        for _ in range(2):
            observed = github.observe(created["id"])
            if not isinstance(observed, dict) or observed.get("id") != created["id"]:
                return _unknown("dispatched run observation mismatch")
            if observed.get("status") == "completed":
                decision = _decide_existing(observed, admission)
                if decision["decision"] == "REUSE":
                    decision["decision"] = "DISPATCHED"
                return decision
        return {"decision": "PENDING", "status": "PENDING", "run": observed}
    except Exception as error:
        return _unknown(type(error).__name__)
