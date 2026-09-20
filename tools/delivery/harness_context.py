#!/usr/bin/env python3
"""State, Codex hook and review-package support for the delivery harness."""

import hashlib
import argparse
import fcntl
import importlib.util
import json
import os
from pathlib import Path
import re
import shlex
import subprocess
import sys
import tempfile
import time
import uuid
from pathlib import PurePosixPath


def correction_review_context(*, full_candidate, last_reviewed_source, delta,
                              findings, suggestions=None, new_risks=None,
                              return_count=0):
    """Build the bounded context for a corrected candidate's repeat review."""
    if not all(isinstance(value, str) and value for value in
               (full_candidate, last_reviewed_source, delta)):
        raise ValueError("candidate identities and delta are required")
    if not isinstance(findings, list):
        raise ValueError("findings must be a list")
    open_findings = []
    for finding in findings:
        if not isinstance(finding, dict) or not finding.get("id"):
            raise ValueError("each finding requires an id")
        status = finding.get("status")
        if status not in {"fixed", "open", "not-applicable"}:
            raise ValueError("each finding requires a supported disposition")
        required = {"fixed": "evidence", "open": "blocker",
                    "not-applicable": "reason"}[status]
        if not finding.get(required):
            raise ValueError(f"{status} finding requires {required}")
        if status == "open":
            open_findings.append(finding)
    status = "READY"
    if open_findings:
        status = "RECONSIDER_OR_BLOCK" if return_count >= 2 else "BLOCKED"
    return {
        "full_candidate": full_candidate,
        "last_reviewed_source": last_reviewed_source,
        "candidate_delta": delta,
        "finding_dispositions": findings,
        "suggestions": list(suggestions or []),
        "new_risks": list(new_risks or []),
        "status": status,
    }


def requires_repeat_code_review(before, after):
    """Return False only for completion checkboxes and PR-number typo fixes."""
    if not isinstance(before, dict) or not isinstance(after, dict):
        raise ValueError("review snapshots must be mappings")
    paths = set(before) | set(after)
    for path in paths:
        old = before.get(path)
        new = after.get(path)
        if old == new:
            continue
        if not isinstance(old, (str, bytes)) or not isinstance(new, (str, bytes)):
            return True
        if isinstance(old, bytes):
            try:
                old = old.decode("utf-8")
                new = new.decode("utf-8") if isinstance(new, bytes) else new
            except UnicodeDecodeError:
                return True
        elif isinstance(new, bytes):
            try:
                new = new.decode("utf-8")
            except UnicodeDecodeError:
                return True
        if path.startswith("openspec/") and path.endswith("/tasks.md"):
            normalize = lambda value: re.sub(r"(?m)^([ \t]*- \[)[ xX](\])", r"\1?\2", value)
            if normalize(old) == normalize(new):
                continue
        if path == "external-pr-record":
            normalize = lambda value: re.sub(r"(?i)\bPR\s*#\d+\b", "PR #?", value)
            if normalize(old) == normalize(new):
                continue
        return True
    return False


def _helpers(value=None):
    if value is not None:
        return value
    import harness
    return harness


def _json(value):
    print(json.dumps(value, ensure_ascii=False, sort_keys=True))


def _run(root, argv, *, check=False, timeout=30):
    result = subprocess.run(argv, cwd=root, text=True, capture_output=True, timeout=timeout)
    if check and result.returncode:
        raise ValueError(result.stderr.strip() or result.stdout.strip() or "command failed")
    return result


def _now():
    return time.strftime("%Y-%m-%dT%H:%M:%SZ", time.gmtime())


def _safe_repo_path(root, value):
    if not isinstance(value, str) or not value or "\x00" in value:
        raise ValueError("invalid repository path")
    relative = PurePosixPath(value)
    if relative.is_absolute() or ".." in relative.parts or value != relative.as_posix():
        raise ValueError("unsafe repository path")
    return root / value


def _safe_canonical_path(root, value):
    path = _safe_repo_path(root, value)
    root = root.resolve()
    current = root
    for part in PurePosixPath(value).parts:
        current = current / part
        if current.is_symlink():
            raise ValueError("canonical requirement cannot contain symlinks")
    try:
        path.resolve(strict=False).relative_to(root)
    except ValueError as error:
        raise ValueError("canonical requirement escapes repository") from error
    return path


def _source(helpers, root):
    try:
        return helpers.source_identity(root)
    except TypeError:
        return helpers.source_identity()


def _state_file(helpers):
    identity = _worktree_key(helpers)
    path = helpers.evidence_home() / "state" / ("github-" + identity + ".json")
    path.parent.mkdir(parents=True, exist_ok=True)
    return path


def _ci_status(checks):
    if not checks:
        return "UNKNOWN"
    if any(item.get("status") != "COMPLETED" for item in checks):
        return "PENDING"
    conclusions = {item.get("conclusion") for item in checks}
    return "SUCCESS" if conclusions == {"SUCCESS"} else "FAILURE"


def _load_observation(path):
    value = json.loads(Path(path).read_text(encoding="utf-8"))
    if not isinstance(value, dict):
        raise ValueError("observation must be a JSON object")
    value["replay"] = True
    return value


def _admit(helpers, observation):
    import admission
    result = admission.evaluate(observation, helpers.ROOT)
    result["ci"]["triage"] = _ci_triage(observation)
    return result


_KNOWN_CI_SIGNATURES = (
    "pr144-inspection-partial-result-json-v1",
    "verification-mariadb-precondition-v1",
)
_PR144_TEST = "tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php"
_DB_PREFLIGHT = "test MariaDB unavailable; run make test-db-reset migrate"


def _triage_unknown(exact, history=None, failed_jobs=None, regressions=None):
    return {
        "classification": "UNKNOWN", "known_signature_ids": list(_KNOWN_CI_SIGNATURES),
        "exact": exact, "signature_id": None, "matched_evidence": [],
        "confidence_basis": "insufficient_evidence", "recommended_action": "NORMAL_TRIAGE",
        "retry_allowed": False, "retry_budget": 1, "retry_remaining": 0,
        "diagnostic_references": {"failed_job_inventory": failed_jobs or [],
                                  "regression_failure_inventory": regressions or [],
                                  "history": history or []},
        "measurement": {"mandatory_log_payloads_materialized": 0, "model_triage_steps": 0,
                        "automatic_retry_count": 0, "token_usage": "UNKNOWN"},
    }


def _ci_triage(observation):
    """Pure, closed classification of the two repository-owned CI signatures."""
    binding = observation.get("binding") if isinstance(observation, dict) else None
    binding = binding if isinstance(binding, dict) else {}
    ci = observation.get("ci") if isinstance(observation, dict) else None
    ci = ci if isinstance(ci, dict) else {}
    jobs = ci.get("jobs") if isinstance(ci.get("jobs"), list) else []
    failed = [job for job in jobs if isinstance(job, dict)
              and str(job.get("conclusion", "")).upper() == "FAILURE"]
    failed_inventory = [{"job_id": job.get("id"), "job": job.get("name"),
                         "check": job.get("check", job.get("name")),
                         "conclusion": "FAILURE"} for job in failed]
    diagnostics = ci.get("diagnostics") if isinstance(ci.get("diagnostics"), list) else []
    diagnostic = diagnostics[0] if len(diagnostics) == 1 and isinstance(diagnostics[0], dict) else None
    setup_jobs = {"Integration (1/2)", "Integration (2/2)", "e2e"}
    applicable = [job for job in failed if job.get("name") in setup_jobs
                  and job.get("check", job.get("name")) == job.get("name")]
    matching = [job for job in applicable if diagnostic is not None
                and diagnostic.get("job_id") == job.get("id")
                and diagnostic.get("job") == job.get("name")
                and diagnostic.get("check") == job.get("check", job.get("name"))]
    target = matching[0] if len(matching) == 1 else {}
    exact = {
        "repository": binding.get("repository", "UNKNOWN"), "pr": binding.get("pr", "UNKNOWN"),
        "run_id": binding.get("run_id", "UNKNOWN"), "attempt": binding.get("attempt", "UNKNOWN"),
        "job_id": target.get("id", "UNKNOWN"), "job": target.get("name", "UNKNOWN"),
        "check": target.get("check", target.get("name", "UNKNOWN")),
        "candidate_source": binding.get("candidate", "UNKNOWN"),
        "head": binding.get("head", "UNKNOWN"),
    }
    history = ci.get("history") if isinstance(ci.get("history"), list) else []
    retained_history = [item for item in history if isinstance(item, dict)
                        and item.get("run_id") == binding.get("run_id")
                        and item.get("head") == binding.get("head")]
    inventory = ci.get("failure_inventory") if isinstance(ci.get("failure_inventory"), list) else []
    regressions = [item for item in inventory if isinstance(item, dict)
                   and item.get("kind") == "REGRESSION_FAILURE"]
    unknown = _triage_unknown(exact, retained_history, failed_inventory, regressions)
    required = ("repository", "pr", "head", "candidate", "run_id", "attempt")
    current = observation.get("current") if isinstance(observation.get("current"), dict) else {}
    if (any(binding.get(key) in (None, "", "UNKNOWN") for key in required)
            or current.get("head") != binding.get("head")
            or ci.get("binding") != binding
            or not isinstance(ci.get("failure_inventory"), list)
            or not isinstance(ci.get("diagnostics"), list)
            or any(not isinstance(job, dict) or job.get("binding") != binding for job in jobs)):
        return unknown
    if ci.get("diagnostics_unavailable") or len(diagnostics) > 1:
        return unknown
    if not target or not isinstance(target.get("id"), int) or target["id"] <= 0:
        return unknown
    if diagnostic is None:
        return unknown
    if "signature_id" in diagnostic:
        return unknown
    common = (diagnostic.get("source") == "bounded_job_diagnostic"
              and diagnostic.get("job_id") == target["id"]
              and diagnostic.get("job") == target["name"]
              and diagnostic.get("check") == exact["check"]
              and diagnostic.get("run_id") == binding["run_id"]
              and diagnostic.get("attempt") == binding["attempt"]
              and diagnostic.get("head") == binding["head"]
              and diagnostic.get("candidate_source") == binding["candidate"])
    if not common:
        return unknown
    payloads = diagnostic.get("materialized_log_payloads")
    if not isinstance(payloads, int) or isinstance(payloads, bool) or payloads < 0 or payloads > 1:
        return unknown
    references = {"run_id": binding["run_id"], "attempt": binding["attempt"],
                  "job_id": target["id"], "failed_job_inventory": failed_inventory,
                  "regression_failure_inventory": regressions,
                  "history": retained_history}
    measurement = {"mandatory_log_payloads_materialized": payloads,
                   "model_triage_steps": 0, "automatic_retry_count": 0,
                   "token_usage": "UNKNOWN"}
    setup = (diagnostic.get("phase") == "category_preflight"
             and diagnostic.get("before_first_test") is True
             and diagnostic.get("message") == _DB_PREFLIGHT and inventory == [])
    if setup:
        return {**unknown, "classification": "SETUP_FAILURE",
                "signature_id": _KNOWN_CI_SIGNATURES[1],
                "matched_evidence": {"evidence_role": "DIAGNOSTIC", "signal": _DB_PREFLIGHT},
                "confidence_basis": "deterministic_signature",
                "recommended_action": "RUN_EXISTING_DB_PREFLIGHT",
                "diagnostic_references": references, "measurement": measurement}
    primary = [item for item in inventory if isinstance(item, dict)
               and item.get("kind") == "REGRESSION_FAILURE" and item.get("primary") is True]
    transient = (target.get("name") == "Integration (2/2)"
                 and binding.get("attempt") == 1 and len(primary) == 1
                 and primary[0].get("job_id") == target["id"]
                 and primary[0].get("job") == target["name"]
                 and primary[0].get("check") == exact["check"]
                 and primary[0].get("path") == _PR144_TEST
                 and primary[0].get("mode") == "--missing-revision"
                 and diagnostic.get("test") == _PR144_TEST
                 and diagnostic.get("mode") == "--missing-revision"
                 and diagnostic.get("phase") == "product_verifier"
                 and diagnostic.get("exception") == "JsonException"
                 and diagnostic.get("message") == "Syntax error"
                 and diagnostic.get("site") == "worker_result_json_decode"
                 and diagnostic.get("parent_failure") == "Mode --missing-revision exit")
    if transient:
        return {**unknown, "classification": "INFRA_TRANSIENT",
                "signature_id": _KNOWN_CI_SIGNATURES[0],
                "matched_evidence": {"evidence_role": "DIAGNOSTIC", "test": _PR144_TEST,
                                     "mode": "--missing-revision", "site": "worker_result_json_decode"},
                "confidence_basis": "deterministic_signature",
                "recommended_action": "SAME_SOURCE_RETRY", "retry_allowed": True,
                "retry_remaining": 1, "diagnostic_references": references,
                "measurement": measurement}
    return unknown


def _native_github_observation(helpers):
    """Collect exact native PR/run/attempt jobs; no rollup is trusted as CI proof."""
    root = helpers.ROOT
    details = helpers.source_details()
    dirty = details.get("dirty", True)
    head = details["head"]
    repository = json.loads(_run(root, ["gh", "repo", "view", "--json", "nameWithOwner"], check=True).stdout)["nameWithOwner"]
    pr = json.loads(_run(root, ["gh", "pr", "view", "--json",
                          "number,state,headRefOid,baseRefOid,url,statusCheckRollup"], check=True).stdout)
    runs = json.loads(_run(root, ["gh", "run", "list", "--workflow", "quality-graph.yml",
                           "--commit", pr.get("headRefOid"),
                           "--json", "databaseId,headSha,status,conclusion,attempt,workflowName,event"], check=True).stdout)
    matching = [item for item in runs if item.get("headSha") == pr.get("headRefOid")]
    if not matching:
        raise ValueError("no workflow run bound to PR head")
    selected = max(matching, key=lambda item: (int(item.get("databaseId", 0)), int(item.get("attempt", 0))))
    run_id = selected["databaseId"]
    run = json.loads(_run(root, ["gh", "api", f"repos/{repository}/actions/runs/{run_id}"], check=True).stdout)
    attempt = int(selected.get("attempt", 0))
    jobs = json.loads(_run(root, ["gh", "api", f"repos/{repository}/actions/runs/{run_id}/attempts/{attempt}/jobs"], check=True).stdout)
    base = pr.get("baseRefOid")
    if not base:
        pulls = run.get("pull_requests") or []
        base = pulls[0].get("base", {}).get("sha") if pulls else None
    plan = _run(root, [sys.executable, "tools/verification/ci.py", "plan", "--base", str(base or ""),
                       "--event", "pull_request"], check=True)
    mode = json.loads(plan.stdout).get("mode")
    import admission
    binding = {"repository": repository, "pr": pr.get("number"), "head": pr.get("headRefOid"),
               "base": base, "candidate": details["digest"], "mode": mode,
               "policy_digest": admission.policy_digest(root), "workflow": admission.WORKFLOW,
               "run_id": run_id, "attempt": attempt}
    ci_binding = dict(binding)
    if run.get("id") != binding["run_id"]:
        ci_binding["run_id"] = run.get("id")
    if run.get("run_attempt") != binding["attempt"]:
        ci_binding["attempt"] = run.get("run_attempt")
    if run.get("head_sha") != binding["head"]:
        ci_binding["head"] = run.get("head_sha")
    if run.get("path") != binding["workflow"]:
        ci_binding["workflow"] = run.get("path")
    if selected.get("event") != "pull_request" or run.get("event") != "pull_request":
        ci_binding["workflow"] = "INVALID_EVENT"
    run_pulls = run.get("pull_requests") or []
    if len(run_pulls) == 1:
        pull = run_pulls[0]
        if pull.get("number") != binding["pr"]:
            ci_binding["pr"] = pull.get("number")
        run_head = pull.get("head", {}).get("sha")
        run_base = pull.get("base", {}).get("sha")
        if run_head != binding["head"]:
            ci_binding["head"] = run_head
        if run_base != binding["base"]:
            ci_binding["base"] = run_base
    else:
        ci_binding["pr"] = None
    job_values = []
    for item in jobs.get("jobs", []):
        job_binding = dict(binding)
        if item.get("head_sha") != binding["head"]:
            job_binding["head"] = item.get("head_sha")
        if item.get("run_id") != binding["run_id"]:
            job_binding["run_id"] = item.get("run_id")
        if item.get("run_attempt") != binding["attempt"]:
            job_binding["attempt"] = item.get("run_attempt")
        job_values.append({"id": item.get("id"), "name": item.get("name"),
                           "check": item.get("name"), "status": str(item.get("status", "")).upper(),
                           "conclusion": str(item.get("conclusion", "")).upper() or None,
                           "binding": job_binding})
    # Materialize the complete machine failed-job inventory before selecting one
    # applicable diagnostic payload. Logs from unrelated failed jobs are never fetched.
    failed_jobs = [{"job_id": item.get("id"), "job": item.get("name"),
                    "check": item.get("name"), "conclusion": item.get("conclusion")}
                   for item in job_values if item.get("conclusion") == "FAILURE"]
    history = []
    if attempt > 1:
        for prior_attempt in range(1, attempt):
            prior = json.loads(_run(root, ["gh", "api",
                f"repos/{repository}/actions/runs/{run_id}/attempts/{prior_attempt}/jobs"],
                check=True).stdout)
            for item in prior.get("jobs", []):
                if str(item.get("conclusion", "")).upper() == "FAILURE":
                    history.append({"run_id": run_id, "attempt": prior_attempt,
                        "job_id": item.get("id"), "job": item.get("name"),
                        "check": item.get("name"),
                        "head": item.get("head_sha") or binding["head"]})
    failure_inventory = []
    diagnostics = []
    diagnostic_categories = {"Integration (1/2)", "Integration (2/2)", "e2e"}
    candidates = [item for item in failed_jobs if item.get("job") in diagnostic_categories
                  and isinstance(item.get("job_id"), int) and item["job_id"] > 0]
    if len(candidates) == 1:
        candidate = candidates[0]
        log = _run(root, ["gh", "run", "view", str(run_id), "--job",
                          str(candidate["job_id"]), "--attempt", str(attempt), "--log"],
                   check=True).stdout
        regression_paths = []
        for line in log.splitlines():
            columns = line.split("\t")
            if len(columns) == 1:
                message = columns[0]
            elif (len(columns) == 3 and columns[0] and columns[1]
                  and re.fullmatch(
                      r"\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?Z .+",
                      columns[2])):
                message = columns[2].split(" ", 1)[1]
            else:
                continue
            match = re.fullmatch(r"REGRESSION_FAILURE: ([^\s]+)", message)
            if match:
                regression_paths.append(match.group(1))
        regression = (_PR144_TEST in regression_paths and "--missing-revision" in log)
        setup = _DB_PREFLIGHT in log and "VERIFY " not in log[:log.index(_DB_PREFLIGHT)]
        for path in regression_paths:
            failure_inventory.append({"kind": "REGRESSION_FAILURE", "primary": True,
                "job_id": candidate["job_id"], "job": candidate["job"], "check": candidate["check"],
                "path": path,
                "mode": "--missing-revision" if path == _PR144_TEST and regression else None})
        diagnostic = {"source": "bounded_job_diagnostic", "job_id": candidate["job_id"],
            "job": candidate["job"], "check": candidate["check"], "run_id": run_id,
            "attempt": attempt, "head": binding["head"], "candidate_source": binding["candidate"],
            "materialized_log_payloads": 1}
        decode_stack = "inspection_item_complete_001_mariadb_test.php(21): json_decode()"
        thrown_site = re.search(
            r"thrown in [^\n]*inspection_item_complete_001_mariadb_test\.php on line 21(?:\s|$)",
            log) is not None
        if (regression and "JsonException: Syntax error" in log
                and decode_stack in log and thrown_site
                and "Mode --missing-revision exit" in log):
            diagnostic.update(test=_PR144_TEST, mode="--missing-revision", phase="product_verifier",
                              exception="JsonException", message="Syntax error",
                              site="worker_result_json_decode",
                              parent_failure="Mode --missing-revision exit")
        elif setup:
            diagnostic.update(phase="category_preflight", before_first_test=True,
                              message=_DB_PREFLIGHT)
        diagnostics.append(diagnostic)
    # I1 has no persisted preflight/review adapter yet: absence must remain blocking.
    observation = {"binding": binding, "current": {"head": head, "base": base},
                   "ci": {"binding": ci_binding, "jobs": job_values,
                          "failed_job_inventory": failed_jobs,
                          "failure_inventory": failure_inventory,
                          "diagnostics": diagnostics, "history": history},
                   "preflight": {}, "reviews": [], "authorization": None,
                   "enforcement": "ENFORCEMENT_NOT_CONFIGURED", "replay": False}
    if dirty:
        observation["current"]["head"] = "DIRTY:" + head
    return observation, pr


def _compute_state(helpers):
    root = helpers.ROOT
    checked_at = _now()
    head = _run(root, ["git", "rev-parse", "HEAD"], check=True).stdout.strip()
    dirty = bool(_run(root, ["git", "status", "--porcelain=v1", "--untracked-files=all"], check=True).stdout)
    source = _source(helpers, root)
    cache_path = _state_file(helpers)
    previous = {}
    try:
        previous = json.loads(cache_path.read_text(encoding="utf-8"))
    except (OSError, json.JSONDecodeError):
        pass
    github = {"state": "UNKNOWN", "headRefOid": "UNKNOWN", "checked_at": checked_at,
              "last_success_at": previous.get("last_success_at", "UNKNOWN")}
    ci = {"status": "UNKNOWN", "source": "UNKNOWN"}
    try:
        response = _run(root, ["gh", "pr", "view", "--json",
                               "number,state,headRefOid,mergeCommit,url,statusCheckRollup"], timeout=3)
    except (OSError, subprocess.TimeoutExpired):
        response = None
    if response is not None and response.returncode == 0:
        try:
            live = json.loads(response.stdout)
            if live.get("state") not in {"OPEN", "CLOSED", "MERGED"} or not isinstance(live.get("headRefOid"), str):
                raise ValueError("malformed GitHub state")
            github.update({key: live.get(key) for key in ("number", "state", "headRefOid", "mergeCommit", "url")})
            github["checked_at"] = checked_at
            github["last_success_at"] = checked_at
            checks = live.get("statusCheckRollup") or []
            if not dirty and live.get("headRefOid") == head:
                ci = {"status": _ci_status(checks), "source": head}
            cache_path.write_text(json.dumps({"last_success_at": checked_at}) + "\n", encoding="utf-8")
        except (OSError, TypeError, json.JSONDecodeError):
            pass
    details = helpers.source_details()
    active = _active_binding(helpers)
    if active and active.get("lifecycle"):
        refreshed_lifecycle = _requirement_freshness(root, active["lifecycle"])
        if refreshed_lifecycle != active["lifecycle"]:
            binding_path = _binding_path(helpers)
            lock_path = binding_path.with_suffix(binding_path.suffix + ".lock")
            with lock_path.open("a+", encoding="utf-8") as lock:
                fcntl.flock(lock.fileno(), fcntl.LOCK_EX)
                active = _active_binding(helpers)
                if active and active.get("lifecycle"):
                    refreshed_lifecycle = _requirement_freshness(root, active["lifecycle"])
                    if refreshed_lifecycle != active["lifecycle"]:
                        active["lifecycle"] = refreshed_lifecycle
                        _atomic_json(binding_path, active)
    state = {"source": source, "executable_source": details.get("executable_digest", source),
             "head": head, "dirty": dirty, "github": github,
             "ci": ci, "deployment": "UNKNOWN", "active_binding": active,
             "active_binding_path": str(_binding_path(helpers)),
             "admission_context": _admission_context(helpers, active) if active else None}
    state["next_action"] = ("merged; await the next owner task" if github.get("state") == "MERGED"
                            else "prepare/review exact source before publication")
    live_path = helpers.evidence_home() / "state" / ("live-" + _worktree_key(helpers) + ".json")
    live_path.write_text(json.dumps(state, ensure_ascii=False, sort_keys=True) + "\n", encoding="utf-8")
    return state


def command_admission(args, helpers):
    result = _admit(helpers, _load_observation(args.observation))
    _json(result)
    return 0 if result["merge_ready"] else 1


def command_live_admission(args, helpers):
    state = _compute_state(helpers)
    try:
        observation, pr = _native_github_observation(helpers)
        observation["admission_context"] = state.get("admission_context")
        observation["reviews"] = _admission_reviews(
            state.get("admission_context"), observation.get("binding", {}))
        result = _admit(helpers, observation)
        result.update(admission_context=state.get("admission_context"),
                      active_binding=state.get("active_binding"),
                      active_binding_path=state.get("active_binding_path"),
                      source=state.get("source"))
        result["github"] = {key: pr.get(key) for key in ("number", "state", "headRefOid", "url")}
    except (OSError, KeyError, TypeError, ValueError, json.JSONDecodeError,
            subprocess.SubprocessError) as error:
        result = {**state, "ci": {"status": "UNKNOWN", "source": "UNKNOWN",
                  "triage": _ci_triage({})}, "publication_ready": False,
                  "merge_ready": False, "action_authorized": False,
                  "enforcement": "ENFORCEMENT_NOT_CONFIGURED",
                  "reasons": ["live_github_unavailable:" + type(error).__name__]}
    _json(result)
    return 0 if args.command == "state" else (0 if result["merge_ready"] else 1)


def command_state(args, helpers):
    if getattr(args, "observation", None):
        return command_admission(args, helpers)
    return command_live_admission(args, helpers)


def _event_identity(event):
    session = str(event.get("session_id", "UNKNOWN"))
    name = str(event.get("hook_event_name", "UNKNOWN"))
    if name == "PostToolUse":
        return f"{session}:tool:{event.get('tool_use_id', 'UNKNOWN')}"
    if name in {"SubagentStart", "SubagentStop"}:
        return f"{session}:agent:{event.get('turn_id', 'UNKNOWN')}:{event.get('agent_id', 'UNKNOWN')}:{name}"
    if name == "SessionStart":
        return f"{session}:session:{name}:{event.get('source', 'UNKNOWN')}"
    return f"{session}:{event.get('turn_id', 'session')}:{name}"


def _integration_identity(root):
    digest = hashlib.sha256()
    for relative in (".codex/hooks.json", "tools/delivery/harness.py", "tools/delivery/harness_context.py"):
        path = root / relative
        digest.update(relative.encode())
        digest.update(path.read_bytes() if path.is_file() else b"MISSING")
    common = _run(root, ["git", "rev-parse", "--path-format=absolute", "--git-common-dir"], check=True).stdout.strip()
    return str(Path(common).resolve()), digest.hexdigest()


def _append_hook_event(helpers, event):
    name = str(event.get("hook_event_name", "UNKNOWN"))
    repository, fingerprint = _integration_identity(helpers.ROOT)
    payload = {"event": name, "session_id": event.get("session_id"), "observed_at": _now(),
               "repository": repository, "cwd": event.get("cwd"),
               "integration_fingerprint": fingerprint}
    for field in ("task", "run_id", "candidate"):
        if event.get(field) is not None:
            payload[field] = event[field]
    if name == "PostToolUse":
        response = event.get("tool_response", "")
        if not isinstance(response, str):
            response = json.dumps(response, ensure_ascii=False, sort_keys=True)
        payload.update(tool_name=event.get("tool_name"), tool_use_id=event.get("tool_use_id"),
                       output_bytes=len(response.encode("utf-8")))
    elif name in {"SubagentStart", "SubagentStop"}:
        payload.update(turn_id=event.get("turn_id"), agent_id=event.get("agent_id"),
                       agent_type=event.get("agent_type"))
        if name == "SubagentStop":
            message = event.get("last_assistant_message")
            if isinstance(message, str) and re.search(r"\bCHANGES_REQUESTED\b", message):
                payload["verdict"] = "CHANGES_REQUESTED"
    elif name == "SessionStart":
        payload["source"] = event.get("source")
    identity = _event_identity(event)
    helpers.append_event("hook_receipt", payload, identity=identity)
    if name == "PostToolUse":
        helpers.append_event("tool_call", payload, identity=identity + ":measurement")
    elif name == "SubagentStart":
        helpers.append_event("agent_task", payload, identity=identity.rsplit(":", 1)[0])
    elif name == "SubagentStop" and payload.get("verdict") == "CHANGES_REQUESTED":
        helpers.append_event("review_return", payload, identity=identity.rsplit(":", 1)[0] + ":return")


def _worktree_realpath(helpers):
    return str(Path(helpers.ROOT).resolve())


def _worktree_key(helpers):
    return hashlib.sha256(_worktree_realpath(helpers).encode()).hexdigest()[:20]


def _binding_path(helpers):
    return helpers.evidence_home() / "state" / ("active-binding-" + _worktree_key(helpers) + ".json")


def _active_binding(helpers):
    path = _binding_path(helpers)
    try:
        value = json.loads(path.read_text(encoding="utf-8"))
        if value.get("worktree") == _worktree_realpath(helpers):
            return value
    except (OSError, TypeError, json.JSONDecodeError):
        pass
    return None


def _atomic_json(path, value):
    path.parent.mkdir(parents=True, exist_ok=True)
    temporary = path.with_suffix(path.suffix + ".tmp-" + uuid.uuid4().hex)
    temporary.write_text(json.dumps(value, ensure_ascii=False, sort_keys=True) + "\n",
                         encoding="utf-8")
    os.replace(temporary, path)


def _plan_for_binding(active):
    data = Path(active["plan"]).read_bytes()
    value = json.loads(data)
    if not isinstance(value, dict):
        raise ValueError("verification plan must be a JSON object")
    return value, hashlib.sha256(data).hexdigest()


def _change_for_binding(root, active):
    value = json.loads(_safe_repo_path(root, active["input"]).read_text(encoding="utf-8"))
    change = value.get("change") if isinstance(value, dict) else None
    if not isinstance(change, str) or not change:
        raise ValueError("verification input change is unavailable")
    return change


def _admission_context(helpers, active=None):
    active = active or _active_binding(helpers)
    if not active:
        return None
    plan, plan_sha256 = _plan_for_binding(active)
    source = _source(helpers, helpers.ROOT)
    import admission
    binding = {"change": _change_for_binding(helpers.ROOT, active),
               "input": active["input"], "base": active["base"],
               "source": source, "candidate": source}
    expected = {**binding, "plan_sha256": plan_sha256,
                "policy_digest": admission.policy_digest(helpers.ROOT)}
    results = active.get("review_results")
    results = results if isinstance(results, list) else []
    reviews = []
    for gate in plan.get("required_reviews", []):
        gate_results = [item for item in results if isinstance(item, dict)
                        and item.get("gate") == gate]
        subject_results = [item for item in gate_results
                           if isinstance(item.get("binding"), dict)
                           and item["binding"].get("change") == binding["change"]]
        exact = [item for item in subject_results
                 if item.get("binding") == {**expected, "gate": gate,
                                             "reviewer_role": "reviewer"}]
        unique = {json.dumps(item, ensure_ascii=True, sort_keys=True,
                             separators=(",", ":")): item for item in exact}
        if len(unique) == 1:
            item = next(iter(unique.values()))
            reviews.append({**item, "status": "CURRENT",
                            "current_verdict": item.get("verdict")})
        elif subject_results:
            reviews.append({"gate": gate, "status": "STALE", "verdict": None,
                            "current_verdict": None})
        else:
            reviews.append({"gate": gate, "status": "MISSING", "verdict": None,
                            "current_verdict": None})
    return {"verification_plan": {"sha256": plan_sha256, "value": plan},
            "selected_obligations": plan.get("commands", []), "binding": binding,
            "policy": {"sha256": expected["policy_digest"]}, "reviews": reviews}


def _admission_reviews(context, native_binding):
    """Adapt current canonical results to the evaluator's existing Gate schema."""
    gate_numbers = {"gate3": 3, "final": 5}
    if not isinstance(context, dict):
        return []
    return [{"gate": gate_numbers[item["gate"]], "verdict": item.get("verdict"),
             "reviewer": item.get("reviewer"), "author": item.get("author"),
             "binding": dict(native_binding)}
            for item in context.get("reviews", [])
            if (isinstance(item, dict) and item.get("status") == "CURRENT"
                and item.get("gate") in gate_numbers)]


def command_record_review(args, helpers):
    if not args.reviewer:
        raise ValueError("reviewer identity is required")
    if not args.author:
        raise ValueError("author identity is required")
    if args.reviewer == args.author:
        raise ValueError("independent reviewer must differ from author")
    binding_path = _binding_path(helpers)
    lock_path = binding_path.with_suffix(binding_path.suffix + ".lock")
    lock_path.parent.mkdir(parents=True, exist_ok=True)
    with lock_path.open("a+", encoding="utf-8") as lock:
        fcntl.flock(lock.fileno(), fcntl.LOCK_EX)
        active = _active_binding(helpers)
        if not active:
            raise ValueError("active binding is unavailable")
        context = _admission_context(helpers, active)
        if context["binding"]["source"] != active.get("source"):
            raise ValueError("source changed since prepare")
        if args.gate not in context["verification_plan"]["value"].get("required_reviews", []):
            raise ValueError("gate is not required by the verification plan")
        record_binding = {**context["binding"],
                          "plan_sha256": context["verification_plan"]["sha256"],
                          "policy_digest": context["policy"]["sha256"],
                          "gate": args.gate, "reviewer_role": "reviewer"}
        record = {"gate": args.gate, "verdict": args.verdict,
                  "reviewer": args.reviewer, "author": args.author,
                  "reviewer_role": "reviewer", "recorded_at": _now(),
                  "binding": record_binding}
        results = active.get("review_results")
        results = results if isinstance(results, list) else []
        identity = {key: value for key, value in record.items() if key != "recorded_at"}
        existing = next((item for item in results if isinstance(item, dict)
                         and {key: value for key, value in item.items()
                              if key != "recorded_at"} == identity), None)
        if existing is None:
            active["review_results"] = [*results, record]
            _atomic_json(binding_path, active)
        else:
            record = existing
    _json(record)
    return 0


def _requirement_freshness(root, lifecycle):
    if (not isinstance(lifecycle, dict)
            or lifecycle.get("route") not in {"FAST_MAINTENANCE", "COMPACT_MAINTENANCE"}):
        return lifecycle
    result = json.loads(json.dumps(lifecycle))
    result["freshness"] = "CURRENT"
    result["freshness_reason"] = "requirement_digests_match"
    for reference in result.get("canonical_requirements", []):
        try:
            path = _safe_canonical_path(root, reference.get("path"))
            current = _sha256_bytes(path.read_bytes()) if path.is_file() else None
        except (OSError, TypeError, ValueError):
            current = None
        if current != reference.get("sha256"):
            result["freshness"] = "STALE"
            result["freshness_reason"] = ("requirement_digest_changed" if current is not None
                                          else "canonical_requirement_missing")
            result["disposition"] = "IN_PROGRESS"
            break
    return result


def _refresh_active_binding(helpers):
    binding_path = _binding_path(helpers)
    lock_path = binding_path.with_suffix(binding_path.suffix + ".lock")
    lock_path.parent.mkdir(parents=True, exist_ok=True)
    with lock_path.open("a+", encoding="utf-8") as lock:
        fcntl.flock(lock.fileno(), fcntl.LOCK_EX)
        active = _active_binding(helpers)
        if not active:
            return None
        module = _load_change_verification(helpers.ROOT)
        plan = module.build(active["base"], active["input"])
        state_dir = helpers.evidence_home() / "state"
        refreshed = state_dir / ("active-verification-plan-" + _worktree_key(helpers) + ".json")
        refreshed.write_text(module.canonical(plan), encoding="utf-8")
        if active.get("lifecycle"):
            active["lifecycle"] = _requirement_freshness(helpers.ROOT, active["lifecycle"])
        active.update(source=_source(helpers, helpers.ROOT), plan=str(refreshed),
                      contracts=sorted({item["spec_path"] for item in plan["acceptances"]}),
                      obligation_count=len(plan["commands"]),
                      verification_lane=plan.get("verification_lane", "STANDARD"),
                      required_reviews=plan.get("required_reviews", ["gate3", "final"]),
                      selected_checks=plan.get("selected_checks", []), refreshed_at=_now())
        _atomic_json(binding_path, active)
        return active


def _context(helpers, role="root"):
    root = helpers.ROOT
    goal = root / "docs/operations/current-delivery-goal.md"
    prefix = (f"FMonitor delivery context ({role}). Read {goal.relative_to(root)} and AGENTS.md. "
            "Use tools/delivery/harness.py state for exact source/PR/CI and prepare role packages. "
            "Follow planner-required reviews; preserve independent final review, WIP, authorization "
            "and history. Full logs/evidence "
            "remain outside the checkout; UNKNOWN is not approval or GREEN.")
    try:
        active = _refresh_active_binding(helpers)
    except (OSError, TypeError, ValueError, subprocess.SubprocessError) as error:
        active = _active_binding(helpers)
        prefix += f" ACTIVE_PLAN_REFRESH_FAILED={type(error).__name__}."
    if not active:
        return prefix + " ROOT_SCOPE_REQUIRED: bind the owner's issue to the applicable compact task or OpenSpec verification input before implementation."
    compact = active.get("lifecycle", {}).get("route") == "COMPACT_MAINTENANCE"
    single_review = (active.get("required_reviews") == ["final"] and role == "reviewer")
    role_route = {"reviewer": ("Reviewer performs the single independent final review of test sensitivity, diff, lifecycle classification, RED-to-GREEN evidence and selected checks; preparation is not approval."
                                if single_review else
                                "Reviewer independently checks the supplied gate/source/evidence and returns a verdict; preparation is not approval."),
                  "executor": ("The single compact-maintenance author writes the regression and implementation inside the bound scope and records focused verification through harness run."
                               if compact else "Executor changes only the bound scope and records focused verification through harness run."),
                  "root": ("Root binds the compact task record, confirms sensitivity and dispatches its independent final review."
                           if compact else "Root resolves scope/spec/tests and dispatches the planner-required author/reviews.")}.get(role, "Use only this role's bounded package.")
    return (prefix + " " + role_route + f" Active binding: input={active['input']}; base={active['base']}; "
            f"contracts={','.join(active.get('contracts', []))}; source_at_prepare={active['source']}; "
            f"obligations={active.get('obligation_count', 'UNKNOWN')}; plan={active['plan']}; package={active['package_path']}.")


def _task_prompt(prompt):
    lowered = prompt.casefold()
    patterns = (r"\bреализ", r"\bпродолж", r"\bimplement\b", r"\bcontinue\b")
    return any(re.search(pattern, lowered) for pattern in patterns)


def command_hook(args, helpers):
    try:
        event = json.load(sys.stdin)
    except (json.JSONDecodeError, TypeError) as error:
        print(f"invalid hook input: {error}", file=sys.stderr)
        return 1
    if not isinstance(event, dict) or not isinstance(event.get("hook_event_name"), str):
        print("invalid hook input", file=sys.stderr)
        return 1
    _append_hook_event(helpers, event)
    name = event["hook_event_name"]
    if name == "SessionStart":
        state = _compute_state(helpers)
        try:
            observation, pr = _native_github_observation(helpers)
            ci_status = _admit(helpers, observation)["ci"]["status"]
            pr_state = pr.get("state", "UNKNOWN")
        except (OSError, KeyError, TypeError, ValueError, json.JSONDecodeError,
                subprocess.SubprocessError):
            ci_status = "UNKNOWN"
            pr_state = state["github"]["state"]
        summary = (f" Exact state: head={state['head']}; source={state['source']}; dirty={state['dirty']}; "
                   f"PR={pr_state}; CI={ci_status}; next={state['next_action']}.")
        output = {"hookSpecificOutput": {"hookEventName": name,
                  "additionalContext": _context(helpers) + summary}}
        _json(output)
    elif name == "UserPromptSubmit" and _task_prompt(str(event.get("prompt", ""))):
        text = _context(helpers) + " This is a delivery task: route it through the applicable Gate and prepared package."
        _json({"hookSpecificOutput": {"hookEventName": name, "additionalContext": text}})
    elif name == "SubagentStart":
        role = str(event.get("agent_type") or "executor")
        _json({"hookSpecificOutput": {"hookEventName": name, "additionalContext": _context(helpers, role)}})
    elif name == "SubagentStop":
        _json({})
    return 0


def command_doctor(args, helpers):
    config = helpers.ROOT / ".codex/hooks.json"
    configured = False
    limitations = ["Hook inputs expose no supported token telemetry; token counters remain UNKNOWN.",
                   "Hosted tools are outside PostToolUse coverage."]
    try:
        value = json.loads(config.read_text(encoding="utf-8"))
        configured = all(name in value.get("hooks", {}) for name in
                         ("SessionStart", "UserPromptSubmit", "PostToolUse", "SubagentStart", "SubagentStop"))
    except (OSError, TypeError, json.JSONDecodeError):
        limitations.append("Repository hook configuration is missing or invalid.")
    loader = getattr(helpers, "load_unique_events", None) or helpers.load_events
    events = loader()
    repository, fingerprint = _integration_identity(helpers.ROOT)
    observed = sorted({item.get("event") for item in events
                       if item.get("kind") == "hook_receipt" and item.get("event")
                       and item.get("repository") == repository
                       and item.get("integration_fingerprint") == fingerprint})
    required = {"SessionStart", "UserPromptSubmit"}
    user_config = Path.home() / ".codex/hooks.json"
    user_installed = False
    try:
        installed = json.loads(user_config.read_text(encoding="utf-8"))
        user_installed = any("hook-dispatcher.py" in handler.get("command", "")
                             for groups in installed.get("hooks", {}).values() for group in groups
                             for handler in group.get("hooks", []) if isinstance(handler, dict))
    except (OSError, TypeError, json.JSONDecodeError):
        pass
    integration = "OBSERVED" if user_installed and required <= set(observed) else ("CONFIGURED" if configured or user_installed else "MISSING")
    if integration != "OBSERVED":
        limitations.append("Actual Codex startup/task routing has not both been observed in local receipts.")
    _json({"integration": integration, "config_path": str(config), "configured": configured,
           "user_config_path": str(user_config), "user_installed": user_installed,
           "repository": repository, "integration_fingerprint": fingerprint,
           "observed_events": observed, "limitations": limitations,
           "token_telemetry": {"status": "UNKNOWN", "source": "unsupported_hook_interface"}})
    return 0


_DISPATCHER = r'''#!/usr/bin/env python3
"""Dispatch FMonitor hooks only inside explicitly registered Git repositories."""
import json
import os
from pathlib import Path
import subprocess
import sys

def git(cwd, *args):
    result = subprocess.run(["git", *args], cwd=cwd, text=True, capture_output=True)
    return result.stdout.strip() if result.returncode == 0 else None

def main():
    raw = sys.stdin.read()
    try:
        event = json.loads(raw)
        cwd = Path(event["cwd"]).expanduser().resolve()
        registry = json.loads(Path(__file__).with_name("dispatcher-registry.json").read_text())
    except (OSError, KeyError, TypeError, ValueError, json.JSONDecodeError):
        return 0
    top = git(cwd, "rev-parse", "--show-toplevel")
    common = git(cwd, "rev-parse", "--path-format=absolute", "--git-common-dir")
    if not top or not common or str(Path(common).resolve()) not in registry.get("git_common_dirs", []):
        return 0
    harness = Path(top).resolve() / "tools/delivery/harness.py"
    if not harness.is_file():
        name = event.get("hook_event_name")
        if name in {"SessionStart", "UserPromptSubmit", "SubagentStart"}:
            print(json.dumps({"hookSpecificOutput": {"hookEventName": name,
                  "additionalContext": "FMonitor delivery harness is registered but unavailable in this checkout; stop delivery and restore the harness before continuing."}}))
        elif name == "SubagentStop":
            print("{}")
        return 0
    result = subprocess.run(["/usr/bin/python3", str(harness), "hook"], input=raw, text=True)
    return result.returncode

if __name__ == "__main__":
    raise SystemExit(main())
'''


def _atomic_text(path, text, mode=0o600):
    path.parent.mkdir(parents=True, exist_ok=True)
    temporary = path.with_suffix(path.suffix + ".tmp-" + uuid.uuid4().hex)
    descriptor = os.open(temporary, os.O_WRONLY | os.O_CREAT | os.O_EXCL, mode)
    with os.fdopen(descriptor, "w", encoding="utf-8") as stream:
        stream.write(text)
    os.replace(temporary, path)
    os.chmod(path, mode)


def _strict_json_bytes(raw):
    def unique(pairs):
        value = {}
        for key, item in pairs:
            if key in value:
                raise ValueError(f"duplicate JSON key: {key}")
            value[key] = item
        return value
    return json.loads(raw, object_pairs_hook=unique)


def command_install(args, helpers):
    config = (Path(args.config).expanduser() if args.config else Path.home() / ".codex/hooks.json").resolve()
    if config.exists():
        original = config.read_bytes()
        try:
            value = _strict_json_bytes(original)
        except (UnicodeDecodeError, json.JSONDecodeError) as error:
            raise ValueError(f"invalid existing hooks config: {error}") from error
        if not isinstance(value, dict) or not isinstance(value.get("hooks"), dict):
            raise ValueError("invalid existing hooks config structure")
    else:
        original = None
        value = {"hooks": {}}

    home = helpers.evidence_home()
    dispatcher = home / "hook-dispatcher.py"
    registry = home / "dispatcher-registry.json"
    common = _run(helpers.ROOT, ["git", "rev-parse", "--path-format=absolute", "--git-common-dir"], check=True).stdout.strip()
    common = str(Path(common).resolve())
    registered = []
    if registry.exists():
        try:
            current = json.loads(registry.read_text(encoding="utf-8"))
            registered = current.get("git_common_dirs", [])
        except (OSError, TypeError, json.JSONDecodeError):
            raise ValueError("invalid dispatcher registry")
    registered = sorted(set(registered) | {common})
    _atomic_text(dispatcher, _DISPATCHER, 0o700)
    _atomic_text(registry, json.dumps({"version": 1, "git_common_dirs": registered}, sort_keys=True) + "\n")

    definition = json.loads((helpers.ROOT / ".codex/hooks.json").read_text(encoding="utf-8"))
    command = f"/usr/bin/python3 {shlex.quote(str(dispatcher))}"
    hooks = value["hooks"]
    changed = original is None
    for event, groups in definition["hooks"].items():
        destination = hooks.setdefault(event, [])
        own = []
        for group in groups:
            clone = json.loads(json.dumps(group))
            for handler in clone["hooks"]:
                handler["command"] = command
                handler.pop("statusMessage", None)
            own.append(clone)
        filtered = []
        for group in destination:
            if not isinstance(group, dict) or not isinstance(group.get("hooks"), list):
                raise ValueError(f"invalid existing {event} hook group")
            retained = [handler for handler in group["hooks"]
                        if not isinstance(handler, dict) or handler.get("command") != command]
            if retained:
                filtered.append({**group, "hooks": retained})
        replacement = filtered + own
        if replacement != destination:
            hooks[event] = replacement
            changed = True
    rendered = json.dumps(value, ensure_ascii=False, sort_keys=True, indent=2) + "\n"
    if changed or original is None:
        _atomic_text(config, rendered)
    _json({"config": str(config), "dispatcher": str(dispatcher), "registry": str(registry),
           "git_common_dir": common, "changed": changed})
    return 0


def _load_change_verification(root):
    path = root / "tools/delivery/change-verification.py"
    spec = importlib.util.spec_from_file_location("fmonitor_change_verification", path)
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


def _normalized_argv(argv):
    if not argv:
        return tuple()
    first = argv[0]
    trusted_interpreters = {"python", "python3", sys.executable,
                            str(Path(sys.executable).resolve())}
    repository_wrapper = Path(__file__).resolve().parents[2] / "tools/delivery/run-in-profile"
    trusted_wrappers = {"tools/delivery/run-in-profile", "./tools/delivery/run-in-profile",
                        str(repository_wrapper)}
    if first in trusted_interpreters:
        first = "python3"
    elif first in trusted_wrappers:
        first = "tools/delivery/run-in-profile"
    return (first, *argv[1:])


def _mapped_commands(plan):
    return {_normalized_argv(item["argv"]) for item in plan.get("commands", [])
            if "acceptance mapping" in item.get("rationales", [item.get("rationale")])}


def _gate_expectations(plan, gate):
    if gate == "5":
        return {command: "GREEN" for command in _mapped_commands(plan)}
    by_test = {}
    for acceptance in plan.get("acceptances", []):
        declared = acceptance.get("gate3_expected")
        for test in acceptance.get("tests", []):
            by_test[test] = declared[test] if declared is not None else "INTENDED_RED"
    return {_normalized_argv(item["argv"]): by_test[item["argv"][-1]]
            for item in plan.get("commands", [])
            if "acceptance mapping" in item.get("rationales", [item.get("rationale")])}


def _validate_evidence(path, source, expectations, plan_commands=None, executable_source=None,
                       reachability=None, acceptance_by_command=None):
    try:
        record = json.loads(path.read_text(encoding="utf-8"))
    except (OSError, json.JSONDecodeError) as error:
        raise ValueError(f"evidence is unreadable: {error}") from error
    if (record.get("source") != source
            or (executable_source is not None
                and record.get("executable_source") != executable_source)):
        raise ValueError("evidence source does not match current source")
    try:
        current_environment = _helpers().environment_identity()["digest"]
    except (AttributeError, TypeError):
        current_environment = None
    if current_environment is not None and record.get("environment") != current_environment:
        raise ValueError("evidence environment does not match current environment")
    argv = _normalized_argv(record.get("argv", []))
    expected = expectations.get(argv)
    plan_command = (plan_commands or {}).get(argv)
    reachability_declaration = (reachability or {}).get(argv)
    is_reachability = record.get("outcome") == "FIXTURE_REACHABLE"
    if is_reachability:
        if reachability_declaration is None:
            raise ValueError("fixture reachability evidence is not declared for this command")
        expected = "FIXTURE_REACHABLE"
    if expected is None and plan_command is not None:
        expected = "GREEN"
    if expected is None:
        raise ValueError("evidence does not cover a plan-owned command")
    if plan_command is not None:
        if record.get("command_id") != plan_command.get("id"):
            raise ValueError("evidence command identity does not match plan")
        if record.get("purpose") != plan_command.get("purpose"):
            raise ValueError("evidence purpose does not match plan")
        if record.get("command_environment") != plan_command.get("environment"):
            raise ValueError("evidence command environment does not match plan")
    acceptance_id = (acceptance_by_command or {}).get(argv)
    if (acceptance_id is not None and plan_command is not None and plan_command.get("id") is not None
            and record.get("acceptance_id") != acceptance_id):
        raise ValueError("evidence acceptance identity does not match plan")
    if is_reachability:
        if (record.get("fixture_reachability_boundary") != reachability_declaration["boundary"]
                or record.get("fixture_reachability_probe_kind") != "fixture_read_only"):
            raise ValueError("fixture reachability boundary does not match plan")
    if record.get("outcome") != expected:
        raise ValueError(f"evidence outcome must be {expected} for this mapped acceptance")
    return {"record": str(path), "argv": record["argv"], "outcome": record["outcome"],
            "source": record.get("source"), "executable_source": record.get("executable_source"),
            "purpose": record.get("purpose"), "command_id": record.get("command_id"),
            "command_environment": record.get("command_environment"),
            "acceptance_id": record.get("acceptance_id"),
            "command_blob": record.get("command_blob"),
            "environment": record.get("environment"),
            "fixture_reachability_boundary": record.get("fixture_reachability_boundary"),
            "fixture_reachability_probe_kind": record.get("fixture_reachability_probe_kind")}


def _dependency_workspaces(args, helpers, plan):
    requested = list(getattr(args, "dependency_workspace", None) or [])
    if sorted(requested) != sorted(plan.get("dependency_workspaces", [])):
        raise ValueError("dependency workspace arguments do not match verification input")
    result = []
    for value in requested:
        lexical = Path(value).expanduser()
        if lexical.is_symlink():
            raise ValueError("dependency workspace manifest cannot be a symlink")
        path = lexical.resolve()
        manifest = json.loads(path.read_text(encoding="utf-8"))
        identity = manifest.get("identity")
        if not isinstance(identity, str) or not re.fullmatch(r"sha256:[0-9a-f]{64}", identity):
            raise ValueError("dependency workspace identity is required")
        root = Path(manifest.get("root", ""))
        allowed_root = Path(manifest.get("allowed_root", ""))
        lock = Path(manifest.get("lock", ""))
        consumers = manifest.get("consumers")
        if (not root.is_absolute() or not root.is_dir() or root.is_symlink()
                or not allowed_root.is_absolute() or not allowed_root.is_dir()
                or allowed_root.is_symlink() or not lock.is_absolute() or not lock.is_file()
                or lock.is_symlink()):
            raise ValueError("dependency workspace root is unavailable or unsafe")
        try:
            root.resolve().relative_to(allowed_root.resolve())
            lock.resolve().relative_to(allowed_root.resolve())
        except ValueError as error:
            raise ValueError("dependency workspace escapes allowed root") from error
        plan_consumers = {item["argv"][-1] for item in plan.get("commands", [])}
        if (not isinstance(consumers, list) or not consumers
                or any(item not in plan_consumers for item in consumers)):
            raise ValueError("dependency workspace consumers are unavailable")
        derived_identity = "sha256:" + hashlib.sha256(lock.read_bytes()).hexdigest()
        if identity != derived_identity:
            raise ValueError("dependency workspace lock identity mismatch")
        content = helpers.fixture_identity(str(root))
        state = helpers.evidence_home() / "state" / ("workspace-" + hashlib.sha256(str(path).encode()).hexdigest() + ".json")
        if state.exists():
            previous = json.loads(state.read_text())
            if previous != {"identity": identity, "content": content}:
                raise ValueError("dependency workspace immutable identity changed")
        else:
            state.parent.mkdir(parents=True, exist_ok=True)
            state.write_text(json.dumps({"identity": identity, "content": content}) + "\n")
        result.append({"manifest": str(path), "root": str(root.resolve()),
                       "allowed_root": str(allowed_root.resolve()), "lock": str(lock.resolve()), "identity": identity,
                       "content": content, "consumers": sorted(consumers)})
    return result


def _snapshot(root, output):
    result = _run(root, [sys.executable, "tools/delivery/review-source.py", "capture",
                         "--repo", str(root), "--output", str(output)])
    if result.returncode:
        raise ValueError(result.stderr.strip() or "review snapshot failed")
    return json.loads(result.stdout)


_CONTEXT_COMMON = ("instruction.constitution", "delivery.workflow", "current.actionable-goal")
_CONTEXT_PROFILES = {
    "ui": _CONTEXT_COMMON + ("ui.presentation",),
    "persistence": _CONTEXT_COMMON + ("product.core", "product.context", "pilot.behavior",
        "pilot.data-model", "persistence.current-state", "domain.state-history", "security.authorization"),
    "auth": _CONTEXT_COMMON + ("product.core", "product.context", "pilot.behavior",
        "security.authorization", "security.session-csrf-secrets"),
    "harness": _CONTEXT_COMMON + ("delivery.verification-governance",),
}
_CONTEXT_CONSERVATIVE = ("AGENTS.md", "docs/development-process.md",
    "docs/operations/current-delivery-goal.md", "PRODUCT.md", "CONTEXT.md",
    "docs/fmonitor-2-pilot-spec.md", "docs/fmonitor-2-pilot-data-model.md")


def _sha256_bytes(value):
    return hashlib.sha256(value).hexdigest()


def _context_profile(paths):
    meaningful = []
    for path in paths:
        if (path.startswith(("openspec/", "specs/", "reviews/", "docs/operations/"))
                or path == "tools/verification/suites.tsv" or path.startswith("tests/")):
            continue
        meaningful.append(path)
    if not meaningful:
        return "conservative"
    if any(path.startswith(("app/InstallationProcess/", "app/AssignmentOrderComposition/",
                            "app/InspectionEvidence/")) for path in meaningful):
        return "persistence"
    harness_files = {"Makefile", ".quality-graph/verification-policy.json", ".github/workflows/quality-graph.yml"}
    if all(path in harness_files or path.startswith(("tools/delivery/", "tools/verification/"))
           for path in meaningful):
        return "harness"
    if all(path.startswith("app/YiiRuntime/") for path in meaningful):
        return "ui"
    if all(path.startswith(("app/IdentityAccess/", "app/PilotHttp/", "config/yii/"))
           for path in meaningful):
        return "auth"
    return "conservative"


def _section_bounds(source_bytes, heading, end_heading=None):
    marker = heading.encode("utf-8") + b"\n"
    if source_bytes.count(marker) != 1:
        raise ValueError("section heading is absent or ambiguous")
    start = source_bytes.index(marker)
    if end_heading is None:
        return start, len(source_bytes)
    end_marker = end_heading.encode("utf-8") + b"\n"
    if source_bytes.count(end_marker) != 1:
        raise ValueError("section end heading is absent or ambiguous")
    end = source_bytes.index(end_marker)
    if end <= start:
        raise ValueError("section end precedes section start")
    return start, end


def build_task_context(root, plan_value, *, role, source, base, contracts, evidence, snapshot):
    """Build a deterministic index and exact materialization from canonical bytes."""
    index_path = root / "tools/delivery/context-sections.json"
    index_bytes = index_path.read_bytes()
    index = json.loads(index_bytes)
    sections = {item["rule_id"]: item for item in index["sections"]}
    paths = plan_value.get("paths", {}).get("planned", [])
    profile = _context_profile(paths)
    required = []
    materialized = []

    def add_full(relative, reason):
        path = root / relative
        if not path.is_file():
            return
        content = path.read_bytes()
        digest = _sha256_bytes(content)
        required.append({"rule_id": "FULL_DOCUMENT", "source": relative,
            "reason": reason, "load_mode": "required_reference", "digest": digest,
            "source_digest": digest,
            "content_reference": {"start_byte": 0, "end_byte": len(content)}})
        materialized.append({"rule_id": "FULL_DOCUMENT", "source": relative,
                             "content": content.decode("utf-8")})

    if profile == "conservative":
        for relative in _CONTEXT_CONSERVATIVE:
            add_full(relative, "unknown boundary: conservative full canonical source")
    else:
        fallback_sources = set()
        extracted = []
        for rule_id in _CONTEXT_PROFILES[profile]:
            locator = sections[rule_id]
            relative = locator["source"]
            content = (root / relative).read_bytes()
            try:
                if locator.get("whole_source") is True:
                    start, end = 0, len(content)
                else:
                    start, end = _section_bounds(content, locator["heading"], locator.get("end_heading"))
            except ValueError:
                fallback_sources.add(relative)
                continue
            extracted.append((rule_id, relative, content, start, end, content[start:end]))
        for rule_id, relative, content, start, end, excerpt in extracted:
            if relative in fallback_sources:
                continue
            required.append({"rule_id": rule_id, "source": relative,
                "reason": f"{profile} boundary profile", "load_mode": "inline",
                "digest": _sha256_bytes(excerpt), "source_digest": _sha256_bytes(content),
                "content_reference": {"start_byte": start, "end_byte": end}})
            materialized.append({"rule_id": rule_id, "source": relative,
                                 "content": excerpt.decode("utf-8")})
        for relative in sorted(fallback_sources):
            add_full(relative, "invalid section locator: full canonical source fallback")

    required.sort(key=lambda item: (item["source"], item["rule_id"]))
    materialized.sort(key=lambda item: (item["source"], item["rule_id"]))
    historical = []
    history_groups = []
    for pattern in ("docs/operations/current-delivery-goal-history*", "docs/operations/*evidence*", "reviews/**/*.md"):
        group = []
        for path in sorted(root.glob(pattern)):
            relative = path.relative_to(root).as_posix()
            if not path.is_file() or relative in contracts:
                continue
            content = path.read_bytes()
            group.append({"source": relative, "digest": _sha256_bytes(content)})
        history_groups.append((pattern, group))
        historical.extend(group)
    if len(historical) <= 20:
        load_on_demand = [{"source": item["source"],
            "reason": "historical context; open on demand", "digest": item["digest"],
            "content_reference": {"path": item["source"]}} for item in historical]
    else:
        load_on_demand = []
        for pattern, group in history_groups:
            if not group:
                continue
            collection_index_bytes = json.dumps(group, ensure_ascii=False, sort_keys=True,
                                                separators=(",", ":")).encode("utf-8")
            load_on_demand.append({"source": pattern,
                "reason": "historical collection; reconstruct index from current canonical tree on demand",
                "digest": _sha256_bytes(collection_index_bytes),
                "content_reference": {"path": pattern.split("*")[0].rstrip("/"),
                                      "pattern": pattern, "entries": len(group)}})
    references = []
    for relative in contracts:
        path = root / relative
        if path.is_file():
            references.append({"source": relative, "digest": _sha256_bytes(path.read_bytes())})
    contract_stems = {Path(relative).stem for relative in contracts}
    reviews = sorted(path.relative_to(root).as_posix() for path in root.glob("reviews/**/*.md")
        if path.is_file() and (path.stem == "CURRENT" or path.stem in contract_stems))
    manifest = {
        "schema": "fmonitor-task-context-v1",
        "task": {"change": plan_value.get("change"), "base": base, "source": source, "role": role,
                 "policy_digests": {"instruction_index": _sha256_bytes(index_bytes)}},
        "applicability": {"mode": profile, "boundaries": sorted(paths)},
        "section_index": {"version": index["version"], "digest": _sha256_bytes(index_bytes),
                          "source": "tools/delivery/context-sections.json"},
        "required_context": required,
        "load_on_demand": sorted(load_on_demand, key=lambda item: item["source"]),
        "product_spec": {"contracts": contracts, "references": references},
        "verification": {"candidate_source": source,
                         "snapshot": snapshot if role == "reviewer" else "candidate-source:" + source,
                         "evidence": evidence,
                         "plan_digest": _sha256_bytes(json.dumps(plan_value, ensure_ascii=False, sort_keys=True, separators=(",", ":")).encode()),
                         "reviews": reviews},
    }
    return manifest, {"schema": "fmonitor-required-context-v1", "items": materialized}


def _delta(root, previous, current, output):
    with tempfile.TemporaryDirectory(prefix="harness-delta-") as directory:
        old = Path(directory) / "old"
        new = Path(directory) / "new"
        try:
            trees = []
            for snapshot, target in ((previous, old), (current, new)):
                result = _run(root, [sys.executable, "tools/delivery/review-source.py", "restore",
                                     "--snapshot", str(snapshot), "--output", str(target)])
                if result.returncode:
                    raise ValueError(result.stderr.strip() or "snapshot restore failed")
                trees.append(_run(target, ["git", "write-tree"], check=True).stdout.strip())
            diff = _run(root, ["git", "diff", "--binary", "--full-index", trees[0], trees[1], "--"], check=True)
            output.write_text(diff.stdout, encoding="utf-8")
        finally:
            for target in (old, new):
                subprocess.run(["git", "-C", str(root), "worktree", "remove", "--force", str(target)],
                               capture_output=True)


def _check_all_whitespace(root):
    descriptor, index_name = tempfile.mkstemp(prefix="harness-whitespace-index-")
    os.close(descriptor)
    Path(index_name).unlink(missing_ok=True)
    environment = os.environ.copy()
    environment["GIT_INDEX_FILE"] = index_name
    try:
        for argv in (["git", "read-tree", "HEAD"], ["git", "add", "-A", "--", "."]):
            result = subprocess.run(argv, cwd=root, text=True, capture_output=True, env=environment)
            if result.returncode:
                raise ValueError(result.stderr.strip() or "whitespace preflight setup failed")
        result = subprocess.run(["git", "diff", "--cached", "--check"], cwd=root,
                                text=True, capture_output=True, env=environment)
        if result.returncode:
            raise ValueError("whitespace errors must be fixed before snapshot: " + result.stdout.strip())
    finally:
        Path(index_name).unlink(missing_ok=True)


def _issue183_transition_authorized(root, change, plan, input_name):
    expected = {
        "base": "1245b44523f258294de4ac949e139dc2af26e07e",
        "input": "docs/operations/issue-183-delivery.json",
        "change": "issue-183-compact-maintenance",
        "requirement": "tests/fixtures/delivery/issue-183-transition-authorization.txt",
        "requirement_sha256": "c185c529f8ff98d189bad041a7c6846ce4a7c1d1b042a059b6ccd9182d1699bd",
        "token": "OWNER_2026-09-17_ISSUE_183_NO_GATE3",
    }
    lifecycle = change.get("lifecycle", {})
    references = lifecycle.get("canonical_requirements")
    if not (lifecycle.get("issue") == "#183"
            and lifecycle.get("transition_authorization") == expected["token"]
            and change.get("change") == expected["change"]
            and plan.get("base") == expected["base"]
            and input_name == expected["input"]
            and references == [{"path": expected["requirement"],
                                 "sha256": expected["requirement_sha256"]}]):
        return False
    try:
        requirement = _safe_canonical_path(root, expected["requirement"])
        return (_sha256_bytes(requirement.read_bytes()) == expected["requirement_sha256"])
    except (OSError, TypeError, ValueError):
        return False


def _fast_maintenance_lifecycle(root, plan, input_name, source, base, plan_path, evidence):
    """Project a fail-closed lifecycle decision after the authoritative planner result."""
    try:
        change = json.loads(_safe_repo_path(root, input_name).read_text(encoding="utf-8"))
    except (OSError, TypeError, ValueError, json.JSONDecodeError):
        change = {}
    declaration = change.get("lifecycle")
    if not isinstance(declaration, dict):
        declaration = {}

    route = "OPENSPEC_REQUIRED"
    reason = "lifecycle_intent_not_fast_maintenance"
    references = []
    regression = declaration.get("executable_regression")
    requirement_status = declaration.get("requirement_status")
    mapped_tests = {test for acceptance in plan.get("acceptances", [])
                    for test in acceptance.get("tests", [])}

    compact = declaration.get("intent") == "COMPACT_MAINTENANCE"
    sensitivity = declaration.get("sensitivity") if compact else None
    if compact:
        required = {"issue", "change_kind", "requirement_status", "semantic_change",
                    "canonical_requirements", "executable_regression"}
        change_kind = declaration.get("change_kind")
        semantic_change = declaration.get("semantic_change")
        valid_change = ((change_kind == "BOUNDED_FIX"
                         and semantic_change == "ESTABLISHED_BEHAVIOR_FIX")
                        or (change_kind in {"PRESENTATION", "READ", "APPLICATION_TEST_OR_REFACTOR"}
                            and semantic_change == "AGREED_ORDINARY_CHANGE"))
        valid = (required <= set(declaration)
                 and valid_change
                 and declaration.get("requirement_status") == "CURRENT"
                 and isinstance(declaration.get("issue"), str) and declaration["issue"].strip()
                 and isinstance(sensitivity, dict)
                 and set(sensitivity) == {"classification", "boundaries", "rationale"}
                 and sensitivity.get("classification") in {"ORDINARY", "SENSITIVE"}
                 and isinstance(sensitivity.get("boundaries"), list)
                 and isinstance(sensitivity.get("rationale"), str)
                 and sensitivity["rationale"].strip()
                 and regression in mapped_tests)
        if not valid:
            reason = "compact_declaration_invalid"
        elif "gate3" in plan.get("required_reviews", []):
            route = "PRELIMINARY_REVIEW_REQUIRED"
            reason = ("declared_sensitive_semantics"
                      if sensitivity["classification"] == "SENSITIVE"
                      else "known_sensitive_boundary")
        elif ((sensitivity["classification"] == "SENSITIVE"
               or declaration.get("transition_authorization") is not None)
              and not _issue183_transition_authorized(root, change, plan, input_name)):
            route = "PRELIMINARY_REVIEW_REQUIRED"
            reason = "transition_authorization_not_exact"
        else:
            route = "COMPACT_MAINTENANCE"
            reason = ("owner_authorized_issue_183_transition"
                      if _issue183_transition_authorized(root, change, plan, input_name)
                      else "eligible_ordinary_change"
                      if change_kind in {"PRESENTATION", "READ", "APPLICATION_TEST_OR_REFACTOR"}
                      else "eligible_bounded_fix")
    elif plan.get("verification_lane") != "FAST":
        reason = "planner_not_fast"
    elif declaration.get("intent") != "FAST_MAINTENANCE":
        reason = "lifecycle_intent_not_fast_maintenance"
    elif declaration.get("semantic_change") is not False:
        reason = "semantic_change_not_false"
    elif requirement_status == "OWNER_DECISION_REQUIRED":
        reason = "NEEDS_OWNER:owner_decision_required"
    elif requirement_status == "CONFLICTING":
        reason = "NEEDS_OWNER:conflicting_requirements"
    elif requirement_status != "CURRENT":
        reason = "canonical_requirement_status_unknown"
    elif not isinstance(declaration.get("issue"), str) or not declaration["issue"].strip():
        reason = "maintenance_issue_missing"
    elif regression not in mapped_tests:
        reason = "executable_regression_not_mapped"
    elif not compact:
        raw_references = declaration.get("canonical_requirements")
        if not isinstance(raw_references, list) or not raw_references:
            reason = "canonical_requirement_missing"
        else:
            reason = None
            seen = set()
            for item in raw_references:
                path_value = item.get("path") if isinstance(item, dict) else None
                try:
                    path = _safe_canonical_path(root, path_value)
                except (TypeError, ValueError):
                    reason = "canonical_requirement_invalid"
                    break
                if (path_value in seen or not path.is_file()
                        or not (path_value in {"PRODUCT.md", "CONTEXT.md"}
                                or path_value.startswith(("specs/", "openspec/specs/", "openspec/changes/")))):
                    reason = ("canonical_requirement_missing" if not path.is_file()
                              else "canonical_requirement_invalid")
                    break
                sha256 = _sha256_bytes(path.read_bytes())
                if item.get("sha256") not in (None, sha256):
                    reason = "canonical_requirement_digest_mismatch"
                    break
                seen.add(path_value)
                references.append({"path": path_value, "sha256": sha256})
            if reason is None:
                route = "FAST_MAINTENANCE"
                reason = "eligible_planner_fast_existing_requirement"

    if route in {"COMPACT_MAINTENANCE", "PRELIMINARY_REVIEW_REQUIRED"}:
        raw_references = declaration.get("canonical_requirements")
        if not isinstance(raw_references, list) or not raw_references:
            route, reason = "OPENSPEC_REQUIRED", "canonical_requirement_missing"
        else:
            seen = set()
            for item in raw_references:
                path_value = item.get("path") if isinstance(item, dict) else None
                try:
                    path = _safe_canonical_path(root, path_value)
                except (TypeError, ValueError):
                    route, reason = "OPENSPEC_REQUIRED", "canonical_requirement_invalid"
                    break
                if path_value in seen or not path.is_file():
                    route, reason = "OPENSPEC_REQUIRED", "canonical_requirement_missing"
                    break
                sha256 = _sha256_bytes(path.read_bytes())
                if item.get("sha256") not in (None, sha256):
                    route, reason = "OPENSPEC_REQUIRED", "canonical_requirement_digest_mismatch"
                    break
                seen.add(path_value)
                references.append({"path": path_value, "sha256": sha256})

    intended_red = any(item.get("outcome") == "INTENDED_RED"
                       and _normalized_argv(item.get("argv", []))[-1:] == (regression,)
                       for item in evidence)
    lifecycle = {
        "route": route,
        "reason": reason,
        "issue": declaration.get("issue", "UNKNOWN"),
        "base": plan.get("base", base),
        "exact_source": source,
        "fast_class": plan.get("fast_class", "UNKNOWN"),
        "fast_reason": plan.get("fast_reason", "UNKNOWN"),
        "semantic_change": declaration.get("semantic_change", "UNKNOWN"),
        "change_kind": declaration.get("change_kind", "UNKNOWN"),
        "canonical_requirements": references,
        "executable_regression": regression or "UNKNOWN",
        "authorship": "single_author" if route == "COMPACT_MAINTENANCE" else "separate_executor",
        "sensitivity": sensitivity or {"classification": "UNKNOWN", "boundaries": [], "rationale": "UNKNOWN"},
        "executable_red": {"required": route in {"FAST_MAINTENANCE", "COMPACT_MAINTENANCE"},
                           "status": "INTENDED_RED" if intended_red else "PENDING"},
        "verification_plan": {"path": str(plan_path),
                              "sha256": _sha256_bytes(plan_path.read_bytes())},
        "final_review": {"required": route in {"FAST_MAINTENANCE", "COMPACT_MAINTENANCE"}, "status": "PENDING"},
        "ci": {"required": route in {"FAST_MAINTENANCE", "COMPACT_MAINTENANCE"}, "status": "UNKNOWN"},
        "disposition": "IN_PROGRESS" if route in {"FAST_MAINTENANCE", "COMPACT_MAINTENANCE"} else reason,
        "freshness": "CURRENT",
        "freshness_reason": ("requirement_digests_match"
                             if route in {"FAST_MAINTENANCE", "COMPACT_MAINTENANCE"} else "not_applicable"),
    }
    return lifecycle


def command_prepare(args, helpers):
    root = helpers.ROOT
    _check_all_whitespace(root)
    previous_binding = _active_binding(helpers)
    if args.role == "executor":
        active = previous_binding
        if active and active.get("lifecycle", {}).get("route") in {"FAST_MAINTENANCE", "COMPACT_MAINTENANCE"}:
            refreshed = _requirement_freshness(root, active["lifecycle"])
            if refreshed.get("freshness") == "STALE":
                label = ("FAST maintenance" if active["lifecycle"].get("route") == "FAST_MAINTENANCE"
                         else "compact maintenance")
                raise ValueError("stale " + label + " binding requires root rebuild")
    module = _load_change_verification(root)
    plan_value = module.build(args.base, args.input)
    configured_home = os.environ.get("FMONITOR_HARNESS_HOME")
    package_home = (Path(configured_home).expanduser().absolute() if configured_home
                    else helpers.evidence_home())
    package_dir = package_home / "packages" / (time.strftime("%Y%m%dT%H%M%SZ", time.gmtime()) + "-" + uuid.uuid4().hex[:10])
    package_dir.mkdir(parents=True, exist_ok=False)
    plan_path = package_dir / "verification-plan.json"
    plan_path.write_text(module.canonical(plan_value), encoding="utf-8")
    source = _source(helpers, root)
    executable_source = helpers.source_details().get("executable_digest", source)
    missing = sorted({test for item in plan_value["acceptances"] for test in item["tests"]
                      if not (root / test).is_file()})
    evidence = []
    preliminary_lifecycle = _fast_maintenance_lifecycle(
        root, plan_value, args.input, source, args.base, plan_path, evidence)
    evidence_gate = ("3" if args.role == "executor"
                     and preliminary_lifecycle["route"] in {"FAST_MAINTENANCE", "COMPACT_MAINTENANCE"}
                     else args.gate)
    expectations = _gate_expectations(plan_value, evidence_gate)
    mapped = set(expectations)
    plan_commands = {_normalized_argv(item["argv"]): item for item in plan_value["commands"]}
    acceptance_by_command = {}
    reachability = {}
    for acceptance in plan_value["acceptances"]:
        for test in acceptance.get("tests", []):
            command = next((_normalized_argv(item["argv"]) for item in plan_value["commands"]
                            if "acceptance mapping" in item.get("rationales", [item.get("rationale")])
                            and item["argv"][-1] == test), None)
            if command is not None:
                acceptance_by_command[command] = acceptance["acceptance_id"]
                declaration = acceptance.get("fixture_reachability", {}).get(test)
                if declaration is not None:
                    reachability[command] = declaration
    for value in getattr(args, "evidence", None) or []:
        evidence.append(_validate_evidence(Path(value).expanduser().resolve(), source, expectations,
                                           plan_commands, executable_source, reachability,
                                           acceptance_by_command))
    lifecycle = _fast_maintenance_lifecycle(root, plan_value, args.input, source, args.base,
                                            plan_path, evidence)
    if (args.role == "executor" and lifecycle["route"] in {"FAST_MAINTENANCE", "COMPACT_MAINTENANCE"}
            and lifecycle["executable_red"]["status"] != "INTENDED_RED"):
        if any(item.get("outcome") == "INTENDED_RED" for item in evidence):
            raise ValueError("maintenance intended RED evidence must cover declared executable regression")
        prefix = "compact maintenance" if lifecycle["route"] == "COMPACT_MAINTENANCE" else "FAST maintenance"
        raise ValueError(prefix + " executor requires intended RED evidence")
    covered = {_normalized_argv(item["argv"]) for item in evidence
               if item["outcome"] != "FIXTURE_REACHABLE"}
    if args.role == "reviewer" and mapped - covered:
        raise ValueError("reviewer evidence does not cover every mapped acceptance test")
    if args.role == "reviewer" and evidence_gate == "3":
        reached = {_normalized_argv(item["argv"]): item for item in evidence
                   if item["outcome"] == "FIXTURE_REACHABLE"}
        if set(reachability) - set(reached):
            raise ValueError("reviewer evidence lacks declared fixture reachability")
        ordinary = {_normalized_argv(item["argv"]): item for item in evidence
                    if item["outcome"] != "FIXTURE_REACHABLE"}
        for command, reached_item in reached.items():
            red_item = ordinary.get(command)
            if (red_item is None or red_item.get("outcome") != "INTENDED_RED"
                    or red_item.get("acceptance_id") != reached_item.get("acceptance_id")
                    or red_item.get("command_id") != reached_item.get("command_id")
                    or red_item.get("command_environment") != reached_item.get("command_environment")
                    or red_item.get("environment") != reached_item.get("environment")
                    or red_item.get("command_blob") != reached_item.get("command_blob")):
                raise ValueError("fixture reachability evidence does not match separate intended RED")
    if args.role == "reviewer" and (missing or not evidence):
        raise ValueError("reviewer package requires existing mapped tests and current Gate evidence")
    historical_red = getattr(args, "historical_red", None)
    test_delta = getattr(args, "test_delta", None)
    if (args.role == "reviewer" and args.gate == "3" and plan_value.get("version") == 1
            and "INTENDED_RED" not in expectations.values()):
        raise ValueError("Gate 3 requires at least one intended RED acceptance")
    if bool(historical_red) != bool(test_delta):
        raise ValueError("historical RED and test delta must be supplied together")
    lineage = None
    if historical_red:
        mapped_delta = next((key for key in expectations if key[-1] == test_delta), None)
        old = json.loads(Path(historical_red).read_text())
        current = next((item for item in evidence if _normalized_argv(item["argv"]) == mapped_delta), None)
        current_record = json.loads(Path(current["record"]).read_text()) if current else {}
        acceptance = next((item for item in plan_value["acceptances"] if test_delta in item["tests"]), None)
        if (mapped_delta is None or acceptance is None or current is None
                or old.get("outcome") != "INTENDED_RED"
                or _normalized_argv(old.get("argv", [])) != mapped_delta
                or old.get("acceptance_id") != acceptance["acceptance_id"]
                or current_record.get("acceptance_id") != acceptance["acceptance_id"]
                or old.get("command_id") != current_record.get("command_id")
                or old.get("purpose") != "acceptance" or current_record.get("purpose") != "acceptance"):
            raise ValueError("historical RED does not match test delta acceptance")
        base_blob = old.get("command_blob")
        current_blob = current_record.get("command_blob")
        if not all(isinstance(value, str) and re.fullmatch(r"[0-9a-f]{64}", value)
                   for value in (base_blob, current_blob)) or base_blob == current_blob:
            raise ValueError("test delta requires distinct exact test blobs")
        delta_sha256 = hashlib.sha256((base_blob + "\0" + current_blob).encode()).hexdigest()
        lineage = {"test": test_delta, "historical_red": str(Path(historical_red).resolve()),
                   "historical_source": old.get("source"), "current_executable_source": executable_source,
                   "acceptance_id": acceptance["acceptance_id"], "base_blob": base_blob,
                   "current_blob": current_blob, "delta_sha256": delta_sha256}
    workspaces = _dependency_workspaces(args, helpers, plan_value)
    if getattr(args, "previous", None) and not getattr(args, "findings", None):
        raise ValueError("previous snapshot requires findings")
    snapshot_path = package_dir / "snapshot"
    captured = _snapshot(root, snapshot_path)
    if _source(helpers, root) != source:
        raise ValueError("source changed while preparing review package")
    contracts = sorted({item["spec_path"] for item in plan_value["acceptances"]})
    sources = sorted({path for path in plan_value["paths"]["effective"] if (root / path).exists()})
    result = {"package_path": str(package_dir / "package.json"), "snapshot": str(snapshot_path),
              "plan": str(plan_path), "plan_sha256": hashlib.sha256(plan_path.read_bytes()).hexdigest(),
              "role": args.role, "approval": "NOT_REVIEWED", "missing_tests": missing,
              "contracts": contracts, "rules": [], "sources": sources, "evidence": evidence,
              "previous": getattr(args, "previous", None), "findings": getattr(args, "findings", None),
              "candidate_source": source, "executable_source": executable_source,
              "dependency_workspaces": workspaces, "test_delta_lineage": lineage,
              "lifecycle": lifecycle}
    result["local_obligations"] = [item for item in plan_value["commands"]
                                   if item.get("execution", "local") == "local"
                                   and item.get("phase") != "integration"]
    result["ci_obligations"] = plan_value.get("ci_obligations", [])
    manifest, delivered = build_task_context(root, plan_value, role=args.role, source=source,
        base=args.base, contracts=contracts, evidence=evidence, snapshot=str(snapshot_path))
    manifest_path = package_dir / "task-context-manifest.json"
    required_path = package_dir / "required-context.json"
    manifest_path.write_text(json.dumps(manifest, ensure_ascii=False, sort_keys=True, indent=2) + "\n", encoding="utf-8")
    required_path.write_text(json.dumps(delivered, ensure_ascii=False, sort_keys=True, indent=2) + "\n", encoding="utf-8")
    result.update(context_manifest=str(manifest_path),
        context_manifest_sha256=_sha256_bytes(manifest_path.read_bytes()),
        required_context_path=str(required_path), required_context_sha256=_sha256_bytes(required_path.read_bytes()),
        context_delivery={"mode": "task_context_manifest", "path": str(required_path)},
        context_metrics={"mandatory_bytes": sum(len(item["content"].encode("utf-8")) for item in delivered["items"]),
                         "mandatory_characters": sum(len(item["content"]) for item in delivered["items"]),
                         "whole_documents": sum(item["content_reference"]["start_byte"] == 0
                             and item["content_reference"]["end_byte"] == (root / item["source"]).stat().st_size
                             for item in manifest["required_context"]),
                         "load_on_demand_references": len(manifest["load_on_demand"]),
                         "token_usage": "UNKNOWN"})
    if result["previous"]:
        previous = Path(result["previous"]).expanduser().resolve()
        findings = Path(result["findings"]).expanduser().resolve()
        if not findings.is_file():
            raise ValueError("findings are unavailable")
        result["findings"] = str(findings)
        result["previous"] = str(previous)
        delta = package_dir / "delta.patch"
        _delta(root, previous, snapshot_path, delta)
        result["delta"] = str(delta)
    binding_path = _binding_path(helpers)
    result["review_results_location"] = str(binding_path)
    binding_path.parent.mkdir(parents=True, exist_ok=True)
    lock_path = binding_path.with_suffix(binding_path.suffix + ".lock")
    with lock_path.open("a+", encoding="utf-8") as lock:
        fcntl.flock(lock.fileno(), fcntl.LOCK_EX)
        latest_binding = _active_binding(helpers)
        review_results = (latest_binding or {}).get("review_results", [])
        result["review_results"] = review_results
        Path(result["package_path"]).write_text(
            json.dumps(result, ensure_ascii=False, sort_keys=True, indent=2) + "\n",
            encoding="utf-8")
        binding = {"repository": str(root), "worktree": _worktree_realpath(helpers),
                   "input": args.input, "base": args.base,
                   "source": source, "plan": str(plan_path), "package_path": result["package_path"],
                   "contracts": contracts, "prepared_at": _now(),
                   "context_manifest": str(manifest_path),
                   "context_manifest_sha256": result["context_manifest_sha256"],
                   "required_context_path": str(required_path),
                   "required_context_sha256": result["required_context_sha256"],
                   "review_results": review_results,
                   "review_results_location": str(binding_path),
                   "lifecycle": lifecycle}
        _atomic_json(binding_path, binding)
    _json(result)
    return 0


def _parse(argv):
    parser = argparse.ArgumentParser(description=__doc__)
    commands = parser.add_subparsers(dest="command", required=True)
    for name in ("admission", "state", "prepare-merge"):
        command = commands.add_parser(name)
        command.add_argument("--observation")
    wait = commands.add_parser("wait")
    wait.add_argument("--observation")
    wait.add_argument("--once", action="store_true")
    commands.add_parser("doctor")
    commands.add_parser("hook")
    install = commands.add_parser("install")
    install.add_argument("--config")
    prepare = commands.add_parser("prepare")
    prepare.add_argument("--input", required=True)
    prepare.add_argument("--base", required=True)
    prepare.add_argument("--role", required=True, choices=("root", "executor", "reviewer"))
    prepare.add_argument("--gate", choices=("3", "5"), default="5")
    prepare.add_argument("--previous")
    prepare.add_argument("--findings")
    prepare.add_argument("--evidence", action="append")
    prepare.add_argument("--historical-red")
    prepare.add_argument("--test-delta")
    prepare.add_argument("--dependency-workspace", action="append")
    review = commands.add_parser("record-review")
    review.add_argument("--gate", required=True, choices=("gate3", "final"))
    review.add_argument("--verdict", required=True,
                        choices=("APPROVED", "CHANGES_REQUESTED"))
    review.add_argument("--reviewer")
    review.add_argument("--author")
    return parser.parse_args(argv)


def main(args, helpers=None):
    helpers = _helpers(helpers)
    if isinstance(args, (list, tuple)):
        args = _parse(args)
    try:
        if args.command in {"admission", "state", "wait", "prepare-merge"}:
            if args.command == "admission" and not args.observation:
                raise ValueError("admission requires --observation")
            if args.observation:
                return command_admission(args, helpers)
            return command_state(args, helpers)
        if args.command == "hook":
            return command_hook(args, helpers)
        if args.command == "doctor":
            return command_doctor(args, helpers)
        if args.command == "prepare":
            return command_prepare(args, helpers)
        if args.command == "record-review":
            return command_record_review(args, helpers)
        if args.command == "install":
            return command_install(args, helpers)
        raise ValueError(f"unsupported context command: {args.command}")
    except (OSError, TypeError, ValueError, subprocess.SubprocessError) as error:
        print(f"SETUP_FAILURE: {error}", file=sys.stderr)
        return 1
