"""Deliberately incomplete pre-implementation seam used only for RED branch reachability."""
def select_or_dispatch(github, admission, binding, polls, poll_interval):
    try:
        observations = []
        for _ in range(polls):
            observations = github.list_runs()
            if observations:
                break
        if observations:
            candidate = observations[0]
            if isinstance(candidate, dict) and candidate.get("status") == "completed":
                admission.evaluate(candidate)
            return {"decision": "NOT_IMPLEMENTED", "run": candidate if isinstance(candidate, dict) else {"id": None}}
        created = github.dispatch()
        if not isinstance(created, dict) or not created.get("id"):
            return {"decision": "NOT_IMPLEMENTED", "run": {"id": None}}
        observed = github.observe(created["id"])
        if observed.get("status") != "completed":
            observed = github.observe(created["id"])
        if observed.get("status") == "completed":
            admission.evaluate(observed)
        return {"decision": "NOT_IMPLEMENTED", "run": observed}
    except Exception:
        return {"decision": "NOT_IMPLEMENTED", "run": {"id": None}}

def correction_review_context(**kwargs):
    findings = kwargs.get("findings", [])
    if any(item.get("status") == "not-applicable" and not item.get("reason") for item in findings):
        raise ValueError("not-applicable finding requires reason")
    open_findings = [item for item in findings if item.get("status") == "open"]
    status = "RECONSIDER_OR_BLOCK" if open_findings and kwargs.get("return_count", 0) >= 2 else ("BLOCKED" if open_findings else "READY")
    return {"full_candidate": kwargs.get("full_candidate"), "last_reviewed_source": kwargs.get("last_reviewed_source"),
            "candidate_delta": kwargs.get("delta"), "finding_dispositions": findings,
            "suggestions": kwargs.get("suggestions", []), "new_risks": kwargs.get("new_risks", []), "status": status}

def requires_repeat_code_review(before, after):
    return False
