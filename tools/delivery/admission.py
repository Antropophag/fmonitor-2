#!/usr/bin/env python3
"""Strict, side-effect-free delivery admission evaluation."""

from collections import Counter
import hashlib
import importlib.util
import json
import re


WORKFLOW = ".github/workflows/quality-graph.yml"


def expected_jobs(root, mode):
    path = root / "tools/verification/ci.py"
    spec = importlib.util.spec_from_file_location("fmonitor_verification_ci_policy", path)
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module.expected_job_conclusions(mode)


def policy_digest(root):
    return hashlib.sha256((root / ".quality-graph/verification-policy.json").read_bytes()).hexdigest()


def _failure_codes(preflight):
    result = []
    for failure in preflight.get("failures", []) if isinstance(preflight, dict) else []:
        if isinstance(failure, dict) and isinstance(failure.get("code"), str):
            result.append(failure["code"])
    return result


def evaluate(observation, root):
    reasons = []
    binding = observation.get("binding") if isinstance(observation, dict) else None
    binding = binding if isinstance(binding, dict) else {}
    required_fields = ("repository", "pr", "head", "base", "candidate", "mode",
                       "policy_digest", "workflow", "run_id", "attempt")
    for field in required_fields:
        if binding.get(field) in (None, "", "UNKNOWN"):
            reasons.append("binding_missing:" + field)
    valid_binding = (isinstance(binding.get("repository"), str) and bool(binding.get("repository"))
                     and isinstance(binding.get("pr"), int) and not isinstance(binding.get("pr"), bool) and binding["pr"] > 0
                     and isinstance(binding.get("head"), str) and bool(re.fullmatch(r"[0-9a-f]{40}", binding["head"]))
                     and isinstance(binding.get("base"), str) and bool(re.fullmatch(r"[0-9a-f]{40}", binding["base"]))
                     and isinstance(binding.get("candidate"), str) and bool(re.fullmatch(r"[0-9a-f]{64}", binding["candidate"]))
                     and isinstance(binding.get("run_id"), int) and not isinstance(binding.get("run_id"), bool) and binding["run_id"] > 0
                     and isinstance(binding.get("attempt"), int) and not isinstance(binding.get("attempt"), bool) and binding["attempt"] > 0)
    if not valid_binding:
        reasons.append("binding_invalid_identity")
    mode = binding.get("mode")
    try:
        expected = expected_jobs(root, mode)
    except (OSError, TypeError, ValueError, AttributeError):
        expected = {}
        reasons.append("unsupported_mode")
    if binding.get("policy_digest") != policy_digest(root):
        reasons.append("policy_digest_mismatch")
    if binding.get("workflow") != WORKFLOW:
        reasons.append("workflow_mismatch")

    def exact(label, value):
        if not isinstance(value, dict) or value != binding:
            reasons.append(label + "_binding_mismatch")
            return False
        return True

    ci = observation.get("ci") if isinstance(observation.get("ci"), dict) else {}
    ci_bound = exact("ci", ci.get("binding"))
    jobs = ci.get("jobs") if isinstance(ci.get("jobs"), list) else []
    names = [job.get("name") for job in jobs if isinstance(job, dict)]
    duplicates = sorted(name for name, count in Counter(names).items() if count > 1)
    if duplicates:
        reasons.append("duplicate_ci_jobs:" + ",".join(str(x) for x in duplicates))
    job_map = {job.get("name"): job for job in jobs if isinstance(job, dict)}
    ci_status = "SUCCESS"
    missing = set(expected) - set(names)
    if not valid_binding or not ci_bound or duplicates or missing:
        ci_status = "UNKNOWN"
        if missing:
            reasons.append("ci_job_inventory_mismatch")
    for name, wanted in expected.items():
        job = job_map.get(name)
        if job is None:
            continue
        if not exact("job:" + name, job.get("binding")):
            ci_status = "UNKNOWN"
        status = str(job.get("status") or "").upper()
        conclusion = str(job.get("conclusion") or "").upper()
        if status != "COMPLETED":
            ci_status = "PENDING" if ci_status == "SUCCESS" else ci_status
            reasons.append("ci_job_pending:" + name)
        elif conclusion != wanted:
            if ci_status == "SUCCESS":
                ci_status = "FAILURE"
            reasons.append("ci_job_conclusion:" + name + ":expected_" + wanted.lower())

    preflight = observation.get("preflight") if isinstance(observation.get("preflight"), dict) else {}
    preflight_bound = exact("preflight", preflight.get("binding"))
    failures = _failure_codes(preflight)
    original_failures = list(preflight.get("failures", [])) if isinstance(preflight.get("failures"), list) else []
    preflight_green = preflight_bound and preflight.get("outcome") == "GREEN" and not failures
    exception = observation.get("owner_exception")
    exception_valid = False
    if isinstance(exception, dict):
        exception_valid = (exception.get("actor") == "owner" and exception.get("action") == "merge"
                           and exception.get("head") == binding.get("head")
                           and exception.get("policy_digest") == binding.get("policy_digest")
                           and isinstance(exception.get("reasons"), list) and bool(exception["reasons"])
                           and set(exception["reasons"]) == set(failures) and bool(failures))
    if not preflight_green and not exception_valid:
        reasons.append("preflight_not_green")

    reviews = observation.get("reviews") if isinstance(observation.get("reviews"), list) else []
    reviews_ok = True
    by_gate = {}
    for review in reviews:
        if not isinstance(review, dict) or review.get("gate") in by_gate:
            reviews_ok = False
            continue
        by_gate[review.get("gate")] = review
    for gate in (3, 5):
        review = by_gate.get(gate)
        if (not review or not exact("review:" + str(gate), review.get("binding"))
                or review.get("verdict") != "APPROVED"
                or not review.get("reviewer") or review.get("reviewer") == review.get("author")):
            reviews_ok = False
    if not reviews_ok:
        reasons.append("reviews_not_approved")

    current = observation.get("current") if isinstance(observation.get("current"), dict) else {}
    unchanged = current.get("head") == binding.get("head") and current.get("base") == binding.get("base")
    if not unchanged:
        reasons.append("head_or_base_changed")
    publication_ready = (not any(reason.startswith(("binding_", "policy_", "workflow_", "preflight_", "review:"))
                                 for reason in reasons)
                         and "unsupported_mode" not in reasons and "reviews_not_approved" not in reasons
                         and preflight_green and reviews_ok)
    merge_ready = (reviews_ok and (preflight_green or exception_valid)
                   and ci_status == "SUCCESS" and unchanged
                   and not any(reason.startswith(("binding_", "policy_", "workflow_", "review:"))
                               for reason in reasons)
                   and "unsupported_mode" not in reasons and "reviews_not_approved" not in reasons)

    observed_enforcement = observation.get("enforcement")
    enforcement = (observed_enforcement if isinstance(observed_enforcement, str) and observed_enforcement in
                   {"ENFORCEMENT_CONFIGURED", "ENFORCEMENT_NOT_CONFIGURED"}
                   else "ENFORCEMENT_NOT_CONFIGURED")
    authorization = observation.get("authorization")
    action_authorized = False
    if isinstance(authorization, dict):
        scoped = (authorization.get("actor") == "owner" and authorization.get("action") == "merge"
                  and authorization.get("head") == binding.get("head")
                  and authorization.get("policy_digest") == binding.get("policy_digest"))
        if authorization.get("mode") == "owner-controlled":
            action_authorized = scoped
        elif authorization.get("mode") == "autonomous":
            action_authorized = scoped and enforcement == "ENFORCEMENT_CONFIGURED"
    return {"ci": {"status": ci_status}, "publication_ready": publication_ready,
            "merge_ready": merge_ready, "action_authorized": action_authorized,
            "enforcement": enforcement, "reasons": sorted(set(reasons)),
            "original_failures": original_failures, "replay": bool(observation.get("replay", False))}
