#!/usr/bin/env python3
import pathlib, sys
sys.path.insert(0, str(pathlib.Path(__file__).resolve().parents[2]))
from tests.Support.clean_stand_contract import CleanStandFixture, result

f = CleanStandFixture()
try:
    golden = result(f.run("run"))["facts"]["golden"]
    assert set(golden) == {"login-access", "fkr", "construction-control", "checklist", "otiz"}
    for name, fact in golden.items(): assert all(fact[k] is True for k in ("authorized","factAppended","unauthorizedRejected","rejectedFactsUnchanged")), name
    traces={x["call"]:x for x in f.trace()};
    for name in golden:
        row=traces["golden."+name]; assert row["method"] in {"GET","POST"} and row["path"].startswith("/pilot/") and row["requestId"]==name+"-request" and row["factId"]==name+"-fact" and row["status"]==200 and row["unauthorizedStatus"]==403 and row["unauthorizedProjectionBefore"]==row["unauthorizedProjectionAfter"]
    bad = CleanStandFixture()
    try:
        bad.driver["observations"]["golden"]["checklist"]["unauthorizedProjectionAfter"] = "q"*64; bad.sync()
        denied = bad.run("run"); assert denied.returncode != 0 and result(denied)["reason"] == "GOLDEN_FLOW_INVALID"
    finally: bad.close()
finally: f.close()
print("PASS: YII2-CLEAN-STAND-CUTOVER-001 golden flows")
