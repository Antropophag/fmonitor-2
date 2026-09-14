#!/usr/bin/env python3
import pathlib, sys
sys.path.insert(0, str(pathlib.Path(__file__).resolve().parents[2]))
from tests.Support.clean_stand_contract import CleanStandFixture, result

f = CleanStandFixture()
try:
    facts = result(f.run("run"))["facts"]; jobs = facts["jobs"]
    assert jobs["chain"] == ["enqueue", "claim", "complete", "outbox-attempt", "outbox-history"]
    assert jobs["stableRead"] is True and jobs["blockingRecovery"] == []
    assert facts["runtime"]["jobsHealth"]["counters"] == {"deadJobs": 0, "expiredLeases": 0, "overdueReady": 0}
    observed=next(x for x in f.trace() if x["call"]=="jobs.observe"); assert observed["tablePrefix"]=="fm2_"
    assert observed["tables"]=={"jobs":"fm2_fm2_jobs","jobHistory":"fm2_fm2_job_events","outbox":"fm2_fm2_outbox_intents","outboxHistory":"fm2_fm2_outbox_attempt_events","heartbeats":"fm2_fm2_worker_heartbeats"}
    assert len({observed[k] for k in ("jobId","leaseJobId","historyJobId")})==1 and len({observed[k] for k in ("outboxId","attemptOutboxId","historyOutboxId")})==1
    bad = CleanStandFixture()
    try:
        bad.driver["observations"]["jobs"]["historyOutboxId"] = "other-outbox"; bad.sync()
        failed = bad.run("run"); assert failed.returncode != 0 and result(failed)["reason"] == "JOBS_ACCEPTANCE_INVALID"
    finally: bad.close()
finally: f.close()
print("PASS: YII2-CLEAN-STAND-CUTOVER-001 jobs/outbox")
