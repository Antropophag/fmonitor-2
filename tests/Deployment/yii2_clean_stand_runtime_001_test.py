#!/usr/bin/env python3
import pathlib, sys
sys.path.insert(0, str(pathlib.Path(__file__).resolve().parents[2]))
from tests.Support.clean_stand_contract import CleanStandFixture,result,IDS

f = CleanStandFixture()
try:
    p = result(f.run("run")); runtime = p["facts"]["runtime"]
    assert set(runtime["healthy"]) == {"php", "web", "jobs-worker", "jobs-scheduler"}
    assert runtime["live"] == 200 and runtime["ready"] == 200 and runtime["jobsHealth"]["ok"] is True
    assert set(runtime["heartbeats"]) == {"worker:pilot", "scheduler:pilot"}
    calls={x["call"]:x for x in f.trace()}; assert calls["health.live"]["httpStatus"]==200 and calls["health.ready"]["httpStatus"]==200
    assert calls["jobs.health"]["exit"]==0 and {x["workerId"] for x in calls["jobs.health"]["heartbeats"]}=={"worker:pilot","scheduler:pilot"}
    assert {x["containerId"] for x in calls["runtime.start"]["containers"]}=={IDS["containers"][n] for n in ("php","web","jobs-worker","jobs-scheduler")}
    bad = CleanStandFixture()
    try:
        bad.driver["observations"]["containers"]["jobs-scheduler"]["healthy"] = False; bad.sync()
        failed = bad.run("run"); assert failed.returncode != 0 and result(failed)["reason"] == "RUNTIME_NOT_READY"
        assert not (bad.evidence / "accepted.json").exists()
    finally: bad.close()
finally: f.close()
print("PASS: YII2-CLEAN-STAND-CUTOVER-001 runtime readiness")
